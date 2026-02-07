<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

include_once 'sumosubs-subscription-functions.php';
include_once 'sumosubs-conditional-functions.php';
include_once 'sumosubs-formatting-functions.php';
include_once 'sumosubs-ui-functions.php';
include_once 'sumosubs-template-functions.php';

/**
 * Get Subscription Next Renewal/Due date.
 *
 * @param int $post_id
 * @param int $product_id
 * @param array $args
 * @return mixed
 */
function sumosubs_get_next_payment_date( $post_id = 0, $product_id = 0, $args = array() ) {
    $next_payment_time = 0;
    $subscription_plan = sumo_get_subscription_plan( $post_id, $product_id );

    if ( is_numeric( $post_id ) && $post_id ) {
        $use_trial = 'Trial' === get_post_meta( $post_id, 'sumo_get_status', true ) || in_array( get_post_meta( $post_id, 'sumo_subscription_awaiting_status', true ), array( 'free-trial', 'paid-trial', 'Trial' ) );
    } else {
        $use_trial = '1' === $subscription_plan[ 'trial_status' ];
    }

    $args = wp_parse_args( $args, array(
        'from_when'           => sumo_get_subscription_timestamp(),
        'initial_payment'     => false,
        'paused_to_resume'    => false,
        'due_date_exceeds'    => false,
        'get_as_timestamp'    => false,
        'use_trial_if_exists' => true,
            ) );

    $trial_enabled = $use_trial && $args[ 'use_trial_if_exists' ] ? true : false;

    //May be subscription is gonna resume
    if ( $post_id && $args[ 'paused_to_resume' ] ) {
        $duration_gap_on_paused = get_post_meta( $post_id, 'sumo_time_gap_on_paused', true );

        if ( isset( $duration_gap_on_paused[ 'current_time_on_paused' ] ) && isset( $duration_gap_on_paused[ 'previous_due_date' ] ) ) {
            $previous_due_date = sumo_get_subscription_timestamp( $duration_gap_on_paused[ 'previous_due_date' ] );
            $paused_time       = $duration_gap_on_paused[ 'current_time_on_paused' ];

            //Get Next Due Time after the Subscription is Resumed.
            if ( $args[ 'due_date_exceeds' ] ) {
                $next_payment_time = sumo_get_subscription_timestamp() + absint( $previous_due_date - $paused_time );
            } else {
                $next_payment_time = absint( ( sumo_get_subscription_timestamp() - $paused_time ) + $previous_due_date );
            }
        }
    } else if ( SUMOSubs_Synchronization::is_subscription_synced( $post_id ? $post_id : $product_id ) ) {
        $next_payment_time = SUMOSubs_Synchronization::get_sync_time( $trial_enabled, $args[ 'initial_payment' ], $args[ 'from_when' ] );
        //May be it is normal subscription
    } else if ( is_numeric( $args[ 'from_when' ] ) && $args[ 'from_when' ] > 0 ) {
        //May be trial is on going for the subscription
        if ( $trial_enabled ) {
            $plan_period = sumo_format_subscription_cyle( $subscription_plan[ 'trial_duration_value' ] . ' ' . $subscription_plan[ 'trial_duration' ] );
        } else {
            $plan_period = sumo_format_subscription_cyle( $subscription_plan[ 'subscription_duration_value' ] . ' ' . $subscription_plan[ 'subscription_duration' ] );
        }
        $next_payment_time = sumo_get_subscription_timestamp( "+{$plan_period}", $args[ 'from_when' ] ); //Get Next Due Time.
    }

    if ( ! $args[ 'get_as_timestamp' ] ) {
        $next_payment_time = sumo_get_subscription_date( $next_payment_time );
    }

    /**
     * Get the next due date.
     * 
     * @since 1.0
     */
    return apply_filters( 'sumosubscriptions_get_next_payment_date', $next_payment_time, $post_id, $product_id, $trial_enabled, $args );
}

/**
 * Resume Subscription if gets Paused by Admin/Subscriber
 *
 * @param int $post_id The Subscription post ID
 * @param string $resume_by Admin | Subscriber
 * @return bool 
 */
