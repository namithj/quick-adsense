<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The plugin settings page.
 */

/**
 * Return the option keys whose values are executable ad or tracking snippets.
 *
 * @return string[] Ad-code option keys.
 */
function quick_adsense_get_ad_code_fields() {
	$fields = [ 'header_embed_code', 'footer_embed_code' ];
	for ( $index = 1; $index <= 10; $index++ ) {
		$fields[] = 'onpost_ad_' . $index . '_content';
		$fields[] = 'widget_ad_' . $index . '_content';
	}
	return $fields;
}

/**
 * Return every supported settings key.
 *
 * @return array<string, bool> Key lookup table.
 */
function quick_adsense_get_allowed_setting_fields() {
	$fields = array_fill_keys( array_keys( quick_adsense_get_defaults() ), true );
	foreach ( quick_adsense_get_ad_code_fields() as $field ) {
		$fields[ $field ] = true;
	}

	foreach ( [ 'widget_enable_global_style', 'widget_global_alignment', 'widget_global_margin' ] as $field ) {
		$fields[ $field ] = true;
	}

	$advanced_suffixes = [
		'hide_device_mobile',
		'hide_device_tablet',
		'hide_device_desktop',
		'hide_visitor_searchengine',
		'hide_visitor_indirect',
		'hide_visitor_direct',
		'hide_visitor_bot',
		'hide_visitor_knownbrowser',
		'hide_visitor_unknownbrowser',
		'hide_visitor_guest',
		'hide_visitor_loggedin',
		'limit_visitor_country',
		'enable_stats',
	];
	for ( $index = 1; $index <= 10; $index++ ) {
		foreach ( $advanced_suffixes as $suffix ) {
			$fields[ 'onpost_ad_' . $index . '_' . $suffix ] = true;
		}
	}

	return $fields;
}

/**
 * Sanitize settings without transforming administrator-authorized ad code.
 *
 * WordPress options.php unslashes request data before the Settings API invokes
 * this callback. Ad code is therefore stored as received here and otherwise
 * treated as opaque. Users who cannot publish unfiltered HTML may edit ordinary
 * settings, but cannot replace or erase a previously stored executable snippet.
 *
 * @param mixed $input Submitted option value.
 * @return array Sanitized settings.
 */
function quick_adsense_sanitize_settings( $input ) {
	if ( ! is_array( $input ) ) {
		return [];
	}

	$allowed         = quick_adsense_get_allowed_setting_fields();
	$code_fields     = quick_adsense_get_ad_code_fields();
	$existing        = get_option( 'quick_adsense_settings', [] );
	$existing        = is_array( $existing ) ? $existing : [];
	$may_update_code = current_user_can( 'manage_options' ) && current_user_can( 'unfiltered_html' );
	$sanitized       = [];

	foreach ( $input as $field => $value ) {
		if ( ! is_string( $field ) || ! isset( $allowed[ $field ] ) ) {
			continue;
		}

		if ( in_array( $field, $code_fields, true ) ) {
			if ( $may_update_code && is_string( $value ) ) {
				$sanitized[ $field ] = $value;
			} elseif ( array_key_exists( $field, $existing ) ) {
				$sanitized[ $field ] = $existing[ $field ];
			}
			continue;
		}

		if ( preg_match( '/_limit_visitor_country$/', $field ) ) {
			if ( is_array( $value ) ) {
				$countries           = array_map(
					static function ( $country ) {
						return strtoupper( sanitize_text_field( $country ) );
					},
					$value
				);
				$sanitized[ $field ] = array_values( array_unique( preg_grep( '/^[A-Z]{2}$/', $countries ) ) );
			}
			continue;
		}

		if ( preg_match( '/(^enable_|^disable_|_enable_|_hide_)/', $field ) ) {
			$sanitized[ $field ] = '1';
			continue;
		}

		if ( ! is_scalar( $value ) ) {
			continue;
		}

		$range = quick_adsense_get_setting_range( $field );
		if ( null !== $range ) {
			$sanitized[ $field ] = (string) max( $range[0], min( $range[1], absint( $value ) ) );
		} else {
			$sanitized[ $field ] = sanitize_text_field( $value );
		}
	}

	if ( ! $may_update_code ) {
		foreach ( $code_fields as $field ) {
			if ( array_key_exists( $field, $existing ) ) {
				$sanitized[ $field ] = $existing[ $field ];
			}
		}
	}

	delete_transient( 'quick_adsense_adstxt_adsense_autocheck_content' );
	return $sanitized;
}

