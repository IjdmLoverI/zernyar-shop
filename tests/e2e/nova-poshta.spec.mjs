import { expect, test } from '@playwright/test';
import {
	addCoffee,
	BILA,
	BRANCH_3,
	clearTelegram,
	fillContact,
	KYIV,
	order,
	orderIdFrom,
	payWith,
	pickBranch,
	telegram,
} from './helpers.mjs';

test.beforeEach( async ( { request } ) => clearTelegram( request ) );

test( 'cash on delivery to a Nova Poshta branch', async ( {
	page,
	request,
} ) => {
	await addCoffee( page, 'colombia-huila', { grind: 'filter' } );
	await page.goto( '/checkout/' );
	await pickBranch( page, 'Біла', '3' );
	await fillContact( page );

	await expect(
		page.locator( '.wc-block-components-shipping-rates-control' )
	).toContainText( '90' );
	await expect(
		page.locator( '.wc-block-components-totals-footer-item' )
	).toContainText( '470' );

	await payWith( page, 'cod' );
	await page.click( '.wc-block-components-checkout-place-order-button' );
	await page.waitForURL( /order-received/ );
	await expect( page.locator( 'main' ) ).toContainText( 'Відділення №3' );

	const placed = await order( request, orderIdFrom( page.url() ) );
	expect( placed.status ).toBe( 'processing' );
	expect( placed.phone ).toBe( '+380671234567' );
	expect( placed.shipping.city ).toBe( 'Біла Церква' );
	expect( placed.shipping.postcode ).toBe( '09108' );
	expect( placed.nova_poshta ).toMatchObject( {
		city_ref: BILA,
		warehouse_ref: BRANCH_3,
		number: '3',
		type: 'branch',
	} );

	const messages = await telegram( request );
	expect( messages ).toHaveLength( 1 );
	expect( messages[ 0 ].text ).toContain( 'Нове замовлення' );
	expect( messages[ 0 ].text ).toContain( 'Відділення №3, Біла Церква' );
} );

test( 'a number finds the branch, and parcel lockers are listed', async ( {
	page,
} ) => {
	await addCoffee( page, 'kenya-nyeri' );
	await page.goto( '/checkout/' );
	await pickBranch( page, 'Біла', '31544' );
	await expect( page.locator( '#uack-np-warehouse' ) ).toHaveValue(
		/Поштомат №31544/
	);
} );

test( 'the combobox works from the keyboard', async ( { page } ) => {
	await addCoffee( page, 'kenya-nyeri' );
	await page.goto( '/checkout/' );
	const city = page.locator( '#uack-np-city' );
	await city.click();
	await page.keyboard.type( 'Ки' );
	await expect( city ).toHaveAttribute( 'aria-expanded', 'true' );
	await expect(
		page.locator( '#uack-np-city-list [role=option][aria-selected=true]' )
	).toContainText( 'Київ' );
	await page.keyboard.press( 'Escape' );
	await expect( city ).toHaveAttribute( 'aria-expanded', 'false' );
} );

test( 'place order without a branch stops on the branch field', async ( {
	page,
} ) => {
	await addCoffee( page, 'brazil-cerrado' );
	await page.goto( '/checkout/' );
	await page.locator( '#uack-np-city' ).click();
	await page.keyboard.type( 'Київ' );
	await page.locator( '#uack-np-city-list [role=option]' ).first().waitFor();
	await page.keyboard.press( 'Enter' );
	await page.waitForLoadState( 'networkidle' );
	await fillContact( page );

	let posted = false;
	page.on( 'request', ( r ) => {
		posted ||=
			r.method() === 'POST' &&
			r.url().includes( '/wc/store/v1/checkout' );
	} );
	await page.click( '.wc-block-components-checkout-place-order-button' );
	await expect( page.locator( '#uack-np-warehouse-error' ) ).toHaveText(
		'Оберіть відділення або поштомат Нової Пошти.'
	);
	await expect( page.locator( '#uack-np-warehouse' ) ).toBeFocused();
	expect( posted ).toBe( false );
} );

test.describe( 'the server checks what the browser sends', () => {
	const address = {
		first_name: 'Іван',
		last_name: 'Тест',
		country: 'UA',
		phone: '0671234567',
		email: 'ivan@example.com',
	};

	async function storeApi( page ) {
		await addCoffee( page, 'brazil-cerrado' );
		await page.goto( '/checkout/' );
		return page.evaluate(
			() => window.wcBlocksMiddlewareConfig.storeApiNonce
		);
	}

	async function post( page, nonce, path, body ) {
		return page.evaluate(
			async ( [ url, token, data ] ) => {
				const response = await fetch( url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						Nonce: token,
					},
					body: JSON.stringify( data ),
				} );
				return {
					status: response.status,
					code: ( await response.json() ).code,
				};
			},
			[ `/wp-json/wc/store/v1/${ path }`, nonce, body ]
		);
	}

	test( 'no branch', async ( { page } ) => {
		const nonce = await storeApi( page );
		const result = await post( page, nonce, 'checkout', {
			billing_address: address,
			shipping_address: address,
			payment_method: 'cod',
		} );
		expect( result ).toEqual( { status: 400, code: 'uack_np_no_branch' } );
	} );

	test( 'a branch from another city', async ( { page } ) => {
		const nonce = await storeApi( page );
		const result = await post( page, nonce, 'cart/extensions', {
			namespace: 'ua-commerce-kit-np',
			data: { city_ref: KYIV, city: 'Київ', warehouse_ref: BRANCH_3 },
		} );
		expect( result ).toEqual( { status: 400, code: 'uack_np_wrong_city' } );
	} );

	test( 'a phone that is not Ukrainian', async ( { page } ) => {
		const nonce = await storeApi( page );
		await post( page, nonce, 'cart/extensions', {
			namespace: 'ua-commerce-kit-np',
			data: {
				city_ref: BILA,
				city: 'Біла Церква',
				warehouse_ref: BRANCH_3,
			},
		} );
		const wrong = { ...address, phone: '+48 501 234 567' };
		const result = await post( page, nonce, 'checkout', {
			billing_address: wrong,
			shipping_address: wrong,
			payment_method: 'cod',
		} );
		expect( result ).toEqual( { status: 400, code: 'uack_np_phone' } );
	} );
} );
