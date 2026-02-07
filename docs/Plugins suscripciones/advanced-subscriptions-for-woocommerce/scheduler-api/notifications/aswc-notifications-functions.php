<?php
/**
 * Notification helper functions for the Scheduler API.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI
 */

if ( ! function_exists( 'aswc_send_notification' ) ) {
    /**
     * Send a subscription email notification.
     *
     * Acts as a wrapper for the plugin's
     * `WC_Subscriptions_Email_Notifications::send_notification` method so the
     * Scheduler API does not depend directly on classes defined outside the
     * library.
     *
     * @param int                 $subscription_id Subscription ID.
     * @param string              $type            Notification type.
     * @param WC_Subscription|int $subscription    Optional subscription object.
     */
    function aswc_send_notification( $subscription_id, $type, $subscription = null ) {
        if (
            class_exists( 'WC_Subscriptions_Email_Notifications' )
            && method_exists( 'WC_Subscriptions_Email_Notifications', 'send_notification' )
        ) {
            WC_Subscriptions_Email_Notifications::send_notification(
                $subscription_id,
                $type,
                $subscription
            );
        }
    }
}

if ( ! function_exists( 'aswc_should_send_notification' ) ) {
    /**
     * Determine whether subscription email notifications should be sent.
     *
     * Delegates to the Scheduler API so the notifications helper relies solely
     * on the API's global notification setting.
     *
     * @return bool
     */
    function aswc_should_send_notification() {
        return ASWC_Scheduler_API::notifications_globally_enabled();
    }
}

if ( ! function_exists( 'aswc_schedule_notification' ) ) {
    /**
     * Schedule a customer notification for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param int             $timestamp    When the notification should run.
     * @param string|null     $group        Optional Action Scheduler group.
     *
     * @return void
     */
    function aswc_schedule_notification( $subscription, $date_type, $timestamp, $group = null ) {
        ASWC_Scheduler_API::schedule_notification( $subscription, $date_type, $timestamp, $group );
    }
}

if ( ! function_exists( 'aswc_schedule_notifications' ) ) {
    /**
     * Schedule multiple customer notifications for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $notifications Map of date type => timestamp.
     * @param string|null     $group         Optional Action Scheduler group.
     *
     * @return void
     */
    function aswc_schedule_notifications( $subscription, $notifications, $group = null ) {
        ASWC_Scheduler_API::schedule_notifications( $subscription, $notifications, $group );
    }
}

if ( ! function_exists( 'aswc_schedule_all_notifications' ) ) {
    /**
     * Schedule all valid customer notifications for a subscription.
     *
     * @param WC_Subscription $subscription   Subscription instance.
     * @param callable|null   $offset_callback Optional callback returning the offset in seconds.
     * @param array|null      $date_types      Optional list of date types to schedule.
     * @param string|null     $group           Optional Action Scheduler group.
     *
     * @return void
     */
    function aswc_schedule_all_notifications( $subscription, $offset_callback = null, $date_types = null, $group = null ) {
        ASWC_Scheduler_API::schedule_all_notifications( $subscription, $offset_callback, $date_types, $group );
    }
}

if ( ! function_exists( 'aswc_unschedule_notification' ) ) {
    /**
     * Unschedule a customer notification for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore the group.
     *
     * @return void
     */
    function aswc_unschedule_notification( $subscription, $date_type, $group = null ) {
        ASWC_Scheduler_API::unschedule_notification( $subscription, $date_type, $group );
    }
}

if ( ! function_exists( 'aswc_unschedule_notifications' ) ) {
    /**
     * Unschedule multiple customer notifications for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Notification types to unschedule.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore the group.
     *
     * @return void
     */
    function aswc_unschedule_notifications( $subscription, $date_types, $group = null ) {
        ASWC_Scheduler_API::unschedule_notifications( $subscription, $date_types, $group );
    }
}

if ( ! function_exists( 'aswc_unschedule_all_notifications' ) ) {
    /**
     * Unschedule all customer notifications for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array|null      $date_types   Date types to unschedule.
     * @param array           $exceptions   Hooks that should not be unscheduled.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore the group.
     *
     * @return void
     */
    function aswc_unschedule_all_notifications( $subscription, $date_types = null, $exceptions = array(), $group = null ) {
        ASWC_Scheduler_API::unschedule_all_notifications( $subscription, $date_types, $exceptions, $group );
    }
}

if ( ! function_exists( 'aswc_get_scheduled_notification' ) ) {
    /**
     * Get the timestamp for a scheduled customer notification.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to search all groups.
     *
     * @return int|false Timestamp or false if none.
     */
    function aswc_get_scheduled_notification( $subscription, $date_type, $group = null ) {
        return ASWC_Scheduler_API::get_scheduled_notification( $subscription, $date_type, $group );
    }
}

if ( ! function_exists( 'aswc_get_scheduled_notifications' ) ) {
    /**
     * Get scheduled customer notifications for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array|null      $date_types   Date types to check.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to search all groups.
     *
     * @return array Map of date type => timestamp.
     */
    function aswc_get_scheduled_notifications( $subscription, $date_types = null, $group = null ) {
        return ASWC_Scheduler_API::get_scheduled_notifications( $subscription, $date_types, $group );
    }
}

if ( ! function_exists( 'aswc_get_scheduled_notification_action' ) ) {
    /**
     * Get the scheduled action object for a notification.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to search all groups.
     *
     * @return ActionScheduler_Action|false Action object or false if none.
     */
    function aswc_get_scheduled_notification_action( $subscription, $date_type, $group = null ) {
        return ASWC_Scheduler_API::get_scheduled_notification_action( $subscription, $date_type, $group );
    }
}

if ( ! function_exists( 'aswc_get_scheduled_notification_actions' ) ) {
    /**
     * Get scheduled notification action objects for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array|null      $date_types   Date types to check.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to search all groups.
     *
     * @return array Map of date type => ActionScheduler_Action.
     */
    function aswc_get_scheduled_notification_actions( $subscription, $date_types = null, $group = null ) {
        return ASWC_Scheduler_API::get_scheduled_notification_actions( $subscription, $date_types, $group );
    }
}

if ( ! function_exists( 'aswc_last_scheduled_notification' ) ) {
    /**
     * Get the timestamp for the most recently scheduled notification.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to search all groups.
     *
     * @return int|false Timestamp or false if none.
     */
    function aswc_last_scheduled_notification( $subscription, $date_type, $group = null ) {
        return ASWC_Scheduler_API::last_scheduled_notification( $subscription, $date_type, $group );
    }
}

if ( ! function_exists( 'aswc_get_last_scheduled_notifications' ) ) {
    /**
     * Get the most recent scheduled notifications for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array|null      $date_types   Date types to check.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to search all groups.
     *
     * @return array Map of date type => timestamp.
     */
    function aswc_get_last_scheduled_notifications( $subscription, $date_types = null, $group = null ) {
        return ASWC_Scheduler_API::get_last_scheduled_notifications( $subscription, $date_types, $group );
    }
}

if ( ! function_exists( 'aswc_get_last_scheduled_notification_action' ) ) {
    /**
     * Get the action object for the last scheduled notification.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to search all groups.
     *
     * @return ActionScheduler_Action|false Action object or false if none.
     */
    function aswc_get_last_scheduled_notification_action( $subscription, $date_type, $group = null ) {
        return ASWC_Scheduler_API::get_last_scheduled_notification_action( $subscription, $date_type, $group );
    }
}

if ( ! function_exists( 'aswc_get_last_scheduled_notification_actions' ) ) {
    /**
     * Get the action objects for the most recently scheduled notifications.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array|null      $date_types   Date types to check.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to search all groups.
     *
     * @return array Map of date type => ActionScheduler_Action.
     */
    function aswc_get_last_scheduled_notification_actions( $subscription, $date_types = null, $group = null ) {
        return ASWC_Scheduler_API::get_last_scheduled_notification_actions( $subscription, $date_types, $group );
    }
}