/**
 * Return the allowed integer range of a numeric settings key (C-2.2).
 *
 * @param string $field Settings key.
 * @return int[]|null Array of minimum and maximum, or null when the key is not numeric.
 */
function quick_adsense_get_setting_range( $field ) {
	if ( 'max_ads_per_page' === $field || 0 === strpos( $field, 'ad_' ) ) {
		return [ 0, 10 ];
	}
	if ( false !== strpos( $field, '_alignment' ) ) {
		return [ 1, 4 ];
	}
	if ( false !== strpos( $field, '_margin' ) || 0 === strpos( $field, 'position_' ) ) {
		return [ 0, 10000 ];
	}
	return null;
}

/**
 * Validate an imported settings array, preserving its shape (C-2.3, C-10.8).
 *
 * Unlike quick_adsense_sanitize_settings() this never turns a '0' switch on and
 * never rewrites a value that is already valid. Unknown keys are dropped, numeric
 * keys are clamped, country lists keep only valid codes and ad code is kept byte
 * for byte, but only for a user with manage_options and unfiltered_html. For any
 * other user the stored code is kept.
 *
 * @param array $settings Decoded settings from an export file.
 * @return array {
 *     @type array $settings Validated settings, ready for Snapshots::write_settings().
 *     @type array $report   List of entries with 'type' (unknown, clamped, converted, countries, dropped,
 *                           code_kept), 'key' and, for clamped and converted values, 'from' and 'to'.
 * }
 */
