<?php // phpcs:ignoreFile
/**
 * Manual renewal helpers.
 *
 * @package Advanced_Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extend next payment date when a manual renewal is processed.
 *
 * @param int    $order_id   Order ID.
 * @param string $old_status Previous order status.
 * @param string $new_status New order status.
 * @return void
 */
function aswc_extend_next_payment_date_on_manual_renewal( $order_id, $old_status, $new_status ) {
		$logger  = wc_get_logger();
		$context = array( 'source' => 'aswc-renewal' );
		$logger->info( sprintf( 'Manual renewal triggered for order %d', $order_id ), $context );

		$manual_flag = aswc_get_meta_data( $order_id, 'aswc_manual_renewal_order', true );
	if ( 'success' !== $manual_flag ) {
			$logger->info( 'Order not marked as manual renewal.', $context );
			return;
	}

	if ( in_array( $old_status, array( 'completed', 'processing' ), true ) ) {
			$logger->info( 'Old status already completed or processing.', $context );
			return;
	}

	if ( 'completed' !== $new_status && 'processing' !== $new_status ) {
			$logger->info( sprintf( 'New status %s is not valid for renewal update.', $new_status ), $context );
			return;
	}

		$subscription_id = aswc_get_meta_data( $order_id, 'aswc_subscription', true );
	if ( ! aswc_check_valid_subscription( $subscription_id ) ) {
			$logger->info( 'Invalid subscription ID.', $context );
			return;
	}

	$current_time          = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
	$trial_end             = (int) aswc_get_meta_data( $subscription_id, 'aswc_susbcription_trial_end', true );
	$existing_next_payment = (int) aswc_get_meta_data( $subscription_id, 'aswc_next_payment_date', true );

	// Calculate base time for next payment.
	// Priority: use existing next payment if in the future, otherwise use current time.
	// This prevents accumulating delays when scheduled tasks run late.
	if ( $existing_next_payment > 0 && $existing_next_payment > $current_time ) {
		// Future payment date exists - use it as base.
		$base_time = $existing_next_payment;
	} elseif ( $trial_end > 0 && $trial_end > $current_time ) {
		// Trial hasn't ended yet - use trial end as base.
		$base_time = $trial_end;
	} else {
		// No valid future date - use current time (catch-up scenario).
		$base_time = $current_time;
		if ( $existing_next_payment > 0 && $existing_next_payment < $current_time ) {
			$logger->info( sprintf( 'Detected overdue payment (was: %d, now: %d). Catching up.', $existing_next_payment, $current_time ), $context );
		}
	}

	$logger->info( sprintf( 'Base time calculated: %d (current: %d, existing_next: %d, trial_end: %d)', $base_time, $current_time, $existing_next_payment, $trial_end ), $context );

                $next_payment = aswc_next_payment_date( $subscription_id, $base_time, 0 );
                $logger->info( sprintf( 'Next payment date set to %d', $next_payment ), $context );
                aswc_update_order_meta( $subscription_id, 'aswc_next_payment_date', $next_payment );
                $saved_next = aswc_get_meta_data( $subscription_id, 'aswc_next_payment_date', true );
                $logger->info( sprintf( 'Stored next payment meta: %d', $saved_next ), $context );

		$existing_expiry = (int) aswc_get_meta_data( $subscription_id, 'aswc_susbcription_end', true );
		$expiry_base     = $existing_expiry > $base_time ? $existing_expiry : $base_time;
		$expiry_date     = aswc_susbcription_expiry_date( $subscription_id, $expiry_base, 0 );
                $logger->info( sprintf( 'Expiry date set to %d', $expiry_date ), $context );
                aswc_update_order_meta( $subscription_id, 'aswc_susbcription_end', $expiry_date );
                $saved_expiry = aswc_get_meta_data( $subscription_id, 'aswc_susbcription_end', true );
                $logger->info( sprintf( 'Stored expiry meta: %d', $saved_expiry ), $context );

        $subscription = aswc_get_subscription( $subscription_id );
        if ( $subscription ) {
                        $logger->info( 'Scheduling next payment.', $context );
                        ASWC_Scheduler_API::schedule_payment( $subscription );
                       $logger->info( 'Scheduling customer notifications.', $context );
                       ASWC_Scheduler_API::schedule_all_notifications( $subscription );
        }
}
add_action( 'woocommerce_order_status_changed', 'aswc_extend_next_payment_date_on_manual_renewal', 100, 3 );

