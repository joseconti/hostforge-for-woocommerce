<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

echo '= ' . wp_kses_post( $email_heading ) . ' =</br>' ;

if ( $admin_template ) {
	/* translators: 1: billing first name 2: billing last name */
	echo sprintf( esc_html__( 'You have received a Subscription order from %1$s %2$s. The Subscription order is as follows:', 'sumosubscriptions' ), esc_html( $order->get_billing_first_name() ), esc_html( $order->get_billing_last_name() ) ) . "\n\n" ;
} else {
	/* translators: 1: blog name */
	echo sprintf( esc_html__( 'You have placed a new Subscription order on %s. The Subscription order is as follows:', 'sumosubscriptions' ), esc_html( get_option( 'blogname' ) ) ) . "\n\n" ;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ) ;

/* translators: 1: order number */
echo sprintf( esc_html__( 'Order #%s', 'sumosubscriptions' ), esc_html( $order->get_id() ) ) . "\n" ;

echo '(' . esc_html( date_i18n( __( 'jS F Y', 'sumosubscriptions' ), strtotime( $order->get_date_created() ) ) ) . ')</br>' ;

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ) ;

echo "\n" ;

do_action( 'sumosubscriptions_email_order_details', $order, $post_id, $email ) ;

echo "==========\n\n" ;

do_action( 'sumosubscriptions_email_order_meta', $order, $post_id, $email, true ) ;

/* translators: 1: order edit url */
echo "\n" . sprintf( esc_html__( 'View Order: %s', 'sumosubscriptions' ), esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ) ) . "\n" ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ) ;

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ) ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ;
