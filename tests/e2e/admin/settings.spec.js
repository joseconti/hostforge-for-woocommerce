/**
 * E2E tests for HostForge Settings page.
 */

import { test, expect } from '@playwright/test';
import { goToHostForgePage } from '../utils/helpers.js';

test.describe( 'HostForge Settings', () => {
	test( 'should load the settings page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-settings' );

		await expect( page.locator( '.wrap' ) ).toBeVisible();
	} );

	test( 'should have a save button', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-settings' );

		const submitButton = page.locator( 'input[type="submit"], button[type="submit"]' );
		await expect( submitButton.first() ).toBeVisible();
	} );

	test( 'should contain nonce field in form', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-settings' );

		// Every form should have a nonce.
		const nonce = page.locator( 'input[name="_wpnonce"], input[name*="nonce"]' );
		const count = await nonce.count();
		expect( count ).toBeGreaterThan( 0 );
	} );

	test( 'should save and persist settings', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-settings' );

		// Find a text input and modify it.
		const textInput = page.locator( 'input[type="text"]' ).first();

		if ( await textInput.isVisible() ) {
			const testValue = 'e2e-test-' + Date.now();
			await textInput.fill( testValue );

			// Submit form.
			await page.locator( 'input[type="submit"], button[type="submit"]' ).first().click();
			await page.waitForLoadState( 'networkidle' );

			// Verify the value persisted.
			const savedValue = await textInput.inputValue();
			expect( savedValue ).toBe( testValue );
		}
	} );

	test( 'should sanitize XSS in text fields', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-settings' );

		const textInput = page.locator( 'input[type="text"]' ).first();

		if ( await textInput.isVisible() ) {
			await textInput.fill( '<script>alert("xss")</script>' );
			await page.locator( 'input[type="submit"], button[type="submit"]' ).first().click();
			await page.waitForLoadState( 'networkidle' );

			// The script tag should have been sanitized.
			const savedValue = await textInput.inputValue();
			expect( savedValue ).not.toContain( '<script>' );
		}
	} );
} );
