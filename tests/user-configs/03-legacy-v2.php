<?php
/**
 * 03: a v2.0 user.
 *
 * quick_adsense_2_options is migrated into quick_adsense_settings, backed up to
 * quick_adsense_2_options_bak and deleted (C-7.2). The migrated array keeps the
 * documented quirks: several Rnd/Nup/Con values become '1' because they exist,
 * v2 alignment falls back to '2', and switches only ever turn on.
 */
return [
	'slug'            => '03-legacy-v2',
	'description'     => 'v2.0 user: quick_adsense_2_options, migrated with its quirks.',
	'shape'           => 'legacy-v2',
	'deleted_by_init' => [ 'quick_adsense_2_options' ],
	'options'         => [
		'quick_adsense_2_options' => [
			'AdsDisp'    => '5',
			'BegnAds'    => '1',
			'BegnRnd'    => '1',
			'EndiAds'    => '1',
			'EndiRnd'    => '2',
			'MoreAds'    => '1',
			'MoreRnd'    => '3',
			'LapaAds'    => '1',
			'LapaRnd'    => '0',
			'Par2Ads'    => '1',
			'Par2Rnd'    => '5',
			'Par2Nup'    => '4',
			'AppPost'    => '1',
			'AppCate'    => '1',
			'AppTags'    => '1',
			'AppSide'    => '1',
			'QckTags'    => '1',
			'QckRnds'    => '1',
			'AdsCode1'   => QA_AD( 'adsense-async-1' ),
			'AdsCode2'   => QA_AD( 'adsense-async-2' ),
			'AdsCode3'   => QA_AD( 'adsense-legacy' ),
			'AdsCode4'   => QA_AD( 'medianet' ),
			'AdsCode5'   => QA_AD( 'adsense-async-5' ),
			'AdsAlign1'  => '1',
			'AdsMargin1' => '5',
			'AdsMargin2' => '10',
			'AdsMargin3' => '15',
			'AdsMargin4' => '20',
			'AdsMargin5' => '0',
			'WidCode1'   => QA_AD( 'widget-1' ),
			'WidCode2'   => QA_AD( 'widget-2' ),
		],
	],
];
