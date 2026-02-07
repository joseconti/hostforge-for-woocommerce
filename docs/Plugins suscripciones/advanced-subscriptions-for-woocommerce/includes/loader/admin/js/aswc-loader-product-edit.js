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

	$(function() {
		
		 $('#variable_product_options').on('change','.aswc_variation_enable', function() {
            $( this ).closest( '.woocommerce_variation' ).find( '.aswc_product' ).first().hide();

			if ( $( this ).is( ':checked' ) ) {
				$( this ).closest( '.woocommerce_variation' ).find( '.aswc_product' ).first().show();
				
				var dateToday = new Date(); 
				$(function() {
				$( ".aswc_subscription_start_date" ).datepicker({
					showButtonPanel: true,
					dateFormat: 'yy-mm-dd',
					minDate: dateToday
				});
				});

			}
        });

		
		$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function(event, needsUpdate) {
			needsUpdate = needsUpdate || false;
			var wrapper = $( '#woocommerce-product-data' );
			if ( ! needsUpdate ) {
				$( 'input.aswc_variation_enable', wrapper ).trigger( 'change' );
			}
			jQuery(document).find('[name^="aswc_variation_enbale_certain_month"]').each(function(index,element){
				var current_selection = $(this).attr('data-attr');
				
				if ( $( this ).is( ':checked' ) ) {
					jQuery(document).find('.aswc_certain_date_enable_wrap'+current_selection).removeClass('aswc_active');
				} else {
					jQuery(document).find('.aswc_certain_date_enable_wrap'+current_selection).addClass('aswc_active');
				}
				
			});
		});

		$('#woocommerce-product-data').on('keyup','[name^="aswc_variation_subscription_expiry_number"]',function() {
			
			var current_loop = $(this).attr('data-attr');
			if (current_loop != '') {
				var subscription_number = $('#aswc_variation_subscription_number'+current_loop ).val();
				$(this).prop('min', subscription_number );
			}

		});
		
		/*Subscription interval set*/
		$('#woocommerce-product-data').on('change','[name^="aswc_variation_subscription_interval"]',function(){
			var current_selection = $(this).val();
			var current_loop = $(this).attr('data-attr');
			var expiry_interval = $('#aswc_variation_subscription_expiry_interval'+current_loop );

            if ( current_selection == 'day' ) {
                 expiry_interval.empty();
                expiry_interval.append($('<option></option>').attr('value','day').text( aswc_product_param.day ) );
    
            }
            else if ( current_selection == 'week' ) {
                 expiry_interval.empty();
                expiry_interval.append($('<option></option>').attr('value','week').text( aswc_product_param.week ) );
               
            }
            else if( current_selection == 'month' ) {
                expiry_interval.empty();
               expiry_interval.append($('<option></option>').attr('value','month').text( aswc_product_param.month ) );
                
            }
            else if( current_selection == 'year' ) {
                expiry_interval.empty();
               expiry_interval.append($('<option></option>').attr('value','year').text( aswc_product_param.year ) );
            }

		});

		/*For simple product*/
		jQuery(document).on('change','#aswc_enbale_certain_month',function() {
			
			if ( $( this ).is( ':checked' ) ) {
				jQuery(document).find('.aswc_certain_date_enable_wrap').removeClass('aswc_active');
			} else {
				jQuery(document).find('.aswc_certain_date_enable_wrap').addClass('aswc_active');
			}
			
		});
		
		jQuery(document).on('change','#aswc_subscription_interval',function() {
			var current_selection = jQuery(this).val();
			if ( current_selection == 'week' ) {
				jQuery(document).find('.aswc_certain_date_enable_wrap').show();
				jQuery(document).find('.aswc_certain_date_enable').show();
				jQuery(document).find('.aswc_certain_date_enable_week').show();
				jQuery(document).find('.aswc_certain_date_enable_month').hide();
				jQuery(document).find('.aswc_certain_date_enable_year').hide();
			} 
			else if( current_selection=='month' ) {
				jQuery(document).find('.aswc_certain_date_enable_wrap').show();
				jQuery(document).find('.aswc_certain_date_enable').show();
				jQuery(document).find('.aswc_certain_date_enable_month').show();
				jQuery(document).find('.aswc_certain_date_enable_week').hide();
				jQuery(document).find('.aswc_certain_date_enable_year').hide();
			}
			else if( current_selection=='year' ) {
				jQuery(document).find('.aswc_certain_date_enable_wrap').show();
				jQuery(document).find('.aswc_certain_date_enable').show();
				jQuery(document).find('.aswc_certain_date_enable_year').show();
				jQuery(document).find('.aswc_certain_date_enable_week').hide();
				jQuery(document).find('.aswc_certain_date_enable_month').hide();
			}
			else{
				jQuery(document).find('.aswc_certain_date_enable_wrap').hide();
				jQuery(document).find('.aswc_certain_date_enable').hide();
			}
		});

		/*For variable product*/
		$('#woocommerce-product-data').on('change','[name^="aswc_variation_enbale_certain_month"]',function(){
			var current_selection = $(this).attr('data-attr');
			
			if ( $( this ).is( ':checked' ) ) {
				jQuery(document).find('.aswc_certain_date_enable_wrap'+current_selection).removeClass('aswc_active');
			} else {
				jQuery(document).find('.aswc_certain_date_enable_wrap'+current_selection).addClass('aswc_active');
			}
		});

		$('#woocommerce-product-data').on('change','[name^="aswc_variation_subscription_interval"]',function(){
			var current_selection = $(this).val();
			var current_loop = $(this).attr('data-attr');

			if ( current_selection == 'week' ) {
				jQuery(document).find('.aswc_certain_date_enable_wrap'+current_loop).show();
				jQuery(document).find('.aswc_certain_date_enable'+current_loop).show();
				jQuery(document).find('.aswc_certain_date_enable_week'+current_loop).show();
				jQuery(document).find('.aswc_certain_date_enable_month'+current_loop).hide();
				jQuery(document).find('.aswc_certain_date_enable_year'+current_loop).hide();
			} 
			else if( current_selection=='month' ) {
				jQuery(document).find('.aswc_certain_date_enable_wrap'+current_loop).show();
				jQuery(document).find('.aswc_certain_date_enable'+current_loop).show();
				jQuery(document).find('.aswc_certain_date_enable_month'+current_loop).show();
				jQuery(document).find('.aswc_certain_date_enable_week'+current_loop).hide();
				jQuery(document).find('.aswc_certain_date_enable_year'+current_loop).hide();
			}
			else if( current_selection=='year' ) {
				jQuery(document).find('.aswc_certain_date_enable_wrap'+current_loop).show();
				jQuery(document).find('.aswc_certain_date_enable'+current_loop).show();
				jQuery(document).find('.aswc_certain_date_enable_year'+current_loop).show();
				jQuery(document).find('.aswc_certain_date_enable_week'+current_loop).hide();
				jQuery(document).find('.aswc_certain_date_enable_month'+current_loop).hide();
			}
			else{
				jQuery(document).find('.aswc_certain_date_enable_wrap'+current_loop).hide();
				jQuery(document).find('.aswc_certain_date_enable'+current_loop).hide();
			}
		});
	
		//Set variable subscription expiry validation.
		$(document).on( 'keyup', '.aswc_variation_subscription_expiry_number', function() {
			var current_loop = $(this).attr('id').slice(-1);
			var subscription_number = $('#s'+current_loop ).val();
			var subscription_expiry = $(this).val();
			if ( subscription_expiry != '' ) {
				if ( Number( subscription_expiry ) < Number( subscription_number ) ) {
					alert( aswc_wcfm_param.expiry_notice );
				}
			}
		});
	});
	
	jQuery( window ).load( function() {
		if( jQuery(document).find('#aswc_enbale_certain_month').is( ':checked' ) ) {
			jQuery(document).find('.aswc_certain_date_enable_wrap').removeClass('aswc_active');
		}
		else{
			jQuery(document).find('.aswc_certain_date_enable_wrap').addClass('aswc_active');
		}
	});

	})( jQuery );