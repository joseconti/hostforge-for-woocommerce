<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle Subscription cron events.
 * 
 * @class SUMOSubs_Cron_Event
 */
class SUMOSubs_Cron_Event extends SUMOSubs_Abstract_Cron_Event {

    /**
     * Schedule to start the Subscription
     *
     * @param int $start_time
     */
    public function schedule_to_start_subscription( $start_time ) {
        $parent_order_id = get_post_meta( $this->subscription_id, 'sumo_get_parent_order_id', true );

        /**
         * Need to schedule ?
         * 
         * @since 1.0
         */
        if ( ! apply_filters( 'sumosubscriptions_schedule_subscription_crons', true, 'start_subscription', $this->subscription_id, $parent_order_id ) ) {
            return false;
        }

        return $this->set_cron_event( absint( $start_time ), 'start_subscription' );
    }

    /**
     * Schedule Automatic Pay.
     *
     * @param int $order_id The Renewal Order post ID
     */
    public function schedule_automatic_pay( $order_id ) {
        $timestamp = sumo_get_subscription_timestamp( get_post_meta( $this->subscription_id, 'sumo_get_next_payment_date', true ) );

        /**
         * Need to schedule ?
         * 
         * @since 1.0
         */
        if ( ! $timestamp > 0 || ! apply_filters( 'sumosubscriptions_schedule_subscription_crons', true, 'automatic_pay', $this->subscription_id, $order_id ) ) {
            return false;
        }

        $next_eligible_status = sumosubs_get_next_eligible_subscription_failed_status( $this->subscription_id );
        switch ( $next_eligible_status ) {
            case 'Overdue':
                $payment_charging_days       = sumosubs_get_overdue_days();
                $payment_retry_times_per_day = sumosubs_get_payment_retry_times_per_day_in( 'Overdue' );
                break;
            case 'Suspended':
                $payment_charging_days       = sumosubs_get_suspend_days();
                $payment_retry_times_per_day = sumosubs_get_payment_retry_times_per_day_in( 'Suspended' );
                break;
            default:
                $payment_charging_days       = 0;
                $payment_retry_times_per_day = 0;
                break;
        }

        return $this->set_cron_event( absint( $timestamp ), 'automatic_pay', array(
                    'parent_order_id'             => absint( get_post_meta( $this->subscription_id, 'sumo_get_parent_order_id', true ) ),
                    'renewal_order_id'            => absint( $order_id ),
                    'payment_charging_days'       => $payment_charging_days,
                    'payment_retry_times_per_day' => $payment_retry_times_per_day,
                    'next_eligible_status'        => $next_eligible_status,
                ) );
    }

    /**
     * Schedule Renewal Order Creation.
     *
     * @param string $next_due_date
     * @param int $timestamp
     */
    public function schedule_next_renewal_order( $next_due_date, $timestamp = 0 ) {
        $parent_order_id     = get_post_meta( $this->subscription_id, 'sumo_get_parent_order_id', true );
        $subscription_period = get_post_meta( $this->subscription_id, 'sumo_subscr_plan', true );
        $trial_period        = get_post_meta( $this->subscription_id, 'sumo_trial_plan', true );
        $subscription_status = get_post_meta( $this->subscription_id, 'sumo_get_status', true );

        /**
         * Need to schedule ?
         * 
         * @since 1.0
         */
        if ( ! apply_filters( 'sumosubscriptions_schedule_subscription_crons', true, 'create_renewal_order', $this->subscription_id, $parent_order_id ) ) {
            return false;
        }

        if ( 0 === $timestamp || ! is_int( $timestamp ) ) {
            $subscription_cycle = sumo_get_subscription_cycle( 'Trial' === $subscription_status ? $trial_period : $subscription_period );
            $no_of_days_before  = absint( SUMOSubs_Admin_Options::get_option( 'renewal_order_creation_schedule' ) ) * 86400;

            if ( $subscription_cycle < $no_of_days_before ) {
                $no_of_days_before = $subscription_cycle;
            }

            if ( $no_of_days_before <= 0 ) {
                $no_of_days_before = 0;
            }

            //Get Timestamp for Next Renewal to be Happened.
            $timestamp = sumo_get_subscription_timestamp( "{$next_due_date} -{$no_of_days_before} seconds" );
        }

        return $this->set_cron_event( absint( $timestamp ), 'create_renewal_order', array(
                    'next_due_on' => $next_due_date,
                ) );
    }

