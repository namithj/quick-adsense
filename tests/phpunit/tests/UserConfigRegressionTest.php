<?php
require_once dirname( __DIR__ ) . '/includes/class-qa-plugin-hook-capture.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-notice-recorder.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-browser-form.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-snapshot-store.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-file-sentinel.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-ajax-die.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-user-config-runner.php';
require_once dirname( __DIR__, 2 ) . '/user-configs/loader.php';

/**
 * R00 regression safety net: 20 user configurations, frozen at v2.9.4.
 *
 * Each configuration is a different kind of real site. The test runs it through
 * the plugin and compares every output with a golden-master snapshot in
 * tests/user-configs/snapshots/. Any release that changes what an existing user
 * sees, or what is stored in their database, fails here.
 *
 * Run it with bin/test-user-configs.sh (composer test:configs). It needs the
 * bootstrap-user-configs.php bootstrap, which keeps it away from the real site.
 * Under the default bootstrap every test here is skipped.
 *
 * @group user-configs
 */
class QuickAdsenseUserConfigRegressionTest extends WP_UnitTestCase {

	/**
	 * Saved global state, restored after every test.
	 *
	 * @var array
	 */
	private $globals_backup = [];

	/**
	 * Skip unless the guarded user-config bootstrap is in use, and refuse to run
	 * if the filesystem_method guard has been removed.
	 */
	public static function set_up_before_class() {
		if ( ! defined( 'QA_USER_CONFIGS_BOOTSTRAP' ) ) {
			self::markTestSkipped( 'Run the user-config group with bin/test-user-configs.sh (composer test:configs); it needs the guarded bootstrap.' );
		}
		parent::set_up_before_class();
		if ( 'direct' === apply_filters( 'filesystem_method', 'direct', [], '', false ) || defined( 'FS_METHOD' ) ) {
			throw new RuntimeException( 'The filesystem_method guard is not active: QuickAdsense\\FileHandler could write the real ads.txt. Refusing to run.' );
		}
	}

	/**
	 * Expose the core test factory to the runner.
	 *
	 * @return WP_UnitTest_Factory
	 */
	public function qa_factory() {
		return self::factory();
	}

	public function set_up() {
		parent::set_up();
		$this->globals_backup = QA_User_Config_Runner::backup_globals();
	}

