<?php
/**
 * WP-CLI: wp zernyar seed.
 *
 * @package ZernyarDemo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Demo store commands.
 */
final class Zernyar_Demo_CLI {

	/**
	 * Imports the demo catalog, pages and menu. Safe to run again.
	 *
	 * ## EXAMPLES
	 *
	 *     wp zernyar seed
	 *
	 * @when after_wp_load
	 */
	public function seed(): void {
		$seeder = new Zernyar_Demo_Seeder( ZERNYAR_DEMO_CATALOG );
		$seeder->run();
		WP_CLI::success( 'Demo content imported.' );
	}
}
