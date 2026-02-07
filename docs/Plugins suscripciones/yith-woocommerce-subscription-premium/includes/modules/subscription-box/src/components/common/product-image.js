'use strict';

import React from "react";

export default function ProductImage({image, wrapperArgs,onClick}) {

	return (
		<>
			<div className="ywsbs-box-product-image" {...wrapperArgs} onClick={onClick} >
				<img src={image?.src} width={image?.width} height={image?.height} loading="lazy"/>
			</div>
		</>
	);
}