if ( ! function_exists( 'aswc_has_scheduled_notification' ) ) {
    /**
     * Determine if a specific notification is scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore the group.
     *
     * @return bool
     */
    function aswc_has_scheduled_notification( $subscription, $date_type, $group = null ) {
        return ASWC_Scheduler_API::has_scheduled_notification( $subscription, $date_type, $group );
    }
}

if ( ! function_exists( 'aswc_has_scheduled_notifications' ) ) {
    /**
     * Check if any notifications are scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array|null      $date_types   Date types to check.
     * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore the group.
     *
     * @return bool
     */
    function aswc_has_scheduled_notifications( $subscription, $date_types = null, $group = null ) {
        return ASWC_Scheduler_API::has_scheduled_notifications( $subscription, $date_types, $group );
    }
}

if ( ! function_exists( 'aswc_get_notification_option_prefix' ) ) {
    /**
     * Get the option prefix used for notification settings.
     *
     * @return string Option name prefix for notifications.
     */
    function aswc_get_notification_option_prefix() {
        return ASWC_Scheduler_API::get_notification_option_prefix();
    }
}

if ( ! function_exists( 'aswc_get_notification_hook_from_date_type' ) ) {
    /**
     * Retrieve the notification hook for a given subscription date type.
     *
     * Provides a procedural wrapper so callers do not need to instantiate
     * the notifications scheduler class directly when resolving the Action
     * Scheduler hook corresponding to a subscription date type.
     *
     * @param string $date_type Subscription date type.
     *
     * @return string Action Scheduler hook name or empty string if none.
     */
    function aswc_get_notification_hook_from_date_type( $date_type ) {
        return ASWC_Scheduler_API::get_notification_hook_from_date_type( $date_type );
    }
}

if ( ! function_exists( 'aswc_get_notification_offset_option_name' ) ) {
    /**
     * Get the option name used to store the notification time offset.
     *
     * Delegates to the central Scheduler API so callers do not depend on the
     * notifications module implementation details.
     *
     * @return string Option key for the notification offset setting.
     */
    function aswc_get_notification_offset_option_name() {
        return ASWC_Scheduler_API::get_notification_offset_option_name();
    }
}

if ( ! function_exists( 'aswc_get_notification_switch_option_name' ) ) {
    /**
     * Get the option name used for the global notifications switch.
     *
     * Uses the Scheduler API to avoid referencing the notifications module
     * directly.
     *
     * @return string Option key for the global notifications switch.
     */
    function aswc_get_notification_switch_option_name() {
        return ASWC_Scheduler_API::get_notification_switch_option_name();
    }
}

if ( ! function_exists( 'aswc_get_notification_settings_update_time_option_name' ) ) {
    /**
     * Get the option name used to store the notification settings update time.
     *
     * Delegates to the central Scheduler API so external code can obtain the
     * option name without coupling to the notifications scheduler.
     *
     * @return string Option key for the notification settings update timestamp.
     */
    function aswc_get_notification_settings_update_time_option_name() {
        return ASWC_Scheduler_API::get_notification_settings_update_time_option_name();
    }
}

if ( ! function_exists( 'aswc_get_notification_settings_update_time' ) ) {
    /**
     * Get the timestamp for the last notification settings update.
     *
     * @return int Unix timestamp of the last update or 0 if never updated.
     */
    function aswc_get_notification_settings_update_time() {
        return ASWC_Scheduler_API::get_notification_settings_update_time();
    }
}

if ( ! function_exists( 'aswc_notifications_globally_enabled' ) ) {
    /**
     * Check if customer notifications are globally enabled.
     *
     * Wrapper around the notifications scheduler so external code can
     * determine whether notification events should be scheduled without
     * instantiating the helper class.
     *
     * @return bool
     */
    function aswc_notifications_globally_enabled() {
        return ASWC_Scheduler_API::notifications_globally_enabled();
    }
}

if ( ! function_exists( 'aswc_convert_notification_offset_to_seconds' ) ) {
    /**
     * Convert a notification offset configuration into seconds.
     *
     * Wrapper around the Scheduler API so callers can transform the
     * structured offset data used by the notifications module into a
     * plain integer of seconds without instantiating any classes.
     *
     * @param array $offset Offset configuration array.
     *
     * @return int Offset expressed in seconds.
     */
    function aswc_convert_notification_offset_to_seconds( $offset ) {
        return ASWC_Scheduler_API::convert_offset_to_seconds( $offset );
    }
}

if ( ! function_exists( 'aswc_get_allowed_notification_statuses' ) ) {
    /**
     * Get statuses eligible for customer notifications.
     *
     * Wrapper around the Scheduler API so external code can retrieve the
     * sanitized list of allowed subscription statuses without instantiating
     * any classes.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array List of allowed subscription statuses.
     */
    function aswc_get_allowed_notification_statuses( $subscription ) {
        return ASWC_Scheduler_API::get_allowed_notification_statuses( $subscription );
    }
}

if ( ! function_exists( 'aswc_get_valid_notifications' ) ) {
    /**
     * Get valid customer notifications for a subscription.
     *
     * Wrapper around the Scheduler API so external code can retrieve the
     * list of notification types that should be scheduled without
     * instantiating any classes.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array List of notification date types.
     */
    function aswc_get_valid_notifications( $subscription ) {
        return ASWC_Scheduler_API::get_valid_notifications( $subscription );
    }
}

if ( ! function_exists( 'aswc_get_time_offset' ) ) {
    /**
     * Get the lead time for a customer notification type.
     *
     * Wrapper around the Scheduler API so callers can determine how many
     * seconds before a subscription date a notification should be sent
     * without instantiating helper classes.
     *
     * @param WC_Subscription $subscription   Subscription instance.
     * @param string          $notification_type Notification date type.
     *
     * @return int Offset in seconds.
     */
    function aswc_get_time_offset( $subscription, $notification_type ) {
        return ASWC_Scheduler_API::get_time_offset( $subscription, $notification_type );
    }
}

if ( ! function_exists( 'aswc_subtract_time_offset' ) ) {
    /**
     * Subtract a time offset from a datetime and return the resulting timestamp.
     *
     * @param string $datetime MySQL date/time string in the GMT/UTC timezone.
     * @param int    $offset   Offset in seconds.
     *
     * @return int Resulting timestamp.
     */
    function aswc_subtract_time_offset( $datetime, $offset ) {
        return ASWC_Scheduler_API::subtract_time_offset( $datetime, $offset );
    }
}

if ( ! function_exists( 'aswc_is_subscription_period_too_short' ) ) {
    /**
     * Determine if a subscription period is too short for notifications.
     *
     * Wrapper around the Scheduler API so callers can check whether a
     * subscription's billing period is long enough to warrant sending
     * customer notifications without instantiating helper classes.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return bool
     */
    function aswc_is_subscription_period_too_short( $subscription ) {
        return ASWC_Scheduler_API::is_subscription_period_too_short( $subscription );
    }
}

if ( ! function_exists( 'aswc_unschedule_notification_group' ) ) {
    /**
     * Unschedule all customer notification actions.
     *
     * Provides a procedural wrapper so external code can clear the
     * notifications Action Scheduler group without instantiating the
     * API directly.
     *
     * @param string|bool $group Optional group name. Defaults to the
     *                           notifications group. Pass false to
     *                           clear all groups.
     *
     * @return void
     */
    function aswc_unschedule_notification_group( $group = null ) {
        ASWC_Scheduler_API::unschedule_notification_group( $group );
    }
}


