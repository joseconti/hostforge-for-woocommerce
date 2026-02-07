/* eslint-disable */
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

	 
	
        jQuery(document).ready(function() {
                var screen_id = aswc_admin_param.screen_id;

if ( 'woocommerce_page_wc-orders--aswc_subscriptions' === screen_id ) {
var blank_message = jQuery( '.woocommerce-BlankState-message' );
if ( blank_message.length ) {
blank_message.text( aswc_admin_blank_state.message );
jQuery( '.woocommerce-BlankState-cta' )
.text( aswc_admin_blank_state.button )
.attr( 'href', aswc_admin_blank_state.url );
}
}

                if( screen_id == 'aswc_subscriptions' || screen_id == 'edit-aswc_subscriptions' || screen_id == 'woocommerce_page_wc-orders--aswc_subscriptions' ){

			jQuery(document).on('change','#customer_user',function() {
				var user_id = jQuery( '#customer_user' ).val();
				jQuery('#aswc_parent_order_selection').html('');
				jQuery('#aswc_parent_order_selection').append('<option value="">Select an order</option>');
				jQuery('.edit_address').show();
				jQuery('.billing-same-as-shipping').show();
				
				if( user_id ){
					var data = {
						user_id: user_id,
						nonce: aswc_admin_param.aswc_auth_nonce,
						action: 'aswc_show_parent_order_for_custom_manual',
					}
					jQuery.ajax({
						type: 'post',
						dataType: 'json',
						url: aswc_admin_param.ajaxurl,
						data: data,
						success: function(response) {
							// console.log(response);
							
							jQuery('#aswc_parent_order_selection').append(response.html);
							
						}
					});
				}
			});
	
                $('.billing-same-as-shipping').on('click', function() {
                               // Copy billing fields to shipping fields
                               $('#_shipping_first_name').val($('#_billing_first_name').val());
                               $('#_shipping_last_name').val($('#_billing_last_name').val());
                               $('#_shipping_company').val($('#_billing_company').val());
                               $('#_shipping_address_1').val($('#_billing_address_1').val());
				$('#_shipping_address_2').val($('#_billing_address_2').val());
				$('#_shipping_city').val($('#_billing_city').val());
				$('#_shipping_state').val($('#_billing_state').val());
				$('#_shipping_postcode').val($('#_billing_postcode').val());
				$('#_shipping_country').val($('#_billing_country').val());
                });

               if ( screen_id == 'woocommerce_page_wc-orders--aswc_subscriptions' || screen_id == 'edit-aswc_subscriptions' ) {
                        $('.wp-list-table .column-order_number').each(function() {
                                var preview = $( this ).find( 'a.order-preview' );
                                var orderView = $( this ).find( 'a.order-view' );
                                if ( orderView.length && preview.length ) {
                                        orderView.after( preview );
                                }
                        });
                }
		}
	
		// Show the popup when the 'Update' button is clicked
		$('.update-subscription').on('click', function(e) {
			e.preventDefault();
			var subscriptionId = $(this).data('subscription_id');
			$('#subscription-id').val(subscriptionId); // Set subscription ID in the hidden field
			$('#update-subscription-popup').fadeIn();
		});
	
		// Close the popup when the 'X' is clicked
		$('.close-popup').on('click', function() {
			$('#update-subscription-popup').fadeOut();
		});

	
		var dateInput = $('#next-payment-date');
	
		// Get today's date in YYYY-MM-DD format
		var today = new Date();
		var day = String(today.getDate()).padStart(2, '0');
		var month = String(today.getMonth() + 1).padStart(2, '0'); // Months are 0-based
		var year = today.getFullYear();
	
		var todayDate = year + '-' + month + '-' + day;
	
		// Set the min attribute to today
		dateInput.attr('min', todayDate);
		
		
	
		// Handle form submission with AJAX
		$(document).on('click','#update-subscription-btn', function(e) {
			e.preventDefault();
	
			var subscriptionId = $('#subscription-id').val();
			var nextPaymentDate = $('#next-payment-date').val();
			var subscriptionPrice = $('#subscription-price').val();

			var today = new Date().toISOString().split('T')[0];

			if (nextPaymentDate !== '' && nextPaymentDate < today) {
				alert(aswc_admin_param.subscription_next_payment_date_error);
				return; // Stop further execution
			}

			if ( subscriptionPrice !== '' && (isNaN(subscriptionPrice) || parseFloat(subscriptionPrice) <= 0)) {
				alert(aswc_admin_param.subscription_price_error);
				return; // Stop further execution
			}
	
                        $.ajax({
                                url: ajaxurl, // Make sure to localize the AJAX URL in your WordPress setup
                                type: 'POST',
				data: {
					action: 'aswc_update_subscription_items',
					subscription_id: subscriptionId,
					next_payment_date: nextPaymentDate,
					subscription_price: subscriptionPrice,
					nonce: aswc_admin_param.aswc_auth_nonce
				},
				success: function(response) {
					if(response.success) {
						if(response.data.success) {
							alert(response.data.message); // Display the success message
						} else {
							alert(response.data.message); // Display message for no changes
						}
						window.location.reload();
					} else {
						alert('Error: ' + response.data.message); // Display error if any
					}
				},
				error: function() {
					alert('AJAX request failed. Please try again.');
				}
                        });
                });

               $( document ).on( 'click', '.aswc-preview-status-btn', function() {
                       var button = $( this );

                       if ( button.is( ':disabled' ) ) {
                               return;
                       }

                       var subscriptionId = button.data( 'subscription-id' );
                       var status = button.data( 'status' );

                       $.post(
                               aswc_admin_param.ajaxurl,
                               {
                                       action: 'aswc_change_subscription_status',
                                       subscription_id: subscriptionId,
                                       status: status,
                                       nonce: aswc_admin_param.aswc_auth_nonce
                               },
                               function( response ) {
                                       if ( response.success ) {
                                               alert( response.data.message );
                                               window.location.reload();
                                       } else {
                                               alert( response.data.message );
                                       }
                               }
                       );
               });



        });
	
	
})( jQuery );

