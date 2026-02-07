<?php
/**
 * Customer notification scheduling utilities.
 *
 * Uses the core scheduler while routing notification events to a dedicated
 * Action Scheduler group so they can be managed through the centralized API
 * without duplicating scheduling logic.
 *
 * @package Advanced_Subscriptions\SchedulerAPI
 */

class ASWC_Scheduler_Notifications extends ASWC_Scheduler_Core {
    /**
     * Group for customer notification scheduled events.
     */
    const ACTION_GROUP = 'aswc_customer_notifications';

    /**
     * Build the option name used to store the notification action group.
     *
     * @return string Option name.
     */
    protected static function get_group_option_name() {
        return self::OPTION_PREFIX . '_scheduler_notifications_group';
    }

    /**
     * Default notification offset in seconds.
     *
     * Used when a custom offset hasn't been configured. Exposed as a class
     * constant so extensions can reference the baseline value.
     */
    const DEFAULT_OFFSET = 3 * DAY_IN_SECONDS;

    /**
     * Default offset configuration when no option is stored.
     *
     * Exposed as an array so the scheduler can request the raw configuration
     * used to calculate the offset without duplicating the structure.
     */
    const DEFAULT_OFFSET_CONFIG = array(
        'number' => 3,
        'unit'   => 'days',
    );

    /**
     * Option prefix for customer notification settings.
     *
     * Ensures all stored options follow the ASWC naming convention and
     * remain isolated from other `advanced_subscriptions_woocommerce` entries.
     */
    const OPTION_PREFIX = 'advanced_subscriptions_woocommerce';

    /**
     * Option identifier for the notification time offset.
     *
     * Uses the `_customer_notifications_offset` setting so the scheduler can
     * operate without depending on external helpers.
     */
    const OFFSET_SETTING = '_customer_notifications_offset';

    /**
     * Option identifier for the global notifications switch.
     */
    const SWITCH_SETTING = '_customer_notifications_enabled';

    /**
     * Get the option prefix for notification settings.
     *
     * Provides access to the internal prefix so external code can build
     * option names without referencing this class constant directly.
     *
     * @return string Option name prefix.
     */
    public static function get_option_prefix() {
        return self::OPTION_PREFIX;
    }

    /**
     * Constructor.
     *
     * Registers hooks to send the notification emails when their
     * scheduled actions are run.
     */
    public function __construct() {
        add_action( 'woocommerce_init', array( $this, 'register_email_hooks' ) );
    }

    /**
     * Get option name for the notification time offset setting.
     *
     * Builds the full option key using the scheduler prefix so external
     * code can read or update the offset consistently.
     *
     * @return string Option name for notification offsets.
     */
    public static function get_offset_option_name() {
        return self::OPTION_PREFIX . self::OFFSET_SETTING;
    }

    /**
     * Get option name for the global notifications switch.
     *
     * Builds the full option key using the scheduler prefix so external
     * code can reference the setting consistently.
     *
     * @return string Option name for the global notifications switch.
     */
    public static function get_switch_option_name() {
        return self::OPTION_PREFIX . self::SWITCH_SETTING;
    }

    /**
     * Get option name for the notification settings update timestamp.
     *
     * Exposes the full option key used to track when notification
     * configuration values last changed so external code can store or
     * retrieve the timestamp consistently.
     *
     * @return string Option name for notification settings update time.
     */
    public static function get_settings_update_time_option_name() {
        return self::OPTION_PREFIX . '_notification_settings_update_time';
    }

    /**
     * Retrieve the timestamp for the last notification settings update.
     *
     * Exposes the stored value so external code can determine when
     * notification configuration values last changed without accessing the
     * options table directly.
     *
     * @return int Unix timestamp of the last settings update.
     */
    public static function get_settings_update_time() {
        return (int) get_option( self::get_settings_update_time_option_name(), 0 );
    }

    /**
     * Register hooks for notification emails.
     *
     * @return void
     */
    public function register_email_hooks() {
        add_action(
            'advanced_scheduled_subscription_customer_notification_renewal',
            'aswc_send_notification'
        );
        add_action(
            'advanced_scheduled_subscription_customer_notification_trial_expiration',
            'aswc_send_notification'
        );
        add_action(
            'advanced_scheduled_subscription_customer_notification_expiration',
            'aswc_send_notification'
        );
    }

