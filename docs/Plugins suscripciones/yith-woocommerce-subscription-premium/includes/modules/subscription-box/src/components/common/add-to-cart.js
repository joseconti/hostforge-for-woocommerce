'use strict';

import React from "react";
import {addProduct} from "../../store/slices/cart";
import {shallowEqual, useDispatch, useSelector} from "react-redux";
import {getProductError} from "../../functions-product";
import _ from 'lodash'
import { Tooltip } from 'react-tooltip'


export default function AddToCart({product}) {

	const dispatch = useDispatch();
	const errorMessage = useSelector(state => getProductError(state, product.id), shallowEqual)?.message || '';

	const handleOnClick = event => {
		event.stopPropagation();
		if ( ! errorMessage ) {
			dispatch(addProduct({product}));
		}
	}

	const id = _.uniqueId('ywsbs-');

	let plusClass = [ 'ywsbs-box-product-add' ];
	if ( errorMessage ) {
		plusClass.push( 'has-error' );
	}

	return (
		<span id={id} className={plusClass.join(' ')} onClick={handleOnClick}>
			<i className="ywsbs-box-icon-plus"></i>
			{errorMessage && <Tooltip anchorSelect={"#"+id} place="bottom" className={"ywsbs-box-product-error"}>{errorMessage}</Tooltip>}
		</span>
	);
}