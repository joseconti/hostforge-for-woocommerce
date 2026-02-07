<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle subscription's renewal payment synchronization.
 * 
 * @class SUMOSubs_Synchronization
 */
class SUMOSubs_Synchronization {

    /**
     * Check whether synchronization is enabled site wide. 
     * 
     * @var bool 
     */
    public static $sync_enabled_site_wide = false;

    /**
     * Get sync mode. 
     * 
     * @var string 
     */
    public static $sync_mode = '';

    /**
     * Get payment for synced periods. 
     * 
     * @var string 
     */
    public static $payment_type = '';

    /**
     * Check whether to apply prorated fee on either in the initial payment or in the initial renewal payment.
     * 
     * @var string 
     */
    public static $apply_prorated_fee_on = '';

    /**
     * Check where to apply prorate payment - all subscriptions/virtual subscriptions
     * 
     * @var string 
     */
    public static $prorate_payment_for = '';

    /**
     * Check whether to show synced next payment date in single product page
     * 
     * @var bool 
     */
    public static $show_synced_date = false;

    /**
     * Get the subscription. 
     * 
     * @var object 
     */
    public static $subscription = false;

    /**
     * Get the subscription object type. 
     * 
     * @var string 
     */
    public static $subscription_obj_type = 'product';

    /**
     * Check whether the subscriber signing up on exact sync date. 
     * 
     * @var bool 
     */
    public static $signingup_on_sync_date = false;

    /**
     * Check whether the initial payment date is extended by subscription length
     * 
     * @var bool 
     */
    protected static $sync_duration_extented = false;

    /**
     * Get an array of weekdays.
     * strtotime() only handles English, so can't use $wp_locale->weekday in some places
     * 
     * @var array 
     */
    protected static $weekdays = array(
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    );

    /**
     * Init SUMOSubs_Synchronization.
     */
    public static function init() {
        self::$sync_enabled_site_wide = 'yes' === SUMOSubs_Admin_Options::get_option( 'allow_synchronization' );
        self::$show_synced_date       = 'yes' === SUMOSubs_Admin_Options::get_option( 'when_sync_show_next_due_date_in_product_page' );
        self::$payment_type           = SUMOSubs_Admin_Options::get_option( 'payment_mode_when_sync' );
        self::$apply_prorated_fee_on  = SUMOSubs_Admin_Options::get_option( 'when_sync_prorate_payment_during' );
        self::$prorate_payment_for    = SUMOSubs_Admin_Options::get_option( 'when_sync_prorate_payment_for' );
        self::$sync_mode              = SUMOSubs_Admin_Options::get_option( 'sync_based_on' );

        add_action( 'woocommerce_after_add_to_cart_form', __CLASS__ . '::get_next_synced_payment_date_to_display' );
        add_filter( 'sumosubscriptions_get_single_variation_data_via_wc_to_display', __CLASS__ . '::get_next_synced_payment_date_to_display', 10, 2 );
        add_filter( 'sumosubscriptions_get_single_variation_data_to_display', __CLASS__ . '::get_next_synced_payment_date_to_display', 10, 2 );
        add_filter( 'woocommerce_add_cart_item', __CLASS__ . '::add_to_cart_synced_item' );
        add_filter( 'sumosubscriptions_get_line_total', __CLASS__ . '::set_line_total', 10, 5 );
        add_action( 'woocommerce_before_calculate_totals', __CLASS__ . '::refresh_cart', 999 );
    }

    /**
     * Check whether the subscription is synchronized.
     *
     * @param mixed $subscription
     * @return boolean
     */
    public static function is_subscription_synced( $subscription ) {
        if ( is_a( $subscription, 'SUMOSubs_Subscription' ) || ( is_numeric( $subscription ) && 'sumosubscriptions' === get_post_type( $subscription ) ) ) {
            self::$subscription_obj_type = 'subscription';
        }

        if ( 'product' === self::$subscription_obj_type ) {
            self::$subscription = sumo_get_subscription_product( $subscription );
        } else {
            self::$subscription = sumo_get_subscription( $subscription );
        }
        return self::$subscription && self::$subscription->is_synced();
    }

