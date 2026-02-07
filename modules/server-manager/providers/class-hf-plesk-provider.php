<?php
/**
 * Plesk Provider.
 *
 * Implements HF_Panel_Provider for Plesk servers.
 * XML API primary (port 8443, text/xml), REST API complement.
 * Auth: X-API-Key header (preferred) or HTTP Basic.
 *
 * @package HostForge\Modules\ServerManager\Providers
 */

namespace HostForge\Modules\ServerManager\Providers;

use HostForge\HF_Encryption;
use HostForge\Interfaces\HF_Panel_Provider;
use HostForge\Traits\HF_Has_Logs;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Plesk_Provider
 */
class HF_Plesk_Provider implements HF_Panel_Provider {

	use HF_Has_Logs;

	/**
	 * Server post ID.
	 *
	 * @var int
	 */
	private int $server_id;

	/**
	 * Server hostname.
	 *
	 * @var string
	 */
	private string $hostname;

	/**
	 * Server port.
	 *
	 * @var int
	 */
	private int $port;

	/**
	 * Auth method (token or password).
	 *
	 * @var string
	 */
	private string $auth_method;

	/**
	 * API key (decrypted).
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Admin username (decrypted).
	 *
	 * @var string
	 */
	private string $username;

	/**
	 * Admin password (decrypted).
	 *
	 * @var string
	 */
	private string $password;

	/**
	 * Request timeout in seconds.
	 *
	 * @var int
	 */
	private int $timeout = 30;

	/**
	 * Constructor.
	 *
	 * @param int $server_id Server post ID.
	 */
	public function __construct( int $server_id ) {
		$this->server_id   = $server_id;
		$this->hostname    = get_post_meta( $server_id, '_hf_hostname', true );
		$port_meta         = get_post_meta( $server_id, '_hf_port', true );
		$this->port        = ! empty( $port_meta ) ? (int) $port_meta : 8443;
		$auth_meta         = get_post_meta( $server_id, '_hf_auth_method', true );
		$this->auth_method = ! empty( $auth_meta ) ? $auth_meta : 'token';
		$this->api_key     = HF_Encryption::decrypt( get_post_meta( $server_id, '_hf_api_token', true ) );
		$this->username    = HF_Encryption::decrypt( get_post_meta( $server_id, '_hf_username', true ) );
		$this->password    = HF_Encryption::decrypt( get_post_meta( $server_id, '_hf_password', true ) );
	}

	/**
	 * Get the module ID for logging.
	 *
	 * @return string
	 */
	protected function get_id(): string {
		return 'server-manager';
	}

	/**
	 * Get the base URL.
	 *
	 * @return string
	 */
	private function get_base_url(): string {
		return sprintf( 'https://%s:%d', $this->hostname, $this->port );
	}

