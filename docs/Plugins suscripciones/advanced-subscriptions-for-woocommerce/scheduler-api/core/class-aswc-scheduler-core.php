<?php // phpcs:ignoreFile
/**
 * Core scheduler API.
 *
 * Provides wrappers around Action Scheduler functions so that scheduling
 * logic can be reused outside of the main plugin classes.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI
 */

class ASWC_Scheduler_Core {

	/**
	 * Group for subscription related scheduled events.
	 */
	const ACTION_GROUP = 'aswc_subscription_scheduled_event';

	/**
	 * Default priority for subscription events.
	 */
	const ACTION_PRIORITY = 1;

	/**
	 * Build the option name used to store the default action group.
	 *
	 * @return string Option name.
	 */
	protected static function get_group_option_name() {
		return 'advanced_subscriptions_woocommerce_scheduler_core_group';
	}

	/**
	 * Retrieve the default Action Scheduler group used by this scheduler.
	 *
	 * Exposing the group name allows external code to target the same
	 * group when querying or managing actions without needing to know the
	 * underlying constant value.
	 *
	 * @return string Action Scheduler group name.
	 */
	public static function get_group() {
		return get_option( static::get_group_option_name(), static::ACTION_GROUP );
	}

	/**
	 * Unschedule all actions for a given hook and arguments.
	 *
	 * @param string      $action_hook Action hook to clear. Pass `null` to match any hook.
	 * @param array       $action_args Action arguments.
	 * @param string|bool $group       Action Scheduler group. Defaults to this scheduler's group. Pass
	 *                                 `false` to ignore group when unscheduling.
	 *
	 * @return void
	 */
	public function unschedule_actions( $action_hook, $action_args, $group = null ) {
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log(
				sprintf(
					'[unschedule_actions] hook:%1$s group:%2$s args:%3$s',
					( null === $action_hook ? 'null' : $action_hook ),
					( null === $group ? 'default' : ( false === $group ? 'all' : $group ) ),
					wp_json_encode( $action_args )
				)
			);
		}

