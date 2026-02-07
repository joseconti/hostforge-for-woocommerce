<?php
/**
 * Warning notice when system detects an url change
 *
 * @package YITH\Subscription
 * @since 3.6.0
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>

<div id="ywsbs-notice-staging" class="notice notice-error woocommerce-message">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'ywsbs_staging' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'yith-woocommerce-subscription' ); ?></a>
	<p>
		<?php printf( '<strong>%s</strong> %s', esc_html__( 'YITH WooCommerce Subscription is in staging mode:', 'yith-woocommerce-subscription' ), esc_html__( 'in this way, you can work with this installation without generating duplicate orders. You can disable the staging mode in YITH > Subscription > General settings > Extra settings.', 'yith-woocommerce-subscription' ) ); ?>
	</p>
</div>
