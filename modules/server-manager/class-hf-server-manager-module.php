<?php
/**
 * Server Manager Module.
 *
 * Manages hosting servers (cPanel/Plesk): connections, accounts,
 * packages and health monitoring.
 *
 * @package HostForge\Modules\ServerManager
 */

namespace HostForge\Modules\ServerManager;

use HostForge\Abstracts\HF_Module;
use HostForge\Traits\HF_Has_Logs;
use HostForge\Traits\HF_Has_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Server_Manager_Module
 */
class HF_Server_Manager_Module extends HF_Module {

	use HF_Has_Logs;
	use HF_Has_Settings;

	/**
	 * Get the module identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'server-manager';
	}

	/**
	 * Get the module display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Server Manager', 'hostforge' );
	}

	/**
	 * Get the module description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Manage cPanel and Plesk servers: connections, accounts, packages and monitoring.', 'hostforge' );
	}

	/**
	 * Get required dependencies.
	 *
	 * @return array<string>
	 */
	public function get_dependencies(): array {
		return array();
	}

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init(): void {
		// Register CPT.
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Register taxonomy for server groups.
		add_action( 'init', array( $this, 'register_taxonomy' ) );

		// Admin hooks.
		if ( is_admin() ) {
			$admin = new Admin\HF_Server_Admin( $this );
			$admin->init();
		}

		// Scheduled actions.
		$this->register_scheduled_actions();

		// Health check callback.
		add_action( 'hostforge_server_health_check', array( $this, 'run_health_check' ) );
	}

	/**
	 * Called when the module is activated.
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->register_post_type();
		$this->register_taxonomy();
		flush_rewrite_rules();

		// Schedule health check if not already scheduled.
		if ( function_exists( 'as_has_scheduled_action' ) && did_action( 'action_scheduler_init' ) && ! as_has_scheduled_action( 'hostforge_server_health_check' ) ) {
			as_schedule_recurring_action(
				time(),
				300, // 5 minutes.
				'hostforge_server_health_check',
				array(),
				'hostforge-server-manager'
			);
		}

		$this->log_info( 'Server Manager module activated.' );
	}

	/**
	 * Called when the module is deactivated.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'hostforge_server_health_check', array(), 'hostforge-server-manager' );
		}

		$this->log_info( 'Server Manager module deactivated.' );
	}

	/**
	 * Register the hf_server custom post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'               => __( 'Servers', 'hostforge' ),
			'singular_name'      => __( 'Server', 'hostforge' ),
			'add_new'            => __( 'Add New Server', 'hostforge' ),
			'add_new_item'       => __( 'Add New Server', 'hostforge' ),
			'edit_item'          => __( 'Edit Server', 'hostforge' ),
			'new_item'           => __( 'New Server', 'hostforge' ),
			'view_item'          => __( 'View Server', 'hostforge' ),
			'search_items'       => __( 'Search Servers', 'hostforge' ),
			'not_found'          => __( 'No servers found.', 'hostforge' ),
			'not_found_in_trash' => __( 'No servers found in Trash.', 'hostforge' ),
			'all_items'          => __( 'Servers', 'hostforge' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_rest'        => false,
			'capability_type'     => 'post',
			'capabilities'        => array(
				'edit_post'          => 'manage_hostforge_servers',
				'read_post'          => 'manage_hostforge_servers',
				'delete_post'        => 'manage_hostforge_servers',
				'edit_posts'         => 'manage_hostforge_servers',
				'edit_others_posts'  => 'manage_hostforge_servers',
				'publish_posts'      => 'manage_hostforge_servers',
				'read_private_posts' => 'manage_hostforge_servers',
			),
			'map_meta_cap'        => false,
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => false,
			'exclude_from_search' => true,
		);

		register_post_type( 'hf_server', $args );
	}

	/**
	 * Register the hf_server_group taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy(): void {
		$labels = array(
			'name'          => __( 'Server Groups', 'hostforge' ),
			'singular_name' => __( 'Server Group', 'hostforge' ),
			'search_items'  => __( 'Search Server Groups', 'hostforge' ),
			'all_items'     => __( 'All Server Groups', 'hostforge' ),
			'edit_item'     => __( 'Edit Server Group', 'hostforge' ),
			'update_item'   => __( 'Update Server Group', 'hostforge' ),
			'add_new_item'  => __( 'Add New Server Group', 'hostforge' ),
			'new_item_name' => __( 'New Server Group Name', 'hostforge' ),
			'menu_name'     => __( 'Server Groups', 'hostforge' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => false,
			'show_ui'           => false,
			'show_in_menu'      => false,
			'show_admin_column' => false,
			'hierarchical'      => false,
			'rewrite'           => false,
			'show_in_rest'      => false,
		);

		/**
		 * Filter the server groups taxonomy registration arguments.
		 *
		 * Allows modification of the hf_server_group taxonomy args
		 * before it is registered with WordPress.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args Taxonomy registration arguments.
		 */
		$args = apply_filters( 'hostforge_server_groups', $args );

