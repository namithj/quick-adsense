<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped, WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand -- Test harness: exceptions are developer-facing, globals are reset on purpose, and the fixed seed is the point.
/**
 * Runs one user configuration through the plugin and collects its snapshots.
 *
 * The runner produces text; it does not compare it. UserConfigRegressionTest
 * compares every produced file with tests/user-configs/snapshots/. Semantic
 * checks (idempotent init, retained ad code, widget registry, ...) are recorded
 * as failure messages so that one run reports everything that broke.
 *
 * Order inside one config run (see R00 section 5.1):
 *   1. clean state, write the config's options and widget placements;
 *   2. the plugin's init (migration), then widgets_init;
 *   3. every front-end scenario, all before any admin step because admin_init
 *      attaches the settings sanitizer to every later update_option call (C-2.3.3);
 *   4. the admin steps.
 */
class QA_User_Config_Runner {

	const SEED     = 20261112;
	const FORM_ID  = 'quick_adsense_settings_form';
	const OPT_FLAG = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;

	const USER_AGENTS = [
		'chrome'    => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
		'iphone'    => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
		'ipad'      => 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
		'googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
		'curl'      => 'curl/8.5.0',
	];

	const REFERERS = [
		'google' => 'https://www.google.com/search?q=x',
		'bing'   => 'https://www.bing.com/',
		'other'  => 'https://news.example.net/',
	];

	/**
	 * Addresses that the bundled GeoIP data resolves to a known country.
	 * Picked at baseline time; config 14 asserts the resolved codes.
	 */
	const GEO_IPS = [
		'us'      => '8.8.8.8',
		'in'      => '1.6.0.1',
		'de'      => '103.252.88.1',
		'unknown' => '192.168.1.1',
	];

	/**
	 * Test case, used for go_to() and the factories.
	 *
	 * @var WP_UnitTestCase
	 */
	private $test;

	/**
	 * Saved copy of the global state this runner changes.
	 *
	 * @var array
	 */
	private $globals_backup;

	/**
	 * Loaded config.
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Current variant name ('' for the base config).
	 *
	 * @var string
	 */
	private $variant = '';

	/**
	 * Fixture post name => post ID.
	 *
	 * @var array<string, int>
	 */
	private $posts = [];

	/**
	 * Term IDs used by the archive scenarios.
	 *
	 * @var array<string, int>
	 */
	private $terms = [];

	/**
	 * Actor name => user ID.
	 *
	 * @var array<string, int>
	 */
	private $users = [];

	/**
	 * Notice recorder for the current capture.
	 *
	 * @var QA_Notice_Recorder
	 */
	private $recorder;

	/**
	 * Failure messages from semantic checks.
	 *
	 * @var string[]
	 */
	private $failures = [];

	/**
	 * Produced files: path relative to the snapshots directory => contents.
	 *
	 * @var array<string, string>
	 */
	private $outputs = [];

	/**
	 * Number of semantic checks made.
	 *
	 * @var int
	 */
	private $checks = 0;

	/**
	 * Settings as stored after init and the config's post-init changes.
	 *
	 * @var mixed
	 */
	private $settings;

	/**
	 * Stats options as the config wrote them: option name => value.
	 *
	 * @var array<string, mixed>
	 */
	private $initial_stats = [];

	/**
	 * Map of "dmY" strings to "D-n" labels, today being D-0.
	 *
	 * @var array<string, string>
	 */
	private $day_labels = [];

	/**
	 * Constructor.
	 *
	 * @param WP_UnitTestCase $test           The running test case.
	 * @param array           $globals_backup Result of backup_globals(), taken before the test.
	 */
	public function __construct( $test, array $globals_backup ) {
		$this->test           = $test;
		$this->globals_backup = $globals_backup;
		$this->recorder       = new QA_Notice_Recorder();
	}

	/**
	 * Names of the global variables that the runner changes and restores.
	 *
	 * @return string[]
	 */
	private static function global_names() {
		return [
			'wp_settings_sections',
			'wp_settings_fields',
			'wp_registered_settings',
			'new_allowed_options',
			'allowed_options',
			'menu',
			'submenu',
			'admin_page_hooks',
			'_registered_pages',
			'_parent_pages',
			'wp_scripts',
			'wp_styles',
			'wp_registered_widgets',
			'wp_registered_widget_controls',
			'wp_registered_widget_updates',
			'wp_registered_sidebars',
			'_wp_sidebars_widgets',
			'sidebars_widgets',
			'current_screen',
			'quick_adsense_ads_displayed',
			'quick_adsense_ads_id',
			'quick_adsense_begin_end',
		];
	}

	/**
	 * Save the global state that a config run changes.
	 *
	 * @return array
	 */
	public static function backup_globals() {
		global $wp_widget_factory;
		$backup = [ 'globals' => [] ];
		foreach ( self::global_names() as $name ) {
			$backup['globals'][ $name ] = array_key_exists( $name, $GLOBALS ) ? [ true, $GLOBALS[ $name ] ] : [ false, null ];
		}
		$backup['widgets'] = isset( $wp_widget_factory->widgets ) ? $wp_widget_factory->widgets : [];
		return $backup;
	}

	/**
	 * Put the global state back.
	 *
	 * @param array $backup Result of backup_globals().
	 */
	public static function restore_globals( array $backup ) {
		global $wp_widget_factory;
		foreach ( $backup['globals'] as $name => $entry ) {
			if ( $entry[0] ) {
				$GLOBALS[ $name ] = $entry[1];
			} else {
				unset( $GLOBALS[ $name ] );
			}
		}
		if ( isset( $wp_widget_factory ) ) {
			$wp_widget_factory->widgets = $backup['widgets'];
		}
	}

