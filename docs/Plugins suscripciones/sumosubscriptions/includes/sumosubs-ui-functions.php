<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Get Subscription date for display purpose.
 *
 * @param int|string $date
 * @return string
 */
function sumo_display_subscription_date( $date ) {
    $date           = sumo_get_subscription_date( $date );
    $wp_date_format = '' !== get_option( 'date_format' ) ? get_option( 'date_format' ) : 'F j, Y';
    $wp_time_format = '' !== get_option( 'time_format' ) ? get_option( 'time_format' ) : 'g:i a';

    /**
     * Need to display time in WordPress timezone?
     * 
     * @since 1.0
     */
    if ( apply_filters( 'sumosubscriptions_display_time_in_wp_timezone', ( is_admin() ? false : true ) ) ) {
        $wp_timezone_offset = 'wordpress' === SUMOSubs_Admin_Options::get_option( 'show_timezone_in_frontend_as' ) ? ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) : 0;
    } else {
        $wp_timezone_offset = 0;
    }

    /**
     * Need to display time?
     * 
     * @since 1.0
     */
    $display_time = apply_filters( 'sumosubscriptions_display_time', ( is_admin() ? true : 'yes' === SUMOSubs_Admin_Options::get_option( 'show_time_in_frontend' ) ) );
    $date_format  = $wp_date_format;
    $time_format  = $display_time ? $wp_time_format : '';

    if ( empty( $time_format ) ) {
        return date_i18n( "{$date_format}", strtotime( $date ) + $wp_timezone_offset );
    }

    return date_i18n( "{$date_format} {$time_format}", strtotime( $date ) + $wp_timezone_offset );
}

/**
 * Format the Date difference from Future date to Current date.
 *
 * @param int|string $future_date
 * @return string
 */
function sumo_get_subscription_date_difference( $future_date = null ) {
    if ( ! $future_date ) {
        return '';
    }

    $now = new DateTime();
    if ( is_string( $future_date ) ) {
        if ( strtotime( $future_date ) < time() ) {
            $future_date = false;
        } else {
            $future_date = new DateTime( $future_date );
        }
    } elseif ( is_numeric( $future_date ) ) {
        if ( absint( $future_date ) < time() ) {
            $future_date = false;
        } else {
            $future_date = new DateTime( gmdate( 'Y-m-d H:i:s', $future_date ) );
        }
    }

    if ( $future_date ) {
        $interval = $future_date->diff( $now );
        /* translators: 1: days 2: hours 3: minutes 4: seconds */
        return $interval->format( '<b>%a</b> day(s), <b>%H</b> hour(s), <b>%I</b> minute(s), <b>%S</b> second(s)' );
    }

    return 'now';
}

/**
 * Display Subscription Status.
 *
 * @param int $post_id The Subscription post ID
 * @return string
 */
function sumo_display_subscription_status( $post_id, $html = true ) {
    $subscription_status   = get_post_meta( $post_id, 'sumo_get_status', true );
    $subscription_statuses = sumo_get_subscription_statuses();
    $display_name          = $subscription_statuses[ $subscription_status ];

    if ( $html ) {
        $subscription_status = '<mark class="sumosubs-status '
                . ( 'Active' === $subscription_status ? 'Active-Subscription' : $subscription_status )
                . '"/>'
                . esc_attr( $display_name )
                . '</mark>';
    } else {
        $subscription_status = $display_name;
    }

    return $subscription_status;
}

/**
 * Display Subscription Number/ID.
 *
 * @param int $post_id The Subscription post ID
 * @return string
 */
function sumo_display_subscription_ID( $post_id, $url = true ) {
    $subscription_ID       = sumo_get_subscription_number( $post_id );
    $subscription_endpoint = sumo_get_subscription_endpoint_url( $post_id );

    if ( '' !== $subscription_ID ) {
        return $url ? "<a href={$subscription_endpoint}>#{$subscription_ID}</a>" : "#{$subscription_ID}";
    }

    return '#0';
}

