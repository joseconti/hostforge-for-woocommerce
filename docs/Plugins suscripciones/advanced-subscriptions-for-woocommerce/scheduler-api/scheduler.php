<?php // phpcs:ignoreFile
/**
 * Scheduler API bootstrap.
 *
 * Loads the core scheduler and related modules so the rest of the plugin can
 * interact with scheduled events through a centralized API.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI
 */

require_once __DIR__ . '/core/class-aswc-scheduler-core.php';
require_once __DIR__ . '/core/aswc-core-functions.php';
require_once __DIR__ . '/payments/class-aswc-scheduler-payments.php';
require_once __DIR__ . '/payments/class-aswc-scheduler-payment-retry-handler.php';
require_once __DIR__ . '/payments/aswc-payments-functions.php';
require_once __DIR__ . '/payments/class-aswc-scheduler-failed-action-manager.php';
require_once __DIR__ . '/lifecycle/class-aswc-scheduler-lifecycle.php';
require_once __DIR__ . '/lifecycle/aswc-lifecycle-functions.php';
require_once __DIR__ . '/lifecycle/class-aswc-scheduler-lifecycle-events.php';
require_once __DIR__ . '/notifications/class-aswc-scheduler-notifications.php';
require_once __DIR__ . '/notifications/aswc-notifications-functions.php';
require_once __DIR__ . '/background/class-aswc-scheduler-background.php';
require_once __DIR__ . '/background/aswc-background-functions.php';
require_once __DIR__ . '/core/class-aswc-scheduler-subscription-hooks.php';

/**
 * Central access point for scheduler API components.
 */
class ASWC_Scheduler_API {
    /**
     * Core scheduler instance.
     *
     * @var ASWC_Scheduler_Core
     */
    protected static $core;

    /**
     * Payment scheduler instance.
     *
     * @var ASWC_Scheduler_Payments
     */
    protected static $payments;

    /**
     * Lifecycle scheduler instance.
     *
     * @var ASWC_Scheduler_Lifecycle
     */
    protected static $lifecycle;

    /**
     * Notifications scheduler instance.
     *
     * @var ASWC_Scheduler_Notifications
     */
    protected static $notifications;

    /**
     * Background scheduler instance.
     *
     * @var ASWC_Scheduler_Background
     */
    protected static $background;

    /**
     * Failed scheduled action manager instance.
     *
     * @var ASWC_Scheduler_Failed_Action_Manager
     */
    protected static $failed_action_manager;

    /**
     * Get the core scheduler.
     *
     * @return ASWC_Scheduler_Core
     */
    public static function core() {
        if ( null === self::$core ) {
            self::$core = new ASWC_Scheduler_Core();
        }

        return self::$core;
    }

    /**
     * Get the payment scheduler.
     *
     * @return ASWC_Scheduler_Payments
     */
    public static function payments() {
        if ( null === self::$payments ) {
            self::$payments = new ASWC_Scheduler_Payments( self::core() );
        }

        return self::$payments;
    }

    /**
     * Get the lifecycle scheduler.
     *
     * @return ASWC_Scheduler_Lifecycle
     */
    public static function lifecycle() {
        if ( null === self::$lifecycle ) {
            self::$lifecycle = new ASWC_Scheduler_Lifecycle( self::core() );
        }

        return self::$lifecycle;
    }

    /**
     * Get the notifications scheduler.
     *
     * @return ASWC_Scheduler_Notifications
     */
    public static function notifications() {
        if ( null === self::$notifications ) {
            self::$notifications = new ASWC_Scheduler_Notifications();
        }

        return self::$notifications;
    }

    /**
     * Get the background process scheduler.
     *
     * @return ASWC_Scheduler_Background
     */
    public static function background() {
        if ( null === self::$background ) {
            self::$background = new ASWC_Scheduler_Background();
        }

        return self::$background;
    }

    /**
     * Initialise the failed scheduled action manager.
     *
     * @param WC_Logger_Interface|null $logger Optional logger instance.
     * @return ASWC_Scheduler_Failed_Action_Manager|null
     */
    public static function init_failed_action_manager( ?WC_Logger_Interface $logger = null ) {
        if ( null === self::$failed_action_manager ) {
            if ( null === $logger ) {
                $logger = self::get_logger();

                if ( null === $logger ) {
                    return null;
                }
            }
            self::$failed_action_manager = new ASWC_Scheduler_Failed_Action_Manager( $logger );
            self::$failed_action_manager->init();
        }

        return self::$failed_action_manager;
    }

    /**
     * Schedule all core events for a subscription.
     *
     * Schedules payment and lifecycle events and, if a notification offset
     * callback is provided, schedules customer notifications as well.
     *
     * @param WC_Subscription $subscription              Subscription instance.
     * @param callable|null   $notification_offset_cb    Callback that returns
     *                                                   the offset in seconds
     *                                                   for notification types.
     *
     * @return void
     */
    public static function schedule_all( $subscription, $notification_offset_cb = null, $notification_date_types = null, $group = null ) {
        self::payments()->schedule_all( $subscription, $group );
        self::lifecycle()->schedule_all( $subscription, $group );
        if ( null === $notification_date_types ) {
            $notification_date_types = array_keys( aswc_get_subscription_date_types() );
        }
        self::notifications()->schedule_all( $subscription, $notification_offset_cb, $notification_date_types, $group );
    }

    /**
     * Unschedule all core events for a subscription.
     *
     * Clears payment, lifecycle and notification actions so the subscription
     * has no queued events remaining.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $notification_date_types Optional notification date
     *                                                types to unschedule.
     *
     * @return void
     */
    public static function unschedule_all( $subscription, $notification_date_types = null, $group = null ) {
        self::payments()->unschedule_all( $subscription, $group );
        self::lifecycle()->unschedule_all( $subscription, $group );
        if ( null === $notification_date_types ) {
            $notification_date_types = array_keys( aswc_get_subscription_date_types() );
        }
        self::notifications()->unschedule_all( $subscription, $notification_date_types, array(), $group );
    }

    /**
     * Update a scheduled date for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Date type key.
     * @param string          $datetime     MySQL formatted date in UTC.
     */
    public static function update_date( $subscription, $date_type, $datetime ) {
        self::core()->update_date( $subscription, $date_type, $datetime );
    }

    /**
     * Delete a scheduled date for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Date type key.
     */
    public static function delete_date( $subscription, $date_type ) {
        self::core()->delete_date( $subscription, $date_type );
    }

    /**
     * Handle a subscription status change.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $new_status   New status.
     * @param string          $old_status   Previous status.
     */
    public static function update_status( $subscription, $new_status, $old_status ) {
        self::core()->update_status( $subscription, $new_status, $old_status );
    }

    /**
     * Schedule an action via the core scheduler.
     *
     * @param int    $timestamp   When the action should run.
     * @param string $action_hook Hook name for the action.
     * @param array  $action_args Arguments for the action.
     * @param bool   $unique      Whether the action should be unique.
     * @param string|null $group  Optional Action Scheduler group.
     *
     * @return int Action ID.
     */
    public static function schedule_action( $timestamp, $action_hook, $action_args = array(), $unique = false, $group = null ) {
        return self::core()->schedule_action( $timestamp, $action_hook, $action_args, $unique, $group );
    }

    /**
     * Schedule a recurring action via the core scheduler.
     *
     * @param int         $timestamp   When the first action should run.
     * @param int         $interval    Seconds between runs.
     * @param string      $action_hook Hook name for the action.
     * @param array       $action_args Arguments for the action.
     * @param bool        $unique      Whether the action should be unique.
     * @param string|null $group       Optional Action Scheduler group.
     *
     * @return int Action ID.
     */
    public static function schedule_recurring_action( $timestamp, $interval, $action_hook, $action_args = array(), $unique = false, $group = null ) {
        return self::core()->schedule_recurring_action( $timestamp, $interval, $action_hook, $action_args, $unique, $group );
    }

    /**
     * Schedule a cron-like action via the core scheduler.
     *
     * @param int         $timestamp   When the first action should run.
     * @param string      $schedule    Cron schedule in WP-Cron format.
     * @param string      $action_hook Hook name for the action.
     * @param array       $action_args Arguments for the action.
     * @param bool        $unique      Whether the action should be unique.
     * @param string|null $group       Optional Action Scheduler group.
     *
     * @return int Action ID.
     */
    public static function schedule_cron_action( $timestamp, $schedule, $action_hook, $action_args = array(), $unique = false, $group = null ) {
        return self::core()->schedule_cron_action( $timestamp, $schedule, $action_hook, $action_args, $unique, $group );
    }

    /**
     * Schedule a unique action via the core scheduler.
     *
     * @param int         $timestamp   When the action should run.
     * @param string      $action_hook Hook name for the action.
     * @param array       $action_args Arguments for the action.
     * @param string|null $group       Optional Action Scheduler group.
     * @param int         $priority    Optional action priority.
     *
     * @return int Action ID.
     */
    public static function schedule_unique_action( $timestamp, $action_hook, $action_args = array(), $group = null, $priority = 10 ) {
        return self::core()->schedule_unique_action( $timestamp, $action_hook, $action_args, $group, $priority );
    }

    /**
     * Enqueue an async action via the core scheduler.
     *
     * @param string      $action_hook Action hook to enqueue.
     * @param array       $action_args Action arguments.
     * @param string|null $group       Optional Action Scheduler group.
     *
     * @return int Action ID.
     */
    public static function enqueue_async_action( $action_hook, $action_args = array(), $group = null ) {
        return self::core()->enqueue_async_action( $action_hook, $action_args, $group );
    }

    /**
     * Reschedule an action via the core scheduler.
     *
     * Clears existing actions before scheduling the new one if the
     * timestamp is in the future.
     *
     * @param int    $timestamp   When the action should run.
     * @param string $action_hook Hook name for the action.
     * @param array  $action_args Arguments for the action.
     *
     * @return void
     */
    public static function reschedule_action( $timestamp, $action_hook, $action_args = array(), $group = null ) {
        self::core()->reschedule_action( $timestamp, $action_hook, $action_args, $group );
    }

    /**
     * Get the timestamp for the next scheduled action from the core scheduler.
     *
     * @param string $action_hook Action hook to check.
     * @param array  $action_args Action arguments.
     *
     * @return int|false
     */
    public static function next_scheduled_action( $action_hook, $action_args = array(), $group = null ) {
        return self::core()->next_scheduled_action( $action_hook, $action_args, $group );
    }

    /**
     * Get the timestamp for the most recently scheduled action.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Action arguments.
     * @param string|bool $group       Action Scheduler group. Defaults to the core group. Pass
     *                                 `false` to search across all groups.
     *
     * @return int|false Timestamp of the latest scheduled action or false if none found.
     */
    public static function last_scheduled_action( $action_hook, $action_args = array(), $group = null ) {
        return self::core()->last_scheduled_action( $action_hook, $action_args, $group );
    }

    /**
     * Determine if an action is scheduled in the core group.
     *
     * @param string $action_hook Action hook to check.
     * @param array  $action_args Action arguments.
     *
     * @return bool True if an action matching the hook and arguments exists.
     */
    public static function has_scheduled_action( $action_hook, $action_args = array(), $group = null ) {
        return self::core()->has_scheduled_action( $action_hook, $action_args, $group );
    }

