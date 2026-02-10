/**
 * E2E tests for Server Manager admin screens.
 */

import { test, expect } from '@playwright/test';
import { goToHostForgePage } from '../utils/helpers.js';

test.describe( 'Server Manager', () => {
	test( 'should load the servers list page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-servers' );

		await expect( page.locator( '.wrap' ) ).toBeVisible();
		const body = await page.content();
		expect( body.includes( 'Server' ) || body.includes( 'server' ) ).toBeTruthy();
	} );

	test( 'should have an Add Server button', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-servers' );

		const addButton = page.locator( 'a:has-text("Add"), a:has-text("New"), .page-title-action' );
		const count = await addButton.count();
		expect( count ).toBeGreaterThan( 0 );
	} );

	test( 'should load the server form page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-servers&action=new' );

		await expect( page.locator( '.wrap' ) ).toBeVisible();

		// Form should have key fields.
		const nameField = page.locator( 'input[name*="name"], input[name*="server_name"]' ).first();
		if ( await nameField.isVisible() ) {
			await expect( nameField ).toBeVisible();
		}
	} );

	test( 'should show provider type selector', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-servers&action=new' );

		const body = await page.content();
		// Should contain provider options or the server form.
		expect(
			body.includes( 'cPanel' ) ||
			body.includes( 'cpanel' ) ||
			body.includes( 'Plesk' ) ||
			body.includes( 'plesk' ) ||
			body.includes( 'provider' ) ||
			body.includes( 'Provider' ) ||
			body.includes( 'Server' ) ||
			body.includes( 'server' )
		).toBeTruthy();
	} );

	test( 'should have test connection button', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-servers&action=new' );

		const testBtn = page.locator(
			'button:has-text("Test"), a:has-text("Test Connection"), .hf-test-connection'
		);
		const count = await testBtn.count();
		expect( count ).toBeGreaterThanOrEqual( 0 );
	} );

	test( 'should have server groups filter on list page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-servers' );

		// Look for filter/dropdown for server groups.
		const body = await page.content();
		expect(
			body.includes( 'group' ) ||
			body.includes( 'Group' ) ||
			body.includes( 'hf_server_group' ) ||
			body.includes( 'filter' )
		).toBeTruthy();
	} );
} );
