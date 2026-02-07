<?php
/**
 * Subscription lifecycle scheduling utilities.
 *
 * Handles scheduling and unscheduling of subscription lifecycle events such as
 * trial end, expiration and end-of-prepaid-term using the core Scheduler API.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI
 */

class ASWC_Scheduler_Lifecycle {
    /**
     * Core scheduler instance.
     *
     * @var ASWC_Scheduler_Core
     */
    protected $scheduler;

    /**
     * Constructor.
     *
     * @param ASWC_Scheduler_Core $scheduler Scheduler core implementation.
     */
    public function __construct( ASWC_Scheduler_Core $scheduler ) {
        $this->scheduler = $scheduler;
    }

    /**
     * Schedule the end of a trial period for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param int|null        $timestamp    When the trial should end. Defaults to the subscription's trial end.
     * @param string|null     $group        Optional action scheduler group.
     *
     * @return void
     */
    public function schedule_trial_end( $subscription, $timestamp = null, $group = null ) {
        if ( null === $timestamp ) {
            $timestamp = $subscription->get_time( 'trial_end' );
        }

        if ( empty( $timestamp ) || $timestamp <= 0 ) {
            $this->unschedule_trial_end( $subscription, $group );
            return;
        }

        $action_hook = 'advanced_scheduled_subscription_trial_end';
        $action_args = $this->scheduler->get_action_args( 'trial_end', $subscription );

        $this->scheduler->reschedule_action( $timestamp, $action_hook, $action_args, $group );
    }

    /**
     * Remove any scheduled trial end for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return void
     */
    public function unschedule_trial_end( $subscription, $group = null ) {
        $this->scheduler->unschedule_actions(
            'advanced_scheduled_subscription_trial_end',
            $this->scheduler->get_action_args( 'trial_end', $subscription ),
            $group
        );
    }

    /**
     * Schedule the expiration of a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param int|null        $timestamp    When the subscription should expire. Defaults to the subscription's end date.
     * @param string|null     $group        Optional action scheduler group.
     *
     * @return void
     */
    public function schedule_expiration( $subscription, $timestamp = null, $group = null ) {
        if ( null === $timestamp ) {
            $timestamp = $subscription->get_time( 'end' );
        }

        if ( empty( $timestamp ) || $timestamp <= 0 ) {
            $this->unschedule_expiration( $subscription, $group );
            return;
        }

        $action_hook = 'advanced_scheduled_subscription_expiration';
        $action_args = $this->scheduler->get_action_args( 'end', $subscription );

        $this->scheduler->reschedule_action( $timestamp, $action_hook, $action_args, $group );
    }

    /**
     * Remove any scheduled expiration for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return void
     */
    public function unschedule_expiration( $subscription, $group = null ) {
        $this->scheduler->unschedule_actions(
            'advanced_scheduled_subscription_expiration',
            $this->scheduler->get_action_args( 'end', $subscription ),
            $group
        );
    }

    /**
     * Schedule the end of the prepaid term for a cancelled subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param int|null        $timestamp    When the prepaid term should end. Defaults to the subscription's end date.
     * @param string|null     $group        Optional action scheduler group.
     *
     * @return void
     */
    public function schedule_end_of_prepaid_term( $subscription, $timestamp = null, $group = null ) {
        if ( null === $timestamp ) {
            $timestamp = $subscription->get_time( 'end' );
        }

        if ( empty( $timestamp ) || $timestamp <= 0 ) {
            $this->unschedule_end_of_prepaid_term( $subscription, $group );
            return;
        }

        $action_hook = 'advanced_scheduled_subscription_end_of_prepaid_term';
        $action_args = $this->scheduler->get_action_args( 'end', $subscription );

        $this->scheduler->reschedule_action( $timestamp, $action_hook, $action_args, $group );
    }

