'use strict';

import React from 'react';
import ProductImage from "../common/product-image";
import {decodeEntities} from "../../functions";
import Price from "../common/price";
import {HAS_FIXED_PRICE} from "../../config";
import QuantityInput from "../common/quantity-input";

export default function CartItem({item, fixedQuantity}) {

	const itemName = fixedQuantity ? item.quantity + ' x ' + decodeEntities(item.product.name) : decodeEntities(item.product.name);

	return (
		<>
			<li className="ywsbs-box-cart-item">
				<ProductImage image={item.product.images?.thumbnail} />
				<span className="ywsbs-box-cart-item">
					<span className="ywsbs-box-cart-item-name">
						{itemName}
					</span>
					{ HAS_FIXED_PRICE || <Price price={item.total} wrapClass={"ywsbs-box-cart-item-price"} /> }
				</span>
				{ fixedQuantity || <QuantityInput product={item.product} compact={true} />}
			</li>
		</>
	);
}