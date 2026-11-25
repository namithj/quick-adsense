<?php
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Tests compare stored values byte for byte.
require_once dirname( __DIR__ ) . '/includes/class-qa-plugin-hook-capture.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-snapshot-store.php';
require_once dirname( __DIR__, 2 ) . '/user-configs/loader.php';

use QuickAdsense\ExportImport;
use QuickAdsense\Snapshots;

/**
 * Tools page export and import.
 */
class QuickAdsenseExportImportTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		remove_all_filters( 'sanitize_option_quick_adsense_settings' );
		QA_User_Config_Loader::clear_plugin_options();
		wp_cache_flush();
		$this->become( 'administrator' );
	}

	/**
	 * Become a user with a role.
	 *
	 * @param string $role Role.
	 */
	private function become( $role ) {
		wp_set_current_user( self::factory()->user->create( [ 'role' => $role ] ) );
	}

	/**
	 * Run the preview and apply steps for a JSON file.
	 *
	 * @param string $json    File contents.
	 * @param bool   $widgets Tick the widget checkbox.
	 * @return true|string|WP_Error
	 */
	private function import( $json, $widgets = false ) {
		$result = ExportImport::handle_preview( [ '_wpnonce' => wp_create_nonce( 'quick-adsense-import-preview' ) ], $json );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$request = [ '_wpnonce' => wp_create_nonce( 'quick-adsense-import-apply' ) ];
		if ( $widgets ) {
			$request['import_widgets'] = '1';
		}
		return ExportImport::handle_apply( $request );
	}

	/**
	 * Export the current site.
	 *
	 * @param bool $stats Include statistics.
	 * @return string
	 */
	private function export_json( $stats = false ) {
		$request = [ '_wpnonce' => wp_create_nonce( 'quick-adsense-export' ) ];
		if ( $stats ) {
			$request['include_stats'] = '1';
		}
		$result = ExportImport::handle_export( $request );
		$this->assertIsArray( $result );
		return $result['body'];
	}

	/**
	 * Settings of a config after the plugin's init (the legacy migration), or null.
	 *
	 * @param string $slug Config slug.
	 * @return array|null
	 */
	private function stored_settings_of( $slug ) {
		QA_User_Config_Loader::clear_plugin_options();
		QA_User_Config_Loader::apply( QA_User_Config_Loader::load( $slug ) );
		QA_Plugin_Hook_Capture::run( 'init' );
		$settings = get_option( 'quick_adsense_settings' );
		return is_array( $settings ) ? $settings : null;
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
	 * @dataProvider config_provider
	 *
	 * @param string $slug Config slug.
	 */
	public function test_round_trip( $slug ) {
		$settings = $this->stored_settings_of( $slug );
		$this->assertIsArray( $settings, $slug . ' stores a settings array after init.' );
		$expected = serialize( $settings );
		$json     = $this->export_json();

		if ( '19-corrupt-partial' === $slug ) {
			$this->assert_corrupt_config_snapshot( $settings, $json );
			return;
		}

		delete_option( 'quick_adsense_settings' );
		$this->assertSame( 'applied', $this->import( $json ), $slug );
		$this->assertSame( $expected, serialize( get_option( 'quick_adsense_settings' ) ), $slug );
	}

	/**
	 * Config 19: unknown keys are dropped and bad values clamped, so compare with a stored snapshot.
	 *
	 * @param array  $settings Stored settings.
	 * @param string $json     Export file.
	 */
	private function assert_corrupt_config_snapshot( array $settings, $json ) {
		$parsed = ExportImport::parse( $json );
		$this->assertIsArray( $parsed );
		$validated = quick_adsense_import_validate( $parsed['settings'] );
		delete_option( 'quick_adsense_settings' );
		$this->assertSame( 'applied', $this->import( $json ) );
		$flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;
		$text  = "== report ==\n" . wp_json_encode( $validated['report'], $flags )
			. "\n== result ==\n" . wp_json_encode( get_option( 'quick_adsense_settings' ), $flags ) . "\n";
		$this->assertSame( serialize( $validated['settings'] ), serialize( get_option( 'quick_adsense_settings' ) ) );
		$this->assertNotSame( serialize( $settings ), serialize( get_option( 'quick_adsense_settings' ) ), 'Config 19 is expected to be changed by validation.' );

		$store   = new QA_Snapshot_Store( dirname( __DIR__ ) . '/snapshots' );
		$message = $store->check( 'export-import/19-corrupt-partial.txt', $text );
		$this->assertNull( $message, (string) $message );
	}

	public function test_validator_keeps_shape_and_clamps() {
		$result = quick_adsense_import_validate(
			[
				'enable_on_posts'                   => '0',
				'max_ads_per_page'                  => '99',
				'onpost_ad_1_alignment'             => '9',
				'onpost_ad_1_margin'                => '05',
				'onpost_ad_1_limit_visitor_country' => [ 'US', 'de', 'XYZ', 'US', 'IN' ],
				'onpost_ad_1_content'               => "<script>a</script>\n ",
				'bogus_key'                         => 'x',
			]
		);
		$this->assertSame(
			[
				'enable_on_posts'                   => '0',
				'max_ads_per_page'                  => '10',
				'onpost_ad_1_alignment'             => '4',
				'onpost_ad_1_margin'                => '05',
				'onpost_ad_1_limit_visitor_country' => [ 'US', 'IN' ],
				'onpost_ad_1_content'               => "<script>a</script>\n ",
			],
			$result['settings']
		);
		$types = array_column( $result['report'], 'type', 'key' );
		$this->assertSame( 'unknown', $types['bogus_key'] );
		$this->assertSame( 'clamped', $types['max_ads_per_page'] );
		$this->assertSame( 'countries', $types['onpost_ad_1_limit_visitor_country'] );
		$this->assertArrayNotHasKey( 'onpost_ad_1_margin', $types );
	}

	public function test_dry_run_diff_is_accurate() {
		$current  = [
			'a' => '1',
			'b' => '2',
			'c' => '3',
		];
		$incoming = [
			'a'                 => '1',
			'b'                 => '5',
			'd'                 => '4',
			'header_embed_code' => 'x',
		];
		$diff     = ExportImport::diff( $current, $incoming );
		$by_key   = array_column( $diff, null, 'key' );
		$this->assertSame( [ 'b', 'c', 'd', 'header_embed_code' ], array_keys( $by_key ) );
		$this->assertSame( 'changed', $by_key['b']['status'] );
		$this->assertSame( [ '2', '5' ], [ $by_key['b']['old'], $by_key['b']['new'] ] );
		$this->assertSame( 'removed', $by_key['c']['status'] );
		$this->assertSame( 'added', $by_key['d']['status'] );
		$this->assertTrue( $by_key['header_embed_code']['code'] );
		$this->assertFalse( $by_key['b']['code'] );
	}

	public function test_preview_does_not_write_and_apply_takes_pre_import_snapshot() {
		update_option( 'quick_adsense_settings', [ 'max_ads_per_page' => '3' ] );
		$file = wp_json_encode(
			[
				'format'         => 'quick-adsense-export',
				'format_version' => 1,
				'settings'       => [ 'max_ads_per_page' => '7' ],
			]
		);
		$this->assertTrue( ExportImport::handle_preview( [ '_wpnonce' => wp_create_nonce( 'quick-adsense-import-preview' ) ], $file ) );
		$this->assertSame( [ 'max_ads_per_page' => '3' ], get_option( 'quick_adsense_settings' ) );
		$this->assertSame( [], Snapshots::all() );
		$this->assertSame( [ 'max_ads_per_page' => '7' ], ExportImport::pending()['settings'] );

		$this->assertSame( 'applied', ExportImport::handle_apply( [ '_wpnonce' => wp_create_nonce( 'quick-adsense-import-apply' ) ] ) );
		$this->assertSame( [ 'max_ads_per_page' => '7' ], get_option( 'quick_adsense_settings' ) );
		$backups = array_values( Snapshots::all() );
		$this->assertCount( 1, $backups );
		$this->assertSame( 'pre-import', $backups[0]['reason'] );
		$this->assertSame( [ 'max_ads_per_page' => '3' ], $backups[0]['settings'] );
		$this->assertNull( ExportImport::pending() );
	}

	public function test_discard_changes_nothing() {
		update_option( 'quick_adsense_settings', [ 'max_ads_per_page' => '3' ] );
		$file = wp_json_encode(
			[
				'format'         => 'quick-adsense-export',
				'format_version' => 1,
				'settings'       => [ 'max_ads_per_page' => '7' ],
			]
		);
		ExportImport::handle_preview( [ '_wpnonce' => wp_create_nonce( 'quick-adsense-import-preview' ) ], $file );
		$request = [
			'_wpnonce' => wp_create_nonce( 'quick-adsense-import-apply' ),
			'discard'  => '1',
		];
		$this->assertSame( 'discarded', ExportImport::handle_apply( $request ) );
		$this->assertSame( [ 'max_ads_per_page' => '3' ], get_option( 'quick_adsense_settings' ) );
		$this->assertSame( [], Snapshots::all() );
	}

	public function test_without_unfiltered_html_existing_code_is_kept() {
		update_option(
			'quick_adsense_settings',
			[
				'max_ads_per_page'    => '3',
				'onpost_ad_1_content' => 'EXISTING',
			]
		);
		$file  = wp_json_encode(
			[
				'format'         => 'quick-adsense-export',
				'format_version' => 1,
				'settings'       => [
					'max_ads_per_page'    => '5',
					'onpost_ad_1_content' => '<script>evil()</script>',
					'onpost_ad_2_content' => '<script>new()</script>',
				],
			]
		);
		$strip = static function ( $caps ) {
			$caps['unfiltered_html'] = false;
			return $caps;
		};
		add_filter( 'user_has_cap', $strip );
		$this->assertSame( 'applied', $this->import( $file ) );
		remove_filter( 'user_has_cap', $strip );
		$this->assertSame(
			[
				'max_ads_per_page'    => '5',
				'onpost_ad_1_content' => 'EXISTING',
			],
			get_option( 'quick_adsense_settings' )
		);
	}

	/**
	 * @return array<string, array{string}>
	 */
	public function malformed_provider() {
		return [
			'not json'            => [ 'not json {' ],
			'json scalar'         => [ '"x"' ],
			'wrong format'        => [ '{"format":"other","format_version":1,"settings":{}}' ],
			'wrong version'       => [ '{"format":"quick-adsense-export","format_version":2,"settings":{}}' ],
			'no settings'         => [ '{"format":"quick-adsense-export","format_version":1}' ],
			'b64 on a normal key' => [ '{"format":"quick-adsense-export","format_version":1,"settings":{"max_ads_per_page":{"__qa_b64":"MQ=="}}}' ],
			'bad b64'             => [ '{"format":"quick-adsense-export","format_version":1,"settings":{"header_embed_code":{"__qa_b64":"***"}}}' ],
		];
	}

	/**
	 * @dataProvider malformed_provider
	 *
	 * @param string $json File contents.
	 */
	public function test_malformed_file_is_rejected( $json ) {
		update_option( 'quick_adsense_settings', [ 'max_ads_per_page' => '3' ] );
		$result = ExportImport::handle_preview( [ '_wpnonce' => wp_create_nonce( 'quick-adsense-import-preview' ) ], $json );
		$this->assertWPError( $result );
		$this->assertNull( ExportImport::pending() );
		$this->assertSame( [ 'max_ads_per_page' => '3' ], get_option( 'quick_adsense_settings' ) );
	}

	public function test_requests_need_nonce_and_capability() {
		$file = '{"format":"quick-adsense-export","format_version":1,"settings":{}}';
		$this->assertWPError( ExportImport::handle_preview( [ '_wpnonce' => 'bad' ], $file ) );
		$this->assertWPError( ExportImport::handle_export( [] ) );
		$this->assertWPError( ExportImport::handle_apply( [ '_wpnonce' => 'bad' ] ) );
		$this->become( 'editor' );
		$this->assertWPError( ExportImport::handle_preview( [ '_wpnonce' => wp_create_nonce( 'quick-adsense-import-preview' ) ], $file ) );
		$this->assertWPError( ExportImport::handle_export( [ '_wpnonce' => wp_create_nonce( 'quick-adsense-export' ) ] ) );
		$this->assertWPError( ExportImport::handle_apply( [ '_wpnonce' => wp_create_nonce( 'quick-adsense-import-apply' ) ] ) );
		$this->become( 'administrator' );
		$expired = ExportImport::handle_apply( [ '_wpnonce' => wp_create_nonce( 'quick-adsense-import-apply' ) ] );
		$this->assertSame( 'quick_adsense_import_expired', $expired->get_error_code() );
	}

	public function test_non_utf8_code_round_trips_through_b64() {
		// A utf8mb4 test database cannot store these bytes, so the option is faked on read.
		$code    = "<script>\xff\xfe bad bytes</script>";
		$stored  = [
			'max_ads_per_page'    => '3',
			'onpost_ad_1_content' => $code,
		];
		$provide = static function () use ( $stored ) {
			return $stored;
		};
		add_filter( 'pre_option_quick_adsense_settings', $provide );
		$json = $this->export_json();
		remove_filter( 'pre_option_quick_adsense_settings', $provide );

		$data = json_decode( $json, true );
		$this->assertSame( [ '__qa_b64' => base64_encode( $code ) ], $data['settings']['onpost_ad_1_content'] );
		$parsed = ExportImport::parse( $json );
		$this->assertSame( $stored, $parsed['settings'] );
		$this->assertSame( $stored, quick_adsense_import_validate( $parsed['settings'] )['settings'] );
	}

	public function test_export_envelope_filename_and_stats() {
		update_option( 'quick_adsense_settings', [ 'max_ads_per_page' => '3' ] );
		update_option(
			'quick_adsense_onpost_ad_2_stats',
			[
				'01012026' => [
					'i' => 5,
					'c' => 1,
				],
			]
		);
		$result = ExportImport::handle_export( [ '_wpnonce' => wp_create_nonce( 'quick-adsense-export' ) ] );
		$this->assertMatchesRegularExpression( '/^quick-adsense-export-[a-z0-9.-]+-\d{8}-\d{6}\.json$/', $result['filename'] );
		$data = json_decode( $result['body'], true );
		$this->assertSame( 'quick-adsense-export', $data['format'] );
		$this->assertSame( 1, $data['format_version'] );
		$this->assertSame( QUICK_ADSENSE_VERSION, $data['plugin_version'] );
		$this->assertFalse( $data['include_stats'] );
		$this->assertArrayNotHasKey( 'stats', $data );
		foreach ( [ 'exported_at', 'site_url', 'settings', 'widgets' ] as $key ) {
			$this->assertArrayHasKey( $key, $data );
		}

		$with = json_decode( $this->export_json( true ), true );
		$this->assertTrue( $with['include_stats'] );
		$this->assertSame(
			[
				'2' => [
					'01012026' => [
						'i' => 5,
						'c' => 1,
					],
				],
			],
			$with['stats']
		);
	}

	public function test_widgets_are_restored_only_when_ticked() {
		update_option( 'quick_adsense_settings', [ 'max_ads_per_page' => '3' ] );
		update_option(
			'sidebars_widgets',
			[
				'wp_inactive_widgets' => [],
				'sidebar-1'           => [ 'text-2', 'adswidget2-quick-adsense-3' ],
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
		$json = $this->export_json();

		$empty = [
			'wp_inactive_widgets' => [],
			'sidebar-1'           => [ 'text-2' ],
			'array_version'       => 3,
		];
		update_option( 'sidebars_widgets', $empty );
		$this->assertSame( 'applied', $this->import( $json ) );
		$this->assertSame( $empty, get_option( 'sidebars_widgets' ) );

		$this->assertSame( 'applied', $this->import( $json, true ) );
		$this->assertSame( [ 'text-2', 'adswidget2-quick-adsense-3' ], get_option( 'sidebars_widgets' )['sidebar-1'] );
	}

	public function test_foreign_widget_ids_in_a_file_are_ignored() {
		$file   = wp_json_encode(
			[
				'format'         => 'quick-adsense-export',
				'format_version' => 1,
				'settings'       => [],
				'widgets'        => [
					'widgets' => [ 'sidebar-1' => [ 'custom_html-9', 'adswidget3-quick-adsense-2' ] ],
				],
			]
		);
		$parsed = ExportImport::parse( $file );
		$this->assertSame( [ 'sidebar-1' => [ 1 => 'adswidget3-quick-adsense-2' ] ], $parsed['widgets']['widgets'] );
	}
}
