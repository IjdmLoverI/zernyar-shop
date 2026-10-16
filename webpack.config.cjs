const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );

module.exports = {
	...defaultConfig,
	entry: {
		checkout: './plugins/ua-commerce-kit/assets/src/checkout/index.js',
		wayforpay: './plugins/ua-commerce-kit/assets/src/wayforpay/index.js',
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'plugins/ua-commerce-kit/build' ),
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin(),
	],
};
