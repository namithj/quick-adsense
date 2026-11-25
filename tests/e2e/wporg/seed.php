<?php
// Demo data for the WordPress.org listing screenshots: a fictional hiking blog, placeholder ad code only.
require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

wp_set_current_user( 1 );
kses_remove_filters();

update_option( 'blogname', 'Trail Notes' );
update_option( 'blogdescription', 'Hikes, gear and quiet corners of the hills' );
update_option( 'timezone_string', 'UTC' );

$settings = function_exists( 'quick_adsense_get_defaults' ) ? quick_adsense_get_defaults() : [];
unset( $settings['disable_for_loggedin_users'] );

$settings['max_ads_per_page']                     = '3';
$settings['enable_on_posts']                      = '1';
$settings['enable_on_pages']                      = '1';
$settings['enable_position_beginning_of_post']    = '1';
$settings['ad_beginning_of_post']                 = '1';
$settings['enable_position_end_of_post']          = '1';
$settings['ad_end_of_post']                       = '3';
$settings['enable_position_after_para_option_1']  = '1';
$settings['ad_after_para_option_1']               = '2';
$settings['position_after_para_option_1']         = '4';
$settings['enable_position_after_image_option_1'] = '0';
$settings['enable_quicktag_buttons']              = '1';

$slots = [
	1 => 'article-top',
	2 => 'article-middle',
	3 => 'article-bottom',
];
foreach ( $slots as $index => $name ) {
	$settings[ 'onpost_ad_' . $index . '_content' ]   = '<script async src="https://ads.example.com/tag.js"></script>' . "\n" . '<div class="ad-slot" data-slot="' . $name . '"></div>';
	$settings[ 'onpost_ad_' . $index . '_alignment' ] = '2';
	$settings[ 'onpost_ad_' . $index . '_margin' ]    = '8';
}
// Slot 1: visibility rules and stats.
$settings['onpost_ad_1_hide_device_tablet']        = '1';
$settings['onpost_ad_1_hide_visitor_bot']          = '1';
$settings['onpost_ad_1_hide_visitor_searchengine'] = '0';
$settings['onpost_ad_1_enable_stats']              = '1';

$settings['widget_ad_1_content'] = '<script async src="https://ads.example.com/tag.js"></script>' . "\n" . '<div class="ad-slot" data-slot="sidebar-top"></div>';
$settings['header_embed_code']   = '<script async src="https://ads.example.com/loader.js" data-site="trail-notes-demo"></script>';
$settings['footer_embed_code']   = '<script>window.adsQueue = window.adsQueue || []; window.adsQueue.push( { site: "trail-notes-demo" } );</script>';

update_option( 'quick_adsense_settings', $settings );

