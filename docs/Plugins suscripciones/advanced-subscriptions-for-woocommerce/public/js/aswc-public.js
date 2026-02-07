(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
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
	jQuery(document).ready(function($) {
		$('.aswc_subs_box-button').on('click', function(e) {
			e.preventDefault();

			
			$('#aswc_subs_box-popup').css('display','flex');
			$('#aswc_subs_box-popup').fadeIn();

			
		});

		$('.aswc_subs_box-close, #aswc_subs_box-popup').on('click', function() {
			$('#aswc_subs_box-popup').fadeOut();
		});

		$('.aswc_subs_box-content').on('click', function(e) {
			e.stopPropagation();
		});

		$('.aswc_sub_box_prod_add_btn').on('click', function(e) {
			e.preventDefault();
			let $btn = $(this);
			let $input = $btn.prev('.aswc_sub_box_prod_count'); // Get input field
			let $minusBtn = $input.prev('.aswc_sub_box_prod_minus_btn'); // Get minus button
			let count = parseInt($input.val()) || 0;

			var aswc_sub_box_price = $btn.prev('.aswc_sub_box_prod_count').data('aswc_sub_box_price'); 

			var aswc_subscription_box_price = $('.aswc-sb-cta-total').data('aswc_subscription_box_price');
			
			// Get the existing price from the span and convert it to a number
			if( aswc_subscription_box_price == 0 ){

				var existing_price = parseFloat($('.aswc-sb-cta-total span').text()) || 0;
				
				// Calculate the new total price
				var aswc_sub_box_total = existing_price + aswc_sub_box_price;
				
				// Update the span with the new total
				$('.aswc-sb-cta-total span').text(aswc_sub_box_total.toFixed(2));
			}
			
			
				count = count + 1;
				$input.val(count).show(); // Update and show input
				$minusBtn.show(); // Show minus button
			
		});

		$('.aswc_sub_box_prod_minus_btn').on('click', function(e) {
			e.preventDefault();
			let $btn = $(this);
			let $input = $btn.next('.aswc_sub_box_prod_count'); // Get input field
			let count = parseInt($input.val()) || 0;
			let $plusbtn = $('.aswc_sub_box_prod_add_btn');
			$plusbtn.show(); 

			var aswc_sub_box_price = $btn.next('.aswc_sub_box_prod_count').data('aswc_sub_box_price'); 

			var aswc_subscription_box_price = $('.aswc-sb-cta-total').data('aswc_subscription_box_price');
			
			// Get the existing price from the span and convert it to a number
			if( aswc_subscription_box_price == 0 ){

				var existing_price = parseFloat($('.aswc-sb-cta-total span').text()) || 0;
				
				// Calculate the new total price
				var aswc_sub_box_total = existing_price - aswc_sub_box_price;
				
				// Update the span with the new total
				$('.aswc-sb-cta-total span').text(aswc_sub_box_total.toFixed(2));
			}
			
				count = count - 1;
				$input.val(count) // Hide input when 0
				if( count == 0 ){
					$input.val(count).hide(); 
					$btn.hide(); // Hide minus button
				}
			
		});

		

		//new code.
		$('#aswc_subs_box-form').on('submit', function (e) {
			e.preventDefault();
		
			let aswc_subscription_product_id = $('.aswc_subscription_product_id').data('subscription-box-id');
		
			let formData = {
				products: [],
				total: $('.aswc-sb-cta-total>span').text(),
				aswc_subscription_product_id: aswc_subscription_product_id,
			};
		
			$('.aswc_sub_box_prod_container .aswc_sub_box_prod_item').each(function () {
				let container = $(this);
				let productId = container.find('.aswc_sub_box_prod_add_btn').data('product-id');
				let quantity = container.find('.aswc_sub_box_prod_count').val();
		
				if (productId && quantity > 0) {
					formData.products.push({
						product_id: productId,
						quantity: quantity
					});
				}
			});
		
			if (formData.products.length === 0) {
				$('.aswc_subscription_box_error_notice').text('No products selected. Please add at least one product.').show();
				return;
			}
		
			$.ajax({
				url: aswc_public_param.ajaxurl,
				type: 'POST',
				data: {
					action: 'aswc_handle_subscription_box',
					subscription_data: JSON.stringify(formData),
					nonce: aswc_public_param.aswc_public_nonce,
				},
				success: function (response) {
					console.log('Server Response:', response);
		
					if (response.message === "Subscription added to cart!") {
						window.location.href = aswc_public_param.cart_url; // Redirect to cart page
					} else {
						$('.aswc_subscription_box_error_notice').text(response.data || 'Something went wrong.').show();
					}
				},
				error: function (error) {
					console.error('Error:', error);
					$('.aswc_subscription_box_error_notice').text('Failed to process request.').show();
				}
			});
		});
			
			

			jQuery(document).ready(function($) {
				$(document).on('click','.aswc_show_customer_subscription_box_popup', function(e) {
					e.preventDefault();
					$(this).next('.aswc-attached-products-popup').addClass('active_customer_popup');
				});
				$(document).on('click','.aswc_customer_close_popup', function(e) {
					$(this).parent('.aswc-attached-products-popup').removeClass('active_customer_popup');
				});
			});
	});
})( jQuery );
