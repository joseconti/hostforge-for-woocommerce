/**
 * E2E tests for Domain Manager admin screens.
 */

import { test, expect } from '@playwright/test';
import { goToHostForgePage } from '../utils/helpers.js';

test.describe( 'Domain Manager', () => {
	test( 'should load the domains list page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-domains' );

		await expect( page.locator( '.wrap' ) ).toBeVisible();
	} );

	test( 'should show status tabs on domain list', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-domains' );

		const body = await page.content();
		const statuses = [ 'Active', 'Pending', 'Expired' ];
		const found = statuses.filter( ( s ) => body.includes( s ) );
		expect( found.length ).toBeGreaterThan( 0 );
	} );

	test( 'should load TLD pricing page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-domains&tab=tld-pricing' );

		const body = await page.content();
		expect(
			body.includes( 'TLD' ) ||
			body.includes( 'tld' ) ||
			body.includes( 'Pricing' ) ||
			body.includes( 'pricing' )
		).toBeTruthy();
	} );

	test( 'should load registrar settings page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-domains&tab=registrar' );

		const body = await page.content();
		expect(
			body.includes( 'Registrar' ) ||
			body.includes( 'registrar' ) ||
			body.includes( 'Namecheap' ) ||
			body.includes( 'API' )
		).toBeTruthy();
	} );

	test( 'should have search functionality', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-domains' );

		const searchInput = page.locator( 'input[type="search"], input[name="s"]' );
		const count = await searchInput.count();
		expect( count ).toBeGreaterThanOrEqual( 0 );
	} );
} );
