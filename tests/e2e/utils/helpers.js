/**
 * E2E test helper utilities.
 */

/**
 * Navigate to a HostForge admin page.
 *
 * @param {import('@playwright/test').Page} page    - Playwright page.
 * @param {string}                          subpage - The admin page slug (e.g., 'hostforge', 'hostforge-servers').
 */
export async function goToHostForgePage( page, subpage = 'hostforge' ) {
	await page.goto( `/wp-admin/admin.php?page=${ subpage }` );
	await page.waitForLoadState( 'networkidle' );
}

/**
 * Navigate to WooCommerce product creation page.
 *
 * @param {import('@playwright/test').Page} page - Playwright page.
 */
export async function goToNewProduct( page ) {
	await page.goto( '/wp-admin/post-new.php?post_type=product' );
	await page.waitForLoadState( 'networkidle' );
}

/**
 * Navigate to the frontend shop.
 *
 * @param {import('@playwright/test').Page} page - Playwright page.
 */
export async function goToShop( page ) {
	await page.goto( '/shop/' );
	await page.waitForLoadState( 'networkidle' );
}

/**
 * Navigate to My Account page.
 *
 * @param {import('@playwright/test').Page} page     - Playwright page.
 * @param {string}                          endpoint - My Account endpoint (e.g., 'hosting-services').
 */
export async function goToMyAccount( page, endpoint = '' ) {
	const path = endpoint ? `/my-account/${ endpoint }/` : '/my-account/';
	await page.goto( path );
	await page.waitForLoadState( 'networkidle' );
}

/**
 * Wait for AJAX request to complete.
 *
 * @param {import('@playwright/test').Page} page - Playwright page.
 */
export async function waitForAjax( page ) {
	await page.waitForResponse(
		( response ) =>
			response.url().includes( 'admin-ajax.php' ) &&
			response.status() === 200
	);
}

/**
 * Make a REST API request and return the JSON response.
 *
 * @param {import('@playwright/test').APIRequestContext} request - API context.
 * @param {string}                                       endpoint - REST endpoint path.
 * @return {Promise<object>} JSON response.
 */
export async function apiGet( request, endpoint ) {
	const response = await request.get( `/wp-json/hostforge/v1/${ endpoint }` );
	return response.json();
}

/**
 * Create a WooCommerce product via WP-CLI (when inside wp-env).
 *
 * @param {import('@playwright/test').Page} page    - Playwright page.
 * @param {string}                          name    - Product name.
 * @param {string}                          type    - Product type.
 * @param {string}                          price   - Regular price.
 * @return {Promise<void>}
 */
export async function createTestProduct( page, name, type = 'simple', price = '9.99' ) {
	await page.goto( '/wp-admin/post-new.php?post_type=product' );
	await page.waitForLoadState( 'networkidle' );

	// Set product name.
	await page.locator( '#title' ).fill( name );

	// Set product type if dropdown is available.
	const typeSelector = page.locator( '#product-type' );
	if ( await typeSelector.isVisible() ) {
		await typeSelector.selectOption( type );
	}

	// Set price.
	const priceField = page.locator( '#_regular_price' );
	if ( await priceField.isVisible() ) {
		await priceField.fill( price );
	}

	// Publish.
	await page.locator( '#publish' ).click();
	await page.waitForLoadState( 'networkidle' );
}
