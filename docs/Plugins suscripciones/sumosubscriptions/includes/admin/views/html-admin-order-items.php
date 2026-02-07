<?php
/**
 * Order items HTML for meta box.
 */
defined( 'ABSPATH' ) || exit ;

global $wpdb ;

$payment_gateway     = wc_get_payment_gateway_by_order( $order ) ;
$line_items          = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', 'line_item' ) ) ;
$discounts           = $order->get_items( 'discount' ) ;
$line_items_fee      = $order->get_items( 'fee' ) ;
$line_items_shipping = $order->get_items( 'shipping' ) ;
$subscription_item   = false ;

if ( wc_tax_enabled() ) {
	$order_taxes      = $order->get_taxes() ;
	$tax_classes      = WC_Tax::get_tax_classes() ;
	$classes_options  = wc_get_product_tax_class_options() ;
	$show_tax_columns = 1 === count( $order_taxes ) ;
}
?>
<div class="woocommerce_order_items_wrapper">
	<table cellpadding="0" cellspacing="0" class="woocommerce_order_items">
		<thead>
			<tr>
				<th class="item sortable" colspan="2" data-sort="string-ins"><?php esc_html_e( 'Item', 'woocommerce' ) ; ?></th>
				<th class="item_cost sortable" data-sort="float"><?php esc_html_e( 'Cost', 'woocommerce' ) ; ?></th>
				<th class="quantity sortable" data-sort="int"><?php esc_html_e( 'Qty', 'woocommerce' ) ; ?></th>
				<th class="line_cost sortable" data-sort="float"><?php esc_html_e( 'Total', 'woocommerce' ) ; ?></th>
				<?php
				if ( ! empty( $order_taxes ) ) :
					foreach ( $order_taxes as $tax_id => $tax_item ) :
						$tax_class      = wc_get_tax_class_by_tax_id( $tax_item[ 'rate_id' ] ) ;
						$tax_class_name = isset( $classes_options[ $tax_class ] ) ? $classes_options[ $tax_class ] : __( 'Tax', 'woocommerce' ) ;
						$column_label   = ! empty( $tax_item[ 'label' ] ) ? $tax_item[ 'label' ] : __( 'Tax', 'woocommerce' ) ;
						/* translators: %1$s: tax item name %2$s: tax class name  */
						$column_tip     = sprintf( esc_html__( '%1$s (%2$s)', 'woocommerce' ), $tax_item[ 'name' ], $tax_class_name ) ;
						?>
						<th class="line_tax tips" data-tip="<?php echo esc_attr( $column_tip ) ; ?>">
							<?php echo esc_attr( $column_label ) ; ?>
						</th>
						<?php
					endforeach ;
				endif ;
				?>
				<th class="wc-order-edit-line-item" width="1%">&nbsp;</th>
			</tr>
		</thead>
		<tbody id="order_line_items">
			<?php
			if ( $is_order_subscription || sumosubs_is_renewal_order( $order ) ) {
				foreach ( $line_items as $item_id => $item ) {
					include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-item.php' ;
				}
			} else {
				foreach ( $line_items as $item_id => $item ) {
					$product_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;
					if ( $product_id != $subscription_plan[ 'subscription_product_id' ] ) {
						continue ;
					}

					$subscription_item = $item ;
					include 'html-admin-order-item.php' ;
				}
			}
			?>
		</tbody>
		<?php if ( $is_order_subscription || sumosubs_is_renewal_order( $order ) ) : ?>
			<tbody id="order_fee_line_items">
				<?php
				foreach ( $line_items_fee as $item_id => $item ) {
					include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-fee.php' ;
				}
				?>
			</tbody>
			<tbody id="order_shipping_line_items">
				<?php
				$shipping_methods = WC()->shipping() ? WC()->shipping()->load_shipping_methods() : array() ;
				foreach ( $line_items_shipping as $item_id => $item ) {
					include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-shipping.php' ;
				}
				?>
			</tbody>
		<?php endif ; ?>
	</table>
