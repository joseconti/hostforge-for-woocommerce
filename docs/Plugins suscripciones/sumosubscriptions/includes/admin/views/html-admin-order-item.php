<?php
/**
 * Shows an order item.
 */
defined( 'ABSPATH' ) || exit ;

$product               = $item->get_product() ;
$meta_data             = $item->get_formatted_meta_data( '' ) ;
$product_link          = $product ? admin_url( 'post.php?post=' . $item->get_product_id() . '&action=edit' ) : '' ;
$thumbnail             = $product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '' ;
$row_class             = apply_filters( 'woocommerce_admin_html_order_item_class', ! empty( $class ) ? $class : '', $item, $order ) ;
$hidden_order_itemmeta = apply_filters( 'woocommerce_hidden_order_itemmeta', array(
	'_qty',
	'_tax_class',
	'_product_id',
	'_variation_id',
	'_line_subtotal',
	'_line_subtotal_tax',
	'_line_total',
	'_line_tax',
	'method_id',
	'cost',
	'_reduced_stock',
		) ) ;
?>
<tr class="item <?php echo esc_attr( $row_class ) ; ?>" data-order_item_id="<?php echo esc_attr( $item_id ) ; ?>">
	<td class="thumb">
		<?php echo '<div class="wc-order-item-thumbnail">' . wp_kses_post( $thumbnail ) . '</div>' ; ?>
	</td>
	<td class="name" data-sort-value="<?php echo esc_attr( $item->get_name() ) ; ?>">
		<?php
		echo $product_link ? '<a href="' . esc_url( $product_link ) . '" class="wc-order-item-name">' . wp_kses_post( $item->get_name() ) . '</a>' : '<div class="wc-order-item-name">' . wp_kses_post( $item->get_name() ) . '</div>' ;

		if ( $product && $product->get_sku() ) {
			echo '<div class="wc-order-item-sku"><strong>' . esc_html__( 'SKU:', 'woocommerce' ) . '</strong> ' . esc_html( $product->get_sku() ) . '</div>' ;
		}

		if ( $item->get_variation_id() ) {
			echo '<div class="wc-order-item-variation"><strong>' . esc_html__( 'Variation ID:', 'woocommerce' ) . '</strong> ' ;
			if ( 'product_variation' === get_post_type( $item->get_variation_id() ) ) {
				echo esc_html( $item->get_variation_id() ) ;

				SUMOSubs_Variation_Switcher::display( $subscription_id, SUMOSubs_Variation_Switcher::get_matched_attributes( $subscription_id ) ) ;
			} else {
				/* translators: %s: variation id */
				printf( esc_html__( '%s (No longer exists)', 'woocommerce' ), esc_html( $item->get_variation_id() ) ) ;
			}
			echo '</div>' ;
		}
		?>
		<input type="hidden" class="order_item_id" name="order_item_id[]" value="<?php echo esc_attr( $item_id ) ; ?>" />
		<input type="hidden" name="order_item_tax_class[<?php echo absint( $item_id ) ; ?>]" value="<?php echo esc_attr( $item->get_tax_class() ) ; ?>" />
		<div class="view">
			<?php if ( $meta_data ) : ?>
				<table cellspacing="0" class="display_meta">
					<?php
					foreach ( $meta_data as $meta_id => $meta ) :
						if ( in_array( $meta->key, $hidden_order_itemmeta, true ) ) {
							continue ;
						}
						?>
						<tr>
							<th><?php echo wp_kses_post( $meta->display_key ) ; ?>:</th>
							<td><?php echo wp_kses_post( force_balance_tags( $meta->display_value ) ) ; ?></td>
						</tr>
					<?php endforeach ; ?>
				</table>
			<?php endif ; ?>
		</div>
	</td>

	<td class="item_cost" width="1%" data-sort-value="<?php echo esc_attr( $order->get_item_subtotal( $item, false, true ) ) ; ?>">
		<div class="view">
			<?php echo wp_kses_post( wc_price( $order->get_item_subtotal( $item, false, true ), array( 'currency' => $order->get_currency() ) ) ) ; ?>
		</div>
	</td>

	<td class="quantity" width="1%">
		<div class="view">
			<?php echo '<small class="times">&times;</small> ' . esc_html( $item->get_quantity() ) ; ?>
		</div>
	</td>

	<td class="line_cost" width="1%" data-sort-value="<?php echo esc_attr( $item->get_total() ) ; ?>">
		<div class="view">
			<?php
			echo wp_kses_post( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) ) ;

			if ( $item->get_subtotal() !== $item->get_total() ) {
				/* translators: %s: discount amount */
				echo '<span class="wc-order-item-discount">' . sprintf( esc_html__( '%s discount', 'woocommerce' ), wp_kses_post( wc_price( wc_format_decimal( $item->get_subtotal() - $item->get_total(), '' ), array( 'currency' => $order->get_currency() ) ) ) ) . '</span>' ;
			}
			?>
		</div>
	</td>

	<?php
	$tax_data = wc_tax_enabled() ? $item->get_taxes() : false ;
	if ( $tax_data ) :
		foreach ( $order_taxes as $tax_item ) :
			$tax_item_id       = $tax_item->get_rate_id() ;
			$tax_item_total    = isset( $tax_data[ 'total' ][ $tax_item_id ] ) ? $tax_data[ 'total' ][ $tax_item_id ] : '' ;
			$tax_item_subtotal = isset( $tax_data[ 'subtotal' ][ $tax_item_id ] ) ? $tax_data[ 'subtotal' ][ $tax_item_id ] : '' ;

			if ( '' !== $tax_item_subtotal ) {
				$round_at_subtotal = 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ;
				$tax_item_total    = wc_round_tax_total( $tax_item_total, $round_at_subtotal ? wc_get_rounding_precision() : null ) ;
				$tax_item_subtotal = wc_round_tax_total( $tax_item_subtotal, $round_at_subtotal ? wc_get_rounding_precision() : null ) ;
			}
			?>
			<td class="line_tax" width="1%">
				<div class="view">
					<?php
					if ( '' !== $tax_item_total ) {
						echo wp_kses_post( wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $order->get_currency() ) ) ) ;
					} else {
						echo '&ndash;' ;
					}
					?>
				</div>
			</td>
			<?php
		endforeach ;
	endif ;
	?>
	<td class="wc-order-edit-line-item" width="1%"></td>
</tr>
