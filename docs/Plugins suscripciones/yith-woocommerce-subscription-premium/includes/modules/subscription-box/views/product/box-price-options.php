<?php
/**
 * Subscription boc product price options template
 *
 * @since 4.0.0
 * @package YITH\Subscription
 * @var array $price_types An array of product price options.
 * @var string $price_type Current product price option.
 * @var string $price Current product price.
 * @var string $discount_enabled If product discount is enabled.
 * @var array $discount_types An array of product discount options.
 * @var string $discount_type The product discount type.
 * @var string $discount_value The product discount value.
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>

<div class="ywsbs-subscription-box-price-options show_if_ywsbs-subscription-box">

	<div class="ywsbs-product-metabox-field">
		<label for="_ywsbs_box_price_type"><?php esc_html_e( 'Box price method', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<select id="_ywsbs_box_price_type" name="_ywsbs_box_price_type">
					<?php foreach ( $price_types as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $price_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</div>

	<div class="ywsbs-product-metabox-field" data-deps-on="_ywsbs_box_price_type" data-deps-val="fixed">
		<label for="_ywsbs_box_price"><?php esc_html_e( 'Box price', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<input type="text" class="wc_input_price" name="_ywsbs_box_price" id="_ywsbs_box_price" value="<?php echo esc_attr( $price ); ?>"/>
				<span><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
			</div>
		</div>
	</div>

	<div class="ywsbs-product-metabox-field">
		<label for="_ywsbs_box_discount"><?php esc_html_e( 'Offer discount', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<?php
				$args = array(
					'type'  => 'onoff',
					'id'    => '_ywsbs_box_discount',
					'name'  => '_ywsbs_box_discount',
					'value' => $discount_enabled,
				);
				yith_plugin_fw_get_field( $args, true );
				?>
			</div>
		</div>
	</div>

	<div class="ywsbs-product-metabox-field" data-deps-on="_ywsbs_box_discount" data-deps-val="yes">
		<label for="_ywsbs_box_discount_value"><?php esc_html_e( 'Discount amount', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<input type="text" class="wc_input_price ywsbs-short" name="_ywsbs_box_discount_value" id="_ywsbs_box_discount_value" value="<?php echo esc_attr( $discount_value ); ?>"/>
				<select name="_ywsbs_box_discount_type" id="_ywsbs_box_discount_type">
					<?php foreach ( $discount_types as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $discount_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</div>

</div>
