<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName, Universal.Files.SeparateFunctionsFromOO.Mixed, WordPress.Security.EscapeOutput.ExceptionNotEscaped, WordPress.WP.GlobalVariablesOverride.Prohibited -- Fixture loader: the file name and the short QA_AD() helper are fixed by the R00 plan; exceptions are developer-facing.
/**
 * User-config loader for the regression safety net (R00).
 *
 * Lists the numbered configuration files, loads them, resolves the symbolic
 * ad-code references and applies a configuration to the (throwaway) database.
 */

/**
 * Return the exact bytes of a named ad payload from fixtures/ad-codes.php.
 *
 * @param string $name Payload name, for example "adsense-async-1".
 * @return string
 */
function QA_AD( $name ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- Short fixture helper used inside config files.
	return QA_User_Config_Loader::ad( $name );
}

/**
 * Build a stats history ending yesterday: "D-days" up to "D-1", oldest first.
 *
 * @param int $days   Number of days of history.
 * @param int $factor Multiplier that makes the numbers distinguishable per slot.
 * @return array<string, array{i:int,c:int}>
 */
function QA_STATS( $days, $factor = 1 ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- Short fixture helper used inside config files.
	return QA_User_Config_Loader::stats_history( $days, $factor );
}

/**
 * Loads and applies user configurations.
 */
class QA_User_Config_Loader {

	/**
	 * Test sidebars that assigned AdsWidget slots are placed in.
	 *
	 * @var array<string, string>
	 */
	const SIDEBARS = [
		'sidebar-1' => 'qa-sidebar-1',
		'sidebar-2' => 'qa-sidebar-2',
	];

	/**
	 * Cached fixture payloads.
	 *
	 * @var array<string, string>|null
	 */
	private static $ad_codes = null;

	/**
	 * Cached fixture post bodies.
	 *
	 * @var array<string, string>|null
	 */
	private static $posts = null;

	/**
	 * Directory that holds the configs, fixtures and snapshots.
	 *
	 * @return string
	 */
	public static function dir() {
		return __DIR__;
	}

	/**
	 * List the slugs of every numbered config, in numeric order.
	 *
	 * @return string[]
	 */
	public static function list() {
		$slugs = [];
		foreach ( glob( __DIR__ . '/[0-9][0-9]-*.php' ) as $file ) {
			$slugs[] = basename( $file, '.php' );
		}
		sort( $slugs, SORT_STRING );
		return $slugs;
	}

	/**
	 * Load one config and fill in the optional keys.
	 *
	 * @param string $slug Config slug, for example "05-all-slots-all-positions".
	 * @return array
	 */
	public static function load( $slug ) {
		$file = __DIR__ . '/' . $slug . '.php';
		if ( ! preg_match( '/^[0-9]{2}-[a-z0-9-]+$/', $slug ) || ! is_file( $file ) ) {
			throw new InvalidArgumentException( 'Unknown user config: ' . $slug );
		}
		$config = include $file;
		if ( ! is_array( $config ) || ( $config['slug'] ?? null ) !== $slug ) {
			throw new UnexpectedValueException( 'Config file ' . $slug . ' must return an array whose "slug" matches its file name.' );
		}
		return array_replace(
			[
				'description'     => '',
				'shape'           => 'saved',
				'options'         => [],
				'post_init'       => [],
				'widgets'         => [],
				'users'           => [
					'admin'                => true,
					'editor_no_unfiltered' => true,
				],
				'skip'            => [],
				'variants'        => [],
				'deleted_by_init' => [],
				'extra_scenarios' => [],
			],
			$config
		);
	}

	/**
	 * Return the exact bytes of a named ad payload.
	 *
	 * @param string $name Payload name.
	 * @return string
	 */
	public static function ad( $name ) {
		if ( null === self::$ad_codes ) {
			self::$ad_codes = include __DIR__ . '/fixtures/ad-codes.php';
		}
		if ( ! array_key_exists( $name, self::$ad_codes ) ) {
			throw new InvalidArgumentException( 'Unknown ad payload: ' . $name );
		}
		return self::$ad_codes[ $name ];
	}

	/**
	 * Return the body of a named fixture post.
	 *
	 * @param string $name Fixture name, for example "long".
	 * @param int    $id   Post ID that replaces the {ID} placeholder.
	 * @return string
	 */
	public static function post_body( $name, $id = 0 ) {
		if ( null === self::$posts ) {
			self::$posts = include __DIR__ . '/fixtures/posts.php';
		}
		if ( ! array_key_exists( $name, self::$posts ) ) {
			throw new InvalidArgumentException( 'Unknown post fixture: ' . $name );
		}
		return str_replace( '{ID}', (string) $id, self::$posts[ $name ] );
	}

