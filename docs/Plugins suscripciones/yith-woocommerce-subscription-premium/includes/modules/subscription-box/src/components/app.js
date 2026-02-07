'use strict';

import React from 'react';
import Header from "./header";
import Step from "./step";
import Footer from "./footer";
import {shallowEqual, useDispatch, useSelector} from "react-redux";
import {getCurrentStep} from "../functions-step";
import {nextStep,prevStep} from "../store/slices/steps";

export default function App() {

	const step = useSelector(getCurrentStep, shallowEqual);
	const dispatch = useDispatch();

	const handleNextStep = event => {
		event.stopPropagation();
		dispatch(nextStep());
	}

	const handlePrevStep = event => {
		event.stopPropagation();
		dispatch(prevStep());
	}

	return (
		<article className="ywsbs-box-wrapper">
			<Header handlePrevStep={handlePrevStep}/>
			<Step step={step}/>
			{ 'cart' !== step?.id && <Footer handleNextStep={handleNextStep}/> }
		</article>
	);
}