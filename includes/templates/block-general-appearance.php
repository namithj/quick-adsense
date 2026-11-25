<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="quick_adsense_block">
	<div class="quick_adsense_block_labels"><?php esc_html_e( 'Appearance', 'quick-adsense' ); ?></div>
	<div class="quick_adsense_block_controls">
	<p>
		<span>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'<b id="quick_adsense_settings_enable_on_posts_label">' . esc_html__( 'Posts', 'quick-adsense' ) . '</b>',
					'quick_adsense_settings_enable_on_posts',
					'quick_adsense_settings[enable_on_posts]',
					quick_adsense_get_value( $quick_adsense_template_args, 'enable_on_posts' ),
					null,
					'input',
					'margin: -1px 10px 0 0;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
		</span>
		<span>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'<b id="quick_adsense_settings_enable_on_pages_label">' . esc_html__( 'Pages', 'quick-adsense' ) . '</b>',
					'quick_adsense_settings_enable_on_pages',
					'quick_adsense_settings[enable_on_pages]',
					quick_adsense_get_value( $quick_adsense_template_args, 'enable_on_pages' ),
					null,
					'input',
					'margin: -1px 10px 0 15px;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
		</span>
	</p>
	<p>
		<span>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'<b id="quick_adsense_settings_enable_on_homepage_label">' . esc_html__( 'Homepage', 'quick-adsense' ) . '</b>',
					'quick_adsense_settings_enable_on_homepage',
					'quick_adsense_settings[enable_on_homepage]',
					quick_adsense_get_value( $quick_adsense_template_args, 'enable_on_homepage' ),
					null,
					'input',
					'margin: -1px 10px 0 0;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
		</span>
		<span>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'<b id="quick_adsense_settings_enable_on_categories_label">' . esc_html__( 'Categories', 'quick-adsense' ) . '</b>',
					'quick_adsense_settings_enable_on_categories',
					'quick_adsense_settings[enable_on_categories]',
					quick_adsense_get_value( $quick_adsense_template_args, 'enable_on_categories' ),
					null,
					'input',
					'margin: -1px 10px 0 15px;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
		</span>
		<span>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'<b id="quick_adsense_settings_enable_on_archives_label">' . esc_html__( 'Archives', 'quick-adsense' ) . '</b>',
					'quick_adsense_settings_enable_on_archives',
					'quick_adsense_settings[enable_on_archives]',
					quick_adsense_get_value( $quick_adsense_template_args, 'enable_on_archives' ),
					null,
					'input',
					'margin: -1px 10px 0 15px;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
		</span>
		<span>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'<b id="quick_adsense_settings_enable_on_tags_label">' . esc_html__( 'Tags', 'quick-adsense' ) . '</b>',
					'quick_adsense_settings_enable_on_tags',
					'quick_adsense_settings[enable_on_tags]',
					quick_adsense_get_value( $quick_adsense_template_args, 'enable_on_tags' ),
					null,
					'input',
					'margin: -1px 10px 0 15px;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
		</span>
		<span>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'<b id="quick_adsense_settings_enable_all_possible_ads_label">' . esc_html__( 'Place all possible Ads on these pages', 'quick-adsense' ) . '</b>',
					'quick_adsense_settings_enable_all_possible_ads',
					'quick_adsense_settings[enable_all_possible_ads]',
					quick_adsense_get_value( $quick_adsense_template_args, 'enable_all_possible_ads' ),
					null,
					'input',
					'margin: -1px 10px 0 35px;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
		</span>
	</p>
	<p>
		<span>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'<b id="quick_adsense_settings_disable_widgets_on_homepage_label">' . esc_html__( 'Disable AdsWidget on Homepage', 'quick-adsense' ) . '</b>',
					'quick_adsense_settings_disable_widgets_on_homepage',
					'quick_adsense_settings[disable_widgets_on_homepage]',
					quick_adsense_get_value( $quick_adsense_template_args, 'disable_widgets_on_homepage' ),
					null,
					'input',
					'margin: -1px 10px 0 0;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
		</span>
	</p>
	<p>
		<span>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'<b id="quick_adsense_settings_disable_for_loggedin_users_label">' . esc_html__( 'Hide Ads when user is logged in to WordPress', 'quick-adsense' ) . '</b>',
					'quick_adsense_settings_disable_for_loggedin_users',
					'quick_adsense_settings[disable_for_loggedin_users]',
					quick_adsense_get_value( $quick_adsense_template_args, 'disable_for_loggedin_users' ),
					null,
					'input',
					'margin: -1px 10px 0 0;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
		</span>
	</p>
	</div>
	<div class="clear"></div>
</div>