    /**
     * Get the notification hook name for a given subscription date type.
     *
     * @param string $date_type Subscription date type.
     *
     * @return string Action Scheduler hook name.
     */
    public function get_action_from_date_type( $date_type ) {
        switch ( $date_type ) {
            case 'trial_end':
                return 'advanced_scheduled_subscription_customer_notification_trial_expiration';
            case 'next_payment':
                return 'advanced_scheduled_subscription_customer_notification_renewal';
            case 'end':
                return 'advanced_scheduled_subscription_customer_notification_expiration';
            default:
                return '';
        }
    }

    /**
     * Convert an offset configuration to seconds.
     *
     * @param array $offset Array with 'number' and 'unit' keys.
     *
     * @return int Offset in seconds.
     */
    public static function convert_offset_to_seconds( $offset ) {
        $default_offset = self::DEFAULT_OFFSET;

        if ( ! isset( $offset['unit'] ) || ! isset( $offset['number'] ) ) {
            return $default_offset;
        }

        switch ( $offset['unit'] ) {
            case 'days':
                return (int) $offset['number'] * DAY_IN_SECONDS;
            case 'weeks':
                return (int) $offset['number'] * WEEK_IN_SECONDS;
            case 'months':
                return (int) $offset['number'] * MONTH_IN_SECONDS;
            case 'years':
                return (int) $offset['number'] * YEAR_IN_SECONDS;
            default:
                return $default_offset;
        }
    }

    /**
     * Subtract a time offset from a datetime and return the resulting timestamp.
     *
     * @param string $datetime MySQL date/time string in the GMT/UTC timezone.
     * @param int    $offset   Offset in seconds.
     *
     * @return int
     */
    public function subtract_time_offset( $datetime, $offset ) {
        $dt = new DateTime( $datetime, new DateTimeZone( 'UTC' ) );

        return $dt->getTimestamp() - (int) $offset;
    }

    /**
     * Determine if a subscription's period is too short to warrant sending
     * customer notifications.
     *
     * Subscriptions with a billing interval of two days or less are
     * considered too short and no notifications should be scheduled for
     * them.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return bool
     */
    public function is_subscription_period_too_short( $subscription ) {
        $period   = $subscription->get_billing_period();
        $interval = $subscription->get_billing_interval();

        // By default, there are no shorter periods than days in ASWC, so we ignore hours, minutes, etc.
        $too_short = ( $interval <= 2 && 'day' === $period );

        /**
         * Filter: `aswc_subscription_customer_notification_is_period_too_short`.
         *
         * Allows adjusting the logic that determines whether a subscription's
         * billing period is considered too short to schedule customer
         * notifications.
         *
         * @since 1.0.0
         *
         * @param bool            $too_short    Whether the period is too short.
         * @param WC_Subscription $subscription Subscription instance.
         */
        return apply_filters( 'aswc_subscription_customer_notification_is_period_too_short', $too_short, $subscription );
    }

    /**
     * Get the time offset for a notification type.
     *
     * Reads the global notification offset setting and allows
     * customisation via the `aswc_subscription_customer_notification_time_offset`
     * filter so integrations can adjust the lead time for individual
     * subscriptions or notification types.
     *
     * @param WC_Subscription $subscription   Subscription instance.
     * @param string          $notification_type Notification date type.
     *
     * @return int Offset in seconds.
     */
    public function get_time_offset( $subscription, $notification_type ) {
        $setting_option = get_option(
            self::get_offset_option_name(),
            self::DEFAULT_OFFSET_CONFIG
        );

        $offset = self::convert_offset_to_seconds( $setting_option );

        /**
         * Filter: `aswc_subscription_customer_notification_time_offset`.
         *
         * Allows adjusting the offset applied to customer notifications for a
         * specific subscription or notification type.
         *
         * @since 1.0.0
         *
         * @param int             $offset           Offset in seconds.
         * @param WC_Subscription $subscription     Subscription instance.
         * @param string          $notification_type Notification date type.
         */
        return apply_filters( 'aswc_subscription_customer_notification_time_offset', $offset, $subscription, $notification_type );
    }

    /**
     * Returns true if the given date for a subscription is now or in the future.
     *
     * @param WC_Subscription $subscription Subscription whose date is examined.
     * @param string          $date_type    Date type to evaluate.
     *
     * @return bool
     */
    protected function is_date_in_the_future_or_now( $subscription, $date_type ) {
        $dt        = new DateTime( $subscription->get_date( $date_type ), new DateTimeZone( 'UTC' ) );
        $timestamp = $dt->getTimestamp();

        return $timestamp >= time();
    }

