<?php // phpcs:ignoreFile
/**
 * Core helper functions for the Scheduler API.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI
 */

if ( ! function_exists( 'aswc_get_subscription_date_types' ) ) {
	/**
	 * Get all subscription date types used by the Scheduler API.
	 *
	 * @return array Map of date type => label.
	 */
	function aswc_get_subscription_date_types() {
		return array(
			'trial_end'    => '',
			'next_payment' => '',
			'end'          => '',
		);
	}
}

if ( ! function_exists( 'aswc_get_subscription_statuses' ) ) {
	/**
	 * Get all subscription status slugs.
	 *
	 * @return array List of subscription status slugs.
	 */
	function aswc_get_subscription_statuses() {
		return array( 'active', 'on-hold', 'cancelled', 'expired', 'pending-cancel', 'pending', 'paused' );
	}
}

if ( ! function_exists( 'aswc_get_subscription_status_names' ) ) {
	/**
	 * Get all subscription statuses with their display names.
	 *
	 * @return array Map of status slug => display name.
	 */
	function aswc_get_subscription_status_names() {
		return array(
			'active'         => 'Active',
			'on-hold'        => 'On hold',
			'cancelled'      => 'Cancelled',
			'expired'        => 'Expired',
			'pending-cancel' => 'Pending Cancellation',
			'pending'        => 'Pending',
			'paused'         => 'Paused',
		);
	}
}

if ( ! function_exists( 'aswc_get_subscription_ended_statuses' ) ) {
	/**
	 * Get subscription statuses that represent an ended subscription.
	 *
	 * @return array List of ended subscription status slugs.
	 */
	function aswc_get_subscription_ended_statuses() {
		return array( 'cancelled', 'trash', 'expired', 'switched', 'pending-cancel' );
	}
}

if ( ! function_exists( 'aswc_sanitize_subscription_status_key' ) ) {
	/**
	 * Sanitize a subscription status key.
	 *
	 * Falls back to a basic sanitization routine when the helper is
	 * unavailable.
	 *
	 * @param string $status Raw status key.
	 * @return string Sanitized status key.
	 */
	function aswc_sanitize_subscription_status_key( $status ) {
		$status = is_string( $status ) ? strtolower( trim( $status ) ) : '';

		return preg_replace( '/[^a-z0-9_-]/', '', $status );
	}
}

if ( ! function_exists( 'aswc_is_subscription' ) ) {
	/**
	 * Determine whether the given value represents a subscription.
	 *
	 * @param mixed $subscription Value to test.
	 * @return bool True if a subscription-like object or ID is detected.
	 */
	function aswc_is_subscription( $subscription ) {
		return (
			is_object( $subscription )
			&& method_exists( $subscription, 'get_id' )
			&& method_exists( $subscription, 'has_status' )
		);
	}
}

if ( ! function_exists( 'aswc_get_objects_property' ) ) {
	/**
	 * Get a property from an object.
	 *
	 * Falls back to basic getter or property access when the property is
	 * unavailable.
	 *
	 * @param object $object   Object to inspect.
	 * @param string $property Property name.
	 * @param string $single   Whether to return a single value. Defaults to 'single'.
	 * @param mixed  $default  Default value to return when the property is unavailable.
	 * @return mixed
	 */
	function aswc_get_objects_property( $object, $property, $single = 'single', $default = null ) {
		if ( is_object( $object ) ) {
			$getter = 'get_' . $property;
			if ( is_callable( array( $object, $getter ) ) ) {
				return $object->{$getter}();
			} elseif ( property_exists( $object, $property ) ) {
				return $object->$property;
			}
		}

		return $default;
	}
}

if ( ! function_exists( 'aswc_date_to_time' ) ) {
	/**
	 * Convert a MySQL datetime string to a Unix timestamp.
	 *
	 * Falls back to PHP's DateTime handling.
	 *
	 * @param string|int $date_string MySQL formatted date/time string or timestamp.
	 * @return int|null Unix timestamp or null on failure.
	 */
	function aswc_date_to_time( $date_string ) {
		if ( 0 === $date_string ) {
			return 0;
		}

		try {
			if ( is_numeric( $date_string ) ) {
				$date_time = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
				$date_time->setTimestamp( (int) $date_string );
			} else {
				$date_time = new DateTime( $date_string, new DateTimeZone( 'UTC' ) );
			}
		} catch ( \Throwable $e ) {
			return null;
		}

		return (int) $date_time->getTimestamp();
	}
}

if ( ! function_exists( 'aswc_local_timestamp_to_utc' ) ) {
       /**
        * Convert a local timestamp to UTC.
        *
        * Adjusts the provided timestamp based on the site's timezone so that
        * scheduled actions run at the correct moment regardless of local
        * settings.
        *
        * @param int $timestamp Local timestamp.
        * @return int UTC timestamp.
        */
       function aswc_local_timestamp_to_utc( $timestamp ) {
               if ( empty( $timestamp ) ) {
                       return (int) $timestamp;
               }

               $timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( get_option( 'timezone_string', 'UTC' ) );
               $offset   = $timezone->getOffset( new DateTime( '@' . (int) $timestamp ) );

               return (int) $timestamp - (int) $offset;
       }
}

if ( ! function_exists( 'aswc_is_woocommerce_pre' ) ) {
	/**
	 * Determine whether the current WooCommerce version is lower than the
	 * provided version.
	 *
	 * @param string $version WooCommerce version to compare against.
	 * @return bool True if the installed WooCommerce version is lower than
	 *              `$version` or the version cannot be determined.
	 */
	function aswc_is_woocommerce_pre( $version ) {
		return ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, $version, '<' );
	}
}

if ( ! function_exists( 'aswc_is_custom_order_tables_usage_enabled' ) ) {
	/**
	 * Determine if WooCommerce's custom order tables are enabled.
	 *
	 * @return bool True when the custom order tables feature is enabled.
	 */
	function aswc_is_custom_order_tables_usage_enabled() {
		return class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}
}

if ( ! function_exists( 'aswc_get_orders_data_synchronizer' ) ) {
	/**
	 * Retrieve WooCommerce's orders data synchronizer instance.
	 *
	 * @return object|null DataSynchronizer instance when available, null otherwise.
	 */
	function aswc_get_orders_data_synchronizer() {
		if (
			function_exists( 'wc_get_container' )
			&& class_exists( '\\Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer' )
		) {
			return wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::class );
		}

		return null;
	}
}

