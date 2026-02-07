<?php
/**
 * Scheduler endpoints handler.
 *
 * @package    Advanced_Subscriptions_For_Woocommerce
 * @subpackage Advanced_Subscriptions_For_Woocommerce/package/rest-api/version1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ASWC_Api_Scheduler
 */
class ASWC_Api_Scheduler {

	/**
	 * Retrieve scheduled actions for a subscription.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function aswc_get_scheduled_actions( $request ) {
		$subscription_id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;

		if ( 0 === $subscription_id ) {
			$response = array(
				'status'  => 'error',
				'code'    => 400,
				'message' => __( 'Invalid subscription ID', 'advanced-subscriptions-for-woocommerce' ),
			);
			return new WP_REST_Response( $response, 400 );
		}

		$subscription = aswc_get_subscription( $subscription_id );
		if ( false === $subscription ) {
			$response = array(
				'status'  => 'error',
				'code'    => 404,
				'message' => __( 'Subscription not found', 'advanced-subscriptions-for-woocommerce' ),
			);
			return new WP_REST_Response( $response, 404 );
		}

		if ( false === class_exists( 'ASWC_Scheduler_API' ) ) {
			$response = array(
				'status'  => 'error',
				'code'    => 500,
				'message' => __( 'Scheduler API not available', 'advanced-subscriptions-for-woocommerce' ),
			);
			return new WP_REST_Response( $response, 500 );
		}

		$actions   = ASWC_Scheduler_API::get_scheduled_subscription_actions( $subscription );
		$formatted = array();

		foreach ( $actions as $type => $action ) {
			$formatted[ $type ] = array(
				'hook'      => $action->get_hook(),
				'timestamp' => $action->get_schedule()->get_date()->getTimestamp(),
				'group'     => $action->get_group(),
			);
		}

		$response = array(
			'status' => 'success',
			'code'   => 200,
			'data'   => $formatted,
		);

		return new WP_REST_Response( $response );
	}
}
