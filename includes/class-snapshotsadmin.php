<?php
namespace QuickAdsense;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Request handling for the Backups box: create, restore and download.
 *
 * The handle_*() methods return a result instead of redirecting or exiting, so
 * they can be tested directly. The thin *_request() callbacks registered on
 * admin_post_* read the request, call a handler and then redirect.
 */
class SnapshotsAdmin {
	/**
	 * Create a backup now.
	 *
	 * @param array $request Request data (nonce in _wpnonce).
	 * @return true|\WP_Error
	 */
	public static function handle_create( array $request ) {
		if ( ! self::nonce_ok( $request, 'quick-adsense-backup-create' ) ) {
			return new \WP_Error( 'quick_adsense_bad_nonce', 'The security check failed.' );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'quick_adsense_denied', 'You are not allowed to do that.' );
		}
		if ( false === Snapshots::create( 'manual' ) ) {
			return new \WP_Error( 'quick_adsense_backup_failed', 'The backup could not be saved.' );
		}
		return true;
	}

	/**
	 * Restore a backup.
	 *
	 * @param array $request Request data (backup, restore_widgets, _wpnonce).
	 * @return true|\WP_Error
	 */
	public static function handle_restore( array $request ) {
		$name = self::field( $request, 'backup' );
		if ( ! self::nonce_ok( $request, 'quick-adsense-backup-restore_' . $name ) ) {
			return new \WP_Error( 'quick_adsense_bad_nonce', 'The security check failed.' );
		}
		// Same gate as the ad-code fields (C-6.3): restore writes without the sanitizer.
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'unfiltered_html' ) ) {
			return new \WP_Error( 'quick_adsense_denied', 'You are not allowed to do that.' );
		}
		return Snapshots::restore( $name, '1' === self::field( $request, 'restore_widgets' ) );
	}

	/**
	 * Fetch a backup for download.
	 *
	 * @param array $request Request data (backup, _wpnonce).
	 * @return array|\WP_Error The payload with the backup name added under "backup".
	 */
	public static function handle_download( array $request ) {
		$name = self::field( $request, 'backup' );
		if ( ! self::nonce_ok( $request, 'quick-adsense-backup-download_' . $name ) ) {
			return new \WP_Error( 'quick_adsense_bad_nonce', 'The security check failed.' );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'quick_adsense_denied', 'You are not allowed to do that.' );
		}
		$payload = Snapshots::get( $name );
		if ( null === $payload ) {
			return new \WP_Error( 'quick_adsense_backup_invalid', 'That backup does not exist.' );
		}
		return array_merge( [ 'backup' => $name ], $payload );
	}

	/**
	 * admin-post.php callback: create a backup, then redirect.
	 */
	public static function create_request() {
		$result = self::handle_create( self::request_data() );
		self::redirect( self::status( $result, 'created' ) );
	}

	/**
	 * admin-post.php callback: restore a backup, then redirect.
	 */
	public static function restore_request() {
		$result = self::handle_restore( self::request_data() );
		self::redirect( self::status( $result, 'restored' ) );
	}

	/**
	 * admin-post.php callback: send a backup as a JSON file, or redirect on failure.
	 */
	public static function download_request() {
		$result = self::handle_download( self::request_data() );
		if ( is_wp_error( $result ) ) {
			self::redirect( self::status( $result, 'created' ) );
			return;
		}
		$file = sanitize_file_name( substr( $result['backup'], strlen( Snapshots::PREFIX ) ) );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="quick-adsense-' . $file . '.json"' );
		echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download, not HTML.
		exit;
	}

	/**
	 * Map a handler result to the status shown after the redirect.
	 *
	 * @param true|\WP_Error|array $result  Handler result.
	 * @param string               $success Status for success.
	 * @return string
	 */
	private static function status( $result, $success ) {
		if ( ! is_wp_error( $result ) ) {
			return $success;
		}
		return in_array( $result->get_error_code(), [ 'quick_adsense_denied', 'quick_adsense_bad_nonce' ], true ) ? 'denied' : 'error';
	}

	/**
	 * Redirect back to the settings page and stop.
	 *
	 * @param string $status One of created, restored, error, denied.
	 */
	private static function redirect( $status ) {
		wp_safe_redirect( add_query_arg( 'qa_backup', $status, admin_url( 'admin.php?page=quick-adsense' ) ) . '#tabs-backups' );
		exit;
	}

	/**
	 * The POST data for the thin callbacks.
	 *
	 * @return array
	 */
	private static function request_data() {
		return $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Every handler verifies its nonce before acting.
	}

	/**
	 * Read one text field from a request.
	 *
	 * @param array  $request Request data.
	 * @param string $key     Field name.
	 * @return string
	 */
	private static function field( array $request, $key ) {
		if ( ! isset( $request[ $key ] ) || ! is_scalar( $request[ $key ] ) ) {
			return '';
		}
		return sanitize_text_field( wp_unslash( (string) $request[ $key ] ) );
	}

	/**
	 * Verify the request nonce without dying.
	 *
	 * @param array  $request Request data.
	 * @param string $action  Nonce action.
	 * @return bool
	 */
	private static function nonce_ok( array $request, $action ) {
		$nonce = self::field( $request, '_wpnonce' );
		return '' !== $nonce && false !== wp_verify_nonce( $nonce, $action );
	}
}

add_action( 'admin_post_quick_adsense_backup_create', [ SnapshotsAdmin::class, 'create_request' ] );
add_action( 'admin_post_quick_adsense_backup_restore', [ SnapshotsAdmin::class, 'restore_request' ] );
add_action( 'admin_post_quick_adsense_backup_download', [ SnapshotsAdmin::class, 'download_request' ] );
