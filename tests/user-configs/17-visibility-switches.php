<?php
/**
 * 17: a members site.
 *
 * No ads for logged-in users, no widget ads on the blog index, and the
 * quicktag buttons are on with the random-ads and position buttons hidden.
 * Pages only: enable_on_posts is absent, so single posts show no ads.
 */
return [
	'slug'        => '17-visibility-switches',
	'description' => 'Members site: pages only, hidden from logged-in users, widget off on the blog index, trimmed quicktags.',
	'shape'       => 'saved',
	'options'     => [
		'quick_adsense_settings' => [
			'max_ads_per_page'                    => '3',
			'enable_on_pages'                     => '1',
			'disable_for_loggedin_users'          => '1',
			'disable_widgets_on_homepage'         => '1',
			'enable_quicktag_buttons'             => '1',
			'disable_randomads_quicktag_button'   => '1',
			'disable_positionad_quicktag_buttons' => '1',
			'enable_position_beginning_of_post'   => '1',
			'ad_beginning_of_post'                => '1',
			'enable_position_end_of_post'         => '1',
			'ad_end_of_post'                      => '2',
			'onpost_ad_1_content'                 => QA_AD( 'adsense-async-1' ),
			'onpost_ad_1_alignment'               => '2',
			'onpost_ad_1_margin'                  => '10',
			'onpost_ad_2_content'                 => QA_AD( 'adsense-async-2' ),
			'onpost_ad_2_alignment'               => '2',
			'onpost_ad_2_margin'                  => '10',
			'widget_ad_1_content'                 => QA_AD( 'widget-1' ),
		],
	],
	'widgets'     => [
		'sidebar-1' => [ 1 ],
	],
];
