'use strict';

import React from 'react';
import parse from "html-react-parser";
import {decodeEntities} from "../../functions";
import {__, sprintf} from "@wordpress/i18n";

export default function Review({review}) {

	return (
		<>
			<div className={'ywsbs-box-product-review'}>
				<div className={'ywsbs-box-product-review-data'}>
					<img src={review.avatar} width={'60px'} height={'60px'}/>
					<div className={'ywsbs-box-product-review-meta'}>
						<span className={'ywsbs-box-product-review-author'}>{decodeEntities(review.author)}</span>
						<time dateTime={review.datetime}>{decodeEntities(review.date)}</time>
						<div className="star-rating" role="img" aria-label={sprintf(__('Rated %s out of 5', 'yith-woocommerce-subscription'), review.rating)}>
							<span style={{width: review.rating * 20 + '%'}}></span>
						</div>
					</div>
				</div>
				<div className={'ywsbs-box-product-review-content'}>
					{parse(review.content)}
				</div>
			</div>
		</>
	)
}