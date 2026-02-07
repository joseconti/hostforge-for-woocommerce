<?php
/**
 * Email template: Domain Expiry Reminder.
 *
 * Sent to the customer when a domain is approaching expiry.
 *
 * @package HostForge
 * @var string $domain_name    Domain name.
 * @var string $customer_name  Customer display name.
 * @var string $expiry_date    Expiry date (formatted).
 * @var int    $days_remaining Days until expiry.
 * @var string $auto_renew     'yes' or 'no'.
 * @var string $manage_url     URL to My Account > My Domains > detail.
 * @var object $email          WC_Email object.
 * @var string $email_heading  Email heading.
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
		/* translators: 1: domain name, 2: number of days, 3: expiry date */
		esc_html__( 'Your domain %1$s will expire in %2$d day(s) on %3$s.', 'hostforge' ),
		'<strong>' . esc_html( $domain_name ) . '</strong>',
		$days_remaining,
		'<strong>' . esc_html( $expiry_date ) . '</strong>'
	);
	?>
</p>

<?php if ( 'yes' === $auto_renew ) : ?>
	<p style="background: #dff0d8; padding: 12px; border-radius: 4px; color: #3c763d;">
		<?php esc_html_e( 'Auto-renewal is enabled for this domain. It will be renewed automatically before expiry.', 'hostforge' ); ?>
	</p>
<?php else : ?>
	<p style="background: #fcf8e3; padding: 12px; border-radius: 4px; color: #8a6d3b;">
		<?php esc_html_e( 'Auto-renewal is NOT enabled for this domain. If you do not renew it manually, it will expire and you may lose it.', 'hostforge' ); ?>
	</p>
<?php endif; ?>

<p>
	<?php esc_html_e( 'You can manage your domain renewal settings from your account:', 'hostforge' ); ?>
</p>

<p style="margin: 20px 0;">
	<a href="<?php echo esc_url( $manage_url ); ?>"
	   style="background-color: #7f54b3; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
		<?php esc_html_e( 'Manage Domain', 'hostforge' ); ?>
	</a>
</p>

<?php
do_action( 'woocommerce_email_footer', $email );