/**
 * Display Subscription Name.
 *
 * @param int $post_id The Subscription post ID
 * @param boolean $qty
 * @param boolean $new_line
 * @return string
 */
function sumo_display_subscription_name( $post_id, $qty = false, $new_line = false, $url = true ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id );

    if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
        return '';
    }

    //may be Order Subscription
    if ( SUMOSubs_Order_Subscription::is_subscribed( $post_id ) ) {
        $items_id       = $subscription_plan[ 'subscription_product_id' ];
        $items_qty      = $subscription_plan[ 'subscription_product_qty' ];
        $products_title = get_post_meta( $post_id, 'sumo_product_name', true );
        $item_title     = array();

        foreach ( $items_id as $item_id ) {
            if ( isset( $products_title[ $item_id ] ) ) {
                $item_name = $products_title[ $item_id ];
                $item_qty  = $qty ? ' x' . $items_qty[ $item_id ] : '';
                $product   = wc_get_product( $item_id );

                /**
                 * Need to show current product title?
                 * 
                 * @since 1.0
                 */
                if ( $product && apply_filters( 'sumosubscriptions_show_current_product_title', true, $post_id ) ) {
                    if ( is_admin() ) {
                        $identifier = $product->get_sku() ? $product->get_sku() : '#' . $product->get_id();
                        $item_name  = sprintf( '%2$s (%1$s)', $identifier, $product->get_name() );
                    } else {
                        $item_name = $product->get_name();
                    }

                    $meta_data = $product->is_type( 'variation' ) ? wc_get_formatted_variation( $product, true, true, true ) : '';
                    if ( ! empty( $meta_data ) ) {
                        $item_name .= ' - <span class="description">' . $meta_data . '</span>';
                    }
                }

                /**
                 * Need to show product url?
                 * 
                 * @since 1.0
                 */
                if ( $url && apply_filters( 'sumosubscriptions_show_product_permalink', true, $post_id ) ) {
                    if ( is_admin() ) {
                        $item_link = $product && $product->get_parent_id() ? get_edit_post_link( $product->get_parent_id() ) : get_edit_post_link( $item_id );
                    } else {
                        $item_link = get_permalink( $item_id );
                    }

                    $item_title[] = isset( $item_link ) ? '<a href="' . esc_url( $item_link ) . '">' . wp_kses_post( $item_name ) . '</a>' . esc_html( $item_qty ) : '-';
                } else {
                    $item_title[] = wp_kses_post( $item_name ) . esc_html( $item_qty );
                }
            }
        }

        $subscription_name = implode( $new_line ? ',<br>' : ', ', $item_title );
    } else {
        $product_title = get_post_meta( $post_id, 'sumo_product_name', true );
        $product_qty   = $qty ? ' x' . sumo_get_subscription_qty( $post_id ) : '';
        $product       = wc_get_product( $subscription_plan[ 'subscription_product_id' ] );

        if ( ! $product ) {
            return sprintf( __( 'Product ID:<strong>(#%s)</strong> Deleted', 'sumosubscriptions' ), esc_html( $subscription_plan[ 'subscription_product_id' ] ) );
        }

        /**
         * Need to show current product title?
         * 
         * @since 1.0
         */
        if ( $product && apply_filters( 'sumosubscriptions_show_current_product_title', true, $post_id ) ) {
            if ( is_admin() ) {
                $identifier    = $product->get_sku() ? $product->get_sku() : '#' . $product->get_id();
                $product_title = sprintf( '%2$s (%1$s)', $identifier, $product->get_name() );
            } else {
                $product_title = $product->get_name();
            }

            $meta_data = $product->is_type( 'variation' ) ? wc_get_formatted_variation( $product, true, true, true ) : '';
            if ( ! empty( $meta_data ) ) {
                $product_title .= ' - <span class="description">' . $meta_data . '</span>';
            }
        }

        /**
         * Need to show product url?
         * 
         * @since 1.0
         */
        if ( $url && apply_filters( 'sumosubscriptions_show_product_permalink', true, $post_id ) ) {
            if ( is_admin() ) {
                $product_link = $product && $product->get_parent_id() ? get_edit_post_link( $product->get_parent_id() ) : get_edit_post_link( $subscription_plan[ 'subscription_product_id' ] );
            } else {
                $product_link = get_permalink( $subscription_plan[ 'subscription_product_id' ] );
            }

            $subscription_name = $product_link ? '<a href="' . esc_url( $product_link ) . '">' . wp_kses_post( $product_title ) . '</a>' . esc_html( $product_qty ) : '&ndash;';
        } else {
            $subscription_name = wp_kses_post( $product_title ) . esc_html( $product_qty );
        }
    }

    return $subscription_name;
}

