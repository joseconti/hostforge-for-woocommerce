<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handle subscription resubscribes by subscriber.
 * 
 * @class SUMOSubs_Resubscribe
 */
class SUMOSubs_Resubscribe {

	public static $can_subscriber_resubscribe     = false;
	public static $resubscribe_not_applicable_for = array();
	public static $resubscribing_statuses         = array( 'Expired', 'Cancelled' );

	/**
	 * Init SUMOSubs_Resubscribe.
	 */
	public static function init() {
		self::$can_subscriber_resubscribe     = 'yes' === SUMOSubs_Admin_Options::get_option( 'allow_subscribers_to_resubscribe' );
		self::$resubscribe_not_applicable_for = SUMOSubs_Admin_Options::get_option( 'hide_resubscribe_to_subscribers_when' );

		add_filter( 'woocommerce_add_to_cart_validation', __CLASS__ . '::validate_product_on_add_to_cart', 10, 5 );
		add_filter( 'sumosubscriptions_alter_subscription_plan_meta', __CLASS__ . '::set_resubscribed_subscription_plan_meta', 10, 4 );
		add_action( 'woocommerce_remove_cart_item', __CLASS__ . '::remove_resubscribed_item_from_cart', 10, 2 );
		add_action( 'woocommerce_cart_item_restored', __CLASS__ . '::restore_resubscribed_item_in_cart', 10, 2 );
		add_filter( 'woocommerce_cart_item_quantity', __CLASS__ . '::set_qty_restriction', 10, 3 );
	}

	/**
	 * Get Subscriber ID
	 *
	 * @param int $subscriber_id
	 * @return int
	 */
	public static function get_subscriber_id( $subscriber_id = 0 ) {
		return is_numeric( $subscriber_id ) && $subscriber_id ? absint( $subscriber_id ) : get_current_user_id();
	}

	/**
	 * Get the Subscription statuses eligible for Resubscribing
	 *
	 * @param int $subscription_id
	 * @return array
	 */
	public static function get_valid_resubscribe_statuses( $subscription_id ) {
		if ( empty( self::$resubscribe_not_applicable_for ) ) {
			return array( 'Cancelled', 'Expired' );
		}

		switch ( get_post_meta( $subscription_id, 'sumo_get_status', true ) ) {
			case 'Cancelled':
				$resubscribe_not_applicable_for = 'manual_cancel';

				if ( 'auto' === sumo_get_payment_type( $subscription_id ) ) {
					$resubscribe_not_applicable_for = 'auto_cancel';
				}

				switch ( get_post_meta( $subscription_id, 'sumo_subscription_cancel_requested_by', true ) ) {
					case 'admin':
						$resubscribe_not_applicable_for = 'admin_cancel';
						break;
					case 'subscriber':
						$resubscribe_not_applicable_for = 'user_cancel';
						break;
				}
				break;
			case 'Expired':
				$resubscribe_not_applicable_for = 'manual_expire';

				if ( 'auto' === sumo_get_payment_type( $subscription_id ) ) {
					$resubscribe_not_applicable_for = 'auto_expire';
				}
				break;
			default:
				$resubscribe_not_applicable_for = '';
				break;
		}

		if ( in_array( $resubscribe_not_applicable_for, self::$resubscribe_not_applicable_for ) ) {
			if ( in_array( $resubscribe_not_applicable_for, array( 'admin_cancel', 'user_cancel', 'auto_cancel', 'manual_cancel' ) ) ) {
				return array( 'Expired' );
			} else if ( in_array( $resubscribe_not_applicable_for, array( 'auto_expire', 'manual_expire' ) ) ) {
				return array( 'Cancelled' );
			}
		}

		return array( 'Cancelled', 'Expired' );
	}

