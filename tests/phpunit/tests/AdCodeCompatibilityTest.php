<?php
/**
 * Integration coverage for privileged opaque ad code.
 */

class QuickAdsenseAdCodeCompatibilityTest extends WP_UnitTestCase {
	/** @var int */
	private $administrator_id;

	public function set_up() {
		parent::set_up();
		$this->administrator_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $this->administrator_id );
	}

	private function payload() {
		return <<<'HTML'
<script async src="https://ads.example.test/a.js?x=1&y=2"></script>
<iframe src="https://ads.example.test/frame" data-provider-slot="a&b"></iframe>
<script>window.ad = {"path":"C:\\ads\\slot","close":"</textarea>"};</script>
HTML;
	}

	public function test_authorized_settings_round_trip_is_byte_exact() {
		$this->assertTrue( current_user_can( 'unfiltered_html' ) );
		$payload = $this->payload();
		$result  = quick_adsense_sanitize_settings(
			[
				'onpost_ad_1_content' => $payload,
				'header_embed_code'   => $payload,
			]
		);
		$this->assertSame( $payload, $result['onpost_ad_1_content'] );
		$this->assertSame( $payload, $result['header_embed_code'] );
	}

	public function test_admin_textarea_is_safe_and_decodes_byte_exactly() {
		$payload = $this->payload();
		$control = quickadsense_get_control( 'textarea-big', '', 'code', 'code', $payload );
		$this->assertStringNotContainsString( '>' . $payload . '</textarea>', $control );
		$this->assertStringContainsString( '&lt;/textarea&gt;', $control );
		preg_match( '/<textarea[^>]*>(.*)<\/textarea>/s', $control, $matches );
		$this->assertSame( $payload, html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' ) );
	}

	public function test_control_labels_allow_expected_markup_only() {
		$control = quickadsense_get_control(
			'checkbox',
			'<b id="appearance-label">Posts</b><script>unsafe()</script>',
			'appearance',
			'appearance',
			'1'
		);
		$this->assertStringContainsString( '<b id="appearance-label">Posts</b>', $control );
		$this->assertStringNotContainsString( '<script', $control );
	}

	public function test_render_helpers_do_not_transform_code() {
		$payload = $this->payload();
		update_option(
			'quick_adsense_settings',
			[
				'onpost_ad_1_content'   => $payload,
				'onpost_ad_1_alignment' => '4',
				'onpost_ad_1_margin'    => '0',
			]
		);
		ob_start();
		quick_adsense_echo_ad_code( $payload );
		$this->assertSame( $payload, ob_get_clean() );
		$this->assertStringContainsString(
			$payload,
			quick_adsense_content_replace_ads( '<!--Ads1-->', 'Ads1', 1 )
		);
	}

	public function test_user_without_unfiltered_html_cannot_replace_existing_code() {
		$payload = $this->payload();
		update_option( 'quick_adsense_settings', [ 'onpost_ad_1_content' => $payload ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
		$result = quick_adsense_sanitize_settings( [ 'onpost_ad_1_content' => '<script>changed()</script>' ] );
		$this->assertSame( $payload, $result['onpost_ad_1_content'] );
	}

	public function test_template_loader_cannot_traverse_or_overwrite_validated_path() {
		$this->assertNull( quick_adsense_load_file( '../quick-adsense.php' ) );
		$this->assertStringContainsString(
			'quick_adsense_ad_stats_chart',
			quick_adsense_load_file(
				'templates/block-stats-chart.php',
				[
					'file_path'  => ABSPATH . 'wp-config.php',
					'stats_data' => '[]',
				]
			)
		);
	}

	public function test_filesystem_wrapper_rejects_parent_traversal() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$handler = new QuickAdsense\FileHandler( 'ads.txt' );
		$this->assertTrue( $handler->exists( 'wp-load.php' ) );
		$this->assertFalse( $handler->exists( '../wp-config.php' ) );
	}

	/**
	 * @dataProvider ad_index_provider
	 */
	public function test_stats_index_is_bounded( $candidate, $expected ) {
		$this->assertSame( $expected, quick_adsense_validate_ad_index( $candidate ) );
	}

	public function ad_index_provider() {
		return [
			'first'       => [ '1', 1 ],
			'last'        => [ 10, 10 ],
			'zero'        => [ 0, false ],
			'too large'   => [ 11, false ],
			'option path' => [ '../1', false ],
			'array'       => [ [ 1 ], false ],
		];
	}
}
