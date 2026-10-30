<?php
/**
 * 08: a photo blog.
 *
 * After-image is on with slot 2. The "images" post has a plain image, a linked
 * image, a [caption] image, an uppercase <IMG> and an <a><IMG></A> pair. The
 * base config places the ad after the 4th image (uppercase, jump on). Three
 * variants cover the other branches: the 1st (plain, jump off), the 2nd (linked,
 * jump on) and the 3rd (caption, jump on).
 */
$base = [
	'max_ads_per_page'                          => '3',
	'enable_on_posts'                           => '1',
	'enable_on_pages'                           => '1',
	'enable_position_beginning_of_post'         => '1',
	'ad_beginning_of_post'                      => '1',
	'enable_position_after_image_option_1'      => '1',
	'ad_after_image_option_1'                   => '2',
	'position_after_image_option_1'             => '4',
	'enable_jump_position_after_image_option_1' => '1',
	'onpost_ad_1_content'                       => QA_AD( 'adsense-async-1' ),
	'onpost_ad_1_alignment'                     => '2',
	'onpost_ad_1_margin'                        => '10',
	'onpost_ad_2_content'                       => QA_AD( 'adsense-async-2' ),
	'onpost_ad_2_alignment'                     => '2',
	'onpost_ad_2_margin'                        => '10',
];

$without_jump = $base;
unset( $without_jump['enable_jump_position_after_image_option_1'] );

return [
	'slug'        => '08-image-placement',
	'description' => 'Photo blog: slots 1-2, after-image with slot 2; variants cover plain, linked and caption images.',
	'shape'       => 'saved',
	'options'     => [ 'quick_adsense_settings' => $base ],
	'variants'    => [
		'img1-nojump' => [
			'quick_adsense_settings' => array_merge( $without_jump, [ 'position_after_image_option_1' => '1' ] ),
		],
		'img2-jump'   => [
			'quick_adsense_settings' => array_merge( $base, [ 'position_after_image_option_1' => '2' ] ),
		],
		'img3-jump'   => [
			'quick_adsense_settings' => array_merge( $base, [ 'position_after_image_option_1' => '3' ] ),
		],
	],
];
