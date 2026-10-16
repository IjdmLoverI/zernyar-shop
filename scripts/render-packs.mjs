// Renders the product images of the demo catalog: coffee pouches, boxes and the gift set.
// Usage: npm run render:packs [-- slug ...]
// Needs ffmpeg on PATH or in FFMPEG. Playwright's Chromium does the drawing; set
// PW_CHANNEL=chrome to use the installed Chrome instead.

import { execFileSync } from 'node:child_process';
import { mkdtempSync, readFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { chromium } from '@playwright/test';

const root = resolve( import.meta.dirname, '..' );
const catalog = JSON.parse(
	readFileSync(
		join( root, 'plugins/zernyar-demo/data/catalog.json' ),
		'utf8'
	)
);
const outDir = join( root, 'plugins/zernyar-demo/assets/products' );
const fontDir = join( root, 'themes/zernyar/assets/fonts' );
const font = ( file ) =>
	`data:font/woff2;base64,${ readFileSync( join( fontDir, file ) ).toString( 'base64' ) }`;
const bean = readFileSync(
	join( root, 'themes/zernyar/assets/icons/bean.svg' ),
	'utf8'
)
	.replace( /<!--.*?-->/s, '' )
	.replace(
		'stroke="currentColor"',
		'stroke="currentColor" aria-hidden="true"'
	);
const ffmpeg = process.env.FFMPEG || 'ffmpeg';
const only = process.argv.slice( 2 );

const roastLevel = { light: 1, medium: 2, 'medium-dark': 3, dark: 4 };

const term = ( attribute, slug ) =>
	catalog.attributes[ attribute ].terms[ slug ];

const escape = ( text ) =>
	String( text ).replace(
		/[&<>"]/g,
		( c ) =>
			( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' } )[ c ]
	);

const shortName = ( product ) =>
	product.name.replace( /,\s*\d+.*$/, '' ).replace( /^Дріп-пакети\s+/, '' );

function originLine( product ) {
	const origins = ( product.attributes?.origin ?? [] ).map( ( slug ) =>
		term( 'origin', slug )
	);
	const processing = ( product.attributes?.processing ?? [] ).map( ( slug ) =>
		term( 'processing', slug ).toLowerCase()
	);
	return [ origins.join( ' + ' ), processing.join( ' + ' ) ]
		.filter( Boolean )
		.join( ' · ' );
}

function dots( product ) {
	const level = roastLevel[ product.attributes?.roast?.[ 0 ] ] ?? 0;
	if ( ! level ) {
		return '';
	}
	const items = [ 1, 2, 3, 4 ]
		.map( ( i ) => `<i class="${ i <= level ? 'on' : '' }"></i>` )
		.join( '' );
	return `<span class="roast"><span class="dots">${ items }</span>${ escape( term( 'roast', product.attributes.roast[ 0 ] ).toLowerCase() ) }</span>`;
}

function label( product, { weight, compact = false } = {} ) {
	const title = product.image.label ?? shortName( product );
	return `
		<div class="label ${ compact ? 'compact' : '' }" style="--tone:${ product.image.color }">
			<div class="brand">${ bean }<span>Зерняр</span></div>
			<div class="body">
				<div class="name">${ escape( title ) }</div>
				${ compact ? '' : `<div class="origin">${ escape( originLine( product ) ) }</div>` }
				${ compact ? '' : `<div class="notes">${ escape( product.notes ?? '' ) }</div>` }
			</div>
			<div class="foot">${ compact ? '' : dots( product ) }<span class="weight">${ escape( weight ?? '' ) }</span></div>
		</div>`;
}

function pouch(
	product,
	{ weight = '250 г', scale = 1, x = 0, y = 0, z = 1 } = {}
) {
	return `
		<div class="pouch" style="--tone:${ product.image.color };transform:translate(${ x }px, ${ y }px) scale(${ scale });z-index:${ z }">
			<div class="crimp"></div>
			<div class="valve"></div>
			${ label( product, { weight, compact: scale < 0.8 } ) }
		</div>`;
}

function box( product ) {
	const count = product.sku.startsWith( 'ZR-DRP' )
		? '10 дріп-пакетів'
		: '100 шт.';
	return `
		<div class="box" style="--tone:${ product.image.color }">
			<div class="lid"></div>
			<div class="front">${ label( product, { weight: count } ) }</div>
		</div>`;
}

function gift( product ) {
	const find = ( sku ) => catalog.products.find( ( p ) => p.sku === sku );
	return `
		<div class="gift">
			${ pouch( find( 'ZR-ETH-YIR' ), { weight: '100 г', scale: 0.62, x: -230, y: 40, z: 1 } ) }
			${ pouch( find( 'ZR-BRA-CER' ), { weight: '100 г', scale: 0.62, x: 230, y: 40, z: 1 } ) }
			${ pouch( find( 'ZR-COL-HUI' ), { weight: '100 г', scale: 0.7, x: 0, y: 10, z: 2 } ) }
			<div class="ribbon" style="--tone:${ product.image.color }">Знайомство · 3 × 100 г</div>
		</div>`;
}

function page( product ) {
	const draw = { box, gift, pouch };
	const art = ( draw[ product.image.kind ] ?? pouch )( product );
	return `<!doctype html><html lang="uk"><head><meta charset="utf-8"><style>
		@font-face { font-family: Manrope; src: url(${ font( 'manrope-cyrillic.woff2' ) }) format('woff2'); font-weight: 400 800; unicode-range: U+0400-045F, U+0490-0491; }
		@font-face { font-family: Manrope; src: url(${ font( 'manrope-latin.woff2' ) }) format('woff2'); font-weight: 400 800; }
		@font-face { font-family: Manrope; src: url(${ font( 'manrope-cyrillic-ext.woff2' ) }) format('woff2'); font-weight: 400 800; unicode-range: U+0460-052F, U+20B4; }
		@font-face { font-family: Lora; src: url(${ font( 'lora-cyrillic.woff2' ) }) format('woff2'); font-weight: 400 700; unicode-range: U+0400-045F, U+0490-0491; }
		@font-face { font-family: Lora; src: url(${ font( 'lora-latin.woff2' ) }) format('woff2'); font-weight: 400 700; }
		* { box-sizing: border-box; margin: 0; }
		body { width: 1000px; height: 1000px; }
		#stage { position: relative; width: 1000px; height: 1000px; display: grid; place-items: center; overflow: hidden;
			background: radial-gradient(circle at 50% 38%, #f6efe5 0%, #ece2d4 55%, #e2d5c3 100%); font-family: Manrope, sans-serif; color: #1f1712; }
		#stage::after { content: ''; position: absolute; left: 50%; bottom: 120px; width: 620px; height: 70px; transform: translateX(-50%);
			background: radial-gradient(closest-side, rgb(31 23 18 / 28%), transparent); filter: blur(6px); z-index: 0; }
		.pouch { position: relative; z-index: 1; width: 500px; height: 700px; margin-top: -30px; border-radius: 26px 26px 46px 46px;
			background: linear-gradient(90deg, rgb(0 0 0 / 14%) 0%, transparent 14%, rgb(255 255 255 / 12%) 45%, transparent 70%, rgb(0 0 0 / 18%) 100%),
				linear-gradient(180deg, #d1ae84 0%, #c49d70 60%, #b98f62 100%);
			box-shadow: inset 0 -18px 30px rgb(0 0 0 / 12%), 0 30px 50px rgb(31 23 18 / 18%); transform-origin: 50% 100%; }
		.gift .pouch { position: absolute; left: 50%; top: 50%; margin: -350px 0 0 -250px; transform-origin: 50% 50%; }
		.crimp { position: absolute; top: 34px; left: 0; right: 0; height: 26px;
			background: repeating-linear-gradient(90deg, rgb(0 0 0 / 14%) 0 3px, transparent 3px 9px); opacity: .7; }
		.valve { position: absolute; top: 96px; left: 50%; width: 46px; height: 46px; margin-left: -23px; border-radius: 50%;
			background: radial-gradient(circle, #2a201a 0 7px, #3a2d25 8px 14px, #b88d61 15px 100%); box-shadow: inset 0 0 0 2px rgb(0 0 0 / 15%); }
		.label { position: absolute; left: 50px; right: 50px; bottom: 70px; height: 390px; display: flex; flex-direction: column;
			background: #fbf7f0; border-radius: 18px; overflow: hidden; box-shadow: 0 1px 0 rgb(0 0 0 / 6%); }
		.brand { display: flex; align-items: center; gap: 12px; padding: 22px 28px; background: var(--tone); color: #fbf7f0;
			font-family: Lora, serif; font-weight: 700; font-size: 30px; letter-spacing: .02em; }
		.brand svg { width: 34px; height: 34px; }
		.body { flex: 1; padding: 26px 28px 0; }
		.name { font-family: Lora, serif; font-weight: 600; font-size: 44px; line-height: 1.08; letter-spacing: -.01em; }
		.origin { margin-top: 16px; font-size: 17px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--tone); }
		.notes { margin-top: 14px; font-size: 22px; color: #5e5048; }
		.foot { display: flex; align-items: center; justify-content: space-between; padding: 18px 28px 22px; border-top: 2px dashed #e2d6c6;
			font-size: 20px; font-weight: 700; }
		.roast { display: flex; align-items: center; gap: 10px; color: #5e5048; font-weight: 600; font-size: 18px; }
		.dots { display: flex; gap: 5px; }
		.dots i { width: 14px; height: 14px; border-radius: 50%; border: 2px solid var(--tone); }
		.dots i.on { background: var(--tone); }
		.weight { margin-left: auto; font-size: 26px; }
		.label.compact { height: 320px; }
		.label.compact .name { font-size: 40px; }
		.box { position: relative; z-index: 1; width: 640px; margin-top: -10px; }
		.box .lid { height: 70px; margin: 0 22px; background: linear-gradient(180deg, color-mix(in srgb, var(--tone) 70%, #000) 0%, var(--tone) 100%);
			clip-path: polygon(4% 0, 96% 0, 100% 100%, 0 100%); }
		.box .front { position: relative; height: 470px; border-radius: 0 0 14px 14px; background: linear-gradient(90deg, rgb(0 0 0 / 10%), transparent 20%, transparent 80%, rgb(0 0 0 / 14%)), var(--tone);
			box-shadow: 0 30px 50px rgb(31 23 18 / 18%); }
		.box .label { left: 40px; right: 40px; top: 40px; bottom: 40px; height: auto; }
		.box[style*="#F7F2EB"] .front { background: linear-gradient(90deg, rgb(0 0 0 / 6%), transparent 20%, transparent 80%, rgb(0 0 0 / 9%)), #efe6da; }
		.box[style*="#F7F2EB"] .brand { background: #a8481f; }
		.box[style*="#F7F2EB"] .lid { background: linear-gradient(180deg, #cdbfae, #e2d6c6); }
		.gift { position: relative; z-index: 1; width: 1000px; height: 1000px; }
		.ribbon { position: absolute; left: 50%; bottom: 120px; transform: translateX(-50%); z-index: 3; padding: 14px 30px; border-radius: 999px;
			background: var(--tone); color: #fbf7f0; font-family: Lora, serif; font-size: 30px; font-weight: 700; white-space: nowrap; box-shadow: 0 10px 24px rgb(31 23 18 / 22%); }
	</style></head><body><div id="stage">${ art }</div></body></html>`;
}

const products = catalog.products.filter(
	( p ) =>
		[ 'pouch', 'box', 'gift' ].includes( p.image.kind ) &&
		( only.length === 0 || only.includes( p.slug ) )
);

const browser = await chromium.launch(
	process.env.PW_CHANNEL ? { channel: process.env.PW_CHANNEL } : {}
);
const tab = await browser.newPage( {
	viewport: { width: 1000, height: 1000 },
} );
const work = mkdtempSync( join( tmpdir(), 'zernyar-packs-' ) );

try {
	for ( const product of products ) {
		await tab.setContent( page( product ), { waitUntil: 'load' } );
		await tab.evaluate( () => document.fonts.ready );
		const png = join( work, `${ product.slug }.png` );
		await tab.locator( '#stage' ).screenshot( { path: png } );
		const webp = join( outDir, `${ product.slug }.webp` );
		execFileSync( ffmpeg, [
			'-hide_banner',
			'-loglevel',
			'error',
			'-y',
			'-i',
			png,
			'-c:v',
			'libwebp',
			'-quality',
			'82',
			webp,
		] );
		console.log( `${ product.slug }.webp` );
	}
} finally {
	await browser.close();
	rmSync( work, { recursive: true, force: true } );
}
