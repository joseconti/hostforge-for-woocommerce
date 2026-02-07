'use strict';

import React from 'react';
import {deleteSessionCart, formatCartItemsForRequest} from "../../functions-cart";
import {__} from '@wordpress/i18n';
import {BOX_ID} from "../../config";

export default function Submit({cartItems}) {

	return (
		<>
			<form id="ywsbs-box-form" method="POST" onSubmit={deleteSessionCart}>
				<input type="hidden" name="_ywsbs_box_content" value={JSON.stringify(formatCartItemsForRequest(cartItems))}/>
				{ywsbs_subscription_box.subscriptionID
					? (
						<>
							<input type="hidden" name="security" value={ywsbs_subscription_box.editNonce}/>
							<input type="hidden" name="_ywsbs_subscription_box_edit" value={ywsbs_subscription_box.subscriptionID}/>
							<button type="submit" className="ywsbs-box-cart-submit">
								{__('Save box', 'yith-woocommerce-subscription')}
							</button>
						</>
					)
					: (
						<>
							<input type="hidden" name="_ywsbs_box_add_to_cart" value={BOX_ID}/>
							<button type="submit" className="ywsbs-box-cart-submit">
								{__('Proceed to Checkout', 'yith-woocommerce-subscription')}
							</button>
						</>
					)
				}
			</form>
		</>
	)
}