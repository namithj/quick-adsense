<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the content of the File after processing.
 *
 * @param string  $file File name.
 * @param array   $args Data to pass to the file.
 * @param boolean $echo Choose whether to echo or return the output.
 */
function quick_adsense_load_file( $file, $args = [], $echo = false ) {
	$base_path = __DIR__ . DIRECTORY_SEPARATOR;
	$file_path = is_string( $file ) ? realpath( $base_path . $file ) : false;
	if ( false !== $file_path && 0 === strpos( $file_path, $base_path ) && 'php' === pathinfo( $file_path, PATHINFO_EXTENSION ) ) {
		$quick_adsense_template_args = is_array( $args ) ? $args : [];
		ob_start();
		include $file_path;
		$content = ob_get_contents();
		ob_end_clean();
		if ( $echo ) {
			echo wp_kses( $content, quick_adsense_get_allowed_html() );
		}
		return $content;
	}
}
