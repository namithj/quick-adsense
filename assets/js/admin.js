var quick_adsense_i18n = wp.i18n;

/**
 * Open a native modal <dialog>. It is removed from the page when it closes.
 *
 * Escape, the close button and a click on the backdrop close it; focus is
 * trapped and restored by the browser.
 *
 * @param {Object}   args            Dialog arguments.
 * @param {string}   args.title      Title text.
 * @param {string}   args.content    Body HTML (admin-ajax output or a loader).
 * @param {string}   [args.className] Extra class for the dialog.
 * @param {Object[]} [args.buttons]  Footer buttons: { label }. Each closes the dialog.
 * @return {HTMLDialogElement} The open dialog.
 */
function quick_adsense_open_dialog(args) {
	var dialog = document.createElement('dialog');
	var titleId = 'qa-dialog-title-' + Date.now();
	dialog.className = 'qa-dialog' + (args.className ? ' ' + args.className : '');
	dialog.setAttribute('aria-labelledby', titleId);

	var header = document.createElement('header');
	header.className = 'qa-dialog__header';
	var title = document.createElement('h2');
	title.className = 'qa-dialog__title';
	title.id = titleId;
	title.textContent = args.title;
	var close = document.createElement('button');
	close.type = 'button';
	close.className = 'qa-dialog__close';
	close.setAttribute('aria-label', quick_adsense_i18n.__('Close', 'quick-adsense'));
	close.innerHTML = '<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>';
	close.addEventListener('click', function () {
		dialog.close();
	});
	header.appendChild(title);
	header.appendChild(close);

	var content = document.createElement('div');
	content.className = 'qa-dialog__content';
	// Focus the body on open (not the close button), as Material dialogs do; screen readers still get the title via aria-labelledby.
	content.tabIndex = -1;
	content.autofocus = true;
	jQuery(content).html(args.content);

	dialog.appendChild(header);
	dialog.appendChild(content);

	if (args.buttons && args.buttons.length) {
		var actions = document.createElement('div');
		actions.className = 'qa-dialog__actions';
		args.buttons.forEach(function (button) {
			var element = document.createElement('button');
			element.type = 'button';
			element.className = 'qa-btn qa-btn--text';
			element.textContent = button.label;
			element.addEventListener('click', function () {
				dialog.close();
			});
			actions.appendChild(element);
		});
		dialog.appendChild(actions);
	}

	// The dialog has no padding, so a click whose target is the dialog itself landed on the backdrop.
	dialog.addEventListener('click', function (event) {
		if (event.target === dialog) {
			dialog.close();
		}
	});
	dialog.addEventListener('close', function () {
		dialog.remove();
	});

	document.body.appendChild(dialog);
	dialog.showModal();
	return dialog;
}

