<?php
/**
 * Server Admin.
 *
 * Handles admin screens for the Server Manager module:
 * server list, add/edit form, test connection, fetch packages.
 *
 * @package HostForge\Modules\ServerManager\Admin
 */

namespace HostForge\Modules\ServerManager\Admin;

use HostForge\Modules\ServerManager\HF_Server_Manager_Module;
use HostForge\HF_Encryption;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Server_Admin
 */
class HF_Server_Admin {

	/**
	 * Module instance.
	 *
	 * @var HF_Server_Manager_Module
	 */
	private HF_Server_Manager_Module $module;

	/**
	 * Constructor.
	 *
	 * @param HF_Server_Manager_Module $module Module instance.
	 */
	public function __construct( HF_Server_Manager_Module $module ) {
		$this->module = $module;
	}

	/**
	 * Initialize admin hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_hf_test_server_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_hf_fetch_server_packages', array( $this, 'ajax_fetch_packages' ) );
		add_action( 'wp_ajax_hf_save_server', array( $this, 'ajax_save_server' ) );
		add_action( 'wp_ajax_hf_delete_server', array( $this, 'ajax_delete_server' ) );
	}

	/**
	 * Enqueue assets on server admin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$screen = get_current_screen();
		if ( ! $screen || ! str_contains( $screen->id, 'hostforge-servers' ) ) {
			return;
		}

		wp_enqueue_style(
			'hostforge-server-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/css/server-admin.css',
			array( 'hostforge-admin' ),
			HOSTFORGE_VERSION
		);

		wp_enqueue_script(
			'hostforge-server-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/js/server-admin.js',
			array( 'hostforge-admin' ),
			HOSTFORGE_VERSION,
			true
		);

		wp_localize_script(
			'hostforge-server-admin',
			'hostforgeServer',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hf_server_nonce' ),
				'i18n'    => array(
					'testing'          => __( 'Testing connection...', 'hostforge' ),
					'testSuccess'      => __( 'Connection successful!', 'hostforge' ),
					'testFailed'       => __( 'Connection failed.', 'hostforge' ),
					'fetching'         => __( 'Fetching packages...', 'hostforge' ),
					'fetchSuccess'     => __( 'Packages fetched successfully.', 'hostforge' ),
					'fetchFailed'      => __( 'Could not fetch packages.', 'hostforge' ),
					'saving'           => __( 'Saving...', 'hostforge' ),
					'saved'            => __( 'Server saved.', 'hostforge' ),
					'confirmDelete'    => __( 'Are you sure you want to delete this server? This cannot be undone.', 'hostforge' ),
					'deleting'         => __( 'Deleting...', 'hostforge' ),
					'deleted'          => __( 'Server deleted.', 'hostforge' ),
					'error'            => __( 'An error occurred.', 'hostforge' ),
					'requiredHostname' => __( 'Server hostname is required.', 'hostforge' ),
					'requiredAuth'     => __( 'Please provide authentication credentials.', 'hostforge' ),
				),
			)
		);
	}

	/**
	 * Render the main servers page (list or form).
	 *
	 * @return void
	 */
	public function render_servers_page(): void {
		if ( ! current_user_can( 'manage_hostforge_servers' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hostforge' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$server_id = isset( $_GET['server_id'] ) ? absint( $_GET['server_id'] ) : 0;

		if ( 'edit' === $action || 'add' === $action ) {
			$this->render_server_form( $server_id );
		} elseif ( 'monitor' === $action && $server_id > 0 ) {
			$this->render_server_monitor( $server_id );
		} else {
			$this->render_server_list();
		}
	}

	/**
	 * Render the server list.
	 *
	 * @return void
	 */
	private function render_server_list(): void {
		$list_table = new HF_Server_List_Table();
		$list_table->prepare_items();

		$template = $this->module->get_module_dir() . 'admin/templates/server-list.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the add/edit server form.
	 *
	 * @param int $server_id Server post ID (0 for new).
	 * @return void
	 */
	private function render_server_form( int $server_id = 0 ): void {
		$server = null;
		$meta   = array();

		if ( $server_id > 0 ) {
			$server = get_post( $server_id );
			if ( ! $server || 'hf_server' !== $server->post_type ) {
				wp_die( esc_html__( 'Server not found.', 'hostforge' ) );
			}

			foreach ( array_keys( HF_Server_Manager_Module::get_server_meta_keys() ) as $key ) {
				$meta[ $key ] = get_post_meta( $server_id, $key, true );
			}

			// Decrypt sensitive fields for form display (show masked).
			$meta['_hf_api_token_set'] = ! empty( $meta['_hf_api_token'] );
			$meta['_hf_username_set']  = ! empty( $meta['_hf_username'] );
			$meta['_hf_password_set']  = ! empty( $meta['_hf_password'] );
		}

		// Get server groups.
		$groups = get_terms(
			array(
				'taxonomy'   => 'hf_server_group',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $groups ) ) {
			$groups = array();
		}

		$current_groups = array();
		if ( $server_id > 0 ) {
			$current_groups = wp_get_object_terms( $server_id, 'hf_server_group', array( 'fields' => 'ids' ) );
			if ( is_wp_error( $current_groups ) ) {
				$current_groups = array();
			}
		}

		/**
		 * Filter the server form fields data before rendering.
		 *
		 * Allows modification of the server meta values and groups
		 * passed to the server add/edit form template.
		 *
		 * @since 1.0.0
		 *
		 * @param array    $form_data {
		 *     Form data for the server.
		 *
		 *     @type array    $meta           Server meta values.
		 *     @type array    $groups         Available server groups.
		 *     @type array    $current_groups Currently assigned group IDs.
		 * }
		 * @param int      $server_id Server post ID (0 for new).
		 * @param \WP_Post|null $server Server post object or null.
		 */
		$form_data = apply_filters(
			'hostforge_server_form_fields',
			array(
				'meta'           => $meta,
				'groups'         => $groups,
				'current_groups' => $current_groups,
			),
			$server_id,
			$server
		);

		$meta           = $form_data['meta'];
		$groups         = $form_data['groups'];
		$current_groups = $form_data['current_groups'];

		$template = $this->module->get_module_dir() . 'admin/templates/server-form.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the server status monitor.
	 *
	 * @param int $server_id Server post ID.
	 * @return void
	 */
	private function render_server_monitor( int $server_id ): void {
		$server = get_post( $server_id );
		if ( ! $server || 'hf_server' !== $server->post_type ) {
			wp_die( esc_html__( 'Server not found.', 'hostforge' ) );
		}

		$meta = array();
		foreach ( array_keys( HF_Server_Manager_Module::get_server_meta_keys() ) as $key ) {
			$meta[ $key ] = get_post_meta( $server_id, $key, true );
		}

		$provider = $this->module->get_provider( $server_id );
		$stats    = null;

		if ( $provider ) {
			// Use cached stats if fresh enough.
			$cache_time = (int) ( $meta['_hf_server_stats_cache_time'] ?? 0 );
			if ( ( time() - $cache_time ) < 300 ) {
				$stats = $meta['_hf_server_stats_cache'];
			} else {
				$stats_result = $provider->get_server_stats();
				if ( $stats_result['success'] ) {
					$stats = $stats_result['data'];
				}
			}
		}

		$template = $this->module->get_module_dir() . 'admin/templates/server-monitor.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * AJAX: Test server connection.
	 *
	 * @return void
	 */
	public function ajax_test_connection(): void {
		check_ajax_referer( 'hf_server_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_servers' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$server_id = isset( $_POST['server_id'] ) ? absint( $_POST['server_id'] ) : 0;

		if ( $server_id > 0 ) {
			$provider = $this->module->get_provider( $server_id );

			if ( ! $provider ) {
				wp_send_json_error( array( 'message' => __( 'Invalid panel type.', 'hostforge' ) ) );
			}

			$result = $provider->test_connection();
			if ( $result['success'] ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result );
			}
		}

		// Test with provided credentials (for unsaved servers).
		$panel_type  = isset( $_POST['panel_type'] ) ? sanitize_text_field( wp_unslash( $_POST['panel_type'] ) ) : '';
		$hostname    = isset( $_POST['hostname'] ) ? sanitize_text_field( wp_unslash( $_POST['hostname'] ) ) : '';
		$port        = isset( $_POST['port'] ) ? absint( $_POST['port'] ) : 0;
		$auth_method = isset( $_POST['auth_method'] ) ? sanitize_text_field( wp_unslash( $_POST['auth_method'] ) ) : 'token';
		$api_token   = isset( $_POST['api_token'] ) ? sanitize_text_field( wp_unslash( $_POST['api_token'] ) ) : '';
		$username    = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
		$password    = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';

		if ( empty( $hostname ) || empty( $panel_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Hostname and panel type are required.', 'hostforge' ) ) );
		}

		// Create a temporary server post for testing.
		$temp_id = wp_insert_post(
			array(
				'post_type'   => 'hf_server',
				'post_title'  => 'temp_test_' . time(),
				'post_status' => 'draft',
			)
		);

		if ( is_wp_error( $temp_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not create temporary server.', 'hostforge' ) ) );
		}

		update_post_meta( $temp_id, '_hf_panel_type', $panel_type );
		update_post_meta( $temp_id, '_hf_hostname', $hostname );
		update_post_meta( $temp_id, '_hf_port', ! empty( $port ) ? $port : ( 'cpanel' === $panel_type ? 2087 : 8443 ) );
		update_post_meta( $temp_id, '_hf_auth_method', $auth_method );
		update_post_meta( $temp_id, '_hf_api_token', HF_Encryption::encrypt( $api_token ) );
		update_post_meta( $temp_id, '_hf_username', HF_Encryption::encrypt( $username ) );
		update_post_meta( $temp_id, '_hf_password', HF_Encryption::encrypt( $password ) );

		$provider = $this->module->get_provider( $temp_id );

		// Clean up temp post.
		wp_delete_post( $temp_id, true );

		if ( ! $provider ) {
			wp_send_json_error( array( 'message' => __( 'Invalid panel type.', 'hostforge' ) ) );
		}

		$result = $provider->test_connection();

		/**
		 * Filter the server connection test result before returning JSON.
		 *
		 * Allows modification of the test result, e.g. to add additional
		 * diagnostics or validation information.
		 *
		 * @since 1.0.0
		 *
		 * @param array $result    Test result with keys: success, message.
		 * @param int   $server_id Server post ID (may be 0 for temp servers).
		 */
		$result = apply_filters( 'hostforge_server_test_result', $result, $server_id );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX: Fetch packages from server.
	 *
	 * @return void
	 */
	public function ajax_fetch_packages(): void {
		check_ajax_referer( 'hf_server_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_servers' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$server_id = isset( $_POST['server_id'] ) ? absint( $_POST['server_id'] ) : 0;

		if ( $server_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid server ID.', 'hostforge' ) ) );
		}

		$provider = $this->module->get_provider( $server_id );

		if ( ! $provider ) {
			wp_send_json_error( array( 'message' => __( 'Invalid panel type.', 'hostforge' ) ) );
		}

		// Force refresh cache.
		delete_post_meta( $server_id, '_hf_packages_cache_time' );

		$result = $provider->get_packages();

		if ( $result['success'] ) {
			wp_send_json_success(
				array(
					'message'  => sprintf(
						/* translators: %d: number of packages found */
						__( '%d packages found.', 'hostforge' ),
						count( $result['packages'] )
					),
					'packages' => $result['packages'],
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Could not fetch packages.', 'hostforge' ) ) );
		}
	}

	/**
	 * AJAX: Save a server.
	 *
	 * @return void
	 */
	public function ajax_save_server(): void {
		check_ajax_referer( 'hf_server_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_servers' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$server_id    = isset( $_POST['server_id'] ) ? absint( $_POST['server_id'] ) : 0;
		$name         = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$panel_type   = isset( $_POST['panel_type'] ) ? sanitize_text_field( wp_unslash( $_POST['panel_type'] ) ) : '';
		$hostname     = isset( $_POST['hostname'] ) ? sanitize_text_field( wp_unslash( $_POST['hostname'] ) ) : '';
		$port         = isset( $_POST['port'] ) ? absint( $_POST['port'] ) : 0;
		$auth_method  = isset( $_POST['auth_method'] ) ? sanitize_text_field( wp_unslash( $_POST['auth_method'] ) ) : 'token';
		$api_token    = isset( $_POST['api_token'] ) ? sanitize_text_field( wp_unslash( $_POST['api_token'] ) ) : '';
		$username     = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
		$password     = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';
		$max_accounts = isset( $_POST['max_accounts'] ) ? absint( $_POST['max_accounts'] ) : 0;
		$nameservers  = isset( $_POST['nameservers'] ) ? sanitize_textarea_field( wp_unslash( $_POST['nameservers'] ) ) : '';
		$notes        = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';
		$server_group = isset( $_POST['server_group'] ) ? sanitize_text_field( wp_unslash( $_POST['server_group'] ) ) : '';

		if ( empty( $name ) ) {
			$name = $hostname;
		}

		if ( empty( $hostname ) || empty( $panel_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Hostname and panel type are required.', 'hostforge' ) ) );
		}

		if ( ! in_array( $panel_type, array( 'cpanel', 'plesk' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid panel type.', 'hostforge' ) ) );
		}

		// Default ports.
		if ( 0 === $port ) {
			$port = 'cpanel' === $panel_type ? 2087 : 8443;
		}

		$post_data = array(
			'post_title'  => $name,
			'post_type'   => 'hf_server',
			'post_status' => 'publish',
		);

		if ( $server_id > 0 ) {
			$post_data['ID'] = $server_id;
			$result          = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$server_id = (int) $result;

		// Save meta.
		update_post_meta( $server_id, '_hf_panel_type', $panel_type );
		update_post_meta( $server_id, '_hf_hostname', $hostname );
		update_post_meta( $server_id, '_hf_port', $port );
		update_post_meta( $server_id, '_hf_protocol', 'https' );
		update_post_meta( $server_id, '_hf_auth_method', $auth_method );
		update_post_meta( $server_id, '_hf_max_accounts', $max_accounts );
		update_post_meta( $server_id, '_hf_notes', $notes );

		// Only update credentials if provided (don't overwrite with empty).
		if ( ! empty( $api_token ) ) {
			update_post_meta( $server_id, '_hf_api_token', HF_Encryption::encrypt( $api_token ) );
		}
		if ( ! empty( $username ) ) {
			update_post_meta( $server_id, '_hf_username', HF_Encryption::encrypt( $username ) );
		}
		if ( ! empty( $password ) ) {
			update_post_meta( $server_id, '_hf_password', HF_Encryption::encrypt( $password ) );
		}

		// Save nameservers as array.
		if ( ! empty( $nameservers ) ) {
			$ns_array = array_filter( array_map( 'trim', explode( "\n", $nameservers ) ) );
			update_post_meta( $server_id, '_hf_nameservers', $ns_array );
		}

		// Set initial status.
		if ( ! get_post_meta( $server_id, '_hf_status', true ) ) {
			update_post_meta( $server_id, '_hf_status', 'unknown' );
		}

		// Handle server group.
		if ( ! empty( $server_group ) ) {
			// Check if it's a new group name or existing term ID.
			if ( is_numeric( $server_group ) ) {
				wp_set_object_terms( $server_id, (int) $server_group, 'hf_server_group' );
			} else {
				$term = term_exists( $server_group, 'hf_server_group' );
				if ( ! $term ) {
					$term = wp_insert_term( $server_group, 'hf_server_group' );
				}
				if ( ! is_wp_error( $term ) ) {
					wp_set_object_terms( $server_id, (int) $term['term_id'], 'hf_server_group' );
				}
			}
		} else {
			wp_set_object_terms( $server_id, array(), 'hf_server_group' );
		}

		/**
		 * Fires after a server is saved (created or updated) via admin.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $server_id  Server post ID.
		 * @param string $panel_type Panel type (cpanel, plesk).
		 * @param string $hostname   Server hostname.
		 */
		do_action( 'hostforge_server_saved', $server_id, $panel_type, $hostname );

		wp_send_json_success(
			array(
				'message'   => __( 'Server saved successfully.', 'hostforge' ),
				'server_id' => $server_id,
				'redirect'  => admin_url( 'admin.php?page=hostforge-servers&action=edit&server_id=' . $server_id ),
			)
		);
	}

	/**
	 * AJAX: Delete a server.
	 *
	 * @return void
	 */
	public function ajax_delete_server(): void {
		check_ajax_referer( 'hf_server_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_servers' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$server_id = isset( $_POST['server_id'] ) ? absint( $_POST['server_id'] ) : 0;

		if ( $server_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid server ID.', 'hostforge' ) ) );
		}

		$server = get_post( $server_id );
		if ( ! $server || 'hf_server' !== $server->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Server not found.', 'hostforge' ) ) );
		}

		$server_name = $server->post_title;

		wp_delete_post( $server_id, true );

		/**
		 * Fires after a server is deleted via admin.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $server_id   The deleted server post ID.
		 * @param string $server_name The deleted server's title.
		 */
		do_action( 'hostforge_server_deleted', $server_id, $server_name );

		wp_send_json_success(
			array(
				'message'  => __( 'Server deleted.', 'hostforge' ),
				'redirect' => admin_url( 'admin.php?page=hostforge-servers' ),
			)
		);
	}
}
