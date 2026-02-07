'use strict';

import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './components/app'
import store from './store'
import { Provider } from 'react-redux'
import {stepController} from "./store/controllers/step";
import {cartController} from "./store/controllers/cart";

const root = createRoot( document.getElementById( 'ywsbs-box' ) );

document.getElementById( 'ywsbs-box-setup-trigger' )?.addEventListener( 'click', () =>  {
	document.documentElement.className += ' open-ywsbs-box';

	// Init controllers once.
	stepController.init();
	cartController.init();

	root.render(
		<Provider store={store}>
			<App/>
		</Provider>
	);
});