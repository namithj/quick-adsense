<?php
/**
 * Quicktag Integration.
 */

add_action(
	'admin_print_footer_scripts',
	function() {
		if ( wp_script_is( 'quicktags' ) ) {
			$settings = get_option( 'quick_adsense_settings' );
			echo '<script type="text/javascript">';
			if ( isset( $settings['enable_quicktag_buttons'] ) && ( $settings['enable_quicktag_buttons'] ) ) {
				for ( $i = 1; $i <= 10; $i++ ) {
					if ( isset( $settings[ 'onpost_ad_' . $i . '_content' ] ) && ( '' !== $settings[ 'onpost_ad_' . $i . '_content' ] ) ) {
						echo 'QTags.addButton("quick_adsense_quicktag_onpost_ad_' . esc_js( $i ) . '", "Ads' . esc_js( $i ) . '", "\n<!--Ads' . esc_js( $i ) . '-->\n", "", "", "Ads' . esc_js( $i ) . '", 201);';
					}
				}
				if ( ! isset( $settings['disable_randomads_quicktag_button'] ) || ( ! $settings['disable_randomads_quicktag_button'] ) ) {
					echo 'QTags.addButton("quick_adsense_quicktag_randomads", "RndAds", "\n<!--RndAds-->\n", "", "", "Random Ads", 201);';
				}
				if ( ! isset( $settings['disable_disablead_quicktag_buttons'] ) || ( ! $settings['disable_disablead_quicktag_buttons'] ) ) {
					echo 'QTags.addButton("quick_adsense_quicktag_noads", "NoAds", "\n<!--NoAds-->\n", "", "", "No Ads", 201);';
					echo 'QTags.addButton("quick_adsense_quicktag_offdef", "OffDef", "\n<!--OffDef-->\n", "", "", "No Def", 201);';
					echo 'QTags.addButton("quick_adsense_quicktag_offwidget", "OffWidget", "\n<!--OffWidget-->\n", "", "", "No AdWidgets", 201);';
				}
				if ( ! isset( $settings['disable_positionad_quicktag_buttons'] ) || ( ! $settings['disable_positionad_quicktag_buttons'] ) ) {
					echo 'QTags.addButton("quick_adsense_quicktag_offbegin", "OffBegin", "\n<!--OffBegin-->\n", "", "", "Disable Beginning of Post Ads", 201);';
					echo 'QTags.addButton("quick_adsense_quicktag_offmiddle", "OffMiddle", "\n<!--OffMiddle-->\n", "", "", "Disable Middle of Post Ads", 201);';
					echo 'QTags.addButton("quick_adsense_quicktag_offend", "OffEnd", "\n<!--OffEnd-->\n", "", "", "Disable End of Post Ads", 201);';
					echo 'QTags.addButton("quick_adsense_quicktag_offafmore", "OffAfMore", "\n<!--OffAfMore-->\n", "", "", "OffAfMore", 201);';
					echo 'QTags.addButton("quick_adsense_quicktag_offbflastpara", "OffBfLastPara", "\n<!--OffBfLastPara-->\n", "", "", "OffBfLastPara", 201);';
				}
			}

			echo '</script>';
		}
	}
);

