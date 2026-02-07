'use strict';

import React from "react";
import {formatPrice} from "../../functions";

export default function Price({price, wrapClass}){
	return (
		<>
			<span className={wrapClass}>
				{formatPrice(price)}
			</span>
		</>
	)
}