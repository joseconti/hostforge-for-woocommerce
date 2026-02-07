<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Handle Woocommerce TM Extra Product Options addon plugin compatibility
 * 
 * @class SUMOSubs_WC_TM_Extra_Product_Options
 */
class SUMOSubs_WC_TM_Extra_Product_Options {

	public static $subscribed_plan = array() ;

	/**
	 * Init SUMOSubs_WC_TM_Extra_Product_Options.
	 */
	public static function init() {
		add_filter( 'sumosubscriptions_get_product_addon_fee', __CLASS__ . '::get_product_addon_fee', 10, 3 ) ;
		add_filter( 'sumosubscriptions_get_line_total', __CLASS__ . '::add_product_addon_fee', 10, 5 ) ;
		add_action( 'woocommerce_new_order_item', __CLASS__ . '::save_addon_fee', 10, 2 ) ;
	}

	/**
	 * Get addon fee from Cart object.
	 * 
	 * @param int $cart_item
	 * @return float|int
	 */
	public static function maybe_get_addon_fee( $cart_item, $item_id = 0 ) {
		$addon_fee = 0 ;

		if ( $item_id > 0 ) {
			self::$subscribed_plan = sumo_get_subscription_plan( 0, $item_id ) ;
		}

		if ( isset( $cart_item[ 'tm_epo_options_prices' ] ) ) {
			if ( is_numeric( $cart_item[ 'tm_epo_options_prices' ] ) && $cart_item[ 'tm_epo_options_prices' ] ) {
				$addon_fee += floatval( $cart_item[ 'tm_epo_options_prices' ] ) ;
			}
			//            if ( isset( $cart_item[ 'epo_price_override' ] ) && $cart_item[ 'epo_price_override' ] ) {
			//                $cart_item[ 'tm_epo_product_original_price' ] = 0 ;
			//            }
			//            if ( $addon_fee > 0 && isset( $cart_item[ 'tm_epo_product_original_price' ] ) && is_numeric( $cart_item[ 'tm_epo_product_original_price' ] ) && $cart_item[ 'tm_epo_product_original_price' ] ) {
			//                $addon_fee += floatval( $cart_item[ 'tm_epo_product_original_price' ] ) ;
			//            }
		}

		return $addon_fee ;
	}

	/**
	 * Neglect Addon Amount in Initial Order if the Subscription product having Trial and consider it in Renewals. 
	 * Calculate Addon Amount only with Subscription fee. 
	 * 
	 * @param float|int $_line_total
	 * @param mixed $subscription
	 * @return float|int
	 */
	public static function add_product_addon_fee( $_line_total, $subscription, $default_line_total, $is_trial_enabled, $subscription_obj_type ) {
		if ( $is_trial_enabled || 'subscription' === $subscription_obj_type ) {
			return $_line_total ;
		}

		$default_line_total = is_numeric( $default_line_total ) && $default_line_total ? $default_line_total : 0 ;
		$addon_amount       = max( $default_line_total, floatval( $subscription->get_recurring_amount() ) ) - min( floatval( $subscription->get_recurring_amount() ), $default_line_total ) ;

		if ( is_numeric( $addon_amount ) && $addon_amount ) {
			if ( $subscription->get_signup( 'forced' ) ) {
				$_line_total = $_line_total > floatval( $subscription->get_signup( 'fee' ) ) ? $_line_total - floatval( $subscription->get_signup( 'fee' ) ) : floatval( $subscription->get_signup( 'fee' ) ) - $_line_total ;
			}

			$_line_total += $addon_amount ;
		}

		return $_line_total ;
	}

	/**
	 * Get Product Addon Fees if it is applicable in Cart.
	 * 
	 * @param float|int $addon_fee
	 * @param int $product_id
	 * @param array $cart_item
	 * @return float|int
	 */
	public static function get_product_addon_fee( $addon_fee, $product_id, $cart_item ) {
		$addon_fee += self::maybe_get_addon_fee( $cart_item, $product_id ) ;
		return $addon_fee ;
	}

	/**
	 * Save Addon Fees if it is applicable in Cart.
	 * 
	 * @param int $order_item_id
	 * @param array $cart_item
	 */
	public static function save_addon_fee( $order_item_id, $cart_item ) {
		if ( ! isset( $cart_item[ 'product_id' ] ) ) {
			return ;
		}

		$product_id            = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;
		self::$subscribed_plan = sumo_get_subscription_plan( 0, $product_id ) ;

		if ( '1' !== self::$subscribed_plan[ 'subscription_status' ] || ! isset( WC()->cart->cart_contents ) || ! is_array( WC()->cart->cart_contents ) ) {
			return ;
		}

		foreach ( WC()->cart->cart_contents as $_cart_item_key => $_cart_item ) {
			if ( ! isset( $_cart_item[ 'product_id' ] ) ) {
				continue ;
			}

			$cart_item_id = $_cart_item[ 'variation_id' ] > 0 ? $_cart_item[ 'variation_id' ] : $_cart_item[ 'product_id' ] ;
			if ( $cart_item_id === $product_id && 'yes' !== get_transient( "sumosubscriptions_{$_cart_item_key}_addon_fee_saved" ) ) {

				$addon_fee = self::maybe_get_addon_fee( $_cart_item ) ;
				if ( ! $addon_fee ) {
					continue ;
				}

				if ( wc_add_order_item_meta( $order_item_id, 'sumo_subscription_parent_order_item_addon_amount', array( $product_id => $addon_fee ) ) ) {
					set_transient( "sumosubscriptions_{$_cart_item_key}_addon_fee_saved", 'yes', 300 ) ;
					break ;
				}
			}
		}
	}
}

SUMOSubs_WC_TM_Extra_Product_Options::init() ;
