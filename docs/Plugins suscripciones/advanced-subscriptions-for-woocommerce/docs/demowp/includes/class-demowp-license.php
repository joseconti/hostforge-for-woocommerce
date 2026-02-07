<?php
/**
 * License Manager for DemoWP
 *
 * @package DemoWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DemoWP_License
 *
 * Handles license activation and plugin updates.
 *
 * @since 1.0.0
 */
class DemoWP_License {

	/**
	 * The URL pointing to the custom API endpoint.
	 *
	 * @var string
	 */
	private $api_url = '';

	/**
	 * The plugin's slug.
	 *
	 * @var string
	 */
	private $slug = '';

	/**
	 * The plugin's name.
	 *
	 * @var string
	 */
	private $name = '';

	/**
	 * The plugin's version.
	 *
	 * @var string
	 */
	private $version = '';

	/**
	 * Whether to override WordPress updates.
	 *
	 * @var bool
	 */
	private $wp_override = false;

	/**
	 * The prefix for plugin options.
	 *
	 * @var string
	 */
	private $prefix = '';

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private $plugin_file = '';

	/**
	 * Item name.
	 *
	 * @var string
	 */
	private $item_name = '';

	/**
	 * License title.
	 *
	 * @var string
	 */
	private $license_title = '';

	/**
	 * API timeout in seconds (short to avoid slowdowns).
	 *
	 * @var int
	 */
	private $api_timeout = 5;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		// Verify constants are defined.
		if ( ! defined( 'DEMOWP_LICENSE_API' ) || ! defined( 'DEMOWP_PLUGIN_FILE' ) || ! defined( 'DEMOWP_VERSION' ) || ! defined( 'DEMOWP_LICENSE_ITEM_NAME' ) || ! defined( 'DEMOWP_LICENSE_PREFIX' ) ) {
			return;
		}

		$this->api_url       = trailingslashit( DEMOWP_LICENSE_API );
		$this->slug          = plugin_basename( DEMOWP_PLUGIN_FILE );
		$this->plugin_file   = DEMOWP_PLUGIN_FILE;
		$this->name          = 'demowp';
		$this->version       = DEMOWP_VERSION;
		$this->wp_override   = false;
		$this->item_name     = DEMOWP_LICENSE_ITEM_NAME;
		$this->prefix        = DEMOWP_LICENSE_PREFIX;
		$this->license_title = __( 'DemoWP License', 'demowp' );

