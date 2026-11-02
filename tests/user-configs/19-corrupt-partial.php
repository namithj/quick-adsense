<?php
/**
 * 19: damaged or odd databases.
 *
 * The settings array has a missing max_ads_per_page, strings where numbers are
 * expected ('abc' as a slot), ints where strings are expected (a margin and a
 * paragraph position), a country limit stored as a string, unknown keys from
 * older versions, an out-of-range slot (11) and an alignment with no style.
 * Nothing is hidden: PHP notices and warnings are recorded in each snapshot's
 * "== notices ==" section, and any Throwable is caught per scenario and
 * recorded too.
 *
 * A non-numeric string in a position_* key is deliberately not used: on PHP 8
 * array_slice() throws a TypeError for it, on PHP 7.4 it only warns, so the
 * snapshot would differ between PHP versions (R00 section 5.7).
 *
 * Variants:
 * - max-present: the same damage with max_ads_per_page present, so the engine
 *   actually runs against the odd values.
 * - non-array-string: the whole option is the non-array string 'a:0:{'. init
 *   treats a non-array value as a new install and overwrites it with the
 *   defaults (C-7.4); R01 (F008) lists the fix as an intended change.
 */
$damaged = [
	'enable_on_posts'                     => '1',
	'enable_on_pages'                     => '1',
	'enable_position_beginning_of_post'   => '1',
	'ad_beginning_of_post'                => 'abc',
	'enable_position_end_of_post'         => '1',
	'ad_end_of_post'                      => '2',
	'enable_position_after_para_option_1' => '1',
	'ad_after_para_option_1'              => '3',
	'position_after_para_option_1'        => 3,
	'onpost_ad_1_content'                 => QA_AD( 'adsense-async-1' ),
	'onpost_ad_1_alignment'               => '2',
	'onpost_ad_1_margin'                  => 3,
	'onpost_ad_1_limit_visitor_country'   => 'US',
	'onpost_ad_2_content'                 => QA_AD( 'adsense-async-2' ),
	'onpost_ad_2_alignment'               => '',
	'onpost_ad_2_margin'                  => 'abc',
	'onpost_ad_3_content'                 => QA_AD( 'adsense-async-3' ),
	'onpost_ad_3_alignment'               => '9',
	'onpost_ad_3_margin'                  => '10',
	'onpost_ad_11_content'                => QA_AD( 'adsense-async-4' ),
	'AdsDisp'                             => '4',
	'foo_bar'                             => 'baz',
];

return [
	'slug'        => '19-corrupt-partial',
	'description' => 'Damaged databases: missing and odd values, unknown keys; variants for a non-array option and a present max.',
	'shape'       => 'corrupt',
	'options'     => [ 'quick_adsense_settings' => $damaged ],
	'variants'    => [
		'max-present'      => [
			'quick_adsense_settings' => array_merge( $damaged, [ 'max_ads_per_page' => '5' ] ),
		],
		'non-array-string' => [
			'quick_adsense_settings' => 'a:0:{',
		],
	],
];
