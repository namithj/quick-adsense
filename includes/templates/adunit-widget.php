<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$quick_adsense_adunit_index = quick_adsense_get_value( $quick_adsense_template_args, 'adunit_index' );
?>
<div id="quick_adsense_widget_adunits_control_<?php echo esc_attr( $quick_adsense_adunit_index ); ?>" class="quick_adsense_widget_adunits_control_wrapper">
	<div class="quick_adsense_widget_adunits_label">AdsWidget<?php echo esc_attr( $quick_adsense_adunit_index ); ?></div>
	<div class="quick_adsense_widget_adunits_control">
		<?php
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		// This textarea needs to allow output of scripts, iframes etc necessary to output ads and trackers.
		echo quickadsense_get_control(
			'textarea',
			'',
			'quick_adsense_settings_widget_ad_' . $quick_adsense_adunit_index . '_content',
			'quick_adsense_settings[widget_ad_' . $quick_adsense_adunit_index . '_content]',
			quick_adsense_get_value( $quick_adsense_template_args, 'widget_ad_' . $quick_adsense_adunit_index . '_content' ),
			null,
			'input',
			'display: block; margin: 0 0 10px 0;',
			'Enter Code'
		);
		// phpcs:enable
		?>
	</div>
	<div class="clear"></div>
</div>
