<?php
/**
 * Environment-driven WordPress PHPUnit configuration.
 */

/**
 * Read a test environment value.
 *
 * @param string $name Environment variable name.
 * @param string $fallback Default value.
 * @return string
 */
function quick_adsense_tests_env( $name, $fallback ) {
	$value = getenv( $name );
	return false === $value || '' === $value ? $fallback : $value;
}

$quick_adsense_tests_abspath = quick_adsense_tests_env( 'WP_TESTS_ABSPATH', '/var/www/html/public' );
define( 'ABSPATH', rtrim( $quick_adsense_tests_abspath, '/\\' ) . '/' );

if ( ! file_exists( ABSPATH . 'wp-settings.php' ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- WordPress is not loaded yet.
	fwrite( STDERR, 'No WordPress core found at ' . ABSPATH . PHP_EOL );
	exit( 1 );
}

define( 'DB_NAME', quick_adsense_tests_env( 'WP_TESTS_DB_NAME', 'quick_adsense_tests' ) );
define( 'DB_USER', quick_adsense_tests_env( 'WP_TESTS_DB_USER', 'root' ) );
define( 'DB_PASSWORD', quick_adsense_tests_env( 'WP_TESTS_DB_PASSWORD', 'root' ) );
define( 'DB_HOST', quick_adsense_tests_env( 'WP_TESTS_DB_HOST', 'db' ) );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required by WordPress's test bootstrap.
$table_prefix = 'qatests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Quick AdSense Test Suite' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WP_DEBUG', true );

define( 'AUTH_KEY', 'quick-adsense-tests' );
define( 'SECURE_AUTH_KEY', 'quick-adsense-tests' );
define( 'LOGGED_IN_KEY', 'quick-adsense-tests' );
define( 'NONCE_KEY', 'quick-adsense-tests' );
define( 'AUTH_SALT', 'quick-adsense-tests' );
define( 'SECURE_AUTH_SALT', 'quick-adsense-tests' );
define( 'LOGGED_IN_SALT', 'quick-adsense-tests' );
define( 'NONCE_SALT', 'quick-adsense-tests' );
