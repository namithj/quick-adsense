<?php
/**
 * Runs only the callbacks that this plugin registered on a hook.
 *
 * WordPress core, themes and other plugins add their own callbacks to
 * the_content, wp_head and friends, and those change between WordPress versions.
 * Running only the callbacks whose code lives inside the plugin directory keeps
 * the captured output version-independent.
 */
class QA_Plugin_Hook_Capture {

	/**
	 * Absolute path of the plugin directory, without a trailing slash.
	 *
	 * @return string
	 */
	public static function plugin_dir() {
		return rtrim( str_replace( '\\', '/', dirname( __DIR__, 3 ) ), '/' );
	}

	/**
	 * Convert an absolute file path to a plugin-relative one, or null.
	 *
	 * Only the plugin's own runtime code counts: quick-adsense.php and
	 * everything under includes/. The throwaway WordPress core lives in
	 * .cache/ inside the plugin directory, and the tests live in tests/, so a
	 * plain "inside the plugin directory" check would match both.
	 *
	 * @param string $file Absolute path.
	 * @return string|null
	 */
	public static function relative_path( $file ) {
		$file = str_replace( '\\', '/', (string) $file );
		$dir  = self::plugin_dir() . '/';
		if ( 0 !== strpos( $file, $dir ) ) {
			return null;
		}
		$relative = substr( $file, strlen( $dir ) );
		if ( 'quick-adsense.php' === $relative || 0 === strpos( $relative, 'includes/' ) ) {
			return $relative;
		}
		return null;
	}

	/**
	 * Return the file a callable is defined in, or null when it is unknown.
	 *
	 * @param callable $callback Callback.
	 * @return string|null
	 */
	public static function callback_file( $callback ) {
		try {
			if ( is_array( $callback ) ) {
				$reflection = new ReflectionMethod( $callback[0], $callback[1] );
			} elseif ( is_string( $callback ) && false !== strpos( $callback, '::' ) ) {
				$reflection = new ReflectionMethod( $callback );
			} elseif ( is_object( $callback ) && ! ( $callback instanceof Closure ) ) {
				$reflection = new ReflectionMethod( $callback, '__invoke' );
			} else {
				$reflection = new ReflectionFunction( $callback );
			}
			$file = $reflection->getFileName();
			return $file ? $file : null;
		} catch ( ReflectionException $exception ) {
			return null;
		}
	}

	/**
	 * Is this callable defined inside the plugin directory?
	 *
	 * @param callable $callback Callback.
	 * @return bool
	 */
	public static function is_plugin_callback( $callback ) {
		$file = self::callback_file( $callback );
		return null !== $file && null !== self::relative_path( $file );
	}

	/**
	 * Return the plugin's callbacks on a hook, in WordPress's execution order.
	 *
	 * @param string $hook Hook name.
	 * @return array[] Each item has priority, function and accepted_args.
	 */
	public static function callbacks( $hook ) {
		global $wp_filter;
		$found = [];
		if ( empty( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof WP_Hook ) {
			return $found;
		}
		$priorities = $wp_filter[ $hook ]->callbacks;
		ksort( $priorities, SORT_NUMERIC );
		foreach ( $priorities as $priority => $callbacks ) {
			foreach ( $callbacks as $entry ) {
				if ( self::is_plugin_callback( $entry['function'] ) ) {
					$found[] = [
						'priority'      => $priority,
						'function'      => $entry['function'],
						'accepted_args' => $entry['accepted_args'],
					];
				}
			}
		}
		return $found;
	}

	/**
	 * Run only the plugin's callbacks on a hook.
	 *
	 * With a non-null $value the hook is treated as a filter and the filtered
	 * value is returned. With null it is treated as an action and the echoed
	 * output is returned.
	 *
	 * @param string $hook  Hook name.
	 * @param mixed  $value Value to filter, or null for an action.
	 * @param array  $args  Extra arguments passed after the value.
	 * @return mixed
	 */
	public static function run( $hook, $value = null, array $args = [] ) {
		$is_filter = null !== $value;
		$all_args  = $is_filter ? array_merge( [ $value ], $args ) : $args;
		ob_start();
		try {
			foreach ( self::callbacks( $hook ) as $entry ) {
				$call_args = array_slice( $all_args, 0, $entry['accepted_args'] );
				$result    = call_user_func_array( $entry['function'], $call_args );
				if ( $is_filter ) {
					$value       = $result;
					$all_args[0] = $value;
				}
			}
		} catch ( Throwable $throwable ) {
			ob_end_clean();
			throw $throwable;
		}
		$output = ob_get_clean();
		return $is_filter ? $value : $output;
	}

	/**
	 * Describe a callable without anything that changes between runs.
	 *
	 * @param callable $callback Callback.
	 * @return string
	 */
	public static function describe( $callback ) {
		if ( $callback instanceof Closure ) {
			return 'Closure@' . self::relative_path( self::callback_file( $callback ) );
		}
		if ( is_array( $callback ) ) {
			$class = is_object( $callback[0] ) ? get_class( $callback[0] ) : $callback[0];
			return $class . '::' . $callback[1];
		}
		if ( is_object( $callback ) ) {
			return get_class( $callback ) . '::__invoke';
		}
		return (string) $callback;
	}

	/**
	 * List every callback this plugin registered, as stable text.
	 *
	 * One line per callback: hook, priority, accepted args, the position among
	 * the plugin's own callbacks at that hook and priority, and the callback.
	 * Sorted by hook name so the output does not depend on which hooks WordPress
	 * happened to create first.
	 *
	 * @return string
	 */
	public static function registry() {
		global $wp_filter;
		$hooks = array_keys( $wp_filter );
		sort( $hooks, SORT_STRING );
		$lines = [];
		foreach ( $hooks as $hook ) {
			$position = [];
			foreach ( self::callbacks( $hook ) as $entry ) {
				$priority              = $entry['priority'];
				$position[ $priority ] = ( $position[ $priority ] ?? 0 ) + 1;
				$lines[]               = sprintf(
					'%s priority=%s accepted_args=%d order=%d callback=%s',
					$hook,
					$priority,
					$entry['accepted_args'],
					$position[ $priority ],
					self::describe( $entry['function'] )
				);
			}
		}
		return implode( "\n", $lines ) . "\n";
	}
}
