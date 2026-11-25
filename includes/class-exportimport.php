<?php
namespace QuickAdsense;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export and import of the plugin settings (Tools page).
 *
 * The handle_*() methods return a result instead of redirecting or exiting, so
 * they can be tested directly. The thin *_request() callbacks registered on
 * admin_post_* read the request, call a handler and then redirect.
 *
 * Import never goes through quick_adsense_sanitize_settings(): it validates with
 * quick_adsense_import_validate() and writes with Snapshots::write_settings().
 */
class ExportImport {
	/**
	 * Value of "format" in an export file.
	 */
	const FORMAT = 'quick-adsense-export';

	/**
	 * Supported value of "format_version".
	 */
	const FORMAT_VERSION = 1;

	/**
	 * Largest accepted upload, in bytes.
	 */
	const MAX_BYTES = 5 * MB_IN_BYTES;

	/**
	 * Minutes the parsed upload waits for confirmation.
	 */
	const PENDING_MINUTES = 15;

	/**
	 * Build the export as an array ready for JSON encoding.
	 *
	 * @param bool $include_stats Include the per-slot statistics options.
	 * @return array
	 */
	public static function build_export( $include_stats ) {
		$settings = get_option( 'quick_adsense_settings' );
		$settings = is_array( $settings ) ? $settings : [];
		$code     = quick_adsense_get_ad_code_fields();
		foreach ( $code as $field ) {
			if ( isset( $settings[ $field ] ) && is_string( $settings[ $field ] ) && ! self::is_utf8( $settings[ $field ] ) ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Lossless transport of non-UTF-8 ad code, not obfuscation.
				$settings[ $field ] = [ '__qa_b64' => base64_encode( $settings[ $field ] ) ];
			}
		}

		$export = [
			'format'         => self::FORMAT,
			'format_version' => self::FORMAT_VERSION,
			'plugin_version' => QUICK_ADSENSE_VERSION,
			'exported_at'    => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'site_url'       => site_url(),
			'settings'       => $settings,
			'widgets'        => [
				'widgets'          => Snapshots::collect_widgets(),
				'widget_instances' => Snapshots::collect_instances(),
			],
			'include_stats'  => (bool) $include_stats,
		];

		if ( $include_stats ) {
			$stats = [];
			for ( $i = 1; $i <= 10; $i++ ) {
				$value = get_option( 'quick_adsense_onpost_ad_' . $i . '_stats' );
				if ( false !== $value ) {
					$stats[ (string) $i ] = $value;
				}
			}
			$export['stats'] = $stats;
		}

		return $export;
	}

	/**
	 * Encode an export array as JSON text.
	 *
	 * @param array $export Export array.
	 * @return string|\WP_Error
	 */
	public static function encode( array $export ) {
		$json = wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( ! is_string( $json ) ) {
			return new \WP_Error( 'quick_adsense_export_failed', __( 'The settings could not be encoded as JSON.', 'quick-adsense' ) );
		}
		return $json;
	}

