<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

echo '= ' . wp_kses_post( $email_heading ) . ' =</br>' ;

/* translators: 1: subscription number 2: cancelled method 3: scheduled date */
echo sprintf( wp_kses_post( __( 'Your request to cancel your subscription #%1$s has been submitted successfully. <br><strong>Cancel Method: </strong> %2$s(%3$s). <br>', 'sumosubscriptions' ) ), esc_html( sumo_get_subscription_number( $post_id ) ), esc_html( $requested_cancel_method ), esc_html( $cancel_scheduled_on ) ) . "\n\n" ;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ) ;

echo esc_html__( 'Subscription Details are shown below for your reference', 'sumosubscriptions' ) . "\n" ;

/* translators: 1: subscription number */
echo sprintf( esc_html__( 'Subscription #%s', 'sumosubscriptions' ), esc_html( sumo_get_subscription_number( $post_id ) ) ) . '</br>' ;

echo '(' . esc_html( date_i18n( __( 'jS F Y', 'sumosubscriptions' ), strtotime( $order->get_date_created() ) ) ) . ')</br>' ;

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ) ;

echo "\n" ;

do_action( 'sumosubscriptions_email_order_details', $order, $post_id, $email ) ;

echo "==========\n\n" ;

do_action( 'sumosubscriptions_email_order_meta', $order, $post_id, $email, true ) ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ) ;

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ) ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ;
