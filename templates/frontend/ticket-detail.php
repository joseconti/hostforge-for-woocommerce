<?php
/**
 * My Account — Support Ticket detail.
 *
 * Override: copy this file to theme/hostforge/frontend/ticket-detail.php
 *
 * @package HostForge\Templates\Frontend
 * @var \WP_Post $ticket     Ticket post object.
 * @var array    $replies    Array of WP_Comment objects (no private notes).
 * @var array    $statuses   Ticket statuses (key => label).
 * @var array    $priorities Ticket priorities (key => label).
 */

defined( 'ABSPATH' ) || exit;

$ticket_status   = get_post_meta( $ticket->ID, '_hf_status', true ) ?: 'open';
$ticket_priority = get_post_meta( $ticket->ID, '_hf_priority', true ) ?: 'medium';
$status_label    = isset( $statuses[ $ticket_status ] ) ? $statuses[ $ticket_status ] : ucfirst( str_replace( '_', ' ', $ticket_status ) );
$priority_label  = isset( $priorities[ $ticket_priority ] ) ? $priorities[ $ticket_priority ] : ucfirst( $ticket_priority );
$is_closed       = 'closed' === $ticket_status;
$client_user_id  = absint( get_post_meta( $ticket->ID, '_hf_client_user_id', true ) );
?>