function sumo_resume_subscription( $post_id, $resume_by = '' ) {
    if ( in_array( get_post_meta( $post_id, 'sumo_get_status', true ), array( 'Cancelled', 'Failed', 'Expired' ) ) ) {
        return false;
    }

    /**
     * Before the subscription gets resumed.
     * 
     * @since 1.0
     */
    do_action( 'sumosubscriptions_before_resume_subscription', $post_id );

    update_post_meta( $post_id, 'sumo_subscription_resume_requested_by', $resume_by );

    switch ( $resume_by ) {
        case 'subscriber':
            $note_for_trial  = __( 'Trial resumed by user.', 'sumosubscriptions' );
            $note_for_active = __( 'Subscription resumed by user.', 'sumosubscriptions' );
            break;
        case 'admin-in-bulk':
            $note_for_trial  = __( 'Subscription status updated by bulk action: Trial resumed by admin.', 'sumosubscriptions' );
            $note_for_active = __( 'Subscription status updated by bulk action: Subscription resumed by admin.', 'sumosubscriptions' );
            break;
        case 'admin':
            $note_for_trial  = __( 'Trial resumed by admin.', 'sumosubscriptions' );
            $note_for_active = __( 'Subscription resumed by admin.', 'sumosubscriptions' );
            break;
        default:
            $note_for_trial  = __( 'Trial resumed.', 'sumosubscriptions' );
            $note_for_active = __( 'Subscription resumed.', 'sumosubscriptions' );
            break;
    }

    //On Resume, check the previous status was on Trial
    if ( 'Trial' === get_post_meta( $post_id, 'sumo_check_trial_status', true ) ) {
        update_post_meta( $post_id, 'sumo_get_status', 'Trial' );

        sumo_add_subscription_note( $note_for_trial, $post_id, sumo_note_status( 'Active' ), __( 'Trial resumed', 'sumosubscriptions' ) );
    } else {
        delete_post_meta( $post_id, 'sumo_check_trial_status' );
        update_post_meta( $post_id, 'sumo_get_status', 'Active' );

        sumo_add_subscription_note( $note_for_active, $post_id, sumo_note_status( 'Active' ), __( 'Subscription resumed', 'sumosubscriptions' ) );
    }
    delete_post_meta( $post_id, 'sumo_subscription_auto_resume_scheduled_on' );

    $cron_event = new SUMOSubs_Cron_Event( $post_id );
    $cron_event->unset_events( 'automatic_resume' );

    SUMOSubs_Order::set_next_payment_date( $post_id );

    /**
     * After the subscription is resumed.
     * 
     * @since 1.0
     */
    do_action( 'sumosubscriptions_subscription_resumed', $post_id );
    return true;
}

/**
 * Pause Subscription by Admin/Subscriber.
 *
 * @param int $post_id The Subscription post ID
 * @param string $note
 * @param string $pause_by Admin | Subscriber
 * @return bool 
 */
function sumo_pause_subscription( $post_id, $note = '', $pause_by = '' ) {
    if ( in_array( get_post_meta( $post_id, 'sumo_get_status', true ), array( 'Cancelled', 'Failed', 'Expired' ) ) ) {
        return false;
    }

    /**
     * Before the subscription gets paused.
     * 
     * @since 1.0
     */
    do_action( 'sumosubscriptions_before_pause_subscription', $post_id );

    //Update for checking Trial status on Subscription Resume.
    update_post_meta( $post_id, 'sumo_check_trial_status', get_post_meta( $post_id, 'sumo_get_status', true ) );
    update_post_meta( $post_id, 'sumo_get_status', 'Pause' ); //Update Subscription status as Paused.
    update_post_meta( $post_id, 'sumo_subscription_pause_requested_by', $pause_by );

    //Valid alone for Subscribers
    if ( 'subscriber' === $pause_by ) {
        $previous_count = absint( get_post_meta( $post_id, 'sumo_no_of_pause_count', true ) );
        $pause_count    = 0;

        if ( absint( SUMOSubs_Admin_Options::get_option( 'max_pause_times_for_subscribers' ) > 0 ) ) {
            $pause_count = 0 === $previous_count ? 1 : $previous_count + 1;
        }

        update_post_meta( $post_id, 'sumo_no_of_pause_count', $pause_count );
    }

    $cron_event = new SUMOSubs_Cron_Event( $post_id );
    $cron_event->unset_events( array(
        'create_renewal_order',
        'notify_overdue',
        'notify_suspend',
        'notify_cancel',
        'notify_expire',
        'automatic_pay',
        'switch_to_manual_pay_mode',
        'notify_expiry_reminder',
    ) );

    update_post_meta( $post_id, 'sumo_time_gap_on_paused', array(
        'current_time_on_paused' => sumo_get_subscription_timestamp(),
        'previous_due_date'      => get_post_meta( $post_id, 'sumo_get_next_payment_date', true ),
    ) );
    update_post_meta( $post_id, 'sumo_get_next_payment_date', 'N/A' );

    if ( '' === $note ) {
        switch ( $pause_by ) {
            case 'admin':
                $note = __( 'Subscription paused by admin.', 'sumosubscriptions' );
                break;
            case 'admin-in-bulk':
                $note = __( 'Subscription status updated by bulk action: Subscription paused by admin.', 'sumosubscriptions' );
                break;
            case 'subscriber':
                $note = __( 'Subscription paused by user.', 'sumosubscriptions' );
                break;
            default:
                $note = __( 'Subscription paused.', 'sumosubscriptions' );
                break;
        }
    }

    sumo_add_subscription_note( $note, $post_id, sumo_note_status( 'Pause' ), __( 'Subscription paused', 'sumosubscriptions' ) );
    sumo_trigger_subscription_email( 'subscription_paused', 0, $post_id );

    /**
     * After the subscription is paused.
     * 
     * @since 1.0
     */
    do_action( 'sumosubscriptions_subscription_paused', $post_id );
    return true;
}

