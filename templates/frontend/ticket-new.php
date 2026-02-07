<?php
/**
 * My Account — New Support Ticket form.
 *
 * Override: copy this file to theme/hostforge/frontend/ticket-new.php
 *
 * @package HostForge\Templates\Frontend
 * @var array $departments Array of WP_Term objects.
 * @var array $services    Array of WP_Post objects (user's active services).
 * @var array $priorities  Ticket priorities (key => label).
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="hf-ticket-new">
	<p class="hf-back-link">
		<a href="<?php echo esc_url( wc_get_endpoint_url( 'support-tickets' ) ); ?>">
			&larr; <?php esc_html_e( 'Back to tickets', 'hostforge' ); ?>
		</a>
	</p>

	<h2><?php esc_html_e( 'New Support Ticket', 'hostforge' ); ?></h2>

	<div id="hf-ticket-notice" class="woocommerce-message" style="display:none;"></div>

	<form class="hf-ticket-form" id="hf-new-ticket-form">
		<p class="hf-form-row">
			<label for="hf-ticket-subject"><?php esc_html_e( 'Subject', 'hostforge' ); ?> <span class="required">*</span></label>
			<input type="text" id="hf-ticket-subject" name="subject" class="input-text" required />
		</p>

		<!-- KB Suggestions (populated via AJAX as user types subject) -->
		<div id="hf-kb-suggestions" class="hf-kb-suggestions" style="display:none;"></div>

		<p class="hf-form-row">
			<label for="hf-ticket-department"><?php esc_html_e( 'Department', 'hostforge' ); ?></label>
			<select id="hf-ticket-department" name="department" class="input-text">
				<option value=""><?php esc_html_e( '&mdash; Select Department &mdash;', 'hostforge' ); ?></option>
				<?php foreach ( $departments as $dept ) : ?>
					<option value="<?php echo esc_attr( $dept->term_id ); ?>">
						<?php echo esc_html( $dept->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<?php if ( ! empty( $services ) ) : ?>
			<p class="hf-form-row">
				<label for="hf-ticket-service"><?php esc_html_e( 'Related Service', 'hostforge' ); ?></label>
				<select id="hf-ticket-service" name="related_service" class="input-text">
					<option value=""><?php esc_html_e( '&mdash; None &mdash;', 'hostforge' ); ?></option>
					<?php foreach ( $services as $service ) :
						$domain = get_post_meta( $service->ID, '_hf_domain', true );
						$label  = ! empty( $domain ) ? $domain : $service->post_title;
					?>
						<option value="<?php echo esc_attr( $service->ID ); ?>">
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php endif; ?>

		<p class="hf-form-row">
			<label for="hf-ticket-message"><?php esc_html_e( 'Message', 'hostforge' ); ?> <span class="required">*</span></label>
			<textarea id="hf-ticket-message" name="message" rows="8" class="input-text" required></textarea>
		</p>

		<p class="hf-form-row">
			<label for="hf-ticket-attachments"><?php esc_html_e( 'Attachments', 'hostforge' ); ?></label>
			<input type="file" id="hf-ticket-attachments" name="attachments[]" multiple class="input-text" />
		</p>

		<div class="hf-form-actions">
			<button type="submit" class="woocommerce-button button hf-btn hf-btn--primary hf-submit-ticket-btn">
				<?php esc_html_e( 'Submit Ticket', 'hostforge' ); ?>
			</button>
		</div>
	</form>
</div>
