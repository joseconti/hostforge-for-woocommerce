<?php
/**
 * Admin template: Registrar Settings.
 *
 * @package HostForge\Modules\DomainManager\Admin
 */

defined( 'ABSPATH' ) || exit;

$hf_tabs = array(
	'domains'     => __( 'Domains', 'hostforge' ),
	'tld-pricing' => __( 'TLD Pricing', 'hostforge' ),
	'registrar'   => __( 'Registrar Settings', 'hostforge' ),
);

$api_user      = get_option( 'hf_namecheap_api_user', '' );
$username      = get_option( 'hf_namecheap_username', '' );
$client_ip     = get_option( 'hf_namecheap_client_ip', '' );
$sandbox       = get_option( 'hf_namecheap_sandbox', 'no' );
$has_api_key   = ! empty( get_option( 'hf_namecheap_api_key', '' ) );
$auto_register = get_option( 'hf_domain_auto_register', 'yes' );
$renew_days    = get_option( 'hf_domain_auto_renew_days', 14 );
$reminder_days = get_option( 'hf_domain_expiry_reminder_days', '30,14,7,1' );
$default_ns    = get_option( 'hf_domain_default_nameservers', '' );
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Domain Manager', 'hostforge' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $hf_tabs as $slug => $label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-domains&tab=' . $slug ) ); ?>"
				class="nav-tab <?php echo 'registrar' === $slug ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="hf-admin-grid">
		<!-- Registrar Credentials -->
		<div class="hf-admin-column">
			<div class="hf-admin-card">
				<h2><?php esc_html_e( 'Namecheap API Settings', 'hostforge' ); ?></h2>

				<form id="hf-registrar-settings-form">
					<table class="form-table">
						<tr>
							<th><label for="hf-api-user"><?php esc_html_e( 'API User', 'hostforge' ); ?></label></th>
							<td>
								<input type="text" id="hf-api-user" name="api_user" class="regular-text" value="<?php echo esc_attr( $api_user ); ?>" />
							</td>
						</tr>
						<tr>
							<th><label for="hf-api-key"><?php esc_html_e( 'API Key', 'hostforge' ); ?></label></th>
							<td>
								<input type="password" id="hf-api-key" name="api_key" class="regular-text" value="<?php echo esc_attr( $has_api_key ? '********' : '' ); ?>" />
								<p class="description"><?php esc_html_e( 'Leave as ******** to keep the current key.', 'hostforge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="hf-username"><?php esc_html_e( 'Username', 'hostforge' ); ?></label></th>
							<td>
								<input type="text" id="hf-username" name="username" class="regular-text" value="<?php echo esc_attr( $username ); ?>" />
								<p class="description"><?php esc_html_e( 'Leave empty to use API User as username.', 'hostforge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="hf-client-ip"><?php esc_html_e( 'Client IP', 'hostforge' ); ?></label></th>
							<td>
								<input type="text" id="hf-client-ip" name="client_ip" class="regular-text" value="<?php echo esc_attr( $client_ip ); ?>" />
								<p class="description"><?php esc_html_e( 'Server IP address whitelisted in Namecheap API settings.', 'hostforge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="hf-sandbox"><?php esc_html_e( 'Sandbox Mode', 'hostforge' ); ?></label></th>
							<td>
								<label>
									<input type="checkbox" id="hf-sandbox" name="sandbox" value="yes" <?php checked( 'yes', $sandbox ); ?> />
									<?php esc_html_e( 'Enable sandbox/test mode', 'hostforge' ); ?>
								</label>
							</td>
						</tr>
					</table>

					<p>
						<button type="button" class="button button-primary" id="hf-save-registrar-settings">
							<?php esc_html_e( 'Save Settings', 'hostforge' ); ?>
						</button>
						<button type="button" class="button" id="hf-test-registrar">
							<?php esc_html_e( 'Test Connection', 'hostforge' ); ?>
						</button>
					</p>

					<div id="hf-registrar-result" class="hf-notice" style="display:none;"></div>
				</form>
			</div>
		</div>

		<!-- Domain Settings -->
		<div class="hf-admin-column">
			<div class="hf-admin-card">
				<h2><?php esc_html_e( 'Domain Settings', 'hostforge' ); ?></h2>

				<form id="hf-domain-settings-form">
					<table class="form-table">
						<tr>
							<th><label for="hf-auto-register"><?php esc_html_e( 'Auto-Register', 'hostforge' ); ?></label></th>
							<td>
								<label>
									<input type="checkbox" id="hf-auto-register" name="auto_register" value="yes" <?php checked( 'yes', $auto_register ); ?> />
									<?php esc_html_e( 'Automatically register/transfer domains when orders are completed', 'hostforge' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><label for="hf-renew-days"><?php esc_html_e( 'Auto-Renew Days', 'hostforge' ); ?></label></th>
							<td>
								<input type="number" id="hf-renew-days" name="auto_renew_days" class="small-text" value="<?php echo esc_attr( $renew_days ); ?>" min="1" max="90" />
								<p class="description"><?php esc_html_e( 'Days before expiry to trigger auto-renewal.', 'hostforge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="hf-reminder-days"><?php esc_html_e( 'Expiry Reminders', 'hostforge' ); ?></label></th>
							<td>
								<input type="text" id="hf-reminder-days" name="expiry_reminder_days" class="regular-text" value="<?php echo esc_attr( $reminder_days ); ?>" />
								<p class="description"><?php esc_html_e( 'Comma-separated days before expiry to send reminders (e.g. 30,14,7,1).', 'hostforge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="hf-default-ns"><?php esc_html_e( 'Default Nameservers', 'hostforge' ); ?></label></th>
							<td>
								<textarea id="hf-default-ns" name="default_nameservers" rows="4" class="large-text"><?php echo esc_textarea( $default_ns ); ?></textarea>
								<p class="description"><?php esc_html_e( 'One nameserver per line. Used for new domain registrations.', 'hostforge' ); ?></p>
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>
