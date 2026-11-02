<?php
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Tests compare stored values byte for byte.
require_once dirname( __DIR__ ) . '/includes/class-qa-plugin-hook-capture.php';
require_once dirname( __DIR__, 2 ) . '/user-configs/loader.php';

use QuickAdsense\Snapshots;
use QuickAdsense\SnapshotsAdmin;

/**
 * Snapshot storage, restore and the admin handlers.
 */
class QuickAdsenseSnapshotsTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		remove_all_filters( 'sanitize_option_quick_adsense_settings' );
		QA_User_Config_Loader::clear_plugin_options();
		wp_cache_flush();
	}

	/**
	 * Become a user with a role and return its ID.
	 *
	 * @param string $role Role.
	 * @return int
	 */
	private function become( $role ) {
		$id = self::factory()->user->create( [ 'role' => $role ] );
		wp_set_current_user( $id );
		return $id;
	}

	/**
	 * Build a restore request with a valid nonce.
	 *
	 * @param string $name    Backup name.
	 * @param bool   $widgets Tick the widget checkbox.
	 * @return array
	 */
	private function restore_request( $name, $widgets = false ) {
		$request = [
			'backup'   => $name,
			'_wpnonce' => wp_create_nonce( 'quick-adsense-backup-restore_' . $name ),
		];
		if ( $widgets ) {
			$request['restore_widgets'] = '1';
		}
		return $request;
	}

	public function test_ring_keeps_five() {
		$names = [];
		for ( $i = 1; $i <= 7; $i++ ) {
			update_option( 'quick_adsense_settings', [ 'n' => (string) $i ] );
			$names[] = Snapshots::create( 'test' . $i );
		}
		$index = get_option( Snapshots::INDEX );
		$this->assertSame( array_slice( $names, 2 ), $index );
		$this->assertFalse( get_option( $names[0] ) );
		$this->assertFalse( get_option( $names[1] ) );
		$this->assertIsArray( get_option( $names[2] ) );
		$this->assertCount( 5, Snapshots::all() );
		$this->assertSame( array_reverse( array_slice( $names, 2 ) ), array_keys( Snapshots::all() ) );
	}

	public function test_prune_never_deletes_a_foreign_option() {
		update_option( 'blogname', 'Keep me' );
		$first = Snapshots::create( 'one' );
		update_option( Snapshots::INDEX, array_merge( [ 'blogname' ], get_option( Snapshots::INDEX ) ), false );
		for ( $i = 0; $i < 6; $i++ ) {
			Snapshots::create( 'more' );
		}
		$this->assertSame( 'Keep me', get_option( 'blogname' ) );
		$this->assertNotContains( 'blogname', get_option( Snapshots::INDEX ) );
		$this->assertFalse( get_option( $first ) );
		$this->assertCount( 5, get_option( Snapshots::INDEX ) );
	}

	public function test_names_do_not_collide_and_reasons_are_normalised() {
		$a = Snapshots::create( 'Bad Reason/../x' );
		$b = Snapshots::create( 'Bad Reason/../x' );
		$this->assertNotSame( $a, $b );
		$this->assertSame( 'badreason..x', get_option( $a )['reason'] );
		$this->assertSame( 'manual', get_option( Snapshots::create( '!!!' ) )['reason'] );
		$this->assertSame( 0, strpos( $a, Snapshots::PREFIX ) );
		$this->assertMatchesRegularExpression( '/-\d+$/', $b );
	}

	public function test_get_only_serves_indexed_backups() {
		update_option( 'blogname', 'x' );
		$this->assertNull( Snapshots::get( 'blogname' ) );
		$name = Snapshots::create( 'x' );
		$this->assertIsArray( Snapshots::get( $name ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public function roundtrip_provider() {
		return [
			'config 18 nasty payloads' => [ '18-non-adsense-payloads' ],
			'config 01 defaults'       => [ '01-fresh-defaults' ],
		];
	}

	/**
	 * @dataProvider roundtrip_provider
	 *
	 * @param string $slug Config slug.
	 */
	public function test_restore_round_trips_byte_for_byte( $slug ) {
		$config   = QA_User_Config_Loader::load( $slug );
		$settings = $config['options']['quick_adsense_settings'] ?? quick_adsense_get_defaults();
		QA_User_Config_Loader::write_option( 'quick_adsense_settings', $settings );
		$expected = serialize( get_option( 'quick_adsense_settings' ) );
		$name     = Snapshots::create( 'manual' );

		$this->become( 'administrator' );
		// Headers are already sent under PHPUnit.
		remove_action( 'admin_init', 'send_frame_options_header' );
		remove_action( 'admin_init', 'wp_admin_headers' );
		// Core's privacy-policy helper complains about being called from a bare do_action().
		$this->setExpectedIncorrectUsage( 'wp_add_privacy_policy_content' );
		do_action( 'admin_init' );
		$this->assertNotFalse( has_filter( 'sanitize_option_quick_adsense_settings', 'quick_adsense_sanitize_settings' ) );
		delete_option( 'quick_adsense_settings' );

		$result = SnapshotsAdmin::handle_restore( $this->restore_request( $name ) );

		$this->assertTrue( $result );
		$this->assertSame( $expected, serialize( get_option( 'quick_adsense_settings' ) ) );
		$this->assertNotFalse( has_filter( 'sanitize_option_quick_adsense_settings', 'quick_adsense_sanitize_settings' ) );
		$reasons = array_column( Snapshots::all(), 'reason' );
		$this->assertContains( 'pre-restore', $reasons );
	}

	public function test_restoring_the_oldest_of_five_works() {
		$this->become( 'administrator' );
		$names = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			update_option( 'quick_adsense_settings', [ 'n' => (string) $i ] );
			$names[] = Snapshots::create( 'test' );
		}
		update_option( 'quick_adsense_settings', [ 'n' => 'current' ] );

		$this->assertTrue( SnapshotsAdmin::handle_restore( $this->restore_request( $names[0] ) ) );
		$this->assertSame( [ 'n' => '1' ], get_option( 'quick_adsense_settings' ) );
		$this->assertCount( 5, Snapshots::all() );
		$this->assertFalse( get_option( $names[0] ) );
	}

	public function test_restore_is_rejected_when_not_allowed() {
		$this->become( 'administrator' );
		update_option( 'quick_adsense_settings', [ 'n' => 'backup' ] );
		$name = Snapshots::create( 'test' );
		$bad  = Snapshots::create( 'broken' );
		$data = get_option( $bad );
		unset( $data['settings'] );
		update_option( $bad, $data, false );
		update_option( 'quick_adsense_settings', [ 'n' => 'current' ] );

		$assert_rejected = function ( array $request, $label ) {
			$result = SnapshotsAdmin::handle_restore( $request );
			$this->assertWPError( $result, $label );
			$this->assertSame( [ 'n' => 'current' ], get_option( 'quick_adsense_settings' ), $label );
		};

		$this->become( 'editor' );
		$assert_rejected( $this->restore_request( $name ), 'editor' );

		$admin = $this->become( 'administrator' );
		$strip = static function ( $caps ) {
			$caps['unfiltered_html'] = false;
			return $caps;
		};
		add_filter( 'user_has_cap', $strip );
		$assert_rejected( $this->restore_request( $name ), 'no unfiltered_html' );
		remove_filter( 'user_has_cap', $strip );

		wp_set_current_user( $admin );
		$request             = $this->restore_request( $name );
		$request['_wpnonce'] = 'bad';
		$assert_rejected( $request, 'bad nonce' );
		$assert_rejected( $this->restore_request( 'blogname' ), 'foreign option name' );
		$assert_rejected( $this->restore_request( Snapshots::PREFIX . 'unknown' ), 'unknown name' );
		$assert_rejected( $this->restore_request( $bad ), 'payload without settings' );
	}

	public function test_widget_restore_is_opt_in() {
		$this->become( 'administrator' );
		update_option( 'quick_adsense_settings', [ 'n' => '1' ] );
		update_option(
			'sidebars_widgets',
			[
				'wp_inactive_widgets' => [],
				'sidebar-1'           => [ 'text-2', 'adswidget2-quick-adsense-3', 'search-2' ],
				'array_version'       => 3,
			]
		);
		update_option(
			'widget_adswidget2-quick-adsense',
			[
				3              => [],
				'_multiwidget' => 1,
			]
		);
		$original = get_option( 'sidebars_widgets' );
		$name     = Snapshots::create( 'test' );
		$payload  = get_option( $name );
		$this->assertSame( [ 'sidebar-1' => [ 1 => 'adswidget2-quick-adsense-3' ] ], $payload['widgets'] );

		$moved = [
			'wp_inactive_widgets' => [ 'adswidget2-quick-adsense-3' ],
			'sidebar-1'           => [ 'text-2', 'search-2', 'meta-2' ],
			'array_version'       => 3,
		];
		update_option( 'sidebars_widgets', $moved );
		delete_option( 'widget_adswidget2-quick-adsense' );

		$this->assertTrue( SnapshotsAdmin::handle_restore( $this->restore_request( $name ) ) );
		$this->assertSame( $moved, get_option( 'sidebars_widgets' ) );
		$this->assertFalse( get_option( 'widget_adswidget2-quick-adsense' ) );

		$this->assertTrue( SnapshotsAdmin::handle_restore( $this->restore_request( $name, true ) ) );
		$this->assertSame(
			[
				'wp_inactive_widgets' => [],
				'sidebar-1'           => [ 'text-2', 'adswidget2-quick-adsense-3', 'search-2', 'meta-2' ],
				'array_version'       => 3,
			],
			get_option( 'sidebars_widgets' )
		);
		$this->assertIsArray( get_option( 'widget_adswidget2-quick-adsense' ) );
		$this->assertNotSame( $original, $moved );
	}

	public function test_create_and_download_handlers() {
		update_option( 'quick_adsense_settings', [ 'n' => '1' ] );

		$this->become( 'editor' );
		$create = [ '_wpnonce' => wp_create_nonce( 'quick-adsense-backup-create' ) ];
		$this->assertWPError( SnapshotsAdmin::handle_create( $create ) );
		$this->assertSame( [], Snapshots::all() );

		$this->become( 'administrator' );
		$this->assertWPError( SnapshotsAdmin::handle_create( [ '_wpnonce' => 'bad' ] ) );
		$this->assertWPError( SnapshotsAdmin::handle_create( [] ) );
		$create = [ '_wpnonce' => wp_create_nonce( 'quick-adsense-backup-create' ) ];
		$this->assertTrue( SnapshotsAdmin::handle_create( $create ) );
		$backups = Snapshots::all();
		$this->assertCount( 1, $backups );
		$name = key( $backups );
		$this->assertSame( 'manual', $backups[ $name ]['reason'] );

		$request = [
			'backup'   => $name,
			'_wpnonce' => wp_create_nonce( 'quick-adsense-backup-download_' . $name ),
		];
		$payload = SnapshotsAdmin::handle_download( $request );
		$this->assertSame( $name, $payload['backup'] );
		$this->assertSame( [ 'n' => '1' ], $payload['settings'] );

		$request['_wpnonce'] = wp_create_nonce( 'quick-adsense-backup-restore_' . $name );
		$this->assertWPError( SnapshotsAdmin::handle_download( $request ) );

		$this->become( 'editor' );
		$request['_wpnonce'] = wp_create_nonce( 'quick-adsense-backup-download_' . $name );
		$this->assertWPError( SnapshotsAdmin::handle_download( $request ) );
	}
}