function quick_adsense_import_validate( array $settings ) {
	$allowed         = quick_adsense_get_allowed_setting_fields();
	$code_fields     = quick_adsense_get_ad_code_fields();
	$existing        = get_option( 'quick_adsense_settings', [] );
	$existing        = is_array( $existing ) ? $existing : [];
	$may_update_code = current_user_can( 'manage_options' ) && current_user_can( 'unfiltered_html' );
	$valid           = [];
	$report          = [];

	foreach ( $settings as $field => $value ) {
		if ( ! is_string( $field ) || ! isset( $allowed[ $field ] ) ) {
			$report[] = [
				'type' => 'unknown',
				'key'  => (string) $field,
			];
			continue;
		}

		if ( in_array( $field, $code_fields, true ) ) {
			if ( ! is_string( $value ) ) {
				$report[] = [
					'type' => 'dropped',
					'key'  => $field,
				];
			} elseif ( $may_update_code ) {
				$valid[ $field ] = $value;
			} elseif ( ! array_key_exists( $field, $existing ) || $existing[ $field ] !== $value ) {
				$report[] = [
					'type' => 'code_kept',
					'key'  => $field,
				];
			}
			continue;
		}

		if ( preg_match( '/_limit_visitor_country$/', $field ) ) {
			if ( ! is_array( $value ) ) {
				$report[] = [
					'type' => 'dropped',
					'key'  => $field,
				];
				continue;
			}
			$codes = [];
			foreach ( $value as $code ) {
				if ( is_string( $code ) && 1 === preg_match( '/^[A-Z]{2}$/', $code ) && ! in_array( $code, $codes, true ) ) {
					$codes[] = $code;
				}
			}
			if ( array_values( $value ) !== $codes || array_keys( $value ) !== array_keys( $codes ) ) {
				$report[] = [
					'type' => 'countries',
					'key'  => $field,
				];
			}
			$valid[ $field ] = $codes;
			continue;
		}

		if ( ! is_scalar( $value ) ) {
			$report[] = [
				'type' => 'dropped',
				'key'  => $field,
			];
			continue;
		}

		if ( preg_match( '/(^enable_|^disable_|_enable_|_hide_)/', $field ) ) {
			// Switches keep their stored string: '0' stays '0' (shape 1).
			if ( is_string( $value ) ) {
				$valid[ $field ] = $value;
			} else {
				$valid[ $field ] = (string) $value;
				$report[]        = [
					'type' => 'converted',
					'key'  => $field,
					'from' => var_export( $value, true ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Display value for the report.
					'to'   => (string) $value,
				];
			}
			continue;
		}

		$range = quick_adsense_get_setting_range( $field );
		if ( null !== $range ) {
			$fixed = (string) max( $range[0], min( $range[1], absint( $value ) ) );
			if ( is_string( $value ) && ctype_digit( $value ) && (int) $value >= $range[0] && (int) $value <= $range[1] ) {
				$fixed = $value;
			}
			if ( $fixed !== $value ) {
				$report[] = [
					'type' => is_int( $value ) && (string) $value === $fixed ? 'converted' : 'clamped',
					'key'  => $field,
					'from' => is_string( $value ) ? $value : var_export( $value, true ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Display value for the report.
					'to'   => $fixed,
				];
			}
			$valid[ $field ] = $fixed;
			continue;
		}

		$clean = is_string( $value ) ? sanitize_text_field( $value ) : (string) $value;
		if ( $clean !== $value ) {
			$report[] = [
				'type' => 'converted',
				'key'  => $field,
				'from' => is_string( $value ) ? $value : var_export( $value, true ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Display value for the report.
				'to'   => $clean,
			];
		}
		$valid[ $field ] = $clean;
	}

	if ( ! $may_update_code ) {
		foreach ( $code_fields as $field ) {
			if ( array_key_exists( $field, $existing ) ) {
				$valid[ $field ] = $existing[ $field ];
			}
		}
	}

	return [
		'settings' => $valid,
		'report'   => $report,
	];
}

/**
 * Add a settings page link to the plugin listing in the plugins admin page.
 */
add_action(
	'plugin_action_links_quick-adsense/quick-adsense.php',
	function ( $links ) {
		$links = array_merge(
			[ '<a href="' . esc_url( admin_url( '/admin.php?page=quick-adsense' ) ) . '">' . esc_html__( 'Settings', 'quick-adsense' ) . '</a>' ],
			$links
		);
		return $links;
	}
);

/**
 * Create the Admin menu entry for the settings page.
 */
add_action(
	'admin_menu',
	function () {
		add_menu_page(
			__( 'Quick Adsense Options', 'quick-adsense' ),
			'Quick Adsense', // Brand name: not translated, because core derives admin page hook suffixes from it.
			'manage_options',
			'quick-adsense',
			function () {
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				// Contains textareas which need to allow output of scripts, iframes etc necessary to output ads and trackers.
				echo quick_adsense_load_file( 'templates/page-settings.php' );
				// phpcs:enable
			}
		);
		// Explicit first submenu so the top-level entry keeps opening the settings page (C-8).
		add_submenu_page( 'quick-adsense', __( 'Quick Adsense Options', 'quick-adsense' ), __( 'Settings', 'quick-adsense' ), 'manage_options', 'quick-adsense' );
	}
);

/**
 * Add scripts and styles for Settings page.
 */
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		global $wp_scripts;
		if ( ( 'toplevel_page_quick-adsense' === $hook ) && ( current_user_can( 'manage_options' ) ) ) {
			wp_enqueue_script( 'quick-adsense-chart-scripts', plugins_url( '../assets/js/chart.min.js', __FILE__ ), [], '3.7.1', false );
			wp_enqueue_style( 'quick-adsense-admin-styles', plugins_url( '../assets/css/admin.css', __FILE__ ), [], QUICK_ADSENSE_VERSION );
			wp_enqueue_script( 'quick-adsense-admin-scripts', plugins_url( '../assets/js/admin.js', __FILE__ ), [ 'jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'wp-util', 'wp-i18n' ], QUICK_ADSENSE_VERSION, false );
			wp_set_script_translations( 'quick-adsense-admin-scripts', 'quick-adsense' );
			wp_localize_script(
				'quick-adsense-admin-scripts',
				'quick_adsense',
				[
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'quick-adsense-nonce' ),
				]
			);
		}
	}
);

/**
 * Hide the tabs until admin.js has built them, so a visitor with JavaScript never sees the unstyled form.
 * Without JavaScript the noscript rule below shows the form.
 *
 * @param string $classes Space-separated body classes.
 * @return string
 */
add_filter(
	'admin_body_class',
	function ( $classes ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'toplevel_page_quick-adsense' === $screen->id && current_user_can( 'manage_options' ) ) {
			$classes .= ' qa-js-loading';
		}
		return $classes;
	}
);

/**
 * Show the form when JavaScript is off (the qa-js-loading class is only removed by admin.js).
 */
add_action(
	'admin_head-toplevel_page_quick-adsense',
	function () {
		if ( current_user_can( 'manage_options' ) ) {
			echo '<noscript><style>.qa-js-loading #quick_adsense_settings_tabs{display:flex}</style></noscript>' . "\n";
		}
	}
);

/**
 * Register settings and sections.
 */
add_action(
	'admin_init',
	function () {
		if ( current_user_can( 'manage_options' ) ) {
			register_setting(
				'quick_adsense_settings',
				'quick_adsense_settings',
				[
					'type'              => 'array',
					'sanitize_callback' => 'quick_adsense_sanitize_settings',
				]
			);
			$settings = get_option( 'quick_adsense_settings' );
			if ( false === $settings ) {
				// A missing option row: start from an empty array instead of PHP's deprecated false-to-array conversion.
				$settings = [];
			}
			$settings['alignment_options'] = [
				[
					'text'  => __( 'Left', 'quick-adsense' ),
					'value' => '1',
				],
				[
					'text'  => __( 'Center', 'quick-adsense' ),
					'value' => '2',
				],
				[
					'text'  => __( 'Right', 'quick-adsense' ),
					'value' => '3',
				],
				[
					'text'  => __( 'None', 'quick-adsense' ),
					'value' => '4',
				],
			];
			add_settings_section(
				'quick_adsense_general',
				'',
				function () use ( $settings ) {
					echo wp_kses(
						quick_adsense_load_file( 'templates/section-general.php', $settings ),
						quick_adsense_get_allowed_html()
					);
				},
				'quick-adsense-general'
			);
			$settings['location'] = 'onpost';
			add_settings_section(
				'quick_adsense_onpost',
				'',
				function () use ( $settings ) {
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					// Contains textareas which need to allow output of scripts, iframes etc necessary to output ads and trackers.
					echo quick_adsense_load_file( 'templates/section-onpost-content.php', $settings );
					// phpcs:enable
				},
				'quick-adsense-onpost'
			);
			$settings['location'] = 'widgets';
			add_settings_section(
				'quick_adsense_widgets',
				'',
				function () use ( $settings ) {
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					// Contains textareas which need to allow output of scripts, iframes etc necessary to output ads and trackers.
					echo quick_adsense_load_file( 'templates/section-widgets.php', $settings );
					// phpcs:enable
				},
				'quick-adsense-widgets'
			);
			$settings['location'] = 'header_footer_codes';
			add_settings_section(
				'quick_adsense_header_footer_codes',
				'',
				function () use ( $settings ) {
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					// Contains textareas which need to allow output of scripts, iframes etc necessary to output ads and trackers.
					echo quick_adsense_load_file( 'templates/section-header-footer.php', $settings );
					// phpcs:enable					
				},
				'quick-adsense-header-footer-codes'
			);
		}
	}
);

add_action(
	'wp_ajax_quick_adsense_onpost_ad_reset_stats',
	function () {
		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'quick-adsense-nonce' ) && ( isset( $_POST['index'] ) ) && ( current_user_can( 'manage_options' ) ) ) {
			$index = quick_adsense_validate_ad_index( sanitize_text_field( wp_unslash( $_POST['index'] ) ) );
			if ( false !== $index ) {
				delete_option( 'quick_adsense_onpost_ad_' . $index . '_stats' );
				wp_send_json_success();
			}
		}
		wp_send_json_error();
	}
);


