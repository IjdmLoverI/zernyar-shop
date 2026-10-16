<?php
/**
 * The part of WP-CLI the demo plugin uses, for static analysis only.
 * php-stubs/wp-cli-stubs still requires WordPress stubs older than 7.0.
 *
 * @package ZernyarDemo
 */

// phpcs:disable

class WP_CLI {

	/**
	 * @param string                $name     Command name.
	 * @param callable|object|string $callable Command implementation.
	 * @param array<string, mixed>  $args     Options.
	 */
	public static function add_command( $name, $callable, $args = array() ): bool {
		return true;
	}

	/**
	 * @param string $message Message.
	 */
	public static function success( $message ): void {}
}
