<div class="quick_adsense_block">
	<div class="quick_adsense_block_labels"><span>Options</span></div>
	<div class="quick_adsense_block_controls">
		<a id="quick_adsense_settings_reset_to_default" href="javascript:;">Reset to Default Settings</a>
	</div>
	<div class="clear"></div>
</div>
<?php
echo wp_kses(
	quick_adsense_load_file( 'templates/block-general-adsense.php', $args ),
	quick_adsense_get_allowed_html()
);
echo wp_kses(
	quick_adsense_load_file( 'templates/block-general-appearance.php', $args ),
	quick_adsense_get_allowed_html()
);
echo wp_kses(
	quick_adsense_load_file( 'templates/block-general-assign-position.php', $args ),
	quick_adsense_get_allowed_html()
);
echo wp_kses(
	quick_adsense_load_file( 'templates/block-general-quicktag.php', $args ),
	quick_adsense_get_allowed_html()
);
?>