</div>
<div class="wc-order-data-row wc-order-totals-items">
	<?php
	$coupons = $order->get_items( 'coupon' ) ;
	if ( ( $is_order_subscription || sumosubs_is_renewal_order( $order ) ) && $coupons ) :
		?>
		<div class="wc-used-coupons">
			<ul class="wc_coupon_list">
				<li><strong><?php esc_html_e( 'Coupon(s)', 'woocommerce' ) ; ?></strong></li>
				<?php
				foreach ( $coupons as $item_id => $item ) :
					$_post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1;", $item->get_code() ) ) ;
					?>
					<li class="code">
						<?php if ( $_post_id ) : ?>
							<?php
							$post_url = apply_filters( 'woocommerce_admin_order_item_coupon_url', add_query_arg( array( 'post' => $_post_id, 'action' => 'edit' ), admin_url( 'post.php' ) ), $item, $order ) ;
							?>
							<a href="<?php echo esc_url( $post_url ) ; ?>" class="tips" data-tip="<?php echo esc_attr( wc_price( $item->get_discount(), array( 'currency' => $order->get_currency() ) ) ) ; ?>">
								<span><?php echo esc_html( $item->get_code() ) ; ?></span>
							</a>
						<?php else : ?>
							<span class="tips" data-tip="<?php echo esc_attr( wc_price( $item->get_discount(), array( 'currency' => $order->get_currency() ) ) ) ; ?>">
								<span><?php echo esc_html( $item->get_code() ) ; ?></span>
							</span>
						<?php endif ; ?>                        
					</li>
				<?php endforeach ; ?>
			</ul>
		</div>
	<?php endif ; ?>
	<table class="wc-order-totals">
		<tr>
			<td class="label"><?php esc_html_e( 'Items Subtotal:', 'woocommerce' ) ; ?></td>
			<td width="1%"></td>
			<td class="total">
				<?php
				if ( $subscription_item ) {
					echo wp_kses_post( wc_price( $order->get_line_subtotal( $subscription_item ), array( 'currency' => $order->get_currency() ) ) ) ;
				} else {
					echo wp_kses_post( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ) ;
				}
				?>
			</td>
		</tr>
		<?php if ( 0 < $order->get_total_discount() ) : ?>
			<tr>
				<td class="label"><?php esc_html_e( 'Coupon(s):', 'woocommerce' ) ; ?></td>
				<td width="1%"></td>
				<td class="total">
					<?php
					if ( $subscription_item ) {
						echo wp_kses_post( wc_price( '-' . wc_format_decimal( $subscription_item->get_subtotal() - $subscription_item->get_total(), '' ), array( 'currency' => $order->get_currency() ) ) ) ;
					} else {
						echo wp_kses_post( wc_price( '-' . $order->get_total_discount(), array( 'currency' => $order->get_currency() ) ) ) ;
					}
					?>
				</td>
			</tr>
		<?php endif ; ?>
		<?php if ( $is_order_subscription || sumosubs_is_renewal_order( $order ) ) : ?>
			<?php if ( is_callable( array( $order, 'get_total_fees' ) ) && 0 < $order->get_total_fees() ) : ?>
				<tr>
					<td class="label"><?php esc_html_e( 'Fees:', 'woocommerce' ) ; ?></td>
					<td width="1%"></td>
					<td class="total">
						<?php echo wp_kses_post( wc_price( $order->get_total_fees(), array( 'currency' => $order->get_currency() ) ) ) ; ?>
					</td>
				</tr>
			<?php endif ; ?>
			<?php if ( $order->get_shipping_methods() ) : ?>
				<tr>
					<td class="label"><?php esc_html_e( 'Shipping:', 'woocommerce' ) ; ?></td>
					<td width="1%"></td>
					<td class="total">
						<?php echo wp_kses_post( wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) ) ; ?>
					</td>
				</tr>
			<?php endif ; ?>
		<?php endif ; ?>
		<?php if ( wc_tax_enabled() ) : ?>
			<?php foreach ( $order->get_tax_totals() as $code => $tax_total ) : ?>
				<tr>
					<td class="label"><?php echo esc_html( $tax_total->label ) ; ?>:</td>
					<td width="1%"></td>
					<td class="total">
						<?php
						if ( $subscription_item ) {
							echo wp_kses_post( wc_price( $order->get_line_tax( $subscription_item ), array( 'currency' => $order->get_currency() ) ) ) ;
							break ;
						} else {
							echo wp_kses_post( wc_price( wc_round_tax_total( $tax_total->amount ), array( 'currency' => $order->get_currency() ) ) ) ;
						}
						?>
					</td>
				</tr>
			<?php endforeach ; ?>
		<?php endif ; ?>
		<tr>
			<td class="label"><?php esc_html_e( 'Order Total', 'woocommerce' ) ; ?>:</td>
			<td width="1%"></td>
			<td class="total">
				<div class="view">
					<?php
					if ( $subscription_item ) {
						echo wp_kses_post( wc_price( $order->get_line_total( $subscription_item, true ), array( 'currency' => $order->get_currency() ) ) ) ;
					} else {
						echo wp_kses_post( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ) ;
					}
					?>
										
				</div>
			</td>
		</tr>
	</table>
	<div class="clear"></div>
</div>