    /**
     * Schedule Multiple Automatic Pay retries incase if the Automatic Subscription fail to renew. 
     *
     * @param int $order_id The Renewal Order post ID
     * @param string $next_eligible_status may be Current Subscription Status
     * @param int $payment_charging_days may be renewal payment failed, the Subscription status goes to either Overdue or Suspend or Cancel
     * @param int $retry_times_per_day may be it is Automatic renewal payment failed, so retry multiple times
     */
    public function schedule_multiple_automatic_pay_retries( $order_id, $next_eligible_status = '', $payment_charging_days = 0, $retry_times_per_day = 0 ) {
        $subscription_status = get_post_meta( $this->subscription_id, 'sumo_get_status', true );
        $parent_order_id     = get_post_meta( $this->subscription_id, 'sumo_get_parent_order_id', true );

        /**
         * Need to schedule ?
         * 
         * @since 1.0
         */
        if ( ! apply_filters( 'sumosubscriptions_schedule_subscription_crons', true, 'retry_automatic_pay', $this->subscription_id, $order_id ) ) {
            return false;
        }

        $cron_suffix = strtolower( $subscription_status );

        if ( 0 === $payment_charging_days ) {
            return false;
        } else if ( 0 === $retry_times_per_day ) {
            return $this->set_cron_event( absint( sumo_get_subscription_timestamp() + ( $payment_charging_days * 86400 ) ), 'retry_automatic_pay_in_' . $cron_suffix, array(
                        'parent_order_id'      => absint( $parent_order_id ),
                        'renewal_order_id'     => absint( $order_id ),
                        'next_eligible_status' => $next_eligible_status,
                    ) );
        }

        /** Automatic Payment retries 
         *  Charge multiple times based upon the retry count set by Admin
         *  Ex. 
         *  1) if $payment_charging_days === 1 && $retry_times_per_day === 2 then retry 2 times for 1 day
         *  2) if $payment_charging_days === 2 && $retry_times_per_day === 2 then retry 2 times for each day
         */
        $scheduled = false;
        for ( $c = 0; $c < $payment_charging_days; $c ++ ) {
            for ( $r = 1; $r <= $retry_times_per_day; $r ++ ) {

                $time             = sumo_get_subscription_timestamp( "+$c days" );
                $day_start_time   = sumo_get_subscription_timestamp( gmdate( 'Y-m-d 00:00:00', $time ) );
                $day_end_time     = sumo_get_subscription_timestamp( gmdate( 'Y-m-d 23:59:59', $time ) );
                $one_day_interval = $day_end_time - $time;

                if ( $c > 0 ) {
                    $one_day_interval = $day_end_time - $day_start_time;
                    $time             = $day_start_time;
                }

                $charging_timestamp = $one_day_interval ? $time + ( $one_day_interval / $r ) : 0;

                if ( $charging_timestamp >= sumo_get_subscription_timestamp() ) {
                    //may be Last retry in Current Subscription status
                    if ( $payment_charging_days - 1 === $c && $charging_timestamp === $day_end_time ) {
                        switch ( $next_eligible_status ) {
                            case 'Suspended':
                                if ( $this->set_cron_event( absint( $charging_timestamp ), 'retry_automatic_pay_in_' . $cron_suffix, array(
                                            'parent_order_id'             => absint( $parent_order_id ),
                                            'renewal_order_id'            => absint( $order_id ),
                                            'next_eligible_status'        => $next_eligible_status,
                                            'payment_charging_days'       => sumosubs_get_suspend_days(),
                                            'payment_retry_times_per_day' => sumosubs_get_payment_retry_times_per_day_in( 'Suspended' ),
                                        ) )
                                ) {
                                    $scheduled = true;
                                }
                                break;
                            case 'Cancelled':
                                if ( $this->set_cron_event( absint( $charging_timestamp ), 'retry_automatic_pay_in_' . $cron_suffix, array(
                                            'parent_order_id'      => absint( $parent_order_id ),
                                            'renewal_order_id'     => absint( $order_id ),
                                            'next_eligible_status' => $next_eligible_status,
                                        ) )
                                ) {
                                    $scheduled = true;
                                }
                                break;
                        }
                    } elseif ( $this->set_cron_event( absint( $charging_timestamp ), 'retry_automatic_pay_in_' . $cron_suffix, array(
                                'parent_order_id'      => absint( $parent_order_id ),
                                'renewal_order_id'     => absint( $order_id ),
                                'next_eligible_status' => $subscription_status,
                            ) )
                    ) {

                        $scheduled = true;
                    }
                }
            }
        }
        return $scheduled;
    }

