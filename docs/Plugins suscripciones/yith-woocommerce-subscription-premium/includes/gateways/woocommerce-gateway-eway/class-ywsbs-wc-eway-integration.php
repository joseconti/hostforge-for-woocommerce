<?php
/**
 * YWSBS_WC_EWAY_Integration integration with WooCommerce Payments Plugin
 *
 * @class   YWSBS_WC_EWAY_Integration
 * @package YITH/Subscription/Gateways
 * @since   2.4.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Compatibility class for  WooCommerce Payments.
 */
class YWSBS_WC_EWAY_Integration {
	use YITH_WC_Subscription_Singleton_Trait;

	/**
	 * Constructor
	 */
	private function __construct() {

		// Include files.
		add_action( 'init', array( $this, 'include_files' ), 0 );

		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_eway_integration_gateway' ), 100 );
		add_filter( 'ywsbs_max_failed_attempts_list', array( $this, 'add_failed_attempts' ) );
		add_filter( 'ywsbs_get_num_of_days_between_attemps', array( $this, 'add_num_of_days_between_attempts' ) );
		add_filter( 'ywsbs_from_list', array( $this, 'add_from_list' ) );
	}

	/**
	 * Include compatibility class files
	 *
	 * @since 3.6.0
	 * @return void
	 */
	public function include_files() {
		include_once 'class-ywsbs-wc-eway.php';
	}

	/**
	 * Add this gateway in the list of maximum number of attempts to do.
	 *
	 * @param array $gateways List of gateways.
	 * @return array
	 */
	public function add_failed_attempts( $gateways ) {
		$gateways['eway'] = 4;
		return $gateways;
	}

	/**
	 * Add this gateway in the list of maximum number of attempts to do.
	 *
	 * @param array $gateways List of gateways.
	 * @return array
	 */
	public function add_num_of_days_between_attempts( $gateways ) {
		$gateways['eway'] = 5;
		return $gateways;
	}

	/**
	 * Add this gateway in the list "from" to understand from where the
	 * update status is requested.
	 *
	 * @param array $gateways List of gateways.
	 * @return array
	 */
	public function add_from_list( $gateways ) {
		$gateways[] = __( 'eWAY', 'yith-woocommerce-subscription' );
		return $gateways;
	}

	/**
	 * Replace the main gateway with the sources gateway.
	 *
	 * @param array $methods List of gateways.
	 *
	 * @return array
	 */
	public function add_eway_integration_gateway( $methods ) {

		if ( ( isset( $_GET['page'], $_GET['tab'] ) && $_GET['page'] === 'wc-settings' && $_GET['tab'] === 'checkout' ) ) { //phpcs:ignore
			return $methods;
		}

		foreach ( $methods as $key => $method ) {

			if ( 'WC_Gateway_EWAY' === $method ) {
				$methods[ $key ] = 'YWSBS_WC_EWAY';
			}
		}

		return $methods;
	}
}
