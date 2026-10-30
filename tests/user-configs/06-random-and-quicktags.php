<?php
/**
 * 06: a site that relies on random placement and editor quicktags.
 *
 * Every position is on with ad '0' (Random). The Random branch is never taken
 * (C-3.4): each position leaves a <!--CusAds0--> stand-in that nothing fills
 * and the cleaner does not remove. Only <!--RndAds--> markers in a post fill
 * slots at random. The scenarios render every marker post.
 */
$settings = [
	'max_ads_per_page'                     => '5',
	'enable_on_posts'                      => '1',
	'enable_on_pages'                      => '1',
	'enable_quicktag_buttons'              => '1',
	'enable_position_beginning_of_post'    => '1',
	'ad_beginning_of_post'                 => '0',
	'enable_position_middle_of_post'       => '1',
	'ad_middle_of_post'                    => '0',
	'enable_position_end_of_post'          => '1',
	'ad_end_of_post'                       => '0',
	'enable_position_after_more_tag'       => '1',
	'ad_after_more_tag'                    => '0',
	'enable_position_before_last_para'     => '1',
	'ad_before_last_para'                  => '0',
	'enable_position_after_para_option_1'  => '1',
	'ad_after_para_option_1'               => '0',
	'position_after_para_option_1'         => '2',
	'enable_position_after_para_option_2'  => '1',
	'ad_after_para_option_2'               => '0',
	'position_after_para_option_2'         => '4',
	'enable_position_after_para_option_3'  => '1',
	'ad_after_para_option_3'               => '0',
	'position_after_para_option_3'         => '6',
	'enable_position_after_image_option_1' => '1',
	'ad_after_image_option_1'              => '0',
	'position_after_image_option_1'        => '1',
];
for ( $i = 1; $i <= 6; $i++ ) {
	$settings[ 'onpost_ad_' . $i . '_content' ]   = QA_AD( 'adsense-async-' . $i );
	$settings[ 'onpost_ad_' . $i . '_alignment' ] = '2';
	$settings[ 'onpost_ad_' . $i . '_margin' ]    = '10';
}

return [
	'slug'        => '06-random-and-quicktags',
	'description' => 'Relies on random placement and quicktags: every position set to Random, slots 1-6, quicktag buttons on.',
	'shape'       => 'saved',
	'options'     => [ 'quick_adsense_settings' => $settings ],
];
