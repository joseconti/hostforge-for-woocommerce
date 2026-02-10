/**
 * Global setup — Authenticates as admin and saves session state.
 */

import { test as setup, expect } from '@playwright/test';
import path from 'path';

const authFile = path.join( __dirname, '.auth/admin.json' );

setup( 'authenticate as admin', async ( { page } ) => {
	// Navigate to WordPress login.
	await page.goto( '/wp-login.php' );

	// Wait for login form to be ready.
	await page.locator( '#user_login' ).waitFor( { state: 'visible' } );
	await page.locator( '#user_pass' ).waitFor( { state: 'visible' } );

	// Clear and fill login form fields with explicit clicks.
	await page.locator( '#user_login' ).click();
	await page.locator( '#user_login' ).fill( 'admin' );

	await page.locator( '#user_pass' ).click();
	await page.locator( '#user_pass' ).fill( 'password' );

	await page.locator( '#wp-submit' ).click();

	// Wait for dashboard to load.
	await page.waitForURL( /wp-admin/, { timeout: 30000 } );
	await expect( page.locator( '#wpadminbar' ) ).toBeVisible();

	// Save signed-in state to file.
	await page.context().storageState( { path: authFile } );
} );