jQuery(document).ready(function () {
	jQuery("#quick_adsense_settings_tabs").tabs();
	jQuery('body').removeClass('qa-js-loading');
	jQuery('#quick_adsense_settings_reset_to_default').click(quick_adsense_settings_reset_to_default);

	jQuery('#quick_adsense_settings_enable_position_beginning_of_post').click(quick_adsense_settings_enable_position_beginning_of_post);
	jQuery('#quick_adsense_settings_enable_position_middle_of_post').click(quick_adsense_settings_enable_position_middle_of_post);
	jQuery('#quick_adsense_settings_enable_position_end_of_post').click(quick_adsense_settings_enable_position_end_of_post);
	jQuery('#quick_adsense_settings_enable_position_after_more_tag').click(quick_adsense_settings_enable_position_after_more_tag);
	jQuery('#quick_adsense_settings_enable_position_before_last_para').click(quick_adsense_settings_enable_position_before_last_para);
	jQuery('#quick_adsense_settings_enable_position_after_para_option_1').click(quick_adsense_settings_enable_position_after_para_option_1);
	jQuery('#quick_adsense_settings_enable_position_after_para_option_2').click(quick_adsense_settings_enable_position_after_para_option_2);
	jQuery('#quick_adsense_settings_enable_position_after_para_option_3').click(quick_adsense_settings_enable_position_after_para_option_3);
	jQuery('#quick_adsense_settings_enable_position_after_image_option_1').click(quick_adsense_settings_enable_position_after_image_option_1);
	jQuery('#quick_adsense_settings_enable_on_posts').click(quick_adsense_settings_enable_on_posts);
	jQuery('#quick_adsense_settings_enable_on_pages').click(quick_adsense_settings_enable_on_pages);
	jQuery('#quick_adsense_settings_enable_on_homepage').click(quick_adsense_settings_enable_on_homepage);
	jQuery('#quick_adsense_settings_enable_on_categories').click(quick_adsense_settings_enable_on_categories);
	jQuery('#quick_adsense_settings_enable_on_archives').click(quick_adsense_settings_enable_on_archives);
	jQuery('#quick_adsense_settings_enable_on_tags').click(quick_adsense_settings_enable_on_tags);
	jQuery('#quick_adsense_settings_enable_all_possible_ads').click(quick_adsense_settings_enable_all_possible_ads);
	jQuery('#quick_adsense_settings_disable_widgets_on_homepage').click(quick_adsense_settings_disable_widgets_on_homepage);
	jQuery('#quick_adsense_settings_disable_for_loggedin_users').click(quick_adsense_settings_disable_for_loggedin_users);
	jQuery('#quick_adsense_settings_enable_quicktag_buttons').click(quick_adsense_settings_enable_quicktag_buttons);
	jQuery('#quick_adsense_settings_disable_randomads_quicktag_button').click(quick_adsense_settings_disable_randomads_quicktag_button);
	jQuery('#quick_adsense_settings_disable_disablead_quicktag_buttons').click(quick_adsense_settings_disable_disablead_quicktag_buttons);
	jQuery('#quick_adsense_settings_disable_positionad_quicktag_buttons').click(quick_adsense_settings_disable_positionad_quicktag_buttons);

	jQuery('#quick_adsense_settings_onpost_enable_global_style').click(quick_adsense_settings_onpost_enable_global_style);
	jQuery('#quick_adsense_settings_onpost_global_alignment').click(quick_adsense_settings_onpost_enable_global_style);
	jQuery('#quick_adsense_settings_onpost_global_margin').click(quick_adsense_settings_onpost_enable_global_style);

	quick_adsense_settings_enable_position_beginning_of_post();
	quick_adsense_settings_enable_position_middle_of_post();
	quick_adsense_settings_enable_position_end_of_post();
	quick_adsense_settings_enable_position_after_more_tag();
	quick_adsense_settings_enable_position_before_last_para();
	quick_adsense_settings_enable_position_after_para_option_1();
	quick_adsense_settings_enable_position_after_para_option_2();
	quick_adsense_settings_enable_position_after_para_option_3();
	quick_adsense_settings_enable_position_after_image_option_1();
	quick_adsense_settings_enable_on_posts();
	quick_adsense_settings_enable_on_pages();
	quick_adsense_settings_enable_on_homepage();
	quick_adsense_settings_enable_on_categories();
	quick_adsense_settings_enable_on_archives();
	quick_adsense_settings_enable_on_tags();
	quick_adsense_settings_enable_all_possible_ads();
	quick_adsense_settings_disable_widgets_on_homepage();
	quick_adsense_settings_disable_for_loggedin_users();
	quick_adsense_settings_enable_quicktag_buttons();
	quick_adsense_settings_disable_randomads_quicktag_button();
	quick_adsense_settings_disable_disablead_quicktag_buttons();
	quick_adsense_settings_disable_positionad_quicktag_buttons();
	quick_adsense_settings_onpost_enable_global_style();

	jQuery('#quick_adsense_onpost_content_adunits_showall_button').click(function () {
		var button = jQuery(this);
		if (!button.data('expanded')) {
			jQuery('#quick_adsense_onpost_content_adunits_all_wrapper').slideDown();
			button.data('expanded', true).html('<span class="dashicons dashicons-arrow-up"></span> <b></b>').find('b').text(quick_adsense_i18n.__('Show Less', 'quick-adsense'));
		} else {
			jQuery('#quick_adsense_onpost_content_adunits_all_wrapper').slideUp();
			button.data('expanded', false).html('<span class="dashicons dashicons-arrow-down"></span> <b></b>').find('b').text(quick_adsense_i18n.__('Show All', 'quick-adsense'));
		}
	});

	jQuery('#quick_adsense_widget_adunits_showall_button').click(function () {
		var button = jQuery(this);
		if (!button.data('expanded')) {
			jQuery('#quick_adsense_widget_adunits_all_wrapper').slideDown();
			button.data('expanded', true).html('<span class="dashicons dashicons-arrow-up"></span> <b></b>').find('b').text(quick_adsense_i18n.__('Show Less', 'quick-adsense'));
		} else {
			jQuery('#quick_adsense_widget_adunits_all_wrapper').slideUp();
			button.data('expanded', false).html('<span class="dashicons dashicons-arrow-down"></span> <b></b>').find('b').text(quick_adsense_i18n.__('Show All', 'quick-adsense'));
		}
	});

	jQuery('#quick_adsense_settings_form').submit(function () {
		jQuery('#quick_adsense_settings_form select').each(function () {
			if (jQuery(this).prop('disabled') == true) {
				jQuery(this).prop('disabled', false);
			}
		});
	});

	jQuery('.quick_adsense_backup_restore_form').on('submit', function () {
		return window.confirm(quick_adsense_i18n.__('Restore this backup? Your current settings are backed up first.', 'quick-adsense'));
	});
	if (window.location.href.indexOf('#quick_adsense_adstxt_adsense_auto_update') > -1) {
		quick_adsense_adstxt_adsense_auto_update();
	}

	jQuery('.quick_adsense_onpost_ad_reset_stats').click(function () {
		jQuery(this).prop('disabled', true);
		wp.ajax.post('quick_adsense_onpost_ad_reset_stats', {
			nonce: quick_adsense.nonce,
			index: jQuery(this).attr('data-index'),
		}).done(function (result) {
			jQuery('.quick_adsense_onpost_ad_reset_stats').each(function () {
				jQuery(this).prop('disabled', false);
			});
		});
	});

	jQuery('.quick_adsense_onpost_ad_show_stats').click(function () {
		var index = jQuery(this).attr('data-index');
		var quick_adsense_ad_stats_chart = null;
		var dialog = quick_adsense_open_dialog({
			className: 'qa-dialog--stats',
			title: quick_adsense_i18n.sprintf(
				/* translators: %s: ad number, for example Ad1. */
				quick_adsense_i18n.__('%s Performance Stats (30 Days)', 'quick-adsense'),
				'Ad' + index
			),
			content: '<div class="quick_adsense_ajaxloader"></div>'
		});
		dialog.addEventListener('close', function () {
			if (quick_adsense_ad_stats_chart) {
				quick_adsense_ad_stats_chart.destroy();
			}
		});
		wp.ajax.post('quick_adsense_onpost_ad_get_stats_chart', {
			nonce: quick_adsense.nonce,
			index: index,
		}).done(function (result) {
				if (!dialog.open) {
					return;
				}
				jQuery(dialog).find('.qa-dialog__content').html(result);

					jQuery('#quick_adsense_ad_stats_chart_wrapper canvas').attr('width', jQuery('#quick_adsense_ad_stats_chart_wrapper').width() + 'px');
					jQuery('#quick_adsense_ad_stats_chart_wrapper canvas').attr('height', jQuery('#quick_adsense_ad_stats_chart_wrapper').height() + 'px');
					if (jQuery('#quick_adsense_ad_stats_chart_data').length) {
						const quick_adsense_ad_stats_chart_data = JSON.parse(jQuery('#quick_adsense_ad_stats_chart_data').val());
						quick_adsense_ad_stats_chart = new Chart(jQuery('#quick_adsense_ad_stats_chart'), {
							type: 'line',
							responsive: false,
							data: {
								datasets: [{
									label: quick_adsense_i18n.__('Impressions', 'quick-adsense'),
									data: quick_adsense_ad_stats_chart_data.map(function (item) {
										return {
											x: item.x,
											y: item.y
										};
									}),
									backgroundColor: '#EDF5FB',
									borderColor: '#186EAE',
									borderWidth: 1,
									yAxisID: 'y',
								},
								{
									label: quick_adsense_i18n.__('Clicks', 'quick-adsense'),
									data: quick_adsense_ad_stats_chart_data.map(function (item) {
										return {
											x: item.x,
											y: item.y1
										};
									}),
									backgroundColor: '#FC9DAF',
									borderColor: '#E80534',
									borderWidth: 1,
									yAxisID: 'y',
								}]
							},
							options: {
								stacked: true,
								title: {
									display: false,
									backgroundColor: '#EDF5FB'
								},
								legend: {
									display: false,
								},
								scales: {
									x: {
										display: true,
										scaleLabel: {
											display: true
										},
										gridLines: {
											display: true,
											drawTicks: true
										},
										ticks: {
											display: true
										}
									},
									y: {
										display: true,
										scaleLabel: {
											display: true
										},
										gridLines: {
											display: true,
											drawTicks: true
										},
										ticks: {
											display: true
										}
									}
								},
								tooltips: {
									displayColors: false,
									callbacks: {
										label: function (tooltipItem, data) {
											return [
												quick_adsense_i18n.sprintf(
													/* translators: %d: number of ad impressions. */
													quick_adsense_i18n.__('Impressions: %d', 'quick-adsense'),
													parseInt(data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index]['y'], 10)
												),
												quick_adsense_i18n.sprintf(
													/* translators: %d: number of ad clicks. */
													quick_adsense_i18n.__('Clicks: %d', 'quick-adsense'),
													parseInt(data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index]['y1'], 10)
												)
											];
										},
										title: function (tooltipItem, data) {
											var monthNames = [
												quick_adsense_i18n._x('Jan', 'month abbreviation', 'quick-adsense'),
												quick_adsense_i18n._x('Feb', 'month abbreviation', 'quick-adsense'),
												quick_adsense_i18n._x('Mar', 'month abbreviation', 'quick-adsense'),
												quick_adsense_i18n._x('Apr', 'month abbreviation', 'quick-adsense'),
												quick_adsense_i18n._x('May', 'month abbreviation', 'quick-adsense'),
												quick_adsense_i18n._x('Jun', 'month abbreviation', 'quick-adsense'),
												quick_adsense_i18n._x('Jul', 'month abbreviation', 'quick-adsense'),
												quick_adsense_i18n._x('Aug', 'month abbreviation', 'quick-adsense'),
												quick_adsense_i18n._x('Sep', 'month abbreviation', 'quick-adsense'),
												quick_adsense_i18n._x('Oct', 'month abbreviation', 'quick-adsense'),
												quick_adsense_i18n._x('Nov', 'month abbreviation', 'quick-adsense'),
												quick_adsense_i18n._x('Dec', 'month abbreviation', 'quick-adsense')
											];
											var dateParts = tooltipItem[0].xLabel.split('/');
											var date = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]);
											return monthNames[date.getMonth()] + ' ' + date.getDate();
										}
									}
								}
							}
						});
					}
				});
	});
});

