'use strict';

import React, {useRef, useState} from 'react';
import {getProductTabs} from "../../functions-product";
import parse from 'html-react-parser';
import AdditionalInfo from "./additional-info";
import Reviews from "./reviews";

export default function Tabs({product}) {

	const tabs = getProductTabs(product);
	if ( !tabs.length ) {
		return;
	}

	// Set the first tab as current.
	const [currentTab, setCurrentTab] = useState( tabs[0].id );

	return (
		<>
			<div className={"ywsbs-box-product-tabs-wrapper"}>
				<div className={"ywsbs-box-product-tabs"}>
					{tabs.map(tab => {
						return <span key={tab.id} className={tab.id === currentTab ? 'current' : ''} data-target={tab.id} onClick={() => setCurrentTab(tab.id)}>
							{tab.label}
						</span>
					})}
				</div>
				<div className={"ywsbs-box-product-tab"}>
					{'description' === currentTab && parse(product.description)}
					{'additional-info' === currentTab && <AdditionalInfo product={product} />}
					{'reviews' === currentTab && <Reviews product={product} />}
				</div>
			</div>
		</>
	);
}