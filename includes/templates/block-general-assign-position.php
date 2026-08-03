<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="quick_adsense_block">
	<div class="quick_adsense_block_labels">Assign position<br />(Default)</div>
	<div class="quick_adsense_block_controls">
		<?php
		$quick_adsense_ad_positions = [
			[
				'text'  => 'Random Ads',
				'value' => '0',
			],
		];
		for ( $quick_adsense_i = 1; $quick_adsense_i <= 10; $quick_adsense_i++ ) {
			$quick_adsense_ad_positions[] = [
				'text'  => 'Ads' . $quick_adsense_i,
				'value' => $quick_adsense_i,
			];
		}

		$quick_adsense_element_count = [];
		for ( $quick_adsense_i = 1; $quick_adsense_i <= 50; $quick_adsense_i++ ) {
			$quick_adsense_element_count[] = [
				'text'  => $quick_adsense_i,
				'value' => $quick_adsense_i,
			];
		}
		?>
		<p>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'',
					'quick_adsense_settings_enable_position_beginning_of_post',
					'quick_adsense_settings[enable_position_beginning_of_post]',
					quick_adsense_get_value( $quick_adsense_template_args, 'enable_position_beginning_of_post' ),
					null,
					'input',
					''
				),
				quick_adsense_get_allowed_html()
			);
			echo wp_kses(
				quickadsense_get_control(
					'select',
					'',
					'quick_adsense_settings_ad_beginning_of_post',
					'quick_adsense_settings[ad_beginning_of_post]',
					quick_adsense_get_value( $quick_adsense_template_args, 'ad_beginning_of_post' ),
					$quick_adsense_ad_positions,
					'input',
					'margin: -2px 10px 0 20px;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
			<b style="width: 120px; display: inline-block;">Beginning of Post</b>
		</p>
		<p>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'',
					'quick_adsense_settings_enable_position_middle_of_post',
					'quick_adsense_settings[enable_position_middle_of_post]',
					quick_adsense_get_value( $quick_adsense_template_args, 'enable_position_middle_of_post' ),
					null,
					'input',
					''
				),
				quick_adsense_get_allowed_html()
			);
			echo wp_kses(
				quickadsense_get_control(
					'select',
					'',
					'quick_adsense_settings_ad_middle_of_post',
					'quick_adsense_settings[ad_middle_of_post]',
					quick_adsense_get_value( $quick_adsense_template_args, 'ad_middle_of_post' ),
					$quick_adsense_ad_positions,
					'input',
					'margin: -2px 10px 0 20px;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
			<b style="width: 120px; display: inline-block;">Middle of Post</b>
		</p>
		<p>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'',
					'quick_adsense_settings_enable_position_end_of_post',
					'quick_adsense_settings[enable_position_end_of_post]',
					quick_adsense_get_value( $quick_adsense_template_args, 'enable_position_end_of_post' ),
					null,
					'input',
					''
				),
				quick_adsense_get_allowed_html()
			);
			echo wp_kses(
				quickadsense_get_control(
					'select',
					'',
					'quick_adsense_settings_ad_end_of_post',
					'quick_adsense_settings[ad_end_of_post]',
					quick_adsense_get_value( $quick_adsense_template_args, 'ad_end_of_post' ),
					$quick_adsense_ad_positions,
					'input',
					'margin: -2px 10px 0 20px;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
			<b>End of Post</b>
		</p>
		<div class="clear"></div>
		<p>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'',
					'quick_adsense_settings_enable_position_after_more_tag',
					'quick_adsense_settings[enable_position_after_more_tag]',
					quick_adsense_get_value( $quick_adsense_template_args, 'enable_position_after_more_tag' ),
					null,
					'input',
					''
				),
				quick_adsense_get_allowed_html()
			);
			echo wp_kses(
				quickadsense_get_control(
					'select',
					'',
					'quick_adsense_settings_ad_after_more_tag',
					'quick_adsense_settings[ad_after_more_tag]',
					quick_adsense_get_value( $quick_adsense_template_args, 'ad_after_more_tag' ),
					$quick_adsense_ad_positions,
					'input',
					'margin: -2px 10px 0 20px;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
			right after <b>the &lt;!--more--&gt; tag</b>
		</p>
		<p>
			<?php
			echo wp_kses(
				quickadsense_get_control(
					'checkbox',
					'',
					'quick_adsense_settings_enable_position_before_last_para',
					'quick_adsense_settings[enable_position_before_last_para]',
					quick_adsense_get_value( $quick_adsense_template_args, 'enable_position_before_last_para' ),
					null,
					'input',
					''
				),
				quick_adsense_get_allowed_html()
			);
			echo wp_kses(
				quickadsense_get_control(
					'select',
					'',
					'quick_adsense_settings_ad_before_last_para',
					'quick_adsense_settings[ad_before_last_para]',
					quick_adsense_get_value( $quick_adsense_template_args, 'ad_before_last_para' ),
					$quick_adsense_ad_positions,
					'input',
					'margin: -2px 10px 0 20px;'
				),
				quick_adsense_get_allowed_html()
			);
			?>
			right before <b>the last Paragraph</b>
		</p>
		<div class="clear"></div>
		<?php for ( $quick_adsense_i = 1; $quick_adsense_i <= 3; $quick_adsense_i++ ) { ?>
			<p>
				<?php
				echo wp_kses(
					quickadsense_get_control(
						'checkbox',
						'',
						'quick_adsense_settings_enable_position_after_para_option_' . $quick_adsense_i,
						'quick_adsense_settings[enable_position_after_para_option_' . $quick_adsense_i . ']',
						quick_adsense_get_value( $quick_adsense_template_args, 'enable_position_after_para_option_' . $quick_adsense_i ),
						null,
						'input',
						''
					),
					quick_adsense_get_allowed_html()
				);
				echo wp_kses(
					quickadsense_get_control(
						'select',
						'',
						'quick_adsense_settings_ad_after_para_option_' . $quick_adsense_i,
						'quick_adsense_settings[ad_after_para_option_' . $quick_adsense_i . ']',
						quick_adsense_get_value( $quick_adsense_template_args, 'ad_after_para_option_' . $quick_adsense_i ),
						$quick_adsense_ad_positions,
						'input',
						'margin: -2px 10px 0 20px;'
					),
					quick_adsense_get_allowed_html()
				);
				?>
				<span style="width: 110px;display: inline-block;"><b>after Paragraph</b></span>
				<?php
				echo wp_kses(
					quickadsense_get_control(
						'select',
						'',
						'quick_adsense_settings_position_after_para_option_' . $quick_adsense_i,
						'quick_adsense_settings[position_after_para_option_' . $quick_adsense_i . ']',
						quick_adsense_get_value( $quick_adsense_template_args, 'position_after_para_option_' . $quick_adsense_i ),
						$quick_adsense_element_count,
						'input',
						'margin: -2px 10px 0 10px;'
					),
					quick_adsense_get_allowed_html()
				);
				?>
				repeat
				<?php
				echo wp_kses(
					quickadsense_get_control(
						'checkbox',
						'',
						'quick_adsense_settings_enable_jump_position_after_para_option_' . $quick_adsense_i,
						'quick_adsense_settings[enable_jump_position_after_para_option_' . $quick_adsense_i . ']',
						quick_adsense_get_value( $quick_adsense_template_args, 'enable_jump_position_after_para_option_' . $quick_adsense_i ),
						null,
						'input',
						'margin: -1px 10px 0;'
					),
					quick_adsense_get_allowed_html()
				);
				?>
				<b>to End of Post</b> if fewer paragraphs are found
			</p>
		<?php } ?>
		<div class="clear"></div>
		<?php for ( $quick_adsense_i = 1; $quick_adsense_i <= 1; $quick_adsense_i++ ) { ?>
			<p>
				<?php
				echo wp_kses(
					quickadsense_get_control(
						'checkbox',
						'',
						'quick_adsense_settings_enable_position_after_image_option_' . $quick_adsense_i,
						'quick_adsense_settings[enable_position_after_image_option_' . $quick_adsense_i . ']',
						quick_adsense_get_value( $quick_adsense_template_args, 'enable_position_after_image_option_' . $quick_adsense_i ),
						null,
						'input',
						''
					),
					quick_adsense_get_allowed_html()
				);
				echo wp_kses(
					quickadsense_get_control(
						'select',
						'',
						'quick_adsense_settings_ad_after_image_option_' . $quick_adsense_i,
						'quick_adsense_settings[ad_after_image_option_' . $quick_adsense_i . ']',
						quick_adsense_get_value( $quick_adsense_template_args, 'ad_after_image_option_' . $quick_adsense_i ),
						$quick_adsense_ad_positions,
						'input',
						'margin: -2px 10px 0 20px;'
					),
					quick_adsense_get_allowed_html()
				);
				?>
				<span style="width: 110px;display: inline-block;">after Image</span>
				<?php
				echo wp_kses(
					quickadsense_get_control(
						'select',
						'',
						'quick_adsense_settings_position_after_image_option_' . $quick_adsense_i,
						'quick_adsense_settings[position_after_image_option_' . $quick_adsense_i . ']',
						quick_adsense_get_value( $quick_adsense_template_args, 'position_after_image_option_' . $quick_adsense_i ),
						$quick_adsense_element_count,
						'input',
						'margin: -2px 10px 0 10px;'
					),
					quick_adsense_get_allowed_html()
				);
				?>
				repeat
				<?php
				echo wp_kses(
					quickadsense_get_control(
						'checkbox',
						'',
						'quick_adsense_settings_enable_jump_position_after_image_option_' . $quick_adsense_i,
						'quick_adsense_settings[enable_jump_position_after_image_option_' . $quick_adsense_i . ']',
						quick_adsense_get_value( $quick_adsense_template_args, 'enable_jump_position_after_image_option_' . $quick_adsense_i ),
						null,
						'input',
						'margin: -1px 10px 0;'
					),
					quick_adsense_get_allowed_html()
				);
				?>
				after <b>Image\'s outer &lt;div&gt; wp-caption</b> if any
			</p>
		<?php } ?>
	</div>
	<div class="clear"></div>
</div>