	/**
	 * Export handler.
	 *
	 * @param array $request Request data (include_stats, _wpnonce).
	 * @return array|\WP_Error Array with "filename" and "body".
	 */
	public static function handle_export( array $request ) {
		if ( ! self::nonce_ok( $request, 'quick-adsense-export' ) ) {
			return new \WP_Error( 'quick_adsense_bad_nonce', __( 'The security check failed.', 'quick-adsense' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'quick_adsense_denied', __( 'You are not allowed to do that.', 'quick-adsense' ) );
		}
		$body = self::encode( self::build_export( '1' === self::field( $request, 'include_stats' ) ) );
		if ( is_wp_error( $body ) ) {
			return $body;
		}
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$host = is_string( $host ) && '' !== $host ? $host : 'site';
		return [
			'filename' => sanitize_file_name( 'quick-adsense-export-' . $host . '-' . gmdate( 'Ymd-His' ) . '.json' ),
			'body'     => $body,
		];
	}

	/**
	 * Parse and check an export file.
	 *
	 * @param string $json File contents.
	 * @return array|\WP_Error Array with "settings", "widgets" (array|null), "include_stats".
	 */
	public static function parse( $json ) {
		$data = json_decode( (string) $json, true, 512 );
		if ( ! is_array( $data ) || ( $data['format'] ?? null ) !== self::FORMAT ) {
			return new \WP_Error( 'quick_adsense_import_format', __( 'This is not a Quick Adsense export file.', 'quick-adsense' ) );
		}
		if ( ( $data['format_version'] ?? null ) !== self::FORMAT_VERSION ) {
			return new \WP_Error( 'quick_adsense_import_version', __( 'This export file has a version this plugin cannot read.', 'quick-adsense' ) );
		}
		if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
			return new \WP_Error( 'quick_adsense_import_format', __( 'This is not a Quick Adsense export file.', 'quick-adsense' ) );
		}

		$settings = $data['settings'];
		$code     = quick_adsense_get_ad_code_fields();
		foreach ( $settings as $field => $value ) {
			if ( ! is_array( $value ) || array_keys( $value ) !== [ '__qa_b64' ] ) {
				continue;
			}
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Lossless transport of non-UTF-8 ad code, not obfuscation.
			$decoded = in_array( $field, $code, true ) && is_string( $value['__qa_b64'] ) ? base64_decode( $value['__qa_b64'], true ) : false;
			if ( false === $decoded ) {
				return new \WP_Error( 'quick_adsense_import_format', __( 'This is not a Quick Adsense export file.', 'quick-adsense' ) );
			}
			$settings[ $field ] = $decoded;
		}

		$widgets = null;
		if ( isset( $data['widgets'] ) && is_array( $data['widgets'] ) ) {
			// Only this plugin's own widget ids may be placed (same pattern the snapshots use).
			$widgets = [
				'widgets'          => [],
				'widget_instances' => isset( $data['widgets']['widget_instances'] ) && is_array( $data['widgets']['widget_instances'] ) ? $data['widgets']['widget_instances'] : [],
			];
			$placed  = isset( $data['widgets']['widgets'] ) && is_array( $data['widgets']['widgets'] ) ? $data['widgets']['widgets'] : [];
			foreach ( $placed as $sidebar => $ids ) {
				if ( ! is_string( $sidebar ) || ! is_array( $ids ) ) {
					continue;
				}
				$ids = array_filter(
					$ids,
					static function ( $id ) {
						return is_string( $id ) && 1 === preg_match( Snapshots::WIDGET_ID_PATTERN, $id );
					}
				);
				if ( $ids ) {
					$widgets['widgets'][ $sidebar ] = $ids;
				}
			}
		}

		return [
			'settings'      => $settings,
			'widgets'       => $widgets,
			'include_stats' => ! empty( $data['include_stats'] ),
		];
	}

	/**
	 * Compare the current settings with the validated import.
	 *
	 * @param array $current  Current settings.
	 * @param array $incoming Validated settings.
	 * @return array[] Entries with "key", "status" (added, removed, changed), "code" (bool), "old", "new".
	 */
	public static function diff( array $current, array $incoming ) {
		$code    = quick_adsense_get_ad_code_fields();
		$changes = [];
		$keys    = array_unique( array_merge( array_keys( $current ), array_keys( $incoming ) ) );
		sort( $keys, SORT_STRING );
		foreach ( $keys as $key ) {
			$has_old = array_key_exists( $key, $current );
			$has_new = array_key_exists( $key, $incoming );
			if ( $has_old && $has_new && serialize( $current[ $key ] ) === serialize( $incoming[ $key ] ) ) { // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Byte comparison.
				continue;
			}
			$changes[] = [
				'key'    => (string) $key,
				'status' => ! $has_old ? 'added' : ( ! $has_new ? 'removed' : 'changed' ),
				'code'   => in_array( $key, $code, true ),
				'old'    => $has_old ? self::display( $current[ $key ] ) : '',
				'new'    => $has_new ? self::display( $incoming[ $key ] ) : '',
			];
		}
		return $changes;
	}

