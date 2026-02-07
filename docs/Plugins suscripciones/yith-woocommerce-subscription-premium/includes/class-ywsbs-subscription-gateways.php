<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements YWSBS_Subscription_Gateways Class
 *
 * @class   YWSBS_Subscription_Gateways
 * @since   1.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Gateways' ) ) {

	/**
	 * Class YWSBS_Subscription_Gateways
	 */
	class YWSBS_Subscription_Gateways {

		/**
		 * Load the classes that support different gateway integration
		 */
		public static function load_gateways() {
			foreach ( self::get_available_gateways() as $gateway_id => $gateway ) {
				$method = 'load_' . str_replace( '-', '_', $gateway_id );
				method_exists( __CLASS__, $method ) && self::$method();
			}
		}

		/**
		 *  Get a list of available gateways
		 *
		 * @since 3.6.0
		 * @return array
		 */
		public static function get_available_gateways() {
			return array_filter(
				self::get_supported_gateways(),
				function ( $gateway, $key ) {
					$key = str_replace( '-', '_', $key );
					return apply_filters( "ywsbs_gateway_{$key}_available", $gateway['available'] ?? true );
				},
				ARRAY_FILTER_USE_BOTH
			);
		}

		/**
		 * Check if given gateway is available
		 *
		 * @since 3.10.0
		 * @param string $gateway The gateway if to check.
		 * @return bool
		 */
		public static function is_gateway_available( $gateway ) {
			return array_key_exists( $gateway, self::get_available_gateways() );
		}

		/**
		 *  Check if there are available gateways
		 *
		 * @since 3.6.0
		 * @return bool
		 */
		public static function has_available_gateways() {
			return ! empty( self::get_available_gateways() );
		}

		/**
		 * Get a list of supported gateways
		 *
		 * @since 3.6.0
		 * @return array
		 */
		public static function get_supported_gateways() {

			$paypal_settings = get_option( 'woocommerce_paypal_settings', array() );

			return array(
				'paypal'                          => array(
					'title'     => 'PayPal Standard',
					'available' => isset( $paypal_settings['enabled'] ) && 'yes' === $paypal_settings['enabled'],
				),
				'yith-woocommerce-stripe'         => array(
					'title'     => 'YITH WooCommerce Stripe Premium',
					'available' => defined( 'YITH_WCSTRIPE_PREMIUM' ) && YITH_WCSTRIPE_PREMIUM,
				),
				'yith-paypal-braintree'           => array(
					'title'     => 'YITH PayPal Braintree',
					'available' => defined( 'YITH_BRAINTREE_PREMIUM' ) && YITH_BRAINTREE_PREMIUM,
				),
				'yith-paypal-express-checkout'    => array(
					'title'     => 'YITH PayPal Express Checkout',
					'available' => defined( 'YITH_PAYPAL_EC_VERSION' ) && YITH_PAYPAL_EC_VERSION,
				),
				'yith-woocommerce-stripe-connect' => array(
					'title'     => 'YITH WooCommerce Stripe Connect',
					'available' => defined( 'YITH_WCSC_VERSION' ) && YITH_WCSC_VERSION,
				),
				'yith-woocommerce-account-funds'  => array(
					'title'     => 'YITH WooCommerce Account Funds',
					'available' => defined( 'YITH_FUNDS_PREMIUM' ) && YITH_FUNDS_PREMIUM,
				),
				'woocommerce-stripe'              => array(
					'title'     => 'WooCommerce Stripe Payment Gateway',
					'available' => ( function_exists( 'woocommerce_gateway_stripe' ) && defined( 'WC_STRIPE_VERSION' ) && version_compare( WC_STRIPE_VERSION, '4.1.11', '>' ) ),
				),
				'woocommerce-amazon-pay'          => array(
					'title'     => 'WooCommerce Amazon Pay',
					'available' => class_exists( 'WC_Amazon_Payments_Advanced' ) && defined( 'WC_AMAZON_PAY_VERSION' ) && version_compare( WC_AMAZON_PAY_VERSION, '2.0.0', '>' ),
				),
				'woocommerce-payments'            => array(
					'title'     => 'WooCommerce Payments',
					'available' => function_exists( 'wcpay_init' ) && defined( 'WCPAY_PLUGIN_FILE' ),
				),
				'woocommerce-paypal-payments'     => array(
					'title'     => 'WooCommerce PayPal Payments',
					'available' => class_exists( 'WooCommerce\PayPalCommerce\PluginModule' ),
				),
				'woocommerce-eway'                => array(
					'title'     => 'WooCommerce eWAY Payment',
					'available' => class_exists( 'WC_Gateway_EWAY' ) && defined( 'WOOCOMMERCE_GATEWAY_EWAY_VERSION' ) && version_compare( WOOCOMMERCE_GATEWAY_EWAY_VERSION, '3.2.1', '>=' ),
				),
				'redsys'                          => array(
					'title'     => 'RedSys payment gateway for WooCommerce',
					'available' => function_exists( 'WCRed' ) && defined( 'REDSYS_VERSION' ) && version_compare( REDSYS_VERSION, '14.0.0', '>=' ),
				),
			);
		}

		/**
		 * Load PayPal Standard gateway
		 *
		 * @since 3.6.0
		 * @return void
		 */
		protected static function load_paypal() {
			include_once YITH_YWSBS_INC . 'gateways/paypal/class-yith-wc-subscription-paypal.php';
			YWSBS_Subscription_Paypal();
		}

		/**
		 * Load WooCommerce Stripe gateway
		 *
		 * @since 3.6.0
		 * @return void
		 */
		protected static function load_woocommerce_stripe() {
			include_once YITH_YWSBS_INC . 'gateways/woocommerce-gateway-stripe/class-yith-wc-stripe-integration.php';
			YITH_WC_Stripe_Integration::get_instance();
		}

		/**
		 * Load WooCommerce Amazon Pay gateway
		 *
		 * @since 3.6.0
		 * @return void
		 */
		protected static function load_woocommerce_amazon_pay() {
			include_once YITH_YWSBS_INC . 'gateways/woocommerce-gateway-amazon-payments-advanced/class-ywsbs-amazon-pay.php';
			YWSBS_Amazon_Pay::get_instance();
		}

		/**
		 * Load WooCommerce Payments gateway
		 *
		 * @since 3.6.0
		 * @return void
		 */
		protected static function load_woocommerce_payments() {
			include_once YITH_YWSBS_INC . 'gateways/woocommerce-payments/class-ywsbs-wc-payments-integration.php';
			YWSBS_WC_Payments_Integration::get_instance();
		}

		/**
		 * Load WooCommerce eWay gateway
		 *
		 * @since 3.6.0
		 * @return void
		 */
		protected static function load_woocommerce_eway() {
			include_once YITH_YWSBS_INC . 'gateways/woocommerce-gateway-eway/class-ywsbs-wc-eway-integration.php';
			YWSBS_WC_EWAY_Integration::get_instance();
		}

		/**
		 * Load PayPal Payments gateway
		 *
		 * @since 3.6.0
		 * @return void
		 */
		protected static function load_woocommerce_paypal_payments() {
			include_once YITH_YWSBS_INC . 'gateways/woocommerce-paypal-payments/class-ywsbs-wc-paypal-payments-integration.php';
			YWSBS_WC_PayPal_Payments_Integration::get_instance();
		}
	}
}
