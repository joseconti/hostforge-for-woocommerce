<?php
/**
 * Order Subscription Form.
 *
 * This template can be overridden by copying it to yourtheme/sumosubscriptions/order-subscription-form.php.
 */
defined( 'ABSPATH' ) || exit;
?>
<table class="shop_table sumo_order_subscription">

	<tr class="sumo_order_subscription_subscribe">
		<td colspan="2">
			<label for="sumo_order_subscription_status"><?php echo wp_kses_post( $subscribe_label ); ?></label>
			<?php if ( ! empty( $user_action ) ) : ?>
				<input type="checkbox" id="sumo_order_subscription_status" <?php checked( 'yes' === $chosen_plan[ 'subscribed' ], true, true ); ?> />
			<?php else : ?>
				<input type="checkbox" id="sumo_order_subscription_status" <?php checked( $options[ 'default_subscribed' ], true, true ); ?> />
			<?php endif; ?>
		</td>
	</tr>

	<?php if ( $options[ 'can_user_select_plan' ] ) { ?>
		<tr class="sumo_order_subscription_subscribe_duration">
			<td><label for="sumo_order_subscription_duration"><?php echo wp_kses_post( $subscription_duration_label ); ?></label></td>
			<td>
				<span class="sumosubs-duration-every"><?php esc_html_e( 'every ', 'sumosubscriptions' ); ?></span>

				<select class="sumosubs-duration-interval" id="sumo_order_subscription_duration_value">
					<?php
					$selected_duration_period = $chosen_plan[ 'duration_period' ] ? $chosen_plan[ 'duration_period' ] : current( $options[ 'duration_period_selector' ] );
					foreach ( sumo_get_subscription_duration_options( $selected_duration_period, false, $options[ 'min_duration_length_user_can_select' ][ $selected_duration_period ], $options[ 'max_duration_length_user_can_select' ][ $selected_duration_period ] ) as $duration_value_key => $label ) {
						?>
						<option value="<?php echo esc_attr( $duration_value_key ); ?>" <?php selected( $duration_value_key, $chosen_plan[ 'duration_length' ], true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
				</select>

				<select class="sumosubs-duration-period" id="sumo_order_subscription_duration">
					<?php
					$duration_period = sumosubs_get_duration_period_selector();

					foreach ( $options[ 'duration_period_selector' ] as $index => $period ) :
						?>
						<option value="<?php echo esc_attr( $period ); ?>" <?php selected( $chosen_plan[ 'duration_period' ], $period, true ); ?>><?php echo esc_html( $duration_period[ $period ] ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>

		<?php if ( $options[ 'can_user_select_recurring_length' ] ) { ?>
			<tr class="sumo_order_subscription_subscribe_length">
				<td><label for="sumo_order_subscription_recurring"><?php echo wp_kses_post( $subscription_length_label ); ?></label></td>
				<td>
					<select class="sumosubs-duration-length" id="sumo_order_subscription_recurring">
						<?php
						if ( '0' === $options[ 'max_recurring_length_user_can_select' ] ) {
							foreach ( sumo_get_subscription_recurring_options( 'last', $options[ 'min_recurring_length_user_can_select' ] ) as $recurring_key => $label ) {
								?>
								<option value="<?php echo esc_attr( $recurring_key ); ?>" <?php selected( $recurring_key, $chosen_plan[ 'recurring_length' ], true ); ?>><?php echo esc_html( $label ); ?></option>
								<?php
							}
						} else {
							foreach ( sumo_get_subscription_recurring_options( false, $options[ 'min_recurring_length_user_can_select' ], $options[ 'max_recurring_length_user_can_select' ] ) as $recurring_key => $label ) {
								?>
								<option value="<?php echo esc_attr( $recurring_key ); ?>" <?php selected( $recurring_key, $chosen_plan[ 'recurring_length' ], true ); ?>><?php echo esc_html( $label ); ?></option>
								<?php
							}
						}
						?>
					</select>
				</td>
			</tr>
		<?php } ?>

	<?php } ?>
</table>