function quick_adsense_adstxt_adsense_auto_update() {
	wp.ajax.post('quick_adsense_adstxt_adsense_auto_update', {
		nonce: quick_adsense_adstxt_adsense.nonce,
	}).done(function () {
		jQuery('.quick_adsense_adstxt_adsense_notice').hide();
	}).fail(function (result) {
		if ('undefined' !== typeof result) {
			quick_adsense_open_dialog({
				title: quick_adsense_i18n.__('Ads.txt Auto Updation Failed', 'quick-adsense'),
				content: result,
				buttons: [{ label: quick_adsense_i18n.__('Cancel', 'quick-adsense') }]
			});
		}
	});
}

function quick_adsense_settings_reset_to_default() {
	jQuery('#quick_adsense_settings_max_ads_per_page').val('3');

	jQuery('#quick_adsense_settings_enable_position_beginning_of_post').prop('checked', true);
	jQuery('#quick_adsense_settings_ad_beginning_of_post').val('1');
	jQuery('#quick_adsense_settings_enable_position_middle_of_post').prop('checked', false);
	jQuery('#quick_adsense_settings_ad_middle_of_post').val('0');
	jQuery('#quick_adsense_settings_enable_position_end_of_post').prop('checked', true);
	jQuery('#quick_adsense_settings_ad_end_of_post').val('0');

	jQuery('#quick_adsense_settings_enable_position_after_more_tag').prop('checked', false);
	jQuery('#quick_adsense_settings_ad_after_more_tag').val('0');
	jQuery('#quick_adsense_settings_enable_position_before_last_para').prop('checked', false);
	jQuery('#quick_adsense_settings_ad_before_last_para').val('0');

	for (var i = 1; i <= 3; i++) {
		jQuery('#quick_adsense_settings_enable_position_after_para_option_' + i).prop('checked', false);
		jQuery('#quick_adsense_settings_ad_after_para_option_' + i).val('0');
		jQuery('#quick_adsense_settings_position_after_para_option_' + i).val('1');
		jQuery('#quick_adsense_settings_enable_jump_position_after_para_option_' + i).prop('checked', false);
	}

	for (var i = 1; i <= 1; i++) {
		jQuery('#quick_adsense_settings_enable_position_after_image_option_' + i).prop('checked', false);
		jQuery('#quick_adsense_settings_ad_after_image_option_' + i).val('0');
		jQuery('#quick_adsense_settings_position_after_image_option_' + i).val('1');
		jQuery('#quick_adsense_settings_enable_jump_position_after_image_option_' + i).prop('checked', false);
	}

	jQuery('#quick_adsense_settings_enable_on_posts').prop('checked', true);
	jQuery('#quick_adsense_settings_enable_on_pages').prop('checked', true);

	jQuery('#quick_adsense_settings_enable_on_homepage').prop('checked', false);
	jQuery('#quick_adsense_settings_enable_on_categories').prop('checked', false);
	jQuery('#quick_adsense_settings_enable_on_archives').prop('checked', false);
	jQuery('#quick_adsense_settings_enable_on_tags').prop('checked', false);
	jQuery('#quick_adsense_settings_enable_all_possible_ads').prop('checked', false);

	jQuery('#quick_adsense_settings_disable_widgets_on_homepage').prop('checked', false);

	jQuery('#quick_adsense_settings_disable_for_loggedin_users').prop('checked', false);

	jQuery('#quick_adsense_settings_enable_quicktag_buttons').prop('checked', true);
	jQuery('#quick_adsense_settings_disable_randomads_quicktag_button').prop('checked', false);
	jQuery('#quick_adsense_settings_disable_disablead_quicktag_buttons').prop('checked', false);
	jQuery('#quick_adsense_settings_disable_positionad_quicktag_buttons').prop('checked', false);

	jQuery('#quick_adsense_settings_onpost_enable_global_style').prop('checked', false);
	jQuery('#quick_adsense_settings_onpost_global_alignment').val('2');
	jQuery('#quick_adsense_settings_onpost_global_margin').val('10');

	for (var i = 1; i <= 10; i++) {
		jQuery('#quick_adsense_settings_onpost_ad_' + i + '_content').val('');
		jQuery('#quick_adsense_settings_onpost_ad_' + i + '_alignment').val('2');
		jQuery('#quick_adsense_settings_onpost_ad_' + i + '_margin').val('10');

		jQuery('#quick_adsense_settings_widget_ad_' + i + '_content').val('');
	}

	quick_adsense_settings_enable_position_beginning_of_post();
	quick_adsense_settings_enable_position_middle_of_post();
	quick_adsense_settings_enable_position_end_of_post();
	quick_adsense_settings_enable_position_after_more_tag();
	quick_adsense_settings_enable_position_before_last_para();
	quick_adsense_settings_enable_position_after_para_option_1();
	quick_adsense_settings_enable_position_after_para_option_2();
	quick_adsense_settings_enable_position_after_para_option_3();
	quick_adsense_settings_enable_position_after_image_option_1();
	quick_adsense_settings_enable_on_posts();
	quick_adsense_settings_enable_on_pages();
	quick_adsense_settings_enable_on_homepage();
	quick_adsense_settings_enable_on_categories();
	quick_adsense_settings_enable_on_archives();
	quick_adsense_settings_enable_on_tags();
	quick_adsense_settings_enable_all_possible_ads();
	quick_adsense_settings_disable_widgets_on_homepage();
	quick_adsense_settings_disable_for_loggedin_users();
	quick_adsense_settings_enable_quicktag_buttons();
	quick_adsense_settings_disable_randomads_quicktag_button();
	quick_adsense_settings_disable_disablead_quicktag_buttons();
	quick_adsense_settings_disable_positionad_quicktag_buttons();
	quick_adsense_settings_onpost_enable_global_style();
}

