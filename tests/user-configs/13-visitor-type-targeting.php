<?php
/**
 * 13: audience targeting.
 *
 * Slot 1 hides from logged-in users, slot 2 from guests, slot 3 from bots,
 * slot 4 from known browsers and slot 5 from unknown browsers. Together with the
 * standard matrix, the extra scenarios render guest and administrator against
 * Chrome, Googlebot and curl (C-3.10).
 */
$settings = [
	'max_ads_per_page'                        => '5',
	'enable_on_posts'                         => '1',
	'enable_on_pages'                         => '1',
	'enable_position_beginning_of_post'       => '1',
	'ad_beginning_of_post'                    => '1',
	'enable_position_middle_of_post'          => '1',
	'ad_middle_of_post'                       => '2',
	'enable_position_end_of_post'             => '1',
	'ad_end_of_post'                          => '3',
	'enable_position_after_para_option_1'     => '1',
	'ad_after_para_option_1'                  => '4',
	'position_after_para_option_1'            => '2',
	'enable_position_after_para_option_2'     => '1',
	'ad_after_para_option_2'                  => '5',
	'position_after_para_option_2'            => '4',
	'onpost_ad_1_hide_visitor_loggedin'       => '1',
	'onpost_ad_2_hide_visitor_guest'          => '1',
	'onpost_ad_3_hide_visitor_bot'            => '1',
	'onpost_ad_4_hide_visitor_knownbrowser'   => '1',
	'onpost_ad_5_hide_visitor_unknownbrowser' => '1',
];
for ( $i = 1; $i <= 5; $i++ ) {
	$settings[ 'onpost_ad_' . $i . '_content' ]   = QA_AD( 'adsense-async-' . $i );
	$settings[ 'onpost_ad_' . $i . '_alignment' ] = '3';
	$settings[ 'onpost_ad_' . $i . '_margin' ]    = '12';
}

// The standard matrix covers guest + Googlebot/curl and admin + Chrome. These add admin + Googlebot/curl.
$extra = [];
foreach ( [ 'googlebot', 'curl' ] as $ua ) {
	$extra[ 'admin-' . $ua ] = [
		'target' => 'single:long',
		'user'   => 'admin',
		'ua'     => $ua,
	];
}

return [
	'slug'            => '13-visitor-type-targeting',
	'description'     => 'Audience targeting: hide from logged-in, guests, bots, known and unknown browsers.',
	'shape'           => 'saved',
	'options'         => [ 'quick_adsense_settings' => $settings ],
	'extra_scenarios' => $extra,
];
