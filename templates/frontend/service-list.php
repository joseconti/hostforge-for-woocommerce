<?php
/**
 * My Account — Hosting Services list.
 *
 * Override: copy this file to theme/hostforge/frontend/service-list.php
 *
 * @package HostForge\Templates\Frontend
 * @var array $services Array of WP_Post objects.
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="hf-services-list">
	<?php if ( empty( $services ) ) : ?>
		<p class="hf-empty"><?php esc_html_e( 'You do not have any hosting services yet.', 'hostforge' ); ?></p>
	<?php else : ?>
		<table class="hf-table woocommerce-orders-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Domain', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Product', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Status', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Next Due', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'hostforge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $services as $service ) :
					$domain     = get_post_meta( $service->ID, '_hf_domain', true );
					$status     = get_post_meta( $service->ID, '_hf_status', true ) ?: 'pending';
					$product_id = absint( get_post_meta( $service->ID, '_hf_product_id', true ) );
					$product    = $product_id ? wc_get_product( $product_id ) : null;
					$next_due   = get_post_meta( $service->ID, '_hf_next_due_date', true );
					$detail_url = wc_get_endpoint_url( 'hosting-services', $service->ID );
				?>
				<tr>
					<td data-title="<?php esc_attr_e( 'Domain', 'hostforge' ); ?>">
						<a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $domain ); ?></a>
					</td>
					<td data-title="<?php esc_attr_e( 'Product', 'hostforge' ); ?>">
						<?php echo $product ? esc_html( $product->get_name() ) : '<span class="hf-muted">—</span>'; ?>
					</td>
					<td data-title="<?php esc_attr_e( 'Status', 'hostforge' ); ?>">
						<span class="hf-status-badge hf-status-badge--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
					</td>
					<td data-title="<?php esc_attr_e( 'Next Due', 'hostforge' ); ?>">
						<?php
						if ( $next_due ) {
							echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $next_due ) ) );
						} else {
							echo '<span class="hf-muted">—</span>';
						}
						?>
					</td>
					<td data-title="<?php esc_attr_e( 'Actions', 'hostforge' ); ?>">
						<a href="<?php echo esc_url( $detail_url ); ?>" class="woocommerce-button button">
							<?php esc_html_e( 'Manage', 'hostforge' ); ?>
						</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
