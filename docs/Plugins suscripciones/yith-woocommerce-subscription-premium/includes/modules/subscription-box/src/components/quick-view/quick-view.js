'use strict';

import React, {useEffect} from 'react';
import ProductImage from "../common/product-image";
import {decodeEntities} from "../../functions";
import QuantityInput from "../common/quantity-input";
import Tabs from "./tabs";
import parse from "html-react-parser";

export default function QuickView({product, onClose}) {

	useEffect(() => {
		document.getElementById('ywsbs-box')?.classList.add('modal-opened');
		setTimeout( () => document.querySelector('.ywsbs-box-product-quick-view').classList.add( 'visible' ), 200 );
	});

	const handleClose = () => {
		document.querySelector('.ywsbs-box-product-quick-view').classList.remove( 'visible' )
		setTimeout( () => {
			document.getElementById('ywsbs-box')?.classList.remove('modal-opened')
			onClose()
		}, 300 );
	}

	return (
		<>
			<div className={"ywsbs-box-product-quick-view-wrapper"}>
				<div className={"ywsbs-box-product-quick-view"}>
					<span className={"ywsbs-box-product-quick-view-close ywsbs-box-icon-close"} onClick={handleClose}></span>
					<div className={"ywsbs-box-product-quick-view-content"}>
						<h2 className={"ywsbs-box-product-title"}>{decodeEntities(product.name)}</h2>
						<ProductImage image={product.images?.single}/>
						<div className={"ywsbs-box-product-description"}>{parse(product.short_description)}</div>
						<div className={"ywsbs-box-product-price"}>{parse(product.price_html)}</div>
						<QuantityInput product={product}/>
						<Tabs product={product}/>
					</div>
				</div>
			</div>
		</>
	);
}