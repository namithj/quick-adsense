<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="quick_adsense_popup_content_wrapper">
	<p><?php esc_html_e( 'Auto Creation / Updation of ads.txt failed due to access permission restrictions on the server.', 'quick-adsense' ); ?></p>
	<p><?php esc_html_e( 'You have to manually upload the file using your Host\'s File manager or your favourite FTP program', 'quick-adsense' ); ?></p>
	<p>
		<?php
		echo wp_kses(
			sprintf(
				/* translators: %s: link to the ads.txt file, with the text "here". */
				__( 'ads.txt should be located in the root of your server. After manually uploading the file click %s to check if its accessible from the correct location', 'quick-adsense' ),
				'<a href="' . esc_url( site_url( '/ads.txt' ) ) . '">' . esc_html__( 'here', 'quick-adsense' ) . '</a>'
			),
			[ 'a' => [ 'href' => [] ] ]
		);
		?>
	</p>
	<textarea style="display: none;" id="quick_adsense_adstxt_content"><?php echo wp_kses( quick_adsense_get_value( $quick_adsense_template_args, 'content' ), quick_adsense_get_allowed_html() ); ?></textarea>
	<p><a onclick="quick_adsense_adstxt_content_download()" class="button button-primary" href="javascript:;"><?php esc_html_e( 'Download ads.txt', 'quick-adsense' ); ?></a></p>
</div>
