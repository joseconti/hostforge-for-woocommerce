<?php // phpcs:ignoreFile
/**
 * Payment scheduling utilities.
 *
 * Provides helper methods for scheduling and unscheduling subscription payment
 * events via the core Scheduler API. Supports scheduling renewal payments,
 * manual payment events and advanced payment retries driven by retry rules.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI
 */

/**
 * Core payment scheduler for subscriptions.
 */
class ASWC_Scheduler_Payments {

	/**
	 * Default retry intervals in seconds for each attempt.
	 *
	 * The array index represents the retry attempt (starting from 0). These
	 * values can be overridden via the `advanced_subscriptions_woocommerce_retry_intervals`
	 * option. The length of the array determines the maximum number of retries
	 * that will be scheduled when using {@see schedule_retry_for_attempt()}.
	 */
	const DEFAULT_RETRY_INTERVALS = array(
		DAY_IN_SECONDS / 2,
		DAY_IN_SECONDS / 2,
		DAY_IN_SECONDS,
		DAY_IN_SECONDS * 2,
		DAY_IN_SECONDS * 3,
	);

	/**
	 * Option name storing custom retry intervals.
	 */
	const RETRY_INTERVALS_OPTION = 'advanced_subscriptions_woocommerce_retry_intervals';

	/**
	 * Option name storing the desired maximum number of retry attempts.
	 * If present, retry intervals will be padded to this length.
	 */
	const RETRY_MAX_ATTEMPTS_OPTION = 'advanced_subscriptions_woocommerce_retry_max_attempts';

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

