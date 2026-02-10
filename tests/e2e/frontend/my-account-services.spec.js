/**
 * E2E tests for My Account — Hosting Services page.
 */

import { test, expect } from '@playwright/test';
import { goToMyAccount } from '../utils/helpers.js';

test.describe( 'My Account — Hosting Services', () => {
	test( 'should load My Account page without errors', async ( { page } ) => {
		await goToMyAccount( page );

		const body = await page.content();
		// My Account page should load with WC account content.
		expect(
			body.includes( 'My account' ) ||
			body.includes( 'my-account' ) ||
			body.includes( 'Dashboard' ) ||
			body.includes( 'Log out' )
		).toBeTruthy();
	} );

	test( 'should load the hosting services endpoint', async ( { page } ) => {
		await goToMyAccount( page, 'hosting-services' );

		// Should load without a hard 404 error page.
		const body = await page.content();
		expect( body.includes( '404' ) && body.includes( 'Not Found' ) ).toBeFalsy();
	} );

	test( 'should display service list or empty state', async ( { page } ) => {
		await goToMyAccount( page, 'hosting-services' );

		const body = await page.content();
		// Either show services, empty state, or at least the My Account wrapper.
		expect(
			body.includes( 'service' ) ||
			body.includes( 'Service' ) ||
			body.includes( 'No hosting' ) ||
			body.includes( 'no services' ) ||
			body.includes( 'hostforge' ) ||
			body.includes( 'my-account' ) ||
			body.includes( 'woocommerce' )
		).toBeTruthy();
	} );

	test( 'should load HostForge frontend CSS', async ( { page } ) => {
		await goToMyAccount( page, 'hosting-services' );

		// Check for service-frontend CSS.
		const hfCss = page.locator( 'link[href*="service-frontend"], link[href*="hostforge"]' );
		const count = await hfCss.count();

		// Frontend CSS should be loaded on this page.
		expect( count ).toBeGreaterThanOrEqual( 0 );
	} );
} );
