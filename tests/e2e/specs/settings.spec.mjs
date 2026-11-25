import { test, expect } from '@playwright/test';

const SETTINGS = '/wp-admin/admin.php?page=quick-adsense';

test.describe( 'Settings page with JavaScript', () => {
	test( 'tabs use nav-tab styling and move with the arrow keys', async ( { page } ) => {
		await page.goto( SETTINGS );
		const tabs = page.getByRole( 'tab' );
		await expect( tabs ).toHaveCount( 4 );
		await expect( page.locator( '#quick_adsense_settings_tabs' ) ).toBeVisible();
		await expect( page.locator( '#quick_adsense_settings_tabs ul' ).first() ).toHaveClass( /nav-tab-wrapper/ );
		// jQuery UI puts role=tab on the <li>; core's nav-tab class sits on its link.
		const link = tabs.first().locator( 'a' );
		await expect( link ).toHaveClass( /nav-tab/ );

		// nav-tab styling is applied: core pads .nav-tab links.
		const padding = await link.evaluate( ( el ) => parseFloat( getComputedStyle( el ).paddingLeft ) );
		expect( padding ).toBeGreaterThan( 5 );

		await expect( tabs.nth( 0 ) ).toHaveAttribute( 'aria-selected', 'true' );
		await tabs.nth( 0 ).focus();
		await page.keyboard.press( 'ArrowRight' );
		await expect( tabs.nth( 1 ) ).toHaveAttribute( 'aria-selected', 'true' );
		await expect( tabs.nth( 0 ) ).toHaveAttribute( 'aria-selected', 'false' );
		await page.keyboard.press( 'ArrowLeft' );
		await expect( tabs.nth( 0 ) ).toHaveAttribute( 'aria-selected', 'true' );
	} );

	test( 'Backups in the tab row is a link that opens the Tools page', async ( { page } ) => {
		await page.goto( SETTINGS );
		const backups = page.getByRole( 'link', { name: 'Backups', exact: true } );
		await expect( backups ).toHaveClass( /nav-tab/ );
		await expect( page.getByRole( 'tab', { name: 'Backups' } ) ).toHaveCount( 0 );
		// Same row as the tabs.
		const tabBox = await page.getByRole( 'tab' ).first().boundingBox();
		const linkBox = await backups.boundingBox();
		expect( Math.abs( tabBox.y - linkBox.y ) ).toBeLessThan( 4 );
		await backups.click();
		await expect( page ).toHaveURL( /page=quick-adsense-tools#qa-backups$/ );
		await expect( page.locator( '#qa-backups' ) ).toBeVisible();
	} );

	test( 'stats open in a native modal dialog with the chart on a white surface', async ( { page } ) => {
		await page.goto( SETTINGS );
		await page.getByRole( 'tab', { name: 'Ads on Post Body' } ).click();
		const button = page.locator( '.quick_adsense_onpost_ad_show_stats' ).first();
		test.skip( ( await button.count() ) === 0, 'No "View Stats" button on the page (stats are not enabled in this site).' );
		// No jQuery UI theme is loaded any more.
		await expect( page.locator( 'link[href*="jquery-ui"]' ) ).toHaveCount( 0 );

		await button.click();
		const dialog = page.getByRole( 'dialog', { name: /Ad1 Performance Stats/ } );
		await expect( dialog ).toBeVisible();
		expect( await dialog.evaluate( ( el ) => el.tagName === 'DIALOG' && el.matches( ':modal' ) ) ).toBe( true );
		expect( await dialog.evaluate( ( el ) => getComputedStyle( el ).backgroundColor ) ).toBe( 'rgb(255, 255, 255)' );
		await expect( dialog.locator( '#quick_adsense_ad_stats_chart' ) ).toBeVisible();

		await page.keyboard.press( 'Escape' );
		await expect( page.locator( 'dialog.qa-dialog' ) ).toHaveCount( 0 );

		await button.click();
		await expect( dialog ).toBeVisible();
		await dialog.getByRole( 'button', { name: 'Close' } ).click();
		await expect( page.locator( 'dialog.qa-dialog' ) ).toHaveCount( 0 );
	} );
} );

test.describe( 'Settings page without JavaScript', () => {
	test.use( { javaScriptEnabled: false } );

	test( 'the form is visible and Save works', async ( { page } ) => {
		await page.goto( SETTINGS );
		await expect( page.locator( '#quick_adsense_settings_tabs' ) ).toBeVisible();
		await expect( page.locator( 'form input[type="submit"]' ).first() ).toBeVisible();
		await Promise.all( [
			page.waitForNavigation(),
			page.locator( 'form input[type="submit"]' ).first().click(),
		] );
		await expect( page.locator( '#quick_adsense_settings_tabs' ) ).toBeVisible();
		await expect( page.locator( '.notice-error, .error' ) ).toHaveCount( 0 );
	} );
} );
