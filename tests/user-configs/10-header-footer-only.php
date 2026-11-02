<?php
/**
 * 10: an auto-ads / analytics user.
 *
 * Header and footer code only; no slots. Both are echoed byte for byte (C-3.11).
 */
return [
	'slug'        => '10-header-footer-only',
	'description' => 'Auto-ads and analytics: header (auto ads + gtag) and footer (Media.net) codes, no slots.',
	'shape'       => 'saved',
	'options'     => [
		'quick_adsense_settings' => [
			'max_ads_per_page'  => '3',
			'enable_on_posts'   => '1',
			'header_embed_code' => QA_AD( 'adsense-auto-ads' ) . "\n" . QA_AD( 'analytics-gtag' ),
			'footer_embed_code' => QA_AD( 'medianet' ),
		],
	],
];
