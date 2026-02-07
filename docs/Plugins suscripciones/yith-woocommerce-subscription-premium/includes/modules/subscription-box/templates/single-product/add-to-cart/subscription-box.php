<?php
/**
 * Subscription box product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/subscription-box.php.
 *
 * @package YITH\Subscription
 * @version 4.0.0
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $product ); // WPCS: XSS ok.

if ( $product->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<button id="ywsbs-box-setup-trigger" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>">
		<?php echo esc_html( $product->single_add_to_cart_text() ); ?>
	</button>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

<?php endif; ?>