    /**
     * Return an array of notification types valid for a subscription based on
     * the dates set on the subscription.
     *
     * Possible values returned are: 'end', 'trial_end', 'next_payment'.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array
     */
    public function get_valid_notifications( $subscription ) {
        $notifications = array();

        if ( $subscription->get_date( 'end' ) && $this->is_date_in_the_future_or_now( $subscription, 'end' ) ) {
            $notifications[] = 'end';
        }

        if ( $subscription->get_date( 'trial_end' ) && $this->is_date_in_the_future_or_now( $subscription, 'trial_end' ) ) {
            $notifications[] = 'trial_end';
        }

        if ( $subscription->get_date( 'next_payment' ) ) {
            // Renewal notification is only valid after the trial has ended.
            $trial_end = $subscription->get_date( 'trial_end' );

            if ( $trial_end ) {
                $trial_end_dt        = new DateTime( $trial_end, new DateTimeZone( 'UTC' ) );
                $trial_end_timestamp = $trial_end_dt->getTimestamp();

                if ( $trial_end_timestamp < time() && $this->is_date_in_the_future_or_now( $subscription, 'next_payment' ) ) {
                    $notifications[] = 'next_payment';
                }
            } elseif ( $this->is_date_in_the_future_or_now( $subscription, 'next_payment' ) ) {
                $notifications[] = 'next_payment';
            }
        }

        /**
         * Filter: `aswc_subscription_valid_customer_notification_types`.
         *
         * Allows filtering the list of notification types that will be scheduled
         * for a particular subscription.
         *
         * @since 1.0.0
         *
         * @param array           $notifications Array of valid notification types.
         * @param WC_Subscription $subscription  Subscription object.
         */
        return (array) apply_filters( 'aswc_subscription_valid_customer_notification_types', $notifications, $subscription );
    }

    /**
     * Get subscription statuses that qualify for customer notifications.
     *
     * Exposes the `aswc_subscription_customer_notification_statuses`
     * filter so callers can inspect or modify the list of statuses that
     * should receive notifications for a given subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array List of allowed subscription statuses.
     */
    public function get_allowed_notification_statuses( $subscription ) {
        if ( ! aswc_is_subscription( $subscription ) ) {
            return array();
        }

        $statuses = apply_filters(
            'aswc_subscription_customer_notification_statuses',
            array( 'active', 'pending-cancel' ),
            $subscription
        );

        $sanitized = array_map( 'aswc_sanitize_subscription_status_key', (array) $statuses );
        $sanitized = array_values( array_filter( array_unique( $sanitized ) ) );

        $valid_statuses = aswc_get_subscription_statuses();

        return array_values( array_intersect( $sanitized, $valid_statuses ) );
    }

    /**
     * Schedule a customer notification for a subscription.
     *
     * @param WC_Subscription $subscription   Subscription instance.
     * @param string          $date_type      Subscription date type being notified.
     * @param int             $timestamp      When the notification should run.
     * @param string|null     $group          Optional Action Scheduler group.
     *
     * @return void
     */
    public function schedule_notification( $subscription, $date_type, $timestamp, $group = null ) {
        $action = $this->get_action_from_date_type( $date_type );

        if ( empty( $action ) ) {
            return;
        }

        $action_args = $this->get_action_args( $date_type, $subscription );

        $this->reschedule_action( $timestamp, $action, $action_args, $group );
    }

    /**
     * Schedule multiple customer notifications for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $notifications Map of date type => timestamp.
     * @param string|null     $group         Optional Action Scheduler group.
     *
     * @return void
     */
    public function schedule_notifications( $subscription, $notifications, $group = null ) {
        foreach ( $notifications as $date_type => $timestamp ) {
            $this->schedule_notification( $subscription, $date_type, $timestamp, $group );
        }
    }

    /**
     * Determine if customer notifications are globally enabled.
     *
     * Exposes the underlying email notification setting so callers can
     * check if scheduling should occur.
     *
     * @return bool
     */
    public function notifications_globally_enabled() {
        return (
            'yes' === get_option( self::get_switch_option_name(), 'yes' )
            && (bool) get_option( self::get_offset_option_name(), self::DEFAULT_OFFSET_CONFIG )
        );
    }

