/**
 * Generate WordPress Playground blueprints for Quick Adsense.
 *
 * Commands:
 *   bundle <output-path>                     Blueprint referencing a bundled zip.
 *   release-url <owner/repo> <tag>           Playground URL for a published release.
 */

import { writeFile } from 'node:fs/promises';

const PLUGIN_SLUG = 'quick-adsense';

/**
 * A demo-only must-use plugin makes the configured widget path visible even
 * when Playground's current default theme has no classic widget sidebar.
 */
const demoHelper = `<?php
/** Quick Adsense Playground helper. */
add_filter(
	'the_content',
	static function ( $content ) {
		if ( ! is_page( 'quick-adsense-demo' ) || ! in_the_loop() || ! is_main_query() || ! class_exists( 'QuickAdsenseAdWidget' ) ) {
			return $content;
		}

		ob_start();
		$widget = new QuickAdsenseAdWidget( 1 );
		$widget->widget(
			array(
				'widget_name'  => 'AdsWidget1 (Quick Adsense)',
				'before_widget' => '<div class="quick-adsense-demo-widget">',
				'after_widget'  => '</div>',
			),
			array()
		);
		$widget_output = ob_get_clean();

		return $content . '<h2>Widget rendering path</h2><p>This final creative is rendered through the plugin widget class.</p>' . $widget_output;
	},
	20
);
`;

/** PHP executed inside Playground after the plugin is activated. */
const demoSetup = `<?php
require_once '/wordpress/wp-load.php';

$banner = static function ( $label, $colour, $slot ) {
	return '<div data-quick-adsense-demo-slot="' . $slot . '" style="background:' . $colour
		. ';color:#fff;font:600 16px/1.4 system-ui,sans-serif;padding:18px;margin:14px 0;'
		. 'text-align:center;border-radius:6px;">' . $label
		. '<br><span style="font-weight:400;font-size:13px;opacity:.85;">Demo creative &mdash; your real HTML or JavaScript goes here</span></div>';
};

$settings = function_exists( 'quick_adsense_get_defaults' ) ? quick_adsense_get_defaults() : array();

// The visitor is logged in because Playground opens wp-admin automatically.
// This option is presence-based in legacy Quick Adsense, so remove it entirely.
unset( $settings['disable_for_loggedin_users'] );

$settings['max_ads_per_page']                  = '10';
$settings['enable_on_pages']                   = '1';
$settings['enable_position_beginning_of_post'] = '1';
$settings['ad_beginning_of_post']              = '1';
$settings['enable_position_middle_of_post']    = '1';
$settings['ad_middle_of_post']                 = '2';
$settings['enable_position_end_of_post']       = '1';
$settings['ad_end_of_post']                    = '3';
$settings['enable_position_before_last_para']  = '1';
$settings['ad_before_last_para']               = '4';

for ( $index = 1; $index <= 3; $index++ ) {
	$settings[ 'enable_position_after_para_option_' . $index ] = '1';
	$settings[ 'ad_after_para_option_' . $index ]              = (string) ( $index + 4 );
	$settings[ 'position_after_para_option_' . $index ]        = (string) ( $index * 2 );
}

$settings['enable_position_after_image_option_1'] = '1';
$settings['ad_after_image_option_1']              = '8';
$settings['position_after_image_option_1']        = '1';

$labels = array(
	1  => 'Beginning of page',
	2  => 'Middle of page',
	3  => 'End of page',
	4  => 'Before the last paragraph',
	5  => 'After paragraph 2',
	6  => 'After paragraph 4',
	7  => 'After paragraph 6',
	8  => 'After the first image',
	9  => 'Manual Ads9 quicktag',
	10 => 'Manual Ads10 quicktag with inline script',
);

$colours = array( '#2271b1', '#3858e9', '#8c1a5c', '#0a4b78', '#008a20' );
foreach ( $labels as $index => $label ) {
	$creative = $banner( $label, $colours[ ( $index - 1 ) % count( $colours ) ], $index );
	$settings[ 'onpost_ad_' . $index . '_content' ]   = $creative;
	$settings[ 'onpost_ad_' . $index . '_alignment' ] = '4';
	$settings[ 'onpost_ad_' . $index . '_margin' ]    = '0';
}
$settings['onpost_ad_10_content'] .= '<script>document.documentElement.dataset.quickAdsenseInlineAd="executed";</script>';

$settings['widget_ad_1_content'] = $banner( 'Sidebar widget ad', '#d63638', 'widget-1' );
$settings['header_embed_code']   = '<script id="quick-adsense-demo-header">document.documentElement.dataset.quickAdsenseHeader="executed";</script>';
$settings['footer_embed_code']   = '<div data-quick-adsense-demo-slot="footer" style="background:#1d2327;color:#fff;padding:12px;text-align:center;">Footer embed-code path</div>';

update_option( 'quick_adsense_settings', $settings );

$content = "<!-- wp:paragraph --><p>This page exercises Quick Adsense with self-contained dummy creatives. Open <strong>Quick Adsense</strong> in wp-admin to edit the same values.</p><!-- /wp:paragraph -->\n\n"
	. "<!-- wp:paragraph --><p>The first automatic paragraph placement follows this paragraph.</p><!-- /wp:paragraph -->\n\n"
	. "<!-- wp:image --><figure class='wp-block-image'><img src='https://s.w.org/images/core/5.3/MtBlanc1.jpg' alt='Quick Adsense placement test' /></figure><!-- /wp:image -->\n\n"
	. "<!-- wp:paragraph --><p>The image placement is inserted immediately after the image element.</p><!-- /wp:paragraph -->\n\n"
	. "<!-- wp:paragraph --><p>Additional paragraphs make midpoint and numbered placement behavior visible.</p><!-- /wp:paragraph -->\n\n"
	. "<!-- wp:paragraph --><p>The next coloured creative comes from another configured paragraph rule.</p><!-- /wp:paragraph -->\n\n"
	. "<!-- wp:paragraph --><p>Every creative identifies the insertion path and stored ad slot.</p><!-- /wp:paragraph -->\n\n"
	. "<!-- wp:html --><!--Ads9--><!-- /wp:html -->\n\n"
	. "<!-- wp:paragraph --><p>The previous creative uses the manual Ads9 editor quicktag.</p><!-- /wp:paragraph -->\n\n"
	. "<!-- wp:html --><!--Ads10--><!-- /wp:html -->\n\n"
	. "<!-- wp:paragraph --><p>The Ads10 creative also runs a harmless inline script, proving executable ad code remains supported.</p><!-- /wp:paragraph -->\n\n"
	. "<!-- wp:paragraph --><p>This is the final paragraph used by the before-last-paragraph placement.</p><!-- /wp:paragraph -->";

$existing = get_page_by_path( 'quick-adsense-demo', OBJECT, 'page' );
$demo_id  = $existing instanceof WP_Post ? $existing->ID : 0;
$postarr  = array(
	'post_title'   => 'Quick Adsense Playground demo',
	'post_name'    => 'quick-adsense-demo',
	'post_content' => $content,
	'post_status'  => 'publish',
	'post_type'    => 'page',
);

if ( $demo_id ) {
	$postarr['ID'] = $demo_id;
	$demo_id       = wp_update_post( $postarr, true );
} else {
	$demo_id = wp_insert_post( $postarr, true );
}

if ( is_wp_error( $demo_id ) ) {
	throw new RuntimeException( 'Could not create the Quick Adsense demo page: ' . $demo_id->get_error_message() );
}

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', (int) $demo_id );
`;

