<?php
/**
 * Outcome of an HTTP call.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit;

defined( 'ABSPATH' ) || exit;

/**
 * Status code and decoded JSON, or the transport error.
 */
final class HttpResult {

	/**
	 * Result of a request that reached the server.
	 *
	 * @param int                       $status HTTP status, 0 when the request failed.
	 * @param array<string, mixed>|null $json   Decoded body when it was JSON.
	 * @param string|null               $error  Transport error.
	 */
	public function __construct(
		public readonly int $status,
		public readonly ?array $json = null,
		public readonly ?string $error = null,
	) {}

	/**
	 * Result of a request that never got an answer.
	 *
	 * @param string $error Transport error.
	 */
	public static function failed( string $error ): self {
		return new self( 0, null, $error );
	}

	/**
	 * Whether the server answered at all.
	 */
	public function reached(): bool {
		return null === $this->error;
	}
}
