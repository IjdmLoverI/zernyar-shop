import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { addCoffee } from './helpers.mjs';

// WooCommerce 11 keeps the closed mini-cart drawer in the page with aria-hidden and focusable
// buttons inside; that is core markup, reported upstream, and not part of this store's code.
const scan = ( page ) =>
	new AxeBuilder( { page } )
		.withTags( [ 'wcag2a', 'wcag2aa', 'wcag21aa' ] )
		.exclude( '.wc-block-mini-cart__drawer' )
		.analyze();

const serious = ( results ) =>
	results.violations
		.filter( ( v ) => [ 'serious', 'critical' ].includes( v.impact ) )
		.map(
			( v ) =>
				`${ v.id }: ${ v.nodes.map( ( n ) => n.target.join( ' ' ) ).join( ', ' ) }`
		);

for ( const path of [
	'/',
	'/shop/',
	'/product/ethiopia-yirgacheffe/',
	'/delivery/',
	'/en/',
] ) {
	test( `no serious accessibility issues on ${ path }`, async ( {
		page,
	} ) => {
		await page.goto( path, { waitUntil: 'networkidle' } );
		expect( serious( await scan( page ) ) ).toEqual( [] );
	} );
}

test( 'no serious accessibility issues on the checkout with the Nova Poshta picker', async ( {
	page,
} ) => {
	await addCoffee( page, 'kenya-nyeri' );
	await page.goto( '/checkout/' );
	await page.locator( '.uack-np-picker' ).waitFor();
	await page
		.locator( '.wc-block-components-totals-footer-item .wc-block-components-formatted-money-amount' )
		.waitFor();
	expect( serious( await scan( page ) ) ).toEqual( [] );
} );
