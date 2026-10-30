<?php
namespace QuickAdsense;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings snapshots: a ring of the five most recent copies of the plugin's
 * settings, stored in autoload=no options (legacy data contract C-10.3).
 *
 * This class is storage only. Capability and nonce checks belong to the caller
 * (see SnapshotsAdmin).
 */
class Snapshots {
	/**
	 * Prefix of every snapshot option name.
	 */
	const PREFIX = 'quick_adsense_settings_backup_';

	/**
	 * Option that lists the snapshot option names, oldest first.
	 */
	const INDEX = 'quick_adsense_settings_backups';

	/**
	 * Number of snapshots kept.
	 */
	const KEEP = 5;

	/**
	 * Matches the sidebar ids of this plugin's widgets.
	 */
	const WIDGET_ID_PATTERN = '/^adswidget([1-9]|10)-quick-adsense-\d+$/';

	/**
	 * Create a snapshot of the current settings.
	 *
	 * @param string $reason Why the snapshot is taken (normalised to [a-z0-9._-]).
	 * @return string|false The new option name, or false when it could not be stored.
	 */
	public static function create( $reason ) {
		$reason = preg_replace( '/[^a-z0-9._-]/', '', strtolower( (string) $reason ) );
		if ( '' === $reason ) {
			$reason = 'manual';
		}

		$index = self::index();
		$base  = self::PREFIX . gmdate( 'YmdHis' ) . '_' . $reason;
		$name  = $base;
		$n     = 2;
		while ( in_array( $name, $index, true ) || false !== get_option( $name ) ) {
			$name = $base . '-' . $n;
			++$n;
		}

		$instances = [];
		for ( $i = 1; $i <= 10; $i++ ) {
			$option = 'widget_adswidget' . $i . '-quick-adsense';
			$value  = get_option( $option );
			if ( false !== $value ) {
				$instances[ $option ] = $value;
			}
		}

		$payload = [
			'plugin_version'   => QUICK_ADSENSE_VERSION,
			'created_at'       => gmdate( 'Y-m-d H:i:s' ),
			'reason'           => $reason,
			'settings'         => get_option( 'quick_adsense_settings' ),
			'widgets'          => self::collect_widgets(),
			'widget_instances' => $instances,
			'notice_dismissed' => get_option( 'quick_adsense_adstxt_adsense_admin_notice_dismissed' ),
		];

		if ( ! add_option( $name, $payload, '', false ) ) {
			return false;
		}

		$index[] = $name;
		update_option( self::INDEX, $index, false );
		self::prune();

		return $name;
	}

	/**
	 * Return every stored snapshot, newest first.
	 *
	 * @return array<string, array> Option name => payload.
	 */
	public static function all() {
		$found = [];
		foreach ( array_reverse( self::index() ) as $name ) {
			$payload = get_option( $name );
			if ( is_array( $payload ) && array_key_exists( 'settings', $payload ) ) {
				$found[ $name ] = $payload;
			}
		}
		return $found;
	}

	/**
	 * Return one snapshot payload.
	 *
	 * Only names listed in the index are accepted, never an arbitrary option name.
	 *
	 * @param string $name Snapshot option name.
	 * @return array|null Payload, or null when unknown or malformed.
	 */
	public static function get( $name ) {
		if ( ! is_string( $name ) || ! in_array( $name, self::index(), true ) ) {
			return null;
		}
		$payload = get_option( $name );
		if ( is_array( $payload ) && array_key_exists( 'settings', $payload ) ) {
			return $payload;
		}
		return null;
	}

	/**
	 * Keep only the newest KEEP snapshots and drop index entries with no option.
	 */
	public static function prune() {
		$index = self::index();
		$keep  = [];
		foreach ( $index as $name ) {
			if ( false !== get_option( $name ) ) {
				$keep[] = $name;
			}
		}
		$surplus = count( $keep ) - self::KEEP;
		for ( $i = 0; $i < $surplus; $i++ ) {
			$oldest = array_shift( $keep );
			// A foreign name planted in the index leaves the index but is never deleted.
			if ( 0 === strpos( $oldest, self::PREFIX ) ) {
				delete_option( $oldest );
			}
		}
		if ( get_option( self::INDEX ) !== $keep ) {
			update_option( self::INDEX, $keep, false );
		}
	}

