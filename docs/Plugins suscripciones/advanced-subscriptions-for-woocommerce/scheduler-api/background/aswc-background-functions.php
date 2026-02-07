<?php
/**
 * Background scheduling helper functions for the Scheduler API.
 *
 * Provides procedural wrappers around the background scheduler so external
 * code can interact with background actions without instantiating classes.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI
 */

if ( ! function_exists( 'aswc_schedule_background_action' ) ) {
    /**
     * Schedule a background action.
     *
     * @param int         $timestamp   When the action should run.
     * @param string      $action_hook Hook name for the action.
     * @param array       $action_args Optional arguments for the action.
     * @param bool        $unique      Whether the action should be unique.
     * @param string|null $group       Optional Action Scheduler group.
     *
     * @return int Action ID.
     */
    function aswc_schedule_background_action( $timestamp, $action_hook, $action_args = array(), $unique = false, $group = null ) {
        return ASWC_Scheduler_API::schedule_background_action( $timestamp, $action_hook, $action_args, $unique, $group );
    }
}

if ( ! function_exists( 'aswc_reschedule_background_action' ) ) {
    /**
     * Reschedule a background action.
     *
     * @param int         $timestamp   When the action should run.
     * @param string      $action_hook Hook name for the action.
     * @param array       $action_args Optional arguments for the action.
     * @param string|null $group       Optional Action Scheduler group.
     *
     * @return void
     */
    function aswc_reschedule_background_action( $timestamp, $action_hook, $action_args = array(), $group = null ) {
        ASWC_Scheduler_API::reschedule_background_action( $timestamp, $action_hook, $action_args, $group );
    }
}

if ( ! function_exists( 'aswc_unschedule_background_action' ) ) {
    /**
     * Unschedule background actions.
     *
     * @param string      $action_hook Action hook to clear.
     * @param array       $action_args Optional arguments for the action.
     * @param string|bool $group       Optional Action Scheduler group. Pass false
     *                                 to ignore groups.
     *
     * @return void
     */
    function aswc_unschedule_background_action( $action_hook, $action_args = array(), $group = null ) {
        ASWC_Scheduler_API::unschedule_background_action( $action_hook, $action_args, $group );
    }
}

if ( ! function_exists( 'aswc_has_scheduled_background_action' ) ) {
    /**
     * Determine if a background action is scheduled.
     *
     * @param string $action_hook Action hook to check.
     * @param array  $action_args Optional action arguments.
     * @param string|null $group  Optional Action Scheduler group.
     *
     * @return bool
     */
    function aswc_has_scheduled_background_action( $action_hook, $action_args = array(), $group = null ) {
        return ASWC_Scheduler_API::has_scheduled_background_action( $action_hook, $action_args, $group );
    }
}

if ( ! function_exists( 'aswc_next_scheduled_background_action' ) ) {
    /**
     * Get the timestamp for the next scheduled background action.
     *
     * @param string $action_hook Action hook to check.
     * @param array  $action_args Optional action arguments.
     * @param string|null $group  Optional Action Scheduler group.
     *
     * @return int|false Timestamp or false if none found.
     */
    function aswc_next_scheduled_background_action( $action_hook, $action_args = array(), $group = null ) {
        return ASWC_Scheduler_API::next_scheduled_background_action( $action_hook, $action_args, $group );
    }
}

if ( ! function_exists( 'aswc_last_scheduled_background_action' ) ) {
    /**
     * Get the timestamp for the most recently scheduled background action.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Optional action arguments.
     * @param string|bool $group       Optional Action Scheduler group. Pass false
     *                                 to search across all groups.
     *
     * @return int|false Timestamp of the latest scheduled action or false if none found.
     */
    function aswc_last_scheduled_background_action( $action_hook, $action_args = array(), $group = null ) {
        return ASWC_Scheduler_API::last_scheduled_background_action( $action_hook, $action_args, $group );
    }
}

if ( ! function_exists( 'aswc_get_scheduled_background_action' ) ) {
    /**
     * Retrieve the first scheduled background action matching a hook and arguments.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Optional action arguments.
     * @param string|bool $group       Optional Action Scheduler group. Pass false
     *                                 to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none found.
     */
    function aswc_get_scheduled_background_action( $action_hook, $action_args = array(), $group = null ) {
        return ASWC_Scheduler_API::get_scheduled_background_action( $action_hook, $action_args, $group );
    }
}

if ( ! function_exists( 'aswc_get_scheduled_background_actions' ) ) {
    /**
     * Retrieve background actions matching a hook and arguments.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Optional action arguments.
     * @param string|bool $group       Optional Action Scheduler group. Pass false
     *                                 to search across all groups.
     *
     * @return ActionScheduler_Action[] Array of action objects.
     */
    function aswc_get_scheduled_background_actions( $action_hook, $action_args = array(), $group = null ) {
        return ASWC_Scheduler_API::get_scheduled_background_actions( $action_hook, $action_args, $group );
    }
}

if ( ! function_exists( 'aswc_get_last_scheduled_background_action' ) ) {
    /**
     * Retrieve the most recently scheduled background action matching a hook and arguments.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Optional action arguments.
     * @param string|bool $group       Optional Action Scheduler group. Pass false
     *                                 to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none found.
     */
    function aswc_get_last_scheduled_background_action( $action_hook, $action_args = array(), $group = null ) {
        return ASWC_Scheduler_API::get_last_scheduled_background_action( $action_hook, $action_args, $group );
    }
}

if ( ! function_exists( 'aswc_get_last_scheduled_background_actions' ) ) {
    /**
     * Retrieve all background actions scheduled at the latest occurrence of a hook.
     *
     * @param string      $action_hook Action hook to check.
     * @param array       $action_args Optional action arguments.
     * @param string|bool $group       Optional Action Scheduler group. Pass false
     *                                 to search across all groups.
     *
     * @return ActionScheduler_Action[] Array of action objects.
     */
    function aswc_get_last_scheduled_background_actions( $action_hook, $action_args = array(), $group = null ) {
        return ASWC_Scheduler_API::get_last_scheduled_background_actions( $action_hook, $action_args, $group );
    }
}

if ( ! function_exists( 'aswc_unschedule_background_group' ) ) {
    /**
     * Unschedule all background actions for the scheduler's group.
     *
     * @param string|bool $group Optional Action Scheduler group. Pass false to
     *                           unschedule actions across all groups.
     *
     * @return void
     */
    function aswc_unschedule_background_group( $group = null ) {
        ASWC_Scheduler_API::unschedule_background_group( $group );
    }
}

