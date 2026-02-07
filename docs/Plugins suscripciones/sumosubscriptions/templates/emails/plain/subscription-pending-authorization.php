<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

echo '= ' . wp_kses_post( $email_heading ) . ' =</br>' ;

if ( $order->has_status( 'pending' ) ) {

	/* translators: 1: subscription number 2: pay url 3: due date 4: pending status */
	echo sprintf( wp_kses_post( __( 'Your Subscription #%1$s is in Pending Authorization status because we couldn\'t charge your account for Subscription Renewal as your bank have declined the authorization which you have previously given. Please pay using another card or else using any other payment gateway <a href="%2$s">pay</a>. If payment is not made for the Subscription Renewal by <b>%3$s</b>. Your Subscription will move to <b>%4$s</b> status.', 'sumosubscriptions' ) ), esc_html( sumo_get_subscription_number( $post_id ) ), esc_url( $order->get_checkout_payment_url() ), esc_html( $upcoming_mail_date ), esc_html( $upcoming_mail_status ) ) . "\n\n" ;
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
