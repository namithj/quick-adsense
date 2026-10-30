<?php
/**
 * 01: a new install that never opened the settings page.
 *
 * No quick_adsense_settings row exists before init, so init must write exactly
 * quick_adsense_get_defaults() (C-7.4). The render scenarios then add slot 1's
 * code to whatever init produced, without a save: homepage, archive and tag ads
 * are active because activation uses isset() (C-3.1), and logged-in users get
 * no ads because disable_for_loggedin_users exists as '0'.
 */
return [
	'slug'        => '01-fresh-defaults',
	'description' => 'New install that never opened settings: shape 1, defaults written by init.',
	'shape'       => 'defaults',
	'options'     => [],
	'post_init'   => [
		'onpost_ad_1_content' => QA_AD( 'adsense-async-1' ),
	],
];
