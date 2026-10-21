<?php
/**
 * Plugin Name: Zernyar local mail
 * Description: Keeps the last 20 emails in an option instead of sending them. Local Docker only.
 *
 * @package Zernyar
 */

defined( 'ABSPATH' ) || exit;

if ( 'local' !== wp_get_environment_type() ) {
	return;
}

add_filter(
	'pre_wp_mail',
	static function ( $result, array $atts ) {
		$mail   = (array) get_option( 'zernyar_dev_mail', array() );
		$mail[] = array(
			'to'      => $atts['to'],
			'subject' => $atts['subject'],
			'message' => $atts['message'],
			'time'    => gmdate( 'c' ),
		);
		update_option( 'zernyar_dev_mail', array_slice( $mail, -20 ), false );
		return true;
	},
	10,
	2
);
