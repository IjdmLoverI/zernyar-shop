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
	 * Sections of the tab.
	 *
	 * @return array<string, string>
	 */
	protected function get_own_sections() {
		return array(
			''            => __( 'Modules', 'ua-commerce-kit' ),
			'nova_poshta' => __( 'Nova Poshta', 'ua-commerce-kit' ),
			'telegram'    => __( 'Telegram', 'ua-commerce-kit' ),
		);
	}

	/**
	 * Fields of the Telegram section.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_settings_for_telegram_section() {
		return array(
			array(
				'id'    => 'uack_telegram',
				'type'  => 'title',
				'title' => __( 'Telegram', 'ua-commerce-kit' ),
				'desc'  => __( 'Create a bot with @BotFather, paste its token here, write anything to the bot and pick your chat below. The UACK_TELEGRAM_TOKEN and UACK_TELEGRAM_CHAT_ID constants win over these fields.', 'ua-commerce-kit' ),
			),
			array(
				'id'      => 'uack_telegram_token',
				'type'    => 'password',
				'title'   => __( 'Bot token', 'ua-commerce-kit' ),
				'default' => '',
			),
			array(
				'id'      => 'uack_telegram_chat_id',
				'type'    => 'text',
				'title'   => __( 'Chat ID', 'ua-commerce-kit' ),
				'desc'    => __( 'Your chat with the bot, or a group the bot is in.', 'ua-commerce-kit' ),
				'default' => '',
			),
			array(
				'id'            => 'uack_telegram_new',
				'type'          => 'checkbox',
				'title'         => __( 'Send', 'ua-commerce-kit' ),
				'desc'          => __( 'New orders', 'ua-commerce-kit' ),
				'default'       => 'yes',
				'checkboxgroup' => 'start',
			),
			array(
				'id'            => 'uack_telegram_paid',
				'type'          => 'checkbox',
				'desc'          => __( 'Online payments', 'ua-commerce-kit' ),
				'default'       => 'yes',
				'checkboxgroup' => 'end',
			),
			array(
				'id'   => 'uack_telegram_tools',
				'type' => 'uack_telegram_tools',
			),
			array(
				'id'   => 'uack_telegram',
				'type' => 'sectionend',
			),
		);
	}

	/**
	 * Fields of the Nova Poshta section.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_settings_for_nova_poshta_section() {
		return array(
			array(
				'id'    => 'uack_np',
				'type'  => 'title',
				'title' => __( 'Nova Poshta', 'ua-commerce-kit' ),
				'desc'  => __( 'Add the Nova Poshta method to a shipping zone in WooCommerce → Settings → Shipping; its cost is set there.', 'ua-commerce-kit' ),
			),
			array(
				'id'       => 'uack_np_api_key',
				'type'     => 'password',
				'title'    => __( 'API key', 'ua-commerce-kit' ),
				'desc'     => __( 'From the Nova Poshta business account → Settings → Security. City and branch lookups and delivery prices work without it.', 'ua-commerce-kit' ),
				'desc_tip' => false,
				'default'  => '',
			),
			array(
				'id'      => 'uack_np_sender_city',
				'type'    => 'text',
				'title'   => __( 'Sender city', 'ua-commerce-kit' ),
				'desc'    => __( 'Where parcels are sent from. Used to ask Nova Poshta for the delivery price.', 'ua-commerce-kit' ),
				'default' => 'Київ',
			),
			array(
				'id'      => 'uack_np_hide_address',
				'type'    => 'checkbox',
				'title'   => __( 'Address fields', 'ua-commerce-kit' ),
				'desc'    => __( 'Hide street, postcode and region for Ukraine: the picked branch replaces them', 'ua-commerce-kit' ),
				'default' => 'yes',
			),
			array(
				'id'   => 'uack_np',
				'type' => 'sectionend',
			),
		);
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
