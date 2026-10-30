<?php
/**
 * 15: a site that uses the built-in stats.
 *
 * Slots 1-3 have stats on. Slots 1 and 2 already hold 30 days of history (D-30
 * to D-1, built relative to today); slot 3 has none. Every render adds one
 * impression, trims the array to 30 entries by dropping the oldest inserted one
 * (C-1), and the click, chart and reset AJAX paths are exercised on top.
 */
$settings = [
	'max_ads_per_page'                    => '3',
	'enable_on_posts'                     => '1',
	'enable_on_pages'                     => '1',
	'enable_position_beginning_of_post'   => '1',
	'ad_beginning_of_post'                => '1',
	'enable_position_end_of_post'         => '1',
	'ad_end_of_post'                      => '2',
	'enable_position_after_para_option_1' => '1',
	'ad_after_para_option_1'              => '3',
	'position_after_para_option_1'        => '2',
	'onpost_ad_1_enable_stats'            => '1',
	'onpost_ad_2_enable_stats'            => '1',
	'onpost_ad_3_enable_stats'            => '1',
];
for ( $i = 1; $i <= 3; $i++ ) {
	$settings[ 'onpost_ad_' . $i . '_content' ]   = QA_AD( 'adsense-async-' . $i );
	$settings[ 'onpost_ad_' . $i . '_alignment' ] = '2';
	$settings[ 'onpost_ad_' . $i . '_margin' ]    = '10';
}

return [
	'slug'        => '15-stats-history',
	'description' => 'Uses the built-in stats: slots 1-3 with stats, 30 days of history on slots 1 and 2.',
	'shape'       => 'saved',
	'ajax'        => true,
	'options'     => [
		'quick_adsense_settings'          => $settings,
		'quick_adsense_onpost_ad_1_stats' => QA_STATS( 30, 1 ),
		'quick_adsense_onpost_ad_2_stats' => QA_STATS( 30, 3 ),
	],
];
