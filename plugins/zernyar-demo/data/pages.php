<?php
/**
 * Content of the demo pages, as block markup.
 *
 * @package ZernyarDemo
 */

defined( 'ABSPATH' ) || exit;

$zernyar_demo_p = static fn ( string $text ): string => "<!-- wp:paragraph -->\n<p>{$text}</p>\n<!-- /wp:paragraph -->";

$zernyar_demo_h = static fn ( string $text ): string => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$text}</h2>\n<!-- /wp:heading -->";

$zernyar_demo_list = static function ( array $items ): string {
	$html = '';
	foreach ( $items as $item ) {
		$html .= "<!-- wp:list-item -->\n<li>{$item}</li>\n<!-- /wp:list-item -->\n";
	}
	return "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n{$html}</ul>\n<!-- /wp:list -->";
};

$zernyar_demo_notice = "<!-- wp:group {\"className\":\"zernyar-demo-notice\",\"backgroundColor\":\"base-2\",\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group zernyar-demo-notice has-base-2-background-color has-background\">"
	. $zernyar_demo_p( '<strong>Це демо-магазин для портфоліо.</strong> Обсмажувальні «Зерняр» не існує, оплата працює в тестовому режимі, а замовлення нікому не відправляються. Не вводьте справжні дані картки.' )
	. "</div>\n<!-- /wp:group -->";

return array(
	'about'    => implode(
		"\n\n",
		array(
			$zernyar_demo_p( '«Зерняр» — невелика обсмажувальня в Києві. Ми купуємо зелену каву в імпортерів, які знають ферми, дегустуємо кожен лот і шукаємо профіль обсмаження, який розкриє його власний смак.' ),
			$zernyar_demo_h( 'Обсмажуємо щовівторка' ),
			$zernyar_demo_p( 'Одна партія — 12 кілограмів, тому кава не лежить на полиці місяцями. У вівторок обсмажуємо, у середу пакуємо й відправляємо. До вас вона приїжджає на третій-п\'ятий день після обсмаження, коли смак уже розкрився.' ),
			$zernyar_demo_h( 'Мелемо під ваш спосіб' ),
			$zernyar_demo_p( 'Кожну каву можна замовити в зернах або змелену для еспресо, фільтра чи турки. Якщо маєте кавомолку, беріть у зернах: мелена кава швидше втрачає аромат.' ),
			$zernyar_demo_notice,
		)
	),
	'delivery' => implode(
		"\n\n",
		array(
			$zernyar_demo_h( 'Доставка' ),
			$zernyar_demo_list(
				array(
					'Новою Поштою у відділення або поштомат по всій Україні.',
					'Відправляємо з середи по п\'ятницю, посилка їде 1–3 дні.',
					'Вартість — за тарифами Нової Пошти, ви бачите її під час оформлення замовлення.',
				)
			),
			$zernyar_demo_h( 'Оплата' ),
			$zernyar_demo_list(
				array(
					'Карткою онлайн через WayForPay: Visa, Mastercard, Apple Pay і Google Pay.',
					'Накладеним платежем у відділенні під час отримання.',
				)
			),
			$zernyar_demo_p( 'Після оплати ви отримаєте лист із підтвердженням, а ми — сповіщення в Telegram і почнемо збирати замовлення.' ),
			$zernyar_demo_notice,
		)
	),
	'contacts' => implode(
		"\n\n",
		array(
			$zernyar_demo_p( 'Пишіть на <a href="mailto:hello@zernyar.example">hello@zernyar.example</a>. Відповідаємо в будні з 10:00 до 18:00.' ),
			$zernyar_demo_p( 'Обсмажувальня в Києві, але самовивозу немає: усе відправляємо Новою Поштою.' ),
			$zernyar_demo_notice,
		)
	),
	'terms'    => implode(
		"\n\n",
		array(
			$zernyar_demo_p( 'Цей сайт — демонстраційний проєкт розробника. Бренд, товари, ціни й контакти вигадані.' ),
			$zernyar_demo_list(
				array(
					'Оплата працює через тестовий мерчант WayForPay: гроші не списуються, справжні картки вводити не потрібно.',
					'Замовлення зберігаються в адмінці, але нікому не відправляються.',
					'Імена, телефони й адреси, введені під час оформлення, видно в адмінці демо-магазину. Не вводьте справжні дані.',
				)
			),
			$zernyar_demo_p( 'Фотографії з <a href="https://unsplash.com">Unsplash</a>, автори вказані в описі проєкту.' ),
		)
	),
);
