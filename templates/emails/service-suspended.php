<?php
/**
 * Service suspended email template.
 *
 * Override: copy to theme/hostforge/emails/service-suspended.php
 *
 * @package HostForge\Templates\Emails
 * @var string $domain        Service domain name.
 * @var string $customer_name Customer display name.
 * @var string $reason        Suspension reason.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	printf(
		/* translators: %s: customer first name */
		esc_html__( 'Hi %s,', 'hostforge' ),
		esc_html( $customer_name )
	);
	?>
</p>

<p>
	<?php
	printf(
		/* translators: %s: domain name */
		esc_html__( 'Your hosting service for %s has been suspended.', 'hostforge' ),
		'<strong>' . esc_html( $domain ) . '</strong>'
	);
	?>
</p>

<?php if ( ! empty( $reason ) ) : ?>
<p>
	<?php
	printf(
		/* translators: %s: suspension reason */
		esc_html__( 'Reason: %s', 'hostforge' ),
		esc_html( $reason )
	);
	?>
</p>
<?php endif; ?>

<p><?php esc_html_e( 'While suspended, your website and email will not be accessible. Your data is preserved and the service can be reactivated once payment is received.', 'hostforge' ); ?></p>

<p><?php esc_html_e( 'To reactivate your service, please log in to your account and make a payment, or contact our support team.', 'hostforge' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );
