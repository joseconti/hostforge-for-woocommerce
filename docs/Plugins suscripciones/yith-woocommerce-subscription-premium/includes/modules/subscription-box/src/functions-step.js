'use strict';

// Get steps list initial state.
import {STEPS} from "./config";

export const getStepListInitialState = () => {

	const steps = STEPS.map(step => {
		return {
			...step,
			nextPage: 1,
			currentPage: false
		}
	});

	return [
		...steps,
		{
			id: 'cart',
			status: 'loaded'
		}
	];
}
// Get current step.
export const getCurrentStepIndex = state => state.steps.currentStep;
// Get current step ID.
export const getCurrentStepId = state => getCurrentStep(state).id;
// Get current step.
export const getCurrentStep = state => state.steps.stepsList[getCurrentStepIndex(state)];
// Get current step products.
export const getCurrentStepProducts = state => getCurrentStep(state).products;
// Get next step.
export const getNextStep = state => state.steps.stepsList[getCurrentStepIndex(state) + 1];
// Get previous step.
export const getPrevStep = state => state.steps.stepsList[getCurrentStepIndex(state) - 1];
// Check if current step has a previous step.
export const hasPrevStep = state => typeof getPrevStep(state) !== 'undefined';
// Check if current step is cart.
export const isCartStep = state => 'cart' === getCurrentStepId(state);
// Maybe get a step error.
export const getStepError = state => {
	const step_id = getCurrentStepId( state );
	return state.notices.errors.find( error => error.step_id === step_id && ! error?.product_id );
}