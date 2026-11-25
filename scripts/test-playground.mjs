/**
 * Smoke test a built Quick Adsense Playground bundle, or the committed wp.org
 * Live Preview blueprint.
 *
 *   node scripts/test-playground.mjs <bundle-zip>
 *   node scripts/test-playground.mjs --wporg <release-zip>
 *
 * The --wporg mode checks .wordpress-org/blueprints/blueprint.json equals the generator
 * output, swaps its wordpress.org plugin resource for the locally built release
 * zip, boots it with @wp-playground/cli and asserts the settings page loads and
 * the demo page shows a placeholder ad.
 */

import { execFileSync, spawn } from 'node:child_process';
import { copyFileSync, existsSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { createBlueprint, SETTINGS_LANDING_PAGE, WPORG_BLUEPRINT_PATH } from './playground-blueprint.mjs';

const REQUIRED_ENTRIES = [ 'blueprint.json', 'quick-adsense.zip' ];

function fail( message ) {
	console.error( `Playground bundle check failed: ${ message }` );
	process.exit( 1 );
}

function readEntry( bundlePath, entry ) {
	return execFileSync( 'unzip', [ '-p', bundlePath, entry ], {
		encoding: 'utf8',
		maxBuffer: 64 * 1024 * 1024,
	} );
}

function listEntries( bundlePath ) {
	return execFileSync( 'unzip', [ '-Z1', bundlePath ], { encoding: 'utf8' } )
		.split( '\n' )
		.map( ( line ) => line.trim() )
		.filter( Boolean );
}

const sleep = ( ms ) => new Promise( ( done ) => setTimeout( done, ms ) );

// Playground's --login sets the auth cookie on a redirect, so follow redirects
// by hand and carry the cookies along.
async function fetchPage( base, path, attempts = 3 ) {
	let lastError;
	for ( let attempt = 0; attempt < attempts; attempt++ ) {
		try {
			const jar = new Map();
			let target = new URL( path, base );
			for ( let hop = 0; hop < 10; hop++ ) {
				const response = await fetch( target, {
					redirect: 'manual',
					headers: { cookie: [ ...jar ].map( ( [ name, value ] ) => `${ name }=${ value }` ).join( '; ' ) },
				} );
				for ( const line of response.headers.getSetCookie() ) {
					const [ pair ] = line.split( ';' );
					const [ name, ...rest ] = pair.split( '=' );
					jar.set( name.trim(), rest.join( '=' ) );
				}
				const location = response.headers.get( 'location' );
				if ( response.status >= 300 && response.status < 400 && location ) {
					target = new URL( location, target );
					continue;
				}
				return { status: response.status, url: target.href, body: await response.text() };
			}
			throw new Error( `too many redirects fetching ${ path }` );
		} catch ( error ) {
			lastError = error;
			await sleep( 2000 );
		}
	}
	fail( `could not fetch ${ path }: ${ lastError?.message }` );
}

async function testWporgBlueprint( releaseZip ) {
	if ( ! existsSync( releaseZip ) ) {
		fail( `release zip not found at ${ releaseZip }` );
	}

	const expected = createBlueprint( {
		pluginResource: { resource: 'wordpress.org/plugins', slug: 'quick-adsense' },
		landingPage: SETTINGS_LANDING_PAGE,
	} );
	const committedText = readFileSync( WPORG_BLUEPRINT_PATH, 'utf8' );
	if ( committedText !== `${ JSON.stringify( expected, null, 2 ) }\n` ) {
		fail( '.wordpress-org/blueprints/blueprint.json is stale. Run: node scripts/playground-blueprint.mjs wporg' );
	}
	if ( /ca-pub-|pub-\d{6,}/.test( committedText ) ) {
		fail( 'wp.org blueprint contains something that looks like a real AdSense publisher ID' );
	}

	const blueprint = JSON.parse( committedText );
	const install = blueprint.steps.find( ( step ) => step.step === 'installPlugin' );
	if ( install?.pluginData?.resource !== 'wordpress.org/plugins' || install.pluginData.slug !== 'quick-adsense' ) {
		fail( 'wp.org blueprint does not install quick-adsense from wordpress.org' );
	}
	if ( blueprint.landingPage !== SETTINGS_LANDING_PAGE ) {
		fail( `wp.org blueprint landing page is ${ blueprint.landingPage }` );
	}
	// Boot against the build under test instead of the published plugin.
	install.pluginData = { resource: 'bundled', path: '/quick-adsense.zip' };

	const workDir = mkdtempSync( join( tmpdir(), 'quick-adsense-wporg-' ) );
	const port = 9400 + Math.floor( Math.random() * 500 );
	const base = `http://127.0.0.1:${ port }`;
	copyFileSync( releaseZip, join( workDir, 'quick-adsense.zip' ) );
	writeFileSync( join( workDir, 'blueprint.json' ), JSON.stringify( blueprint ) );

	let log = '';
	const server = spawn(
		'npx',
		[
			'--yes',
			'@wp-playground/cli@3',
			'server',
			`--blueprint=${ join( workDir, 'blueprint.json' ) }`,
			'--blueprint-may-read-adjacent-files',
			'--login',
			`--port=${ port }`,
		],
		{ stdio: [ 'ignore', 'pipe', 'pipe' ] }
	);
	server.stdout.on( 'data', ( chunk ) => ( log += chunk ) );
	server.stderr.on( 'data', ( chunk ) => ( log += chunk ) );

	let exited = false;
	server.on( 'exit', () => ( exited = true ) );
	// fail() exits the process directly, which would skip the finally block.
	process.on( 'exit', () => server.kill( 'SIGKILL' ) );

	try {
		const deadline = Date.now() + 8 * 60 * 1000;
		while ( ! log.includes( base ) && ! /Ready|listening/i.test( log ) ) {
			if ( exited || Date.now() > deadline ) {
				fail( `Playground server did not start.\n${ log.slice( -2000 ) }` );
			}
			await sleep( 1000 );
		}

		const settings = await fetchPage( base, SETTINGS_LANDING_PAGE );
		if ( settings.status !== 200 || ! settings.url.includes( 'page=quick-adsense' ) ) {
			fail( `settings page did not load (HTTP ${ settings.status }, ended at ${ settings.url })` );
		}
		if ( ! /Quick Adsense/i.test( settings.body ) || /Fatal error|There has been a critical error/i.test( settings.body ) ) {
			fail( 'settings page rendered without the Quick Adsense UI or with a fatal error' );
		}

		const demo = await fetchPage( base, '/?pagename=quick-adsense-demo' );
		if ( demo.status !== 200 || ! demo.body.includes( 'data-quick-adsense-demo-slot="1"' ) ) {
			fail( `demo page does not show the placeholder ad (HTTP ${ demo.status })` );
		}
		if ( /ca-pub-|googlesyndication/.test( demo.body ) ) {
			fail( 'demo page output references AdSense' );
		}
		console.log( 'wp.org blueprint OK — matches the generator, settings page loads, demo page shows the placeholder ad.' );
	} finally {
		server.kill( 'SIGTERM' );
		await sleep( 500 );
		if ( ! exited ) {
			server.kill( 'SIGKILL' );
		}
		rmSync( workDir, { recursive: true, force: true } );
	}
}

const [ firstArg, secondArg ] = process.argv.slice( 2 );
if ( firstArg === '--wporg' ) {
	if ( ! secondArg ) {
		fail( 'Usage: node scripts/test-playground.mjs --wporg <release-zip>' );
	}
	await testWporgBlueprint( secondArg );
	process.exit( 0 );
}

const bundlePath = firstArg;
if ( ! bundlePath ) {
	fail( 'Usage: node scripts/test-playground.mjs <bundle-zip>' );
}
if ( ! existsSync( bundlePath ) ) {
	fail( `bundle not found at ${ bundlePath }` );
}

const entries = listEntries( bundlePath );
for ( const required of REQUIRED_ENTRIES ) {
	if ( ! entries.includes( required ) ) {
		fail( `bundle is missing ${ required } (found: ${ entries.join( ', ' ) })` );
	}
}

let blueprint;
try {
	blueprint = JSON.parse( readEntry( bundlePath, 'blueprint.json' ) );
} catch ( error ) {
	fail( `blueprint.json is not valid JSON — ${ error.message }` );
}

const installStep = blueprint.steps?.find( ( step ) => step.step === 'installPlugin' );
const helperStep = blueprint.steps?.find( ( step ) => step.step === 'writeFile' );
const runPhpStep = blueprint.steps?.find( ( step ) => step.step === 'runPHP' );

if ( ! installStep || installStep.options?.activate !== true ) {
	fail( 'blueprint does not install and activate the plugin' );
}
if ( installStep.pluginData?.resource === 'bundled' ) {
	const bundledPath = String( installStep.pluginData.path || '' ).replace( /^\//, '' );
	if ( ! entries.includes( bundledPath ) ) {
		fail( `blueprint references bundled '${ bundledPath }' which is not in the bundle` );
	}
}
if ( ! helperStep?.data?.includes( 'QuickAdsenseAdWidget' ) ) {
	fail( 'blueprint does not expose the widget rendering path' );
}
if ( ! runPhpStep?.code?.includes( '<?php' ) ) {
	fail( 'blueprint is missing the demo setup runPHP step' );
}

for ( const setting of [
	'quick_adsense_settings',
	'header_embed_code',
	'footer_embed_code',
	'widget_ad_1_content',
	'onpost_ad_10_content',
	'<!--Ads9-->',
	'<!--Ads10-->',
] ) {
	if ( ! runPhpStep.code.includes( setting ) ) {
		fail( `demo setup no longer seeds ${ setting }` );
	}
}

const workDir = mkdtempSync( join( tmpdir(), 'quick-adsense-playground-' ) );
const innerZip = join( workDir, 'quick-adsense.zip' );
let pluginEntries;
try {
	writeFileSync(
		innerZip,
		execFileSync( 'unzip', [ '-p', bundlePath, 'quick-adsense.zip' ], {
			maxBuffer: 256 * 1024 * 1024,
		} )
	);
	pluginEntries = listEntries( innerZip );
} finally {
	rmSync( workDir, { recursive: true, force: true } );
}

// Files quick-adsense.php requires unconditionally at load. If packaging drops
// them the plugin fatals the moment Playground activates it, so assert their
// presence here rather than discovering it in the browser.
const REQUIRED_PLUGIN_FILES = [
	'quick-adsense/quick-adsense.php',
	'quick-adsense/includes/loader.php',
	'quick-adsense/includes/vendor/MobileDetect/Mobile_Detect.php',
	'quick-adsense/includes/vendor/GeoIP/GeoIPCountry.php',
];

for ( const required of REQUIRED_PLUGIN_FILES ) {
	if ( ! pluginEntries.includes( required ) ) {
		fail( `plugin zip does not contain ${ required }` );
	}
}
if ( pluginEntries.some( ( entry ) => entry.startsWith( 'quick-adsense/tests/' ) || entry.startsWith( 'quick-adsense/scripts/' ) ) ) {
	fail( 'plugin zip contains development-only tests or Playground scripts' );
}

console.log(
	`Playground bundle OK — ${ entries.length } bundle entries, ${ pluginEntries.length } plugin files, ${ blueprint.steps.length } blueprint steps.`
);
