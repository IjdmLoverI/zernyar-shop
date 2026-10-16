const wordpress = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...wordpress,
	{
		// WordPress and WooCommerce provide these packages at runtime; the build maps them to globals.
		rules: {
			'import/no-unresolved': [
				'error',
				{ ignore: [ '^@wordpress/', '^@woocommerce/' ] },
			],
			'import/no-extraneous-dependencies': 'off',
		},
	},
	{
		files: [ 'scripts/**', 'tests/**' ],
		rules: {
			'no-console': 'off',
		},
	},
];