	/**
	 * Get subscriptions available from the Subscriber
	 *
	 * @param int $subscription_id
	 * @param bool $get_resubscribing_subscriptions may be get valid subscriptions to resubscribe
	 * @return array
	 */
	public static function get_subscriptions_by_user( $subscription_id, $get_resubscribing_subscriptions = true ) {
		$statuses = self::get_valid_resubscribe_statuses( $subscription_id );

		if ( array( 'Cancelled' ) === $statuses ) {
			$meta_query = array(
				'key'     => 'sumo_get_status',
				'value'   => 'Cancelled',
				'compare' => $get_resubscribing_subscriptions ? '=' : '!=',
			);
		} elseif ( array( 'Expired' ) === $statuses ) {
			$meta_query = array(
				'key'     => 'sumo_get_status',
				'value'   => 'Expired',
				'compare' => $get_resubscribing_subscriptions ? '=' : '!=',
			);
		} else {
			$meta_query = array(
				'relation' => $get_resubscribing_subscriptions ? 'OR' : 'AND',
				array(
					'key'     => 'sumo_get_status',
					'value'   => 'Expired',
					'compare' => $get_resubscribing_subscriptions ? '=' : '!=',
				),
				array(
					'key'     => 'sumo_get_status',
					'value'   => 'Cancelled',
					'compare' => $get_resubscribing_subscriptions ? '=' : '!=',
				),
			);
		}

		return sumosubscriptions()->query->get( array(
					'type'       => 'sumosubscriptions',
					'status'     => 'publish',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'     => 'sumo_get_user_id',
							'value'   => self::get_subscriber_id(),
							'type'    => 'numeric',
							'compare' => '=',
						),
						array(
							'key'     => 'sumo_is_order_based_subscriptions',
							'value'   => 'yes',
							'compare' => 'NOT EXISTS',
						),
						$meta_query,
					),
				) );
	}

	/**
	 * Check whether the given product is resubscribed already by Subscriber. 
	 * That is, available currently in the Subscriber's Cart
	 * 
	 * @param int $product_id
	 * @param int $subscriber_id
	 * @return bool
	 */
	public static function is_subscription_resubscribed( $product_id, $subscriber_id = 0 ) {
		$subscriber_id = self::get_subscriber_id( $subscriber_id );
		return 0 === self::get_resubscribed_subscription( $product_id, $subscriber_id ) ? false : true;
	}

	/**
	 * Get resubscribed subscription from the subscriber resubscribed plan associated Subscriptions.
	 * 
	 * @param int $resubscribed_product_id
	 * @param int $subscriber_id
	 * @return int
	 */
	public static function get_resubscribed_subscription( $resubscribed_product_id, $subscriber_id = 0 ) {
		$subscriber_id                              = self::get_subscriber_id( $subscriber_id );
		$resubscribed_plan_associated_subscriptions = self::get_resubscribed_plan_associated_subscriptions( $resubscribed_product_id, $subscriber_id );

		//Since the resubscribed plans are same for the plan associated subscriptions, get any one of the Subscription ID
		if ( isset( $resubscribed_plan_associated_subscriptions[ 0 ] ) ) {
			return absint( $resubscribed_plan_associated_subscriptions[ 0 ] );
		}

		return 0;
	}

	/**
	 * Get subscriber resubscribed plan associated subscriptions.
	 *
	 * @param int $resubscribed_product_id
	 * @param int $subscriber_id
	 * @param int $resubscribed_subscription_id
	 * @return array
	 */
	public static function get_resubscribed_plan_associated_subscriptions( $resubscribed_product_id, $subscriber_id = 0, $resubscribed_subscription_id = 0 ) {
		$subscriber_id                              = self::get_subscriber_id( $subscriber_id );
		$resubscribed_plan_associated_subscriptions = get_user_meta( $subscriber_id, "sumo_resubscribed_plan_associated_subscriptions_of{$resubscribed_product_id}", true );
		$resubscribed_plan_associated_subscriptions = is_array( $resubscribed_plan_associated_subscriptions ) ? $resubscribed_plan_associated_subscriptions : array();

		if ( $resubscribed_subscription_id > 0 ) {
			$valid_subscriptions_to_resubscribe = self::get_subscriptions_by_user( $resubscribed_subscription_id );

			if ( $valid_subscriptions_to_resubscribe ) {
				foreach ( $valid_subscriptions_to_resubscribe as $_subscription_id ) {
					$valid_subscription_plan_to_resubscribe = sumo_get_subscription_plan( $_subscription_id );
					$resubscribed_subscription_plan         = sumo_get_subscription_plan( $resubscribed_subscription_id );

					if ( $resubscribed_subscription_plan == $valid_subscription_plan_to_resubscribe ) {
						$resubscribed_plan_associated_subscriptions[] = $_subscription_id;
					}
				}
			}
		}

		return array_unique( $resubscribed_plan_associated_subscriptions );
	}

	/**
	 * Get subscriber resubscribed plan associated subscriptions after he has removed the resubscribed product from the cart.
	 *
	 * @param int $resubscribed_product_id
	 * @param int $subscriber_id
	 * @return array
	 */
	public static function get_removed_resubscribed_plan_associated_subscriptions( $resubscribed_product_id, $subscriber_id = 0 ) {
		$subscriber_id                                      = self::get_subscriber_id( $subscriber_id );
		$removed_resubscribed_plan_associated_subscriptions = get_user_meta( $subscriber_id, "sumo_removed_resubscribed_plan_associated_subscriptions_of{$resubscribed_product_id}", true );
		return is_array( $removed_resubscribed_plan_associated_subscriptions ) ? $removed_resubscribed_plan_associated_subscriptions : array();
	}

	/**
	 * Check whether the Subscriber is eligible to resubscribe the Currently viewing Subscription
	 *
	 * @param int $current_viewing_subscription_id
	 * @return bool Show resubscribe button upon True or Hide upon False
	 */
	public static function can_subscriber_resubscribe( $current_viewing_subscription_id ) {
		if ( ! self::$can_subscriber_resubscribe ) {
			return false;
		}

		if ( ! in_array( get_post_meta( $current_viewing_subscription_id, 'sumo_get_status', true ), self::$resubscribing_statuses ) || SUMOSubs_Order_Subscription::is_subscribed( $current_viewing_subscription_id ) ) {
			return false;
		}

		if ( 'no' === get_post_meta( $current_viewing_subscription_id, 'sumo_subscription_can_resubscribe', true ) ) {
			return false;
		}

		//Get subscription plan from the Subscriber currently viewing Subscription page
		$current_viewing_subscription_plan = sumo_get_subscription_plan( $current_viewing_subscription_id );
		if ( self::is_subscription_resubscribed( $current_viewing_subscription_plan[ 'subscription_product_id' ] ) ) {
			return false;
		}

		$resubscribed_product = wc_get_product( $current_viewing_subscription_plan[ 'subscription_product_id' ] );
		if ( ! $resubscribed_product || ! $resubscribed_product->is_in_stock() ) {
			return false;
		}

		if ( ! sumo_can_purchase_subscription( $current_viewing_subscription_plan[ 'subscription_product_id' ] ) ) {
			return false;
		}
		//may be Admin restricted to show Resubscribe button based on self::$resubscribe_not_applicable_for
		if ( 1 === count( self::get_valid_resubscribe_statuses( $current_viewing_subscription_id ) ) ) {
			return false;
		}

		//may be Subscriber having the Subscriptions other than Cancelled or Expired status
		$resubscribe_not_applicable_subscriptions = self::get_subscriptions_by_user( $current_viewing_subscription_id, false );
		if ( $resubscribe_not_applicable_subscriptions ) {
			foreach ( $resubscribe_not_applicable_subscriptions as $_subscription_id ) {
				if ( $current_viewing_subscription_id == $_subscription_id ) {
					continue;
				}

				$resubscribe_not_applicable_subscription_plan = sumo_get_subscription_plan( $_subscription_id );
				//may be the Subscriber having other than Cancelled or Expired subscriptions for the currently viewing Subscription plan
				if ( $current_viewing_subscription_plan == $resubscribe_not_applicable_subscription_plan ) {
					return false;
				}
			}
		}

		//Show resubscribe button to the Subscriber
		return true;
	}

	/**
	 * Set resubscribed subscription plan
	 *
	 * @param array $subscription_plan_meta
	 * @param int $subscription_id
	 * @param int $product_id
	 * @return array
	 */
	public static function set_resubscribed_subscription_plan_meta( $subscription_plan_meta, $subscription_id, $product_id, $user_id ) {
		if ( ! is_cart() && ! is_checkout() && ! did_action( 'sumosubscriptions_before_adding_new_subscriptions' ) ) {
			return $subscription_plan_meta;
		}

		if ( self::is_subscription_resubscribed( $product_id, $user_id ) ) {
			$raw_subscription_plan_meta = sumo_get_subscription_meta( self::get_resubscribed_subscription( $product_id, $user_id ) );

			if ( 'new-price' === SUMOSubs_Admin_Options::get_option( 'update_old_subscription_price_to' ) ) {
				$raw_subscription_plan_meta[ 'subfee' ]   = isset( $subscription_plan_meta[ 'subfee' ] ) ? $subscription_plan_meta[ 'subfee' ] : 0;
				$raw_subscription_plan_meta[ 'sale_fee' ] = isset( $subscription_plan_meta[ 'sale_fee' ] ) ? $subscription_plan_meta[ 'sale_fee' ] : 0;
			}

			$subscription_plan_meta                            = $raw_subscription_plan_meta;
			$subscription_plan_meta[ 'trial_selection' ]       = '2';
			$subscription_plan_meta[ 'signusumoee_selection' ] = '2';
		}

		return $subscription_plan_meta;
	}

	/**
	 * Applicable for Mixed Checkout. 
	 * Throw error message when customer add to cart the Subscription product with Regular product else viceversa
	 * 
	 * @param bool $valid
	 * @param int $product_id Product post ID
	 * @param int $quantity
	 * @param int $variation_id Product variation post ID
	 * @param array $variations
	 * @return bool
	 */
	public static function validate_product_on_add_to_cart( $valid, $product_id, $quantity, $variation_id = null, $variations = null ) {
		if ( 0 === WC()->cart->get_cart_contents_count() ) {
			return $valid;
		}

		$add_to_cart_product_id = is_numeric( $variation_id ) && $variation_id ? $variation_id : $product_id;
		if ( self::is_subscription_resubscribed( $add_to_cart_product_id ) ) {
			wc_add_notice( __( 'Cannot add this product to cart because the same product is selected for resubscribe and it is currently added to cart!!', 'sumosubscriptions' ), 'error' );
			return false;
		}

		return $valid;
	}

	/**
	 * Doing some actions after the Subscriber removed the Resubscribed subscription from the cart.
	 *
	 * @param string $cart_item_key
	 * @param object $cart
	 */
	public static function remove_resubscribed_item_from_cart( $cart_item_key, $cart ) {
		$product_id = $cart->cart_contents[ $cart_item_key ][ 'variation_id' ] ? $cart->cart_contents[ $cart_item_key ][ 'variation_id' ] : $cart->cart_contents[ $cart_item_key ][ 'product_id' ];
		self::may_be_unset_resubscribed_subscription_by_user( $product_id );
	}

	/**
	 * Doing some actions after the Subscriber undo'd the Resubscribed subscription in the cart.
	 *
	 * @param string $cart_item_key
	 * @param object $cart
	 */
	public static function restore_resubscribed_item_in_cart( $cart_item_key, $cart ) {
		$product_id = $cart->cart_contents[ $cart_item_key ][ 'variation_id' ] ? $cart->cart_contents[ $cart_item_key ][ 'variation_id' ] : $cart->cart_contents[ $cart_item_key ][ 'product_id' ];
		self::may_be_set_resubscribed_subscription_by_user( $product_id );
	}

	/**
	 * Restrict Qty Min/Max field for resubscribed products
	 *
	 * @param int $product_quantity
	 * @param string $cart_item_key
	 * @param array $cart_item
	 * @return string
	 */
	public static function set_qty_restriction( $product_quantity, $cart_item_key, $cart_item = array() ) {
		if ( is_object( $cart_item ) || ! isset( $cart_item[ 'product_id' ] ) ) {
			$cart_item = WC()->cart->cart_contents[ $cart_item_key ];
		}

		$product_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ];

		if ( self::is_subscription_resubscribed( $product_id ) ) {
			return $cart_item[ 'quantity' ];
		}

		return $product_quantity;
	}

	/**
	 * Handling when Subscription resubscribed by Subscriber.
	 *
	 * @param int $subscription_id
	 */
	public static function do_resubscribe( $subscription_id ) {
		$subscription_plan  = sumo_get_subscription_plan( $subscription_id );
		$subscribed_product = wc_get_product( $subscription_plan[ 'subscription_product_id' ] );

		if ( ! $subscribed_product ) {
			wc_add_notice( __( 'Something went wrong!!', 'sumosubscriptions' ), 'error' );
			return sumo_get_subscription_endpoint_url( $subscription_id );
		}

		$subscribed_product_qty = sumo_get_subscription_qty( $subscription_id );

		if ( is_array( WC()->cart->cart_contents ) ) {
			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				if ( ! isset( $cart_item[ 'product_id' ] ) ) {
					continue;
				}

				$product_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ];
				if ( $subscription_plan[ 'subscription_product_id' ] == $product_id ) {
					wc_add_notice( __( 'You cannot resubscribe to this subscription because the same subscription product is currently in cart. Kindly complete the purchase/remove the product from cart and try again!!', 'sumosubscriptions' ), 'error' );
					return sumo_get_subscription_endpoint_url( $subscription_id );
				}
			}
		}

		if ( is_numeric( $subscription_plan[ 'variable_product_id' ] ) && $subscription_plan[ 'variable_product_id' ] ) {
			$cart_item_key = WC()->cart->add_to_cart( $subscription_plan[ 'variable_product_id' ], $subscribed_product_qty, $subscription_plan[ 'subscription_product_id' ] );
		} else {
			$cart_item_key = WC()->cart->add_to_cart( $subscription_plan[ 'subscription_product_id' ], $subscribed_product_qty );
		}

		if ( isset( WC()->cart->cart_contents[ "$cart_item_key" ] ) ) {
			$cart_item = WC()->cart->cart_contents[ "$cart_item_key" ];
			$cart_item[ 'data' ]->set_price( $subscription_plan[ 'subscription_fee' ] / $cart_item[ 'quantity' ] );

			if ( self::may_be_set_resubscribed_subscription_by_user( $subscription_plan[ 'subscription_product_id' ], $subscription_id ) ) {
				wc_add_notice( __( 'Complete checkout to resubscribe', 'sumosubscriptions' ), 'success' );
				return wc_get_checkout_url();
			}
		}

		return false;
	}

	/**
	 * May be save the Subscriber resubscribed product
	 * This might be useful when the Subscriber newly resubscribed the product or restoring the resubscribed product in to the Cart.
	 * 
	 * @param int $resubscribed_product_id
	 * @param int $resubscribed_subscription_id
	 * @param int $subscriber_id
	 * @return boolean True on success and False on failure
	 */
	public static function may_be_set_resubscribed_subscription_by_user( $resubscribed_product_id, $resubscribed_subscription_id = 0, $subscriber_id = 0 ) {
		$subscriber_id = self::get_subscriber_id( $subscriber_id );

		if ( $resubscribed_subscription_id > 0 ) {
			$resubscribed_plan_associated_subscriptions = self::get_resubscribed_plan_associated_subscriptions( $resubscribed_product_id, $subscriber_id, $resubscribed_subscription_id );
		} else {
			$resubscribed_plan_associated_subscriptions = self::get_removed_resubscribed_plan_associated_subscriptions( $resubscribed_product_id, $subscriber_id );
		}

		if ( $resubscribed_plan_associated_subscriptions ) {
			update_user_meta( $subscriber_id, "sumo_resubscribed_plan_associated_subscriptions_of{$resubscribed_product_id}", $resubscribed_plan_associated_subscriptions );
			delete_user_meta( $subscriber_id, "sumo_removed_resubscribed_plan_associated_subscriptions_of{$resubscribed_product_id}" );
			return true;
		}

		return false;
	}

	/**
	 * May be clear the Subscriber resubscribed product
	 * This might be useful when the Subscriber removes the resubscribed product from the Cart.
	 * 
	 * @param int $resubscribed_product_id
	 * @param int $subscriber_id
	 * @return boolean True on success and False on failure
	 */
	public static function may_be_unset_resubscribed_subscription_by_user( $resubscribed_product_id, $subscriber_id = 0 ) {
		$subscriber_id = self::get_subscriber_id( $subscriber_id );

		if ( self::is_subscription_resubscribed( $resubscribed_product_id, $subscriber_id ) ) {
			update_user_meta( $subscriber_id, "sumo_removed_resubscribed_plan_associated_subscriptions_of{$resubscribed_product_id}", self::get_resubscribed_plan_associated_subscriptions( $resubscribed_product_id, $subscriber_id ) );
			delete_user_meta( $subscriber_id, "sumo_resubscribed_plan_associated_subscriptions_of{$resubscribed_product_id}" );
			return true;
		}

		return false;
	}

	/**
	 * May be clear the resubscribed associated subscriptions. 
	 * When it is cleared, resubscribe button will be visible to the subscriber for the associated subscriptions. 
	 * This might be useful when the resubscribed subscription goes to Cancelled or Expired
	 * 
	 * @param int $subscription_id
	 * @return boolean True on success and False on failure
	 */
	public static function may_be_unset_resubscribed_associated_subscriptions( $subscription_id ) {
		$associated_subscriptions = get_post_meta( $subscription_id, 'sumo_resubscribed_plan_associated_subscriptions', true );

		if ( is_array( $associated_subscriptions ) ) {
			foreach ( $associated_subscriptions as $associated_subscription_id ) {
				delete_post_meta( $associated_subscription_id, 'sumo_subscription_can_resubscribe' );
			}

			delete_post_meta( $subscription_id, 'sumo_resubscribed_plan_associated_subscriptions' );
			return true;
		}

		return false;
	}
}

SUMOSubs_Resubscribe::init();

/**
 * For Backward Compatibility.
 */
class SUMO_Subscription_Resubscribe extends SUMOSubs_Resubscribe {
	
}
