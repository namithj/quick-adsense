<?php
/**
 * Detects changes to a file that the tests must never touch.
 *
 * Used for ABSPATH . 'ads.txt': the plugin's FileHandler would write it when the
 * filesystem method is "direct", so the user-config bootstrap disables that
 * method and this sentinel proves nothing slipped past.
 */
class QA_File_Sentinel {

	/**
	 * Record whether a file exists, and its size and modification time.
	 *
	 * @param string $path File path.
	 * @return array{exists:bool,size:int,mtime:int}
	 */
	public static function capture( $path ) {
		clearstatcache( true, $path );
		$exists = file_exists( $path );
		return [
			'exists' => $exists,
			'size'   => $exists ? (int) filesize( $path ) : 0,
			'mtime'  => $exists ? (int) filemtime( $path ) : 0,
		];
	}

	/**
	 * Compare the current state of a file with an earlier capture.
	 *
	 * @param string $path   File path.
	 * @param array  $before State from capture().
	 * @return string|null A failure message, or null when nothing changed.
	 */
	public static function changed( $path, array $before ) {
		$now = self::capture( $path );
		if ( $now === $before ) {
			return null;
		}
		return sprintf(
			'%s changed during the run (before: %s; after: %s). The user-config suite must never touch the site files.',
			$path,
			wp_json_encode( $before ),
			wp_json_encode( $now )
		);
	}
}
