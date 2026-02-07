'use strict';

import {React, useState} from 'react';
import {shallowEqual, useSelector} from 'react-redux'
import Container from "./container";
import MiniCart from "./cart/mini-cart";
import { _x } from '@wordpress/i18n';
import {getStepError} from "../functions-step";

export default function Footer({handleNextStep}) {
	const stepError = useSelector( getStepError, shallowEqual );

	let buttonClasses = [ 'button',  'ywsbs-box-next-step' ];
	if ( typeof stepError != 'undefined' ) {
		buttonClasses.push( 'disabled' );
	}

	return (
		<footer>
			<Container>
				<MiniCart />
				<button className={buttonClasses.join( ' ' )} onClick={handleNextStep}>
					{_x( 'Next', 'Subscription box button label', 'yith-woocommerce-subscription')}
				</button>
				{ stepError && ( <span className="ywsbs-box-step-error">{stepError?.message}</span> )}
			</Container>
		</footer>
	);
}