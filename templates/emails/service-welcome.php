<?php
/**
 * Service welcome email template.
 *
 * Override: copy to theme/hostforge/emails/service-welcome.php
 *
 * @package HostForge\Templates\Emails
 * @var string $domain         Service domain name.
 * @var string $username       Panel username.
 * @var string $password       Panel password (plain text).
 * @var string $panel_type     Panel type (cpanel/plesk).
 * @var string $panel_url      Panel login URL.
 * @var string $customer_name  Customer display name.
 * @var string $package        Hosting package name.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook: hostforge_email_header.
 */
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

<p><?php esc_html_e( 'Your hosting account has been created and is ready to use! Here are your login details:', 'hostforge' ); ?></p>

<table cellspacing="0" cellpadding="6" border="1" style="border-collapse:collapse; border:1px solid #e5e5e5; width:100%; margin-bottom:20px;">
	<tr>
		<th style="text-align:left; padding:12px; background:#f8f8f8; width:140px;"><?php esc_html_e( 'Domain', 'hostforge' ); ?></th>
		<td style="padding:12px;"><?php echo esc_html( $domain ); ?></td>
	</tr>
	<tr>
		<th style="text-align:left; padding:12px; background:#f8f8f8;"><?php esc_html_e( 'Username', 'hostforge' ); ?></th>
		<td style="padding:12px;"><code><?php echo esc_html( $username ); ?></code></td>
	</tr>
	<tr>
		<th style="text-align:left; padding:12px; background:#f8f8f8;"><?php esc_html_e( 'Password', 'hostforge' ); ?></th>
		<td style="padding:12px;"><code><?php echo esc_html( $password ); ?></code></td>
	</tr>
	<?php if ( ! empty( $package ) ) : ?>
	<tr>
		<th style="text-align:left; padding:12px; background:#f8f8f8;"><?php esc_html_e( 'Plan', 'hostforge' ); ?></th>
		<td style="padding:12px;"><?php echo esc_html( $package ); ?></td>
	</tr>
	<?php endif; ?>
	<?php if ( ! empty( $panel_url ) ) : ?>
	<tr>
		<th style="text-align:left; padding:12px; background:#f8f8f8;"><?php esc_html_e( 'Control Panel', 'hostforge' ); ?></th>
		<td style="padding:12px;"><a href="<?php echo esc_url( $panel_url ); ?>"><?php echo esc_html( $panel_url ); ?></a></td>
	</tr>
	<?php endif; ?>
</table>

<p><?php esc_html_e( 'For security, we recommend changing your password after your first login.', 'hostforge' ); ?></p>

<p><?php esc_html_e( 'You can manage your hosting services from your account dashboard.', 'hostforge' ); ?></p>

<?php
/**
 * Hook: hostforge_email_footer.
 */
do_action( 'woocommerce_email_footer', $email );
