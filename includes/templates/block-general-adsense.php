<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="quick_adsense_block">
	<div class="quick_adsense_block_labels">Adsense</div>
	<div class="quick_adsense_block_controls">
		Place up to
		<?php
		$quick_adsense_max_ads_count = [];
		for ( $quick_adsense_i = 0; $quick_adsense_i <= 10; $quick_adsense_i++ ) {
			$quick_adsense_max_ads_count[] = [
				'text'  => $quick_adsense_i,
				'value' => $quick_adsense_i,
			];
		}
		echo wp_kses(
			quickadsense_get_control(
				'select',
				'',
				'quick_adsense_settings_max_ads_per_page',
				'quick_adsense_settings[max_ads_per_page]',
				quick_adsense_get_value( $quick_adsense_template_args, 'max_ads_per_page' ),
				$quick_adsense_max_ads_count,
				'input',
				'margin: -2px 10px 0 40px;'
			),
			quick_adsense_get_allowed_html()
		);
		?>
		Ads on a page
	</div>
	<div class="clear"></div>
</div>
