<?php
/**
 * Return the content of the File after processing.
 *
 * @param string $file File name.
 * @param array  $args Data to pass to the file.
 */
function quick_adsense_load_file( $file, $args = [] ) {
	if ( ( '' !== $file ) && file_exists( dirname( __FILE__ ) . '/' . $file ) ) {
		//phpcs:disable
		// Usage of extract() is necessary in this content to simulate templating functionality.
		extract( $args );
		//phpcs:enable
		ob_start();
		include dirname( __FILE__ ) . '/' . $file;
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
}
