<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Subscription Admin Options.
 * 
 * @class SUMOSubs_Admin_Options
 */
class SUMOSubs_Admin_Options {

    /**
     * Prefix to our option name.
     * 
     * @var string 
     */
    protected static $prefix = 'sumosubs_';

    /**
     * Array of cached option => value
     * 
     * @var array 
     */
    protected static $options = array();

    /**
     * Prepare the option name.
     * 
     * @param string $key
     * @return string
     */
    public static function prepare_option_name( $key ) {
        return self::$prefix . $key;
    }

    /**
     * Trim our option name without prefix.
     * 
     * @param string $name
     * @return string
     */
    public static function maybe_trim_option_name( $name ) {
        return substr( $name, 0, 9 ) === self::$prefix ? substr( $name, 9 ) : $name;
    }

    /**
     * Check whether the option key or name exists?
     * 
     * @param string $key_or_name
     * @return bool
     */
    public static function option_exists( $key_or_name ) {
        $option_key = self::maybe_trim_option_name( $key_or_name );
        $default    = self::get_option_default( $option_key );
        return false !== $default;
    }

    /**
     * Get default value of the option.
     * 
     * @param string $option_key
     * @return mixed
     */
    public static function get_option_default( $option_key ) {
        $default = array(
            // General
            'subscribe_text'                                                  => __( 'Sign up Now', 'sumosubscriptions' ),
            // Renewals 
            'renewal_order_creation_schedule'                                 => '1',
            'charge_shipping_during_renewals'                                 => 'yes',
            'charge_tax_during_renewals'                                      => 'yes',
            // User Role
            'active_user_role'                                                => 'subscriber',
            'inactive_user_role'                                              => 'customer',
            // Notification schedules
            'manual_renewal_due_reminder_schedule'                            => '3,2,1',
            'auto_renewal_due_reminder_schedule'                              => '3,2,1',
            'overdue_reminder_schedule'                                       => '1,2,3',
            'suspend_reminder_schedule'                                       => '1,2,3',
            'expiration_reminder_schedule'                                    => '3,2,1',
            // Grace schedules
            'overdue_schedule'                                                => '5',
            'suspend_schedule'                                                => '5',
            // Restrictions
            'allow_mixed_checkout'                                            => 'yes',
            'restrict_outofstock_product'                                     => 'no',
            'restrict_multiple_subscriptions_in_checkout'                     => 'no',
            'limit_subscription_by'                                           => 'no-limit',
            'limit_trial_by'                                                  => 'no-limit',
            'limit_variable_subscription_at'                                  => 'variant',
            // Payments
            'accept_manual_payments'                                          => 'no',
            'disable_auto_payments'                                           => 'no',
            'subscription_payment_gateway_mode'                               => 'force-auto',
            'payment_retries_in_overdue'                                      => '2',
            'payment_retries_in_suspend'                                      => '2',
            'show_subscription_payment_gateways_when_order_amt_0'             => 'yes',
            // Advanced
            'update_old_subscription_price_to'                                => 'old-price',
            'activate_subscription'                                           => 'auto',
            'activate_free_trial'                                             => 'auto',
            'subscription_number_prefix'                                      => '',
            'subscription_as_regular_product_defined_rules'                   => array(),
            // Display
            'variable_product_price_display_as'                               => 'subscription-message',
            'show_timezone_in_frontend'                                       => 'yes',
            'show_timezone_in_frontend_as'                                    => 'default',
            'show_time_in_frontend'                                           => 'no',
            'inline_style'                                                    => '',
            // Emails
            'disabled_wc_order_emails'                                        => array(),
            'new_subscription_order_email_for_old_subscribers'                => 'default',
            // Troubleshoot
            'variation_data_template'                                         => 'from-woocommerce',
            'order_subscription_load_ajax_synchronously'                      => 'no',
            // Pause
            'allow_subscribers_to_pause'                                      => 'no',
            'allow_subscribers_to_pause_synced'                               => 'no',
            'allow_subscribers_to_select_resume_date'                         => 'no',
            'max_pause_times_for_subscribers'                                 => '0',
            'max_pause_duration_for_subscribers'                              => '10',
            'user_wide_pause_for'                                             => 'all-users',
            'user_ids_for_pause'                                              => array(),
            'user_roles_for_pause'                                            => array(),
            // Cancel
            'allow_subscribers_to_cancel'                                     => 'no',
            'allow_subscribers_to_cancel_after_schedule'                      => '0',
            'cancel_options_for_subscriber'                                   => array( 'immediate' ),
            'product_wide_cancellation_for'                                   => 'all-products',
            'product_ids_for_cancel'                                          => array(),
            'product_cat_ids_for_cancel'                                      => array(),
            'user_wide_cancellation_for'                                      => 'all-users',
            'user_ids_for_cancel'                                             => array(),
            'user_roles_for_cancel'                                           => array(),
            // Miscellaneous
            'allow_subscribers_to_switch_bw_identical_variations'             => 'no',
            'allow_subscribers_to_update_subscription_qty'                    => 'no',
            'allow_subscribers_to_resubscribe'                                => 'no',
            'allow_subscribers_to_turnoff_auto_renewals'                      => 'no',
            'allow_subscribers_to_change_shipping_address'                    => 'no',
            'drip_downloadable_content'                                       => 'no',
            'enable_additional_digital_downloads'                             => 'no',
            'show_subscription_activities'                                    => 'yes',
            'hide_resubscribe_to_subscribers_when'                            => array(),
            // Endpoints
            'my_account_subscriptions_endpoint'                               => 'sumo-subscriptions',
            'my_account_view_subscription_endpoint'                           => 'view-subscription',
            // Order Subscription
            'order_subs_allow_in_checkout'                                    => 'no',
            'order_subs_allow_in_cart'                                        => 'no',
            'order_subs_subscribed_default'                                   => 'yes',
            'order_subs_subscribe_values'                                     => 'userdefined',
            'order_subs_predefined_subscription_period'                       => 'M',
            'order_subs_predefined_subscription_period_interval'              => '1',
            'order_subs_predefined_subscription_length'                       => '0',
            'order_subs_userdefined_subscription_periods'                     => array( 'D', 'W', 'M', 'Y' ),
            'order_subs_userdefined_min_subscription_period_intervals'        => array( 'D' => '1', 'W' => '1', 'M' => '1', 'Y' => '1' ),
            'order_subs_userdefined_max_subscription_period_intervals'        => array( 'D' => '90', 'W' => '52', 'M' => '24', 'Y' => '10' ),
            'order_subs_userdefined_allow_indefinite_subscription_length'     => 'yes',
            'order_subs_userdefined_min_subscription_length'                  => '1',
            'order_subs_userdefined_max_subscription_length'                  => '0',
            'order_subs_charge_signupfee'                                     => 'no',
            'order_subs_signupfee'                                            => '',
            'order_subs_product_wide_selection'                               => 'all-products',
            'order_subs_allowed_product_ids'                                  => array(),
            'order_subs_restricted_product_ids'                               => array(),
            'order_subs_allowed_product_cat_ids'                              => array(),
            'order_subs_restricted_product_cat_ids'                           => array(),
            'order_subs_min_order_total'                                      => '',
            'order_subs_min_order_total_consideration'                        => 'total',
            'order_subs_cart_position'                                        => 'before_cart_totals',
            'order_subs_checkout_position'                                    => 'checkout_order_review',
            'order_subs_subscribe_label'                                      => __( 'Enable Order Subscription', 'sumosubscriptions' ),
            'order_subs_subscription_duration_label'                          => __( 'Renewal frequency', 'sumosubscriptions' ),
            'order_subs_subscription_length_label'                            => __( 'Number of installments', 'sumosubscriptions' ),
            'order_subs_inline_style'                                         => '',
            // Switcher
            'allow_switcher'                                                  => 'no',
            'switch_based_on'                                                 => 'price',
            'allow_user_to_switch'                                            => array( 'up-grade', 'down-grade', 'cross-grade' ),
            'allow_switch_between'                                            => array(),
            'payment_mode_when_switch'                                        => 'prorate',
            'when_switch_prorate_recurring_payment_for'                       => array(),
            'when_switch_charge_signup_fee'                                   => 'no',
            'when_switch_prorate_subscription_length'                         => 'no',
            'switch_button_text'                                              => __( 'Upgrade/Downgrade', 'sumosubscriptions' ),
            // Synchronization
            'allow_synchronization'                                           => 'no',
            'sync_based_on'                                                   => 'exact-date-r-day',
            'when_sync_show_next_due_date_in_product_page'                    => 'yes',
            'payment_mode_when_sync'                                          => 'free',
            'when_sync_prorate_payment_for'                                   => 'all-subscriptions',
            'when_sync_prorate_payment_during'                                => 'first-payment',
            // Messages
            'signup_fee_strings'                                              => __( '<b>[sumo_initial_fee]</b> for now and ', 'sumosubscriptions' ),
            'free_trial_strings'                                              => __( '<b>Free Trial</b> ', 'sumosubscriptions' ),
            'trial_fee_and_duration_strings'                                  => __( '<b>[sumo_trial_fee]</b> for the first <b>[sumo_trial_period_value]</b> [sumo_trial_period] Then ', 'sumosubscriptions' ),
            'subscription_price_and_duration_strings'                         => __( '<b>[sumo_subscription_fee]</b> every <b>[sumo_subscription_period_value]</b> [sumo_subscription_period] ', 'sumosubscriptions' ),
            'subscription_length_strings'                                     => __( 'for <b>[sumo_instalment_period_value]</b> [sumo_instalment_period] ', 'sumosubscriptions' ),
            'variable_product_price_strings'                                  => __( 'Subscription Starts from ', 'sumosubscriptions' ),
            'optional_free_trial_strings'                                     => __( 'Include Free Trial', 'sumosubscriptions' ),
            'optional_paid_trial_strings'                                     => __( 'Purchase with Trial Fee [sumo_trial_fee]', 'sumosubscriptions' ),
            'optional_signup_fee_strings'                                     => __( 'Include Sign Up with Fee [sumo_signup_fee_only]', 'sumosubscriptions' ),
            'discounted_renewal_amount_strings'                               => __( '<br>Renewal Fee After Discount: <strong>[renewal_fee_after_discount]</strong> ', 'sumosubscriptions' ),
            'synced_plan_strings'                                             => '',
            'synced_prorated_amount_strings'                                  => __( '<b>[sumo_prorated_fee]</b> for Prorating till <b>[sumo_synchronized_prorated_date]</b> and ', 'sumosubscriptions' ),
            'synced_prorated_amount_during_first_renewal_strings'             => __( 'Prorated till <b>[sumo_synchronized_prorated_date]</b> and amount will be charged on <b>[sumo_synchronized_next_payment_date]</b> and ', 'sumosubscriptions' ),
            'period_day_r_days_label'                                         => __( 'day,days', 'sumosubscriptions' ),
            'period_week_r_weeks_label'                                       => __( 'week,weeks', 'sumosubscriptions' ),
            'period_month_r_months_label'                                     => __( 'month,months', 'sumosubscriptions' ),
            'period_year_r_years_label'                                       => __( 'year,years', 'sumosubscriptions' ),
            'period_length_r_lengths_label'                                   => __( 'installment,installments', 'sumosubscriptions' ),
            'show_error_messages_in_product_page'                             => 'yes',
            'product_wide_subscription_limit_error_message'                   => __( 'You cannot purchase this subscription product again, as you have already purchased this subscription product', 'sumosubscriptions' ),
            'site_wide_subscription_limit_error_message'                      => __( 'You cannot purchase the subscription product again, as you have already purchased one subscription product', 'sumosubscriptions' ),
            'cannot_purchase_regular_with_subscription_product_error_message' => __( 'You cannot purchase subscription and non-subscription products in a single checkout', 'sumosubscriptions' ),
            'cannot_purchase_multiple_subscriptions_error_message'            => __( 'You cannot purchase multiple subscriptions in a single checkout', 'sumosubscriptions' ),
            'cannot_purchase_subscription_with_regular_product_error_message' => __( 'You cannot purchase subscription product along with non-subscription product', 'sumosubscriptions' ),
            'show_error_messages_in_cart_page'                                => 'yes',
            'product_wide_trial_limit_error_message'                          => __( 'You cannot purchase the trial period on the product(s) [product_name(s)] again, as you have already purchased this trial period. You have to pay the full subscription price', 'sumosubscriptions' ),
            'site_wide_trial_limit_error_message'                             => __( 'You cannot purchase the trial period again. As you have already purchased one trial period. You have to pay the full subscription price', 'sumosubscriptions' ),
            'show_error_messages_in_pay_for_order_page'                       => 'yes',
            'renewal_order_payment_in_paused_error_message'                   => __( 'This subscription status is currently Paused --it cannot be paid for right now. Please contact us if you need assistance.', 'sumosubscriptions' ),
            'renewal_order_payment_in_pending_cancel_error_message'           => __( 'This subscription status is currently in Pending for Cancellation --it cannot be paid for right now. Please contact us if you need assistance.', 'sumosubscriptions' ),
        );

        return isset( $default[ $option_key ] ) ? $default[ $option_key ] : false;
    }

