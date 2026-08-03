<?php
/**
 * AJAX authorization and option-key boundary coverage.
 *
 * @group ajax
 */
class QuickAdsenseAjaxSecurityTest extends WP_Ajax_UnitTestCase {
	public function test_reset_stats_rejects_out_of_range_index() {
		$this->_setRole( 'administrator' );
		update_option( 'quick_adsense_onpost_ad_999_stats', [ 'sentinel' => true ] );
		$_POST['nonce'] = wp_create_nonce( 'quick-adsense-nonce' );
		$_POST['index'] = '999';

		try {
			$this->_handleAjax( 'quick_adsense_onpost_ad_reset_stats' );
		} catch ( WPAjaxDieStopException $exception ) {
			unset( $exception );
		} catch ( WPAjaxDieContinueException $exception ) {
			unset( $exception );
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertSame( [ 'sentinel' => true ], get_option( 'quick_adsense_onpost_ad_999_stats' ) );
	}

	public function test_reset_stats_requires_manage_options() {
		$this->_setRole( 'subscriber' );
		update_option( 'quick_adsense_onpost_ad_1_stats', [ 'sentinel' => true ] );
		$_POST['nonce'] = wp_create_nonce( 'quick-adsense-nonce' );
		$_POST['index'] = '1';

		try {
			$this->_handleAjax( 'quick_adsense_onpost_ad_reset_stats' );
		} catch ( WPAjaxDieStopException $exception ) {
			unset( $exception );
		} catch ( WPAjaxDieContinueException $exception ) {
			unset( $exception );
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertSame( [ 'sentinel' => true ], get_option( 'quick_adsense_onpost_ad_1_stats' ) );
	}
}
