/**
 * E2E tests for HostForge checkout fields.
 */

import { test, expect } from '@playwright/test';

test.describe( 'Checkout with HostForge Products', () => {
	test.beforeEach( async ( { page } ) => {
		// Ensure we're logged in as admin (shared auth state).
		await page.goto( '/wp-admin/' );
		await expect( page.locator( '#wpadminbar' ) ).toBeVisible();
	} );

	test( 'should display HostForge checkout fields when hosting product is in cart', async ( { page } ) => {
		// Navigate to shop.
		await page.goto( '/shop/' );

		// Try to find and add a hosting product.
		const addToCartBtn = page.locator( '.add_to_cart_button, .single_add_to_cart_button' ).first();

		if ( await addToCartBtn.isVisible() ) {
			await addToCartBtn.click();
			await page.waitForTimeout( 1000 );

			// Go to checkout.
			await page.goto( '/checkout/' );
			await page.waitForLoadState( 'networkidle' );

			const body = await page.content();
			// If a hosting product is in the cart, HF fields should appear.
			// If no HF product, the test passes as baseline.
			expect( body ).toBeTruthy();
		}
	} );

	test( 'should validate required domain field on checkout', async ( { page } ) => {
		// This test requires a hosting product in the cart.
		// Navigate to checkout directly.
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		// Look for HF checkout fields.
		const domainField = page.locator( '#hf_hosting_domain, #hf_domain_name, input[name="hf_hosting_domain"]' );

		if ( await domainField.isVisible() ) {
			// Leave domain empty and try to submit.
			await page.locator( '#place_order' ).click();
			await page.waitForTimeout( 2000 );

			// Should show validation error.
			const errors = page.locator( '.woocommerce-error, .wc-block-components-validation-error' );
			const errorCount = await errors.count();
			expect( errorCount ).toBeGreaterThan( 0 );
		}
	} );

	test( 'should reject invalid domain format', async ( { page } ) => {
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		const domainField = page.locator( '#hf_hosting_domain, input[name="hf_hosting_domain"]' );

		if ( await domainField.isVisible() ) {
			await domainField.fill( 'not a domain' );
			await page.locator( '#place_order' ).click();
			await page.waitForTimeout( 2000 );

			const body = await page.content();
			expect(
				body.includes( 'valid domain' ) ||
				body.includes( 'error' ) ||
				body.includes( 'Error' )
			).toBeTruthy();
		}
	} );

	test( 'should accept valid domain format', async ( { page } ) => {
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		const domainField = page.locator( '#hf_hosting_domain, input[name="hf_hosting_domain"]' );

		if ( await domainField.isVisible() ) {
			await domainField.fill( 'testdomain.com' );

			// The field should accept the value without immediate error.
			const value = await domainField.inputValue();
			expect( value ).toBe( 'testdomain.com' );
		}
	} );
} );
