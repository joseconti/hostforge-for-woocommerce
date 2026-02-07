<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YITH_WC_Stripe_Integration integration with WooCommerce Stripe Plugin
 *
 * @class   YITH_WC_Stripe_Integration
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Compatibility class for WooCommerce Gateway Stripe.
 */
class YITH_WC_Stripe_Integration {
	use YITH_WC_Subscription_Singleton_Trait;

	/**
	 * Constructor
	 */
	protected function __construct() {

		// Include files.
		add_action( 'init', array( $this, 'include_files' ), 0 );

		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_stripe_integration_gateway' ), 11 );
		add_filter( 'ywsbs_max_failed_attempts_list', array( $this, 'add_failed_attempts' ) );
		add_filter( 'ywsbs_get_num_of_days_between_attemps', array( $this, 'add_num_of_days_between_attempts' ) );
		add_filter( 'ywsbs_from_list', array( $this, 'add_from_list' ) );

		add_action( 'ywsbs_subscription_payment_complete', array( $this, 'add_payment_meta_data_to_subscription' ), 10, 2 );
	}

	/**
	 * Include compatibility class files
	 *
	 * @since 3.6.0
	 * @return void
	 */
	public function include_files() {
		include_once 'class-yith-wc-subscription-wc-stripe.php';
		include_once 'class-yith-wc-subscription-wc-stripe-upe.php';
		include_once 'class-yith-wc-subscription-wc-stripe-sepa.php';
	}

	/**
	 * Add this gateway in the list of maximum number of attempts to do.
	 *
	 * @param array $gateways List of gateways.
	 * @return mixed
	 */
	public function add_failed_attempts( $gateways ) {
		if ( class_exists( 'WC_Stripe_Feature_Flags' ) && WC_Stripe_Feature_Flags::is_upe_preview_enabled() && WC_Stripe_Feature_Flags::is_upe_checkout_enabled() ) {
			$gateways[ YITH_WC_Subscription_WC_Stripe_UPE::ID ] = 4;
		} else {
			$gateways[ YITH_WC_Subscription_WC_Stripe::$gateway_id ] = 4;
		}
		return $gateways;
	}

	/**
	 * Add this gateway in the list of maximum number of attempts to do.
	 *
	 * @param array $gateways List of gateways.
	 * @return mixed
	 */
	public function add_num_of_days_between_attempts( $gateways ) {
		if ( class_exists( 'WC_Stripe_Feature_Flags' ) && WC_Stripe_Feature_Flags::is_upe_preview_enabled() && WC_Stripe_Feature_Flags::is_upe_checkout_enabled() ) {
			$gateways[ YITH_WC_Subscription_WC_Stripe_UPE::ID ] = 5;
		} else {
			$gateways[ YITH_WC_Subscription_WC_Stripe::$gateway_id ] = 5;
		}
		return $gateways;
	}

	/**
	 * Add this gateway in the list "from" to understand from where the
	 * update status is requested.
	 *
	 * @param array $gateways List of gateways.
	 * @return mixed
	 */
	public function add_from_list( $gateways ) {
		if ( class_exists( 'WC_Stripe_Feature_Flags' ) && WC_Stripe_Feature_Flags::is_upe_preview_enabled() && WC_Stripe_Feature_Flags::is_upe_checkout_enabled() ) {
			$gateways[] = YITH_WC_Subscription_WC_Stripe_UPE::get_instance()->payment_methods['card']->get_label();
		} else {
			$gateways[] = YITH_WC_Subscription_WC_Stripe::get_instance()->get_method_title();
		}
		return $gateways;
	}

	/**
	 * Replace the main gateway with the sources gateway.
	 *
	 * @param array $methods List of gateways.
	 *
	 * @return array
	 */
	public function add_stripe_integration_gateway( $methods ) {
        // Avoid override methods on admin sections as it is not required
        if ( is_admin() || $this->is_rest_providers_request() ) {
            return $methods;
        }

        foreach ( $methods as $key => $method ) {
            if ( class_exists( 'WC_Stripe_Feature_Flags' ) && WC_Stripe_Feature_Flags::is_upe_preview_enabled() && WC_Stripe_Feature_Flags::is_upe_checkout_enabled() ) {
                if ( 'WC_Stripe_UPE_Payment_Gateway' === $method || $method instanceof WC_Stripe_UPE_Payment_Gateway ) {
                    $methods[ $key ] = 'YITH_WC_Subscription_WC_Stripe_UPE';
                }
            } else {
                if ( 'WC_Gateway_Stripe' === $method || $method instanceof WC_Gateway_Stripe ) {
                    $methods[ $key ] = 'YITH_WC_Subscription_WC_Stripe';
                }
            }

            if ( 'WC_Gateway_Stripe_Sepa' === $method || $method instanceof WC_Gateway_Stripe_Sepa ) {
                $methods[ $key ] = 'YITH_WC_Subscription_WC_Stripe_Sepa';
            }
        }

		return $methods;
	}

	/**
	 * Is admin rest providers endpoint?
	 * Check if is request /wp-json/wc-admin/settings/payments/providers
	 *
	 * @return boolean
	 */
	protected function is_rest_providers_request() {
		global $wp;

		return wp_is_rest_endpoint() && $wp instanceof WP && 'wp-json/wc-admin/settings/payments/providers' === $wp->request;
	}

	/**
	 * Register the payment information on subscription meta.
	 *
	 * @param YWSBS_Subscription $subscription Subscription.
	 * @param WC_Order           $order        Order.
	 */
	public function add_payment_meta_data_to_subscription( $subscription, $order ) {

		if ( ! $subscription || ! $order || $subscription->get_order_id() !== $order->get_id() ) {
			return;
		}

		if ( in_array( $order->get_payment_method(), array( 'stripe', 'stripe_sepa', 'stripe_sepa_debit' ), true ) ) {
			$subscription->set( '_stripe_customer_id', $order->get_meta( '_stripe_customer_id' ) );
			$subscription->set( '_stripe_source_id', $order->get_meta( '_stripe_source_id' ) );
		}
	}
}
