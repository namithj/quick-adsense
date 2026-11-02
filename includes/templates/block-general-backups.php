<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quick_adsense_backups     = \QuickAdsense\Snapshots::all();
$quick_adsense_can_restore = current_user_can( 'unfiltered_html' );
$quick_adsense_messages    = [
	'created'  => [ 'notice-success', 'Backup created.' ],
	'restored' => [ 'notice-success', 'Backup restored. A copy of your previous settings was saved first.' ],
	'error'    => [ 'notice-error', 'That did not work. Nothing was changed.' ],
	'denied'   => [ 'notice-error', 'You are not allowed to do that, or the page expired. Nothing was changed.' ],
];
$quick_adsense_status      = isset( $_GET['qa_backup'] ) ? sanitize_key( wp_unslash( $_GET['qa_backup'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only message selector.
$quick_adsense_post_url    = admin_url( 'admin-post.php' );
$quick_adsense_date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
?>
<div class="quick_adsense_block">
	<div class="quick_adsense_block_labels">Backups</div>
	<div class="quick_adsense_block_controls">
		<?php if ( isset( $quick_adsense_messages[ $quick_adsense_status ] ) ) : ?>
			<div class="notice <?php echo esc_attr( $quick_adsense_messages[ $quick_adsense_status ][0] ); ?> inline"><p><?php echo esc_html( $quick_adsense_messages[ $quick_adsense_status ][1] ); ?></p></div>
		<?php endif; ?>
		<p>Quick Adsense saves a copy of its settings before every plugin update and before every restore. The 5 most recent backups are kept.</p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Date</th>
					<th>Reason</th>
					<th>Version</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $quick_adsense_backups ) : ?>
					<tr><td colspan="4">No backups yet.</td></tr>
				<?php endif; ?>
				<?php
				foreach ( $quick_adsense_backups as $quick_adsense_name => $quick_adsense_payload ) :
					$quick_adsense_reason  = isset( $quick_adsense_payload['reason'] ) && is_string( $quick_adsense_payload['reason'] ) ? $quick_adsense_payload['reason'] : '';
					$quick_adsense_label   = 'Manual';
					$quick_adsense_created = isset( $quick_adsense_payload['created_at'] ) && is_string( $quick_adsense_payload['created_at'] ) ? strtotime( $quick_adsense_payload['created_at'] . ' UTC' ) : false;
					if ( 0 === strpos( $quick_adsense_reason, 'upgrade-from-' ) ) {
						$quick_adsense_label = 'Before upgrade from ' . substr( $quick_adsense_reason, strlen( 'upgrade-from-' ) );
					} elseif ( 0 === strpos( $quick_adsense_reason, 'pre-migration-' ) ) {
						$quick_adsense_label = 'Before migration ' . substr( $quick_adsense_reason, strlen( 'pre-migration-' ) );
					} elseif ( 'pre-restore' === $quick_adsense_reason ) {
						$quick_adsense_label = 'Before restore';
					}
					?>
					<tr>
						<td><?php echo esc_html( false !== $quick_adsense_created ? wp_date( $quick_adsense_date_format, $quick_adsense_created ) : '' ); ?></td>
						<td><?php echo esc_html( $quick_adsense_label ); ?></td>
						<td><?php echo esc_html( isset( $quick_adsense_payload['plugin_version'] ) && is_scalar( $quick_adsense_payload['plugin_version'] ) ? (string) $quick_adsense_payload['plugin_version'] : '' ); ?></td>
						<td>
							<form class="quick_adsense_backup_restore_form" method="post" action="<?php echo esc_url( $quick_adsense_post_url ); ?>" style="display: inline-block; margin-right: 10px;">
								<input type="hidden" name="action" value="quick_adsense_backup_restore" />
								<input type="hidden" name="backup" value="<?php echo esc_attr( $quick_adsense_name ); ?>" />
								<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'quick-adsense-backup-restore_' . $quick_adsense_name ) ); ?>" />
								<label><input type="checkbox" name="restore_widgets" value="1" /> Also restore widget placements</label>
								<button type="submit" class="button"<?php disabled( ! $quick_adsense_can_restore ); ?>>Restore</button>
								<?php if ( ! $quick_adsense_can_restore ) : ?>
									<br /><em>Restoring needs the unfiltered_html capability, which your account does not have.</em>
								<?php endif; ?>
							</form>
							<form method="post" action="<?php echo esc_url( $quick_adsense_post_url ); ?>" style="display: inline-block;">
								<input type="hidden" name="action" value="quick_adsense_backup_download" />
								<input type="hidden" name="backup" value="<?php echo esc_attr( $quick_adsense_name ); ?>" />
								<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'quick-adsense-backup-download_' . $quick_adsense_name ) ); ?>" />
								<button type="submit" class="button">Download JSON</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<form method="post" action="<?php echo esc_url( $quick_adsense_post_url ); ?>" style="margin-top: 10px;">
			<input type="hidden" name="action" value="quick_adsense_backup_create" />
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'quick-adsense-backup-create' ) ); ?>" />
			<button type="submit" class="button button-primary">Create backup now</button>
		</form>
	</div>
	<div class="clear"></div>
</div>
