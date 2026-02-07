'use strict';

import React from 'react';

export default function Container({children}) {
	return (
		<>
			<div className="ywsbs-box-container">
				{children}
			</div>
		</>
	);
}