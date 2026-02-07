<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

echo '= ' . wp_kses_post( $email_heading ) . ' =</br>' ;

$_link = '' ;
if ( 'yes' === $payment_link ) {
	/* translators: 1: pay url */
	$_link = sprintf( __( 'If you wish to pay using an alternate method, please use the following payment link: %s', 'sumosubscriptions' ), $order->get_checkout_payment_url() ) ;
}

if ( $order->has_status( 'pending' ) ) {
	/* translators: 1: subscription number 2: linking text 3: due date 4: pending status */
	echo sprintf( wp_kses_post( __( 'Your Subscription #%1$s is in Overdue status because we couldn\'t charge your account for Subscription Renewal. Please make sure that you have sufficient funds in your account. <br>%2$s. If payment is not made for the Subscription Renewal by <b>%3$s</b>. Your Subscription will move to <b>%4$s</b> status.', 'sumosubscriptions' ) ), esc_html( sumo_get_subscription_number( $post_id ) ), wp_kses_post( $_link ), esc_html( $upcoming_mail_date ), esc_html( $upcoming_mail_status ) ) . "\n\n" ;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ) ;

/* translators: 1: subscription number */
echo sprintf( esc_html__( 'Subscription #%s', 'sumosubscriptions' ), esc_html( sumo_get_subscription_number( $post_id ) ) ) . '</br>' ;

echo '(' . esc_html( date_i18n( __( 'jS F Y', 'sumosubscriptions' ), strtotime( $order->get_date_created() ) ) ) . ')</br>' ;

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ) ;

echo "\n" ;

do_action( 'sumosubscriptions_email_order_details', $order, $post_id, $email ) ;

echo "==========\n\n" ;

do_action( 'sumosubscriptions_email_order_meta', $order, $post_id, $email, true ) ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ;
