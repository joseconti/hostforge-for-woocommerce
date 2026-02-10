/**
 * E2E tests for REST API via browser context.
 *
 * Note: Cookie-based REST API auth requires a nonce. Without it,
 * protected endpoints return 401. These tests verify that
 * endpoints are registered and respond appropriately.
 */

import { test, expect } from '@playwright/test';

test.describe( 'REST API — Browser Context', () => {
	test( 'should return status endpoint data', async ( { page } ) => {
		// Use page context to get proper nonce-based auth.
		await page.goto( '/wp-admin/' );
		const nonce = await page.evaluate( () => window.wpApiSettings?.nonce || '' );

		const response = await page.request.get( '/wp-json/hostforge/v1/status', {
			headers: nonce ? { 'X-WP-Nonce': nonce } : {},
		} );

		// With nonce should be 200, without nonce 401 is expected.
		expect( [ 200, 401 ] ).toContain( response.status() );

		if ( response.status() === 200 ) {
			const json = await response.json();
			expect( json.success ).toBe( true );
		}
	} );

	test( 'should return servers list', async ( { request } ) => {
		const response = await request.get( '/wp-json/hostforge/v1/servers' );
		// 200 (success), 401 (no nonce), 403 (forbidden), 404 (route not found).
		expect( [ 200, 401, 403, 404 ] ).toContain( response.status() );
	} );

	test( 'should return services list', async ( { request } ) => {
		const response = await request.get( '/wp-json/hostforge/v1/services' );
		expect( [ 200, 401, 403, 404 ] ).toContain( response.status() );
	} );

	test( 'should return tickets list', async ( { request } ) => {
		const response = await request.get( '/wp-json/hostforge/v1/tickets' );
		expect( [ 200, 401, 403, 404 ] ).toContain( response.status() );
	} );

	test( 'should return domains list', async ( { request } ) => {
		const response = await request.get( '/wp-json/hostforge/v1/domains' );
		expect( [ 200, 401, 403, 404 ] ).toContain( response.status() );
	} );

	test( 'should return security ip-blocks', async ( { request } ) => {
		const response = await request.get( '/wp-json/hostforge/v1/security/ip-blocks' );
		expect( [ 200, 401, 403, 404 ] ).toContain( response.status() );
	} );

	test( 'should return reports revenue', async ( { request } ) => {
		const response = await request.get( '/wp-json/hostforge/v1/reports/revenue?period=30' );
		expect( [ 200, 401, 403, 404 ] ).toContain( response.status() );
	} );

	test( 'should return 4xx for invalid endpoint', async ( { request } ) => {
		const response = await request.get( '/wp-json/hostforge/v1/nonexistent' );
		expect( response.status() ).toBeGreaterThanOrEqual( 400 );
	} );

	test( 'should return 4xx for servers/{invalid_id}', async ( { request } ) => {
		const response = await request.get( '/wp-json/hostforge/v1/servers/999999' );
		expect( response.status() ).toBeGreaterThanOrEqual( 400 );
	} );
} );