    /**
     * Schedule all valid customer notifications for a subscription.
     *
     * Determines which notification types apply to the subscription, clears
     * any queued notifications that are no longer valid and schedules the
     * remaining ones using the provided offset callback to calculate when each
     * notification should run.
     *
     * @param WC_Subscription $subscription   Subscription instance.
     * @param callable|null   $offset_callback Callback that returns the offset
     *                                         in seconds for a notification type.
     *                                         Defaults to the global notification
     *                                         offset setting if not provided.
     * @param array           $date_types     Date types to consider when
     *                                         unscheduling. Defaults to trial
     *                                         end, next payment and end.
     * @param string|null     $group          Optional Action Scheduler group.
     *
     * @return void
     */
    public function schedule_all( $subscription, $offset_callback = null, $date_types = null, $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        $offset_callback      = $offset_callback ?: array( $this, 'get_time_offset' );
        $valid_notifications  = $this->get_valid_notifications( $subscription );
        $actual_notifications = array_keys( $this->get_scheduled_notifications( $subscription, $date_types, $group ) );

        if ( ! $this->notifications_globally_enabled() ) {
            $this->unschedule_all( $subscription, $date_types, array(), $group );
            return;
        }

        $notifications_to_unschedule = array_diff( $actual_notifications, $valid_notifications );
        $this->unschedule_notifications( $subscription, $notifications_to_unschedule, $group );

        $notifications_to_schedule = array();

        $allowed_statuses = $this->get_allowed_notification_statuses( $subscription );

        foreach ( $valid_notifications as $notification_type ) {
            if ( ! $subscription->has_status( $allowed_statuses ) ) {
                continue;
            }

            if ( $this->is_subscription_period_too_short( $subscription ) ) {
                continue;
            }

            $event_date = $subscription->get_date( $notification_type );
            $offset     = call_user_func( $offset_callback, $subscription, $notification_type );
            $timestamp  = $this->subtract_time_offset( $event_date, $offset );

            $next_scheduled = $this->get_scheduled_notification( $subscription, $notification_type );

            if ( $timestamp === $next_scheduled ) {
                continue;
            }

            $notifications_to_schedule[ $notification_type ] = $timestamp;
        }

        $this->schedule_notifications( $subscription, $notifications_to_schedule, $group );
    }

    /**
     * Get the scheduled timestamp for a specific notification.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     *
     * @return int|false Timestamp or false if no action is queued.
     */
    public function get_scheduled_notification( $subscription, $date_type, $group = null ) {
        $action = $this->get_action_from_date_type( $date_type );

        if ( empty( $action ) ) {
            return false;
        }

        return $this->next_scheduled_action( $action, $this->get_action_args( $date_type, $subscription ), $group );
    }

    /**
     * Get the scheduled action object for a specific notification.
     *
     * Provides direct access to the underlying Action Scheduler entry so
     * callers can inspect metadata associated with a notification.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to search across all groups.
     *
     * @return ActionScheduler_Action|false The scheduled action object or false if none exists.
     */
    public function get_scheduled_notification_action( $subscription, $date_type, $group = null ) {
        $action = $this->get_action_from_date_type( $date_type );

        if ( empty( $action ) ) {
            return false;
        }

        return $this->get_scheduled_action( $action, $this->get_action_args( $date_type, $subscription ), $group );
    }

    /**
     * Get the most recently scheduled action object for a notification.
     *
     * Provides access to the underlying Action Scheduler entry for the last
     * time a notification was queued, which can be useful for debugging or
     * audit purposes.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none exists.
     */
    public function get_last_scheduled_notification_action( $subscription, $date_type, $group = null ) {
        $action = $this->get_action_from_date_type( $date_type );

        if ( empty( $action ) ) {
            return false;
        }

        return $this->get_last_scheduled_action( $action, $this->get_action_args( $date_type, $subscription ), $group );
    }

    /**
     * Get scheduled customer notifications for a subscription.
     *
     * Returns a map of date types to their scheduled timestamps so that
     * callers can easily inspect which notification events are queued.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Date types to check. Defaults to
     *                                      trial end, next payment and end.
     *
     * @return array Map of date type => timestamp for scheduled notifications.
     */
    public function get_scheduled_notifications( $subscription, $date_types = null, $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        $scheduled = array();

        foreach ( $date_types as $date_type ) {
            $timestamp = $this->get_scheduled_notification( $subscription, $date_type, $group );

            if ( $timestamp ) {
                $scheduled[ $date_type ] = $timestamp;
            }
        }

        return $scheduled;
    }

    /**
     * Get scheduled notification action objects for a subscription.
     *
     * Provides direct access to the underlying Action Scheduler entries for
     * each notification type so callers can inspect metadata associated with
     * the queued actions.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Date types to check. Defaults to
     *                                      trial end, next payment and end.
     * @param string|bool     $group        Action Scheduler group. Defaults to this
     *                                      scheduler's group. Pass `false` to search
     *                                      across all groups.
     *
     * @return array Map of date type => ActionScheduler_Action for scheduled notifications.
     */
    public function get_scheduled_notification_actions( $subscription, $date_types = null, $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        $scheduled = array();

        foreach ( $date_types as $date_type ) {
            $action = $this->get_scheduled_notification_action( $subscription, $date_type, $group );

            if ( $action ) {
                $scheduled[ $date_type ] = $action;
            }
        }

        return $scheduled;
    }

