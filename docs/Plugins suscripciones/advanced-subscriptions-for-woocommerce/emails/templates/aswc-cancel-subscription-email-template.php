<?php
/**
 * Cancelled Email template
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<?php /* translators: %s: subscription ID */ ?>
<p><?php printf( esc_html__( 'A subscription [#%s] has been cancelled. Their subscription\'s details are as follows:', 'advanced-subscriptions-for-woocommerce' ), esc_html( $aswc_subscription ) ); ?></p>

<?php
aswc_email_subscriptions_details( $aswc_subscription );

do_action( 'woocommerce_email_footer', $email );
