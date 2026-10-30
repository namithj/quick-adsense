<?php
/**
 * 20: everything at once.
 *
 * Ten mixed payloads with mixed targeting, stats on slots 1-3, four assigned
 * widgets, header and footer code, every page type on, "show all", a limit of 6
 * and the ads.txt notice dismissed. Seven positions are enabled with distinct
 * slots that all pass targeting in the default scenario, so single-long shows
 * six ads. This is the witness for C-3.3: with four assigned widgets, a working
 * widget deduction would show two.
 */
$payloads = [
	1  => 'adsense-async-1',
	2  => 'adsense-legacy',
	3  => 'gam-gpt',
	4  => 'amazon-iframe',
	5  => 'medianet',
	6  => 'adsense-async-6',
	7  => 'adsense-async-7',
	8  => 'adsense-auto-ads',
	9  => 'adsense-async-9',
	10 => 'adsense-async-10',
];
$settings = [
	'max_ads_per_page'                      => '6',
	'enable_on_posts'                       => '1',
	'enable_on_pages'                       => '1',
	'enable_on_homepage'                    => '1',
	'enable_on_categories'                  => '1',
	'enable_on_archives'                    => '1',
	'enable_on_tags'                        => '1',
	'enable_all_possible_ads'               => '1',
	'enable_quicktag_buttons'               => '1',
	'enable_position_beginning_of_post'     => '1',
	'ad_beginning_of_post'                  => '1',
	'enable_position_middle_of_post'        => '1',
	'ad_middle_of_post'                     => '2',
	'enable_position_end_of_post'           => '1',
	'ad_end_of_post'                        => '3',
	'enable_position_before_last_para'      => '1',
	'ad_before_last_para'                   => '4',
	'enable_position_after_para_option_1'   => '1',
	'ad_after_para_option_1'                => '5',
	'position_after_para_option_1'          => '2',
	'enable_position_after_para_option_2'   => '1',
	'ad_after_para_option_2'                => '6',
	'position_after_para_option_2'          => '4',
	'enable_position_after_para_option_3'   => '1',
	'ad_after_para_option_3'                => '7',
	'position_after_para_option_3'          => '6',
	'header_embed_code'                     => QA_AD( 'analytics-gtag' ),
	'footer_embed_code'                     => QA_AD( 'medianet' ),
	// Targeting that does not apply in the default (guest, Chrome, no referer, no address) scenario.
	'onpost_ad_8_hide_device_mobile'        => '1',
	'onpost_ad_9_hide_visitor_searchengine' => '1',
	'onpost_ad_10_limit_visitor_country'    => [ 'US' ],
	'onpost_ad_4_hide_visitor_bot'          => '1',
	'onpost_ad_5_hide_device_tablet'        => '1',
	'onpost_ad_1_enable_stats'              => '1',
	'onpost_ad_2_enable_stats'              => '1',
	'onpost_ad_3_enable_stats'              => '1',
];
for ( $i = 1; $i <= 10; $i++ ) {
	$settings[ 'onpost_ad_' . $i . '_content' ]   = QA_AD( $payloads[ $i ] );
	$settings[ 'onpost_ad_' . $i . '_alignment' ] = (string) ( ( ( $i + 1 ) % 4 ) + 1 );
	$settings[ 'onpost_ad_' . $i . '_margin' ]    = (string) ( $i * 3 );
}
for ( $i = 1; $i <= 4; $i++ ) {
	$settings[ 'widget_ad_' . $i . '_content' ] = QA_AD( 'widget-' . $i );
}

return [
	'slug'        => '20-power-user',
	'description' => 'Everything at once: 10 mixed slots, targeting, stats, 4 assigned widgets, header/footer, max 6.',
	'shape'       => 'saved',
	'ajax'        => true,
	'options'     => [
		'quick_adsense_settings'          => $settings,
		'quick_adsense_onpost_ad_1_stats' => QA_STATS( 5, 2 ),
		'quick_adsense_adstxt_adsense_admin_notice_dismissed' => 'true',
	],
	'widgets'     => [
		'sidebar-1' => [ 1, 2 ],
		'sidebar-2' => [ 3, 4 ],
	],
];
