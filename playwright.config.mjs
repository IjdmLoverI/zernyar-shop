import { defineConfig } from '@playwright/test';

export default defineConfig( {
	testDir: './tests/e2e',
	timeout: 60_000,
	expect: { timeout: 10_000 },
	fullyParallel: false,
	workers: 1,
	retries: process.env.CI ? 1 : 0,
	reporter: process.env.CI
		? [ [ 'list' ], [ 'html', { open: 'never' } ] ]
		: 'list',
	use: {
		baseURL: process.env.E2E_BASE_URL || 'http://localhost:8090',
		// Turns the API fixtures on for these requests only; see docker/mu-plugins/e2e-fixtures.php.
		extraHTTPHeaders: { 'X-Uack-E2E': '1' },
		locale: 'uk-UA',
		viewport: { width: 1280, height: 900 },
		trace: 'retain-on-failure',
		...( process.env.PW_CHANNEL
			? { channel: process.env.PW_CHANNEL }
			: {} ),
	},
} );