    /**
     * Retrieve scheduled actions from the core scheduler.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Action arguments.
     * @param string|bool $group       Action Scheduler group. Defaults to the core group. Pass
     *                                 `false` to search across all groups.
     *
     * @return ActionScheduler_Action[] Array of action objects.
     */
    public static function get_scheduled_actions( $action_hook, $action_args = array(), $group = null ) {
        return self::core()->get_scheduled_actions( $action_hook, $action_args, $group );
    }

    /**
     * Retrieve the first scheduled action for a hook and arguments.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Action arguments.
     * @param string|bool $group       Action Scheduler group. Defaults to the core group. Pass
     *                                 `false` to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none found.
     */
    public static function get_scheduled_action( $action_hook, $action_args = array(), $group = null ) {
        return self::core()->get_scheduled_action( $action_hook, $action_args, $group );
    }

    /**
     * Retrieve a scheduled action by its ID.
     *
     * Exposes the core scheduler's ability to fetch an action object so
     * callers can inspect additional metadata for debugging or logging
     * purposes without needing to interact with the core instance
     * directly.
     *
     * @param int $action_id Action Scheduler action ID.
     *
     * @return ActionScheduler_Action The action object.
     */
    public static function get_action( $action_id ) {
        return self::core()->get_action( $action_id );
    }

    /**
     * Persist a scheduled action object to the data store.
     *
     * @param object      $action         Action object to save.
     * @param DateTime|null $scheduled_date Optional scheduled date.
     * @return int|false Action ID on success, false otherwise.
     */
    public static function save_action( $action, $scheduled_date = null ) {
        return self::core()->save_scheduled_action( $action, $scheduled_date );
    }

    /**
     * Cancel a scheduled action by its identifier.
     *
     * @param int $action_id Action ID to cancel.
     * @return bool True on success, false otherwise.
     */
    public static function cancel_action( $action_id ) {
        return self::core()->cancel_scheduled_action( $action_id );
    }

    /**
     * Delete a scheduled action from the data store.
     *
     * @param int $action_id Action ID to delete.
     * @return bool True on success, false otherwise.
     */
    public static function delete_action( $action_id ) {
        return self::core()->delete_scheduled_action( $action_id );
    }

    /**
     * Mark a scheduled action as complete.
     *
     * @param int $action_id Action ID to mark complete.
     * @return bool True on success, false otherwise.
     */
    public static function mark_action_complete( $action_id ) {
        return self::core()->mark_action_complete( $action_id );
    }

    /**
     * Mark a scheduled action as failed.
     *
     * @param int $action_id Action ID to mark failed.
     * @return bool True on success, false otherwise.
     */
    public static function mark_action_failed( $action_id ) {
        return self::core()->mark_action_failed( $action_id );
    }

    /**
     * Claim a set of scheduled actions for processing.
     *
     * @param int        $claim_id    Claim identifier.
     * @param int        $limit       Maximum number of actions to claim.
     * @param DateTime|null $before_date Optional upper bound on scheduled date.
     * @param array      $hooks       Optional list of hooks to claim.
     * @param string|bool $group      Action Scheduler group. Pass false to claim across all groups.
     * @return array List of claimed action IDs.
     */
    public static function claim_actions( $claim_id, $limit, $before_date = null, $hooks = array(), $group = null ) {
        return self::core()->claim_actions( $claim_id, $limit, $before_date, $hooks, $group );
    }

    /**
     * Release a previously claimed set of actions.
     *
     * @param int $claim_id Claim identifier to release.
     * @return bool True on success, false otherwise.
     */
    public static function release_claim( $claim_id ) {
        return self::core()->release_claim( $claim_id );
    }

    /**
     * Unclaim a previously claimed action.
     *
     * @param int $action_id Action identifier to unclaim.
     * @return bool True on success, false otherwise.
     */
    public static function unclaim_action( $action_id ) {
        return self::core()->unclaim_action( $action_id );
    }

    /**
     * Query scheduled actions directly from the data store.
     *
     * @param array $query_args Optional query arguments.
     * @return array List of matching action IDs or objects.
     */
    public static function query_actions( $query_args = array() ) {
        return self::core()->query_actions( $query_args );
    }

    /**
     * Retrieve the hook name for a scheduled action object.
     *
     * @param object $action Action object.
     * @return string|null Hook name or null when unavailable.
     */
    public static function get_action_hook( $action ) {
        return self::core()->get_action_hook( $action );
    }

    /**
     * Retrieve the arguments for a scheduled action object.
     *
     * @param object $action Action object.
     * @return array Action arguments.
     */
    public static function get_action_args_from_action( $action ) {
        return self::core()->get_action_args_from_action( $action );
    }

    /**
     * Retrieve the schedule for a scheduled action object.
     *
     * @param object $action Action object.
     * @return object|null Action schedule or null when unavailable.
     */
    public static function get_action_schedule( $action ) {
        return self::core()->get_action_schedule( $action );
    }

    /**
     * Retrieve the status for a scheduled action object.
     *
     * @param object $action Action object.
     * @return string|null Action status or null when unavailable.
     */
    public static function get_action_status( $action ) {
        return self::core()->get_action_status( $action );
    }

    /**
     * Retrieve the group for a scheduled action object.
     *
     * @param object $action Action object.
     * @return string Action group or empty string when unavailable.
     */
    public static function get_action_group( $action ) {
        return self::core()->get_action_group( $action );
    }

    /**
     * Retrieve the identifier for a scheduled action object.
     *
     * @param object $action Action object.
     * @return int Action ID or 0 when unavailable.
     */
    public static function get_action_id( $action ) {
        return self::core()->get_action_id( $action );
    }

    /**
     * Retrieve the priority for a scheduled action object.
     *
     * @param object $action Action object.
     * @return int Action priority or default of 10 when unavailable.
     */
    public static function get_action_priority_from_action( $action ) {
        return self::core()->get_action_priority_from_action( $action );
    }

    /**
     * Retrieve the attempt count for a scheduled action object.
     *
     * @param object $action Action object.
     * @return int Number of attempts or 0 when unavailable.
     */
    public static function get_action_attempts_from_action( $action ) {
        return self::core()->get_action_attempts_from_action( $action );
    }

    /**
     * Retrieve the claim ID for a scheduled action object.
     *
     * @param object $action Action object.
     * @return int Claim ID or 0 when unavailable.
     */
    public static function get_action_claim_id_from_action( $action ) {
        return self::core()->get_action_claim_id_from_action( $action );
    }

    /**
     * Retrieve the post ID associated with a scheduled action object.
     *
     * @param object $action Action object.
     * @return int Post ID or 0 when unavailable.
     */
    public static function get_action_post_id( $action ) {
        return self::core()->get_action_post_id_from_action( $action );
    }

    /**
     * Retrieve the user ID associated with a scheduled action object.
     *
     * @param object $action Action object.
     * @return int User ID or 0 when unavailable.
     */
    public static function get_action_user_id( $action ) {
        return self::core()->get_action_user_id_from_action( $action );
    }

    /**
     * Set the hook for a scheduled action object.
     *
     * @param object $action Action object.
     * @param string $hook   Hook name.
     * @return bool True on success, false otherwise.
     */
    public static function set_action_hook( $action, $hook ) {
        return self::core()->set_action_hook( $action, $hook );
    }

    /**
     * Set the arguments for a scheduled action object.
     *
     * @param object $action Action object.
     * @param array  $args   Action arguments.
     * @return bool True on success, false otherwise.
     */
    public static function set_action_args( $action, $args ) {
        return self::core()->set_action_args( $action, $args );
    }

    /**
     * Set the schedule for a scheduled action object.
     *
     * @param object $action   Action object.
     * @param object $schedule Schedule object.
     * @return bool True on success, false otherwise.
     */
    public static function set_action_schedule( $action, $schedule ) {
        return self::core()->set_action_schedule( $action, $schedule );
    }

    /**
     * Set the group for a scheduled action object.
     *
     * @param object $action Action object.
     * @param string $group  Action group.
     * @return bool True on success, false otherwise.
     */
    public static function set_action_group( $action, $group ) {
        return self::core()->set_action_group( $action, $group );
    }

    /**
     * Set the status for a scheduled action object.
     *
     * @param object $action Action object.
     * @param string $status Action status.
     * @return bool True on success, false otherwise.
     */
    public static function set_action_status( $action, $status ) {
        return self::core()->set_action_status( $action, $status );
    }

    /**
     * Set the priority for a scheduled action object.
     *
     * @param object $action   Action object.
     * @param int    $priority Priority level.
     * @return bool True on success, false otherwise.
     */
    public static function set_action_priority( $action, $priority ) {
        return self::core()->set_action_priority( $action, $priority );
    }

    /**
     * Set the attempt count for a scheduled action object.
     *
     * @param object $action   Action object.
     * @param int    $attempts Attempt count.
     * @return bool True on success, false otherwise.
     */
    public static function set_action_attempts( $action, $attempts ) {
        return self::core()->set_action_attempts( $action, $attempts );
    }

    /**
     * Set the claim ID for a scheduled action object.
     *
     * @param object $action   Action object.
     * @param int    $claim_id Claim identifier.
     * @return bool True on success, false otherwise.
     */
    public static function set_action_claim_id( $action, $claim_id ) {
        return self::core()->set_action_claim_id( $action, $claim_id );
    }

    /**
     * Set the post ID for a scheduled action object.
     *
     * @param object $action  Action object.
     * @param int    $post_id Post ID.
     * @return bool True on success, false otherwise.
     */
    public static function set_action_post_id( $action, $post_id ) {
        return self::core()->set_action_post_id( $action, $post_id );
    }

    /**
     * Set the user ID for a scheduled action object.
     *
     * @param object $action  Action object.
     * @param int    $user_id User ID.
     * @return bool True on success, false otherwise.
     */
    public static function set_action_user_id( $action, $user_id ) {
        return self::core()->set_action_user_id( $action, $user_id );
    }

    /**
     * Retrieve a meta value from a scheduled action object.
     *
     * @param object $action Action object.
     * @param string $key    Meta key.
     * @return mixed|null Meta value or null when unavailable.
     */
    public static function get_action_meta( $action, $key ) {
        return self::core()->get_action_meta( $action, $key );
    }

    /**
     * Save a meta value for a scheduled action object.
     *
     * @param object $action Action object.
     * @param string $key    Meta key.
     * @param mixed  $value  Meta value.
     * @return bool True on success, false otherwise.
     */
    public static function set_action_meta( $action, $key, $value ) {
        return self::core()->set_action_meta( $action, $key, $value );
    }

    /**
     * Delete a meta value from a scheduled action object.
     *
     * @param object $action Action object.
     * @param string $key    Meta key.
     * @return bool True on success, false otherwise.
     */
    public static function delete_action_meta( $action, $key ) {
        return self::core()->delete_action_meta( $action, $key );
    }

    /**
     * Determine whether a scheduled action has finished executing.
     *
     * @param object $action Action object.
     * @return bool True if the action is finished, false otherwise.
     */
    public static function is_action_finished( $action ) {
        return self::core()->is_action_finished( $action );
    }

