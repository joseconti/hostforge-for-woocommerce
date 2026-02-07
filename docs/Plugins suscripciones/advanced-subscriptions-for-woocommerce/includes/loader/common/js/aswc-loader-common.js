(function( $ ) {
	'use strict';

	/**
	 * All of the code for your common JavaScript source
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

})( jQuery );
jQuery(document).ready(function($){
	// saving the data while selecting between one time and subscription for simple product
	jQuery( document ).on( 'click', '.aswc_check_simple_cart_one_time_purchase' ,function() {
		jQuery(this).parents('.aswc_subscription_wrapper').find('.aswc_check_simple_cart_subscription_purchase').prop('checked', false );
		subscription_or_ontime_check( jQuery(this) );
	});
	//simle product subscription enable
	jQuery( document ).on( 'click', '.aswc_check_simple_cart_subscription_purchase', function() {
		jQuery(this).parents('.aswc_subscription_wrapper').find('.aswc_check_simple_cart_one_time_purchase').prop('checked', false );
		subscription_or_ontime_check( jQuery(this) );
	});
	// saving the data while selecting between one time and subscription for variable product
	jQuery(document).on('click','.aswc_check_variartion_cart_one_time_purchase',function(){
		jQuery(this).parents('.aswc_subscription_wrapper').find('.aswc_check_variable_cart_subscription_purchase').prop('checked', false );
		subscription_or_ontime_check( jQuery(this) );
	});
	jQuery(document).on('click','.aswc_check_variable_cart_subscription_purchase',function(){
		jQuery(this).parents('.aswc_subscription_wrapper').find('.aswc_check_variartion_cart_one_time_purchase').prop('checked', false );
		subscription_or_ontime_check( jQuery(this) );
	});

	function subscription_or_ontime_check( this_object ) {
		var data = {
			id : this_object.data('id'),
			type : this_object.data('pro_type'),
			checked : this_object.is(':checked'),
			action: 'aswc_onetime_purchase',
			security: aswc_common_param.nonce,
		};
		jQuery.ajax({
			url: aswc_common_param.ajaxurl,
			type: 'POST',
			data: data,
			success: function (response) {
			}
		})
	}
	
});
 