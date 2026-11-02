<?php
/**
 * Plugin Name: Quick Adsense – Ad Manager & AdSense Ads
 * Plugin URI: https://github.com/namithj/quick-adsense
 * Description: Free, lightweight ad manager: place AdSense or any ad code in posts, widgets, header & footer. No upsells.
 * Author: namithjawahar
 * Author URI: https://smartlogix.co.in/
 * Version: 2.10.0
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: quick-adsense
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * Kept in step with the plugin header and the readme Stable tag by
 * bin/set-version.sh, which the release pipeline runs from the pushed tag.
 * Used to cache-bust the plugin's own admin assets.
 */
define( 'QUICK_ADSENSE_VERSION', '2.10.0' );

require_once __DIR__ . '/includes/loader.php';
require_once __DIR__ . '/includes/countries.php';
require_once __DIR__ . '/includes/defaults.php';
require_once __DIR__ . '/includes/controls.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/widgets.php';
require_once __DIR__ . '/includes/quicktags.php';
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/adsense.php';
require_once __DIR__ . '/includes/class-filehandler.php';
require_once __DIR__ . '/includes/class-snapshots.php';
require_once __DIR__ . '/includes/class-snapshotsadmin.php';
require_once __DIR__ . '/includes/class-migrations.php';
if ( ! class_exists( 'Mobile_Detect' ) ) {
	require_once __DIR__ . '/includes/vendor/MobileDetect/Mobile_Detect.php';
}
if ( ! class_exists( 'iriven\\GeoIPCountry' ) ) {
	require_once __DIR__ . '/includes/vendor/GeoIP/GeoIPCountry.php';
}
