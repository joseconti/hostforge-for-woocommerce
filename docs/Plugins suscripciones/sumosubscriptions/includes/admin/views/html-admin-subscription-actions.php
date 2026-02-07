<ul class="order_actions submitbox">
	<li class="wide" id="subscription_action">
		<select name="subscription_action" class="wc-enhanced-select wide">
			<option value=""><?php esc_html_e( 'Actions', 'sumosubscriptions' ); ?></option>
			<optgroup label="<?php esc_html_e( 'Resend subscription emails', 'sumosubscriptions' ); ?>">
				<?php
				switch ( $subscription_status ) {
					case 'Pending':
						$available_emails = array( 'subscription_new_order' );
						break;
					case 'Expired':
						$available_emails = array( 'subscription_expired' );
						break;
					case 'Cancelled':
						$available_emails = array( 'subscription_cancelled' );
						break;
					case 'Active':
						if ( $is_invoice_present ) {
							if ( $is_automatic_payment ) {
								$available_emails = array( 'subscription_new_order', 'subscription_order_processing', 'subscription_auto_renewal_success', 'subscription_order_completed' );
							} else {
								$available_emails = array( 'subscription_new_order', 'subscription_order_processing', 'subscription_invoice', 'subscription_order_completed' );
							}
						} else {
							$available_emails = array( 'subscription_new_order', 'subscription_order_processing', 'subscription_order_completed' );
						}
						break;
					case 'Pause':
						$available_emails = array( 'subscription_paused' );
						break;
					case 'Trial':
						if ( $is_invoice_present ) {
							if ( $is_automatic_payment ) {
								$available_emails = array( 'subscription_new_order', 'subscription_auto_renewal_success' );
							} else {
								$available_emails = array( 'subscription_new_order', 'subscription_invoice' );
							}
						} else {
							$available_emails = array( 'subscription_new_order' );
						}
						break;
					case 'Pending_Authorization':
						$available_emails = array( 'subscription_pending_authorization' );
						break;
					case 'Overdue':
						if ( $is_automatic_payment ) {
							$available_emails = array( 'subscription_overdue_automatic' );
						} else {
							$available_emails = array( 'subscription_overdue_manual' );
						}
						break;
					case 'Suspended':
						if ( $is_automatic_payment ) {
							$available_emails = array( 'subscription_suspended_automatic' );
						} else {
							$available_emails = array( 'subscription_suspended_manual' );
						}
						break;
					default:
						$available_emails = array();
						do_action( 'sumosubscriptions_admin_send_manual_subscription_email', $post->ID, $parent_order_id, $subscription_status );
						break;
				}

				if ( SUMOSubs_Emails::available() ) {
					$emails = SUMOSubs_Emails::get_emails();

					foreach ( $emails as $email ) {
						if ( in_array( $email->key, $available_emails ) ) {
							echo '<option value="send_email_' . esc_attr( $email->key ) . '">' . esc_html( $email->title ) . '</option>';
						}
					}
				}
				?>
			</optgroup>
		</select>
	</li>
	<li class="wide">
		<div id="delete-action">
			<?php
			if ( current_user_can( 'delete_post', $post->ID ) ) {
				if ( ! EMPTY_TRASH_DAYS ) {
					$delete_text = __( 'Delete Permanently', 'sumosubscriptions' );
				} else {
					$delete_text = __( 'Move to Trash', 'sumosubscriptions' );
				}
				?>
				<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo esc_html( $delete_text ); ?></a>
				<?php
			}
			?>
		</div>
		<input type="submit" class="button save_subscription save_order button-primary tips" name="save" value="<?php /* translators: 1: post type label */ printf( esc_html__( 'Save %s', 'sumosubscriptions' ), esc_html( get_post_type_object( $post->post_type )->labels->singular_name ) ); ?>" data-tip="<?php /* translators: 1: post type label */ printf( esc_html__( 'Save/update the %s', 'sumosubscriptions' ), esc_html( get_post_type_object( $post->post_type )->labels->singular_name ) ); ?>" />
	</li>
</ul>
