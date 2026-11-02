<?php
/**
 * 05: a heavy monetiser.
 *
 * All ten slots are filled and every position is on, each with a different
 * specific slot. Every page type is on, "show all possible ads" is on, and the
 * limit is 10.
 */
$settings = [
	'max_ads_per_page'                     => '10',
	'enable_on_posts'                      => '1',
	'enable_on_pages'                      => '1',
	'enable_on_homepage'                   => '1',
	'enable_on_categories'                 => '1',
	'enable_on_archives'                   => '1',
	'enable_on_tags'                       => '1',
	'enable_all_possible_ads'              => '1',
	'enable_position_beginning_of_post'    => '1',
	'ad_beginning_of_post'                 => '1',
	'enable_position_middle_of_post'       => '1',
	'ad_middle_of_post'                    => '2',
	'enable_position_end_of_post'          => '1',
	'ad_end_of_post'                       => '3',
	'enable_position_after_more_tag'       => '1',
	'ad_after_more_tag'                    => '4',
	'enable_position_before_last_para'     => '1',
	'ad_before_last_para'                  => '5',
	'enable_position_after_para_option_1'  => '1',
	'ad_after_para_option_1'               => '6',
	'position_after_para_option_1'         => '1',
	'enable_position_after_para_option_2'  => '1',
	'ad_after_para_option_2'               => '7',
	'position_after_para_option_2'         => '3',
	'enable_position_after_para_option_3'  => '1',
	'ad_after_para_option_3'               => '8',
	'position_after_para_option_3'         => '5',
	'enable_position_after_image_option_1' => '1',
	'ad_after_image_option_1'              => '9',
	'position_after_image_option_1'        => '2',
];
for ( $i = 1; $i <= 10; $i++ ) {
	$settings[ 'onpost_ad_' . $i . '_content' ]   = QA_AD( 'adsense-async-' . $i );
	$settings[ 'onpost_ad_' . $i . '_alignment' ] = (string) ( ( ( $i - 1 ) % 4 ) + 1 );
	$settings[ 'onpost_ad_' . $i . '_margin' ]    = (string) ( 5 + $i );
}

return [
	'slug'        => '05-all-slots-all-positions',
	'description' => 'Heavy monetiser: 10 slots, nine positions with different slots, every page type, show all, max 10.',
	'shape'       => 'saved',
	'options'     => [ 'quick_adsense_settings' => $settings ],
];