    /**
     * Retrieve the timestamp for a schedule object.
     *
     * @param object $schedule Schedule object.
     * @return int|false Unix timestamp for the schedule or false when unavailable.
     */
    public static function get_schedule_timestamp( $schedule ) {
        return self::core()->get_schedule_timestamp( $schedule );
    }

    /**
     * Retrieve the GMT timestamp for a schedule object.
     *
     * @param object $schedule Schedule object.
     * @return int|false GMT Unix timestamp or false when unavailable.
     */
    public static function get_schedule_gmt_timestamp( $schedule ) {
        return self::core()->get_schedule_gmt_timestamp( $schedule );
    }

    /**
     * Retrieve the next run timestamp for a schedule object.
     *
     * @param object         $schedule Schedule object.
     * @param \DateTime|null $after    Optional starting point.
     * @return int|false Unix timestamp for the next run or false when unavailable.
     */
    public static function get_schedule_next_timestamp( $schedule, $after = null ) {
        return self::core()->get_schedule_next_timestamp( $schedule, $after );
    }

    /**
     * Retrieve the recurrence interval for a schedule object.
     *
     * @param object $schedule Schedule object.
     * @return int|false Recurrence interval in seconds or false when unavailable.
     */
    public static function get_schedule_recurrence( $schedule ) {
        return self::core()->get_schedule_recurrence( $schedule );
    }

    /**
     * Determine whether a schedule represents a recurring action.
     *
     * @param object $schedule Schedule object.
     * @return bool True if the schedule is recurring, false otherwise.
     */
    public static function is_schedule_recurring( $schedule ) {
        return self::core()->is_schedule_recurring( $schedule );
    }

    /**
     * Build the arguments for a scheduled action.
     *
     * Convenience wrapper so callers can generate the argument array for a
     * particular subscription date type without instantiating the core
     * scheduler directly.
     *
     * @param string          $date_type    Type of date to schedule.
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array
     */
    public static function get_action_args( $date_type, $subscription ) {
        return self::core()->get_action_args( $date_type, $subscription );
    }

    /**
     * Unschedule a single action from the core scheduler.
     *
     * @param string      $action_hook Action hook to clear.
     * @param array       $action_args Action arguments.
     * @param string|bool $group       Action Scheduler group. Defaults to the
     *                                 core group. Pass false to ignore the
     *                                 group when unscheduling.
     *
     * @return void
     */
    public static function unschedule_action( $action_hook, $action_args = array(), $group = null ) {
        self::core()->unschedule_action( $action_hook, $action_args, $group );
    }

    /**
     * Unschedule all actions matching a hook and arguments.
     *
     * Convenience wrapper around the core scheduler's unschedule_actions()
     * so callers don't need to access the core instance directly when
     * clearing multiple queued events.
     *
     * @param string|null  $action_hook Action hook to clear. Pass null to match any hook.
     * @param array        $action_args Action arguments.
     * @param string|bool  $group       Action Scheduler group. Defaults to the core group. Pass
     *                                  false to ignore the group when unscheduling.
     *
     * @return void
     */
    public static function unschedule_actions( $action_hook = null, $action_args = array(), $group = null ) {
        self::core()->unschedule_actions( $action_hook, $action_args, $group );
    }

    /**
     * Unschedule all actions in the core scheduler group.
     *
     * Provides a convenient way to clear every queued subscription event
     * managed by the core scheduler. If a custom group is provided, that
     * group's actions will be cleared instead of the default core group.
     *
     * @param string|bool $group Optional Action Scheduler group. Pass `false` to
     *                           unschedule actions across all groups.
     *
     * @return void
     */
    public static function unschedule_core_group( $group = null ) {
        self::core()->unschedule_group( $group );
    }

    /**
     * Unschedule all customer notification actions.
     *
     * Clears the dedicated notification group so no reminder emails remain
     * queued. If a custom group is provided, that group's actions will be
     * cleared instead.
     *
     * @param string|bool $group Optional Action Scheduler group. Pass `false` to
     *                           unschedule actions across all groups.
     *
     * @return void
     */
    public static function unschedule_notification_group( $group = null ) {
        self::notifications()->unschedule_group( $group );
    }

    /**
     * Unschedule all background processing actions.
     *
     * Removes any queued batch processing or maintenance tasks handled by
     * the background scheduler. If a custom group is provided, that group's
     * actions will be cleared instead of the default background group.
     *
     * @param string|bool $group Optional Action Scheduler group. Pass `false` to
     *                           unschedule actions across all groups.
     *
     * @return void
     */
    public static function unschedule_background_group( $group = null ) {
        self::background()->unschedule_group( $group );
    }

    /**
     * Unschedule all scheduled action groups.
     *
     * Provides a single helper to clear every queued action managed by
     * the Scheduler API, including core events, customer notifications
     * and background processes. Custom groups can be specified for each
     * scheduler via the provided map.
     *
     * @param array $groups Optional map of scheduler keys to group names.
     *                      Supported keys are 'core', 'notifications' and
     *                      'background'. Pass `false` for a key to clear
     *                      actions across all groups for that scheduler.
     *
     * @return void
     */
    public static function unschedule_all_groups( $groups = array() ) {
        self::unschedule_core_group( $groups['core'] ?? null );
        self::unschedule_notification_group( $groups['notifications'] ?? null );
        self::unschedule_background_group( $groups['background'] ?? null );
    }

    /**
     * Schedule a background action.
     *
     * Convenience wrapper so callers don't need to directly access the
     * background scheduler instance when queuing maintenance tasks.
     *
     * @param int    $timestamp   When the action should run.
     * @param string $action_hook Hook name for the action.
     * @param array  $action_args Arguments for the action.
     * @param bool   $unique      Whether the action should be unique.
     * @param string|null $group  Optional Action Scheduler group.
     *
     * @return int Action ID.
     */
    public static function schedule_background_action( $timestamp, $action_hook, $action_args = array(), $unique = false, $group = null ) {
        return self::background()->schedule_action( $timestamp, $action_hook, $action_args, $unique, $group );
    }

    /**
     * Reschedule a background action.
     *
     * Clears any existing matching actions before scheduling the new one.
     *
     * @param int    $timestamp   When the action should run.
     * @param string $action_hook Hook name for the action.
     * @param array  $action_args Arguments for the action.
     *
     * @return void
     */
    public static function reschedule_background_action( $timestamp, $action_hook, $action_args = array(), $group = null ) {
        self::background()->reschedule_action( $timestamp, $action_hook, $action_args, $group );
    }

    /**
     * Unschedule background actions.
     *
     * @param string      $action_hook Action hook to clear.
     * @param array       $action_args Action arguments.
     * @param string|bool $group       Action Scheduler group. Defaults to the
     *                                 background group. Pass `false` to ignore
     *                                 the group.
     *
     * @return void
     */
    public static function unschedule_background_action( $action_hook, $action_args = array(), $group = null ) {
        self::background()->unschedule_actions( $action_hook, $action_args, $group );
    }

    /**
     * Determine if a background action is scheduled.
     *
     * @param string $action_hook Action hook to check.
     * @param array  $action_args Action arguments.
     *
     * @return bool
     */
    public static function has_scheduled_background_action( $action_hook, $action_args = array(), $group = null ) {
        return self::background()->has_scheduled_action( $action_hook, $action_args, $group );
    }

    /**
     * Get the timestamp for the next scheduled background action.
     *
     * @param string $action_hook Action hook to check.
     * @param array  $action_args Action arguments.
     *
     * @return int|false
     */
    public static function next_scheduled_background_action( $action_hook, $action_args = array(), $group = null ) {
        return self::background()->next_scheduled_action( $action_hook, $action_args, $group );
    }

    /**
     * Get the timestamp for the most recently scheduled background action.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Action arguments.
     * @param string|bool $group       Action Scheduler group. Defaults to the
     *                                 background group. Pass `false` to search
     *                                 across all groups.
     *
     * @return int|false Timestamp of the latest scheduled action or false if none found.
     */
    public static function last_scheduled_background_action( $action_hook, $action_args = array(), $group = null ) {
        return self::background()->last_scheduled_action( $action_hook, $action_args, $group );
    }

    /**
     * Retrieve the most recently scheduled background action matching a hook and arguments.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Action arguments.
     * @param string|bool $group       Action Scheduler group. Defaults to the
     *                                 background group. Pass `false` to search
     *                                 across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none found.
     */
    public static function get_last_scheduled_background_action( $action_hook, $action_args = array(), $group = null ) {
        return self::background()->get_last_scheduled_action( $action_hook, $action_args, $group );
    }

    /**
     * Retrieve all background actions scheduled at the latest occurrence of a hook.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Action arguments.
     * @param string|bool $group       Action Scheduler group. Defaults to the background
     *                                 group. Pass `false` to search across all groups.
     *
     * @return ActionScheduler_Action[] Array of action objects.
     */
    public static function get_last_scheduled_background_actions( $action_hook, $action_args = array(), $group = null ) {
        return self::background()->get_last_scheduled_actions( $action_hook, $action_args, $group );
    }

    /**
     * Retrieve background actions matching a hook and arguments.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Action arguments.
     * @param string|bool $group       Action Scheduler group. Defaults to the background
     *                                 group. Pass `false` to search across all groups.
     *
     * @return ActionScheduler_Action[] Array of action objects.
     */
    public static function get_scheduled_background_actions( $action_hook, $action_args = array(), $group = null ) {
        return self::background()->get_scheduled_actions( $action_hook, $action_args, $group );
    }

    /**
     * Retrieve the first scheduled background action matching a hook and arguments.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Action arguments.
     * @param string|bool $group       Action Scheduler group. Defaults to the background
     *                                 group. Pass `false` to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none found.
     */
    public static function get_scheduled_background_action( $action_hook, $action_args = array(), $group = null ) {
        return self::background()->get_scheduled_action( $action_hook, $action_args, $group );
    }

    /**
     * Handle a scheduled subscription payment.
     *
     * @param int|WC_Subscription $subscription_id Subscription ID or instance.
     * @param mixed               $deprecated      Deprecated argument.
     *
     * @return void
     */
    public static function gateway_scheduled_subscription_payment( $subscription_id, $deprecated = null ) {
        self::payments()->gateway_scheduled_subscription_payment( $subscription_id, $deprecated );
    }

    /**
     * Fire a gateway specific hook when a renewal payment is due.
     *
     * @param WC_Order|false $renewal_order Renewal order instance.
     *
     * @return void
     */
    public static function trigger_gateway_renewal_payment_hook( $renewal_order ) {
        self::payments()->trigger_gateway_renewal_payment_hook( $renewal_order );
    }

    /**
     * Check whether a gateway defines a renewal payment hook.
     *
     * @param string $gateway_id Gateway identifier.
     *
     * @return bool
     */
    public static function has_gateway_renewal_payment_hook( $gateway_id ) {
        return self::payments()->has_gateway_renewal_payment_hook( $gateway_id );
    }

