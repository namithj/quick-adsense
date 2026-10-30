<?php
namespace QuickAdsense;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema version tracking and the numbered migration runner.
 *
 * Runs on init at priority 20, after the legacy v1/v2 migration closure in
 * defaults.php (priority 10). The probe at priority 1 records whether a
 * settings array existed before that closure ran, which is how an upgrade is
 * told apart from a new install or a v1/v2 migration.
 *
 * Both options below are stored with autoload "no". That costs one query per
 * request, but it keeps the options out of the autoloaded set, as the release
 * spec requires.
 */
class Migrations {
	/**
	 * Current schema version. 1 means "2.x legacy schema verified".
	 */
	const DB_VERSION = 1;

	/**
	 * Option that holds the schema version.
	 */
	const OPTION_DB_VERSION = 'quick_adsense_db_version';

	/**
	 * Option that holds the last plugin version seen.
	 */
	const OPTION_PLUGIN_VERSION = 'quick_adsense_plugin_version';

	/**
	 * Schema version => migration method. Every method must be idempotent.
	 */
	const MIGRATIONS = [
		1 => 'migration_1',
	];

	/**
	 * Migrations that rewrite nothing and so need no pre-migration snapshot.
	 */
	const NO_SNAPSHOT = [ 1 ];

	/**
	 * Whether a settings array existed before the legacy migration, this request.
	 *
	 * @var bool|null
	 */
	private static $had_settings = null;

	/**
	 * Record whether quick_adsense_settings is an array right now.
	 */
	public static function probe() {
		self::$had_settings = is_array( get_option( 'quick_adsense_settings' ) );
	}

	/**
	 * Take the upgrade snapshot, record the version and run pending migrations.
	 */
	public static function run() {
		// Consume the probe result; no probe counts as "no prior settings".
		$had                = ( true === self::$had_settings );
		self::$had_settings = null;

		$stored = get_option( self::OPTION_PLUGIN_VERSION );
		if ( QUICK_ADSENSE_VERSION !== $stored ) {
			if ( $had ) {
				$from = is_string( $stored ) && '' !== $stored ? $stored : 'pre-2.10';
				if ( false === Snapshots::create( 'upgrade-from-' . $from ) ) {
					// Retry on the next request; do not record the version.
					return;
				}
			}
			update_option( self::OPTION_PLUGIN_VERSION, QUICK_ADSENSE_VERSION, false );
		}

		$current = (int) get_option( self::OPTION_DB_VERSION, 0 );
		$pending = array_keys( self::MIGRATIONS );
		sort( $pending, SORT_NUMERIC );
		foreach ( $pending as $version ) {
			if ( $version <= $current ) {
				continue;
			}
			if ( ! in_array( $version, self::NO_SNAPSHOT, true ) && false === Snapshots::create( 'pre-migration-' . $version ) ) {
				return;
			}
			call_user_func( [ self::class, self::MIGRATIONS[ $version ] ] );
			update_option( self::OPTION_DB_VERSION, $version, false );
		}
	}

	/**
	 * Migration 1: 2.x legacy schema verified; no data transformation.
	 *
	 * Idempotent: it changes nothing, so running it again is harmless.
	 */
	public static function migration_1() {
	}
}

add_action( 'init', [ Migrations::class, 'probe' ], 1 );
add_action( 'init', [ Migrations::class, 'run' ], 20 );
