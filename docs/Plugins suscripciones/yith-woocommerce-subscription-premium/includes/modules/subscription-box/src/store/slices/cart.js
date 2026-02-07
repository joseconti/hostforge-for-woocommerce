'use strict';

import {createAsyncThunk, createSlice} from '@reduxjs/toolkit'
import {createUUID, getCartInitialState,getCartItemsForRequest} from '../../functions-cart'
import {BOX_ID} from "../../config";
import apiFetch from "@wordpress/api-fetch";

export const updateCart = createAsyncThunk('cart/updateCart', async (_, {getState}) => {
	const items = getCartItemsForRequest(getState());
	// Merge with default
	let totals = {};

	await apiFetch(
		{
			path: '/yith-ywsbs/box-cart/update',
			method: 'POST',
			data: {
				box_id: BOX_ID,
				items,
			},
		})
		.then(res => {
			totals = res?.totals;
		})
		.catch(error => {
			console.error("Error:", error);
		});

	return totals;
});

export const cartSlice = createSlice({
	name: 'cart',
	initialState: getCartInitialState(),
	reducers: {
		addProduct: (cart, action) => {
			const product = action.payload.product;
			const stepId = action.payload.step_id;

			// search if product is already in cart.
			const cartItemId = createUUID(product.id, stepId);
			const cartItemIndex = typeof cart.items[stepId] != 'undefined' ? cart.items[stepId].findIndex( item => item.id === cartItemId) : -1;

			if ( -1 !== cartItemIndex ) {
				cart.items[stepId][cartItemIndex].quantity += 1;
				cart.items[stepId][cartItemIndex].total = parseFloat(product.price * cart.items[stepId][cartItemIndex].quantity);

			} else {

				if ( typeof cart.items[stepId] == 'undefined' )
					cart.items[stepId] = [];

				cart.items[stepId].push({
					id: cartItemId,
					quantity: 1,
					total: product.price,
					product: product
				});
			}
		},
		increaseQuantity: (cart, action) => {

			const itemId = action.payload.item_id;
			const stepId = action.payload.step_id;

			// search if product is already in cart.
			const cartItemIndex = typeof cart.items[stepId] != 'undefined' ? cart.items[stepId].findIndex( item => item.id === itemId) : -1;

			if ( -1 !== cartItemIndex ) {
				cart.items[stepId][cartItemIndex].quantity += 1;
				cart.items[stepId][cartItemIndex].total = cart.items[stepId][cartItemIndex].product.price * cart.items[stepId][cartItemIndex].quantity
			}
		},
		decreaseQuantity: (cart, action) => {

			const itemId = action.payload.item_id;
			const stepId = action.payload.step_id;

			// search if product is already in cart.
			const cartItemIndex = typeof cart.items[stepId] != 'undefined' ? cart.items[stepId].findIndex( item => item.id === itemId) : -1;

			if ( -1 !== cartItemIndex ) {
				if ( 1 === cart.items[stepId][cartItemIndex].quantity ) {
					cart.items[stepId].splice(cartItemIndex, 1,);
				} else {
					cart.items[stepId][cartItemIndex].quantity -= 1;
					cart.items[stepId][cartItemIndex].total = cart.items[stepId][cartItemIndex].product.price * cart.items[stepId][cartItemIndex].quantity
				}

				if ( ! cart.items[stepId].length ) {
					delete cart.items[stepId];
				}
			}
		},
		updateCartTotals: (cart, action) => {
			cart.totals = action.payload.totals;
		}
	},
	extraReducers(builder) {
		builder
			.addCase(updateCart.pending, (cart, action) => {
				cart.status = 'updating'
			})
			.addCase(updateCart.fulfilled, (cart, action) => {
				cart.status = 'updated'
				if ( !_.isEmpty(action.payload) ) {
					cart.totals = action.payload;
				}
			})
			.addCase(updateCart.rejected, (cart, action) => {
				cart.status = 'failed'
			})
	}
})

export const {addProduct, increaseQuantity, decreaseQuantity, updateCartTotals} = cartSlice.actions
export default cartSlice.reducer
