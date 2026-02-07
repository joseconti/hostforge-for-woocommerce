<?php
/**
 * My Account — Support Tickets list.
 *
 * Override: copy this file to theme/hostforge/frontend/ticket-list.php
 *
 * @package HostForge\Templates\Frontend
 * @var array  $tickets    Array of WP_Post objects.
 * @var array  $statuses   Ticket statuses (key => label).
 * @var array  $priorities Ticket priorities (key => label).
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="hf-ticket-list">
	<div class="hf-ticket-list__header">
		<a href="<?php echo esc_url( wc_get_endpoint_url( 'support-tickets', 'new' ) ); ?>" class="woocommerce-button button hf-btn hf-btn--primary">
			<?php esc_html_e( 'New Ticket', 'hostforge' ); ?>
		</a>
	</div>

	<?php if ( empty( $tickets ) ) : ?>
		<p class="hf-empty"><?php esc_html_e( 'You have no support tickets.', 'hostforge' ); ?></p>
	<?php else : ?>
		<table class="hf-table hf-ticket-table woocommerce-orders-table">
			<thead>
				<tr>
					<th class="hf-ticket-table__id"><?php esc_html_e( '#ID', 'hostforge' ); ?></th>
					<th class="hf-ticket-table__subject"><?php esc_html_e( 'Subject', 'hostforge' ); ?></th>
					<th class="hf-ticket-table__department"><?php esc_html_e( 'Department', 'hostforge' ); ?></th>
					<th class="hf-ticket-table__priority"><?php esc_html_e( 'Priority', 'hostforge' ); ?></th>
					<th class="hf-ticket-table__status"><?php esc_html_e( 'Status', 'hostforge' ); ?></th>
					<th class="hf-ticket-table__last-reply"><?php esc_html_e( 'Last Reply', 'hostforge' ); ?></th>
					<th class="hf-ticket-table__created"><?php esc_html_e( 'Created', 'hostforge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $tickets as $ticket ) :
					$ticket_status   = get_post_meta( $ticket->ID, '_hf_status', true ) ?: 'open';
					$ticket_priority = get_post_meta( $ticket->ID, '_hf_priority', true ) ?: 'medium';
					$last_reply_at   = get_post_meta( $ticket->ID, '_hf_last_reply_at', true );
					$detail_url      = wc_get_endpoint_url( 'support-tickets', $ticket->ID );

					// Department.
					$dept_name = '';
					$terms     = wp_get_object_terms( $ticket->ID, 'hf_department', array( 'fields' => 'names' ) );
					if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
						$dept_name = $terms[0];
					}

					// Status label.
					$status_label = isset( $statuses[ $ticket_status ] ) ? $statuses[ $ticket_status ] : ucfirst( str_replace( '_', ' ', $ticket_status ) );

					// Priority label.
					$priority_label = isset( $priorities[ $ticket_priority ] ) ? $priorities[ $ticket_priority ] : ucfirst( $ticket_priority );

					// Last reply display.
					$last_reply_display = '';
					if ( ! empty( $last_reply_at ) ) {
						$timestamp = strtotime( $last_reply_at );
						$now       = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
						$last_reply_display = sprintf(
							/* translators: %s: human time difference */
							esc_html__( '%s ago', 'hostforge' ),
							human_time_diff( $timestamp, $now )
						);
					}
				?>
				<tr>
					<td data-title="<?php esc_attr_e( '#ID', 'hostforge' ); ?>">
						<?php echo esc_html( $ticket->ID ); ?>
					</td>
					<td data-title="<?php esc_attr_e( 'Subject', 'hostforge' ); ?>">
						<a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $ticket->post_title ); ?></a>
					</td>
					<td data-title="<?php esc_attr_e( 'Department', 'hostforge' ); ?>">
						<?php if ( ! empty( $dept_name ) ) : ?>
							<?php echo esc_html( $dept_name ); ?>
						<?php else : ?>
							<span class="hf-muted">&mdash;</span>
						<?php endif; ?>
					</td>
					<td data-title="<?php esc_attr_e( 'Priority', 'hostforge' ); ?>">
						<span class="hf-priority hf-priority--<?php echo esc_attr( $ticket_priority ); ?>">
							<?php echo esc_html( $priority_label ); ?>
						</span>
					</td>
					<td data-title="<?php esc_attr_e( 'Status', 'hostforge' ); ?>">
						<span class="hf-status hf-status--<?php echo esc_attr( $ticket_status ); ?>">
							<?php echo esc_html( $status_label ); ?>
						</span>
					</td>
					<td data-title="<?php esc_attr_e( 'Last Reply', 'hostforge' ); ?>">
						<?php if ( ! empty( $last_reply_display ) ) : ?>
							<?php echo esc_html( $last_reply_display ); ?>
						<?php else : ?>
							<span class="hf-muted">&mdash;</span>
						<?php endif; ?>
					</td>
					<td data-title="<?php esc_attr_e( 'Created', 'hostforge' ); ?>">
						<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ticket->post_date ) ) ); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