function quick_adsense_settings_enable_position_beginning_of_post() {
	if (jQuery('#quick_adsense_settings_enable_position_beginning_of_post').prop('checked') == true) {
		jQuery('#quick_adsense_settings_ad_beginning_of_post').prop('disabled', false);
		jQuery('#quick_adsense_settings_ad_beginning_of_post').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_ad_beginning_of_post').prop('disabled', true);
		jQuery('#quick_adsense_settings_ad_beginning_of_post').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_position_middle_of_post() {
	if (jQuery('#quick_adsense_settings_enable_position_middle_of_post').prop('checked') == true) {
		jQuery('#quick_adsense_settings_ad_middle_of_post').prop('disabled', false);
		jQuery('#quick_adsense_settings_ad_middle_of_post').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_ad_middle_of_post').prop('disabled', true);
		jQuery('#quick_adsense_settings_ad_middle_of_post').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_position_end_of_post() {
	if (jQuery('#quick_adsense_settings_enable_position_end_of_post').prop('checked') == true) {
		jQuery('#quick_adsense_settings_ad_end_of_post').prop('disabled', false);
		jQuery('#quick_adsense_settings_ad_end_of_post').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_ad_end_of_post').prop('disabled', true);
		jQuery('#quick_adsense_settings_ad_end_of_post').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_position_after_more_tag() {
	if (jQuery('#quick_adsense_settings_enable_position_after_more_tag').prop('checked') == true) {
		jQuery('#quick_adsense_settings_ad_after_more_tag').prop('disabled', false);
		jQuery('#quick_adsense_settings_ad_after_more_tag').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_ad_after_more_tag').prop('disabled', true);
		jQuery('#quick_adsense_settings_ad_after_more_tag').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_position_before_last_para() {
	if (jQuery('#quick_adsense_settings_enable_position_before_last_para').prop('checked') == true) {
		jQuery('#quick_adsense_settings_ad_before_last_para').prop('disabled', false);
		jQuery('#quick_adsense_settings_ad_before_last_para').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_ad_before_last_para').prop('disabled', true);
		jQuery('#quick_adsense_settings_ad_before_last_para').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_position_after_para_option_1() {
	if (jQuery('#quick_adsense_settings_enable_position_after_para_option_1').prop('checked') == true) {
		jQuery('#quick_adsense_settings_ad_after_para_option_1').prop('disabled', false);
		jQuery('#quick_adsense_settings_position_after_para_option_1').prop('disabled', false);
		jQuery('#quick_adsense_settings_enable_jump_position_after_para_option_1').prop('disabled', false);
		jQuery('#quick_adsense_settings_ad_after_para_option_1').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_ad_after_para_option_1').prop('disabled', true);
		jQuery('#quick_adsense_settings_position_after_para_option_1').prop('disabled', true);
		jQuery('#quick_adsense_settings_enable_jump_position_after_para_option_1').prop('disabled', true);
		jQuery('#quick_adsense_settings_ad_after_para_option_1').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_position_after_para_option_2() {
	if (jQuery('#quick_adsense_settings_enable_position_after_para_option_2').prop('checked') == true) {
		jQuery('#quick_adsense_settings_ad_after_para_option_2').prop('disabled', false);
		jQuery('#quick_adsense_settings_position_after_para_option_2').prop('disabled', false);
		jQuery('#quick_adsense_settings_enable_jump_position_after_para_option_2').prop('disabled', false);
		jQuery('#quick_adsense_settings_ad_after_para_option_2').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_ad_after_para_option_2').prop('disabled', true);
		jQuery('#quick_adsense_settings_position_after_para_option_2').prop('disabled', true);
		jQuery('#quick_adsense_settings_enable_jump_position_after_para_option_2').prop('disabled', true);
		jQuery('#quick_adsense_settings_ad_after_para_option_2').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_position_after_para_option_3() {
	if (jQuery('#quick_adsense_settings_enable_position_after_para_option_3').prop('checked') == true) {
		jQuery('#quick_adsense_settings_ad_after_para_option_3').prop('disabled', false);
		jQuery('#quick_adsense_settings_position_after_para_option_3').prop('disabled', false);
		jQuery('#quick_adsense_settings_enable_jump_position_after_para_option_3').prop('disabled', false);
		jQuery('#quick_adsense_settings_ad_after_para_option_3').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_ad_after_para_option_3').prop('disabled', true);
		jQuery('#quick_adsense_settings_position_after_para_option_3').prop('disabled', true);
		jQuery('#quick_adsense_settings_enable_jump_position_after_para_option_3').prop('disabled', true);
		jQuery('#quick_adsense_settings_ad_after_para_option_3').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_position_after_image_option_1() {
	if (jQuery('#quick_adsense_settings_enable_position_after_image_option_1').prop('checked') == true) {
		jQuery('#quick_adsense_settings_ad_after_image_option_1').prop('disabled', false);
		jQuery('#quick_adsense_settings_position_after_image_option_1').prop('disabled', false);
		jQuery('#quick_adsense_settings_enable_jump_position_after_image_option_1').prop('disabled', false);
		jQuery('#quick_adsense_settings_ad_after_image_option_1').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_ad_after_image_option_1').prop('disabled', true);
		jQuery('#quick_adsense_settings_position_after_image_option_1').prop('disabled', true);
		jQuery('#quick_adsense_settings_enable_jump_position_after_image_option_1').prop('disabled', true);
		jQuery('#quick_adsense_settings_ad_after_image_option_1').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_on_posts() {
	if (jQuery('#quick_adsense_settings_enable_on_posts').prop('checked') == true) {
		jQuery('#quick_adsense_settings_enable_on_posts').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_enable_on_posts').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_on_pages() {
	if (jQuery('#quick_adsense_settings_enable_on_pages').prop('checked') == true) {
		jQuery('#quick_adsense_settings_enable_on_pages').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_enable_on_pages').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_on_homepage() {
	if (jQuery('#quick_adsense_settings_enable_on_homepage').prop('checked') == true) {
		jQuery('#quick_adsense_settings_enable_on_homepage').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_enable_on_homepage').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_on_categories() {
	if (jQuery('#quick_adsense_settings_enable_on_categories').prop('checked') == true) {
		jQuery('#quick_adsense_settings_enable_on_categories').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_enable_on_categories').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_on_archives() {
	if (jQuery('#quick_adsense_settings_enable_on_archives').prop('checked') == true) {
		jQuery('#quick_adsense_settings_enable_on_archives').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_enable_on_archives').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_on_tags() {
	if (jQuery('#quick_adsense_settings_enable_on_tags').prop('checked') == true) {
		jQuery('#quick_adsense_settings_enable_on_tags').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_enable_on_tags').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_all_possible_ads() {
	if (jQuery('#quick_adsense_settings_enable_all_possible_ads').prop('checked') == true) {
		jQuery('#quick_adsense_settings_enable_all_possible_ads').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_enable_all_possible_ads').parent().addClass('disabled');
	}
}

function quick_adsense_settings_disable_widgets_on_homepage() {
	if (jQuery('#quick_adsense_settings_disable_widgets_on_homepage').prop('checked') == true) {
		jQuery('#quick_adsense_settings_disable_widgets_on_homepage').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_disable_widgets_on_homepage').parent().addClass('disabled');
	}
}

function quick_adsense_settings_disable_for_loggedin_users() {
	if (jQuery('#quick_adsense_settings_disable_for_loggedin_users').prop('checked') == true) {
		jQuery('#quick_adsense_settings_disable_for_loggedin_users').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_disable_for_loggedin_users').parent().addClass('disabled');
	}
}

function quick_adsense_settings_enable_quicktag_buttons() {
	if (jQuery('#quick_adsense_settings_enable_quicktag_buttons').prop('checked') == true) {
		jQuery('#quick_adsense_settings_enable_quicktag_buttons').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_enable_quicktag_buttons').parent().addClass('disabled');
	}
}

function quick_adsense_settings_disable_randomads_quicktag_button() {
	if (jQuery('#quick_adsense_settings_disable_randomads_quicktag_button').prop('checked') == true) {
		jQuery('#quick_adsense_settings_disable_randomads_quicktag_button').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_disable_randomads_quicktag_button').parent().addClass('disabled');
	}
}

function quick_adsense_settings_disable_disablead_quicktag_buttons() {
	if (jQuery('#quick_adsense_settings_disable_disablead_quicktag_buttons').prop('checked') == true) {
		jQuery('#quick_adsense_settings_disable_disablead_quicktag_buttons').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_disable_disablead_quicktag_buttons').parent().addClass('disabled');
	}
}

function quick_adsense_settings_disable_positionad_quicktag_buttons() {
	if (jQuery('#quick_adsense_settings_disable_positionad_quicktag_buttons').prop('checked') == true) {
		jQuery('#quick_adsense_settings_disable_positionad_quicktag_buttons').parent().removeClass('disabled');
	} else {
		jQuery('#quick_adsense_settings_disable_positionad_quicktag_buttons').parent().addClass('disabled');
	}
}

function quick_adsense_settings_onpost_enable_global_style() {
	if (jQuery('#quick_adsense_settings_onpost_enable_global_style').prop('checked') == true) {
		jQuery('#quick_adsense_settings_onpost_enable_global_style').parent().removeClass('disabled');
		jQuery('#quick_adsense_settings_onpost_global_alignment').prop('disabled', false);
		jQuery('#quick_adsense_settings_onpost_global_margin').prop('disabled', false);
		for (var i = 1; i <= 10; i++) {
			jQuery('#quick_adsense_settings_onpost_ad_' + i + '_alignment').val(jQuery('#quick_adsense_settings_onpost_global_alignment').val());
			jQuery('#quick_adsense_settings_onpost_ad_' + i + '_margin').val(jQuery('#quick_adsense_settings_onpost_global_margin').val());
			jQuery('#quick_adsense_settings_onpost_ad_' + i + '_alignment').prop('disabled', true);
			jQuery('#quick_adsense_settings_onpost_ad_' + i + '_margin').prop('disabled', true);
			jQuery('#quick_adsense_settings_onpost_ad_' + i + '_alignment').parent().addClass('disabled');
		}
	} else {
		jQuery('#quick_adsense_settings_onpost_enable_global_style').parent().addClass('disabled');
		jQuery('#quick_adsense_settings_onpost_global_alignment').prop('disabled', true);
		jQuery('#quick_adsense_settings_onpost_global_margin').prop('disabled', true);
		for (var i = 1; i <= 10; i++) {
			jQuery('#quick_adsense_settings_onpost_ad_' + i + '_alignment').prop('disabled', false);
			jQuery('#quick_adsense_settings_onpost_ad_' + i + '_margin').prop('disabled', false);
			jQuery('#quick_adsense_settings_onpost_ad_' + i + '_alignment').parent().removeClass('disabled');
		}
	}
}
