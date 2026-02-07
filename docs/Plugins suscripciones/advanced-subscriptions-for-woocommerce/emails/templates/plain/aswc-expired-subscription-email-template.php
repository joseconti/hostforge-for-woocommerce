<?php
/**
 * Expired Email template
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
echo esc_html( $email_heading ) . "\n\n"; // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<?php /* translators: %s: subscription ID */ ?>
<p><?php printf( esc_html__( 'A subscription [#%s] has been expired. Their subscription\'s details are as follows:', 'advanced-subscriptions-for-woocommerce' ), esc_html( $aswc_subscription ) ); ?></p>

<?php
$aswc_product_name = aswc_get_meta_data( $aswc_subscription, 'product_name', true );
$product_qty       = aswc_get_meta_data( $aswc_subscription, 'product_qty', true );

?>
<table>
	<tr>
		<td><?php esc_html_e( 'Product', 'advanced-subscriptions-for-woocommerce' ); ?></td>
				<td><?php echo esc_html( $aswc_product_name ); ?> </td>
	</tr>
	<tr>
		<td> <?php esc_html_e( 'Quantity', 'advanced-subscriptions-for-woocommerce' ); ?> </td>
		<td> <td><?php echo esc_html( $product_qty ); ?> </td> </td>
	</tr>
	<tr>
		<td> <?php esc_html_e( 'Price', 'advanced-subscriptions-for-woocommerce' ); ?> </td>
				<td> <?php do_action( 'aswc_display_susbcription_recerring_total_account_page', $aswc_subscription ); ?> </td>
	</tr>
</table>
<?php
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped
