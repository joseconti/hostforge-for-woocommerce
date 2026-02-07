<?php
/**
 * The file that defines the core plugin api class
 *
 * A class definition that includes api's endpoints and functions used across the plugin
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/package/rest-api/version1
 */

/**
 * The core plugin  api class.
 *
 * This is used to define internationalization, api-specific hooks, and
 * endpoints for plugin.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/package/rest-api/version1
 */
class ASWC_Rest_Api {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin api.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the merthods, and set the hooks for the api and
	 *
	 * @since    1.0.0
	 * @param   string $plugin_name    Name of the plugin.
	 * @param   string $version        Version of the plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}


	/**
	 * Define endpoints for the plugin.
	 *
	 * Uses the ASWC_Rest_Api class in order to create the endpoint
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function aswc_add_endpoint() {
					register_rest_route(
						'aswc-route/v1',
						'/aswc-view-subscription/',
						array(
							'methods'             => 'GET',
							'callback'            => array( $this, 'aswc_view_subscription_callback' ),
							'permission_callback' => array( $this, 'aswc_subscription_permission_check' ),
						)
					);

					register_rest_route(
						'aswc-route/v1',
						'/aswc-scheduled-actions/(?P<id>[\d]+)',
						array(
							'methods'             => 'GET',
							'callback'            => array( $this, 'aswc_get_scheduled_actions_callback' ),
							'permission_callback' => array( $this, 'aswc_subscription_permission_check' ),
						)
					);
	}


	/**
	 * Begins validation process of api endpoint.
	 *
	 * @param   Array $request    All information related with the api request containing in this array.
	 * @return  Array   $result   return rest response to server from where the endpoint hits.
	 * @since    1.0.0
	 */
	public function aswc_subscription_permission_check( $request ) {

		$request_params = $request->get_params();
		$aswc_secretkey  = isset( $request_params['consumer_secret'] ) ? $request_params['consumer_secret'] : '';

			$result = $this->aswc_validate_secretkey( $aswc_secretkey );

		return $result;
	}

		/**
		 * Validate secret key.
		 *
		 * @param string $aswc_secretkey Secret key to validate.
		 *
		 * @return bool
		 */
	public function aswc_validate_secretkey( $aswc_secretkey ) {
			$aswc_secret_code = '';

		if ( aswc_check_api_enable() ) {
				$aswc_secret_code = aswc_api_get_secret_key();
		}

		if ( '' === $aswc_secretkey ) {
			return false;
		} elseif ( trim( $aswc_secret_code ) === trim( $aswc_secretkey ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Begins execution of api endpoint.
	 *
	 * @param Array $request Request data from the endpoint.
	 *
	 * @return WP_REST_Response
	 */
	public function aswc_view_subscription_callback( $request ) {

		require_once ASWC_DIR_PATH . 'includes/rest-api/version1/class-aswc-api-process.php';
					$aswc_api_obj     = new ASWC_Api_Process();
					$aswc_resultsdata = $aswc_api_obj->aswc_default_process( $request );
		if ( is_array( $aswc_resultsdata ) && isset( $aswc_resultsdata['code'] ) && 200 === $aswc_resultsdata['code'] ) {

				$aswc_response = new WP_REST_Response( $aswc_resultsdata );
		} else {
			$aswc_resultsdata = array(
				'status'  => 'error',
				'code'    => 404,
				'message' => __( 'Data not found', 'advanced-subscriptions-for-woocommerce' ),

			);
						$aswc_response = new WP_REST_Response( $aswc_resultsdata );
		}
					return $aswc_response;
	}

		/**
		 * Return scheduled actions for a subscription.
		 *
		 * @param WP_REST_Request $request Request instance.
		 *
		 * @return WP_REST_Response
		 */
	public function aswc_get_scheduled_actions_callback( $request ) {
			require_once ASWC_DIR_PATH . 'includes/rest-api/version1/class-aswc-api-scheduler.php';
			$api = new ASWC_Api_Scheduler();

			return $api->aswc_get_scheduled_actions( $request );
	}
}
