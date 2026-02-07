'use strict';

import { configureStore } from '@reduxjs/toolkit'
import stepsReducer from './slices/steps'
import cartReducer from './slices/cart'
import noticesReducer from "./slices/notices";
import {actionsMiddleware} from "./middleware/actions";

export default configureStore({
	reducer: {
		steps: stepsReducer,
		cart: cartReducer,
		notices: noticesReducer
	},
	middleware: (getDefaultMiddleware) => getDefaultMiddleware()
		.concat(actionsMiddleware)
})