    /**
     * Schedule the next payment for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param int|null        $timestamp    When the payment should run. Defaults to the subscription's next payment time.
     * @param string|null     $group        Optional action scheduler group. Uses the core group when `null` or `false`.
     *
     * @return void
     */
    public static function schedule_payment( $subscription, $timestamp = null, $group = null ) {
        self::payments()->schedule_payment( $subscription, $timestamp, $group );
    }

    /**
     * Schedule a manual payment for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param int|null        $timestamp    When the payment should run. Defaults to the subscription's next payment time.
     * @param string|null     $group        Optional action scheduler group. Uses the core group when `null` or `false`.
     *
     * @return int Scheduled timestamp.
     */
    public static function schedule_manual_payment( $subscription, $timestamp = null, $group = null ) {
        return self::payments()->schedule_manual_payment( $subscription, $timestamp, $group );
    }

    /**
     * Unschedule the next payment for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Optional action scheduler group. Pass
     *                                      `false` to ignore group when clearing.
     *
     * @return void
     */
    public static function unschedule_payment( $subscription, $group = null ) {
        self::payments()->unschedule_payment( $subscription, $group );
    }

    /**
     * Schedule a payment retry for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param int|null        $timestamp    When the retry should run. Defaults to the subscription's retry time.
     * @param string|null     $group        Optional action scheduler group. Uses the core group when `null` or `false`.
     *
     * @return void
     */
    public static function schedule_retry( $subscription, $timestamp = null, $group = null ) {
        self::payments()->schedule_retry( $subscription, $timestamp, $group );
    }

    /**
     * Schedule a payment retry using a retry rule.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param object          $rule         Retry rule providing `get_retry_interval()`.
     * @param string|null     $group        Optional action scheduler group. Uses the core group when `null` or `false`.
     *
     * @return int Scheduled timestamp.
     */
    public static function schedule_retry_with_rule( $subscription, $rule, $group = null ) {
        return self::payments()->schedule_retry_with_rule( $subscription, $rule, $group );
    }

    /**
     * Schedule a payment retry after a given interval.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param int             $interval     Interval in seconds after which the retry should run.
     * @param string|null     $group        Optional action scheduler group. Uses the core group when `null` or `false`.
     *
     * @return int Scheduled timestamp.
     */
    public static function schedule_retry_after( $subscription, $interval, $group = null ) {
        return self::payments()->schedule_retry_after( $subscription, $interval, $group );
    }

    /**
     * Schedule a payment retry based on the attempt number.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param int             $attempt      Retry attempt index (0-based).
     * @param string|null     $group        Optional action scheduler group. Uses the core group when `null` or `false`.
     *
     * @return int Scheduled timestamp.
     */
    public static function schedule_retry_for_attempt( $subscription, $attempt, $group = null ) {
        return self::payments()->schedule_retry_for_attempt( $subscription, $attempt, $group );
    }

    /**
     * Process a scheduled payment retry for a subscription.
     *
     * Wrapper to delegate retry processing to the payments scheduler using
     * the subscription context (not the renewal order).
     *
     * @param int|WC_Subscription $subscription_id Subscription ID or instance.
     * @param int|null            $attempt         Optional attempt number (0-based).
     * @param mixed               $deprecated      Deprecated argument for legacy compatibility.
     *
     * @return void
     */
    public static function process_scheduled_retry( $subscription_id, $attempt = null, $deprecated = null ) {
        self::payments()->process_scheduled_retry( $subscription_id, $attempt, $deprecated );
    }

    /**
     * Get the configured maximum number of retries.
     *
     * @return int
     */
    public static function get_retry_limit() {
        return self::payments()->get_retry_limit();
    }

    /**
     * Get the current retry count for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @return int
     */
    public static function get_retry_count( $subscription ) {
        return self::payments()->get_retry_count( $subscription );
    }

    /**
     * Increment and persist the retry counter on a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @return int Updated counter value.
     */
    public static function increment_retry_count( $subscription ) {
        return self::payments()->increment_retry_count( $subscription );
    }

    /**
     * Reset the retry counter on a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @return void
     */
    public static function reset_retry_count( $subscription ) {
        self::payments()->reset_retry_count( $subscription );
    }

    /**
     * Get a human-friendly label of the retry status for UI rendering.
     *
     * Returns:
     * - "Sin reintentos" when no retries yet.
     * - "Reintentos: X de Y" when within limits.
     * - "Máximos reintentos alcanzado" when the limit has been reached.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @return string
     */
    public static function get_retry_status_label( $subscription ) {
        $count = (int) self::get_retry_count( $subscription );
        $limit = (int) self::get_retry_limit();

        if ( $limit <= 0 ) {
            // No retries configured.
            return __( 'Sin reintentos', 'advanced-subscriptions-for-woocommerce' );
        }

        if ( $count <= 0 ) {
            return __( 'Sin reintentos', 'advanced-subscriptions-for-woocommerce' );
        }

        if ( $count >= $limit ) {
            return __( 'Máximos reintentos alcanzado', 'advanced-subscriptions-for-woocommerce' );
        }

        /* translators: 1: current retry count, 2: max retry limit */
        return sprintf(
            __( 'Reintentos: %1$d de %2$d', 'advanced-subscriptions-for-woocommerce' ),
            $count,
            $limit
        );
    }

    /**
     * Retrieve the configured retry intervals.
     *
     * @return array List of intervals in seconds for each retry attempt.
     */
    public static function get_retry_intervals() {
        return self::payments()->get_retry_intervals();
    }

    /**
     * Unschedule a payment retry for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Optional action scheduler group. Pass
     *                                      `false` to ignore group when clearing.
     *
     * @return void
     */
    public static function unschedule_retry( $subscription, $group = null ) {
        self::payments()->unschedule_retry( $subscription, $group );
    }

    /**
     * Get the scheduled timestamp for the next payment.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return int|false Timestamp or false if no action is queued.
     */
    public static function get_scheduled_payment( $subscription, $group = null ) {
        return self::payments()->get_scheduled_payment( $subscription, $group );
    }

    /**
     * Get the scheduled timestamp for the next payment retry.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return int|false Timestamp or false if no action is queued.
     */
    public static function get_scheduled_retry( $subscription, $group = null ) {
        return self::payments()->get_scheduled_retry( $subscription, $group );
    }

    /**
     * Get the scheduled action object for the next payment.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none found.
     */
    public static function get_scheduled_payment_action( $subscription, $group = null ) {
        return self::payments()->get_scheduled_payment_action( $subscription, $group );
    }

    /**
     * Get the scheduled action object for the next payment retry.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none found.
     */
    public static function get_scheduled_retry_action( $subscription, $group = null ) {
        return self::payments()->get_scheduled_retry_action( $subscription, $group );
    }

    /**
     * Get all scheduled payment retry action objects for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return array Array of ActionScheduler_Action objects.
     */
    public static function get_scheduled_retry_actions( $subscription, $group = null ) {
        return self::payments()->get_scheduled_retry_actions( $subscription, $group );
    }

    /**
     * Get the timestamp for the most recently scheduled payment.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return int|false Timestamp or false if no action exists.
     */
    public static function last_scheduled_payment( $subscription, $group = null ) {
        return self::payments()->last_scheduled_payment( $subscription, $group );
    }

    /**
     * Get the timestamp for the most recently scheduled payment retry.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return int|false Timestamp or false if no action exists.
     */
    public static function last_scheduled_retry( $subscription, $group = null ) {
        return self::payments()->last_scheduled_retry( $subscription, $group );
    }

    /**
     * Retrieve the most recently scheduled payment action object.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none found.
     */
    public static function get_last_scheduled_payment_action( $subscription, $group = null ) {
        return self::payments()->get_last_scheduled_payment_action( $subscription, $group );
    }

    /**
     * Retrieve the most recently scheduled payment retry action object.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none found.
     */
    public static function get_last_scheduled_retry_action( $subscription, $group = null ) {
        return self::payments()->get_last_scheduled_retry_action( $subscription, $group );
    }

    /**
     * Check if a payment action is scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return bool
     */
    public static function has_scheduled_payment( $subscription, $group = null ) {
        return self::payments()->has_scheduled_payment( $subscription, $group );
    }

    /**
     * Check if a payment retry action is scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return bool
     */
    public static function has_scheduled_retry( $subscription, $group = null ) {
        return self::payments()->has_scheduled_retry( $subscription, $group );
    }

    /**
     * Check if any payment-related events are scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return bool
     */
    public static function has_scheduled_payments( $subscription, $group = null ) {
        return self::payments()->has_scheduled_payments( $subscription, $group );
    }

    /**
     * Get all scheduled payment-related events for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return array Map of date type => timestamp for scheduled events.
     */
    public static function get_scheduled_payments( $subscription, $group = null ) {
        return self::payments()->get_scheduled_payments( $subscription, $group );
    }

    /**
     * Get the most recent scheduled payment-related events for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return array Map of date type => timestamp for scheduled events.
     */
    public static function get_last_scheduled_payments( $subscription, $group = null ) {
        return self::payments()->get_last_scheduled_payments( $subscription, $group );
    }

    /**
     * Get scheduled payment and retry action objects for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return array Map of date type => ActionScheduler_Action.
     */
    public static function get_scheduled_payment_actions( $subscription, $group = null ) {
        return self::payments()->get_scheduled_payment_actions( $subscription, $group );
    }

    /**
     * Get the most recently scheduled payment and retry action objects.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action scheduler group. Pass `false`
     *                                      to search across all groups.
     *
     * @return array Map of date type => ActionScheduler_Action.
     */
    public static function get_last_scheduled_payment_actions( $subscription, $group = null ) {
        return self::payments()->get_last_scheduled_payment_actions( $subscription, $group );
    }

    /**
     * Schedule all payment-related events for a subscription.
     *
     * Convenience wrapper that delegates to the payment scheduler so callers
     * can schedule both the next payment and any pending retries in a single
     * call.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Optional action scheduler group. Pass
     *                                      `false` to search across all groups.
     *
     * @return void
     */
    public static function schedule_all_payments( $subscription, $group = null ) {
        self::payments()->schedule_all( $subscription, $group );
    }

    /**
     * Unschedule all payment-related events for a subscription.
     *
     * Removes any queued renewal payments and payment retries so nothing
     * remains pending for the subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Optional action scheduler group. Pass
     *                                      `false` to ignore group when clearing.
     *
     * @return void
     */
    public static function unschedule_all_payments( $subscription, $group = null ) {
        self::payments()->unschedule_all( $subscription, $group );
    }

    /**
     * Schedule the end of the trial period for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param int|null        $timestamp    When the trial should end. Defaults to the subscription's trial end time.
     * @param string|null     $group        Optional action scheduler group.
     *
     * @return void
    */
    public static function schedule_trial_end( $subscription, $timestamp = null, $group = null ) {
        self::lifecycle()->schedule_trial_end( $subscription, $timestamp, $group );
    }

    /**
     * Unschedule the end of the trial period for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return void
     */
    public static function unschedule_trial_end( $subscription, $group = null ) {
        self::lifecycle()->unschedule_trial_end( $subscription, $group );
    }

    /**
     * Schedule the expiration of a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param int|null        $timestamp    When the subscription should expire. Defaults to the subscription's end time.
     * @param string|null     $group        Optional action scheduler group.
     *
     * @return void
     */
    public static function schedule_expiration( $subscription, $timestamp = null, $group = null ) {
        self::lifecycle()->schedule_expiration( $subscription, $timestamp, $group );
    }

