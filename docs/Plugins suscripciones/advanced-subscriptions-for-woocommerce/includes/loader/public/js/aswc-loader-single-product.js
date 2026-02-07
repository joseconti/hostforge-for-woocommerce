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

		if ($(document).find( 'div.product-type-variable' ).length > 0 ) {
			
			$(document).on( 'found_variation', function( event, variation_data ) {
				
				if ( variation_data.aswc_first_payment_html != undefined ) {
					$(document).find('.aswc_sync_fist_payment_date').html( variation_data.aswc_first_payment_html );
				} else {
					$(document).find('.aswc_sync_fist_payment_date').html('');
				}

				if ( variation_data.aswc_start_payment_html != undefined ) {
					$(document).find('.aswc_start_date').html( variation_data.aswc_start_payment_html );
				} else {
					$(document).find('.aswc_start_date').html('');
				}
			});
			$(document).on( 'reset_data', function( event, variation_data) {
				$(document).find('.aswc_sync_fist_payment_date').html('');
				$(document).find('.aswc_start_date').html('');


			});
		} 
		jQuery( document ).on('change','.variation_id',function() {
			var variation_id = jQuery(this).val();
			if ( aswc_pro_public_param.aswc_is_expiry_enable == '1' ) {
				if( variation_id != '' ) {

					jQuery.ajax({
						url: aswc_pro_public_param.ajaxurl,
						type: "POST",
						dataType :'json',
						data: {
							'action': 'aswc_variation_expiry',
							'variation_id' : variation_id,
							'aswc_nonce': aswc_pro_public_param.aswc_nonce
						},
						success:function(response) {
							
				            if ( response.result ) {
				            	var expiry_interval = $('#aswc_expiry_number_interval');
				            	jQuery(document).find('.aswc_expiry_interval_field_wrap').show();
				            	var current_selection = response.aswc_interval;
				            	
				            	if ( current_selection == 'day' ) {
					                 expiry_interval.empty();
					                 expiry_interval.append($('<option></option>').attr('value','day').text( aswc_pro_public_param.day ) );
					    
					            }
					           else if ( current_selection == 'week' ) {
					                 expiry_interval.empty();
					                 expiry_interval.append($('<option></option>').attr('value','week').text( aswc_pro_public_param.week ) );
				
					           }
					           else if ( current_selection == 'month' ) {
					                 expiry_interval.empty();
					                 expiry_interval.append($('<option></option>').attr('value','month').text( aswc_pro_public_param.month ) );
				
					           }
					           else if ( current_selection == 'year' ) {
					                 expiry_interval.empty();
					                 expiry_interval.append($('<option></option>').attr('value','year').text( aswc_pro_public_param.year ) );
				
					           }

				            } else {
				          
				            	jQuery(document).find('.aswc_expiry_interval_field_wrap').hide();
				            }
						}
					});
				}
			}
			
		});
		

	});
})( jQuery );