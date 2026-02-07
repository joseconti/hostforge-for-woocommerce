<?php
/**
 * Service detail admin template.
 *
 * @package HostForge\Modules\AutoProvisioning\Admin
 * @var \WP_Post      $service     Service post.
 * @var array         $meta        Service meta values.
 * @var \WC_Order|null $order      Related order.
 * @var \WP_User|null $user        Customer user.
 * @var \WP_Post|null $server      Related server.
 * @var \WC_Product|null $product  Related product.
 * @var array         $queue_items Queue history.
 */

defined( 'ABSPATH' ) || exit;

$status  = $meta['_hf_status'] ?? 'pending';
$domain  = $meta['_hf_domain'] ?? '';
?>
<div class="wrap">
	<h1>
		<?php
		printf(
			/* translators: %s: domain name */
			esc_html__( 'Service: %s', 'hostforge' ),
			esc_html( $domain ?: $service->post_title )
		);
		?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-services' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Back to List', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<div id="hf-service-notice" class="notice" style="display:none;"><p></p></div>

	<div class="hf-service-detail-grid">
		<!-- Service Info Card -->
		<div class="hf-card">
			<h2><?php esc_html_e( 'Service Information', 'hostforge' ); ?></h2>
			<table class="hf-info-table">
				<tr>
					<th><?php esc_html_e( 'Domain', 'hostforge' ); ?></th>
					<td><?php echo esc_html( $domain ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Status', 'hostforge' ); ?></th>
					<td><?php echo hf_format_status_badge( $status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Panel Username', 'hostforge' ); ?></th>
					<td><code><?php echo esc_html( $meta['_hf_panel_username'] ?? '' ); ?></code></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Panel Type', 'hostforge' ); ?></th>
					<td><?php echo esc_html( 'cpanel' === ( $meta['_hf_panel_type'] ?? '' ) ? 'cPanel' : 'Plesk' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Package', 'hostforge' ); ?></th>
					<td><?php echo esc_html( $meta['_hf_package'] ?? '—' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Provisioned', 'hostforge' ); ?></th>
					<td><?php echo esc_html( $meta['_hf_provisioned_at'] ?? '—' ); ?></td>
				</tr>
				<?php if ( ! empty( $meta['_hf_suspended_at'] ) ) : ?>
				<tr>
					<th><?php esc_html_e( 'Suspended', 'hostforge' ); ?></th>
					<td><?php echo esc_html( $meta['_hf_suspended_at'] ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( ! empty( $meta['_hf_terminated_at'] ) ) : ?>
				<tr>
					<th><?php esc_html_e( 'Terminated', 'hostforge' ); ?></th>
					<td><?php echo esc_html( $meta['_hf_terminated_at'] ); ?></td>
				</tr>
				<?php endif; ?>
				<tr>
					<th><?php esc_html_e( 'Next Due Date', 'hostforge' ); ?></th>
					<td><?php echo esc_html( $meta['_hf_next_due_date'] ?? '—' ); ?></td>
				</tr>
			</table>
		</div>

		<!-- Related Info Card -->
		<div class="hf-card">
			<h2><?php esc_html_e( 'Related Information', 'hostforge' ); ?></h2>
			<table class="hf-info-table">
				<tr>
					<th><?php esc_html_e( 'Customer', 'hostforge' ); ?></th>
					<td>
						<?php if ( $user ) : ?>
							<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $user->ID ) ); ?>">
								<?php echo esc_html( $user->display_name ?: $user->user_email ); ?>
							</a>
						<?php else : ?>
							<span class="hf-muted"><?php esc_html_e( 'Unknown', 'hostforge' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Order', 'hostforge' ); ?></th>
					<td>
						<?php if ( $order ) : ?>
							<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">
								#<?php echo esc_html( $order->get_id() ); ?>
							</a>
						<?php else : ?>
							<span class="hf-muted"><?php esc_html_e( 'None', 'hostforge' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Subscription', 'hostforge' ); ?></th>
					<td>
						<?php
						$sub_id = absint( $meta['_hf_subscription_id'] ?? 0 );
						echo $sub_id ? '#' . esc_html( $sub_id ) : '<span class="hf-muted">' . esc_html__( 'None', 'hostforge' ) . '</span>';
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Product', 'hostforge' ); ?></th>
					<td>
						<?php if ( $product ) : ?>
							<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $product->get_id() . '&action=edit' ) ); ?>">
								<?php echo esc_html( $product->get_name() ); ?>
							</a>
						<?php else : ?>
							<span class="hf-muted"><?php esc_html_e( 'Deleted', 'hostforge' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Server', 'hostforge' ); ?></th>
					<td>
						<?php if ( $server ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-servers&action=edit&server_id=' . $server->ID ) ); ?>">
								<?php echo esc_html( $server->post_title ); ?>
							</a>
						<?php else : ?>
							<span class="hf-muted"><?php esc_html_e( 'None', 'hostforge' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</div>

		<!-- Cancellation Request -->
		<?php if ( ! empty( $meta['_hf_cancel_requested_at'] ) ) : ?>
		<div class="hf-card hf-card--warning">
			<h2><?php esc_html_e( 'Cancellation Request', 'hostforge' ); ?></h2>
			<table class="hf-info-table">
				<tr>
					<th><?php esc_html_e( 'Requested At', 'hostforge' ); ?></th>
					<td><?php echo esc_html( $meta['_hf_cancel_requested_at'] ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Reason', 'hostforge' ); ?></th>
					<td><?php echo esc_html( $meta['_hf_cancel_reason'] ?? '—' ); ?></td>
				</tr>
			</table>
		</div>
		<?php endif; ?>

		<!-- Manual Actions Card -->
		<div class="hf-card">
			<h2><?php esc_html_e( 'Manual Actions', 'hostforge' ); ?></h2>
			<div class="hf-service-actions">
				<?php if ( 'active' === $status ) : ?>
					<button type="button" class="button hf-service-action-btn" data-action="suspend" data-service-id="<?php echo esc_attr( $service->ID ); ?>">
						<?php esc_html_e( 'Suspend Service', 'hostforge' ); ?>
					</button>
				<?php endif; ?>

				<?php if ( 'suspended' === $status ) : ?>
					<button type="button" class="button button-primary hf-service-action-btn" data-action="unsuspend" data-service-id="<?php echo esc_attr( $service->ID ); ?>">
						<?php esc_html_e( 'Reactivate Service', 'hostforge' ); ?>
					</button>
				<?php endif; ?>

				<?php if ( ! in_array( $status, array( 'terminated', 'cancelled' ), true ) ) : ?>
					<button type="button" class="button button-link-delete hf-service-action-btn" data-action="terminate" data-service-id="<?php echo esc_attr( $service->ID ); ?>">
						<?php esc_html_e( 'Terminate Service', 'hostforge' ); ?>
					</button>
				<?php endif; ?>

				<?php if ( in_array( $status, array( 'terminated', 'cancelled' ), true ) ) : ?>
					<p class="description"><?php esc_html_e( 'No actions available for terminated or cancelled services.', 'hostforge' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Provisioning Queue History -->
		<div class="hf-card hf-card--full">
			<h2><?php esc_html_e( 'Provisioning History', 'hostforge' ); ?></h2>
			<?php if ( ! empty( $queue_items ) ) : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Action', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Status', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Attempts', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Error', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Scheduled', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Completed', 'hostforge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $queue_items as $qi ) : ?>
					<tr>
						<td><?php echo esc_html( ucfirst( $qi->action ) ); ?></td>
						<td><?php echo hf_format_status_badge( $qi->status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						<td><?php echo esc_html( $qi->attempts . '/' . $qi->max_attempts ); ?></td>
						<td><?php echo esc_html( $qi->last_error ?: '—' ); ?></td>
						<td><?php echo esc_html( $qi->scheduled_at ); ?></td>
						<td><?php echo esc_html( $qi->completed_at ?: '—' ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No provisioning history found.', 'hostforge' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