export function createBlueprint( { pluginResource } ) {
	return {
		$schema: 'https://playground.wordpress.net/blueprint-schema.json',
		meta: {
			title: 'Quick Adsense demo',
			description: 'Quick Adsense with dummy ads configured across automatic, manual, widget, header and footer paths.',
			author: 'namithjawahar',
			categories: [ 'demo' ],
		},
		preferredVersions: {
			php: '8.2',
			wp: 'latest',
		},
		features: {
			networking: true,
		},
		landingPage: '/',
		login: true,
		steps: [
			{
				step: 'installPlugin',
				pluginData: pluginResource,
				options: {
					activate: true,
					targetFolderName: PLUGIN_SLUG,
				},
			},
			{
				step: 'writeFile',
				path: '/wordpress/wp-content/mu-plugins/quick-adsense-playground-demo.php',
				data: demoHelper,
			},
			{
				step: 'runPHP',
				code: demoSetup,
			},
		],
	};
}

function encodePathSegment( value ) {
	return encodeURIComponent( value ).replaceAll( '%2F', '/' );
}

function encodeRepository( repository ) {
	return repository.split( '/' ).map( encodeURIComponent ).join( '/' );
}

function toPlaygroundUrl( blueprint ) {
	const encoded = Buffer.from( JSON.stringify( blueprint ) ).toString( 'base64' );
	return `https://playground.wordpress.net/#${ encoded }`;
}

async function main() {
	const [ command, ...args ] = process.argv.slice( 2 );

	if ( command === 'bundle' ) {
		const [ outputPath ] = args;
		if ( ! outputPath ) {
			throw new Error( 'Usage: playground-blueprint.mjs bundle <output-path>' );
		}
		await writeFile(
			outputPath,
			`${ JSON.stringify( createBlueprint( { pluginResource: { resource: 'bundled', path: `/${ PLUGIN_SLUG }.zip` } } ), null, 2 ) }\n`
		);
		return;
	}

	if ( command === 'release-url' ) {
		const [ repository, tag ] = args;
		if ( ! repository || ! tag ) {
			throw new Error( 'Usage: playground-blueprint.mjs release-url <owner/repository> <tag>' );
		}
		const url = `https://github.com/${ encodeRepository( repository ) }/releases/download/${ encodePathSegment( tag ) }/${ PLUGIN_SLUG }.zip`;
		process.stdout.write( toPlaygroundUrl( createBlueprint( { pluginResource: { resource: 'url', url } } ) ) );
		return;
	}

	throw new Error( 'Usage: playground-blueprint.mjs <bundle|release-url> [arguments]' );
}

if ( import.meta.url === `file://${ process.argv[ 1 ] }` ) {
	main().catch( ( error ) => {
		console.error( error.message );
		process.exitCode = 1;
	} );
}