    /**
     * Schedule Next eligible Subscription status after renewal payment gets failed.
     *
     * @param int $payment_charging_days may be renewal payment failed, the Subscription status goes to either Overdue or Suspend or Cancel 
     * @param int $retry_times_per_day may be it is Automatic renewal payment failed, so retry multiple times
     */
    public function schedule_next_eligible_payment_failed_status( $payment_charging_days = 0, $retry_times_per_day = 0, $next_due_on = '' ) {
        $subscription_status  = get_post_meta( $this->subscription_id, 'sumo_get_status', true );
        $next_due_on          = empty( $next_due_on ) ? get_post_meta( $this->subscription_id, 'sumo_get_next_payment_date', true ) : $next_due_on;
        $renewal_order_id     = get_post_meta( $this->subscription_id, 'sumo_get_renewal_id', true );
        $upcoming_mail_info   = get_post_meta( $this->subscription_id, 'sumo_get_args_for_pay_link_templates', true );
        $next_eligible_status = sumosubs_get_next_eligible_subscription_failed_status( $this->subscription_id );

        $upcoming_mail_info = wp_parse_args( is_array( $upcoming_mail_info ) ? $upcoming_mail_info : array(), array(
            'next_status'       => '',
            'scheduled_duedate' => '',
                ) );

        switch ( $subscription_status ) {
            case 'Trial':
            case 'Active':
            case 'Pending':
            case 'Pending_Authorization':
                if ( $payment_charging_days > 0 ) {
                    $next_due_on = sumo_get_subscription_date( sumo_get_subscription_timestamp( $next_due_on ) + ( $payment_charging_days * 86400 ) );
                }

                switch ( $next_eligible_status ) {
                    case 'Overdue':
                        $this->schedule_overdue_notify( $renewal_order_id, sumosubs_get_overdue_days(), $next_due_on );
                        break;
                    case 'Suspended':
                        $this->schedule_suspend_notify( $renewal_order_id, sumosubs_get_suspend_days(), $next_due_on );
                        break;
                    case 'Cancelled':
                        $this->schedule_cancel_notify( $renewal_order_id, 0, $next_due_on );
                        break;
                }

                //may be useful for display purpose in Subscription Email Templates
                if ( ! in_array( $next_eligible_status, $upcoming_mail_info ) ) {
                    update_post_meta( $this->subscription_id, 'sumo_get_args_for_pay_link_templates', array(
                        'next_status'       => $next_eligible_status,
                        'scheduled_duedate' => $next_due_on,
                    ) );
                }
                break;
            case 'Overdue':
                switch ( $next_eligible_status ) {
                    case 'Suspended':
                        if ( 'auto' === sumo_get_payment_type( $this->subscription_id ) ) {
                            $this->schedule_multiple_automatic_pay_retries( $renewal_order_id, $next_eligible_status, $payment_charging_days, $retry_times_per_day );
                        } else {
                            $this->schedule_suspend_notify( $renewal_order_id, $payment_charging_days );
                        }
                        break;
                    case 'Cancelled':
                        if ( 'auto' === sumo_get_payment_type( $this->subscription_id ) ) {
                            $this->schedule_multiple_automatic_pay_retries( $renewal_order_id, $next_eligible_status, $payment_charging_days, $retry_times_per_day );
                        } else {
                            $this->schedule_cancel_notify( $renewal_order_id, $payment_charging_days );
                        }
                        break;
                }

                //may be useful for display purpose in Subscription Email Templates
                if ( ! in_array( $next_eligible_status, $upcoming_mail_info ) ) {
                    update_post_meta( $this->subscription_id, 'sumo_get_args_for_pay_link_templates', array(
                        'next_status'       => $next_eligible_status,
                        'scheduled_duedate' => sumo_get_subscription_date( sumo_get_subscription_timestamp() + ( $payment_charging_days * 86400 ) ),
                    ) );
                }
                break;
            case 'Suspended':
                switch ( $next_eligible_status ) {
                    case 'Cancelled':
                        if ( 'auto' === sumo_get_payment_type( $this->subscription_id ) ) {
                            $this->schedule_multiple_automatic_pay_retries( $renewal_order_id, $next_eligible_status, $payment_charging_days, $retry_times_per_day );
                        } else {
                            $this->schedule_cancel_notify( $renewal_order_id, $payment_charging_days );
                        }
                        break;
                }

                //may be useful for display purpose in Subscription Email Templates
                if ( ! in_array( $next_eligible_status, $upcoming_mail_info ) ) {
                    update_post_meta( $this->subscription_id, 'sumo_get_args_for_pay_link_templates', array(
                        'next_status'       => $next_eligible_status,
                        'scheduled_duedate' => sumo_get_subscription_date( sumo_get_subscription_timestamp() + ( $payment_charging_days * 86400 ) ),
                    ) );
                }
                break;
        }
    }