// 30 days of plausible stats for slot 1 (dmY => impressions / clicks), weekends a little busier.
mt_srand( 20261126 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand -- Seeded on purpose so regenerated screenshots show the same chart.
$stats = [];
for ( $i = 29; $i >= 0; $i-- ) {
	$ts                            = strtotime( '-' . $i . ' day' );
	$weekend                       = in_array( (int) gmdate( 'N', $ts ), [ 6, 7 ], true );
	$trend                         = 1 + ( 29 - $i ) * 0.012;
	$base                          = 900 + mt_rand( 0, 260 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Deterministic demo data (seeded above).
	$imp                           = (int) round( $base * ( $weekend ? 1.28 : 1 ) * $trend );
	$ctr                           = 0.011 + mt_rand( 0, 70 ) / 10000; // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Deterministic demo data (seeded above).
	$stats[ gmdate( 'dmY', $ts ) ] = [
		'i' => $imp,
		'c' => (int) round( $imp * $ctr ),
	];
}
update_option( 'quick_adsense_onpost_ad_1_stats', $stats );

// Sidebar widget in the classic theme's sidebar.
update_option(
	'widget_adswidget1-quick-adsense',
	[
		2              => [],
		'_multiwidget' => 1,
	]
);
$sidebars              = get_option( 'sidebars_widgets', [] );
$sidebars['sidebar-1'] = [ 'adswidget1-quick-adsense-2' ];
update_option( 'sidebars_widgets', $sidebars );

// Backups: manual, before upgrade, before import, with believable dates.
$made = [];
foreach ( [
	[ 'upgrade-from-2.10.0', '-21 days', '2.10.0' ],
	[ 'pre-import', '-6 days', defined( 'QUICK_ADSENSE_VERSION' ) ? QUICK_ADSENSE_VERSION : '2.11.0' ],
	[ 'manual', '-1 day', defined( 'QUICK_ADSENSE_VERSION' ) ? QUICK_ADSENSE_VERSION : '2.11.0' ],
] as $row ) {
	$name = \QuickAdsense\Snapshots::create( $row[0] );
	if ( $name ) {
		$payload                   = get_option( $name );
		$payload['created_at']     = gmdate( 'Y-m-d H:i:s', strtotime( $row[1] . ' 09:42' ) );
		$payload['plugin_version'] = $row[2];
		update_option( $name, $payload, false );
	}
}

$img      = content_url( 'uploads/trail-notes-ridge.svg' );
$p        = static function ( $text ) {
	return "<!-- wp:paragraph -->\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->\n\n";
};
$content  = $p( 'There is a particular kind of quiet that only exists on a ridge before eight in the morning. The valley is still full of mist, the sheep have not yet decided to be offended by you, and the only sound is your own boots finding a rhythm on the stone. After a decade of walking the northern fells, these are the five routes I keep returning to when I want that feeling back.' );
$content .= $p( 'None of them are secrets, exactly. They are simply long enough, steep enough or far enough from a car park that most people turn back early. Start before the crowds, carry more water than you think you need, and give yourself the time to stop.' );
$content .= "<!-- wp:image {\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"" . esc_url( $img ) . "\" alt=\"A ridge path at sunrise above a misty valley\"/><figcaption class=\"wp-element-caption\">First light on the ridge path, a little after seven.</figcaption></figure>\n<!-- /wp:image -->\n\n";
$content .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">1. The long way round Blea Tarn</h2>\n<!-- /wp:heading -->\n\n";
$content .= $p( 'Most walkers take the direct path to the tarn and call it a day. Take the old quarry track instead: it adds three kilometres, climbs gently through bracken and rewards you with the best view of the valley at the one point where nobody else ever stands.' );
$content .= $p( 'Pack a flask. The stone shelter just above the second gate is out of the wind, dry, and exactly the right size for two people and a very large sandwich.' );
$content .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">2. Stonethwaite to Rosthwaite by the back path</h2>\n<!-- /wp:heading -->\n\n";
$content .= $p( 'This one is all about the river. The path hugs the water for almost its whole length, crossing it twice on stepping stones that are perfectly fine in July and a different proposition in March. Check the forecast, and trust your boots.' );
$content .= $p( 'If you only have half a day, this is the one to choose. It is flat enough for tired legs, varied enough to stay interesting, and finishes within sight of a pub that serves soup the way soup should be served.' );
$content .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">What I carry</h2>\n<!-- /wp:heading -->\n\n";
$content .= $p( 'A light waterproof, a spare layer, a paper map, a headtorch I hope never to use, and more snacks than any reasonable person needs. Everything else is optional. The best gear is whatever gets you out of the door on a grey Tuesday.' );
$content .= $p( 'See you out there, and leave the gates as you found them.' );

$existing = get_page_by_path( 'five-quiet-trails-worth-the-early-start', OBJECT, 'post' );
$postarr  = [
	'post_title'   => 'Five quiet trails worth the early start',
	'post_name'    => 'five-quiet-trails-worth-the-early-start',
	'post_content' => $content,
	'post_excerpt' => 'Five long, quiet northern routes that reward an early alarm.',
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_author'  => 1,
];
if ( $existing ) {
	$postarr['ID'] = $existing->ID;
}
$qa_post_id = wp_insert_post( $postarr, true );
if ( is_wp_error( $qa_post_id ) ) {
	throw new RuntimeException( esc_html( $qa_post_id->get_error_message() ) );
}
update_option( 'show_on_front', 'posts' );
update_option( 'qa_wporg_post_id', (int) $qa_post_id );
wp_update_user(
	[
		'ID'           => 1,
		'display_name' => 'Maya Ellis',
	]
);