if ( ! function_exists( 'aswc_is_custom_order_tables_data_sync_enabled' ) ) {
	/**
	 * Check whether WooCommerce synchronizes custom order tables with posts.
	 *
	 * @return bool True when data synchronization is enabled.
	 */
	function aswc_is_custom_order_tables_data_sync_enabled() {
		$data_synchronizer = aswc_get_orders_data_synchronizer();

		return $data_synchronizer && $data_synchronizer->data_sync_is_enabled();
	}
}

if ( ! function_exists( 'aswc_append_numeral_suffix' ) ) {
	/**
	 * Append an English ordinal suffix to a number.
	 *
	 * @param int|string $number Number to suffix.
	 * @return string Number with ordinal suffix.
	 */
	function aswc_append_numeral_suffix( $number ) {
		$number = (string) $number;
		if ( strlen( $number ) > 1 && '1' === substr( $number, -2, 1 ) ) {
			$suffix = 'th';
		} else {
			switch ( substr( $number, -1 ) ) {
				case '1':
					$suffix = 'st';
					break;
				case '2':
					$suffix = 'nd';
					break;
				case '3':
					$suffix = 'rd';
					break;
				default:
					$suffix = 'th';
					break;
			}
		}

		$number_string = $number . $suffix;

		return apply_filters( 'woocommerce_numeral_suffix', $number_string, $number );
	}
}

if ( ! function_exists( 'aswc_get_subscription_period_strings' ) ) {
	/**
	 * Return subscription period strings.
	 *
	 * @param int    $number Interval for the period.
	 * @param string $period Optional period key. One of day, week, month or year.
	 * @return array|string Map of period => label or a single label if `$period`
	 *                      is provided.
	 */
	function aswc_get_subscription_period_strings( $number = 1, $period = '' ) {
		$periods = array(
			'day'   => $number > 1 ? sprintf( '%s days', $number ) : 'day',
			'week'  => $number > 1 ? sprintf( '%s weeks', $number ) : 'week',
			'month' => $number > 1 ? sprintf( '%s months', $number ) : 'month',
			'year'  => $number > 1 ? sprintf( '%s years', $number ) : 'year',
		);

		return $period ? $periods[ $period ] : $periods;
	}
}

if ( ! function_exists( 'aswc_get_subscription_period_interval_strings' ) ) {
	/**
	 * Return subscription period interval strings.
	 *
	 * @param int|null $interval Specific interval to retrieve. Defaults to all.
	 * @return array|string Map of interval => label or a single label if
	 *                      `$interval` is provided.
	 */
	function aswc_get_subscription_period_interval_strings( $interval = null ) {
		$intervals = array( 1 => 'every' );
		for ( $i = 2; $i <= 6; $i++ ) {
			$intervals[ $i ] = sprintf( 'every %s', aswc_append_numeral_suffix( $i ) );
		}

		$intervals = apply_filters( 'woocommerce_subscription_period_interval_strings', $intervals );

		if ( empty( $interval ) ) {
			return $intervals;
		}

		return isset( $intervals[ $interval ] ) ? $intervals[ $interval ] : '';
	}
}

if ( ! function_exists( 'aswc_get_subscription_trial_period_strings' ) ) {
	/**
	 * Return subscription trial period strings.
	 *
	 * @param int    $number Interval for the period.
	 * @param string $period Optional period key. One of day, week, month or year.
	 * @return array|string Map of period => label or a single label if `$period`
	 *                      is provided.
	 */
	function aswc_get_subscription_trial_period_strings( $number = 1, $period = '' ) {
		$periods = array(
			'day'   => $number > 1 ? sprintf( '%s days', $number ) : sprintf( '%s day', $number ),
			'week'  => $number > 1 ? sprintf( '%s weeks', $number ) : sprintf( '%s week', $number ),
			'month' => $number > 1 ? sprintf( '%s months', $number ) : sprintf( '%s month', $number ),
			'year'  => $number > 1 ? sprintf( '%s years', $number ) : sprintf( '%s year', $number ),
		);

		return $period ? $periods[ $period ] : $periods;
	}
}

if ( ! function_exists( 'aswc_get_subscription_ranges' ) ) {
	/**
	 * Get allowed subscription length ranges for each period.
	 *
	 * @param string|null $subscription_period Optional period key. If provided,
	 *                                         only the ranges for that period
	 *                                         are returned.
	 * @return array Subscription length ranges.
	 */
	function aswc_get_subscription_ranges( $subscription_period = null ) {
		$periods = array(
			'day'   => 90,
			'week'  => 52,
			'month' => 24,
			'year'  => 5,
		);

		$ranges = array();

		foreach ( $periods as $period => $max ) {
			$lengths   = array( 'Do not stop until cancelled' );
			$lengths[] = aswc_get_subscription_period_strings( 1, $period );
			for ( $i = 2; $i <= $max; $i++ ) {
				$lengths[ $i ] = aswc_get_subscription_period_strings( $i, $period );
			}
			$ranges[ $period ] = $lengths;
		}

		if ( $subscription_period ) {
			return isset( $ranges[ $subscription_period ] ) ? $ranges[ $subscription_period ] : array();
		}

		return $ranges;
	}
}

if ( ! function_exists( 'aswc_get_available_time_periods' ) ) {
	/**
	 * Get allowed time periods for subscriptions.
	 *
	 * @param string $form Optional. 'singular' for singular forms, anything else
	 *                     for plural forms.
	 * @return array Map of period key => label.
	 */
	function aswc_get_available_time_periods( $form = 'singular' ) {
		$number  = ( 'singular' === $form ) ? 1 : 2;
		$periods = array(
			'day'   => $number > 1 ? 'days' : 'day',
			'week'  => $number > 1 ? 'weeks' : 'week',
			'month' => $number > 1 ? 'months' : 'month',
			'year'  => $number > 1 ? 'years' : 'year',
		);

		return apply_filters( 'woocommerce_subscription_available_time_periods', $periods );
	}
}

if ( ! function_exists( 'aswc_get_subscription_trial_lengths' ) ) {
	/**
	 * Get allowed trial period lengths for subscriptions.
	 *
	 * Relies on `aswc_get_subscription_ranges()` for the base ranges.
	 *
	 * @param string $subscription_period Optional period key. If provided,
	 *                                    only lengths for that period are
	 *                                    returned.
	 * @return array Map of length => label or a map of periods => lengths when
	 *               no period is provided.
	 */
	function aswc_get_subscription_trial_lengths( $subscription_period = '' ) {
		$all = aswc_get_subscription_ranges();
		foreach ( $all as $period => &$lengths ) {
			$lengths[0] = 'no';
		}
		unset( $lengths );

		if ( $subscription_period ) {
			return isset( $all[ $subscription_period ] ) ? $all[ $subscription_period ] : array();
		}

		return $all;
	}
}

