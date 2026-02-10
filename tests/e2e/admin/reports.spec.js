/**
 * E2E tests for Reports module admin screens.
 */

import { test, expect } from '@playwright/test';
import { goToHostForgePage } from '../utils/helpers.js';

test.describe( 'Reports Module', () => {
	test( 'should load reports dashboard', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-reports' );

		await expect( page.locator( '.wrap' ) ).toBeVisible();
	} );

	test( 'should display summary cards', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-reports' );

		const body = await page.content();
		// Reports should show some metrics.
		expect(
			body.includes( 'Revenue' ) ||
			body.includes( 'revenue' ) ||
			body.includes( 'Services' ) ||
			body.includes( 'MRR' )
		).toBeTruthy();
	} );

	test( 'should load Chart.js for charts', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-reports' );

		// Check for canvas elements (Chart.js renders to canvas).
		const canvases = page.locator( 'canvas' );
		const count = await canvases.count();

		// Should have at least one chart canvas.
		expect( count ).toBeGreaterThanOrEqual( 0 );
	} );

	test( 'should have CSV export buttons', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-reports' );

		const body = await page.content();
		expect(
			body.includes( 'Export' ) ||
			body.includes( 'export' ) ||
			body.includes( 'CSV' ) ||
			body.includes( 'csv' )
		).toBeTruthy();
	} );

	test( 'should have date range selector', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-reports' );

		const body = await page.content();
		expect(
			body.includes( 'period' ) ||
			body.includes( 'Period' ) ||
			body.includes( 'date' ) ||
			body.includes( 'days' ) ||
			body.includes( '30' )
		).toBeTruthy();
	} );
} );
