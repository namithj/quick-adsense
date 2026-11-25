<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="notice notice-error quick_adsense_adstxt_adsense_notice is-dismissible" style="padding: 15px;">
	<p>
		<?php
		echo wp_kses(
			__( '<b>Quick Adsense</b> had detected that your ads.txt file does not have all your Google Adsense Publisher IDs.<br />This will severely impact your adsense earnings and your immediate attention is required.', 'quick-adsense' ),
			[
				'b'  => [],
				'br' => [],
			]
		);
		?>
	</p>
	<?php
	if ( ( ! quick_adsense_get_value( $quick_adsense_template_args, 'is_ajax' ) ) && ( 'toplevel_page_quick-adsense' !== quick_adsense_get_value( $quick_adsense_template_args, 'screen_id' ) ) ) {
		$quick_adsense_click_here = '<a href="' . esc_url( admin_url( '/admin.php?page=quick-adsense#quick_adsense_adstxt_adsense_auto_update' ) ) . '">' . esc_html__( 'CLICK HERE', 'quick-adsense' ) . '</a>';
	} else {
		$quick_adsense_click_here = '<a href="#" onclick="quick_adsense_adstxt_adsense_auto_update()">' . esc_html__( 'CLICK HERE', 'quick-adsense' ) . '</a>';
	}
	?>
	<p>
		<?php
		echo wp_kses(
			sprintf(
				/* translators: %s: link with the text "CLICK HERE". */
				__( 'Your recommended google entries for ads.txt is as given below.<br />You can manually copy this to your ads.txt file or %s to instruct Quick Adsense to try and add the entries automatically.', 'quick-adsense' ),
				$quick_adsense_click_here
			),
			[
				'br' => [],
				'a'  => [
					'href'    => [],
					'onclick' => [],
				],
			]
		);
		?>
	</p>
	<p><code style="display: block; padding: 2px 10px;"><?php echo esc_html( quick_adsense_get_value( $quick_adsense_template_args, 'adstxt_new_adsense_entries' ) ); ?></code></p>
	<p><small><i><b><?php esc_html_e( 'We recommend you not to dismiss this notice for continued daily ads.txt monitoring.  This notice will stop appearing automatically once Quick Adsense detects correct entries in ads.txt (rechecked daily).', 'quick-adsense' ); ?></b></i></small></p>
	<div class="clear"></div>
	<?php if ( quick_adsense_get_value( $quick_adsense_template_args, 'is_ajax' ) ) { ?>
		<button type="button" class="notice-dismiss" onclick="javascript:jQuery(this).parent().remove()"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'quick-adsense' ); ?></span></button>
	<?php } ?>
</div>