	/**
	 * JSON for a value with object keys sorted, so key order never matters.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function canonical_json( $value ) {
		return json_encode( self::sort_keys( $value ), self::OPT_FLAG ) . "\n"; // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Stable bytes are required; wp_json_encode may differ between versions.
	}

	/**
	 * Sort associative arrays by key, recursively. Lists keep their order.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private static function sort_keys( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::sort_keys( $item );
		}
		if ( array_values( $value ) !== $value ) {
			ksort( $value, SORT_STRING );
		}
		return $value;
	}

	/* ------------------------------------------------------------------ */
	/* Entry point                                                         */
	/* ------------------------------------------------------------------ */

	/**
	 * Run a config (the base, then each variant).
	 *
	 * @param array $config Loaded config.
	 * @return array{outputs:array<string,string>,failures:string[],checks:int}
	 */
	public function run( array $config ) {
		$this->config   = $config;
		$this->outputs  = [];
		$this->failures = [];
		$this->checks   = 0;

		$this->day_labels = [];
		for ( $n = 45; $n >= 0; $n-- ) {
			$this->day_labels[ gmdate( 'dmY', strtotime( '-' . $n . ' day' ) ) ] = 'D-' . $n;
		}

		$this->create_fixtures();

		$this->run_matrix( '' );
		foreach ( array_keys( $config['variants'] ) as $variant ) {
			$this->run_matrix( $variant );
		}

		return [
			'outputs'  => $this->outputs,
			'failures' => $this->failures,
			'checks'   => $this->checks,
		];
	}

	/* ------------------------------------------------------------------ */
	/* Small helpers                                                       */
	/* ------------------------------------------------------------------ */

	/**
	 * Record a produced file.
	 *
	 * @param string $name Path relative to the config's (variant) directory.
	 * @param string $text Contents.
	 */
	private function emit( $name, $text ) {
		$dir                                 = $this->config['slug'] . ( '' === $this->variant ? '' : '/' . $this->variant );
		$this->outputs[ $dir . '/' . $name ] = $text;
	}

	/**
	 * Record a file that is shared by all configs (snapshots/_global/).
	 *
	 * Every config must produce the same text; a difference is a failure.
	 *
	 * @param string $name File name.
	 * @param string $text Contents.
	 */
	private function emit_global( $name, $text ) {
		$path = '_global/' . $name;
		if ( isset( $this->outputs[ $path ] ) && $this->outputs[ $path ] !== $text ) {
			$this->fail( 'Output for ' . $path . " differs between runs of this config:\n" . QA_Snapshot_Store::diff( $this->outputs[ $path ], $text ) );
			return;
		}
		$this->outputs[ $path ] = $text;
	}

	/**
	 * Prefix a failure message with the config and variant.
	 *
	 * @param string $message Message.
	 */
	private function fail( $message ) {
		$this->failures[] = '[' . $this->config['slug'] . ( '' === $this->variant ? '' : '/' . $this->variant ) . '] ' . $message;
	}

	/**
	 * Check that two values are identical, comparing their JSON form.
	 *
	 * @param mixed  $expected Expected value.
	 * @param mixed  $actual   Actual value.
	 * @param string $label    What is being checked.
	 */
	private function expect_same( $expected, $actual, $label ) {
		++$this->checks;
		$expected_text = is_string( $expected ) ? $expected : self::canonical_json( $expected );
		$actual_text   = is_string( $actual ) ? $actual : self::canonical_json( $actual );
		if ( $expected_text !== $actual_text ) {
			$this->fail( $label . "\n" . QA_Snapshot_Store::diff( $expected_text, $actual_text ) );
		}
	}

	/**
	 * Check a condition.
	 *
	 * @param bool   $condition Must be true.
	 * @param string $label     Failure message.
	 */
	private function expect_true( $condition, $label ) {
		++$this->checks;
		if ( ! $condition ) {
			$this->fail( $label );
		}
	}

	/**
	 * Build a snapshot file from labelled sections.
	 *
	 * @param array<string, string> $sections Section name => body.
	 * @return string
	 */
	private static function sections( array $sections ) {
		$text = '';
		foreach ( $sections as $name => $body ) {
			$text .= '== ' . $name . " ==\n" . $body . "\n";
		}
		return $text;
	}

	/**
	 * Format recorded notices for a "== notices ==" section.
	 *
	 * @param string[] $notices Entries.
	 * @return string
	 */
	private static function notice_text( array $notices ) {
		return $notices ? implode( "\n", $notices ) : '(none)';
	}

	/* ------------------------------------------------------------------ */
	/* Fixtures                                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * Create the users, terms and posts that every scenario uses.
	 *
	 * Posts get fixed IDs so permalinks are stable. They are created by an
	 * administrator so that KSES filters do not rewrite the stored bodies.
	 */
	private function create_fixtures() {
		$factory = $this->test->qa_factory();

		$this->users['admin']      = $factory->user->create( [ 'role' => 'administrator' ] );
		$this->users['editor']     = $factory->user->create( [ 'role' => 'editor' ] );
		$this->users['subscriber'] = $factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $this->users['admin'] );

		update_option( 'posts_per_page', 3 );
		update_option( 'posts_per_rss', 3 );

		$this->terms['category'] = $factory->category->create( [ 'name' => 'QAFX Category' ] );
		$this->terms['tag']      = $factory->tag->create( [ 'name' => 'QAFX Tag' ] );

		$index = 0;
		foreach ( QA_User_Config_Loader::post_names() as $name ) {
			++$index;
			$id                   = 1000 + $index;
			$this->posts[ $name ] = $this->insert_post( $id, 'post', 'QAFX ' . $name, QA_User_Config_Loader::post_body( $name, $id ), '2026-01-' . sprintf( '%02d', $index ) . ' 12:00:00' );
		}