if ( ! function_exists( 'aswc_get_subscription_item_grouping_key' ) ) {
	/**
	 * Generate a key for grouping subscription items with the same schedule.
	 *
	 * @param WC_Order_Item_Product $item         Order item.
	 * @param int                   $renewal_time Timestamp for first renewal.
	 * @return string Grouping key.
	 */
	function aswc_get_subscription_item_grouping_key( $item, $renewal_time = 0 ) {
		$product_id = 0;

		if ( is_object( $item ) ) {
			if ( method_exists( $item, 'get_product_id' ) ) {
				$product_id = (int) $item->get_product_id();
			} elseif ( method_exists( $item, 'get_product' ) ) {
				$product = $item->get_product();
				if ( is_object( $product ) && method_exists( $product, 'get_id' ) ) {
					$product_id = (int) $product->get_id();
				}
			}
		}

		return $product_id . ':' . (int) $renewal_time;
	}
}

if ( ! function_exists( 'aswc_get_latest_action_scheduler_version' ) ) {
	/**
	 * Get the latest available Action Scheduler version.
	 *
	 * Wraps Action Scheduler's version helper so the Scheduler API does not
	 * depend on classes defined outside the library.
	 *
	 * @return string Semantic version string. Defaults to '0' when Action
	 *                Scheduler is unavailable.
	 */
	function aswc_get_latest_action_scheduler_version() {
		if ( class_exists( '\\ActionScheduler\\Versions' ) && method_exists( '\\ActionScheduler\\Versions', 'instance' ) ) {
			return \ActionScheduler\Versions::instance()->latest_version();
		}

		if ( class_exists( 'ActionScheduler_Versions' ) && method_exists( 'ActionScheduler_Versions', 'instance' ) ) {
			return ActionScheduler_Versions::instance()->latest_version();
		}

		return '0';
	}
}

if ( ! function_exists( 'aswc_get_action_scheduler_store' ) ) {
	/**
	 * Retrieve the Action Scheduler data store instance.
	 *
	 * Provides access to Action Scheduler's storage layer while avoiding a
	 * hard dependency on the library when it's unavailable.
	 *
	 * @return ActionScheduler_Store|null Store instance or null if not
	 *                                    available.
	 */
	function aswc_get_action_scheduler_store() {
		if ( class_exists( '\\ActionScheduler\\Store' ) && method_exists( '\\ActionScheduler\\Store', 'instance' ) ) {
			return \ActionScheduler\Store::instance();
		}

		if ( class_exists( 'ActionScheduler_Store' ) && method_exists( 'ActionScheduler_Store', 'instance' ) ) {
			return ActionScheduler_Store::instance();
		}

		return null;
	}
}

