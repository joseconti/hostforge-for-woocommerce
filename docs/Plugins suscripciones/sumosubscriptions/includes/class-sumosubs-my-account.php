<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle subscriptions in my account page.
 * 
 * @class SUMOSubs_My_Account
 */
class SUMOSubs_My_Account {

    /**
     * Init SUMOSubs_My_Account.
     */
    public static function init() {
        add_filter( 'woocommerce_account_menu_items', __CLASS__ . '::set_my_account_menu_items', 99 );

        add_action( 'woocommerce_account_sumo-subscriptions_endpoint', __CLASS__ . '::my_subscriptions' );
        add_shortcode( 'sumo_my_subscriptions', __CLASS__ . '::my_subscriptions', 10, 3 );

        add_action( 'woocommerce_account_view-subscription_endpoint', __CLASS__ . '::view_subscription' );
        add_action( 'sumosubscriptions_my_subscriptions_view-subscription_endpoint', __CLASS__ . '::view_subscription' );

        add_filter( 'user_has_cap', __CLASS__ . '::customer_has_capability', 10, 3 );
        add_filter( 'sumosubscriptions_my_subscription_table_pause_action', __CLASS__ . '::remove_pause_action', 10, 3 );
        add_filter( 'sumosubscriptions_my_subscription_table_cancel_action', __CLASS__ . '::remove_cancel_action', 10, 3 );
        add_action( 'wp_loaded', __CLASS__ . '::user_action', 20 );
        // Add pay now button
        add_action( 'woocommerce_my_sumo_subscriptions_actions', __CLASS__ . '::add_pay_now_button', 10, 1 );
        // Render change payment method form
        add_action( 'after_woocommerce_pay', __CLASS__ . '::render_change_payment_method_form', 99 );
        // Change the "Pay for Order" page title to "Change Payment Method"
        add_filter( 'the_title', __CLASS__ . '::change_payment_method_page_title', 100 );
        // Change the "Pay for Order" breadcrumb to "Change Payment Method"
        add_filter( 'woocommerce_get_breadcrumb', __CLASS__ . '::change_payment_method_breadcrumb', 10, 1 );
        // Process change payment method subscriber
        add_action( 'wp', __CLASS__ . '::process_change_payment_method', 20 );

        if ( isset( $_GET[ 'pay_for_order' ] ) ) {
            add_action( 'before_woocommerce_pay', __CLASS__ . '::wc_checkout_notice' );
        }
    }

    /**
     * Set our menus under My account menu items
     *
     * @param array $items
     * @return array
     */
    public static function set_my_account_menu_items( $items ) {
        $endpoint = sumosubscriptions()->query->get_query_var( 'sumo-subscriptions' );
        $menu     = array( $endpoint => apply_filters( 'sumosubscriptions_my_subscriptions_table_title', __( 'My Subscriptions', 'sumosubscriptions' ) ) );
        $position = 2;
        $items    = array_slice( $items, 0, $position ) + $menu + array_slice( $items, $position, count( $items ) - 1 );
        return $items;
    }

    /**
     * My Subscriptions template.
     */
    public static function my_subscriptions( $atts = '', $content = '', $tag = '' ) {
        if ( is_null( WC()->cart ) || is_admin() ) {
            return;
        }

        global $wp;
        if ( 'sumo_my_subscriptions' === $tag ) {
            if ( ! empty( $wp->query_vars ) ) {
                foreach ( $wp->query_vars as $key => $value ) {
                    // Ignore pagename param.
                    if ( 'pagename' === $key ) {
                        continue;
                    }

                    if ( has_action( 'sumosubscriptions_my_subscriptions_' . $key . '_endpoint' ) ) {
                        do_action( 'sumosubscriptions_my_subscriptions_' . $key . '_endpoint', $value );
                        return;
                    }
                }
            }
        }

        $endpoint = sumosubscriptions()->query->get_query_var( 'sumo-subscriptions' );
        if ( isset( $wp->query_vars[ $endpoint ] ) && ! empty( $wp->query_vars[ $endpoint ] ) ) {
            $current_page = absint( $wp->query_vars[ $endpoint ] );
        } else {
            $current_page = 1;
        }

        $query = new WP_Query( apply_filters( 'woocommerce_my_account_my_sumo_subscriptions_query', array(
                    'post_type'      => 'sumosubscriptions',
                    'post_status'    => 'publish',
                    'meta_key'       => 'sumo_get_user_id',
                    'meta_value'     => get_current_user_id(),
                    'fields'         => 'ids',
                    'paged'          => $current_page,
                    'posts_per_page' => 5,
                ) ) );

        $customer_subscriptions = ( object ) array(
                    'subscriptions' => $query->posts,
                    'max_num_pages' => $query->max_num_pages,
                    'total'         => $query->found_posts,
        );

        sumosubscriptions_get_template( 'subscriptions.php', array(
            'current_page'           => absint( $current_page ),
            'customer_subscriptions' => $customer_subscriptions,
            'subscriptions'          => $customer_subscriptions->subscriptions,
            'has_subscription'       => 0 < $customer_subscriptions->total,
            'endpoint'               => $endpoint,
        ) );
    }

