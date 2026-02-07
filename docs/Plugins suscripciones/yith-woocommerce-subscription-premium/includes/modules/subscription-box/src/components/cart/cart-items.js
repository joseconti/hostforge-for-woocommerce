'use strict';

import React from 'react';
import CartItem from "./cart-item";
import {shallowEqual, useDispatch, useSelector} from "react-redux";
import {getCartItems} from "../../functions-cart";
import {getCurrentStepIndex} from "../../functions-step";
import {STEPS} from "../../config";
import {goToStep} from "../../store/slices/steps";
import {_x} from "@wordpress/i18n";

export default function CartItems({fixedQuantity}) {

	const cartItems = useSelector(getCartItems, shallowEqual);
	const currentStepIndex = useSelector(getCurrentStepIndex, shallowEqual);
	const dispatch = useDispatch();

	return (
		<>
			<div className="ywsbs-box-cart-items-steps">
				{Object.entries(cartItems).map(([step_id, items]) => {
					const index = STEPS.findIndex( step => step.id === step_id );

					return (
						<div key={step_id} className="ywsbs-box-cart-items-step">
							<div className="ywsbs-box-cart-items-step-label">
								{STEPS[index]?.label}
								{index < currentStepIndex ? ( <span className="ywsbs-box-cart-items-step-edit" onClick={
									() => {dispatch(goToStep({step_id:step_id}));}
								}>{_x('edit', 'Subscription Box mini-cart items edit label', 'yith-woocommerce-subscription')}</span> ) : null}
							</div>
							<ul className="ywsbs-box-cart-items">
								{
									items.map(item => {
										return <CartItem key={item.id} item={item} fixedQuantity={fixedQuantity || index !== currentStepIndex}/>
									})
								}
							</ul>
						</div>
					)
				})}
			</div>
		</>
	);
}