    public static function can_prorate_subscription() {
        if (
                'prorate' === self::$payment_type &&
                ! self::$signingup_on_sync_date &&
                (
                'xtra-time-to-charge-full-fee' !== self::$subscription->get_sync( 'subscribed_after_sync_date_type' ) ||
                ( 'xtra-time-to-charge-full-fee' === self::$subscription->get_sync( 'subscribed_after_sync_date_type' ) && ! self::$subscription->get_sync( 'xtra_time_to_charge_full_fee' ) )
                ) &&
                'product' === self::$subscription_obj_type && ! self::$subscription->get_trial( 'forced' )
        ) {
            if (
                    'all-subscriptions' === self::$prorate_payment_for ||
                    ( 'all-virtual' === self::$prorate_payment_for && ( self::$subscription->is_downloadable() || self::$subscription->is_virtual() ) )
            ) {
                if ( self::is_sync_not_started() ) {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    public static function can_prorate_due_date() {
        if (
                ! self::$signingup_on_sync_date &&
                (
                'xtra-time-to-charge-full-fee' !== self::$subscription->get_sync( 'subscribed_after_sync_date_type' ) ||
                ( 'xtra-time-to-charge-full-fee' === self::$subscription->get_sync( 'subscribed_after_sync_date_type' ) && ! self::$subscription->get_sync( 'xtra_time_to_charge_full_fee' ) )
                ) &&
                'product' === self::$subscription_obj_type && ! self::$subscription->get_trial( 'forced' )
        ) {
            if (
                    'all-subscriptions' === self::$prorate_payment_for ||
                    ( 'all-virtual' === self::$prorate_payment_for && ( self::$subscription->is_downloadable() || self::$subscription->is_virtual() ) )
            ) {
                if ( self::is_sync_not_started() ) {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    public static function is_subscriber_signingup_on_sync_date() {
        $this_week  = gmdate( 'N' );
        $this_day   = gmdate( 'd' );
        $this_month = gmdate( 'm' );

        switch ( self::$subscription->get_duration_period() ) {
            case 'W':
                self::$signingup_on_sync_date = $this_week == self::$subscription->get_sync( 'duration_period' ) ? true : false;
                break;
            case 'M':
                if ( 'exact-date-r-day' === self::$subscription->get_sync( 'type' ) ) {
                    self::$signingup_on_sync_date = $this_month == self::$subscription->get_sync( 'duration_period' ) && $this_day == self::$subscription->get_sync( 'duration_period_length' ) ? true : false;
                } else {
                    self::$signingup_on_sync_date = $this_day == self::$subscription->get_sync( 'duration_period_length' ) ? true : false;
                }
                break;
            case 'Y':
                if ( 'exact-date-r-day' === self::$subscription->get_sync( 'type' ) ) {
                    self::$signingup_on_sync_date = $this_month == self::$subscription->get_sync( 'duration_period' ) && $this_day == self::$subscription->get_sync( 'duration_period_length' ) ? true : false;
                } else {
                    self::$signingup_on_sync_date = $this_day == self::$subscription->get_sync( 'duration_period_length' ) ? true : false;
                }
                break;
        }
        return self::$signingup_on_sync_date;
    }

    public static function order_contains_subscription_sync( $order_id ) {
        if ( empty( $order_id ) ) {
            return false;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }

        $parent_order_id = sumosubs_get_parent_order_id( $order );
        if ( SUMOSubs_Order_Subscription::is_subscribed( 0, $parent_order_id, $order->get_customer_id() ) ) {
            return false;
        }

        $subscription_products = sumo_pluck_subscription_products( $order );
        if ( empty( $subscription_products ) ) {
            return false;
        }

        $sync_product_ids = array();
        $parent_order     = wc_get_order( $parent_order_id );
        $subscriptions    = $parent_order ? $parent_order->get_meta( 'sumo_subsc_get_available_postids_from_parent_order', true ) : array();

        if ( ! empty( $subscriptions ) ) {
            foreach ( $subscriptions as $product_id => $subscription_id ) {
                if ( $product_id && $subscription_id && in_array( $product_id, $subscription_products ) && self::is_subscription_synced( $subscription_id ) ) {
                    $sync_product_ids[] = $product_id;
                }
            }
        } else {
            foreach ( $subscription_products as $product_id ) {
                if ( self::is_subscription_synced( $product_id ) ) {
                    $sync_product_ids[] = $product_id;
                }
            }
        }

        return ! empty( $sync_product_ids );
    }

    public static function cart_contains_sync( $contains = '' ) {
        if ( ! empty( WC()->cart->cart_contents ) ) {
            foreach ( WC()->cart->cart_contents as $cart_key => $cart_item ) {
                if ( '' !== $contains ) {
                    if ( ! empty( $cart_item[ 'sumosubscriptions' ][ 'sync' ][ $contains ] ) ) {
                        return true;
                    }
                } elseif ( ! empty( $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ] ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function cart_item_contains_sync( $_cart_item, $contains = '' ) {
        if ( ! empty( WC()->cart->cart_contents ) ) {
            if ( is_numeric( $_cart_item ) ) {
                foreach ( WC()->cart->cart_contents as $cart_key => $cart_item ) {
                    if ( $_cart_item == $cart_item[ 'variation_id' ] || $_cart_item == $cart_item[ 'product_id' ] ) {
                        if ( '' !== $contains ) {
                            if ( ! empty( $cart_item[ 'sumosubscriptions' ][ 'sync' ][ $contains ] ) ) {
                                return true;
                            }
                        } elseif ( ! empty( $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ] ) ) {
                            return true;
                        }
                    }
                }
            } elseif ( '' !== $contains ) {
                if ( ! empty( $_cart_item[ 'sumosubscriptions' ][ 'sync' ][ $contains ] ) ) {
                    return true;
                }
            } else if ( ! empty( $_cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ] ) ) {
                return true;
            }
        }
        return false;
    }

    public static function get_synced( $product_id = null, $customer_id = 0 ) {
        $prorated_data = array();

        if ( is_numeric( $customer_id ) && $customer_id ) {
            $prorated_data = get_user_meta( $customer_id, 'sumosubs_subscription_prorated_data', true );

            if ( ! empty( $prorated_data[ $product_id ] ) ) {
                $prorated_data = $prorated_data[ $product_id ];
            }
        } else if ( ! empty( WC()->cart->cart_contents ) ) {
            foreach ( WC()->cart->cart_contents as $cart_item ) {
                if ( ! empty( $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ] ) ) {
                    $item_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ];

                    if ( $product_id == $item_id ) {
                        $prorated_data = $cart_item[ 'sumosubscriptions' ][ 'sync' ];
                        break;
                    } else {
                        $prorated_data[ $item_id ] = $cart_item[ 'sumosubscriptions' ][ 'sync' ];
                    }
                }
            }
        }
        return is_array( $prorated_data ) ? $prorated_data : array();
    }

    /**
     * Check whether to apply prorated fee on either in the initial payment or in the next renewal payment.
     *
     * @param int $product_id The Product post ID
     * @param int $customer_id
     * @return string
     */
    public static function apply_prorated_fee_on( $product_id, $customer_id = 0 ) {
        $sync = self::get_synced( $product_id, $customer_id );
        return ! empty( $sync[ 'apply_prorated_subscription_fee_on' ] ) ? $sync[ 'apply_prorated_subscription_fee_on' ] : null;
    }

    public static function get_prorated_fee( $product_id, $customer_id = 0, $format_decimal = false ) {
        $sync         = self::get_synced( $product_id, $customer_id );
        $prorated_fee = isset( $sync[ 'prorated_subscription_fee' ] ) ? floatval( $sync[ 'prorated_subscription_fee' ] ) : null;

        if ( $format_decimal ) {
            return wc_format_decimal( $prorated_fee, wc_get_price_decimals() );
        }
        return wc_format_decimal( $prorated_fee );
    }

    /**
     * Get prorated date, how long the prorated fee becomes valid for the parent order ?
     *
     * @param int $product_id
     * @return mixed
     */
    public static function get_prorated_date_till( $product_id, $customer_id = 0 ) {
        $sync               = self::get_synced( $product_id, $customer_id );
        $prorated_date_till = '';

        if ( ! empty( $sync[ 'initial_payment_time' ] ) ) {
            $prorated_date_till = $sync[ 'initial_payment_time' ] > 0 ? '<b>' . sumo_display_subscription_date( absint( $sync[ 'initial_payment_time' ] - 86400 ) ) . '</b>' : '';
        }
        return $prorated_date_till;
    }

    public static function initial_payment_delayed( $subscription_id ) {
        $sync = get_post_meta( $subscription_id, 'sumo_subscription_synced_data', true );

        if ( ( ! empty( $sync[ 'xtra_time_to_charge_full_fee' ] ) || ! empty( $sync[ 'extra_duration_period' ] ) ) && isset( $sync[ 'awaiting_initial_payment' ] ) && $sync[ 'awaiting_initial_payment' ] ) {
            return true;
        }
        return false;
    }

    public static function maybe_get_awaiting_initial_payment_charge_time( $subscription_id ) {
        if ( self::initial_payment_delayed( $subscription_id ) && 'Pending' === get_post_meta( $subscription_id, 'sumo_get_status', true ) ) {
            $sync = get_post_meta( $subscription_id, 'sumo_subscription_synced_data', true );
            return $sync[ 'initial_payment_time' ];
        }
        return false;
    }

    public static function current_time_exceeds_xtra_time( $xtra_time_in_days, $sync_time ) {
        if ( is_numeric( $xtra_time_in_days ) && $xtra_time_in_days ) {
            $previous_sync_time = self::get_previous_sync_time( $sync_time );

            if ( $previous_sync_time ) {
                $this_time          = sumo_get_subscription_timestamp( 0, 0, true );
                $xtra_duration_time = sumo_get_subscription_timestamp( "+{$xtra_time_in_days} days", $previous_sync_time, true );

                if ( $this_time > $xtra_duration_time ) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function awaiting_initial_payment( $sync ) {
        return self::current_time_exceeds_xtra_time( $sync[ 'xtra_time_to_charge_full_fee' ], $sync[ 'initial_payment_time' ] );
    }

    public static function get_duration_options( $subscription_period, $show_sync_period = false ) {
        $total_no_of_days_in_the_month = 28; //Set Default for each Month
        $options                       = array();

        switch ( $subscription_period ) {
            case 'W':
                $options = array(
                    '0' => __( 'Do not synchronize', 'sumosubscriptions' ),
                    '1' => __( 'Monday each week', 'sumosubscriptions' ),
                    '2' => __( 'Tuesday each week', 'sumosubscriptions' ),
                    '3' => __( 'Wednesday each week', 'sumosubscriptions' ),
                    '4' => __( 'Thursday each week', 'sumosubscriptions' ),
                    '5' => __( 'Friday each week', 'sumosubscriptions' ),
                    '6' => __( 'Saturday each week', 'sumosubscriptions' ),
                    '7' => __( 'Sunday each week', 'sumosubscriptions' ),
                );
                break;
            case 'M':
                for ( $i = 0; $i <= $total_no_of_days_in_the_month; $i ++ ) {
                    if ( 0 === $i ) {
                        $options[] = __( 'Do not synchronize', 'sumosubscriptions' );
                    } else {
                        /* translators: 1: month value */
                        $options[] = sprintf( __( '%s day of the month', 'sumosubscriptions' ), sumo_get_number_suffix( $i ) );
                    }
                }

                if ( $show_sync_period ) {
                    $options = array(
                        '1'  => __( 'January', 'sumosubscriptions' ),
                        '2'  => __( 'February', 'sumosubscriptions' ),
                        '3'  => __( 'March', 'sumosubscriptions' ),
                        '4'  => __( 'April', 'sumosubscriptions' ),
                        '5'  => __( 'May', 'sumosubscriptions' ),
                        '6'  => __( 'June', 'sumosubscriptions' ),
                        '7'  => __( 'July', 'sumosubscriptions' ),
                        '8'  => __( 'August', 'sumosubscriptions' ),
                        '9'  => __( 'September', 'sumosubscriptions' ),
                        '10' => __( 'October', 'sumosubscriptions' ),
                        '11' => __( 'November', 'sumosubscriptions' ),
                        '12' => __( 'December', 'sumosubscriptions' ),
                    );
                }
                break;
            case 'Y':
                for ( $i = 0; $i <= $total_no_of_days_in_the_month; $i ++ ) {
                    if ( 0 === $i ) {
                        $options[] = __( 'Do not synchronize', 'sumosubscriptions' );
                    } elseif ( 'first-occurrence' === self::$sync_mode ) {
                        /* translators: 1: month value */
                        $options[] = sprintf( __( '%s day of the month', 'sumosubscriptions' ), sumo_get_number_suffix( $i ) );
                    } else {
                        $options[] = $i;
                    }
                }

                if ( $show_sync_period ) {
                    $options = array(
                        '1'  => __( 'January', 'sumosubscriptions' ),
                        '2'  => __( 'February', 'sumosubscriptions' ),
                        '3'  => __( 'March', 'sumosubscriptions' ),
                        '4'  => __( 'April', 'sumosubscriptions' ),
                        '5'  => __( 'May', 'sumosubscriptions' ),
                        '6'  => __( 'June', 'sumosubscriptions' ),
                        '7'  => __( 'July', 'sumosubscriptions' ),
                        '8'  => __( 'August', 'sumosubscriptions' ),
                        '9'  => __( 'September', 'sumosubscriptions' ),
                        '10' => __( 'October', 'sumosubscriptions' ),
                        '11' => __( 'November', 'sumosubscriptions' ),
                        '12' => __( 'December', 'sumosubscriptions' ),
                    );
                }
                break;
        }

        return $options;
    }

    public static function get_xtra_duration_options( $subscription_period, $subscription_duration_length ) {
        $subscription_cycle_in_days = 0;

        switch ( $subscription_period ) {
            case 'Y':
                $subscription_cycle_in_days = 'first-occurrence' === self::$sync_mode ? 28 : 365 * $subscription_duration_length;
                break;
            case 'M':
                $subscription_cycle_in_days = 'first-occurrence' === self::$sync_mode ? 28 : 28 * $subscription_duration_length;
                break;
            case 'W':
                $subscription_cycle_in_days = 'first-occurrence' === self::$sync_mode ? 7 : 7 * $subscription_duration_length;
                break;
        }

        return $subscription_cycle_in_days;
    }

    public static function get_initial_payment_date( $product_id, $display = false ) {
        if ( ! self::$subscription ) {
            self::is_subscription_synced( $product_id );
        }

        $initial_payment_time = sumosubs_get_next_payment_date( 0, $product_id, array(
            'initial_payment'  => self::is_subscriber_signingup_on_sync_date() ? false : self::can_prorate_due_date(),
            'get_as_timestamp' => true,
                ) );

        return $display ? sumo_display_subscription_date( $initial_payment_time ) : $initial_payment_time;
    }

    public static function is_sync_not_started() {
        if ( 'exact-date-r-day' === self::$subscription->get_sync( 'type' ) && self::get_sync_start_time() > 0 && sumo_get_subscription_timestamp( 0, 0, true ) < self::get_sync_start_time() ) {
            if ( in_array( self::$subscription->get_duration_period(), array( 'M', 'Y' ) ) ) {
                return true;
            }
        }

        return false;
    }

    public static function get_sync_start_time() {
        return sumo_get_subscription_timestamp( self::$subscription->get_sync( 'start_year' ) . '/' . self::$subscription->get_sync( 'duration_period' ) . '/' . self::$subscription->get_sync( 'duration_period_length' ), 0, true );
    }

    public static function get_sync_time( $is_trial = false, $is_initial_payment = false, $from_when = false ) {
        $next_sync_time = self::get_next_sync_time( $is_initial_payment, $from_when );

        //If it is Trial Product.
        if ( $is_trial && $is_initial_payment ) {
            $trial_end_time = sumo_get_subscription_timestamp( '+' . sumo_format_subscription_cyle( self::$subscription->get_trial( 'duration_period_length' ) . ' ' . self::$subscription->get_trial( 'duration_period' ) ) );

            if ( $trial_end_time > $next_sync_time ) {
                $next_sync_time = self::get_next_sync_time( $is_initial_payment, $trial_end_time );
            }
        }

        return $next_sync_time;
    }

    public static function prorate_subscription_fee() {
        if ( ! is_numeric( self::$subscription->get_duration_period_length() ) ) {
            return 0;
        }

        $from_time                    = sumo_get_subscription_timestamp( 0, 0, true );
        $sync_time                    = self::get_sync_time( false, true );
        $subscription_duration_length = self::$sync_duration_extented ? 2 * self::$subscription->get_duration_period_length() : self::$subscription->get_duration_period_length();

        $total_days = 0;
        switch ( self::$subscription->get_duration_period() ) {
            case 'W':
                $total_days = $subscription_duration_length * 7;
                break;
            case 'M':
            case 'Y':
                $period     = 'M' === self::$subscription->get_duration_period() ? 'month' : 'year';
                $to_time    = sumo_get_subscription_timestamp( "+{$subscription_duration_length} {$period}", 0, true );
                $total_days = $to_time ? ( max( $to_time, $from_time ) - min( $to_time, $from_time ) ) / 86400 : 0;
                break;
        }

        if ( $total_days <= 0 ) {
            return 0;
        }

        $prorated_days   = $sync_time ? ( max( $sync_time, $from_time ) - min( $sync_time, $from_time ) ) / 86400 : 0;
        $prorated_amount = $prorated_days && $total_days ? ( self::$subscription->get_recurring_amount() / $total_days ) * $prorated_days : self::$subscription->get_recurring_amount();
        return wc_format_decimal( $prorated_amount );
    }

    public static function update_user_meta( $customer_id ) {
        delete_user_meta( $customer_id, 'sumosubs_subscription_prorated_data' );

        $prorated_data = self::get_synced();
        if ( $prorated_data ) {
            add_user_meta( $customer_id, 'sumosubs_subscription_prorated_data', $prorated_data );
        }
    }

    public static function calculate_sync_time_for( $duration_period, $from_time, $is_initial_payment = false ) {
        if ( ! is_numeric( self::$subscription->get_sync( 'duration_period_length' ) ) ) {
            return 0;
        }

        $next_sync_time         = 0;
        $from_day               = gmdate( 'd', $from_time ); // returns the Day
        $from_month             = gmdate( 'm', $from_time ); // returns the Month Number as 01 for jan, 02 for feb,...12 for dec
        $next_day               = gmdate( 'd', sumo_get_subscription_timestamp( 'next day', $from_time ) ); // returns the Next Day
        $duration_period_length = self::$subscription->get_duration_period_length();

        if ( $is_initial_payment && 'first-occurrence' === self::$subscription->get_sync( 'type' ) ) {
            if (
                    self::$subscription->get_sync( 'duration_period_length' ) < $from_day ||
                    self::$subscription->get_sync( 'duration_period_length' ) == $from_day
            ) {
                //The Renewal time crossed this time while the Subscription is Renewing/Subscribing                    
                if ( 12 == $from_month ) {
                    $next_renewal_month = 1;
                    $next_renewal_year  = gmdate( 'Y', $from_time ) + 1;
                } else {
                    $next_renewal_month = $from_month + 1;
                    $next_renewal_year  = gmdate( 'Y', $from_time );
                }
                $next_sync_time = sumo_get_subscription_timestamp(
                        $next_renewal_year . '/' .
                        $next_renewal_month . '/' .
                        self::$subscription->get_sync( 'duration_period_length' )
                );
            } else {
                //The Renewal time not yet crossed this time while the Subscription is Renewing/Subscribing
                $next_sync_time = sumo_get_subscription_timestamp(
                        gmdate( 'Y', $from_time ) . '/' .
                        gmdate( 'm', $from_time ) . '/' .
                        self::$subscription->get_sync( 'duration_period_length' )
                );
            }
        } elseif ( $is_initial_payment ) {
            $next_month = 1;
            $_from_when = 0;

            for ( $i = 1; $i <= $next_month; $i ++ ) {
                if ( 1 === $i ) {
                    $_next_sync_time = self::get_sync_start_time();
                } else {
                    $_next_sync_time = sumo_get_subscription_timestamp( "+{$duration_period_length} {$duration_period}", $_from_when ? $_from_when : $from_time, true );
                }

                if ( $from_time < $_next_sync_time ) {
                    $next_sync_time = sumo_get_subscription_timestamp( $_next_sync_time, 0, true );
                } else {
                    ++ $next_month;
                }
                $_from_when = $_next_sync_time;
            }
        } else {
            $next_sync_time = sumo_get_subscription_timestamp(
                    gmdate( 'Y', sumo_get_subscription_timestamp( "+{$duration_period_length} {$duration_period}", $from_time ) ) . '/' .
                    gmdate( 'm', sumo_get_subscription_timestamp( "+{$duration_period_length} {$duration_period}", $from_time ) ) . '/' .
                    self::$subscription->get_sync( 'duration_period_length' )
            );
        }

        return $next_sync_time;
    }

    public static function calculate_previous_sync_time_for( $duration_period, $from_time ) {
        if ( ! is_numeric( self::$subscription->get_sync( 'duration_period_length' ) ) ) {
            return 0;
        }

        $previous_sync_time     = 0;
        $from_day               = gmdate( 'd', $from_time ); // returns the Day
        $from_month             = gmdate( 'm', $from_time ); // returns the Month Number as 01 for jan, 02 for feb,...12 for dec
        $duration_period_length = self::$subscription->get_duration_period_length();

        if ( 'first-occurrence' === self::$subscription->get_sync( 'type' ) ) {
            if (
                    self::$subscription->get_sync( 'duration_period_length' ) < $from_day ||
                    self::$subscription->get_sync( 'duration_period_length' ) == $from_day
            ) {
                if ( 1 == $from_month ) {
                    $next_renewal_month = 12;
                    $next_renewal_year  = gmdate( 'Y', $from_time ) - 1;
                } else {
                    $next_renewal_month = $from_month - 1;
                    $next_renewal_year  = gmdate( 'Y', $from_time );
                }

                $previous_sync_time = sumo_get_subscription_timestamp(
                        $next_renewal_year . '/' .
                        $next_renewal_month . '/' .
                        self::$subscription->get_sync( 'duration_period_length' )
                );
            }
        } else {
            $previous_sync_time = sumo_get_subscription_timestamp(
                    gmdate( 'Y', sumo_get_subscription_timestamp( "-{$duration_period_length} {$duration_period}", $from_time ) ) . '/' .
                    gmdate( 'm', sumo_get_subscription_timestamp( "-{$duration_period_length} {$duration_period}", $from_time ) ) . '/' .
                    self::$subscription->get_sync( 'duration_period_length' )
            );
        }

        return $previous_sync_time;
    }

    public static function get_next_sync_time( $is_initial_payment = false, $from_when = false ) {
        if ( ! is_numeric( self::$subscription->get_sync( 'duration_period' ) ) && ! is_numeric( self::$subscription->get_duration_period_length() ) ) {
            return 0;
        }

        $next_sync_time = 0;
        $next_due_date  = get_post_meta( self::$subscription->get_id(), 'sumo_get_next_payment_date', true );

        if ( $next_due_date ) {
            $from_time = sumo_get_subscription_timestamp( $next_due_date ); // returns Next Due Date set previously
        } else {
            $from_time = sumo_get_subscription_timestamp( 0, 0, true ); // returns the Current Date
        }
        if ( $from_when && is_numeric( $from_when ) ) {
            $from_time = $from_when; // get Custom - From time
        }

        switch ( self::$subscription->get_duration_period() ) {
            case 'W':
                //Eg: Renewal on every Week of Monday
                //Here self::$subscription->get_sync( 'duration_period' ) indicates 01,02...07 number for a Week
                $next_renewal_week      = self::$weekdays[ self::$subscription->get_sync( 'duration_period' ) ];
                $duration_period_length = self::$subscription->get_duration_period_length();

                if ( self::$subscription->get_sync( 'duration_period' ) == gmdate( 'N', $from_time ) ) {
                    $next_sync_time = $from_time + ( $duration_period_length * 7 * 86400 );
                } elseif ( $is_initial_payment && 'first-occurrence' === self::$subscription->get_sync( 'type' ) ) {
                    $next_sync_time = sumo_get_subscription_timestamp( "{$next_renewal_week}", $from_time );
                } else {
                    $next_sync_time = sumo_get_subscription_timestamp( "{$duration_period_length} {$next_renewal_week}", $from_time );
                }
                break;
            case 'M':
                //Eg: Renewal on every Month 01
                //Here self::$subscription->get_sync( 'duration_period' ) indicates 01,02...12 Months for a Year and self::$subscription->get_sync( 'duration_period_length' ) indicates 01,02...30/31 days for a Month
                $next_sync_time = self::calculate_sync_time_for( 'month', $from_time, $is_initial_payment );
                break;
            case 'Y':
                //Eg: Renewal on every Year January 01
                //Here self::$subscription->get_sync( 'duration_period' ) indicates 01,02...12 Months for a Year and self::$subscription->get_sync( 'duration_period_length' ) indicates Number of Years to Renew
                $next_sync_time = self::calculate_sync_time_for( 'year', $from_time, $is_initial_payment );
                break;
        }

        self::$sync_duration_extented = false;
        if ( $next_sync_time && $is_initial_payment && 'cutoff-time-to-not-renew-nxt-subs-cycle' === self::$subscription->get_sync( 'subscribed_after_sync_date_type' ) && self::$subscription->get_sync( 'cutoff_time_to_not_renew_nxt_subs_cycle' ) ) {
            if ( self::current_time_exceeds_xtra_time( self::$subscription->get_sync( 'cutoff_time_to_not_renew_nxt_subs_cycle' ), $next_sync_time ) ) {
                $next_sync_time               = self::get_next_sync_time( $is_initial_payment, $next_sync_time );
                self::$sync_duration_extented = true;
            }
        }

        return $next_sync_time;
    }

    public static function get_previous_sync_time( $from_when ) {
        if ( ! is_numeric( self::$subscription->get_sync( 'duration_period' ) ) && ! is_numeric( self::$subscription->get_duration_period_length() ) ) {
            return 0;
        }

        $previous_sync_time = 0;
        if ( $from_when && is_numeric( $from_when ) ) {
            $from_time = $from_when; // get Custom - From time
        } else {
            $from_time = sumo_get_subscription_timestamp( 0, 0, true ); // returns the Current Date
        }

        switch ( self::$subscription->get_duration_period() ) {
            case 'W':
                //Eg: Renewal on every Week of Monday
                //Here self::$subscription->get_sync( 'duration_period' ) indicates 01,02...07 number for a Week
                $next_renewal_week      = self::$weekdays[ self::$subscription->get_sync( 'duration_period' ) ];
                $duration_period_length = self::$subscription->get_duration_period_length();

                if ( self::$subscription->get_sync( 'duration_period' ) == gmdate( 'N', $from_time ) ) {
                    $previous_sync_time = $from_time - ( $duration_period_length * 7 * 86400 );
                } elseif ( 'first-occurrence' === self::$subscription->get_sync( 'type' ) ) {
                    $previous_sync_time = sumo_get_subscription_timestamp( "-1 {$next_renewal_week}", $from_time );
                } else {
                    $previous_sync_time = sumo_get_subscription_timestamp( "-{$duration_period_length} {$next_renewal_week}", $from_time );
                }
                break;
            case 'M':
                //Eg: Renewal on every Month 01
                //Here self::$subscription->get_sync( 'duration_period' ) indicates 01,02...12 Months for a Year and self::$subscription->get_sync( 'duration_period_length' ) indicates 01,02...30/31 days for a Month
                $previous_sync_time = self::calculate_previous_sync_time_for( 'month', $from_time );
                break;
            case 'Y':
                //Eg: Renewal on every Year January 01
                //Here self::$subscription->get_sync( 'duration_period' ) indicates 01,02...12 Months for a Year and self::$subscription->get_sync( 'duration_period_length' ) indicates Number of Years to Renew
                $previous_sync_time = self::calculate_previous_sync_time_for( 'year', $from_time );
                break;
        }

        return $previous_sync_time;
    }

    public static function get_line_total( $sync ) {
        $line_total = 0;

        switch ( $sync[ 'initial_payment_charge_type' ] ) {
            case 'full-payment':
                $line_total = floatval( self::$subscription->get_recurring_amount() );
                break;
            case 'prorate':
                if ( is_numeric( $sync[ 'prorated_subscription_fee' ] ) ) {
                    //When prorated fee is applied on First Payment itself then consider subscription fee as prorated amount or else prorated amount will be calculated in the 1st renewal order so that consider subscription fee as 0
                    if ( in_array( $sync[ 'apply_prorated_subscription_fee_on' ], array( 'first-payment', 'first_payment' ) ) ) {
                        $line_total = $sync[ 'prorated_subscription_fee' ];
                    }
                } elseif ( $sync[ 'signingup_on_sync_date' ] ) {
                    $line_total = floatval( self::$subscription->get_recurring_amount() );
                }
                break;
        }

        if ( $sync[ 'xtra_time_to_charge_full_fee' ] ) {
            if ( $sync[ 'awaiting_initial_payment' ] ) {
                $line_total = 0;
            } else {
                $line_total = floatval( self::$subscription->get_recurring_amount() );
            }
        }

        if ( self::is_sync_not_started() ) {
            $line_total = 0;
        }

        if ( 'product' === self::$subscription_obj_type && self::$subscription->get_signup( 'forced' ) ) {
            $line_total += floatval( self::$subscription->get_signup( 'fee' ) );
        }

        return $line_total;
    }

    public static function get_next_synced_payment_date_to_display( $variation_data = array(), $variation = null ) {
        global $product;

        //Check whether to display in Single Product Page or Not.
        if ( ! $product || ! self::$show_synced_date ) {
            return $variation_data;
        }

        $maybe_subscription = new SUMOSubs_Product( $variation ? $variation : $product );

        if ( sumosubs_is_subscription_product_type( $maybe_subscription->get_type() ) ) {
            switch ( $maybe_subscription->get_type() ) {
                case 'variation':
                    if ( ! empty( $variation_data[ 'sumosubs_plan_message' ] ) && self::is_subscription_synced( $maybe_subscription ) ) {
                        /* translators: 1: label 2: initial payment date */
                        $variation_data[ 'sumosubs_synced_next_payment_date' ] = sprintf( '<p id="sumosubs_initial_synced_payment_date">%s<strong>%s</strong></p>', esc_html__( 'Next Payment on: ', 'sumosubscriptions' ), esc_html( self::get_initial_payment_date( $maybe_subscription->get_id(), true ) ) );
                    }
                    break;
                case 'grouped':
                    foreach ( $maybe_subscription->product->get_children() as $child_id ) {
                        if ( self::is_subscription_synced( $child_id ) ) {
                            /* translators: 1: product title 2: label 3: initial payment date */
                            printf( '<p id="sumosubs_initial_synced_payment_date">%s -> %s<strong>%s</strong></p>', wp_kses_post( get_post( $child_id )->post_title ), esc_html__( 'Next Payment on: ', 'sumosubscriptions' ), esc_html( self::get_initial_payment_date( $child_id, true ) ) );
                        }
                    }
                    break;
                default:
                    if ( self::is_subscription_synced( $maybe_subscription ) ) {
                        /* translators: 1: label 2: initial payment date */
                        printf( '<p id="sumosubs_initial_synced_payment_date">%s<strong>%s</strong></p>', esc_html__( 'Next Payment on: ', 'sumosubscriptions' ), esc_html( self::get_initial_payment_date( $maybe_subscription->get_id(), true ) ) );
                    }
                    break;
            }
        }

        return $variation_data;
    }

    public static function add_to_cart_synced_item( $cart_item ) {
        if ( self::is_subscription_synced( $cart_item[ 'data' ] ) ) {
            $cart_item[ 'sumosubscriptions' ][ 'sync' ] = array(
                'enabled'                            => true,
                'initial_payment_charge_type'        => self::$payment_type,
                'prorated_subscription_fee'          => null,
                'apply_prorated_subscription_fee_on' => null,
                'signingup_on_sync_date'             => self::is_subscriber_signingup_on_sync_date(),
                'initial_payment_time'               => self::get_initial_payment_date( self::$subscription->get_id() ),
                'xtra_time_to_charge_full_fee'       => null,
                'awaiting_initial_payment'           => null,
                'can_prorate_due_date'               => self::can_prorate_due_date()
            );

            if ( 'xtra-time-to-charge-full-fee' === self::$subscription->get_sync( 'subscribed_after_sync_date_type' ) && self::$subscription->get_sync( 'xtra_time_to_charge_full_fee' ) ) {
                $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'xtra_time_to_charge_full_fee' ] = self::$subscription->get_sync( 'xtra_time_to_charge_full_fee' );
                $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'awaiting_initial_payment' ]     = self::awaiting_initial_payment( $cart_item[ 'sumosubscriptions' ][ 'sync' ] );
            }

            if ( self::can_prorate_subscription() ) {
                $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'prorated_subscription_fee' ]          = self::prorate_subscription_fee();
                $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'apply_prorated_subscription_fee_on' ] = self::$apply_prorated_fee_on;
            }

            $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'signup_amount' ] = self::get_line_total( $cart_item[ 'sumosubscriptions' ][ 'sync' ] );
        }

        return $cart_item;
    }

    public static function set_line_total( $line_total, $subscription, $default_line_total, $is_trial_enabled, $subscription_obj_type ) {
        if ( ! $is_trial_enabled ) {
            if ( 'product' === $subscription_obj_type && self::cart_item_contains_sync( $subscription->get_id() ) ) {
                $sync = self::get_synced( $subscription->get_id() );
            } else if ( 'subscription' === $subscription_obj_type && self::cart_item_contains_sync( $subscription->get_subscribed_product() ) ) {
                $sync = self::get_synced( $subscription->get_subscribed_product() );
            }

            if ( isset( $sync[ 'signup_amount' ] ) && is_numeric( $sync[ 'signup_amount' ] ) ) {
                return wc_format_decimal( $sync[ 'signup_amount' ], wc_get_price_decimals() );
            }
        }

        return $line_total;
    }

    public static function refresh_cart() {
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( isset( $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ] ) ) {
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ]                            = false;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'initial_payment_charge_type' ]        = null;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'prorated_subscription_fee' ]          = null;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'apply_prorated_subscription_fee_on' ] = null;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'signingup_on_sync_date' ]             = null;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'signup_amount' ]                      = null;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'initial_payment_time' ]               = null;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'xtra_time_to_charge_full_fee' ]       = null;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'awaiting_initial_payment' ]           = null;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'can_prorate_due_date' ]               = null;

                if ( self::is_subscription_synced( $cart_item[ 'data' ] ) ) {
                    if ( isset( WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'extra_duration_period' ] ) ) {//BKWD CMPT
                        WC()->cart->remove_cart_item( $cart_item_key );
                        continue;
                    }

                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ]                     = true;
                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'initial_payment_charge_type' ] = self::$payment_type;
                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'signingup_on_sync_date' ]      = self::is_subscriber_signingup_on_sync_date();
                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'initial_payment_time' ]        = self::get_initial_payment_date( self::$subscription->get_id() );
                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'can_prorate_due_date' ]        = self::can_prorate_due_date();

                    if ( 'xtra-time-to-charge-full-fee' === self::$subscription->get_sync( 'subscribed_after_sync_date_type' ) && self::$subscription->get_sync( 'xtra_time_to_charge_full_fee' ) ) {
                        WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'xtra_time_to_charge_full_fee' ] = self::$subscription->get_sync( 'xtra_time_to_charge_full_fee' );
                        WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'awaiting_initial_payment' ]     = self::awaiting_initial_payment( WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ] );
                    }

                    if ( self::can_prorate_subscription() ) {
                        WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'prorated_subscription_fee' ]          = self::prorate_subscription_fee();
                        WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'apply_prorated_subscription_fee_on' ] = self::$apply_prorated_fee_on;
                    }

                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'signup_amount' ] = self::get_line_total( WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ] );
                }
            }
        }
    }
}

SUMOSubs_Synchronization::init();

/**
 * For Backward Compatibility.
 */
class SUMO_Subscription_Synchronization extends SUMOSubs_Synchronization {
    
}
