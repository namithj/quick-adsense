<?php
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase, WordPress.Security.EscapeOutput.ExceptionNotEscaped -- DOM API property names; exceptions are developer-facing.
/**
 * A deliberately small simulation of a browser submitting an HTML form.
 *
 * Shared by every later release that needs to know what a classic settings
 * save really stores (R00 section 5.4.3, R14). It follows the HTML rules that
 * matter to Quick Adsense (C-6.2):
 *
 * - only checked checkboxes and radios are submitted;
 * - a single select submits its selected option, or its first option;
 * - a multiple select submits every selected option, as "name[]";
 * - a textarea loses ONE line feed directly after its start tag, and every line
 *   break in its value is then normalised to CRLF;
 * - text, number and hidden inputs are submitted as they are;
 * - disabled controls and buttons that were not clicked are not submitted.
 *
 * JavaScript is not run.
 */
class QA_Browser_Form {

	/**
	 * Build the POST array a browser would send for a form.
	 *
	 * @param string               $html      Page HTML that contains the form.
	 * @param string               $form_id   The form's id attribute.
	 * @param array<string,mixed>  $overrides Control name => replacement value. A null value removes the control.
	 * @return array POST data with PHP-style nested arrays.
	 */
	public static function submit( $html, $form_id, array $overrides = [] ) {
		$post = [];
		foreach ( self::pairs( $html, $form_id ) as $pair ) {
			list( $name, $value ) = $pair;
			if ( array_key_exists( $name, $overrides ) ) {
				continue;
			}
			self::assign( $post, $name, $value );
		}
		foreach ( $overrides as $name => $value ) {
			if ( null !== $value ) {
				self::assign( $post, $name, $value );
			}
		}
		return $post;
	}

	/**
	 * What a browser submits for a textarea whose stored value is $stored.
	 *
	 * Used to compute the expected result of a no-op save for ad code.
	 *
	 * @param string $stored The value that esc_textarea() printed.
	 * @return string
	 */
	public static function textarea_value( $stored ) {
		// The HTML preprocessor turns CRLF and lone CR into LF first.
		$value = str_replace( [ "\r\n", "\r" ], "\n", $stored );
		// The parser then drops ONE line feed directly after the start tag.
		if ( 0 === strpos( $value, "\n" ) ) {
			$value = substr( $value, 1 );
		}
		// Form submission normalises every line break to CRLF.
		return str_replace( "\n", "\r\n", $value );
	}

	/**
	 * List the names of every control in a form (successful or not).
	 *
	 * @param string $html    Page HTML.
	 * @param string $form_id The form's id attribute.
	 * @return string[]
	 */
	public static function control_names( $html, $form_id ) {
		$names = [];
		foreach ( self::form_node( $html, $form_id )->getElementsByTagName( '*' ) as $node ) {
			if ( in_array( $node->nodeName, [ 'input', 'select', 'textarea' ], true ) && $node->hasAttribute( 'name' ) ) {
				$names[] = $node->getAttribute( 'name' );
			}
		}
		return array_values( array_unique( $names ) );
	}

