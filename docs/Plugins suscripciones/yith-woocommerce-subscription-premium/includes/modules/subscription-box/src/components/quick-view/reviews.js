'use strict';

import React, {useRef, useState} from 'react';
import Loader from "../loader";
import {fetchProductReviews} from "../../functions-product";
import Review from "./review";

export default function Reviews({product}) {

	const [reviewsPage, setReviewsPage ] = useState(1);
	const reviews = useRef([]);

	const getProductReviews = async () => {
		if ( reviewsPage ) {
			let response = await fetchProductReviews(product, reviewsPage );

			setReviewsPage( response?.nextPage || false );
			reviews.current = [ ...reviews.current, ...response?.reviews ];
		}
	}

	return (
		<>
			<div className={'ywsbs-box-product-reviews'}>
				{reviews.current.map( review => {
					return <Review key={review.id} review={review} />;
				})}
			</div>
			{reviewsPage && <Loader containerSelector={'.ywsbs-box-product-quick-view-content'} callback={getProductReviews}/>}
		</>
	)
}
