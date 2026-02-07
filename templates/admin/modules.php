<?php
/**
 * Admin Modules template.
 *
 * Displays all registered modules with AJAX toggle switches.
 *
 * @package HostForge
 */

defined( 'ABSPATH' ) || exit;

$module_manager = \HostForge\HostForge::instance()->module_manager();
$modules        = $module_manager->get_all_modules_info();
?>
<div class="wrap hostforge-wrap">
	<h1><?php esc_html_e( 'HostForge Modules', 'hostforge' ); ?></h1>
	<p><?php esc_html_e( 'Enable or disable modules to customize your HostForge installation.', 'hostforge' ); ?></p>

	<div class="hostforge-modules-grid">
		<?php foreach ( $modules as $id => $module ) : ?>
			<div class="hostforge-module-card <?php echo $module['active'] ? 'is-active' : ''; ?>" data-module-id="<?php echo esc_attr( $id ); ?>">
				<div class="hostforge-module-header">
					<h3><?php echo esc_html( $module['name'] ); ?></h3>
					<label class="hostforge-toggle">
						<input
							type="checkbox"
							class="hf-module-toggle"
							data-module-id="<?php echo esc_attr( $id ); ?>"
							<?php checked( $module['active'] ); ?>
						/>
						<span class="hostforge-toggle-slider"></span>
					</label>
				</div>
				<p class="hostforge-module-description"><?php echo esc_html( $module['description'] ); ?></p>
				<?php if ( ! empty( $module['dependencies'] ) ) : ?>
					<p class="hostforge-module-deps">
						<strong><?php esc_html_e( 'Requires:', 'hostforge' ); ?></strong>
						<?php echo esc_html( implode( ', ', $module['dependencies'] ) ); ?>
					</p>
				<?php endif; ?>
				<div class="hostforge-module-status">
					<span class="hf-badge <?php echo $module['active'] ? 'hf-badge--success' : 'hf-badge--inactive'; ?>">
						<?php echo $module['active'] ? esc_html__( 'Active', 'hostforge' ) : esc_html__( 'Inactive', 'hostforge' ); ?>
					</span>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
