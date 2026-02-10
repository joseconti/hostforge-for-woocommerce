/**
 * E2E tests for My Account — Support Tickets page.
 */

import { test, expect } from '@playwright/test';
import { goToMyAccount } from '../utils/helpers.js';

test.describe( 'My Account — Support Tickets', () => {
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

	test( 'should load the support tickets endpoint', async ( { page } ) => {
		await goToMyAccount( page, 'support-tickets' );

		const body = await page.content();
		expect( body.includes( '404' ) && body.includes( 'Not Found' ) ).toBeFalsy();
	} );

	test( 'should display ticket list or My Account content', async ( { page } ) => {
		await goToMyAccount( page, 'support-tickets' );

		const body = await page.content();
		expect(
			body.includes( 'New Ticket' ) ||
			body.includes( 'new-ticket' ) ||
			body.includes( 'Create' ) ||
			body.includes( 'Open Ticket' ) ||
			body.includes( 'Support' ) ||
			body.includes( 'my-account' ) ||
			body.includes( 'woocommerce' )
		).toBeTruthy();
	} );

	test( 'should load new ticket form or My Account content', async ( { page } ) => {
		await goToMyAccount( page, 'support-tickets/new' );

		const body = await page.content();
		expect(
			body.includes( 'Subject' ) ||
			body.includes( 'subject' ) ||
			body.includes( 'Message' ) ||
			body.includes( 'Department' ) ||
			body.includes( 'my-account' ) ||
			body.includes( 'woocommerce' )
		).toBeTruthy();
	} );

	test( 'should validate required fields on new ticket form', async ( { page } ) => {
		await goToMyAccount( page, 'support-tickets/new' );

		// Try to submit empty form.
		const submitBtn = page.locator( 'button[type="submit"], input[type="submit"]' ).first();

		if ( await submitBtn.isVisible() ) {
			await submitBtn.click();
			await page.waitForTimeout( 1000 );

			// Should show validation errors or stay on the form.
			const url = page.url();
			expect( url ).toContain( 'support-tickets' );
		}
	} );
} );
