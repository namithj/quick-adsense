<?php
/**
 * 09: a site that only uses sidebar widgets.
 *
 * All on-post codes are empty, ten widget codes are filled, widgets 1-6 are
 * assigned across the two test sidebars and 7-10 are registered but unassigned.
 * disable_widgets_on_homepage is absent, which freezes the undefined-key
 * warning when a widget renders on the blog index (C-4.2).
 */
$settings = [
	'max_ads_per_page'                  => '3',
	'enable_on_posts'                   => '1',
	'enable_on_pages'                   => '1',
	'enable_position_beginning_of_post' => '1',
	'ad_beginning_of_post'              => '1',
];
for ( $i = 1; $i <= 10; $i++ ) {
	$settings[ 'onpost_ad_' . $i . '_content' ] = '';
	$settings[ 'widget_ad_' . $i . '_content' ] = QA_AD( 'widget-' . $i );
}

return [
	'slug'        => '09-widgets-only',
	'description' => 'Sidebar-only ads: widgets 1-6 assigned to the two test sidebars, 7-10 registered but unassigned.',
	'shape'       => 'saved',
	'options'     => [ 'quick_adsense_settings' => $settings ],
	'widgets'     => [
		'sidebar-1' => [ 1, 2, 3 ],
		'sidebar-2' => [ 4, 5, 6 ],
	],
];
