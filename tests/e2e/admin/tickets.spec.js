/**
 * E2E tests for Support Desk — Tickets admin screens.
 */

import { test, expect } from '@playwright/test';
import { goToHostForgePage } from '../utils/helpers.js';

test.describe( 'Support Desk — Tickets', () => {
	test( 'should load the tickets list page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-tickets' );

		await expect( page.locator( '.wrap' ) ).toBeVisible();
	} );

	test( 'should have a new ticket button', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-tickets' );

		const newBtn = page.locator( 'a:has-text("New"), .page-title-action' );
		const count = await newBtn.count();
		expect( count ).toBeGreaterThan( 0 );
	} );

	test( 'should load the new ticket form', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-tickets&action=new' );

		const body = await page.content();
		expect(
			body.includes( 'Subject' ) ||
			body.includes( 'subject' ) ||
			body.includes( 'Message' ) ||
			body.includes( 'message' )
		).toBeTruthy();
	} );

	test( 'should show status filters on ticket list', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-tickets' );

		const body = await page.content();
		const statuses = [ 'Open', 'Answered', 'Closed' ];
		const found = statuses.filter( ( s ) => body.includes( s ) );
		expect( found.length ).toBeGreaterThan( 0 );
	} );

	test( 'should load departments page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-tickets&action=departments' );

		const body = await page.content();
		expect(
			body.includes( 'Department' ) ||
			body.includes( 'department' )
		).toBeTruthy();
	} );

	test( 'should load knowledge base page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-knowledge-base' );

		const body = await page.content();
		expect(
			body.includes( 'Knowledge' ) ||
			body.includes( 'knowledge' ) ||
			body.includes( 'Article' )
		).toBeTruthy();
	} );

	test( 'should load canned responses page', async ( { page } ) => {
		await goToHostForgePage( page, 'hostforge-tickets&action=canned' );

		const body = await page.content();
		expect(
			body.includes( 'Canned' ) ||
			body.includes( 'canned' ) ||
			body.includes( 'Response' )
		).toBeTruthy();
	} );
} );
