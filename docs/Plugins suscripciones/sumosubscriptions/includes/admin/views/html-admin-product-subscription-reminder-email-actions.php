<?php
switch ( $product->get_type() ) {
	case 'variable':
		?>
		<table>
			<tbody>
				<?php
				foreach ( $subscription_variation as $variation_id ) {
					$_variation                = wc_get_product( $variation_id );
					$reminder_emails           = get_post_meta( $variation_id, 'sumosubs_send_payment_reminder_email', true );
					$order_confirmation_emails = get_post_meta( $variation_id, 'sumosubs_send_order_confirmation_email', true );
					?>
					<tr>
						<th><?php echo wp_kses_post( $_variation->get_formatted_name() ); ?></th>
					</tr>
					<tr>
						<td>
							<div>
								<input type="checkbox" name="sumosubs_send_payment_reminder_email[<?php echo esc_attr( $variation_id ); ?>][auto]" value="yes" <?php checked( 'yes', ( '' === $reminder_emails || ( isset( $reminder_emails[ 'auto' ] ) && 'yes' === $reminder_emails[ 'auto' ] ) ? 'yes' : '' ) ); ?>/>
								<label>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=sumosubs_subscription_auto_renewal_reminder_email' ) ); ?>"><?php esc_html_e( 'Subscription Automatic Renewal Reminder', 'sumosubscriptions' ); ?></a>
								</label>
							</div>
							<div>
								<input type="checkbox" name="sumosubs_send_payment_reminder_email[<?php echo esc_attr( $variation_id ); ?>][manual]" value="yes" <?php checked( 'yes', ( '' === $reminder_emails || ( isset( $reminder_emails[ 'manual' ] ) && 'yes' === $reminder_emails[ 'manual' ] ) ? 'yes' : '' ) ); ?>/>
								<label>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=sumosubs_subscription_invoice_email' ) ); ?>"><?php esc_html_e( 'Subscription Invoice - Manual', 'sumosubscriptions' ); ?></a>
								</label>
							</div>
							<div>
								<input type="checkbox" name="sumosubs_send_order_confirmation_email[<?php echo esc_attr( $variation_id ); ?>][processing]" value="yes" <?php checked( 'yes', ( '' === $order_confirmation_emails || ( isset( $order_confirmation_emails[ 'processing' ] ) && 'yes' === $order_confirmation_emails[ 'processing' ] ) ? 'yes' : '' ) ); ?>/>
								<label>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=sumosubs_subscription_order_processing_email' ) ); ?>"><?php esc_html_e( 'Subscription Processing Order', 'sumosubscriptions' ); ?></a>
								</label>
							</div>
							<div>
								<input type="checkbox" name="sumosubs_send_order_confirmation_email[<?php echo esc_attr( $variation_id ); ?>][completed]" value="yes" <?php checked( 'yes', ( '' === $order_confirmation_emails || ( isset( $order_confirmation_emails[ 'completed' ] ) && 'yes' === $order_confirmation_emails[ 'completed' ] ) ? 'yes' : '' ) ); ?>/>
								<label>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=sumosubs_subscription_order_completed_email' ) ); ?>"><?php esc_html_e( 'Subscription Completed Order', 'sumosubscriptions' ); ?></a>
								</label>
							</div>
							<input name="sumo_subscription_product_ids[]" type="hidden" value="<?php echo esc_attr( $variation_id ); ?>">
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php
		break;
	default:
		$reminder_emails           = get_post_meta( $product->get_id(), 'sumosubs_send_payment_reminder_email', true );
		$order_confirmation_emails = get_post_meta( $product->get_id(), 'sumosubs_send_order_confirmation_email', true );
		?>
		<div>
			<input type="checkbox" name="sumosubs_send_payment_reminder_email[<?php echo esc_attr( $product->get_id() ); ?>][auto]" value="yes" <?php checked( 'yes', ( '' === $reminder_emails || ( isset( $reminder_emails[ 'auto' ] ) && 'yes' === $reminder_emails[ 'auto' ] ) ? 'yes' : '' ) ); ?>/>
			<label>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=sumosubs_subscription_auto_renewal_reminder_email' ) ); ?>"><?php esc_html_e( 'Subscription Automatic Renewal Reminder', 'sumosubscriptions' ); ?></a>
			</label>
		</div>
		<div>
			<input type="checkbox" name="sumosubs_send_payment_reminder_email[<?php echo esc_attr( $product->get_id() ); ?>][manual]" value="yes" <?php checked( 'yes', ( '' === $reminder_emails || ( isset( $reminder_emails[ 'manual' ] ) && 'yes' === $reminder_emails[ 'manual' ] ) ? 'yes' : '' ) ); ?>/>
			<label>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=sumosubs_subscription_invoice_email' ) ); ?>"><?php esc_html_e( 'Subscription Invoice - Manual', 'sumosubscriptions' ); ?></a>
			</label>
		</div>
		<div>
			<input type="checkbox" name="sumosubs_send_order_confirmation_email[<?php echo esc_attr( $product->get_id() ); ?>][processing]" value="yes" <?php checked( 'yes', ( '' === $order_confirmation_emails || ( isset( $order_confirmation_emails[ 'processing' ] ) && 'yes' === $order_confirmation_emails[ 'processing' ] ) ? 'yes' : '' ) ); ?>/>
			<label>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=sumosubs_subscription_order_processing_email' ) ); ?>"><?php esc_html_e( 'Subscription Processing Order', 'sumosubscriptions' ); ?></a>
			</label>
		</div>
		<div>
			<input type="checkbox" name="sumosubs_send_order_confirmation_email[<?php echo esc_attr( $product->get_id() ); ?>][completed]" value="yes" <?php checked( 'yes', ( '' === $order_confirmation_emails || ( isset( $order_confirmation_emails[ 'completed' ] ) && 'yes' === $order_confirmation_emails[ 'completed' ] ) ? 'yes' : '' ) ); ?>/>
			<label>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=sumosubs_subscription_order_completed_email' ) ); ?>"><?php esc_html_e( 'Subscription Completed Order', 'sumosubscriptions' ); ?></a>
			</label>
		</div>
		<input name="sumo_subscription_product_ids[]" type="hidden" value="<?php echo esc_attr( $product->get_id() ); ?>">
		<?php
		break;
}
