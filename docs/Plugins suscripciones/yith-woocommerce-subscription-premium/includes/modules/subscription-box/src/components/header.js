'use strict';

import React from 'react';
import Container from './container';
import {BACK_ICON, HEADER_LOGO} from "../config";
import {shallowEqual, useDispatch, useSelector} from "react-redux";
import {prevStep} from "../store/slices/steps";
import {hasPrevStep} from "../functions-step";
import {__} from "@wordpress/i18n";

export default function Header() {
	const showPrev = useSelector(hasPrevStep, shallowEqual);
	const dispatch = useDispatch();

	return (
		<header>
			<Container>
				{showPrev &&
					<span className="ywsbs-box-prev-step" onClick={() => {dispatch(prevStep())}}>
						<img src={BACK_ICON} width="30px" alt=""/><span className="ywsbs-box-prev-step-text">{__('Back', 'yith-woocommerce-subscription')}</span>
					</span>
				}
				{HEADER_LOGO &&
					<img className="ywsbs-box-logo" src={HEADER_LOGO} alt=""/>
				}
			</Container>
		</header>
	);
}