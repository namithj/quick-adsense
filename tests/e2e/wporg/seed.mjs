/**
 * Blueprint for the WordPress.org listing screenshots: a fictional hiking blog
 * ("Trail Notes") with placeholder ad code only (ads.example.com, no network, no real IDs).
 */
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname( fileURLToPath( import.meta.url ) );

// Demo-only helper: hides the admin bar for the front-end shot and notices/promos in wp-admin.
const muPlugin = `<?php
/** Quick Adsense listing screenshots: tidy chrome. */
add_filter( 'show_admin_bar', '__return_false' );
add_action( 'admin_head', static function () {
	echo '<style>#wpbody-content > .notice, .wrap > .notice:not(.inline), .update-nag, #wpfooter, .notice-info, .notice-warning { display:none !important; }</style>';
} );
add_action( 'admin_init', static function () {
	remove_all_actions( 'admin_notices' );
	remove_all_actions( 'all_admin_notices' );
}, 99 );
`;

export function createWporgBlueprint() {
	return {
		$schema: 'https://playground.wordpress.net/blueprint-schema.json',
		landingPage: '/wp-admin/admin.php?page=quick-adsense',
		login: true,
		features: { networking: true },
		preferredVersions: { php: '8.2', wp: 'latest' },
		steps: [
			{ step: 'installTheme', themeData: { resource: 'wordpress.org/themes', slug: 'twentysixteen' }, options: { activate: true } },
			{ step: 'activatePlugin', pluginPath: 'quick-adsense/quick-adsense.php' },
			{ step: 'writeFile', path: '/wordpress/wp-content/mu-plugins/qa-wporg-tidy.php', data: muPlugin },
			{ step: 'mkdir', path: '/wordpress/wp-content/uploads' },
			{ step: 'writeFile', path: '/wordpress/wp-content/uploads/trail-notes-ridge.svg', data: readFileSync( resolve( here, 'ridge.svg' ), 'utf8' ) },
			{ step: 'runPHP', code: readFileSync( resolve( here, 'seed.php' ), 'utf8' ) },
		],
	};
}
