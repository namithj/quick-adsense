<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Function to return the markup to display an HTML user input element.
 *
 * @param string $type The element type.
 * @param string $label The element label.
 * @param string $id The element id.
 * @param string $name The element name.
 * @param string $value The element value.
 * @param array  $data The element data.  Eg for options in a select.
 * @param string $class The element classes.
 * @param string $style The element styles.
 * @param string $placeholder The element placeholder.
 *
 * @return string the markup for the HTML element.
 */
function quickadsense_get_control( $type, $label, $id, $name, $value = '', $data = null, $class = 'input widefat', $style = '', $placeholder = '' ) {
	$output     = '';
	$label_html = wp_kses(
		$label,
		[
			'b' => [
				'id' => [],
			],
		]
	);
	switch ( $type ) {
		case 'hidden':
			$output .= '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" style="display: none;" />';
			break;
		case 'text':
			if ( '' !== $label ) {
				$output .= '<label for="' . esc_attr( $id ) . '">' . $label_html . '</label>';
			}
			$output .= '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="multilanguage-input ' . esc_attr( $class ) . '" style="' . esc_attr( $style ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';
			break;
		case 'password':
			if ( '' !== $label ) {
				$output .= '<label for="' . esc_attr( $id ) . '">' . $label_html . '</label>';
			}
			$output .= '<input type="password" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="multilanguage-input ' . esc_attr( $class ) . '" style="' . esc_attr( $style ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';
			break;
		case 'number':
			if ( '' !== $label ) {
				$output .= '<label for="' . esc_attr( $id ) . '">' . $label_html . '</label>';
			}
			$output .= '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="multilanguage-input ' . esc_attr( $class ) . '" style="' . esc_attr( $style ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';
			break;
		case 'checkbox':
			$output .= '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" class="input" ' . checked( $value, 1, false ) . ' style="' . esc_attr( $style ) . '" />';
			if ( '' !== $label ) {
				$output .= '<label for="' . esc_attr( $id ) . '">' . $label_html . '</label>';
			}
			break;
		case 'textarea':
			if ( '' !== $label ) {
				$output .= '<label for="' . esc_attr( $id ) . '">' . $label_html . '</label><br />';
			}
			$output .= '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="multilanguage-input ' . esc_attr( $class ) . '" style="height: 100px; ' . esc_attr( $style ) . '" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( $value ) . '</textarea>';
			break;
		case 'textarea-big':
			if ( '' !== $label ) {
				$output .= '<label for="' . esc_attr( $id ) . '">' . $label_html . '</label><br />';
			}
			$output .= '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="multilanguage-input ' . esc_attr( $class ) . '" style="height: 300px; ' . esc_attr( $style ) . '" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( $value ) . '</textarea>';
			break;
		case 'select':
			if ( '' !== $label ) {
				$output .= '<label for="' . esc_attr( $id ) . '">' . $label_html . '</label>';
			}
			$output .= '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $class ) . '" style="' . esc_attr( $style ) . '" >';
			if ( $data ) {
				foreach ( $data as $option ) {
					$metadata = '';
					if ( isset( $option['metadata'] ) && is_array( $option['metadata'] ) ) {
						foreach ( $option['metadata'] as $key => $metavalue ) {
							$metadata .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $metavalue ) . '"';
						}
					}
					$output .= '<option' . $metadata . ' value="' . esc_attr( $option['value'] ) . '" ' . selected( $value, $option['value'], false ) . '>' . esc_html( $option['text'] ) . '</option>';
				}
			}
			$output .= '</select>';
			break;
		case 'upload':
			if ( '' !== $label ) {
				$output .= '<label for="' . esc_attr( $id ) . '">' . $label_html . '</label><br />';
			}
			$output .= '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="' . esc_attr( $class ) . '" style="width: 74%; ' . esc_attr( $style ) . '" />';
			$output .= '<input type="button" value="Upload Image" class="quick_adsense_uploader_button" id="upload_image_button" style="width: 25%;" />';
			break;
		case 'multiselect':
			if ( '' !== $label ) {
				$output .= '<label for="' . esc_attr( $id ) . '">' . $label_html . '</label><br />';
			}
			$output .= '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $class ) . '" multiple="multiple" style="height: 120px; ' . esc_attr( $style ) . '" >';
			if ( $data ) {
				foreach ( $data as $option ) {
					if ( is_array( $value ) && in_array( $option['value'], $value, true ) ) {
						$output .= '<option value="' . esc_attr( $option['value'] ) . '" selected="selected">' . esc_html( $option['text'] ) . '</option>';
					} else {
						$output .= '<option value="' . esc_attr( $option['value'] ) . '">' . esc_html( $option['text'] ) . '</option>';
					}
				}
			}
			$output .= '</select>';
			break;
	}
	return $output;
}

/**
 * Check for null and empty and returns the value or the default
 *
 * @param array  $data The data.
 * @param string $field_name The name of field/key for data.
 * @param string $default The default value to send if the selected data value if empty or not set.
 */
function quick_adsense_get_value( $data, $field_name, $default = '' ) {
	if ( isset( $data ) && is_array( $data ) && isset( $data[ $field_name ] ) && ( '' !== $data[ $field_name ] ) ) {
		return $data[ $field_name ];
	}
	return $default;
}

/**
 * Output an administrator-authorized ad snippet without changing its bytes.
 *
 * Ad snippets are executable by design and are protected at the settings-save
 * capability boundary. Escaping or KSES filtering here would corrupt valid
 * provider HTML and JavaScript.
 *
 * @param mixed $code Stored ad code.
 */
function quick_adsense_echo_ad_code( $code ) {
	if ( is_string( $code ) ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Opaque administrator-authorized ad code; see function contract.
		echo $code;
	}
}

/**
 * Allowed HTML attributes and tags for the wp_kses function.
 */
function quick_adsense_get_allowed_html() {
	$common_html_attributes = [
		'id'             => [],
		'name'           => [],
		'class'          => [],
		'for'            => [],
		'href'           => [],
		'target'         => [],
		'rel'            => [],
		'title'          => [],
		'datetime'       => [],
		'style'          => [],
		'alt'            => [],
		'height'         => [],
		'src'            => [],
		'srcset'         => [],
		'width'          => [],
		'type'           => [],
		'value'          => [],
		'checked'        => [],
		'selected'       => [],
		'multiple'       => [],
		'data-index'     => [],
		'onclick'        => [],
		'async'          => [],
		'crossorigin'    => [],
		'action'         => [],
		'method'         => [],
		'content'        => [],
		'property'       => [],
		'data-ad-client' => [],
		'data-ad-slot'   => [],
	];
	$common_html_tags       = [
		'a'          => $common_html_attributes,
		'abbr'       => $common_html_attributes,
		'b'          => $common_html_attributes,
		'br'         => $common_html_attributes,
		'blockquote' => $common_html_attributes,
		'cite'       => $common_html_attributes,
		'code'       => $common_html_attributes,
		'del'        => $common_html_attributes,
		'dd'         => $common_html_attributes,
		'div'        => $common_html_attributes,
		'dl'         => $common_html_attributes,
		'dt'         => $common_html_attributes,
		'em'         => $common_html_attributes,
		'form'       => $common_html_attributes,
		'h1'         => $common_html_attributes,
		'h2'         => $common_html_attributes,
		'h3'         => $common_html_attributes,
		'h4'         => $common_html_attributes,
		'h5'         => $common_html_attributes,
		'h6'         => $common_html_attributes,
		'hr'         => $common_html_attributes,
		'i'          => $common_html_attributes,
		'img'        => $common_html_attributes,
		'ins'        => $common_html_attributes,
		'label'      => $common_html_attributes,
		'link'       => $common_html_attributes,
		'li'         => $common_html_attributes,
		'meta'       => $common_html_attributes,
		'ol'         => $common_html_attributes,
		'ul'         => $common_html_attributes,
		'p'          => $common_html_attributes,
		'q'          => $common_html_attributes,
		'span'       => $common_html_attributes,
		'strike'     => $common_html_attributes,
		'strong'     => $common_html_attributes,
		'script'     => $common_html_attributes,
		'noscript'   => $common_html_attributes,
		'style'      => $common_html_attributes,
		'input'      => $common_html_attributes,
		'textarea'   => $common_html_attributes,
		'title'      => $common_html_attributes,
		'select'     => $common_html_attributes,
		'option'     => $common_html_attributes,
	];
	return $common_html_tags;
}
