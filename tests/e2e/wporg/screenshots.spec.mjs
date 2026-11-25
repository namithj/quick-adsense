import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const here = dirname( fileURLToPath( import.meta.url ) );
const out = resolve( here, '../../../.wordpress-org' );
const ADMIN = '/wp-admin/admin.php?page=quick-adsense';
const TOOLS = '/wp-admin/admin.php?page=quick-adsense-tools';
const POST = '/?name=five-quiet-trails-worth-the-early-start';

// The server answers before the blueprint (theme, plugin, seed) has finished: wait for the seeded site.
test.beforeAll( async ( { playwright, baseURL } ) => {
	const api = await playwright.request.newContext( { baseURL } );
	await expect.poll( async () => ( await ( await api.get( POST ) ).text() ).includes( 'Trail Notes' ), { timeout: 150_000, intervals: [ 2000 ] } ).toBe( true );
	await api.dispose();
} );

// Chrome-hiding CSS for every navigation (form-submit redirects included): no admin menu,
// admin bar or footer in wp-admin, and no fixed theme frame on the front end.
test.beforeEach( async ( { context } ) => {
	await context.addInitScript( ( css ) => {
		document.addEventListener( 'DOMContentLoaded', () => {
			const style = document.createElement( 'style' );
			style.textContent = css;
			document.head.appendChild( style );
		} );
	}, "#adminmenuwrap, #adminmenuback, #adminmenumain, #wpadminbar, #wpadminbar *, #wpfooter { display: none !important; } #wpcontent, #wpfooter { margin-left: 0 !important; } html.wp-toolbar { padding-top: 0 !important; } html { margin-top: 0 !important; } #wpbody-content { padding-bottom: 0 !important; } body:before, body:after { display: none !important; } body, .site { background: #fff !important; } .site { margin: 0 !important; } body.wp-admin { background: #f0f0f1 !important; }" );
} );

test.use( { viewport: { width: 1280, height: 800 }, deviceScaleFactor: 1.5 } );

const shot = ( page, n, options = {} ) => page.screenshot( { path: resolve( out, `screenshot-${ n }.png` ), ...options } );

// Placeholder creatives: generic display ads from a made-up advertiser. Injected into the
// `.ad-slot` containers the seeded ad code prints, because ads.example.com serves nothing.
const creatives = {
	'article-top': { w: 'wide', head: 'Lightweight tents for the weekend', body: 'Two-person shelters under 1.4 kg. Free returns for 60 days.', cta: 'Shop tents', hue: '#2e7d57' },
	'article-middle': { w: 'wide', head: 'Waterproof boots, broken in for you', body: 'Resoled free for life. Try a pair on the trail first.', cta: 'Find your fit', hue: '#b4532a' },
	'article-bottom': { w: 'wide', head: 'Paper maps of every national park', body: 'Tear-proof, pocket-sized and printed on demand.', cta: 'Browse maps', hue: '#35577a' },
	'sidebar-top': { w: 'box', head: 'Merino base layers', body: 'Warm when it is wet, odour-free for days.', cta: 'See the range', hue: '#2e7d57' },
};

