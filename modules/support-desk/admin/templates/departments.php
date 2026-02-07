<?php
/**
 * Departments management admin template.
 *
 * @package HostForge\Modules\SupportDesk\Admin
 * @var array $departments Array of WP_Term departments.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap hf-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Departments', 'hostforge' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-tickets' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Back to Tickets', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<div id="hf-department-notice" class="notice" style="display:none;"><p></p></div>

	<div class="hf-two-col-grid">
		<!-- Left Column: Add/Edit Form -->
		<div class="hf-two-col-grid__left">
			<div class="hf-card">
				<h2 class="hf-card__title" id="hf-department-form-title"><?php esc_html_e( 'Add New Department', 'hostforge' ); ?></h2>

				<form id="hf-department-form" class="hf-form">
					<input type="hidden" id="hf-department-id" name="department_id" value="0" />

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="hf-department-name"><?php esc_html_e( 'Name', 'hostforge' ); ?> <span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="hf-department-name" name="name" class="regular-text" required
									placeholder="<?php esc_attr_e( 'e.g. Technical Support', 'hostforge' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hf-department-description"><?php esc_html_e( 'Description', 'hostforge' ); ?></label>
							</th>
							<td>
								<textarea id="hf-department-description" name="description" rows="4" class="large-text"
									placeholder="<?php esc_attr_e( 'Optional description for this department...', 'hostforge' ); ?>"></textarea>
							</td>
						</tr>
					</table>

					<div class="hf-form-actions">
						<button type="submit" class="button button-primary" id="hf-save-department">
							<?php esc_html_e( 'Save Department', 'hostforge' ); ?>
						</button>
						<button type="button" class="button" id="hf-cancel-edit-department" style="display:none;">
							<?php esc_html_e( 'Cancel', 'hostforge' ); ?>
						</button>
						<span id="hf-department-status" class="hf-inline-status"></span>
					</div>
				</form>
			</div>
		</div>

		<!-- Right Column: Department List -->
		<div class="hf-two-col-grid__right">
			<div class="hf-card">
				<h2 class="hf-card__title"><?php esc_html_e( 'Existing Departments', 'hostforge' ); ?></h2>

				<?php if ( ! empty( $departments ) ) : ?>
					<table class="widefat striped" id="hf-departments-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Name', 'hostforge' ); ?></th>
								<th><?php esc_html_e( 'Description', 'hostforge' ); ?></th>
								<th><?php esc_html_e( 'Tickets', 'hostforge' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'hostforge' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $departments as $dept ) : ?>
								<tr data-department-id="<?php echo esc_attr( $dept->term_id ); ?>">
									<td><strong><?php echo esc_html( $dept->name ); ?></strong></td>
									<td><?php echo esc_html( $dept->description ?: '—' ); ?></td>
									<td><?php echo esc_html( $dept->count ); ?></td>
									<td>
										<button type="button" class="button button-small hf-edit-department"
											data-id="<?php echo esc_attr( $dept->term_id ); ?>"
											data-name="<?php echo esc_attr( $dept->name ); ?>"
											data-description="<?php echo esc_attr( $dept->description ); ?>">
											<?php esc_html_e( 'Edit', 'hostforge' ); ?>
										</button>
										<button type="button" class="button button-small button-link-delete hf-delete-department"
											data-id="<?php echo esc_attr( $dept->term_id ); ?>">
											<?php esc_html_e( 'Delete', 'hostforge' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="hf-muted"><?php esc_html_e( 'No departments found. Create one using the form on the left.', 'hostforge' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