/**
 * Display Subscription Start Date.
 *
 * @param int $post_id The Subscription post ID
 * @return string
 */
function sumo_display_start_date( $post_id ) {
    $start_date = get_post_meta( $post_id, 'sumo_get_sub_start_date', true );
    $utc_offset = is_account_page() && 'yes' === SUMOSubs_Admin_Options::get_option( 'show_timezone_in_frontend' ) ? ' (' . sumosubs_get_utc_offset_in_frontend() . ')' : '';

    return $start_date ? sumo_display_subscription_date( $start_date ) . $utc_offset : __( 'Not yet Started', 'sumosubscriptions' );
}

/**
 * Display Subscription Next Due Date.
 *
 * @param int $post_id The Subscription post ID
 * @return string
 */
function sumo_display_next_due_date( $post_id ) {
    $subscription_status = get_post_meta( $post_id, 'sumo_get_status', true );
    $next_payment_date   = get_post_meta( $post_id, 'sumo_get_next_payment_date', true );
    $utc_offset          = is_account_page() && 'yes' === SUMOSubs_Admin_Options::get_option( 'show_timezone_in_frontend' ) ? ' (' . sumosubs_get_utc_offset_in_frontend() . ')' : '';

    if ( $next_payment_date ) {
        if ( 'Pause' === $subscription_status ) {
            return __( 'Profile Has Been Paused', 'sumosubscriptions' );
        } else if ( in_array( $subscription_status, array( 'Cancelled', 'Expired', 'Overdue', 'Suspended', 'Failed', 'Pending_Authorization' ) ) ) {
            /* translators: 1: subscription status */
            return sprintf( __( 'Profile Has Been %s', 'sumosubscriptions' ), sumo_get_subscription_status( $subscription_status ) );
        } else if ( in_array( $subscription_status, array( 'Active', 'Trial', 'Pending_Cancellation' ) ) ) {
            return '--' === $next_payment_date ? '---' : sumo_display_subscription_date( $next_payment_date ) . $utc_offset;
        }
    }

    return '---';
}

/**
 * Display Subscription Trial End Date.
 *
 * @param int $post_id The Subscription post ID
 * @return string
 */
function sumo_display_trial_end_date( $post_id ) {
    $subscription_status = get_post_meta( $post_id, 'sumo_get_status', true );
    $trial_end_date      = get_post_meta( $post_id, 'sumo_get_trial_end_date', true );

    if ( $trial_end_date ) {
        if ( 'Pause' === $subscription_status ) {
            return __( 'Profile Has Been Paused', 'sumosubscriptions' );
        } else if ( in_array( $subscription_status, array( 'Cancelled', 'Expired', 'Overdue', 'Suspended', 'Failed', 'Pending_Authorization' ) ) ) {
            /* translators: 1: subscription status */
            return sprintf( __( 'Profile Has Been %s', 'sumosubscriptions' ), sumo_get_subscription_status( $subscription_status ) );
        } else if ( in_array( $subscription_status, array( 'Active', 'Trial', 'Pending_Cancellation' ) ) ) {
            return sumo_display_subscription_date( $trial_end_date );
        }
    }

    return __( 'None', 'sumosubscriptions' );
}