	/**
	 * Return the names of every fixture post.
	 *
	 * @return string[]
	 */
	public static function post_names() {
		self::post_body( 'long' );
		return array_keys( self::$posts );
	}

	/**
	 * Build a stats history ending yesterday, oldest entry first.
	 *
	 * Keys are the UTC "dmY" strings the plugin uses, built relative to today.
	 *
	 * @param int $days   Number of days.
	 * @param int $factor Multiplier for the counters.
	 * @return array
	 */
	public static function stats_history( $days, $factor = 1 ) {
		$stats = [];
		for ( $n = $days; $n >= 1; $n-- ) {
			$stats[ gmdate( 'dmY', strtotime( '-' . $n . ' day' ) ) ] = [
				'i' => ( 100 + $n ) * $factor,
				'c' => $n * $factor,
			];
		}
		return $stats;
	}

	/**
	 * Names of the v1.x loose options the plugin migrates from.
	 *
	 * @return string[]
	 */
	public static function legacy_option_names() {
		$names = [ 'AdsDisp', 'BegnAds', 'BegnRnd', 'MiddAds', 'MiddRnd', 'EndiAds', 'EndiRnd', 'MoreAds', 'MoreRnd', 'LapaAds', 'LapaRnd' ];
		for ( $i = 1; $i <= 3; $i++ ) {
			foreach ( [ 'Ads', 'Rnd', 'Nup', 'Con' ] as $suffix ) {
				$names[] = 'Par' . $i . $suffix;
			}
		}
		foreach ( [ 'Ads', 'Rnd', 'Nup', 'Con' ] as $suffix ) {
			$names[] = 'Img1' . $suffix;
		}
		foreach ( [ 'AppPost', 'AppPage', 'AppHome', 'AppCate', 'AppArch', 'AppTags', 'AppMaxA', 'AppSide', 'AppLogg', 'QckTags', 'QckRnds', 'QckOffs', 'QckOfPs' ] as $name ) {
			$names[] = $name;
		}
		for ( $i = 1; $i <= 10; $i++ ) {
			$names[] = 'AdsCode' . $i;
			$names[] = 'AdsAlign' . $i;
			$names[] = 'AdsMargin' . $i;
			$names[] = 'WidCode' . $i;
		}
		return $names;
	}

	/**
	 * Write an option exactly as given, bypassing the settings sanitizer.
	 *
	 * Once an admin_init has run, the sanitize_option_quick_adsense_settings
	 * filter is attached and would silently rewrite the fixture (C-2.3.3): it
	 * turns '0' switches on and drops unknown keys. The filter is therefore
	 * detached for the write and restored afterwards.
	 *
	 * @param string $name  Option name.
	 * @param mixed  $value Option value.
	 */
	public static function write_option( $name, $value ) {
		$hook      = 'sanitize_option_' . $name;
		$callbacks = isset( $GLOBALS['wp_filter'][ $hook ] ) ? $GLOBALS['wp_filter'][ $hook ] : null;
		if ( null !== $callbacks ) {
			unset( $GLOBALS['wp_filter'][ $hook ] );
		}
		// The row may exist with an equal value, in which case update_option() is a no-op.
		update_option( $name, $value );
		if ( null !== $callbacks ) {
			$GLOBALS['wp_filter'][ $hook ] = $callbacks;
		}
	}