async function fillCreatives( page ) {
	await page.addStyleTag( { content: `
		.qa-demo-ad { box-sizing: border-box; font-family: -apple-system, 'Segoe UI', Roboto, sans-serif; color: #1f2a37; background: #fff; border: 1px solid #dfe3e8; border-radius: 10px; overflow: hidden; text-align: left; }
		.qa-demo-ad .sp { display: block; font-size: 10.5px; letter-spacing: .08em; text-transform: uppercase; color: #6b7785; padding: 8px 14px 0; }
		.qa-demo-ad .in { display: flex; align-items: center; gap: 14px; padding: 8px 14px 14px; }
		.qa-demo-ad .pic { flex: none; width: 76px; height: 76px; border-radius: 8px; position: relative; }
		.qa-demo-ad .pic:after { content: ''; position: absolute; left: 14px; right: 14px; bottom: 14px; height: 30px; background: rgba(255,255,255,.92); clip-path: polygon(50% 0, 100% 100%, 0 100%); }
		.qa-demo-ad h4 { margin: 0 0 4px; font-size: 16px; line-height: 1.25; font-weight: 700; }
		.qa-demo-ad p { margin: 0 0 8px; font-size: 13px; line-height: 1.4; color: #4a5560; }
		.qa-demo-ad .btn { display: inline-block; padding: 6px 14px; border-radius: 999px; color: #fff; font-size: 13px; font-weight: 600; }
		.qa-demo-ad.box .in { display: block; }
		.qa-demo-ad.box .pic { width: 100%; height: 110px; margin-bottom: 12px; }
	` } );
	await page.evaluate( ( items ) => {
		document.querySelectorAll( '.ad-slot' ).forEach( ( el ) => {
			const c = items[ el.dataset.slot ];
			if ( ! c ) { return; }
			el.innerHTML = `<div class="qa-demo-ad ${ c.w === 'box' ? 'box' : '' }"><span class="sp">Sponsored &middot; Example Outdoor Co.</span><div class="in"><div class="pic" style="background:linear-gradient(135deg, ${ c.hue }, #0f3d2a)"></div><div><h4>${ c.head }</h4><p>${ c.body }</p><span class="btn" style="background:${ c.hue }">${ c.cta }</span></div></div></div>`;
		} );
	}, creatives );
}

async function admin( page, url, height = 1400 ) {
	await page.setViewportSize( { width: 1280, height } );
	await page.goto( url );
	await page.waitForLoadState( 'networkidle' );
}

// No focus ring or hover state from the clicks that opened the view.
async function settle( page ) {
	await page.evaluate( () => document.activeElement && document.activeElement.blur() );
	await page.mouse.move( 5, 300 );
	await page.waitForTimeout( 200 );
	await expect( page.locator( '#wpadminbar' ) ).toBeHidden();
}

// Screenshot an element's box plus a little padding (never wider than the viewport).
async function boxShot( page, n, selector, { pad = 16, maxHeight } = {} ) {
	await settle( page );
	const box = await page.locator( selector ).first().boundingBox();
	const x = Math.max( 0, box.x - pad );
	const y = Math.max( 0, box.y - pad );
	const clip = { x, y, width: Math.min( 1280 - x, box.width + pad * 2 ), height: Math.min( maxHeight || 99999, box.height + pad * 2 ) };
	await shot( page, n, { fullPage: true, clip } );
}

test( '1 front end: demo post with ads placed automatically', async ( { page } ) => {
	await page.setViewportSize( { width: 1280, height: 1800 } );
	await page.route( /ads\.example\.com/, ( route ) => route.abort() );
	await page.goto( POST );
	await page.waitForSelector( '.ad-slot', { state: 'attached' } );
	await fillCreatives( page );
	await page.waitForTimeout( 400 );
	// Count: beginning, after paragraph 4, end.
	expect( await page.locator( '.entry-content .ad-slot' ).count() ).toBe( 3 );
	await settle( page );
	await shot( page, 1, { clip: { x: 0, y: 0, width: 1280, height: Number( process.env.QA_SHOT1_HEIGHT || 1600 ) } } );
} );

test( '2 settings tab', async ( { page } ) => {
	await admin( page, ADMIN );
	await page.getByRole( 'tab', { name: 'Settings' } ).click();
	await page.waitForTimeout( 300 );
	await boxShot( page, 2, '.wrap' );
} );

