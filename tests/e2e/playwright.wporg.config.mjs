import { defineConfig } from '@playwright/test';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';
import { mkdirSync, writeFileSync } from 'node:fs';
import { createWporgBlueprint } from './wporg/seed.mjs';

// Generates the WordPress.org listing assets (see scripts/wporg-assets/README.md).
// Run with `npm run wporg-assets`; the normal e2e config is untouched.
const here = dirname( fileURLToPath( import.meta.url ) );
const pluginDir = resolve( here, '../..' );
const port = Number( process.env.QA_WPORG_PORT || 9478 );
const baseURL = `http://127.0.0.1:${ port }`;
// WPORG_ONLY=artwork renders the icon and banner only (no Playground server).
const artworkOnly = process.env.WPORG_ONLY === 'artwork';

const blueprintPath = resolve( here, 'test-results/wporg-blueprint.json' );
mkdirSync( dirname( blueprintPath ), { recursive: true } );
writeFileSync( blueprintPath, JSON.stringify( createWporgBlueprint(), null, 2 ) );

export default defineConfig( {
	testDir: './wporg',
	testMatch: artworkOnly ? /artwork\.spec\.mjs$/ : /\.spec\.mjs$/,
	outputDir: './test-results/wporg',
	timeout: 180_000,
	workers: 1,
	fullyParallel: false,
	reporter: 'list',
	use: { baseURL },
	projects: [ { name: 'chromium', use: { browserName: 'chromium' } } ],
	webServer: artworkOnly
		? undefined
		: {
				command: [
					'npx wp-playground-cli server',
					`--port=${ port }`,
					'--login',
					'--php=8.2',
					`--mount=${ pluginDir }:/wordpress/wp-content/plugins/quick-adsense`,
					`--blueprint=${ blueprintPath }`,
				].join( ' ' ),
				url: `${ baseURL }/wp-includes/images/blank.gif`,
				timeout: 8 * 60_000,
				reuseExistingServer: Boolean( process.env.WPORG_REUSE ),
				stdout: 'ignore',
				stderr: 'pipe',
		  },
} );