	/**
	 * Delete every option row the plugin owns, and the v1.x loose options.
	 */
	public static function clear_plugin_options() {
		global $wpdb;
		$names = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'quick\\_adsense%' OR option_name LIKE '\\_transient\\_quick\\_adsense%' OR option_name LIKE 'widget\\_adswidget%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( $names as $name ) {
			delete_option( $name );
		}
		foreach ( self::legacy_option_names() as $name ) {
			delete_option( $name );
		}
		delete_option( 'sidebars_widgets' );
		self::write_option(
			'sidebars_widgets',
			[
				'wp_inactive_widgets' => [],
				'array_version'       => 3,
			]
		);
	}

	/**
	 * Resolve the effective options of a config, optionally for a variant.
	 *
	 * @param array  $config  Loaded config.
	 * @param string $variant Variant name, or an empty string for the base.
	 * @return array
	 */
	public static function effective_options( array $config, $variant = '' ) {
		$options = $config['options'];
		if ( '' !== $variant ) {
			if ( ! isset( $config['variants'][ $variant ] ) ) {
				throw new InvalidArgumentException( 'Unknown variant: ' . $variant );
			}
			$options = array_replace( $options, $config['variants'][ $variant ] );
		}
		return $options;
	}

	/**
	 * Write a config's options and widget placements to the database.
	 *
	 * Runs before the plugin's init and widgets_init callbacks, as stored data
	 * would on a real site. Instance numbers start at 2, like core's own.
	 *
	 * @param array  $config  Loaded config.
	 * @param string $variant Variant name, or an empty string for the base.
	 */
	public static function apply( array $config, $variant = '' ) {
		foreach ( self::effective_options( $config, $variant ) as $name => $value ) {
			self::write_option( $name, $value );
		}

		if ( $config['widgets'] ) {
			$sidebars  = [
				'wp_inactive_widgets' => [],
				'array_version'       => 3,
			];
			$instances = [];
			foreach ( $config['widgets'] as $sidebar => $slots ) {
				$sidebar_id              = self::SIDEBARS[ $sidebar ];
				$sidebars[ $sidebar_id ] = [];
				foreach ( $slots as $slot ) {
					$id_base                   = 'adswidget' . $slot . '-quick-adsense';
					$sidebars[ $sidebar_id ][] = $id_base . '-2';
					$instances[ $id_base ]     = [
						2              => [],
						'_multiwidget' => 1,
					];
				}
			}
			foreach ( $instances as $id_base => $instance ) {
				self::write_option( 'widget_' . $id_base, $instance );
			}
			self::write_option( 'sidebars_widgets', $sidebars );
		}
	}

	/**
	 * Merge the config's post-init changes into the settings, without a save.
	 *
	 * @param array $config Loaded config.
	 */
	public static function apply_post_init( array $config ) {
		if ( ! $config['post_init'] ) {
			return;
		}
		$settings = get_option( 'quick_adsense_settings' );
		self::write_option( 'quick_adsense_settings', array_replace( (array) $settings, $config['post_init'] ) );
	}

	/**
	 * Describe how a config differs from the others, for the independence check.
	 *
	 * The signature is (shape, enabled positions, targeting, sinks).
	 *
	 * @param array $config Loaded config.
	 * @return string
	 */
	public static function signature( array $config ) {
		$settings = $config['options']['quick_adsense_settings'] ?? [];
		$settings = is_array( $settings ) ? $settings : [];
		$legacy   = array_diff_key( $config['options'], [ 'quick_adsense_settings' => 1 ] );

		$positions = [];
		$targeting = [];
		$sinks     = [];
		foreach ( $settings as $key => $value ) {
			if ( 0 === strpos( $key, 'enable_position_' ) && '1' === $value ) {
				$ad_key      = 'ad_' . substr( $key, strlen( 'enable_position_' ) );
				$positions[] = $key . '=' . ( $settings[ $ad_key ] ?? '' );
			} elseif ( preg_match( '/^onpost_ad_(\d+)_(hide_.+|limit_visitor_country)$/', $key, $m ) ) {
				$targeting[] = $m[1] . ':' . $m[2] . '=' . wp_json_encode( $value );
			} elseif ( preg_match( '/^(onpost_ad_\d+_content|widget_ad_\d+_content|header_embed_code|footer_embed_code)$/', $key ) && '' !== $value && is_string( $value ) ) {
				$sinks[] = $key;
			}
		}
		foreach ( array_keys( $legacy ) as $key ) {
			$sinks[] = 'legacy:' . $key;
		}
		foreach ( $config['widgets'] as $sidebar => $slots ) {
			$sinks[] = 'assigned:' . $sidebar . '=' . implode( ',', $slots );
		}
		sort( $positions );
		sort( $targeting );
		sort( $sinks );

		return wp_json_encode(
			[
				'shape'     => $config['shape'],
				'positions' => $positions,
				'targeting' => $targeting,
				'sinks'     => $sinks,
				'switches'  => self::switches( $settings ),
			]
		);
	}

	/**
	 * List the page-type and visibility switches present in a settings array.
	 *
	 * @param array $settings Settings.
	 * @return string[]
	 */
	private static function switches( array $settings ) {
		$switches = [];
		foreach ( $settings as $key => $value ) {
			if ( preg_match( '/^(enable_on_|disable_|enable_all|enable_quicktag)/', $key ) && '1' === $value ) {
				$switches[] = $key;
			}
		}
		sort( $switches );
		return $switches;
	}
}
