<?php
/**
 * Subscription box content email
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-invoice.php.
 *
 * @package YITH\Subscription
 * @version 4.0.0
 * @var WC_Email $email The email instance.
 * @var string $email_heading The email heading.
 * @var string $additional_content The email additional content.
 * @var string $delivery_date The subscription delivery date id Delivery Schedules module is active for subscription.
 * @var array $box_content The subscription box content.
 * @var YWSBS_Subscription $subscription The subscription object.
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Executes the e-mail header.
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
	<?php
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'yith-woocommerce-subscription' ), esc_html( $subscription->get_billing_first_name() ) );
	?>
</p>
<p>
	<?php
	echo esc_html(
		sprintf(
		// translators: %1$s is a string, could be one of delivered|renewed, %2$s is a date.
			__( 'We are preparing your next box, which will be %1$s on %2$s', 'yith-woocommerce-subscription' ),
			$delivery_date
			? _x( 'delivered', 'Part of email text "We are preparing your next box, that will be delivered on 01,Jan 2000"', 'yith-woocommerce-subscription' )
			: _x( 'renewed', 'Part of email text "We are preparing your next box, that will be renewed on 01,Jan 2000"', 'yith-woocommerce-subscription' ),
			$delivery_date ?: date_i18n( wc_date_format(), $subscription->get_payment_due_date() ) // phpcs:ignore
		)
	);
	?>
</p>
<p><?php echo esc_html__( 'Take a look at the fabulous items you will get:', 'yith-woocommerce-subscription' ); ?></p>
<div style="margin-bottom: 40px;">
	<?php foreach ( $box_content as $step ) : ?>
	<h3><?php echo esc_html( $step['label'] ); ?></h3>
		<?php foreach ( $step['items'] as $item ) : ?>
			<div style="margin-bottom: 10px;">
				<span><img src="<?php echo esc_url( $item['image'] ); ?>" width="60" height="60" alt="<?php echo esc_attr( $item['name'] ); ?>" /></span>
				<span><?php echo esc_html( $item['name'] ); ?></span>
			</div>
		<?php endforeach; ?>
	<?php endforeach; ?>
</div>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/**
 * Executes the email footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