    /**
     * My Subscriptions > View Subscription template.
     *
     * @param int $subscription_id
     */
    public static function view_subscription( $subscription_id ) {
        if ( ! current_user_can( 'view-subscription', $subscription_id ) ) {
            echo '<div class="woocommerce-error">' . esc_html__( 'Invalid subscription.', 'sumosubscriptions' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="wc-forward">' . esc_html__( 'My account', 'sumosubscriptions' ) . '</a></div>';
            return;
        }

        echo '<div class="sumo-view-subscription">';
        sumosubscriptions_get_template( 'view-subscription.php', array(
            'subscription_id'                                     => absint( $subscription_id ),
            'subscription'                                        => sumo_get_subscription( $subscription_id ),
            'allow_subscribers_to_pause'                          => SUMOSubs_Admin_Options::get_option( 'allow_subscribers_to_pause' ),
            'allow_subscribers_to_pause_synced'                   => SUMOSubs_Admin_Options::get_option( 'allow_subscribers_to_pause_synced' ),
            'allow_subscribers_to_cancel'                         => SUMOSubs_Admin_Options::get_option( 'allow_subscribers_to_cancel' ),
            'allow_subscribers_to_select_resume_date'             => SUMOSubs_Admin_Options::get_option( 'allow_subscribers_to_select_resume_date' ),
            'allow_subscribers_to_turnoff_auto_renewals'          => SUMOSubs_Admin_Options::get_option( 'allow_subscribers_to_turnoff_auto_renewals' ),
            'allow_subscribers_to_switch_bw_identical_variations' => SUMOSubs_Admin_Options::get_option( 'allow_subscribers_to_switch_bw_identical_variations' ),
            'allow_subscribers_to_change_shipping_address'        => SUMOSubs_Admin_Options::get_option( 'allow_subscribers_to_change_shipping_address' ),
            'allow_subscribers_to_update_subscription_qty'        => SUMOSubs_Admin_Options::get_option( 'allow_subscribers_to_update_subscription_qty' ),
            'show_subscription_activities'                        => SUMOSubs_Admin_Options::get_option( 'show_subscription_activities' ),
            'max_pause_duration_for_subscribers'                  => absint( SUMOSubs_Admin_Options::get_option( 'max_pause_duration_for_subscribers' ) ),
        ) );
        echo '</div>';
    }

    /**
     * Checks if a user has a certain capability.
     *
     * @param array $allcaps All capabilities.
     * @param array $caps    Capabilities.
     * @param array $args    Arguments.
     *
     * @return array The filtered array of all capabilities.
     */
    public static function customer_has_capability( $allcaps, $caps, $args ) {
        if ( isset( $caps[ 0 ] ) ) {
            switch ( $caps[ 0 ] ) {
                case 'view-subscription':
                    $user_id         = absint( $args[ 1 ] );
                    $subscription_id = absint( $args[ 2 ] );

                    if ( sumo_is_subscription_exists( $subscription_id ) && absint( get_post_meta( $subscription_id, 'sumo_get_user_id', true ) ) === $user_id ) {
                        $allcaps[ 'view-subscription' ] = true;
                    }
                    break;
            }
        }

        return $allcaps;
    }

    /**
     * Hide Pause action from my Subscriptions table
     *
     * @param bool $action
     * @param int $subscription_id
     * @param int $parent_order_id
     * @return bool
     */
    public static function remove_pause_action( $action, $subscription_id, $parent_order_id ) {
        if ( 'Pending_Cancellation' === get_post_meta( $subscription_id, 'sumo_get_status', true ) ) {
            return false;
        }

        return $action;
    }

    /**
     * Minimum waiting time for the User to get previlege to Cancel their Subscription.
     * Show Cancel button only when the User has got the previlege
     * 
     * @param bool $action
     * @param int $subscription_id
     * @param int $parent_order_id
     * @return bool
     */
    public static function remove_cancel_action( $action, $subscription_id, $parent_order_id ) {
        $min_days_user_wait_to_cancel = absint( SUMOSubs_Admin_Options::get_option( 'allow_subscribers_to_cancel_after_schedule' ) );
        if ( 0 === $min_days_user_wait_to_cancel ) {
            return $action;
        }

        $order      = wc_get_order( $parent_order_id );
        $order_date = $order ? $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) : '';

        if ( $min_days_user_wait_to_cancel > 0 && '' !== $order_date ) {
            $order_time                   = sumo_get_subscription_timestamp( $order_date );
            $min_time_user_wait_to_cancel = $order_time + ( $min_days_user_wait_to_cancel * 86400 );

            if ( sumo_get_subscription_timestamp() >= $min_time_user_wait_to_cancel ) {
                return $action;
            }
        }

        return false;
    }

