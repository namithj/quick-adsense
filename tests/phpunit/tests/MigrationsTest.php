<?php
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Tests compare stored values byte for byte.
require_once dirname( __DIR__ ) . '/includes/class-qa-plugin-hook-capture.php';
require_once dirname( __DIR__, 2 ) . '/user-configs/loader.php';

/**
 * Schema version, upgrade detection and the order relative to the legacy migration.
 */
class QuickAdsenseMigrationsTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		remove_all_filters( 'sanitize_option_quick_adsense_settings' );
		QA_User_Config_Loader::clear_plugin_options();
		wp_cache_flush();
	}

	/**
	 * Run every init callback of the plugin, in priority order.
	 */
	private function run_init() {
		QA_Plugin_Hook_Capture::run( 'init' );
	}

	public function test_hooks_are_registered_in_order() {
		$priorities = [];
		foreach ( QA_Plugin_Hook_Capture::callbacks( 'init' ) as $entry ) {
			$priorities[ $entry['priority'] ][] = $entry['function'];
		}
		$this->assertSame( [ \QuickAdsense\Migrations::class, 'probe' ], $priorities[1][0] );
		$this->assertInstanceOf( Closure::class, $priorities[10][0] );
		$this->assertSame( [ \QuickAdsense\Migrations::class, 'run' ], $priorities[20][0] );
		$this->assertSame( 1, has_action( 'init', [ \QuickAdsense\Migrations::class, 'probe' ] ) );
		$this->assertSame( 20, has_action( 'init', [ \QuickAdsense\Migrations::class, 'run' ] ) );
	}

	public function test_db_version_is_set_once_and_second_run_is_a_no_op() {
		update_option( 'quick_adsense_settings', [ 'max_ads_per_page' => '4' ] );
		$this->run_init();

		$this->assertSame( 1, get_option( 'quick_adsense_db_version' ) );
		$this->assertSame( QUICK_ADSENSE_VERSION, get_option( 'quick_adsense_plugin_version' ) );
		$this->assertCount( 1, \QuickAdsense\Snapshots::all() );

		$writes = [];
		$record = static function ( $option ) use ( &$writes ) {
			if ( 0 === strpos( $option, 'quick_adsense' ) ) {
				$writes[] = $option;
			}
		};
		add_action( 'added_option', $record );
		add_action( 'updated_option', $record );
		$this->run_init();
		remove_action( 'added_option', $record );
		remove_action( 'updated_option', $record );

		$this->assertSame( [], $writes );
		$this->assertCount( 1, \QuickAdsense\Snapshots::all() );
		$this->assertSame( 1, get_option( 'quick_adsense_db_version' ) );
	}

	public function test_new_install_takes_no_backup() {
		$this->assertFalse( get_option( 'quick_adsense_settings' ) );
		$this->run_init();

		$this->assertIsArray( get_option( 'quick_adsense_settings' ) );
		$this->assertSame( [], \QuickAdsense\Snapshots::all() );
		$this->assertSame( 1, get_option( 'quick_adsense_db_version' ) );
		$this->assertSame( QUICK_ADSENSE_VERSION, get_option( 'quick_adsense_plugin_version' ) );
	}

	public function test_upgrade_from_a_stored_version_takes_one_backup() {
		$settings = [ 'max_ads_per_page' => '4' ];
		update_option( 'quick_adsense_settings', $settings );
		update_option( 'quick_adsense_plugin_version', '2.9.0', false );
		$this->run_init();

		$backups = \QuickAdsense\Snapshots::all();
		$this->assertCount( 1, $backups );
		$backup = reset( $backups );
		$this->assertSame( 'upgrade-from-2.9.0', $backup['reason'] );
		$this->assertSame( $settings, $backup['settings'] );
		$this->assertSame( QUICK_ADSENSE_VERSION, get_option( 'quick_adsense_plugin_version' ) );
	}

	public function test_same_version_takes_no_backup() {
		update_option( 'quick_adsense_settings', [ 'max_ads_per_page' => '4' ] );
		update_option( 'quick_adsense_plugin_version', QUICK_ADSENSE_VERSION, false );
		$this->run_init();

		$this->assertSame( [], \QuickAdsense\Snapshots::all() );
	}

	public function test_run_without_probe_takes_no_backup() {
		update_option( 'quick_adsense_settings', [ 'max_ads_per_page' => '4' ] );
		\QuickAdsense\Migrations::run();

		$this->assertSame( [], \QuickAdsense\Snapshots::all() );
		$this->assertSame( QUICK_ADSENSE_VERSION, get_option( 'quick_adsense_plugin_version' ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public function config_provider() {
		$cases = [];
		foreach ( QA_User_Config_Loader::list() as $slug ) {
			$cases[ $slug ] = [ $slug ];
		}
		return $cases;
	}

	/**
	 * The upgrade backup holds the pre-init settings, so the runner took it before the legacy migration.
	 *
	 * @dataProvider config_provider
	 *
	 * @param string $slug Config slug.
	 */
	public function test_every_user_config_backs_up_only_a_prior_array( $slug ) {
		$config = QA_User_Config_Loader::load( $slug );
		QA_User_Config_Loader::apply( $config );
		$pre = get_option( 'quick_adsense_settings' );

		$this->run_init();

		$backups = \QuickAdsense\Snapshots::all();
		$this->assertCount( is_array( $pre ) ? 1 : 0, $backups );
		if ( is_array( $pre ) ) {
			$backup = reset( $backups );
			$this->assertSame( serialize( $pre ), serialize( $backup['settings'] ) );
			$this->assertSame( 'upgrade-from-pre-2.10', $backup['reason'] );
		}
		$this->assertSame( 1, get_option( 'quick_adsense_db_version' ) );

		if ( in_array( $config['shape'], [ 'legacy-v1', 'legacy-v2' ], true ) ) {
			// Order proof: the legacy migration produced an array, but no backup was taken.
			$this->assertIsArray( get_option( 'quick_adsense_settings' ) );
			$this->assertSame( [], $backups );
		}
	}
}