test( '3 ads on post body: slot, visibility rules, stats buttons', async ( { page } ) => {
	await admin( page, ADMIN );
	await page.getByRole( 'tab', { name: 'Ads on Post Body' } ).click();
	// Pick countries in the form (not saved) so the rule is visible; scroll the list to them.
	await page.locator( '#quick_adsense_settings_onpost_ad_1_limit_visitor_country' ).selectOption( [ { label: 'Australia' }, { label: 'Austria' } ] );
	await page.locator( '#quick_adsense_settings_onpost_ad_1_limit_visitor_country' ).evaluate( ( el ) => { el.scrollTop = Math.max( 0, el.selectedIndex * ( el.scrollHeight / el.options.length ) - 2 ); } );
	await page.waitForTimeout( 300 );
	const wrapTop = ( await page.locator( '.wrap' ).first().boundingBox() ).y;
	const slot = await page.locator( '#quick_adsense_onpost_adunits_control_1' ).boundingBox();
	await boxShot( page, 3, '.wrap', { maxHeight: slot.y + slot.height - wrapTop + 40 } );
} );

test( '4 stats dialog with the 30-day chart', async ( { page } ) => {
	await admin( page, ADMIN );
	await page.getByRole( 'tab', { name: 'Ads on Post Body' } ).click();
	await page.locator( '.quick_adsense_onpost_ad_show_stats[data-index="1"]' ).first().click();
	await page.locator( 'dialog[open] canvas' ).waitFor();
	await page.waitForTimeout( 1500 );
	await settle( page );
	await page.locator( 'dialog[open]' ).screenshot( { path: resolve( out, 'screenshot-4.png' ) } );
} );

test( '5 sidebar widget tab', async ( { page } ) => {
	await admin( page, ADMIN );
	await page.getByRole( 'tab', { name: 'Sidebar Widget' } ).click();
	await page.waitForTimeout( 300 );
	await boxShot( page, 5, '.wrap' );
} );

test( '6 header / footer codes tab', async ( { page } ) => {
	await admin( page, ADMIN );
	await page.getByRole( 'tab', { name: 'Header / Footer Codes' } ).click();
	await page.waitForTimeout( 300 );
	await boxShot( page, 6, '.wrap' );
} );

test( '7 tools: import review step', async ( { page } ) => {
	await admin( page, TOOLS );
	const [ download ] = await Promise.all( [
		page.waitForEvent( 'download' ),
		page.getByRole( 'button', { name: 'Download export file' } ).click(),
	] );
	const data = JSON.parse( readFileSync( await download.path(), 'utf8' ) );
	// A plausible export from another site: a few settings differ.
	Object.assign( data.settings, {
		max_ads_per_page: '4',
		enable_on_homepage: '1',
		enable_position_middle_of_post: '1',
		ad_middle_of_post: '2',
		onpost_ad_2_alignment: '1',
		onpost_ad_3_margin: '20',
		footer_embed_code: '<script>window.adsQueue = window.adsQueue || [];</script>',
	} );
	await admin( page, TOOLS );
	await page.locator( 'input[name="import_file"]' ).setInputFiles( {
		name: 'quick-adsense-export-staging.json',
		mimeType: 'application/json',
		buffer: Buffer.from( JSON.stringify( data ) ),
	} );
	await page.getByRole( 'button', { name: 'Upload and review' } ).click();
	await page.locator( '#qa-import-diff' ).waitFor();
	await page.locator( '#qa-import-diff' ).scrollIntoViewIfNeeded();
	await page.waitForTimeout( 300 );
	const top = ( await page.locator( '.wrap' ).first().boundingBox() ).y;
	const backups = ( await page.locator( '#qa-backups' ).boundingBox() ).y;
	await boxShot( page, 7, '.wrap', { maxHeight: backups - top - 8 } );
} );

test( '8 tools: backups list', async ( { page } ) => {
	await admin( page, TOOLS );
	if ( await page.getByRole( 'link', { name: 'Discard' } ).or( page.getByRole( 'button', { name: 'Discard' } ) ).count() ) {
		await page.getByRole( 'link', { name: 'Discard' } ).or( page.getByRole( 'button', { name: 'Discard' } ) ).first().click();
		await page.waitForLoadState( 'networkidle' );
	}
	await expect( page.locator( '#qa-backups tbody tr' ) ).toHaveCount( 3 );
	await boxShot( page, 8, '#qa-backups' );
} );
