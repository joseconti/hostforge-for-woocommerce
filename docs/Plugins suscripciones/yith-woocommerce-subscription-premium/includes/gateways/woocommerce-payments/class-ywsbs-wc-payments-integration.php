<?php
/**
 * YWSBS_WC_Payments_Integration integration with WooCommerce Payments Plugin
 *
 * @class   YWSBS_WC_Payments_Integration
 * @since   2.4.0
 * @author YITH
 * @package YITH/Subscription/Gateways
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Compatibility class for  WooCommerce Payments.
 */
class YWSBS_WC_Payments_Integration {
	use YITH_WC_Subscription_Singleton_Trait;

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'prevent_subscription_disabler' ), 0 ); // valid from version 10.2.0
		add_action( 'init', array( $this, 'init_integration' ), 0 );
	}

	/**
	 * Prevent WC_Payments_Subscriptions_Disabler to remove plugin contents.
	 * This is a workaround to prevent remove my-account endpoints. See for reference woocommerce-payments/includes/subscriptions/class-wc-payments-subscriptions-disabler.php:625
	 *
	 * @return void
	 */
	public function prevent_subscription_disabler() {
		if ( ! defined( 'WCPAY_VERSION_NUMBER' ) || version_compare( WCPAY_VERSION_NUMBER, '10.2.0', '<' ) ) {
			return;
		}

		$options = array(
			'woocommerce_myaccount_view_subscription_endpoint',
			'woocommerce_myaccount_subscription_payment_method_endpoint',
			'woocommerce_myaccount_subscriptions_endpoint'
		);

		foreach ( $options as $option ) {
			add_filter( "pre_option_{$option}", '__return_empty_string', 0 );
		}
	}

	/**
	 * Init class integrations hooks and filters
	 *
	 * @return void
	 */
	public function init_integration() {
		if ( ! defined( 'WCPAY_VERSION_NUMBER' ) || version_compare( WCPAY_VERSION_NUMBER, '2.4.0', '<' ) ) {
			return;
		}

		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_wp_payments_integration_gateway' ), 100 );
		add_filter( 'ywsbs_max_failed_attempts_list', array( $this, 'add_failed_attempts' ) );
		add_filter( 'ywsbs_get_num_of_days_between_attemps', array( $this, 'add_num_of_days_between_attempts' ) );
		add_filter( 'ywsbs_from_list', array( $this, 'add_from_list' ) );
	}

	/**
	 * Add this gateway in the list of maximum number of attempts to do.
	 *
	 * @param array $list List of gateways.
	 *
	 * @return mixed
	 */
	public function add_failed_attempts( $list ) {
		$list['woocommerce-payments'] = 4;

		return $list;
	}

	/**
	 * Add this gateway in the list of maximum number of attempts to do.
	 *
	 * @param array $list List of gateways.
	 * @return mixed
	 */
	public function add_num_of_days_between_attempts( $list ) {
		$list['woocommerce_payments'] = 5;

		return $list;
	}

	/**
	 * Add this gateway in the list "from" to understand from where the
	 * update status is requested.
	 *
	 * @param array $list List of gateways.
	 *
	 * @return mixed
	 */
	public function add_from_list( $list ) {
		$list[] = __( 'WooCommerce Payments', 'yith-woocommerce-subscription' );

		return $list;
	}

	/**
	 * Replace the main gateway with the sources gateway.
	 *
	 * @param array $methods List of gateways.
	 *
	 * @return array
	 */
	public function add_wp_payments_integration_gateway( $methods ) {

		if ( ( isset( $_GET['page'], $_GET['tab'] ) && 'wc-settings' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && 'checkout' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $methods;
		}

		if (
			! class_exists( 'WC_Payments' )
			|| ! class_exists( 'WCPay\Session_Rate_Limiter' )
			|| ! class_exists( 'WC_Payments_Session_Service' )
			|| ! class_exists( 'WC_Payments_Order_Service' )
			|| ! class_exists( 'WC_Payments_Token_Service' )
			|| ! class_exists( 'WCPay\Duplicate_Payment_Prevention_Service' )
			|| ! class_exists( 'WC_Payment_Gateway_WCPay' )
		) {
			return $methods;
		}

		$api_client                           = WC_Payments::create_api_client();
		$account                              = WC_Payments::get_account_service();
		$customer_service                     = WC_Payments::get_customer_service();
		$action_scheduler_service             = WC_Payments::get_action_scheduler_service();
		$localization_service                 = WC_Payments::get_localization_service();
		$fraud_service                        = WC_Payments::get_fraud_service();
		$session_service                      = method_exists( WC_Payments::class, 'get_session_service' ) ? WC_Payments::get_session_service() : new WC_Payments_Session_Service( $api_client );
		$order_service                        = method_exists( WC_Payments::class, 'get_order_service' ) ? WC_Payments::get_order_service() : new WC_Payments_Order_Service( $api_client );
		$token_service                        = method_exists( WC_Payments::class, 'get_token_service' ) ? WC_Payments::get_token_service() : new WC_Payments_Token_Service( $api_client, $customer_service );
		$failed_transaction_rate_limiter      = new WCPay\Session_Rate_Limiter( WCPay\Session_Rate_Limiter::SESSION_KEY_DECLINED_CARD_REGISTRY, 5, 10 * MINUTE_IN_SECONDS );
		$duplicate_payment_prevention_service = new WCPay\Duplicate_Payment_Prevention_Service();

		if ( version_compare( WCPAY_VERSION_NUMBER, '7.0.0', '<' ) ) {
			include_once YITH_YWSBS_INC . 'gateways/woocommerce-payments/legacy/class-ywsbs-wc-payments.php';
			$gateway = new YWSBS_WC_Payments( $api_client, $account, $customer_service, $token_service, $action_scheduler_service, $failed_transaction_rate_limiter, $order_service, $duplicate_payment_prevention_service, $localization_service, $fraud_service );

			$methods = array_map(
				function ( $method ) use ( $gateway ) {
					return ( $method instanceof WC_Payment_Gateway_WCPay ) ? $gateway : $method;
				},
				$methods
			);

		} else {

			$payment_methods    = WC_Payments::get_payment_method_map();
			$duplicates_service = class_exists( 'WCPay\Duplicates_Detection_Service' ) ? new WCPay\Duplicates_Detection_Service() : null;

			if ( class_exists( 'WCPay\Payment_Methods\CC_Payment_Method' ) && isset( $payment_methods[ WCPay\Payment_Methods\CC_Payment_Method::PAYMENT_METHOD_STRIPE_ID ] ) ) {

				$payment_method = $payment_methods[ WCPay\Payment_Methods\CC_Payment_Method::PAYMENT_METHOD_STRIPE_ID ];

				include_once YITH_YWSBS_INC . 'gateways/woocommerce-payments/class-ywsbs-wc-payments.php';
				$gateway = new YWSBS_WC_Payments( $api_client, $account, $customer_service, $token_service, $action_scheduler_service, $payment_method, $payment_methods, $failed_transaction_rate_limiter, $order_service, $duplicate_payment_prevention_service, $localization_service, $fraud_service, $duplicates_service );

				$methods = array_map(
					function ( $method ) use ( $gateway ) {
						return ( $method instanceof WC_Payment_Gateway_WCPay && $gateway->get_stripe_id() === $method->get_stripe_id() ) ? $gateway : $method;
					},
					$methods
				);

			}
		}

		return $methods;
	}
}
