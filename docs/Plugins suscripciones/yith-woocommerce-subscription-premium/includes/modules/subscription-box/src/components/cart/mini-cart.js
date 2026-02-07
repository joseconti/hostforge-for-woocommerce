'use strict';

import React, {useEffect, useState} from 'react';
import {BOX_ICON} from "../../config";
import { _x } from '@wordpress/i18n';
import {shallowEqual, useSelector} from "react-redux";
import {countProductsInCart, getCartSubtotal} from "../../functions-cart";
import CartItems from "./cart-items";
import Price from "../common/price";

export default function MiniCart() {

	const [ miniCartVisible, setMiniCartVisible ] = useState( false );

	const cartSubtotal  = useSelector( getCartSubtotal, shallowEqual );
	const productsCount = useSelector( countProductsInCart, shallowEqual );

	const toggleMiniCartVisible = () => setMiniCartVisible( productsCount ? ! miniCartVisible : false );

	useEffect( () =>  {
		! productsCount && miniCartVisible && toggleMiniCartVisible()
	}, [productsCount] )

	return (
		<>
			<div className="ywsbs-box-mini-cart">
				<div className="ywsbs-box-mini-cart-trigger" onClick={toggleMiniCartVisible}>
					<div className="ywsbs-box-mini-cart-icon">
						<img src={BOX_ICON} width="30" height="30" alt={_x('Mini Cart icon', 'Subscription Box mini-cart icon alt', 'yith-woocommerce-subscription')}/>
						<span className="ywsbs-box-mini-cart-count">{productsCount}</span>
					</div>
					<span className="ywsbs-box-mini-cart-total">
					{_x('Box price:', 'Subscription Box mini-cart label', 'yith-woocommerce-subscription')}
						<Price price={cartSubtotal} wrapClass="ywsbs-box-mini-cart-total-amount"/>
				</span>
				</div>
				<div className={"ywsbs-box-mini-cart-content" + (miniCartVisible ? ' opened' : '')}>
					<div className={"ywsbs-box-mini-cart-title"}>{_x('Your box', 'Subscription Box mini-cart title', 'yith-woocommerce-subscription')}</div>
					<span className={"ywsbs-box-mini-cart-close ywsbs-box-icon-close"} onClick={toggleMiniCartVisible}></span>
					<CartItems fixedQuantity={false}/>
				</div>
			</div>

		</>
	);
}