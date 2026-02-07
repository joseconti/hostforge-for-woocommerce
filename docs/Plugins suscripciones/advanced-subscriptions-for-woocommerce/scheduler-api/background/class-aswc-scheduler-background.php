<?php
/**
 * Background process scheduling utilities.
 *
 * Extends the core scheduler to use a dedicated Action Scheduler group for
 * batch processing and other background tasks.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI
 */

class ASWC_Scheduler_Background extends ASWC_Scheduler_Core {
    /**
     * Group for background process scheduled events.
     */
    const ACTION_GROUP = 'aswc_background_processes';

    /**
     * Build the option name used to store the background action group.
     *
     * @return string Option name.
     */
    protected static function get_group_option_name() {
        return 'advanced_subscriptions_woocommerce_scheduler_background_group';
    }

    /**
     * Retrieve the most recently scheduled actions for a given hook.
     *
     * Returns all actions whose scheduled timestamp matches the latest
     * occurrence for the provided hook and arguments.
     *
     * @param string      $action_hook Action hook to inspect.
     * @param array       $action_args Action arguments to match.
     * @param string|bool $group       Action Scheduler group. Defaults to the
     *                                 background group. Pass `false` to search
     *                                 across all groups.
     *
     * @return ActionScheduler_Action[] Array of action objects.
     */
    public function get_last_scheduled_actions( $action_hook, $action_args = array(), $group = null ) {
        $actions = $this->get_scheduled_actions( $action_hook, $action_args, $group );

        if ( empty( $actions ) ) {
            return array();
        }

        $latest = max( array_map( function( $action ) {
            $schedule = aswc_get_action_schedule( $action );
            return aswc_get_schedule_timestamp( $schedule );
        }, $actions ) );

        return array_values( array_filter( $actions, function( $action ) use ( $latest ) {
            $schedule = aswc_get_action_schedule( $action );
            return aswc_get_schedule_timestamp( $schedule ) === $latest;
        } ) );
    }
}

