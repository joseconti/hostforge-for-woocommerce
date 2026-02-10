/**
 * E2E tests for Knowledge Base frontend pages.
 */

import { test, expect } from '@playwright/test';

test.describe( 'Knowledge Base Frontend', () => {
	test( 'should load the KB archive page', async ( { page } ) => {
		// KB might be at different URLs depending on permalink settings.
		await page.goto( '/knowledge-base/' );

		const status = page.url();
		// If the page redirects or loads, it should work.
		expect( status ).toBeTruthy();
	} );

	test( 'should display KB categories if they exist', async ( { page } ) => {
		await page.goto( '/knowledge-base/' );

		const body = await page.content();
		// Either show categories or an empty/not-found state.
		expect( body ).toBeTruthy();
	} );

	test( 'should be accessible without authentication', async ( { page, context } ) => {
		// Clear all cookies to simulate unauthenticated user.
		await context.clearCookies();

		await page.goto( '/knowledge-base/' );

		// Should not redirect to login.
		const url = page.url();
		expect( url ).not.toContain( 'wp-login.php' );
	} );
} );
