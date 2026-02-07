<?php
/**
 * Single product template options for delivery schedules
 *
 * @package YITH\Subscription
 * @since   2.2.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Vars used on this template.
 *
 * @var array $delivery_sync Delivery sync data.
 * @var string $override_delivery_schedule Subscription override delivery scheduled.
 */
?>

<h3 class="ywsbs-title-section"><?php esc_html_e( 'Delivery Settings', 'yith-woocommerce-subscription' ); ?></h3>

<div class="ywsbs-product-metabox-field">
	<label for="_ywsbs_override_delivery_schedule"><?php esc_html_e( 'Override the delivery schedule settings', 'yith-woocommerce-subscription' ); ?></label>
	<div class="ywsbs-product-metabox-field-container">
		<?php
		yith_plugin_fw_get_field(
			array(
				'type'  => 'onoff',
				'id'    => '_ywsbs_override_delivery_schedule',
				'name'  => '_ywsbs_override_delivery_schedule',
				'value' => $override_delivery_schedule,
			),
			true
		);
		?>
		<div class="ywsbs-product-metabox-field-description">
			<?php esc_html_e( 'Enable if you want to set a specific delivery schedule for this product.', 'yith-woocommerce-subscription' ); ?>
		</div>
	</div>
</div>

<div data-deps-on="_ywsbs_override_delivery_schedule" data-deps-val="yes">

	<div class="ywsbs-product-metabox-field">
		<label for="_ywsbs_delivery_schedule"><?php esc_html_e( 'Deliver the subscription products', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<span><?php esc_html_e( 'Every', 'yith-woocommerce-subscription' ); ?></span>
				<input type="number" class="ywsbs-short" name="_ywsbs_delivery_synch[delivery_gap]" id="_ywsbs_delivery_synch_delivery_gap" value="<?php echo esc_attr( $delivery_sync['delivery_gap'] ); ?>"/>
				<select id="_ywsbs_delivery_synch_delivery_period" name="_ywsbs_delivery_synch[delivery_period]" class="select ywsbs-with-margin yith-short-select single-delivery-period">
					<?php foreach ( ywsbs_get_time_options() as $key => $value ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $delivery_sync['delivery_period'], $key ); ?>><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="ywsbs-product-metabox-field-description">
				<?php esc_html_e( 'Set a delivery schedule for this product.', 'yith-woocommerce-subscription' ); ?>
			</div>
		</div>
	</div>

	<div class="ywsbs-product-metabox-field">
		<label for="_ywsbs_delivery_synch_on"><?php esc_html_e( 'Synchronize delivery schedules', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<?php
			yith_plugin_fw_get_field(
				array(
					'type'  => 'onoff',
					'id'    => '_ywsbs_delivery_synch_on',
					'name'  => '_ywsbs_delivery_synch[on]',
					'value' => $delivery_sync['on'],
				),
				true
			);
			?>
			<div class="ywsbs-product-metabox-field-description">
				<?php esc_html_e( 'Enable if you want to ship the product on a specific day.', 'yith-woocommerce-subscription' ); ?>
			</div>
		</div>
	</div>

	<div data-deps-on="_ywsbs_delivery_synch[on]" data-deps-val="yes">

		<div class="ywsbs-product-metabox-field" data-deps-on="_ywsbs_delivery_synch[delivery_period]" data-deps-val="weeks|months|years">
			<label for="_ywsbs_delivery_schedule"><?php esc_html_e( 'Synchronize delivery on', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">

				<div class="ywsbs-product-metabox-field-content" data-deps-on="_ywsbs_delivery_synch[delivery_period]" data-deps-val="weeks" data-deps-effect="plain">
					<select id="_ywsbs_delivery_synch_sych_weeks" name="_ywsbs_delivery_synch[sych_weeks]">
						<?php foreach ( ywsbs_get_period_options( 'day_weeks' ) as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $delivery_sync['sych_weeks'], $key ); ?>><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="ywsbs-product-metabox-field-content" data-deps-on="_ywsbs_delivery_synch[delivery_period]" data-deps-val="months" data-deps-effect="plain">
					<select id="_ywsbs_delivery_synch_months" name="_ywsbs_delivery_synch[months]">
						<?php foreach ( ywsbs_get_period_options( 'day_months' ) as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $delivery_sync['months'], $key ); ?>><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
					<span><?php esc_html_e( 'of each month', 'yith-woocommerce-subscription' ); ?></span>
				</div>

				<div class="ywsbs-product-metabox-field-content" data-deps-on="_ywsbs_delivery_synch[delivery_period]" data-deps-val="years" data-deps-effect="plain">
					<select id="_ywsbs_delivery_synch_years_day" name="_ywsbs_delivery_synch[years_day]">
						<?php foreach ( ywsbs_get_period_options( 'day_months' ) as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $delivery_sync['years_day'], $key ); ?>><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
					<select id="_ywsbs_delivery_synch_years_month" name="_ywsbs_delivery_synch[years_month]">
						<?php foreach ( ywsbs_get_period_options( 'months' ) as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $delivery_sync['years_month'], $key ); ?>><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Set a specific day for the delivery schedule.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

	</div>

</div>
