<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$quick_adsense_adunit_index = quick_adsense_get_value( $quick_adsense_template_args, 'adunit_index' );
?>
<div id="quick_adsense_onpost_adunits_control_<?php echo esc_attr( $quick_adsense_adunit_index ); ?>" class="quick_adsense_onpost_adunits_control_wrapper">
	<div class="quick_adsense_onpost_adunits_label">Ads<?php echo esc_attr( $quick_adsense_adunit_index ); ?></div>
	<div class="quick_adsense_onpost_adunits_control">
		<?php
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		// This textarea needs to allow output of scripts, iframes etc necessary to output ads and trackers.
		echo quickadsense_get_control(
			'textarea',
			'',
			'quick_adsense_settings_onpost_ad_' . $quick_adsense_adunit_index . '_content',
			'quick_adsense_settings[onpost_ad_' . $quick_adsense_adunit_index . '_content]',
			quick_adsense_get_value( $quick_adsense_template_args, 'onpost_ad_' . $quick_adsense_adunit_index . '_content' ),
			null,
			'input',
			'display: block; margin: 0 0 10px 0',
			'Enter Code'
		);
		// phpcs:enable
		?>
		<p class="quick_adsense_onpost_adunits_styling_controls">
			Alignment
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'select',
					'',
					'quick_adsense_settings_onpost_ad_' . $quick_adsense_adunit_index . '_alignment',
					'quick_adsense_settings[onpost_ad_' . $quick_adsense_adunit_index . '_alignment]',
					quick_adsense_get_value( $quick_adsense_template_args, 'onpost_ad_' . $quick_adsense_adunit_index . '_alignment' ),
					quick_adsense_get_value( $quick_adsense_template_args, 'alignment_options' ),
					'input',
					'margin: -2px 20px 0 10px;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
			<wbr />margin
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'number',
					'',
					'quick_adsense_settings_onpost_ad_' . $quick_adsense_adunit_index . '_margin',
					'quick_adsense_settings[onpost_ad_' . $quick_adsense_adunit_index . '_margin]',
					quick_adsense_get_value( $quick_adsense_template_args, 'onpost_ad_' . $quick_adsense_adunit_index . '_margin' ),
					null,
					'input',
					'margin: -2px 10px 0 10px; width: 52px;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
			px
		</p>
		<?php
		quick_adsense_load_file( 'templates/block-adunit-advanced.php', $quick_adsense_template_args, true );
		?>
	</div>
	<div class="clear"></div>
</div>
