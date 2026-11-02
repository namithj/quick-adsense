<?php
/**
 * 02: a site that installed v1.x years ago and never upgraded its settings.
 *
 * Only the loose v1 options exist. init migrates them into
 * quick_adsense_settings (shape 3), deletes AdsDisp and keeps every other loose
 * option (C-7.3, C-1).
 */
return [
	'slug'            => '02-legacy-v1',
	'description'     => 'Installed v1.x years ago: loose options only, migrated on init.',
	'shape'           => 'legacy-v1',
	'deleted_by_init' => [ 'AdsDisp' ],
	'options'         => [
		'AdsDisp'    => '4',
		'BegnAds'    => '1',
		'BegnRnd'    => '2',
		'MiddAds'    => '1',
		'MiddRnd'    => '0',
		'EndiAds'    => '1',
		'EndiRnd'    => '3',
		'Par1Ads'    => '1',
		'Par1Rnd'    => '1',
		'Par1Nup'    => '2',
		'Par1Con'    => '1',
		'Img1Ads'    => '1',
		'Img1Rnd'    => '4',
		'Img1Nup'    => '1',
		'AppPost'    => '1',
		'AppPage'    => '1',
		'AppHome'    => '1',
		'AppLogg'    => '1',
		'QckTags'    => '1',
		'AdsCode1'   => QA_AD( 'adsense-legacy' ),
		'AdsCode2'   => QA_AD( 'adsense-async-2' ),
		'AdsCode3'   => QA_AD( 'adsense-async-3' ),
		'AdsCode4'   => QA_AD( 'adsense-async-4' ),
		'AdsAlign1'  => '1',
		'AdsAlign2'  => '2',
		'AdsAlign3'  => '3',
		'AdsAlign4'  => '4',
		'AdsMargin1' => '0',
		'AdsMargin2' => '5',
		'AdsMargin3' => '10',
		'AdsMargin4' => '25',
		'WidCode1'   => QA_AD( 'amazon-iframe' ),
	],
];