    /**
     * Remove any scheduled end of prepaid term for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return void
     */
    public function unschedule_end_of_prepaid_term( $subscription, $group = null ) {
        $this->scheduler->unschedule_actions(
            'advanced_scheduled_subscription_end_of_prepaid_term',
            $this->scheduler->get_action_args( 'end', $subscription ),
            $group
        );
    }

    /**
     * Determine if a trial end is scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return bool
     */
    public function has_scheduled_trial_end( $subscription, $group = null ) {
        return $this->scheduler->has_scheduled_action(
            'advanced_scheduled_subscription_trial_end',
            $this->scheduler->get_action_args( 'trial_end', $subscription ),
            $group
        );
    }

    /**
     * Determine if an expiration is scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return bool
     */
    public function has_scheduled_expiration( $subscription, $group = null ) {
        return $this->scheduler->has_scheduled_action(
            'advanced_scheduled_subscription_expiration',
            $this->scheduler->get_action_args( 'end', $subscription ),
            $group
        );
    }

    /**
     * Determine if an end-of-prepaid-term event is scheduled.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return bool
     */
    public function has_scheduled_end_of_prepaid_term( $subscription, $group = null ) {
        return $this->scheduler->has_scheduled_action(
            'advanced_scheduled_subscription_end_of_prepaid_term',
            $this->scheduler->get_action_args( 'end', $subscription ),
            $group
        );
    }

    /**
     * Check if any lifecycle events are scheduled for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return bool
     */
    public function has_scheduled_events( $subscription, $group = null ) {
        return $this->has_scheduled_trial_end( $subscription, $group )
            || $this->has_scheduled_expiration( $subscription, $group )
            || $this->has_scheduled_end_of_prepaid_term( $subscription, $group );
    }

    /**
     * Get the scheduled timestamp for a trial end event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return int|false Timestamp or false if no action is queued.
     */
    public function get_scheduled_trial_end( $subscription, $group = null ) {
        return $this->scheduler->next_scheduled_action(
            'advanced_scheduled_subscription_trial_end',
            $this->scheduler->get_action_args( 'trial_end', $subscription ),
            $group
        );
    }

    /**
     * Get the scheduled timestamp for an expiration event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return int|false Timestamp or false if no action is queued.
     */
    public function get_scheduled_expiration( $subscription, $group = null ) {
        return $this->scheduler->next_scheduled_action(
            'advanced_scheduled_subscription_expiration',
            $this->scheduler->get_action_args( 'end', $subscription ),
            $group
        );
    }

    /**
     * Get the scheduled timestamp for an end-of-prepaid-term event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return int|false Timestamp or false if no action is queued.
     */
    public function get_scheduled_end_of_prepaid_term( $subscription, $group = null ) {
        return $this->scheduler->next_scheduled_action(
            'advanced_scheduled_subscription_end_of_prepaid_term',
            $this->scheduler->get_action_args( 'end', $subscription ),
            $group
        );
    }

    /**
     * Retrieve the scheduled action object for a trial end event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none found.
     */
    public function get_scheduled_trial_end_action( $subscription, $group = null ) {
        return $this->scheduler->get_scheduled_action(
            'advanced_scheduled_subscription_trial_end',
            $this->scheduler->get_action_args( 'trial_end', $subscription ),
            $group
        );
    }

    /**
     * Retrieve the scheduled action object for an expiration event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none found.
     */
    public function get_scheduled_expiration_action( $subscription, $group = null ) {
        return $this->scheduler->get_scheduled_action(
            'advanced_scheduled_subscription_expiration',
            $this->scheduler->get_action_args( 'end', $subscription ),
            $group
        );
    }

    /**
     * Retrieve the scheduled action object for an end-of-prepaid-term event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none found.
     */
    public function get_scheduled_end_of_prepaid_term_action( $subscription, $group = null ) {
        return $this->scheduler->get_scheduled_action(
            'advanced_scheduled_subscription_end_of_prepaid_term',
            $this->scheduler->get_action_args( 'end', $subscription ),
            $group
        );
    }

