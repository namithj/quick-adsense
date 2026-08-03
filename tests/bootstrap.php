<?php
/**
 * Minimal WordPress environment for Quick AdSense characterization tests.
 *
 * This intentionally executes the plugin's own rendering code without a
 * database or a complete WordPress bootstrap. Minimal output helpers are deliberately used; this suite characterizes plugin flow and payload fidelity, not WordPress core sanitization.
 */

define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' );

$GLOBALS['quick_adsense_test_actions']  = [];
$GLOBALS['quick_adsense_test_filters']  = [];
$GLOBALS['quick_adsense_test_options']  = [];
$GLOBALS['quick_adsense_test_content']  = '';

function add_action( $hook, $callback ) {
	$GLOBALS['quick_adsense_test_actions'][ $hook ][] = $callback;
}

function add_filter( $hook, $callback ) {
	$GLOBALS['quick_adsense_test_filters'][ $hook ][] = $callback;
}

function apply_filters( $hook, $value ) {
	return $value;
}

function did_action( $hook ) {
	return 0;
}

function wp_allowed_protocols() {
	return [ 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'ircs', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn' ];
}

function get_option( $option, $default = false ) {
	return $GLOBALS['quick_adsense_test_options'][ $option ] ?? $default;
}

function update_option( $option, $value ) {
	$GLOBALS['quick_adsense_test_options'][ $option ] = $value;
	return true;
}

function get_the_content() {
	return $GLOBALS['quick_adsense_test_content'];
}

function get_the_ID() {
	return 1;
}

function get_bloginfo( $show ) {
	return 'url' === $show ? 'https://example.test' : '';
}

function is_active_widget() {
	return false;
}

function is_feed() {
	return false;
}

function is_single() {
	return true;
}

function is_page() {
	return false;
}

function is_home() {
	return false;
}

function is_category() {
	return false;
}

function is_archive() {
	return false;
}

function is_tag() {
	return false;
}

function is_user_logged_in() {
	return false;
}

class WP_Widget { public function __construct() {} }
function esc_attr( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function esc_html( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function esc_textarea( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function checked( $checked, $current = true, $display = true ) {
	$result = $checked == $current ? 'checked="checked"' : '';
	if ( $display ) {
		echo $result;
	}
	return $result;
}

function wp_kses( $content, $allowed_html, $allowed_protocols = [] ) {
	return $content;
}

function sanitize_title( $title ) {
	return strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $title ) );
}
require_once dirname( __DIR__ ) . '/includes/controls.php';
require_once dirname( __DIR__ ) . '/includes/loader.php';
class Mobile_Detect {
	public function isMobile() { return false; }
	public function isTablet() { return false; }
	public function is( $key ) { return false; }
	public function match( $regex ) { return false; }
}

require_once dirname( __DIR__ ) . '/includes/content.php';
require_once dirname( __DIR__ ) . '/includes/widgets.php';
