<?php
/**
 * 16: layout edge cases.
 *
 * Eight slots cover alignments 1-4 with margins 0 and 10000. The global style
 * keys are present, but they are admin-JS only and must not affect rendering
 * (C-2). Eight positions are used (no after-image), so the config differs from
 * config 05.
 */
$settings = [
	'max_ads_per_page'                    => '8',
	'enable_on_posts'                     => '1',
	'enable_on_pages'                     => '1',
	'onpost_enable_global_style'          => '1',
	'onpost_global_alignment'             => '3',
	'onpost_global_margin'                => '7',
	'enable_position_beginning_of_post'   => '1',
	'ad_beginning_of_post'                => '1',
	'enable_position_end_of_post'         => '1',
	'ad_end_of_post'                      => '2',
	'enable_position_after_para_option_1' => '1',
	'ad_after_para_option_1'              => '3',
	'position_after_para_option_1'        => '1',
	'enable_position_after_para_option_2' => '1',
	'ad_after_para_option_2'              => '4',
	'position_after_para_option_2'        => '2',
	'enable_position_after_para_option_3' => '1',
	'ad_after_para_option_3'              => '5',
	'position_after_para_option_3'        => '3',
	'enable_position_before_last_para'    => '1',
	'ad_before_last_para'                 => '6',
	'enable_position_middle_of_post'      => '1',
	'ad_middle_of_post'                   => '7',
	'enable_position_after_more_tag'      => '1',
	'ad_after_more_tag'                   => '8',
];
for ( $i = 1; $i <= 8; $i++ ) {
	$settings[ 'onpost_ad_' . $i . '_content' ]   = QA_AD( 'adsense-async-' . $i );
	$settings[ 'onpost_ad_' . $i . '_alignment' ] = (string) ( ( ( $i - 1 ) % 4 ) + 1 );
	$settings[ 'onpost_ad_' . $i . '_margin' ]    = $i <= 4 ? '0' : '10000';
}

return [
	'slug'        => '16-alignment-margins',
	'description' => 'Layout edge cases: alignments 1-4 with margins 0 and 10000; global style keys present but unused.',
	'shape'       => 'saved',
	'options'     => [ 'quick_adsense_settings' => $settings ],
];
