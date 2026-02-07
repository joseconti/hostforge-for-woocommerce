'use strict';

import {getCurrentStep, getCurrentStepId, getNextStep} from "../../functions-step";
import {getCartItem} from "../../functions-cart";
import {doAction} from "@wordpress/hooks";

export const actionsMiddleware = ({getState}) => {
	return next => async action => {

		const state = getState();
		const currentBoxStep = getCurrentStep(state);
		let returnValue, product;

		try {

			switch (action.type) {
				case 'steps/nextStep':

					let nextBoxStep = getNextStep(state);

					doAction('ywsbs_before_load_next_box_step', nextBoxStep, currentBoxStep);

					returnValue = next(action);

					doAction('ywsbs_after_load_next_box_step', nextBoxStep, currentBoxStep);

					break;

				case 'cart/addProduct':
				case 'cart/increaseQuantity':

					product = action.payload?.item_id ? getCartItem(state, action.payload.item_id)?.product : action.payload?.product;

					doAction('ywsbs_before_add_product_to_box', product);

					// Since this action can be performed only on current step, add step id to action payload.
					action.payload.step_id = getCurrentStepId(state);

					returnValue = next(action);

					doAction('ywsbs_product_added_to_box', product);

					break;

				case 'cart/decreaseQuantity':

					product = getCartItem(state, action.payload.item_id)?.product;

					doAction('ywsbs_before_remove_product_from_box', product);

					// Since this action can be performed only on current step, add step id to action payload.
					action.payload.step_id = getCurrentStepId(state);

					returnValue = next(action);

					doAction('ywsbs_product_removed_from_box', product);

					break;

				case 'cart/updateCartTotals':

					returnValue = next(action);

					doAction('ywsbs_box_cart_totals_updated');

					break;

				default:
					returnValue = next(action);
					break;
			}

		} catch (error) {
			return next({
				type: 'notices/addError',
				payload: {step_id: currentBoxStep?.id, product_id: product?.id, ...error}
			});
		}

		return returnValue;
	}
}