    /**
     * Get the most recent scheduled trial end timestamp.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return int|false Timestamp or false if no action exists.
     */
    public function last_scheduled_trial_end( $subscription, $group = null ) {
        return $this->scheduler->last_scheduled_action(
            'advanced_scheduled_subscription_trial_end',
            $this->scheduler->get_action_args( 'trial_end', $subscription ),
            $group
        );
    }

    /**
     * Get the most recent scheduled expiration timestamp.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return int|false Timestamp or false if no action exists.
     */
    public function last_scheduled_expiration( $subscription, $group = null ) {
        return $this->scheduler->last_scheduled_action(
            'advanced_scheduled_subscription_expiration',
            $this->scheduler->get_action_args( 'end', $subscription ),
            $group
        );
    }

    /**
     * Get the most recent scheduled end-of-prepaid-term timestamp.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return int|false Timestamp or false if no action exists.
     */
    public function last_scheduled_end_of_prepaid_term( $subscription, $group = null ) {
        return $this->scheduler->last_scheduled_action(
            'advanced_scheduled_subscription_end_of_prepaid_term',
            $this->scheduler->get_action_args( 'end', $subscription ),
            $group
        );
    }

    /**
     * Retrieve the most recently scheduled action object for a trial end event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none exists.
     */
    public function get_last_scheduled_trial_end_action( $subscription, $group = null ) {
        return $this->scheduler->get_last_scheduled_action(
            'advanced_scheduled_subscription_trial_end',
            $this->scheduler->get_action_args( 'trial_end', $subscription ),
            $group
        );
    }

    /**
     * Retrieve the most recently scheduled action object for an expiration event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none exists.
     */
    public function get_last_scheduled_expiration_action( $subscription, $group = null ) {
        return $this->scheduler->get_last_scheduled_action(
            'advanced_scheduled_subscription_expiration',
            $this->scheduler->get_action_args( 'end', $subscription ),
            $group
        );
    }

    /**
     * Retrieve the most recently scheduled action object for an end-of-prepaid-term event.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|bool     $group        Action Scheduler group. Defaults to this scheduler's group. Pass
     *                                      `false` to search across all groups.
     *
     * @return ActionScheduler_Action|false The action object or false if none exists.
     */
    public function get_last_scheduled_end_of_prepaid_term_action( $subscription, $group = null ) {
        return $this->scheduler->get_last_scheduled_action(
            'advanced_scheduled_subscription_end_of_prepaid_term',
            $this->scheduler->get_action_args( 'end', $subscription ),
            $group
        );
    }

    /**
     * Get the most recent lifecycle action objects scheduled for a subscription.
     *
     * Returns a map of event type to the Action Scheduler object so callers
     * can inspect metadata for each lifecycle hook.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array Map of event type => ActionScheduler_Action.
     */
    public function get_last_scheduled_actions( $subscription, $group = null ) {
        $actions = array();

        $trial_end = $this->get_last_scheduled_trial_end_action( $subscription, $group );
        if ( $trial_end ) {
            $actions['trial_end'] = $trial_end;
        }

        $expiration = $this->get_last_scheduled_expiration_action( $subscription, $group );
        if ( $expiration ) {
            $actions['expiration'] = $expiration;
        }

        $prepaid_term = $this->get_last_scheduled_end_of_prepaid_term_action( $subscription, $group );
        if ( $prepaid_term ) {
            $actions['end_of_prepaid_term'] = $prepaid_term;
        }

        return $actions;
    }