/**
 * Cancel Subscription.
 * 
 * @param int $post_id The Subscription post ID
 * @param array $args
 * @return bool
 */
function sumosubs_cancel_subscription( $post_id, $args = array() ) {
    $subscription_status = get_post_meta( $post_id, 'sumo_get_status', true );
    $args                = wp_parse_args( $args, array(
        'request_by'    => 'system',
        'when'          => 'immediate',
        'schedule_date' => '',
        'note'          => '',
            ) );

    if ( in_array( $subscription_status, array( 'Cancelled', 'Failed', 'Expired' ) ) ) {
        return false;
    }

    update_post_meta( $post_id, 'sumo_subscription_cancel_requested_by', $args[ 'request_by' ] );
    update_post_meta( $post_id, 'sumo_subscription_requested_cancel_method', $args[ 'when' ] );

    if ( 'end_of_billing_cycle' === $args[ 'when' ] ) {
        $renewal_order_id    = absint( get_post_meta( $post_id, 'sumo_get_renewal_id', true ) );
        $next_payment_date   = get_post_meta( $post_id, 'sumo_get_next_payment_date', true );
        $persistent_due_date = '--' === $next_payment_date ? get_post_meta( $post_id, 'sumo_get_saved_due_date', true ) : $next_payment_date;

        if ( in_array( $subscription_status, array( 'Trial', 'Active' ) ) ) {
            update_post_meta( $post_id, 'sumo_subscription_previous_status', $subscription_status );
        }

        delete_post_meta( $post_id, 'sumo_subscription_cancellation_scheduled_on' );
        update_post_meta( $post_id, 'sumo_get_status', 'Pending_Cancellation' );
        update_post_meta( $post_id, 'sumo_get_sub_end_date', $persistent_due_date );

        sumo_add_subscription_note( __( 'Subscription cancel request submitted and it will be cancelled automatically at the end of billing cycle.', 'sumosubscriptions' ), $post_id, 'success', __( 'Cancel at the end of billing cycle', 'sumosubscriptions' ) );
        sumo_trigger_subscription_email( 'subscription_cancel_request_submitted', 0, $post_id );

        $cron_event = new SUMOSubs_Cron_Event( $post_id );
        $cron_event->unset_events();
        $cron_event->schedule_cancel_notify( $renewal_order_id, 0, $persistent_due_date, true );

        /**
         * After the subscription is in pending cancellation.
         * 
         * @since 1.0
         */
        do_action( 'sumosubscriptions_subscription_in_pending_cancellation', $post_id, $args[ 'when' ] );
    } else if ( 'scheduled_date' === $args[ 'when' ] ) {
        $renewal_order_id = absint( get_post_meta( $post_id, 'sumo_get_renewal_id', true ) );

        if ( in_array( $subscription_status, array( 'Trial', 'Active' ) ) ) {
            update_post_meta( $post_id, 'sumo_subscription_previous_status', $subscription_status );
        }

        update_post_meta( $post_id, 'sumo_get_status', 'Pending_Cancellation' );
        update_post_meta( $post_id, 'sumo_subscription_cancellation_scheduled_on', $args[ 'schedule_date' ] );
        update_post_meta( $post_id, 'sumo_get_sub_end_date', $args[ 'schedule_date' ] );

        /* translators: 1: scheduled date */
        sumo_add_subscription_note( sprintf( __( 'Subscription cancel request submitted and it will be cancelled automatically on the scheduled date <b>%s</b>.', 'sumosubscriptions' ), $args[ 'schedule_date' ] ), $post_id, 'success', __( 'Cancel on scheduled date', 'sumosubscriptions' ) );
        sumo_trigger_subscription_email( 'subscription_cancel_request_submitted', 0, $post_id );

        $cron_event = new SUMOSubs_Cron_Event( $post_id );
        $cron_event->unset_events();
        $cron_event->schedule_cancel_notify( $renewal_order_id, 0, $args[ 'schedule_date' ], true );

        /**
         * After the subscription is in pending cancellation.
         * 
         * @since 1.0
         */
        do_action( 'sumosubscriptions_subscription_in_pending_cancellation', $post_id, $args[ 'when' ] );
    } else {
        /**
         * Before the subscription gets cancelled.
         * 
         * @since 1.0
         */
        do_action( 'sumosubscriptions_before_cancel_subscription', $post_id );

        update_post_meta( $post_id, 'sumo_get_status', 'Cancelled' );
        update_post_meta( $post_id, 'sumo_get_next_payment_date', '--' );
        update_post_meta( $post_id, 'sumo_get_sub_end_date', sumo_get_subscription_date() );
        delete_post_meta( $post_id, 'sumo_subscription_awaiting_status' );

        $cron_event = new SUMOSubs_Cron_Event( $post_id );
        $cron_event->unset_events();

        if ( '' === $args[ 'note' ] ) {
            switch ( $args[ 'request_by' ] ) {
                case 'admin':
                    $args[ 'note' ] = __( 'Subscription cancelled by admin.', 'sumosubscriptions' );
                    break;
                case 'subscriber':
                    $args[ 'note' ] = __( 'Subscription cancelled by user.', 'sumosubscriptions' );
                    break;
                default:
                    $args[ 'note' ] = __( 'Subscription cancelled.', 'sumosubscriptions' );
                    break;
            }
        }

        SUMOSubs_Resubscribe::may_be_unset_resubscribed_associated_subscriptions( $post_id );
        sumo_add_subscription_note( $args[ 'note' ], $post_id, 'success', __( 'Subscription cancelled', 'sumosubscriptions' ) );
        sumo_trigger_subscription_email( 'subscription_cancelled', 0, $post_id );

        /**
         * After the subscription is cancelled.
         * 
         * @since 1.0
         */
        do_action( 'sumosubscriptions_subscription_cancelled', $post_id );
    }

    return true;
}

