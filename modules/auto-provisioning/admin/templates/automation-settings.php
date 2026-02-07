<?php
/**
 * Automation settings admin template.
 *
 * @package HostForge\Modules\AutoProvisioning\Admin
 * @var array $settings Current settings values.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Automation Settings', 'hostforge' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-services' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Back to Services', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<div id="hf-settings-notice" class="notice" style="display:none;"><p></p></div>

	<form id="hf-automation-settings-form" class="hf-form">
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="hf-provision-on-processing"><?php esc_html_e( 'Provision on Processing', 'hostforge' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" id="hf-provision-on-processing" name="provision_on_processing" value="yes" <?php checked( $settings['provision_on_processing'], 'yes' ); ?> />
						<?php esc_html_e( 'Start provisioning when order status is "Processing" (instead of waiting for "Completed")', 'hostforge' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Enable this if you want to provision accounts immediately on payment, without waiting for manual order completion.', 'hostforge' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hf-auto-suspend-days"><?php esc_html_e( 'Auto-Suspend Grace Period', 'hostforge' ); ?></label>
				</th>
				<td>
					<input type="number" id="hf-auto-suspend-days" name="auto_suspend_days" value="<?php echo esc_attr( $settings['auto_suspend_days'] ); ?>" min="0" max="90" class="small-text" />
					<?php esc_html_e( 'days', 'hostforge' ); ?>
					<p class="description">
						<?php esc_html_e( 'Number of days after subscription expiry before auto-suspending the service. Set to 0 to disable.', 'hostforge' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hf-auto-terminate-days"><?php esc_html_e( 'Auto-Terminate After Suspension', 'hostforge' ); ?></label>
				</th>
				<td>
					<input type="number" id="hf-auto-terminate-days" name="auto_terminate_days" value="<?php echo esc_attr( $settings['auto_terminate_days'] ); ?>" min="0" max="365" class="small-text" />
					<?php esc_html_e( 'days', 'hostforge' ); ?>
					<p class="description">
						<?php esc_html_e( 'Number of days a service stays suspended before being terminated. Set to 0 to disable.', 'hostforge' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hf-password-length"><?php esc_html_e( 'Generated Password Length', 'hostforge' ); ?></label>
				</th>
				<td>
					<input type="number" id="hf-password-length" name="password_length" value="<?php echo esc_attr( $settings['password_length'] ); ?>" min="12" max="32" class="small-text" />
					<?php esc_html_e( 'characters', 'hostforge' ); ?>
					<p class="description">
						<?php esc_html_e( 'Length of auto-generated panel passwords. Minimum 12, maximum 32.', 'hostforge' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary" id="hf-save-automation-settings">
				<?php esc_html_e( 'Save Settings', 'hostforge' ); ?>
			</button>
		</p>
	</form>
</div>
