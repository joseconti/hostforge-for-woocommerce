<?php
/**
 * Admin template: TLD Pricing.
 *
 * @package HostForge\Modules\DomainManager\Admin
 * @var array $tld_pricing TLD pricing rows.
 */

defined( 'ABSPATH' ) || exit;

$hf_tabs = array(
	'domains'     => __( 'Domains', 'hostforge' ),
	'tld-pricing' => __( 'TLD Pricing', 'hostforge' ),
	'registrar'   => __( 'Registrar Settings', 'hostforge' ),
);
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Domain Manager', 'hostforge' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $hf_tabs as $slug => $label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-domains&tab=' . $slug ) ); ?>"
				class="nav-tab <?php echo 'tld-pricing' === $slug ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="hf-admin-card">
		<div class="hf-card-header">
			<h2><?php esc_html_e( 'TLD Pricing', 'hostforge' ); ?></h2>
			<div class="hf-card-actions">
				<button type="button" class="button" id="hf-import-tld-pricing">
					<?php esc_html_e( 'Import Common TLDs', 'hostforge' ); ?>
				</button>
				<button type="button" class="button button-primary" id="hf-add-tld-btn">
					<?php esc_html_e( 'Add TLD', 'hostforge' ); ?>
				</button>
			</div>
		</div>

		<table class="widefat striped" id="hf-tld-pricing-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'TLD', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Register Price', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Renew Price', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Transfer Price', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Currency', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Active', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'hostforge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $tld_pricing ) ) : ?>
					<?php foreach ( $tld_pricing as $tld ) : ?>
						<tr data-id="<?php echo esc_attr( $tld->id ); ?>">
							<td><strong>.<?php echo esc_html( $tld->tld ); ?></strong></td>
							<td><?php echo esc_html( number_format( (float) $tld->register_price, 2 ) ); ?></td>
							<td><?php echo esc_html( number_format( (float) $tld->renew_price, 2 ) ); ?></td>
							<td><?php echo esc_html( number_format( (float) $tld->transfer_price, 2 ) ); ?></td>
							<td><?php echo esc_html( $tld->currency ); ?></td>
							<td>
								<?php if ( (int) $tld->is_active ) : ?>
									<span class="hf-badge hf-badge--success"><?php esc_html_e( 'Yes', 'hostforge' ); ?></span>
								<?php else : ?>
									<span class="hf-badge hf-badge--muted"><?php esc_html_e( 'No', 'hostforge' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<button type="button" class="button button-small hf-edit-tld"
									data-id="<?php echo esc_attr( $tld->id ); ?>"
									data-tld="<?php echo esc_attr( $tld->tld ); ?>"
									data-register="<?php echo esc_attr( $tld->register_price ); ?>"
									data-renew="<?php echo esc_attr( $tld->renew_price ); ?>"
									data-transfer="<?php echo esc_attr( $tld->transfer_price ); ?>"
									data-currency="<?php echo esc_attr( $tld->currency ); ?>"
									data-active="<?php echo esc_attr( $tld->is_active ); ?>">
									<?php esc_html_e( 'Edit', 'hostforge' ); ?>
								</button>
								<button type="button" class="button button-small hf-delete-tld" data-id="<?php echo esc_attr( $tld->id ); ?>">
									<?php esc_html_e( 'Delete', 'hostforge' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="7"><?php esc_html_e( 'No TLD pricing configured. Click "Import Common TLDs" to get started.', 'hostforge' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- Add/Edit TLD Modal -->
	<div id="hf-tld-modal" class="hf-modal" style="display:none;">
		<div class="hf-modal__content">
			<h3 id="hf-tld-modal-title"><?php esc_html_e( 'Add TLD', 'hostforge' ); ?></h3>
			<input type="hidden" id="hf-tld-id" value="0" />
			<p>
				<label for="hf-tld-name"><?php esc_html_e( 'TLD', 'hostforge' ); ?></label>
				<input type="text" id="hf-tld-name" class="regular-text" placeholder="com" />
			</p>
			<p>
				<label for="hf-tld-register"><?php esc_html_e( 'Register Price', 'hostforge' ); ?></label>
				<input type="number" id="hf-tld-register" step="0.01" min="0" class="small-text" />
			</p>
			<p>
				<label for="hf-tld-renew"><?php esc_html_e( 'Renew Price', 'hostforge' ); ?></label>
				<input type="number" id="hf-tld-renew" step="0.01" min="0" class="small-text" />
			</p>
			<p>
				<label for="hf-tld-transfer"><?php esc_html_e( 'Transfer Price', 'hostforge' ); ?></label>
				<input type="number" id="hf-tld-transfer" step="0.01" min="0" class="small-text" />
			</p>
			<p>
				<label for="hf-tld-currency"><?php esc_html_e( 'Currency', 'hostforge' ); ?></label>
				<input type="text" id="hf-tld-currency" value="USD" maxlength="3" class="small-text" />
			</p>
			<p>
				<label>
					<input type="checkbox" id="hf-tld-active" checked />
					<?php esc_html_e( 'Active', 'hostforge' ); ?>
				</label>
			</p>
			<div class="hf-modal__actions">
				<button type="button" class="button button-primary" id="hf-save-tld"><?php esc_html_e( 'Save', 'hostforge' ); ?></button>
				<button type="button" class="button" id="hf-cancel-tld"><?php esc_html_e( 'Cancel', 'hostforge' ); ?></button>
			</div>
		</div>
	</div>
</div>
