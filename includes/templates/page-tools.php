<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quick_adsense_post_url = admin_url( 'admin-post.php' );
$quick_adsense_status   = isset( $_GET['qa_tools'] ) ? sanitize_key( wp_unslash( $_GET['qa_tools'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only message selector.
$quick_adsense_notices  = [
	'applied'   => [ 'success', __( 'Import finished. A backup of your previous settings was saved first.', 'quick-adsense' ) ],
	'discarded' => [ 'info', __( 'The import was discarded. Nothing was changed.', 'quick-adsense' ) ],
	'format'    => [ 'error', __( 'That file is not a Quick Adsense export (.json). Nothing was changed.', 'quick-adsense' ) ],
	'version'   => [ 'error', __( 'That export file has a version this plugin cannot read. Nothing was changed.', 'quick-adsense' ) ],
	'size'      => [ 'error', __( 'The file is empty or larger than 5 MB. Nothing was changed.', 'quick-adsense' ) ],
	'upload'    => [ 'error', __( 'The file could not be uploaded. Nothing was changed.', 'quick-adsense' ) ],
	'expired'   => [ 'error', __( 'The uploaded file is no longer available. Upload it again. Nothing was changed.', 'quick-adsense' ) ],
	'error'     => [ 'error', __( 'That did not work. Nothing was changed.', 'quick-adsense' ) ],
	'denied'    => [ 'error', __( 'You are not allowed to do that, or the page expired. Nothing was changed.', 'quick-adsense' ) ],
];
$quick_adsense_pending  = \QuickAdsense\ExportImport::pending();
$quick_adsense_current  = get_option( 'quick_adsense_settings' );
$quick_adsense_current  = is_array( $quick_adsense_current ) ? $quick_adsense_current : [];
?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Quick Adsense Tools', 'quick-adsense' ); ?></h1>
	<?php
	if ( isset( $quick_adsense_notices[ $quick_adsense_status ] ) ) {
		wp_admin_notice(
			$quick_adsense_notices[ $quick_adsense_status ][1],
			[
				'type'        => $quick_adsense_notices[ $quick_adsense_status ][0],
				'dismissible' => false,
			]
		);
	}
	?>

	<div class="quick_adsense_block" id="qa-export">
		<div class="quick_adsense_block_labels"><?php echo esc_html__( 'Export', 'quick-adsense' ); ?></div>
		<div class="quick_adsense_block_controls">
			<p><?php echo esc_html__( 'Download your settings, ad code and widget placements as one JSON file.', 'quick-adsense' ); ?></p>
			<form method="post" action="<?php echo esc_url( $quick_adsense_post_url ); ?>">
				<input type="hidden" name="action" value="quick_adsense_export" />
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'quick-adsense-export' ) ); ?>" />
				<p><label><input type="checkbox" name="include_stats" value="1" /> <?php echo esc_html__( 'Include ad statistics', 'quick-adsense' ); ?></label></p>
				<p class="description"><?php echo esc_html__( 'Statistics included in an export are for your records only. Importing a file does not restore them.', 'quick-adsense' ); ?></p>
				<button type="submit" class="button button-primary"><?php echo esc_html__( 'Download export file', 'quick-adsense' ); ?></button>
			</form>
		</div>
		<div class="clear"></div>
	</div>

	<div class="quick_adsense_block" id="qa-import">
		<div class="quick_adsense_block_labels"><?php echo esc_html__( 'Import', 'quick-adsense' ); ?></div>
		<div class="quick_adsense_block_controls">
			<p><?php echo esc_html__( 'Upload a file exported from Quick Adsense. You will see what would change before anything is saved, and a backup is taken first.', 'quick-adsense' ); ?></p>
			<form method="post" action="<?php echo esc_url( $quick_adsense_post_url ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="quick_adsense_import_preview" />
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'quick-adsense-import-preview' ) ); ?>" />
				<p><input type="file" name="import_file" accept=".json,application/json" required /></p>
				<button type="submit" class="button"><?php echo esc_html__( 'Upload and review', 'quick-adsense' ); ?></button>
			</form>

			<?php
			if ( null !== $quick_adsense_pending ) :
				$quick_adsense_diff   = \QuickAdsense\ExportImport::diff( $quick_adsense_current, $quick_adsense_pending['settings'] );
				$quick_adsense_report = $quick_adsense_pending['report'];
				?>
				<h3><?php echo esc_html__( 'Review before importing', 'quick-adsense' ); ?></h3>
				<?php if ( $quick_adsense_report ) : ?>
					<h4><?php echo esc_html__( 'Report', 'quick-adsense' ); ?></h4>
					<ul class="ul-disc">
						<?php foreach ( $quick_adsense_report as $quick_adsense_item ) : ?>
							<li>
								<code><?php echo esc_html( $quick_adsense_item['key'] ); ?></code>:
								<?php
								switch ( $quick_adsense_item['type'] ) {
									case 'unknown':
										echo esc_html__( 'unknown setting, not imported.', 'quick-adsense' );
										break;
									case 'dropped':
										echo esc_html__( 'invalid value, not imported.', 'quick-adsense' );
										break;
									case 'countries':
										echo esc_html__( 'invalid country codes removed.', 'quick-adsense' );
										break;
									case 'code_kept':
										echo esc_html__( 'ad code kept as it is, because your account may not change ad code (needs the unfiltered_html capability).', 'quick-adsense' );
										break;
									default:
										/* translators: 1: original value, 2: value that will be saved. */
										echo esc_html( sprintf( __( 'value "%1$s" corrected to "%2$s".', 'quick-adsense' ), $quick_adsense_item['from'] ?? '', $quick_adsense_item['to'] ?? '' ) );
								}
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<h4><?php echo esc_html__( 'Changes', 'quick-adsense' ); ?></h4>
				<?php if ( ! $quick_adsense_diff ) : ?>
					<p><?php echo esc_html__( 'The settings in the file are the same as your current settings.', 'quick-adsense' ); ?></p>
				<?php else : ?>
					<table class="widefat striped" id="qa-import-diff">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Setting', 'quick-adsense' ); ?></th>
								<th><?php echo esc_html__( 'Now', 'quick-adsense' ); ?></th>
								<th><?php echo esc_html__( 'After import', 'quick-adsense' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $quick_adsense_diff as $quick_adsense_change ) : ?>
								<tr>
									<td><code><?php echo esc_html( $quick_adsense_change['key'] ); ?></code></td>
									<?php if ( $quick_adsense_change['code'] ) : ?>
										<td colspan="2"><?php echo esc_html__( 'ad code changed', 'quick-adsense' ); ?></td>
									<?php else : ?>
										<td><?php echo 'added' === $quick_adsense_change['status'] ? '&mdash;' : esc_html( $quick_adsense_change['old'] ); ?></td>
										<td><?php echo 'removed' === $quick_adsense_change['status'] ? '&mdash;' : esc_html( $quick_adsense_change['new'] ); ?></td>
									<?php endif; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( $quick_adsense_post_url ); ?>" style="margin-top: 10px;">
					<input type="hidden" name="action" value="quick_adsense_import_apply" />
					<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'quick-adsense-import-apply' ) ); ?>" />
					<?php if ( is_array( $quick_adsense_pending['widgets'] ) ) : ?>
						<p><label><input type="checkbox" name="import_widgets" value="1" /> <?php echo esc_html__( 'Also restore widget placements', 'quick-adsense' ); ?></label></p>
					<?php endif; ?>
					<button type="submit" class="button button-primary"><?php echo esc_html__( 'Import these settings', 'quick-adsense' ); ?></button>
					<button type="submit" class="button" name="discard" value="1"><?php echo esc_html__( 'Discard', 'quick-adsense' ); ?></button>
				</form>
			<?php endif; ?>
		</div>
		<div class="clear"></div>
	</div>

	<?php
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	// The template escapes everything itself.
	echo quick_adsense_load_file( 'templates/block-general-backups.php' );
	// phpcs:enable
	?>
</div>
