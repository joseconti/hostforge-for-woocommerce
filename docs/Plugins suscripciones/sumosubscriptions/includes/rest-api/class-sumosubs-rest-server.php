<?php

/**
 * Initialize this version of the REST API.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class SUMOSubs_REST_Server {

	/**
	 * REST API namespaces and endpoints.
	 *
	 * @var array
	 */
	protected static $controllers = array();

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public static function init() {
		add_action( 'rest_api_init', __CLASS__ . '::register_rest_routes' );
	}

	/**
	 * Get API namespaces - new namespaces should be registered here.
	 *
	 * @return array List of Namespaces and Main controller classes.
	 */
	protected static function get_rest_namespaces() {
		/**
		 * Get the rest namespaces.
		 * 
		 * @param array $namespaces 
		 * @since 1.0
		 */
		return apply_filters( 'sumosubscriptions_rest_api_get_rest_namespaces', array(
			'wc-sumosubs/v1' => self::get_v1_controllers(),
				) );
	}

	/**
	 * List of controllers in the wc-sumosubs/v1 namespace.
	 *
	 * @return array
	 */
	protected static function get_v1_controllers() {
		return array(
			'subscriptions' => 'SUMOSubs_REST_Subscriptions_Controller',
		);
	}

	/**
	 * Register REST API routes.
	 */
	public static function register_rest_routes() {
		include_once 'class-sumosubs-rest-subscriptions-controller.php' ;

		foreach ( self::get_rest_namespaces() as $namespace => $controllers ) {
			foreach ( $controllers as $controller_name => $controller_class ) {
				self::$controllers[ $namespace ][ $controller_name ] = new $controller_class();
				self::$controllers[ $namespace ][ $controller_name ]->register_routes();
			}
		}
	}
}