    /**
     * Unschedule the expiration of a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return void
     */
    public static function unschedule_expiration( $subscription, $group = null ) {
        self::lifecycle()->unschedule_expiration( $subscription, $group );
    }

    /**
     * Schedule the end of the prepaid term for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param int|null        $timestamp    When the prepaid term should end. Defaults to the subscription's end time.
     * @param string|null     $group        Optional action scheduler group.
     *
     * @return void
     */
    public static function schedule_end_of_prepaid_term( $subscription, $timestamp = null, $group = null ) {
        self::lifecycle()->schedule_end_of_prepaid_term( $subscription, $timestamp, $group );
    }

    /**
     * Unschedule the end of the prepaid term for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return void
     */
    public static function unschedule_end_of_prepaid_term( $subscription, $group = null ) {
        self::lifecycle()->unschedule_end_of_prepaid_term( $subscription, $group );
    }

    /**
     * Schedule all lifecycle events for a subscription.
     *
     * Wraps the lifecycle scheduler so callers can queue trial end,
     * expiration or end-of-prepaid-term events with one method call.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|null     $group        Optional action scheduler group.
     *
     * @return void
     */
    public static function schedule_all_lifecycle_events( $subscription, $group = null ) {
        self::lifecycle()->schedule_all( $subscription, $group );
    }

    /**
     * Unschedule all lifecycle events for a subscription.
     *
     * Clears any trial end, expiration or end-of-prepaid-term hooks that are
     * queued for the subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|null     $group        Optional action scheduler group.
     *
     * @return void
     */
    public static function unschedule_all_lifecycle_events( $subscription, $group = null ) {
        self::lifecycle()->unschedule_all( $subscription, $group );
    }

    /**
     * Get the scheduled timestamp for a trial end event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return int|false Timestamp or false if no action is queued.
     */
    public static function get_scheduled_trial_end( $subscription, $group = null ) {
        return self::lifecycle()->get_scheduled_trial_end( $subscription, $group );
    }

    /**
     * Get the scheduled timestamp for an expiration event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return int|false Timestamp or false if no action is queued.
     */
    public static function get_scheduled_expiration( $subscription, $group = null ) {
        return self::lifecycle()->get_scheduled_expiration( $subscription, $group );
    }

    /**
     * Get the scheduled timestamp for an end-of-prepaid-term event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return int|false Timestamp or false if no action is queued.
     */
    public static function get_scheduled_end_of_prepaid_term( $subscription, $group = null ) {
        return self::lifecycle()->get_scheduled_end_of_prepaid_term( $subscription, $group );
    }

    /**
     * Get the scheduled action object for a trial end event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return ActionScheduler_Action|false
     */
    public static function get_scheduled_trial_end_action( $subscription, $group = null ) {
        return self::lifecycle()->get_scheduled_trial_end_action( $subscription, $group );
    }

    /**
     * Get the scheduled action object for an expiration event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return ActionScheduler_Action|false
     */
    public static function get_scheduled_expiration_action( $subscription, $group = null ) {
        return self::lifecycle()->get_scheduled_expiration_action( $subscription, $group );
    }

    /**
     * Get the scheduled action object for an end-of-prepaid-term event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return ActionScheduler_Action|false
     */
    public static function get_scheduled_end_of_prepaid_term_action( $subscription, $group = null ) {
        return self::lifecycle()->get_scheduled_end_of_prepaid_term_action( $subscription, $group );
    }

    /**
     * Get scheduled lifecycle action objects for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array Map of event type => ActionScheduler_Action.
     */
    public static function get_scheduled_lifecycle_actions( $subscription, $group = null ) {
        return self::lifecycle()->get_scheduled_actions( $subscription, $group );
    }

    /**
     * Get scheduled payment and lifecycle action objects for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array Map of event type => ActionScheduler_Action.
     */
    public static function get_scheduled_subscription_actions( $subscription, $group = null ) {
        return array_merge(
            self::get_scheduled_payment_actions( $subscription, $group ),
            self::get_scheduled_lifecycle_actions( $subscription, $group )
        );
    }

    /**
     * Get the timestamp for the most recently scheduled trial end event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return int|false Timestamp or false if no action exists.
     */
    public static function last_scheduled_trial_end( $subscription, $group = null ) {
        return self::lifecycle()->last_scheduled_trial_end( $subscription, $group );
    }

    /**
     * Get the timestamp for the most recently scheduled expiration event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return int|false Timestamp or false if no action exists.
     */
    public static function last_scheduled_expiration( $subscription, $group = null ) {
        return self::lifecycle()->last_scheduled_expiration( $subscription, $group );
    }

    /**
     * Get the timestamp for the most recently scheduled end-of-prepaid-term event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return int|false Timestamp or false if no action exists.
     */
    public static function last_scheduled_end_of_prepaid_term( $subscription, $group = null ) {
        return self::lifecycle()->last_scheduled_end_of_prepaid_term( $subscription, $group );
    }

    /**
     * Get the most recently scheduled action object for a trial end event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return ActionScheduler_Action|false
     */
    public static function get_last_scheduled_trial_end_action( $subscription, $group = null ) {
        return self::lifecycle()->get_last_scheduled_trial_end_action( $subscription, $group );
    }

    /**
     * Get the most recently scheduled action object for an expiration event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return ActionScheduler_Action|false
     */
    public static function get_last_scheduled_expiration_action( $subscription, $group = null ) {
        return self::lifecycle()->get_last_scheduled_expiration_action( $subscription, $group );
    }

    /**
     * Get the most recently scheduled action object for an end-of-prepaid-term event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return ActionScheduler_Action|false
     */
    public static function get_last_scheduled_end_of_prepaid_term_action( $subscription, $group = null ) {
        return self::lifecycle()->get_last_scheduled_end_of_prepaid_term_action( $subscription, $group );
    }

    /**
     * Get the most recent lifecycle action objects scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array Map of event type => ActionScheduler_Action.
     */
    public static function get_last_scheduled_lifecycle_actions( $subscription, $group = null ) {
        return self::lifecycle()->get_last_scheduled_actions( $subscription, $group );
    }

    /**
     * Get the most recent payment and lifecycle action objects for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array Map of event type => ActionScheduler_Action.
     */
    public static function get_last_scheduled_subscription_actions( $subscription, $group = null ) {
        return array_merge(
            self::get_last_scheduled_payment_actions( $subscription, $group ),
            self::get_last_scheduled_lifecycle_actions( $subscription, $group )
        );
    }

    /**
     * Get the most recent lifecycle events scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array Map of event type => timestamp for scheduled events.
     */
    public static function get_last_scheduled_lifecycle_events( $subscription, $group = null ) {
        return self::lifecycle()->get_last_scheduled_events( $subscription, $group );
    }

    /**
     * Get the most recent payment and lifecycle events scheduled for a subscription.
     *
     * Combines the latest queued payment and lifecycle actions so callers can
     * inspect historical scheduling information without querying each module
     * individually.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array Map of event type => timestamp for scheduled events.
     */
    public static function get_last_scheduled_events( $subscription, $group = null ) {
        return array_merge(
            self::get_last_scheduled_payments( $subscription, $group ),
            self::get_last_scheduled_lifecycle_events( $subscription, $group )
        );
    }

    /**
     * Check if a trial end event is scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return bool
     */
    public static function has_scheduled_trial_end( $subscription, $group = null ) {
        return self::lifecycle()->has_scheduled_trial_end( $subscription, $group );
    }

    /**
     * Check if an expiration event is scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return bool
     */
    public static function has_scheduled_expiration( $subscription, $group = null ) {
        return self::lifecycle()->has_scheduled_expiration( $subscription, $group );
    }

    /**
     * Check if an end-of-prepaid-term event is scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return bool
     */
    public static function has_scheduled_end_of_prepaid_term( $subscription, $group = null ) {
        return self::lifecycle()->has_scheduled_end_of_prepaid_term( $subscription, $group );
    }

    /**
     * Determine if any lifecycle events are scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return bool
     */
    public static function has_scheduled_lifecycle_events( $subscription, $group = null ) {
        return self::lifecycle()->has_scheduled_events( $subscription, $group );
    }

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
    public static function schedule_notification( $subscription, $date_type, $timestamp, $group = null ) {
        self::notifications()->schedule_notification( $subscription, $date_type, $timestamp, $group );
    }

     /**
     * Schedule multiple customer notifications for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $notifications Map of date type => timestamp.
     * @param string|null     $group        Optional Action Scheduler group.
     *
     * @return void
     */
    public static function schedule_notifications( $subscription, $notifications, $group = null ) {
        self::notifications()->schedule_notifications( $subscription, $notifications, $group );
    }

     /**
     * Unschedule a customer notification for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Action Scheduler group. Defaults to the notifications group. Pass
     *                                      `false` to ignore groups when unscheduling.
     *
     * @return void
     */
    public static function unschedule_notification( $subscription, $date_type, $group = null ) {
        self::notifications()->unschedule_notification( $subscription, $date_type, $group );
    }

    /**
     * Unschedule multiple customer notifications for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Notification date types to unschedule.
     * @param string|bool     $group        Action Scheduler group. Defaults to the notifications group. Pass
     *                                      `false` to ignore groups when unscheduling.
     *
     * @return void
     */
    public static function unschedule_notifications( $subscription, $date_types, $group = null ) {
        self::notifications()->unschedule_notifications( $subscription, $date_types, $group );
    }

    /**
     * Schedule all valid customer notifications for a subscription.
     *
     * @param WC_Subscription $subscription   Subscription instance.
     * @param callable|null   $offset_callback Callback that returns the offset in seconds for a notification type.
     * @param array           $date_types     Date types to consider when scheduling. Defaults to trial end, next payment and end.
     * @param string|null     $group          Optional Action Scheduler group.
     *
     * @return void
     */
    public static function schedule_all_notifications( $subscription, $offset_callback = null, $date_types = null, $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        self::notifications()->schedule_all( $subscription, $offset_callback, $date_types, $group );
    }

    /**
     * Unschedule all notifications for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Date types to unschedule. Defaults to trial end, next payment and end.
     * @param array           $exceptions   Action hooks that should not be unscheduled.
     * @param string|bool     $group        Action Scheduler group. Defaults to the notifications group. Pass
     *                                      `false` to ignore groups when unscheduling.
     *
     * @return void
     */
    public static function unschedule_all_notifications( $subscription, $date_types = null, $exceptions = array(), $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        self::notifications()->unschedule_all( $subscription, $date_types, $exceptions, $group );
    }

     /**
     * Get all scheduled payment and lifecycle events for a subscription.
     *
     * Provides a single view of the core scheduled events managed by the
     * API so callers don't need to query each scheduler separately.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array Map of date type => timestamp for scheduled events.
     */
    public static function get_scheduled_events( $subscription, $group = null ) {
        return array_merge(
            self::payments()->get_scheduled_payments( $subscription, $group ),
            self::lifecycle()->get_scheduled_events( $subscription, $group )
        );
    }