		// Set up hooks.
		$this->init();
	}

	/**
	 * Set up WordPress filters to hook into WP's update process.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'activate_license' ) );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ), 9999, 1 );
		add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 9999, 3 );
		add_action( "in_plugin_update_message-{$this->slug}", array( $this, 'in_plugin_update_message' ) );
		add_filter( 'upgrader_pre_install', array( $this, 'upgrader_pre_install' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/**
	 * Render Admin Notice if license is not active
	 */
	public function admin_notice() {
		// Only show on DemoWP admin pages.
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'demowp' ) === false ) {
			return;
		}

		$license = get_option( $this->prefix . '_license_key', '' );
		$status  = get_option( $this->prefix . '_license_status', '' );

		if ( ! empty( $license ) && 'valid' === $status ) {
			return;
		}

		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'DemoWP', 'demowp' ); ?></strong>:
				<?php esc_html_e( 'Please enter your license key to enable automatic updates.', 'demowp' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=demowp' ) ); ?>">
					<?php esc_html_e( 'Enter License Key', 'demowp' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Clear transients on upgrade
	 *
	 * @param mixed $return return value.
	 * @param mixed $plugin plugin data.
	 * @return mixed
	 */
	public function upgrader_pre_install( $return, $plugin ) {
		if ( is_wp_error( $return ) ) {
			return $return;
		}
		if ( isset( $plugin['plugin'] ) && $this->slug === $plugin['plugin'] ) {
			delete_site_transient( md5( $this->slug . 'plugin_update_info' ) );
		}
		return $return;
	}

	/**
	 * Activate license process
	 *
	 * @return string Error message or empty string on success.
	 */
	public function activate_license() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST[ $this->prefix . '_license_key' ] ) ) {
			return '';
		}

		// Check if we're on the settings page.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['option_page'] ) || 'demowp_settings' !== $_POST['option_page'] ) {
			return '';
		}

		// Run a quick security check.
		if ( ! check_admin_referer( 'demowp_settings-options' ) ) {
			return '';
		}

		// Retrieve the license from the POST data.
		$license     = sanitize_text_field( wp_unslash( $_POST[ $this->prefix . '_license_key' ] ) );
		$old_license = get_option( $this->prefix . '_license_key', '' );

		// If license hasn't changed, don't reactivate.
		if ( $license === $old_license ) {
			return '';
		}

		// Update the license key option.
		update_option( $this->prefix . '_license_key', $license );

		// If license is empty, clear status.
		if ( empty( $license ) ) {
			delete_option( $this->prefix . '_license_status' );
			delete_option( $this->prefix . '_license_salt' );
			return '';
		}

		$url    = get_site_url( get_current_blog_id() );
		$domain = strtolower( rawurlencode( rtrim( $url, '/' ) ) );

		// Data to send in our API request.
		$api_params = array(
			'action'    => 'activate_license',
			'license'   => $license,
			'item_name' => rawurlencode( $this->item_name ),
			'url'       => home_url(),
			'blog_id'   => get_current_blog_id(),
			'site_url'  => $url,
			'domain'    => $domain,
		);

		$api_url = add_query_arg( 'wc-api', 'lm-license-api', $this->api_url );

		$args = array(
			'method'      => 'POST',
			'timeout'     => 15, // Longer timeout for activation only.
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'sslverify'   => false,
			'headers'     => array(),
			'body'        => $api_params,
			'cookies'     => array(),
		);

		// Call the custom API.
		$response = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			// Try with SSL checking.
			$args['sslverify'] = true;
			$response          = wp_remote_post( $api_url, $args );

			if ( is_wp_error( $response ) ) {
				return 'Error: ' . $response->get_error_message();
			}
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return __( 'Error: Could not connect to license server.', 'demowp' );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return __( 'Error: Empty response from license server.', 'demowp' );
		}

		$license_data = json_decode( $body );

		if ( ! is_object( $license_data ) ) {
			return __( 'Error: Invalid response from license server.', 'demowp' );
		}

		if ( ! isset( $license_data->activated ) || false === $license_data->activated ) {
			$message = ! empty( $license_data->error ) ? $license_data->error : __( 'Error: Could not activate license.', 'demowp' );
			delete_option( $this->prefix . '_license_status' );
			return $message;
		}

		// Update license status.
		if ( ! empty( $license_data->license ) ) {
			update_option( $this->prefix . '_license_status', $license_data->license );
		}
		if ( ! empty( $license_data->salt ) ) {
			update_option( $this->prefix . '_license_salt', $license_data->salt );
		}

		return '';
	}

	/**
	 * Check for Updates by request to the marketplace
	 *
	 * @param object $transient plugin update array build by WordPress.
	 * @return object modified plugin update array.
	 */
	public function check_update( $transient ) {
		if ( empty( $transient ) || ! is_object( $transient ) ) {
			return $transient;
		}

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// If response for current product isn't empty check for override.
		if ( ! empty( $transient->response ) && ! empty( $transient->response[ $this->slug ] ) && false === $this->wp_override ) {
			return $transient;
		}

		$license = get_option( $this->prefix . '_license_key', '' );
		$salt    = get_option( $this->prefix . '_license_salt', '' );
		$status  = get_option( $this->prefix . '_license_status', '' );

		// Only check for updates if license is valid.
		if ( empty( $license ) || 'valid' !== $status ) {
			return $transient;
		}

		$version_info = $this->api_request(
			'plugin_latest_version',
			array(
				'license'         => $license,
				'item_name'       => $this->item_name,
				'slug'            => $this->slug,
				'current_version' => $this->version,
				'salt'            => $salt,
			)
		);

		if ( false !== $version_info && is_object( $version_info ) && isset( $version_info->new_version ) ) {
			$version = $version_info->new_version;

			if ( version_compare( $this->version, $version, '<' ) ) {
				$transient->response[ $this->slug ] = (object) array(
					'id'          => isset( $version_info->id ) ? $version_info->id : '',
					'slug'        => isset( $version_info->slug ) ? $version_info->slug : $this->name,
					'plugin'      => $this->slug,
					'new_version' => $version,
					'url'         => isset( $version_info->url ) ? $version_info->url : '',
					'package'     => isset( $version_info->package ) ? $version_info->package : '',
				);
			}
		}

		return $transient;
	}

	/**
	 * Updates information on the "View version x.x details" popup
	 *
	 * @param mixed  $data   Plugin data.
	 * @param string $action The type of information being requested.
	 * @param object $args   Plugin API arguments.
	 * @return mixed Plugin data.
	 */
	public function plugins_api_filter( $data, $action = '', $args = null ) {
		if ( 'plugin_information' !== $action ) {
			return $data;
		}

		if ( empty( $this->slug ) ) {
			return $data;
		}

		$slug_parts = explode( '/', $this->slug );
		$slug       = $slug_parts[0];

		if ( ! isset( $args->slug ) || ( $args->slug !== $slug ) ) {
			return $data;
		}

		$license = get_option( $this->prefix . '_license_key', '' );
		$salt    = get_option( $this->prefix . '_license_salt', '' );

		$to_send = array(
			'license' => $license,
			'salt'    => $salt,
			'slug'    => $this->slug,
			'is_ssl'  => is_ssl(),
			'fields'  => array(
				'banners' => false,
				'reviews' => false,
			),
		);

		$cache_key = 'demowp_api_request_' . substr( md5( wp_json_encode( $this->item_name ) ), 0, 15 );

		// Get the transient where we store the api request for this plugin for 24 hours.
		$api_request_transient = get_site_transient( $cache_key );

		if ( ! empty( $api_request_transient ) && is_object( $api_request_transient ) && ! is_wp_error( $api_request_transient ) ) {
			return $api_request_transient;
		}

		$api_response = $this->api_request( 'plugin_information', $to_send );

		if ( false === $api_response || is_wp_error( $api_response ) ) {
			return $data;
		}

		if ( is_object( $api_response ) && ! empty( $api_response->sections ) ) {
			$api_response->sections = (array) $api_response->sections;
		}

		// Expires in 1 day.
		set_site_transient( $cache_key, $api_response, DAY_IN_SECONDS );

		return $api_response;
	}

	/**
	 * Show message for major updates
	 *
	 * @param array $args plugin data.
	 */
	public function in_plugin_update_message( $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$license = get_option( $this->prefix . '_license_key', '' );
		$salt    = get_option( $this->prefix . '_license_salt', '' );

		$transient_name         = md5( $this->slug . 'plugin_update_info' );
		$transient_version_info = get_site_transient( $transient_name );

		if ( empty( $transient_version_info ) ) {
			$version_info = $this->api_request(
				'plugin_latest_version',
				array(
					'license'         => $license,
					'slug'            => $this->slug,
					'current_version' => $this->version,
					'salt'            => $salt,
				)
			);

			if ( false !== $version_info && ! is_wp_error( $version_info ) ) {
				set_site_transient( $transient_name, $version_info, 12 * HOUR_IN_SECONDS );
			}
		} else {
			$version_info = $transient_version_info;
		}

		if ( false !== $version_info && is_object( $version_info ) && isset( $version_info->new_version ) ) {
			// Show update version block if new version > current and is major.
			if ( version_compare( $this->version, $version_info->new_version, '<' ) && ! empty( $version_info->is_major ) ) {
				$upgrade_notice = '<span class="demowp_plugin_upgrade_notice"> ';

				if ( ! empty( $version_info->major_log ) ) {
					$upgrade_notice .= esc_html( $version_info->major_log );
				} else {
					$upgrade_notice .= sprintf(
						/* translators: %s: new version number */
						esc_html__( '%s is a major update. We recommend creating a full backup before updating.', 'demowp' ),
						esc_html( $version_info->new_version )
					);
				}

				$upgrade_notice .= '</span>';

				echo '<style type="text/css">
					.demowp_plugin_upgrade_notice {
						font-weight: 400;
						color: #fff;
						background: #d53221;
						padding: 1em;
						margin: 9px 0;
						display: block;
						box-sizing: border-box;
					}
					.demowp_plugin_upgrade_notice:before {
						content: "\f348";
						display: inline-block;
						font: 400 18px/1 dashicons;
						speak: none;
						margin: 0 8px 0 -2px;
						vertical-align: top;
					}
				</style>' . wp_kses_post( $upgrade_notice );
			}
		}
	}

	/**
	 * Extends the download URL with parameters needed for the API call.
	 *
	 * @param string $download_url The download URL.
	 * @param array  $data         Plugin data.
	 * @return string
	 */
	private function extend_download_url( $download_url, $data ) {
		$url    = get_site_url( get_current_blog_id() );
		$domain = strtolower( rawurlencode( rtrim( $url, '/' ) ) );
		$salt   = get_option( $this->prefix . '_license_salt', '' );

		$api_params = array(
			'action'    => 'get_last_version',
			'license'   => ! empty( $data['license'] ) ? $data['license'] : '',
			'item_name' => rawurlencode( $this->item_name ),
			'blog_id'   => get_current_blog_id(),
			'site_url'  => rawurlencode( $url ),
			'domain'    => rawurlencode( $domain ),
			'slug'      => ! empty( $data['slug'] ) ? rawurlencode( $data['slug'] ) : '',
			'salt'      => $salt,
		);

		return add_query_arg( $api_params, $download_url );
	}

	/**
	 * Calls the API and returns the object delivered by the API.
	 *
	 * @param string $action The requested action.
	 * @param array  $data   Parameters for the API action.
	 * @return false|object Returns false on failure, object on success.
	 */
	private function api_request( $action, $data ) {
		if ( empty( $data['slug'] ) || $data['slug'] !== $this->slug ) {
			return false;
		}

		if ( $this->api_url === trailingslashit( home_url() ) ) {
			return false; // Don't allow a plugin to ping itself.
		}

		$url    = get_site_url( get_current_blog_id() );
		$domain = strtolower( rawurlencode( rtrim( $url, '/' ) ) );

		$api_params = array(
			'action'       => $action,
			'license'      => ! empty( $data['license'] ) ? $data['license'] : '',
			'salt'         => ! empty( $data['salt'] ) ? $data['salt'] : '',
			'item_name'    => rawurlencode( $this->item_name ),
			'item_version' => ! empty( $data['current_version'] ) ? $data['current_version'] : $this->version,
			'blog_id'      => get_current_blog_id(),
			'site_url'     => $url,
			'domain'       => $domain,
			'slug'         => $data['slug'],
		);

		$api_url = add_query_arg( 'wc-api', 'upgrade-api', $this->api_url );

		$request = wp_remote_post(
			$api_url,
			array(
				'timeout'   => $this->api_timeout,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		// Return false if request failed (server down, timeout, etc.).
		if ( is_wp_error( $request ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $request );
		if ( 200 !== $response_code ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $request );
		if ( empty( $body ) ) {
			return false;
		}

		$response = json_decode( $body );

		if ( ! is_object( $response ) ) {
			return false;
		}

		if ( ! empty( $response->package ) ) {
			$response->package = $this->extend_download_url( $response->package, $data );
		}

		if ( ! empty( $response->download_link ) ) {
			$response->download_link = $this->extend_download_url( $response->download_link, $data );
		}

		if ( 'plugin_information' === $action ) {
			if ( isset( $response->sections ) ) {
				$response->sections = maybe_unserialize( $response->sections );
			}
		}

		return $response;
	}

	/**
	 * Get the license status.
	 *
	 * @return string License status ('valid', 'invalid', or empty).
	 */
	public static function get_license_status() {
		if ( ! defined( 'DEMOWP_LICENSE_PREFIX' ) ) {
			return '';
		}
		return get_option( DEMOWP_LICENSE_PREFIX . '_license_status', '' );
	}

	/**
	 * Check if the license is valid.
	 *
	 * @return bool True if license is valid.
	 */
	public static function is_license_valid() {
		return 'valid' === self::get_license_status();
	}
}
