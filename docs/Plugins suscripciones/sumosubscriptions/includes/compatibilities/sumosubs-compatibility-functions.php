<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Remove unnecessary notice under My Subscriptions > View Subscription
 */
if ( ! function_exists( 'sumosubs_remove_wc_invalid_subscription_notice' ) ) {

	function sumosubs_remove_wc_invalid_subscription_notice( $subscription_id ) {
		if ( function_exists( 'sumo_is_subscription_exists' ) && sumo_is_subscription_exists( $subscription_id ) ) {
			remove_action( 'woocommerce_account_view-subscription_endpoint', 'WCS_Template_Loader::get_view_subscription_template' ) ;
		}
	}

	add_action( 'woocommerce_account_view-subscription_endpoint', 'sumosubs_remove_wc_invalid_subscription_notice', 0 ) ;
}

/**
 * RightPress WC Dynamic Discounts -- Fixed Memory Exhaust Issue in Product page
 */
if ( ! function_exists( 'sumosubs_rp_dynamic_discounts_compat_remove_filter_get_sale_price' ) ) {

	function sumosubs_rp_dynamic_discounts_compat_remove_filter_get_sale_price( $price ) {
		if ( class_exists( 'RightPress_Product_Price_Router' ) ) {
			remove_filter( 'woocommerce_product_get_sale_price', array( RightPress_Product_Price_Router::get_instance(), 'route_get_price_call' ), RightPress_Help::get_php_int_min() ) ;
		}

		return $price ;
	}

	add_filter( 'woocommerce_product_get_price', 'sumosubs_rp_dynamic_discounts_compat_remove_filter_get_sale_price', 95 ) ;
	add_filter( 'woocommerce_product_variation_get_price', 'sumosubs_rp_dynamic_discounts_compat_remove_filter_get_sale_price', 95 ) ;
}

if ( ! function_exists( 'sumosubs_rp_dynamic_discounts_compat_add_filter_get_sale_price' ) ) {

	function sumosubs_rp_dynamic_discounts_compat_add_filter_get_sale_price( $price ) {
		if ( class_exists( 'RightPress_Product_Price_Router' ) ) {
			add_filter( 'woocommerce_product_get_sale_price', array( RightPress_Product_Price_Router::get_instance(), 'route_get_price_call' ), RightPress_Help::get_php_int_min(), 2 ) ;
		}

		return $price ;
	}

	add_filter( 'woocommerce_product_get_price', 'sumosubs_rp_dynamic_discounts_compat_add_filter_get_sale_price', 105 ) ;
	add_filter( 'woocommerce_product_variation_get_price', 'sumosubs_rp_dynamic_discounts_compat_add_filter_get_sale_price', 105 ) ;
}


/**
 * WooCommerce Dynamic Pricing -- Fixed Memory Exhaust Issue in Product page
 */
if ( ! function_exists( 'sumosubs_wc_dynamic_discounts_compat_remove_filter_is_on_sale' ) ) {

	function sumosubs_wc_dynamic_discounts_compat_remove_filter_is_on_sale( $price ) {
		if ( class_exists( 'WC_Dynamic_Pricing' ) ) {
			remove_filter( 'woocommerce_product_is_on_sale', array( WC_Dynamic_Pricing::instance(), 'on_get_product_is_on_sale' ), 10, 2 ) ;
		}

		return $price ;
	}

	add_filter( 'woocommerce_product_get_price', 'sumosubs_wc_dynamic_discounts_compat_remove_filter_is_on_sale', 95 ) ;
	add_filter( 'woocommerce_product_variation_get_price', 'sumosubs_wc_dynamic_discounts_compat_remove_filter_is_on_sale', 95 ) ;
}


if ( ! function_exists( 'sumosubs_wc_dynamic_discounts_compat_add_filter_is_on_sale' ) ) {

	function sumosubs_wc_dynamic_discounts_compat_add_filter_is_on_sale( $price ) {
		if ( class_exists( 'WC_Dynamic_Pricing' ) ) {
			add_filter( 'woocommerce_product_is_on_sale', array( WC_Dynamic_Pricing::instance(), 'on_get_product_is_on_sale' ), 10, 2 ) ;
		}

		return $price ;
	}

	add_filter( 'woocommerce_product_get_price', 'sumosubs_wc_dynamic_discounts_compat_add_filter_is_on_sale', 105 ) ;
	add_filter( 'woocommerce_product_variation_get_price', 'sumosubs_wc_dynamic_discounts_compat_add_filter_is_on_sale', 105 ) ;
}
