import {createSlice} from '@reduxjs/toolkit'

export const noticesSlice = createSlice({
	name: 'notices',
	initialState: {
		errors: []
	},
	reducers: {
		addError: ( state, action ) => {
			const step_id    = action.payload?.step_id || ''
			const product_id = action.payload?.product_id || '';

			// Check if an error for step or for product, if provided, exists!
			const errorIndex = state.errors.findIndex( error => {
				return error.step_id === step_id && error.product_id === product_id;
			});

			if ( -1 === errorIndex ) {
				state.errors.push( action.payload );
			} else {
				state.errors[ errorIndex ].message = action.payload.message;
			}
		},
		removeErrorByCode: ( state, action ) => {
			state.errors = state.errors.filter(
				error => error?.code !== action.payload.code && ( ! action.payload?.product_id || error.product_id === action.payload?.product_id )
			);
		}
	}
})

export const {addError, removeErrorByCode} = noticesSlice.actions
export default noticesSlice.reducer
