import { defineConfig } from '@playwright/test';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const here = dirname( fileURLToPath( import.meta.url ) );
const pluginDir = resolve( here, '../..' );
const port = Number( process.env.QA_E2E_PORT || 9477 );
const baseURL = `http://127.0.0.1:${ port }`;

export default defineConfig( {
	testDir: './specs',
	timeout: 90_000,
	expect: { timeout: 15_000 },
	// The specs share one Playground site (and its options), so run in order.
	workers: 1,
	fullyParallel: false,
	retries: process.env.CI ? 1 : 0,
	reporter: process.env.CI ? [ [ 'list' ], [ 'html', { open: 'never' } ] ] : 'list',
	use: { baseURL, trace: 'retain-on-failure' },
	projects: [ { name: 'chromium', use: { browserName: 'chromium' } } ],
	// Working-tree plugin mounted into a throwaway Playground site (SQLite, no DDEV, no MySQL).
	// --login makes Playground set the admin auth cookie on the first redirect.
	webServer: {
		command: [
			'npx wp-playground-cli server',
			`--port=${ port }`,
			'--login',
			'--php=8.2',
			`--mount=${ pluginDir }:/wordpress/wp-content/plugins/quick-adsense`,
			`--blueprint=${ resolve( here, 'blueprint.json' ) }`,
		].join( ' ' ),
		// Playground answers every dynamic request with a cookie-setting redirect to itself, which
		// Playwright (no cookie jar) would follow forever, so poll a static file.
		url: `${ baseURL }/wp-includes/images/blank.gif`,
		timeout: 8 * 60_000,
		reuseExistingServer: ! process.env.CI,
		stdout: 'ignore',
		stderr: 'pipe',
	},
} );