    /**
     * Determine if any payment or lifecycle events are scheduled for a subscription.
     *
     * Combines checks for both payment and lifecycle schedulers so callers
     * can easily verify whether the subscription has core actions queued
     * without inspecting each module individually.
     *
     * Determine if any events are scheduled for a subscription.
     *
     * Checks payment, lifecycle and customer notification queues so callers
     * can easily verify whether the subscription has any action scheduled
     * without inspecting each module individually.
     *
     * @param WC_Subscription $subscription          Subscription instance.
     * @param array           $notification_date_types Optional notification
     *                                                date types to check.
     *                                                Defaults to trial end,
     *                                                next payment and end.
     *
     * @return bool
     */
    public static function has_scheduled_events( $subscription, $notification_date_types = null, $group = null ) {
        if ( null === $notification_date_types ) {
            $notification_date_types = array_keys( aswc_get_subscription_date_types() );
        }
        return self::payments()->has_scheduled_payments( $subscription, $group )
            || self::lifecycle()->has_scheduled_events( $subscription, $group )
            || self::notifications()->has_scheduled_notifications( $subscription, $notification_date_types, $group );
    }

    /**
     * Get scheduled customer notifications for a subscription.
     *
     * Convenience wrapper around the notifications scheduler so callers can
     * inspect queued notification events without instantiating the helper
     * directly.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Date types to check. Defaults to
     *                                      trial end, next payment and end.
     *
     * @return array Map of date type => timestamp for scheduled notifications.
     */
    public static function get_scheduled_notifications( $subscription, $date_types = null, $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        return self::notifications()->get_scheduled_notifications( $subscription, $date_types, $group );
    }

    /**
     * Get the scheduled timestamp for a customer notification.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     *
     * @return int|false Timestamp or false if no notification is queued.
     */
    public static function get_scheduled_notification( $subscription, $date_type, $group = null ) {
        return self::notifications()->get_scheduled_notification( $subscription, $date_type, $group );
    }

    /**
     * Retrieve the scheduled action object for a customer notification.
     *
     * Exposes the underlying Action Scheduler entry so external code can
     * inspect metadata associated with a notification without instantiating
     * the notifications scheduler directly.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Action Scheduler group. Defaults to the notifications group. Pass
     *                                      `false` to search across all groups.
     *
     * @return ActionScheduler_Action|false The scheduled action object or false if none exists.
     */
    public static function get_scheduled_notification_action( $subscription, $date_type, $group = null ) {
        return self::notifications()->get_scheduled_notification_action( $subscription, $date_type, $group );
    }

    /**
     * Retrieve scheduled action objects for customer notifications.
     *
     * Convenience wrapper that returns the underlying Action Scheduler
     * entries for multiple notification types so callers can inspect
     * metadata associated with each queued event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Date types to check. Defaults to trial end, next payment and end.
     * @param string|bool     $group        Action Scheduler group. Defaults to the notifications group. Pass
     *                                      `false` to search across all groups.
     *
     * @return array Map of date type => ActionScheduler_Action for scheduled notifications.
     */
    public static function get_scheduled_notification_actions( $subscription, $date_types = null, $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        return self::notifications()->get_scheduled_notification_actions( $subscription, $date_types, $group );
    }

    /**
     * Get the most recently scheduled action object for a customer notification.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     * @param string|bool     $group        Action Scheduler group. Defaults to the notifications group. Pass
     *                                      `false` to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none exists.
     */
    public static function get_last_scheduled_notification_action( $subscription, $date_type, $group = null ) {
        return self::notifications()->get_last_scheduled_notification_action( $subscription, $date_type, $group );
    }

    /**
     * Get the timestamp for the most recently scheduled customer notification.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     *
     * @return int|false Timestamp or false if no action exists.
     */
    public static function last_scheduled_notification( $subscription, $date_type, $group = null ) {
        return self::notifications()->last_scheduled_notification( $subscription, $date_type, $group );
    }

    /**
     * Get the most recent scheduled customer notifications for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Date types to check. Defaults to trial end, next payment and end.
     *
     * @return array Map of date type => timestamp for notifications.
     */
    public static function get_last_scheduled_notifications( $subscription, $date_types = null, $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        return self::notifications()->get_last_scheduled_notifications( $subscription, $date_types, $group );
    }

    /**
     * Retrieve the most recently scheduled notification action objects for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Date types to check. Defaults to trial end, next payment and end.
     * @param string|bool     $group        Action Scheduler group. Defaults to the notifications group. Pass
     *                                      `false` to search across all groups.
     *
     * @return array Map of date type => ActionScheduler_Action for notifications.
     */
    public static function get_last_scheduled_notification_actions( $subscription, $date_types = null, $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        return self::notifications()->get_last_scheduled_notification_actions( $subscription, $date_types, $group );
    }

    /**
     * Check if any customer notifications are scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param array           $date_types   Date types to check. Defaults to
     *                                      trial end, next payment and end.
     *
     * @return bool
     */
    public static function has_scheduled_notifications( $subscription, $date_types = null, $group = null ) {
        if ( null === $date_types ) {
            $date_types = array_keys( aswc_get_subscription_date_types() );
        }
        return self::notifications()->has_scheduled_notifications( $subscription, $date_types, $group );
    }

    /**
     * Determine if a specific customer notification is scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Subscription date type being notified.
     *
     * @return bool
     */
    public static function has_scheduled_notification( $subscription, $date_type, $group = null ) {
        return self::notifications()->has_scheduled_notification( $subscription, $date_type, $group );
    }

    /**
     * Get the notification hook name for a subscription date type.
     *
     * Provides a convenient wrapper so callers can convert a subscription
     * date type to the corresponding Action Scheduler hook without
     * instantiating the notifications scheduler directly.
     *
     * @param string $date_type Subscription date type.
     *
     * @return string Action Scheduler hook name or empty string if none.
     */
    public static function get_notification_hook_from_date_type( $date_type ) {
        return self::notifications()->get_action_from_date_type( $date_type );
    }

    /**
     * Get valid customer notification types for a subscription.
     *
     * Convenience wrapper so callers can determine which notification
     * events should be scheduled without instantiating the helper
     * directly.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array List of valid notification date types.
     */
    public static function get_valid_notifications( $subscription ) {
        return self::notifications()->get_valid_notifications( $subscription );
    }

    /**
     * Get subscription statuses that qualify for customer notifications.
     *
     * Wrapper around the notifications scheduler so external code can
     * determine which subscription statuses should receive notifications.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array List of allowed subscription statuses.
     */
    public static function get_allowed_notification_statuses( $subscription ) {
        return self::notifications()->get_allowed_notification_statuses( $subscription );
    }

    /**
     * Get the option prefix used for notification settings.
     *
     * Exposes the prefix defined by the notifications scheduler so callers can
     * construct related option names without referencing internal constants.
     *
     * @return string Option name prefix for notifications.
     */
    public static function get_notification_option_prefix() {
        return ASWC_Scheduler_Notifications::get_option_prefix();
    }

    /**
     * Get option name used for the notification time offset setting.
     *
     * Exposes the underlying option key so external code can read or update
     * the configured lead time for customer notifications.
     *
     * @return string Option name for notification offsets.
     */
    public static function get_notification_offset_option_name() {
        return ASWC_Scheduler_Notifications::get_offset_option_name();
    }

    /**
     * Get option name used for the global notification switch.
     *
     * Exposes the underlying option key so external code can reference or
     * update whether customer notifications are enabled.
     *
     * @return string Option name for the global notification switch.
     */
    public static function get_notification_switch_option_name() {
        return ASWC_Scheduler_Notifications::get_switch_option_name();
    }

    /**
     * Get option name used for the notification settings update timestamp.
     *
     * Exposes the underlying option key so external code can track when
     * notification configuration values last changed.
     *
     * @return string Option name for notification settings update time.
     */
    public static function get_notification_settings_update_time_option_name() {
        return ASWC_Scheduler_Notifications::get_settings_update_time_option_name();
    }

    /**
     * Get the timestamp of the last notification settings update.
     *
     * Provides a wrapper so external code can determine when notification
     * configuration values last changed without querying options directly.
     *
     * @return int Unix timestamp of the last settings update.
     */
    public static function get_notification_settings_update_time() {
        return ASWC_Scheduler_Notifications::get_settings_update_time();
    }

    /**
     * Check if customer notifications are globally enabled.
     *
     * Wrapper around the notifications scheduler utility so external code can
     * determine whether notification events should be scheduled.
     *
     * @return bool
     */
    public static function notifications_globally_enabled() {
        return self::notifications()->notifications_globally_enabled();
    }

    /**
     * Send a subscription email notification.
     *
     * Exposes the notification helper so external code can trigger
     * subscription emails via the central Scheduler API without
     * depending on plugin classes.
     *
     * @param int                 $subscription_id Subscription ID.
     * @param string              $type            Notification type.
     * @param WC_Subscription|int $subscription    Optional subscription object.
     *
     * @return void
     */
    public static function send_notification( $subscription_id, $type, $subscription = null ) {
        aswc_send_notification( $subscription_id, $type, $subscription );
    }

    /**
     * Check whether subscription notifications should be sent.
     *
     * Wrapper around the email notifications handler so external code can
     * determine if subscription emails should be dispatched without depending
     * on plugin classes.
     *
     * @return bool
     */
    public static function should_send_notification() {
        return aswc_should_send_notification();
    }

    /**
     * Determine if a subscription's period is too short for notifications.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return bool
     */
    public static function is_subscription_period_too_short( $subscription ) {
        return self::notifications()->is_subscription_period_too_short( $subscription );
    }

    /**
     * Get the time offset for a notification type.
     *
     * Wrapper around the notifications scheduler method so callers can
     * retrieve the configured offset via the central API.
     *
     * @param WC_Subscription $subscription   Subscription instance.
     * @param string          $notification_type Notification date type.
     *
     * @return int Offset in seconds.
     */
    public static function get_time_offset( $subscription, $notification_type ) {
        return self::notifications()->get_time_offset( $subscription, $notification_type );
    }

    /**
     * Convert a notification offset configuration to seconds.
     *
     * Exposes the notifications scheduler utility so callers can
     * reuse the offset conversion logic without referencing the
     * scheduler class directly.
     *
     * @param array $offset Array with 'number' and 'unit' keys.
     *
     * @return int Offset in seconds.
     */
    public static function convert_offset_to_seconds( $offset ) {
        return ASWC_Scheduler_Notifications::convert_offset_to_seconds( $offset );
    }

    /**
     * Subtract a time offset from a datetime and return the resulting timestamp.
     *
     * Wrapper around the notifications scheduler method so external code can
     * perform offset calculations through the central API.
     *
     * @param string $datetime MySQL date/time string in the GMT/UTC timezone.
     * @param int    $offset   Offset in seconds.
     *
     * @return int Resulting timestamp after subtracting the offset.
     */
    public static function subtract_time_offset( $datetime, $offset ) {
        return self::notifications()->subtract_time_offset( $datetime, $offset );
    }

    /**
     * Get the latest Action Scheduler version.
     *
     * Exposes the core wrapper so external code can detect the Action Scheduler
     * version without depending on the library's classes.
     *
     * @return string Semantic version string. Defaults to '0' when unavailable.
     */
    public static function get_latest_action_scheduler_version() {
        return aswc_get_latest_action_scheduler_version();
    }

