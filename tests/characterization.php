<?php
/**
 * Ad-code compatibility characterization tests.
 *
 * Run with: php tests/characterization.php
 */

require_once __DIR__ . '/bootstrap.php';

function quick_adsense_test_assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		throw new RuntimeException(
			$message . "\nExpected:\n" . var_export( $expected, true ) . "\nActual:\n" . var_export( $actual, true )
		);
	}
}

function quick_adsense_test_assert_contains( $needle, $haystack, $message ) {
	if ( false === strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . "\nMissing:\n" . $needle . "\nOutput:\n" . $haystack );
	}
}

function quick_adsense_test_action_output( $hook ) {
	ob_start();
	foreach ( $GLOBALS['quick_adsense_test_actions'][ $hook ] ?? [] as $callback ) {
		$callback();
	}
	return ob_get_clean();
}

function quick_adsense_test_filter( $hook, $content ) {
	foreach ( $GLOBALS['quick_adsense_test_filters'][ $hook ] ?? [] as $callback ) {
		$content = $callback( $content );
	}
	return $content;
}

$ad_code = <<<'HTML'
<script async src="https://ads.example.test/loader.js?client=ca-pub-123456" crossorigin="anonymous"></script>
<ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-123456" data-ad-slot="987654"></ins>
<script>window.qaAd = {"quote":"\"double\" and 'single'", "path":"C:\\ads\\slot"};</script>
HTML;

$settings = [
	'max_ads_per_page'                     => '3',
	'enable_on_posts'                      => '1',
	'enable_position_beginning_of_post'    => '1',
	'ad_beginning_of_post'                 => '1',
	'enable_position_middle_of_post'       => '0',
	'ad_middle_of_post'                    => '0',
	'enable_position_end_of_post'          => '0',
	'ad_end_of_post'                       => '0',
	'enable_position_after_more_tag'       => '0',
	'ad_after_more_tag'                    => '0',
	'enable_position_before_last_para'     => '0',
	'ad_before_last_para'                  => '0',
	'enable_position_after_image_option_1' => '0',
	'onpost_ad_1_content'                  => $ad_code,
	'onpost_ad_1_alignment'                => '4',
	'onpost_ad_1_margin'                   => '0',
	'widget_ad_1_content'                  => $ad_code,
	'header_embed_code'                    => $ad_code,
	'footer_embed_code'                    => $ad_code,
	'disable_widgets_on_homepage'          => '0',
];

for ( $i = 1; $i <= 3; $i++ ) {
	$settings[ 'enable_position_after_para_option_' . $i ] = '0';
	$settings[ 'ad_after_para_option_' . $i ]              = '0';
}

$GLOBALS['quick_adsense_test_options']['quick_adsense_settings'] = $settings;

// Settings controls may entity-escape their HTML source, but their textarea
// value must round-trip byte-for-byte so saving it does not change ad code.
$control = quickadsense_get_control( 'textarea-big', '', 'ad-code', 'ad-code', $ad_code );
preg_match( '/>(.*)<\/textarea>$/s', $control, $textarea_match );
quick_adsense_test_assert_same( $ad_code, html_entity_decode( $textarea_match[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8' ), 'The ad-code textarea must round-trip quotes, backslashes, HTML, and JavaScript exactly.' );
$header_footer_controls = quick_adsense_load_file(
	'templates/section-header-footer.php',
	[
		'header_embed_code' => $ad_code,
		'footer_embed_code' => $ad_code,
	]
);
quick_adsense_test_assert_same( 2, substr_count( html_entity_decode( $header_footer_controls, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), $ad_code ), 'Header and footer settings controls must each round-trip the exact saved ad code.' );

$appearance_controls = quick_adsense_load_file( 'templates/block-general-appearance.php', $settings );
quick_adsense_test_assert_contains( '<b id="quick_adsense_settings_enable_on_posts_label">Posts</b>', $appearance_controls, 'Plugin-owned label markup must render as markup, not visible HTML source.' );

$widget_controls = quick_adsense_load_file(
	'templates/adunit-widget.php',
	[
		'adunit_index'        => 1,
		'widget_ad_1_content' => $ad_code,
	]
);
quick_adsense_test_assert_contains( 'id="quick_adsense_settings_widget_ad_1_content"', $widget_controls, 'Widget ad units must render an editable textarea.' );
preg_match( '/<textarea[^>]*id="quick_adsense_settings_widget_ad_1_content"[^>]*>(.*)<\/textarea>/s', $widget_controls, $widget_textarea_match );
quick_adsense_test_assert_same( $ad_code, html_entity_decode( $widget_textarea_match[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8' ), 'The widget textarea must preserve its saved ad code exactly.' );
// Header and footer are independent frontend output paths. These assertions pin
// compatibility filtering boundary as well as raw JavaScript/HTML character preservation.
quick_adsense_test_assert_same( $ad_code, quick_adsense_test_action_output( 'wp_head' ), 'Header embed output must preserve the compatible ad payload exactly.' );
quick_adsense_test_assert_same( $ad_code, quick_adsense_test_action_output( 'wp_footer' ), 'Footer embed output must preserve the compatible ad payload exactly.' );

// Quick AdSense does not register WordPress shortcodes; its equivalent is the
// editor quicktag comment. Test the helper that replaces that marker directly.
$replaced = quick_adsense_content_replace_ads( '<p>Before</p><!--CusAds1--><p>After</p>', 'CusAds1', 1 );
quick_adsense_test_assert_contains( $ad_code, $replaced, 'Quicktag insertion must preserve the exact ad payload.' );
quick_adsense_test_assert_contains( '<p>Before</p>', $replaced, 'Quicktag insertion must retain content preceding the marker.' );
quick_adsense_test_assert_contains( '<p>After</p>', $replaced, 'Quicktag insertion must retain content following the marker.' );

// Exercise the registered the_content insertion callback as a separate path.
$GLOBALS['quick_adsense_ads_displayed'] = 0;
$GLOBALS['quick_adsense_ads_id']        = [];
$GLOBALS['quick_adsense_begin_end']     = 0;
$inserted                               = quick_adsense_test_filter( 'the_content', '<p>Post body</p>' );
quick_adsense_test_assert_contains( $ad_code, $inserted, 'Automatic post insertion must preserve the exact ad payload.' );

// Widget rendering filters its output separately from post insertion.
$widget                                = new QuickAdsenseAdWidget( 1 );
$GLOBALS['quick_adsense_test_content'] = '<p>Widget host post</p>';
ob_start();
$widget->widget(
	[
		'widget_name'   => 'AdsWidget1 (Quick Adsense)',
		'before_widget' => '<aside class="quick-adsense-widget">',
		'after_widget'  => '</aside>',
	],
	[]
);
$widget_output = ob_get_clean();
quick_adsense_test_assert_contains( $ad_code, $widget_output, 'Widget output must preserve the exact compatible ad payload.' );
quick_adsense_test_assert_contains( '<aside class="quick-adsense-widget">', $widget_output, 'Widget output must retain its opening wrapper.' );
quick_adsense_test_assert_contains( '</aside>', $widget_output, 'Widget output must retain its closing wrapper.' );

echo "Characterization tests passed (settings, header, footer, quicktag, automatic insertion, widget).\n";
