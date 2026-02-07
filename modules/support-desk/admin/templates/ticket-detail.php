<?php
/**
 * Ticket detail admin template.
 *
 * @package HostForge\Modules\SupportDesk\Admin
 * @var \WP_Post       $ticket          Ticket post.
 * @var array          $meta            Ticket meta values.
 * @var array          $replies         Array of WP_Comment replies.
 * @var array          $canned_responses Array of WP_Post canned responses.
 * @var \WP_User|null  $client          Customer user.
 * @var \WP_User|null  $assigned_to     Assigned staff user.
 * @var array          $departments     Ticket department terms.
 * @var array          $statuses        Valid statuses.
 * @var array          $priorities      Valid priorities.
 * @var array          $staff_users     Admin/shop_manager users.
 */

defined( 'ABSPATH' ) || exit;

$ticket_status   = $meta['_hf_status'] ?? 'open';
$ticket_priority = $meta['_hf_priority'] ?? 'medium';
$is_flagged      = ! empty( $meta['_hf_flagged'] );
$related_service = absint( $meta['_hf_related_service'] ?? 0 );
$last_reply_at   = $meta['_hf_last_reply_at'] ?? '';
$current_dept    = ! empty( $departments ) ? $departments[0] : null;

// Get all departments for the dropdown.
$all_departments = get_terms(
	array(
		'taxonomy'   => 'hf_department',
		'hide_empty' => false,
	)
);

