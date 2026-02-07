<?php
/**
 * Abstract API Client.
 *
 * Base class for external API connections (cPanel, Plesk, registrars).
 * Provides HTTP request helpers using wp_remote_request().
 *
 * @package HostForge\Abstracts
 */

namespace HostForge\Abstracts;

use HostForge\Traits\HF_Has_Logs;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_API_Client
 */
abstract class HF_API_Client {

	use HF_Has_Logs;

	/**
	 * Base URL for API requests.
	 *
	 * @var string
	 */
	protected string $base_url = '';

	/**
	 * Request timeout in seconds.
	 *
	 * @var int
	 */
	protected int $timeout = 30;

	/**
	 * Make an HTTP GET request.
	 *
	 * @param string $endpoint API endpoint path.
	 * @param array  $params   Query parameters.
	 * @param array  $headers  Additional headers.
	 * @return array{success: bool, data: mixed, code: int}
	 */
	protected function get( string $endpoint, array $params = array(), array $headers = array() ): array {
		$url = $this->build_url( $endpoint );

		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		return $this->request( 'GET', $url, array(), $headers );
	}

	/**
	 * Make an HTTP POST request.
	 *
	 * @param string $endpoint API endpoint path.
	 * @param array  $body     Request body.
	 * @param array  $headers  Additional headers.
	 * @return array{success: bool, data: mixed, code: int}
	 */
	protected function post( string $endpoint, array $body = array(), array $headers = array() ): array {
		$url = $this->build_url( $endpoint );
		return $this->request( 'POST', $url, $body, $headers );
	}

	/**
	 * Make an HTTP request.
	 *
	 * @param string $method  HTTP method.
	 * @param string $url     Full URL.
	 * @param array  $body    Request body.
	 * @param array  $headers HTTP headers.
	 * @return array{success: bool, data: mixed, code: int}
	 */
	protected function request( string $method, string $url, array $body = array(), array $headers = array() ): array {
		$args = array(
			'method'    => $method,
			'timeout'   => $this->timeout,
			'sslverify' => true,
			'headers'   => array_merge( $this->get_default_headers(), $headers ),
		);

		if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->log_error(
				'API request failed',
				array(
					'url'   => $url,
					'error' => $response->get_error_message(),
				)
			);

			return array(
				'success' => false,
				'data'    => $response->get_error_message(),
				'code'    => 0,
			);
		}

		$code     = wp_remote_retrieve_response_code( $response );
		$body_raw = wp_remote_retrieve_body( $response );

		$data = json_decode( $body_raw, true );
		if ( null === $data ) {
			$data = $body_raw;
		}

		$success = $code >= 200 && $code < 300;

		if ( ! $success ) {
			$this->log_warning(
				'API request returned non-2xx status',
				array(
					'url'  => $url,
					'code' => $code,
					'body' => is_string( $data ) ? substr( $data, 0, 500 ) : $data,
				)
			);
		}

		return array(
			'success' => $success,
			'data'    => $data,
			'code'    => $code,
		);
	}

	/**
	 * Build the full URL for an endpoint.
	 *
	 * @param string $endpoint Endpoint path.
	 * @return string Full URL.
	 */
	protected function build_url( string $endpoint ): string {
		return rtrim( $this->base_url, '/' ) . '/' . ltrim( $endpoint, '/' );
	}

	/**
	 * Get default headers for all requests.
	 *
	 * Override in subclasses to add auth headers.
	 *
	 * @return array<string, string>
	 */
	protected function get_default_headers(): array {
		return array(
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		);
	}

	/**
	 * Get the module ID for logging.
	 *
	 * @return string
	 */
	protected function get_id(): string {
		return 'api-client';
	}
}
