'use strict';

import {HAS_FIXED_PRICE,SESSION_ID} from "./config";
import {getCurrentStepId} from "./functions-step";

// Create unique ID for cart item.
export const createUUID = ( product_id, step_id ) => {
	return `${step_id}|${product_id}`;
};
// Get cart.
export const getCart = state => state.cart;
// Get cart totals array
export const getCartTotals = state => state.cart.totals;
// Get cart total
export const getCartTotal = state => state.cart.totals?.total;
// Get cart subtotal
export const getCartSubtotal = state => state.cart.totals?.subtotal;
// Get cart items
export const getCartItems = state => state.cart.items;
// Get cart items for step
export const getCartItemsByStep = (state, step_id) => {
	step_id = step_id || getCurrentStepId(state)
	return getCartItems(state)[step_id] || [];
}
// Get cart item
export const getCartItem = (state, item_id, step_id) => {
	return getCartItemsByStep(state, step_id)?.find(item => item.id === item_id);
}
// Find cart item index by ID and state.
export const getCartItemIndex = (state, item_id, step_id ) => {
	const itemsByStep = getCartItemsByStep(state, step_id);
	return typeof itemsByStep != 'undefined' ? itemsByStep.findIndex(item => item.id === item_id) : -1;
}
// Get cart item by product
export const getCartItemByProduct = (state, product_id, step_id) => {
	return getCartItemsByStep(state, step_id).find(item => item.product.id === product_id);
}
// Count products in cart by step or global
export const countProductsInCart = (state, step_id) => {
	const cartItems = getCartItems(state);
	let count = 0;

	for ( const itemsStep in cartItems ) {
		if ( step_id && itemsStep !== step_id ) {
			continue;
		}

		for ( const item of cartItems[itemsStep] ) {
			count += item.quantity;
		}
	}

	return count;
}
// Get cart initial state
export const getCartInitialState = () => {

	let items  = {};
	let initialState = ywsbs_subscription_box?.cartContent || getSessionCart();

	if ( initialState ) {
		for ( const itemsStep in initialState ) {
			items[itemsStep] = [];

			for ( const item of initialState[itemsStep] ) {
				item.id = createUUID( item.product.id, itemsStep );
				items[itemsStep].push(
					item
				);
			}
		}
	}

	return {
		items: items,
		totals: { subtotal: HAS_FIXED_PRICE ? ywsbs_subscription_box.boxPrice : calculateCartSubtotal(items) },
		status: 'needs-update'
	}
}
// Calculate cart subtotal
export const calculateCartSubtotal = items => {
	let subtotal = 0;
	for( const step_id in items ) {
		for ( const item of items[step_id] ) {
			subtotal += parseFloat( item.total );
		}
	}

	return subtotal;
}
// Get cart items for request
export const getCartItemsForRequest = state => {
	const cartItems = getCartItems(state);
	return formatCartItemsForRequest(cartItems);
}
// Format cart items for request
export const formatCartItemsForRequest = cartItems => {
	let preparedItems = {};

	for ( const itemsStep in cartItems ) {
		preparedItems[itemsStep] = [];

		for ( const item of cartItems[itemsStep] ) {
			preparedItems[itemsStep].push(
				{
					product: item.product.id,
					quantity: item.quantity,
					price: ! HAS_FIXED_PRICE ? item.product.price : null
				}
			);
		}
	}

	return preparedItems;
}
// Store current cart in session
export const getSessionCart = () => {
	const boxCartContent = sessionStorage?.getItem( SESSION_ID );
	return boxCartContent ? JSON.parse( boxCartContent ) : undefined;
}
// Store current cart in session
export const storeSessionCart = state => {
	sessionStorage?.setItem( SESSION_ID, JSON.stringify( getCartItems(state) ) );
}
// Delete current cart from session
export const deleteSessionCart = () => {
	sessionStorage?.removeItem( SESSION_ID );
}
