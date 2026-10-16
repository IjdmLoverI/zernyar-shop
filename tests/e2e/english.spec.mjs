import { expect, test } from '@playwright/test';
import { addCoffee, fillContact, payWith, pickBranch } from './helpers.mjs';

test( 'the language switcher links the same page in English', async ( {
	page,
} ) => {
	await page.goto( '/product/kenya-nyeri/' );
	await page.locator( '.zernyar-lang' ).getByText( 'EN' ).click();

	await expect( page ).toHaveURL( /\/en\/product\/kenya-nyeri\/$/ );
	await expect( page.locator( 'html' ) ).toHaveAttribute( 'lang', 'en-US' );
	await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveText(
		'Kenya Nyeri AA'
	);
	await expect( page.locator( '.zernyar-lang a[aria-current]' ) ).toHaveText(
		'EN'
	);
} );

test( 'catalog filters are English', async ( { page } ) => {
	await page.goto( '/en/shop/' );
	await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveText(
		'Shop'
	);
	const roast = page
		.locator( '.wp-block-woocommerce-product-filter-attribute' )
		.filter( { hasText: 'Roast' } );
	await expect( roast ).toContainText( 'Medium-dark' );
} );

test( 'checkout in English sends the shopper to an English payment page', async ( {
	page,
} ) => {
	await page.route( 'https://secure.wayforpay.com/**', ( route ) =>
		route.fulfill( { body: 'ok' } )
	);
	await addCoffee( page, 'colombia-huila', {}, '/en' );
	await page.goto( '/en/checkout/' );
	await expect( page.locator( '.uack-np-picker legend' ) ).toHaveText(
		'Where to deliver'
	);
	await expect(
		page.locator( '.wc-block-components-order-summary' )
	).toContainText( 'Weight: 250 g' );

	await pickBranch( page, 'Біла', '1' );
	await fillContact( page );
	await payWith( page, 'uack_wayforpay' );
	const form = page.waitForRequest( ( r ) =>
		r.url().startsWith( 'https://secure.wayforpay.com/pay' )
	);
	await page.click( '.wc-block-components-checkout-place-order-button' );
	expect(
		new URLSearchParams( ( await form ).postData() ).get( 'language' )
	).toBe( 'EN' );
} );
