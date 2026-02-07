<?php
/**
 * Product subscription box data panel.
 *
 * @since   4.0.0
 * @package YITH\Subscription
 * @var array  $box_steps       An array of box creation steps.
 * @var string $enable_price_threshold
 * @var array  $price_threshold An array of price threshold
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>

<div id="subscription_box_data" class="panel woocommerce_options_panel">
	<div class="ywsbs-product-metabox-options-panel yith-plugin-ui options_group">
		<h3 class="ywsbs-title-section"><?php esc_html_e( 'Box creation', 'yith-woocommerce-subscription' ); ?></h3>
		<p class="ywsbs-description-section">
			<?php echo esc_html__( 'You can separate the box creation process into steps and you can choose which products to show to your customers in each step.', 'yith-woocommerce-subscription' ); ?>
		</p>

		<div class="ywsbs-box-steps" data-steps="<?php echo wc_esc_json( json_encode( $box_steps ) ); // phpcs:ignore ?>">
			<span class="yith-plugin-fw__button--add ywsbs-box-step-add"><?php esc_html_e( 'Add step', 'yith-woocommerce-subscription' ); ?></span>
		</div>
	</div>

	<div class="ywsbs-product-metabox-options-panel yith-plugin-ui options_group" data-deps-on="_ywsbs_box_price_type" data-deps-val="sum">
		<h3 class="ywsbs-title-section"><?php esc_html_e( 'Box options', 'yith-woocommerce-subscription' ); ?></h3>

		<div class="ywsbs-product-metabox-field">
			<label for="_ywsbs_box_enable_price_threshold"><?php esc_html_e( 'Set min/max values for the box', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<div class="ywsbs-product-metabox-field-content">
					<?php
					$args = array(
						'type'  => 'onoff',
						'id'    => '_ywsbs_box_enable_price_threshold',
						'name'  => '_ywsbs_box_enable_price_threshold',
						'value' => $enable_price_threshold,
					);
					yith_plugin_fw_get_field( $args, true );
					?>
				</div>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Enable to set min/max values required for customers to order the box.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<div class="ywsbs-product-metabox-field" data-deps-on="_ywsbs_box_enable_price_threshold" data-deps-val="yes">
			<label for="_ywsbs_box_price_threshold"><?php esc_html_e( 'Box value', 'yith-woocommerce-subscription' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</label>
			<div class="ywsbs-product-metabox-field-container">
				<div class="ywsbs-product-metabox-field-content">
					<span><?php esc_html_e( 'Min', 'yith-woocommerce-subscription' ); ?></span>
					<input type="text" class="wc_input_price ywsbs-short" name="_ywsbs_box_price_threshold[min]" id="_ywsbs_box_price_threshold_min" value="<?php echo esc_attr( isset( $price_threshold['min'] ) ? $price_threshold['min'] : '' ); ?>"/>
					<span><?php esc_html_e( 'Max', 'yith-woocommerce-subscription' ); ?></span>
					<input type="text" class="wc_input_price ywsbs-short" name="_ywsbs_box_price_threshold[max]" id="_ywsbs_box_price_threshold_max" value="<?php echo esc_attr( isset( $price_threshold['max'] ) ? $price_threshold['max'] : '' ); ?>"/>
				</div>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Set the minimum amount required to purchase this box. You can also set a maximum amount or leave the field empty if no limit applies.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>
	</div>
</div>


