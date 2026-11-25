<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quick_adsense_backups     = \QuickAdsense\Snapshots::all();
$quick_adsense_can_restore = current_user_can( 'unfiltered_html' );
$quick_adsense_messages    = [
	'created'  => [ 'notice-success', __( 'Backup created.', 'quick-adsense' ) ],
	'restored' => [ 'notice-success', __( 'Backup restored. A copy of your previous settings was saved first.', 'quick-adsense' ) ],
	'error'    => [ 'notice-error', __( 'That did not work. Nothing was changed.', 'quick-adsense' ) ],
	'denied'   => [ 'notice-error', __( 'You are not allowed to do that, or the page expired. Nothing was changed.', 'quick-adsense' ) ],
];
$quick_adsense_status      = isset( $_GET['qa_backup'] ) ? sanitize_key( wp_unslash( $_GET['qa_backup'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only message selector.
$quick_adsense_post_url    = admin_url( 'admin-post.php' );
$quick_adsense_date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
?>
<div class="quick_adsense_block" id="qa-backups">
	<div class="quick_adsense_block_labels"><?php esc_html_e( 'Backups', 'quick-adsense' ); ?></div>
	<div class="quick_adsense_block_controls">
		<?php if ( isset( $quick_adsense_messages[ $quick_adsense_status ] ) ) : ?>
			<div class="notice <?php echo esc_attr( $quick_adsense_messages[ $quick_adsense_status ][0] ); ?> inline"><p><?php echo esc_html( $quick_adsense_messages[ $quick_adsense_status ][1] ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Quick Adsense saves a copy of its settings before every plugin update and before every restore. The 5 most recent backups are kept.', 'quick-adsense' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'quick-adsense' ); ?></th>
					<th><?php esc_html_e( 'Reason', 'quick-adsense' ); ?></th>
					<th><?php esc_html_e( 'Version', 'quick-adsense' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'quick-adsense' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $quick_adsense_backups ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No backups yet.', 'quick-adsense' ); ?></td></tr>
				<?php endif; ?>
				<?php
				foreach ( $quick_adsense_backups as $quick_adsense_name => $quick_adsense_payload ) :
					$quick_adsense_reason  = isset( $quick_adsense_payload['reason'] ) && is_string( $quick_adsense_payload['reason'] ) ? $quick_adsense_payload['reason'] : '';
					$quick_adsense_label   = __( 'Manual', 'quick-adsense' );
					$quick_adsense_created = isset( $quick_adsense_payload['created_at'] ) && is_string( $quick_adsense_payload['created_at'] ) ? strtotime( $quick_adsense_payload['created_at'] . ' UTC' ) : false;
					if ( 0 === strpos( $quick_adsense_reason, 'upgrade-from-' ) ) {
						$quick_adsense_label = sprintf(
							/* translators: %s: plugin version. */
							__( 'Before upgrade from %s', 'quick-adsense' ),
							substr( $quick_adsense_reason, strlen( 'upgrade-from-' ) )
						);
					} elseif ( 0 === strpos( $quick_adsense_reason, 'pre-migration-' ) ) {
						$quick_adsense_label = sprintf(
							/* translators: %s: migration identifier. */
							__( 'Before migration %s', 'quick-adsense' ),
							substr( $quick_adsense_reason, strlen( 'pre-migration-' ) )
						);
					} elseif ( 'pre-restore' === $quick_adsense_reason ) {
						$quick_adsense_label = __( 'Before restore', 'quick-adsense' );
					} elseif ( 'pre-import' === $quick_adsense_reason ) {
						$quick_adsense_label = __( 'Before import', 'quick-adsense' );
					}
					?>
					<tr>
						<td><?php echo esc_html( false !== $quick_adsense_created ? wp_date( $quick_adsense_date_format, $quick_adsense_created ) : '' ); ?></td>
						<td><?php echo esc_html( $quick_adsense_label ); ?></td>
						<td><?php echo esc_html( isset( $quick_adsense_payload['plugin_version'] ) && is_scalar( $quick_adsense_payload['plugin_version'] ) ? (string) $quick_adsense_payload['plugin_version'] : '' ); ?></td>
						<td>
							<div class="qa-backup-actions">
								<form class="quick_adsense_backup_restore_form qa-backup-restore" method="post" action="<?php echo esc_url( $quick_adsense_post_url ); ?>">
									<input type="hidden" name="action" value="quick_adsense_backup_restore" />
									<input type="hidden" name="backup" value="<?php echo esc_attr( $quick_adsense_name ); ?>" />
									<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'quick-adsense-backup-restore_' . $quick_adsense_name ) ); ?>" />
									<button type="submit" class="qa-btn qa-btn--contained"<?php disabled( ! $quick_adsense_can_restore ); ?>><span class="dashicons dashicons-backup" aria-hidden="true"></span><?php esc_html_e( 'Restore', 'quick-adsense' ); ?></button>
									<label class="qa-backup-option"><input type="checkbox" name="restore_widgets" value="1"<?php disabled( ! $quick_adsense_can_restore ); ?> /> <?php esc_html_e( 'Also restore widget placements', 'quick-adsense' ); ?></label>
									<?php if ( ! $quick_adsense_can_restore ) : ?>
										<em class="qa-backup-option"><?php esc_html_e( 'Restoring needs the unfiltered_html capability, which your account does not have.', 'quick-adsense' ); ?></em>
									<?php endif; ?>
								</form>
								<form method="post" action="<?php echo esc_url( $quick_adsense_post_url ); ?>">
									<input type="hidden" name="action" value="quick_adsense_backup_download" />
									<input type="hidden" name="backup" value="<?php echo esc_attr( $quick_adsense_name ); ?>" />
									<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'quick-adsense-backup-download_' . $quick_adsense_name ) ); ?>" />
									<button type="submit" class="qa-btn qa-btn--outlined"><span class="dashicons dashicons-download" aria-hidden="true"></span><?php esc_html_e( 'Download JSON', 'quick-adsense' ); ?></button>
								</form>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<form method="post" action="<?php echo esc_url( $quick_adsense_post_url ); ?>" style="margin-top: 10px;">
			<input type="hidden" name="action" value="quick_adsense_backup_create" />
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'quick-adsense-backup-create' ) ); ?>" />
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Create backup now', 'quick-adsense' ); ?></button>
		</form>
	</div>
	<div class="clear"></div>
</div>
