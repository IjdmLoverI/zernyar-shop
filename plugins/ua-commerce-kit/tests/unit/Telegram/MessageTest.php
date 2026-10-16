<?php
/**
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Tests\Telegram;

use UaCommerceKit\Telegram\Message;
use UaCommerceKit\Tests\TestCase;

final class MessageTest extends TestCase {

	/**
	 * @return array{event: string, number: string, total: string, items: list<array{name: string, quantity: int}>, customer: string, phone: string, delivery: string, payment: string, url: string}
	 */
	private function order( array $changes = array() ): array {
		return array_merge(
			array(
				'event'    => Message::NEW_ORDER,
				'number'   => '158',
				'total'    => '470 ₴',
				'items'    => array(
					array(
						'name'     => 'Колумбія Уїла - 250 г, Для фільтра',
						'quantity' => 1,
					),
				),
				'customer' => 'Олена Коваленко',
				'phone'    => '+380671234567',
				'delivery' => 'Відділення №3, Біла Церква',
				'payment'  => 'Накладений платіж',
				'url'      => 'https://zernyar.free.nf/wp-admin/admin.php?page=wc-orders&action=edit&id=158',
			),
			$changes
		);
	}

	public function test_new_order(): void {
		$text = Message::for_order( $this->order() );

		$this->assertStringStartsWith( '🛒 <b>New order #158</b> · 470 ₴', $text );
		$this->assertStringContainsString( '• Колумбія Уїла - 250 г, Для фільтра × 1', $text );
		$this->assertStringContainsString( '👤 Олена Коваленко, +380671234567', $text );
		$this->assertStringContainsString( '📦 Відділення №3, Біла Церква', $text );
		$this->assertStringContainsString( '&amp;action=edit&amp;id=158">Open the order</a>', $text );
	}

	public function test_paid_order(): void {
		$text = Message::for_order( $this->order( array( 'event' => Message::PAID ) ) );

		$this->assertStringStartsWith( '✅ <b>Order #158 is paid</b>', $text );
	}

	public function test_shopper_input_cannot_inject_markup(): void {
		$text = Message::for_order(
			$this->order(
				array(
					'customer' => '<a href="https://evil.example">Олена</a>',
					'items'    => array(
						array(
							'name'     => 'Кава <b>&</b>',
							'quantity' => 2,
						),
					),
				)
			)
		);

		$this->assertStringNotContainsString( 'href="https://evil.example"', $text );
		$this->assertStringContainsString( '&lt;a href=&quot;https://evil.example&quot;&gt;Олена&lt;/a&gt;', $text );
		$this->assertStringContainsString( 'Кава &lt;b&gt;&amp;&lt;/b&gt; × 2', $text );
	}

	public function test_no_delivery_line_for_orders_without_shipping(): void {
		$this->assertStringNotContainsString( '📦', Message::for_order( $this->order( array( 'delivery' => '' ) ) ) );
	}

	public function test_long_orders_fit_the_limit(): void {
		$items = array();
		for ( $i = 0; $i < 300; $i++ ) {
			$items[] = array(
				'name'     => "Дуже довга назва товару номер {$i}, щоб повідомлення стало задовгим",
				'quantity' => 1,
			);
		}
		$text = Message::for_order( $this->order( array( 'items' => $items ) ) );

		$this->assertLessThanOrEqual( 4096, mb_strlen( $text ) );
		$this->assertStringContainsString( 'Open the order', $text );
		$this->assertStringContainsString( '…', $text );
	}
}