	/**
	 * Read an uploaded file into a string, with the upload checks.
	 *
	 * @param mixed $file One entry of $_FILES.
	 * @return string|\WP_Error
	 */
	public static function read_upload( $file ) {
		if ( ! is_array( $file ) || ! isset( $file['tmp_name'], $file['error'], $file['size'], $file['name'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new \WP_Error( 'quick_adsense_import_upload', __( 'The file could not be uploaded.', 'quick-adsense' ) );
		}
		if ( ! is_string( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new \WP_Error( 'quick_adsense_import_upload', __( 'The file could not be uploaded.', 'quick-adsense' ) );
		}
		if ( (int) $file['size'] > self::MAX_BYTES || (int) $file['size'] < 1 ) {
			return new \WP_Error( 'quick_adsense_import_size', __( 'The file is empty or larger than 5 MB.', 'quick-adsense' ) );
		}
		if ( 'json' !== strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) ) ) {
			return new \WP_Error( 'quick_adsense_import_format', __( 'Only .json export files can be imported.', 'quick-adsense' ) );
		}
		$contents = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local uploaded file.
		if ( ! is_string( $contents ) ) {
			return new \WP_Error( 'quick_adsense_import_upload', __( 'The file could not be uploaded.', 'quick-adsense' ) );
		}
		return $contents;
	}

	/**
	 * Step 1: validate an uploaded file and keep the result for confirmation.
	 *
	 * @param array  $request Request data (_wpnonce).
	 * @param string $json    File contents.
	 * @return true|\WP_Error
	 */
	public static function handle_preview( array $request, $json ) {
		if ( ! self::nonce_ok( $request, 'quick-adsense-import-preview' ) ) {
			return new \WP_Error( 'quick_adsense_bad_nonce', __( 'The security check failed.', 'quick-adsense' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'quick_adsense_denied', __( 'You are not allowed to do that.', 'quick-adsense' ) );
		}
		$parsed = self::parse( $json );
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}
		$result = quick_adsense_import_validate( $parsed['settings'] );
		set_transient(
			self::pending_key(),
			[
				'settings' => $result['settings'],
				'report'   => $result['report'],
				'widgets'  => $parsed['widgets'],
			],
			self::PENDING_MINUTES * MINUTE_IN_SECONDS
		);
		return true;
	}

	/**
	 * The parsed upload waiting for confirmation, if any.
	 *
	 * @return array|null
	 */
	public static function pending() {
		$pending = get_transient( self::pending_key() );
		return is_array( $pending ) && isset( $pending['settings'] ) ? $pending : null;
	}

	/**
	 * Step 2: take the pre-import snapshot and write the validated settings.
	 *
	 * @param array $request Request data (import_widgets, discard, _wpnonce).
	 * @return string|\WP_Error "applied" or "discarded".
	 */
	public static function handle_apply( array $request ) {
		if ( ! self::nonce_ok( $request, 'quick-adsense-import-apply' ) ) {
			return new \WP_Error( 'quick_adsense_bad_nonce', __( 'The security check failed.', 'quick-adsense' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'quick_adsense_denied', __( 'You are not allowed to do that.', 'quick-adsense' ) );
		}
		$pending = self::pending();
		if ( null === $pending ) {
			return new \WP_Error( 'quick_adsense_import_expired', __( 'The uploaded file is no longer available. Upload it again.', 'quick-adsense' ) );
		}
		delete_transient( self::pending_key() );
		if ( '1' === self::field( $request, 'discard' ) ) {
			return 'discarded';
		}

		if ( false === Snapshots::create( 'pre-import' ) ) {
			return new \WP_Error( 'quick_adsense_backup_failed', __( 'The current settings could not be backed up, so nothing was imported.', 'quick-adsense' ) );
		}
		if ( ! Snapshots::write_settings( $pending['settings'] ) ) {
			return new \WP_Error( 'quick_adsense_import_failed', __( 'The settings could not be written.', 'quick-adsense' ) );
		}
		if ( '1' === self::field( $request, 'import_widgets' ) && is_array( $pending['widgets'] ) ) {
			Snapshots::restore_widgets( $pending['widgets'] );
		}
		return 'applied';
	}

	/**
	 * Map an error to the status shown after the redirect.
	 *
	 * @param \WP_Error $error Error.
	 * @return string
	 */
	public static function error_status( \WP_Error $error ) {
		switch ( $error->get_error_code() ) {
			case 'quick_adsense_denied':
			case 'quick_adsense_bad_nonce':
				return 'denied';
			case 'quick_adsense_import_format':
			case 'quick_adsense_import_version':
			case 'quick_adsense_import_size':
			case 'quick_adsense_import_upload':
			case 'quick_adsense_import_expired':
				return substr( $error->get_error_code(), strlen( 'quick_adsense_import_' ) );
			default:
				return 'error';
		}
	}

	/**
	 * admin-post.php callback: send the export file.
	 */
	public static function export_request() {
		$result = self::handle_export( self::request_data() );
		if ( is_wp_error( $result ) ) {
			self::redirect( self::error_status( $result ) );
			return;
		}
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $result['filename'] . '"' );
		echo $result['body']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download, not HTML.
		exit;
	}

	/**
	 * admin-post.php callback: upload step of the import.
	 */
	public static function preview_request() {
		$data = self::request_data();
		if ( ! self::nonce_ok( $data, 'quick-adsense-import-preview' ) || ! current_user_can( 'manage_options' ) ) {
			self::redirect( 'denied' );
			return;
		}
		$json = self::read_upload( $_FILES['import_file'] ?? null ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Nonce checked above; the upload is validated in read_upload().
		if ( is_wp_error( $json ) ) {
			self::redirect( self::error_status( $json ) );
			return;
		}
		$result = self::handle_preview( $data, $json );
		self::redirect( is_wp_error( $result ) ? self::error_status( $result ) : 'preview' );
	}

	/**
	 * admin-post.php callback: confirm or discard the import.
	 */
	public static function apply_request() {
		$result = self::handle_apply( self::request_data() );
		self::redirect( is_wp_error( $result ) ? self::error_status( $result ) : $result );
	}

	/**
	 * Redirect to the Tools page and stop.
	 *
	 * @param string $status Status key shown as a notice.
	 */
	private static function redirect( $status ) {
		wp_safe_redirect( add_query_arg( 'qa_tools', $status, admin_url( 'admin.php?page=quick-adsense-tools' ) ) );
		exit;
	}

	/**
	 * Transient name of the current user's pending import.
	 *
	 * @return string
	 */
	private static function pending_key() {
		return 'quick_adsense_import_' . get_current_user_id();
	}

	/**
	 * Short text for a value in the diff.
	 *
	 * @param mixed $value Stored value.
	 * @return string
	 */
	private static function display( $value ) {
		if ( is_array( $value ) ) {
			return implode( ', ', array_map( 'strval', array_filter( $value, 'is_scalar' ) ) );
		}
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Is the string valid UTF-8?
	 *
	 * @param string $value Text.
	 * @return bool
	 */
	private static function is_utf8( $value ) {
		return 1 === preg_match( '//u', $value );
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

add_action( 'admin_post_quick_adsense_export', [ ExportImport::class, 'export_request' ] );
add_action( 'admin_post_quick_adsense_import_preview', [ ExportImport::class, 'preview_request' ] );
add_action( 'admin_post_quick_adsense_import_apply', [ ExportImport::class, 'apply_request' ] );