    /**
     * Schedule Overdue Notification.
     *
     * @param int $renewal_order_id The Renewal Order post ID
     * @param int $payment_charging_days
     * @param string | int $next_due_on
     */
    public function schedule_overdue_notify( $renewal_order_id, $payment_charging_days, $next_due_on ) {
        $parent_order_id = get_post_meta( $this->subscription_id, 'sumo_get_parent_order_id', true );

        /**
         * Need to schedule ?
         * 
         * @since 1.0
         */
        if ( ! apply_filters( 'sumosubscriptions_schedule_subscription_crons', true, 'notify_overdue', $this->subscription_id, $parent_order_id ) ) {
            return false;
        }

        return $this->set_cron_event( sumo_get_subscription_timestamp( $next_due_on ), 'notify_overdue', array(
                    'renewal_order_id'      => absint( $renewal_order_id ),
                    'next_due_on'           => sumo_get_subscription_date( $next_due_on ),
                    'payment_charging_days' => $payment_charging_days,
                ) );
    }

    /**
     * Schedule Suspend Notification.
     *
     * @param int $renewal_order_id The Renewal Order post ID
     * @param int $payment_charging_days
     * @param string | int $next_due_on
     */
    public function schedule_suspend_notify( $renewal_order_id, $payment_charging_days = 0, $next_due_on = '' ) {
        $parent_order_id = get_post_meta( $this->subscription_id, 'sumo_get_parent_order_id', true );
        $timestamp       = sumo_get_subscription_timestamp( $next_due_on );

        if ( '' === $next_due_on && $payment_charging_days > 0 ) {
            $timestamp = sumo_get_subscription_timestamp() + ( $payment_charging_days * 86400 );
        }

        /**
         * Need to schedule ?
         * 
         * @since 1.0
         */
        if ( ! apply_filters( 'sumosubscriptions_schedule_subscription_crons', true, 'notify_suspend', $this->subscription_id, $parent_order_id ) ) {
            return false;
        }

        return $this->set_cron_event( absint( $timestamp ), 'notify_suspend', array(
                    'renewal_order_id'      => absint( $renewal_order_id ),
                    'payment_charging_days' => sumosubs_get_suspend_days(),
                ) );
    }

    /**
     * Schedule Cancel Notification.
     *
     * @param int $renewal_order_id The Renewal Order post ID
     * @param int $payment_charging_days
     * @param string | int $next_due_on
     */
    public function schedule_cancel_notify( $renewal_order_id = 0, $payment_charging_days = 0, $next_due_on = '', $force_cancel = false ) {
        $parent_order_id = get_post_meta( $this->subscription_id, 'sumo_get_parent_order_id', true );
        $timestamp       = sumo_get_subscription_timestamp( $next_due_on );

        if ( '' === $next_due_on && $payment_charging_days > 0 ) {
            $timestamp = sumo_get_subscription_timestamp() + ( $payment_charging_days * 86400 );
        }

        /**
         * Need to schedule ?
         * 
         * @since 1.0
         */
        if ( ! apply_filters( 'sumosubscriptions_schedule_subscription_crons', true, 'notify_cancel', $this->subscription_id, $parent_order_id ) ) {
            return false;
        }

        return $this->set_cron_event( absint( $timestamp ), 'notify_cancel', array(
                    'renewal_order_id' => absint( $renewal_order_id ),
                    'force_cancel'     => $force_cancel,
                ) );
    }

    /**
     * Schedule Expire Notification.
     *
     * @param string $expiry_on
     * @param int $renewal_order_id The Renewal Order post ID
     */
    public function schedule_expire_notify( $expiry_on, $renewal_order_id = '' ) {
        $parent_order_id = get_post_meta( $this->subscription_id, 'sumo_get_parent_order_id', true );

        /**
         * Need to schedule ?
         * 
         * @since 1.0
         */
        if ( ! apply_filters( 'sumosubscriptions_schedule_subscription_crons', true, 'notify_expire', $this->subscription_id, $parent_order_id ) ) {
            return false;
        }

        return $this->set_cron_event( sumo_get_subscription_timestamp( $expiry_on ), 'notify_expire', array(
                    'renewal_order_id' => absint( $renewal_order_id ),
                    'expiry_on'        => $expiry_on,
                ) );
    }