		// A page, and three newest posts for the home, archive and feed loops.
		$this->posts['page'] = $this->insert_post( 1100, 'page', 'QAFX page', QA_User_Config_Loader::post_body( 'long', 1100 ), '2026-02-01 12:00:00' );
		$trio                = [
			'home-long'   => [ 1101, 'long', '2026-03-03 12:00:00' ],
			'home-images' => [ 1102, 'images', '2026-03-02 12:00:00' ],
			'home-short'  => [ 1103, 'short', '2026-03-01 12:00:00' ],
		];
		foreach ( $trio as $name => $row ) {
			$this->posts[ $name ] = $this->insert_post( $row[0], 'post', 'QAFX qatrio ' . $name, QA_User_Config_Loader::post_body( $row[1], $row[0] ), $row[2] );
			wp_set_object_terms( $this->posts[ $name ], [ $this->terms['category'] ], 'category' );
			wp_set_object_terms( $this->posts[ $name ], [ $this->terms['tag'] ], 'post_tag' );
		}

		wp_set_current_user( 0 );
	}

	/**
	 * Insert one post and verify that its stored body is byte-identical.
	 *
	 * @param int    $id    Fixed post ID.
	 * @param string $type  Post type.
	 * @param string $title Title.
	 * @param string $body  Body.
	 * @param string $date  Local and GMT date (the test site is UTC).
	 * @return int
	 */
	private function insert_post( $id, $type, $title, $body, $date ) {
		$inserted = wp_insert_post(
			wp_slash(
				[
					'import_id'     => $id,
					'post_type'     => $type,
					'post_status'   => 'publish',
					'post_title'    => $title,
					'post_name'     => sanitize_title( $title ),
					'post_content'  => $body,
					'post_date'     => $date,
					'post_date_gmt' => $date,
				]
			),
			true
		);
		if ( is_wp_error( $inserted ) ) {
			throw new RuntimeException( 'Could not create fixture post ' . $title . ': ' . $inserted->get_error_message() );
		}
		$stored = get_post_field( 'post_content', $inserted, 'raw' );
		if ( $stored !== $body ) {
			throw new RuntimeException( 'Fixture post ' . $title . ' was altered while being saved; the fixtures must be stored byte-for-byte.' );
		}
		return (int) $inserted;
	}

	/* ------------------------------------------------------------------ */
	/* State                                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * Return the process to a clean state before applying a config (R00 5.1).
	 */
	private function reset_state() {
		self::restore_globals( $this->globals_backup );
		remove_all_filters( 'sanitize_option_quick_adsense_settings' );
		QA_User_Config_Loader::clear_plugin_options();
		// On the front end core caches the sidebar layout in a global.
		$GLOBALS['_wp_sidebars_widgets'] = null;
		$this->reset_engine();
		$this->reset_server();
		wp_set_current_user( 0 );
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		wp_cache_flush();
	}

	/**
	 * Reset the three content-engine globals, as a fresh request would.
	 *
	 * The plugin is loaded inside a function by the test bootstrap, so its own
	 * file-level initialisation does not create real globals.
	 */
	private function reset_engine() {
		$GLOBALS['quick_adsense_ads_displayed'] = 0;
		$GLOBALS['quick_adsense_ads_id']        = [];
		$GLOBALS['quick_adsense_begin_end']     = 0;
	}

	/**
	 * Reset the request headers the plugin reads.
	 *
	 * @param array $spec Optional overrides: ua, referer, ip (keys of the lookup tables).
	 */
	private function reset_server( array $spec = [] ) {
		$_SERVER['HTTP_USER_AGENT'] = self::USER_AGENTS[ $spec['ua'] ?? 'chrome' ];
		unset( $_SERVER['HTTP_REFERER'], $_SERVER['REMOTE_ADDR'] );
		if ( ! empty( $spec['referer'] ) ) {
			$_SERVER['HTTP_REFERER'] = self::REFERERS[ $spec['referer'] ];
		}
		if ( ! empty( $spec['ip'] ) ) {
			$_SERVER['REMOTE_ADDR'] = self::GEO_IPS[ $spec['ip'] ];
		}
	}

	/**
	 * Put the stats options back to what the config wrote.
	 */
	private function reset_stats() {
		for ( $i = 1; $i <= 10; $i++ ) {
			delete_option( 'quick_adsense_onpost_ad_' . $i . '_stats' );
		}
		foreach ( $this->initial_stats as $name => $value ) {
			QA_User_Config_Loader::write_option( $name, $value );
		}
	}

	/**
	 * Describe what a render changed: engine globals, settings and stats.
	 *
	 * @return string
	 */
	private function state_text() {
		$stats = [];
		for ( $i = 1; $i <= 10; $i++ ) {
			$value = get_option( 'quick_adsense_onpost_ad_' . $i . '_stats' );
			if ( false === $value ) {
				continue;
			}
			if ( is_array( $value ) ) {
				// Keys become "D-n" labels, today being D-0. The plugin trims with
				// array_shift(), which renumbers numeric keys (every date whose day
				// is 10 or more), so after a trim the keys are no longer dates. Which
				// keys survive depends on the calendar, so only the ordered entries
				// are recorded in that case, never the keys.
				$labelled  = [];
				$all_dates = true;
				foreach ( $value as $key => $entry ) {
					if ( isset( $this->day_labels[ (string) $key ] ) ) {
						$labelled[ $this->day_labels[ (string) $key ] ] = $entry;
					} else {
						$all_dates = false;
					}
				}
				$value = $all_dates ? $labelled : [
					'keys'    => 'not dmY dates any more (array_shift renumbers numeric keys)',
					'entries' => array_values( $value ),
				];
			}
			$stats[ 'ad_' . $i ] = $value;
		}
		$engine = [
			'displayed' => $GLOBALS['quick_adsense_ads_displayed'] ?? null,
			'ids'       => $GLOBALS['quick_adsense_ads_id'] ?? null,
			'begin_end' => $GLOBALS['quick_adsense_begin_end'] ?? null,
		];
		return 'engine: ' . wp_json_encode( $engine ) . "\n"
			. 'settings: ' . ( get_option( 'quick_adsense_settings' ) === $this->settings ? 'unchanged' : 'CHANGED' ) . "\n"
			. 'stats: ' . ( $stats ? json_encode( $stats, JSON_UNESCAPED_SLASHES ) : '(none)' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}

	/* ------------------------------------------------------------------ */
	/* One config run                                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * Run the whole matrix for the base config or one variant.
	 *
	 * @param string $variant Variant name, or '' for the base config.
	 */
	private function run_matrix( $variant ) {
		$this->variant = $variant;
		$config        = $this->config;
		$options       = QA_User_Config_Loader::effective_options( $config, $variant );

		$this->reset_state();
		$this->register_test_sidebars();
		QA_User_Config_Loader::apply( $config, $variant );

		$this->run_init( $options );
		QA_User_Config_Loader::apply_post_init( $config );
		$this->settings = get_option( 'quick_adsense_settings' );

		$this->initial_stats = [];
		for ( $i = 1; $i <= 10; $i++ ) {
			$value = get_option( 'quick_adsense_onpost_ad_' . $i . '_stats' );
			if ( false !== $value ) {
				$this->initial_stats[ 'quick_adsense_onpost_ad_' . $i . '_stats' ] = $value;
			}
		}

		$sidebars_before = get_option( 'sidebars_widgets' );
		$this->run_widgets_init();

		$this->run_front_end();

		$this->run_admin( $sidebars_before );

		if ( '' === $variant ) {
			$this->check_geo_expectations();
		}
	}

	/**
	 * Assert that the bundled GeoIP data still resolves the scenario addresses
	 * to the countries the config recorded at baseline time.
	 */
	private function check_geo_expectations() {
		if ( empty( $this->config['geo_expect'] ) ) {
			return;
		}
		foreach ( $this->config['geo_expect'] as $key => $country ) {
			$resolved = null;
			$this->recorder->start();
			try {
				$geo      = new \iriven\GeoIPCountry();
				$resolved = $geo->resolve( self::GEO_IPS[ $key ] );
			} catch ( Throwable $throwable ) {
				$this->recorder->add_throwable( $throwable );
			}
			$this->recorder->stop();
			$this->expect_same( $country, $resolved, 'The bundled GeoIP data resolves ' . self::GEO_IPS[ $key ] . ' to a different country than recorded at baseline.' );
		}
	}

	/**
	 * Register the two sidebars that assigned AdsWidgets are placed in.
	 */
	private function register_test_sidebars() {
		register_sidebar(
			[
				'id'            => 'qa-sidebar-1',
				'name'          => 'QA Sidebar 1',
				'before_widget' => '<section id="%1$s" class="widget %2$s" aria-label="Ads">',
				'after_widget'  => '</section>',
				'before_title'  => '',
				'after_title'   => '',
			]
		);
		register_sidebar(
			[
				'id'            => 'qa-sidebar-2',
				'name'          => 'QA Sidebar 2',
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '',
				'after_title'   => '',
			]
		);
	}

	/**
	 * Run the plugin's init callbacks (the legacy migration) and check the result.
	 *
	 * @param array $options The options this run wrote.
	 */
	private function run_init( array $options ) {
		$config  = $this->config;
		$notices = [];
		$pre     = get_option( 'quick_adsense_settings' );

		$this->recorder->start();
		try {
			QA_Plugin_Hook_Capture::run( 'init' );
		} catch ( Throwable $throwable ) {
			$this->recorder->add_throwable( $throwable );
		}
		$notices = $this->recorder->stop();

		// R01: an upgrade from 2.9.4 (no plugin_version option) backs up a prior settings array, and nothing else.
		$backups = \QuickAdsense\Snapshots::all();
		$this->expect_same( is_array( $pre ) ? 1 : 0, count( $backups ), 'Wrong number of backups after the first init.' );
		if ( is_array( $pre ) && 1 === count( $backups ) ) {
			$backup = reset( $backups );
			$this->expect_same( serialize( $pre ), serialize( $backup['settings'] ), 'The upgrade backup does not hold the pre-init settings byte for byte.' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Byte comparison.
			$this->expect_same( 'upgrade-from-pre-2.10', $backup['reason'], 'Wrong reason on the upgrade backup.' );
		}
		$this->expect_same( 1, get_option( 'quick_adsense_db_version' ), 'quick_adsense_db_version should be 1 after init.' );
		$this->expect_same( QUICK_ADSENSE_VERSION, get_option( 'quick_adsense_plugin_version' ), 'quick_adsense_plugin_version should be recorded by init.' );

		$after = get_option( 'quick_adsense_settings' );
		$this->emit( 'settings-after-init.json', self::canonical_json( $after ) );
		$this->init_notices = $notices;

		// Running init again must change nothing (idempotent).
		$this->recorder->start();
		try {
			QA_Plugin_Hook_Capture::run( 'init' );
		} catch ( Throwable $throwable ) {
			$this->recorder->add_throwable( $throwable );
		}
		$this->recorder->stop();
		$this->expect_same( $after, get_option( 'quick_adsense_settings' ), 'Running init a second time changed quick_adsense_settings.' );
		$this->expect_same( count( $backups ), count( \QuickAdsense\Snapshots::all() ), 'Running init a second time created another backup.' );

		// Non-legacy configs: init leaves an array untouched; a missing or
		// non-array value becomes the defaults (C-7.4).
		$input = $options['quick_adsense_settings'] ?? null;
		if ( in_array( $config['shape'], [ 'saved', 'corrupt', 'defaults' ], true ) ) {
			if ( is_array( $input ) ) {
				$this->expect_same( $input, $after, 'init changed a settings array that already existed (C-7.1).' );
			} else {
				$this->expect_same( quick_adsense_get_defaults(), $after, 'init did not create exactly quick_adsense_get_defaults() (C-7.4).' );
			}
		} else {
			$this->expect_true( is_array( $after ), 'Legacy migration did not produce a settings array.' );
		}

		// Legacy, backup, stats and notice options must still exist, except those init deletes (C-7).
		foreach ( $options as $name => $value ) {
			if ( 'quick_adsense_settings' === $name ) {
				continue;
			}
			if ( in_array( $name, $config['deleted_by_init'], true ) ) {
				$this->expect_same( false, get_option( $name ), 'Option ' . $name . ' should have been deleted by init.' );
			} else {
				$this->expect_same( $value, get_option( $name ), 'Option ' . $name . ' should be unchanged by init.' );
			}
		}
		if ( 'legacy-v2' === $config['shape'] ) {
			$this->expect_same( $options['quick_adsense_2_options'], get_option( 'quick_adsense_2_options_bak' ), 'quick_adsense_2_options_bak must equal the v2.0 options (C-1).' );
			$this->expect_same( false, get_option( 'quick_adsense_2_options' ), 'quick_adsense_2_options must be deleted after the migration (C-7.2).' );
		}
	}

	/**
	 * Notices recorded while init ran (kept for admin-steps.txt).
	 *
	 * @var string[]
	 */
	private $init_notices = [];

	/**
	 * Run the plugin's widgets_init callbacks and register the new widgets.
	 *
	 * Registered after the config's sidebars_widgets and instance options are
	 * written, as on a real request, so assigned instances come alive.
	 */
	private function run_widgets_init() {
		global $wp_widget_factory;
		$before = array_keys( $wp_widget_factory->widgets );

		$this->recorder->start();
		try {
			QA_Plugin_Hook_Capture::run( 'widgets_init' );
			foreach ( $wp_widget_factory->widgets as $key => $widget ) {
				if ( ! in_array( $key, $before, true ) && $widget instanceof QuickAdsenseAdWidget ) {
					$widget->_register();
				}
			}
		} catch ( Throwable $throwable ) {
			$this->recorder->add_throwable( $throwable );
		}
		$this->widget_notices = $this->recorder->stop();
	}

	/**
	 * Notices recorded while widgets_init ran.
	 *
	 * @var string[]
	 */
	private $widget_notices = [];

	/* ------------------------------------------------------------------ */
	/* Front end                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Build the scenario list: id => spec.
	 *
	 * @return array<string, array>
	 */
	private function scenarios() {
		$s = [];

		$s['single-long'] = [ 'target' => 'single:long' ];
		$variants         = [
			'short'           => 'short',
			'two-paras'       => 'two-paras',
			'upper-p'         => 'upper-p',
			'empty-paras'     => 'empty-paras',
			'images'          => 'images',
			'more'            => 'more',
			'blocks'          => 'blocks',
			'classic-markers' => 'classic-block-markers',
		];
		foreach ( $variants as $id => $fixture ) {
			$s[ 'single-' . $id ] = [ 'target' => 'single:' . $fixture ];
		}
		foreach ( QA_User_Config_Loader::post_names() as $name ) {
			if ( 0 === strpos( $name, 'm-' ) ) {
				$s[ 'marker-' . substr( $name, 2 ) ] = [ 'target' => 'single:' . $name ];
			}
		}

		$s['page']         = [ 'target' => 'page' ];
		$s['home']         = [ 'target' => 'home' ];
		$s['category']     = [ 'target' => 'category' ];
		$s['tag']          = [ 'target' => 'tag' ];
		$s['date-archive'] = [ 'target' => 'date' ];
		$s['search']       = [ 'target' => 'search' ];
		$s['feed']         = [ 'target' => 'feed' ];

		$s['logged-in-subscriber'] = [
			'target' => 'single:long',
			'user'   => 'subscriber',
		];
		$s['logged-in-admin']      = [
			'target' => 'single:long',
			'user'   => 'admin',
		];
		foreach ( [ 'iphone', 'ipad', 'googlebot', 'curl' ] as $ua ) {
			$s[ 'ua-' . $ua ] = [
				'target' => 'single:long',
				'ua'     => $ua,
			];
		}
		foreach ( [ 'google', 'bing', 'other' ] as $ref ) {
			$s[ 'ref-' . $ref ] = [
				'target'  => 'single:long',
				'referer' => $ref,
			];
		}
		foreach ( [ 'us', 'in', 'de', 'unknown' ] as $ip ) {
			$s[ 'geo-' . $ip ] = [
				'target' => 'single:long',
				'ip'     => $ip,
			];
		}

		$s['head']         = [
			'target' => 'single:long',
			'kind'   => 'head',
		];
		$s['footer']       = [
			'target' => 'single:long',
			'kind'   => 'footer',
		];
		$s['widgets']      = [ 'kind' => 'widgets' ];
		$s['assets-front'] = [
			'target' => 'single:long',
			'kind'   => 'assets',
		];

		foreach ( $this->config['extra_scenarios'] as $id => $spec ) {
			$s[ $id ] = $spec;
		}
		foreach ( array_keys( $this->config['skip'] ) as $id ) {
			unset( $s[ $id ] );
		}
		return $s;
	}

	/**
	 * Run every scenario and the per-config smoke checks.
	 */
	private function run_front_end() {
		foreach ( $this->scenarios() as $id => $spec ) {
			$this->run_scenario( $id, $spec );
		}
		$this->run_pipeline_smoke();
	}

	/**
	 * Resolve a target name to a URL.
	 *
	 * @param string $target Target name.
	 * @return string
	 */
	private function url_for( $target ) {
		if ( 0 === strpos( $target, 'single:' ) ) {
			return get_permalink( $this->posts[ substr( $target, 7 ) ] );
		}
		switch ( $target ) {
			case 'page':
				return get_permalink( $this->posts['page'] );
			case 'home':
				return home_url( '/' );
			case 'category':
				return get_category_link( $this->terms['category'] );
			case 'tag':
				return get_tag_link( $this->terms['tag'] );
			case 'date':
				return get_month_link( 2026, 3 );
			case 'search':
				return home_url( '/?s=qatrio' );
			case 'feed':
				return home_url( '/?feed=rss2' );
		}
		throw new InvalidArgumentException( 'Unknown scenario target: ' . $target );
	}

	/**
	 * Run one scenario and store its snapshot.
	 *
	 * @param string $id   Scenario id (becomes the snapshot file name).
	 * @param array  $spec Scenario spec.
	 */
	private function run_scenario( $id, array $spec ) {
		$kind  = $spec['kind'] ?? 'content';
		$actor = $spec['user'] ?? 'guest';
		$body  = [];

		wp_set_current_user( 'guest' === $actor ? 0 : $this->users[ $actor ] );
		$this->reset_server( $spec );
		$this->reset_engine();
		$this->reset_stats();
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		mt_srand( self::SEED );

		$this->recorder->start();
		try {
			switch ( $kind ) {
				case 'content':
					$this->test->go_to( $this->url_for( $spec['target'] ) );
					$body['content'] = $this->content_loop();
					break;
				case 'head':
					$this->test->go_to( $this->url_for( $spec['target'] ) );
					$this->advance_loop();
					$body['head'] = QA_Plugin_Hook_Capture::run( 'wp_head' );
					break;
				case 'footer':
					$this->test->go_to( $this->url_for( $spec['target'] ) );
					$this->advance_loop();
					$body['footer'] = QA_Plugin_Hook_Capture::run( 'wp_footer' );
					break;
				case 'widgets':
					$body['widgets'] = $this->widgets_text();
					break;
				case 'assets':
					$this->test->go_to( $this->url_for( $spec['target'] ) );
					$body['assets'] = $this->assets_text();
					break;
			}
		} catch ( Throwable $throwable ) {
			$this->recorder->add_throwable( $throwable );
		}
		$notices = $this->recorder->stop();

		$body['notices'] = self::notice_text( $notices );
		$body['state']   = $this->state_text();
		$this->emit( $id . '.txt', self::sections( $body ) );

		$this->reset_server();
		wp_set_current_user( 0 );
	}

	/**
	 * Render the main loop through the plugin's the_content callbacks only.
	 *
	 * @return string
	 */
	private function content_loop() {
		global $wp_query;
		$many = $wp_query->post_count > 1;
		$out  = '';
		while ( have_posts() ) {
			the_post();
			$out .= $this->normalise( QA_Plugin_Hook_Capture::run( 'the_content', get_the_content() ) );
			if ( $many ) {
				$out .= "\n<!--QAFX:post-boundary-->\n";
			}
		}
		return $out;
	}

	/**
	 * Step through the loop without rendering, so the post globals are set as
	 * they would be after a theme's loop.
	 */
	private function advance_loop() {
		while ( have_posts() ) {
			the_post();
		}
	}

	/**
	 * Replace values that change between runs.
	 *
	 * @param string $text Captured output.
	 * @return string
	 */
	private function normalise( $text ) {
		foreach ( $this->posts as $id ) {
			$text = str_replace( 'id="more-' . $id . '"', 'id="more-{POST_ID}"', $text );
		}
		return $text;
	}

	/**
	 * Render the two test sidebars on a single post and on the blog index.
	 *
	 * @return string
	 */
	private function widgets_text() {
		$text = '';
		foreach ( [
			'single-long' => 'single:long',
			'home'        => 'home',
		] as $label => $target ) {
			$this->test->go_to( $this->url_for( $target ) );
			$this->advance_loop();
			foreach ( [ 'qa-sidebar-1', 'qa-sidebar-2' ] as $sidebar ) {
				ob_start();
				$active = dynamic_sidebar( $sidebar );
				$html   = ob_get_clean();
				$text  .= '-- ' . $label . ' / ' . $sidebar . ( $active ? '' : ' (nothing rendered)' ) . " --\n" . $html . "\n";
			}
		}
		return rtrim( $text, "\n" );
	}

	/**
	 * Capture the stats inline script the plugin adds on every front-end page.
	 *
	 * @return string
	 */
	private function assets_text() {
		QA_Plugin_Hook_Capture::run( 'wp_enqueue_scripts' );
		$after = wp_scripts()->get_data( 'jquery', 'after' );
		$text  = is_array( $after ) ? implode( "\n", $after ) : '(none)';
		$text  = preg_replace_callback(
			'/("quick_adsense_nonce": ")([^"]*)(")/',
			function ( $m ) {
				$this->expect_true( (bool) wp_verify_nonce( $m[2], 'quick-adsense-stats' ), 'The stats nonce in the front-end script is not valid for quick-adsense-stats.' );
				return $m[1] . '{NONCE:quick-adsense-stats}' . $m[3];
			},
			$text
		);
		return $text;
	}

	/**
	 * Check that the real the_content pipeline keeps every ad payload intact.
	 *
	 * The plugin-only snapshot says which ads appear on single-long. The full
	 * pipeline (wpautop, shortcodes, ...) must carry each of them through
	 * byte-identical. Known exceptions are listed in the config under
	 * 'pipeline_exceptions' as payload slot => reason.
	 */
	private function run_pipeline_smoke() {
		$settings = $this->settings;
		if ( ! is_array( $settings ) ) {
			return;
		}
		$exceptions = $this->config['pipeline_exceptions'] ?? [];

		wp_set_current_user( 0 );
		$this->reset_server();
		$this->reset_engine();
		$this->reset_stats();
		mt_srand( self::SEED );

		$this->recorder->start();
		try {
			$this->test->go_to( $this->url_for( 'single:long' ) );
			the_post();
			$plugin_only = QA_Plugin_Hook_Capture::run( 'the_content', get_the_content() );
			$this->reset_engine();
			$this->reset_stats();
			mt_srand( self::SEED );
			$full = apply_filters( 'the_content', get_the_content() );
		} catch ( Throwable $throwable ) {
			$this->recorder->add_throwable( $throwable );
			$plugin_only = '';
			$full        = '';
		}
		$this->recorder->stop();

		preg_match_all( '/data-index="(\d+)"/', $plugin_only, $matches );
		foreach ( array_unique( $matches[1] ) as $index ) {
			$code = $settings[ 'onpost_ad_' . $index . '_content' ] ?? null;
			if ( ! is_string( $code ) || '' === $code ) {
				continue;
			}
			if ( isset( $exceptions[ (int) $index ] ) ) {
				continue;
			}
			$this->expect_true(
				false !== strpos( $full, $code ),
				'Full the_content pipeline altered the ad code of slot ' . $index . ' (it is not byte-identical in the output).'
			);
		}
		$this->reset_engine();
		$this->reset_stats();
	}

	/* ------------------------------------------------------------------ */
	/* Admin                                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * Run widget registry, admin page, save and quicktag checks.
	 *
	 * @param mixed $sidebars_before The sidebars_widgets option before widgets_init.
	 */
	private function run_admin( $sidebars_before ) {
		$sections = [];

		$this->check_widget_registry( $sidebars_before );

		// Admin page render and save round trips.
		$page                      = $this->render_admin_page();
		$sections['init-notices']  = self::notice_text( $this->init_notices );
		$sections['admin-notices'] = self::notice_text( $page['notices'] );
		if ( 0 !== strpos( $this->config['slug'], '19-' ) ) {
			$this->expect_same( '(none)', self::notice_text( $page['notices'] ), 'The admin page raised PHP notices or warnings.' );
		}

		$this->check_admin_form( $page['html'], $sidebars_before );

		// Quicktags.
		$sections['quicktags'] = $this->quicktags_text();

		// ads.txt (pure function only: never the file).
		$ids                              = quick_adsense_adstxt_adsense_get_publisherids();
		$sections['adstxt-publisher-ids'] = self::canonical_json( $ids );

		$sections['widget-init-notices'] = self::notice_text( $this->widget_notices );
		$this->emit( 'admin-steps.txt', self::sections( $sections ) );
	}

	/**
	 * Assert and snapshot which AdsWidget classes got registered.
	 *
	 * @param mixed $sidebars_before The sidebars_widgets option before widgets_init.
	 */
	private function check_widget_registry( $sidebars_before ) {
		global $wp_widget_factory;
		$registered = [];
		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $widget instanceof QuickAdsenseAdWidget ) {
				$registered[] = $widget->id_base;
			}
		}
		sort( $registered, SORT_STRING );

		$expected = [];
		$settings = is_array( $this->settings ) ? $this->settings : [];
		for ( $i = 1; $i <= 10; $i++ ) {
			$code = $settings[ 'widget_ad_' . $i . '_content' ] ?? '';
			if ( '' !== $code && isset( $settings[ 'widget_ad_' . $i . '_content' ] ) ) {
				$expected[] = 'adswidget' . $i . '-quick-adsense';
			}
		}
		sort( $expected, SORT_STRING );
		$this->expect_same( $expected, $registered, 'Registered Quick Adsense widget id_bases differ from the non-empty widget slots (C-4.1).' );
		$this->expect_same( $sidebars_before, get_option( 'sidebars_widgets' ), 'sidebars_widgets changed during init or widgets_init.' );

		$active = [];
		for ( $i = 1; $i <= 10; $i++ ) {
			$id_base            = 'adswidget' . $i . '-quick-adsense';
			$active[ $id_base ] = is_active_widget( false, false, $id_base, false );
		}
		$instances = [];
		$placed    = get_option( 'sidebars_widgets' );
		foreach ( (array) $placed as $sidebar => $widgets ) {
			if ( ! is_array( $widgets ) ) {
				continue;
			}
			foreach ( $widgets as $widget_id ) {
				if ( 0 === strpos( $widget_id, 'adswidget' ) ) {
					$instances[ $sidebar ][] = $widget_id;
				}
			}
		}
		$this->emit(
			'widgets-registry.json',
			self::canonical_json(
				[
					'registered_id_bases' => $registered,
					'is_active_widget'    => $active,
					'placements'          => $instances,
				]
			)
		);
	}

	/**
	 * Render the settings page as the administrator.
	 *
	 * @param bool $classic_editor Pretend the Classic Editor plugin is active.
	 * @return array{html:string,notices:string[]}
	 */
	private function render_admin_page( $classic_editor = false ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		wp_set_current_user( $this->users['admin'] );
		$this->reset_server();

		$activate = static function ( $plugins ) {
			$plugins   = (array) $plugins;
			$plugins[] = 'classic-editor/classic-editor.php';
			return $plugins;
		};
		if ( $classic_editor ) {
			add_filter( 'option_active_plugins', $activate );
		}

		$html = '';
		$this->recorder->start();
		try {
			set_current_screen( 'toplevel_page_quick-adsense' );
			QA_Plugin_Hook_Capture::run( 'admin_menu' );
			QA_Plugin_Hook_Capture::run( 'admin_init' );
			$html = QA_Plugin_Hook_Capture::run( 'toplevel_page_quick-adsense' );
		} catch ( Throwable $throwable ) {
			$this->recorder->add_throwable( $throwable );
		}
		$notices = $this->recorder->stop();
		if ( $classic_editor ) {
			remove_filter( 'option_active_plugins', $activate );
		}
		return [
			'html'    => $html,
			'notices' => $notices,
		];
	}

	/**
	 * Run the sanitizer as the current user, recording notices.
	 *
	 * @param array $input Submitted settings.
	 * @return array
	 */
	private function sanitize( array $input ) {
		$result = [];
		$this->recorder->start();
		try {
			$result = quick_adsense_sanitize_settings( $input );
		} catch ( Throwable $throwable ) {
			$this->recorder->add_throwable( $throwable );
		}
		$this->recorder->stop();
		return $result;
	}

	/**
	 * Browser-accurate no-op save, unprivileged saves and the partial save.
	 *
	 * @param string $html            The rendered settings page.
	 * @param mixed  $sidebars_before The sidebars_widgets option before the save.
	 */
	private function check_admin_form( $html, $sidebars_before ) {
		$code_fields = quick_adsense_get_ad_code_fields();
		$settings    = is_array( $this->settings ) ? $this->settings : [];

		// The form's control names are the same for every config (the "full field set").
		$controls = QA_Browser_Form::control_names( $html, self::FORM_ID );
		sort( $controls, SORT_STRING );
		$this->emit_global( 'admin-form-controls.txt', implode( "\n", $controls ) . "\n" );

		// 3. No-op save, as the full administrator with unfiltered_html.
		wp_set_current_user( $this->users['admin'] );
		$this->expect_true( current_user_can( 'unfiltered_html' ), 'The test administrator must have unfiltered_html (single site).' );
		$post  = QA_Browser_Form::submit( $html, self::FORM_ID );
		$input = isset( $post['quick_adsense_settings'] ) ? $post['quick_adsense_settings'] : [];
		$saved = $this->sanitize( $input );
		$this->emit( 'settings-after-noop-save.json', self::canonical_json( $saved ) );

		foreach ( $code_fields as $field ) {
			if ( isset( $settings[ $field ] ) && is_string( $settings[ $field ] ) ) {
				$this->expect_same(
					QA_Browser_Form::textarea_value( $settings[ $field ] ),
					isset( $saved[ $field ] ) ? $saved[ $field ] : null,
					'No-op save changed the bytes of ' . $field . ' beyond what a browser does (C-6.2).'
				);
			}
		}
		$this->expect_same( $sidebars_before, get_option( 'sidebars_widgets' ), 'sidebars_widgets changed during the admin steps.' );

		// The same save with the Classic Editor plugin active: the quicktag controls appear.
		$classic = $this->render_admin_page( true );
		wp_set_current_user( $this->users['admin'] );
		$classic_post = QA_Browser_Form::submit( $classic['html'], self::FORM_ID );
		$this->emit(
			'settings-after-noop-save-classic-editor.json',
			self::canonical_json( $this->sanitize( isset( $classic_post['quick_adsense_settings'] ) ? $classic_post['quick_adsense_settings'] : [] ) )
		);

		// 4. Unprivileged saves must keep every stored code field.
		$hacked = $input;
		$count  = 0;
		foreach ( $code_fields as $field ) {
			++$count;
			if ( 0 === $count % 3 ) {
				unset( $hacked[ $field ] );
			} else {
				$hacked[ $field ] = 'HACKED';
			}
		}
		$strip = static function ( $allcaps ) {
			$allcaps['unfiltered_html'] = false;
			return $allcaps;
		};
		add_filter( 'user_has_cap', $strip );
		wp_set_current_user( $this->users['admin'] );
		$this->expect_true( ! current_user_can( 'unfiltered_html' ) && current_user_can( 'manage_options' ), 'The administrator without unfiltered_html was not set up correctly.' );
		$as_admin = $this->sanitize( $hacked );
		remove_filter( 'user_has_cap', $strip );
		wp_set_current_user( $this->users['editor'] );
		$as_editor = $this->sanitize( $hacked );
		foreach ( [
			'administrator without unfiltered_html' => $as_admin,
			'editor'                                => $as_editor,
		] as $who => $result ) {
			foreach ( $code_fields as $field ) {
				if ( array_key_exists( $field, $settings ) ) {
					$this->expect_same( $settings[ $field ], $result[ $field ] ?? null, 'Save by ' . $who . ' did not retain the stored ' . $field . ' (C-2.2, C-6.3).' );
				} else {
					$this->expect_true( ! array_key_exists( $field, $result ), 'Save by ' . $who . ' created the code field ' . $field . '.' );
				}
			}
			$this->expect_true( false === strpos( wp_json_encode( $result ), 'HACKED' ), 'Save by ' . $who . ' stored submitted code.' );
		}

		// 4b. Privileged partial save: omitted code fields are erased (C-2.3.1).
		wp_set_current_user( $this->users['admin'] );
		$partial = array_diff_key( $input, array_flip( $code_fields ) );
		$this->emit( 'settings-after-privileged-partial-save.json', self::canonical_json( $this->sanitize( $partial ) ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Capture the inline script the plugin adds to the classic editor's quicktags.
	 *
	 * @return string
	 */
	private function quicktags_text() {
		wp_set_current_user( $this->users['admin'] );
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		$this->recorder->start();
		try {
			QA_Plugin_Hook_Capture::run( 'admin_enqueue_scripts', null, [ 'post.php' ] );
		} catch ( Throwable $throwable ) {
			$this->recorder->add_throwable( $throwable );
		}
		$this->recorder->stop();
		$after = wp_scripts()->get_data( 'quicktags', 'after' );
		wp_set_current_user( 0 );
		return is_array( $after ) ? implode( "\n", $after ) : '(none)';
	}
}