	/**
	 * Make an XML API request to Plesk.
	 *
	 * @param string $xml XML request body.
	 * @return array{success: bool, data: mixed, code: int}
	 */
	private function xml_request( string $xml ): array {
		$url     = $this->get_base_url() . '/enterprise/control/agent.php';
		$headers = array(
			'Content-Type' => 'text/xml',
			'Accept'       => 'text/xml',
		);

		if ( 'token' === $this->auth_method && ! empty( $this->api_key ) ) {
			$headers['KEY'] = $this->api_key;
		} else {
			$headers['HTTP_AUTH_LOGIN']  = $this->username;
			$headers['HTTP_AUTH_PASSWD'] = $this->password;
		}

		$args = array(
			'method'    => 'POST',
			'timeout'   => $this->timeout,
			'sslverify' => true,
			'headers'   => $headers,
			'body'      => $xml,
		);

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->log_error(
				'Plesk XML API request failed',
				array(
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

		// Parse XML response.
		libxml_use_internal_errors( true );
		$xml_data = simplexml_load_string( $body_raw );

		if ( false === $xml_data ) {
			return array(
				'success' => false,
				'data'    => __( 'Failed to parse Plesk XML response.', 'hostforge' ),
				'code'    => $code,
			);
		}

		$success = $code >= 200 && $code < 300;

		$result = array(
			'success' => $success,
			'data'    => $xml_data,
			'code'    => $code,
		);

		/**
		 * Filter the raw Plesk XML API response.
		 *
		 * Allows inspection or modification of any Plesk XML API response
		 * before it is processed by the calling method.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $result    Response array with keys: success, data, code.
		 * @param string $xml       XML request body that was sent.
		 * @param int    $server_id Server post ID.
		 */
		$result = apply_filters( 'hostforge_plesk_api_response', $result, $xml, $this->server_id );

		return $result;
	}

	/**
	 * Make a REST API request to Plesk.
	 *
	 * @param string $method   HTTP method.
	 * @param string $endpoint REST endpoint path.
	 * @param array  $body     Request body.
	 * @return array{success: bool, data: mixed, code: int}
	 */
	private function rest_request( string $method, string $endpoint, array $body = array() ): array {
		$url     = $this->get_base_url() . '/api/v2/' . ltrim( $endpoint, '/' );
		$headers = array(
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		);

		if ( 'token' === $this->auth_method && ! empty( $this->api_key ) ) {
			$headers['X-API-Key'] = $this->api_key;
		} else {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$headers['Authorization'] = 'Basic ' . base64_encode( $this->username . ':' . $this->password );
		}

		$args = array(
			'method'    => $method,
			'timeout'   => $this->timeout,
			'sslverify' => true,
			'headers'   => $headers,
		);

		if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->log_error(
				'Plesk REST API request failed',
				array(
					'endpoint' => $endpoint,
					'error'    => $response->get_error_message(),
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
		$data     = json_decode( $body_raw, true );

		if ( null === $data ) {
			$data = $body_raw;
		}

		$success = $code >= 200 && $code < 300;

		if ( ! $success ) {
			$this->log_warning(
				'Plesk REST API returned non-2xx',
				array(
					'endpoint' => $endpoint,
					'code'     => $code,
					'body'     => is_string( $data ) ? substr( $data, 0, 500 ) : $data,
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
	 * Test the connection to the Plesk server.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function test_connection(): array {
		$result = $this->rest_request( 'GET', 'server' );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => __( 'Could not connect to Plesk server.', 'hostforge' ),
			);
		}

		$version = '';
		if ( is_array( $result['data'] ) && isset( $result['data']['platform']['version'] ) ) {
			$version = $result['data']['platform']['version'];
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %s: Plesk version */
				__( 'Connected successfully. Plesk version: %s', 'hostforge' ),
				$version
			),
		);
	}

	/**
	 * Create a hosting account (webspace) in Plesk.
	 *
	 * @param array $params Account parameters.
	 * @return array{success: bool, message: string, data: array}
	 */
	public function create_account( array $params ): array {
		$domain   = $params['domain'] ?? '';
		$username = $params['username'] ?? '';
		$password = $params['password'] ?? '';
		$plan     = $params['plan'] ?? '';
		$email    = $params['email'] ?? '';

		// First, create a customer if needed.
		$customer_xml = sprintf(
			'<?xml version="1.0" encoding="UTF-8"?>
			<packet>
				<customer>
					<add>
						<gen_info>
							<pname>%s</pname>
							<login>%s</login>
							<passwd>%s</passwd>
							<email>%s</email>
						</gen_info>
					</add>
				</customer>
			</packet>',
			esc_attr( $username ),
			esc_attr( $username ),
			esc_attr( $password ),
			esc_attr( $email )
		);

		$customer_result = $this->xml_request( $customer_xml );
		$customer_id     = 0;

		if ( $customer_result['success'] && $customer_result['data'] instanceof \SimpleXMLElement ) {
			$status = (string) ( $customer_result['data']->customer->add->result->status ?? '' );
			if ( 'ok' === $status ) {
				$customer_id = (int) ( $customer_result['data']->customer->add->result->id ?? 0 );
			}
		}

		// Create webspace (subscription).
		$webspace_xml = sprintf(
			'<?xml version="1.0" encoding="UTF-8"?>
			<packet>
				<webspace>
					<add>
						<gen_setup>
							<name>%s</name>
							<owner-id>%d</owner-id>
							<htype>vrt_hst</htype>
							<ip_address></ip_address>
							<status>0</status>
						</gen_setup>
						<hosting>
							<vrt_hst>
								<property>
									<name>ftp_login</name>
									<value>%s</value>
								</property>
								<property>
									<name>ftp_password</name>
									<value>%s</value>
								</property>
							</vrt_hst>
						</hosting>
						%s
					</add>
				</webspace>
			</packet>',
			esc_attr( $domain ),
			$customer_id > 0 ? $customer_id : 0,
			esc_attr( $username ),
			esc_attr( $password ),
			! empty( $plan ) ? sprintf( '<plan-name>%s</plan-name>', esc_attr( $plan ) ) : ''
		);

		/**
		 * Filter the Plesk webspace creation XML.
		 *
		 * @param string $webspace_xml XML request.
		 * @param array  $params       Original parameters.
		 * @param int    $server_id    Server post ID.
		 */
		$webspace_xml = apply_filters( 'hostforge_plesk_create_xml', $webspace_xml, $params, $this->server_id );

		$result = $this->xml_request( $webspace_xml );

		if ( ! $result['success'] || ! ( $result['data'] instanceof \SimpleXMLElement ) ) {
			$error_msg = $this->extract_xml_error( $result['data'] );
			$this->log_error(
				'Failed to create Plesk webspace',
				array(
					'server_id' => $this->server_id,
					'domain'    => $domain,
					'error'     => $error_msg,
				)
			);

			return array(
				'success' => false,
				'message' => $error_msg,
				'data'    => array(),
			);
		}

		$status = (string) ( $result['data']->webspace->add->result->status ?? '' );

		if ( 'ok' !== $status ) {
			$error_msg = (string) ( $result['data']->webspace->add->result->errtext ?? __( 'Unknown Plesk error.', 'hostforge' ) );
			return array(
				'success' => false,
				'message' => $error_msg,
				'data'    => array(),
			);
		}

		$this->log_info(
			'Plesk webspace created',
			array(
				'server_id' => $this->server_id,
				'domain'    => $domain,
				'username'  => $username,
			)
		);

		// Update account count.
		$current = (int) get_post_meta( $this->server_id, '_hf_current_accounts', true );
		update_post_meta( $this->server_id, '_hf_current_accounts', $current + 1 );

		return array(
			'success' => true,
			'message' => __( 'Webspace created successfully.', 'hostforge' ),
			'data'    => array(
				'username'    => $username,
				'domain'      => $domain,
				'customer_id' => $customer_id,
			),
		);
	}

	/**
	 * Suspend a hosting account.
	 *
	 * @param string $username Account username.
	 * @param string $reason   Reason for suspension.
	 * @return array{success: bool, message: string}
	 */
	public function suspend_account( string $username, string $reason = '' ): array {
		$params = array(
			'username' => $username,
			'reason'   => $reason,
			'status'   => 16,
		);

		/**
		 * Filter the Plesk suspend account parameters.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $params    Parameters for suspension including username and status code.
		 * @param string $username  Account username being suspended.
		 * @param int    $server_id Server post ID.
		 */
		$params = apply_filters( 'hostforge_plesk_suspend_params', $params, $username, $this->server_id );

		$xml = sprintf(
			'<?xml version="1.0" encoding="UTF-8"?>
			<packet>
				<webspace>
					<set>
						<filter>
							<name>%s</name>
						</filter>
						<values>
							<gen_setup>
								<status>%d</status>
							</gen_setup>
						</values>
					</set>
				</webspace>
			</packet>',
			esc_attr( $params['username'] ),
			absint( $params['status'] )
		);

		$result = $this->xml_request( $xml );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $this->extract_xml_error( $result['data'] ),
			);
		}

		$this->log_info( 'Plesk webspace suspended', array( 'username' => $username ) );

		return array(
			'success' => true,
			'message' => __( 'Webspace suspended successfully.', 'hostforge' ),
		);
	}

	/**
	 * Unsuspend a hosting account.
	 *
	 * @param string $username Account username.
	 * @return array{success: bool, message: string}
	 */
	public function unsuspend_account( string $username ): array {
		$params = array(
			'username' => $username,
			'status'   => 0,
		);

		/**
		 * Filter the Plesk unsuspend account parameters.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $params    Parameters for unsuspension including username and status code.
		 * @param string $username  Account username being unsuspended.
		 * @param int    $server_id Server post ID.
		 */
		$params = apply_filters( 'hostforge_plesk_unsuspend_params', $params, $username, $this->server_id );

		$xml = sprintf(
			'<?xml version="1.0" encoding="UTF-8"?>
			<packet>
				<webspace>
					<set>
						<filter>
							<name>%s</name>
						</filter>
						<values>
							<gen_setup>
								<status>%d</status>
							</gen_setup>
						</values>
					</set>
				</webspace>
			</packet>',
			esc_attr( $params['username'] ),
			absint( $params['status'] )
		);

		$result = $this->xml_request( $xml );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $this->extract_xml_error( $result['data'] ),
			);
		}

		$this->log_info( 'Plesk webspace unsuspended', array( 'username' => $username ) );

		return array(
			'success' => true,
			'message' => __( 'Webspace unsuspended successfully.', 'hostforge' ),
		);
	}

	/**
	 * Terminate (permanently delete) a hosting account.
	 *
	 * @param string $username Account username.
	 * @return array{success: bool, message: string}
	 */
	public function terminate_account( string $username ): array {
		$params = array(
			'username' => $username,
		);

		/**
		 * Filter the Plesk terminate account parameters.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $params    Parameters for termination.
		 * @param string $username  Account username being terminated.
		 * @param int    $server_id Server post ID.
		 */
		$params = apply_filters( 'hostforge_plesk_terminate_params', $params, $username, $this->server_id );

		$xml = sprintf(
			'<?xml version="1.0" encoding="UTF-8"?>
			<packet>
				<webspace>
					<del>
						<filter>
							<name>%s</name>
						</filter>
					</del>
				</webspace>
			</packet>',
			esc_attr( $params['username'] )
		);

		$result = $this->xml_request( $xml );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $this->extract_xml_error( $result['data'] ),
			);
		}

