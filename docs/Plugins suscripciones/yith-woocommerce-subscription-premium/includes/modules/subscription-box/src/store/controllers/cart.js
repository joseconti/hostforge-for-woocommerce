'use strict';

import store from "../index";
import {HAS_FIXED_PRICE} from "../../config";
import {calculateCartSubtotal, getCartItemByProduct, getCartItems, storeSessionCart} from "../../functions-cart";
import {updateCartTotals} from "../slices/cart";
import {addAction} from "@wordpress/hooks";
import {__} from "@wordpress/i18n";
import {removeErrorByCode} from "../slices/notices";

export const cartController = {

	init: function () {
		// Handle add/remove product from cart
		addAction('ywsbs_before_add_product_to_box', 'ywsbs_subscription_box', this.validateAddToCart);
		addAction('ywsbs_product_removed_from_box', 'ywsbs_subscription_box', this.emptyProductCartError);

		// Handle after cart changed.
		addAction('ywsbs_product_added_to_box', 'ywsbs_subscription_box', this.afterCartChanged );
		addAction('ywsbs_product_removed_from_box', 'ywsbs_subscription_box', this.afterCartChanged );
	},

	validateAddToCart: (product) => {
		// Check if can I add this product to cart. Throw exception in case of error.
		// Check stock quantity.
		if ( product.stock_quantity !== null ) {

			const stockQuantity = parseInt(product.stock_quantity);
			if ( 0 === stockQuantity ) {
				throw {
					context: 'product',
					message: __('You cannot add this product to the box because it is out of stock.', 'yith-woocommerce-subscription'),
					code: 'product_out_of_stock'
				};
			}

			// Check if product is still on cart.
			const cartItem = getCartItemByProduct(store.getState(), product.id);
			const quantity = cartItem?.quantity || 0;

			if ( (quantity + 1) > product.stock_quantity ) {
				throw {
					context: 'product',
					message: sprintf(__('You cannot add more than %s to the box because there isn\'t enough stock.', 'yith-woocommerce-subscription'), quantity),
					code: 'product_not_enough_stock'
				};
			}
		}
	},

	afterCartChanged: () => {
		// Store cart in session.
		storeSessionCart(store.getState());

		HAS_FIXED_PRICE || cartController.calculateBoxSubtotal();
	},

	calculateBoxSubtotal: () => {
		const items = getCartItems(store.getState());
		let subtotal = calculateCartSubtotal(items);
		store.dispatch(updateCartTotals({totals: {subtotal}}));
	},

	emptyProductCartError: (product) => {
		store.dispatch(removeErrorByCode({code: 'product_out_of_stock', product: product?.id}));
		store.dispatch(removeErrorByCode({code: 'product_not_enough_stock', product: product?.id}));
	}
}