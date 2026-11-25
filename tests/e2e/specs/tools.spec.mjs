import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const TOOLS = '/wp-admin/admin.php?page=quick-adsense-tools';
const KEY = 'max_ads_per_page';

test( 'export downloads a Quick Adsense JSON file', async ( { page } ) => {
	await page.goto( TOOLS );
	await expect( page.getByRole( 'heading', { name: 'Quick Adsense Tools' } ) ).toBeVisible();
	// The help line says stats are not restored by an import.
	await expect( page.locator( '#qa-export .description' ) ).toContainText( 'does not restore' );

	// Optional screenshot for the maintainer (QA_E2E_SCREENSHOT=<path>).
	if ( process.env.QA_E2E_SCREENSHOT ) {
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.screenshot( { path: process.env.QA_E2E_SCREENSHOT, fullPage: true } );
	}

	const [ download ] = await Promise.all( [
		page.waitForEvent( 'download' ),
		page.getByRole( 'button', { name: 'Download export file' } ).click(),
	] );
	expect( download.suggestedFilename() ).toMatch( /\.json$/ );
	const data = JSON.parse( readFileSync( await download.path(), 'utf8' ) );
	expect( data.format ).toBe( 'quick-adsense-export' );
	expect( data.settings ).toHaveProperty( KEY );
} );

test( 'import shows a dry-run diff, applies on confirm and keeps a pre-import backup', async ( { page } ) => {
	await page.goto( TOOLS );
	const [ download ] = await Promise.all( [
		page.waitForEvent( 'download' ),
		page.getByRole( 'button', { name: 'Download export file' } ).click(),
	] );
	const data = JSON.parse( readFileSync( await download.path(), 'utf8' ) );
	const before = Number( data.settings[ KEY ] );
	const after = before === 7 ? 8 : 7;
	data.settings[ KEY ] = after;

	await page.goto( TOOLS );
	await page.locator( 'input[name="import_file"]' ).setInputFiles( {
		name: 'modified.json',
		mimeType: 'application/json',
		buffer: Buffer.from( JSON.stringify( data ) ),
	} );
	await page.getByRole( 'button', { name: 'Upload and review' } ).click();

	// Dry run: the changed key is listed, and nothing is saved yet.
	const diff = page.locator( '#qa-import-diff' );
	await expect( diff ).toBeVisible();
	await expect( diff.locator( 'tbody tr' ) ).toHaveCount( 1 );
	await expect( diff ).toContainText( KEY );
	await expect( diff ).toContainText( String( after ) );

	await page.getByRole( 'button', { name: 'Import these settings' } ).click();
	await expect( page.locator( '.notice-success' ) ).toContainText( 'Import finished' );

	// A pre-import backup now exists in the Backups table.
	await expect( page.locator( '#qa-backups tbody tr' ).first() ).toContainText( /before (the )?import/i );

	// The new value is stored: a fresh export carries it.
	const [ again ] = await Promise.all( [
		page.waitForEvent( 'download' ),
		page.getByRole( 'button', { name: 'Download export file' } ).click(),
	] );
	const saved = JSON.parse( readFileSync( await again.path(), 'utf8' ) );
	expect( Number( saved.settings[ KEY ] ) ).toBe( after );
} );
