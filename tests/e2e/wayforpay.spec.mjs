import { createHmac } from 'node:crypto';
import { expect, test } from '@playwright/test';
import {
	addCoffee,
	clearTelegram,
	fillContact,
	order,
	payWith,
	pickBranch,
	telegram,
} from './helpers.mjs';

const SECRET = 'flk3409refn54t54t*FNJRET';
const sign = ( values ) =>
	createHmac( 'md5', SECRET ).update( values.join( ';' ) ).digest( 'hex' );

/**
 * Places a card order and stops at the WayForPay page.
 *
 * @param  page
 * @param  base
 * @return {Promise<{ id: number, key: string, form: URLSearchParams }>}
 */
async function placeCardOrder( page, base = '' ) {
	await page.route( 'https://secure.wayforpay.com/**', ( route ) =>
		route.fulfill( { contentType: 'text/html', body: '<p>WayForPay</p>' } )
	);
	await addCoffee( page, 'ethiopia-yirgacheffe', {}, base );
	await page.goto( `${ base }/checkout/` );
	await pickBranch( page, 'Біла', '3' );
	await fillContact( page );
	await payWith( page, 'uack_wayforpay' );

	const payRequest = page.waitForRequest( ( r ) =>
		r.url().includes( 'uack_wayforpay_pay' )
	);
	const formRequest = page.waitForRequest( ( r ) =>
		r.url().startsWith( 'https://secure.wayforpay.com/pay' )
	);
	await page.click( '.wc-block-components-checkout-place-order-button' );
	const pay = new URL( ( await payRequest ).url() );
	const form = new URLSearchParams( ( await formRequest ).postData() );
	return {
		id: Number( pay.searchParams.get( 'order' ) ),
		key: pay.searchParams.get( 'key' ),
		form,
	};
}

test.beforeEach( async ( { request } ) => clearTelegram( request ) );

test( 'the payment form is signed the way WayForPay checks it', async ( {
	page,
} ) => {
	const { form } = await placeCardOrder( page );

	expect( form.get( 'merchantAccount' ) ).toBe( 'test_merch_n1' );
	expect( form.get( 'amount' ) ).toBe( '510.00' );
	expect( form.getAll( 'productName[]' ) ).toEqual( [
		'Ефіопія Іргачеффе - 250 г, У зернах',
		'Нова Пошта: відділення, поштомат або пункт',
	] );
	expect( form.get( 'language' ) ).toBe( 'UA' );
	expect( form.get( 'returnUrl' ) ).toContain( 'uack_wayforpay_return' );

	const signed = [
		'merchantAccount',
		'merchantDomainName',
		'orderReference',
		'orderDate',
		'amount',
		'currency',
	]
		.map( ( field ) => form.get( field ) )
		.concat(
			form.getAll( 'productName[]' ),
			form.getAll( 'productCount[]' ),
			form.getAll( 'productPrice[]' )
		);
	expect( form.get( 'merchantSignature' ) ).toBe( sign( signed ) );
} );

test( 'an approved payment completes the order', async ( {
	page,
	request,
} ) => {
	const { id, key } = await placeCardOrder( page );
	await page.setExtraHTTPHeaders( {
		'X-Uack-E2E': '1',
		'X-Uack-E2E-WayForPay': 'approved',
	} );
	await page.goto(
		`/wc-api/uack_wayforpay_return/?order=${ id }&key=${ key }`
	);

	await expect( page ).toHaveURL( /order-received/ );
	const paid = await order( request, id );
	expect( paid.status ).toBe( 'processing' );
	expect( paid.paid ).toBe( true );
	expect( paid.transaction_id ).toBe( paid.reference );

	const messages = ( await telegram( request ) ).map( ( m ) => m.text );
	expect( messages[ 0 ] ).toContain( 'очікує оплату' );
	expect( messages[ 1 ] ).toContain( 'оплачено' );
} );

test( 'a declined payment sends the shopper back to pay again', async ( {
	page,
	request,
} ) => {
	const { id, key } = await placeCardOrder( page );
	await page.setExtraHTTPHeaders( {
		'X-Uack-E2E': '1',
		'X-Uack-E2E-WayForPay': 'declined',
	} );
	await page.goto(
		`/wc-api/uack_wayforpay_return/?order=${ id }&key=${ key }`
	);

	await expect( page ).toHaveURL( /order-pay/ );
	await expect( page.locator( 'main' ) ).toContainText( 'Оплата не пройшла' );
	expect( ( await order( request, id ) ).status ).toBe( 'failed' );
} );

test( 'the thank-you page waits for a pending payment and reloads when it is approved', async ( {
	page,
	request,
} ) => {
	const { id, key } = await placeCardOrder( page );
	await page.setExtraHTTPHeaders( {
		'X-Uack-E2E': '1',
		'X-Uack-E2E-WayForPay': 'pending',
	} );
	await page.goto(
		`/wc-api/uack_wayforpay_return/?order=${ id }&key=${ key }`
	);

	await expect( page.locator( '.uack-wfp-status' ) ).toContainText(
		'Чекаємо, поки WayForPay підтвердить оплату'
	);
	expect( ( await order( request, id ) ).status ).toBe( 'on-hold' );

	await page.setExtraHTTPHeaders( {
		'X-Uack-E2E': '1',
		'X-Uack-E2E-WayForPay': 'approved',
	} );
	await expect( page.locator( '.uack-wfp-status' ) ).toHaveCount( 0, {
		timeout: 30_000,
	} );
	expect( ( await order( request, id ) ).status ).toBe( 'processing' );
} );

test( 'the service callback is checked and answered with a signature', async ( {
	page,
	request,
} ) => {
	const { id } = await placeCardOrder( page );
	const { reference, total } = await order( request, id );
	const callback = {
		merchantAccount: 'test_merch_n1',
		orderReference: reference,
		amount: total,
		currency: 'UAH',
		authCode: '541963',
		cardPan: '41****8217',
		transactionStatus: 'Approved',
		reasonCode: 1100,
	};
	callback.merchantSignature = sign(
		[
			'merchantAccount',
			'orderReference',
			'amount',
			'currency',
			'authCode',
			'cardPan',
			'transactionStatus',
			'reasonCode',
		].map( ( f ) => callback[ f ] )
	);

	const tampered = await request.post( '/wc-api/uack_wayforpay_callback/', {
		data: { ...callback, amount: 1 },
	} );
	expect( tampered.status() ).toBe( 400 );
	expect( ( await order( request, id ) ).paid ).toBe( false );

	const accepted = await request.post( '/wc-api/uack_wayforpay_callback/', {
		data: callback,
	} );
	expect( accepted.status() ).toBe( 200 );
	const reply = await accepted.json();
	expect( reply.status ).toBe( 'accept' );
	expect( reply.signature ).toBe(
		sign( [ reference, 'accept', reply.time ] )
	);
	expect( ( await order( request, id ) ).status ).toBe( 'processing' );
} );