    /**
     * Get the most recent lifecycle events scheduled for a subscription.
     *
     * Returns a map of event type to the timestamp when the action was last
     * scheduled so callers can inspect historical scheduling.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array Map of event type => timestamp for scheduled events.
     */
    public function get_last_scheduled_events( $subscription, $group = null ) {
        $scheduled = array();

        $trial_end = $this->last_scheduled_trial_end( $subscription, $group );
        if ( $trial_end ) {
            $scheduled['trial_end'] = $trial_end;
        }

        $expiration = $this->last_scheduled_expiration( $subscription, $group );
        if ( $expiration ) {
            $scheduled['expiration'] = $expiration;
        }

        $prepaid_term = $this->last_scheduled_end_of_prepaid_term( $subscription, $group );
        if ( $prepaid_term ) {
            $scheduled['end_of_prepaid_term'] = $prepaid_term;
        }

        return $scheduled;
    }

    /**
     * Get all scheduled lifecycle events for a subscription.
     *
     * Returns a map of event type to the timestamp when the action is
     * scheduled to run so callers can easily inspect which lifecycle
     * events are queued.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array Map of event type => timestamp for scheduled events.
     */
    public function get_scheduled_events( $subscription, $group = null ) {
        $scheduled = array();

        $trial_end = $this->get_scheduled_trial_end( $subscription, $group );
        if ( $trial_end ) {
            $scheduled['trial_end'] = $trial_end;
        }

        $expiration = $this->get_scheduled_expiration( $subscription, $group );
        if ( $expiration ) {
            $scheduled['expiration'] = $expiration;
        }

        $prepaid_term = $this->get_scheduled_end_of_prepaid_term( $subscription, $group );
        if ( $prepaid_term ) {
            $scheduled['end_of_prepaid_term'] = $prepaid_term;
        }

        return $scheduled;
    }

    /**
     * Get scheduled lifecycle action objects for a subscription.
     *
     * Returns a map of event type to the Action Scheduler object so callers
     * can inspect the queued lifecycle hooks directly.
     *
     * @param WC_Subscription $subscription Subscription instance.
     *
     * @return array Map of event type => ActionScheduler_Action for scheduled events.
     */
    public function get_scheduled_actions( $subscription, $group = null ) {
        $actions = array();

        $trial_end = $this->get_scheduled_trial_end_action( $subscription, $group );
        if ( $trial_end ) {
            $actions['trial_end'] = $trial_end;
        }

        $expiration = $this->get_scheduled_expiration_action( $subscription, $group );
        if ( $expiration ) {
            $actions['expiration'] = $expiration;
        }

        $prepaid_term = $this->get_scheduled_end_of_prepaid_term_action( $subscription, $group );
        if ( $prepaid_term ) {
            $actions['end_of_prepaid_term'] = $prepaid_term;
        }

        return $actions;
    }

    /**
     * Schedule all lifecycle events for a subscription.
     *
     * Convenience wrapper that schedules the trial end and either the
     * expiration or end-of-prepaid-term event based on the subscription's
     * current status. Any opposing hooks are cleared to avoid duplicate
     * lifecycle events.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string|null     $group        Optional action scheduler group.
     *
     * @return void
     */
    public function schedule_all( $subscription, $group = null ) {
        $this->schedule_trial_end( $subscription, null, $group );

        if ( $subscription->has_status( array( 'cancelled', 'pending-cancel' ) ) ) {
            // Cancelled subscriptions should fire end-of-prepaid-term instead of expiration.
            $this->unschedule_expiration( $subscription, $group );
            $this->schedule_end_of_prepaid_term( $subscription, null, $group );
        } else {
            // Active subscriptions need the expiration hook, not end-of-prepaid-term.
            $this->unschedule_end_of_prepaid_term( $subscription, $group );
            $this->schedule_expiration( $subscription, null, $group );
        }
    }

    /**
     * Unschedule all lifecycle events for a subscription.
     *
     * @param WC_Subscription $subscription Subscription instance.
     */
    public function unschedule_all( $subscription, $group = null ) {
        $this->unschedule_trial_end( $subscription, $group );
        $this->unschedule_expiration( $subscription, $group );
        $this->unschedule_end_of_prepaid_term( $subscription, $group );
    }
}