add_action(
	'wp_ajax_quick_adsense_onpost_ad_get_stats_chart',
	function () {
		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'quick-adsense-nonce' ) && ( isset( $_POST['index'] ) ) && ( current_user_can( 'manage_options' ) ) ) {
			$index = quick_adsense_validate_ad_index( sanitize_text_field( wp_unslash( $_POST['index'] ) ) );
			if ( false === $index ) {
				wp_send_json_error();
			}
			$stats      = get_option( 'quick_adsense_onpost_ad_' . $index . '_stats' );
			$stats_data = [];
			for ( $i = 0; $i < 30; $i++ ) {
				$clicks      = 0;
				$impressions = 0;
				if ( isset( $stats ) && is_array( $stats ) && isset( $stats[ gmdate( 'dmY', strtotime( '-' . $i . ' day' ) ) ] ) ) {
					$clicks      = $stats[ gmdate( 'dmY', strtotime( '-' . $i . ' day' ) ) ]['c'];
					$impressions = $stats[ gmdate( 'dmY', strtotime( '-' . $i . ' day' ) ) ]['i'];
				}
				$stats_data[] = [
					'x'  => gmdate( 'm/d/Y', strtotime( '-' . $i . ' day' ) ),
					'y'  => $impressions,
					'y1' => $clicks,
				];
			}
			wp_send_json_success(
				quick_adsense_load_file(
					'templates/block-stats-chart.php',
					[
						'stats_data' => wp_json_encode( $stats_data ),
					]
				)
			);
		}
		wp_send_json_error();
	}
);
