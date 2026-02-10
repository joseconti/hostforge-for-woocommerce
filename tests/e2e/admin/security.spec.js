/**
 * E2E tests for Security module admin screens.
 */

import { test, expect } from '@playwright/test';
import { goToHostForgePage } from '../utils/helpers.js';

test.describe( 'Security Module', () => {
	test( 'should load security settings page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-security' );

		await expect( page.locator( '.wrap' ) ).toBeVisible();
	} );

	test( 'should load IP blocks page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-security&tab=ip-blocks' );

		const body = await page.content();
		expect(
			body.includes( 'IP' ) ||
			body.includes( 'Block' ) ||
			body.includes( 'block' )
		).toBeTruthy();
	} );

	test( 'should load login attempts page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-security&tab=login-attempts' );

		const body = await page.content();
		expect(
			body.includes( 'Login' ) ||
			body.includes( 'login' ) ||
			body.includes( 'Attempt' )
		).toBeTruthy();
	} );

	test( 'should load audit log page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-security&tab=audit-log' );

		const body = await page.content();
		expect(
			body.includes( 'Audit' ) ||
			body.includes( 'audit' ) ||
			body.includes( 'Log' )
		).toBeTruthy();
	} );

	test( 'should have brute force settings', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-security' );

		const body = await page.content();
		expect(
			body.includes( 'attempts' ) ||
			body.includes( 'Attempts' ) ||
			body.includes( 'Brute' ) ||
			body.includes( 'brute' ) ||
			body.includes( 'max_login' )
		).toBeTruthy();
	} );

	test( 'should have CAPTCHA configuration', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-security' );

		const body = await page.content();
		expect(
			body.includes( 'CAPTCHA' ) ||
			body.includes( 'captcha' ) ||
			body.includes( 'Turnstile' ) ||
			body.includes( 'reCAPTCHA' )
		).toBeTruthy();
	} );
} );