		$this->log_info( 'Plesk webspace terminated', array( 'username' => $username ) );

		// Update account count.
		$current = (int) get_post_meta( $this->server_id, '_hf_current_accounts', true );
		update_post_meta( $this->server_id, '_hf_current_accounts', max( 0, $current - 1 ) );

		return array(
			'success' => true,
			'message' => __( 'Webspace terminated successfully.', 'hostforge' ),
		);
	}

	/**
	 * Change the password for a hosting account.
	 *
	 * @param string $username     Account username.
	 * @param string $new_password New password.
	 * @return array{success: bool, message: string}
	 */
	public function change_password( string $username, string $new_password ): array {
		$xml = sprintf(
			'<?xml version="1.0" encoding="UTF-8"?>
			<packet>
				<webspace>
					<set>
						<filter>
							<name>%s</name>
						</filter>
						<values>
							<hosting>
								<vrt_hst>
									<property>
										<name>ftp_password</name>
										<value>%s</value>
									</property>
								</vrt_hst>
							</hosting>
						</values>
					</set>
				</webspace>
			</packet>',
			esc_attr( $username ),
			esc_attr( $new_password )
		);

		$result = $this->xml_request( $xml );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $this->extract_xml_error( $result['data'] ),
			);
		}

		$this->log_info( 'Plesk password changed', array( 'username' => $username ) );

		return array(
			'success' => true,
			'message' => __( 'Password changed successfully.', 'hostforge' ),
		);
	}

	/**
	 * Change the hosting plan/package for an account.
	 *
	 * @param string $username Account username.
	 * @param string $plan     New plan/package name.
	 * @return array{success: bool, message: string}
	 */
	public function change_package( string $username, string $plan ): array {
		$params = array(
			'username' => $username,
			'plan'     => $plan,
		);

		/**
		 * Filter the Plesk change package parameters.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $params    Parameters for package change.
		 * @param string $username  Account username.
		 * @param string $plan      New plan/package name.
		 * @param int    $server_id Server post ID.
		 */
		$params = apply_filters( 'hostforge_plesk_change_package_params', $params, $username, $plan, $this->server_id );

		$xml = sprintf(
			'<?xml version="1.0" encoding="UTF-8"?>
			<packet>
				<webspace>
					<switch-subscription>
						<filter>
							<name>%s</name>
						</filter>
						<plan-name>%s</plan-name>
					</switch-subscription>
				</webspace>
			</packet>',
			esc_attr( $params['username'] ),
			esc_attr( $params['plan'] )
		);

		$result = $this->xml_request( $xml );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $this->extract_xml_error( $result['data'] ),
			);
		}

		$this->log_info(
			'Plesk plan changed',
			array(
				'username' => $username,
				'plan'     => $plan,
			)
		);

		return array(
			'success' => true,
			'message' => __( 'Plan changed successfully.', 'hostforge' ),
		);
	}

	/**
	 * Get available service plans from Plesk.
	 *
	 * @return array{success: bool, packages: array}
	 */
	public function get_packages(): array {
		// Check cache first.
		$cache_time = (int) get_post_meta( $this->server_id, '_hf_packages_cache_time', true );
		if ( $cache_time > 0 && ( time() - $cache_time ) < 900 ) {
			$cached = get_post_meta( $this->server_id, '_hf_packages_cache', true );
			if ( is_array( $cached ) ) {
				return array(
					'success'  => true,
					'packages' => $cached,
				);
			}
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<packet>
				<service-plan>
					<get>
						<filter/>
					</get>
				</service-plan>
			</packet>';

		$result = $this->xml_request( $xml );

		if ( ! $result['success'] || ! ( $result['data'] instanceof \SimpleXMLElement ) ) {
			return array(
				'success'  => false,
				'packages' => array(),
			);
		}

		$packages    = array();
		$xpath_plans = $result['data']->xpath( '//service-plan/get/result' );
		$plans       = ! empty( $xpath_plans ) ? $xpath_plans : array();

		foreach ( $plans as $plan ) {
			if ( 'ok' !== (string) ( $plan->status ?? '' ) ) {
				continue;
			}
			$packages[] = array(
				'name' => (string) ( $plan->name ?? '' ),
				'id'   => (int) ( $plan->id ?? 0 ),
			);
		}

		// Cache packages.
		update_post_meta( $this->server_id, '_hf_packages_cache', $packages );
		update_post_meta( $this->server_id, '_hf_packages_cache_time', time() );

		return array(
			'success'  => true,
			'packages' => $packages,
		);
	}

	/**
	 * Get resource usage for an account.
	 *
	 * @param string $username Account username (domain name in Plesk).
	 * @return array{success: bool, data: array}
	 */
	public function get_account_usage( string $username ): array {
		$xml = sprintf(
			'<?xml version="1.0" encoding="UTF-8"?>
			<packet>
				<webspace>
					<get>
						<filter>
							<name>%s</name>
						</filter>
						<dataset>
							<gen_info/>
							<hosting/>
							<stat/>
							<disk_usage/>
						</dataset>
					</get>
				</webspace>
			</packet>',
			esc_attr( $username )
		);

		$result = $this->xml_request( $xml );

		if ( ! $result['success'] || ! ( $result['data'] instanceof \SimpleXMLElement ) ) {
			return array(
				'success' => false,
				'data'    => array(),
			);
		}

		$account = array();
		$ws_data = $result['data']->webspace->get->result ?? null;

		if ( $ws_data && 'ok' === (string) ( $ws_data->status ?? '' ) ) {
			$gen_info = $ws_data->data->gen_info ?? null;
			$account  = array(
				'domain' => (string) ( $gen_info->name ?? '' ),
				'status' => (string) ( $gen_info->status ?? '' ),
			);
		}

		return array(
			'success' => true,
			'data'    => $account,
		);
	}

	/**
	 * Get server statistics via REST API.
	 *
	 * @return array{success: bool, data: array}
	 */
	public function get_server_stats(): array {
		$result = $this->rest_request( 'GET', 'server' );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'data'    => array(),
			);
		}

		$stats = array(
			'hostname' => $this->hostname,
		);

		if ( is_array( $result['data'] ) ) {
			$stats['version']  = $result['data']['platform']['version'] ?? '';
			$stats['os']       = $result['data']['platform']['os'] ?? '';
			$stats['hostname'] = $result['data']['hostname'] ?? $this->hostname;
		}

		return array(
			'success' => true,
			'data'    => $stats,
		);
	}

	/**
	 * Get SSO URL for a user to access Plesk.
	 *
	 * @param string $username Account username.
	 * @return array{success: bool, url: string}
	 */
	public function get_sso_url( string $username ): array {
		$xml = sprintf(
			'<?xml version="1.0" encoding="UTF-8"?>
			<packet>
				<server>
					<create_session>
						<login>%s</login>
						<data>
							<user_ip></user_ip>
						</data>
					</create_session>
				</server>
			</packet>',
			esc_attr( $username )
		);

		$result = $this->xml_request( $xml );

		if ( ! $result['success'] || ! ( $result['data'] instanceof \SimpleXMLElement ) ) {
			return array(
				'success' => false,
				'url'     => '',
			);
		}

		$session_id = (string) ( $result['data']->server->create_session->result->id ?? '' );

		if ( empty( $session_id ) ) {
			return array(
				'success' => false,
				'url'     => '',
			);
		}

		$url = sprintf(
			'%s/enterprise/rsession_init.php?PLESKSESSID=%s',
			$this->get_base_url(),
			$session_id
		);

		return array(
			'success' => true,
			'url'     => $url,
		);
	}

	/**
	 * Get domains list via REST API.
	 *
	 * @return array{success: bool, domains: array}
	 */
	public function get_domains(): array {
		$result = $this->rest_request( 'GET', 'domains' );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'domains' => array(),
			);
		}

		return array(
			'success' => true,
			'domains' => is_array( $result['data'] ) ? $result['data'] : array(),
		);
	}

	/**
	 * Get DNS records for a domain via REST API.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, records: array}
	 */
	public function get_dns_records( string $domain ): array {
		$result = $this->rest_request( 'GET', 'dns/records?domain=' . rawurlencode( $domain ) );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'records' => array(),
			);
		}

		return array(
			'success' => true,
			'records' => is_array( $result['data'] ) ? $result['data'] : array(),
		);
	}

	/**
	 * Get customers list.
	 *
	 * @return array{success: bool, customers: array}
	 */
	public function get_customers(): array {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<packet>
				<customer>
					<get>
						<filter/>
						<dataset>
							<gen_info/>
						</dataset>
					</get>
				</customer>
			</packet>';

		$result = $this->xml_request( $xml );

		if ( ! $result['success'] || ! ( $result['data'] instanceof \SimpleXMLElement ) ) {
			return array(
				'success'   => false,
				'customers' => array(),
			);
		}

		$customers     = array();
		$xpath_results = $result['data']->xpath( '//customer/get/result' );
		$results       = ! empty( $xpath_results ) ? $xpath_results : array();

		foreach ( $results as $cust ) {
			if ( 'ok' !== (string) ( $cust->status ?? '' ) ) {
				continue;
			}
			$gen_info    = $cust->data->gen_info ?? null;
			$customers[] = array(
				'id'    => (int) ( $cust->id ?? 0 ),
				'name'  => (string) ( $gen_info->pname ?? '' ),
				'login' => (string) ( $gen_info->login ?? '' ),
				'email' => (string) ( $gen_info->email ?? '' ),
			);
		}

		return array(
			'success'   => true,
			'customers' => $customers,
		);
	}

	/**
	 * Extract error message from XML response.
	 *
	 * @param mixed $data Response data.
	 * @return string
	 */
	private function extract_xml_error( $data ): string {
		if ( is_string( $data ) ) {
			return $data;
		}

		if ( $data instanceof \SimpleXMLElement ) {
			// Try common error paths.
			$errtext = (string) ( $data->system->errtext ?? '' );
			if ( ! empty( $errtext ) ) {
				return $errtext;
			}

			// Search for any errtext in the response.
			$errors = $data->xpath( '//errtext' );
			if ( ! empty( $errors ) ) {
				return (string) $errors[0];
			}
		}

		return __( 'Unknown Plesk error.', 'hostforge' );
	}
}
