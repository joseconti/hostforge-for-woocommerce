<?php
/**
 * Customer Invoive Email template
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo esc_html( $email_heading ) . "\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'advanced-subscriptions-for-woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";

if ( $order->has_status( 'pending' ) ) {
	// translators: %1$s: name of the blog, %2$s: link to checkout payment url, note: no full stop due to url at the end.
	printf( esc_html_x( 'An order has been created for you to renew your subscription on %1$s. To pay for this invoice please use the following link: %2$s', 'In customer renewal invoice email', 'advanced-subscriptions-for-woocommerce' ), esc_html( get_bloginfo( 'name' ) ), esc_attr( $order->get_checkout_payment_url() ) ) . "\n\n";
}

echo "\n\n=-=-=-=-=-=-=-=-=-=\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped
