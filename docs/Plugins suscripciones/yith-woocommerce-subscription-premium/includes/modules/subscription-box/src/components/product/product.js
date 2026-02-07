'use strict';

import React from 'react';
import {useSelector, shallowEqual} from "react-redux";
import {decodeEntities} from "../../functions";
import {getCartItemByProduct} from "../../functions-cart"
import AddToCart from "../common/add-to-cart";
import ProductImage from "../common/product-image";
import QuantityInput from "../common/quantity-input";

export default function Product({product, openQuickView}) {

	const cartItem = useSelector(state => getCartItemByProduct(state, product.id), shallowEqual);
	const isInCart = typeof cartItem !== 'undefined';

	// Define product wrap class.
	let productClasses = ['ywsbs-box-product'];
	if ( isInCart ) {
		productClasses.push('in-cart');
	}

	return (
		<div className={productClasses.join(' ')}>
			<ProductImage image={product.images?.thumbnail} wrapperArgs={{'data-quantity': cartItem?.quantity}} onClick={() => {openQuickView(product)}}/>
			<div className="ywsbs-box-product-action">
				<span className="ywsbs-box-product-title" onClick={() => {openQuickView(product)}}>{decodeEntities(product.name)}</span>
				{!isInCart && <AddToCart product={product}/>}
			</div>
			{isInCart && <QuantityInput product={product}/>}
		</div>
	);
}