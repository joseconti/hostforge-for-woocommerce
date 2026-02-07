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

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'advanced-subscriptions-for-woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>

<?php if ( $order->has_status( 'pending' ) ) : ?>
	<p>
	<?php
	echo wp_kses(
		sprintf(
		// translators: %1$s: name of the blog, %2$s: link to checkout payment url, note: no full stop due to url at the end.
			_x( 'An order has been created for you to renew your subscription on %1$s. To pay for this invoice please use the following link: %2$s', 'advanced-subscriptions-for-woocommerce' ),
			esc_html( get_bloginfo( 'name' ) ),
			'<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay Now &raquo;', 'advanced-subscriptions-for-woocommerce' ) . '</a>'
		),
		array( 'a' => array( 'href' => true ) )
	);
	?>
	</p>
<?php endif; ?>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );


do_action( 'woocommerce_email_footer', $email );