    /**
     * Retrieve the Action Scheduler data store instance.
     *
     * Provides access to the store while allowing callers to avoid a hard
     * dependency on Action Scheduler when it's not loaded.
     *
     * @return ActionScheduler_Store|null Store instance or null if unavailable.
     */
    public static function get_action_scheduler_store() {
        return aswc_get_action_scheduler_store();
    }

    /**
     * Retrieve the status slug used for pending actions in Action Scheduler.
     *
     * Provides access to the status constant without requiring consumers to
     * depend on Action Scheduler directly.
     *
     * @return string Pending status slug.
     */
    public static function get_action_scheduler_pending_status() {
        return aswc_get_action_scheduler_pending_status();
    }

    /**
     * Retrieve the status slug used for completed actions in Action Scheduler.
     *
     * @return string Complete status slug.
     */
    public static function get_action_scheduler_complete_status() {
        return aswc_get_action_scheduler_complete_status();
    }

    /**
     * Retrieve the status slug used for failed actions in Action Scheduler.
     *
     * @return string Failed status slug.
     */
    public static function get_action_scheduler_failed_status() {
        return aswc_get_action_scheduler_failed_status();
    }

    /**
     * Retrieve the status slug used for in-progress actions in Action Scheduler.
     *
     * @return string Running status slug.
     */
    public static function get_action_scheduler_running_status() {
        return aswc_get_action_scheduler_running_status();
    }

    /**
     * Retrieve the status slug used for canceled actions in Action Scheduler.
     *
     * @return string Canceled status slug.
     */
    public static function get_action_scheduler_canceled_status() {
        return aswc_get_action_scheduler_canceled_status();
    }

    /**
     * Log a message for a scheduled action through Action Scheduler's logger.
     *
     * Provides access to Action Scheduler's logging system while allowing
     * callers to avoid a direct dependency on the library.
     *
     * @param int    $action_id Action identifier.
     * @param string $message   Message to record for the action.
     *
     * @return void
     */
    public static function log_action( $action_id, $message ) {
        aswc_action_scheduler_log( $action_id, $message );
    }

    /**
     * Convert a MySQL datetime string to a Unix timestamp.
     *
     * Provides access to the core wrapper so external code can normalize
     * dates via the centralized API.
     *
     * @param string|int $datetime MySQL formatted date/time string or timestamp.
     * @return int|null Unix timestamp or null on failure.
     */
    public static function date_to_time( $datetime ) {
        return aswc_date_to_time( $datetime );
    }

    /**
     * Get a property from an object.
     *
     * Wrapper around the core helper so external code can access values
     * without relying on plugin utilities.
     *
     * @param object $object   Object to inspect.
     * @param string $property Property name.
     * @param string $single   Whether to return a single value. Defaults to 'single'.
     * @param mixed  $default  Default value to return if the property is unavailable.
     * @return mixed
     */
    public static function get_objects_property( $object, $property, $single = 'single', $default = null ) {
        return aswc_get_objects_property( $object, $property, $single, $default );
    }

    /**
     * Get all subscription date types.
     *
     * Wrapper around the core helper so callers can retrieve the list of
     * subscription date identifiers without referencing the functions
     * directly.
     *
     * @return array Map of date type => label.
     */
    public static function get_subscription_date_types() {
        return aswc_get_subscription_date_types();
    }

    /**
     * Get all subscription status slugs.
     *
     * Exposes the core wrapper for retrieving subscription statuses so
     * external code can obtain the list via the centralized API.
     *
     * @return array List of subscription status slugs.
     */
    public static function get_subscription_statuses() {
        return aswc_get_subscription_statuses();
    }

    /**
     * Get all subscription statuses with their display names.
     *
     * @return array Map of status slug => display name.
     */
    public static function get_subscription_status_names() {
        return aswc_get_subscription_status_names();
    }

    /**
     * Get subscription statuses that represent an ended subscription.
     *
     * @return array List of ended subscription status slugs.
     */
    public static function get_subscription_ended_statuses() {
        return aswc_get_subscription_ended_statuses();
    }

    /**
     * Determine whether the installed WooCommerce version is lower than the
     * provided version.
     *
     * Wrapper around the core helper so external code can perform version
     * comparisons via the centralized API.
     *
     * @param string $version WooCommerce version to compare against.
     * @return bool True if the store is running a version lower than
     *              `$version` or the version cannot be determined.
     */
    public static function is_woocommerce_pre( $version ) {
        return aswc_is_woocommerce_pre( $version );
    }

    /**
     * Determine if WooCommerce's custom order tables are enabled.
     *
     * Wrapper around the core helper so external code can check the status of
     * the HPOS feature via the centralized API.
     *
     * @return bool True when custom order tables are enabled.
     */
    public static function is_custom_order_tables_usage_enabled() {
        return aswc_is_custom_order_tables_usage_enabled();
    }

    /**
     * Check whether WooCommerce synchronizes custom order tables with posts.
     *
     * Wrapper around the core helper so external code can determine if HPOS
     * data synchronization is active via the centralized API.
     *
     * @return bool True when data synchronization is enabled.
     */
    public static function is_custom_order_tables_data_sync_enabled() {
        return aswc_is_custom_order_tables_data_sync_enabled();
    }

    /**
     * Append an ordinal suffix to a number.
     *
     * Wrapper around the core helper so external code can format numbers via
     * the centralized API.
     *
     * @param int|string $number Number to suffix.
     * @return string Number with ordinal suffix.
     */
    public static function append_numeral_suffix( $number ) {
        return aswc_append_numeral_suffix( $number );
    }

    /**
     * Get subscription period strings.
     *
     * Wrapper around the core helper so external code can retrieve
     * human-readable period labels without loading plugin functions directly.
     *
     * @param int    $number Interval for the period.
     * @param string $period Optional period key. One of day, week, month or year.
     *
     * @return array|string Map of period => label or a single label if `$period` is provided.
     */
    public static function get_subscription_period_strings( $number = 1, $period = '' ) {
        return aswc_get_subscription_period_strings( $number, $period );
    }

    /**
     * Get subscription period interval strings.
     *
     * Exposes the core wrapper so callers can retrieve human-readable period
     * interval labels without loading plugin functions directly.
     *
     * @param int|null $interval Specific interval to retrieve. Defaults to all.
     * @return array|string Map of interval => label or a single label if
     *                      `$interval` is provided.
     */
    public static function get_subscription_period_interval_strings( $interval = null ) {
        return aswc_get_subscription_period_interval_strings( $interval );
    }

    /**
     * Get subscription trial period strings.
     *
     * Exposes the core wrapper for retrieving trial period labels via the
     * centralized API.
     *
     * @param int    $number Interval for the period.
     * @param string $period Optional period key. One of day, week, month or year.
     *
     * @return array|string Map of period => label or a single label if `$period` is provided.
     */
    public static function get_subscription_trial_period_strings( $number = 1, $period = '' ) {
        return aswc_get_subscription_trial_period_strings( $number, $period );
    }

    /**
     * Get subscription length ranges.
     *
     * Wrapper around the core helper so callers can inspect allowed ranges for
     * subscription lengths without depending on plugin functions.
     *
     * @param string|null $subscription_period Optional period key. If provided,
     *                                         only the ranges for that period are returned.
     *
     * @return array Subscription length ranges.
     */
    public static function get_subscription_ranges( $subscription_period = null ) {
        return aswc_get_subscription_ranges( $subscription_period );
    }

    /**
     * Get available time periods for subscriptions.
     *
     * Wrapper around the core helper so external code can retrieve allowed
     * time periods via the central API.
     *
     * @param string $form Optional. 'singular' or 'plural'.
     * @return array Map of period key => label.
     */
    public static function get_available_time_periods( $form = 'singular' ) {
        return aswc_get_available_time_periods( $form );
    }

    /**
     * Get allowed trial period lengths.
     *
     * Exposes the core wrapper so callers can retrieve trial length options via
     * the centralized API.
     *
     * @param string $subscription_period Optional period key to filter by.
     * @return array Map of length => label or periods => lengths when no period
     *               is provided.
     */
    public static function get_subscription_trial_lengths( $subscription_period = '' ) {
        return aswc_get_subscription_trial_lengths( $subscription_period );
    }

    /**
     * Sanitize a subscription status key.
     *
     * Wrapper around the core helper so external code can normalize custom
     * statuses via the central API.
     *
     * @param string $status Raw status key.
     * @return string Sanitized status key.
     */
    public static function sanitize_subscription_status_key( $status ) {
        return aswc_sanitize_subscription_status_key( $status );
    }

    /**
     * Generate a key for grouping subscription items with the same schedule.
     *
     * Exposes the core wrapper so external code can group order items without
     * depending on the plugin's helper directly.
     *
     * @param WC_Order_Item_Product $item         Order item.
     * @param int                   $renewal_time Timestamp for first renewal.
     *
     * @return string Grouping key.
     */
    public static function get_subscription_item_grouping_key( $item, $renewal_time = 0 ) {
        return aswc_get_subscription_item_grouping_key( $item, $renewal_time );
    }

    /**
     * Retrieve a subscription by its ID.
     *
     * Exposes the lifecycle wrapper through the central API so callers can
     * fetch subscriptions without depending on external helpers.
     *
     * @param int $subscription_id Subscription post ID.
     *
     * @return WC_Subscription|false Subscription object if found, otherwise false.
     */
    public static function get_subscription( $subscription_id ) {
        return aswc_get_subscription( $subscription_id );
    }

    /**
     * Retrieve subscriptions matching query arguments.
     *
     * Exposes the lifecycle wrapper through the central API so callers can
     * query subscriptions without relying on plugin helpers.
     *
     * @param array $args Optional query arguments.
     *
     * @return array List of WC_Subscription objects.
     */
    public static function get_subscriptions( $args = array() ) {
        return aswc_get_subscriptions( $args );
    }

    /**
     * Retrieve subscriptions linked to an order.
     *
     * Provides a wrapper around the lifecycle helper so callers can
     * inspect related subscriptions without relying on plugin functions.
     *
     * @param int|WC_Order $order Order object or ID.
     *
     * @return array List of WC_Subscription objects.
     */
    public static function get_subscriptions_for_order( $order, $args = array() ) {
        return aswc_get_subscriptions_for_order( $order, $args );
    }

    /**
     * Retrieve subscription IDs linked to an order.
     *
     * @param int|WC_Order    $order       Order object or ID.
     * @param string|string[] $order_types Relationship types to include.
     *
     * @return array List of subscription IDs.
     */
    public static function get_subscription_ids_for_order( $order, $order_types = array( 'any' ) ) {
        return aswc_get_subscription_ids_for_order( $order, $order_types );
    }

    /**
     * Retrieve subscriptions linked to a renewal order.
     *
     * Provides a wrapper around the lifecycle helper so callers can
     * inspect subscriptions for renewal orders without relying on plugin
     * functions.
     *
     * @param int|WC_Order $order Renewal order object or ID.
     *
     * @return array List of WC_Subscription objects.
     */
    public static function get_subscriptions_for_renewal_order( $order, $args = array() ) {
        return aswc_get_subscriptions_for_renewal_order( $order, $args );
    }

