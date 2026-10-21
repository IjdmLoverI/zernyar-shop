import { expect, test } from '@playwright/test';

test( 'home page shows the hero and four featured coffees', async ( {
	page,
} ) => {
	await page.goto( '/' );
	await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveText(
		'Кава, обсмажена цього тижня'
	);
	await expect(
		page.locator( '.zernyar-featured .wc-block-product' )
	).toHaveCount( 4 );
	await expect( page.getByRole( 'navigation' ).first() ).toContainText(
		'Каталог'
	);
} );

test( 'catalog filters by roast', async ( { page } ) => {
	await page.goto( '/shop/' );
	const cards = page.locator( '.zernyar-catalog .wc-block-product' );
	await expect( cards ).toHaveCount( 16 );

	await page
		.locator( '.wp-block-woocommerce-product-filter-attribute' )
		.filter( { hasText: 'Обсмаження' } )
		.getByLabel( 'Темне', { exact: true } )
		.check();
	await expect( page ).toHaveURL( /filter_roast=dark/ );
	await expect( cards ).toHaveCount( 1 );
	await expect( cards.first() ).toContainText( 'Суматра Гайо' );
} );

test( 'a coffee page shows the price of the picked weight', async ( {
	page,
} ) => {
	await page.goto( '/product/ethiopia-yirgacheffe/' );
	await expect(
		page.locator( '.wp-block-woocommerce-product-price' ).first()
	).toContainText( 'від 420' );

	await page.selectOption( 'select[name=attribute_pa_weight]', '1kg' );
	await expect(
		page.locator( '.woocommerce-variation-price' )
	).toContainText( '1 480' );

	await page.click( 'button.single_add_to_cart_button' );
	await page.goto( '/cart/' );
	await expect( page.locator( '.wc-block-cart-items' ) ).toContainText(
		'Ефіопія Іргачеффе'
	);
	await expect( page.locator( '.wc-block-cart-items' ) ).toContainText(
		'1 кг'
	);
} );