if ( ! function_exists( 'aswc_get_action_scheduler_action' ) ) {
	/**
	 * Retrieve an action from Action Scheduler's data store.
	 *
	 * Wraps the store's `fetch_action` method so the Scheduler API does not
	 * depend on Action Scheduler when it's unavailable.
	 *
	 * @param int $action_id Action ID to fetch.
	 * @return ActionScheduler_Action|false The action object or false if not available.
	 */
	function aswc_get_action_scheduler_action( $action_id ) {
		$store = aswc_get_action_scheduler_store();

		if ( $store && method_exists( $store, 'fetch_action' ) ) {
			return $store->fetch_action( $action_id );
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_save_action' ) ) {
	/**
	 * Persist a scheduled action object to the data store.
	 *
	 * Wraps Action Scheduler's store `save_action()` method so the Scheduler
	 * API does not depend on the library when it's unavailable.
	 *
	 * @param object        $action         Action object to save.
	 * @param DateTime|null $scheduled_date Optional scheduled date.
	 * @return int|false Action ID on success, false otherwise.
	 */
	function aswc_save_action( $action, $scheduled_date = null ) {
		$store = aswc_get_action_scheduler_store();

		if ( $store && method_exists( $store, 'save_action' ) ) {
			return $store->save_action( $action, $scheduled_date );
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_cancel_action' ) ) {
	/**
	 * Cancel a scheduled action in the data store.
	 *
	 * Wraps Action Scheduler's store `cancel_action()` method so the Scheduler
	 * API does not depend on the library when it's unavailable.
	 *
	 * @param int $action_id Action ID to cancel.
	 * @return bool True on success, false otherwise.
	 */
	function aswc_cancel_action( $action_id ) {
		$store = aswc_get_action_scheduler_store();

		if ( $store && method_exists( $store, 'cancel_action' ) ) {
			$store->cancel_action( $action_id );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_delete_action' ) ) {
	/**
	 * Delete a scheduled action from the data store.
	 *
	 * Wraps Action Scheduler's store `delete_action()` method so the Scheduler
	 * API does not depend on the library when it's unavailable.
	 *
	 * @param int $action_id Action ID to delete.
	 * @return bool True on success, false otherwise.
	 */
	function aswc_delete_action( $action_id ) {
		$store = aswc_get_action_scheduler_store();

		if ( $store && method_exists( $store, 'delete_action' ) ) {
			$store->delete_action( $action_id );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_mark_action_complete' ) ) {
	/**
	 * Mark a scheduled action as complete in the data store.
	 *
	 * Wraps Action Scheduler's store `mark_complete()` method so the Scheduler
	 * API does not depend on the library when it's unavailable.
	 *
	 * @param int $action_id Action ID to mark complete.
	 * @return bool True on success, false otherwise.
	 */
	function aswc_mark_action_complete( $action_id ) {
		$store = aswc_get_action_scheduler_store();

		if ( $store && method_exists( $store, 'mark_complete' ) ) {
			$store->mark_complete( $action_id );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_mark_action_failed' ) ) {
	/**
	 * Mark a scheduled action as failed in the data store.
	 *
	 * Wraps Action Scheduler's store `mark_failure()` method so the Scheduler
	 * API does not depend on the library when it's unavailable.
	 *
	 * @param int $action_id Action ID to mark failed.
	 * @return bool True on success, false otherwise.
	 */
	function aswc_mark_action_failed( $action_id ) {
		$store = aswc_get_action_scheduler_store();

		if ( $store && method_exists( $store, 'mark_failure' ) ) {
			$store->mark_failure( $action_id );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_claim_actions' ) ) {
	/**
	 * Claim a set of scheduled actions for processing.
	 *
	 * Wraps Action Scheduler's store `claim_actions()` method so the Scheduler
	 * API does not depend on the library when it's unavailable.
	 *
	 * @param int           $claim_id    Claim identifier.
	 * @param int           $limit       Maximum number of actions to claim.
	 * @param DateTime|null $before_date Optional upper bound on scheduled date.
	 * @param array         $hooks       Optional list of hooks to claim.
	 * @param string        $group       Optional action group.
	 * @return array List of claimed action IDs.
	 */
	function aswc_claim_actions( $claim_id, $limit, $before_date = null, $hooks = array(), $group = '' ) {
		$store = aswc_get_action_scheduler_store();

		if ( $store && method_exists( $store, 'claim_actions' ) ) {
			return $store->claim_actions( $claim_id, $limit, $before_date, $hooks, $group );
		}

		return array();
	}
}

if ( ! function_exists( 'aswc_release_claim' ) ) {
	/**
	 * Release a previously claimed set of actions.
	 *
	 * Wraps Action Scheduler's store `release_claim()` method so the Scheduler
	 * API does not depend on the library when it's unavailable.
	 *
	 * @param int $claim_id Claim identifier to release.
	 * @return bool True on success, false otherwise.
	 */
	function aswc_release_claim( $claim_id ) {
		$store = aswc_get_action_scheduler_store();

		if ( $store && method_exists( $store, 'release_claim' ) ) {
			$store->release_claim( $claim_id );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_unclaim_action' ) ) {
	/**
	 * Unclaim a previously claimed action.
	 *
	 * Wraps Action Scheduler's store `unclaim_action()` method so the Scheduler
	 * API does not depend on the library when it's unavailable.
	 *
	 * @param int $action_id Action identifier to unclaim.
	 * @return bool True on success, false otherwise.
	 */
	function aswc_unclaim_action( $action_id ) {
		$store = aswc_get_action_scheduler_store();

		if ( $store && method_exists( $store, 'unclaim_action' ) ) {
			return (bool) $store->unclaim_action( $action_id );
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_query_actions' ) ) {
	/**
	 * Query scheduled actions from Action Scheduler's store.
	 *
	 * Wraps the store's `query_actions()` method so the Scheduler API does not
	 * depend on the library when it's unavailable.
	 *
	 * @param array $query_args Optional query arguments.
	 * @return array List of matching action IDs or objects.
	 */
	function aswc_query_actions( $query_args = array() ) {
		$store = aswc_get_action_scheduler_store();

		if ( $store && method_exists( $store, 'query_actions' ) ) {
			return $store->query_actions( $query_args );
		}

		return array();
	}
}

if ( ! function_exists( 'aswc_is_schedule_recurring' ) ) {
	/**
	 * Determine whether a schedule represents a recurring action.
	 *
	 * Wraps the schedule's recurring check so the Scheduler API does not depend
	 * on Action Scheduler's schedule classes when they're unavailable.
	 *
	 * @param object $schedule Schedule object.
	 * @return bool True if the schedule is recurring, false otherwise.
	 */
	function aswc_is_schedule_recurring( $schedule ) {
		if ( is_object( $schedule ) && method_exists( $schedule, 'is_recurring' ) ) {
			return (bool) $schedule->is_recurring();
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_get_action_hook' ) ) {
	/**
	 * Retrieve the hook name from a scheduled action object.
	 *
	 * Provides a safe wrapper so the Scheduler API can inspect action objects
	 * without depending on Action Scheduler's classes when it's unavailable.
	 *
	 * @param object $action The action object.
	 * @return string|null Action hook name or null when unavailable.
	 */
	function aswc_get_action_hook( $action ) {
		return ( is_object( $action ) && method_exists( $action, 'get_hook' ) )
			? $action->get_hook()
			: null;
	}
}

if ( ! function_exists( 'aswc_get_action_args' ) ) {
	/**
	 * Retrieve the arguments from a scheduled action object.
	 *
	 * Provides a safe wrapper so the Scheduler API can access action arguments
	 * without depending on Action Scheduler's classes when it's unavailable.
	 *
	 * @param object $action The action object.
	 * @return array Action arguments.
	 */
	function aswc_get_action_args( $action ) {
		return ( is_object( $action ) && method_exists( $action, 'get_args' ) )
			? $action->get_args()
			: array();
	}
}

if ( ! function_exists( 'aswc_get_action_schedule' ) ) {
	/**
	 * Retrieve the schedule object from a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can inspect an action's
	 * schedule without depending on Action Scheduler's classes when it's
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @return object|null Action schedule or null when unavailable.
	 */
	function aswc_get_action_schedule( $action ) {
		return ( is_object( $action ) && method_exists( $action, 'get_schedule' ) )
			? $action->get_schedule()
			: null;
	}
}

if ( ! function_exists( 'aswc_get_action_status' ) ) {
	/**
	 * Retrieve the status of a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can inspect an action's
	 * status without depending on Action Scheduler's classes when it's
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @return string|null Action status or null when unavailable.
	 */
	function aswc_get_action_status( $action ) {
		return ( is_object( $action ) && method_exists( $action, 'get_status' ) )
			? $action->get_status()
			: null;
	}
}

if ( ! function_exists( 'aswc_get_action_group' ) ) {
	/**
	 * Retrieve the group for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can inspect an action's
	 * group without depending on Action Scheduler's classes when it's
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @return string Action group or empty string when unavailable.
	 */
	function aswc_get_action_group( $action ) {
		return ( is_object( $action ) && method_exists( $action, 'get_group' ) )
			? $action->get_group()
			: '';
	}
}

if ( ! function_exists( 'aswc_get_action_id' ) ) {
	/**
	 * Retrieve the identifier for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can access an action's ID
	 * without depending on Action Scheduler's classes when it's unavailable.
	 *
	 * @param object $action The action object.
	 * @return int Action ID or 0 when unavailable.
	 */
	function aswc_get_action_id( $action ) {
		return ( is_object( $action ) && method_exists( $action, 'get_id' ) )
			? (int) $action->get_id()
			: 0;
	}
}

if ( ! function_exists( 'aswc_get_action_priority' ) ) {
	/**
	 * Retrieve the priority for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can access an action's
	 * priority without depending on Action Scheduler's classes when it's
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @return int Action priority or default of 10 when unavailable.
	 */
	function aswc_get_action_priority( $action ) {
		return ( is_object( $action ) && method_exists( $action, 'get_priority' ) )
			? (int) $action->get_priority()
			: 10;
	}
}

if ( ! function_exists( 'aswc_get_action_attempts' ) ) {
	/**
	 * Retrieve the attempt count for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can access an action's
	 * attempt count without depending on Action Scheduler's classes when it's
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @return int Number of attempts or 0 when unavailable.
	 */
	function aswc_get_action_attempts( $action ) {
		return ( is_object( $action ) && method_exists( $action, 'get_attempts' ) )
			? (int) $action->get_attempts()
			: 0;
	}
}

if ( ! function_exists( 'aswc_get_action_claim_id' ) ) {
	/**
	 * Retrieve the claim ID for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can access an action's
	 * claim identifier without depending on Action Scheduler's classes when it's
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @return int Claim ID or 0 when unavailable.
	 */
	function aswc_get_action_claim_id( $action ) {
		return ( is_object( $action ) && method_exists( $action, 'get_claim_id' ) )
			? (int) $action->get_claim_id()
			: 0;
	}
}

if ( ! function_exists( 'aswc_get_action_post_id' ) ) {
	/**
	 * Retrieve the post ID associated with a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can inspect an action's
	 * post identifier without depending on Action Scheduler's classes when it's
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @return int Post ID or 0 when unavailable.
	 */
	function aswc_get_action_post_id( $action ) {
		return ( is_object( $action ) && method_exists( $action, 'get_post_id' ) )
			? (int) $action->get_post_id()
			: 0;
	}
}

if ( ! function_exists( 'aswc_get_action_user_id' ) ) {
	/**
	 * Retrieve the user ID associated with a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can inspect an action's
	 * user identifier without depending on Action Scheduler's classes when it's
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @return int User ID or 0 when unavailable.
	 */
	function aswc_get_action_user_id( $action ) {
		return ( is_object( $action ) && method_exists( $action, 'get_user_id' ) )
			? (int) $action->get_user_id()
			: 0;
	}
}

if ( ! function_exists( 'aswc_set_action_hook' ) ) {
	/**
	 * Set the hook for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can modify an action's hook
	 * without depending on Action Scheduler's classes when they're
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @param string $hook   Hook name.
	 * @return bool True on success, false when unavailable.
	 */
	function aswc_set_action_hook( $action, $hook ) {
		if ( is_object( $action ) && method_exists( $action, 'set_hook' ) ) {
			$action->set_hook( $hook );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_set_action_args' ) ) {
	/**
	 * Set the arguments for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can modify an action's
	 * arguments without depending on Action Scheduler's classes when they're
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @param array  $args   Action arguments.
	 * @return bool True on success, false when unavailable.
	 */
	function aswc_set_action_args( $action, $args ) {
		if ( is_object( $action ) && method_exists( $action, 'set_args' ) ) {
			$action->set_args( $args );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_set_action_schedule' ) ) {
	/**
	 * Set the schedule for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can modify an action's
	 * schedule without depending on Action Scheduler's classes when they're
	 * unavailable.
	 *
	 * @param object $action   The action object.
	 * @param object $schedule Schedule object.
	 * @return bool True on success, false when unavailable.
	 */
	function aswc_set_action_schedule( $action, $schedule ) {
		if ( is_object( $action ) && method_exists( $action, 'set_schedule' ) ) {
			$action->set_schedule( $schedule );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_set_action_group' ) ) {
	/**
	 * Set the group for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can modify an action's
	 * group without depending on Action Scheduler's classes when they're
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @param string $group  Action group.
	 * @return bool True on success, false when unavailable.
	 */
	function aswc_set_action_group( $action, $group ) {
		if ( is_object( $action ) && method_exists( $action, 'set_group' ) ) {
			$action->set_group( $group );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_set_action_status' ) ) {
	/**
	 * Set the status for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can modify an action's
	 * status without depending on Action Scheduler's classes when they're
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @param string $status Action status.
	 * @return bool True on success, false when unavailable.
	 */
	function aswc_set_action_status( $action, $status ) {
		if ( is_object( $action ) && method_exists( $action, 'set_status' ) ) {
			$action->set_status( $status );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_set_action_priority' ) ) {
	/**
	 * Set the priority for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can modify an action's
	 * priority without depending on Action Scheduler's classes when they're
	 * unavailable.
	 *
	 * @param object $action   The action object.
	 * @param int    $priority Priority level.
	 * @return bool True on success, false when unavailable.
	 */
	function aswc_set_action_priority( $action, $priority ) {
		if ( is_object( $action ) && method_exists( $action, 'set_priority' ) ) {
			$action->set_priority( $priority );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_set_action_attempts' ) ) {
	/**
	 * Set the attempt count for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can modify an action's
	 * attempt count without depending on Action Scheduler's classes when they're
	 * unavailable.
	 *
	 * @param object $action   The action object.
	 * @param int    $attempts Attempt count.
	 * @return bool True on success, false when unavailable.
	 */
	function aswc_set_action_attempts( $action, $attempts ) {
		if ( is_object( $action ) && method_exists( $action, 'set_attempts' ) ) {
			$action->set_attempts( $attempts );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_set_action_claim_id' ) ) {
	/**
	 * Set the claim ID for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can modify an action's
	 * claim ID without depending on Action Scheduler's classes when they're
	 * unavailable.
	 *
	 * @param object $action   The action object.
	 * @param int    $claim_id Claim identifier.
	 * @return bool True on success, false when unavailable.
	 */
	function aswc_set_action_claim_id( $action, $claim_id ) {
		if ( is_object( $action ) && method_exists( $action, 'set_claim_id' ) ) {
			$action->set_claim_id( $claim_id );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_set_action_post_id' ) ) {
	/**
	 * Set the post ID for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can modify an action's
	 * post identifier without depending on Action Scheduler's classes when they're
	 * unavailable.
	 *
	 * @param object $action  The action object.
	 * @param int    $post_id Post ID.
	 * @return bool True on success, false when unavailable.
	 */
	function aswc_set_action_post_id( $action, $post_id ) {
		if ( is_object( $action ) && method_exists( $action, 'set_post_id' ) ) {
			$action->set_post_id( $post_id );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_set_action_user_id' ) ) {
	/**
	 * Set the user ID for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can modify an action's
	 * user identifier without depending on Action Scheduler's classes when they're
	 * unavailable.
	 *
	 * @param object $action  The action object.
	 * @param int    $user_id User ID.
	 * @return bool True on success, false when unavailable.
	 */
	function aswc_set_action_user_id( $action, $user_id ) {
		if ( is_object( $action ) && method_exists( $action, 'set_user_id' ) ) {
			$action->set_user_id( $user_id );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_get_action_meta' ) ) {
	/**
	 * Retrieve a meta value for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can access an action's
	 * meta data without depending on Action Scheduler's classes when it's
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @param string $key    Meta key.
	 * @return mixed|null Meta value or null when unavailable.
	 */
	function aswc_get_action_meta( $action, $key ) {
		return ( is_object( $action ) && method_exists( $action, 'get_meta' ) )
			? $action->get_meta( $key )
			: null;
	}
}

if ( ! function_exists( 'aswc_set_action_meta' ) ) {
	/**
	 * Save a meta value for a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can persist action meta
	 * without depending on Action Scheduler's classes when they're
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @param string $key    Meta key.
	 * @param mixed  $value  Meta value.
	 * @return bool True on success, false when unavailable.
	 */
	function aswc_set_action_meta( $action, $key, $value ) {
		if ( is_object( $action ) && method_exists( $action, 'save_meta' ) ) {
			$action->save_meta( $key, $value );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_delete_action_meta' ) ) {
	/**
	 * Delete a meta value from a scheduled action.
	 *
	 * Provides a safe wrapper so the Scheduler API can remove action meta
	 * without depending on Action Scheduler's classes when they're
	 * unavailable.
	 *
	 * @param object $action The action object.
	 * @param string $key    Meta key.
	 * @return bool True on success, false when unavailable.
	 */
	function aswc_delete_action_meta( $action, $key ) {
		if ( is_object( $action ) && method_exists( $action, 'delete_meta' ) ) {
			$action->delete_meta( $key );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_is_action_finished' ) ) {
	/**
	 * Determine whether a scheduled action has finished executing.
	 *
	 * Provides a safe wrapper so the Scheduler API can check an action's
	 * completion state without depending on Action Scheduler's classes when
	 * they're unavailable.
	 *
	 * @param object $action The action object.
	 * @return bool True if the action is finished, false otherwise.
	 */
	function aswc_is_action_finished( $action ) {
		return ( is_object( $action ) && method_exists( $action, 'is_finished' ) )
			? (bool) $action->is_finished()
			: false;
	}
}

if ( ! function_exists( 'aswc_get_schedule_timestamp' ) ) {
	/**
	 * Retrieve the timestamp for a schedule object.
	 *
	 * Wraps the schedule's date retrieval so the Scheduler API does not depend
	 * on Action Scheduler's schedule classes when they're unavailable.
	 *
	 * @param object $schedule Schedule object.
	 * @return int|false Unix timestamp for the schedule or false when unavailable.
	 */
	function aswc_get_schedule_timestamp( $schedule ) {
		if ( is_object( $schedule ) && method_exists( $schedule, 'get_date' ) ) {
			$date = $schedule->get_date();

			if ( is_object( $date ) && method_exists( $date, 'getTimestamp' ) ) {
				return $date->getTimestamp();
			}
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_get_schedule_gmt_timestamp' ) ) {
	/**
	 * Retrieve the GMT timestamp for a schedule object.
	 *
	 * Wraps the schedule's GMT date retrieval so the Scheduler API does not
	 * depend on Action Scheduler's schedule classes when they're unavailable.
	 *
	 * @param object $schedule Schedule object.
	 * @return int|false GMT Unix timestamp for the schedule or false when unavailable.
	 */
	function aswc_get_schedule_gmt_timestamp( $schedule ) {
		if ( is_object( $schedule ) && method_exists( $schedule, 'get_date_gmt' ) ) {
			$date = $schedule->get_date_gmt();

			if ( is_object( $date ) && method_exists( $date, 'getTimestamp' ) ) {
				return $date->getTimestamp();
			}
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_get_schedule_next_timestamp' ) ) {
	/**
	 * Retrieve the next run timestamp for a schedule object.
	 *
	 * Wraps the schedule's next() method so the Scheduler API does not depend
	 * on Action Scheduler's schedule classes when they're unavailable.
	 *
	 * @param object         $schedule Schedule object.
	 * @param \DateTime|null $after    Optional starting point.
	 * @return int|false Unix timestamp for the next run or false when unavailable.
	 */
	function aswc_get_schedule_next_timestamp( $schedule, $after = null ) {
		if ( is_object( $schedule ) && method_exists( $schedule, 'next' ) ) {
			$next = $schedule->next( $after instanceof \DateTime ? $after : null );

			if ( is_object( $next ) && method_exists( $next, 'getTimestamp' ) ) {
				return $next->getTimestamp();
			}
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_get_schedule_recurrence' ) ) {
	/**
	 * Retrieve the recurrence interval for a schedule object.
	 *
	 * Wraps the schedule's recurrence getter so the Scheduler API does not
	 * depend on Action Scheduler's schedule classes when they're unavailable.
	 *
	 * @param object $schedule Schedule object.
	 * @return int|false Recurrence interval in seconds or false when unavailable.
	 */
	function aswc_get_schedule_recurrence( $schedule ) {
		if ( is_object( $schedule ) && method_exists( $schedule, 'get_recurrence' ) ) {
			$recurrence = $schedule->get_recurrence();

			if ( is_int( $recurrence ) ) {
				return $recurrence;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_get_action_scheduler_pending_status' ) ) {
	/**
	 * Retrieve the status slug used for pending actions.
	 *
	 * Acts as a wrapper around Action Scheduler's pending status constant so
	 * the Scheduler API does not depend on the library when it's unavailable.
	 *
	 * @return string Pending status slug.
	 */
	function aswc_get_action_scheduler_pending_status() {
		if ( class_exists( '\\ActionScheduler\\Store' ) ) {
			return \ActionScheduler\Store::STATUS_PENDING;
		}

		if ( class_exists( 'ActionScheduler_Store' ) ) {
			return ActionScheduler_Store::STATUS_PENDING;
		}

		return 'pending';
	}
}

if ( ! function_exists( 'aswc_get_action_scheduler_complete_status' ) ) {
	/**
	 * Retrieve the status slug used for completed actions.
	 *
	 * Acts as a wrapper around Action Scheduler's complete status constant so
	 * the Scheduler API does not depend on the library when it's unavailable.
	 *
	 * @return string Complete status slug.
	 */
	function aswc_get_action_scheduler_complete_status() {
		if ( class_exists( '\\ActionScheduler\\Store' ) ) {
			return \ActionScheduler\Store::STATUS_COMPLETE;
		}

		if ( class_exists( 'ActionScheduler_Store' ) ) {
			return ActionScheduler_Store::STATUS_COMPLETE;
		}

		return 'complete';
	}
}

if ( ! function_exists( 'aswc_get_action_scheduler_failed_status' ) ) {
	/**
	 * Retrieve the status slug used for failed actions.
	 *
	 * Provides a fallback when Action Scheduler is unavailable so the Scheduler
	 * API remains decoupled from the library.
	 *
	 * @return string Failed status slug.
	 */
	function aswc_get_action_scheduler_failed_status() {
		if ( class_exists( '\\ActionScheduler\\Store' ) ) {
			return \ActionScheduler\Store::STATUS_FAILED;
		}

		if ( class_exists( 'ActionScheduler_Store' ) ) {
			return ActionScheduler_Store::STATUS_FAILED;
		}

		return 'failed';
	}
}

if ( ! function_exists( 'aswc_get_action_scheduler_running_status' ) ) {
	/**
	 * Retrieve the status slug used for in-progress actions.
	 *
	 * Wraps Action Scheduler's running status constant so the Scheduler API does
	 * not depend on the library when it's unavailable.
	 *
	 * @return string Running status slug.
	 */
	function aswc_get_action_scheduler_running_status() {
		if ( class_exists( '\\ActionScheduler\\Store' ) ) {
			return \ActionScheduler\Store::STATUS_RUNNING;
		}

		if ( class_exists( 'ActionScheduler_Store' ) ) {
			return ActionScheduler_Store::STATUS_RUNNING;
		}

		return 'in-progress';
	}
}

if ( ! function_exists( 'aswc_get_action_scheduler_canceled_status' ) ) {
	/**
	 * Retrieve the status slug used for canceled actions.
	 *
	 * Acts as a wrapper around Action Scheduler's canceled status constant so
	 * the Scheduler API does not depend on the library when it's unavailable.
	 *
	 * @return string Canceled status slug.
	 */
	function aswc_get_action_scheduler_canceled_status() {
		if ( class_exists( '\\ActionScheduler\\Store' ) ) {
			return \ActionScheduler\Store::STATUS_CANCELED;
		}

		if ( class_exists( 'ActionScheduler_Store' ) ) {
			return ActionScheduler_Store::STATUS_CANCELED;
		}

		return 'canceled';
	}
}

if ( ! function_exists( 'aswc_action_scheduler_log' ) ) {
	/**
	 * Log a message for a scheduled action without depending on Action Scheduler's logger.
	 *
	 * Provides access to Action Scheduler's logging system while avoiding a hard
	 * dependency on the library when it's unavailable.
	 *
	 * @param int    $action_id Action identifier.
	 * @param string $message   Message to record for the action.
	 *
	 * @return void
	 */
	function aswc_action_scheduler_log( $action_id, $message ) {
		if (
			class_exists( '\\ActionScheduler\\Logger' )
			&& method_exists( '\\ActionScheduler\\Logger', 'instance' )
		) {
			\ActionScheduler\Logger::instance()->log( $action_id, $message );
		} elseif (
			class_exists( 'ActionScheduler_Logger' )
			&& method_exists( 'ActionScheduler_Logger', 'instance' )
		) {
			ActionScheduler_Logger::instance()->log( $action_id, $message );
		}
	}
}

if ( ! function_exists( 'aswc_schedule_single_action' ) ) {
	/**
	 * Schedule a single action via Action Scheduler.
	 *
	 * Acts as a wrapper for the external {@see as_schedule_single_action()} helper
	 * so the Scheduler API does not depend directly on functions defined outside
	 * of the library.
	 *
	 * @param int    $timestamp When the action should run.
	 * @param string $hook      Hook name for the action.
	 * @param array  $args      Arguments for the action.
	 * @param string $group     Optional Action Scheduler group.
	 * @param bool   $unique    Whether the action is unique.
	 * @param int    $priority  Action priority.
	 *
	 * @return int Action ID. Returns 0 when Action Scheduler is unavailable.
	 */
	function aswc_schedule_single_action( $timestamp, $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			return as_schedule_single_action( $timestamp, $hook, $args, $group, $unique, $priority );
		}

		return 0;
	}
}

if ( ! function_exists( 'aswc_schedule_recurring_action' ) ) {
	/**
	 * Schedule a recurring action via Action Scheduler.
	 *
	 * Acts as a wrapper for the external {@see as_schedule_recurring_action()} helper
	 * so the Scheduler API does not depend directly on functions defined outside
	 * of the library.
	 *
	 * @param int    $timestamp When the first action should run.
	 * @param int    $interval  Seconds between runs.
	 * @param string $hook      Hook name for the action.
	 * @param array  $args      Arguments for the action.
	 * @param string $group     Optional Action Scheduler group.
	 * @param bool   $unique    Whether the action is unique.
	 * @param int    $priority  Action priority.
	 *
	 * @return int Action ID. Returns 0 when Action Scheduler is unavailable.
	 */
	function aswc_schedule_recurring_action( $timestamp, $interval, $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
		if ( function_exists( 'as_schedule_recurring_action' ) ) {
			return as_schedule_recurring_action( $timestamp, $interval, $hook, $args, $group, $unique, $priority );
		}

		return 0;
	}
}

if ( ! function_exists( 'aswc_schedule_cron_action' ) ) {
	/**
	 * Schedule a cron-like action via Action Scheduler.
	 *
	 * Acts as a wrapper for the external {@see as_schedule_cron_action()} helper
	 * so the Scheduler API does not depend directly on functions defined outside
	 * of the library.
	 *
	 * @param int    $timestamp When the first action should run.
	 * @param string $schedule  Cron schedule in WP-Cron format.
	 * @param string $hook      Hook name for the action.
	 * @param array  $args      Arguments for the action.
	 * @param string $group     Optional Action Scheduler group.
	 * @param bool   $unique    Whether the action is unique.
	 * @param int    $priority  Action priority.
	 *
	 * @return int Action ID. Returns 0 when Action Scheduler is unavailable.
	 */
	function aswc_schedule_cron_action( $timestamp, $schedule, $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
		if ( function_exists( 'as_schedule_cron_action' ) ) {
			return as_schedule_cron_action( $timestamp, $schedule, $hook, $args, $group, $unique, $priority );
		}

		return 0;
	}
}

if ( ! function_exists( 'aswc_schedule_unique_action' ) ) {
	/**
	 * Schedule a single unique action via Action Scheduler.
	 *
	 * Acts as a wrapper around {@see aswc_schedule_single_action()} to ensure
	 * the action is only scheduled once while keeping the Scheduler API
	 * decoupled from external functions.
	 *
	 * @param int    $timestamp When the action should run.
	 * @param string $hook      Hook name for the action.
	 * @param array  $args      Optional arguments for the action.
	 * @param string $group     Optional Action Scheduler group.
	 * @param int    $priority  Optional action priority.
	 *
	 * @return int Action ID. Returns 0 when Action Scheduler is unavailable.
	 */
	function aswc_schedule_unique_action( $timestamp, $hook, $args = array(), $group = '', $priority = 10 ) {
		return aswc_schedule_single_action( $timestamp, $hook, $args, $group, true, $priority );
	}
}

if ( ! function_exists( 'aswc_enqueue_async_action' ) ) {
	/**
	 * Enqueue an async action via Action Scheduler.
	 *
	 * Acts as a wrapper for the external {@see as_enqueue_async_action()} helper
	 * so the Scheduler API does not depend directly on functions defined outside
	 * of the library.
	 *
	 * @param string $hook  Hook name for the action.
	 * @param array  $args  Arguments for the action.
	 * @param string $group Optional Action Scheduler group.
	 *
	 * @return int Action ID. Returns 0 when Action Scheduler is unavailable.
	 */
	function aswc_enqueue_async_action( $hook, $args = array(), $group = '' ) {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			return as_enqueue_async_action( $hook, $args, $group );
		}

		return 0;
	}
}

if ( ! function_exists( 'aswc_unschedule_all_actions' ) ) {
	/**
	 * Unschedule all matching actions via Action Scheduler.
	 *
	 * Wrapper for {@see as_unschedule_all_actions()} to avoid a hard dependency
	 * on the function when the library is not loaded.
	 *
	 * @param string|null $hook  Optional hook name to clear. Default null.
	 * @param array       $args  Optional arguments to match.
	 * @param string      $group Optional action group.
	 *
	 * @return void
	 */
	function aswc_unschedule_all_actions( $hook = null, $args = array(), $group = '' ) {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( $hook, $args, $group );
		}
	}
}

if ( ! function_exists( 'aswc_unschedule_action' ) ) {
	/**
	 * Unschedule a single action via Action Scheduler.
	 *
	 * Wrapper for {@see as_unschedule_action()} to avoid a hard dependency on
	 * the function when the library is not loaded.
	 *
	 * @param string $hook  Hook name to clear.
	 * @param array  $args  Arguments to match.
	 * @param string $group Optional action group.
	 *
	 * @return void
	 */
	function aswc_unschedule_action( $hook, $args = array(), $group = '' ) {
		if ( function_exists( 'as_unschedule_action' ) ) {
			as_unschedule_action( $hook, $args, $group );
		}
	}
}

if ( ! function_exists( 'aswc_next_scheduled_action' ) ) {
	/**
	 * Get the timestamp for the next scheduled action.
	 *
	 * Acts as a wrapper for {@see as_next_scheduled_action()} so the Scheduler
	 * API does not rely directly on external functions.
	 *
	 * @param string $hook  Hook name to search for.
	 * @param array  $args  Optional arguments to match.
	 * @param string $group Optional action group.
	 *
	 * @return int|false Timestamp of the next action or false if none found.
	 */
	function aswc_next_scheduled_action( $hook, $args = array(), $group = '' ) {
		if ( function_exists( 'as_next_scheduled_action' ) ) {
			return as_next_scheduled_action( $hook, $args, $group );
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_has_scheduled_action' ) ) {
	/**
	 * Determine if a scheduled action exists.
	 *
	 * Wrapper for {@see as_has_scheduled_action()} to decouple the Scheduler
	 * API from external functions.
	 *
	 * @param string $hook  Hook name to search for.
	 * @param array  $args  Optional arguments to match.
	 * @param string $group Optional action group.
	 *
	 * @return bool True if a matching action exists.
	 */
	function aswc_has_scheduled_action( $hook, $args = array(), $group = '' ) {
		if ( function_exists( 'as_has_scheduled_action' ) ) {
			return as_has_scheduled_action( $hook, $args, $group );
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_get_scheduled_actions' ) ) {
	/**
	 * Retrieve scheduled actions from Action Scheduler.
	 *
	 * Wrapper for {@see as_get_scheduled_actions()} to avoid direct
	 * dependencies on the library.
	 *
	 * @param array $query_args Query arguments.
	 *
	 * @return array List of scheduled action objects.
	 */
	function aswc_get_scheduled_actions( $query_args = array() ) {
		if ( function_exists( 'as_get_scheduled_actions' ) ) {
			return as_get_scheduled_actions( $query_args );
		}

		return array();
	}
}

if ( ! function_exists( 'aswc_get_edit_post_link' ) ) {
	/**
	 * Retrieve the edit post link for a given post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return string|false Edit link or false if unavailable.
	 */
	function aswc_get_edit_post_link( $post_id ) {
		return get_edit_post_link( $post_id );
	}
}

if ( ! function_exists( 'aswc_create_admin_notice' ) ) {
	/**
	 * Create an admin notice using core plugin utilities.
	 *
	 * Wraps the `WCS_Admin_Notice` class so the Scheduler API does not depend
	 * directly on the plugin's implementation when displaying notices.
	 *
	 * @param string $type        Notice type.
	 * @param array  $attributes  Optional container attributes.
	 * @param string $dismiss_url Optional URL used to dismiss the notice.
	 * @return WCS_Admin_Notice|null Notice instance or null if unavailable.
	 */
	function aswc_create_admin_notice( $type, $attributes = array(), $dismiss_url = '' ) {
		if ( class_exists( 'WCS_Admin_Notice' ) ) {
			return new WCS_Admin_Notice( $type, $attributes, $dismiss_url );
		}

		return null;
	}
}

if ( ! function_exists( 'aswc_get_plugin_directory' ) ) {
	/**
	 * Retrieve the plugin directory path using core plugin utilities.
	 *
	 * Wraps the `WC_Subscriptions_Plugin::get_plugin_directory` method so the
	 * Scheduler API does not depend directly on the main plugin class.
	 *
	 * @param string $path Optional subpath to append to the plugin directory.
	 * @return string Plugin directory path or empty string if unavailable.
	 */
	function aswc_get_plugin_directory( $path = '' ) {
		if (
			class_exists( 'WC_Subscriptions_Plugin' )
			&& method_exists( 'WC_Subscriptions_Plugin', 'instance' )
			&& method_exists( 'WC_Subscriptions_Plugin', 'get_plugin_directory' )
		) {
			return WC_Subscriptions_Plugin::instance()->get_plugin_directory( $path );
		}

		return '';
	}
}

if ( ! function_exists( 'aswc_get_logger' ) ) {
	/**
	 * Retrieve a WooCommerce logger instance.
	 *
	 * Wraps the `wc_get_logger` helper so the Scheduler API does not depend
	 * directly on WooCommerce when obtaining a logger.
	 *
	 * @return WC_Logger_Interface|null Logger instance or null if unavailable.
	 */
	function aswc_get_logger() {
		if ( function_exists( 'wc_get_logger' ) ) {
			return wc_get_logger();
		}

		return null;
	}
}
