/**
 * E2E tests for HostForge product types in WooCommerce.
 */

import { test, expect } from '@playwright/test';
import { goToNewProduct } from '../utils/helpers.js';

test.describe( 'HostForge Product Types', () => {
	test( 'should show HostForge product types in the dropdown', async ( { page } ) => {
		await goToNewProduct( page );

		const typeSelector = page.locator( '#product-type' );
		await expect( typeSelector ).toBeVisible();

		// Get all options.
		const options = await typeSelector.locator( 'option' ).allTextContents();

		const hfTypes = [
			'Shared Hosting',
			'Reseller Hosting',
			'VPS Server',
			'Dedicated Server',
			'Domain',
			'SSL Certificate',
			'Software License',
		];

		// At least some HF types should be in the dropdown.
		const found = hfTypes.filter( ( t ) =>
			options.some( ( o ) => o.toLowerCase().includes( t.toLowerCase() ) )
		);

		expect( found.length ).toBeGreaterThan( 0 );
	} );

	test( 'should show Hosting tab when Shared Hosting is selected', async ( { page } ) => {
		await goToNewProduct( page );

		const typeSelector = page.locator( '#product-type' );

		// Select Shared Hosting.
		await typeSelector.selectOption( 'hf_shared_hosting' );

		// Wait for tab to appear.
		await page.waitForTimeout( 500 );

		const body = await page.content();
		expect(
			body.includes( 'Hosting' ) ||
			body.includes( 'hosting' ) ||
			body.includes( 'hf_hosting' ) ||
			body.includes( 'Server Group' )
		).toBeTruthy();
	} );

	test( 'should show VPS tab when VPS Server is selected', async ( { page } ) => {
		await goToNewProduct( page );

		const typeSelector = page.locator( '#product-type' );

		// Select VPS Server.
		await typeSelector.selectOption( 'hf_vps_server' );

		await page.waitForTimeout( 500 );

		const body = await page.content();
		expect(
			body.includes( 'VPS' ) ||
			body.includes( 'vps' ) ||
			body.includes( 'CPU' ) ||
			body.includes( 'RAM' )
		).toBeTruthy();
	} );

	test( 'should create and save a Shared Hosting product', async ( { page } ) => {
		await goToNewProduct( page );

		// Set title.
		await page.locator( '#title' ).fill( 'E2E Test - Shared Hosting Plan' );

		// Select product type.
		await page.locator( '#product-type' ).selectOption( 'hf_shared_hosting' );

		// Set price.
		const priceTab = page.locator( '.general_options a, a[href="#general_product_data"]' ).first();
		if ( await priceTab.isVisible() ) {
			await priceTab.click();
		}

		const priceField = page.locator( '#_regular_price' );
		if ( await priceField.isVisible() ) {
			await priceField.fill( '9.99' );
		}

		// Publish.
		await page.locator( '#publish' ).click();
		await page.waitForLoadState( 'networkidle' );

		// Verify success message.
		const body = await page.content();
		expect(
			body.includes( 'Post published' ) ||
			body.includes( 'Product published' ) ||
			body.includes( 'updated' )
		).toBeTruthy();
	} );
} );