if ( is_wp_error( $all_departments ) ) {
	$all_departments = array();
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php
		printf(
			/* translators: %1$d: ticket ID, %2$s: ticket subject */
			esc_html__( 'Ticket #%1$d: %2$s', 'hostforge' ),
			esc_html( $ticket->ID ),
			esc_html( $ticket->post_title )
		);
		?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-tickets' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Back to Tickets', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<div id="hf-ticket-notice" class="notice" style="display:none;"><p></p></div>

	<div class="hf-ticket-detail-grid">
		<!-- Main Content -->
		<div class="hf-ticket-main">
			<!-- Ticket Header -->
			<div class="hf-ticket-header">
				<div class="hf-ticket-header__badges">
					<span class="hf-status hf-status--<?php echo esc_attr( $ticket_status ); ?>">
						<?php echo esc_html( $statuses[ $ticket_status ] ?? ucfirst( $ticket_status ) ); ?>
					</span>
					<span class="hf-priority hf-priority--<?php echo esc_attr( $ticket_priority ); ?>">
						<?php echo esc_html( $priorities[ $ticket_priority ] ?? ucfirst( $ticket_priority ) ); ?>
					</span>
					<?php if ( $is_flagged ) : ?>
						<span class="hf-badge hf-badge--flagged"><?php esc_html_e( 'Flagged', 'hostforge' ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<!-- Original Message -->
			<div class="hf-card hf-ticket-message hf-ticket-message--original">
				<div class="hf-ticket-message__header">
					<strong>
						<?php
						$author = get_user_by( 'id', $ticket->post_author );
						echo esc_html( $author ? $author->display_name : __( 'Unknown', 'hostforge' ) );
						?>
					</strong>
					<span class="hf-ticket-message__badge hf-ticket-message__badge--customer">
						<?php esc_html_e( 'Customer', 'hostforge' ); ?>
					</span>
					<span class="hf-ticket-message__date">
						<?php echo esc_html( get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ticket ) ); ?>
					</span>
				</div>
				<div class="hf-ticket-message__content">
					<?php echo wp_kses_post( wpautop( $ticket->post_content ) ); ?>
				</div>
			</div>

			<!-- Replies Thread -->
			<div id="hf-ticket-replies" class="hf-ticket-replies">
				<?php if ( ! empty( $replies ) ) : ?>
					<?php foreach ( $replies as $reply ) : ?>
						<?php
						$is_private  = get_comment_meta( $reply->comment_ID, '_hf_is_private_note', true );
						$is_staff    = get_comment_meta( $reply->comment_ID, '_hf_is_staff_reply', true );
						$attachments = get_comment_meta( $reply->comment_ID, '_hf_attachments', true );

						$reply_class = 'hf-reply';
						if ( $is_private ) {
							$reply_class .= ' hf-reply--private';
						} elseif ( $is_staff ) {
							$reply_class .= ' hf-reply--staff';
						} else {
							$reply_class .= ' hf-reply--customer';
						}
						?>
						<div class="<?php echo esc_attr( $reply_class ); ?>" data-reply-id="<?php echo esc_attr( $reply->comment_ID ); ?>">
							<div class="hf-reply__header">
								<strong><?php echo esc_html( $reply->comment_author ); ?></strong>
								<?php if ( $is_private ) : ?>
									<span class="hf-reply__badge hf-reply__badge--private"><?php esc_html_e( 'Private Note', 'hostforge' ); ?></span>
								<?php elseif ( $is_staff ) : ?>
									<span class="hf-reply__badge hf-reply__badge--staff"><?php esc_html_e( 'Staff', 'hostforge' ); ?></span>
								<?php else : ?>
									<span class="hf-reply__badge hf-reply__badge--customer"><?php esc_html_e( 'Customer', 'hostforge' ); ?></span>
								<?php endif; ?>
								<span class="hf-reply__date">
									<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $reply->comment_date ) ) ); ?>
								</span>
							</div>
							<div class="hf-reply__content">
								<?php echo wp_kses_post( wpautop( $reply->comment_content ) ); ?>
							</div>
							<?php if ( ! empty( $attachments ) && is_array( $attachments ) ) : ?>
								<div class="hf-reply__attachments">
									<strong><?php esc_html_e( 'Attachments:', 'hostforge' ); ?></strong>
									<ul>
										<?php foreach ( $attachments as $attach_id ) : ?>
											<?php
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
				<?php else : ?>
					<p class="hf-muted"><?php esc_html_e( 'No replies yet.', 'hostforge' ); ?></p>
				<?php endif; ?>
			</div>

			<!-- Reply Form -->
			<div class="hf-card hf-ticket-reply-form">
				<h3><?php esc_html_e( 'Add Reply', 'hostforge' ); ?></h3>
				<form id="hf-reply-form">
					<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->ID ); ?>" />

					<div class="hf-form-row">
						<textarea id="hf-reply-content" name="content" rows="8" class="large-text" placeholder="<?php esc_attr_e( 'Type your reply...', 'hostforge' ); ?>"></textarea>
					</div>

					<div class="hf-form-row hf-form-row--inline">
						<label class="hf-checkbox-label">
							<input type="checkbox" id="hf-reply-private" name="is_private" value="1" />
							<?php esc_html_e( 'Private note (not visible to customer)', 'hostforge' ); ?>
						</label>
					</div>

					<div class="hf-form-row">
						<label for="hf-reply-attachments"><?php esc_html_e( 'Attachments', 'hostforge' ); ?></label>
						<input type="file" id="hf-reply-attachments" name="attachments[]" multiple />
					</div>

					<?php if ( ! empty( $canned_responses ) ) : ?>
						<div class="hf-form-row">
							<label for="hf-canned-response"><?php esc_html_e( 'Insert Canned Response', 'hostforge' ); ?></label>
							<select id="hf-canned-response" class="regular-text">
								<option value=""><?php esc_html_e( 'Select a response...', 'hostforge' ); ?></option>
								<?php foreach ( $canned_responses as $response ) : ?>
									<option value="<?php echo esc_attr( $response->ID ); ?>" data-content="<?php echo esc_attr( $response->post_content ); ?>">
										<?php echo esc_html( $response->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<div class="hf-form-row">
						<button type="submit" class="button button-primary" id="hf-submit-reply">
							<?php esc_html_e( 'Send Reply', 'hostforge' ); ?>
						</button>
						<span id="hf-reply-status" class="hf-inline-status"></span>
					</div>
				</form>
			</div>
		</div>

		<!-- Sidebar -->
		<div class="hf-ticket-sidebar">
			<!-- Customer Info -->
			<div class="hf-card">
				<h3 class="hf-card__title"><?php esc_html_e( 'Customer', 'hostforge' ); ?></h3>
				<?php if ( $client ) : ?>
					<div class="hf-sidebar-info">
						<p>
							<strong><?php echo esc_html( $client->display_name ); ?></strong><br />
							<a href="<?php echo esc_url( 'mailto:' . $client->user_email ); ?>">
								<?php echo esc_html( $client->user_email ); ?>
							</a>
						</p>
						<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $client->ID ) ); ?>" class="button button-small">
							<?php esc_html_e( 'View Profile', 'hostforge' ); ?>
						</a>
					</div>
				<?php else : ?>
					<p class="hf-muted"><?php esc_html_e( 'Unknown customer', 'hostforge' ); ?></p>
				<?php endif; ?>
			</div>

			<!-- Status -->
			<div class="hf-card">
				<h3 class="hf-card__title"><?php esc_html_e( 'Status', 'hostforge' ); ?></h3>
				<div class="hf-sidebar-action">
					<select id="hf-ticket-status" class="hf-sidebar-select">
						<?php foreach ( $statuses as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $ticket_status, $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button button-small" id="hf-update-status" data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>">
						<?php esc_html_e( 'Update', 'hostforge' ); ?>
					</button>
				</div>
			</div>

			<!-- Priority -->
			<div class="hf-card">
				<h3 class="hf-card__title"><?php esc_html_e( 'Priority', 'hostforge' ); ?></h3>
				<div class="hf-sidebar-action">
					<select id="hf-ticket-priority" class="hf-sidebar-select">
						<?php foreach ( $priorities as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $ticket_priority, $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button button-small" id="hf-update-priority" data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>">
						<?php esc_html_e( 'Update', 'hostforge' ); ?>
					</button>
				</div>
			</div>

			<!-- Department -->
			<div class="hf-card">
				<h3 class="hf-card__title"><?php esc_html_e( 'Department', 'hostforge' ); ?></h3>
				<div class="hf-sidebar-action">
					<select id="hf-ticket-department" class="hf-sidebar-select">
						<option value=""><?php esc_html_e( 'None', 'hostforge' ); ?></option>
						<?php foreach ( $all_departments as $dept ) : ?>
							<option value="<?php echo esc_attr( $dept->term_id ); ?>" <?php selected( $current_dept ? $current_dept->term_id : 0, $dept->term_id ); ?>>
								<?php echo esc_html( $dept->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button button-small" id="hf-update-department" data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>">
						<?php esc_html_e( 'Update', 'hostforge' ); ?>
					</button>
				</div>
			</div>

			<!-- Assigned To -->
			<div class="hf-card">
				<h3 class="hf-card__title"><?php esc_html_e( 'Assigned To', 'hostforge' ); ?></h3>
				<div class="hf-sidebar-action">
					<select id="hf-ticket-assigned" class="hf-sidebar-select">
						<option value="0"><?php esc_html_e( 'Unassigned', 'hostforge' ); ?></option>
						<?php foreach ( $staff_users as $staff ) : ?>
							<option value="<?php echo esc_attr( $staff->ID ); ?>" <?php selected( $assigned_to ? $assigned_to->ID : 0, $staff->ID ); ?>>
								<?php echo esc_html( $staff->display_name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button button-small" id="hf-assign-ticket" data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>">
						<?php esc_html_e( 'Assign', 'hostforge' ); ?>
					</button>
				</div>
			</div>

			<!-- Related Service -->
			<?php if ( $related_service > 0 ) : ?>
				<?php $service_post = get_post( $related_service ); ?>
				<?php if ( $service_post ) : ?>
					<div class="hf-card">
						<h3 class="hf-card__title"><?php esc_html_e( 'Related Service', 'hostforge' ); ?></h3>
						<div class="hf-sidebar-info">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-services&action=detail&service_id=' . $related_service ) ); ?>">
								<?php echo esc_html( $service_post->post_title ); ?>
							</a>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<!-- Flag Toggle -->
			<div class="hf-card">
				<h3 class="hf-card__title"><?php esc_html_e( 'Flag', 'hostforge' ); ?></h3>
				<div class="hf-sidebar-action">
					<button type="button" class="button button-small <?php echo $is_flagged ? 'hf-btn--active' : ''; ?>" id="hf-toggle-flag" data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>" data-flagged="<?php echo esc_attr( $is_flagged ? '1' : '0' ); ?>">
						<?php echo $is_flagged ? esc_html__( 'Unflag Ticket', 'hostforge' ) : esc_html__( 'Flag Ticket', 'hostforge' ); ?>
					</button>
				</div>
			</div>

			<!-- Dates -->
			<div class="hf-card">
				<h3 class="hf-card__title"><?php esc_html_e( 'Dates', 'hostforge' ); ?></h3>
				<table class="hf-info-table">
					<tr>
						<th><?php esc_html_e( 'Created', 'hostforge' ); ?></th>
						<td><?php echo esc_html( get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ticket ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Last Reply', 'hostforge' ); ?></th>
						<td>
							<?php
							if ( ! empty( $last_reply_at ) ) {
								echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_reply_at ) ) );
							} else {
								esc_html_e( 'No replies', 'hostforge' );
							}
							?>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
</div>
