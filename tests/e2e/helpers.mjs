import { expect } from '@playwright/test';

export const BILA = 'db5c88ce-391c-11dd-90d9-001a92567626';
export const KYIV = '8d5a980d-391c-11dd-90d9-001a92567626';
export const BRANCH_3 = '0d545f5e-e1c2-11e3-8c4a-0050568002cf';

/**
 * Adds a coffee to the cart from its page.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string}                          slug Product slug.
 * @param {Object}                          pick Variation: weight and grind term slugs.
 * @param {string}                          base Language prefix, '' or '/en'.
 */
export async function addCoffee( page, slug, pick = {}, base = '' ) {
	await page.goto( `${ base }/product/${ slug }/` );
	if ( pick.weight ) {
		await page.selectOption(
			'select[name=attribute_pa_weight]',
			pick.weight
		);
	}
	if ( pick.grind ) {
		await page.selectOption(
			'select[name=attribute_pa_grind]',
			pick.grind
		);
	}
	await page.click( 'button.single_add_to_cart_button' );
	await expect(
		page
			.locator(
				'.woocommerce-message, .wc-block-components-notice-banner'
			)
			.first()
	).toBeVisible();
}

/**
 * Picks a city and a branch in the Nova Poshta picker.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string}                          city   What to type in the city field.
 * @param {string}                          branch What to type in the branch field.
 */
export async function pickBranch( page, city, branch ) {
	await page.locator( '#uack-np-city' ).click();
	await page.keyboard.type( city );
	await page.locator( '#uack-np-city-list [role=option]' ).first().waitFor();
	await page.keyboard.press( 'Enter' );
	await expect( page.locator( '#uack-np-warehouse' ) ).toBeEnabled();
	await page.waitForLoadState( 'networkidle' );

	await page.locator( '#uack-np-warehouse' ).click();
	await page.keyboard.type( branch );
	await page
		.locator( '#uack-np-warehouse-list [role=option]' )
		.first()
		.waitFor();
	await page.keyboard.press( 'Enter' );
	await page.waitForLoadState( 'networkidle' );
}

/**
 * Fills the contact fields. Call it after the branch: the address form re-renders when the rates change.
 *
 * @param {import('@playwright/test').Page} page
 * @param {Object}                          person
 */
export async function fillContact( page, person = {} ) {
	await page.fill( '#email', person.email ?? 'olena@example.com' );
	await page.fill( '#shipping-first_name', person.first ?? 'Олена' );
	await page.fill( '#shipping-last_name', person.last ?? 'Коваленко' );
	await page.fill( '#shipping-phone', person.phone ?? '067 123 45 67' );
}

/**
 * Picks a payment method by its gateway ID.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string}                          id   cod or uack_wayforpay.
 */
export async function payWith( page, id ) {
	await page
		.locator( `#radio-control-wc-payment-method-options-${ id }` )
		.check();
}

/**
 * Order state from the fixtures endpoint.
 *
 * @param {import('@playwright/test').APIRequestContext} request
 * @param {number|string}                                id
 */
export async function order( request, id ) {
	const response = await request.get( `/wp-json/uack-e2e/v1/order/${ id }` );
	expect( response.ok() ).toBeTruthy();
	return response.json();
}

/**
 * Telegram messages the fixtures caught.
 *
 * @param {import('@playwright/test').APIRequestContext} request
 */
export async function telegram( request ) {
	return ( await request.get( '/wp-json/uack-e2e/v1/telegram' ) ).json();
}

/**
 * Forgets the caught Telegram messages.
 *
 * @param {import('@playwright/test').APIRequestContext} request
 */
export async function clearTelegram( request ) {
	await request.delete( '/wp-json/uack-e2e/v1/telegram' );
}

/**
 * Order ID from a thank-you or order-pay URL.
 *
 * @param {string} url
 */
export function orderIdFrom( url ) {
	return Number(
		url.match( /order-(?:received|pay)\/(\d+)/ )?.[ 1 ] ??
			url.match( /[?&]order=(\d+)/ )?.[ 1 ]
	);
}
