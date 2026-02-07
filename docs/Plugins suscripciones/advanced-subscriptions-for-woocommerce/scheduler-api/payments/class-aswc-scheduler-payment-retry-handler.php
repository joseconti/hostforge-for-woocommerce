<?php
/**
 * Scheduler payment retry handler.
 *
 * @package Advanced_Subscriptions_For_WooCommerce
 */

/**
 * Handle scheduled subscription payment retries.
 */
class ASWC_Scheduler_Payment_Retry_Handler {
	/**
	 * Register callback for payment retry actions.
	 *
	 * @return void
	 */
	public static function init() {
				add_action( 'advanced_scheduled_subscription_payment_retry', array( __CLASS__, 'process_payment_retry' ), 10, 1 );
	}

	/**
	 * Process a scheduled payment retry.
	 *
	 * @param int $order_id Order ID for the failed renewal payment.
	 *
	 * @return void
	 */
	public static function process_payment_retry( $order_id ) {
		$order_id = absint( $order_id );

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( '[process_payment_retry] order_id:' . $order_id );
		}

		if ( 0 !== $order_id && function_exists( 'wc_get_order' ) ) {
						$order = wc_get_order( $order_id );

			if ( $order ) {
										do_action( 'aswc_scheduled_subscription_payment_retry', $order_id );

				if ( $order->needs_payment() && class_exists( 'ASWC_Scheduler_API' ) ) {
							ASWC_Scheduler_API::payments()->trigger_gateway_renewal_payment_hook( $order );
				}
			}
		}

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( '[process_payment_retry] exit order_id:' . $order_id );
		}
	}
}

ASWC_Scheduler_Payment_Retry_Handler::init();
