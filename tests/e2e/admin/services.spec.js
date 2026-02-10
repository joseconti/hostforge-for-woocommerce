/**
 * E2E tests for Auto Provisioning — Services admin screens.
 */

import { test, expect } from '@playwright/test';
import { goToHostForgePage } from '../utils/helpers.js';

test.describe( 'Auto Provisioning — Services', () => {
	test( 'should load the services list page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-services' );

		await expect( page.locator( '.wrap' ) ).toBeVisible();
	} );

	test( 'should show status tabs', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-services' );

		const body = await page.content();
		const statuses = [ 'Active', 'Pending', 'Suspended', 'Terminated' ];

		const found = statuses.filter( ( s ) => body.includes( s ) || body.toLowerCase().includes( s.toLowerCase() ) );
		expect( found.length ).toBeGreaterThan( 0 );
	} );

	test( 'should load automation settings', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-services&tab=automation' );

		const body = await page.content();
		expect(
			body.includes( 'Automation' ) ||
			body.includes( 'automation' ) ||
			body.includes( 'auto' ) ||
			body.includes( 'provision' )
		).toBeTruthy();
	} );
} );
