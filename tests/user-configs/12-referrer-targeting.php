<?php
/**
 * 12: traffic-source targeting.
 *
 * Slot 1 hides from search-engine visitors, slot 2 from direct visitors (empty
 * referer), slot 3 from indirect visitors (any other referer, including the
 * site itself), slot 4 has no rule. Rendered with no referer, Google, Bing and
 * an unrelated site (C-3.10).
 */
$settings = [
	'max_ads_per_page'                      => '4',
	'enable_on_posts'                       => '1',
	'enable_on_pages'                       => '1',
	'enable_position_beginning_of_post'     => '1',
	'ad_beginning_of_post'                  => '1',
	'enable_position_middle_of_post'        => '1',
	'ad_middle_of_post'                     => '2',
	'enable_position_end_of_post'           => '1',
	'ad_end_of_post'                        => '3',
	'enable_position_after_para_option_1'   => '1',
	'ad_after_para_option_1'                => '4',
	'position_after_para_option_1'          => '3',
	'onpost_ad_1_hide_visitor_searchengine' => '1',
	'onpost_ad_2_hide_visitor_direct'       => '1',
	'onpost_ad_3_hide_visitor_indirect'     => '1',
];
for ( $i = 1; $i <= 4; $i++ ) {
	$settings[ 'onpost_ad_' . $i . '_content' ]   = QA_AD( 'adsense-async-' . $i );
	$settings[ 'onpost_ad_' . $i . '_alignment' ] = '1';
	$settings[ 'onpost_ad_' . $i . '_margin' ]    = '8';
}

return [
	'slug'        => '12-referrer-targeting',
	'description' => 'Referrer targeting: hide from search engines, direct, indirect visitors; slot 4 unrestricted.',
	'shape'       => 'saved',
	'options'     => [ 'quick_adsense_settings' => $settings ],
];
