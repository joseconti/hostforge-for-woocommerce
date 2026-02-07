<?php
/**
 * Frontend template: Domain List.
 *
 * Displays the customer's domains in My Account.
 * Can be overridden by copying to theme/hostforge/frontend/domain-list.php.
 *
 * @package HostForge
 * @var array $domains Array of WP_Post objects.
 */

defined( 'ABSPATH' ) || exit;

$statuses = \HostForge\Modules\DomainManager\HF_Domain_Manager_Module::get_statuses();
?>

<div class="hf-domain-list">
	<?php if ( empty( $domains ) ) : ?>
		<p class="woocommerce-message woocommerce-message--info">
			<?php esc_html_e( 'You do not have any domains yet.', 'hostforge' ); ?>
		</p>
	<?php else : ?>
		<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Domain', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Status', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Expiry', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Auto-Renew', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'hostforge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $domains as $domain ) : ?>
					<?php
					$domain_name = get_post_meta( $domain->ID, '_hf_domain_name', true );
					$status      = get_post_meta( $domain->ID, '_hf_status', true ) ?: 'pending';
					$expiry      = get_post_meta( $domain->ID, '_hf_expiry_date', true );
					$auto_renew  = get_post_meta( $domain->ID, '_hf_auto_renew', true );
					$status_label = $statuses[ $status ] ?? ucfirst( $status );
					?>
					<tr>
						<td data-title="<?php esc_attr_e( 'Domain', 'hostforge' ); ?>">
							<strong><?php echo esc_html( $domain_name ); ?></strong>
						</td>
						<td data-title="<?php esc_attr_e( 'Status', 'hostforge' ); ?>">
							<span class="hf-status-badge hf-status-badge--<?php echo esc_attr( $status ); ?>">
								<?php echo esc_html( $status_label ); ?>
							</span>
						</td>
						<td data-title="<?php esc_attr_e( 'Expiry', 'hostforge' ); ?>">
							<?php
							if ( ! empty( $expiry ) ) {
								echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $expiry ) ) );
							} else {
								echo '&mdash;';
							}
							?>
						</td>
						<td data-title="<?php esc_attr_e( 'Auto-Renew', 'hostforge' ); ?>">
							<?php
							if ( 'yes' === $auto_renew ) {
								echo '<span class="hf-badge hf-badge--success">' . esc_html__( 'Yes', 'hostforge' ) . '</span>';
							} else {
								echo '<span class="hf-badge hf-badge--muted">' . esc_html__( 'No', 'hostforge' ) . '</span>';
							}
							?>
						</td>
						<td data-title="<?php esc_attr_e( 'Actions', 'hostforge' ); ?>">
							<a href="<?php echo esc_url( wc_get_endpoint_url( 'my-domains', $domain->ID ) ); ?>" class="woocommerce-button button">
								<?php esc_html_e( 'Manage', 'hostforge' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
