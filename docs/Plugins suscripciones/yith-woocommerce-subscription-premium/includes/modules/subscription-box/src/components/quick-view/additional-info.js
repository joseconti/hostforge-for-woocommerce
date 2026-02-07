'use strict';

import React from 'react';
import {decodeEntities} from "../../functions";

export default function AdditionalInfo({product}) {

	return (
		<>
			<div className={"ywsbs-box-product-attributes"}>
				{product?.attributes.map( attribute => {
					return <div className={"ywsbs-box-product-attribute"} key={attribute.id}>
								<span className={"ywsbs-box-product-attribute-label"}>{decodeEntities(attribute.label)}</span>
								<span className={"ywsbs-box-product-attribute-value"}>{decodeEntities(attribute.value)}</span>
							</div>
				})}
			</div>
		</>
	);
}