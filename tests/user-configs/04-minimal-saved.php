<?php
/**
 * 04: a typical small blog.
 *
 * Saved shape (2): only what was ticked or chosen is present. One beginning
 * ad, posts only.
 */
return [
	'slug'        => '04-minimal-saved',
	'description' => 'Typical small blog: posts only, one beginning-of-post ad, up to 3 ads.',
	'shape'       => 'saved',
	'options'     => [
		'quick_adsense_settings' => [
			'max_ads_per_page'                  => '3',
			'enable_on_posts'                   => '1',
			'enable_position_beginning_of_post' => '1',
			'ad_beginning_of_post'              => '1',
			'onpost_ad_1_content'               => QA_AD( 'adsense-async-1' ),
			'onpost_ad_1_alignment'             => '2',
			'onpost_ad_1_margin'                => '10',
		],
	],
];