	public function tear_down() {
		QA_User_Config_Runner::restore_globals( $this->globals_backup );
		parent::tear_down();

		// The ads.txt sentinel: nothing in the run may have touched it.
		if ( isset( $GLOBALS['qa_adstxt_sentinel'] ) ) {
			$message = QA_File_Sentinel::changed( $GLOBALS['qa_adstxt_sentinel']['path'], $GLOBALS['qa_adstxt_sentinel']['state'] );
			$this->assertNull( $message, (string) $message );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Providers                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Every config, in numeric order, keyed by slug.
	 *
	 * @return array<string, array{string}>
	 */
	public function config_provider() {
		$rows = [];
		foreach ( QA_User_Config_Loader::list() as $slug ) {
			$rows[ $slug ] = [ $slug ];
		}
		return $rows;
	}

	/**
	 * The configs whose stats are exercised through AJAX (15 and 20).
	 *
	 * @return array<string, array{string}>
	 */
	public function ajax_config_provider() {
		$rows = [];
		foreach ( QA_User_Config_Loader::list() as $slug ) {
			$config = QA_User_Config_Loader::load( $slug );
			if ( ! empty( $config['ajax'] ) ) {
				$rows[ $slug ] = [ $slug ];
			}
		}
		return $rows;
	}

	/* ------------------------------------------------------------------ */
	/* The 20 configurations                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * Run one configuration against its snapshots.
	 *
	 * @dataProvider config_provider
	 *
	 * @param string $slug Config slug.
	 */
	public function test_user_config( $slug ) {
		$store  = new QA_Snapshot_Store( QA_User_Config_Loader::dir() . '/snapshots' );
		$result = null;

		// Stats keys are UTC days. If the day rolls over mid-run, run once more from a clean state.
		for ( $attempt = 1; $attempt <= 2; $attempt++ ) {
			$day    = gmdate( 'dmY' );
			$runner = new QA_User_Config_Runner( $this, $this->globals_backup );
			$result = $runner->run( QA_User_Config_Loader::load( $slug ) );
			if ( gmdate( 'dmY' ) === $day ) {
				break;
			}
			$result = null;
			$this->restart_transaction();
		}
		$this->assertNotNull( $result, 'The UTC day changed during the run, twice in a row. Re-run the config.' );

		$problems = $result['failures'];
		foreach ( $result['outputs'] as $relative => $text ) {
			$problem = $store->check( $relative, $text );
			if ( null !== $problem ) {
				$problems[] = $problem;
			}
		}
		foreach ( $store->stale( $slug, true ) as $stale ) {
			$problems[] = 'Stale snapshot (no scenario produces it any more): ' . $stale;
		}

		$this->addToAssertionCount( $result['checks'] + count( $result['outputs'] ) );
		if ( $store->is_update_mode() ) {
			fwrite( STDERR, sprintf( "  [update] %s: %d snapshot file(s) written\n", $slug, $store->written() ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		}
		$this->report( $slug, $problems );
	}

	/**
	 * Roll the database back and start a fresh transaction.
	 */
	private function restart_transaction() {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
		$wpdb->query( 'START TRANSACTION' );
		wp_cache_flush();
	}

	/**
	 * Fail with a readable report when there are problems.
	 *
	 * @param string   $label    What was run.
	 * @param string[] $problems Messages.
	 */
	private function report( $label, array $problems ) {
		if ( ! $problems ) {
			$this->assertSame( [], $problems );
			return;
		}
		// QA_FULL_REPORT=1 lists every problem instead of the first eight.
		$shown = '1' === getenv( 'QA_FULL_REPORT' ) ? $problems : array_slice( $problems, 0, 8 );
		$more  = count( $problems ) - count( $shown );
		$this->fail(
			$label . ': ' . count( $problems ) . " regression(s) against the v2.9.4 snapshots.\n\n"
			. implode( "\n\n", $shown )
			. ( $more > 0 ? "\n\n... and " . $more . ' more.' : '' )
			. "\n\nIf the change is intended, it must be listed under \"Intended snapshot changes\" in the release plan; then regenerate with QA_UPDATE_SNAPSHOTS=1."
		);
	}

	/* ------------------------------------------------------------------ */
	/* AJAX (configs 15 and 20)                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * Call an admin-ajax handler the way admin-ajax.php would.
	 *
	 * @param string $action Action without the wp_ajax_ prefix.
	 * @param array  $post   POST data.
	 * @return array|null Decoded JSON response.
	 */
	private function ajax( $action, array $post ) {
		$_POST    = $post;
		$_REQUEST = $post;
		$handler  = static function () {
			return static function () {
				throw new QA_Ajax_Die();
			};
		};
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', $handler );
		ob_start();
		try {
			do_action( 'wp_ajax_' . $action );
			do_action( 'wp_ajax_nopriv_' . $action );
		} catch ( QA_Ajax_Die $die ) {
			unset( $die );
		}
		$output = ob_get_clean();
		remove_filter( 'wp_die_ajax_handler', $handler );
		remove_filter( 'wp_doing_ajax', '__return_true' );
		$_POST    = [];
		$_REQUEST = [];
		return json_decode( $output, true );
	}

	/**
	 * The click, chart and reset endpoints work, and reject bad indexes.
	 *
	 * @group ajax
	 * @dataProvider ajax_config_provider
	 *
	 * @param string $slug Config slug.
	 */
	public function test_ajax_endpoints( $slug ) {
		$config = QA_User_Config_Loader::load( $slug );
		QA_User_Config_Runner::restore_globals( $this->globals_backup );
		remove_all_filters( 'sanitize_option_quick_adsense_settings' );
		QA_User_Config_Loader::clear_plugin_options();
		QA_User_Config_Loader::apply( $config );
		QA_Plugin_Hook_Capture::run( 'init' );
		QA_User_Config_Loader::apply_post_init( $config );
		$settings = get_option( 'quick_adsense_settings' );

		$slots = [];
		for ( $i = 1; $i <= 10; $i++ ) {
			if ( isset( $settings[ 'onpost_ad_' . $i . '_enable_stats' ] ) ) {
				$slots[] = $i;
			}
		}
		$this->assertNotEmpty( $slots, 'The config must have at least one slot with stats enabled.' );
		$slot = $slots[0];
		$name = 'quick_adsense_onpost_ad_' . $slot . '_stats';

		// Click: guests may call it with the front-end nonce; it counts one click.
		wp_set_current_user( 0 );
		$before = get_option( $name );
		$today  = gmdate( 'dmY' );
		$clicks = isset( $before[ $today ] ) ? $before[ $today ]['c'] : 0;
		$post   = [
			'quick_adsense_nonce'           => wp_create_nonce( 'quick-adsense-stats' ),
			'quick_adsense_onpost_ad_index' => (string) $slot,
		];
		$reply  = $this->ajax( 'quick_adsense_onpost_ad_click', $post );
		$this->assertTrue( $reply['success'], 'The click endpoint did not report success.' );
		$after = get_option( $name );
		// The new entry is the last one. array_shift() may have renumbered the keys when trimming, so look at the position, not the key.
		$newest = end( $after );
		$this->assertSame( $clicks + 1, $newest['c'], 'One click must be added to today.' );
		$this->assertSame( isset( $before[ $today ] ) ? $before[ $today ]['i'] : 0, $newest['i'], 'A click must not change impressions.' );
		$this->assertLessThanOrEqual( 30, count( $after ), 'The stats array is trimmed to 30 days.' );

		// A slot without stats enabled records nothing.
		$off = 10;
		unset( $settings[ 'onpost_ad_' . $off . '_enable_stats' ] );
		$reply = $this->ajax( 'quick_adsense_onpost_ad_click', array_merge( $post, [ 'quick_adsense_onpost_ad_index' => (string) $off ] ) );
		$this->assertFalse( $reply['success'] );
		$this->assertFalse( get_option( 'quick_adsense_onpost_ad_' . $off . '_stats' ) );

		// Chart: thirty points, one per day, as the admin.
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$admin_post = [
			'nonce' => wp_create_nonce( 'quick-adsense-nonce' ),
			'index' => (string) $slot,
		];
		$reply      = $this->ajax( 'quick_adsense_onpost_ad_get_stats_chart', $admin_post );
		$this->assertTrue( $reply['success'], 'The chart endpoint did not report success.' );
		$this->assertSame( 1, preg_match( '#<textarea id="quick_adsense_ad_stats_chart_data"[^>]*>(.*?)</textarea>#s', $reply['data'], $m ) );
		$points = json_decode( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ), true );
		$this->assertCount( 30, $points, 'The chart must have 30 points.' );
		$this->assertSame( gmdate( 'm/d/Y' ), $points[0]['x'], 'The first chart point is today.' );

		// Reset deletes the stats option.
		$reply = $this->ajax( 'quick_adsense_onpost_ad_reset_stats', $admin_post );
		$this->assertTrue( $reply['success'], 'The reset endpoint did not report success.' );
		$this->assertFalse( get_option( $name ), 'Reset must delete the stats option.' );

		// Invalid indexes are rejected and nothing is written.
		foreach ( [ '0', '11', '1 OR 1' ] as $bad ) {
			foreach ( [
				'quick_adsense_onpost_ad_click'           => array_merge( $post, [ 'quick_adsense_onpost_ad_index' => $bad ] ),
				'quick_adsense_onpost_ad_get_stats_chart' => array_merge( $admin_post, [ 'index' => $bad ] ),
				'quick_adsense_onpost_ad_reset_stats'     => array_merge( $admin_post, [ 'index' => $bad ] ),
			] as $action => $data ) {
				$reply = $this->ajax( $action, $data );
				$this->assertFalse( $reply['success'], $action . ' accepted the index ' . $bad );
				$this->assertFalse( get_option( 'quick_adsense_onpost_ad_' . $bad . '_stats' ), $action . ' wrote an option for ' . $bad );
			}
		}
	}

	/* ------------------------------------------------------------------ */
	/* Run-wide checks                                                     */
	/* ------------------------------------------------------------------ */

	/**
	 * The guards, the hook registry and the independence of the configs.
	 */
	public function test_run_wide_contract() {
		$store    = new QA_Snapshot_Store( QA_User_Config_Loader::dir() . '/snapshots' );
		$problems = [];

		// The test site's identity is fixed, so md5( url ) in the wrapper class is stable.
		$this->assertSame( 'http://example.org', get_bloginfo( 'url' ) );
		$this->assertSame( md5( 'http://example.org' ), md5( get_bloginfo( 'url' ) ) );

		// Isolation guards (R00 section 2.3).
		$this->assertNotSame( 'direct', apply_filters( 'filesystem_method', 'direct', [], '', false ), 'The filesystem_method guard must be active.' );
		$this->assertFalse( defined( 'FS_METHOD' ), 'FS_METHOD must not be defined.' );
		$this->assertFalse( has_filter( 'sanitize_option_quick_adsense_settings' ), 'The settings sanitizer must not be attached when a run starts (C-2.3.3).' );
		$this->assertArrayHasKey( 'qa_adstxt_sentinel', $GLOBALS, 'The bootstrap did not record the ads.txt sentinel.' );

		// With the guard in force the plugin's file handler is inert: it can neither read nor write ads.txt.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$handler = new QuickAdsense\FileHandler( 'ads.txt' );
		$this->assertFalse( $handler->exists(), 'FileHandler must not see ads.txt while the guard is active.' );
		$this->assertFalse( $handler->write( 'qa-guard-probe' ), 'FileHandler must not write ads.txt while the guard is active.' );
		if ( ! $GLOBALS['qa_adstxt_sentinel']['state']['exists'] ) {
			$this->assertFileDoesNotExist( ABSPATH . 'ads.txt' );
		}

		// The sentinel itself notices a created, changed or removed file.
		$probe = tempnam( sys_get_temp_dir(), 'qa-sentinel-' );
		$state = QA_File_Sentinel::capture( $probe );
		$this->assertNull( QA_File_Sentinel::changed( $probe, $state ) );
		file_put_contents( $probe, 'changed' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->assertNotNull( QA_File_Sentinel::changed( $probe, $state ), 'The sentinel missed a write.' );
		unlink( $probe ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		$this->assertNotNull( QA_File_Sentinel::changed( $probe, $state ), 'The sentinel missed a delete.' );
		$absent = $probe . '-absent';
		$state  = QA_File_Sentinel::capture( $absent );
		file_put_contents( $absent, 'x' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->assertNotNull( QA_File_Sentinel::changed( $absent, $state ), 'The sentinel missed a created file.' );
		unlink( $absent ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink

		// The 20 base configs exist, numbered 01 to 20, and none looks like another.
		$slugs = QA_User_Config_Loader::list();
		$this->assertGreaterThanOrEqual( 20, count( $slugs ) );
		foreach ( range( 1, 20 ) as $number ) {
			$prefix = sprintf( '%02d-', $number );
			$found  = array_filter(
				$slugs,
				static function ( $slug ) use ( $prefix ) {
					return 0 === strpos( $slug, $prefix );
				}
			);
			$this->assertCount( 1, $found, 'Exactly one config must be numbered ' . $prefix . '.' );
		}
		$signatures = [];
		foreach ( $slugs as $slug ) {
			$signature = QA_User_Config_Loader::signature( QA_User_Config_Loader::load( $slug ) );
			$this->assertArrayNotHasKey( $signature, $signatures, $slug . ' has the same (shape, positions, targeting, sinks) signature as ' . ( $signatures[ $signature ] ?? '' ) );
			$signatures[ $signature ] = $slug;
		}

		// The widget has no options (C-4.3).
		$widget = new QuickAdsenseAdWidget( 1 );
		$this->assertSame( '<p>There are no options for this widget.</p>', $widget->form( [] ) );
		$this->assertSame( [ 'x' => 'y' ], $widget->update( [ 'x' => 'y' ], [ 'old' => 'z' ] ) );
		$this->assertSame( 'adswidget1-quick-adsense', $widget->id_base );
		$this->assertSame( 'AdsWidget1 (Quick Adsense)', $widget->name );

		// Every callback the plugin registers: hook, priority, accepted args, order.
		$problem = $store->check( '_global/hooks.txt', QA_Plugin_Hook_Capture::registry() );
		if ( null !== $problem ) {
			$problems[] = $problem;
		}

		// Semantic hook assertions (C-3.5b, C-3.11). Collected, so the registry diff above is reported too.
		global $wp_filter;
		$engine = null;
		foreach ( QA_Plugin_Hook_Capture::callbacks( 'the_content' ) as $entry ) {
			$engine = $entry;
		}
		if ( null === $engine ) {
			$problems[] = 'The content engine is not registered on the_content.';
		} else {
			if ( 10 !== $engine['priority'] ) {
				$problems[] = 'The content engine must stay on the_content at priority 10 (C-3.5b); it is at ' . $engine['priority'] . '.';
			}
			$order    = isset( $wp_filter['the_content']->callbacks[ $engine['priority'] ] ) ? $wp_filter['the_content']->callbacks[ $engine['priority'] ] : [];
			$position = array_search( 'wpautop', array_keys( $order ), true );
			$ours     = false;
			foreach ( array_keys( $order ) as $index => $key ) {
				if ( $order[ $key ]['function'] === $engine['function'] ) {
					$ours = $index;
				}
			}
			if ( false === $position || false === $ours || $ours < $position ) {
				$problems[] = 'The content engine must be registered after core wpautop at the same priority (C-3.5b).';
			}
		}
		foreach ( [ 'wp_head', 'wp_footer' ] as $hook ) {
			$callbacks = QA_Plugin_Hook_Capture::callbacks( $hook );
			if ( ! $callbacks ) {
				$problems[] = $hook . ' has no Quick Adsense callback.';
			}
			foreach ( $callbacks as $entry ) {
				if ( 10 !== $entry['priority'] ) {
					$problems[] = $hook . ' output must be at priority 10 (C-3.11); it is at ' . $entry['priority'] . '.';
				}
			}
		}

		$this->report( 'run-wide', $problems );
	}
}