    /**
     * Get the option.
     * 
     * @param string $key_or_name
     * @return mixed
     */
    public static function get_option( $key_or_name ) {
        $option_key = self::maybe_trim_option_name( $key_or_name );

        if ( isset( self::$options[ $option_key ] ) ) {
            return self::$options[ $option_key ];
        }

        self::$options[ $option_key ] = get_option( self::prepare_option_name( $option_key ), self::get_option_default( $option_key ) );
        return self::$options[ $option_key ];
    }

    /**
     * Add the option.
     * 
     * @param string $key_or_name
     * @param mixed $value
     */
    public static function add_option( $key_or_name, $value = false ) {
        $option_key    = self::maybe_trim_option_name( $key_or_name );
        $current_value = false;

        switch ( $option_key ) {
            /**
             * General
             */
            case 'subscribe_text':
                $current_value = get_option( 'sumo_add_to_cart_text' );
                break;
            /**
             * Renewals
             */
            case 'renewal_order_creation_schedule':
                $current_value = get_option( 'sumo_create_renewal_order_on' );
                break;
            case 'charge_shipping_during_renewals':
                $current_value = get_option( 'sumo_shipping_option' );
                break;
            case 'charge_tax_during_renewals':
                $current_value = get_option( 'sumo_tax_option' );
                break;
            /**
             * Notification schedules
             */
            case 'manual_renewal_due_reminder_schedule':
                $current_value = get_option( 'sumo_remaind_notification_email' );
                break;
            case 'auto_renewal_due_reminder_schedule':
                $current_value = get_option( 'sumo_remaind_notification_email_for_automatic' );
                break;
            case 'expiration_reminder_schedule':
                $current_value = get_option( 'sumo_expiry_reminder_email' );
                break;
            /**
             * Grace schedules
             */
            case 'overdue_schedule':
                $current_value = get_option( 'sumo_settings_overdue_notification_email' );
                break;
            case 'suspend_schedule':
                $current_value = get_option( 'sumo_suspend_notification_email' );
                break;
            /**
             * Restrictions
             */
            case 'allow_mixed_checkout':
                $current_value = get_option( 'sumosubscription_apply_mixed_checkout' );
                break;
            case 'limit_subscription_by':
                $current_value = get_option( 'sumo_limit_subscription_quantity' );

                // map deprecated current option value to new value.
                if ( '2' === $current_value ) {
                    $current_value = 'product-wide';
                } else if ( '3' === $current_value ) {
                    $current_value = 'site-wide';
                }
                break;
            case 'limit_trial_by':
                $current_value = get_option( 'sumo_trial_handling' );

                // map deprecated current option value to new value.
                if ( '2' === $current_value ) {
                    $current_value = 'product-wide';
                } else if ( '3' === $current_value ) {
                    $current_value = 'site-wide';
                }
                break;
            case 'limit_variable_subscription_at':
                // map deprecated current option value to new value.
                if ( '2' === get_option( 'sumo_limit_variable_product_level' ) ) {
                    $current_value = 'variable';
                }
                break;
            /**
             * Payments
             */
            case 'accept_manual_payments':
                $current_value = get_option( 'sumosubs_accept_manual_payment_gateways' );
                break;
            case 'disable_auto_payments':
                $current_value = get_option( 'sumosubs_disable_auto_payment_gateways' );
                break;
            case 'subscription_payment_gateway_mode':
                // map deprecated current option value to new value.
                if ( 'no' === get_option( 'sumo_paypal_payment_option' ) ) {
                    $current_value = 'auto-r-manual';
                } else if ( false !== get_option( 'sumo_force_auto_manual_paypal_adaptive' ) ) {
                    if ( '2' === get_option( 'sumo_force_auto_manual_paypal_adaptive' ) ) {
                        $current_value = 'force-auto';
                    } else {
                        $current_value = 'force-manual';
                    }
                }
                break;
            case 'payment_retries_in_overdue':
                $current_value = get_option( 'sumo_auto_payment_in_overdue' );
                break;
            case 'payment_retries_in_suspend':
                $current_value = get_option( 'sumo_auto_payment_in_suspend' );
                break;
            case 'show_subscription_payment_gateways_when_order_amt_0':
                $current_value = get_option( 'sumosubscription_show_payment_gateways_when_order_amt_zero' );
                break;
            /**
             * Advanced
             */
            case 'update_old_subscription_price_to':
                // map deprecated current option value to new value.
                if ( 'current_fee' === get_option( 'sumosubs_apply_subscription_fee_by' ) ) {
                    $current_value = 'new-price';
                }
                break;
            case 'activate_subscription':
                // map deprecated current option value to new value.
                if ( 'admin_approval' === get_option( 'sumosubs_activate_subscription_by' ) ) {
                    $current_value = 'after-admin-approval';
                }
                break;
            case 'activate_free_trial':
                // map deprecated current option value to new value.
                if ( 'admin_approval' === get_option( 'sumosubs_activate_free_trial_by' ) ) {
                    $current_value = 'after-admin-approval';
                }
                break;
            case 'subscription_number_prefix':
                $current_value = get_option( 'sumo_subscription_number_custom_prefix' );
                break;
            case 'subscription_as_regular_product_defined_rules':
                $current_value = get_option( 'sumo_subscription_as_regular_product_defined_rules' );
                break;
            /**
             * Display
             */
            case 'variable_product_price_display_as':
                $current_value = get_option( 'sumosubs_apply_variable_product_price_msg_based_on' );
                break;
            case 'show_timezone_in_frontend':
                $current_value = get_option( 'sumo_show_subscription_timezone' );
                break;
            case 'show_timezone_in_frontend_as':
                $current_value = get_option( 'sumo_set_subscription_timezone_as' );
                break;
            case 'show_time_in_frontend':
                // map deprecated current option value to new value.
                if ( 'enable' === get_option( 'sumosubs_show_time_in_frontend' ) ) {
                    $current_value = 'yes';
                }
                break;
            case 'inline_style':
                $current_value = get_option( 'sumo_subsc_custom_css' );
                break;
            /**
             * Emails
             */
            case 'new_subscription_order_email_for_old_subscribers':
                $current_value = get_option( 'sumosubs_new_subscription_order_template_for_old_subscribers' );
                break;
            case 'order_subscription_load_ajax_synchronously':
                $current_value = get_option( 'sumo_sync_ajax_for_order_subscription' );
                break;
            /**
             * Pause
             */
            case 'allow_subscribers_to_pause':
                $current_value = get_option( 'sumo_pause_resume_option' );
                break;
            case 'allow_subscribers_to_pause_synced':
                $current_value = get_option( 'sumo_sync_pause_resume_option' );
                break;
            case 'allow_subscribers_to_select_resume_date':
                $current_value = get_option( 'sumo_allow_user_to_select_resume_date' );
                break;
            case 'max_pause_times_for_subscribers':
                $current_value = get_option( 'sumo_settings_max_no_of_pause' );
                break;
            case 'max_pause_duration_for_subscribers':
                $current_value = get_option( 'sumo_settings_max_duration_of_pause' );
                break;
            case 'user_wide_pause_for':
                $current_value = get_option( 'sumo_subscription_pause_by_user_or_userrole_filter' );

                // map deprecated current option value to new value.
                if ( 'included_users' === $current_value ) {
                    $current_value = 'allowed-user-ids';
                } else if ( 'excluded_users' === $current_value ) {
                    $current_value = 'restricted-user-ids';
                } else if ( 'included_user_role' === $current_value ) {
                    $current_value = 'allowed-user-roles';
                } else if ( 'excluded_user_role' === $current_value ) {
                    $current_value = 'restricted-user-roles';
                } else if ( 'all_users' === $current_value ) {
                    $current_value = 'all-users';
                }
                break;
            case 'user_ids_for_pause':
                $current_value = get_option( 'sumo_subscription_pause_by_user_filter' );
                break;
            case 'user_roles_for_pause':
                $current_value = get_option( 'sumo_subscription_pause_by_userrole_filter' );
                break;
            /**
             * Cancel
             */
            case 'allow_subscribers_to_cancel':
                $current_value = get_option( 'sumo_cancel_option' );
                break;
            case 'allow_subscribers_to_cancel_after_schedule':
                $current_value = get_option( 'sumo_min_days_user_wait_to_cancel_their_subscription' );
                break;
            case 'cancel_options_for_subscriber':
                $current_value = get_option( 'sumo_subscription_cancel_methods_available_to_subscriber' );
                break;
            case 'product_wide_cancellation_for':
                $current_value = get_option( 'sumo_subscription_cancel_by_product_or_category_filter' );

                // map deprecated current option value to new value.
                if ( 'included_products' === $current_value ) {
                    $current_value = 'allowed-product-ids';
                } else if ( 'excluded_products' === $current_value ) {
                    $current_value = 'restricted-product-ids';
                } else if ( 'included_categories' === $current_value ) {
                    $current_value = 'allowed-product-cat-ids';
                } else if ( 'excluded_categories' === $current_value ) {
                    $current_value = 'restricted-product-cat-ids';
                } else if ( 'all_categories' === $current_value || 'all_products' === $current_value ) {
                    $current_value = 'all-products';
                }
                break;
            case 'product_ids_for_cancel':
                $current_value = get_option( 'sumo_subscription_cancel_by_product_filter' );
                break;
            case 'product_cat_ids_for_cancel':
                $current_value = get_option( 'sumo_subscription_cancel_by_category_filter' );
                break;
            case 'user_wide_cancellation_for':
                $current_value = get_option( 'sumo_subscription_cancel_by_user_or_userrole_filter' );

                // map deprecated current option value to new value.
                if ( 'included_users' === $current_value ) {
                    $current_value = 'allowed-user-ids';
                } else if ( 'excluded_users' === $current_value ) {
                    $current_value = 'restricted-user-ids';
                } else if ( 'included_user_role' === $current_value ) {
                    $current_value = 'allowed-user-roles';
                } else if ( 'excluded_user_role' === $current_value ) {
                    $current_value = 'restricted-user-roles';
                } else if ( 'all_users' === $current_value ) {
                    $current_value = 'all-users';
                }
                break;
            case 'user_ids_for_cancel':
                $current_value = get_option( 'sumo_subscription_cancel_by_user_filter' );
                break;
            case 'user_roles_for_cancel':
                $current_value = get_option( 'sumo_subscription_cancel_by_userrole_filter' );
                break;
            /**
             * Miscellaneous
             */
            case 'allow_subscribers_to_switch_bw_identical_variations':
                $current_value = get_option( 'sumo_switch_variation_subscription_option' );
                break;
            case 'allow_subscribers_to_update_subscription_qty':
                $current_value = get_option( 'sumo_allow_subscribers_to_change_qty' );
                break;
            case 'allow_subscribers_to_resubscribe':
                $current_value = get_option( 'sumo_allow_subscribers_to_resubscribe' );
                break;
            case 'allow_subscribers_to_turnoff_auto_renewals':
                $current_value = get_option( 'sumo_allow_subscribers_to_turnoff_auto_payments' );
                break;
            case 'allow_subscribers_to_change_shipping_address':
                $current_value = get_option( 'sumo_allow_subscribers_to_change_shipping_address' );
                break;
            case 'hide_resubscribe_to_subscribers_when':
                $current_value = get_option( 'sumo_hide_resubscribe_button_when' );
                break;
            case 'drip_downloadable_content':
                $current_value = get_option( 'sumo_enable_content_dripping' );
                break;
            case 'enable_additional_digital_downloads':
                $current_value = get_option( 'sumo_enable_additional_digital_downloads_option' );
                break;
            case 'show_subscription_activities':
                $current_value = get_option( 'sumosubs_show_activity_logs' );

                // map deprecated current option value to new value.
                if ( 'hide' === $current_value ) {
                    $current_value = 'no';
                } else if ( 'show' === $current_value ) {
                    $current_value = 'yes';
                }
                break;
            /**
             * Endpoints
             */
            case 'my_account_subscriptions_endpoint':
                $current_value = get_option( 'sumo_my_account_subscriptions_endpoint' );
                break;
            case 'my_account_view_subscription_endpoint':
                $current_value = get_option( 'sumo_my_account_view_subscription_endpoint' );
                break;
            /**
             * Order Subscription
             */
            case 'order_subs_allow_in_checkout':
                $current_value = get_option( 'sumo_order_subsc_check_option' );
                break;
            case 'order_subs_allow_in_cart':
                $current_value = get_option( 'sumo_display_order_subscription_in_cart' );
                break;
            case 'order_subs_subscribed_default':
                $current_value = get_option( 'sumo_order_subsc_checkout_option' );
                break;
            case 'order_subs_subscribe_values':
                $current_value = get_option( 'sumo_order_subsc_chosen_by_option' );

                // map deprecated current option value to new value.
                if ( 'user' === $current_value ) {
                    $current_value = 'userdefined';
                } else if ( 'admin' === $current_value ) {
                    $current_value = 'predefined';
                }
                break;
            case 'order_subs_predefined_subscription_period':
                $current_value = get_option( 'sumo_order_subsc_duration_option' );
                break;
            case 'order_subs_predefined_subscription_period_interval':
                $current_value = get_option( 'sumo_order_subsc_duration_value_option' );
                break;
            case 'order_subs_predefined_subscription_length':
                $current_value = get_option( 'sumo_order_subsc_recurring_option' );
                break;
            case 'order_subs_userdefined_subscription_periods':
                $current_value = get_option( 'sumo_get_order_subsc_duration_period_selector_for_users' );
                break;
            case 'order_subs_userdefined_min_subscription_period_intervals':
                $current_value = get_option( 'sumo_order_subsc_min_subsc_duration_value_user_can_select' );
                break;
            case 'order_subs_userdefined_max_subscription_period_intervals':
                $current_value = get_option( 'sumo_order_subsc_max_subsc_duration_value_user_can_select' );
                break;
            case 'order_subs_userdefined_allow_indefinite_subscription_length':
                $current_value = get_option( 'sumo_order_subsc_enable_recurring_cycle_option_for_users' );
                break;
            case 'order_subs_userdefined_min_subscription_length':
                $current_value = get_option( 'sumo_order_subsc_min_recurring_cycle_user_can_select' );
                break;
            case 'order_subs_userdefined_max_subscription_length':
                $current_value = get_option( 'sumo_order_subsc_max_recurring_cycle_user_can_select' );
                break;
            case 'order_subs_charge_signupfee':
                $current_value = get_option( 'sumo_order_subsc_has_signup' );
                break;
            case 'order_subs_signupfee':
                $current_value = get_option( 'sumo_order_subsc_signup_fee' );
                break;
            case 'order_subs_product_wide_selection':
                $current_value = get_option( 'sumo_order_subsc_get_product_selected_type' );

                // map deprecated current option value to new value.
                if ( 'included-products' === $current_value ) {
                    $current_value = 'allowed-product-ids';
                } else if ( 'excluded-products' === $current_value ) {
                    $current_value = 'restricted-product-ids';
                } else if ( 'included-categories' === $current_value ) {
                    $current_value = 'allowed-product-cat-ids';
                } else if ( 'excluded-categories' === $current_value ) {
                    $current_value = 'restricted-product-cat-ids';
                }
                break;
            case 'order_subs_allowed_product_ids':
                $current_value = get_option( 'sumo_order_subsc_get_included_products' );
                break;
            case 'order_subs_restricted_product_ids':
                $current_value = get_option( 'sumo_order_subsc_get_excluded_products' );
                break;
            case 'order_subs_allowed_product_cat_ids':
                $current_value = get_option( 'sumo_order_subsc_get_included_categories' );
                break;
            case 'order_subs_restricted_product_cat_ids':
                $current_value = get_option( 'sumo_order_subsc_get_excluded_categories' );
                break;
            case 'order_subs_min_order_total':
                $current_value = get_option( 'sumo_min_order_total_to_display_order_subscription' );
                break;
            case 'order_subs_checkout_position':
                $current_value = get_option( 'sumo_order_subsc_form_position' );
                break;
            case 'order_subs_subscribe_label':
                $current_value = get_option( 'sumo_order_subsc_checkout_label_option' );
                break;
            case 'order_subs_subscription_duration_label':
                $current_value = get_option( 'sumo_order_subsc_duration_checkout_label_option' );
                break;
            case 'order_subs_subscription_length_label':
                $current_value = get_option( 'sumo_order_subsc_recurring_checkout_label_option' );
                break;
            case 'order_subs_inline_style':
                $current_value = get_option( 'sumo_order_subsc_custom_css' );
                break;
            /**
             * Switcher
             */
            case 'allow_switcher':
                $current_value = get_option( 'sumosubs_allow_upgrade_r_downgrade' );
                break;
            case 'switch_based_on':
                $current_value = get_option( 'sumosubs_upgrade_r_downgrade_based_on' );
                break;
            case 'allow_user_to_switch':
                $current_value = get_option( 'sumosubs_allow_user_to' );
                break;
            case 'allow_switch_between':
                $current_value = get_option( 'sumosubs_allow_upgrade_r_downgrade_between' );
                break;
            case 'payment_mode_when_switch':
                $current_value = get_option( 'sumosubs_payment_for_upgrade_r_downgrade' );

                if ( 'full_payment' === $current_value ) {
                    $current_value = 'full-payment';
                }
                break;
            case 'when_switch_prorate_recurring_payment_for':
                $current_value = get_option( 'sumosubs_prorate_recurring_payment' );
                break;
            case 'when_switch_charge_signup_fee':
                $current_value = get_option( 'sumosubs_charge_signup_fee' );
                break;
            case 'when_switch_prorate_subscription_length':
                $current_value = get_option( 'sumosubs_prorate_subscription_recurring_cycle' );
                break;
            case 'switch_button_text':
                $current_value = get_option( 'sumosubs_upgrade_r_downgrade_button_text' );
                break;
            /**
             * Synchronization
             */
            case 'allow_synchronization':
                $current_value = get_option( 'sumo_synchronize_check_option' );
                break;
            case 'sync_based_on':
                $current_value = get_option( 'sumo_subscription_synchronize_mode' );

                if ( '2' === $current_value ) {
                    $current_value = 'first-occurrence';
                } else if ( '1' === $current_value ) {
                    $current_value = 'exact-date-r-day';
                }
                break;
            case 'when_sync_show_next_due_date_in_product_page':
                $current_value = get_option( 'sumo_synchronized_next_payment_date_option' );
                break;
            case 'payment_mode_when_sync':
                $current_value = get_option( 'sumosubs_payment_for_synced_period' );

                if ( 'full_payment' === $current_value ) {
                    $current_value = 'full-payment';
                } else if ( 'yes' === get_option( 'sumo_synchronize_prorate_check_option' ) ) {
                    $current_value = 'prorate';
                }
                break;
            case 'when_sync_prorate_payment_for':
                $current_value = get_option( 'sumo_prorate_payment_for_selection' );

                if ( 'all_subscriptions' === $current_value ) {
                    $current_value = 'all-subscriptions';
                } else if ( 'all_virtual' === $current_value ) {
                    $current_value = 'all-virtual';
                }
                break;
            case 'when_sync_prorate_payment_during':
                $current_value = get_option( 'sumo_prorate_payment_on_selection' );

                if ( 'first_payment' === $current_value ) {
                    $current_value = 'first-payment';
                } else if ( 'first_renewal' === $current_value ) {
                    $current_value = 'first-renewal';
                }
                break;
            /**
             * Messages
             */
            case 'signup_fee_strings':
                $current_value = get_option( 'sumo_signup_fee_msg_customization' );
                break;
            case 'free_trial_strings':
                $current_value = get_option( 'sumo_freetrial_caption_msg_customization' );
                break;
            case 'trial_fee_and_duration_strings':
                $current_value = get_option( 'sumo_trial_fee_msg_customization' );
                break;
            case 'subscription_price_and_duration_strings':
                $current_value = get_option( 'sumo_subscription_fee_msg_customization' );
                break;
            case 'subscription_length_strings':
                $current_value = get_option( 'sumo_instalment_msg_customization' );
                break;
            case 'variable_product_price_strings':
                $current_value = get_option( 'sumo_variation_product_fee_range_msg_customization' );
                break;
            case 'optional_free_trial_strings':
                $current_value = get_option( 'sumo_product_optional_free_trial_msg_customization' );
                break;
            case 'optional_paid_trial_strings':
                $current_value = get_option( 'sumo_product_optional_paid_trial_msg_customization' );
                break;
            case 'optional_signup_fee_strings':
                $current_value = get_option( 'sumo_product_optional_signup_msg_customization' );
                break;
            case 'discounted_renewal_amount_strings':
                $current_value = get_option( 'sumo_renewal_fee_after_discount_msg_customization' );
                break;
            case 'synced_plan_strings':
                $current_value = get_option( 'sumo_subscription_synchronization_plan_msg_customization' );
                break;
            case 'synced_prorated_amount_strings':
                $current_value = get_option( 'sumo_prorated_amount_first_payment_msg_customization' );
                break;
            case 'synced_prorated_amount_during_first_renewal_strings':
                $current_value = get_option( 'sumo_prorated_amount_first_renewal_msg_customization' );
                break;
            case 'period_day_r_days_label':
                $current_value = get_option( 'sumo_day_single_plural' );
                break;
            case 'period_week_r_weeks_label':
                $current_value = get_option( 'sumo_week_single_plural' );
                break;
            case 'period_month_r_months_label':
                $current_value = get_option( 'sumo_month_single_plural' );
                break;
            case 'period_year_r_years_label':
                $current_value = get_option( 'sumo_year_single_plural' );
                break;
            case 'period_length_r_lengths_label':
                $current_value = get_option( 'sumo_instalment_single_plural' );
                break;
            case 'show_error_messages_in_product_page':
                $current_value = get_option( 'sumo_show_hide_err_msg_product_page' );
                break;
            case 'product_wide_subscription_limit_error_message':
                $current_value = get_option( 'sumo_active_subsc_per_product_in_product_page' );
                break;
            case 'site_wide_subscription_limit_error_message':
                $current_value = get_option( 'sumo_active_subsc_through_site_in_product_page' );
                break;
            case 'cannot_purchase_regular_with_subscription_product_error_message':
                $current_value = get_option( 'sumo_err_msg_for_add_to_cart_non_subscription_with_subscription' );
                break;
            case 'cannot_purchase_subscription_with_regular_product_error_message':
                $current_value = get_option( 'sumo_err_msg_for_add_to_cart_subscription_with_non_subscription' );
                break;
            case 'show_error_messages_in_cart_page':
                $current_value = get_option( 'sumo_show_hide_err_msg_cart_page' );
                break;
            case 'product_wide_trial_limit_error_message':
                $current_value = get_option( 'sumo_active_trial_per_product_in_cart_page' );
                break;
            case 'site_wide_trial_limit_error_message':
                $current_value = get_option( 'sumo_active_trial_through_site_in_cart_page' );
                break;
            case 'show_error_messages_in_pay_for_order_page':
                $current_value = get_option( 'sumo_show_hide_err_msg_pay_order_page' );
                break;
            case 'renewal_order_payment_in_paused_error_message':
                $current_value = get_option( 'sumo_err_msg_for_paused_in_pay_for_order_page' );
                break;
            case 'renewal_order_payment_in_pending_cancel_error_message':
                $current_value = get_option( 'sumo_err_msg_for_pending_cancellation_in_pay_for_order_page' );
                break;
        }

        if ( false !== $current_value ) {
            $value = $current_value;
        } else if ( false === $value ) {
            $value = self::get_option_default( $option_key );
        }

        add_option( self::prepare_option_name( $option_key ), $value );
    }