/**
 * Display Subscription End Date.
 *
 * @param int $post_id The Subscription post ID
 */
function sumo_display_end_date( $post_id ) {
    $end_date            = get_post_meta( $post_id, 'sumo_get_sub_end_date', true );
    $subscription_status = get_post_meta( $post_id, 'sumo_get_status', true );
    $utc_offset          = is_account_page() && 'yes' === SUMOSubs_Admin_Options::get_option( 'show_timezone_in_frontend' ) ? ' (' . sumosubs_get_utc_offset_in_frontend() . ')' : '';

    if ( $end_date ) {
        $end_date = sumo_display_subscription_date( $end_date ) . $utc_offset;
    } else {
        $sub_expiry_date = get_post_meta( $post_id, 'sumo_get_saved_due_date', true );
        if ( $sub_expiry_date ) {
            $end_date = sumo_display_subscription_date( $sub_expiry_date ) . $utc_offset;
        } else {
            $subscription_plan = sumo_get_subscription_plan( $post_id );

            if ( '0' === $subscription_plan[ 'subscription_recurring' ] ) {
                $end_date = __( 'Never Ends', 'sumosubscriptions' );
            } else {
                $end_date = '--';
                switch ( $subscription_status ) {
                    case 'Active':
                    case 'Trial':
                        $next_payment_dates = sumosubs_get_possible_next_payment_dates( $post_id, 0, true );

                        if ( ! empty( $next_payment_dates ) ) {
                            $final_due_date = end( $next_payment_dates );
                            $end_time       = sumosubs_get_next_payment_date( $post_id, 0, array(
                                'from_when'           => $final_due_date,
                                'get_as_timestamp'    => true,
                                'use_trial_if_exists' => false,
                                    ) );

                            $end_date = sumo_display_subscription_date( $end_time ) . $utc_offset;
                        }
                        break;
                    case 'Pending_Cancellation':
                        switch ( get_post_meta( $post_id, 'sumo_subscription_requested_cancel_method', true ) ) {
                            case 'end_of_billing_cycle':
                                $end_date = sumo_display_subscription_date( get_post_meta( $post_id, 'sumo_get_next_payment_date', true ) ) . $utc_offset;
                                break;
                            case 'scheduled_date':
                                $end_date = sumo_display_subscription_date( get_post_meta( $post_id, 'sumo_subscription_cancellation_scheduled_on', true ) ) . $utc_offset;
                                break;
                        }
                        break;
                    case 'Cancelled':
                    case 'Failed':
                    case 'Expired':
                        $end_date = __( 'Subscription Ended !!', 'sumosubscriptions' );
                        break;
                }
            }
        }
    }

    return $end_date;
}

/**
 * Display Subscription Expired Date.
 *
 * @param int $post_id The Subscription post ID
 */
function sumo_display_expired_date( $post_id ) {
    $expired_date = get_post_meta( $post_id, 'sumo_get_sub_exp_date', true );
    return $expired_date ? sumo_display_subscription_date( $expired_date ) : __( 'Not yet Expired', 'sumosubscriptions' );
}

/**
 * Display Subscription Last Payment Date.
 *
 * @param int $post_id The Subscription post ID
 */
function sumo_display_last_payment_date( $post_id ) {
    $last_payment_date = get_post_meta( $post_id, 'sumo_get_last_payment_date', true );

    if ( $last_payment_date && 'Trial' !== get_post_meta( $post_id, 'sumo_get_status', true ) ) {
        return sumo_display_subscription_date( $last_payment_date );
    }

    return '---';
}

/**
 * Display Subscription Plan.
 *
 * @param int $post_id The Subscription post ID
 * @param int $product_id The Product post ID
 * @param int $addon_fee may be Addon fee available in Cart/Checkout.
 * @param bool $apply_price_range
 * @return string
 */
