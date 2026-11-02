<?php
/**
 * Bootstrap for the user-config regression group (R00).
 *
 * Same as bootstrap.php, plus the safeguards that keep the group away from the
 * developer's real site:
 *
 * 1. WP_TESTS_ABSPATH must be set explicitly. bin/test-user-configs.sh points
 *    it at a throwaway core in .cache/wp-core/. The default in
 *    wp-tests-config.php is the real WordPress root, whose wp-content would
 *    also load.
 * 2. The filesystem_method filter returns a method that does not exist, so
 *    QuickAdsense\FileHandler (which only acts on "direct") can never read or
 *    write a real ads.txt.
 * 3. A sentinel records ABSPATH . 'ads.txt' before anything runs; the test case
 *    fails the run if it changed.
 */

define( 'QA_USER_CONFIGS_BOOTSTRAP', true );

if ( false === getenv( 'WP_TESTS_ABSPATH' ) || '' === getenv( 'WP_TESTS_ABSPATH' ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- WordPress is not loaded yet.
	fwrite( STDERR, "Refusing to run: WP_TESTS_ABSPATH is not set.\nThe user-config group must run against a throwaway WordPress core, never the real site.\nUse bin/test-user-configs.sh (composer test:configs), or point WP_TESTS_ABSPATH at a core installed with bin/install-wp-core.sh.\n" );
	exit( 1 );
}

require_once __DIR__ . '/includes/class-qa-file-sentinel.php';

if ( false === getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Required by wp-phpunit.
	putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );
}

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$quick_adsense_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
if ( false === $quick_adsense_tests_dir ) {
	$quick_adsense_tests_dir = dirname( __DIR__, 2 ) . '/vendor/wp-phpunit/wp-phpunit';
}

require_once $quick_adsense_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__, 2 ) . '/quick-adsense.php';
	}
);

// Safeguard 2. To prove the guard works, comment this line out: the check at the
// end of this file then stops the run.
tests_add_filter(
	'filesystem_method',
	static function () {
		return 'qa-tests-disabled';
	},
	PHP_INT_MAX
);

require $quick_adsense_tests_dir . '/includes/bootstrap.php';

// The guard must be in force before a single test runs.
if ( defined( 'FS_METHOD' ) || 'direct' === apply_filters( 'filesystem_method', 'direct', [], '', false ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Bootstrap message.
	fwrite( STDERR, "Refusing to run: the filesystem_method guard is not active, so QuickAdsense\\FileHandler could write the real ads.txt.\n" );
	exit( 1 );
}

// Safeguard 3.
$GLOBALS['qa_adstxt_sentinel'] = [
	'path'  => ABSPATH . 'ads.txt',
	'state' => QA_File_Sentinel::capture( ABSPATH . 'ads.txt' ),
];
