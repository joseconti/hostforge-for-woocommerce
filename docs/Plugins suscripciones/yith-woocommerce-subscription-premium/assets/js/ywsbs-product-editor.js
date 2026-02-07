/**
 * ywsbs-product-editor.js
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Subscription
 * @version 1.0.0
 */

jQuery(function ($) {

	const subscriptionChecked = function () {
		const tab = $('.subscription-settings_tab');
		const panel = $('#ywsbs_subscription_settings');
		if ( $('input[name="_ywsbs_subscription"]').is(':checked') ) {
			tab.show();
		} else {
			tab.hide();
			// if panel is visible, die it and switch to general.
			if ( panel.is(':visible') ) {
				panel.hide();
				$('.general_tab a').click();
			}
		}
	}

	// Open or close the subscription panel for single products.
	$('#_ywsbs_subscription').on('change', subscriptionChecked).change();

	// Open or close the subscription panel for variation products.
	$(document).on('change', '.checkbox_ywsbs_subscription', function () {

		const panel = $(this).closest('.woocommerce_variable_attributes').find('.ywsbs-product-metabox-options-panel');
		if ( $(this).is(':checked') ) {
			panel.slideDown('slow');
		} else {
			panel.slideUp('slow');
		}

		countSubscriptionVariation();
	});

	// Listen form input change.
	$(document).on('change', '.ywsbs_price_time_option, .variable_ywsbs_subscription', function () {
		var timeOption = $(this),
			panel = timeOption.closest('.ywsbs-product-metabox-options-panel'),
			selected = $('option:selected', timeOption),
			timeOptionVal = timeOption.val();

		panel.find('.max-length-time-opt').text(selected.data('text'));

		$(document).trigger('ywsbs_price_time_option_changed', [timeOption, timeOptionVal])
	});

	// Handle the option dependencies.
	$(document).on('change', '.ywsbs-product-metabox-options-panel :input', function (ev) {
		var input = $(this),
			inputType = input.attr('type'),
			inputName = input.attr('name');

		// If is radio and input is not checked skip
		if ( 'radio' === inputType && !input.is(':checked') ) {
			return false;
		}

		// Search deps.
		var depFields = $(document).find('.ywsbs-product-metabox-options-panel [data-deps-on="' + inputName + '"]');
		if ( !depFields.length ) {
			return false;
		}

		var inputVal = 'checkbox' === inputType ? (input.is(':checked') ? 'yes' : 'no') : input.val();

		$.each(depFields, function () {
			let depValues = $(this).data('deps-val').split('|'),
				depEffect = $(this).data('deps-effect') ?? 'fade';

			if ( -1 !== $.inArray(inputVal, depValues) ) {
				switch (depEffect) {
					case 'fade':
						$(this).fadeIn();
						break;
					case 'slide':
						$(this).slideDown('slow');
						break;
					case 'plain':
						$(this).show();
						break;
				}
			} else {
				switch (depEffect) {
					case 'fade':
						$(this).fadeOut();
						break;
					case 'slide':
						$(this).slideUp('slow');
						break;
					case 'plain':
						$(this).hide();
						break;
				}
			}

			$(this).change();
		});
	});

	// Trigger input change.
	$(document).find('.ywsbs-product-metabox-options-panel :input').change();

	function countSubscriptionVariation() {
		var subscriptionVariations = $(document).find('.woocommerce_variable_attributes input.checkbox_ywsbs_subscription:checked').length;

		var switchPriority = $(document).find('.switchable_priority');

		$.each(switchPriority, function () {
			var $t = $(this);
			var currentValue = parseInt($t.val());

			var $html = '';
			var i;
			for (i = 0; i < subscriptionVariations; i++) {
				var counter = parseInt(i);
				var optionSelected = (currentValue === counter) ? '" selected="selected"' : '"';
				$html += '<option value="' + counter + optionSelected + '>' + (counter + 1) + '</option>';
			}

			$t.html($html);

		});
	}

	$('#woocommerce-product-data').on('woocommerce_variations_loaded', function () {
		$(document).find('.checkbox_ywsbs_subscription').change();
		$(document).find('.ywsbs-product-metabox-options-panel :input').change();
	});


	const subscriptionTabsVisibility = function () {

		let mainTab = $(document).find('.subscription-settings_tab'),
			subTabs = $(document).find('.ywsbs-sub-tab'),
			hasSubTabs = !! subTabs.filter(':not(.subscription-settings-sub_tab):visible').length;

		if ( hasSubTabs ) {
			mainTab.addClass('ywsbs-has-sub-tab')
			subTabs.removeClass('last-child');

			subTabs.filter(':visible').last().addClass('last-child');
		} else {
			mainTab.removeClass('ywsbs-has-sub-tab')
		}

		$(document).find('.subscription-settings-sub_tab').toggle(hasSubTabs);
	}

	$(document.body).on('woocommerce-product-type-change', subscriptionTabsVisibility);
	subscriptionTabsVisibility();
});
