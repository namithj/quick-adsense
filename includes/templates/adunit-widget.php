<?php $adunit_index = quick_adsense_get_value( $args, 'adunit_index' ); ?>
<div id="quick_adsense_widget_adunits_control_<?php echo esc_attr( $adunit_index ); ?>" class="quick_adsense_widget_adunits_control_wrapper">
	<div class="quick_adsense_widget_adunits_label">AdsWidget<?php echo esc_attr( $adunit_index ); ?></div>
	<div class="quick_adsense_widget_adunits_control">
		<?php
		echo wp_kses(
			quickadsense_get_control(
				'textarea',
				'',
				'quick_adsense_settings_widget_ad_' . $adunit_index . '_content',
				'quick_adsense_settings[widget_ad_' . $adunit_index . '_content]',
				quick_adsense_get_value( $args, 'widget_ad_' . $adunit_index . '_content' ),
				null,
				'input',
				'display: block; margin: 0 0 10px 0;',
				'Enter Code'
			),
			quick_adsense_get_allowed_html()
		);
		?>
	</div>
	<div class="clear"></div>
</div>
