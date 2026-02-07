<?php
/**
 * New ticket form admin template.
 *
 * @package HostForge\Modules\SupportDesk\Admin
 * @var array $departments Array of WP_Term departments.
 * @var array $priorities  Array of priority key => label.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap hf-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'New Ticket', 'hostforge' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-tickets' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Back to Tickets', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<div id="hf-ticket-notice" class="notice" style="display:none;"><p></p></div>

	<form id="hf-new-ticket-form" class="hf-form" enctype="multipart/form-data">
		<div class="hf-card">
			<h2 class="hf-card__title"><?php esc_html_e( 'Ticket Details', 'hostforge' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="hf-ticket-subject"><?php esc_html_e( 'Subject', 'hostforge' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<input type="text" id="hf-ticket-subject" name="subject" class="regular-text" required
							placeholder="<?php esc_attr_e( 'Enter ticket subject...', 'hostforge' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hf-ticket-department"><?php esc_html_e( 'Department', 'hostforge' ); ?></label>
					</th>
					<td>
						<select id="hf-ticket-department" name="department">
							<option value=""><?php esc_html_e( 'Select department...', 'hostforge' ); ?></option>
							<?php foreach ( $departments as $dept ) : ?>
								<option value="<?php echo esc_attr( $dept->term_id ); ?>">
									<?php echo esc_html( $dept->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hf-ticket-priority"><?php esc_html_e( 'Priority', 'hostforge' ); ?></label>
					</th>
					<td>
						<select id="hf-ticket-priority" name="priority">
							<?php foreach ( $priorities as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, 'medium' ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hf-ticket-client"><?php esc_html_e( 'Client', 'hostforge' ); ?></label>
					</th>
					<td>
						<input type="text" id="hf-ticket-client-search" class="regular-text"
							placeholder="<?php esc_attr_e( 'Search by name or email...', 'hostforge' ); ?>"
							autocomplete="off" />
						<input type="hidden" id="hf-ticket-client" name="client_id" value="" />
						<div id="hf-client-search-results" class="hf-search-results" style="display:none;"></div>
						<p id="hf-client-selected" class="description" style="display:none;"></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hf-ticket-content"><?php esc_html_e( 'Message', 'hostforge' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<textarea id="hf-ticket-content" name="content" rows="12" class="large-text" required
							placeholder="<?php esc_attr_e( 'Describe the issue or request...', 'hostforge' ); ?>"></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hf-ticket-attachments"><?php esc_html_e( 'Attachments', 'hostforge' ); ?></label>
					</th>
					<td>
						<input type="file" id="hf-ticket-attachments" name="attachments[]" multiple />
						<p class="description"><?php esc_html_e( 'Optional. You can select multiple files.', 'hostforge' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="hf-form-actions">
			<button type="submit" class="button button-primary button-large" id="hf-save-ticket">
				<?php esc_html_e( 'Create Ticket', 'hostforge' ); ?>
			</button>
			<span id="hf-save-status" class="hf-inline-status"></span>
		</div>
	</form>
</div>
