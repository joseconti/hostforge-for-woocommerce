jQuery(function($){
	if ( ! window.wc ) {
		return;
	}
	var { registerCheckoutFilters } = window.wc.blocksCheckout;

        const aswcModifySubtotalPriceFormat = (
		defaultValue,
		extensions,
		args,
		validation
	) => {
		const isCartContext = args?.context === 'cart';
		
		if ( ! isCartContext ) {
			return defaultValue;
		}
	    const cartItem = args?.cartItem.item_data;
		var aswcPrice = '';
		if(cartItem != '' && cartItem != undefined){
	     aswcPrice = cartItem.find( item => item.name === 'aswc-price-html');
		}
	    if ( aswcPrice ) {
			val = aswcPrice?.value;
	        if ( val != '' ) {
				return defaultValue + ' ' + val;
	        }
	    }
		return defaultValue;
	};

        const aswcModifyCartItemPrice = (
		defaultValue,
		extensions,
		args,
		validation
	) => {
		const isCartContext = args?.context === 'cart' || args?.context === 'summary';
	
		if ( ! isCartContext ) {
			return defaultValue;
		}
		
            const cartItem = args?.cartItem.item_data;
                let switchDirectionData = '';
                if(cartItem != '' && cartItem != undefined){
                         switchDirectionData = cartItem.find( item => item.name === 'aswc-switch-direction');
		}

		// subscription box.
		if ( ! cartItem ) {
			return defaultValue;
		}
		const cartkey = cartItem.find( item => item.name === 'aswc_subscription_box_cart_key' );
		const cartIndex = cartItem.find( item => item.name === 'aswc_subscription_box_cart_index' );

		if ( cartkey && cartIndex ) {
			let cartKey = cartkey.value;
			jQuery.ajax({
                url: aswc_public_block.ajaxurl,
				type: "POST",
				data: {
					action: "aswc_get_cart_item",
					cart_key: cartKey,
                                          nonce: aswc_public_param.aswc_public_nonce,
				},
				success: function (response) {
					const cartBoxIndex = parseInt(cartIndex.value);
					if (response.success) {
						let attachedProducts = response.data.attached_products;
						if (attachedProducts.length > 0) {
							let attachedProductsHtml = `<div class="aswc-attached-products-popup">
								<strong>Attached Products:</strong><ul>`;
		
							attachedProducts.forEach(product => {
								attachedProductsHtml += `<li>
									<img src="${product.image}" width="40" height="40" />
									${product.name} x ${product.quantity}
								</li>`;
							});
		
							attachedProductsHtml += `</ul>
								<span class="aswc_customer_close_popup" style="cursor: pointer;">&times;</span>
							</div>`;

							const viewLabelClass = 'aswc_view_product_label_' + cartBoxIndex;
							const viewLabelSelector = '.' + viewLabelClass;
							const viewLabelHTML = '<a href="#" class="aswc_show_customer_subscription_box_popup">View Attached Products</a>' + attachedProductsHtml;

							// Try both checkout and cart rows
							const containers = [
								$(".wc-block-components-order-summary-item").eq(cartBoxIndex).find('.wc-block-components-product-name'),
								$(".wc-block-cart-items__row").eq(cartBoxIndex).find('.wc-block-cart-item__prices')
							];

							containers.forEach(container => {
								if (!container.length) return;

								let viewLabel = $(document).find(viewLabelSelector);

								if (viewLabel.length) {
									viewLabel.html(viewLabelHTML);
								} else {
									container.after(`<p class="${viewLabelClass}">${viewLabelHTML}</p>`);
								}
							});
						}
					}
				},
				error: function (error) {
					console.error("Error fetching cart item:", error);
				},
			});
		
			// ✅ Return only the default value (without extra HTML)
			return defaultValue;
		}
		// subscription box.
		
            if ( switchDirectionData ) {
                        val = switchDirectionData?.value;
	        if ( val != '' ) {
	           return defaultValue + ' ' + val;
	        }
	    }
		return defaultValue;
	};

	

	const modifyPlaceOrderButtonLabel = ( defaultValue, extensions, args ) => {

            if ( aswc_public_block.place_order_button_text ) {
                    return aswc_public_block.place_order_button_text;
		}
		return defaultValue;
	};

	
	registerCheckoutFilters( 'aswc-checkout-block', {
            subtotalPriceFormat: aswcModifySubtotalPriceFormat,
            cartItemPrice: aswcModifyCartItemPrice,
		placeOrderButtonLabel: modifyPlaceOrderButtonLabel,
	} );
});
