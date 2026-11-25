<?php
namespace QuickAdsense;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Tools admin page: backups, export and import.
 */
class Tools {
	/**
	 * Menu slug of the page.
	 */
	const SLUG = 'quick-adsense-tools';

	/**
	 * Hook suffix returned by add_submenu_page().
	 *
	 * @var string|false
	 */
	private static $hook = false;

	/**
	 * Register the submenu (the "Settings" first entry is added with the top-level menu).
	 */
	public static function register_menu() {
		self::$hook = add_submenu_page(
			'quick-adsense',
			__( 'Quick Adsense Tools', 'quick-adsense' ),
			__( 'Tools', 'quick-adsense' ),
			'manage_options',
			self::SLUG,
			[ self::class, 'render' ]
		);
	}

	/**
	 * Load the admin stylesheet on the Tools page only (no jQuery UI).
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public static function enqueue( $hook ) {
		if ( false !== self::$hook && self::$hook === $hook && current_user_can( 'manage_options' ) ) {
			wp_enqueue_style( 'quick-adsense-admin-styles', plugins_url( '../assets/css/admin.css', __FILE__ ), [], QUICK_ADSENSE_VERSION );
			// Ask before a restore overwrites the settings (admin.js, which did this on the old Backups tab, is not loaded here).
			wp_register_script( 'quick-adsense-tools', false, [], QUICK_ADSENSE_VERSION, true );
			wp_enqueue_script( 'quick-adsense-tools' );
			wp_add_inline_script(
				'quick-adsense-tools',
				'document.addEventListener("submit",function(e){var f=e.target;if(f.classList&&f.classList.contains("quick_adsense_backup_restore_form")&&!window.confirm(' . wp_json_encode( __( 'Restore this backup? Your current settings are backed up first.', 'quick-adsense' ) ) . ')){e.preventDefault();}});'
			);
		}
	}

	/**
	 * Render the page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		// The templates escape everything themselves; forms and inputs are not in the allowed HTML list, so they are not passed through wp_kses.
		echo quick_adsense_load_file( 'templates/page-tools.php' );
		// phpcs:enable
	}
}

add_action( 'admin_menu', [ Tools::class, 'register_menu' ], 20 );
add_action( 'admin_enqueue_scripts', [ Tools::class, 'enqueue' ] );
