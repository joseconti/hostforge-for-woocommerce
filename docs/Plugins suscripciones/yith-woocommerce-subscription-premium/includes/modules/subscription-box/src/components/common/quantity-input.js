'use strict';

import React from "react";
import {shallowEqual, useDispatch, useSelector} from "react-redux";
import {decreaseQuantity} from "../../store/slices/cart";
import AddToCart from "./add-to-cart";
import {getCartItemByProduct} from "../../functions-cart";
import {_x} from "@wordpress/i18n";

export default function QuantityInput({product, compact}) {

	const dispatch = useDispatch();
	const cartItem = useSelector( state => getCartItemByProduct(state, product.id), shallowEqual );

	const cartItemQuantity = cartItem?.quantity || 0;

	const handleDecreaseQuantity = event => {
		event.stopPropagation();
		cartItemQuantity && dispatch(decreaseQuantity({item_id:cartItem.id}));
	}

	return (

		<div className="ywsbs-box-product-cart-action">
			{ ! compact && (
				<div className={"ywsbs-box-item-quantity"}>
					<div className="ywsbs-box-product-qty-label">{_x('Quantity', 'Box product cart quantity label', 'yith-woocommerce-subscription')}</div>
					<div className="ywsbs-box-product-qty">{cartItemQuantity}</div>
				</div>
			)}
			<div className="ywsbs-box-item-quantity-input">
				<span className="ywsbs-box-product-remove" onClick={handleDecreaseQuantity}>
					<i className="ywsbs-box-icon-minus"></i>
				</span>
					{compact && <span className="ywsbs-box-item-quantity">{cartItemQuantity}</span>}
					<AddToCart product={product}/>
			</div>
		</div>
	);
}