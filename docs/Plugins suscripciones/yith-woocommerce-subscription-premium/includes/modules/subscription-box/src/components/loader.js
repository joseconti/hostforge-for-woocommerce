'use strict';

import React, {useEffect, useState} from "react";

export default function Loader({containerSelector, callback}) {

	// Handle infinite scrolling.
	useEffect(() => {

		if ( typeof callback !== 'function' ) {
			return;
		}

		const loader = document.querySelector('.ywsbs-box-loader-wrapper');
		const container = document.querySelector(containerSelector);
		// Handle loader with callback.
		// When loader is visible call the callback.
		const handleLoader = () => {
			if ( ( loader.getBoundingClientRect().top - container.getBoundingClientRect().top ) < container.getBoundingClientRect().height ) {
				// Remove itself do avoid multiple call.
				container.removeEventListener('scroll', handleLoader);
				// Trigger callback!
				callback();
			} else {
				// Since elem is not visible listen to scroll.
				container.addEventListener('scroll', handleLoader);
			}
		}

		handleLoader();

		return () => {
			container.removeEventListener('scroll', handleLoader);
		}

	} );

	return (
		<>
			<div className="ywsbs-box-loader-wrapper">
				<div className="ywsbs-box-loader">
					<div></div>
					<div></div>
					<div></div>
				</div>
			</div>
		</>
	);
}