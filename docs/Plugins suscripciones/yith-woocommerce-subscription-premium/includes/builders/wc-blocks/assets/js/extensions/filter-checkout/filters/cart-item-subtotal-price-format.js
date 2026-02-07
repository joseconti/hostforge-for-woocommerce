import parse from 'html-react-parser';
import {select} from '@wordpress/data';
import {cartHasSubscription} from '../../../common';

document.addEventListener("DOMContentLoaded", () => {
	if ( window.wc?.blocksCheckout && cartHasSubscription() ) {
		const {registerCheckoutFilters} = window.wc.blocksCheckout;

		const checkContext = (args) => {
			return args?.context === 'cart' || args?.context === 'summary';
		}

		const updateRecurringTotals = (defaultValue) => {
			const store = select('wc/store/cart');
			const recurringTotalsHtml = store.getCartData()?.extensions?.ywsbs_recurring_totals.html;

			if ( recurringTotalsHtml ) {
				var domElem = document.getElementsByClassName('ywsbs-recurring-totals-items')[0];
				if ( domElem ) {
					domElem.innerHTML = recurringTotalsHtml;
				}
			}

			return defaultValue;
		}

		registerCheckoutFilters('ywsbs-subscription-product-price', {
			showApplyCouponNotice: updateRecurringTotals,
			showRemoveCouponNotice: updateRecurringTotals,
			cartItemClass: (defaultValue, extensions, args) => {

				if ( !checkContext(args) ) {
					return defaultValue;
				}

				// Check if is subscription item
				if ( !args?.cartItem.item_data.find(item => item.name === 'ywsbs-subscription-info') ) {
					return defaultValue;
				}

				return 'ywsbs-cart-item';
			},
			subtotalPriceFormat: (defaultValue, extensions, args) => {

				if ( !checkContext(args) ) {
					return defaultValue;
				}

				const cartItem = args?.cartItem.item_data;
				if ( !cartItem ) {
					return defaultValue;
				}

				const ywsbsData = cartItem.find(item => item.name === 'ywsbs-price-html');
				if ( !ywsbsData ) {
					return defaultValue;
				}

				return '<price/> ' + parse(ywsbsData.value);
			},
		});
	}
});