		add_action(
			'advanced_scheduled_subscription_payment',
			array( $this, 'gateway_scheduled_subscription_payment' ),
			10,
			1
		);
		add_action(
			'advanced_scheduled_subscription_payment_retry',
			array( $this, 'gateway_scheduled_subscription_payment' ),
			10,
			1
		);
		// Some environments/processors fire a different hook name for retries.
		// Hook it as well so our unified processor runs regardless.
		add_action(
			'advanced_process_subscription_payment_retry',
			array( $this, 'gateway_scheduled_subscription_payment' ),
			10,
			1
		);
		add_action( 'woocommerce_order_status_failed', array( $this, 'handle_failed_renewal_order' ), 10, 1 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'maybe_schedule_retry_on_failed_change' ), 10, 4 );
	}

	/**
	 * Handle a scheduled subscription payment.
	 *
	 * @param int|WC_Subscription $subscription_id Subscription ID or instance.
	 * @param mixed               $deprecated      Deprecated argument.
	 *
	 * @throws InvalidArgumentException When the subscription cannot be found.
	 * @return void
	 */
	public function gateway_scheduled_subscription_payment( $subscription_id, $deprecated = null ) {
		// Detect which hook is being executed.
		$current_hook = current_filter();
		$is_retry_hook = in_array( $current_hook, array( 'advanced_scheduled_subscription_payment_retry', 'advanced_process_subscription_payment_retry' ), true );
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( '[gateway_scheduled_subscription_payment] Hook context: ' . $current_hook . ' (retry: ' . ( $is_retry_hook ? 'yes' : 'no' ) . ')' );
		}
		// Log entry point and raw args.
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( '[gateway_scheduled_subscription_payment] ENTER — $subscription_id type: ' . ( is_object( $subscription_id ) ? get_class( $subscription_id ) : gettype( $subscription_id ) ) );
			ASWC_Log::log( '[gateway_scheduled_subscription_payment] ENTER — $subscription_id value: ' . ( is_object( $subscription_id ) ? ( method_exists( $subscription_id, 'get_id' ) ? $subscription_id->get_id() : 'object(no get_id)' ) : (string) $subscription_id ) );
			ASWC_Log::log( '[gateway_scheduled_subscription_payment] ENTER — $deprecated present: ' . ( null === $deprecated ? 'no' : 'yes' ) );
		}

		// If a retry passed an order ID as first argument, it will arrive as a scalar.
		// Detect when the value looks like an order id and resolve the subscription from it.
		if ( is_scalar( $subscription_id ) && ! is_object( $subscription_id ) ) {
			$maybe_order = wc_get_order( absint( $subscription_id ) );
			if ( $maybe_order instanceof WC_Order ) {
				$maybe_sub_id = (int) aswc_get_meta_data( $maybe_order->get_id(), '_aswc_subscription', 0 );
				if ( $maybe_sub_id > 0 ) {
					if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log( '[gateway_scheduled_subscription_payment] First arg looks like order_id. Resolved subscription via order ' . $maybe_order->get_id() . ' => sub ' . $maybe_sub_id );
					}
					$subscription_id = $maybe_sub_id;
				}
			}
		}

		// Normalize to a WC_Subscription object.
		if ( ! is_object( $subscription_id ) ) {
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[gateway_scheduled_subscription_payment] Resolving subscription object from ID: ' . $subscription_id );
			}
			$subscription = ASWC_Scheduler_API::get_subscription( $subscription_id );
		} else {
			$subscription = $subscription_id;
		}

		// Safety: bail if subscription not found (but log before throwing).
		if ( false === $subscription ) {
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[gateway_scheduled_subscription_payment] ERROR — Subscription not found for ID: ' . $subscription_id );
			}
			// translators: %d: subscription ID.
			throw new InvalidArgumentException( sprintf( esc_html__( 'Subscription doesn\'t exist in scheduled action: %d', 'advanced-subscriptions-for-woocommerce' ), absint( $subscription_id ) ) );
		}


		// PHASE 1.2: Monitor for potential race conditions (logging only, no blocking).
		$subscription_id_for_lock = method_exists( $subscription, 'get_id' ) ? $subscription->get_id() : 0;
		if ( $subscription_id_for_lock > 0 && function_exists( 'aswc_is_payment_locked' ) ) {
			$is_currently_locked = aswc_is_payment_locked( $subscription_id_for_lock );
			if ( $is_currently_locked ) {
				// Potential race condition detected - log it for monitoring.
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( sprintf(
						'[gateway_scheduled_subscription_payment] ⚠️ RACE CONDITION DETECTED: Subscription %d payment lock exists - another process may be running simultaneously',
						$subscription_id_for_lock
					) );
				}
				// NOTE: We do NOT abort here - this is monitoring phase only.
				// If you see this message repeatedly, consider implementing full lock system.
			} else {
				// No lock detected - acquire monitoring lock to detect future race conditions.
				if ( function_exists( 'aswc_acquire_payment_lock' ) ) {
					$lock_acquired = aswc_acquire_payment_lock( $subscription_id_for_lock, 300 );
					if ( $lock_acquired && class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log( sprintf(
							'[gateway_scheduled_subscription_payment] 🔒 Monitoring lock acquired for subscription %d',
							$subscription_id_for_lock
						) );
					}
				}
			}
		}
		// Log some subscription context.
		if ( class_exists( 'ASWC_Log' ) ) {
			$sub_id       = method_exists( $subscription, 'get_id' ) ? $subscription->get_id() : '(no id)';
			$sub_status   = method_exists( $subscription, 'get_status' ) ? $subscription->get_status() : '(no status)';
			$next_payment = method_exists( $subscription, 'get_time' ) ? $subscription->get_time( 'next_payment' ) : 0;
			$retry_time   = method_exists( $subscription, 'get_time' ) ? $subscription->get_time( 'payment_retry' ) : 0;
			$is_manual    = method_exists( $subscription, 'is_manual' ) ? ( $subscription->is_manual() ? 'yes' : 'no' ) : 'unknown';
			ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Context — sub_id:%1$s status:%2$s next_payment_ts:%3$s retry_ts:%4$s manual:%5$s', $sub_id, $sub_status, $next_payment, $retry_time, $is_manual ) );

			// Log complete subscription state before payment processing.
			if ( method_exists( 'ASWC_Log', 'log_subscription_snapshot' ) ) {
				ASWC_Log::log_subscription_snapshot( $sub_id, 'before-payment' );
			}
		}

		// If there is a retry scheduled, clear it before proceeding (avoid duplicates).
		if ( $this->has_scheduled_retry( $subscription, false ) ) {
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[gateway_scheduled_subscription_payment] Found scheduled retry — unscheduling now.' );
			}
			$this->unschedule_retry( $subscription, false );
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[gateway_scheduled_subscription_payment] Retry unscheduled.' );
			}
		}

		// Skip ended subscriptions.
		$ended_statuses = ASWC_Scheduler_API::get_subscription_ended_statuses();
		if ( method_exists( $subscription, 'has_status' ) && $subscription->has_status( $ended_statuses ) ) {
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[gateway_scheduled_subscription_payment] Subscription is in an ended status; skipping payment processing.' );
			}
			// PHASE 1.2: Release lock before return.
			if ( isset( $subscription_id_for_lock ) && $subscription_id_for_lock > 0 && function_exists( 'aswc_release_payment_lock' ) ) {
				aswc_release_payment_lock( $subscription_id_for_lock );
			}
			return;
		}

		// If handling a retry, always try to create a new renewal order, even if the last is not failed.
		if ( $is_retry_hook ) {
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[gateway_scheduled_subscription_payment] Handling RETRY context (advanced_scheduled_subscription_payment_retry): will force creation of a new renewal order.' );
			}
			// Prevent duplicate scheduling: if subscription is being manually reactivated (status active), skip retry creation.
			if ( method_exists( $subscription, 'get_status' ) && 'active' === $subscription->get_status() ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( '[gateway_scheduled_subscription_payment] Subscription is active (possibly manually reactivated); skipping retry renewal order creation.' );
				}
			// PHASE 1.2: Release lock before return.
			if ( isset( $subscription_id_for_lock ) && $subscription_id_for_lock > 0 && function_exists( 'aswc_release_payment_lock' ) ) {
				aswc_release_payment_lock( $subscription_id_for_lock );
			}
				return;
			}
			// Always create a new renewal order for retry.
			$new_order = $this->maybe_create_new_renewal_order( $subscription );
			if ( $new_order instanceof WC_Order ) {
				aswc_update_meta_data( $subscription->get_id(), '_aswc_last_renewal_order_id', $new_order->get_id() );
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Forced new renewal order created for retry: %d', $new_order->get_id() ) );
				}
				$latest_renewal_order = $new_order;
			} else {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( '[gateway_scheduled_subscription_payment] WARNING — Could not create a new renewal order for retry.' );
				}
				// Fallback: try to use the last order if available.
				$latest_renewal_order = $subscription->get_last_order( 'all', 'renewal' );
			}
			// -----------------------------------------------
			// Ensure we have a renewal order for retries. Try harder if still empty.
			if ( empty( $latest_renewal_order ) || ! ( $latest_renewal_order instanceof WC_Order ) ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( '[gateway_scheduled_subscription_payment] No renewal order resolved for RETRY — attempting to force-create again.' );
				}
				// Second attempt: try all factories again.
				$forced = $this->maybe_create_new_renewal_order( $subscription );
				if ( $forced instanceof WC_Order ) {
					$latest_renewal_order = $forced;
					aswc_update_meta_data( $subscription->get_id(), '_aswc_last_renewal_order_id', $latest_renewal_order->get_id() );
					if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Forced creation succeeded on retry: %d', $latest_renewal_order->get_id() ) );
					}
				}
			}
			// Final guard: if we STILL don't have an order, bail safely with diagnostics.
			if ( empty( $latest_renewal_order ) || ! ( $latest_renewal_order instanceof WC_Order ) ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( '[gateway_scheduled_subscription_payment] ERROR — Retry execution aborted because no renewal order could be created or found.' );
				}
				if ( method_exists( $subscription, 'add_order_note' ) ) {
					$subscription->add_order_note( __( 'Retry could not be processed because no renewal order could be created.', 'advanced-subscriptions-for-woocommerce' ) );
				}
			// PHASE 1.2: Release lock before return.
			if ( isset( $subscription_id_for_lock ) && $subscription_id_for_lock > 0 && function_exists( 'aswc_release_payment_lock' ) ) {
				aswc_release_payment_lock( $subscription_id_for_lock );
			}
				return;
			}
			// Continue to payment logic below, skipping the normal "failed" check.
		} else {
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[gateway_scheduled_subscription_payment] Handling NORMAL renewal context.' );
			}

			// Attempt to get latest renewal order.
			$latest_renewal_order = $subscription->get_last_order( 'all', 'renewal' );
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[gateway_scheduled_subscription_payment] Latest renewal order fetched: ' . ( $latest_renewal_order ? $latest_renewal_order->get_id() : 'none' ) );
			}

			if ( empty( $latest_renewal_order ) ) {
				$query = wc_get_orders(
					array(
						'subscription_renewal' => $subscription->get_id(),
						'orderby'              => 'date',
						'order'                => 'DESC',
						'limit'                => 1,
					)
				);

				if ( ! empty( $query ) ) {
					$latest_renewal_order = $query[0];
					aswc_update_meta_data( $subscription->get_id(), 'aswc_last_renewal_order_id', $latest_renewal_order->get_id() );
					if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log( '[gateway_scheduled_subscription_payment] Fallback renewal order lookup succeeded: ' . $latest_renewal_order->get_id() );
					}
				}
			}

			if ( empty( $latest_renewal_order ) ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( '[gateway_scheduled_subscription_payment] WARNING — No renewal order found. Adding order note and returning.' );
				}
				$subscription->add_order_note( __( "Renewal order payment processing was skipped because we couldn't locate the latest renewal order.", 'advanced-subscriptions-for-woocommerce' ) );
			// PHASE 1.2: Release lock before return.
			if ( isset( $subscription_id_for_lock ) && $subscription_id_for_lock > 0 && function_exists( 'aswc_release_payment_lock' ) ) {
				aswc_release_payment_lock( $subscription_id_for_lock );
			}
				return;
			}

			// If the latest renewal order is failed (typical in a retry), create a NEW renewal order
			// so we never attempt to charge a failed order again.
			$created_new_order = false;
			if ( $latest_renewal_order && ( $latest_renewal_order->has_status( 'failed' ) ) ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Latest renewal order %d is failed — attempting to create a fresh renewal order for this attempt.', $latest_renewal_order->get_id() ) );
				}

				$new_order = $this->maybe_create_new_renewal_order( $subscription );
				if ( $new_order instanceof WC_Order ) {
					$latest_renewal_order = $new_order;
					$created_new_order    = true;
					// Persist last renewal order id for later retry args/logs.
					aswc_update_meta_data( $subscription->get_id(), '_aswc_last_renewal_order_id', $latest_renewal_order->get_id() );
					if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Fresh renewal order created: %d', $latest_renewal_order->get_id() ) );
					}
				} else {
					if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log( '[gateway_scheduled_subscription_payment] WARNING — Could not create a new renewal order; will proceed with existing one.' );
					}
				}
			}
		}
		// Defensive: Ensure we have a valid renewal order before proceeding.
		if ( empty( $latest_renewal_order ) || ! ( $latest_renewal_order instanceof WC_Order ) ) {
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[gateway_scheduled_subscription_payment] No renewal order available after resolution — exiting safely.' );
			}
			// PHASE 1.2: Release lock before return.
			if ( isset( $subscription_id_for_lock ) && $subscription_id_for_lock > 0 && function_exists( 'aswc_release_payment_lock' ) ) {
				aswc_release_payment_lock( $subscription_id_for_lock );
			}
			return;
		}
		// If we have an order, log some details.
		if ( class_exists( 'ASWC_Log' ) ) {
			$order_id   = $latest_renewal_order->get_id();
			$order_tot  = $latest_renewal_order->get_total();
			$order_cur  = $latest_renewal_order->get_currency();
			$needs_pay  = $latest_renewal_order->needs_payment() ? 'yes' : 'no';
			$order_stat = function_exists( 'aswc_get_order_status_name' ) ? aswc_get_order_status_name( $latest_renewal_order ) : $latest_renewal_order->get_status();
			ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Order context — id:%1$d total:%2$s %3$s status:%4$s needs_payment:%5$s', $order_id, $order_tot, $order_cur, $order_stat, $needs_pay ) );

			// Log pre-payment processing details.
			if ( method_exists( 'ASWC_Log', 'log_payment_processing' ) ) {
				ASWC_Log::log_payment_processing(
					$subscription->get_id(),
					$order_id,
					'pre-payment',
					array(
						'amount'   => $order_tot,
						'currency' => $order_cur,
						'status'   => $order_stat,
					)
				);
			}
		}

				if ( $latest_renewal_order->needs_payment() ) {
					if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log( '[gateway_scheduled_subscription_payment] Order needs payment — dispatching gateway hook.' );
					}
					$payment_result = $this->trigger_gateway_renewal_payment_hook( $latest_renewal_order );
					if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log( '[gateway_scheduled_subscription_payment] Gateway hook dispatched.' );
					}
					// Diagnostic: log returned payment result (raw type and value).
					if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log( '[gateway_scheduled_subscription_payment] Payment result (raw type): ' . gettype( $payment_result ) );
						ASWC_Log::log( '[gateway_scheduled_subscription_payment] Payment result (raw value): ' . var_export( $payment_result, true ) );
					}

					// Determine success by result flag or by checking if the order still needs payment.
					$order_after = wc_get_order( $latest_renewal_order->get_id() );
					if ( class_exists( 'ASWC_Log' ) && $order_after ) {
						ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Post-hook order state — id:%1$d status:%2$s needs_payment:%3$s', $order_after->get_id(), $order_after->get_status(), $order_after->needs_payment() ? 'yes' : 'no' ) );
					}
					$success = ( true === $payment_result ) || ( $order_after && ! $order_after->needs_payment() && ! $order_after->has_status( 'failed' ) );
					if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log( '[gateway_scheduled_subscription_payment] Computed success: ' . ( $success ? 'true' : 'false' ) );

						// Log post-payment processing details.
						if ( method_exists( 'ASWC_Log', 'log_payment_processing' ) && $order_after ) {
							ASWC_Log::log_payment_processing(
								$subscription->get_id(),
								$order_after->get_id(),
								'post-payment',
								array(
									'success'       => $success ? 'yes' : 'no',
									'status'        => $order_after->get_status(),
									'needs_payment' => $order_after->needs_payment() ? 'yes' : 'no',
								)
							);
						}
					}
					if ( class_exists( 'ASWC_Log' ) ) {
						$attempts_preview = (int) aswc_get_meta_data( $subscription->get_id(), '_aswc_retry_attempts', 0 );
						$max_preview      = (int) $this->get_max_retry_attempts( $subscription );
						ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Attempts so far: %d of %d', $attempts_preview, $max_preview ) );
					}

					if ( $success ) {
						// Clear retry metadata and attempts on success.
						$this->reset_retries( $subscription );
						if ( class_exists( 'ASWC_Log' ) ) {
							ASWC_Log::log( '[gateway_scheduled_subscription_payment] Payment succeeded — cleared retry metadata, reset attempts, and unscheduled retries.' );
						}
						// Ensure subscription is active after a successful payment and (re)schedule next renewal.
						if ( method_exists( $subscription, 'get_status' ) ) {
							$current_status = $subscription->get_status();
							if ( in_array( $current_status, array( 'on-hold', 'cancelled', 'pending-cancel' ), true ) ) {
								// Use meta as single source of truth instead of update_status().
								aswc_update_meta_data( $subscription->get_id(), 'aswc_subscription_status', 'active' );
								if ( class_exists( 'ASWC_Log' ) ) {
									ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Subscription %d set to active after successful retry.', $subscription->get_id() ) );

									// Log status change with before/after values.
									if ( method_exists( 'ASWC_Log', 'log_data_change' ) ) {
										ASWC_Log::log_data_change(
											$subscription->get_id(),
											'aswc_subscription_status',
											$current_status,
											'active',
											'payment-success'
										);
									}
								}
							}
						}
						$this->schedule_payment( $subscription, null, null );
						// Diagnostic: confirm no retry scheduled after success.
						if ( class_exists( 'ASWC_Log' ) ) {
							$scheduled_retry = $this->get_scheduled_retry( $subscription, null );
							ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] After success, next scheduled retry: %s', false === $scheduled_retry ? 'none' : $scheduled_retry ) );

							// Log final subscription state after successful payment.
							if ( method_exists( 'ASWC_Log', 'log_subscription_snapshot' ) ) {
								ASWC_Log::log_subscription_snapshot( $subscription->get_id(), 'after-payment-success' );
							}
						}
					} else {
						// Mark failure for subscription state handlers.
						if ( method_exists( $subscription, 'payment_failed' ) ) {
							$subscription->payment_failed();
						}

						// Ensure a valid last renewal order id is persisted.
						$last_id_meta = (int) aswc_get_meta_data( $subscription->get_id(), '_aswc_last_renewal_order_id', null );
						if ( ! $last_id_meta ) {
							aswc_update_meta_data( $subscription->get_id(), '_aswc_last_renewal_order_id', $latest_renewal_order->get_id() );
						}

						// Confirm we are entering the failure path.
						if ( class_exists( 'ASWC_Log' ) ) {
							ASWC_Log::log( '[gateway_scheduled_subscription_payment] Handling failure path — will compute retry/backoff.' );
						}
						if ( class_exists( 'ASWC_Log' ) ) {
							$retry_args_preview = $this->get_retry_action_args( $subscription );
							ASWC_Log::log( '[gateway_scheduled_subscription_payment] Retry args preview (will be stored on action): ' . wp_json_encode( array_values( $retry_args_preview ) ) );
						}

						// Compute retry backoff using configured intervals and attempt counter.
						$attempts_key = '_aswc_retry_attempts';
						$attempts     = (int) aswc_get_meta_data( $subscription->get_id(), $attempts_key, 0 );
						$intervals    = $this->get_retry_intervals();
						$max_retries  = (int) $this->get_max_retry_attempts( $subscription );

						// Optional diagnostic: log effective max retries and their sources.
						if ( class_exists( 'ASWC_Log' ) ) {
							ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Effective max retries for subscription %d: %d (setting:%d, option:%d, intervals:%d)', 
								$subscription->get_id(),
								$max_retries,
								(int) get_option( 'aswc_after_no_failed_attempt_cancel', 0 ),
								(int) get_option( self::RETRY_MAX_ATTEMPTS_OPTION, 0 ),
								(int) count( $this->get_retry_intervals() )
							) );
						}

						// If we've already exhausted retries, cancel the subscription and stop.
						if ( $attempts >= $max_retries ) {
							$this->unschedule_retry( $subscription, false );
							$old_status = method_exists( $subscription, 'get_status' ) ? $subscription->get_status() : '(unknown)';
							// Use meta as single source of truth instead of update_status().
							aswc_update_meta_data( $subscription->get_id(), 'aswc_subscription_status', 'cancelled' );
							if ( class_exists( 'ASWC_Log' ) ) {
								ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Max retries reached (%d). Subscription canceled; not scheduling further retries.', $max_retries ) );

								// Log status change with before/after values.
								if ( method_exists( 'ASWC_Log', 'log_data_change' ) ) {
									ASWC_Log::log_data_change(
										$subscription->get_id(),
										'aswc_subscription_status',
										$old_status,
										'cancelled',
										'max-retries-reached'
									);
								}

								// Log final subscription state.
								if ( method_exists( 'ASWC_Log', 'log_subscription_snapshot' ) ) {
									ASWC_Log::log_subscription_snapshot( $subscription->get_id(), 'after-cancel' );
								}
							}
			// PHASE 1.2: Release lock before return.
			if ( isset( $subscription_id_for_lock ) && $subscription_id_for_lock > 0 && function_exists( 'aswc_release_payment_lock' ) ) {
				aswc_release_payment_lock( $subscription_id_for_lock );
			}
							return;
						}

						if ( $attempts < $max_retries ) {
							if ( isset( $intervals[ $attempts ] ) ) {
								$delay = (int) $intervals[ $attempts ];
							} else {
								// If intervals are shorter than max attempts, keep using the last interval.
								$delay = (int) end( $intervals );
							}
							$next_time = $this->calculate_timestamp_from_interval( $delay );

							// Persist next retry details and bump attempts.
							aswc_update_meta_data( $subscription->get_id(), '_aswc_payment_retry', $next_time );
							aswc_update_meta_data( $subscription->get_id(), $attempts_key, $attempts + 1 );

							if ( class_exists( 'ASWC_Log' ) ) {
								ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Gateway reported failure — scheduling retry. attempt:%d delay:%d next_ts:%d', $attempts, $delay, $next_time ) );
							}

							// Log retry action args before scheduling.
							if ( class_exists( 'ASWC_Log' ) ) {
								$retry_args = $this->get_retry_action_args( $subscription );
								ASWC_Log::log( '[gateway_scheduled_subscription_payment] Retry action args: ' . wp_json_encode( $retry_args ) );
							}

							// Schedule the retry at the computed timestamp.
							if ( class_exists( 'ASWC_Log' ) ) {
								ASWC_Log::log( '[gateway_scheduled_subscription_payment] Scheduling retry now…' );
							}
							$this->schedule_retry( $subscription, $next_time, null );
							if ( class_exists( 'ASWC_Log' ) ) {
								$scheduled_retry = $this->get_scheduled_retry( $subscription, null );
								ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] get_scheduled_retry after schedule: %s', false === $scheduled_retry ? 'none' : $scheduled_retry ) );
							}
						} else {
							// No more retries available per max attempts.
							$this->unschedule_retry( $subscription, false );
							if ( class_exists( 'ASWC_Log' ) ) {
								ASWC_Log::log( '[gateway_scheduled_subscription_payment] Max attempts reached for configured policy — not scheduling further retries.' );
							}
						}
					}
				} elseif ( $latest_renewal_order->get_total() > 0 ) {
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[gateway_scheduled_subscription_payment] Order total > 0 but already paid — adding order note and skipping.' );
			}
			$subscription->add_order_note(
				sprintf(
				/* Translators: 1: renewal order ID as a link, 2: order status */
					__( 'Payment processing of the renewal order %1$s was skipped because it is already paid (%2$s).', 'advanced-subscriptions-for-woocommerce' ),
					'<a href="' . esc_url( $latest_renewal_order->get_edit_order_url() ) . '">' . _x( '#', 'hash before order number', 'advanced-subscriptions-for-woocommerce' ) . $latest_renewal_order->get_order_number() . '</a>',
					function_exists( 'aswc_get_order_status_name' ) ? aswc_get_order_status_name( $latest_renewal_order ) : $latest_renewal_order->get_status()
				)
			);
		} elseif ( 0 === $latest_renewal_order->get_total() ) {
			// This branch handles zero-total renewals or other edge cases.
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[gateway_scheduled_subscription_payment] Order total <= 0 — nothing to charge. Skipping.' );
			}
		}

		// PHASE 1.2: Release monitoring lock if it was acquired.
		if ( isset( $subscription_id_for_lock ) && $subscription_id_for_lock > 0 && function_exists( 'aswc_release_payment_lock' ) ) {
			$lock_released = aswc_release_payment_lock( $subscription_id_for_lock );
			if ( $lock_released && class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( sprintf(
					'[gateway_scheduled_subscription_payment] 🔓 Monitoring lock released for subscription %d',
					$subscription_id_for_lock
				) );
			}
		}

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( '[gateway_scheduled_subscription_payment] EXIT' );
		}
	}

	/**
	 * Back-compat wrapper expected by scheduler.php to process a scheduled retry.
	 *
	 * Some parts of the scheduler call this method directly. This wrapper normalizes
	 * the arguments and forwards to the unified handler
	 * {@see ASWC_Scheduler_Payments::gateway_scheduled_subscription_payment()}.
	 *
	 * Accepts any of:
	 *  - ($subscription_id)
	 *  - ($subscription_id, $order_id)
	 *  - (array $args) where $args contains 'subscription_id' and optionally 'order_id'
	 *
	 * @param mixed $subscription_id Subscription ID or args array.
	 * @param mixed $order_id        Optional renewal order ID.
	 * @return void
	 */
	public function process_scheduled_retry( $subscription_id, $order_id = null ) {
		// Normalize arguments if an associative array was passed from Action Scheduler.
		if ( is_array( $subscription_id ) ) {
			$args            = $subscription_id;
			$subscription_id = isset( $args['subscription_id'] ) ? (int) $args['subscription_id'] : ( isset( $args[1] ) ? (int) $args[1] : 0 );
			$order_id        = isset( $args['order_id'] ) ? (int) $args['order_id'] : ( isset( $args[0] ) ? (int) $args[0] : null );
		}

		// If arguments were passed inverted, try to detect and swap.
		if ( $order_id && ! $subscription_id ) {
			// Maybe first param was actually the order id.
			$maybe_order = wc_get_order( (int) $order_id );
			if ( $maybe_order instanceof WC_Order ) {
				$maybe_sub_id = (int) aswc_get_meta_data( $maybe_order->get_id(), '_aswc_subscription', 0 );
				if ( $maybe_sub_id > 0 ) {
					$subscription_id = $maybe_sub_id;
				}
			}
		}

		// Persist last renewal order id if provided so retry args and logs have it.
		if ( $subscription_id && $order_id ) {
			aswc_update_meta_data( (int) $subscription_id, '_aswc_last_renewal_order_id', (int) $order_id );
		}

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log(
				sprintf(
					'[process_scheduled_retry] subscription_id:%1$s order_id:%2$s',
					$subscription_id ? (int) $subscription_id : 'n/a',
					$order_id ? (int) $order_id : 'n/a'
				)
			);
		}

		// Delegate to unified handler which already detects retry context by hook name
		// and will force-create a fresh renewal order if needed.
		$this->gateway_scheduled_subscription_payment( $subscription_id );
	}

			   /**
				* Fire a gateway specific hook when a renewal payment is due.
				*
				* @param WC_Order|false $renewal_order Renewal order instance.
				*
				* @return bool|null True if paid, false on failure, null when no hook is available.
				*/
		public function trigger_gateway_renewal_payment_hook( $renewal_order ) {
			// Basic validation of order and chargeability.
			if ( empty( $renewal_order ) ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( 'No renewal order instance provided to trigger_gateway_renewal_payment_hook.' );
				}
				return null;
			}

			if ( $renewal_order->get_total() <= 0 ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( sprintf( 'Renewal order %d has total <= 0. Nothing to charge.', $renewal_order->get_id() ) );
				}
				return null;
			}

			// If the order does not have a payment method, try to inherit it.
			$gateway_on_order = $renewal_order->get_payment_method();
			if ( empty( $gateway_on_order ) ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( sprintf( 'Renewal order %d has no payment method. Attempting to inherit…', $renewal_order->get_id() ) );
				}

				$subscription = null;

				// 1) Prefer Advanced Subscriptions For WooCommerce helper when available.
				if ( function_exists( 'wcs_get_subscriptions_for_renewal_order' ) ) {
					$subs = wcs_get_subscriptions_for_renewal_order( $renewal_order );
					if ( ! empty( $subs ) && is_array( $subs ) ) {
						$subscription = current( $subs );
					}
				}

				// 2) Our own linkage: the renewal order stores _aswc_subscription with the subscription id.
				if ( ! $subscription ) {
					$sub_id_from_meta = (int) aswc_get_meta_data( $renewal_order->get_id(), '_aswc_subscription', 0 );
					if ( $sub_id_from_meta > 0 && class_exists( 'ASWC_Scheduler_API' ) && method_exists( 'ASWC_Scheduler_API', 'get_subscription' ) ) {
						$subscription = ASWC_Scheduler_API::get_subscription( $sub_id_from_meta );
					}
				}

				// 3) As a last resort, try to infer via generic query (legacy environments).
				if ( ! $subscription && function_exists( 'wc_get_orders' ) ) {
					$maybe = wc_get_orders(
						array(
							'subscription_renewal' => $renewal_order->get_id(),
							'limit'                 => 1,
						)
					);
					if ( ! empty( $maybe ) && is_array( $maybe ) ) {
						$subscription = current( $maybe );
					}
				}

				// Try to inherit from the subscription itself.
				if ( $subscription && is_object( $subscription ) ) {
					$sub_gateway = method_exists( $subscription, 'get_payment_method' ) ? $subscription->get_payment_method() : '';
					if ( ! empty( $sub_gateway ) ) {
						$renewal_order->set_payment_method( $sub_gateway );
						if ( method_exists( $subscription, 'get_payment_method_title' ) && $subscription->get_payment_method_title() ) {
							$renewal_order->set_payment_method_title( $subscription->get_payment_method_title() );
						}
						$renewal_order->save();
						if ( class_exists( 'ASWC_Log' ) ) {
							ASWC_Log::log( sprintf( 'Payment method "%1$s" inherited from subscription for renewal order %2$d.', $sub_gateway, $renewal_order->get_id() ) );
						}
					}
				}

				// 4) If still empty, inherit from the subscription's parent order (typical in our plugin).
				if ( empty( $renewal_order->get_payment_method() ) ) {
					$sub_id = isset( $sub_id_from_meta ) ? (int) $sub_id_from_meta : 0;
					if ( 0 === $sub_id && isset( $subscription ) && is_object( $subscription ) && method_exists( $subscription, 'get_id' ) ) {
						$sub_id = (int) $subscription->get_id();
					}
					if ( $sub_id > 0 ) {
						$parent_order_id = (int) aswc_get_meta_data( $sub_id, '_aswc_parent_order', 0 );
						if ( $parent_order_id > 0 ) {
							$parent_order = wc_get_order( $parent_order_id );
							if ( $parent_order ) {
								$pm  = $parent_order->get_payment_method();
								$pmt = $parent_order->get_payment_method_title();
								if ( ! empty( $pm ) ) {
									$renewal_order->set_payment_method( $pm );
									if ( ! empty( $pmt ) ) {
										$renewal_order->set_payment_method_title( $pmt );
									}
									$renewal_order->save();
									if ( class_exists( 'ASWC_Log' ) ) {
										ASWC_Log::log( sprintf( 'Payment method "%1$s" inherited from parent order %2$d for renewal order %3$d.', $pm, $parent_order_id, $renewal_order->get_id() ) );
									}
								}
							}
						}
					}
				}

				// Re-read after attempted inheritance.
				$gateway_on_order = $renewal_order->get_payment_method();
				if ( empty( $gateway_on_order ) && class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( 'No payment method available to trigger gateway hook after inheritance attempt.' );
				}
			}

			if ( empty( $gateway_on_order ) ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( 'No payment method available to trigger gateway hook after inheritance attempt.' );
				}
				return null;
			}

			// Ensure gateways are loaded now that we have a method.
			WC()->payment_gateways();

			$gateway_id      = $gateway_on_order;
			$advanced_hook   = 'advanced_scheduled_subscription_payment_' . $gateway_id;
			$deprecated_hook = 'woocommerce_scheduled_subscription_payment_' . $gateway_id;

			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log(
					sprintf(
						'Triggering renewal payment hook for order %1$d. Total: %2$s %3$s. Gateway ID: %4$s',
						$renewal_order->get_id(),
						$renewal_order->get_total(),
						$renewal_order->get_currency(),
						$gateway_id
					)
				);
			}

			$advanced_hook_exists   = has_action( $advanced_hook );
			$deprecated_hook_exists = has_action( $deprecated_hook );

			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log(
					sprintf(
						'Hook availability for gateway %1$s - advanced: %2$s, deprecated: %3$s',
						$gateway_id,
						$advanced_hook_exists ? 'yes' : 'no',
						$deprecated_hook_exists ? 'yes' : 'no'
					)
				);
			}

			if ( true === $advanced_hook_exists ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( 'Firing hook: ' . $advanced_hook );
				}
				do_action( $advanced_hook, $renewal_order->get_total(), $renewal_order );
			} elseif ( true === $deprecated_hook_exists ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( 'Firing legacy hook: ' . $deprecated_hook );
				}
				do_action( $deprecated_hook, $renewal_order->get_total(), $renewal_order );
			} else {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( 'No payment hook found for gateway ' . $gateway_id );
				}
				return null;
			}

			// Refresh order object to read the final payment state.
			$renewal_order = wc_get_order( $renewal_order->get_id() );
			return $renewal_order->needs_payment() ? false : true;
		}

	/**
	 * Attempt to create a fresh renewal order for a subscription.
	 * Tries several known integration points to maximize compatibility.
	 *
	 * @param WC_Subscription $subscription
	 * @return WC_Order|false
	 */
	protected function maybe_create_new_renewal_order( $subscription ) {
		// Preferred: Advanced Subscriptions For WooCommerce helper if available.
		if ( function_exists( 'wcs_create_renewal_order' ) ) {
			try {
				$new_order = wcs_create_renewal_order( $subscription );
				if ( $new_order instanceof WC_Order ) {
					return $new_order;
				}
			} catch ( Exception $e ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( '[maybe_create_new_renewal_order] Exception using wcs_create_renewal_order: ' . $e->getMessage() );
				}
			}
		}

		// Alternative: some implementations expose `create_renewal_order()` on the subscription.
		if ( is_object( $subscription ) && method_exists( $subscription, 'create_renewal_order' ) ) {
			try {
				$new_order = $subscription->create_renewal_order();
				if ( $new_order instanceof WC_Order ) {
					return $new_order;
				}
			} catch ( Exception $e ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( '[maybe_create_new_renewal_order] Exception using subscription->create_renewal_order: ' . $e->getMessage() );
				}
			}
		}

		// Last resort: use scheduler API if it provides a factory (kept generic on purpose).
		if ( class_exists( 'ASWC_Scheduler_API' ) && method_exists( 'ASWC_Scheduler_API', 'create_renewal_order' ) ) {
			try {
				$new_order = ASWC_Scheduler_API::create_renewal_order( $subscription );
				if ( $new_order instanceof WC_Order ) {
					return $new_order;
				}
			} catch ( Exception $e ) {
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( '[maybe_create_new_renewal_order] Exception using ASWC_Scheduler_API::create_renewal_order: ' . $e->getMessage() );
				}
			}
		}

		return false;
	}

	/**
	 * Determine whether a gateway defines a renewal payment hook.
	 *
	 * @param string $gateway_id Gateway identifier.
	 *
	 * @return bool
	 */
	public function has_gateway_renewal_payment_hook( $gateway_id ) {
		return (bool) has_action( 'advanced_scheduled_subscription_payment_' . $gateway_id );
	}

		/**
		 * Resolve a timestamp for a given date type.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param string          $date_type    Subscription date type.
		 * @param int|null        $timestamp    Proposed timestamp.
		 *
		 * @return int
		 */
		protected function resolve_timestamp( $subscription, $date_type, $timestamp ) {
			   if ( null === $timestamp ) {
					   // Retrieve the timestamp in the site's timezone so it can
					   // be converted to UTC when scheduling the action.
					   $timestamp = $subscription->get_time( $date_type, 'site' );
						if ( class_exists( 'ASWC_Log' ) ) {
								ASWC_Log::log(
										sprintf(
												'[resolve_timestamp] subscription:%1$d date_type:%2$s derived:%3$d',
												$subscription->get_id(),
												$date_type,
												$timestamp
										)
								);
						}
				} else {
						if ( class_exists( 'ASWC_Log' ) ) {
								ASWC_Log::log(
										sprintf(
												'[resolve_timestamp] subscription:%1$d date_type:%2$s provided:%3$d',
												$subscription->get_id(),
												$date_type,
												$timestamp
										)
								);
						}
				}

				return (int) $timestamp;
		}

		/**
		 * Calculate a future timestamp based on an interval.
		 *
		 * @param int $interval Interval in seconds.
		 *
		 * @return int
		 */
	protected function calculate_timestamp_from_interval( $interval ) {
		return time() + (int) $interval;
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
	public function schedule_payment( $subscription, $timestamp = null, $group = null ) {
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log(
				sprintf(
					'[schedule_payment] subscription:%1$d raw_timestamp:%2$s group:%3$s',
					$subscription->get_id(),
					null === $timestamp ? 'null' : $timestamp,
					( null === $group ? 'default' : $group )
				)
			);
		}

		// If the subscription is active (e.g. manually reactivated), clear any retry state
		// and make sure no retry action survives. This prevents duplicate retry/payment actions
		// and ensures attempt counters do not carry over across manual activations.
		if ( method_exists( $subscription, 'get_status' ) && 'active' === $subscription->get_status() ) {
			$this->reset_retries( $subscription );
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[schedule_payment] Subscription is active — cleared retry metadata and unscheduled any pending retries.' );
			}
		}

		$timestamp = $this->resolve_timestamp( $subscription, 'next_payment', $timestamp );

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( '[schedule_payment] resolved timestamp:%d', $timestamp ) );
		}

		if ( empty( $timestamp ) || 0 >= $timestamp ) {
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[schedule_payment] Timestamp empty or <= 0. Unscheduling.' );
			}
			$this->unschedule_payment( $subscription, false );
			return;
		}

		// Always clear existing payment actions across all groups to prevent duplicates.
		$this->unschedule_payment( $subscription, false );
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( '[schedule_payment] Cleared existing payment actions.' );
		}

		$action_hook = 'advanced_scheduled_subscription_payment';
		$action_args = $this->scheduler->get_action_args( 'next_payment', $subscription );
		$this->scheduler->reschedule_action( $timestamp, $action_hook, $action_args, $group );

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( '[schedule_payment] Scheduled action at %d', $timestamp ) );
			$scheduled = $this->get_scheduled_payment( $subscription, $group );
			ASWC_Log::log( sprintf( '[schedule_payment] get_scheduled_payment result: %s', false === $scheduled ? 'none' : $scheduled ) );
		}
	}

		/**
		 * Remove any scheduled payment for a subscription.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param string|bool     $group        Action scheduler group. Pass `false`
		 *                                      to ignore the group when clearing.
		 *
		 * @return void
		 */
		public function unschedule_payment( $subscription, $group = null ) {
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log(
					sprintf(
						'[unschedule_payment] subscription:%1$d group:%2$s',
						$subscription->get_id(),
						( null === $group ? 'default' : ( false === $group ? 'all' : $group ) )
					)
				);
			}
			$this->scheduler->unschedule_actions(
				'advanced_scheduled_subscription_payment',
				$this->scheduler->get_action_args( 'next_payment', $subscription ),
				$group
			);

			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( '[unschedule_payment] completed.' );
			}
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
	public function schedule_retry( $subscription, $timestamp = null, $group = null ) {
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( '[schedule_retry] ENTER — sub_id:%d raw_timestamp:%s group:%s', $subscription->get_id(), ( null === $timestamp ? 'null' : (string) $timestamp ), ( null === $group ? 'default' : ( false === $group ? 'all' : (string) $group ) ) ) );
		}

		$timestamp = $this->resolve_timestamp( $subscription, 'payment_retry', $timestamp );
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( '[schedule_retry] Resolved timestamp: %d', (int) $timestamp ) );
		}

		if ( empty( $timestamp ) || $timestamp <= 0 ) {
			// Compute fallback from attempt counter and configured intervals.
			// IMPORTANTE: NO aumentar aquí _aswc_retry_attempts; solo se incrementa en el flujo de fallo real.
			$attempts_key = '_aswc_retry_attempts';
			$attempts     = (int) aswc_get_meta_data( $subscription->get_id(), $attempts_key, 0 );
			$intervals    = $this->get_retry_intervals();

			if ( isset( $intervals[ $attempts ] ) ) {
				$delay     = (int) $intervals[ $attempts ];
				$timestamp = $this->calculate_timestamp_from_interval( $delay );
				aswc_update_meta_data( $subscription->get_id(), '_aswc_payment_retry', $timestamp );
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( sprintf( '[schedule_retry] Fallback timestamp computed — attempt:%d delay:%d next_ts:%d', $attempts, $delay, $timestamp ) );
				}
			} else {
				$this->unschedule_retry( $subscription, false );
				if ( class_exists( 'ASWC_Log' ) ) {
					ASWC_Log::log( '[schedule_retry] No fallback available — retries exhausted. Unscheduling.' );
				}
				return;
			}
		}

		// Always clear existing retry actions across all groups to prevent duplicates.
		$this->unschedule_retry( $subscription, false );

		$action_hook = 'advanced_scheduled_subscription_payment_retry';
		$action_args = $this->get_retry_action_args( $subscription );
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( '[schedule_retry] About to reschedule_action — hook:' . $action_hook . ' args:' . wp_json_encode( $action_args ) . ' group:' . ( null === $group ? 'default' : ( false === $group ? 'all' : (string) $group ) ) );
		}
		$this->scheduler->reschedule_action( $timestamp, $action_hook, $action_args, $group );
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( '[schedule_retry] Scheduled retry action at %d', $timestamp ) );
			$scheduled = $this->get_scheduled_retry( $subscription, $group );
			ASWC_Log::log( sprintf( '[schedule_retry] get_scheduled_retry result: %s', false === $scheduled ? 'none' : $scheduled ) );
		}
	}

		/**
		 * Schedule a manual payment for a subscription.
		 *
		 * Manual payments share the same hook as automatic payments but this
		 * helper provides a clearer semantic wrapper for integrations that need
		 * to queue manual renewals.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param int|null        $timestamp    When the payment should run. Defaults to the subscription's next payment time.
		 * @param string|null     $group        Optional action scheduler group. Uses the core group when `null` or `false`.
		 *
		 * @return int Scheduled timestamp.
		 */
	public function schedule_manual_payment( $subscription, $timestamp = null, $group = null ) {
		$timestamp = $this->resolve_timestamp( $subscription, 'next_payment', $timestamp );
		$this->schedule_payment( $subscription, $timestamp, $group );

		return $timestamp;
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
	public function schedule_retry_with_rule( $subscription, $rule, $group = null ) {
		$interval = aswc_get_retry_interval_from_rule( $rule );
		if ( $interval <= 0 ) {
			$this->unschedule_retry( $subscription, $group );
			return 0;
		}

		$timestamp = $this->calculate_timestamp_from_interval( $interval );
		$this->schedule_retry( $subscription, $timestamp, $group );

		return $timestamp;
	}

		/**
		 * Schedule a payment retry after a given interval.
		 *
		 * Convenience wrapper for integrations that provide a delay in seconds
		 * rather than a specific timestamp or retry rule object.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param int             $interval     Interval in seconds after which the retry should run.
		 * @param string|null     $group        Optional action scheduler group. Uses the core group when `null` or `false`.
		 *
		 * @return int Scheduled timestamp.
		 */
	public function schedule_retry_after( $subscription, $interval, $group = null ) {
		$interval = (int) $interval;
		if ( $interval <= 0 ) {
			$this->unschedule_retry( $subscription, $group );
			return 0;
		}

		$timestamp = $this->calculate_timestamp_from_interval( $interval );
		$this->schedule_retry( $subscription, $timestamp, $group );

		return $timestamp;
	}

		/**
		 * Retrieve the configured retry intervals.
		 *
		 * @return array List of intervals in seconds for each retry attempt.
		 */
	/**
	 * Interválicos entre reintentos (en segundos).
	 * - Si el option RETRY_INTERVALS_OPTION es un array => se usa tal cual (cast a int).
	 * - Si es un número => se rellena repitiendo el último default hasta alcanzar ese número.
	 * - Si no existe => DEFAULT_RETRY_INTERVALS.
	 *
	 * Nota: El número MÁXIMO de intentos no lo fija este método; lo fija get_max_retry_attempts().
	 * Si hay más intentos que intervalos, la llamada usará el último intervalo disponible.
	 *
	 * @return array
	 */
	public function get_retry_intervals() {
	    $raw = get_option( self::RETRY_INTERVALS_OPTION, self::DEFAULT_RETRY_INTERVALS );

	    // Base: defaults
	    $intervals = array_map( 'intval', (array) self::DEFAULT_RETRY_INTERVALS );

	    if ( is_array( $raw ) && ! empty( $raw ) ) {
	        $intervals = array_map( 'intval', $raw );
	    } elseif ( is_numeric( $raw ) ) {
	        $target_len = max( 0, (int) $raw );
	        if ( $target_len > 0 ) {
	            $last = end( $intervals );
	            while ( count( $intervals ) < $target_len ) {
	                $intervals[] = (int) $last;
	            }
	        }
	    }

	    return array_map( 'intval', (array) $intervals );
	}

		/**
		 * Schedule a payment retry based on the attempt number.
		 *
		 * Uses the interval defined for the given attempt in the
		 * `advanced_subscriptions_woocommerce_retry_intervals` option. If the
		 * attempt index is out of range, any existing retries are unscheduled.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param int             $attempt      Retry attempt index (0-based).
		 * @param string|null     $group        Optional action scheduler group.
		 *
		 * @return int Scheduled timestamp or 0 when unscheduled.
		 */
	public function schedule_retry_for_attempt( $subscription, $attempt, $group = null ) {
		$intervals = $this->get_retry_intervals();
		$attempt   = (int) $attempt;

		if ( ! isset( $intervals[ $attempt ] ) ) {
			$this->unschedule_retry( $subscription, $group );
			return 0;
		}

		return $this->schedule_retry_after( $subscription, $intervals[ $attempt ], $group );
	}

		/**
		 * Remove any scheduled payment retry for a subscription.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param string|bool     $group        Action scheduler group. Pass `false`
		 *                                      to ignore the group when clearing.
		 *
		 * @return void
		 */
	public function unschedule_retry( $subscription, $group = null ) {
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log(
				sprintf(
					'[unschedule_retry] subscription:%1$d group:%2$s',
					$subscription->get_id(),
					( null === $group ? 'default' : ( false === $group ? 'all' : $group ) )
				)
			);
		}
		$this->scheduler->unschedule_actions(
			'advanced_scheduled_subscription_payment_retry',
			$this->get_retry_action_args( $subscription ),
			$group
		);
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( '[unschedule_retry] completed.' );
		}
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
		public function get_scheduled_payment( $subscription, $group = null ) {
				$timestamp = $this->scheduler->next_scheduled_action(
						'advanced_scheduled_subscription_payment',
						$this->scheduler->get_action_args( 'next_payment', $subscription ),
						$group
				);

				if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log(
								sprintf(
										'[get_scheduled_payment] subscription:%1$d group:%2$s result:%3$s',
										$subscription->get_id(),
										( null === $group ? 'default' : ( false === $group ? 'all' : $group ) ),
										false === $timestamp ? 'none' : $timestamp
								)
						);
				}

				return $timestamp;
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
	public function get_scheduled_retry( $subscription, $group = null ) {
		$timestamp = $this->scheduler->next_scheduled_action(
			'advanced_scheduled_subscription_payment_retry',
			$this->get_retry_action_args( $subscription ),
			$group
		);

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log(
				sprintf(
					'[get_scheduled_retry] subscription:%1$d group:%2$s result:%3$s',
					$subscription->get_id(),
					( null === $group ? 'default' : ( false === $group ? 'all' : $group ) ),
					false === $timestamp ? 'none' : $timestamp
				)
			);
		}

		return $timestamp;
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
	public function get_scheduled_payment_action( $subscription, $group = null ) {
		return $this->scheduler->get_scheduled_action(
			'advanced_scheduled_subscription_payment',
			$this->scheduler->get_action_args( 'next_payment', $subscription ),
			$group
		);
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
	public function get_scheduled_retry_action( $subscription, $group = null ) {
		return $this->scheduler->get_scheduled_action(
			'advanced_scheduled_subscription_payment_retry',
			$this->get_retry_action_args( $subscription ),
			$group
		);
	}

		/**
		 * Get scheduled payment and retry action objects for a subscription.
		 *
		 * Returns a map of date type => ActionScheduler_Action so callers can
		 * inspect both renewal payments and payment retries in a single call.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param string|bool     $group        Action scheduler group. Pass `false`
		 *                                      to search across all groups.
		 *
		 * @return array Map of date type => ActionScheduler_Action.
		 */
	public function get_scheduled_payment_actions( $subscription, $group = null ) {
		$actions = array();

		$payment_action = $this->get_scheduled_payment_action( $subscription, $group );
		if ( $payment_action ) {
			$actions['next_payment'] = $payment_action;
		}

		$retry_action = $this->get_scheduled_retry_action( $subscription, $group );
		if ( $retry_action ) {
			$actions['payment_retry'] = $retry_action;
		}

		return $actions;
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
	public function get_scheduled_retry_actions( $subscription, $group = null ) {
		return $this->scheduler->get_scheduled_actions(
			'advanced_scheduled_subscription_payment_retry',
			$this->get_retry_action_args( $subscription ),
			$group
		);
	}

	/**
	 * Get the timestamp for the most recently scheduled payment.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Action scheduler group. Pass false to search across all groups.
	 *
	 * @return int|false Timestamp or false if no action exists.
	 */
	public function last_scheduled_payment( $subscription, $group = null ) {
		return $this->scheduler->last_scheduled_action(
			'advanced_scheduled_subscription_payment',
			$this->scheduler->get_action_args( 'next_payment', $subscription ),
			$group
		);
	}

	/**
	 * Get the timestamp for the most recently scheduled payment retry.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Action scheduler group. Pass false to search across all groups.
	 *
	 * @return int|false Timestamp or false if no action exists.
	 */
	public function last_scheduled_retry( $subscription, $group = null ) {
		return $this->scheduler->last_scheduled_action(
			'advanced_scheduled_subscription_payment_retry',
			$this->get_retry_action_args( $subscription ),
			$group
		);
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
	public function get_last_scheduled_payment_action( $subscription, $group = null ) {
		return $this->scheduler->get_last_scheduled_action(
			'advanced_scheduled_subscription_payment',
			$this->scheduler->get_action_args( 'next_payment', $subscription ),
			$group
		);
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
	public function get_last_scheduled_retry_action( $subscription, $group = null ) {
		return $this->scheduler->get_last_scheduled_action(
			'advanced_scheduled_subscription_payment_retry',
			$this->get_retry_action_args( $subscription ),
			$group
		);
	}

		/**
		 * Get the most recently scheduled payment and retry action objects.
		 *
		 * Returns a map of date type => ActionScheduler_Action for the last time
		 * each payment-related action was queued.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param string|bool     $group        Action scheduler group. Pass `false`
		 *                                      to search across all groups.
		 *
		 * @return array Map of date type => ActionScheduler_Action.
		 */
	public function get_last_scheduled_payment_actions( $subscription, $group = null ) {
		$actions = array();

		$payment_action = $this->get_last_scheduled_payment_action( $subscription, $group );
		if ( $payment_action ) {
			$actions['next_payment'] = $payment_action;
		}

		$retry_action = $this->get_last_scheduled_retry_action( $subscription, $group );
		if ( $retry_action ) {
			$actions['payment_retry'] = $retry_action;
		}

		return $actions;
	}

		/**
		 * Determine if a payment action is scheduled for a subscription.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param string|bool     $group        Action scheduler group. Pass `false`
		 *                                      to search across all groups.
		 *
		 * @return bool
		 */
	public function has_scheduled_payment( $subscription, $group = null ) {
		return $this->scheduler->has_scheduled_action(
			'advanced_scheduled_subscription_payment',
			$this->scheduler->get_action_args( 'next_payment', $subscription ),
			$group
		);
	}

		/**
		 * Determine if a payment retry action is scheduled for a subscription.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param string|bool     $group        Action scheduler group. Pass `false`
		 *                                      to search across all groups.
		 *
		 * @return bool
		 */
	public function has_scheduled_retry( $subscription, $group = null ) {
		return $this->scheduler->has_scheduled_action(
			'advanced_scheduled_subscription_payment_retry',
			$this->get_retry_action_args( $subscription ),
			$group
		);
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
	public function has_scheduled_payments( $subscription, $group = null ) {
		return $this->has_scheduled_payment( $subscription, $group ) || $this->has_scheduled_retry( $subscription, $group );
	}

		/**
		 * Get all scheduled payment-related events for a subscription.
		 *
		 * Returns a map of date types to their scheduled timestamps so callers
		 * can inspect both renewal payments and payment retries with a single
		 * method call.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param string|bool     $group        Action scheduler group. Pass `false`
		 *                                      to search across all groups.
		 *
		 * @return array Map of date type => timestamp for scheduled events.
		 */
	public function get_scheduled_payments( $subscription, $group = null ) {
		$scheduled = array();

		$next_payment = $this->get_scheduled_payment( $subscription, $group );
		if ( $next_payment ) {
			$scheduled['next_payment'] = $next_payment;
		}

		$retry = $this->get_scheduled_retry( $subscription, $group );
		if ( $retry ) {
			$scheduled['payment_retry'] = $retry;
		}

		return $scheduled;
	}

		/**
		 * Get the most recent scheduled payment-related events for a subscription.
		 *
		 * Returns a map of date types to their latest scheduled timestamps so
		 * callers can inspect renewal payments and payment retries in a single
		 * method call.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param string|bool     $group        Action scheduler group. Pass `false`
		 *                                      to search across all groups.
		 *
		 * @return array Map of date type => timestamp for scheduled events.
		 */
	public function get_last_scheduled_payments( $subscription, $group = null ) {
		$scheduled = array();

		$next_payment = $this->last_scheduled_payment( $subscription, $group );
		if ( $next_payment ) {
			$scheduled['next_payment'] = $next_payment;
		}

		$retry = $this->last_scheduled_retry( $subscription, $group );
		if ( $retry ) {
			$scheduled['payment_retry'] = $retry;
		}

		return $scheduled;
	}

		/**
		 * Schedule all payment-related events for a subscription.
		 *
		 * Convenience wrapper that schedules the next payment and any pending
		 * payment retry in a single call.
		 *
		 * @param WC_Subscription $subscription Subscription instance.
		 * @param string|bool     $group        Action scheduler group. Pass `false`
		 *                                      to search across all groups.
		 *
		 * @return void
		 */
	public function schedule_all( $subscription, $group = null ) {
		$this->schedule_payment( $subscription, null, $group );
		$this->schedule_retry( $subscription, null, $group );
	}

	/**
	 * Unschedule all payment-related actions for a subscription.
	 *
	 * Clears both renewal payments and payment retries in a single call.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Action scheduler group. Pass `false`
	 *                                      to ignore the group when clearing.
	 *
	 * @return void
	 */
	public function unschedule_all( $subscription, $group = null ) {
		$this->unschedule_payment( $subscription, $group );
		$this->unschedule_retry( $subscription, $group );
	}
	/**
	 * Schedule a retry when a renewal order transitions to `failed`.
	 *
	 * @param int $order_id The failed order ID.
	 * @return void
	 */
	public function handle_failed_renewal_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || 'shop_order' !== $order->get_type() ) {
			return;
		}

		$is_renewal = 'yes' === aswc_get_meta_data( $order_id, '_aswc_renewal_order', null );
		if ( ! $is_renewal ) {
			return;
		}

		$subscription_id = (int) aswc_get_meta_data( $order_id, '_aswc_subscription', null );
		if ( ! $subscription_id ) {
			return;
		}

		$subscription = ASWC_Scheduler_API::get_subscription( $subscription_id );
		if ( ! $subscription ) {
			return;
		}

		// Persist last renewal order id for action args.
		aswc_update_meta_data( $subscription_id, '_aswc_last_renewal_order_id', $order_id );

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( '[handle_failed_renewal_order] Scheduling retry due to order %d failed.', $order_id ) );
		}

		$this->schedule_retry( $subscription, null );
	}
	/**
	 * Catch-all: when any order changes status to failed, schedule a retry if it's a renewal.
	 *
	 * @param int    $order_id   Order ID.
	 * @param string $old_status Old status (without wc- prefix).
	 * @param string $new_status New status (without wc- prefix).
	 * @param WC_Order $order    Order object.
	 * @return void
	 */
	public function maybe_schedule_retry_on_failed_change( $order_id, $old_status, $new_status, $order ) {
		if ( 'failed' !== $new_status ) {
			return;
		}

		if ( ! $order || 'shop_order' !== $order->get_type() ) {
			return;
		}

		$is_renewal = 'yes' === aswc_get_meta_data( $order_id, '_aswc_renewal_order', null );
		if ( ! $is_renewal ) {
			return;
		}

		$subscription_id = (int) aswc_get_meta_data( $order_id, '_aswc_subscription', null );
		if ( ! $subscription_id ) {
			return;
		}

		$subscription = ASWC_Scheduler_API::get_subscription( $subscription_id );
		if ( ! $subscription ) {
			return;
		}

		aswc_update_meta_data( $subscription_id, '_aswc_last_renewal_order_id', $order_id );

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( '[maybe_schedule_retry_on_failed_change] Detected status change to failed on order %d — scheduling retry.', $order_id ) );
		}

		$this->schedule_retry( $subscription, null );

		if ( class_exists( 'ASWC_Log' ) ) {
			$scheduled_retry = $this->get_scheduled_retry( $subscription, null );
			ASWC_Log::log( sprintf( '[maybe_schedule_retry_on_failed_change] get_scheduled_retry after schedule: %s', false === $scheduled_retry ? 'none' : $scheduled_retry ) );
		}
	}
	/**
	 * Build action args for retry scheduling consistently.
	 *
	 * For retries we pass both the last failed renewal order id (for legacy handlers)
	 * and the subscription id (for new handlers), in that order.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @return array Arguments array to store on the scheduled action.
	 */
	protected function get_retry_action_args( $subscription ) 
	{
		$sub_id = $subscription->get_id();

		// Try to include the last failed renewal order id as first positional arg
		// for backward compatibility with legacy handlers that expect $order_id.
		$order_id = (int) aswc_get_meta_data( $sub_id, '_aswc_last_renewal_order_id', 0 );

		if ( ! $order_id ) {
			// Attempt to resolve from the subscription object.
			$last_order = false;
			if ( method_exists( $subscription, 'get_last_order' ) ) {
				$last_order = $subscription->get_last_order( 'all', 'renewal' );
			}
			if ( $last_order instanceof WC_Order ) {
				$order_id = (int) $last_order->get_id();
			} else {
				// Fallback query.
				$query = function_exists( 'wc_get_orders' ) ? wc_get_orders(
					array(
						'subscription_renewal' => $sub_id,
						'orderby'              => 'date',
						'order'                => 'DESC',
						'limit'                => 1,
					)
				) : array();

				if ( ! empty( $query ) && isset( $query[0] ) && $query[0] instanceof WC_Order ) {
					$order_id = (int) $query[0]->get_id();
				}
			}

			// Persist for later runs if we found it.
			if ( $order_id ) {
				aswc_update_meta_data( $sub_id, '_aswc_last_renewal_order_id', $order_id );
			}
		}

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log(
				sprintf(
					'[get_retry_action_args] Back-compat args prepared — order_id:%1$d subscription_id:%2$d',
					(int) $order_id,
					$sub_id
				)
			);
		}

		// Use an associative array for clarity. Action Scheduler will pass arguments
		// in order, but setting explicit keys makes logs and stored payload self-descriptive.
		// Our handler tolerates receiving just the first value (order_id) thanks to
		// its resolution logic at the top of gateway_scheduled_subscription_payment().
		$args = array(
			'order_id'        => (int) $order_id,
			'subscription_id' => (int) $sub_id,
			'is_retry'        => true,
		);
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( '[get_retry_action_args] Prepared args: ' . wp_json_encode( $args ) );
		}
		return $args;
	}
	/**
	 * Reset retry metadata and unschedule pending retries for a subscription.
	 *
	 * @param WC_Subscription $subscription
	 * @return void
	 */
	public function reset_retries( $subscription ) {
	    aswc_update_meta_data( $subscription->get_id(), '_aswc_payment_retry', 0 );
	    aswc_update_meta_data( $subscription->get_id(), '_aswc_retry_attempts', 0 );
	    $this->unschedule_retry( $subscription, false );
	    if ( class_exists( 'ASWC_Log' ) ) {
	        ASWC_Log::log( '[reset_retries] Cleared retry metadata and unscheduled retries.' );
	    }
	}
	/**
	 * Máximo de reintentos permitidos antes de cancelar.
	 * Prioridad:
	 * 1) Meta por suscripción: _aswc_retry_max_attempts (si existe y > 0)
	 * 2) Ajuste global: aswc_after_no_failed_attempt_cancel (fallback 5)
	 *
	 * @param WC_Subscription $subscription
	 * @return int
	 */
	public function get_max_retry_attempts( $subscription ) {
	    // 1) Override por suscripción (opcional)
	    $by_meta = (int) aswc_get_meta_data( $subscription->get_id(), '_aswc_retry_max_attempts', 0 );
	    if ( $by_meta > 0 ) {
	        return $by_meta;
	    }

	    // 2) Ajuste global (única fuente global)
	    $by_setting = (int) get_option( 'aswc_after_no_failed_attempt_cancel', 5 );

	    // Evitar valores 0 o negativos por configuración incompleta
	    return max( 1, $by_setting );
	}
}
