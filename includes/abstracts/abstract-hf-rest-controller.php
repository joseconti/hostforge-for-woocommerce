<?php
/**
 * Abstract REST Controller.
 *
 * Base class for all HostForge REST API controllers.
 * Extends WP_REST_Controller with shared namespace and permission helpers.
 *
 * @package HostForge\Abstracts
 */

namespace HostForge\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_REST_Controller
 */
abstract class HF_REST_Controller extends \WP_REST_Controller {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'hostforge/v1';

	/**
	 * Check if the current user can manage HostForge.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function check_admin_permission( \WP_REST_Request $request ): bool|\WP_Error {
		if ( ! current_user_can( 'manage_hostforge' ) ) {
			return new \WP_Error(
				'hostforge_rest_forbidden',
				__( 'You do not have permission to access this resource.', 'hostforge' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check if the current user is logged in.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function check_authenticated( \WP_REST_Request $request ): bool|\WP_Error {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'hostforge_rest_not_logged_in',
				__( 'You must be logged in to access this resource.', 'hostforge' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Send a success response.
	 *
	 * @param mixed $data    Response data.
	 * @param int   $status  HTTP status code.
	 * @return \WP_REST_Response
	 */
	protected function success( mixed $data = null, int $status = 200 ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			$status
		);
	}

	/**
	 * Send an error response.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code.
	 * @return \WP_Error
	 */
	protected function error( string $code, string $message, int $status = 400 ): \WP_Error {
		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/**
	 * Check rate limiting via transients.
	 *
	 * @param string $identifier Unique identifier for the rate limit (e.g. user ID or IP).
	 * @param int    $max_calls  Maximum allowed calls.
	 * @param int    $period     Period in seconds.
	 * @return bool True if within limits, false if rate limited.
	 */
	protected function check_rate_limit( string $identifier, int $max_calls = 60, int $period = 60 ): bool {
		$transient_key = 'hf_rate_' . md5( $identifier );
		$count         = (int) get_transient( $transient_key );

		if ( $count >= $max_calls ) {
			return false;
		}

		set_transient( $transient_key, $count + 1, $period );
		return true;
	}
}