function sumo_display_subscription_plan( $post_id = 0, $product_id = 0, $addon_fee = 0, $apply_price_range = false, $subscription_plan = array() ) {
    $subscription_plan = empty( $subscription_plan ) ? sumo_get_subscription_plan( $post_id, $product_id ) : $subscription_plan;

    if ( '1' !== $subscription_plan[ 'subscription_status' ] || '' === $subscription_plan[ 'subscription_fee' ] ) {
        return '';
    }

    $subscription_plan_string              = '<span class="sumosubs_plan_message">';
    $synced_prorated_date                  = '';
    $synced_next_payment_date              = '';
    $prorated_fee                          = '';
    $subscription_sale_fee                 = '';
    $subscription_fee_prorated_in_cart     = false;
    $is_trial_enabled                      = '1' === $subscription_plan[ 'trial_status' ];
    $is_signup_enabled                     = '1' === $subscription_plan[ 'signup_status' ];
    $is_synced                             = '1' === $subscription_plan[ 'synchronization_status' ];
    $is_paid_trial_enabled                 = 'paid' === $subscription_plan[ 'trial_type' ];
    $duration_length                       = absint( $subscription_plan[ 'subscription_duration_value' ] );
    $installments                          = absint( $subscription_plan[ 'subscription_recurring' ] );
    $qty                                   = absint( $subscription_plan[ 'subscription_product_qty' ] );
    $initial_fee                           = floatval( $subscription_plan[ 'signup_fee' ] );
    $signup_fee                            = floatval( $subscription_plan[ 'signup_fee' ] );
    $trial_fee                             = floatval( $subscription_plan[ 'trial_fee' ] );
    $subscription_fee                      = floatval( $subscription_plan[ 'subscription_fee' ] );
    $apply_onetime_fee_on_subscription_fee = ! SUMOSubs_Order_Subscription::is_subscribed( $post_id );

    if ( $post_id ) {
        $parent_order = wc_get_order( get_post_meta( $post_id, 'sumo_get_parent_order_id', true ) );
        $currency     = $parent_order ? $parent_order->get_currency() : '';
    } else {
        $currency = '';
    }

    if ( is_numeric( $addon_fee ) && $addon_fee ) {
        $subscription_fee += floatval( $addon_fee );
    }

    if ( $qty ) {
        $initial_fee *= $qty;
        $trial_fee   *= $qty;
    }

    if ( $is_synced && $product_id ) {
        if ( ( is_product() || is_cart() || is_checkout() ) && SUMOSubs_Synchronization::is_subscription_synced( $product_id ) ) {
            $synced_next_payment_date = SUMOSubs_Synchronization::get_initial_payment_date( $product_id, true );
        }
        if ( ( is_cart() || is_checkout() ) && SUMOSubs_Synchronization::cart_item_contains_sync( $product_id ) ) {
            $sync                              = SUMOSubs_Synchronization::get_synced( $product_id );
            $subscription_fee_prorated_in_cart = 'prorate' === $sync[ 'initial_payment_charge_type' ] && ! $sync[ 'awaiting_initial_payment' ] && is_numeric( $sync[ 'prorated_subscription_fee' ] );

            if ( $subscription_fee_prorated_in_cart ) {
                $apply_onetime_fee_on_subscription_fee = false;
                $synced_prorated_date                  = SUMOSubs_Synchronization::get_prorated_date_till( $product_id );
                $prorated_fee                          = SUMOSubs_Synchronization::get_prorated_fee( $product_id, 0, true );
            } else if ( 'free' === $sync[ 'initial_payment_charge_type' ] ) {
                $apply_onetime_fee_on_subscription_fee = false;
            }
        }
    }

    if (
            ! $post_id &&
            ( SUMOSubs_Switcher::is_switcher_page() || SUMOSubs_Switcher::is_subscription_switched( $product_id ) )
    ) {
        $switched = true;

        if ( is_cart() || is_checkout() ) {
            $is_signup_enabled = false;
        }
    }

    if ( $is_signup_enabled ) {
        if ( $is_trial_enabled ) {
            $initial_fee += 0;

            if ( $is_paid_trial_enabled ) {
                $initial_fee += $trial_fee;
            }
        } else if ( $apply_onetime_fee_on_subscription_fee ) {
            $initial_fee += $subscription_fee;
        }
    }

    if ( $post_id && SUMOSubs_Resubscribe::is_subscription_resubscribed( $product_id ) ) {
        $subscription_fee = floatval( $subscription_plan[ 'subscription_fee' ] ) / $qty;
    }

    if ( $post_id && sumosubs_recurring_fee_has_changed( $post_id ) ) {
        $subscription_fee *= $qty;
    }

    if ( ! $is_signup_enabled && ! $is_trial_enabled ) {
        /**
         * Get subscription price.
         * 
         * @since 1.0
         */
        $subscription_fee = apply_filters( 'sumosubscriptions_product_price_msg_for_subsc_fee', $subscription_fee, $product_id, wc_get_product( $product_id ) );
    }

    $subscription_product = $subscription_plan[ 'subscription_product_id' ] > 0 ? wc_get_product( $subscription_plan[ 'subscription_product_id' ] ) : false;
    if ( $subscription_product ) {
        $initial_fee      = wc_get_price_to_display( $subscription_product, array( 'qty' => 1, 'price' => $initial_fee ) );
        $signup_fee       = wc_get_price_to_display( $subscription_product, array( 'qty' => 1, 'price' => $signup_fee ) );
        $subscription_fee = wc_get_price_to_display( $subscription_product, array( 'qty' => 1, 'price' => $subscription_fee ) );
        $trial_fee        = wc_get_price_to_display( $subscription_product, array( 'qty' => 1, 'price' => $trial_fee ) );
        $prorated_fee     = wc_get_price_to_display( $subscription_product, array( 'qty' => 1, 'price' => $prorated_fee ) );
    }

    //may be Sale price available
    if ( is_numeric( $subscription_plan[ 'subscription_sale_fee' ] ) && empty( $switched ) ) {
        $regular_subscription_fee = $subscription_product ? wc_get_price_to_display( $subscription_product, array( 'qty' => 1, 'price' => $subscription_plan[ 'subscription_regular_fee' ] ) ) : $subscription_plan[ 'subscription_regular_fee' ];
        $subscription_sale_fee    = wc_format_sale_price( $regular_subscription_fee, $subscription_fee );
    }

    /**
     * Get shortcode => content
     * 
     * @since 1.0
     */
    $shortcode_content = apply_filters( 'sumosubscriptions_get_subscription_plan_shortcode_content', array(
        '[sumo_signup_fee]'                     => sumo_format_subscription_price( $initial_fee, array( 'currency' => $currency ) ), //deprecated since 6.3
        '[sumo_initial_fee]'                    => sumo_format_subscription_price( $initial_fee, array( 'currency' => $currency ) ),
        '[sumo_signup_fee_only]'                => sumo_format_subscription_price( $signup_fee, array( 'currency' => $currency ) ),
        '[sumo_subscription_fee]'               => '' === $subscription_sale_fee ? sumo_format_subscription_price( $subscription_fee, array( 'currency' => $currency ) ) : $subscription_sale_fee,
        '[sumo_prorated_fee]'                   => sumo_format_subscription_price( $prorated_fee, array( 'currency' => $currency ) ),
        '[sumo_trial_fee]'                      => $is_paid_trial_enabled ? sumo_format_subscription_price( $trial_fee, array( 'currency' => $currency ) ) : SUMOSubs_Admin_Options::get_option( 'free_trial_strings' ),
        '[sumo_subscription_period]'            => sumo_format_subscription_duration_period( $subscription_plan[ 'subscription_duration' ], $duration_length ),
        '[sumo_trial_period]'                   => sumo_format_subscription_duration_period( $subscription_plan[ 'trial_duration' ], $subscription_plan[ 'trial_duration_value' ] ),
        '[sumo_subscription_period_value]'      => $duration_length > 1 ? $duration_length : '',
        '[sumo_trial_period_value]'             => $subscription_plan[ 'trial_duration_value' ],
        '[sumo_synchronized_prorated_date]'     => $synced_prorated_date,
        '[sumo_synchronized_next_payment_date]' => $synced_next_payment_date,
        '[sumo_instalment_period]'              => sumo_format_subscription_duration_period( '', $installments ),
        '[sumo_instalment_period_value]'        => $installments,
            ), $post_id, $product_id, $subscription_plan );

    if ( is_array( $shortcode_content ) ) {

        do_action_ref_array( 'sumosubscriptions_before_applying_subscription_plan_shortcode', array( &$shortcode_content, $post_id, $product_id, $subscription_plan ) );

        $find_values    = array_keys( $shortcode_content );
        $replace_values = array_values( $shortcode_content );

        /** Get Shortcode Message Content * */
        //may be display product level variation price range
        if ( $apply_price_range && $subscription_product && in_array( $subscription_product->get_type(), array( 'variation', 'variable' ) ) ) {
            $subscription_plan_string .= str_replace( $find_values, $replace_values, SUMOSubs_Admin_Options::get_option( 'variable_product_price_strings' ) ) . ' ';
        }
        //may be display Signup fee
        if ( $is_signup_enabled ) {
            $subscription_plan_string .= str_replace( $find_values, $replace_values, SUMOSubs_Admin_Options::get_option( 'signup_fee_strings' ) ) . ' ';
        }
        //may be display prorated fee with/without for Synced product in cart
        if ( $is_synced && $subscription_fee_prorated_in_cart ) {
            if ( 'first-payment' === SUMOSubs_Synchronization::$apply_prorated_fee_on ) {
                $subscription_plan_string .= str_replace( $find_values, $replace_values, SUMOSubs_Admin_Options::get_option( 'synced_prorated_amount_strings' ) ) . ' ';
            } else {
                $subscription_plan_string .= str_replace( $find_values, $replace_values, SUMOSubs_Admin_Options::get_option( 'synced_prorated_amount_during_first_renewal_strings' ) ) . ' ';
            }
        }
        //may be display trial fee
        if ( $is_trial_enabled ) {
            $subscription_plan_string .= str_replace( $find_values, $replace_values, SUMOSubs_Admin_Options::get_option( 'trial_fee_and_duration_strings' ) ) . ' ';
        }
        //may be display Subscription fee
        $subscription_plan_string .= str_replace( $find_values, $replace_values, SUMOSubs_Admin_Options::get_option( 'subscription_price_and_duration_strings' ) ) . ' ';

        //may be display limited installments
        if ( $installments > 0 ) {
            $subscription_plan_string .= str_replace( $find_values, $replace_values, SUMOSubs_Admin_Options::get_option( 'subscription_length_strings' ) );
        }
        //may be display Synced plan
        if ( $is_synced && is_product() ) {
            $subscription_plan_string .= str_replace( $find_values, $replace_values, SUMOSubs_Admin_Options::get_option( 'synced_plan_strings' ) ) . ' ';
        }

        do_action_ref_array( 'sumosubscriptions_subscription_plan_shortcode_applied', array( &$subscription_plan_string, $post_id, $product_id, $subscription_plan ) );
    }

    $subscription_plan_string .= '</span>';
    return $subscription_plan_string;
}

