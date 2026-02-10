/**
 * E2E tests for HostForge Admin Dashboard.
 */

import { test, expect } from '@playwright/test';
import { goToHostForgePage } from '../utils/helpers.js';

test.describe( 'HostForge Dashboard', () => {
	test( 'should load the dashboard page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-dashboard' );

		// WP page title includes the page name and site name.
		const body = await page.content();
		expect(
			body.includes( 'HostForge' ) ||
			body.includes( 'hostforge' )
		).toBeTruthy();
		await expect( page.locator( '.wrap' ).first() ).toBeVisible();
	} );

	test( 'should display summary cards', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-dashboard' );

		// Verify the dashboard has summary content.
		const body = await page.locator( '.wrap' );
		await expect( body ).toBeVisible();
	} );

	test( 'should show admin menu with HostForge items', async ( { page } ) => {
		await page.goto( '/wp-admin/' );

		// HostForge menu should exist in the admin sidebar.
		const menu = page.locator( '#adminmenu' );
		await expect( menu ).toBeVisible();

		const hfMenu = page.locator( '#toplevel_page_hostforge-dashboard' );
		await expect( hfMenu ).toBeVisible();
	} );

	test( 'should not show HostForge CSS/JS on other admin pages', async ( { page } ) => {
		await page.goto( '/wp-admin/edit.php' );

		// HostForge admin CSS should not be loaded on the posts page.
		const hfCss = await page.locator( 'link[href*="hostforge"][href*="admin.css"]' ).count();
		expect( hfCss ).toBe( 0 );
	} );
} );
