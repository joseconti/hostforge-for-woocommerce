<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Order Legacy Abstract Class.
 *
 * @class   YWSBS_Subscription_Order_Legacy
 * @package YITH\Subscription
 * @since   3.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Class YWSBS_Subscription_Legacy
 */
abstract class YWSBS_Subscription_Order_Legacy {
	/**
	 * Save the options of subscription in an array with order item id
	 *
	 * @access     public
	 *
	 * @param   int                   $item_id   Order item id.
	 * @param   WC_Order_Item_Product $item      Order Item object.
	 * @param   int                   $order_id  Order id.
	 *
	 * @return void
	 * @deprecated 3.0.0
	 */
	public function add_subscription_order_item_meta( $item_id, $item, $order_id ) {
		_deprecated_function( 'get_formatted_recurring::add_subscription_order_item_meta', '3.0.0', 'This method will not be used in the future because the logic to create a subscription when an order is submitted has changed.' );
		if ( isset( $item->legacy_cart_item_key ) ) {
			$this->cart_item_order_item[ $item->legacy_cart_item_key ] = $item_id;
		}
	}

	/**
	 * Save some info if a subscription is in the cart
	 *
	 * @access     public
	 *
	 * @param   int   $order_id  Order id.
	 * @param   array $posted    Post variable.
	 *
	 * @throws Exception Trigger error.
	 * @deprecated 3.0.0
	 */
	public function get_extra_subscription_meta( $order_id, $posted ) {
		_deprecated_function( 'get_formatted_recurring::get_extra_subscription_meta', '3.0.0', 'This method will not be used in the future because the logic to create a subscription when an order is submitted has changed.' );
	}

	/**
	 * Revert cart after checkout.
	 *
	 * @deprecated 2.0.0
	 */
	public function revert_cart_after_checkout() {
		if ( isset( $this->order ) ) {
			_deprecated_function( 'YWSBS_Subscription_Order::revert_cart_after_checkout', '2.0.0' );
			$cart = get_post_meta( $this->order, 'saved_cart', true );
			WC()->cart->empty_cart( true );
			WC()->session->set( 'cart', $cart );
			WC()->cart->get_cart_from_session();
			WC()->cart->set_session();
		}
	}

	/**
	 * Check if the new order have subscriptions
	 *
	 * @return     bool
	 * @since      1.0.0
	 * @deprecated 2.0.0
	 */
	public function the_order_have_subscriptions() {
		_deprecated_function( 'YWSBS_Subscription_Order::the_order_have_subscriptions', '2.0.0', 'YWSBS_Subscription_Cart::cart_has_subscriptions' );

		return YWSBS_Subscription_Cart::cart_has_subscriptions();
	}

	/**
	 * Check in the order if there's a subscription and create it
	 *
	 * @param   int   $order_id  Order ID.
	 * @param   array $posted    $_POST variable.
	 *
	 * @return void
	 * @throws Exception Trigger an error.
	 * @depracated 3.0.0
	 */
	public function check_order_for_subscription( $order_id, $posted ) {
		_deprecated_function( 'YWSBS_Subscription_Order::check_order_for_subscription', '3.0.0', 'This method will not be used in the future because the logic to create a subscription when an order is submitted has changed.' );
	}
}