/**
 * Backward Compatibility for Displaying Subscription Plan.
 *
 * @param int $product_id The Product post ID
 * @return string
 */
function sumo_display_susbcription_plan_message( $product_id ) {
    return sumo_display_subscription_plan( 0, $product_id );
}

/**
 * Display WC search field with respect to products and variations/customer
 * 
 * @param array $args
 * @param bool $echo
 */
function sumosubs_wc_search_field( $args = array(), $echo = true ) {
    $args = wp_parse_args( $args, array(
        'class'       => '',
        'id'          => '',
        'name'        => '',
        'type'        => '',
        'action'      => '',
        'title'       => '',
        'placeholder' => '',
        'css'         => 'width: 50%;',
        'multiple'    => true,
        'allow_clear' => true,
        'selected'    => true,
        'options'     => array(),
            ) );

    ob_start();
    if ( '' !== $args[ 'title' ] ) {
        ?>
        <tr valign="top">
            <th class="titledesc" scope="row">
                <label for="<?php echo esc_attr( $args[ 'id' ] ); ?>"><?php echo esc_attr( $args[ 'title' ] ); ?></label>
            </th>
            <td class="forminp forminp-select">
                <?php
            }
            ?>
            <select <?php echo $args[ 'multiple' ] ? 'multiple="multiple"' : ''; ?> name="<?php echo esc_attr( '' !== $args[ 'name' ] ? $args[ 'name' ] : $args[ 'id' ]  ); ?>[]" id="<?php echo esc_attr( $args[ 'id' ] ); ?>" class="<?php echo esc_attr( $args[ 'class' ] ); ?>" data-action="<?php echo esc_attr( $args[ 'action' ] ); ?>" data-placeholder="<?php echo esc_attr( $args[ 'placeholder' ] ); ?>" style="<?php echo esc_attr( $args[ 'css' ] ); ?>">
                <?php
                if ( is_array( $args[ 'options' ] ) ) {
                    foreach ( $args[ 'options' ] as $id ) {
                        $option_value = '';

                        switch ( $args[ 'type' ] ) {
                            case 'product':
                                $product = wc_get_product( $id );
                                if ( $product ) {
                                    $option_value = wp_kses_post( $product->get_formatted_name() );
                                }
                                break;
                            case 'customer':
                                $user = get_user_by( 'id', $id );
                                if ( $user ) {
                                    $option_value = esc_html( esc_html( $user->display_name ) . '(#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')' );
                                }
                                break;
                        }

                        if ( $option_value ) {
                            ?>
                            <option value="<?php echo esc_attr( $id ); ?>" <?php echo $args[ 'selected' ] ? 'selected="selected"' : ''; ?>><?php echo wp_kses_post( $option_value ); ?></option>
                            <?php
                        }
                    }
                }
                ?>
            </select>
            <?php
            if ( '' !== $args[ 'title' ] ) {
                ?>
            </td>
        </tr>
        <?php
    }

    if ( $echo ) {
        ob_end_flush();
    } else {
        return ob_get_clean();
    }
}

