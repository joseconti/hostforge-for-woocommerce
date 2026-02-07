'use strict';

import React, {useEffect} from 'react';
import {useDispatch, useSelector} from "react-redux";
import {updateCart} from "../../store/slices/cart";
import {getCart, countProductsInCart} from "../../functions-cart";
import {__, _n, sprintf} from '@wordpress/i18n';
import CartItems from "./cart-items";
import Price from "../common/price";
import Submit from "./submit";
import Loader from "../loader";
import parse from "html-react-parser";

export default function Cart() {

	const dispatch = useDispatch();
	const cart = useSelector( getCart );
	const cartItemsCount = useSelector( countProductsInCart );
	const cartSummary = [
		{ id: 'subtotal', label: sprintf(_n( '%s product', '%s products', cartItemsCount, 'yith-woocommerce-subscription'), cartItemsCount) },
		{ id: 'shipping', label: sprintf( '%s %s', __( 'Shipping', 'yith-woocommerce-subscription'), ywsbs_subscription_box?.boxDeliveryInfo) },
		{ id: 'taxes', label: __( 'Tax', 'yith-woocommerce-subscription') },
		{ id: 'discount', label: __( 'Discount', 'yith-woocommerce-subscription') },
		{ id: 'total', label: __( 'Total', 'yith-woocommerce-subscription') },
	]

	useEffect( () => {
		dispatch( updateCart() );
	}, [cart.items] );

	if ( 'updated' !== cart.status ) {
		return <Loader />
	}

	return (
		<div className="ywsbs-box-cart">
			<div className="ywsbs-box-cart-content">
				<h3>{__('Your box', 'yith-woocommerce-subscription')}</h3>
				<CartItems fixedQuantity={true} />
			</div>
			<div className="ywsbs-box-cart-summary">
				<h3>{__('Order details', 'yith-woocommerce-subscription')}</h3>
				<div className="ywsbs-box-cart-totals">
					{ cartSummary.map( cartSummaryItem => {

						let className = "ywsbs-box-cart-" + cartSummaryItem.id,
							value = cart.totals[ cartSummaryItem.id ];

						if ( ! value ) {
							return;
						}

						return (
							<div key={cartSummaryItem.id} className={className}>
								<span className="label">{parse(cartSummaryItem.label)}</span>
								<Price price={value} wrapClass="value" />
							</div>
						);
					})}
				</div>
				<Submit cartItems={cart.items}/>
			</div>
		</div>
	);
}