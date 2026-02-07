<?php
/**
 * Frontend template: Domain Detail.
 *
 * Displays domain management interface in My Account.
 * Can be overridden by copying to theme/hostforge/frontend/domain-detail.php.
 *
 * @package HostForge
 * @var \WP_Post $domain      Domain post object.
 * @var int      $domain_id   Domain post ID.
 * @var array    $meta        Domain meta values.
 * @var array    $nameservers Nameserver list.
 * @var array    $dns_records DNS record rows.
 */

defined( 'ABSPATH' ) || exit;

$statuses    = \HostForge\Modules\DomainManager\HF_Domain_Manager_Module::get_statuses();
$status      = $meta['_hf_status'] ?? 'pending';
$domain_name = $meta['_hf_domain_name'] ?? '';
?>

<div class="hf-domain-detail">
	<p>
		<a href="<?php echo esc_url( wc_get_endpoint_url( 'my-domains' ) ); ?>">&larr; <?php esc_html_e( 'Back to My Domains', 'hostforge' ); ?></a>
	</p>

	<h2>
		<?php echo esc_html( $domain_name ); ?>
		<span class="hf-status-badge hf-status-badge--<?php echo esc_attr( $status ); ?>">
			<?php echo esc_html( $statuses[ $status ] ?? ucfirst( $status ) ); ?>
		</span>
	</h2>

	<!-- Domain Overview -->
	<div class="hf-domain-card">
		<h3><?php esc_html_e( 'Domain Overview', 'hostforge' ); ?></h3>
		<table class="hf-detail-table">
			<tr>
				<th><?php esc_html_e( 'Registration Date', 'hostforge' ); ?></th>
				<td><?php echo ! empty( $meta['_hf_registration_date'] ) ? esc_html( wp_date( get_option( 'date_format' ), strtotime( $meta['_hf_registration_date'] ) ) ) : '&mdash;'; ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Expiry Date', 'hostforge' ); ?></th>
				<td><?php echo ! empty( $meta['_hf_expiry_date'] ) ? esc_html( wp_date( get_option( 'date_format' ), strtotime( $meta['_hf_expiry_date'] ) ) ) : '&mdash;'; ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Auto-Renew', 'hostforge' ); ?></th>
				<td>
					<label class="hf-toggle">
						<input type="checkbox"
							class="hf-domain-auto-renew"
							data-domain-id="<?php echo esc_attr( $domain_id ); ?>"
							<?php checked( 'yes', $meta['_hf_auto_renew'] ?? 'no' ); ?>
						/>
						<span class="hf-toggle__slider"></span>
					</label>
				</td>
			</tr>
		</table>
	</div>

	<?php if ( 'active' === $status ) : ?>
	<!-- Nameservers -->
	<div class="hf-domain-card">
		<h3><?php esc_html_e( 'Nameservers', 'hostforge' ); ?></h3>
		<div class="hf-nameservers-form" data-domain-id="<?php echo esc_attr( $domain_id ); ?>">
			<?php for ( $i = 0; $i < 5; $i++ ) : ?>
				<p>
					<label><?php printf( esc_html__( 'NS%d', 'hostforge' ), $i + 1 ); ?></label>
					<input type="text"
						name="nameservers[]"
						class="input-text"
						value="<?php echo esc_attr( $nameservers[ $i ] ?? '' ); ?>"
						placeholder="<?php printf( esc_attr__( 'ns%d.example.com', 'hostforge' ), $i + 1 ); ?>"
					/>
				</p>
			<?php endfor; ?>
			<p>
				<button type="button" class="woocommerce-button button hf-save-nameservers">
					<?php esc_html_e( 'Save Nameservers', 'hostforge' ); ?>
				</button>
			</p>
			<div class="hf-frontend-notice" style="display:none;"></div>
		</div>
	</div>

	<!-- DNS Records -->
	<div class="hf-domain-card">
		<h3><?php esc_html_e( 'DNS Records', 'hostforge' ); ?></h3>

		<?php if ( ! empty( $dns_records ) ) : ?>
			<table class="shop_table shop_table_responsive">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Type', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Host', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Value', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'TTL', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'hostforge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $dns_records as $record ) : ?>
						<tr data-record-id="<?php echo esc_attr( $record->id ); ?>">
							<td data-title="<?php esc_attr_e( 'Type', 'hostforge' ); ?>"><?php echo esc_html( $record->record_type ); ?></td>
							<td data-title="<?php esc_attr_e( 'Host', 'hostforge' ); ?>"><?php echo esc_html( $record->host ); ?></td>
							<td data-title="<?php esc_attr_e( 'Value', 'hostforge' ); ?>" class="hf-dns-value"><?php echo esc_html( $record->value ); ?></td>
							<td data-title="<?php esc_attr_e( 'TTL', 'hostforge' ); ?>"><?php echo esc_html( $record->ttl ); ?></td>
							<td data-title="<?php esc_attr_e( 'Actions', 'hostforge' ); ?>">
								<button type="button" class="woocommerce-button button button--small hf-frontend-delete-dns"
									data-domain-id="<?php echo esc_attr( $domain_id ); ?>"
									data-record-id="<?php echo esc_attr( $record->registrar_record_id ); ?>">
									<?php esc_html_e( 'Delete', 'hostforge' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No DNS records found.', 'hostforge' ); ?></p>
		<?php endif; ?>

		<!-- Add DNS Record -->
		<div class="hf-frontend-dns-form" data-domain-id="<?php echo esc_attr( $domain_id ); ?>">
			<h4><?php esc_html_e( 'Add Record', 'hostforge' ); ?></h4>
			<div class="hf-dns-inline-form">
				<select name="record_type" class="hf-dns-type">
					<option value="A">A</option>
					<option value="AAAA">AAAA</option>
					<option value="CNAME">CNAME</option>
					<option value="MX">MX</option>
					<option value="TXT">TXT</option>
				</select>
				<input type="text" name="host" class="input-text hf-dns-host" placeholder="@" />
				<input type="text" name="value" class="input-text hf-dns-value-input" placeholder="<?php esc_attr_e( 'Value', 'hostforge' ); ?>" />
				<input type="number" name="ttl" class="input-text hf-dns-ttl" value="3600" min="60" />
				<button type="button" class="woocommerce-button button hf-frontend-add-dns">
					<?php esc_html_e( 'Add', 'hostforge' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- Transfer Away -->
	<?php if ( 'active' === $status ) : ?>
	<div class="hf-domain-card">
		<h3><?php esc_html_e( 'Transfer Away', 'hostforge' ); ?></h3>
		<p><?php esc_html_e( 'To transfer this domain to another registrar, you will need the EPP/Authorization code.', 'hostforge' ); ?></p>
		<button type="button" class="woocommerce-button button hf-request-epp"
			data-domain-id="<?php echo esc_attr( $domain_id ); ?>">
			<?php esc_html_e( 'Request EPP Code', 'hostforge' ); ?>
		</button>
		<div class="hf-epp-result" style="display:none;"></div>
	</div>
	<?php endif; ?>

	<?php if ( ! empty( $meta['_hf_linked_service_id'] ) ) : ?>
	<!-- Linked Service -->
	<div class="hf-domain-card">
		<h3><?php esc_html_e( 'Linked Hosting Service', 'hostforge' ); ?></h3>
		<p>
			<a href="<?php echo esc_url( wc_get_endpoint_url( 'hosting-services', absint( $meta['_hf_linked_service_id'] ) ) ); ?>" class="woocommerce-button button">
				<?php esc_html_e( 'View Hosting Service', 'hostforge' ); ?>
			</a>
		</p>
	</div>
	<?php endif; ?>
</div>
