<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quick_adsense_adunit_index = quick_adsense_get_value( $quick_adsense_template_args, 'adunit_index' );
$quick_adsense_location     = quick_adsense_get_value( $quick_adsense_template_args, 'location' );
?>
<p class="quick_adsense_<?php echo esc_attr( $quick_adsense_location ); ?>_adunits_device_controls">
	<b><?php esc_html_e( 'Hide by Device Type:', 'quick-adsense' ); ?></b><br />
	<?php
	echo wp_kses(
		quickadsense_get_control(
			'checkbox',
			__( 'Mobile', 'quick-adsense' ),
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_device_mobile',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_device_mobile]',
			quick_adsense_get_value( $quick_adsense_template_args, '_ad_' . $quick_adsense_adunit_index . '_hide_device_mobile' ),
			null,
			'input',
			'margin: -1px 5px 0 0;'
		),
		quick_adsense_get_allowed_html()
	);
	echo wp_kses(
		quickadsense_get_control(
			'checkbox',
			__( 'Tablet', 'quick-adsense' ),
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_device_tablet',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_device_tablet]',
			quick_adsense_get_value( $quick_adsense_template_args, $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_device_tablet' ),
			null,
			'input',
			'margin: -1px 5px 0 15px;'
		),
		quick_adsense_get_allowed_html()
	);
	echo wp_kses(
		quickadsense_get_control(
			'checkbox',
			__( 'Desktop', 'quick-adsense' ),
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_device_desktop',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_device_desktop]',
			quick_adsense_get_value( $quick_adsense_template_args, $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_device_desktop' ),
			null,
			'input',
			'margin: -1px 5px 0 15px;'
		),
		quick_adsense_get_allowed_html()
	);
	?>
</p>
<p class="quick_adsense_<?php echo esc_attr( $quick_adsense_location ); ?>_adunits_device_controls">
	<b><?php esc_html_e( 'Hide by Visitor Source:', 'quick-adsense' ); ?></b><br />
	<?php
	echo wp_kses(
		quickadsense_get_control(
			'checkbox',
			__( 'Search Engine', 'quick-adsense' ),
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_searchengine',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_searchengine]',
			quick_adsense_get_value( $quick_adsense_template_args, $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_searchengine' ),
			null,
			'input',
			'margin: -1px 5px 0 0;'
		),
		quick_adsense_get_allowed_html()
	);
	echo wp_kses(
		quickadsense_get_control(
			'checkbox',
			__( 'Indirect', 'quick-adsense' ),
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_indirect',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_indirect]',
			quick_adsense_get_value( $quick_adsense_template_args, $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_indirect' ),
			null,
			'input',
			'margin: -1px 5px 0 15px;'
		),
		quick_adsense_get_allowed_html()
	);
	echo wp_kses(
		quickadsense_get_control(
			'checkbox',
			__( 'Direct', 'quick-adsense' ),
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_direct',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_direct]',
			quick_adsense_get_value( $quick_adsense_template_args, $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_direct' ),
			null,
			'input',
			'margin: -1px 5px 0 15px;'
		),
		quick_adsense_get_allowed_html()
	);
	?>
</p>
<p class="quick_adsense_<?php echo esc_attr( $quick_adsense_location ); ?>_adunits_device_controls">
	<b><?php esc_html_e( 'Hide by Visitor Type:', 'quick-adsense' ); ?></b><br />
	<?php
	echo wp_kses(
		quickadsense_get_control(
			'checkbox',
			__( 'Bots', 'quick-adsense' ),
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_bot',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_bot]',
			quick_adsense_get_value( $quick_adsense_template_args, $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_bot' ),
			null,
			'input',
			'margin: -1px 5px 0 0;'
		),
		quick_adsense_get_allowed_html()
	);
	echo wp_kses(
		quickadsense_get_control(
			'checkbox',
			__( 'Known Browser', 'quick-adsense' ),
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_knownbrowser',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_knownbrowser]',
			quick_adsense_get_value( $quick_adsense_template_args, $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_knownbrowser' ),
			null,
			'input',
			'margin: -1px 5px 0 15px;'
		),
		quick_adsense_get_allowed_html()
	);
	echo wp_kses(
		quickadsense_get_control(
			'checkbox',
			__( 'Unknown Browser', 'quick-adsense' ),
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_unknownbrowser',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_unknownbrowser]',
			quick_adsense_get_value( $quick_adsense_template_args, $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_unknownbrowser' ),
			null,
			'input',
			'margin: -1px 5px 0 15px;'
		),
		quick_adsense_get_allowed_html()
	);
	?>
	<br />
	<?php
	echo wp_kses(
		quickadsense_get_control(
			'checkbox',
			__( 'Guest', 'quick-adsense' ),
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_guest',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_guest]',
			quick_adsense_get_value( $quick_adsense_template_args, $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_guest' ),
			null,
			'input',
			'margin: -1px 5px 0 0;'
		),
		quick_adsense_get_allowed_html()
	);
	echo wp_kses(
		quickadsense_get_control(
			'checkbox',
			'Logged-in',
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_loggedin',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_loggedin]',
			quick_adsense_get_value( $quick_adsense_template_args, $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_hide_visitor_loggedin' ),
			null,
			'input',
			'margin: -1px 5px 0 15px;'
		),
		quick_adsense_get_allowed_html()
	);
	?>
</p>
<p class="quick_adsense_<?php echo esc_attr( $quick_adsense_location ); ?>_adunits_device_controls">
	<b><?php esc_html_e( 'Limit by Visitor Countries:', 'quick-adsense' ); ?></b><br />
	<?php
	echo wp_kses(
		quickadsense_get_control(
			'multiselect',
			'',
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_limit_visitor_country',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_limit_visitor_country][]',
			quick_adsense_get_value( $quick_adsense_template_args, $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_limit_visitor_country' ),
			quick_adsense_get_countries()
		),
		quick_adsense_get_allowed_html()
	);
	?>
</p>
<p class="quick_adsense_<?php echo esc_attr( $quick_adsense_location ); ?>_adunits_device_controls">
	<b><?php esc_html_e( 'Ad Stats:', 'quick-adsense' ); ?></b><br />
	<?php
	echo wp_kses(
		quickadsense_get_control(
			'checkbox',
			__( 'Enable Stats', 'quick-adsense' ),
			'quick_adsense_settings_' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_enable_stats',
			'quick_adsense_settings[' . $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_enable_stats]',
			quick_adsense_get_value( $quick_adsense_template_args, $quick_adsense_location . '_ad_' . $quick_adsense_adunit_index . '_enable_stats' ),
			null,
			'input',
			'margin: -1px 5px 0 0;'
		),
		quick_adsense_get_allowed_html()
	);
	?>
</p>
<div class="qa-stats-actions">
	<button type="button" class="quick_adsense_<?php echo esc_attr( $quick_adsense_location ); ?>_ad_show_stats qa-btn qa-btn--outlined" data-index="<?php echo esc_attr( $quick_adsense_adunit_index ); ?>"><span class="dashicons dashicons-chart-line" aria-hidden="true"></span><?php esc_html_e( 'View Stats', 'quick-adsense' ); ?></button>
	<button type="button" class="quick_adsense_<?php echo esc_attr( $quick_adsense_location ); ?>_ad_reset_stats qa-btn qa-btn--outlined qa-btn--danger" data-index="<?php echo esc_attr( $quick_adsense_adunit_index ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span><?php esc_html_e( 'Reset Stats', 'quick-adsense' ); ?></button>
</div>
