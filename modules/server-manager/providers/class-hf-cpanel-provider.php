<?php
/**
 * cPanel/WHM Provider.
 *
 * Implements HF_Panel_Provider for cPanel/WHM servers via WHM API v1.
 * HTTPS port 2087, Authorization: whm root:{token}, JSON responses.
 *
 * @package HostForge\Modules\ServerManager\Providers
 */

namespace HostForge\Modules\ServerManager\Providers;

use HostForge\Abstracts\HF_API_Client;
use HostForge\HF_Encryption;
use HostForge\Interfaces\HF_Panel_Provider;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_CPanel_Provider
 */
class HF_CPanel_Provider extends HF_API_Client implements HF_Panel_Provider {

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
	 * Authentication method (token or password).
	 *
	 * @var string
	 */
	private string $auth_method;

	/**
	 * API token (decrypted).
	 *
	 * @var string
	 */
	private string $api_token;

	/**
	 * WHM username.
	 *
	 * @var string
	 */
	private string $whm_username;

	/**
	 * WHM password (decrypted).
	 *
	 * @var string
	 */
	private string $whm_password;

	/**
	 * Constructor.
	 *
	 * @param int $server_id Server post ID.
	 */
	public function __construct( int $server_id ) {
		$this->server_id   = $server_id;
		$this->hostname    = get_post_meta( $server_id, '_hf_hostname', true );
		$this->port        = (int) ( get_post_meta( $server_id, '_hf_port', true ) ?: 2087 );
		$this->auth_method = get_post_meta( $server_id, '_hf_auth_method', true ) ?: 'token';
		$this->api_token   = HF_Encryption::decrypt( get_post_meta( $server_id, '_hf_api_token', true ) );
		$this->whm_username = HF_Encryption::decrypt( get_post_meta( $server_id, '_hf_username', true ) );
		$this->whm_password = HF_Encryption::decrypt( get_post_meta( $server_id, '_hf_password', true ) );
		$this->base_url    = sprintf( 'https://%s:%d', $this->hostname, $this->port );
		$this->timeout     = 30;
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
	 * Get default headers for WHM API requests.
	 *
	 * @return array<string, string>
	 */
	protected function get_default_headers(): array {
		$headers = array(
			'Accept' => 'application/json',
		);

		if ( 'token' === $this->auth_method && ! empty( $this->api_token ) ) {
			$headers['Authorization'] = sprintf( 'whm %s:%s', $this->whm_username ?: 'root', $this->api_token );
		}

		return $headers;
	}

	/**
	 * Make a WHM API v1 call.
	 *
	 * @param string $function WHM API function name.
	 * @param array  $params   API parameters.
	 * @return array{success: bool, data: mixed, code: int}
	 */
	private function whm_api( string $function, array $params = array() ): array {
		$endpoint = '/json-api/' . $function;

		if ( ! empty( $params ) ) {
			$endpoint = add_query_arg( $params, $this->base_url . $endpoint );
			return $this->request( 'GET', $endpoint );
		}

		return $this->get( $endpoint );
	}

	/**
	 * Test the connection to the WHM server.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function test_connection(): array {
		$result = $this->whm_api( 'version' );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => __( 'Could not connect to WHM server.', 'hostforge' ),
			);
		}

		$version = '';
		if ( is_array( $result['data'] ) && isset( $result['data']['version'] ) ) {
			$version = $result['data']['version'];
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %s: cPanel/WHM version */
				__( 'Connected successfully. WHM version: %s', 'hostforge' ),
				$version
			),
		);
	}

	/**
	 * Create a hosting account.
	 *
	 * @param array $params Account parameters.
	 * @return array{success: bool, message: string, data: array}
	 */
	public function create_account( array $params ): array {
		$api_params = array(
			'username' => $params['username'] ?? '',
			'domain'   => $params['domain'] ?? '',
			'password' => $params['password'] ?? '',
			'plan'     => $params['plan'] ?? '',
		);

		if ( ! empty( $params['email'] ) ) {
			$api_params['contactemail'] = $params['email'];
		}

		/**
		 * Filter the cPanel account creation parameters.
		 *
		 * @param array $api_params WHM API parameters.
		 * @param array $params     Original parameters.
		 * @param int   $server_id  Server post ID.
		 */
		$api_params = apply_filters( 'hostforge_cpanel_create_params', $api_params, $params, $this->server_id );

		$result = $this->whm_api( 'createacct', $api_params );

		if ( ! $result['success'] ) {
			$error_msg = $this->extract_whm_error( $result['data'] );
			$this->log_error(
				'Failed to create cPanel account',
				array(
					'server_id' => $this->server_id,
					'username'  => $api_params['username'],
					'domain'    => $api_params['domain'],
					'error'     => $error_msg,
				)
			);

			return array(
				'success' => false,
				'message' => $error_msg,
				'data'    => array(),
			);
		}

		$this->log_info(
			'cPanel account created',
			array(
				'server_id' => $this->server_id,
				'username'  => $api_params['username'],
				'domain'    => $api_params['domain'],
			)
		);

		// Update account count.
		$current = (int) get_post_meta( $this->server_id, '_hf_current_accounts', true );
		update_post_meta( $this->server_id, '_hf_current_accounts', $current + 1 );

		return array(
			'success' => true,
			'message' => __( 'Account created successfully.', 'hostforge' ),
			'data'    => array(
				'username' => $api_params['username'],
				'domain'   => $api_params['domain'],
				'package'  => $api_params['plan'],
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
		$params = array( 'user' => $username );
		if ( ! empty( $reason ) ) {
			$params['reason'] = $reason;
		}

		$result = $this->whm_api( 'suspendacct', $params );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $this->extract_whm_error( $result['data'] ),
			);
		}

		$this->log_info( 'cPanel account suspended', array( 'username' => $username ) );

		return array(
			'success' => true,
			'message' => __( 'Account suspended successfully.', 'hostforge' ),
		);
	}

	/**
	 * Unsuspend a hosting account.
	 *
	 * @param string $username Account username.
	 * @return array{success: bool, message: string}
	 */
	public function unsuspend_account( string $username ): array {
		$result = $this->whm_api( 'unsuspendacct', array( 'user' => $username ) );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $this->extract_whm_error( $result['data'] ),
			);
		}

		$this->log_info( 'cPanel account unsuspended', array( 'username' => $username ) );

		return array(
			'success' => true,
			'message' => __( 'Account unsuspended successfully.', 'hostforge' ),
		);
	}

	/**
	 * Terminate (permanently delete) a hosting account.
	 *
	 * @param string $username Account username.
	 * @return array{success: bool, message: string}
	 */
	public function terminate_account( string $username ): array {
		$result = $this->whm_api( 'removeacct', array( 'user' => $username ) );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $this->extract_whm_error( $result['data'] ),
			);
		}

		$this->log_info( 'cPanel account terminated', array( 'username' => $username ) );

		// Update account count.
		$current = (int) get_post_meta( $this->server_id, '_hf_current_accounts', true );
		update_post_meta( $this->server_id, '_hf_current_accounts', max( 0, $current - 1 ) );

		return array(
			'success' => true,
			'message' => __( 'Account terminated successfully.', 'hostforge' ),
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
		$result = $this->whm_api(
			'passwd',
			array(
				'user'     => $username,
				'password' => $new_password,
			)
		);

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $this->extract_whm_error( $result['data'] ),
			);
		}

		$this->log_info( 'cPanel password changed', array( 'username' => $username ) );

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
		$result = $this->whm_api(
			'changepackage',
			array(
				'user' => $username,
				'pkg'  => $plan,
			)
		);

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $this->extract_whm_error( $result['data'] ),
			);
		}

		$this->log_info(
			'cPanel package changed',
			array(
				'username' => $username,
				'plan'     => $plan,
			)
		);

		return array(
			'success' => true,
			'message' => __( 'Package changed successfully.', 'hostforge' ),
		);
	}

	/**
	 * Get available hosting packages from the server.
	 *
	 * @return array{success: bool, packages: array}
	 */
	public function get_packages(): array {
		// Check cache first.
		$cache_time = (int) get_post_meta( $this->server_id, '_hf_packages_cache_time', true );
		if ( $cache_time > 0 && ( time() - $cache_time ) < 900 ) { // 15 min cache.
			$cached = get_post_meta( $this->server_id, '_hf_packages_cache', true );
			if ( is_array( $cached ) ) {
				return array(
					'success'  => true,
					'packages' => $cached,
				);
			}
		}

		$result = $this->whm_api( 'listpkgs' );

		if ( ! $result['success'] ) {
			return array(
				'success'  => false,
				'packages' => array(),
			);
		}

		$packages = array();

		if ( is_array( $result['data'] ) && isset( $result['data']['package'] ) ) {
			foreach ( $result['data']['package'] as $pkg ) {
				$packages[] = array(
					'name'       => $pkg['name'] ?? '',
					'disk'       => $pkg['QUOTA'] ?? '',
					'bandwidth'  => $pkg['BWLIMIT'] ?? '',
					'max_emails' => $pkg['MAXPOP'] ?? '',
					'max_dbs'    => $pkg['MAXSQL'] ?? '',
					'max_subs'   => $pkg['MAXSUB'] ?? '',
					'max_addons' => $pkg['MAXADDON'] ?? '',
				);
			}
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
	 * @param string $username Account username.
	 * @return array{success: bool, data: array}
	 */
	public function get_account_usage( string $username ): array {
		$result = $this->whm_api( 'accountsummary', array( 'user' => $username ) );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'data'    => array(),
			);
		}

		$account = array();
		if ( is_array( $result['data'] ) && isset( $result['data']['acct'][0] ) ) {
			$acct    = $result['data']['acct'][0];
			$account = array(
				'domain'       => $acct['domain'] ?? '',
				'disk_used'    => $acct['diskused'] ?? '0',
				'disk_limit'   => $acct['disklimit'] ?? 'unlimited',
				'bandwidth'    => $acct['bwused'] ?? '0',
				'bw_limit'     => $acct['bwlimit'] ?? 'unlimited',
				'plan'         => $acct['plan'] ?? '',
				'status'       => $acct['suspended'] ?? false ? 'suspended' : 'active',
				'ip'           => $acct['ip'] ?? '',
				'email'        => $acct['email'] ?? '',
				'start_date'   => $acct['startdate'] ?? '',
			);
		}

		return array(
			'success' => true,
			'data'    => $account,
		);
	}

	/**
	 * Get server statistics.
	 *
	 * @return array{success: bool, data: array}
	 */
	public function get_server_stats(): array {
		$result = $this->whm_api( 'systemloadavg', array( 'api.version' => 1 ) );

		$stats = array(
			'load_1'  => '',
			'load_5'  => '',
			'load_15' => '',
		);

		if ( $result['success'] && is_array( $result['data'] ) ) {
			$data = $result['data']['data'] ?? $result['data'];
			$stats['load_1']  = $data['one'] ?? '';
			$stats['load_5']  = $data['five'] ?? '';
			$stats['load_15'] = $data['fifteen'] ?? '';
		}

		// Get disk usage.
		$disk_result = $this->whm_api( 'getdiskusage' );
		if ( $disk_result['success'] && is_array( $disk_result['data'] ) ) {
			$stats['disk'] = $disk_result['data'] ?? array();
		}

		// Get hostname.
		$hostname_result = $this->whm_api( 'gethostname' );
		if ( $hostname_result['success'] && is_array( $hostname_result['data'] ) ) {
			$stats['hostname'] = $hostname_result['data']['hostname'] ?? $this->hostname;
		}

		return array(
			'success' => true,
			'data'    => $stats,
		);
	}

	/**
	 * Get SSO URL for a user to access cPanel.
	 *
	 * @param string $username Account username.
	 * @return array{success: bool, url: string}
	 */
	public function get_sso_url( string $username ): array {
		$result = $this->whm_api(
			'create_user_session',
			array(
				'user'    => $username,
				'service' => 'cpaneld',
			)
		);

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'url'     => '',
			);
		}

		$url = '';
		if ( is_array( $result['data'] ) && isset( $result['data']['data']['url'] ) ) {
			$url = $result['data']['data']['url'];
		}

		return array(
			'success' => ! empty( $url ),
			'url'     => $url,
		);
	}

	/**
	 * Extract an error message from WHM API response data.
	 *
	 * @param mixed $data Response data.
	 * @return string Error message.
	 */
	private function extract_whm_error( $data ): string {
		if ( is_string( $data ) ) {
			return $data;
		}

		if ( is_array( $data ) ) {
			if ( isset( $data['metadata']['reason'] ) ) {
				return $data['metadata']['reason'];
			}
			if ( isset( $data['statusmsg'] ) ) {
				return $data['statusmsg'];
			}
			if ( isset( $data['error'] ) ) {
				return $data['error'];
			}
		}

		return __( 'Unknown WHM API error.', 'hostforge' );
	}
}
