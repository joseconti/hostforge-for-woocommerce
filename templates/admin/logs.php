<?php
/**
 * Admin Logs template.
 *
 * Displays log entries with filters for module, level and search.
 *
 * @package HostForge
 */

defined( 'ABSPATH' ) || exit;

$current_module = isset( $_GET['module'] ) ? sanitize_text_field( wp_unslash( $_GET['module'] ) ) : '';
$current_level  = isset( $_GET['level'] ) ? sanitize_text_field( wp_unslash( $_GET['level'] ) ) : '';
$search         = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$paged          = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

// Verify nonce for clear action.
if ( isset( $_GET['action'], $_GET['_wpnonce'] ) && 'clear' === $_GET['action'] ) {
	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'hf_clear_logs' ) && current_user_can( 'manage_hostforge' ) ) {
		\HostForge\Admin\HF_Log_Viewer::clear_all();
		wp_safe_redirect( admin_url( 'admin.php?page=hostforge-logs&cleared=1' ) );
		exit;
	}
}

$result  = \HostForge\Admin\HF_Log_Viewer::get_logs(
	array(
		'module' => $current_module,
		'level'  => $current_level,
		'search' => $search,
		'page'   => $paged,
	)
);
$modules = \HostForge\Admin\HF_Log_Viewer::get_modules();
$levels  = array( 'debug', 'info', 'notice', 'warning', 'error', 'critical' );
?>
<div class="wrap hostforge-wrap">
	<h1>
		<?php esc_html_e( 'HostForge Logs', 'hostforge' ); ?>
		<a
			href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=hostforge-logs&action=clear' ), 'hf_clear_logs' ) ); ?>"
			class="page-title-action"
			onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear all logs?', 'hostforge' ) ); ?>');"
		>
			<?php esc_html_e( 'Clear All Logs', 'hostforge' ); ?>
		</a>
	</h1>

	<?php if ( isset( $_GET['cleared'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'All logs have been cleared.', 'hostforge' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Filters -->
	<form method="get" class="hostforge-log-filters">
		<input type="hidden" name="page" value="hostforge-logs" />

		<select name="module">
			<option value=""><?php esc_html_e( 'All Modules', 'hostforge' ); ?></option>
			<?php foreach ( $modules as $mod ) : ?>
				<option value="<?php echo esc_attr( $mod ); ?>" <?php selected( $current_module, $mod ); ?>>
					<?php echo esc_html( $mod ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<select name="level">
			<option value=""><?php esc_html_e( 'All Levels', 'hostforge' ); ?></option>
			<?php foreach ( $levels as $lvl ) : ?>
				<option value="<?php echo esc_attr( $lvl ); ?>" <?php selected( $current_level, $lvl ); ?>>
					<?php echo esc_html( ucfirst( $lvl ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<input
			type="search"
			name="s"
			value="<?php echo esc_attr( $search ); ?>"
			placeholder="<?php esc_attr_e( 'Search logs...', 'hostforge' ); ?>"
		/>

		<?php submit_button( __( 'Filter', 'hostforge' ), 'secondary', 'filter', false ); ?>
	</form>

	<!-- Log Table -->
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th class="column-id"><?php esc_html_e( 'ID', 'hostforge' ); ?></th>
				<th class="column-date"><?php esc_html_e( 'Date', 'hostforge' ); ?></th>
				<th class="column-level"><?php esc_html_e( 'Level', 'hostforge' ); ?></th>
				<th class="column-module"><?php esc_html_e( 'Module', 'hostforge' ); ?></th>
				<th class="column-message"><?php esc_html_e( 'Message', 'hostforge' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $result['items'] ) ) : ?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No log entries found.', 'hostforge' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $result['items'] as $log ) : ?>
					<tr>
						<td><?php echo esc_html( (string) $log->id ); ?></td>
						<td><?php echo esc_html( $log->created_at ); ?></td>
						<td>
							<span class="hf-badge hf-badge--<?php echo esc_attr( $log->level ); ?>">
								<?php echo esc_html( ucfirst( $log->level ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $log->module ); ?></td>
						<td>
							<?php echo esc_html( $log->message ); ?>
							<?php if ( ! empty( $log->context ) ) : ?>
								<button type="button" class="button-link hf-toggle-context" data-target="context-<?php echo esc_attr( (string) $log->id ); ?>">
									<?php esc_html_e( 'Show context', 'hostforge' ); ?>
								</button>
								<pre class="hf-log-context" id="context-<?php echo esc_attr( (string) $log->id ); ?>" style="display:none;">
									<?php echo esc_html( wp_json_encode( json_decode( $log->context ), JSON_PRETTY_PRINT ) ); ?>
								</pre>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Pagination -->
	<?php if ( $result['pages'] > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base'    => add_query_arg( 'paged', '%#%' ),
							'format'  => '',
							'current' => $paged,
							'total'   => $result['pages'],
						)
					)
				);
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
