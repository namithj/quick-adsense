<?php
/**
 * 14: country targeting.
 *
 * Slot 1 shows in US and CA, slot 2 in IN, slot 3 in DE, FR and GB, and slot 4
 * has an empty list (no restriction). Resolved by the bundled GeoIP data from
 * REMOTE_ADDR only (C-3.10); unresolvable and private addresses hide every
 * restricted slot, and an empty REMOTE_ADDR skips the check.
 *
 * The addresses used by the geo-* scenarios were picked at baseline time and
 * the countries they resolved to are recorded in 'geo_expect'. The test fails
 * if the bundled data later resolves them differently.
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
	'onpost_ad_1_limit_visitor_country'   => [ 'US', 'CA' ],
	'onpost_ad_2_limit_visitor_country'   => [ 'IN' ],
	'onpost_ad_3_limit_visitor_country'   => [ 'DE', 'FR', 'GB' ],
	'onpost_ad_4_limit_visitor_country'   => [],
];
for ( $i = 1; $i <= 4; $i++ ) {
	$settings[ 'onpost_ad_' . $i . '_content' ]   = QA_AD( 'adsense-async-' . $i );
	$settings[ 'onpost_ad_' . $i . '_alignment' ] = '2';
	$settings[ 'onpost_ad_' . $i . '_margin' ]    = '10';
}

return [
	'slug'        => '14-geo-targeting',
	'description' => 'Country targeting: US/CA, IN, DE/FR/GB and an empty list, resolved from REMOTE_ADDR.',
	'shape'       => 'saved',
	'options'     => [ 'quick_adsense_settings' => $settings ],
	'geo_expect'  => [
		'us'      => 'US',
		'in'      => 'IN',
		'de'      => 'DE',
		'unknown' => 'ZZ',
	],
];
