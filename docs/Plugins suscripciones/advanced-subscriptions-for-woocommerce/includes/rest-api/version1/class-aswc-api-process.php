<?php
/**
 * Fired during plugin activation
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/package/rest-api/version1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! class_exists( 'ASWC_Api_Process' ) ) {

	/**
	 * The plugin API class.
	 *
	 * This is used to define the functions and data manipulation for custom endpoints.
	 *
	 * @since      1.0.0
	 * @package    advanced-subscriptions-for-woocommerce
	 * @subpackage advanced-subscriptions-for-woocommerce/package/rest-api/version1
	 */
	class ASWC_Api_Process {

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 */
		public function __construct() {
		}

		/**
		 * Define the function to process data for custom endpoint.
		 *
		 * @since    1.0.0
		 * @param   Array $aswc_request  data of requesting headers and other information.
		 * @return  Array $aswc_rest_response    returns processed data and status of operations.
		 */
		public function aswc_default_process( $aswc_request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				$aswc_rest_response = array();

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$args              = array(
					'return'     => 'ids',
					'post_type'  => 'aswc_subscriptions',
					'limit'      => -1,
					'status'     => function_exists( 'aswc_get_subscription_statuses_for_query' ) ? aswc_get_subscription_statuses_for_query() : array(),
                                        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query' => array(
						array(
							'key'     => 'aswc_customer_id',
							'compare' => 'EXISTS',
						),
					),
				);
				$aswc_subscriptions = wc_get_orders( $args );
			} else {
								$args              = array(
									'numberposts' => -1,
									'post_type'   => 'aswc_subscriptions',
									'post_status' => function_exists( 'aswc_get_subscription_post_statuses_for_query' ) ? aswc_get_subscription_post_statuses_for_query() : 'any',
                                        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
									'meta_query'  => array(
										array(
											'key'     => 'aswc_customer_id',
											'compare' => 'EXISTS',
										),
									),
								);
								$aswc_subscriptions = get_posts( $args );
			}

			$aswc_subscriptions_data = array();
			if ( isset( $aswc_subscriptions ) && ! empty( $aswc_subscriptions ) && is_array( $aswc_subscriptions ) ) {
				foreach ( $aswc_subscriptions as $key => $value ) {
					if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
						$subcription_id = $value;
					} else {
						$subcription_id = $value->ID;
					}

										$parent_order_id         = aswc_get_meta_data( $subcription_id, 'aswc_parent_order', true );
										$aswc_subscription_status = aswc_get_meta_data( $subcription_id, 'aswc_subscription_status', true );
										$product_name            = aswc_get_meta_data( $subcription_id, 'product_name', true );
										$aswc_recurring_total     = aswc_get_meta_data( $subcription_id, 'aswc_recurring_total', true );

										$aswc_number   = aswc_get_meta_data( $subcription_id, 'aswc_subscription_number', true );
										$aswc_interval = aswc_get_meta_data( $subcription_id, 'aswc_subscription_interval', true );

										$aswc_next_payment_date = aswc_get_meta_data( $subcription_id, 'aswc_next_payment_date', true );
										$aswc_susbcription_end  = aswc_get_meta_data( $subcription_id, 'aswc_susbcription_end', true );

										$aswc_customer_id = aswc_get_meta_data( $subcription_id, 'aswc_customer_id', true );
					$user                                = get_user_by( 'id', $aswc_customer_id );

					$user_nicename            = isset( $user->user_nicename ) ? $user->user_nicename : '';
					$aswc_subscriptions_data[] = array(
						'subscription_id'           => $subcription_id,
						'parent_order_id'           => $parent_order_id,
						'status'                    => $aswc_subscription_status,
						'product_name'              => $product_name,
						'recurring_amount'          => $aswc_recurring_total,
						'interval_number'           => $aswc_number,
						'interval_type'             => $aswc_interval,
						'user_name'                 => $user_nicename,
						'next_payment_date'         => aswc_get_the_wordpress_date_format( $aswc_next_payment_date ),
						'subscriptions_expiry_date' => aswc_get_the_wordpress_date_format( $aswc_susbcription_end ),
					);
				}
			}

			// Write your custom code here.

						$aswc_rest_response['code']   = 200;
						$aswc_rest_response['status'] = 'success';
						$aswc_rest_response['data']   = $aswc_subscriptions_data;
						return $aswc_rest_response;
		}
	}
}