	/**
	 * Collect the successful name/value pairs of a form, in document order.
	 *
	 * @param string $html    Page HTML.
	 * @param string $form_id The form's id attribute.
	 * @return array[] List of [ name, value ].
	 */
	private static function pairs( $html, $form_id ) {
		$pairs             = [];
		$form              = self::form_node( $html, $form_id );
		$first_submit_done = false;
		foreach ( $form->getElementsByTagName( '*' ) as $node ) {
			$tag = $node->nodeName;
			if ( ! in_array( $tag, [ 'input', 'select', 'textarea' ], true ) ) {
				continue;
			}
			if ( ! $node->hasAttribute( 'name' ) || $node->hasAttribute( 'disabled' ) ) {
				continue;
			}
			$name = $node->getAttribute( 'name' );

			if ( 'textarea' === $tag ) {
				$pairs[] = [ $name, self::textarea_value( $node->textContent ) ];
				continue;
			}

			if ( 'select' === $tag ) {
				$options = [];
				foreach ( $node->getElementsByTagName( 'option' ) as $option ) {
					if ( ! $option->hasAttribute( 'disabled' ) ) {
						$options[] = $option;
					}
				}
				$selected = [];
				foreach ( $options as $option ) {
					if ( $option->hasAttribute( 'selected' ) ) {
						$selected[] = $option;
					}
				}
				if ( ! $node->hasAttribute( 'multiple' ) ) {
					if ( ! $selected && $options ) {
						$selected = [ $options[0] ];
					}
					$selected = array_slice( $selected, -1 );
				}
				foreach ( $selected as $option ) {
					$pairs[] = [ $name, $option->hasAttribute( 'value' ) ? $option->getAttribute( 'value' ) : $option->textContent ];
				}
				continue;
			}

			$type = strtolower( $node->getAttribute( 'type' ) );
			if ( 'checkbox' === $type || 'radio' === $type ) {
				if ( $node->hasAttribute( 'checked' ) ) {
					$pairs[] = [ $name, $node->hasAttribute( 'value' ) ? $node->getAttribute( 'value' ) : 'on' ];
				}
			} elseif ( 'submit' === $type ) {
				// The button the user clicks is submitted: the first one.
				if ( ! $first_submit_done ) {
					$pairs[]           = [ $name, $node->getAttribute( 'value' ) ];
					$first_submit_done = true;
				}
			} elseif ( ! in_array( $type, [ 'button', 'reset', 'image', 'file' ], true ) ) {
				$pairs[] = [ $name, $node->getAttribute( 'value' ) ];
			}
		}
		return $pairs;
	}

	/**
	 * Parse the page and return the form element.
	 *
	 * @param string $html    Page HTML.
	 * @param string $form_id The form's id attribute.
	 * @return DOMElement
	 */
	private static function form_node( $html, $form_id ) {
		$previous = libxml_use_internal_errors( true );
		$document = new DOMDocument();
		$document->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		$form = $document->getElementById( $form_id );
		if ( ! $form ) {
			// getElementById needs the id to be known as an ID by the parser.
			foreach ( $document->getElementsByTagName( 'form' ) as $candidate ) {
				if ( $candidate->getAttribute( 'id' ) === $form_id ) {
					$form = $candidate;
					break;
				}
			}
		}
		if ( ! $form ) {
			throw new RuntimeException( 'Form #' . $form_id . ' not found in the page.' );
		}
		return $form;
	}

	/**
	 * Store a value in a nested array the way PHP parses "a[b][]=v".
	 *
	 * @param array  $target Array to modify.
	 * @param string $name   Control name.
	 * @param string $value  Value.
	 */
	private static function assign( array &$target, $name, $value ) {
		if ( ! preg_match( '/^([^\[]+)((?:\[[^\]]*\])*)$/', $name, $m ) ) {
			$target[ $name ] = $value;
			return;
		}
		$keys = [ $m[1] ];
		if ( '' !== $m[2] ) {
			preg_match_all( '/\[([^\]]*)\]/', $m[2], $parts );
			foreach ( $parts[1] as $part ) {
				$keys[] = $part;
			}
		}
		$cursor = &$target;
		foreach ( $keys as $index => $key ) {
			$is_last = count( $keys ) - 1 === $index;
			if ( '' === $key && $index > 0 ) {
				if ( $is_last ) {
					$cursor[] = $value;
					return;
				}
				$cursor[] = [];
				$key      = array_key_last( $cursor );
			}
			if ( $is_last ) {
				$cursor[ $key ] = $value;
				return;
			}
			if ( ! isset( $cursor[ $key ] ) || ! is_array( $cursor[ $key ] ) ) {
				$cursor[ $key ] = [];
			}
			$cursor = &$cursor[ $key ];
		}
	}
}