/**
 * Extend next payment date after a renewal payment completes.
 *
 * Ensures subscriptions processed through the scheduler update their
 * `aswc_next_payment_date` meta so future renewals are correctly scheduled.
 *
 * @param WC_Subscription $subscription  Subscription instance.
 * @param WC_Order        $_renewal_order Renewal order related to the payment.
 * @return void
 */
function aswc_extend_next_payment_date_on_renewal_payment( $subscription, $_renewal_order ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$logger  = wc_get_logger();
		$context = array( 'source' => 'aswc-renewal' );
		$logger->info( sprintf( 'Renewal payment completed for subscription %d', $subscription->get_id() ), $context );

	if ( ! aswc_is_subscription( $subscription ) ) {
			$logger->info( 'Object is not a subscription.', $context );
			return;
	}

		$subscription_id = $subscription->get_id();
	if ( ! aswc_check_valid_subscription( $subscription_id ) ) {
			$logger->info( 'Invalid subscription ID.', $context );
			return;
	}

				$current_time          = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
				$trial_end             = (int) aswc_get_meta_data( $subscription_id, 'aswc_susbcription_trial_end', true );
				$existing_next_payment = (int) aswc_get_meta_data( $subscription_id, 'aswc_next_payment_date', true );

	// Calculate base time for next payment.
	// Priority: use existing next payment if in the future, otherwise use current time.
	// This prevents accumulating delays when scheduled tasks run late.
	if ( $existing_next_payment > 0 && $existing_next_payment > $current_time ) {
		// Future payment date exists - use it as base.
		$base_time = $existing_next_payment;
	} elseif ( $trial_end > 0 && $trial_end > $current_time ) {
		// Trial hasn't ended yet - use trial end as base.
		$base_time = $trial_end;
	} else {
		// No valid future date - use current time (catch-up scenario).
		$base_time = $current_time;
		if ( $existing_next_payment > 0 && $existing_next_payment < $current_time ) {
			$logger->info( sprintf( 'Detected overdue payment (was: %d, now: %d). Catching up.', $existing_next_payment, $current_time ), $context );
		}
	}

				$logger->info( sprintf( 'Base time calculated: %d (current: %d, existing_next: %d, trial_end: %d)', $base_time, $current_time, $existing_next_payment, $trial_end ), $context );

                $next_payment = aswc_next_payment_date( $subscription_id, $base_time, 0 );
                $logger->info( sprintf( 'Next payment date set to %d', $next_payment ), $context );
                aswc_update_order_meta( $subscription_id, 'aswc_next_payment_date', $next_payment );
                $saved_next = aswc_get_meta_data( $subscription_id, 'aswc_next_payment_date', true );
                $logger->info( sprintf( 'Stored next payment meta: %d', $saved_next ), $context );

                $existing_expiry = (int) aswc_get_meta_data( $subscription_id, 'aswc_susbcription_end', true );
                $expiry_base     = $existing_expiry > $base_time ? $existing_expiry : $base_time;
                $expiry_date     = aswc_susbcription_expiry_date( $subscription_id, $expiry_base, 0 );
                $logger->info( sprintf( 'Expiry date set to %d', $expiry_date ), $context );
                aswc_update_order_meta( $subscription_id, 'aswc_susbcription_end', $expiry_date );
                $saved_expiry = aswc_get_meta_data( $subscription_id, 'aswc_susbcription_end', true );
                $logger->info( sprintf( 'Stored expiry meta: %d', $saved_expiry ), $context );

                aswc_update_order_meta( $subscription_id, 'aswc_subscription_status', 'active' );
                // Note: Removed $subscription->update_status() call to prevent status inconsistencies.
                // The meta field is the single source of truth, updated via aswc_update_order_meta above.
                $logger->info( 'Subscription status set to active.', $context );

                $logger->info( 'Scheduling next payment.', $context );
                ASWC_Scheduler_API::schedule_payment( $subscription );
               $logger->info( 'Scheduling customer notifications.', $context );
               ASWC_Scheduler_API::schedule_all_notifications( $subscription );
}
add_action( 'woocommerce_subscription_renewal_payment_complete', 'aswc_extend_next_payment_date_on_renewal_payment', 100, 2 );
