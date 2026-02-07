<?php
/**
 * Service terminated email template.
 *
 * Override: copy to theme/hostforge/emails/service-terminated.php
 *
 * @package HostForge\Templates\Emails
 * @var string $domain        Service domain name.
 * @var string $customer_name Customer display name.
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
		esc_html__( 'Your hosting service for %s has been terminated.', 'hostforge' ),
		'<strong>' . esc_html( $domain ) . '</strong>'
	);
	?>
</p>

<p><?php esc_html_e( 'All associated data, files, databases and email accounts have been permanently removed from the server. This action cannot be reversed.', 'hostforge' ); ?></p>

<p><?php esc_html_e( 'If you believe this was done in error, please contact our support team immediately.', 'hostforge' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );
