<?php
/**
 * Admin template: Domain Detail.
 *
 * @package HostForge\Modules\DomainManager\Admin
 * @var \WP_Post $domain      Domain post object.
 * @var int      $domain_id   Domain post ID.
 * @var array    $meta        Domain meta values.
 * @var array    $dns_records DNS record rows.
 */

defined( 'ABSPATH' ) || exit;

$statuses      = \HostForge\Modules\DomainManager\HF_Domain_Manager_Module::get_statuses();
$status        = $meta['_hf_status'] ?? 'pending';
$domain_name   = $meta['_hf_domain_name'] ?? '';
$ns_decoded    = json_decode( $meta['_hf_nameservers'] ?? '[]', true );
$nameservers   = ! empty( $ns_decoded ) ? $ns_decoded : array();
$whois_decoded = json_decode( $meta['_hf_whois_cache'] ?? '{}', true );
$whois_data    = ! empty( $whois_decoded ) ? $whois_decoded : array();
?>

<div class="wrap">
	<h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-domains' ) ); ?>">&larr;</a>
		<?php echo esc_html( $domain_name ); ?>
		<span class="hf-status-badge hf-status-badge--<?php echo esc_attr( $status ); ?>">
			<?php echo esc_html( $statuses[ $status ] ?? ucfirst( $status ) ); ?>
		</span>
	</h1>

	<div class="hf-admin-grid">
		<!-- Left Column -->
		<div class="hf-admin-column">

			<!-- Domain Info -->
			<div class="hf-admin-card">
				<h2><?php esc_html_e( 'Domain Information', 'hostforge' ); ?></h2>
				<table class="hf-info-table">
					<tr>
						<th><?php esc_html_e( 'Domain Name', 'hostforge' ); ?></th>
						<td><?php echo esc_html( $domain_name ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Registrar', 'hostforge' ); ?></th>
						<td><?php echo esc_html( ucfirst( $meta['_hf_registrar_id'] ?? '' ) ); ?></td>
					</tr>
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
									id="hf-auto-renew-toggle"
									data-domain-id="<?php echo esc_attr( $domain_id ); ?>"
									<?php checked( 'yes', $meta['_hf_auto_renew'] ?? 'no' ); ?>
								/>
								<span class="hf-toggle__slider"></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Transfer Lock', 'hostforge' ); ?></th>
						<td>
							<label class="hf-toggle">
								<input type="checkbox"
									id="hf-lock-toggle"
									data-domain-id="<?php echo esc_attr( $domain_id ); ?>"
									<?php checked( 'yes', $meta['_hf_locked'] ?? 'no' ); ?>
								/>
								<span class="hf-toggle__slider"></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Last Synced', 'hostforge' ); ?></th>
						<td><?php echo ! empty( $meta['_hf_last_synced'] ) ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $meta['_hf_last_synced'] ) ) ) : '&mdash;'; ?></td>
					</tr>
				</table>
			</div>

			<!-- Nameservers -->
			<div class="hf-admin-card">
				<h2><?php esc_html_e( 'Nameservers', 'hostforge' ); ?></h2>
				<div id="hf-nameservers-form" data-domain-id="<?php echo esc_attr( $domain_id ); ?>">
					<?php for ( $i = 0; $i < 5; $i++ ) : ?>
						<p class="form-row">
							<label><?php printf( esc_html__( 'NS%d', 'hostforge' ), $i + 1 ); ?></label>
							<input type="text"
								name="nameservers[]"
								class="regular-text"
								value="<?php echo esc_attr( $nameservers[ $i ] ?? '' ); ?>"
								placeholder="<?php printf( esc_attr__( 'ns%d.example.com', 'hostforge' ), $i + 1 ); ?>"
							/>
						</p>
					<?php endfor; ?>
					<p>
						<button type="button" class="button button-primary" id="hf-save-nameservers">
							<?php esc_html_e( 'Save Nameservers', 'hostforge' ); ?>
						</button>
					</p>
				</div>
			</div>

			<!-- WHOIS -->
			<?php if ( ! empty( $whois_data ) && isset( $whois_data['registrant'] ) ) : ?>
			<div class="hf-admin-card">
				<h2><?php esc_html_e( 'WHOIS Contact', 'hostforge' ); ?></h2>
				<table class="hf-info-table">
					<?php
					$registrant   = $whois_data['registrant'];
					$whois_fields = array(
						'first_name'   => __( 'First Name', 'hostforge' ),
						'last_name'    => __( 'Last Name', 'hostforge' ),
						'organization' => __( 'Organization', 'hostforge' ),
						'email'        => __( 'Email', 'hostforge' ),
						'phone'        => __( 'Phone', 'hostforge' ),
						'address1'     => __( 'Address', 'hostforge' ),
						'city'         => __( 'City', 'hostforge' ),
						'state'        => __( 'State', 'hostforge' ),
						'postal_code'  => __( 'Postal Code', 'hostforge' ),
						'country'      => __( 'Country', 'hostforge' ),
					);
					foreach ( $whois_fields as $key => $label ) :
						if ( ! empty( $registrant[ $key ] ) ) :
							?>
						<tr>
							<th><?php echo esc_html( $label ); ?></th>
							<td><?php echo esc_html( $registrant[ $key ] ); ?></td>
						</tr>
							<?php
						endif;
					endforeach;
					?>
				</table>
			</div>
			<?php endif; ?>

		</div>

		<!-- Right Column -->
		<div class="hf-admin-column">

			<!-- DNS Records -->
			<div class="hf-admin-card">
				<h2>
					<?php esc_html_e( 'DNS Records', 'hostforge' ); ?>
					<button type="button" class="button button-small" id="hf-sync-dns" data-domain-id="<?php echo esc_attr( $domain_id ); ?>">
						<?php esc_html_e( 'Sync', 'hostforge' ); ?>
					</button>
				</h2>

				<table class="widefat striped" id="hf-dns-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Type', 'hostforge' ); ?></th>
							<th><?php esc_html_e( 'Host', 'hostforge' ); ?></th>
							<th><?php esc_html_e( 'Value', 'hostforge' ); ?></th>
							<th><?php esc_html_e( 'TTL', 'hostforge' ); ?></th>
							<th><?php esc_html_e( 'Priority', 'hostforge' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'hostforge' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $dns_records ) ) : ?>
							<?php foreach ( $dns_records as $record ) : ?>
								<tr data-record-id="<?php echo esc_attr( $record->id ); ?>">
									<td><?php echo esc_html( $record->record_type ); ?></td>
									<td><?php echo esc_html( $record->host ); ?></td>
									<td class="hf-dns-value"><?php echo esc_html( $record->value ); ?></td>
									<td><?php echo esc_html( $record->ttl ); ?></td>
									<td><?php echo esc_html( ! empty( $record->priority ) ? $record->priority : '&mdash;' ); ?></td>
									<td>
										<button type="button" class="button button-small hf-edit-dns" data-record-id="<?php echo esc_attr( $record->id ); ?>">
											<?php esc_html_e( 'Edit', 'hostforge' ); ?>
										</button>
										<button type="button" class="button button-small hf-delete-dns" data-record-id="<?php echo esc_attr( $record->registrar_record_id ); ?>" data-local-id="<?php echo esc_attr( $record->id ); ?>">
											<?php esc_html_e( 'Delete', 'hostforge' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="6"><?php esc_html_e( 'No DNS records found. Click Sync to fetch from registrar.', 'hostforge' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<!-- Add DNS Record Form -->
				<div class="hf-dns-add-form" id="hf-dns-add-form" data-domain-id="<?php echo esc_attr( $domain_id ); ?>">
					<h3><?php esc_html_e( 'Add DNS Record', 'hostforge' ); ?></h3>
					<div class="hf-dns-form-row">
						<select name="record_type" id="hf-dns-type">
							<option value="A">A</option>
							<option value="AAAA">AAAA</option>
							<option value="CNAME">CNAME</option>
							<option value="MX">MX</option>
							<option value="TXT">TXT</option>
							<option value="NS">NS</option>
							<option value="SRV">SRV</option>
							<option value="CAA">CAA</option>
						</select>
						<input type="text" name="host" id="hf-dns-host" placeholder="@" class="regular-text" />
						<input type="text" name="value" id="hf-dns-value" placeholder="<?php esc_attr_e( 'Value', 'hostforge' ); ?>" class="regular-text" />
						<input type="number" name="ttl" id="hf-dns-ttl" value="3600" min="60" class="small-text" />
						<input type="number" name="priority" id="hf-dns-priority" value="10" min="0" class="small-text" />
						<button type="button" class="button button-primary" id="hf-add-dns-record">
							<?php esc_html_e( 'Add Record', 'hostforge' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Actions -->
			<div class="hf-admin-card">
				<h2><?php esc_html_e( 'Actions', 'hostforge' ); ?></h2>
				<div class="hf-action-buttons">
					<button type="button" class="button" id="hf-sync-domain" data-domain-id="<?php echo esc_attr( $domain_id ); ?>">
						<?php esc_html_e( 'Sync with Registrar', 'hostforge' ); ?>
					</button>
					<button type="button" class="button" id="hf-get-epp" data-domain-id="<?php echo esc_attr( $domain_id ); ?>">
						<?php esc_html_e( 'Get EPP Code', 'hostforge' ); ?>
					</button>
					<?php if ( 'active' === $status ) : ?>
						<button type="button" class="button" id="hf-renew-domain" data-domain-id="<?php echo esc_attr( $domain_id ); ?>">
							<?php esc_html_e( 'Renew Domain', 'hostforge' ); ?>
						</button>
					<?php endif; ?>
				</div>
				<div id="hf-action-result" class="hf-notice" style="display:none;"></div>
			</div>

			<!-- Related Information -->
			<div class="hf-admin-card">
				<h2><?php esc_html_e( 'Related Information', 'hostforge' ); ?></h2>
				<table class="hf-info-table">
					<?php if ( ! empty( $meta['_hf_order_id'] ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Order', 'hostforge' ); ?></th>
							<td>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $meta['_hf_order_id'] ) . '&action=edit' ) ); ?>">
									#<?php echo esc_html( $meta['_hf_order_id'] ); ?>
								</a>
							</td>
						</tr>
					<?php endif; ?>
					<?php if ( ! empty( $meta['_hf_user_id'] ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Customer', 'hostforge' ); ?></th>
							<td>
								<?php
								$user = get_userdata( absint( $meta['_hf_user_id'] ) );
								if ( $user ) :
									?>
									<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . absint( $meta['_hf_user_id'] ) ) ); ?>">
										<?php echo esc_html( $user->display_name ); ?>
									</a>
								<?php else : ?>
									&mdash;
								<?php endif; ?>
							</td>
						</tr>
					<?php endif; ?>
					<?php if ( ! empty( $meta['_hf_linked_service_id'] ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Linked Service', 'hostforge' ); ?></th>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-services&tab=detail&service_id=' . absint( $meta['_hf_linked_service_id'] ) ) ); ?>">
									#<?php echo esc_html( $meta['_hf_linked_service_id'] ); ?>
								</a>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<th><?php esc_html_e( 'Type', 'hostforge' ); ?></th>
						<td><?php echo esc_html( ucfirst( $meta['_hf_type'] ?? 'registration' ) ); ?></td>
					</tr>
				</table>
			</div>

		</div>
	</div>
</div>