/**
 * Expire Subscription.
 *
 * @param int $post_id The Subscription post ID
 * @param string $expiry_on
 * @param boolean $unschedule_crons
 * @return bool 
 */
function sumo_expire_subscription( $post_id, $expiry_on = '', $unschedule_crons = true ) {
    if ( in_array( get_post_meta( $post_id, 'sumo_get_status', true ), array( 'Cancelled', 'Failed', 'Expired' ) ) ) {
        return false;
    }

    /**
     * Before the subscription gets expired.
     * 
     * @since 1.0
     */
    do_action( 'sumosubscriptions_before_expire_subscription', $post_id );

    $sub_end_date = sumo_get_subscription_date();
    update_post_meta( $post_id, 'sumo_get_status', 'Expired' );
    update_post_meta( $post_id, 'sumo_get_saved_due_date', '' === $expiry_on ? get_post_meta( $post_id, 'sumo_get_next_payment_date', true ) : $expiry_on ); //Save Expiry date.
    update_post_meta( $post_id, 'sumo_get_next_payment_date', '--' );
    update_post_meta( $post_id, 'sumo_get_sub_end_date', $sub_end_date );
    update_post_meta( $post_id, 'sumo_get_sub_exp_date', $sub_end_date );
    delete_post_meta( $post_id, 'sumo_subscription_awaiting_status' );

    if ( $unschedule_crons ) {
        $cron_event = new SUMOSubs_Cron_Event( $post_id );
        $cron_event->unset_events();
    }

    SUMOSubs_Resubscribe::may_be_unset_resubscribed_associated_subscriptions( $post_id );
    sumo_add_subscription_note( __( 'Subscription expired', 'sumosubscriptions' ), $post_id, sumo_note_status( 'Expired' ), __( 'Subscription expired', 'sumosubscriptions' ) );
    sumo_trigger_subscription_email( 'subscription_expired', 0, $post_id );

    /**
     * After the subscription is expired.
     * 
     * @since 1.0
     */
    do_action( 'sumosubscriptions_subscription_expired', $post_id );
    return true;
}

/**
 * Cancel Subscription by Admin/Subscriber.
 *
 * @deprecated since version 14.6
 * @return bool 
 */
function sumo_cancel_subscription( $post_id, $note = '', $cancel_by = '' ) {
    return sumosubs_cancel_subscription( $post_id, array(
        'request_by' => $cancel_by,
        'note'       => $note,
            ) );
}

/**
 * Fetch responsed data via PHP cURL API.
 *
 * @param string $url PayPal Endpoint URL
 * @param array $headers
 * @param array $data
 * @return object from paypal
 */
function sumo_get_cURL_response( $url, $headers, $data ) {
    $ch         = curl_init();
    $get_header = array();

    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
    curl_setopt( $ch, CURLOPT_SSLVERSION, 6 );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );

    if ( ! empty( $headers ) ) {
        foreach ( $headers as $name => $value ) {
            $get_header[] = "{$name}: $value";
        }
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $get_header );
    } else {
        curl_setopt( $ch, CURLOPT_HEADER, false );
    }

    $response = curl_exec( $ch );

    curl_close( $ch );

    return $response;
}

/**
 * Get Subscription Recurring fee. 
 *
 * @param int $post_id The Subscription post ID
 * @param array $order_item may be Parent Order items
 * @param int $order_item_id may be Parent Order item id
 * @param boolean $calc_with_qty calculate line total with the respective item qty
 * @return float|int
 */
function sumo_get_recurring_fee( $post_id, $order_item = array(), $order_item_id = 0, $calc_with_qty = true ) {
    $subscription_meta = sumo_get_subscription_meta( $post_id );
    $item_total        = 0;

    if ( isset( $order_item[ 'product_id' ] ) ) {
        $product_id  = $order_item[ 'variation_id' ] > 0 ? $order_item[ 'variation_id' ] : $order_item[ 'product_id' ];
        $product_qty = is_numeric( $order_item[ 'qty' ] ) ? $order_item[ 'qty' ] : 1;
    } else {
        $product_id  = ! empty( $subscription_meta[ 'productid' ] ) ? $subscription_meta[ 'productid' ] : 0;
        $product_qty = ! empty( $subscription_meta[ 'product_qty' ] ) ? absint( $subscription_meta[ 'product_qty' ] ) : 1;
    }

    if ( ! $product_qty ) {
        $product_qty = 1;
    }

    if ( SUMOSubs_Order_Subscription::is_subscribed( $post_id ) ) {
        if ( isset( $subscription_meta[ 'sale_fee' ] ) && is_numeric( $subscription_meta[ 'sale_fee' ] ) ) {
            $item_total = $subscription_meta[ 'sale_fee' ];
        } else if ( isset( $subscription_meta[ 'subfee' ] ) && is_numeric( $subscription_meta[ 'subfee' ] ) ) {
            $item_total = $subscription_meta[ 'subfee' ];
        }

        if ( is_array( $product_id ) ) {
            $pending_renewal_order_id = absint( get_post_meta( $post_id, 'sumo_get_renewal_id', true ) );
            $pending_renewal_order    = $pending_renewal_order_id > 0 ? wc_get_order( $pending_renewal_order_id ) : false;

            if ( $pending_renewal_order ) {
                $item_total = $pending_renewal_order->get_total();
            }
        } else if ( isset( $subscription_meta[ 'item_fee' ][ $product_id ] ) ) {
            $item_total = $subscription_meta[ 'item_fee' ][ $product_id ];

            if ( 'new-price' === SUMOSubs_Admin_Options::get_option( 'update_old_subscription_price_to' ) ) {
                $product = wc_get_product( $product_id );

                if ( $product ) {
                    $item_total = $product->is_on_sale() ? $product->get_sale_price() : $product->get_regular_price();
                }
            }

            $item_total *= ( $calc_with_qty ? $product_qty : 1 );
        }
        //May be Admin had set custom subscription fee for this subscription.
    } else if ( sumosubs_recurring_fee_has_changed( $post_id ) ) {
        $item_total = floatval( wc_format_decimal( get_post_meta( $post_id, 'sumo_get_updated_renewal_fee', true ) ) );
    } else {
        if ( 'new-price' === SUMOSubs_Admin_Options::get_option( 'update_old_subscription_price_to' ) ) {
            $subscription_meta = sumo_get_subscription_meta( 0, $product_id );
        }

        if ( isset( $subscription_meta[ 'sale_fee' ] ) && is_numeric( $subscription_meta[ 'sale_fee' ] ) ) {
            $item_total = $subscription_meta[ 'sale_fee' ];
        } else if ( isset( $subscription_meta[ 'subfee' ] ) && is_numeric( $subscription_meta[ 'subfee' ] ) ) {
            $item_total = $subscription_meta[ 'subfee' ];
        }

        $subscription_fee = $item_total;
        $item_total       *= ( $calc_with_qty ? $product_qty : 1 );

        //Calculate with Addon Amount if it is applicable in this Subscription
        if ( sumo_subscription_has_addon_amount( $post_id ) ) {
            $order_item_data = get_post_meta( $post_id, 'sumo_subscription_parent_order_item_data', true );

            if ( isset( $order_item_data[ $order_item_id ][ 'addon' ] ) && $order_item_data[ $order_item_id ][ 'addon' ] > 0 ) {
                $item_total = $subscription_fee + $order_item_data[ $order_item_id ][ 'addon' ];
                $item_total *= ( $calc_with_qty ? $product_qty : 1 );
            } else if ( ! $order_item && ! $order_item_id && is_array( $order_item_data ) ) {

                $item_total = 0;
                foreach ( $order_item_data as $_item ) {
                    if ( ! isset( $_item[ 'addon' ] ) ) {
                        continue;
                    }
                    $_item_qty = $calc_with_qty ? $_item[ 'qty' ] : 1;

                    if ( $_item[ 'addon' ] > 0 ) {
                        $item_total += ( ( $subscription_fee + $_item[ 'addon' ] ) * $_item_qty );
                    } else {
                        $item_total += ( $subscription_fee * $_item_qty );
                    }
                }
            }
        }
    }

    /**
     * Get renewal amount.
     * 
     * @since 1.0
     */
    return apply_filters( 'sumosubscriptions_renewal_item_total', $item_total, $product_id, $post_id );
}

/**
 * Save/Update the Subscription Order Payment Information.
 *
 * @param mixed $order The Order
 * @param array $args
 * @param int $product_id . To update custom index from the array.
 * @return boolean
 */
function sumo_save_subscription_payment_info( $order, $args = array(), $product_id = '' ) {
    $order = sumosubs_maybe_get_order_instance( $order );
    if ( ! $order ) {
        return;
    }

    $parent_order_id = sumosubs_get_parent_order_id( $order );
    $parent_order    = $parent_order_id == $order->get_id() ? $order : wc_get_order( $parent_order_id );
    if ( ! $parent_order ) {
        return;
    }

    $payment_info = $parent_order->get_meta( 'sumosubscription_payment_order_information', true );
    $args         = wp_parse_args( $args, array(
        'payment_type'   => '',
        'payment_method' => '',
        'payment_key'    => '',
        'profile_id'     => '',
            ) );

    if ( ! is_array( $payment_info ) ) {
        $payment_info = array();
    }

    if ( SUMOSubs_Order_Subscription::is_subscribed( 0, $parent_order, $order->get_customer_id() ) ) {
        $parent_order->update_meta_data( 'sumosubscription_payment_order_information', $args );
        $parent_order->save();
    } else {
        if ( is_numeric( $product_id ) && $product_id ) {
            $payment_info[ $product_id ] = array(
                'payment_type'   => $args[ 'payment_type' ],
                'payment_method' => $args[ 'payment_method' ],
                'payment_key'    => $args[ 'payment_key' ],
                'profile_id'     => $args[ 'profile_id' ],
            );
        } else {
            $subscription_products = sumo_pluck_subscription_products( $order );
            if ( $subscription_products ) {
                foreach ( $subscription_products as $_product_id ) {
                    $payment_info[ $_product_id ] = array(
                        'payment_type'   => $args[ 'payment_type' ],
                        'payment_method' => $args[ 'payment_method' ],
                        'payment_key'    => $args[ 'payment_key' ],
                        'profile_id'     => $args[ 'profile_id' ],
                    );
                }
            }
        }

        $parent_order->update_meta_data( 'sumosubscription_payment_order_information', $payment_info );
        $parent_order->save();
    }

    return true;
}

/**
 * Trigger Subscription Email.
 * 
 * @param string $template_key The Email template key
 * @param int $order_id The Order post ID
 * @param int $post_id The Subscription post ID || Parent Order post ID
 * @param boolean $manual True, may be Admin has manually triggered the Subscription email
 */
function sumo_trigger_subscription_email( $template_key, $order_id = 0, $post_id = 0, $manual = false ) {
    $post_id = absint( $post_id );

    if ( empty( $template_key ) || ( ! $post_id && ! $order_id ) ) {
        return false;
    }

    $order = false;
    if ( is_numeric( $order_id ) && $order_id > 0 ) {
        $order = wc_get_order( $order_id );
    }

    if ( ! $order ) {
        $order = wc_get_order( absint( get_post_meta( $post_id, 'sumo_get_renewal_id', true ) ) );
    }

    if ( ! $order ) {
        $order = wc_get_order( absint( get_post_meta( $post_id, 'sumo_get_parent_order_id', true ) ) );
    }

    if ( ! $order ) {
        return false;
    }

    if ( in_array( $template_key, array( 'subscription_order_completed', 'subscription_order_processing' ), true ) ) {
        $subscription_plan = sumo_get_subscription_plan( $post_id );
        $product_id        = isset( $subscription_plan[ 'subscription_product_id' ] ) ? $subscription_plan[ 'subscription_product_id' ] : '';

        if ( ! empty( $product_id ) && in_array( get_post_type( $product_id ), array( 'product', 'product_variation' ), true ) ) {
            $order_confirmation_emails = get_post_meta( $product_id, 'sumosubs_send_order_confirmation_email', true );

            if ( 'subscription_order_processing' === $template_key && isset( $order_confirmation_emails[ 'processing' ] ) && 'yes' !== $order_confirmation_emails[ 'processing' ] ) {
                return false;
            }

            if ( 'subscription_order_completed' === $template_key && isset( $order_confirmation_emails[ 'completed' ] ) && 'yes' !== $order_confirmation_emails[ 'completed' ] ) {
                return false;
            }
        }
    }

    $email = SUMOSubs_Emails::get_email( $template_key );
    if ( ! $email ) {
        return false;
    }

    $sent = $email->trigger( $order, $post_id, get_post_meta( $post_id, 'sumo_buyer_email', true ) );
    if ( ! $sent ) {
        return false;
    }

    /* translators: 1: email title 2: email recipient */
    $note = sprintf( __( '%1$s email has been sent to %2$s.', 'sumosubscriptions' ), $email->title, $email->recipient );
    /* translators: 1: email title */
    sumo_add_subscription_note( $note, $post_id, sumo_note_status( get_post_meta( $post_id, 'sumo_get_status', true ) ), sprintf( __( '%s email sent', 'sumosubscriptions' ), $email->title ) );
    return true;
}

/**
 * Get SUMO Subscriptions templates.
 *
 * @param string $template_name
 * @param array $args (default: array())
 * @param string $template_path (default: SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME_DIR)
 * @param string $default_path (default: SUMO_SUBSCRIPTIONS_TEMPLATE_PATH)
 */
function sumosubscriptions_get_template( $template_name, $args = array(), $template_path = SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME_DIR, $default_path = SUMO_SUBSCRIPTIONS_TEMPLATE_PATH ) {
    if ( ! $template_name ) {
        return;
    }

    wc_get_template( $template_name, $args, $template_path, $default_path );
}

/**
 * Change the subscription renewal price.
 * 
 * @param int $subscription_id
 * @param float $price 
 * @return bool 
 */
function sumo_change_subscription_renewal_price( $subscription_id, $price ) {
    $new_renewal_price = wc_format_decimal( $price );
    if ( ! is_numeric( $new_renewal_price ) ) {
        return false;
    }

    if ( SUMOSubs_Order_Subscription::is_subscribed( $subscription_id ) ) {
        return false;
    }

    $old_renewal_price = sumo_get_recurring_fee( $subscription_id, array(), 0, false );
    if ( "$new_renewal_price" == "$old_renewal_price" ) {
        return false;
    }

    $parent_order   = wc_get_order( get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ) );
    $currency       = $parent_order ? $parent_order->get_currency() : '';
    $payment_method = sumo_get_subscription_payment_method( $subscription_id );
    $payment_method = ! empty( $payment_method ) ? $payment_method : ( $parent_order ? $parent_order->get_payment_method() : '' );

    //Warning !! Do not update the renewal fee. Preapproved amount should not be greater than the Admin entered fee. It results in payment error.
    if ( 'auto' === sumo_get_payment_type( $subscription_id ) && in_array( $payment_method, array( 'sumo_paypal_preapproval', 'sumosubscription_paypal_adaptive', 'paypal' ) ) ) {
        return false;
    }

    update_post_meta( $subscription_id, 'sumo_get_updated_renewal_fee', $new_renewal_price );

    /* translators: 1: from renewal price 2: to renewal price */
    sumo_add_subscription_note( sprintf( __( 'Subscription renewal price has been changed from %1$s to %2$s by admin.', 'sumosubscriptions' ), sumo_format_subscription_price( $old_renewal_price, array( 'currency' => $currency ) ), sumo_format_subscription_price( $new_renewal_price, array( 'currency' => $currency ) ) ), $subscription_id, 'success', __( 'Renewal price updated', 'sumosubscriptions' ) );

    /**
     * After renewal price is changed.
     * 
     * @since 1.0
     */
    do_action( 'sumosubscriptions_subscription_renewal_price_changed', $new_renewal_price, $old_renewal_price, $subscription_id );
    return true;
}

/**
 * Change the subscription quantity for renewal.
 * 
 * @param int $subscription_id
 * @param int $qty 
 * @param string $requestBy 
 * @return bool 
 */
function sumo_change_subscription_qty( $subscription_id, $qty = 1, $requestBy = 'admin' ) {
    $new_qty = absint( $qty );
    if ( ! $new_qty ) {
        return false;
    }

    if ( SUMOSubs_Order_Subscription::is_subscribed( $subscription_id ) ) {
        return false;
    }

    $subscription_plan = ( array ) get_post_meta( $subscription_id, 'sumo_subscription_product_details', true );
    $old_qty           = isset( $subscription_plan[ 'product_qty' ] ) ? absint( $subscription_plan[ 'product_qty' ] ) : 1;

    if ( $new_qty === $old_qty ) {
        return false;
    }

    $subscription_plan[ 'product_qty' ] = $new_qty;
    update_post_meta( $subscription_id, 'sumo_subscription_product_details', $subscription_plan );

    if ( 'customer' === $requestBy ) {
        /* translators: 1: old qty 2: new qty */
        sumo_add_subscription_note( sprintf( __( 'Subscription quantity has been updated from <b>%1$s</b> to <b>%2$s</b> by subscriber.', 'sumosubscriptions' ), $old_qty, $new_qty ), $subscription_id, 'success', __( 'Subscription quantity updated', 'sumosubscriptions' ) );
    } else {
        /* translators: 1: old qty 2: new qty */
        sumo_add_subscription_note( sprintf( __( 'Subscription quantity has been updated from <b>%1$s</b> to <b>%2$s</b> by admin.', 'sumosubscriptions' ), $old_qty, $new_qty ), $subscription_id, 'success', __( 'Subscription quantity updated', 'sumosubscriptions' ) );
    }

    /**
     * After subscription qty is changed.
     * 
     * @since 1.0
     */
    do_action( 'sumosubscriptions_subscription_quantity_changed', $new_qty, $old_qty, $subscription_id );

    /**
     * After subscription qty is changed.
     * 
     * @deprecated since 13.3
     * @since 1.0
     */
    do_action( 'sumosubscriptions_subscription_qty_changed', $new_qty, $subscription_id, $subscription_plan );
    return true;
}

/**
 * Get Applied Recurring Discounts.
 * 
 * @since 14.7.0
 */
function sumosubs_get_applied_recurring_discount_amount() {
    $discounts = 0;

    try {
        $applied_coupons     = WC()->cart->get_coupon_discount_totals();
        $applied_coupons_tax = WC()->cart->get_coupon_discount_tax_totals();

        if ( ! empty( $applied_coupons ) ) {
            foreach ( $applied_coupons as $coupon_code => $discount ) {
                $coupon = new WC_Coupon( $coupon_code );

                if ( $coupon->is_type( array( 'sumosubs_recurring_fee_discount', 'sumosubs_recurring_fee_percent_discount' ) ) ) {
                    $discounts += ( float ) $discount;

                    if ( isset( $applied_coupons_tax[ $coupon_code ] ) ) {
                        $discounts += ( float ) $applied_coupons_tax[ $coupon_code ];
                    }
                }
            }
        }
    } catch ( Exception $e ) {
        
    }

    return $discounts;
}
