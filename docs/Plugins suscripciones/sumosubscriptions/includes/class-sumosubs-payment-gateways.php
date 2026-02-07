<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle subscription payment gateways.
 * 
 * @class SUMOSubs_Payment_Gateways
 */
class SUMOSubs_Payment_Gateways {

    /**
     * Check if our payment gateways are loaded in to the WC checkout.
     *
     * @var bool 
     */
    private static $gateways_loaded = false;

    /**
     * Automatic payments supported payment gateways.
     *
     * @var array 
     */
    protected static $subscription_payment_gateways = array();

    /**
     * Need auto payments alone in checkout?
     *
     * @var bool 
     */
    protected static $auto_payments_alone_enabled = false;

    /**
     * Check whether mixed payments are enabled in checkout.
     *
     * @var bool 
     */
    protected static $mixed_payments_enabled = false;

    /**
     * Check whether checkout has subscription
     *
     * @var bool 
     */
    protected static $checkout_has_subscription;

    /**
     * Check whether checkout having multiple subscriptions
     *
     * @var bool 
     */
    protected static $checkout_has_multiple_subscriptions;

    /**
     * Check whether checkout has synced subscription
     *
     * @var bool 
     */
    protected static $checkout_has_synced_subscription;

    /**
     * Check whether checkout has subscription with signup and without trial
     *
     * @var bool 
     */
    protected static $checkout_has_signup_subscription_without_trial;

    /**
     * Load deprecated gateways.
     * 
     * @var array 
     */
    protected static $deprecated_gateways = array();

    /**
     * Init SUMOSubs_Payment_Gateways.
     */
    public static function init() {
        self::$auto_payments_alone_enabled = 'no' === SUMOSubs_Admin_Options::get_option( 'accept_manual_payments' );
        self::$mixed_payments_enabled      = 'yes' === SUMOSubs_Admin_Options::get_option( 'accept_manual_payments' ) && 'no' === SUMOSubs_Admin_Options::get_option( 'disable_auto_payments' );

        add_action( 'plugins_loaded', __CLASS__ . '::load_payment_gateways', 20 );
        add_filter( 'woocommerce_payment_gateways', __CLASS__ . '::add_payment_gateways', 100 );
        add_filter( 'woocommerce_cart_needs_payment', __CLASS__ . '::need_payment_gateways', 999, 2 );
        add_filter( 'woocommerce_order_needs_payment', __CLASS__ . '::order_needs_payment', 999, 2 );
        add_filter( 'woocommerce_available_payment_gateways', __CLASS__ . '::get_payment_gateways', 99 );
        add_filter( 'woocommerce_gateway_description', __CLASS__ . '::maybe_get_gateway_description_with_payment_mode', 99, 2 );
        add_action( 'woocommerce_blocks_loaded', __CLASS__ . '::add_gateways_blocks_support' );
    }

    /**
     * Get payment gateways to load in to the WC checkout.
     */
    public static function load_payment_gateways() {
        if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
            return;
        }

        if ( ! self::$gateways_loaded ) {
            include_once 'gateways/paypal/paypal-standard/class-sumosubs-paypal-standard.php';
            include_once 'gateways/stripe/class-sumosubs-wc-stripe-gateway.php';
            include_once 'gateways/paypal/paypal-reference-transaction/class-sumosubs-paypal-reference-transaction-gateway.php';

            self::$gateways_loaded = true;
        }

