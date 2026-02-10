/**
 * HostForge for WooCommerce — Playwright E2E Configuration.
 *
 * @see https://playwright.dev/docs/test-configuration
 */

import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.WP_BASE_URL || 'http://localhost:8888';

export default defineConfig( {
	testDir: './tests/e2e',
	fullyParallel: false,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: 1,
	reporter: process.env.CI ? 'github' : 'html',
	timeout: 60_000,
	expect: {
		timeout: 10_000,
	},

	use: {
		baseURL,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},

	projects: [
		{
			name: 'setup',
			testMatch: /global-setup\.js/,
			teardown: 'cleanup',
			use: {
				storageState: undefined,
			},
		},
		{
			name: 'cleanup',
			testMatch: /global-teardown\.js/,
			use: {
				storageState: undefined,
			},
		},
		{
			name: 'admin',
			testDir: './tests/e2e/admin',
			dependencies: [ 'setup' ],
			use: {
				...devices[ 'Desktop Chrome' ],
				storageState: 'tests/e2e/.auth/admin.json',
			},
		},
		{
			name: 'frontend',
			testDir: './tests/e2e/frontend',
			dependencies: [ 'setup' ],
			use: {
				...devices[ 'Desktop Chrome' ],
				storageState: 'tests/e2e/.auth/admin.json',
			},
		},
	],
} );
