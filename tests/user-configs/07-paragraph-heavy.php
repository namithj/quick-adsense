<?php
/**
 * 07: a long-form content site.
 *
 * All three after-paragraph options are on: option 1 at paragraph 0 (which
 * inserts the stray </p> at the very start, C-3.5), option 2 at paragraph 5,
 * option 3 at paragraph 50 with jump-to-end (beyond the paragraph count).
 * Before-last-paragraph is on. The upper-p and empty-paras posts are part of
 * the standard scenario matrix.
 */
return [
	'slug'        => '07-paragraph-heavy',
	'description' => 'Long-form site: three after-paragraph positions (0, 5, 50 with jump), before-last-paragraph, max 5.',
	'shape'       => 'saved',
	'options'     => [
		'quick_adsense_settings' => [
			'max_ads_per_page'                         => '5',
			'enable_on_posts'                          => '1',
			'enable_on_pages'                          => '1',
			'enable_position_after_para_option_1'      => '1',
			'ad_after_para_option_1'                   => '1',
			'position_after_para_option_1'             => '0',
			'enable_position_after_para_option_2'      => '1',
			'ad_after_para_option_2'                   => '2',
			'position_after_para_option_2'             => '5',
			'enable_position_after_para_option_3'      => '1',
			'ad_after_para_option_3'                   => '3',
			'position_after_para_option_3'             => '50',
			'enable_jump_position_after_para_option_3' => '1',
			'enable_position_before_last_para'         => '1',
			'ad_before_last_para'                      => '4',
			'onpost_ad_1_content'                      => QA_AD( 'adsense-async-1' ),
			'onpost_ad_1_alignment'                    => '2',
			'onpost_ad_1_margin'                       => '10',
			'onpost_ad_2_content'                      => QA_AD( 'adsense-async-2' ),
			'onpost_ad_2_alignment'                    => '1',
			'onpost_ad_2_margin'                       => '10',
			'onpost_ad_3_content'                      => QA_AD( 'adsense-async-3' ),
			'onpost_ad_3_alignment'                    => '3',
			'onpost_ad_3_margin'                       => '10',
			'onpost_ad_4_content'                      => QA_AD( 'adsense-async-4' ),
			'onpost_ad_4_alignment'                    => '2',
			'onpost_ad_4_margin'                       => '10',
		],
	],
];
