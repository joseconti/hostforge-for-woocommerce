'use strict';

import store from "../index";
import {addAction} from "@wordpress/hooks";
import {getCurrentStep} from "../../functions-step";
import {countProductsInCart, getCartItemByProduct, getCartTotals} from "../../functions-cart";
import {formatPrice} from "../../functions";
import {__} from "@wordpress/i18n";
import {removeErrorByCode} from "../slices/notices";
import {getStepError} from "../../functions-step";

export const stepController = {

	init: function () {
		addAction('ywsbs_before_load_next_box_step', 'ywsbs_subscription_box', this.checkStepConditions);
		addAction('ywsbs_before_add_product_to_box', 'ywsbs_subscription_box', this.checkStepConditionsBeforeAddToCart);
		addAction('ywsbs_product_added_to_box', 'ywsbs_subscription_box', this.updateStepError, 20);
		addAction('ywsbs_product_removed_from_box', 'ywsbs_subscription_box', this.resetQuantityError, 20);
	},

	checkStepConditions: (nextStep, currentStep) => {

		const state = store.getState();

		// Check first step limits
		if ( countProductsInCart(state, currentStep.id) < (currentStep?.rules?.min || 0) ) {
			throw {
				context: 'step',
				message: sprintf(__('Please select at least %s product(s) for this step', 'yith-woocommerce-subscription'), currentStep?.rules?.min),
				code: 'invalid_min_products'
			};
		}

		// Check conditions for last step.
		if ( 'cart' === nextStep?.id ) {

			if ( !countProductsInCart(state) ) {
				throw {
					context: 'step',
					message: __('Please add at least one product to this box', 'yith-woocommerce-subscription'),
					code: 'box_empty'
				};
			}

			if ( parseFloat(getCartTotals(state)?.subtotal) < parseFloat(ywsbs_subscription_box?.boxPriceRules?.min || 0) ) {
				throw {
					context: 'step',
					message: sprintf(__('Minimum amount required: %s', 'yith-woocommerce-subscription'), formatPrice(ywsbs_subscription_box?.boxPriceRules?.min)),
					code: 'invalid_box_min_price'
				};
			}
		}
	},

	checkStepConditionsBeforeAddToCart: (product) => {

		const state = store.getState();
		const step = getCurrentStep(state);

		// Check first step up limit
		if ( step?.rules?.max && (countProductsInCart(state, step.id) + 1) > step.rules.max ) {
			throw {
				context: 'product',
				message: sprintf(__('You can add a max of %s product(s) in this step', 'yith-woocommerce-subscription'), step.rules.max),
				code: 'invalid_max_products'
			};
		}

		// Check max unit limit
		const cartItem = getCartItemByProduct(state, product?.id);
		if ( step?.rules?.max_units && (cartItem?.quantity + 1) > step.rules.max_units ) {
			throw {
				context: 'product',
				message: sprintf(__('You can add a max of %s unit(s) of this product', 'yith-woocommerce-subscription'), step.rules.max_units),
				code: 'invalid_max_units_product'
			};
		}

		if ( ywsbs_subscription_box?.boxPriceRules?.max && (parseFloat(getCartTotals(state)?.subtotal || 0) + parseFloat(product?.price)) > parseFloat(ywsbs_subscription_box.boxPriceRules.max) ) {
			throw {
				context: 'product',
				message: sprintf(__('Maximum amount allowed: %s', 'yith-woocommerce-subscription'), formatPrice(ywsbs_subscription_box.boxPriceRules.max)),
				code: 'invalid_box_max_price'
			};
		}
	},

	updateStepError: () => {
		const state = store.getState();
		const step = getCurrentStep(state);
		const errorCode = getStepError(state)?.code;

		if (
			('invalid_min_products' === errorCode && countProductsInCart(state, step.id) >= (step?.rules?.min || 0)) ||
			('box_empty' === errorCode && countProductsInCart(state)) ||
			('invalid_box_min_price' === errorCode && parseFloat(getCartTotals(state)?.subtotal) >= parseFloat(ywsbs_subscription_box?.boxPriceRules?.min))
		) {
			store.dispatch(removeErrorByCode({code: errorCode}));
		}
	},

	resetQuantityError: (product) => {
		store.dispatch(removeErrorByCode({code: 'invalid_max_products'}));
		store.dispatch(removeErrorByCode({code: 'invalid_box_max_price'}));
		store.dispatch(removeErrorByCode({code: 'invalid_max_units_product', product: product?.id}));
	}
}