        // Deprecated
        if ( empty( self::$deprecated_gateways ) ) {
            self::$deprecated_gateways[] = include_once 'deprecated/gateways/paypal-adaptive/class-paypal-adaptive-gateway.php';
            self::$deprecated_gateways[] = include_once 'deprecated/gateways/sumo-stripe/class-sumo-stripe-gateway.php';
        }
    }

    /**
     * Add subscription payment gateways.
     *
     * @param WC_Payment_Gateway[] $gateways
     * @return array
     */
    public static function add_payment_gateways( $gateways ) {
        foreach ( $gateways as $key => $method ) {
            if ( ! self::$gateways_loaded && class_exists( 'WC_Stripe' ) && class_exists( 'SUMOSubs_WC_Stripe_UPE_Payment_Gateway_Helper' ) && ( 'WC_Stripe_UPE_Payment_Gateway' === $method || $method instanceof WC_Stripe_UPE_Payment_Gateway ) ) {
                $gateways[ $key ] = new SUMOSubs_WC_Stripe_UPE_Payment_Gateway_Helper();
            }
        }

        if ( self::$gateways_loaded ) {
            $gateways[] = 'SUMOSubs_PayPal_Reference_Transaction_Gateway';
        }

        return $gateways;
    }

    /**
     * Get an array of subscription payment gateways.
     * 
     * @return WC_Payment_Gateway::$id[]
     */
    public static function get_subscription_payment_gateways() {
        /**
         * Get subscription supported payment gateways.
         * 
         * @since 1.0
         */
        return ( array ) apply_filters( 'sumosubscriptions_available_payment_gateways', self::$subscription_payment_gateways );
    }

    /**
     * Check if automatic payment gateways alone enabled in checkout.
     *
     * @return bool 
     */
    public static function auto_payments_alone_enabled() {
        return self::$auto_payments_alone_enabled;
    }

    /**
     * Check whether mixed payments are enabled in checkout.
     *
     * @return bool 
     */
    public static function mixed_payments_enabled() {
        return self::$mixed_payments_enabled;
    }

    /**
     * Check whether the cart contains subscription.
     *
     * @return bool
     */
    public static function checkout_has_subscription() {
        if ( is_checkout_pay_page() ) {
            if ( is_null( self::$checkout_has_subscription ) ) {
                self::$checkout_has_subscription = sumosubs_get_subscription_order_from_pay_for_order_page() > 0;
            }

            return self::$checkout_has_subscription;
        } else if ( ! is_null( WC()->cart ) ) {
            if ( is_null( self::$checkout_has_subscription ) ) {
                self::$checkout_has_subscription = sumo_is_cart_contains_subscription_items() || SUMOSubs_Order_Subscription::is_subscribed();
            }

            return self::$checkout_has_subscription;
        }

        return false;
    }

    /**
     * Check whether the cart contains multiple subscriptions.
     *
     * @return bool
     */
    public static function checkout_has_multiple_subscriptions() {
        if ( ! is_null( WC()->cart ) ) {
            if ( is_null( self::$checkout_has_multiple_subscriptions ) ) {
                if ( SUMOSubs_Order_Subscription::is_subscribed() ) {
                    self::$checkout_has_multiple_subscriptions = false;
                } else {
                    self::$checkout_has_multiple_subscriptions = count( sumo_pluck_subscription_products( WC()->cart ) ) > 1;
                }
            }

            return self::$checkout_has_multiple_subscriptions;
        }

        return false;
    }

    /**
     * Check whether the cart contains synced subscription.
     *
     * @return bool
     */
    public static function checkout_has_synced_subscription() {
        if ( is_checkout_pay_page() ) {
            if ( is_null( self::$checkout_has_synced_subscription ) ) {
                self::$checkout_has_synced_subscription = SUMOSubs_Synchronization::order_contains_subscription_sync( sumosubs_get_subscription_order_from_pay_for_order_page() );
            }

            return self::$checkout_has_synced_subscription;
        } else if ( ! is_null( WC()->cart ) ) {
            if ( is_null( self::$checkout_has_synced_subscription ) ) {
                self::$checkout_has_synced_subscription = SUMOSubs_Synchronization::cart_contains_sync();
            }

            return self::$checkout_has_synced_subscription;
        }

        return false;
    }

    /**
     * Check whether the cart contains subscription with signup and without trial.
     *
     * @return bool
     */
    public static function checkout_has_signup_subscription_without_trial() {
        if ( ! is_null( WC()->cart ) && ! empty( WC()->cart->cart_contents ) ) {
            if ( is_null( self::$checkout_has_signup_subscription_without_trial ) ) {
                if ( SUMOSubs_Order_Subscription::is_subscribed() ) {
                    self::$checkout_has_signup_subscription_without_trial = false;
                } else {
                    foreach ( WC()->cart->cart_contents as $cart_item ) {
                        if ( empty( $cart_item[ 'data' ] ) ) {
                            continue;
                        }

                        $subscription_plan = sumo_get_subscription_plan( 0, $cart_item[ 'data' ]->get_id() );
                        if ( '1' === $subscription_plan[ 'signup_status' ] && '1' !== $subscription_plan[ 'trial_status' ] ) {
                            self::$checkout_has_signup_subscription_without_trial = true;
                            break;
                        }
                    }
                }
            }

            return self::$checkout_has_signup_subscription_without_trial;
        }

        return false;
    }

    /**
     * Check whether the cart/order total is zero in checkout.
     *
     * @return boolean
     */
    public static function checkout_has_order_total_zero() {
        $subscription_order_id = sumosubs_get_subscription_order_from_pay_for_order_page();
        if ( $subscription_order_id ) {
            if ( wc_get_order( $subscription_order_id )->get_total() <= 0 ) {
                return true;
            }
        } else if ( isset( WC()->cart->total ) && WC()->cart->total <= 0 ) {
            return true;
        }

        return false;
    }

    /**
     * Check whether the gateway requires automatic renewals in checkout.
     *
     * @param string $gateway_id
     * @return bool
     */
    public static function gateway_requires_auto_renewals( $gateway_id ) {
        if ( self::checkout_has_subscription() ) {
            if ( 'yes' !== SUMOSubs_Admin_Options::get_option( 'show_subscription_payment_gateways_when_order_amt_0' ) && self::checkout_has_order_total_zero() ) {
                return false;
            }

            if ( self::$mixed_payments_enabled ) {
                switch ( SUMOSubs_Admin_Options::get_option( 'subscription_payment_gateway_mode' ) ) {
                    case 'force-auto':
                        return in_array( $gateway_id, self::get_subscription_payment_gateways() ) ? true : false;
                        break;
                    case 'auto-r-manual':
                        return isset( $_REQUEST[ "{$gateway_id}_auto_payment_mode_enabled" ] ) && 'yes' === wc_clean( wp_unslash( $_REQUEST[ "{$gateway_id}_auto_payment_mode_enabled" ] ) );
                        break;
                }
            } else if ( self::$auto_payments_alone_enabled ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Need to show payment gateways when order amount is 0?
     *
     * @param bool $needs_payment
     * @param object $cart
     * @return bool
     */
    public static function need_payment_gateways( $needs_payment, $cart ) {
        if ( $cart->total <= 0 && self::checkout_has_subscription() ) {
            $needs_payment = 'yes' === SUMOSubs_Admin_Options::get_option( 'show_subscription_payment_gateways_when_order_amt_0' ) ? true : false;
        }

        return $needs_payment;
    }

    /**
     * Check if the order needs payment when the checkout is $0.
     *
     * @param bool $needs_payment
     * @return bool
     */
    public static function order_needs_payment( $needs_payment, $order ) {
        $order = wc_get_order( $order );

        if ( $order && $order->get_total() <= 0 && sumo_order_contains_subscription( $order ) ) {
            if ( ! is_null( WC()->cart ) ) {
                $needs_payment = 'yes' === SUMOSubs_Admin_Options::get_option( 'show_subscription_payment_gateways_when_order_amt_0' ) ? true : false;
            } else if ( $order->has_status( array( 'checkout-draft', 'pending', 'failed' ) ) ) {
                $needs_payment = true;
            }
        }

        return $needs_payment;
    }

    /**
     * Check whether specific payment gateway is needed.
     *
     * @param string $gateway_id
     * @return bool
     */
    public static function need_payment_gateway( $gateway_id ) {
        $need = true;

        if ( self::$checkout_has_subscription ) {
            if ( self::$auto_payments_alone_enabled ) {
                if ( ! in_array( $gateway_id, self::get_subscription_payment_gateways() ) ) {
                    $need = false;
                }
            } else if ( self::$mixed_payments_enabled ) {
                if ( self::checkout_has_order_total_zero() ) {
                    if ( in_array( $gateway_id, self::get_subscription_payment_gateways() ) ) {
                        $need = 'yes' === SUMOSubs_Admin_Options::get_option( 'show_subscription_payment_gateways_when_order_amt_0' );
                    } else {
                        $need = false;
                    }
                }
            } elseif ( in_array( $gateway_id, self::get_subscription_payment_gateways() ) ) {
                $need = false;
            }
        }

        /**
         * Is payment gateway needed for subscription?
         * 
         * @since 1.0
         */
        return apply_filters( 'sumosubscriptions_need_payment_gateway', $need, $gateway_id );
    }

    /**
     * Handle payment gateways in checkout.
     *
     * @param array $available_gateways
     * @return array
     */
    public static function get_payment_gateways( $available_gateways ) {
        if ( is_admin() || is_null( WC()->cart ) ) {
            return $available_gateways;
        }

        self::checkout_has_subscription();
        self::checkout_has_multiple_subscriptions();

        foreach ( $available_gateways as $gateway_name => $gateway ) {
            if ( ! isset( $gateway->id ) ) {
                continue;
            }

            if ( ! self::need_payment_gateway( $gateway->id ) ) {
                unset( $available_gateways[ $gateway_name ] );
            }
        }

        return $available_gateways;
    }

    /**
     * Maybe get the payment mode for gateway.
     * 
     * @param string $gateway_id
     * @param bool $echo
     * @return string
     */
    public static function maybe_get_payment_mode_for_gateway( $gateway_id, $echo = true ) {
        if (
                self::$mixed_payments_enabled &&
                'auto-r-manual' === SUMOSubs_Admin_Options::get_option( 'subscription_payment_gateway_mode' ) &&
                /**
                 * Get payment mode switcher supported payment gateways.
                 * 
                 * @since 1.0
                 */
                in_array( $gateway_id, apply_filters( 'sumosubscriptions_payment_mode_switcher_payment_gateways', self::get_subscription_payment_gateways(), $gateway_id ) ) &&
                self::checkout_has_subscription() &&
                ! SUMOSubs_My_Account::is_valid_add_or_change_payment_request()
        ) {
            ob_start();
            sumosubscriptions_get_template( 'gateway-payment-mode.php', array( 'gateway_id' => $gateway_id ) );

            if ( $echo ) {
                ob_end_flush();
            } else {
                return ob_get_clean();
            }
        }

        return '';
    }

    /**
     * Maybe get the payment mode for subscription payment gateways in gateway description.
     *
     * @param string $description
     * @param string $gateway_id
     * @return string
     */
    public static function maybe_get_gateway_description_with_payment_mode( $description, $gateway_id ) {
        $description .= self::maybe_get_payment_mode_for_gateway( $gateway_id, false );
        return $description;
    }

    /**
     * Hook in Blocks integration. This action is called in a callback on plugins loaded.
     *
     * @since 15.6.0
     */
    public static function add_gateways_blocks_support() {
        if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            include_once 'gateways/paypal/class-sumosubs-paypal-reference-transaction-gateways-blocks-support.php';

            add_action( 'woocommerce_blocks_payment_method_type_registration', __CLASS__ . '::register_gateways_blocks_support', 5 );
        }
    }

    /**
     * Priority is important here because this ensures this integration is registered before the WooCommerce Blocks built-in SUMOSubs_Payment_Gateways gateway registration.
     * Blocks code has a check in place to only register if subscription paypal reference transaction gateway is not already registered.
     *
     * @since 15.6.0
     * @param object $payment_method_registry
     */
    public static function register_gateways_blocks_support( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
        if ( ! class_exists( 'SUMOSubs_Payment_Gateways' ) ) {
            return;
        }

        $container = Automattic\WooCommerce\Blocks\Package::container();
        $container->register(
                SUMOSubs_Paypal_Reference_Transaction_Gateway_Blocks_Support::class,
                function() {
                    return new SUMOSubs_Paypal_Reference_Transaction_Gateway_Blocks_Support();
                }
        );
        $payment_method_registry->register( $container->get( SUMOSubs_Paypal_Reference_Transaction_Gateway_Blocks_Support::class ) );
    }

}

SUMOSubs_Payment_Gateways::init();

include_once 'deprecated/class-sumosubs-subscription-payment-gateways.php';
