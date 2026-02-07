<?php
/**
 * Security settings admin template.
 *
 * @package HostForge\Modules\Security
 */

defined( 'ABSPATH' ) || exit;

$settings = $this->module->get_security_settings();
?>
<div class="wrap hf-wrap">
	<h1><?php esc_html_e( 'Security', 'hostforge' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-security&tab=' . $tab_id ) ); ?>"
				class="nav-tab <?php echo $tab === $tab_id ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="hf-settings-form" id="hf-security-settings">
		<form id="hf-security-settings-form">
			<h2><?php esc_html_e( 'Brute Force Protection', 'hostforge' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="max_login_attempts"><?php esc_html_e( 'Max Failed Attempts', 'hostforge' ); ?></label>
					</th>
					<td>
						<input type="number" id="max_login_attempts" name="max_login_attempts"
							value="<?php echo esc_attr( $settings['max_login_attempts'] ); ?>"
							min="1" max="50" class="small-text" />
						<p class="description"><?php esc_html_e( 'Number of failed login attempts before blocking the IP.', 'hostforge' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="lockout_duration"><?php esc_html_e( 'Lockout Duration', 'hostforge' ); ?></label>
					</th>
					<td>
						<input type="number" id="lockout_duration" name="lockout_duration"
							value="<?php echo esc_attr( $settings['lockout_duration'] ); ?>"
							min="1" max="1440" class="small-text" />
						<select id="lockout_duration_unit" name="lockout_duration_unit">
							<option value="minutes" <?php selected( $settings['lockout_duration_unit'], 'minutes' ); ?>><?php esc_html_e( 'Minutes', 'hostforge' ); ?></option>
							<option value="hours" <?php selected( $settings['lockout_duration_unit'], 'hours' ); ?>><?php esc_html_e( 'Hours', 'hostforge' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="ip_allowlist"><?php esc_html_e( 'IP Allowlist', 'hostforge' ); ?></label>
					</th>
					<td>
						<textarea id="ip_allowlist" name="ip_allowlist" rows="4" class="large-text code"><?php echo esc_textarea( $settings['ip_allowlist'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One IP per line. These IPs will never be blocked. Supports CIDR notation (e.g. 192.168.1.0/24).', 'hostforge' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="ip_blocklist"><?php esc_html_e( 'IP Blocklist', 'hostforge' ); ?></label>
					</th>
					<td>
						<textarea id="ip_blocklist" name="ip_blocklist" rows="4" class="large-text code"><?php echo esc_textarea( $settings['ip_blocklist'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One IP per line. These IPs will always be blocked. Supports CIDR notation.', 'hostforge' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Fraud Detection', 'hostforge' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Fraud Detection', 'hostforge' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="fraud_enabled" value="1" <?php checked( $settings['fraud_enabled'], 'yes' ); ?> />
							<?php esc_html_e( 'Check orders for fraud signals at checkout.', 'hostforge' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fraud_block_countries"><?php esc_html_e( 'Blocked Countries', 'hostforge' ); ?></label>
					</th>
					<td>
						<input type="text" id="fraud_block_countries" name="fraud_block_countries"
							value="<?php echo esc_attr( $settings['fraud_block_countries'] ); ?>"
							class="regular-text" />
						<p class="description"><?php esc_html_e( 'Comma-separated ISO country codes (e.g. XX,YY). Orders from these billing countries will be blocked.', 'hostforge' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fraud_block_emails"><?php esc_html_e( 'Blocked Email Patterns', 'hostforge' ); ?></label>
					</th>
					<td>
						<textarea id="fraud_block_emails" name="fraud_block_emails" rows="4" class="large-text code"><?php echo esc_textarea( $settings['fraud_block_emails'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One pattern per line. Use @domain.com to block an email provider, or full email for exact match.', 'hostforge' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'CAPTCHA', 'hostforge' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable CAPTCHA', 'hostforge' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="captcha_enabled" value="1" <?php checked( $settings['captcha_enabled'], 'yes' ); ?> />
							<?php esc_html_e( 'Add CAPTCHA to selected forms.', 'hostforge' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="captcha_provider"><?php esc_html_e( 'Provider', 'hostforge' ); ?></label>
					</th>
					<td>
						<select id="captcha_provider" name="captcha_provider">
							<option value="turnstile" <?php selected( $settings['captcha_provider'], 'turnstile' ); ?>><?php esc_html_e( 'Cloudflare Turnstile', 'hostforge' ); ?></option>
							<option value="recaptcha" <?php selected( $settings['captcha_provider'], 'recaptcha' ); ?>><?php esc_html_e( 'Google reCAPTCHA v2', 'hostforge' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="captcha_site_key"><?php esc_html_e( 'Site Key', 'hostforge' ); ?></label>
					</th>
					<td>
						<input type="text" id="captcha_site_key" name="captcha_site_key"
							value="<?php echo esc_attr( $settings['captcha_site_key'] ); ?>"
							class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="captcha_secret_key"><?php esc_html_e( 'Secret Key', 'hostforge' ); ?></label>
					</th>
					<td>
						<input type="password" id="captcha_secret_key" name="captcha_secret_key"
							value="<?php echo esc_attr( $settings['captcha_secret_key'] ); ?>"
							class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show CAPTCHA On', 'hostforge' ); ?></th>
					<td>
						<fieldset>
							<label><input type="checkbox" name="captcha_on_login" value="1" <?php checked( $settings['captcha_on_login'], 'yes' ); ?> /> <?php esc_html_e( 'Login form', 'hostforge' ); ?></label><br>
							<label><input type="checkbox" name="captcha_on_register" value="1" <?php checked( $settings['captcha_on_register'], 'yes' ); ?> /> <?php esc_html_e( 'Registration form', 'hostforge' ); ?></label><br>
							<label><input type="checkbox" name="captcha_on_checkout" value="1" <?php checked( $settings['captcha_on_checkout'], 'yes' ); ?> /> <?php esc_html_e( 'Checkout', 'hostforge' ); ?></label><br>
							<label><input type="checkbox" name="captcha_on_tickets" value="1" <?php checked( $settings['captcha_on_tickets'], 'yes' ); ?> /> <?php esc_html_e( 'Support tickets', 'hostforge' ); ?></label>
						</fieldset>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Audit Log', 'hostforge' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Audit Log', 'hostforge' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="audit_enabled" value="1" <?php checked( $settings['audit_enabled'], 'yes' ); ?> />
							<?php esc_html_e( 'Record user and system actions.', 'hostforge' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="audit_retention_days"><?php esc_html_e( 'Retention Period', 'hostforge' ); ?></label>
					</th>
					<td>
						<input type="number" id="audit_retention_days" name="audit_retention_days"
							value="<?php echo esc_attr( $settings['audit_retention_days'] ); ?>"
							min="7" max="365" class="small-text" />
						<?php esc_html_e( 'days', 'hostforge' ); ?>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary" id="hf-save-security-settings">
					<?php esc_html_e( 'Save Settings', 'hostforge' ); ?>
				</button>
				<span class="spinner" id="hf-security-spinner"></span>
			</p>
		</form>

		<div id="hf-security-notice" class="notice" style="display: none;"></div>
	</div>
</div>