    /**
     * Perform user action.
     */
    public static function user_action() {
        if ( ! isset( $_REQUEST[ 'wpnonce' ] ) ) {
            return;
        }

        if ( isset( $_REQUEST[ 'sumosubs-cancel-immediate' ] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST[ 'wpnonce' ] ) ), 'sumosubs-cancel-immediate-handler' ) ) {
            $subscription_id = absint( wp_unslash( $_REQUEST[ 'sumosubs-cancel-immediate' ] ) );

            //Cancel Subscription.
            if ( sumosubs_cancel_subscription( $subscription_id, array( 'request_by' => 'subscriber' ) ) ) {
                wc_add_notice( __( 'Subscription has been Cancelled successfully.', 'sumosubscriptions' ), 'success' );
            }

            wp_safe_redirect( sumo_get_subscription_endpoint_url( $subscription_id ) );
            exit;
        }
    }

    /**
     * Prevent the User placing Paused/Cancelled Subscription renewal order from Pay for Order page.
     */
    public static function wc_checkout_notice() {
        $renewal_order_id = sumosubs_get_subscription_renewal_order_in_pay_for_order();
        if ( ! $renewal_order_id ) {
            return;
        }

        $subscription_id = sumosubs_get_subscription_id_from_renewal_order( $renewal_order_id );
        switch ( get_post_meta( $subscription_id, 'sumo_get_status', true ) ) {
            case 'Pause':
                if ( 'yes' === SUMOSubs_Admin_Options::get_option( 'show_error_messages_in_pay_for_order_page' ) ) {
                    wc_add_notice( SUMOSubs_Admin_Options::get_option( 'renewal_order_payment_in_paused_error_message' ), 'error' );
                }
                echo '<style>#order_review {display: none;}</style>';
                break;
            case 'Pending_Cancellation':
                if ( 'yes' === SUMOSubs_Admin_Options::get_option( 'show_error_messages_in_pay_for_order_page' ) ) {
                    wc_add_notice( SUMOSubs_Admin_Options::get_option( 'renewal_order_payment_in_pending_cancel_error_message' ), 'error' );
                }
                echo '<style>#order_review {display: none;}</style>';
        }
    }

    /**
     * Add pay now button
     * 
     * @since 15.4.0
     * @param int $subscription_id Subscription ID
     */
    public static function add_pay_now_button( $subscription_id ) {
        $renewal_order_id = get_post_meta( $subscription_id, 'sumo_get_renewal_id', true );
        $renewal_order    = wc_get_order( $renewal_order_id );

        if ( $renewal_order && ! sumosubs_is_order_paid( $renewal_order ) ) {
            printf(
                    '<a class="woocommerce-button button pay" href="%s">%s</a>',
                    $renewal_order->get_checkout_payment_url(), __( 'Pay Now', 'sumosubscriptions' ) );
        }
    }

    /**
     * Validates the request to change a payment method.
     * 
     * @since 15.5.0   
     * @global array $wp
     * @return bool
     */
    public static function is_valid_add_or_change_payment_request() {
        global $wp;

        return isset( $_GET[ 'sumosubs_add_or_change_payment' ], $_GET[ '_sumosubs_nonce' ], $_GET[ 'key' ], $wp->query_vars[ 'order-pay' ] );
    }

    /**
     * Validates the request to change a subscription's payment method.
     * 
     * @since 15.5.0   
     * @global array $wp
     * @param int $subscription_id Subscription_id
     *      
     * @return bool
     */
    public static function is_valid_add_or_change_payment_request_for_subscription( $subscription_id ) {
        global $wp;
        if ( ! $subscription_id || ! sumo_is_subscription_exists( $subscription_id ) ) {
            return false;
        }

        if ( ! wp_verify_nonce( wc_clean( wp_unslash( $_GET[ '_sumosubs_nonce' ] ) ), $subscription_id ) ) {
            return false;
        }

        $order = wc_get_order( absint( $wp->query_vars[ 'order-pay' ] ) );
        if ( ! $order || ! hash_equals( $order->get_order_key(), wc_clean( wp_unslash( $_GET[ 'key' ] ) ) ) ) {
            return false;
        }

        return true;
    }

    /**
     * Render change payment method form in my account
     * 
     * @since 15.5.0
     * @global array $wp
     */
    public static function render_change_payment_method_form() {
        global $wp;
        if ( ! self::is_valid_add_or_change_payment_request() ) {
            return;
        }

        if ( ! is_main_query() || ! in_the_loop() || ! is_page() || ! is_checkout_pay_page() ) {
            return;
        }

        // Clear the output buffer.
        ob_clean();
        // Because we've cleared the buffer, we need to re-include the opening container div.
        echo '<div class="woocommerce">';

        /**
         * Before WC pay.
         * 
         * @since 15.5.0
         */
        do_action( 'before_woocommerce_pay' );

        try {
            $subscription_id = absint( wp_unslash( $_GET[ 'sumosubs_add_or_change_payment' ] ) );
            if ( ! self::is_valid_add_or_change_payment_request_for_subscription( $subscription_id ) ) {
                throw new Exception( __( 'Invalid request.', 'sumosubscriptions' ) );
            }

            $order          = wc_get_order( absint( $wp->query_vars[ 'order-pay' ] ) );
            $subscription   = sumo_get_subscription( $subscription_id );
            $valid_statuses = apply_filters( 'sumosubscriptions_add_or_change_payment_valid_statuses', array( 'Trial', 'Active', 'Pending', 'Pause', 'Overdue', 'Suspended', 'Pending_Authorization', 'Failed' ) );
            $status         = get_post_meta( $subscription_id, 'sumo_get_status', true );

            if ( ! in_array( $status, $valid_statuses ) ) {
                throw new Exception( __( 'Invalid request.', 'sumosubscriptions' ) );
            }

            $unpaid_renewal_order_exists = sumosubs_unpaid_renewal_order_exists( $subscription_id );
            $renewal_order_id            = get_post_meta( $subscription_id, 'sumo_get_renewal_id', true );
            $renewal_order               = $unpaid_renewal_order_exists ? wc_get_order( $renewal_order_id ) : false;

            if ( $order && in_array( $status, array( 'Overdue', 'Pending_Authorization', 'Failed', 'Suspended' ) ) ) {
                wp_safe_redirect( $renewal_order->get_checkout_payment_url() );
                exit;
            }

            wc_print_notice( __( 'Choose a new payment method.', 'sumosubscriptions' ), 'notice' );

            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            foreach ( $available_gateways as $gateway_name => $gateway ) {
                if ( ! $gateway->supports( 'sumosubs_add_or_change_payment' ) ) {
                    unset( $available_gateways[ $gateway_name ] );
                }
            }

            if ( 'auto' === sumo_get_payment_type( $subscription_id ) ) {
                $payment_button_text = apply_filters( 'sumosubscriptions_change_payment_button_text', __( 'Change payment method', 'sumosubscriptions' ) );
            } else {
                $payment_button_text = apply_filters( 'sumosubscriptions_add_payment_button_text', __( 'Add payment method', 'sumosubscriptions' ) );
            }

            sumosubscriptions_get_template( 'form-add-or-change-payment-method.php', array(
                'order'               => $order,
                'subscription_id'     => $subscription_id,
                'subscription'        => $subscription,
                'payment_button_text' => $payment_button_text,
                'available_gateways'  => $available_gateways
            ) );
        } catch ( Exception $e ) {
            wc_print_notice( $e->getMessage(), 'error' );
        }
    }

    /**
     * Change payment method page title
     *
     * @since 15.5.0    
     * @param string $title    
     * 
     * @return string
     */
    public static function change_payment_method_page_title( $title ) {
        if ( ! self::is_valid_add_or_change_payment_request() ) {
            return $title;
        }

        if ( ! is_main_query() || ! in_the_loop() || ! is_page() || ! is_checkout_pay_page() ) {
            return $title;
        }

        $subscription_id = absint( wp_unslash( $_GET[ 'sumosubs_add_or_change_payment' ] ) );
        if ( ! self::is_valid_add_or_change_payment_request_for_subscription( $subscription_id ) ) {
            return $title;
        }

        if ( 'auto' === sumo_get_payment_type( $subscription_id ) ) {
            $title = __( 'Change payment method', 'sumosubscriptions' );
        } else {
            $title = __( 'Add payment method', 'sumosubscriptions' );
        }

        return $title;
    }

    /**
     * Change Payment Method Breadcrumb
     *
     * @since 15.5.0
     * @param  string $crumbs
     * 
     * @return string     
     */
    public static function change_payment_method_breadcrumb( $crumbs ) {
        if ( ! self::is_valid_add_or_change_payment_request() ) {
            return $crumbs;
        }

        if ( ! is_main_query() || ! is_page() || ! is_checkout_pay_page() ) {
            return $crumbs;
        }

        $subscription_id = absint( wp_unslash( $_GET[ 'sumosubs_add_or_change_payment' ] ) );
        if ( ! self::is_valid_add_or_change_payment_request_for_subscription( $subscription_id ) ) {
            return $crumbs;
        }

        $crumbs[ 1 ] = array(
            get_the_title( wc_get_page_id( 'myaccount' ) ),
            get_permalink( wc_get_page_id( 'myaccount' ) ),
        );

        $crumbs[ 2 ] = array(
            // translators: %s: order number.
            sprintf( __( 'Subscription #%s', 'sumosubscriptions' ), $subscription_id ),
            esc_url( sumo_get_subscription_endpoint_url( $subscription_id ) ),
        );

        if ( 'auto' === sumo_get_payment_type( $subscription_id ) ) {
            $crumbs[ 3 ] = array(
                __( 'Change payment method', 'sumosubscriptions' ),
                '',
            );
        } else {
            $crumbs[ 3 ] = array(
                __( 'Add payment method', 'sumosubscriptions' ),
                '',
            );
        }

        return $crumbs;
    }

    /**
     * Process the change payment method.
     * @since 15.5.0
     * @global array $wp Array
     * 
     * @return void
     */
    public static function process_change_payment_method() {
        global $wp;
        if ( ! self::is_valid_add_or_change_payment_request() ) {
            return;
        }

        $subscription_id = absint( wp_unslash( $_GET[ 'sumosubs_add_or_change_payment' ] ) );
        if ( ! self::is_valid_add_or_change_payment_request_for_subscription( $subscription_id ) ) {
            return;
        }

        // Update renewal order payment method                
        $order = wc_get_order( absint( $wp->query_vars[ 'order-pay' ] ) );
        $subscription = sumo_get_subscription( $subscription_id );
        wc_nocache_headers();
        ob_start();

        WC()->customer->set_props(
                array(
                    'billing_country'  => $order->get_billing_country() ? $order->get_billing_country() : null,
                    'billing_state'    => $order->get_billing_state() ? $order->get_billing_state() : null,
                    'billing_postcode' => $order->get_billing_postcode() ? $order->get_billing_postcode() : null,
                    'billing_city'     => $order->get_billing_city() ? $order->get_billing_city() : null,
                )
        );

        try {
            $new_payment_method_id = isset( $_POST[ 'payment_method' ] ) ? wc_clean( wp_unslash( $_POST[ 'payment_method' ] ) ) : false;
            if ( ! $new_payment_method_id ) {
                throw new Exception( __( 'Invalid payment method.', 'sumosubscriptions' ) );
            }

            //Available payment gateways
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

            $new_payment_method = isset( $available_gateways[ $new_payment_method_id ] ) ? $available_gateways[ $new_payment_method_id ] : false;
            if ( ! $new_payment_method ) {
                throw new Exception( __( 'Invalid payment method.', 'sumosubscriptions' ) );
            }

            $new_payment_method->validate_fields();

            // Process payment for the new method
            if ( 0 === wc_notice_count( 'error' ) ) {
                $notice = 'auto' !== sumo_get_payment_type( $subscription_id ) ? __( 'Payment method added.', 'sumosubscriptions' ) : __( 'Payment method updated.', 'sumosubscriptions' );
                $result = apply_filters( "sumosubscriptions_process_new_payment_method_via_{$new_payment_method_id}", array(), $order, $subscription_id );
                $result = wp_parse_args( $result, array(
                    'result'   => '',
                    'redirect' => sumo_get_subscription_endpoint_url( $subscription_id ),
                        ) );

                if ( 'success' !== $result[ 'result' ] ) {
                    if ( 'auto' !== sumo_get_payment_type( $subscription_id ) ) {
                        throw new Exception( __( 'Unable to add payment method.', 'sumosubscriptions' ) );
                    } else {
                        throw new Exception( __( 'Unable to update payment method.', 'sumosubscriptions' ) );
                    }
                }

                if ( 'success' === $result[ 'result' ] ) {
                    if ( isset( $_POST[ 'update_payment_method_for_all_valid_subscriptions' ] ) ) {
                        $subscriber_valid_status = apply_filters( 'sumosubscriptions_add_or_change_updated_payment_valid_statuses', array( 'Trial', 'Active', 'Pending', 'Pause', 'Overdue', 'Suspended', 'Pending_Cancellation', 'Pending_Authorization', 'Failed' ) );
                        $subscription_ids        = sumosubs_get_subscriptions_by_user( $order->get_customer_id(), $subscriber_valid_status );

                        if ( ! is_array( $subscription_ids ) ) {
                            throw new Exception( __( 'No valid subscriptions found.', 'sumosubscriptions' ) );
                        }

                        $payment_info = sumo_get_subscription_payment( $subscription_id );

                        foreach ( $subscription_ids as $new_subscription_id ) {
                            $subs_order = wc_get_order( get_post_meta( $new_subscription_id, 'sumo_get_parent_order_id', true ) );
                            if ( $subs_order ) {
                                sumo_save_subscription_payment_info( $subs_order, $payment_info );
                                self::maybe_schedule_next_payment_process( $new_subscription_id );
                                $new_subscription = sumo_get_subscription( $new_subscription_id );
                                /* translators: %s: payment method */
                                sumo_add_subscription_note( sprintf( __( 'Payment Method Updated. Future renewals will be charged automatically %s', 'sumosubscriptions' ), $new_subscription->get_payment_method_to_display( 'customer' ) ), $new_subscription_id, sumo_note_status( get_post_meta( $new_subscription_id, 'sumo_get_status', true ) ), __( 'Payment Method Updated', 'sumosubscriptions' ) );
                                $subs_order->set_payment_method( $new_payment_method );
                                $subs_order->save();
                            }
                        }

                        $notice = __( 'Payment method has been updated successfully for all of your valid subscriptions.', 'sumosubscriptions' );
                    } else {
                        self::maybe_schedule_next_payment_process( $subscription_id );
                        /* translators: %s: payment method */
                        sumo_add_subscription_note( sprintf( __( 'Payment Method Updated. Future renewals will be charged automatically %s', 'sumosubscriptions' ), $subscription->get_payment_method_to_display( 'customer' ) ), $subscription_id, sumo_note_status( get_post_meta( $subscription_id, 'sumo_get_status', true ) ), __( 'Payment Method Updated', 'sumosubscriptions' ) );
                    }
                }

                // Redirect to success
                wc_add_notice( $notice );
                wp_safe_redirect( $result[ 'redirect' ] );
                exit;
            }
        } catch ( Exception $e ) {
            wc_add_notice( $e->getMessage(), 'error' );
        }

        ob_get_clean();
    }

    /**
     * Maybe schedule next payment process.
     * 
     * @since 15.5.0
     * @param int $subscription_id Subscription ID
     */
    private static function maybe_schedule_next_payment_process( $subscription_id ) {
        $unpaid_renewal_order_exists = sumosubs_unpaid_renewal_order_exists( $subscription_id );
        if ( $unpaid_renewal_order_exists ) {
            $renewal_order_id = absint( get_post_meta( $subscription_id, 'sumo_get_renewal_id', true ) );
            $cron_event       = new SUMOSubs_Cron_Event( $subscription_id );
            $cron_event->unset_events();
            $next_due_on      = get_post_meta( $subscription_id, 'sumo_get_next_payment_date', true );

            if ( 'auto' === sumo_get_payment_type( $subscription_id ) ) {
                $cron_event->schedule_automatic_pay( $renewal_order_id );
                $cron_event->schedule_reminders( $renewal_order_id, $next_due_on, '', 'subscription_auto_renewal_reminder' );
            } else {
                $cron_event->schedule_next_eligible_payment_failed_status( 0, 0, $next_due_on );
                $cron_event->schedule_reminders( $renewal_order_id, $next_due_on );
            }
        }
    }

}

SUMOSubs_My_Account::init();
