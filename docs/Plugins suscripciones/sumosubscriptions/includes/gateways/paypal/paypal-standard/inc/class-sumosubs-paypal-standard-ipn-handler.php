<?php

/**
 * PayPal Standard IPN request handler.
 * 
 * @class SUMOSubs_PayPal_Standard_IPN_Handler
 */
class SUMOSubs_PayPal_Standard_IPN_Handler extends SUMOSubs_PayPal_Standard_API {

	/**
	 * Setup Subscription API.
	 *
	 * @param type $paypal_args
	 * @return type
	 */
	public function alter_payal_request( $paypal_args ) {
		$cmd_args          = $this->set_up_future_payments( $paypal_args );
		$trial_args        = $this->set_up_trial_parameters( $cmd_args );
		$subscription_args = $this->set_up_subscription_parameters( $trial_args );
		$final_args        = $this->set_up_recurring_parameters( $subscription_args );
		return $final_args;
	}

	/**
	 * Complete the renewal payment.
	 *
	 * @return int
	 */
	public function complete_payment() {
		$parent_order_id  = get_post_meta( $this->subscription_id, 'sumo_get_parent_order_id', true );
		$renewal_order_id = absint( get_post_meta( $this->subscription_id, 'sumo_get_renewal_id', true ) );

		//BKWD CMPT
		$unpaid_renewal_order_exists = sumosubs_unpaid_renewal_order_exists( $this->subscription_id );
		if ( ! $unpaid_renewal_order_exists ) {
			$renewal_order_id = SUMOSubs_Order::create_renewal_order( $parent_order_id, $this->subscription_id );
		}

		$renewal_order = wc_get_order( $renewal_order_id );
		if ( $renewal_order ) {
			$renewal_order->payment_complete();
			$this->add_note( __( 'Subscription Automatic Renewal Successful based upon IPN received from PayPal', 'sumosubscriptions' ), __( 'Subscription Renewal Success', 'sumosubscriptions' ) );
		}

		return $renewal_order_id;
	}

	/**
	 * Retrieve IPN request and manage Subscription.
	 *
	 * @param array $posted
	 */
	public function manage_subscription_via_ipn( $posted ) {
		switch ( $posted[ 'txn_type' ] ) {
			case 'subscr_signup':
			case 'subscr_modify':
				sumo_save_subscription_payment_info( $this->payment_order, array(
					'payment_type'   => 'auto',
					'payment_method' => $this->gateway_id,
					'profile_id'     => $posted[ 'subscr_id' ],
				) );

				$this->payment_order->payment_complete(); // Create New Subscription
				$this->populate( $this->payment_order_id ); // Populate new subscription created
				$this->log_response( $posted );
				$this->add_note( __( 'Subscription has been created successful based upon IPN received from PayPal.', 'sumosubscriptions' ), __( 'Subscription Created', 'sumosubscriptions' ) );
				break;
			case 'subscr_cancel':
				$this->log_response( $posted );

				if ( 'yes' === get_post_meta( $this->subscription_id, '_paypal_subscription_revoke_pending', true ) ) {
					delete_post_meta( $this->subscription_id, '_paypal_subscription_revoke_pending' );
					$this->add_note( __( 'Subscription has been Revoked based upon IPN received from PayPal', 'sumosubscriptions' ), __( 'Subscription Revoked', 'sumosubscriptions' ) );
				} else {
					update_post_meta( $this->subscription_id, 'sumo_get_status', 'Cancelled' );
					update_post_meta( $this->subscription_id, 'sumo_get_sub_end_date', sumo_get_subscription_date() );
					$this->add_note( __( 'Subscription has been Cancelled based upon IPN received from PayPal', 'sumosubscriptions' ), __( 'Subscription Cancelled', 'sumosubscriptions' ) );
				}
				break;
			case 'recurring_payment_suspended':
				$this->log_response( $posted );

				if ( 'Pending_Cancellation' !== get_post_meta( $this->subscription_id, 'sumo_get_status', true ) ) {
					update_post_meta( $this->subscription_id, 'sumo_get_status', 'Suspended' );
				}

				$this->add_note( __( 'Subscription has been Suspended based upon IPN received from PayPal.', 'sumosubscriptions' ), __( 'Subscription Suspended', 'sumosubscriptions' ) );
				break;
			case 'subscr_failed':
				$this->log_response( $posted );

				// if any failed payment happen change it to manual
				update_post_meta( $this->subscription_id, 'sumo_get_status', 'Failed' );
				update_post_meta( $this->subscription_id, 'sumo_get_last_payment_date', sumo_get_subscription_date() );

				$this->add_note( __( 'Subscription failed to renew this Subscription based upon IPN received from PayPal.', 'sumosubscriptions' ), __( 'Subscription Failed', 'sumosubscriptions' ) );
				break;
			case 'subscr_payment':
				$this->log_response( $posted );

				if ( 'Completed' === $posted[ 'payment_status' ] && sumo_is_next_renewal_possible( $this->subscription_id ) ) {
					$this_payment_date = sumo_get_subscription_date( $posted[ 'payment_date' ] );
					$next_due_time     = sumo_get_subscription_timestamp( get_post_meta( $this->subscription_id, 'sumo_get_next_payment_date', true ), 0, true );
					$this_payment_time = sumo_get_subscription_timestamp( $this_payment_date, 0, true );

					if ( $this_payment_time >= $next_due_time ) {
						//Prevent renewal order creation from subscription switch.
						if ( get_post_meta( $this->subscription_id, 'sumo_get_renewal_id', true ) != $this->payment_order_id ) {
							$this->complete_payment();
						}
					}
					//set transaction ID
					sumosubs_save_transaction_id( $this->payment_order_id, $posted[ 'txn_id' ], true );
				}
				break;
			case 'subscr_eot':
				$this->log_response( $posted );
				add_post_meta( $this->subscription_id, 'sumo_subscription_awaiting_status', 'Expired' );
				break;
		}
	}
}