<div class="hf-ticket-detail">
	<p class="hf-back-link">
		<a href="<?php echo esc_url( wc_get_endpoint_url( 'support-tickets' ) ); ?>">
			&larr; <?php esc_html_e( 'Back to tickets', 'hostforge' ); ?>
		</a>
	</p>

	<div id="hf-ticket-notice" class="woocommerce-message" style="display:none;"></div>

	<!-- Ticket Header -->
	<div class="hf-ticket-detail__header">
		<h2 class="hf-ticket-detail__title">
			<?php
			printf(
				/* translators: %d: ticket ID */
				esc_html__( '#%d', 'hostforge' ),
				esc_html( $ticket->ID )
			);
			?>
			&mdash;
			<?php echo esc_html( $ticket->post_title ); ?>
		</h2>
		<div class="hf-ticket-detail__meta">
			<span class="hf-status hf-status--<?php echo esc_attr( $ticket_status ); ?>">
				<?php echo esc_html( $status_label ); ?>
			</span>
			<span class="hf-priority hf-priority--<?php echo esc_attr( $ticket_priority ); ?>">
				<?php echo esc_html( $priority_label ); ?>
			</span>
		</div>
	</div>

	<!-- Original Message -->
	<div class="hf-ticket-message hf-ticket-message--original">
		<div class="hf-ticket-message__header">
			<strong><?php echo esc_html( get_the_author_meta( 'display_name', $ticket->post_author ) ); ?></strong>
			<span class="hf-ticket-message__date">
				<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->post_date ) ) ); ?>
			</span>
		</div>
		<div class="hf-ticket-message__body">
			<?php echo wp_kses_post( wpautop( $ticket->post_content ) ); ?>
		</div>
		<?php
		$original_attachments = get_post_meta( $ticket->ID, '_hf_attachments', true );
		if ( ! empty( $original_attachments ) && is_array( $original_attachments ) ) : ?>
			<div class="hf-ticket-message__attachments">
				<strong><?php esc_html_e( 'Attachments:', 'hostforge' ); ?></strong>
				<ul class="hf-attachment-list">
					<?php foreach ( $original_attachments as $attach_id ) :
						$attach_id  = absint( $attach_id );
						$attach_url = wp_get_attachment_url( $attach_id );
						if ( ! $attach_url ) {
							continue;
						}
					?>
						<li>
							<a href="<?php echo esc_url( $attach_url ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( get_the_title( $attach_id ) ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>

	<!-- Replies Thread -->
	<?php if ( ! empty( $replies ) ) : ?>
		<div class="hf-ticket-replies" id="hf-ticket-replies">
			<?php foreach ( $replies as $reply ) :
				$is_staff      = (bool) get_comment_meta( $reply->comment_ID, '_hf_is_staff_reply', true );
				$reply_class   = $is_staff ? 'hf-ticket-reply hf-ticket-reply--staff' : 'hf-ticket-reply hf-ticket-reply--customer';
				$reply_time    = strtotime( $reply->comment_date );
				$now           = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
				$diff_seconds  = $now - $reply_time;
				$attachments   = get_comment_meta( $reply->comment_ID, '_hf_attachments', true );

				if ( $diff_seconds < 7 * DAY_IN_SECONDS ) {
					$date_display = sprintf(
						/* translators: %s: human time difference */
						esc_html__( '%s ago', 'hostforge' ),
						human_time_diff( $reply_time, $now )
					);
				} else {
					$date_display = esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $reply_time ) );
				}
			?>
				<div class="<?php echo esc_attr( $reply_class ); ?>" data-reply-id="<?php echo esc_attr( $reply->comment_ID ); ?>">
					<div class="hf-ticket-reply__header">
						<strong><?php echo esc_html( $reply->comment_author ); ?></strong>
						<?php if ( $is_staff ) : ?>
							<span class="hf-ticket-reply__badge hf-ticket-reply__badge--staff"><?php esc_html_e( 'Staff', 'hostforge' ); ?></span>
						<?php endif; ?>
						<span class="hf-ticket-reply__date"><?php echo esc_html( $date_display ); ?></span>
					</div>
					<div class="hf-ticket-reply__body">
						<?php echo wp_kses_post( wpautop( $reply->comment_content ) ); ?>
					</div>
					<?php if ( ! empty( $attachments ) && is_array( $attachments ) ) : ?>
						<div class="hf-ticket-reply__attachments">
							<strong><?php esc_html_e( 'Attachments:', 'hostforge' ); ?></strong>
							<ul class="hf-attachment-list">
								<?php foreach ( $attachments as $attach_id ) :
									$attach_id  = absint( $attach_id );
									$attach_url = wp_get_attachment_url( $attach_id );
									if ( ! $attach_url ) {
										continue;
									}
								?>
									<li>
										<a href="<?php echo esc_url( $attach_url ); ?>" target="_blank" rel="noopener noreferrer">
											<?php echo esc_html( get_the_title( $attach_id ) ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<!-- Reply Form -->
	<div class="hf-ticket-reply-form-wrapper">
		<?php if ( $is_closed ) : ?>
			<p class="hf-ticket-closed-notice woocommerce-info">
				<?php esc_html_e( 'This ticket is closed. Reply to reopen it.', 'hostforge' ); ?>
			</p>
		<?php endif; ?>

		<form class="hf-ticket-reply-form" data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>">
			<h3><?php esc_html_e( 'Reply', 'hostforge' ); ?></h3>

			<p class="hf-form-row">
				<label for="hf-reply-message"><?php esc_html_e( 'Message', 'hostforge' ); ?> <span class="required">*</span></label>
				<textarea id="hf-reply-message" name="message" rows="6" class="input-text" required></textarea>
			</p>

			<p class="hf-form-row">
				<label for="hf-reply-attachments"><?php esc_html_e( 'Attachments', 'hostforge' ); ?></label>
				<input type="file" id="hf-reply-attachments" name="attachments[]" multiple class="input-text" />
			</p>

			<div class="hf-form-actions">
				<button type="submit" class="woocommerce-button button hf-btn hf-btn--primary hf-reply-submit">
					<?php esc_html_e( 'Submit Reply', 'hostforge' ); ?>
				</button>

				<?php if ( ! $is_closed ) : ?>
					<button type="button" class="woocommerce-button button hf-btn hf-btn--secondary hf-close-ticket-btn" data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>">
						<?php esc_html_e( 'Close Ticket', 'hostforge' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</form>
	</div>
</div>
