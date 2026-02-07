<?php
/**
 * Lifecycle helper functions for the Scheduler API.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI
 */

if ( ! function_exists( 'aswc_get_subscription' ) ) {
	/**
	 * Retrieve a subscription by its ID.
	 *
	 * @param int $subscription_id Subscription post ID.
	 * @return WC_Subscription|false Subscription object if found, otherwise false.
	 */
	function aswc_get_subscription( $subscription_id ) {
		if ( function_exists( 'wc_get_order' ) ) {
			$subscription = wc_get_order( $subscription_id );

			if ( aswc_is_subscription( $subscription ) ) {
				return $subscription;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_get_order' ) ) {
	/**
	 * Retrieve an order by its ID.
	 *
	 * Wraps the `wc_get_order` helper so the Scheduler API does not depend
	 * directly on WooCommerce functions when fetching orders.
	 *
	 * @param int $order_id Order ID.
	 * @return WC_Order|false Order object if found, otherwise false.
	 */
	function aswc_get_order( $order_id ) {
		if ( function_exists( 'wc_get_order' ) ) {
			return wc_get_order( $order_id );
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_get_payment_gateway_by_order' ) ) {
	/**
	 * Retrieve the payment gateway used by an order.
	 *
	 * Wraps the `wc_get_payment_gateway_by_order` helper so the Scheduler API
	 * does not depend directly on WooCommerce functions when fetching a
	 * gateway.
	 *
	 * @param int|WC_Order $order Order object or ID.
	 * @return WC_Payment_Gateway|false Payment gateway instance if available,
	 *                                 otherwise false.
	 */
	function aswc_get_payment_gateway_by_order( $order ) {
		if ( function_exists( 'wc_get_payment_gateway_by_order' ) ) {
			return wc_get_payment_gateway_by_order( $order );
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_get_subscriptions' ) ) {
	/**
	 * Retrieve subscriptions matching query arguments.
	 *
	 * @param array $args Optional query arguments.
	 * @return array List of WC_Subscription objects.
	 */
	function aswc_get_subscriptions( $args = array() ) {
		unset( $args );
		return array();
	}
}

if ( ! function_exists( 'aswc_get_subscriptions_for_order' ) ) {
	/**
	 * Retrieve subscriptions associated with an order.
	 *
	 * @param int|WC_Order $order Order object or ID.
	 * @param array        $args  Optional query arguments.
	 *
	 * @return array List of WC_Subscription objects.
	 */
	function aswc_get_subscriptions_for_order( $order, $args = array() ) {
		if ( function_exists( 'wcs_get_subscriptions_for_order' ) ) {
			$subscriptions = wcs_get_subscriptions_for_order( $order, $args );
			return is_array( $subscriptions ) ? $subscriptions : array();
		}
		return array();
	}
}

if ( ! function_exists( 'aswc_get_subscription_ids_for_order' ) ) {
	/**
	 * Retrieve subscription IDs associated with an order.
	 *
	 * @param int|WC_Order    $order       Order object or ID.
	 * @param string|string[] $order_types Relationship types to include.
	 *
	 * @return array List of subscription IDs.
	 */
	function aswc_get_subscription_ids_for_order( $order, $order_types = array( 'any' ) ) {
		if ( function_exists( 'wcs_get_subscription_ids_for_order' ) ) {
			$subscription_ids = wcs_get_subscription_ids_for_order( $order, $order_types );
			return is_array( $subscription_ids ) ? $subscription_ids : array();
		}
		return array();
	}
}

if ( ! function_exists( 'aswc_get_subscriptions_for_renewal_order' ) ) {
	/**
	 * Retrieve subscriptions associated with a renewal order.
	 *
	 * @param int|WC_Order $order Renewal order object or ID.
	 * @param array        $args  Optional query arguments.
	 * @return array List of WC_Subscription objects.
	 */
	function aswc_get_subscriptions_for_renewal_order( $order, $args = array() ) {
		return aswc_get_subscriptions_for_order( $order, $args );
	}
}

if ( ! function_exists( 'aswc_get_canonical_product_id' ) ) {
	/**
	 * Get the canonical product ID for an order or subscription item.
	 *
	 * @param mixed $item Order item or subscription line item.
	 * @return int Product ID. Returns 0 when unavailable.
	 */
	function aswc_get_canonical_product_id( $item ) {
		if ( is_object( $item ) && method_exists( $item, 'get_product_id' ) ) {
			return (int) $item->get_product_id();
		}

		return 0;
	}
}

if ( ! function_exists( 'aswc_get_order_item' ) ) {
	/**
	 * Retrieve an order item by ID.
	 *
	 * @param int      $item_id Order item ID.
	 * @param WC_Order $order   Order instance.
	 * @return WC_Order_Item|false Order item object if found, otherwise false.
	 */
	function aswc_get_order_item( $item_id, $order ) {
		if ( is_object( $order ) && method_exists( $order, 'get_item' ) ) {
			return $order->get_item( $item_id );
		}

		return false;
	}
}

if ( ! function_exists( 'aswc_copy_order_item' ) ) {
	/**
	 * Copy data from one order item to another.
	 *
	 * @param WC_Order_Item $from_item Source order item instance.
	 * @param WC_Order_Item $to_item   Destination order item instance.
	 *
	 * @return void
	 */
	function aswc_copy_order_item( $from_item, $to_item ) {
		foreach ( $from_item->get_meta_data() as $meta_data ) {
			if ( '_reduced_stock' === $meta_data->key ) {
				continue;
			}

			$to_item->update_meta_data( $meta_data->key, $meta_data->value );
		}

		switch ( $from_item->get_type() ) {
			case 'line_item':
				$to_item->set_props(
					array(
						'product_id'   => $from_item->get_product_id(),
						'variation_id' => $from_item->get_variation_id(),
						'quantity'     => $from_item->get_quantity(),
						'tax_class'    => $from_item->get_tax_class(),
						'subtotal'     => $from_item->get_subtotal(),
						'total'        => $from_item->get_total(),
						'taxes'        => $from_item->get_taxes(),
					)
				);
				break;
			case 'shipping':
				$to_item->set_props(
					array(
						'method_id' => $from_item->get_method_id(),
						'total'     => $from_item->get_total(),
						'taxes'     => $from_item->get_taxes(),
					)
				);

				if ( method_exists( $from_item, 'get_instance_id' ) ) {
					$to_item->set_instance_id( $from_item->get_instance_id() );
				}
				break;
			case 'tax':
				$to_item->set_props(
					array(
						'rate_id'            => $from_item->get_rate_id(),
						'label'              => $from_item->get_label(),
						'compound'           => $from_item->get_compound(),
						'tax_total'          => $from_item->get_tax_total(),
						'shipping_tax_total' => $from_item->get_shipping_tax_total(),
					)
				);

				if ( is_callable( array( $from_item, 'get_rate_percent' ) ) ) {
					$to_item->set_rate_percent( $from_item->get_rate_percent() );
				}
				break;
			case 'fee':
				$to_item->set_props(
					array(
						'tax_class'  => $from_item->get_tax_class(),
						'tax_status' => $from_item->get_tax_status(),
						'total'      => $from_item->get_total(),
						'taxes'      => $from_item->get_taxes(),
					)
				);
				break;
			case 'coupon':
				$to_item->set_props(
					array(
						'discount'     => $from_item->get_discount(),
						'discount_tax' => $from_item->get_discount_tax(),
					)
				);
				break;
		}
	}
}

if ( ! function_exists( 'aswc_create_renewal_order' ) ) {
	/**
	 * Create a renewal order for a subscription.
	 *
	 * This function is completely independent and does not rely on WooCommerce Subscriptions.
	 * It copies all necessary data from the subscription to the renewal order, including
	 * payment tokens for automatic payments.
	 *
	 * @param WC_Subscription|int $subscription Subscription instance or ID.
	 *
	 * @return WC_Order|WP_Error Renewal order on success or WP_Error on failure.
	 */
	function aswc_create_renewal_order( $subscription ) {
		if ( ! $subscription instanceof WC_Subscription ) {
			$subscription = aswc_get_subscription( $subscription );
		}

		if ( ! $subscription ) {
			return new WP_Error(
				'aswc_create_renewal_order_invalid_subscription',
				__( 'Invalid subscription.', 'advanced-subscriptions-for-woocommerce' )
			);
		}

		try {
			// Create the renewal order.
			$renewal_order = wc_create_order(
				array(
					'customer_id'   => $subscription->get_user_id(),
					'customer_note' => $subscription->get_customer_note(),
					'created_via'   => 'subscription',
				)
			);

			if ( is_wp_error( $renewal_order ) ) {
				return $renewal_order;
			}

			// Copy addresses from subscription.
			$renewal_order->set_address( $subscription->get_address( 'billing' ), 'billing' );
			$renewal_order->set_address( $subscription->get_address( 'shipping' ), 'shipping' );

			// Copy payment method from subscription.
			$payment_method = $subscription->get_payment_method();
			if ( $payment_method ) {
				$renewal_order->set_payment_method( $payment_method );
				$renewal_order->set_payment_method_title( $subscription->get_payment_method_title() );
			}

			// Copy payment tokens from subscription (crucial for automatic payments).
			$payment_tokens = $subscription->get_payment_tokens();
			if ( ! empty( $payment_tokens ) && method_exists( $renewal_order->get_data_store(), 'update_payment_token_ids' ) ) {
				$renewal_order->get_data_store()->update_payment_token_ids( $renewal_order, $payment_tokens );
			}

			// Copy currency from subscription.
			$currency = $subscription->get_currency();
			if ( $currency ) {
				$renewal_order->set_currency( $currency );
			}

			// Copy prices_include_tax setting.
			if ( method_exists( $subscription, 'get_prices_include_tax' ) ) {
				$renewal_order->set_prices_include_tax( $subscription->get_prices_include_tax() );
			}

			// Copy customer IP and user agent if available.
			if ( method_exists( $subscription, 'get_customer_ip_address' ) ) {
				$customer_ip = $subscription->get_customer_ip_address();
				if ( $customer_ip ) {
					$renewal_order->set_customer_ip_address( $customer_ip );
				}
			}
			if ( method_exists( $subscription, 'get_customer_user_agent' ) ) {
				$user_agent = $subscription->get_customer_user_agent();
				if ( $user_agent ) {
					$renewal_order->set_customer_user_agent( $user_agent );
				}
			}

			// Copy all order items (line items, fees, shipping, tax, coupons).
			$items = $subscription->get_items( array( 'line_item', 'fee', 'shipping', 'tax', 'coupon' ) );

			foreach ( $items as $item ) {
				$order_item_id = wc_add_order_item(
					$renewal_order->get_id(),
					array(
						'order_item_name' => $item->get_name(),
						'order_item_type' => $item->get_type(),
					)
				);

				$order_item = $renewal_order->get_item( $order_item_id );
				if ( $order_item ) {
					aswc_copy_order_item( $item, $order_item );
					$order_item->save();
				}
			}

			// Mark as subscription renewal.
			$renewal_order->update_meta_data( '_subscription_renewal', $subscription->get_id() );
			$renewal_order->save();

			// Reload order to ensure all items are available before calculating totals.
			$renewal_order = wc_get_order( $renewal_order->get_id() );

			// Recalculate totals including taxes.
			$renewal_order->calculate_totals();
			$renewal_order->save();

			// Link the renewal order to the subscription for later lookups (ASWC specific meta).
			aswc_update_meta_data( $renewal_order->get_id(), 'aswc_subscription', $subscription->get_id() );
			aswc_update_meta_data( $renewal_order->get_id(), 'aswc_renewal_order', 'yes' );

			aswc_update_meta_data( $subscription->get_id(), 'aswc_last_renewal_order_id', $renewal_order->get_id() );

			$renewal_orders = aswc_get_meta_data( $subscription->get_id(), 'aswc_renewal_order_data', true );
			if ( ! is_array( $renewal_orders ) ) {
				$renewal_orders = array();
			}
			$renewal_orders[] = $renewal_order->get_id();
			aswc_update_meta_data( $subscription->get_id(), 'aswc_renewal_order_data', $renewal_orders );

			return $renewal_order;
		} catch ( Exception $e ) {
			return new WP_Error( 'aswc_create_renewal_order_error', $e->getMessage() );
		}
	}
}