    /**
     * Get the most recent scheduled notification timestamp for a subscription.
     *
     * Retrieves when a particular notification type was last scheduled so
     * callers can inspect historical scheduling information.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to search across all groups.
     *
     * @return int|false Timestamp or false if no action exists.
     */
     public function last_scheduled_notification( $subscription, $date_type, $group = null ) {
         $action = $this->get_action_from_date_type( $date_type );

         if ( empty( $action ) ) {
             return false;
         }

         return $this->last_scheduled_action( $action, $this->get_action_args( $date_type, $subscription ), $group );
     }

    /**
     * Get the most recent scheduled notifications for a subscription.
     *
     * Returns a map of date types to the timestamps when the actions were last
     * scheduled so callers can easily inspect historical notification events.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Date types to check. Defaults to trial end, next payment and end.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to search across all groups.
     *
     * @return array Map of date type => timestamp for notifications.
     */
     public function get_last_scheduled_notifications( $subscription, $date_types = null, $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
         $scheduled = array();

         foreach ( $date_types as $date_type ) {
             $timestamp = $this->last_scheduled_notification( $subscription, $date_type, $group );

             if ( $timestamp ) {
                 $scheduled[ $date_type ] = $timestamp;
             }
         }

         return $scheduled;
     }

    /**
     * Get the most recent scheduled notification action objects for a subscription.
     *
     * Returns a map of date types to the action objects representing the last
     * time each notification was queued so callers can inspect historical
     * scheduling information and associated metadata.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Date types to check. Defaults to trial end, next payment and end.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to search across all groups.
     *
     * @return array Map of date type => ActionScheduler_Action for notifications.
     */
    public function get_last_scheduled_notification_actions( $subscription, $date_types = null, $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        $scheduled = array();

        foreach ( $date_types as $date_type ) {
            $action = $this->get_last_scheduled_notification_action( $subscription, $date_type, $group );

            if ( $action ) {
                $scheduled[ $date_type ] = $action;
            }
        }

        return $scheduled;
    }

    /**
     * Determine if a specific notification is scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     *
     * @return bool
     */
    public function has_scheduled_notification( $subscription, $date_type, $group = null ) {
        $action = $this->get_action_from_date_type( $date_type );

        if ( empty( $action ) ) {
            return false;
        }

        return $this->has_scheduled_action( $action, $this->get_action_args( $date_type, $subscription ), $group );
    }

    /**
     * Check if any notifications are scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Date types to check. Defaults to trial end, next payment and end.
     *
     * @return bool
     */
    public function has_scheduled_notifications( $subscription, $date_types = null, $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        foreach ( $date_types as $date_type ) {
            if ( $this->has_scheduled_notification( $subscription, $date_type, $group ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Unschedule a customer notification for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to ignore the group when unscheduling.
     *
     * @return void
     */
    public function unschedule_notification( $subscription, $date_type, $group = null ) {
        $action = $this->get_action_from_date_type( $date_type );

        if ( empty( $action ) ) {
            return;
        }

        $this->unschedule_actions( $action, $this->get_action_args( $date_type, $subscription ), $group );
    }

    /**
     * Unschedule multiple customer notifications for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Notification types to unschedule.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to ignore the group when unscheduling.
     *
     * @return void
     */
    public function unschedule_notifications( $subscription, $date_types, $group = null ) {
        foreach ( $date_types as $date_type ) {
            $this->unschedule_notification( $subscription, $date_type, $group );
        }
    }

    /**
     * Unschedule all notifications for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Date types to unschedule. Defaults to trial end, next payment and end.
     * @param array           $exceptions   Action hooks that should not be unscheduled.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to ignore the group when unscheduling.
     *
     * @return void
     */
    public function unschedule_all( $subscription, $date_types = null, $exceptions = array(), $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        foreach ( $date_types as $date_type ) {
            $action = $this->get_action_from_date_type( $date_type );

            if ( empty( $action ) || in_array( $action, $exceptions, true ) ) {
                continue;
            }

            $this->unschedule_actions( $action, $this->get_action_args( $date_type, $subscription ), $group );
        }
    }

}