    /**
     * Get the canonical product ID for an order or subscription item.
     *
     * Wraps the lifecycle helper so external code can resolve product IDs
     * without depending on the plugin's utility functions.
     *
     * @param mixed $item Order or subscription line item.
     *
     * @return int Product ID.
     */
    public static function get_canonical_product_id( $item ) {
        return aswc_get_canonical_product_id( $item );
    }

    /**
     * Retrieve an order by its ID.
     *
     * Provides a wrapper around WooCommerce's `wc_get_order` so external code
     * can fetch orders without introducing a direct dependency on WooCommerce
     * functions.
     *
     * @param int $order_id Order ID.
     *
     * @return WC_Order|false Order object or false if unavailable.
     */
    public static function get_order( $order_id ) {
        return aswc_get_order( $order_id );
    }

    /**
     * Retrieve the payment gateway used by an order.
     *
     * Provides a wrapper around WooCommerce's `wc_get_payment_gateway_by_order`
     * so external code can fetch the gateway without introducing a direct
     * dependency on WooCommerce functions.
     *
     * @param int|WC_Order $order Order ID or instance.
     * @return WC_Payment_Gateway|false Gateway instance or false if unavailable.
     */
    public static function get_payment_gateway_by_order( $order ) {
        return aswc_get_payment_gateway_by_order( $order );
    }

    /**
     * Retrieve an order item from an order.
     *
     * Exposes the lifecycle helper to fetch order items without a hard
     * dependency on external functions.
     *
     * @param int      $item_id Order item ID.
     * @param WC_Order $order   Order instance.
     *
     * @return WC_Order_Item|false Order item or false if unavailable.
     */
    public static function get_order_item( $item_id, $order ) {
        return aswc_get_order_item( $item_id, $order );
    }

    /**
     * Create a renewal order for a subscription.
     *
     * Exposes the lifecycle helper so external code can generate renewal
     * orders without depending on plugin functions.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @return WC_Order|WP_Error Renewal order on success or WP_Error on failure.
     */
    public static function create_renewal_order( $subscription ) {
        return aswc_create_renewal_order( $subscription );
    }

    /**
     * Retrieve the retry interval from a retry rule object.
     *
     * @param object $rule Retry rule instance.
     * @return int Interval in seconds. Returns 0 if unavailable.
     */
    public static function get_retry_interval_from_rule( $rule ) {
        return aswc_get_retry_interval_from_rule( $rule );
    }

    /**
     * Retrieve the raw data from a retry rule object.
     *
     * @param object $rule Retry rule instance.
     * @return array Raw rule data. Returns an empty array if unavailable.
     */
    public static function get_retry_rule_raw_data( $rule ) {
        return aswc_get_retry_rule_raw_data( $rule );
    }

    /**
     * Retrieve the status to apply from a retry rule object for a given item.
     *
     * @param object $rule       Retry rule instance.
     * @param string $object_key Object type to retrieve the status for.
     * @return string Status slug or empty string if unavailable.
     */
    public static function get_retry_rule_status_to_apply( $rule, $object_key ) {
        return aswc_get_retry_rule_status_to_apply( $rule, $object_key );
    }

    /**
     * Determine whether a retry rule defines an email template for a recipient.
     *
     * @param object $rule      Retry rule instance.
     * @param string $recipient Recipient identifier. Defaults to 'customer'.
     * @return bool True if a template exists, false otherwise.
     */
    public static function retry_rule_has_email_template( $rule, $recipient = 'customer' ) {
        return aswc_retry_rule_has_email_template( $rule, $recipient );
    }

    /**
     * Retrieve the email template defined by a retry rule for a recipient.
     *
     * @param object $rule      Retry rule instance.
     * @param string $recipient Recipient identifier. Defaults to 'customer'.
     * @return string Email template slug or empty string if unavailable.
     */
    public static function get_retry_rule_email_template( $rule, $recipient = 'customer' ) {
        return aswc_get_retry_rule_email_template( $rule, $recipient );
    }

    /**
     * Determine whether a value represents a subscription.
     *
     * Provides access to the core wrapper so external code can check
     * subscription objects or IDs without referencing plugin helpers.
     *
     * @param mixed $subscription Value to test.
     * @return bool True if the value is a subscription.
     */
    public static function is_subscription( $subscription ) {
        return aswc_is_subscription( $subscription );
    }

    /**
     * Retrieve a WooCommerce logger instance.
     *
     * Exposes the core wrapper so external code can obtain a logger without
     * referencing WooCommerce helpers directly.
     *
     * @return WC_Logger_Interface|null Logger instance or null if unavailable.
     */
    public static function get_logger() {
        return aswc_get_logger();
    }

    /**
     * Retrieve the edit post link for a given post ID.
     *
     * @param int $post_id Post ID.
     * @return string|false Edit link or false if unavailable.
     */
    public static function get_edit_post_link( $post_id ) {
        return aswc_get_edit_post_link( $post_id );
    }

    /**
     * Retrieve the plugin directory path.
     *
     * Provides access to the core wrapper so external code can determine the
     * plugin's directory without referencing the main plugin class directly.
     *
     * @param string $path Optional subpath to append to the plugin directory.
     * @return string Plugin directory path or empty string if unavailable.
     */
    public static function get_plugin_directory( $path = '' ) {
        return aswc_get_plugin_directory( $path );
    }

    /**
     * Get all scheduled action objects for a subscription.
     *
     * Combines payment, lifecycle and customer notification actions to provide
     * a complete overview of the queued Action Scheduler entries.
     *
     * @param WC_Subscription $subscription           Subscription instance.
     * @param array           $notification_date_types Optional notification
     *                                                date types to include.
     *
     * @return array Map of event type => ActionScheduler_Action.
     */
    public static function get_all_scheduled_subscription_actions( $subscription, $notification_date_types = null, $group = null ) {
        if ( null === $notification_date_types ) {
            $notification_date_types = array_keys( aswc_get_subscription_date_types() );
        }
        return array_merge(
            self::get_scheduled_subscription_actions( $subscription, $group ),
            self::get_scheduled_notification_actions( $subscription, $notification_date_types, $group )
        );
    }

    /**
     * Get all scheduled events for a subscription.
     *
     * Combines payment, lifecycle and customer notification events to provide
     * a complete overview of every action queued for the subscription.
     *
     * @param WC_Subscription $subscription           Subscription instance.
     * @param array           $notification_date_types Optional notification
     *                                                date types to include.
     *
     * @return array Map of event type => timestamp for scheduled events.
     */
    public static function get_all_scheduled_events( $subscription, $notification_date_types = null, $group = null ) {
        if ( null === $notification_date_types ) {
            $notification_date_types = array_keys( aswc_get_subscription_date_types() );
        }
        return array_merge(
            self::get_scheduled_events( $subscription, $group ),
            self::get_scheduled_notifications( $subscription, $notification_date_types, $group )
        );
    }

    /**
     * Get the most recent scheduled action objects for a subscription across all modules.
     *
     * Combines the latest payment, lifecycle and customer notification actions
     * so callers can inspect historical scheduling information from a single
     * access point.
     *
     * @param WC_Subscription $subscription           Subscription instance.
     * @param array           $notification_date_types Optional notification
     *                                                date types to include.
     *
     * @return array Map of event type => ActionScheduler_Action.
     */
    public static function get_all_last_scheduled_subscription_actions( $subscription, $notification_date_types = null, $group = null ) {
        if ( null === $notification_date_types ) {
            $notification_date_types = array_keys( aswc_get_subscription_date_types() );
        }
        return array_merge(
            self::get_last_scheduled_subscription_actions( $subscription, $group ),
            self::get_last_scheduled_notification_actions( $subscription, $notification_date_types, $group )
        );
    }

    /**
     * Get the most recent scheduled events for a subscription across all modules.
     *
     * Combines the latest payment, lifecycle and customer notification events
     * so callers can inspect historical scheduling information from a single
     * access point.
     *
     * @param WC_Subscription $subscription           Subscription instance.
     * @param array           $notification_date_types Optional notification
     *                                                date types to include.
     *
     * @return array Map of event type => timestamp for scheduled events.
     */
    public static function get_all_last_scheduled_events( $subscription, $notification_date_types = null, $group = null ) {
        if ( null === $notification_date_types ) {
            $notification_date_types = array_keys( aswc_get_subscription_date_types() );
        }
        return array_merge(
            self::get_last_scheduled_events( $subscription, $group ),
            self::get_last_scheduled_notifications( $subscription, $notification_date_types, $group )
        );
    }
}
// --- Hook registrations for scheduled payments & retries ---

/**
 * Handle scheduled subscription payment (normal renewal).
 *
 * Some older schedules may still pass a deprecated 2nd param; keep the signature at 2.
 */
add_action(
    'advanced_scheduled_subscription_payment',
    function ( $subscription_id, $deprecated = null ) {
        ASWC_Scheduler_API::gateway_scheduled_subscription_payment( $subscription_id, $deprecated );
    },
    10,
    2
);

/**
 * Handle scheduled subscription payment retry.
 *
 * We normalize the argument order because some schedules may have been created
 * with positional args [attempt, subscription_id] while newer ones use
 * ['subscription_id' => ID, 'attempt' => N].
 */
add_action(
    'advanced_scheduled_subscription_payment_retry',
    function ( $arg0 = null, $arg1 = null, $arg2 = null ) {
        // Accept both associative and positional args.
        $subscription_id = null;
        $attempt         = null;

        // Case 1: associative args wrapped in an array, e.g. ['subscription_id' => 123, 'attempt' => 0].
        if ( is_array( $arg0 ) && ( isset( $arg0['subscription_id'] ) || isset( $arg0['attempt'] ) ) ) {
            $subscription_id = isset( $arg0['subscription_id'] ) ? absint( $arg0['subscription_id'] ) : null;
            $attempt         = isset( $arg0['attempt'] ) ? absint( $arg0['attempt'] ) : null;
        } else {
            // Case 2: positional args; try to detect which is which.
            $maybe_a = is_numeric( $arg0 ) ? absint( $arg0 ) : null;
            $maybe_b = is_numeric( $arg1 ) ? absint( $arg1 ) : null;

            // Prefer the value that is an aswc_subscriptions post as the subscription ID.
            if ( $maybe_a && 'aswc_subscriptions' === get_post_type( $maybe_a ) ) {
                $subscription_id = $maybe_a;
                $attempt         = $maybe_b;
            } elseif ( $maybe_b && 'aswc_subscriptions' === get_post_type( $maybe_b ) ) {
                $subscription_id = $maybe_b;
                $attempt         = $maybe_a;
            } else {
                // Fallback: assume the second param is the subscription id (as seen in some logs).
                $subscription_id = $maybe_b ?: $maybe_a;
                $attempt         = ( $subscription_id === $maybe_a ) ? $maybe_b : $maybe_a;
            }
        }

        if ( $subscription_id ) {
            ASWC_Scheduler_API::process_scheduled_retry( $subscription_id, $attempt );
        }
    },
    10,
    3
);

