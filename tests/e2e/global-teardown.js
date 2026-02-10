/**
 * Global teardown — Cleanup after all tests.
 */

import { test as teardown } from '@playwright/test';

teardown( 'cleanup test data', async ( { page } ) => {
	// Optional: cleanup test data created during E2E tests.
	// This runs after all test suites complete.
} );
