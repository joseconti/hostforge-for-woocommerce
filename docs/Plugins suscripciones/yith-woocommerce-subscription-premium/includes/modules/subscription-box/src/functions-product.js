'use strict';

import {BOX_ID} from "./config";
import {getCurrentStepId} from "./functions-step";
import {_x, sprintf} from "@wordpress/i18n";
import apiFetch from "@wordpress/api-fetch";
import {addQueryArgs} from '@wordpress/url';

// Get API get products url.
export const getProductsUrl = (data) => {

	const url = `yith-ywsbs/box-products/${BOX_ID}`;
	const searchParams = new URLSearchParams(data);

	return url + '?' + searchParams.toString();
}

// Maybe get product error.
export const getProductError = (state, product_id) => {
	const step_id = getCurrentStepId(state);
	return state.notices.errors.find(error => error.step_id === step_id && error.product_id === product_id);
}

// Build an array of product tabs for quick view.
export const getProductTabs = (product) => {
	let tabs = [];

	// Add description tab.
	if ( product?.description ) {
		tabs.push({
			id: 'description',
			label: _x('Description', 'Quick view product tab label', 'yith-woocommerce-subscription'),
		});
	}

	// Add additional info tab.
	if ( product?.attributes?.length ) {
		tabs.push({
			id: 'additional-info',
			label: _x('Additional information', 'Quick view product tab label', 'yith-woocommerce-subscription'),
		});
	}

	// Add additional info tab.
	if ( product?.rating_count ) {
		tabs.push({
			id: 'reviews',
			label: sprintf(_x('Reviews (%s)', 'Quick view product tab label', 'yith-woocommerce-subscription'), product.rating_count),
		});
	}

	return tabs;
}

// Get product reviews
export const fetchProductReviews = async (product, page = 1) => {

	let response = {};

	await apiFetch(
		{
			path: addQueryArgs(`yith-ywsbs/product-reviews/${product.id}`, {page: page}),
			parse: false
		})
		.then(remoteResponse => {
			// Parse headers.
			response.nextPage = (page < parseInt(remoteResponse.headers.get('X-WP-TotalPages'))) ? page + 1 : false;
			return remoteResponse.json()
		})
		.then(reviews => response.reviews = reviews)
		.catch(error => {
			console.error("Error:", error);
		});

	return response;
}