		if ( false === $group ) {
			aswc_unschedule_all_actions( $action_hook, $action_args );
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[unschedule_actions] cleared across all groups.' );
			}
			return;
		}

		aswc_unschedule_all_actions( $action_hook, $action_args, $group ?? static::get_group() );
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( '[unschedule_actions] cleared.' );
		}
	}

	/**
	 * Unschedule a single action for a given hook and arguments.
	 *
	 * @param string      $action_hook Action hook to clear.
	 * @param array       $action_args Action arguments.
	 * @param string|bool $group       Action Scheduler group. Defaults to this
	 *                                 scheduler's group. Pass false to ignore
	 *                                 the group when unscheduling.
	 *
	 * @return void
	 */
	public function unschedule_action( $action_hook, $action_args, $group = null ) {
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log(
				sprintf(
					'[unschedule_action] hook:%1$s group:%2$s args:%3$s',
					( null === $action_hook ? 'null' : $action_hook ),
					( null === $group ? 'default' : ( false === $group ? 'all' : $group ) ),
					wp_json_encode( $action_args )
				)
			);
		}

		if ( false === $group ) {
			aswc_unschedule_action( $action_hook, $action_args );
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[unschedule_action] cleared across all groups.' );
			}
			return;
		}

		aswc_unschedule_action( $action_hook, $action_args, $group ?? static::get_group() );
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( '[unschedule_action] cleared.' );
		}
	}

	/**
	 * Unschedule all actions for a given group.
	 *
	 * Provides a convenient way to clear every queued action managed by
	 * the scheduler without specifying individual hooks. If a custom
	 * group is provided, that group's actions will be removed instead of
	 * the scheduler's default group.
	 *
	 * @param string|bool $group Action Scheduler group. Defaults to this scheduler's group. Pass
	 *                           `false` to unschedule actions across all groups.
	 *
	 * @return void
	 */
	public function unschedule_group( $group = null ) {
		if ( false === $group ) {
			aswc_unschedule_all_actions();
			return;
		}

		aswc_unschedule_all_actions( null, array(), $group ?? static::get_group() );
	}

	/**
	 * Get the timestamp for the next scheduled action for a hook and arguments.
	 *
	 * @param string $action_hook Action hook to check.
	 * @param array  $action_args Action arguments.
	 *
	 * @return int|false
	 */
	public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log(
				sprintf(
					'[next_scheduled_action] hook:%1$s group:%2$s args:%3$s',
					( null === $action_hook ? 'null' : $action_hook ),
					( null === $group ? 'default' : ( false === $group ? 'all' : $group ) ),
					wp_json_encode( $action_args )
				)
			);
		}

		if ( false === $group ) {
			$timestamp = aswc_next_scheduled_action( $action_hook, $action_args );
		} else {
			$timestamp = aswc_next_scheduled_action( $action_hook, $action_args, $group ?? static::get_group() );
		}

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( '[next_scheduled_action] result:%s', false === $timestamp ? 'none' : $timestamp ) );
		}

		return $timestamp;
	}

	/**
	 * Get the most recent scheduled action for a hook and arguments.
	 *
	 * @param string      $action_hook Action hook to check.
	 * @param array       $action_args Action arguments.
	 * @param string|bool $group       Action Scheduler group. Defaults to this scheduler's group. Pass
	 *                                 `false` to search across all groups.
	 *
	 * @return int|false Timestamp of the latest scheduled action or false if none found.
	 */
	public function last_scheduled_action( $action_hook, $action_args, $group = null ) {
		$query_args = array(
			'hook'    => $action_hook,
			'args'    => $action_args,
			'orderby' => 'date',
			'order'   => 'DESC',
			'per_page'=> 1,
			'status'  => aswc_get_action_scheduler_pending_status(),
			'format'  => 'objects',
		);

		if ( false !== $group ) {
			$query_args['group'] = $group ?? static::get_group();
		}

		$actions = aswc_get_scheduled_actions( $query_args );

		if ( empty( $actions ) ) {
			return false;
		}

		$schedule = aswc_get_action_schedule( $actions[0] );

		return $schedule ? $this->get_schedule_timestamp( $schedule ) : false;
	}

	/**
	 * Determine if an action is scheduled for a hook and arguments.
	 *
	 * Wrapper around Action Scheduler's {@see as_has_scheduled_action()} that
	 * automatically scopes the query to this scheduler's group.
	 *
	 * @param string $action_hook Action hook to check.
	 * @param array  $action_args Action arguments.
	 *
	 * @return bool True if an action matching the hook and arguments exists.
	 */
	public function has_scheduled_action( $action_hook, $action_args, $group = null ) {
		if ( false === $group ) {
			return aswc_has_scheduled_action( $action_hook, $action_args );
		}

		return aswc_has_scheduled_action( $action_hook, $action_args, $group ?? static::get_group() );
	}

	/**
	 * Retrieve scheduled actions for a hook and arguments.
	 *
	 * Wrapper around {@see as_get_scheduled_actions()} that scopes the query
	 * to this scheduler's group by default.
	 *
	 * @param string      $action_hook Action hook to check.
	 * @param array       $action_args Action arguments.
	 * @param string|bool $group       Action Scheduler group. Defaults to this scheduler's
	 *                                 group. Pass `false` to search across all groups.
	 *
	 * @return ActionScheduler_Action[] Array of action objects.
	 */
	public function get_scheduled_actions( $action_hook, $action_args = array(), $group = null ) {
		$query_args = array(
			'hook'   => $action_hook,
			'args'   => $action_args,
			'status' => aswc_get_action_scheduler_pending_status(),
			'format' => 'objects',
		);

		if ( false !== $group ) {
			$query_args['group'] = $group ?? static::get_group();
		}

		return aswc_get_scheduled_actions( $query_args );
	}

	/**
	 * Retrieve the first scheduled action for a hook and arguments.
	 *
	 * @param string      $action_hook Action hook to check.
	 * @param array       $action_args Action arguments.
	 * @param string|bool $group       Action Scheduler group. Defaults to this scheduler's
	 *                                 group. Pass `false` to search across all groups.
	 *
	 * @return ActionScheduler_Action|false The action object or false if none found.
	 */
	public function get_scheduled_action( $action_hook, $action_args = array(), $group = null ) {
		$actions = $this->get_scheduled_actions( $action_hook, $action_args, $group );

		return reset( $actions ) ?: false;
	}

	/**
	 * Retrieve the most recently scheduled action for a hook and arguments.
	 *
	 * @param string      $action_hook Action hook to check.
	 * @param array       $action_args Action arguments.
	 * @param string|bool $group       Action Scheduler group. Defaults to this scheduler's
	 *                                 group. Pass `false` to search across all groups.
	 *
	 * @return ActionScheduler_Action|false The action object or false if none found.
	 */
	public function get_last_scheduled_action( $action_hook, $action_args = array(), $group = null ) {
		$query_args = array(
			'hook'    => $action_hook,
			'args'    => $action_args,
			'orderby' => 'date',
			'order'   => 'DESC',
			'per_page'=> 1,
			'status'  => aswc_get_action_scheduler_pending_status(),
			'format'  => 'objects',
		);

		if ( false !== $group ) {
			$query_args['group'] = $group ?? static::get_group();
		}

		$actions = aswc_get_scheduled_actions( $query_args );

		return empty( $actions ) ? false : $actions[0];
	}

	/**
	 * Retrieve a scheduled action by ID.
	 *
	 * @param int $action_id Action Scheduler action ID.
	 *
	 * @return ActionScheduler_Action|false The action object or false when unavailable.
	 */
	public function get_action( $action_id ) {
		return aswc_get_action_scheduler_action( $action_id );
	}

	/**
	 * Persist a scheduled action object to the data store.
	 *
	 * @param object      $action         Action object to save.
	 * @param DateTime|null $scheduled_date Optional scheduled date.
	 * @return int|false Action ID on success, false otherwise.
	 */
	public function save_scheduled_action( $action, $scheduled_date = null ) {
		return aswc_save_action( $action, $scheduled_date );
	}

	/**
	 * Cancel a scheduled action by its identifier.
	 *
	 * @param int $action_id Action ID to cancel.
	 * @return bool True on success, false otherwise.
	 */
	public function cancel_scheduled_action( $action_id ) {
		return aswc_cancel_action( $action_id );
	}

	/**
	 * Delete a scheduled action from the data store.
	 *
	 * @param int $action_id Action ID to delete.
	 * @return bool True on success, false otherwise.
	 */
	public function delete_scheduled_action( $action_id ) {
		return aswc_delete_action( $action_id );
	}

	/**
	 * Mark a scheduled action as complete.
	 *
	 * @param int $action_id Action ID to mark complete.
	 * @return bool True on success, false otherwise.
	 */
	public function mark_action_complete( $action_id ) {
		return aswc_mark_action_complete( $action_id );
	}

	/**
	 * Mark a scheduled action as failed.
	 *
	 * @param int $action_id Action ID to mark failed.
	 * @return bool True on success, false otherwise.
	 */
	public function mark_action_failed( $action_id ) {
		return aswc_mark_action_failed( $action_id );
	}

	/**
	 * Claim a set of scheduled actions for processing.
	 *
	 * @param int        $claim_id    Claim identifier.
	 * @param int        $limit       Maximum number of actions to claim.
	 * @param DateTime|null $before_date Optional upper bound on scheduled date.
	 * @param array      $hooks       Optional list of hooks to claim.
	 * @param string|bool $group      Action Scheduler group. Defaults to this scheduler's group.
	 *                                Pass false to claim across all groups.
	 * @return array List of claimed action IDs.
	 */
	public function claim_actions( $claim_id, $limit, $before_date = null, $hooks = array(), $group = null ) {
		$group = ( false === $group ) ? '' : ( $group ?? static::get_group() );

		return aswc_claim_actions( $claim_id, $limit, $before_date, $hooks, $group );
	}

	/**
	 * Release a previously claimed set of actions.
	 *
	 * @param int $claim_id Claim identifier to release.
	 * @return bool True on success, false otherwise.
	 */
	public function release_claim( $claim_id ) {
		return aswc_release_claim( $claim_id );
	}

	/**
	 * Unclaim a previously claimed action.
	 *
	 * @param int $action_id Action identifier to unclaim.
	 * @return bool True on success, false otherwise.
	 */
	public function unclaim_action( $action_id ) {
		return aswc_unclaim_action( $action_id );
	}

	/**
	 * Query scheduled actions directly from the data store.
	 *
	 * @param array $query_args Optional query arguments.
	 * @return array List of matching action IDs or objects.
	 */
	public function query_actions( $query_args = array() ) {
		return aswc_query_actions( $query_args );
	}

	/**
	 * Retrieve the hook name for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @return string|null Hook name or null when unavailable.
	 */
	public function get_action_hook( $action ) {
		return aswc_get_action_hook( $action );
	}

	/**
	 * Retrieve the arguments for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @return array Action arguments.
	 */
	public function get_action_args_from_action( $action ) {
		return aswc_get_action_args( $action );
	}

	/**
	 * Retrieve the schedule for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @return object|null Action schedule or null when unavailable.
	 */
	public function get_action_schedule( $action ) {
		return aswc_get_action_schedule( $action );
	}

	/**
	 * Retrieve the status for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @return string|null Action status or null when unavailable.
	 */
	public function get_action_status( $action ) {
		return aswc_get_action_status( $action );
	}

	/**
	 * Retrieve the group for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @return string Action group or empty string when unavailable.
	 */
	public function get_action_group( $action ) {
		return aswc_get_action_group( $action );
	}

	/**
	 * Retrieve the identifier for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @return int Action ID or 0 when unavailable.
	 */
	public function get_action_id( $action ) {
		return aswc_get_action_id( $action );
	}

	/**
	 * Retrieve the priority for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @return int Action priority or default of 10 when unavailable.
	 */
	public function get_action_priority_from_action( $action ) {
		return aswc_get_action_priority( $action );
	}

	/**
	 * Retrieve the attempt count for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @return int Number of attempts or 0 when unavailable.
	 */
	public function get_action_attempts_from_action( $action ) {
		return aswc_get_action_attempts( $action );
	}

	/**
	 * Retrieve the claim ID for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @return int Claim ID or 0 when unavailable.
	 */
	public function get_action_claim_id_from_action( $action ) {
		return aswc_get_action_claim_id( $action );
	}

	/**
	 * Retrieve the post ID associated with a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @return int Post ID or 0 when unavailable.
	 */
	public function get_action_post_id_from_action( $action ) {
		return aswc_get_action_post_id( $action );
	}

	/**
	 * Retrieve the user ID associated with a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @return int User ID or 0 when unavailable.
	 */
	public function get_action_user_id_from_action( $action ) {
		return aswc_get_action_user_id( $action );
	}

	/**
	 * Set the hook for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @param string $hook   Hook name.
	 * @return bool True on success, false when unavailable.
	 */
	public function set_action_hook( $action, $hook ) {
		return aswc_set_action_hook( $action, $hook );
	}

	/**
	 * Set the arguments for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @param array  $args   Action arguments.
	 * @return bool True on success, false when unavailable.
	 */
	public function set_action_args( $action, $args ) {
		return aswc_set_action_args( $action, $args );
	}

	/**
	 * Set the schedule for a scheduled action object.
	 *
	 * @param object $action   Scheduled action object.
	 * @param object $schedule Schedule object.
	 * @return bool True on success, false when unavailable.
	 */
	public function set_action_schedule( $action, $schedule ) {
		return aswc_set_action_schedule( $action, $schedule );
	}

	/**
	 * Set the group for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @param string $group  Action group.
	 * @return bool True on success, false when unavailable.
	 */
	public function set_action_group( $action, $group ) {
		return aswc_set_action_group( $action, $group );
	}

	/**
	 * Set the status for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @param string $status Action status.
	 * @return bool True on success, false when unavailable.
	 */
	public function set_action_status( $action, $status ) {
		return aswc_set_action_status( $action, $status );
	}

	/**
	 * Set the priority for a scheduled action object.
	 *
	 * @param object $action   Scheduled action object.
	 * @param int    $priority Priority level.
	 * @return bool True on success, false when unavailable.
	 */
	public function set_action_priority( $action, $priority ) {
		return aswc_set_action_priority( $action, $priority );
	}

	/**
	 * Set the attempt count for a scheduled action object.
	 *
	 * @param object $action   Scheduled action object.
	 * @param int    $attempts Attempt count.
	 * @return bool True on success, false when unavailable.
	 */
	public function set_action_attempts( $action, $attempts ) {
		return aswc_set_action_attempts( $action, $attempts );
	}

	/**
	 * Set the claim ID for a scheduled action object.
	 *
	 * @param object $action   Scheduled action object.
	 * @param int    $claim_id Claim identifier.
	 * @return bool True on success, false when unavailable.
	 */
	public function set_action_claim_id( $action, $claim_id ) {
		return aswc_set_action_claim_id( $action, $claim_id );
	}

	/**
	 * Set the post ID for a scheduled action object.
	 *
	 * @param object $action  Scheduled action object.
	 * @param int    $post_id Post ID.
	 * @return bool True on success, false when unavailable.
	 */
	public function set_action_post_id( $action, $post_id ) {
		return aswc_set_action_post_id( $action, $post_id );
	}

	/**
	 * Set the user ID for a scheduled action object.
	 *
	 * @param object $action  Scheduled action object.
	 * @param int    $user_id User ID.
	 * @return bool True on success, false when unavailable.
	 */
	public function set_action_user_id( $action, $user_id ) {
		return aswc_set_action_user_id( $action, $user_id );
	}

	/**
	 * Retrieve a meta value for a scheduled action object.
	 *
	 * @param object $action Scheduled action object.
	 * @param string $key    Meta key.
	 * @return mixed|null Meta value or null when unavailable.
	 */
	public function get_action_meta( $action, $key ) {
		return aswc_get_action_meta( $action, $key );
	}

	/**
	 * Save a meta value for a scheduled action.
	 *
	 * @param object $action Scheduled action object.
	 * @param string $key    Meta key.
	 * @param mixed  $value  Meta value.
	 * @return bool True on success, false when unavailable.
	 */
	public function set_action_meta( $action, $key, $value ) {
		return aswc_set_action_meta( $action, $key, $value );
	}

	/**
	 * Delete a meta value from a scheduled action.
	 *
	 * @param object $action Scheduled action object.
	 * @param string $key    Meta key.
	 * @return bool True on success, false when unavailable.
	 */
	public function delete_action_meta( $action, $key ) {
		return aswc_delete_action_meta( $action, $key );
	}

	/**
	 * Determine whether a scheduled action has finished executing.
	 *
	 * @param object $action Scheduled action object.
	 * @return bool True if the action is finished, false otherwise.
	 */
	public function is_action_finished( $action ) {
		return aswc_is_action_finished( $action );
	}

	/**
	 * Retrieve the timestamp for a schedule object.
	 *
	 * @param object $schedule Schedule object.
	 * @return int|false Unix timestamp for the schedule or false when unavailable.
	 */
	public function get_schedule_timestamp( $schedule ) {
		return aswc_get_schedule_timestamp( $schedule );
	}

	/**
	 * Retrieve the GMT timestamp for a schedule object.
	 *
	 * @param object $schedule Schedule object.
	 * @return int|false GMT Unix timestamp for the schedule or false when unavailable.
	 */
	public function get_schedule_gmt_timestamp( $schedule ) {
		return aswc_get_schedule_gmt_timestamp( $schedule );
	}

	/**
	 * Retrieve the next run timestamp for a schedule object.
	 *
	 * @param object         $schedule Schedule object.
	 * @param \DateTime|null $after    Optional starting point.
	 * @return int|false Unix timestamp for the next run or false when unavailable.
	 */
	public function get_schedule_next_timestamp( $schedule, $after = null ) {
		return aswc_get_schedule_next_timestamp( $schedule, $after );
	}

	/**
	 * Retrieve the recurrence interval for a schedule object.
	 *
	 * @param object $schedule Schedule object.
	 * @return int|false Recurrence interval in seconds or false when unavailable.
	 */
	public function get_schedule_recurrence( $schedule ) {
		return aswc_get_schedule_recurrence( $schedule );
	}

	/**
	 * Determine whether a schedule represents a recurring action.
	 *
	 * @param object $schedule Schedule object.
	 * @return bool True if the schedule is recurring, false otherwise.
	 */
	public function is_schedule_recurring( $schedule ) {
		return aswc_is_schedule_recurring( $schedule );
	}

	/**
	 * Get the priority for a scheduled action.
	 *
	 * @param string $action_hook Action hook.
	 *
	 * @return int
	 */
	public function get_action_priority( $action_hook ) {
		$priority = (int) get_option( 'advanced_subscriptions_woocommerce_scheduler_action_priority', self::ACTION_PRIORITY );

		return (int) apply_filters( 'advanced_subscriptions_woocommerce_scheduled_action_priority', $priority, $action_hook );
	}

	/**
	 * Schedule an action via Action Scheduler.
	 *
	 * @param int    $timestamp   When the action should run.
	 * @param string $action_hook Hook name for the action.
	 * @param array  $action_args Arguments for the action.
	 *
	 * @return int Action ID.
	 */
	public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
		$timestamp  = aswc_local_timestamp_to_utc( $timestamp );
		$as_version = aswc_get_latest_action_scheduler_version();

		$group = ( null === $group ) ? static::get_group() : $group;

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log(
				sprintf(
					'[schedule_action] hook:%1$s timestamp:%2$d group:%3$s unique:%4$s args:%5$s',
					$action_hook,
					$timestamp,
					$group,
					$unique ? 'true' : 'false',
					wp_json_encode( $action_args )
				)
			);
		}

		// On older versions of Action Scheduler, we cannot specify a priority or uniqueness.
		try {
			$action_id = version_compare( $as_version, '3.6.0', '<' )
				? aswc_schedule_single_action( $timestamp, $action_hook, $action_args, $group, $unique )
				: aswc_schedule_single_action(
					$timestamp,
					$action_hook,
					$action_args,
					$group,
					$unique,
					$this->get_action_priority( $action_hook )
				);
		} catch ( \Throwable $e ) {
			$logger = ASWC_Scheduler_API::get_logger();
			if ( $logger ) {
				$logger->error(
					sprintf( 'Failed scheduling action %s: %s', $action_hook, $e->getMessage() ),
					array( 'group' => $group, 'args' => $action_args )
				);
			}

			return 0;
		}

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( '[schedule_action] action_id:%d', $action_id ) );
		}

		return $action_id;
	}

	/**
	 * Schedule a recurring action via Action Scheduler.
	 *
	 * @param int         $timestamp   When the first action should run.
	 * @param int         $interval    Seconds between runs.
	 * @param string      $action_hook Hook name for the action.
	 * @param array       $action_args Arguments for the action.
	 * @param bool        $unique      Whether the action is unique.
	 * @param string|null $group       Optional Action Scheduler group.
	 *
	 * @return int Action ID.
	 */
	public function schedule_recurring_action( $timestamp, $interval, $action_hook, $action_args = array(), $unique = false, $group = null ) {
		$timestamp  = aswc_local_timestamp_to_utc( $timestamp );
		$as_version = aswc_get_latest_action_scheduler_version();

		$group = ( null === $group ) ? static::get_group() : $group;

		if ( version_compare( $as_version, '3.6.0', '<' ) ) {
			return aswc_schedule_recurring_action( $timestamp, $interval, $action_hook, $action_args, $group, $unique );
		}

		return aswc_schedule_recurring_action(
			$timestamp,
			$interval,
			$action_hook,
			$action_args,
			$group,
			$unique,
			$this->get_action_priority( $action_hook )
		);
	}

	/**
	 * Schedule a cron-like action via Action Scheduler.
	 *
	 * @param int         $timestamp   When the first action should run.
	 * @param string      $schedule    Cron schedule in WP-Cron format.
	 * @param string      $action_hook Hook name for the action.
	 * @param array       $action_args Arguments for the action.
	 * @param bool        $unique      Whether the action is unique.
	 * @param string|null $group       Optional Action Scheduler group.
	 *
	 * @return int Action ID.
	 */
	public function schedule_cron_action( $timestamp, $schedule, $action_hook, $action_args = array(), $unique = false, $group = null ) {
		$timestamp  = aswc_local_timestamp_to_utc( $timestamp );
		$as_version = aswc_get_latest_action_scheduler_version();

		$group = ( null === $group ) ? static::get_group() : $group;

		if ( version_compare( $as_version, '3.6.0', '<' ) ) {
			return aswc_schedule_cron_action( $timestamp, $schedule, $action_hook, $action_args, $group, $unique );
		}

		return aswc_schedule_cron_action(
			$timestamp,
			$schedule,
			$action_hook,
			$action_args,
			$group,
			$unique,
			$this->get_action_priority( $action_hook )
		);
	}

	/**
	 * Schedule a unique action via Action Scheduler.
	 *
	 * @param int         $timestamp   When the action should run.
	 * @param string      $action_hook Hook name for the action.
	 * @param array       $action_args Arguments for the action.
	 * @param string|null $group       Optional Action Scheduler group.
	 * @param int         $priority    Optional action priority.
	 *
	 * @return int Action ID.
	 */
	public function schedule_unique_action( $timestamp, $action_hook, $action_args = array(), $group = null, $priority = 10 ) {
		$timestamp = aswc_local_timestamp_to_utc( $timestamp );
		$group     = ( null === $group ) ? static::get_group() : $group;

		return aswc_schedule_unique_action( $timestamp, $action_hook, $action_args, $group, $priority );
	}

	/**
	 * Enqueue an async action via Action Scheduler.
	 *
	 * @param string $action_hook Hook name for the action.
	 * @param array  $action_args Arguments for the action.
	 * @param string|null $group  Optional Action Scheduler group.
	 *
	 * @return int Action ID.
	 */
	public function enqueue_async_action( $action_hook, $action_args = array(), $group = null ) {
		return aswc_enqueue_async_action(
			$action_hook,
			$action_args,
			$group ?? static::get_group()
		);
	}

	/**
	 * Reschedule an action by first clearing existing instances and then
	 * scheduling the action if the timestamp is in the future.
	 *
	 * @param int    $timestamp   When the action should run.
	 * @param string $action_hook Hook name for the action.
	 * @param array  $action_args Arguments for the action.
	 *
	 * @return void
	 */
	public function reschedule_action( $timestamp, $action_hook, $action_args, $group = null ) {
		$schedule_group = ( false === $group ) ? null : $group;
		$utc_timestamp  = aswc_local_timestamp_to_utc( $timestamp );

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log(
				sprintf(
					'[reschedule_action] hook:%1$s timestamp:%2$d group:%3$s args:%4$s',
					$action_hook,
					$utc_timestamp,
					( null === $group ? 'default' : ( false === $group ? 'all' : $group ) ),
					wp_json_encode( $action_args )
				)
			);
		}

		$next_scheduled = $this->next_scheduled_action( $action_hook, $action_args, $group );

		if ( false !== $next_scheduled && (int) $next_scheduled === (int) $utc_timestamp ) {
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[reschedule_action] existing action matches timestamp, skipping.' );
			}
			return;
		}

		try {
			$this->unschedule_actions( $action_hook, $action_args, $group );
		} catch ( \Throwable $e ) {
			$logger = ASWC_Scheduler_API::get_logger();
			if ( $logger ) {
				$logger->error(
					sprintf( 'Failed unscheduling action %s: %s', $action_hook, $e->getMessage() ),
					array( 'group' => $group, 'args' => $action_args )
				);
			}
		}

		if ( 0 === $utc_timestamp || $utc_timestamp <= current_time( 'timestamp', true ) ) {
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[reschedule_action] timestamp in the past or zero, skipping schedule.' );
			}
			return;
		}

		try {
			$this->schedule_action( $timestamp, $action_hook, $action_args, false, $schedule_group );
		} catch ( \Throwable $e ) {
			$logger = ASWC_Scheduler_API::get_logger();
			if ( $logger ) {
				$logger->error(
					sprintf( 'Failed rescheduling action %s: %s', $action_hook, $e->getMessage() ),
					array( 'group' => $schedule_group, 'args' => $action_args )
				);
			}
			return;
		}

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( '[reschedule_action] action scheduled.' );
		}
	}

	/**
	 * Build action args. Retries are always scoped to the subscription (not an order).
	 */
	public function get_action_args( $date_type, $subscription ) {
		$action_args = array( 'subscription_id' => $subscription->get_id() );

		// For backward compatibility, include the last renewal order ID on payment retries.
		// Some gateways/handlers still look for an "order_id" arg. We keep retries scoped
		// to the subscription, but pass an extra hint.
		if ( 'payment_retry' === $date_type ) {
			$last_order_id = (int) aswc_get_meta_data( $subscription->get_id(), '_aswc_last_renewal_order_id', true );
			if ( $last_order_id > 0 ) {
				$action_args['order_id'] = $last_order_id; // compat
			}
			$action_args['is_retry'] = true;
		}

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log(
				sprintf(
					'[get_action_args] date_type:%s args:%s',
					$date_type,
					wp_json_encode( $action_args )
				)
			);
		}

		return apply_filters( 'advanced_subscriptions_woocommerce_scheduled_action_args', $action_args, $date_type, $subscription );
	}

	/**
	 * Determine the action scheduler hook for a subscription date type.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string          $date_type    Date type being scheduled.
	 *
	 * @return string Hook name.
	 */
	public function get_scheduled_action_hook( $subscription, $date_type ) {
		$hook = '';

		switch ( $date_type ) {
			case 'next_payment':
				$hook = 'advanced_scheduled_subscription_payment';
				break;
			case 'payment_retry':
				$hook = 'advanced_scheduled_subscription_payment_retry';
				break;
			case 'trial_end':
				$hook = 'advanced_scheduled_subscription_trial_end';
				break;
			case 'end':
				if ( $subscription->has_status( array( 'cancelled', 'pending-cancel' ) ) ) {
					$hook = 'advanced_scheduled_subscription_end_of_prepaid_term';
				} elseif ( $subscription->has_status( 'active' ) ) {
					$hook = 'advanced_scheduled_subscription_expiration';
				}
				break;
		}

		return apply_filters( 'advanced_subscriptions_woocommerce_scheduled_action_hook', $hook, $date_type );
	}

	/**
	 * Get the date types that should be scheduled.
	 *
	 * Matches the scheduler's behaviour of scheduling all subscription
	 * date types except the start and last payment dates.
	 *
	 * @return array
	 */
	protected function get_date_types_to_schedule() {
		$date_types = aswc_get_subscription_date_types();
		unset( $date_types['start'], $date_types['last_payment'] );

		return array_keys( $date_types );
	}

	/**
	 * Maybe set a scheduled action if the new date is in the future.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string          $date_type    Can be 'trial_end', 'next_payment',
	 *                                     'payment_retry', 'end',
	 *                                     'end_of_prepaid_term' or a custom
	 *                                     date type.
	 * @param string          $datetime     A MySQL formatted date/time string
	 *                                     in the GMT/UTC timezone.
	 *
	 * @return void
	 */
	public function update_date( $subscription, $date_type, $datetime ) {
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log(
				sprintf(
					'[update_date] date_type:%s datetime:%s sub_id:%d',
					$date_type,
					is_string( $datetime ) ? $datetime : (string) $datetime,
					$subscription->get_id()
				)
			);
		}
		if ( 'next_payment' === $date_type ) {
			ASWC_Scheduler_API::schedule_payment( $subscription, ASWC_Scheduler_API::date_to_time( $datetime ) );
			return;
		}

		if ( 'payment_retry' === $date_type ) {
			// Never schedule retries when the subscription is ACTIVE.
			if ( method_exists( $subscription, 'has_status' ) && $subscription->has_status( 'active' ) ) {
				// Clear any retry date and queued actions just in case.
				$this->delete_date( $subscription, 'payment_retry' );
				aswc_update_meta_data( $subscription->get_id(), '_aswc_payment_retry', 0 );
				aswc_update_meta_data( $subscription->get_id(), '_aswc_retry_attempts', 0 );
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( sprintf( '[update_date:payment_retry] Subscription %d is active — skip scheduling retry.', $subscription->get_id() ) );
				}
				return;
			}

			// Respect a configurable maximum number of retries (default 5).
			$max_attempts      = (int) apply_filters( 'aswc_max_retry_attempts', 5, $subscription );
			$current_attempts  = (int) aswc_get_meta_data( $subscription->get_id(), '_aswc_retry_attempts' );
			if ( $current_attempts >= $max_attempts ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( sprintf( '[update_date:payment_retry] Max attempts reached (%d/%d). Skipping schedule.', $current_attempts, $max_attempts ) );
				}
				return;
			}

			// Convert provided datetime to timestamp (UTC). May be null/zero.
			$ts = ASWC_Scheduler_API::date_to_time( $datetime );

			// If no timestamp is provided/derived, compute a fallback based on attempts backoff.
			if ( empty( $ts ) || 0 === (int) $ts ) {
				$attempts_key = '_aswc_retry_attempts';
				$attempts     = (int) aswc_get_meta_data( $subscription->get_id(), $attempts_key );

				// Backoff ladder: 1m, 5m, 30m, 6h, 24h.
				$delays  = array( 60, 300, 1800, 21600, 86400 );
				$index   = max( 0, min( $attempts, count( $delays ) - 1 ) );
				$backoff = (int) apply_filters( 'aswc_payment_retry_backoff_seconds', $delays[ $index ], $attempts, $subscription );

				$ts = current_time( 'timestamp', true ) + $backoff;

				// Persist next retry and bump attempts for future escalations.
				aswc_update_meta_data( $subscription->get_id(), '_aswc_payment_retry', $ts );
				aswc_update_meta_data( $subscription->get_id(), $attempts_key, $attempts + 1 );

				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( sprintf( '[update_date:payment_retry] Fallback computed — attempts:%d backoff:%d next_ts:%d', $attempts, $backoff, $ts ) );
				}
			}

			// Always schedule the retry against the subscription (never the order).
			ASWC_Scheduler_API::schedule_retry( $subscription, $ts );
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( sprintf( '[update_date:payment_retry] Scheduled retry at %d (UTC) for subscription %d.', $ts, $subscription->get_id() ) );
			}
			return;
		}

		if ( 'trial_end' === $date_type ) {
			ASWC_Scheduler_API::schedule_trial_end( $subscription, ASWC_Scheduler_API::date_to_time( $datetime ) );
			return;
		}

		if ( 'end' === $date_type ) {
			ASWC_Scheduler_API::schedule_expiration( $subscription, ASWC_Scheduler_API::date_to_time( $datetime ) );
			return;
		}

		if ( in_array( $date_type, $this->get_date_types_to_schedule(), true ) ) {
			$action_hook = $this->get_scheduled_action_hook( $subscription, $date_type );

			if ( ! empty( $action_hook ) ) {
				$action_args = $this->get_action_args( $date_type, $subscription );
				$timestamp   = ASWC_Scheduler_API::date_to_time( $datetime );

				// Only schedule the action if the subscription is in a valid state. Otherwise clear it.
				if ( 'payment_retry' === $date_type || $subscription->has_status( 'active' ) || ( $subscription->has_status( 'pending-cancel' ) && 'end' === $date_type ) ) {
					$this->reschedule_action( $timestamp, $action_hook, $action_args );
				} else {
					$this->unschedule_actions( $action_hook, $action_args );
				}
			}
		}
	}

	/**
	 * Delete a date from the action scheduler queue.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string          $date_type    Can be 'trial_end', 'next_payment',
	 *                                     'end', 'end_of_prepaid_term' or a
	 *                                     custom date type.
	 *
	 * @return void
	 */
	public function delete_date( $subscription, $date_type ) {
		if ( 'next_payment' === $date_type ) {
			ASWC_Scheduler_API::unschedule_payment( $subscription );
			return;
		}

		if ( 'payment_retry' === $date_type ) {
			ASWC_Scheduler_API::unschedule_retry( $subscription );
			return;
		}

		if ( 'trial_end' === $date_type ) {
			ASWC_Scheduler_API::unschedule_trial_end( $subscription );
			return;
		}

		if ( 'end' === $date_type ) {
			ASWC_Scheduler_API::unschedule_expiration( $subscription );
			return;
		}

		$this->update_date( $subscription, $date_type, 0 );
	}

	/**
	 * When a subscription's status is updated, maybe schedule an event.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string          $new_status   New status.
	 * @param string          $old_status   Previous status.
	 *
	 * @return void
	 */
	public function update_status( $subscription, $new_status, $old_status ) {
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( '[update_status] sub_id:%d %s → %s', $subscription->get_id(), $old_status, $new_status ) );
		}
		switch ( $new_status ) {
			case 'active':
				// On manual reactivation or post-successful charge, ensure no retry remnants survive.
				// 1) Clear any queued retry actions (in our default group and in *any* other group just in case).
				// Also wipe the stored retry date so schedule_all() cannot re-queue it.
				$this->delete_date( $subscription, 'payment_retry' );
				ASWC_Scheduler_API::unschedule_retry( $subscription );
				$this->unschedule_actions( 'advanced_scheduled_subscription_payment_retry', array( 'subscription_id' => $subscription->get_id() ) );
				$this->unschedule_actions( 'advanced_scheduled_subscription_payment_retry', array( 'subscription_id' => $subscription->get_id() ), false );

				// 2) Reset counters and stored timestamps that could cause retries to be (re)scheduled.
				aswc_update_meta_data( $subscription->get_id(), '_aswc_retry_attempts', 0 );
				aswc_update_meta_data( $subscription->get_id(), '_aswc_payment_retry', 0 );

				ASWC_Scheduler_API::schedule_all( $subscription );

				$date_types = array_diff( $this->get_date_types_to_schedule(), array( 'next_payment', 'payment_retry', 'trial_end', 'end' ) );

				foreach ( $date_types as $date_type ) {
					$action_hook = $this->get_scheduled_action_hook( $subscription, $date_type );

					if ( empty( $action_hook ) ) {
						continue;
					}

					$event_time  = $subscription->get_time( $date_type );
					$action_args = $this->get_action_args( $date_type, $subscription );
					$this->reschedule_action( $event_time, $action_hook, $action_args );
				}

				// Safety net: if any code re-queued a retry during schedule_all(), clear it again.
				ASWC_Scheduler_API::unschedule_retry( $subscription );
				$this->unschedule_actions( 'advanced_scheduled_subscription_payment_retry', array( 'subscription_id' => $subscription->get_id() ) );
				$this->unschedule_actions( 'advanced_scheduled_subscription_payment_retry', array( 'subscription_id' => $subscription->get_id() ), false );

				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( sprintf( '[update_status] Cleaned retry state and counters for subscription %d on activation.', $subscription->get_id() ) );
				}

				break;

			case 'pending-cancel':
				// Now that we have the current times, clear the scheduled hooks.
				ASWC_Scheduler_API::unschedule_all( $subscription );
				foreach ( $this->get_date_types_to_schedule() as $date_type ) {
					switch ( $date_type ) {
						case 'next_payment':
						case 'payment_retry':
							// Handled by payment scheduler above.
							break;

						case 'trial_end':
						case 'end':
							// Handled by lifecycle scheduler below.
							break;

						default:
							$action_hook = $this->get_scheduled_action_hook( $subscription, $date_type );

							if ( empty( $action_hook ) ) {
								break;
							}

							$this->unschedule_actions( $action_hook, $this->get_action_args( $date_type, $subscription ) );
							break;
					}
				}

				$end_time = $subscription->get_time( 'end' ); // This will have been set to the correct date already.

				// The end date is already set to the correct value, so we can schedule our action for that time.
				if ( $end_time > current_time( 'timestamp', true ) ) {
					ASWC_Scheduler_API::schedule_end_of_prepaid_term( $subscription, $end_time );
				}
				break;

			case 'on-hold':
				$has_retry = ( $subscription->get_time( 'payment_retry' ) > 0 );
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( sprintf( '[update_status:on-hold] sub_id:%d has_retry:%s', $subscription->get_id(), $has_retry ? 'yes' : 'no' ) );
				}

				if ( $has_retry ) {
					ASWC_Scheduler_API::unschedule_payment( $subscription );
					ASWC_Scheduler_API::lifecycle()->unschedule_all( $subscription );
					ASWC_Scheduler_API::unschedule_all_notifications( $subscription );
				} else {
					ASWC_Scheduler_API::unschedule_all( $subscription );
				}

				foreach ( $this->get_date_types_to_schedule() as $date_type ) {
					switch ( $date_type ) {
						case 'next_payment':
							// Handled by payment scheduler above.
							break;

						case 'payment_retry':
							if ( ! $has_retry ) {
								$action_hook = $this->get_scheduled_action_hook( $subscription, $date_type );

								if ( empty( $action_hook ) ) {
									break;
								}

								// Args now reference the subscription, not an order.
								$this->unschedule_actions( $action_hook, $this->get_action_args( $date_type, $subscription ) );
							}
							break;

						case 'trial_end':
						case 'end':
							// Handled by lifecycle scheduler above.
							break;

						default:
							$action_hook = $this->get_scheduled_action_hook( $subscription, $date_type );

							if ( empty( $action_hook ) ) {
								break;
							}

							$this->unschedule_actions( $action_hook, $this->get_action_args( $date_type, $subscription ) );
							break;
					}
				}
				break;

			case 'paused':
			case 'cancelled':
			case 'switched':
			case 'expired':
			case 'trash':
				ASWC_Scheduler_API::unschedule_all( $subscription );
				foreach ( $this->get_date_types_to_schedule() as $date_type ) {
					switch ( $date_type ) {
						case 'next_payment':
						case 'payment_retry':
							// Handled by payment scheduler above.
							break;

						case 'trial_end':
						case 'end':
							// Handled by lifecycle scheduler below.
							break;

						default:
							$action_hook = $this->get_scheduled_action_hook( $subscription, $date_type );

							if ( empty( $action_hook ) ) {
								break;
							}

							$this->unschedule_actions( $action_hook, $this->get_action_args( $date_type, $subscription ) );
							break;
					}
				}
				break;
		}
	}
}

