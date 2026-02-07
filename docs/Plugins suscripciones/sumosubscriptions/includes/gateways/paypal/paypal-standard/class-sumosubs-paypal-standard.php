<?php

include_once 'inc/class-sumosubs-paypal-standard-api.php';
include_once 'inc/class-sumosubs-paypal-standard-ipn-handler.php';

/**
 * PayPal Standard for subscription.
 * 
 * @class SUMOSubs_PayPal_Standard
 */
class SUMOSubs_PayPal_Standard extends SUMOSubs_PayPal_Standard_IPN_Handler {

	/**
	 * Payment gateway ID.
	 * 
	 * @var string
	 */
	public $gateway_id = 'paypal';

	/**
	 * Array cache of PayPal Standard settings in WC.
	 * 
	 * @var array 
	 */
	protected $paypal_settings;

	/**
	 * Check whether PayPal subscriptions enabled in checkout.
	 *
	 * @var bool
	 */
	protected $subscriptions_enabled = false;

	/**
	 * Check whether to show PayPal subscriptions when multiple subscriptions are in cart.
	 *
	 * @var bool 
	 */
	protected $show_when_multiple_subscriptions_in_cart = false;

	/**
	 * SUMOSubs_PayPal_Standard constructor.
	 */
	public function __construct() {
		$this->paypal_settings                          = get_option( 'woocommerce_paypal_settings', array() );
		$this->subscriptions_enabled                    = isset( $this->paypal_settings[ 'sumosubs_enabled_for_subscriptions' ] ) ? 'yes' === $this->paypal_settings[ 'sumosubs_enabled_for_subscriptions' ] : ( 'yes' === get_option( 'sumo_include_paypal_subscription_api_option' ) );
		$this->show_when_multiple_subscriptions_in_cart = isset( $this->paypal_settings[ 'sumosubs_show_when_multiple_subscriptions_in_cart' ] ) ? 'yes' === $this->paypal_settings[ 'sumosubs_show_when_multiple_subscriptions_in_cart' ] : ( 'yes' === get_option( 'sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ) );

		add_filter( 'sumosubscriptions_available_payment_gateways', array( $this, 'add_subscription_supports' ) );
		add_filter( 'sumosubscriptions_need_payment_gateway', array( $this, 'need_payment_gateway' ), 20, 2 );
		add_filter( 'woocommerce_paypal_args', array( $this, 'subscription_request' ) );
		add_action( 'valid-paypal-standard-ipn-request', array( $this, 'process_subscriptions_based_upon_IPN_request' ) );

		add_filter( 'woocommerce_settings_api_form_fields_paypal', array( $this, 'add_enable_subscriptions_setting' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->gateway_id, array( $this, 'delete_deprecated_meta' ), 20 );
		add_filter( 'sumosubscriptions_admin_can_change_subscription_statuses', array( $this, 'can_change_subscription_statuses' ), 10, 2 );
		add_filter( 'sumosubscriptions_edit_subscription_page_readonly_mode', array( $this, 'set_subscription_as_non_editable_fields' ), 10, 3 );
		add_filter( 'sumosubscriptions_edit_subscription_statuses', array( $this, 'set_paypal_statuses' ), 10, 4 );
		add_action( 'sumosubscriptions_manual_suspended_subscription', array( $this, 'suspend_subscription' ), 10, 2 );
		add_action( 'sumosubscriptions_manual_reactivate_subscription', array( $this, 'reactivate_subscription' ), 10, 2 );
		add_action( 'sumosubscriptions_subscription_cancelled', array( $this, 'cancel_subscription' ) );
		add_action( 'sumosubscriptions_subscription_expired', array( $this, 'cancel_subscription' ) );
		add_filter( 'sumosubscriptions_revoke_automatic_subscription', array( $this, 'revoke_subscription' ), 10, 3 );
		add_filter( 'sumosubscriptions_revoke_cancel_request_scheduled', array( $this, 'revoke_cancel_request' ), 10, 3 );
		add_filter( 'sumosubscriptions_schedule_cancel', array( $this, 'suspend_subscription_on_scheduled_to_cancel' ), 10, 3 );

		add_filter( 'sumosubscriptions_my_subscription_table_pause_action', array( $this, 'hide_pause_action_in_my_subscriptions_table' ), 10, 3 );

		add_filter( 'sumosubscriptions_can_upgrade_or_downgrade', array( $this, 'hide_switcher' ), 10, 2 );
		add_filter( 'sumosubscriptions_display_variation_switch_fields', array( $this, 'hide_switcher' ), 10, 2 );
		add_filter( 'sumosubscriptions_schedule_subscription_crons', array( $this, 'prevent_subscription_cron_schedules' ), 10, 4 );
	}

	/**
	 * Extract the Order ID from PayPal args.
	 * 
	 * @param array $paypal_args
	 * @return int
	 */
	public function get_the_order_id( $paypal_args ) {
		if ( ! empty( $paypal_args[ 'custom' ] ) ) {
			$json_object = json_decode( $paypal_args[ 'custom' ] );

			if ( ! empty( $json_object->order_id ) ) {
				$this->payment_order_id = absint( $json_object->order_id );
				$this->get_payment_order();
			}
		}

		return $this->payment_order_id;
	}

	/**
	 * Check whether the Order has valid Subscription?
	 * 
	 * @return boolean
	 */
	public function order_has_valid_subscription() {
		if ( $this->payment_order && 1 === count( $this->payment_order->get_items() ) ) {
			$this->get_subscription();

			if ( $this->subscription ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add gateway to support subscriptions.
	 * 
	 * @param array $subscription_gateways
	 * @return array
	 */
	public function add_subscription_supports( $subscription_gateways ) {
		if ( $this->subscriptions_enabled ) {
			$subscription_gateways[] = $this->gateway_id;
		}

		return $subscription_gateways;
	}

	/**
	 * Allow gateway on demand.
	 * 
	 * @param bool $need
	 * @param string $gateway_id
	 * @return bool
	 */
	public function need_payment_gateway( $need, $gateway_id ) {
		if ( ! $need || $this->gateway_id !== $gateway_id ) {
			return $need;
		}

		if ( ! $this->subscriptions_enabled ) {
			return $need;
		}

		if ( SUMOSubs_Order_Subscription::is_subscribed() ) {
			return false;
		}

		if ( SUMOSubs_Payment_Gateways::auto_payments_alone_enabled() || SUMOSubs_Payment_Gateways::mixed_payments_enabled() ) {
			if ( count( WC()->cart->cart_contents ) <= 1 ) {
				return $need;
			}

			if ( SUMOSubs_Payment_Gateways::checkout_has_multiple_subscriptions() && $this->show_when_multiple_subscriptions_in_cart ) {
				$this->subscriptions_enabled = false;
				return $need;
			}
		}

		return false;
	}

	/**
	 * Setup the Subscription API in the PayPal args.
	 * 
	 * @param array $paypal_args
	 * @return array
	 */
	public function subscription_request( $paypal_args ) {
		if ( $this->subscriptions_enabled && SUMOSubs_Payment_Gateways::gateway_requires_auto_renewals( $this->gateway_id ) ) {
			$this->populate( $this->get_the_order_id( $paypal_args ) );

			if ( $this->order_has_valid_subscription() ) {
				$paypal_args = $this->alter_payal_request( $paypal_args );
			}
		}

		return $paypal_args;
	}

	/**
	 * Process PayPal Subscriptions based on incoming IPN request.
	 * 
	 * @param array $posted
	 */
	public function process_subscriptions_based_upon_IPN_request( $posted ) {
		if ( ! empty( $posted[ 'txn_type' ] ) ) {
			$this->populate( $this->get_the_order_id( $posted ) );
			if ( $this->order_has_valid_subscription() ) {
				$this->manage_subscription_via_ipn( $posted );
			}
		}
	}

	/**
	 * Add the enabled or subscriptions setting.
	 *
	 * @param array $settings The WooCommerce PayPal Settings array.
	 * @return array
	 */
	public function add_enable_subscriptions_setting( $settings ) {
		$updated_settings = array();

		foreach ( $settings as $key => $value ) {
			$updated_settings[ $key ] = $value;

			if ( 'enabled' === $key ) {
				$updated_settings[ 'sumosubs_enabled_for_subscriptions' ] = array(
					'type'    => 'checkbox',
					'label'   => __( 'Enable PayPal Standard for Subscriptions', 'sumosubscriptions' ),
					'default' => get_option( 'sumo_include_paypal_subscription_api_option', 'no' ),
				);
			} else if ( 'image_url' === $key ) {
				$updated_settings[ 'sumosubs_show_when_multiple_subscriptions_in_cart' ] = array(
					'title'   => __( 'Show when Multiple Subscription Products are in Cart', 'sumosubscriptions' ),
					'type'    => 'checkbox',
					'label'   => ' ',
					'default' => get_option( 'sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart', 'no' ),
				);
			}
		}

		return $updated_settings;
	}

	/**
	 * Flush out deprecated meta on save.
	 */
	public function delete_deprecated_meta() {
		delete_option( 'sumo_include_paypal_subscription_api_option' );
		delete_option( 'sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' );
	}

	/**
	 * Check admin has privilege to change subscription status in edit subscription page.
	 * 
	 * @param boolean $bool
	 * @param int $subscription_id The Subscription post ID
	 * @return boolean
	 */
	public function can_change_subscription_statuses( $bool, $subscription_id ) {
		$this->populate( 0, $subscription_id );
		if ( $this->is_paypal_subscription() ) {
			return true;
		}

		return $bool;
	}

	/**
	 * Set edit subscription page as non editable fields.
	 * 
	 * @param boolean $bool
	 * @param int $subscription_id The Subscription post ID
	 * @param int $order_id The Parent Order post ID
	 * @return boolean
	 */
	public function set_subscription_as_non_editable_fields( $bool, $subscription_id, $order_id ) {
		$this->populate( $order_id, $subscription_id );
		if ( $this->is_paypal_subscription() ) {
			return true;
		}

		return $bool;
	}

	/**
	 * Manage Subscription Status in backend settings.
	 * 
	 * @param array $statuses
	 * @param int $subscription_id The Subscription post ID
	 * @param int $order_id The Parent Order post ID
	 * @param string $subscription_status
	 * @return string
	 */
	public function set_paypal_statuses( $statuses, $subscription_id, $order_id, $subscription_status ) {
		$this->populate( $order_id, $subscription_id );
		if ( $this->is_paypal_subscription() ) {
			switch ( $subscription_status ) {
				case 'Trial':
				case 'Active':
					unset( $statuses[ 'Pause' ] );
					$statuses[ 'Suspended' ]  = 'Suspended';
					break;
				case 'Suspended':
					unset( $statuses[ 'Resume' ] );
					$statuses[ 'Reactivate' ] = 'Reactivate';
					break;
			}
		}

		return $statuses;
	}

	/**
	 * Request PayPal to Suspend Subscription.
	 * 
	 * @param int $subscription_id The Subscription post ID
	 * @param int $order_id The Parent Order post ID
	 */
	public function suspend_subscription( $subscription_id, $order_id ) {
		$this->populate( $order_id, $subscription_id );
		if ( $this->is_paypal_subscription() ) {
			if ( $this->is_ACK_success( $this->request_suspend() ) ) {
				$this->add_note( __( 'Awaiting for PayPal IPN to suspend the subscription.', 'sumosubscriptions' ), __( 'Awaiting PayPal IPN response', 'sumosubscriptions' ) );
			} else {
				sumo_add_subscription_note( __( 'Unable to suspend the subscription in PayPal.', 'sumosubscriptions' ), $subscription_id, 'failure', __( 'Subscription suspend failed in PayPal', 'sumosubscriptions' ) );
			}
		}
	}

	/**
	 * Request PayPal to Reactivate Subscription.
	 * 
	 * @param int $subscription_id The Subscription post ID
	 * @param int $order_id The Parent Order post ID
	 */
	public function reactivate_subscription( $subscription_id, $order_id ) {
		$this->populate( $order_id, $subscription_id );
		if ( $this->is_paypal_subscription() ) {
			if ( $this->is_ACK_success( $this->request_reactivate() ) ) {
				// Since we do not have IPN response from PayPal for Subscription Reactivation we are doing like this.
				update_post_meta( $subscription_id, 'sumo_get_status', 'Active' );
				$this->add_note( __( 'Subscription reactivated in PayPal.', 'sumosubscriptions' ), __( 'Subscription reactivated', 'sumosubscriptions' ) );
			} else {
				sumo_add_subscription_note( __( 'Unable to reactivate the subscription in PayPal.', 'sumosubscriptions' ), $subscription_id, 'failure', __( 'Subscription reactivation failed in PayPal', 'sumosubscriptions' ) );
			}
		}
	}

	/**
	 * Request PayPal to Cancel Subscription.
	 * 
	 * @param int $subscription_id The Subscription post ID
	 */
	public function cancel_subscription( $subscription_id ) {
		$order_id = get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true );
		$this->populate( $order_id, $subscription_id );

		if ( $this->is_paypal_subscription() ) {
			if ( $this->is_ACK_success( $this->request_cancel() ) ) {
				$this->add_note( __( 'Awaiting for PayPal IPN to cancel the subscription.', 'sumosubscriptions' ), __( 'Awaiting PayPal IPN response', 'sumosubscriptions' ) );
			} else {
				sumo_add_subscription_note( __( 'Unable to cancel the subscription in PayPal.', 'sumosubscriptions' ), $subscription_id, 'failure', __( 'Subscription cancel failed in PayPal', 'sumosubscriptions' ) );
			}
		}
	}

	/**
	 * Since revoke API is not available in PayPal, Cancel the Subscription.
	 * 
	 * @param boolean $bool
	 * @param int $subscription_id The Subscription post ID
	 * @param int $order_id The Parent Order post ID
	 */
	public function revoke_subscription( $bool, $subscription_id, $order_id ) {
		$this->populate( $order_id, $subscription_id );
		if ( $this->is_paypal_subscription() ) {
			if ( $this->is_ACK_success( $this->request_cancel() ) ) {
				$bool = true;
				add_post_meta( $this->subscription_id, '_paypal_subscription_revoke_pending', 'yes' );
				$this->add_note( __( 'Awaiting for PayPal IPN to cancel the subscription.', 'sumosubscriptions' ), __( 'Awaiting PayPal IPN response', 'sumosubscriptions' ) );
			} else {
				$bool = false;
				sumo_add_subscription_note( __( 'Unable to cancel the subscription in PayPal.', 'sumosubscriptions' ), $subscription_id, 'failure', __( 'Subscription cancel failed in PayPal', 'sumosubscriptions' ) );
			}
		}

		return $bool;
	}

	/**
	 * Since profile is suspended in PayPal, request PayPal to Reactivate Subscription.
	 * 
	 * @param boolean $bool
	 * @param int $subscription_id The Subscription post ID
	 * @param int $order_id The Parent Order post ID
	 */
	public function revoke_cancel_request( $bool, $subscription_id, $order_id ) {
		$this->populate( $order_id, $subscription_id );
		if ( $this->is_paypal_subscription() ) {
			if ( $this->is_ACK_success( $this->request_reactivate() ) ) {
				$bool = true;
				$this->add_note( __( 'Subscription reactivated in PayPal.', 'sumosubscriptions' ), __( 'Subscription reactivated', 'sumosubscriptions' ) );
			} else {
				$bool = false;
				sumo_add_subscription_note( __( 'Unable to reactivate the subscription in PayPal.', 'sumosubscriptions' ), $subscription_id, 'failure', __( 'Subscription reactivation failed in PayPal', 'sumosubscriptions' ) );
			}
		}

		return $bool;
	}

	/**
	 * Suspend the Subscription when it is scheduled to cancel.
	 * 
	 * @param boolean $bool
	 * @param int $subscription_id The Subscription post ID
	 * @param int $order_id The Parent Order post ID
	 */
	public function suspend_subscription_on_scheduled_to_cancel( $bool, $subscription_id, $order_id ) {
		$this->populate( $order_id, $subscription_id );
		if ( $this->is_paypal_subscription() ) {
			if ( $this->is_ACK_success( $this->request_suspend() ) ) {
				$bool = true;
				$this->add_note( __( 'Awaiting for PayPal IPN to suspend the subscription.', 'sumosubscriptions' ), __( 'Awaiting PayPal IPN response', 'sumosubscriptions' ) );
			} else {
				$bool = false;
				sumo_add_subscription_note( __( 'Unable to suspend the subscription in PayPal.', 'sumosubscriptions' ), $subscription_id, 'failure', __( 'Subscription suspend failed in PayPal', 'sumosubscriptions' ) );
			}
		}

		return $bool;
	}

	/**
	 * Prevent existing Subscription Crons from schedule for the PayPal Standard Subscription.
	 * 
	 * @param boolean $action
	 * @param string $cron_job
	 * @param int $subscription_id The Subscription post ID
	 * @param int $order_id The Parent Order post ID
	 * @return boolean
	 */
	public function prevent_subscription_cron_schedules( $action, $cron_job, $subscription_id, $order_id ) {
		switch ( $cron_job ) {
			case 'create_renewal_order':
			case 'notify_invoice_reminder':
				if ( 'auto' === sumo_get_payment_type( $subscription_id ) ) {
					return $action;
				}
				break;
			case 'notify_cancel':
				if ( 'Pending_Cancellation' === get_post_meta( $subscription_id, 'sumo_get_status', true ) ) {
					return $action;
				}
				break;
			case 'notify_expire':
				return $action;
				break;
		}

		$this->populate( $order_id, $subscription_id );

		if ( $this->is_paypal_subscription() ) {
			return false;
		}

		return $action;
	}

	/**
	 * Manage Pause Actions in My Subscriptions Table on My Account page.
	 * 
	 * @param bool $action
	 * @param int $subscription_id The Subscription post ID
	 * @param int $parent_order_id The Parent Order post ID
	 * @return bool
	 */
	public function hide_pause_action_in_my_subscriptions_table( $action, $subscription_id, $parent_order_id ) {
		$this->populate( $parent_order_id, $subscription_id );
		if ( $this->is_paypal_subscription() ) {
			return false;
		}

		return $action;
	}

	/**
	 * Hide Switcher in Edit Subscription Page and My Subscriptions Table on My Account page.
	 * 
	 * @param string $action
	 * @param int $subscription_id The Subscription post ID
	 * @return string
	 */
	public function hide_switcher( $action, $subscription_id ) {
		$order_id = get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true );

		$this->populate( $order_id, $subscription_id );
		if ( $this->is_paypal_subscription() ) {
			return '';
		}

		return $action;
	}
}

new SUMOSubs_PayPal_Standard();
