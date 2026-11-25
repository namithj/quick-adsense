<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h2 id="quick_adsense_title">
		<?php esc_html_e( 'Quick Adsense Settings', 'quick-adsense' ); ?>
		<span style="font-size: 14px;">
			<?php
			printf(
				/* translators: %s: plugin version number. */
				esc_html__( '(Version %s)', 'quick-adsense' ),
				esc_html( QUICK_ADSENSE_VERSION )
			);
			?>
		</span>
	</h2>
	<div id="quick_adsense_settings_tabs">
		<ul class="nav-tab-wrapper">
			<li><a class="nav-tab" href="#tabs-settings"><?php esc_html_e( 'Settings', 'quick-adsense' ); ?></a></li>
			<li><a class="nav-tab" href="#tabs-post-body-ads"><?php esc_html_e( 'Ads on Post Body', 'quick-adsense' ); ?></a></li>
			<li><a class="nav-tab" href="#tabs-sidebar-widget-ads"><?php esc_html_e( 'Sidebar Widget', 'quick-adsense' ); ?></a></li>
			<li><a class="nav-tab" href="#tabs-header-footer-codes"><?php esc_html_e( 'Header / Footer Codes', 'quick-adsense' ); ?></a></li>
		</ul>
		<div class="qa-nav-links">
			<a class="nav-tab" href="<?php echo esc_url( admin_url( 'admin.php?page=quick-adsense-tools#qa-backups' ) ); ?>"><?php esc_html_e( 'Backups', 'quick-adsense' ); ?></a>
		</div>
		<form id="quick_adsense_settings_form" method="post" action="options.php" name="wp_auto_commenter_form">
			<?php settings_fields( 'quick_adsense_settings' ); ?>
			<div id="tabs-settings">
				<div id="quick_adsense_top_sections_wrapper">
					<?php do_settings_sections( 'quick-adsense-general' ); ?>
				</div>
				<?php submit_button( __( 'Save Changes', 'quick-adsense' ) ); ?>
			</div>
			<div id="tabs-post-body-ads">
				<?php do_settings_sections( 'quick-adsense-onpost' ); ?>
				<?php submit_button( __( 'Save Changes', 'quick-adsense' ) ); ?>
			</div>
			<div id="tabs-sidebar-widget-ads">
				<?php do_settings_sections( 'quick-adsense-widgets' ); ?>
				<?php submit_button( __( 'Save Changes', 'quick-adsense' ) ); ?>
			</div>
			<div id="tabs-header-footer-codes">
				<?php do_settings_sections( 'quick-adsense-header-footer-codes' ); ?>
				<?php submit_button( __( 'Save Changes', 'quick-adsense' ) ); ?>
			</div>
		</form>
	</div>
</div>
