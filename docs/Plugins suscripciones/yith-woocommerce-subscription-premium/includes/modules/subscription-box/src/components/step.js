'use strict';

import {React, useEffect} from 'react';
import Products from "./product/products";
import Container from "./container";
import Cart from "./cart/cart";
import parse from 'html-react-parser';

export default function Step( {step} ) {

	if ( 'cart' === step.id ) {
		return (
			<section className="ywsbs-box-step">
				<Container>
					<Cart/>
				</Container>
			</section>
		);
	}

	return (
		<section className="ywsbs-box-step">
			<Container>
				<div className="ywsbs-box-step-text">
					<h2>{step.label}</h2>
					{parse(step.text)}
				</div>
				<Products key={step.id + '_products'} />
			</Container>
		</section>
	);
}