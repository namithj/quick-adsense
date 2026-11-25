import { test } from '@playwright/test';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { dirname, resolve } from 'node:path';
import { mkdirSync } from 'node:fs';

const here = dirname( fileURLToPath( import.meta.url ) );
const art = resolve( here, '../../../scripts/wporg-assets/artwork' );
const out = resolve( here, '../../../.wordpress-org' );
mkdirSync( out, { recursive: true } );

const url = ( file, query = '' ) => pathToFileURL( resolve( art, file ) ).href + query;

async function render( browser, file, query, width, height, scale, target ) {
	const context = await browser.newContext( { viewport: { width, height }, deviceScaleFactor: scale } );
	const page = await context.newPage();
	await page.goto( url( file, query ) );
	await page.waitForSelector( 'body[data-ready="1"]' );
	await page.screenshot( { path: resolve( out, target ), omitBackground: true, clip: { x: 0, y: 0, width, height } } );
	await context.close();
}

test( 'icons', async ( { browser } ) => {
	await render( browser, 'icon.html', '?size=256', 256, 256, 1, 'icon-256x256.png' );
	await render( browser, 'icon.html', '?size=128', 128, 128, 1, 'icon-128x128.png' );
} );

test( 'banners', async ( { browser } ) => {
	await render( browser, 'banner.html', '', 772, 250, 1, 'banner-772x250.png' );
	await render( browser, 'banner.html', '', 772, 250, 2, 'banner-1544x500.png' );
} );