	/**
	 * Write the settings option exactly as given, bypassing the sanitizer.
	 *
	 * The sanitizer is attached on admin_init (C-2.3.3) and would turn '0'
	 * switches on and drop unknown keys. It is detached for this single write.
	 *
	 * @param mixed $value The exact value to store.
	 * @return bool True when the stored value equals $value afterwards.
	 */
	public static function write_settings( $value ) {
		$filter   = 'sanitize_option_quick_adsense_settings';
		$callback = 'quick_adsense_sanitize_settings';
		$priority = has_filter( $filter, $callback );
		if ( false !== $priority ) {
			remove_filter( $filter, $callback, $priority );
		}
		try {
			update_option( 'quick_adsense_settings', $value );
		} finally {
			if ( false !== $priority ) {
				add_filter( $filter, $callback, $priority );
			}
		}
		delete_transient( 'quick_adsense_adstxt_adsense_autocheck_content' );

		// update_option() returns false for an unchanged value, so compare instead.
		return serialize( get_option( 'quick_adsense_settings' ) ) === serialize( $value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Byte comparison of stored values.
	}

	/**
	 * Restore a snapshot.
	 *
	 * No capability check here: the admin handler does that.
	 *
	 * @param string $name         Snapshot option name.
	 * @param bool   $with_widgets Also restore the widget placements.
	 * @return true|\WP_Error
	 */
	public static function restore( $name, $with_widgets ) {
		// Load first: the pre-restore snapshot may prune the very backup being restored.
		$payload = self::get( $name );
		if ( null === $payload || ! is_array( $payload['settings'] ) ) {
			return new \WP_Error( 'quick_adsense_backup_invalid', 'That backup cannot be restored.' );
		}

		if ( false === self::create( 'pre-restore' ) ) {
			return new \WP_Error( 'quick_adsense_backup_failed', 'The current settings could not be backed up, so nothing was restored.' );
		}

		if ( ! self::write_settings( $payload['settings'] ) ) {
			return new \WP_Error( 'quick_adsense_restore_failed', 'The settings could not be written.' );
		}

		$dismissed = $payload['notice_dismissed'] ?? false;
		if ( false !== $dismissed ) {
			// Never un-dismiss a notice.
			update_option( 'quick_adsense_adstxt_adsense_admin_notice_dismissed', $dismissed );
		}

		if ( $with_widgets ) {
			self::restore_widgets( $payload );
		}

		return true;
	}

	/**
	 * Read the list of snapshot names from the index.
	 *
	 * @return string[] Oldest first.
	 */
	private static function index() {
		$index = get_option( self::INDEX );
		if ( ! is_array( $index ) ) {
			return [];
		}
		$names = [];
		foreach ( $index as $name ) {
			if ( is_string( $name ) && 0 === strpos( $name, self::PREFIX ) ) {
				$names[] = $name;
			}
		}
		return $names;
	}

	/**
	 * Collect this plugin's placements from sidebars_widgets.
	 *
	 * @return array<string, array<int, string>> Sidebar id => widget ids, keyed by position.
	 */
	private static function collect_widgets() {
		$sidebars = get_option( 'sidebars_widgets' );
		$found    = [];
		if ( ! is_array( $sidebars ) ) {
			return $found;
		}
		foreach ( $sidebars as $sidebar => $ids ) {
			if ( 'array_version' === $sidebar || ! is_array( $ids ) ) {
				continue;
			}
			$ours = array_filter(
				$ids,
				static function ( $id ) {
					return is_string( $id ) && 1 === preg_match( self::WIDGET_ID_PATTERN, $id );
				}
			);
			if ( $ours ) {
				$found[ $sidebar ] = $ours;
			}
		}
		return $found;
	}

	/**
	 * Write widget instances and placements back from a payload.
	 *
	 * @param array $payload Snapshot payload.
	 */
	private static function restore_widgets( array $payload ) {
		$instances = isset( $payload['widget_instances'] ) && is_array( $payload['widget_instances'] ) ? $payload['widget_instances'] : [];
		foreach ( $instances as $option => $value ) {
			if ( is_string( $option ) && 1 === preg_match( '/^widget_adswidget([1-9]|10)-quick-adsense$/', $option ) ) {
				update_option( $option, $value );
			}
		}

		$sidebars = get_option( 'sidebars_widgets' );
		if ( ! is_array( $sidebars ) ) {
			$sidebars = [];
		}
		foreach ( $sidebars as $sidebar => $ids ) {
			if ( 'array_version' === $sidebar || ! is_array( $ids ) ) {
				continue;
			}
			$sidebars[ $sidebar ] = array_values(
				array_filter(
					$ids,
					static function ( $id ) {
						return ! ( is_string( $id ) && 1 === preg_match( self::WIDGET_ID_PATTERN, $id ) );
					}
				)
			);
		}

		$stored = isset( $payload['widgets'] ) && is_array( $payload['widgets'] ) ? $payload['widgets'] : [];
		foreach ( $stored as $sidebar => $placements ) {
			if ( ! is_array( $placements ) ) {
				continue;
			}
			if ( ! isset( $sidebars[ $sidebar ] ) || ! is_array( $sidebars[ $sidebar ] ) ) {
				// The sidebar no longer exists: park the widgets as inactive.
				$inactive                        = isset( $sidebars['wp_inactive_widgets'] ) && is_array( $sidebars['wp_inactive_widgets'] ) ? $sidebars['wp_inactive_widgets'] : [];
				$sidebars['wp_inactive_widgets'] = array_merge( $inactive, array_values( $placements ) );
				continue;
			}
			ksort( $placements, SORT_NUMERIC );
			foreach ( $placements as $position => $id ) {
				$position = max( 0, min( (int) $position, count( $sidebars[ $sidebar ] ) ) );
				array_splice( $sidebars[ $sidebar ], $position, 0, [ $id ] );
			}
		}

		update_option( 'sidebars_widgets', $sidebars );
	}
}
