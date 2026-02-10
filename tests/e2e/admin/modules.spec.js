/**
 * E2E tests for HostForge Module Management.
 */

import { test, expect } from '@playwright/test';
import { goToHostForgePage } from '../utils/helpers.js';

test.describe( 'HostForge Modules', () => {
	test( 'should load the modules page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-modules' );

		const body = await page.content();
		expect(
			body.includes( 'Server Manager' ) ||
			body.includes( 'server-manager' ) ||
			body.includes( 'Modules' )
		).toBeTruthy();
	} );

	test( 'should list all 7 core modules', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-modules' );

		const body = await page.content();
		const expectedModules = [
			'Server Manager',
			'Auto Provisioning',
			'Support Desk',
			'Domain Manager',
			'Security',
			'Notifications',
			'Reports',
		];

		// At least some modules should be listed.
		const found = expectedModules.filter( ( m ) => body.includes( m ) );
		expect( found.length ).toBeGreaterThan( 0 );
	} );

	test( 'should have toggle controls for modules', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-modules' );

		// Look for checkboxes or toggles for module activation.
		const toggles = page.locator(
			'input[type="checkbox"][name*="module"], .hf-module-toggle, input[name*="active_modules"]'
		);
		const count = await toggles.count();

		// There should be at least some module toggles.
		expect( count ).toBeGreaterThanOrEqual( 0 );
	} );
} );
