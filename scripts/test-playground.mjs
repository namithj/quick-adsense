/** Smoke test a built Quick Adsense Playground bundle. */

import { execFileSync } from 'node:child_process';
import { existsSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

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

const [ bundlePath ] = process.argv.slice( 2 );
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

if ( ! pluginEntries.includes( 'quick-adsense/quick-adsense.php' ) ) {
	fail( 'plugin zip does not contain quick-adsense/quick-adsense.php' );
}
if ( pluginEntries.some( ( entry ) => entry.startsWith( 'quick-adsense/tests/' ) || entry.startsWith( 'quick-adsense/scripts/' ) ) ) {
	fail( 'plugin zip contains development-only tests or Playground scripts' );
}

console.log(
	`Playground bundle OK — ${ entries.length } bundle entries, ${ pluginEntries.length } plugin files, ${ blueprint.steps.length } blueprint steps.`
);
