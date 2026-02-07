<?php
/**
 * Server Add/Edit Form Template.
 *
 * @package HostForge\Modules\ServerManager\Admin
 * @var \WP_Post|null $server         Server post object or null for new.
 * @var array         $meta           Server meta values.
 * @var array         $groups         Available server groups.
 * @var array         $current_groups Current server group IDs.
 */

defined( 'ABSPATH' ) || exit;

$is_edit  = ! empty( $server );
$hf_title = $is_edit
	/* translators: %s: server name */
	? sprintf( __( 'Edit Server: %s', 'hostforge' ), $server->post_title )
	: __( 'Add New Server', 'hostforge' );
?>
<div class="wrap hf-wrap">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( $hf_title ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-servers' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Back to Servers', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<div id="hf-server-notices"></div>

	<form id="hf-server-form" class="hf-form">
		<input type="hidden" name="server_id" value="<?php echo esc_attr( $is_edit ? $server->ID : 0 ); ?>" />

		<div class="hf-form-grid">
			<!-- Left Column -->
			<div class="hf-form-col">
				<div class="hf-card">
					<h2 class="hf-card__title"><?php esc_html_e( 'Server Details', 'hostforge' ); ?></h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="hf-server-name"><?php esc_html_e( 'Server Name', 'hostforge' ); ?></label>
							</th>
							<td>
								<input type="text" id="hf-server-name" name="name" class="regular-text"
									value="<?php echo esc_attr( $is_edit ? $server->post_title : '' ); ?>"
									placeholder="<?php esc_attr_e( 'e.g. Web Server 1', 'hostforge' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hf-panel-type"><?php esc_html_e( 'Panel Type', 'hostforge' ); ?> <span class="required">*</span></label>
							</th>
							<td>
								<select id="hf-panel-type" name="panel_type" required>
									<option value=""><?php esc_html_e( 'Select panel type...', 'hostforge' ); ?></option>
									<option value="cpanel" <?php selected( $meta['_hf_panel_type'] ?? '', 'cpanel' ); ?>><?php esc_html_e( 'cPanel/WHM', 'hostforge' ); ?></option>
									<option value="plesk" <?php selected( $meta['_hf_panel_type'] ?? '', 'plesk' ); ?>><?php esc_html_e( 'Plesk', 'hostforge' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hf-hostname"><?php esc_html_e( 'Hostname / IP', 'hostforge' ); ?> <span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="hf-hostname" name="hostname" class="regular-text" required
									value="<?php echo esc_attr( $meta['_hf_hostname'] ?? '' ); ?>"
									placeholder="<?php esc_attr_e( 'e.g. server1.example.com', 'hostforge' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hf-port"><?php esc_html_e( 'Port', 'hostforge' ); ?></label>
							</th>
							<td>
								<input type="number" id="hf-port" name="port" class="small-text"
									value="<?php echo esc_attr( $meta['_hf_port'] ?? '' ); ?>"
									placeholder="<?php esc_attr_e( 'Auto', 'hostforge' ); ?>" />
								<p class="description"><?php esc_html_e( 'Default: 2087 (cPanel) / 8443 (Plesk)', 'hostforge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hf-max-accounts"><?php esc_html_e( 'Max Accounts', 'hostforge' ); ?></label>
							</th>
							<td>
								<input type="number" id="hf-max-accounts" name="max_accounts" class="small-text" min="0"
									value="<?php echo esc_attr( $meta['_hf_max_accounts'] ?? '' ); ?>" />
								<p class="description"><?php esc_html_e( 'Leave 0 for unlimited.', 'hostforge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hf-server-group"><?php esc_html_e( 'Server Group', 'hostforge' ); ?></label>
							</th>
							<td>
								<select id="hf-server-group" name="server_group">
									<option value=""><?php esc_html_e( 'No group', 'hostforge' ); ?></option>
									<?php foreach ( $groups as $group ) : ?>
									<option value="<?php echo esc_attr( $group->term_id ); ?>"
										<?php selected( in_array( $group->term_id, $current_groups, true ) ); ?>>
										<?php echo esc_html( $group->name ); ?>
									</option>
									<?php endforeach; ?>
								</select>
								<input type="text" id="hf-new-group" name="new_group" class="regular-text hf-hidden"
									placeholder="<?php esc_attr_e( 'New group name...', 'hostforge' ); ?>" />
								<button type="button" id="hf-toggle-new-group" class="button button-small">
									<?php esc_html_e( 'New Group', 'hostforge' ); ?>
								</button>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hf-nameservers"><?php esc_html_e( 'Nameservers', 'hostforge' ); ?></label>
							</th>
							<td>
								<?php
								$nameservers = $meta['_hf_nameservers'] ?? array();
								$ns_text     = is_array( $nameservers ) ? implode( "\n", $nameservers ) : '';
								?>
								<textarea id="hf-nameservers" name="nameservers" rows="3" class="large-text"
									placeholder="<?php esc_attr_e( "ns1.example.com\nns2.example.com", 'hostforge' ); ?>"><?php echo esc_textarea( $ns_text ); ?></textarea>
								<p class="description"><?php esc_html_e( 'One nameserver per line.', 'hostforge' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div class="hf-card">
					<h2 class="hf-card__title"><?php esc_html_e( 'Notes', 'hostforge' ); ?></h2>
					<textarea name="notes" rows="4" class="large-text"
						placeholder="<?php esc_attr_e( 'Internal notes about this server...', 'hostforge' ); ?>"><?php echo esc_textarea( $meta['_hf_notes'] ?? '' ); ?></textarea>
				</div>
			</div>

			<!-- Right Column -->
			<div class="hf-form-col">
				<div class="hf-card">
					<h2 class="hf-card__title"><?php esc_html_e( 'Authentication', 'hostforge' ); ?></h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="hf-auth-method"><?php esc_html_e( 'Auth Method', 'hostforge' ); ?></label>
							</th>
							<td>
								<select id="hf-auth-method" name="auth_method">
									<option value="token" <?php selected( $meta['_hf_auth_method'] ?? 'token', 'token' ); ?>><?php esc_html_e( 'API Token / Key', 'hostforge' ); ?></option>
									<option value="password" <?php selected( $meta['_hf_auth_method'] ?? '', 'password' ); ?>><?php esc_html_e( 'Username & Password', 'hostforge' ); ?></option>
								</select>
							</td>
						</tr>
						<tr class="hf-auth-field hf-auth-token">
							<th scope="row">
								<label for="hf-api-token"><?php esc_html_e( 'API Token / Key', 'hostforge' ); ?></label>
							</th>
							<td>
								<input type="password" id="hf-api-token" name="api_token" class="regular-text"
									autocomplete="new-password"
									placeholder="<?php echo esc_attr( ( $meta['_hf_api_token_set'] ?? false ) ? __( 'Token saved (leave blank to keep)', 'hostforge' ) : '' ); ?>" />
							</td>
						</tr>
						<tr class="hf-auth-field hf-auth-all">
							<th scope="row">
								<label for="hf-username"><?php esc_html_e( 'Username', 'hostforge' ); ?></label>
							</th>
							<td>
								<input type="text" id="hf-username" name="username" class="regular-text"
									autocomplete="off"
									placeholder="<?php echo esc_attr( ( $meta['_hf_username_set'] ?? false ) ? __( 'Username saved (leave blank to keep)', 'hostforge' ) : 'root' ); ?>" />
							</td>
						</tr>
						<tr class="hf-auth-field hf-auth-password">
							<th scope="row">
								<label for="hf-password"><?php esc_html_e( 'Password', 'hostforge' ); ?></label>
							</th>
							<td>
								<input type="password" id="hf-password" name="password" class="regular-text"
									autocomplete="new-password"
									placeholder="<?php echo esc_attr( ( $meta['_hf_password_set'] ?? false ) ? __( 'Password saved (leave blank to keep)', 'hostforge' ) : '' ); ?>" />
							</td>
						</tr>
					</table>

					<div class="hf-card__actions">
						<button type="button" id="hf-test-connection" class="button">
							<?php esc_html_e( 'Test Connection', 'hostforge' ); ?>
						</button>
						<span id="hf-test-result"></span>
					</div>
				</div>

				<?php if ( $is_edit ) : ?>
				<div class="hf-card">
					<h2 class="hf-card__title"><?php esc_html_e( 'Packages', 'hostforge' ); ?></h2>

					<?php
					$packages = $meta['_hf_packages_cache'] ?? array();
					if ( ! empty( $packages ) && is_array( $packages ) ) :
						?>
						<ul class="hf-package-list">
							<?php foreach ( $packages as $pkg ) : ?>
							<li><?php echo esc_html( $pkg['name'] ?? '' ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="hf-muted"><?php esc_html_e( 'No packages cached.', 'hostforge' ); ?></p>
					<?php endif; ?>

					<div class="hf-card__actions">
						<button type="button" id="hf-fetch-packages" class="button" data-server-id="<?php echo esc_attr( $server->ID ); ?>">
							<?php esc_html_e( 'Fetch Packages', 'hostforge' ); ?>
						</button>
						<span id="hf-fetch-result"></span>
					</div>
				</div>

				<div class="hf-card">
					<h2 class="hf-card__title"><?php esc_html_e( 'Status', 'hostforge' ); ?></h2>
					<table class="hf-info-table">
						<tr>
							<td><?php esc_html_e( 'Status', 'hostforge' ); ?></td>
							<td><?php echo wp_kses_post( hf_format_status_badge( $meta['_hf_status'] ?? 'unknown' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Accounts', 'hostforge' ); ?></td>
							<td>
								<?php
								$current = (int) ( $meta['_hf_current_accounts'] ?? 0 );
								$max     = (int) ( $meta['_hf_max_accounts'] ?? 0 );
								echo esc_html( $current );
								if ( $max > 0 ) {
									echo ' / ' . esc_html( $max );
								}
								?>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Last Check', 'hostforge' ); ?></td>
							<td>
								<?php
								$last_check = $meta['_hf_last_check'] ?? '';
								if ( $last_check ) {
									echo esc_html( human_time_diff( strtotime( $last_check ), current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'hostforge' );
								} else {
									esc_html_e( 'Never', 'hostforge' );
								}
								?>
							</td>
						</tr>
					</table>
				</div>
				<?php endif; ?>

				<div class="hf-form-actions">
					<button type="submit" class="button button-primary button-large" id="hf-save-server">
						<?php echo esc_html( $is_edit ? __( 'Update Server', 'hostforge' ) : __( 'Add Server', 'hostforge' ) ); ?>
					</button>

					<?php if ( $is_edit ) : ?>
					<button type="button" class="button button-link-delete hf-delete-server" data-server-id="<?php echo esc_attr( $server->ID ); ?>">
						<?php esc_html_e( 'Delete Server', 'hostforge' ); ?>
					</button>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</form>
</div>
