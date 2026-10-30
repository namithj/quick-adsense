<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h2 id="quick_adsense_title">Quick Adsense Settings <span style="font-size: 14px;">(Version <?php echo esc_html( QUICK_ADSENSE_VERSION ); ?>)</span></h2>
	<div id="quick_adsense_settings_tabs" style="display: none;">
		<ul>
			<li><a href="#tabs-settings">Settings</a></li>
			<li><a href="#tabs-post-body-ads">Ads on Post Body</a></li>
			<li><a href="#tabs-sidebar-widget-ads">Sidebar Widget</a></li>
			<li><a href="#tabs-header-footer-codes">Header / Footer Codes</a></li>
			<li><a href="#tabs-backups">Backups</a></li>
		</ul>
		<form id="quick_adsense_settings_form" method="post" action="options.php" name="wp_auto_commenter_form">
			<?php settings_fields( 'quick_adsense_settings' ); ?>
			<div id="tabs-settings">
				<div id="quick_adsense_top_sections_wrapper">
					<?php do_settings_sections( 'quick-adsense-general' ); ?>
				</div>
				<?php submit_button( 'Save Changes' ); ?>
			</div>
			<div id="tabs-post-body-ads">
				<?php do_settings_sections( 'quick-adsense-onpost' ); ?>
				<?php submit_button( 'Save Changes' ); ?>
			</div>
			<div id="tabs-sidebar-widget-ads">
				<?php do_settings_sections( 'quick-adsense-widgets' ); ?>
				<?php submit_button( 'Save Changes' ); ?>
			</div>
			<div id="tabs-header-footer-codes">
				<?php do_settings_sections( 'quick-adsense-header-footer-codes' ); ?>
				<?php submit_button( 'Save Changes' ); ?>
			</div>
		</form>
		<div id="tabs-backups">
			<?php
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			// The template escapes everything itself; forms and inputs are not in the allowed HTML list, so it is not passed through wp_kses.
			echo quick_adsense_load_file( 'templates/block-general-backups.php' );
			// phpcs:enable
			?>
		</div>
	</div>
</div>
