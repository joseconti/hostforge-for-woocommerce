<?php
/**
 * Canned responses management admin template.
 *
 * @package HostForge\Modules\SupportDesk\Admin
 * @var array $canned_responses Array of WP_Post canned responses.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap hf-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Canned Responses', 'hostforge' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-tickets' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Back to Tickets', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<div id="hf-canned-notice" class="notice" style="display:none;"><p></p></div>

	<div class="hf-two-col-grid">
		<!-- Left Column: Add/Edit Form -->
		<div class="hf-two-col-grid__left">
			<div class="hf-card">
				<h2 class="hf-card__title" id="hf-canned-form-title"><?php esc_html_e( 'Add New Response', 'hostforge' ); ?></h2>

				<form id="hf-canned-form" class="hf-form">
					<input type="hidden" id="hf-canned-id" name="response_id" value="0" />

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="hf-canned-title"><?php esc_html_e( 'Title', 'hostforge' ); ?> <span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="hf-canned-title" name="title" class="regular-text" required
									placeholder="<?php esc_attr_e( 'e.g. Welcome Response', 'hostforge' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hf-canned-content"><?php esc_html_e( 'Content', 'hostforge' ); ?> <span class="required">*</span></label>
							</th>
							<td>
								<textarea id="hf-canned-content" name="content" rows="10" class="large-text" required
									placeholder="<?php esc_attr_e( 'Type the response content...', 'hostforge' ); ?>"></textarea>
							</td>
						</tr>
					</table>

					<div class="hf-form-actions">
						<button type="submit" class="button button-primary" id="hf-save-canned">
							<?php esc_html_e( 'Save Response', 'hostforge' ); ?>
						</button>
						<button type="button" class="button" id="hf-cancel-edit-canned" style="display:none;">
							<?php esc_html_e( 'Cancel', 'hostforge' ); ?>
						</button>
						<span id="hf-canned-status" class="hf-inline-status"></span>
					</div>
				</form>
			</div>

			<!-- Merge Tags Reference -->
			<div class="hf-card">
				<h2 class="hf-card__title"><?php esc_html_e( 'Available Merge Tags', 'hostforge' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Use these tags in your response content. They will be replaced with actual values when inserted into a ticket reply.', 'hostforge' ); ?></p>
				<table class="widefat striped hf-merge-tags-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tag', 'hostforge' ); ?></th>
							<th><?php esc_html_e( 'Description', 'hostforge' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>{customer_name}</code></td>
							<td><?php esc_html_e( 'Customer display name', 'hostforge' ); ?></td>
						</tr>
						<tr>
							<td><code>{customer_email}</code></td>
							<td><?php esc_html_e( 'Customer email address', 'hostforge' ); ?></td>
						</tr>
						<tr>
							<td><code>{ticket_id}</code></td>
							<td><?php esc_html_e( 'Ticket ID number', 'hostforge' ); ?></td>
						</tr>
						<tr>
							<td><code>{ticket_subject}</code></td>
							<td><?php esc_html_e( 'Ticket subject line', 'hostforge' ); ?></td>
						</tr>
						<tr>
							<td><code>{service_domain}</code></td>
							<td><?php esc_html_e( 'Related service domain name', 'hostforge' ); ?></td>
						</tr>
						<tr>
							<td><code>{site_name}</code></td>
							<td><?php esc_html_e( 'Website name', 'hostforge' ); ?></td>
						</tr>
						<tr>
							<td><code>{site_url}</code></td>
							<td><?php esc_html_e( 'Website URL', 'hostforge' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Right Column: Responses List -->
		<div class="hf-two-col-grid__right">
			<div class="hf-card">
				<h2 class="hf-card__title"><?php esc_html_e( 'Existing Responses', 'hostforge' ); ?></h2>

				<?php if ( ! empty( $canned_responses ) ) : ?>
					<table class="widefat striped" id="hf-canned-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'hostforge' ); ?></th>
								<th><?php esc_html_e( 'Preview', 'hostforge' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'hostforge' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $canned_responses as $response ) : ?>
								<tr data-response-id="<?php echo esc_attr( $response->ID ); ?>">
									<td><strong><?php echo esc_html( $response->post_title ); ?></strong></td>
									<td>
										<span class="hf-canned-preview">
											<?php echo esc_html( wp_trim_words( $response->post_content, 15, '...' ) ); ?>
										</span>
									</td>
									<td>
										<button type="button" class="button button-small hf-edit-canned"
											data-id="<?php echo esc_attr( $response->ID ); ?>"
											data-title="<?php echo esc_attr( $response->post_title ); ?>"
											data-content="<?php echo esc_attr( $response->post_content ); ?>">
											<?php esc_html_e( 'Edit', 'hostforge' ); ?>
										</button>
										<button type="button" class="button button-small button-link-delete hf-delete-canned"
											data-id="<?php echo esc_attr( $response->ID ); ?>">
											<?php esc_html_e( 'Delete', 'hostforge' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="hf-muted"><?php esc_html_e( 'No canned responses found. Create one using the form on the left.', 'hostforge' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
