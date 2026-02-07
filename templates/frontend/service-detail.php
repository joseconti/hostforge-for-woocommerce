<?php
/**
 * My Account — Hosting Service detail.
 *
 * Override: copy this file to theme/hostforge/frontend/service-detail.php
 *
 * @package HostForge\Templates\Frontend
 * @var \WP_Post        $service    Service post.
 * @var int             $service_id Service post ID.
 * @var array           $meta       Service meta values.
 * @var \WC_Product|null $product   Related product.
 */

defined( 'ABSPATH' ) || exit;

$status = $meta['_hf_status'] ?? 'pending';
$domain = $meta['_hf_domain'] ?? '';
?>

<div class="hf-service-detail">
	<p>
		<a href="<?php echo esc_url( wc_get_endpoint_url( 'hosting-services' ) ); ?>">
			&larr; <?php esc_html_e( 'Back to services', 'hostforge' ); ?>
		</a>
	</p>

	<div id="hf-frontend-notice" class="woocommerce-message" style="display:none;"></div>

	<!-- Service Info -->
	<div class="hf-service-info-card">
		<h3><?php esc_html_e( 'Service Information', 'hostforge' ); ?></h3>
		<table class="hf-detail-table">
			<tr>
				<th><?php esc_html_e( 'Domain', 'hostforge' ); ?></th>
				<td><?php echo esc_html( $domain ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Status', 'hostforge' ); ?></th>
				<td><span class="hf-status-badge hf-status-badge--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Username', 'hostforge' ); ?></th>
				<td><code><?php echo esc_html( $meta['_hf_panel_username'] ?? '' ); ?></code></td>
			</tr>
			<?php if ( $product ) : ?>
			<tr>
				<th><?php esc_html_e( 'Plan', 'hostforge' ); ?></th>
				<td><?php echo esc_html( $product->get_name() ); ?></td>
			</tr>
			<?php endif; ?>
			<?php if ( ! empty( $meta['_hf_next_due_date'] ) ) : ?>
			<tr>
				<th><?php esc_html_e( 'Next Due Date', 'hostforge' ); ?></th>
				<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $meta['_hf_next_due_date'] ) ) ); ?></td>
			</tr>
			<?php endif; ?>
		</table>
	</div>

	<?php if ( 'active' === $status ) : ?>
	<!-- Quick Actions -->
	<div class="hf-service-actions-card">
		<h3><?php esc_html_e( 'Quick Actions', 'hostforge' ); ?></h3>

		<!-- SSO Button -->
		<button type="button" class="woocommerce-button button hf-sso-btn" data-service-id="<?php echo esc_attr( $service_id ); ?>">
			<?php esc_html_e( 'Login to Control Panel', 'hostforge' ); ?>
		</button>
	</div>

	<!-- Change Password -->
	<div class="hf-service-password-card">
		<h3><?php esc_html_e( 'Change Password', 'hostforge' ); ?></h3>
		<form class="hf-change-password-form" data-service-id="<?php echo esc_attr( $service_id ); ?>">
			<p>
				<label for="hf-new-password"><?php esc_html_e( 'New Password', 'hostforge' ); ?></label>
				<input type="password" id="hf-new-password" name="new_password" minlength="8" required class="input-text" />
			</p>
			<p>
				<button type="submit" class="woocommerce-button button">
					<?php esc_html_e( 'Change Password', 'hostforge' ); ?>
				</button>
			</p>
		</form>
	</div>

	<!-- Usage Stats -->
	<div class="hf-service-usage-card">
		<h3><?php esc_html_e( 'Resource Usage', 'hostforge' ); ?></h3>
		<div id="hf-usage-container" data-service-id="<?php echo esc_attr( $service_id ); ?>">
			<p class="hf-loading"><?php esc_html_e( 'Loading usage data...', 'hostforge' ); ?></p>
		</div>
	</div>

	<!-- Upgrade/Downgrade -->
	<div class="hf-service-upgrade-card">
		<h3><?php esc_html_e( 'Change Plan', 'hostforge' ); ?></h3>
		<?php
		$server_id = absint( $meta['_hf_server_id'] ?? 0 );
		$packages  = array();
		if ( $server_id ) {
			$cached = get_post_meta( $server_id, '_hf_packages_cache', true );
			if ( is_array( $cached ) ) {
				$packages = $cached;
			}
		}
		?>
		<?php if ( ! empty( $packages ) ) : ?>
		<form class="hf-upgrade-form" data-service-id="<?php echo esc_attr( $service_id ); ?>">
			<p>
				<label for="hf-new-package"><?php esc_html_e( 'Select Plan', 'hostforge' ); ?></label>
				<select id="hf-new-package" name="new_package" class="input-text">
					<option value=""><?php esc_html_e( '— Select —', 'hostforge' ); ?></option>
					<?php foreach ( $packages as $pkg ) :
						$pkg_name = is_array( $pkg ) ? ( $pkg['name'] ?? $pkg ) : $pkg;
					?>
						<option value="<?php echo esc_attr( $pkg_name ); ?>"
							<?php selected( $meta['_hf_package'] ?? '', $pkg_name ); ?>>
							<?php echo esc_html( $pkg_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<button type="submit" class="woocommerce-button button">
					<?php esc_html_e( 'Request Change', 'hostforge' ); ?>
				</button>
			</p>
		</form>
		<?php else : ?>
			<p class="hf-muted"><?php esc_html_e( 'No packages available. Contact support for plan changes.', 'hostforge' ); ?></p>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<!-- Cancellation -->
	<?php if ( ! in_array( $status, array( 'terminated', 'cancelled' ), true ) ) : ?>
	<div class="hf-service-cancel-card">
		<h3><?php esc_html_e( 'Request Cancellation', 'hostforge' ); ?></h3>
		<?php if ( ! empty( $meta['_hf_cancel_requested_at'] ) ) : ?>
			<p class="woocommerce-info">
				<?php esc_html_e( 'A cancellation request is already pending. Our team will review it shortly.', 'hostforge' ); ?>
			</p>
		<?php else : ?>
		<form class="hf-cancel-form" data-service-id="<?php echo esc_attr( $service_id ); ?>">
			<p>
				<label for="hf-cancel-reason"><?php esc_html_e( 'Reason for cancellation (optional)', 'hostforge' ); ?></label>
				<textarea id="hf-cancel-reason" name="reason" rows="3" class="input-text"></textarea>
			</p>
			<p>
				<button type="submit" class="woocommerce-button button button--cancel">
					<?php esc_html_e( 'Request Cancellation', 'hostforge' ); ?>
				</button>
			</p>
		</form>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</div>
