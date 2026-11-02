<?php
/**
 * 11: device-specific ads.
 *
 * Slot 1 hides on mobile, slot 2 on tablet, slot 3 on desktop and slot 4 on
 * mobile and tablet. Mobile_Detect's isMobile() is also true for tablets, so
 * "hide on mobile" hides tablets too (C-3.10).
 */
$settings = [
	'max_ads_per_page'                    => '4',
	'enable_on_posts'                     => '1',
	'enable_on_pages'                     => '1',
	'enable_position_beginning_of_post'   => '1',
	'ad_beginning_of_post'                => '1',
	'enable_position_middle_of_post'      => '1',
	'ad_middle_of_post'                   => '2',
	'enable_position_end_of_post'         => '1',
	'ad_end_of_post'                      => '3',
	'enable_position_after_para_option_1' => '1',
	'ad_after_para_option_1'              => '4',
	'position_after_para_option_1'        => '2',
	'onpost_ad_1_hide_device_mobile'      => '1',
	'onpost_ad_2_hide_device_tablet'      => '1',
	'onpost_ad_3_hide_device_desktop'     => '1',
	'onpost_ad_4_hide_device_mobile'      => '1',
	'onpost_ad_4_hide_device_tablet'      => '1',
];
for ( $i = 1; $i <= 4; $i++ ) {
	$settings[ 'onpost_ad_' . $i . '_content' ]   = QA_AD( 'adsense-async-' . $i );
	$settings[ 'onpost_ad_' . $i . '_alignment' ] = '2';
	$settings[ 'onpost_ad_' . $i . '_margin' ]    = '10';
}

return [
	'slug'        => '11-device-targeting',
	'description' => 'Device targeting: slots hide on mobile, tablet, desktop, and mobile plus tablet.',
	'shape'       => 'saved',
	'options'     => [ 'quick_adsense_settings' => $settings ],
];
