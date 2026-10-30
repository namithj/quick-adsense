<?php
/**
 * 18: other networks and hostile bytes.
 *
 * GAM, an Amazon iframe with an ampersand query, Media.net, the "nasty" payload
 * and a whitespace-only slot, plus the nasty payload as widget, header and
 * footer code. Proves byte-for-byte output (C-6) on every sink.
 *
 * Known exception: the real the_content pipeline runs do_shortcode and friends
 * after the engine (priority 11 and later), so a payload containing a shortcode
 * is not byte-identical in a full render. The plugin-only snapshot is the
 * contract; the exception is listed here so the smoke check stays strict for
 * every other slot.
 */
$settings = [
	'max_ads_per_page'                    => '5',
	'enable_on_posts'                     => '1',
	'enable_on_pages'                     => '1',
	'enable_position_beginning_of_post'   => '1',
	'ad_beginning_of_post'                => '1',
	'enable_position_middle_of_post'      => '1',
	'ad_middle_of_post'                   => '2',
	'enable_position_end_of_post'         => '1',
	'ad_end_of_post'                      => '3',
	'enable_position_after_para_option_1' => '1',
	'ad_after_para_option_1'              => '4',
	'position_after_para_option_1'        => '2',
	'enable_position_after_para_option_2' => '1',
	'ad_after_para_option_2'              => '5',
	'position_after_para_option_2'        => '4',
	'onpost_ad_1_content'                 => QA_AD( 'gam-gpt' ),
	'onpost_ad_2_content'                 => QA_AD( 'amazon-iframe' ),
	'onpost_ad_3_content'                 => QA_AD( 'medianet' ),
	'onpost_ad_4_content'                 => QA_AD( 'nasty' ),
	'onpost_ad_5_content'                 => QA_AD( 'whitespace' ),
	'widget_ad_1_content'                 => QA_AD( 'nasty' ),
	'header_embed_code'                   => QA_AD( 'nasty' ),
	'footer_embed_code'                   => QA_AD( 'nasty' ),
];
for ( $i = 1; $i <= 5; $i++ ) {
	$settings[ 'onpost_ad_' . $i . '_alignment' ] = '2';
	$settings[ 'onpost_ad_' . $i . '_margin' ]    = '10';
}

return [
	'slug'                => '18-non-adsense-payloads',
	'description'         => 'Other networks and nasty bytes on every sink: slots, widget, header and footer.',
	'shape'               => 'saved',
	'options'             => [ 'quick_adsense_settings' => $settings ],
	'widgets'             => [
		'sidebar-1' => [ 1 ],
	],
	'pipeline_exceptions' => [
		4 => 'contains a [gallery] shortcode, which core do_shortcode expands after the engine runs',
	],
];
