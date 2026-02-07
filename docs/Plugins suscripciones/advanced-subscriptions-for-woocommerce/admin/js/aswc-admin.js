
(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	 $(document).ready(function() {
		// Avoid errors when the MDC library is not loaded on the page.
		const mdc = window.mdc || null;

		if ( mdc ) {
			if ( mdc.textField && mdc.textField.MDCTextField ) {
				const MDCText = mdc.textField.MDCTextField;
				[].map.call(document.querySelectorAll('.mdc-text-field'), function(el) {
					return new MDCText(el);
				});
			}

			if ( mdc.ripple && mdc.ripple.MDCRipple ) {
				const MDCRipple = mdc.ripple.MDCRipple;
				[].map.call(document.querySelectorAll('.mdc-button'), function(el) {
					return new MDCRipple(el);
				});
			}

			if ( mdc.switchControl && mdc.switchControl.MDCSwitch ) {
				const MDCSwitch = mdc.switchControl.MDCSwitch;
				[].map.call(document.querySelectorAll('.mdc-switch'), function(el) {
					return new MDCSwitch(el);
				});
			}
		}

        $(document).on('click','.aswc-password-hidden', function() {
            if ($('.aswc-form__password').attr('type') == 'text') {
                $('.aswc-form__password').attr('type', 'password');
            } else {
                $('.aswc-form__password').attr('type', 'text');
            }
        });
		
	});

	$(window).load(function(){
		// add selectWoo for multiselect.
		if ( $( document ).find( '.aswc-defaut-multiselect' ).length > 0 ) {
			$( document ).find( '.aswc-defaut-multiselect' ).selectWoo();
		}
	});

	// Note: This jQuery(document).ready block appears incomplete and has been commented out
	// to fix syntax errors. It references undefined variables (clientID, clientSecret, data).
	// Uncomment and fix if this functionality is needed.
	/*
	jQuery(document).ready(function() {
		if ( ! clientID && ! clientSecret ) {
			alert( aswc_admin_param.empty_fields );
			return;
		}
		jQuery.ajax({
			type: 'post',
			dataType: 'json',
			url: aswc_admin_param.ajaxurl,
			data: data,
			success: function(data) {
				alert( data.msg );
			}
		});
	});
	*/

	// Open API tab details.
	jQuery(document).ready(function(){

		jQuery('.aswc_rest_api_response').hide();
		jQuery('.aswc_rest_api_response').first().show();
		jQuery('.aswc_api_details_main_wrapper h4').first().addClass('active');

		jQuery(document).on('click','.aswc_api_details_main_wrapper h4', function(){
		jQuery(this).next('.aswc_rest_api_response').slideToggle(500);
			jQuery(this).toggleClass('active');
	})

	})

	//supported payment through js.
	jQuery(document).ready(function ($) {
		// Only run this on the WooCommerce > Settings > Payments tab
		if (typeof window.location.href !== 'undefined' && window.location.href.includes('page=wc-settings') && window.location.href.includes('tab=checkout')) {
			const interval = setInterval(function () {
		
				const $items = $('.woocommerce-item__payment-gateway');
				
				if ($items.length) {
					clearInterval(interval);
	
				
	
					$items.each(function () {
						
						let gatewayId = jQuery(this).attr('id');
	
						
	
let content = '';
if (gatewayId === 'stripe' || gatewayId === 'payfast' || gatewayId === 'amazon_payments_advanced' || gatewayId === 'woocommerce_payments' || gatewayId === 'ppcp-gateway' || gatewayId === 'authnet' || gatewayId === 'braintree_credit_card' || gatewayId === 'eway' || gatewayId === 'mollie_wc_gateway_' || gatewayId === 'mollie_stand_in' || gatewayId === 'multisafepay_' || gatewayId === 'payhere' || gatewayId === 'stripe_') {
// content = '<div class="custom-extra-info"> Supported Recurring Payment</div>';
content = '<div class="aswc_recurring_support_symbol"><img src="' + aswc_admin_param.recurring_payment_icon + '" alt="Supported" > ' + aswc_admin_param.Supported_recurring_payment + '</div>';
}
	
						$(this).find('.woocommerce-list__item-title').append(content);
					});
				}
			}, 1000);
		}

		// Filter subscription status dropdown to show only subscription-specific statuses.
		// This is a safety measure for HPOS when the PHP filter doesn't work.
		if (window.location.href.indexOf('aswc_subscriptions') !== -1 ||
		    jQuery('body').hasClass('post-type-aswc_subscriptions')) {

			var subscriptionStatuses = [
				'wc-active',
				'wc-on-hold',
				'wc-cancelled',
				'wc-expired',
				'wc-pending',
				'wc-paused',
				'wc-pending-cancel'
			];

			// Filter the status dropdown - try multiple selectors for different WooCommerce versions
			var statusSelectors = ['#order_status', 'select[name="order_status"]', '#post-status-select select'];

			statusSelectors.forEach(function(selector) {
				jQuery(selector + ' option').each(function() {
					var value = jQuery(this).val();
					if (value && subscriptionStatuses.indexOf(value) === -1) {
						jQuery(this).remove();
					}
				});
			});
		}

	}); // End document.ready

})( jQuery ); // End IIFE

var aswc_subscripiton_migration_success = function() {

		if ( aswc_admin_param.pending_product_count != 0 && aswc_admin_param.pending_orders_count != 0 && aswc_admin_param.pending_subscription_count != 0 ) {
			jQuery( "#aswc_migration-button" ).click();
			jQuery( "#aswc_migration-button" ).show();
		}else{
			jQuery( "#aswc_migration-button" ).hide();

		}
	}