    /**
     * Schedule Multiple Reminders.
     *
     * @param int $renewal_order_id The Renewal Order post ID
     * @param string $remind_before
     * @param string $remind_from
     * @param string $template_key Email template key
     */
    public function schedule_reminders( $renewal_order_id, $remind_before, $remind_from = '', $template_key = 'subscription_invoice' ) {
        $job_name = 'subscription_expiry_reminder' === $template_key ? 'notify_expiry_reminder' : 'notify_invoice_reminder';

        /**
         * Need to schedule ?
         * 
         * @since 1.0
         */
        if ( ! apply_filters( 'sumosubscriptions_schedule_subscription_crons', true, $job_name, $this->subscription_id, get_post_meta( $this->subscription_id, 'sumo_get_parent_order_id', true ) ) ) {
            return false;
        }

        $subscription_plan        = sumo_get_subscription_plan( $this->subscription_id );
        $payment_type             = sumo_get_payment_type( $this->subscription_id );
        $reminder_intervals       = sumosubs_get_reminder_intervals( $this->subscription_id, $template_key );
        $remind_before_time       = sumo_get_subscription_timestamp( $remind_before );
        $remind_from_time         = sumo_get_subscription_timestamp( $remind_from );
        $available_days_to_notify = ceil( ( $remind_before_time - $remind_from_time ) / 86400 );
        $scheduled                = false;
        $notifications            = array();

        //Schedule by times per day
        if ( isset( $reminder_intervals[ 'times-per-day' ] ) ) {
            for ( $c = 0; $c < $available_days_to_notify; $c ++ ) {
                for ( $r = 1; $r <= $reminder_intervals[ 'times-per-day' ]; $r ++ ) {

                    $time             = sumo_get_subscription_timestamp( "+$c days" );
                    $day_start_time   = sumo_get_subscription_timestamp( gmdate( 'Y-m-d 00:00:00', $time ) );
                    $day_end_time     = sumo_get_subscription_timestamp( gmdate( 'Y-m-d 23:59:59', $time ) );
                    $one_day_interval = $day_end_time - $time;

                    if ( $c > 0 ) {
                        $one_day_interval = $day_end_time - $day_start_time;
                        $time             = $day_start_time;
                    }

                    $notification_time = $one_day_interval ? $time + ( $one_day_interval / $r ) : 0;

                    if ( $notification_time >= sumo_get_subscription_timestamp() && $this->set_cron_event( $notification_time, $job_name, array(
                                'renewal_order_id' => absint( $renewal_order_id ),
                                'mail_template_id' => $template_key,
                            ) )
                    ) {
                        $scheduled = true;
                    }
                }
            }
        } else if ( isset( $reminder_intervals[ 'no-of-days' ] ) ) {
            //Schedule by comma separated days
            if (
                    isset( $subscription_plan[ 'send_payment_reminder_email' ][ $payment_type ] ) &&
                    'yes' === $subscription_plan[ 'send_payment_reminder_email' ][ $payment_type ]
            ) {
                foreach ( $reminder_intervals[ 'no-of-days' ] as $notify_day ) {
                    if ( $notify_day && $available_days_to_notify >= $notify_day ) {
                        $notifications[] = absint( $remind_before_time - ( 86400 * $notify_day ) );
                    }
                }

                $notifications = array_unique( $notifications );
                if ( $notifications ) {
                    foreach ( $notifications as $notification_time ) {
                        if ( $notification_time >= sumo_get_subscription_timestamp( 0, 0, true ) && $this->set_cron_event( $notification_time, $job_name, array(
                                    'renewal_order_id' => absint( $renewal_order_id ),
                                    'mail_template_id' => $template_key,
                                ) )
                        ) {
                            $scheduled = true;
                        }
                    }
                }
            } else {
                $scheduled = true;
            }
        }

        if ( ! $scheduled ) {
            $scheduled = $this->set_cron_event( sumo_get_subscription_timestamp(), $job_name, array(
                'renewal_order_id' => absint( $renewal_order_id ),
                'mail_template_id' => $template_key,
                    ) );
        }

        return $scheduled;
    }

    /**
     * Schedule Subscription Automatic Resume.
     */
    public function schedule_automatic_resume( $automatic_resume_on ) {
        /**
         * Need to schedule ?
         * 
         * @since 1.0
         */
        if ( ! apply_filters( 'sumosubscriptions_schedule_subscription_crons', true, 'automatic_resume', $this->subscription_id, get_post_meta( $this->subscription_id, 'sumo_get_parent_order_id', true ) ) ) {
            return false;
        }

        return $this->set_cron_event( sumo_get_subscription_timestamp( $automatic_resume_on ), 'automatic_resume' );
    }

}
