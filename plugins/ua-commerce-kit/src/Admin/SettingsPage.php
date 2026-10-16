<?php
/**
 * WooCommerce → Settings → UA Commerce Kit.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Admin;

use WC_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Settings tab with the module switches.
 */
final class SettingsPage extends WC_Settings_Page {

	public const ID = 'ua-commerce-kit';

	/**
	 * Registers the tab.
	 */
	public function __construct() {
		$this->id    = self::ID;
		$this->label = __( 'UA Commerce Kit', 'ua-commerce-kit' );
		parent::__construct();
	}

	/**
	 * Fields of the main section.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_settings_for_default_section() {
		return array(
			array(
				'id'    => 'uack_modules',
				'type'  => 'title',
				'title' => __( 'Modules', 'ua-commerce-kit' ),
				'desc'  => __( 'Switch off what this store does not use. Tools → Site Health checks that the services can be reached from this server.', 'ua-commerce-kit' ),
			),
			array(
				'id'      => 'uack_nova_poshta_enabled',
				'type'    => 'checkbox',
				'title'   => __( 'Nova Poshta', 'ua-commerce-kit' ),
				'desc'    => __( 'Delivery to Nova Poshta branches and parcel lockers', 'ua-commerce-kit' ),
				'default' => 'yes',
			),
			array(
				'id'      => 'uack_wayforpay_enabled',
				'type'    => 'checkbox',
				'title'   => __( 'WayForPay', 'ua-commerce-kit' ),
				'desc'    => __( 'Card payments through WayForPay', 'ua-commerce-kit' ),
				'default' => 'yes',
			),
			array(
				'id'      => 'uack_telegram_enabled',
				'type'    => 'checkbox',
				'title'   => __( 'Telegram', 'ua-commerce-kit' ),
				'desc'    => __( 'Order notifications in Telegram', 'ua-commerce-kit' ),
				'default' => 'yes',
			),
			array(
				'id'   => 'uack_modules',
				'type' => 'sectionend',
			),
		);
	}
}
