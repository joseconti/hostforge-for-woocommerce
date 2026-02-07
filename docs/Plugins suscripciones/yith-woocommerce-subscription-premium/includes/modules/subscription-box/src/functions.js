'use strict';

import {WC} from "./config"

export const decodeEntities = encodedString => {
	const tmp = document.createElement( 'DIV' );
	tmp.innerHTML = encodedString;
	return tmp.textContent || tmp.innerText || '';
}

export const sanitizeHTML = html => {
	const tmp = document.createElement( 'DIV' );
	tmp.innerHTML = html;
	return tmp.innerHTML || '';
}

export const formatPrice = price => {
	return decodeEntities( accounting.formatMoney( price, {
		symbol:    WC.currencySymbol,
		decimal:   WC.decimalSeparator,
		thousand:  WC.thousandSeparator,
		precision: WC.decimals,
	} ) );
}
