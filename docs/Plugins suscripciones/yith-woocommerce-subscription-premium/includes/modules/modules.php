<?php
/**
 * Modules list.
 *
 * @package YITH\Subscription
 * @since   2.3.0
 * @author  YITH
 */

defined( 'YITH_YWSBS_VERSION' ) || exit;

return array(
	'synchronization'    => array(
		'name'        => _x( 'Renewal synchronization', 'Module name', 'yith-woocommerce-subscription' ),
		'description' => __( 'Synchronize all subscription renewals to easily track your customers’ payments.', 'yith-woocommerce-subscription' ),
	),
	'delivery-schedules' => array(
		'name'        => _x( 'Delivery schedules', 'Module name', 'yith-woocommerce-subscription' ),
		'description' => __( 'Synchronize the delivery of subscription products to easily manage shipping and deliveries.', 'yith-woocommerce-subscription' ),
	),
	'subscription-box'   => array(
		'name'        => _x( 'Subscription box', 'Module name', 'yith-woocommerce-subscription' ),
		'description' => __( 'Configure and sell subscription product boxes to send to your customers on a regular basis.', 'yith-woocommerce-subscription' ),
	),
);
