<?php
/**
 * Subscription box content metabox.
 *
 * @since   4.0.0
 * @package YITH\Subscription
 * @var array   $plain_box_content The box items list.
 * @var string  $placeholder_image Product placeholder html.
 * @var boolean $can_edit_product  True if current user can edit product, false otherwise.
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

foreach ( $plain_box_content as $product_id => $quantity ) :
	$product = wc_get_product( $product_id );
	?>
	<tr class="ywsbs-box-content-item">
		<td class="thumb">
			<div class="wc-order-item-thumbnail">
				<?php echo $product ? $product->get_image( 'thumbnail' ) : $placeholder_image; // phpcs:ignore
				?>
			</div>
		</td>
		<td class="name">
			<?php if ( $can_edit_product && $product ) : ?>
				<a href="<?php echo esc_url( get_edit_post_link( $product->get_id() ) ); ?>" target="_blank"><?php echo esc_html( $product->get_name() ); ?></a>
			<?php else : ?>
				<?php
				// Translators: %d is the product ID.
				echo esc_html( $product ? $product->get_name() : sprintf( __( 'Product ID #%d', 'yith-woocommerce-subscription' ), $product_id ) );
				?>
			<?php endif; ?>
			<?php do_action( 'ywsbs_box_item_after_product_title', $product ); ?>
		</td>
		<td class="item_cost" width="1%">&nbsp;</td>
		<td class="quantity" width="1%"><small class="times">×</small><?php echo esc_html( $quantity ); ?></td>
		<td class="line_cost" width="1%">&nbsp;</td>
		<td class="line_tax" width="1%">&nbsp;</td>
		<td class="wc-order-edit-line-item"></td>
	</tr>
	<?php
endforeach;
