<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Manage subscription preapprovals.
 * 
 * @class SUMOSubs_Preapproval
 */
class SUMOSubs_Preapproval {

	public static $payment_type   = '';
	public static $payment_method = '';

	/**
	 * Init SUMOSubs_Preapproval.
	 */
	public static function init() {
		add_action( 'sumosubscriptions_preapproved_payment_transaction_success', __CLASS__ . '::payment_success' );
		add_action( 'sumosubscriptions_preapproved_payment_transaction_failed', __CLASS__ . '::payment_failed' );
		add_action( 'sumosubscriptions_preapproved_access_is_revoked', __CLASS__ . '::preapproved_access_revoked' );
		add_action( 'sumosubscriptions_subscription_cancelled', __CLASS__ . '::revoke_payment_data', 9999 );
		add_action( 'sumosubscriptions_subscription_expired', __CLASS__ . '::revoke_payment_data', 9999 );
	}

	/**
	 * Check whether it is valid to charge the payment automatically
	 *
	 * @param int $subscription_id
	 * @param object $payment_order
	 * @return bool true upon preapproval status is valid
	 */
	public static function is_valid( $subscription_id, $payment_order ) {
		self::$payment_type   = sumo_get_payment_type( $subscription_id );
		self::$payment_method = sumo_get_subscription_payment( $subscription_id, 'payment_method' );

		/**
		 * Is payment method valid to pay renewals automatically?
		 * 
		 * @since 1.0
		 */
		if ( 'auto' === self::$payment_type && apply_filters( 'sumosubscriptions_is_' . self::$payment_method . '_preapproval_status_valid', false, $subscription_id, $payment_order ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether auto payment transaction is success
	 *
	 * @param int $subscription_id
	 * @param object $payment_order
	 * @return bool true upon recurring payment success
	 */
	public static function is_payment_txn_success( $subscription_id, $payment_order ) {
		$is_txn_success = false;

		if ( 'auto' === self::$payment_type ) {
			if ( $payment_order->get_total() > 0 ) {
				/**
				 * Is payment method captured the renewal payment automatically?
				 * 
				 * @since 1.0
				 */
				if ( apply_filters( 'sumosubscriptions_is_' . self::$payment_method . '_preapproved_payment_transaction_success', false, $subscription_id, $payment_order ) ) {
					$is_txn_success = true;
				}
			} else {
				$is_txn_success = true;
			}
		}

		return $is_txn_success;
	}

	/**
	 * Do some action when preapproved payment success.
	 *
	 * @param array $args
	 */
	public static function payment_success( $args ) {
		$renewal_order = wc_get_order( $args[ 'renewal_order_id' ] );
		if ( ! $renewal_order ) {
			return;
		}

		if ( sumosubs_is_order_paid( $renewal_order ) ) {
			return;
		}

		//Update new Order status to Renew the Subscription.
		$renewal_order->payment_complete();
	}

	/**
	 * Do some action when preapproved payment failed.
	 *
	 * @param array $args
	 */
	public static function payment_failed( $args ) {
		$subscription_status = get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true );

		/**
		 * Get the subscription next eligible failed statuses.
		 * 
		 * @since 1.0
		 */
		switch ( apply_filters( 'sumosubscriptions_get_next_eligible_subscription_failed_status', $args[ 'next_eligible_status' ], $args[ 'subscription_id' ] ) ) {
			case 'Pending_Authorization':
				if ( in_array( $subscription_status, array( 'Trial', 'Active' ) ) ) {
					$payment_method = sumo_get_subscription_payment_method( $args[ 'subscription_id' ] );

					/**
					 * Get the pending auth period.
					 * 
					 * @since 1.0
					 */
					$payment_charging_days = apply_filters( "sumosubscriptions_{$payment_method}_pending_auth_period", 1, $args[ 'subscription_id' ] );

					SUMOSubs_Background_Process::set_pending_authorization( array(
						'subscription_id'       => $args[ 'subscription_id' ],
						'renewal_order_id'      => $args[ 'renewal_order_id' ],
						'payment_charging_days' => absint( $payment_charging_days ),
					) );
				}
				break;
			case 'Overdue':
				if ( in_array( $subscription_status, array( 'Trial', 'Active' ) ) ) {
					SUMOSubs_Background_Process::set_overdue( array(
						'subscription_id'             => $args[ 'subscription_id' ],
						'renewal_order_id'            => $args[ 'renewal_order_id' ],
						'payment_charging_days'       => 0 === $args[ 'payment_charging_days' ] ? sumosubs_get_overdue_days() : $args[ 'payment_charging_days' ],
						'payment_retry_times_per_day' => 0 === $args[ 'payment_retry_times_per_day' ] ? sumosubs_get_payment_retry_times_per_day_in( 'Overdue' ) : $args[ 'payment_retry_times_per_day' ],
					) );
				}
				break;
			case 'Suspended':
				if ( in_array( $subscription_status, array( 'Trial', 'Active', 'Overdue', 'Pending_Authorization' ) ) ) {
					SUMOSubs_Background_Process::set_suspend( array(
						'subscription_id'             => $args[ 'subscription_id' ],
						'renewal_order_id'            => $args[ 'renewal_order_id' ],
						'payment_charging_days'       => 0 === $args[ 'payment_charging_days' ] ? sumosubs_get_suspend_days() : $args[ 'payment_charging_days' ],
						'payment_retry_times_per_day' => 0 === $args[ 'payment_retry_times_per_day' ] ? sumosubs_get_payment_retry_times_per_day_in( 'Suspended' ) : $args[ 'payment_retry_times_per_day' ],
					) );
				}
				break;
			case 'Cancelled':
				if ( in_array( $subscription_status, array( 'Trial', 'Active', 'Overdue', 'Suspended', 'Pending_Authorization' ) ) ) {
					SUMOSubs_Background_Process::set_cancel( array(
						'subscription_id'  => $args[ 'subscription_id' ],
						'renewal_order_id' => $args[ 'renewal_order_id' ],
					) );
				}
				break;
		}
	}

	/**
	 * Do some action after preapproved access is revoked.
	 *
	 * @param array $args
	 */
	public static function preapproved_access_revoked( $args ) {
		$renewal_order = wc_get_order( $args[ 'renewal_order_id' ] );
		if ( ! $renewal_order ) {
			return;
		}

		if ( sumosubs_is_order_paid( $renewal_order ) ) {
			return;
		}

		//Set as Manual pay mode.
		sumo_save_subscription_payment_info( $renewal_order, array(
			'payment_type'   => 'manual',
			'payment_method' => $renewal_order->get_payment_method(),
		) );

		sumo_add_subscription_note( __( 'Subscription switched to manual payment mode since preapproval access has been revoked.', 'sumosubscriptions' ), $args[ 'subscription_id' ], 'success', __( 'Preapproval access revoked', 'sumosubscriptions' ) );

		$subscription_status  = get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true );
		$next_eligible_status = sumosubs_get_next_eligible_subscription_failed_status( $args[ 'subscription_id' ] );
		$next_due_on          = get_post_meta( $args[ 'subscription_id' ], 'sumo_get_next_payment_date', true );
		$upcoming_mail_info   = get_post_meta( $args[ 'subscription_id' ], 'sumo_get_args_for_pay_link_templates', true );
		$upcoming_mail_info   = wp_parse_args( is_array( $upcoming_mail_info ) ? $upcoming_mail_info : array(), array(
			'next_status'       => '',
			'scheduled_duedate' => '',
				) );

		switch ( $subscription_status ) {
			case 'Trial':
			case 'Active':
			case 'Pending':
			case 'Pending_Authorization':
				//may be useful for display purpose in Subscription Email Templates
				if ( ! in_array( $next_eligible_status, $upcoming_mail_info ) ) {
					update_post_meta( $args[ 'subscription_id' ], 'sumo_get_args_for_pay_link_templates', array(
						'next_status'       => $next_eligible_status,
						'scheduled_duedate' => $next_due_on,
					) );
				}

				switch ( $next_eligible_status ) {
					case 'Overdue':
						SUMOSubs_Background_Process::set_overdue( $args );
						break;
					case 'Suspended':
						SUMOSubs_Background_Process::set_suspend( $args );
						break;
					case 'Cancelled':
						SUMOSubs_Background_Process::set_cancel( $args );
						break;
				}
				break;
			case 'Overdue':
				//may be useful for display purpose in Subscription Email Templates
				if ( ! in_array( $next_eligible_status, $upcoming_mail_info ) ) {
					update_post_meta( $args[ 'subscription_id' ], 'sumo_get_args_for_pay_link_templates', array(
						'next_status'       => $next_eligible_status,
						'scheduled_duedate' => sumo_get_subscription_date( sumo_get_subscription_timestamp() + ( $args[ 'payment_charging_days' ] * 86400 ) ),
					) );
				}

				switch ( $next_eligible_status ) {
					case 'Suspended':
						SUMOSubs_Background_Process::set_suspend( $args );
						break;
					case 'Cancelled':
						SUMOSubs_Background_Process::set_cancel( $args );
						break;
				}
				break;
			case 'Suspended':
				//may be useful for display purpose in Subscription Email Templates
				if ( ! in_array( $next_eligible_status, $upcoming_mail_info ) ) {
					update_post_meta( $args[ 'subscription_id' ], 'sumo_get_args_for_pay_link_templates', array(
						'next_status'       => $next_eligible_status,
						'scheduled_duedate' => sumo_get_subscription_date( sumo_get_subscription_timestamp() + ( $args[ 'payment_charging_days' ] * 86400 ) ),
					) );
				}

				switch ( $next_eligible_status ) {
					case 'Cancelled':
						SUMOSubs_Background_Process::set_cancel( $args );
						break;
				}
				break;
		}
	}

	/**
	 * Revoke Payment Data upon Subscription gets Cancelled/Expired.
	 *
	 * @param int $subscription_id The Subscription post ID
	 */
	public static function revoke_payment_data( $subscription_id ) {
		$subscription_plan = sumo_get_subscription_plan( $subscription_id );
		$order_id          = get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true );

		sumo_save_subscription_payment_info( $order_id, array(), $subscription_plan[ 'subscription_product_id' ] );
	}
}

SUMOSubs_Preapproval::init();

/**
 * For Backward Compatibility.
 */
class SUMO_Subscription_Preapproval extends SUMOSubs_Preapproval {
	
}
