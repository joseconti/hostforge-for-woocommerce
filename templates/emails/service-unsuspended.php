<?php
/**
 * Service unsuspended email template.
 *
 * Override: copy to theme/hostforge/emails/service-unsuspended.php
 *
 * @package HostForge\Templates\Emails
 * @var string $customer_name Customer display name.
 * @var string $domain        Service domain.
 * @var string $panel_url     Panel login URL.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	printf(
		/* translators: %s: customer name */
		esc_html__( 'Hi %s,', 'hostforge' ),
		esc_html( $customer_name )
	);
	?>
</p>

<p>
	<?php
	printf(
		/* translators: %s: domain name */
		esc_html__( 'Great news! Your hosting service for %s has been reactivated and is back online.', 'hostforge' ),
		'<strong>' . esc_html( $domain ) . '</strong>'
	);
	?>
</p>

<?php if ( ! empty( $panel_url ) ) : ?>
	<p>
		<?php
		printf(
			/* translators: %s: panel URL */
			esc_html__( 'You can access your control panel at: %s', 'hostforge' ),
			'<a href="' . esc_url( $panel_url ) . '">' . esc_html( $panel_url ) . '</a>'
		);
		?>
	</p>
<?php endif; ?>

<p><?php esc_html_e( 'If you have any questions, please contact our support team.', 'hostforge' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );
