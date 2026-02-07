/**
 * ywsbs-admin.js
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Subscription
 * @version 1.0.0
 */

jQuery(function ($) {

	$('[data-deps]').each(function () {

		var t = $(this),
			wrap = t.closest('.yith-plugin-fw__panel__option'),
			deps = t.attr('data-deps').split(','),
			values = t.attr('data-deps_value').split(','),
			conditions = [];

		$.each(deps, function (i, dep) {
			$('[name="' + dep + '"]').on('change', function () {

				var value = this.value,
					check_values = '';

				// exclude radio if not checked
				if ( 'radio' === this.type && !$(this).is(':checked') ) {
					return;
				}

				if ( 'checkbox' === this.type ) {
					value = $(this).is(':checked') ? 'yes' : 'no';
				}

				check_values = values[i] + ''; // force to string
				check_values = check_values.split('|');
				conditions[i] = $.inArray(value, check_values) !== -1;

				if ( $.inArray(false, conditions) === -1 ) {
					wrap.fadeIn();
				} else {
					wrap.fadeOut();
				}

			}).change();
		});
	});

	var manageFailedPaymentOptionDep = function () {
		$(document).on('change', '#ywsbs_change_status_after_renew_order_creation_status', function () {
			var $t = $(this),
				current_status = $t.val();

			$(document).find('.show-if-overdue').hide();
			$(document).find('.show-if-suspended').hide();
			$(document).find('.show-if-cancelled').hide();
			$(document).find('.show-if-' + current_status).show();

		});

		$(document).on('change', '#ywsbs_change_status_after_renew_order_creation_step_2_status', function () {
			var $t = $(this),
				current_status = $t.val(),
				div_to_change = $(document).find('.show-if-no-cancelled-step-2');

			if ( 'cancelled' === current_status ) {
				div_to_change.hide();
			} else {
				div_to_change.show();
			}
		});


		$('#ywsbs_change_status_after_renew_order_creation_status').change();
		$('#ywsbs_change_status_after_renew_order_creation_step_2_status').change();
	}

	var managePanelStyle = function () {
		// remove table row padding on subscription status when a payment failed.
		$(document).find('.without-padding').closest('tr').find('td').css({padding: '0 20px 30px 20px'});

		// add a general wrapper inside the custom list table.
		var activitiesWrapper = $(document).find('.wrap.ywsbs_subscription_activities');
		if ( activitiesWrapper.length > 0 ) {
			activitiesWrapper.closest('.wrap.yith-plugin-ui').addClass('yith-plugin-fw-wp-page-wrapper').addClass('yith-current-subtab-opened').removeClass('wrap');
		}
	}

	manageFailedPaymentOptionDep();
	managePanelStyle();


	$('#ywsbs_change_status_after_renew_order_creation_status').on('change', function () {
		var $t = $(this);
		if ( $t.val() == 'overdue' ) {
			$('.hide-overdue').hide();
			$('.renew_order_step1').closest('tr').addClass('no-padding-bottom');
		} else {
			$('.hide-overdue').show();
			$('.renew_order_step1').closest('tr').removeClass('no-padding-bottom');
		}
	}).change();

	$('#ywsbs_subscription_action_style').on('change', function () {
		var $t = $(this),
			can_be_cancelled = $('#ywsbs_allow_customer_cancel_subscription').is(':checked');

		if ( 'dropdown' === $t.val() && can_be_cancelled ) {
			$('[data-dep-target="ywsbs_text_cancel_subscription_dropdown"]').show();
		} else {
			$('[data-dep-target="ywsbs_text_cancel_subscription_dropdown"]').hide();
		}
	});

	$('#post-query-submit').on('click', function (e) {
		e.preventDefault();
		window.onbeforeunload = null;
		$(this).closest('form').submit();
	});

	$(document).on('click', '.on-off-module .on_off', function () {

		const
			input = jQuery(this),
			wrap = input.closest('.module'),
			module = input.attr('data-module');

		let data = {
			action: 'ywsbs_module_activation_switch',
			security: ywsbs_admin.modules_nonce,
			module,
		};

		// Make sure the WC alert is removed
		window.onbeforeunload = '';

		$.ajax({
			url: ywsbs_admin.ajaxurl,
			data: data,
			type: 'POST',
			beforeSend: function () {
				wrap.block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6,
					}
				});
			},
			success: function (res) {
				window.onbeforeunload = '';
				window.location.reload();
			}
		});
	});

	// payment methods warning modal.
	if ( typeof yith.ui != 'undefined' ) {

		jQuery('#ywsbs-payment-methods-warning a.open-modal').on('click', function (event) {
			event.preventDefault();

			let template = wp.template('ywsbs-payment-methods-warning-modal');

			yith.ui.modal(
				{
					title: '',
					content: template(),
					footer: '',
					width: 600,
					closeSelector: '.ywsbs-payment-methods-warning-modal__close',
					classes: {
						main: 'ywsbs-payment-methods-warning-modal'
					}
				}
			);
		});
	}

	( function() {
		var scrollTarget = jQuery( '.yith-plugin-ui__wp-list-auto-h-scroll__scrollable' );
		// Check if scrollTarget exists.
		// Check if target is heighter than the window.
		if ( ! scrollTarget.length || ( scrollTarget.offset().top + scrollTarget.height() ) < jQuery(window).height() ) {
			return false;
		}

		// Add scrollable mirror elem.
		scrollTarget.parent().before( '<div class="yith-plugin-ui__wp-list-upper-scrollable"><div class="yith-plugin-ui__wp-list-upper-scrollable-content"></div></div>' );
		var  scrollMirror = $(document).find( '.yith-plugin-ui__wp-list-upper-scrollable' );

		$( window ).on( 'resize', function() {
			scrollMirror.find('.yith-plugin-ui__wp-list-upper-scrollable-content').css( { minWidth: scrollTarget.find('.wp-list-table').outerWidth() + 'px' } )
		} ).trigger( 'resize' );

		scrollTarget.scroll(function(){
			scrollMirror.scrollLeft(scrollTarget.scrollLeft());
		});
		scrollMirror.scroll(function(){
			scrollTarget.scrollLeft(scrollMirror.scrollLeft());
		});

	} )();
});