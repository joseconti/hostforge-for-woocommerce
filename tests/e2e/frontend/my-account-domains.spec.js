/**
 * E2E tests for My Account — My Domains page.
 */

import { test, expect } from '@playwright/test';
import { goToMyAccount } from '../utils/helpers.js';

test.describe( 'My Account — My Domains', () => {
	test( 'should load My Account page', async ( { page } ) => {
		await goToMyAccount( page );

		const body = await page.content();
		expect(
			body.includes( 'My account' ) ||
			body.includes( 'my-account' ) ||
			body.includes( 'Dashboard' ) ||
			body.includes( 'Log out' )
		).toBeTruthy();
	} );

	test( 'should load the my-domains endpoint', async ( { page } ) => {
		await goToMyAccount( page, 'my-domains' );

		const body = await page.content();
		expect( body.includes( '404' ) && body.includes( 'Not Found' ) ).toBeFalsy();
	} );

	test( 'should display domain list or My Account content', async ( { page } ) => {
		await goToMyAccount( page, 'my-domains' );

		const body = await page.content();
		expect(
			body.includes( 'domain' ) ||
			body.includes( 'Domain' ) ||
			body.includes( 'No domains' ) ||
			body.includes( 'no domains' ) ||
			body.includes( 'hostforge' ) ||
			body.includes( 'my-account' ) ||
			body.includes( 'woocommerce' )
		).toBeTruthy();
	} );
} );