    /**
     * Delete the option.
     * 
     * @param string $key_or_name
     */
    public static function delete_option( $key_or_name ) {
        $option_key = self::maybe_trim_option_name( $key_or_name );

        switch ( $option_key ) {
            /**
             * General
             */
            case 'subscribe_text':
                delete_option( 'sumo_add_to_cart_text' );
                break;
            /**
             * Renewals
             */
            case 'renewal_order_creation_schedule':
                delete_option( 'sumo_create_renewal_order_on' );
                break;
            case 'charge_shipping_during_renewals':
                delete_option( 'sumo_shipping_option' );
                break;
            case 'charge_tax_during_renewals':
                delete_option( 'sumo_tax_option' );
                break;
            /**
             * Notification schedules
             */
            case 'manual_renewal_due_reminder_schedule':
                delete_option( 'sumo_remaind_notification_email' );
                break;
            case 'auto_renewal_due_reminder_schedule':
                delete_option( 'sumo_remaind_notification_email_for_automatic' );
                break;
            case 'expiration_reminder_schedule':
                delete_option( 'sumo_expiry_reminder_email' );
                break;
            /**
             * Grace schedules
             */
            case 'overdue_schedule':
                delete_option( 'sumo_settings_overdue_notification_email' );
                break;
            case 'suspend_schedule':
                delete_option( 'sumo_suspend_notification_email' );
                break;
            /**
             * Restrictions
             */
            case 'allow_mixed_checkout':
                delete_option( 'sumosubscription_apply_mixed_checkout' );
                break;
            case 'limit_subscription_by':
                delete_option( 'sumo_limit_subscription_quantity' );
                break;
            case 'limit_trial_by':
                delete_option( 'sumo_trial_handling' );
                break;
            case 'limit_variable_subscription_at':
                delete_option( 'sumo_limit_variable_product_level' );
                break;
            /**
             * Payments
             */
            case 'accept_manual_payments':
                delete_option( 'sumosubs_accept_manual_payment_gateways' );
                break;
            case 'disable_auto_payments':
                delete_option( 'sumosubs_disable_auto_payment_gateways' );
                break;
            case 'subscription_payment_gateway_mode':
                delete_option( 'sumo_paypal_payment_option' );
                delete_option( 'sumo_force_auto_manual_paypal_adaptive' );
                break;
            case 'payment_retries_in_overdue':
                delete_option( 'sumo_auto_payment_in_overdue' );
                break;
            case 'payment_retries_in_suspend':
                delete_option( 'sumo_auto_payment_in_suspend' );
                break;
            case 'show_subscription_payment_gateways_when_order_amt_0':
                delete_option( 'sumosubscription_show_payment_gateways_when_order_amt_zero' );
                break;
            /**
             * Advanced
             */
            case 'update_old_subscription_price_to':
                delete_option( 'sumosubs_apply_subscription_fee_by' );
                break;
            case 'activate_subscription':
                delete_option( 'sumosubs_activate_subscription_by' );
                break;
            case 'activate_free_trial':
                delete_option( 'sumosubs_activate_free_trial_by' );
                break;
            case 'subscription_number_prefix':
                delete_option( 'sumo_subscription_number_custom_prefix' );
                break;
            case 'subscription_as_regular_product_defined_rules':
                delete_option( 'sumo_subscription_as_regular_product_defined_rules' );
                break;
            /**
             * Display
             */
            case 'variable_product_price_display_as':
                delete_option( 'sumosubs_apply_variable_product_price_msg_based_on' );
                break;
            case 'show_timezone_in_frontend':
                delete_option( 'sumo_show_subscription_timezone' );
                break;
            case 'show_timezone_in_frontend_as':
                delete_option( 'sumo_set_subscription_timezone_as' );
                break;
            case 'show_time_in_frontend':
                delete_option( 'sumosubs_show_time_in_frontend' );
                break;
            case 'inline_style':
                delete_option( 'sumo_subsc_custom_css' );
                break;
            /**
             * Emails
             */
            case 'new_subscription_order_email_for_old_subscribers':
                delete_option( 'sumosubs_new_subscription_order_template_for_old_subscribers' );
                break;
            case 'order_subscription_load_ajax_synchronously':
                delete_option( 'sumo_sync_ajax_for_order_subscription' );
                break;
            /**
             * Pause
             */
            case 'allow_subscribers_to_pause':
                delete_option( 'sumo_pause_resume_option' );
                break;
            case 'allow_subscribers_to_pause_synced':
                delete_option( 'sumo_sync_pause_resume_option' );
                break;
            case 'allow_subscribers_to_select_resume_date':
                delete_option( 'sumo_allow_user_to_select_resume_date' );
                break;
            case 'max_pause_times_for_subscribers':
                delete_option( 'sumo_settings_max_no_of_pause' );
                break;
            case 'max_pause_duration_for_subscribers':
                delete_option( 'sumo_settings_max_duration_of_pause' );
                break;
            case 'user_wide_pause_for':
                delete_option( 'sumo_subscription_pause_by_user_or_userrole_filter' );
                break;
            case 'user_ids_for_pause':
                delete_option( 'sumo_subscription_pause_by_user_filter' );
                break;
            case 'user_roles_for_pause':
                delete_option( 'sumo_subscription_pause_by_userrole_filter' );
                break;
            /**
             * Cancel
             */
            case 'allow_subscribers_to_cancel':
                delete_option( 'sumo_cancel_option' );
                break;
            case 'allow_subscribers_to_cancel_after_schedule':
                delete_option( 'sumo_min_days_user_wait_to_cancel_their_subscription' );
                break;
            case 'cancel_options_for_subscriber':
                delete_option( 'sumo_subscription_cancel_methods_available_to_subscriber' );
                break;
            case 'product_wide_cancellation_for':
                delete_option( 'sumo_subscription_cancel_by_product_or_category_filter' );
                break;
            case 'product_ids_for_cancel':
                delete_option( 'sumo_subscription_cancel_by_product_filter' );
                break;
            case 'product_cat_ids_for_cancel':
                delete_option( 'sumo_subscription_cancel_by_category_filter' );
                break;
            case 'user_wide_cancellation_for':
                delete_option( 'sumo_subscription_cancel_by_user_or_userrole_filter' );
                break;
            case 'user_ids_for_cancel':
                delete_option( 'sumo_subscription_cancel_by_user_filter' );
                break;
            case 'user_roles_for_cancel':
                delete_option( 'sumo_subscription_cancel_by_userrole_filter' );
                break;
            /**
             * Miscellaneous
             */
            case 'allow_subscribers_to_switch_bw_identical_variations':
                delete_option( 'sumo_switch_variation_subscription_option' );
                break;
            case 'allow_subscribers_to_update_subscription_qty':
                delete_option( 'sumo_allow_subscribers_to_change_qty' );
                break;
            case 'allow_subscribers_to_resubscribe':
                delete_option( 'sumo_allow_subscribers_to_resubscribe' );
                break;
            case 'allow_subscribers_to_turnoff_auto_renewals':
                delete_option( 'sumo_allow_subscribers_to_turnoff_auto_payments' );
                break;
            case 'allow_subscribers_to_change_shipping_address':
                delete_option( 'sumo_allow_subscribers_to_change_shipping_address' );
                break;
            case 'hide_resubscribe_to_subscribers_when':
                delete_option( 'sumo_hide_resubscribe_button_when' );
                break;
            case 'drip_downloadable_content':
                delete_option( 'sumo_enable_content_dripping' );
                break;
            case 'enable_additional_digital_downloads':
                delete_option( 'sumo_enable_additional_digital_downloads_option' );
                break;
            case 'show_subscription_activities':
                delete_option( 'sumosubs_show_activity_logs' );
                break;
            /**
             * Endpoints
             */
            case 'my_account_subscriptions_endpoint':
                delete_option( 'sumo_my_account_subscriptions_endpoint' );
                break;
            case 'my_account_view_subscription_endpoint':
                delete_option( 'sumo_my_account_view_subscription_endpoint' );
                break;
            /**
             * Order Subscription
             */
            case 'order_subs_allow_in_checkout':
                delete_option( 'sumo_order_subsc_check_option' );
                break;
            case 'order_subs_allow_in_cart':
                delete_option( 'sumo_display_order_subscription_in_cart' );
                break;
            case 'order_subs_subscribed_default':
                delete_option( 'sumo_order_subsc_checkout_option' );
                break;
            case 'order_subs_subscribe_values':
                delete_option( 'sumo_order_subsc_chosen_by_option' );
                break;
            case 'order_subs_predefined_subscription_period':
                delete_option( 'sumo_order_subsc_duration_option' );
                break;
            case 'order_subs_predefined_subscription_period_interval':
                delete_option( 'sumo_order_subsc_duration_value_option' );
                break;
            case 'order_subs_predefined_subscription_length':
                delete_option( 'sumo_order_subsc_recurring_option' );
                break;
            case 'order_subs_userdefined_subscription_periods':
                delete_option( 'sumo_get_order_subsc_duration_period_selector_for_users' );
                break;
            case 'order_subs_userdefined_min_subscription_period_intervals':
                delete_option( 'sumo_order_subsc_min_subsc_duration_value_user_can_select' );
                break;
            case 'order_subs_userdefined_max_subscription_period_intervals':
                delete_option( 'sumo_order_subsc_max_subsc_duration_value_user_can_select' );
                break;
            case 'order_subs_userdefined_allow_indefinite_subscription_length':
                delete_option( 'sumo_order_subsc_enable_recurring_cycle_option_for_users' );
                break;
            case 'order_subs_userdefined_min_subscription_length':
                delete_option( 'sumo_order_subsc_min_recurring_cycle_user_can_select' );
                break;
            case 'order_subs_userdefined_max_subscription_length':
                delete_option( 'sumo_order_subsc_max_recurring_cycle_user_can_select' );
                break;
            case 'order_subs_charge_signupfee':
                delete_option( 'sumo_order_subsc_has_signup' );
                break;
            case 'order_subs_signupfee':
                delete_option( 'sumo_order_subsc_signup_fee' );
                break;
            case 'order_subs_product_wide_selection':
                delete_option( 'sumo_order_subsc_get_product_selected_type' );
                break;
            case 'order_subs_allowed_product_ids':
                delete_option( 'sumo_order_subsc_get_included_products' );
                break;
            case 'order_subs_restricted_product_ids':
                delete_option( 'sumo_order_subsc_get_excluded_products' );
                break;
            case 'order_subs_allowed_product_cat_ids':
                delete_option( 'sumo_order_subsc_get_included_categories' );
                break;
            case 'order_subs_restricted_product_cat_ids':
                delete_option( 'sumo_order_subsc_get_excluded_categories' );
                break;
            case 'order_subs_min_order_total':
                delete_option( 'sumo_min_order_total_to_display_order_subscription' );
                break;
            case 'order_subs_checkout_position':
                delete_option( 'sumo_order_subsc_form_position' );
                break;
            case 'order_subs_subscribe_label':
                delete_option( 'sumo_order_subsc_checkout_label_option' );
                break;
            case 'order_subs_subscription_duration_label':
                delete_option( 'sumo_order_subsc_duration_checkout_label_option' );
                break;
            case 'order_subs_subscription_length_label':
                delete_option( 'sumo_order_subsc_recurring_checkout_label_option' );
                break;
            case 'order_subs_inline_style':
                delete_option( 'sumo_order_subsc_custom_css' );
                break;
            /**
             * Switcher
             */
            case 'allow_switcher':
                delete_option( 'sumosubs_allow_upgrade_r_downgrade' );
                break;
            case 'switch_based_on':
                delete_option( 'sumosubs_upgrade_r_downgrade_based_on' );
                break;
            case 'allow_user_to_switch':
                delete_option( 'sumosubs_allow_user_to' );
                break;
            case 'allow_switch_between':
                delete_option( 'sumosubs_allow_upgrade_r_downgrade_between' );
                break;
            case 'payment_mode_when_switch':
                delete_option( 'sumosubs_payment_for_upgrade_r_downgrade' );
                break;
            case 'when_switch_prorate_recurring_payment_for':
                delete_option( 'sumosubs_prorate_recurring_payment' );
                break;
            case 'when_switch_charge_signup_fee':
                delete_option( 'sumosubs_charge_signup_fee' );
                break;
            case 'when_switch_prorate_subscription_length':
                delete_option( 'sumosubs_prorate_subscription_recurring_cycle' );
                break;
            case 'switch_button_text':
                delete_option( 'sumosubs_upgrade_r_downgrade_button_text' );
                break;
            /**
             * Synchronization
             */
            case 'allow_synchronization':
                delete_option( 'sumo_synchronize_check_option' );
                break;
            case 'sync_based_on':
                delete_option( 'sumo_subscription_synchronize_mode' );
                break;
            case 'when_sync_show_next_due_date_in_product_page':
                delete_option( 'sumo_synchronized_next_payment_date_option' );
                break;
            case 'payment_mode_when_sync':
                delete_option( 'sumosubs_payment_for_synced_period' );
                break;
            case 'when_sync_prorate_payment_for':
                delete_option( 'sumo_prorate_payment_for_selection' );
                break;
            case 'when_sync_prorate_payment_during':
                delete_option( 'sumo_prorate_payment_on_selection' );
                break;
            /**
             * Messages
             */
            case 'signup_fee_strings':
                delete_option( 'sumo_signup_fee_msg_customization' );
                break;
            case 'free_trial_strings':
                delete_option( 'sumo_freetrial_caption_msg_customization' );
                break;
            case 'trial_fee_and_duration_strings':
                delete_option( 'sumo_trial_fee_msg_customization' );
                break;
            case 'subscription_price_and_duration_strings':
                delete_option( 'sumo_subscription_fee_msg_customization' );
                break;
            case 'subscription_length_strings':
                delete_option( 'sumo_instalment_msg_customization' );
                break;
            case 'variable_product_price_strings':
                delete_option( 'sumo_variation_product_fee_range_msg_customization' );
                break;
            case 'optional_free_trial_strings':
                delete_option( 'sumo_product_optional_free_trial_msg_customization' );
                break;
            case 'optional_paid_trial_strings':
                delete_option( 'sumo_product_optional_paid_trial_msg_customization' );
                break;
            case 'optional_signup_fee_strings':
                delete_option( 'sumo_product_optional_signup_msg_customization' );
                break;
            case 'discounted_renewal_amount_strings':
                delete_option( 'sumo_renewal_fee_after_discount_msg_customization' );
                break;
            case 'synced_plan_strings':
                delete_option( 'sumo_subscription_synchronization_plan_msg_customization' );
                break;
            case 'synced_prorated_amount_strings':
                delete_option( 'sumo_prorated_amount_first_payment_msg_customization' );
                break;
            case 'synced_prorated_amount_during_first_renewal_strings':
                delete_option( 'sumo_prorated_amount_first_renewal_msg_customization' );
                break;
            case 'period_day_r_days_label':
                delete_option( 'sumo_day_single_plural' );
                break;
            case 'period_week_r_weeks_label':
                delete_option( 'sumo_week_single_plural' );
                break;
            case 'period_month_r_months_label':
                delete_option( 'sumo_month_single_plural' );
                break;
            case 'period_year_r_years_label':
                delete_option( 'sumo_year_single_plural' );
                break;
            case 'period_length_r_lengths_label':
                delete_option( 'sumo_instalment_single_plural' );
                break;
            case 'show_error_messages_in_product_page':
                delete_option( 'sumo_show_hide_err_msg_product_page' );
                break;
            case 'product_wide_subscription_limit_error_message':
                delete_option( 'sumo_active_subsc_per_product_in_product_page' );
                break;
            case 'site_wide_subscription_limit_error_message':
                delete_option( 'sumo_active_subsc_through_site_in_product_page' );
                break;
            case 'cannot_purchase_regular_with_subscription_product_error_message':
                delete_option( 'sumo_err_msg_for_add_to_cart_non_subscription_with_subscription' );
                break;
            case 'cannot_purchase_subscription_with_regular_product_error_message':
                delete_option( 'sumo_err_msg_for_add_to_cart_subscription_with_non_subscription' );
                break;
            case 'show_error_messages_in_cart_page':
                delete_option( 'sumo_show_hide_err_msg_cart_page' );
                break;
            case 'product_wide_trial_limit_error_message':
                delete_option( 'sumo_active_trial_per_product_in_cart_page' );
                break;
            case 'site_wide_trial_limit_error_message':
                delete_option( 'sumo_active_trial_through_site_in_cart_page' );
                break;
            case 'show_error_messages_in_pay_for_order_page':
                delete_option( 'sumo_show_hide_err_msg_pay_order_page' );
                break;
            case 'renewal_order_payment_in_paused_error_message':
                delete_option( 'sumo_err_msg_for_paused_in_pay_for_order_page' );
                break;
            case 'renewal_order_payment_in_pending_cancel_error_message':
                delete_option( 'sumo_err_msg_for_pending_cancellation_in_pay_for_order_page' );
                break;
        }

        delete_option( self::prepare_option_name( $option_key ) );
        unset( self::$options[ $option_key ] );
    }
}
