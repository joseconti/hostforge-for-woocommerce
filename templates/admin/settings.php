<?php
/**
 * Admin Settings template.
 *
 * @package HostForge
 */

defined( 'ABSPATH' ) || exit;

// Initialize settings registration.
\HostForge\Admin\HF_Settings::init();
?>
<div class="wrap hostforge-wrap">
	<h1><?php esc_html_e( 'HostForge Settings', 'hostforge' ); ?></h1>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( \HostForge\Admin\HF_Settings::get_option_group() );
		do_settings_sections( \HostForge\Admin\HF_Settings::get_option_group() );
		submit_button();
		?>
	</form>
</div>
