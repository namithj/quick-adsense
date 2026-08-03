<?php
/**
 * Load the WordPress core test library with Quick AdSense active.
 */

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

if ( false === getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Required by wp-phpunit.
	putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );
}

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

require $quick_adsense_tests_dir . '/includes/bootstrap.php';
