'use strict';

import React, {useEffect, useState} from 'react';
import Product from "./product";
import {shallowEqual, useDispatch, useSelector} from "react-redux";
import {fetchProducts} from "../../store/slices/steps";
import {getCurrentStep} from "../../functions-step"
import Loader from "../loader";
import QuickView from "../quick-view/quick-view";

export default function Products() {

	const dispatch = useDispatch();
	const step = useSelector(getCurrentStep, shallowEqual);

	// Products.
	const loadProducts = () => {
		if ( step.nextPage && 'loading' !== step.status ) {
			dispatch(fetchProducts( step.nextPage ));
		}
	}

	// Quick view.
	const [quickViewProduct, setQuickViewProduct] = useState(false);
	const openQuickView = product => setQuickViewProduct(product);
	const closeQuickView = () => setQuickViewProduct(false);

	return (
		<>
			<div className="ywsbs-box-products">
				{step.products.map(product => {
					return <Product key={product.id} product={product} openQuickView={openQuickView}/>
				})}
			</div>
			{quickViewProduct && <QuickView product={quickViewProduct} onClose={closeQuickView}/>}
			{step.nextPage && <Loader containerSelector={'.ywsbs-box-step'} callback={loadProducts}/>}
		</>
	);
}