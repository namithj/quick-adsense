<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
foreach ( $quick_adsense_template_args['active_ads'] as $quick_adsense_active_ad_index ) {
	?>
	QTags.addButton("quick_adsense_quicktag_onpost_ad_<?php echo esc_js( $quick_adsense_active_ad_index ); ?>", "Ads<?php echo esc_js( $quick_adsense_active_ad_index ); ?>", "\n<!--Ads<?php echo esc_js( $quick_adsense_active_ad_index ); ?>-->\n", "", "", "Ads<?php echo esc_js( $quick_adsense_active_ad_index ); ?>", 201);
<?php } ?>
<?php if ( $quick_adsense_template_args['enable_randomads'] ) { ?>
	QTags.addButton("quick_adsense_quicktag_randomads", "RndAds", "\n<!--RndAds-->\n", "", "", "<?php echo esc_js( __( 'Random Ads', 'quick-adsense' ) ); ?>", 201);
<?php } ?>
<?php if ( $quick_adsense_template_args['enable_disableads'] ) { ?>
	QTags.addButton("quick_adsense_quicktag_noads", "NoAds", "\n<!--NoAds-->\n", "", "", "<?php echo esc_js( __( 'No Ads', 'quick-adsense' ) ); ?>", 201);
	QTags.addButton("quick_adsense_quicktag_offdef", "OffDef", "\n<!--OffDef-->\n", "", "", "<?php echo esc_js( __( 'No Def', 'quick-adsense' ) ); ?>", 201);
	QTags.addButton("quick_adsense_quicktag_offwidget", "OffWidget", "\n<!--OffWidget-->\n", "", "", "<?php echo esc_js( __( 'No AdWidgets', 'quick-adsense' ) ); ?>", 201);
<?php } ?>
<?php if ( $quick_adsense_template_args['enable_positionads'] ) { ?>
	QTags.addButton("quick_adsense_quicktag_offbegin", "OffBegin", "\n<!--OffBegin-->\n", "", "", "<?php echo esc_js( __( 'Disable Beginning of Post Ads', 'quick-adsense' ) ); ?>", 201);
	QTags.addButton("quick_adsense_quicktag_offmiddle", "OffMiddle", "\n<!--OffMiddle-->\n", "", "", "<?php echo esc_js( __( 'Disable Middle of Post Ads', 'quick-adsense' ) ); ?>", 201);
	QTags.addButton("quick_adsense_quicktag_offend", "OffEnd", "\n<!--OffEnd-->\n", "", "", "<?php echo esc_js( __( 'Disable End of Post Ads', 'quick-adsense' ) ); ?>", 201);
	QTags.addButton("quick_adsense_quicktag_offafmore", "OffAfMore", "\n<!--OffAfMore-->\n", "", "", "OffAfMore", 201);
	QTags.addButton("quick_adsense_quicktag_offbflastpara", "OffBfLastPara", "\n<!--OffBfLastPara-->\n", "", "", "OffBfLastPara", 201);
<?php } ?>
