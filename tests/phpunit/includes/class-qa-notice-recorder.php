<?php
/**
 * Records PHP notices, warnings and deprecations raised by the plugin's code.
 *
 * The frozen v2.9.4 quirks include undefined-key warnings (C-4.2, config 19),
 * which PHPUnit would otherwise turn into test errors. Every entry is reduced
 * to "<kind>:<key or message stem>@<plugin-relative file>:<line>" so the output
 * is identical on PHP 7.4 ("Undefined index") and PHP 8 ("Undefined array key").
 * Notices raised outside the plugin directory are not recorded.
 */
class QA_Notice_Recorder {

	/**
	 * Recorded entries, in order of first occurrence, mapped to a count.
	 *
	 * @var array<string, int>
	 */
	private $entries = [];

	/**
	 * The handler that was active before start().
	 *
	 * @var callable|null
	 */
	private $previous = null;

	/**
	 * Whether the handler is installed.
	 *
	 * @var bool
	 */
	private $active = false;

	/**
	 * Deprecations that only newer PHP versions raise. They are ignored so the
	 * notices section is identical on PHP 7.4 and PHP 8.x.
	 *
	 * @var string[]
	 */
	const NEWER_PHP_DEPRECATIONS = [
		'/Passing null to parameter/',
		'/Automatic conversion of false to array/',
		'/Implicit conversion from float/',
		'/Creation of dynamic property/',
		'/Optional parameter .* declared before required parameter/',
		'/Using null as an array offset/',
		'/Accessing static property .* as non static/',
	];

	/**
	 * Start recording. Replaces the current error handler.
	 */
	public function start() {
		$this->entries  = [];
		$this->previous = set_error_handler( [ $this, 'handle' ], E_ALL ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Test instrumentation.
		$this->active   = true;
	}

	/**
	 * Stop recording and restore the previous handler.
	 *
	 * @return string[] Normalised entries, one string each.
	 */
	public function stop() {
		if ( $this->active ) {
			restore_error_handler();
			$this->active = false;
		}
		$lines = [];
		foreach ( $this->entries as $entry => $count ) {
			$lines[] = $count > 1 ? $entry . ' x' . $count : $entry;
		}
		return $lines;
	}

	/**
	 * Record a thrown error or exception as a notices entry.
	 *
	 * @param Throwable $throwable What was thrown.
	 */
	public function add_throwable( Throwable $throwable ) {
		$file = QA_Plugin_Hook_Capture::relative_path( $throwable->getFile() );
		if ( null === $file ) {
			$file = 'outside-plugin:' . basename( $throwable->getFile() );
		}
		$this->add( 'throwable:' . get_class( $throwable ) . ': ' . $throwable->getMessage() . '@' . $file . ':' . $throwable->getLine() );
	}

	/**
	 * The error handler. Always returns true so PHP's own handling is skipped.
	 *
	 * @param int    $errno   Error level.
	 * @param string $errstr  Message.
	 * @param string $errfile File.
	 * @param int    $errline Line.
	 * @return bool
	 */
	public function handle( $errno, $errstr, $errfile = '', $errline = 0 ) {
		if ( ! ( error_reporting() & $errno ) ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- Reads the level, to honour the @ operator.
			return true; // Suppressed with the @ operator.
		}
		$file = QA_Plugin_Hook_Capture::relative_path( $errfile );
		if ( null === $file ) {
			return true; // Raised outside the plugin.
		}

		$is_deprecation = E_DEPRECATED === $errno || E_USER_DEPRECATED === $errno;
		if ( $is_deprecation ) {
			foreach ( self::NEWER_PHP_DEPRECATIONS as $pattern ) {
				if ( preg_match( $pattern, $errstr ) ) {
					return true;
				}
			}
		}

		$this->add( self::classify( $errno, $errstr ) . '@' . $file . ':' . $errline );
		return true;
	}

	/**
	 * Reduce an error to "<kind>:<stem>".
	 *
	 * @param int    $errno  Error level.
	 * @param string $errstr Message.
	 * @return string
	 */
	public static function classify( $errno, $errstr ) {
		if ( preg_match( '/^Undefined (?:index|offset|array key):? ?"?([^"]*)"?$/', $errstr, $m ) ) {
			return 'undefined-key:' . $m[1];
		}
		if ( preg_match( '/^Undefined variable:? \$?(\w+)$/', $errstr, $m ) ) {
			return 'undefined-variable:' . $m[1];
		}
		if ( E_DEPRECATED === $errno || E_USER_DEPRECATED === $errno ) {
			return 'deprecated:' . $errstr;
		}
		if ( preg_match( '/^Trying to access array offset on (?:value of type )?(\w+)$/', $errstr, $m ) ) {
			return 'other:array-offset-on-' . $m[1];
		}
		return 'other:' . $errstr;
	}

	/**
	 * Add one entry.
	 *
	 * @param string $entry Normalised entry.
	 */
	private function add( $entry ) {
		$this->entries[ $entry ] = ( $this->entries[ $entry ] ?? 0 ) + 1;
	}
}