		register_taxonomy( 'hf_server_group', 'hf_server', $args );
	}

	/**
	 * Register scheduled actions.
	 *
	 * @return void
	 */
	public function register_scheduled_actions(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		if ( ! did_action( 'action_scheduler_init' ) ) {
			add_action( 'action_scheduler_init', array( $this, 'register_scheduled_actions' ) );
			return;
		}

		if ( ! as_has_scheduled_action( 'hostforge_server_health_check' ) ) {
			as_schedule_recurring_action(
				time(),
				300,
				'hostforge_server_health_check',
				array(),
				'hostforge-server-manager'
			);
		}
	}

	/**
	 * Run health check on all active servers.
	 *
	 * @return void
	 */
	public function run_health_check(): void {
		$servers = get_posts(
			array(
				'post_type'      => 'hf_server',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $servers as $server_id ) {
			$panel_type = get_post_meta( $server_id, '_hf_panel_type', true );
			$provider   = $this->get_provider( $server_id );

			if ( ! $provider ) {
				update_post_meta( $server_id, '_hf_status', 'error' );
				continue;
			}

			$result = $provider->test_connection();

			if ( $result['success'] ) {
				update_post_meta( $server_id, '_hf_status', 'active' );

				// Cache server stats.
				$stats = $provider->get_server_stats();
				if ( $stats['success'] ) {
					/**
					 * Filter the server health check data before saving to cache.
					 *
					 * Allows modification or enrichment of server stats data
					 * before it is persisted as post meta.
					 *
					 * @since 1.0.0
					 *
					 * @param array $data      Server stats data array.
					 * @param int   $server_id The server post ID.
					 */
					$stats['data'] = apply_filters( 'hostforge_server_health_data', $stats['data'], $server_id );

					update_post_meta( $server_id, '_hf_server_stats_cache', $stats['data'] );
					update_post_meta( $server_id, '_hf_server_stats_cache_time', time() );
				}

				/**
				 * Fires when a server health check succeeds.
				 *
				 * @param int $server_id The server post ID.
				 */
				do_action( 'hostforge_server_connected', $server_id );
			} else {
				update_post_meta( $server_id, '_hf_status', 'error' );

				$this->log_warning(
					'Server health check failed',
					array(
						'server_id' => $server_id,
						'message'   => $result['message'] ?? '',
					)
				);

				/**
				 * Fires when a server health check fails.
				 *
				 * @param int    $server_id The server post ID.
				 * @param string $error     Error message.
				 */
				do_action( 'hostforge_server_connection_failed', $server_id, $result['message'] ?? '' );
			}

			update_post_meta( $server_id, '_hf_last_check', current_time( 'mysql' ) );
		}
	}

	/**
	 * Get the panel provider for a server.
	 *
	 * @param int $server_id Server post ID.
	 * @return \HostForge\Interfaces\HF_Panel_Provider|null
	 */
	public function get_provider( int $server_id ): ?\HostForge\Interfaces\HF_Panel_Provider {
		$panel_type = get_post_meta( $server_id, '_hf_panel_type', true );

		/**
		 * Filter the available panel provider classes.
		 *
		 * Allows third-party plugins to register additional panel providers
		 * (e.g., DirectAdmin, ISPConfig) by mapping a panel type slug
		 * to a fully-qualified class name implementing HF_Panel_Provider.
		 *
		 * @since 1.0.0
		 *
		 * @param array $providers Associative array of panel_type => class_name.
		 */
		$providers = apply_filters(
			'hostforge_panel_providers',
			array(
				'cpanel' => Providers\HF_CPanel_Provider::class,
				'plesk'  => Providers\HF_Plesk_Provider::class,
			)
		);

		if ( isset( $providers[ $panel_type ] ) ) {
			$class = $providers[ $panel_type ];
			if ( class_exists( $class ) ) {
				return new $class( $server_id );
			}
		}

		return null;
	}

	/**
	 * Get admin menu items.
	 *
	 * @return array
	 */
	public function get_admin_menu_items(): array {
		return array(
			array(
				'title'      => __( 'Servers', 'hostforge' ),
				'slug'       => 'hostforge-servers',
				'capability' => 'manage_hostforge_servers',
				'callback'   => array( new Admin\HF_Server_Admin( $this ), 'render_servers_page' ),
			),
		);
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$controller = new Api\HF_REST_Server_Controller();
		$controller->register_routes();
	}

	/**
	 * Get dashboard widgets.
	 *
	 * @return array
	 */
	public function get_dashboard_widgets(): array {
		return array(
			array(
				'id'       => 'hf-server-status',
				'title'    => __( 'Server Status', 'hostforge' ),
				'callback' => array( $this, 'render_dashboard_widget' ),
			),
		);
	}

	/**
	 * Render the server status dashboard widget.
	 *
	 * @return void
	 */
	public function render_dashboard_widget(): void {
		$servers = get_posts(
			array(
				'post_type'      => 'hf_server',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		$total   = count( $servers );
		$active  = 0;
		$error   = 0;
		$unknown = 0;

		foreach ( $servers as $server ) {
			$status = get_post_meta( $server->ID, '_hf_status', true );
			switch ( $status ) {
				case 'active':
					++$active;
					break;
				case 'error':
					++$error;
					break;
				default:
					++$unknown;
					break;
			}
		}

		?>
		<div class="hf-widget-stats">
			<div class="hf-stat">
				<span class="hf-stat__number"><?php echo esc_html( $total ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Total Servers', 'hostforge' ); ?></span>
			</div>
			<div class="hf-stat">
				<span class="hf-stat__number hf-stat__number--success"><?php echo esc_html( $active ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Online', 'hostforge' ); ?></span>
			</div>
			<div class="hf-stat">
				<span class="hf-stat__number hf-stat__number--error"><?php echo esc_html( $error ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Errors', 'hostforge' ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Get all meta keys for the hf_server CPT.
	 *
	 * @return array<string, string> Key => description.
	 */
	public static function get_server_meta_keys(): array {
		return array(
			'_hf_panel_type'              => 'cpanel or plesk',
			'_hf_hostname'                => 'Server hostname or IP',
			'_hf_port'                    => 'API port (2087 for cPanel, 8443 for Plesk)',
			'_hf_protocol'                => 'https',
			'_hf_auth_method'             => 'token or password',
			'_hf_api_token'               => 'API token (encrypted)',
			'_hf_username'                => 'Admin username (encrypted)',
			'_hf_password'                => 'Admin password (encrypted)',
			'_hf_max_accounts'            => 'Maximum accounts allowed',
			'_hf_current_accounts'        => 'Current number of accounts',
			'_hf_status'                  => 'active, error, or unknown',
			'_hf_last_check'              => 'Last health check timestamp',
			'_hf_packages_cache'          => 'Cached packages list',
			'_hf_packages_cache_time'     => 'Packages cache timestamp',
			'_hf_server_stats_cache'      => 'Cached server stats',
			'_hf_server_stats_cache_time' => 'Stats cache timestamp',
			'_hf_nameservers'             => 'Server nameservers (serialized array)',
			'_hf_notes'                   => 'Admin notes',
		);
	}
}
