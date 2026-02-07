'use strict';

import {createSlice, createAsyncThunk} from '@reduxjs/toolkit'
import {getProductsUrl} from "../../functions-product";
import {getCurrentStep, getStepListInitialState} from "../../functions-step";
import apiFetch from '@wordpress/api-fetch';

export const fetchProducts = createAsyncThunk('steps/fetchProducts', async ( page = 1, {getState} ) => {
	const step  = getCurrentStep( getState() );
	// Merge with default
	let returnValue = {};

	if ( false === step?.nextPage ) { // exit if not more pages.
		return returnValue;
	}

	await apiFetch(
		{
			path: getProductsUrl({step:step.id, page:page} ),
			parse: false
		} )
			.then( response => {
				// Parse headers.
				returnValue.nextPage = ( page < parseInt( response.headers.get('X-WP-TotalPages') ) ) ? page + 1 : false;
				return response.json()
			} )
			.then( products => returnValue.products = products )
			.catch(error => {
				console.error("Error:", error);
			});

	return returnValue;
});

export const stepsSlice = createSlice({
	name: 'steps',
	initialState: {
		currentStep: 0,
		stepsList: getStepListInitialState()
	},
	reducers: {
		nextStep: steps => {
			if ( steps.stepsList.length > ( steps.currentStep + 1 ) )
				steps.currentStep += 1;
		},
		prevStep: steps => {
			if ( steps.currentStep > 0 )
				steps.currentStep -= 1;
		},
		goToStep: (steps, action) => {
			const stepIndex = steps.stepsList.findIndex( step => step.id === action.payload?.step_id );
			if ( -1 !== stepIndex ) {
				steps.currentStep = stepIndex;
			}
		}
	},
	extraReducers(builder) {
		builder
			.addCase(fetchProducts.fulfilled, (state, action) => {
				if ( ! _.isEmpty( action.payload ) ) {
					state.stepsList[state.currentStep].currentPage = state.stepsList[state.currentStep].nextPage;
					state.stepsList[state.currentStep].nextPage = action.payload.nextPage;
					state.stepsList[state.currentStep].products.push( ...action.payload.products );
				}
			})
	}
})

export const {nextStep, prevStep, goToStep} = stepsSlice.actions
export default stepsSlice.reducer