/**
 * Display WC enhanced select field.
 * 
 * @param array $args
 * @param bool $echo
 */
function sumosubs_wc_enhanced_select_field( $args = array(), $echo = true ) {
    $args = wp_parse_args( $args, array(
        'class'    => '',
        'id'       => '',
        'name'     => '',
        'multiple' => true,
        'selected' => array(),
        'options'  => array(),
            ) );

    ob_start();
    ?>
    <select <?php echo $args[ 'multiple' ] ? 'multiple="multiple"' : ''; ?> class="<?php echo esc_attr( $args[ 'class' ] ); ?> wc-enhanced-select" name="<?php echo esc_attr( '' !== $args[ 'name' ] ? $args[ 'name' ] : $args[ 'id' ]  ); ?>[]" id="<?php echo esc_attr( $args[ 'id' ] ); ?>">
        <?php
        foreach ( $args[ 'options' ] as $key => $val ) {
            ?>
            <option value="<?php echo esc_attr( $key ); ?>" 
            <?php
            if ( is_array( $args[ 'selected' ] ) ) {
                selected( in_array( $key, $args[ 'selected' ] ), true );
            } else {
                selected( $args[ 'selected' ], $key );
            }
            ?>
                    ><?php echo esc_html( $val ); ?></option>
                <?php } ?>
    </select>
    <?php
    if ( $echo ) {
        ob_end_flush();
    } else {
        return ob_get_clean();
    }
}
