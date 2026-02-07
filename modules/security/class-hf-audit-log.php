<?php
/**
 * Audit Log.
 *
 * Records important user and system actions to the hf_activity_log table.
 * Covers login, settings changes, module toggles, service actions,
 * ticket actions and domain operations.
 *
 * @package HostForge\Modules\Security
 */

namespace HostForge\Modules\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Audit_Log
 */
class HF_Audit_Log {

	/**
	 * Module instance.
	 *
	 * @var HF_Security_Module
	 */
	private HF_Security_Module $module;

	/**
	 * Constructor.
	 *
	 * @param HF_Security_Module $module Module instance.
	 */
	public function __construct( HF_Security_Module $module ) {
		$this->module = $module;
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		$settings = $this->module->get_security_settings();

		if ( 'yes' !== ( ! empty( $settings['audit_enabled'] ) ? $settings['audit_enabled'] : 'yes' ) ) {
			return;
		}

		// Authentication events.
		add_action( 'wp_login', array( $this, 'log_login' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'log_logout' ) );
		add_action( 'wp_login_failed', array( $this, 'log_login_failed' ) );

		// User events.
		add_action( 'user_register', array( $this, 'log_user_register' ) );
		add_action( 'delete_user', array( $this, 'log_user_delete' ) );
		add_action( 'set_user_role', array( $this, 'log_role_change' ), 10, 3 );

		// Module events.
		add_action( 'hostforge_module_activated', array( $this, 'log_module_activated' ) );
		add_action( 'hostforge_module_deactivated', array( $this, 'log_module_deactivated' ) );

		// Service lifecycle.
		add_action( 'hostforge_after_provision', array( $this, 'log_service_provisioned' ), 10, 2 );
		add_action( 'hostforge_after_suspend', array( $this, 'log_service_suspended' ) );
		add_action( 'hostforge_after_unsuspend', array( $this, 'log_service_unsuspended' ) );
		add_action( 'hostforge_after_terminate', array( $this, 'log_service_terminated' ) );

		// Ticket events.
		add_action( 'hostforge_ticket_created', array( $this, 'log_ticket_created' ), 10, 2 );
		add_action( 'hostforge_ticket_closed', array( $this, 'log_ticket_closed' ) );

		// Domain events.
		add_action( 'hostforge_domain_registered', array( $this, 'log_domain_registered' ), 10, 2 );
		add_action( 'hostforge_domain_transferred', array( $this, 'log_domain_transferred' ), 10, 2 );

		// Settings changes.
		add_action( 'update_option', array( $this, 'log_option_update' ), 10, 3 );

		// WooCommerce order events.
		add_action( 'woocommerce_order_status_changed', array( $this, 'log_order_status_change' ), 10, 3 );
	}

	/**
	 * Log a login event.
	 *
	 * @param string   $username User login name.
	 * @param \WP_User $user     User object.
	 * @return void
	 */
	public function log_login( string $username, \WP_User $user ): void {
		$this->record(
			$user->ID,
			'user_login',
			'user',
			$user->ID,
			sprintf( 'User "%s" logged in.', $username )
		);
	}

	/**
	 * Log a logout event.
	 *
	 * @return void
	 */
	public function log_logout(): void {
		$user_id = get_current_user_id();

		if ( $user_id ) {
			$this->record(
				$user_id,
				'user_logout',
				'user',
				$user_id,
				'User logged out.'
			);
		}
	}

	/**
	 * Log a failed login.
	 *
	 * @param string $username Attempted username.
	 * @return void
	 */
	public function log_login_failed( string $username ): void {
		$this->record(
			0,
			'login_failed',
			'user',
			0,
			sprintf( 'Failed login attempt for "%s".', sanitize_user( $username ) )
		);
	}

	/**
	 * Log user registration.
	 *
	 * @param int $user_id New user ID.
	 * @return void
	 */
	public function log_user_register( int $user_id ): void {
		$user = get_userdata( $user_id );
		$name = $user ? $user->user_login : 'Unknown';

		$this->record(
			$user_id,
			'user_registered',
			'user',
			$user_id,
			sprintf( 'New user registered: "%s".', $name )
		);
	}

	/**
	 * Log user deletion.
	 *
	 * @param int $user_id Deleted user ID.
	 * @return void
	 */
	public function log_user_delete( int $user_id ): void {
		$this->record(
			get_current_user_id(),
			'user_deleted',
			'user',
			$user_id,
			sprintf( 'User #%d deleted.', $user_id )
		);
	}

	/**
	 * Log role change.
	 *
	 * @param int    $user_id  User ID.
	 * @param string $new_role New role.
	 * @param array  $old_roles Old roles.
	 * @return void
	 */
	public function log_role_change( int $user_id, string $new_role, array $old_roles ): void {
		$this->record(
			get_current_user_id(),
			'role_changed',
			'user',
			$user_id,
			sprintf( 'User #%d role changed from "%s" to "%s".', $user_id, implode( ', ', $old_roles ), $new_role )
		);
	}

	/**
	 * Log module activation.
	 *
	 * @param string $module_id Module ID.
	 * @return void
	 */
	public function log_module_activated( string $module_id ): void {
		$this->record(
			get_current_user_id(),
			'module_activated',
			'module',
			0,
			sprintf( 'Module "%s" activated.', $module_id )
		);
	}

	/**
	 * Log module deactivation.
	 *
	 * @param string $module_id Module ID.
	 * @return void
	 */
	public function log_module_deactivated( string $module_id ): void {
		$this->record(
			get_current_user_id(),
			'module_deactivated',
			'module',
			0,
			sprintf( 'Module "%s" deactivated.', $module_id )
		);
	}

	/**
	 * Log service provisioned.
	 *
	 * @param int   $service_id Service post ID.
	 * @param array $data       Account data.
	 * @return void
	 */
	public function log_service_provisioned( int $service_id, array $data ): void {
		$domain = ! empty( $data['domain'] ) ? $data['domain'] : 'N/A';

		$this->record(
			0,
			'service_provisioned',
			'service',
			$service_id,
			sprintf( 'Service #%d provisioned for domain "%s".', $service_id, $domain )
		);
	}

	/**
	 * Log service suspended.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function log_service_suspended( int $service_id ): void {
		$this->record(
			get_current_user_id(),
			'service_suspended',
			'service',
			$service_id,
			sprintf( 'Service #%d suspended.', $service_id )
		);
	}

	/**
	 * Log service unsuspended.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function log_service_unsuspended( int $service_id ): void {
		$this->record(
			get_current_user_id(),
			'service_unsuspended',
			'service',
			$service_id,
			sprintf( 'Service #%d unsuspended.', $service_id )
		);
	}

	/**
	 * Log service terminated.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function log_service_terminated( int $service_id ): void {
		$this->record(
			get_current_user_id(),
			'service_terminated',
			'service',
			$service_id,
			sprintf( 'Service #%d terminated.', $service_id )
		);
	}

	/**
	 * Log ticket created.
	 *
	 * @param int $ticket_id Ticket post ID.
	 * @param int $user_id   User ID.
	 * @return void
	 */
	public function log_ticket_created( int $ticket_id, int $user_id ): void {
		$this->record(
			$user_id,
			'ticket_created',
			'ticket',
			$ticket_id,
			sprintf( 'Ticket #%d created.', $ticket_id )
		);
	}

	/**
	 * Log ticket closed.
	 *
	 * @param int $ticket_id Ticket post ID.
	 * @return void
	 */
	public function log_ticket_closed( int $ticket_id ): void {
		$this->record(
			get_current_user_id(),
			'ticket_closed',
			'ticket',
			$ticket_id,
			sprintf( 'Ticket #%d closed.', $ticket_id )
		);
	}

	/**
	 * Log domain registered.
	 *
	 * @param int    $domain_id   Domain post ID.
	 * @param string $domain_name Domain name.
	 * @return void
	 */
	public function log_domain_registered( int $domain_id, string $domain_name ): void {
		$this->record(
			0,
			'domain_registered',
			'domain',
			$domain_id,
			sprintf( 'Domain "%s" registered.', $domain_name )
		);
	}

	/**
	 * Log domain transferred.
	 *
	 * @param int    $domain_id   Domain post ID.
	 * @param string $domain_name Domain name.
	 * @return void
	 */
	public function log_domain_transferred( int $domain_id, string $domain_name ): void {
		$this->record(
			0,
			'domain_transferred',
			'domain',
			$domain_id,
			sprintf( 'Domain "%s" transferred.', $domain_name )
		);
	}

	/**
	 * Log HostForge option updates.
	 *
	 * @param string $option    Option name.
	 * @param mixed  $old_value Old value.
	 * @param mixed  $value     New value.
	 * @return void
	 */
	public function log_option_update( string $option, $old_value, $value ): void {
		// Only log HostForge-related options.
		if ( strpos( $option, 'hf_' ) !== 0 && strpos( $option, 'hostforge_' ) !== 0 ) {
			return;
		}

		// Skip transients and internal state.
		if ( strpos( $option, '_transient' ) !== false || 'hf_db_version' === $option ) {
			return;
		}

		$this->record(
			get_current_user_id(),
			'setting_changed',
			'setting',
			0,
			sprintf( 'Setting "%s" updated.', $option )
		);
	}

	/**
	 * Log WooCommerce order status changes.
	 *
	 * @param int    $order_id   Order ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @return void
	 */
	public function log_order_status_change( int $order_id, string $old_status, string $new_status ): void {
		$this->record(
			get_current_user_id(),
			'order_status_changed',
			'order',
			$order_id,
			sprintf( 'Order #%d status changed from "%s" to "%s".', $order_id, $old_status, $new_status )
		);
	}

	/**
	 * Record an audit log entry.
	 *
	 * @param int    $user_id     User ID (0 for system).
	 * @param string $action      Action identifier.
	 * @param string $object_type Object type (user, service, ticket, etc.).
	 * @param int    $object_id   Object ID.
	 * @param string $details     Human-readable description.
	 * @return void
	 */
	public function record( int $user_id, string $action, string $object_type, int $object_id, string $details ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'hf_activity_log';

		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );

			if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				$ip = '';
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			array(
				'user_id'     => $user_id,
				'action'      => $action,
				'object_type' => $object_type,
				'object_id'   => $object_id,
				'details'     => $details,
				'ip_address'  => $ip,
				'created_at'  => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get audit log entries.
	 *
	 * @param array $args Query arguments.
	 * @return array{items: array, total: int}
	 */
	public function get_entries( array $args = array() ): array {
		global $wpdb;

		$table    = $wpdb->prefix . 'hf_activity_log';
		$defaults = array(
			'per_page'    => 20,
			'page'        => 1,
			'action'      => '',
			'object_type' => '',
			'user_id'     => 0,
			'search'      => '',
		);

		$args   = wp_parse_args( $args, $defaults );
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['action'] ) ) {
			$where[]  = 'action = %s';
			$values[] = $args['action'];
		}

		if ( ! empty( $args['object_type'] ) ) {
			$where[]  = 'object_type = %s';
			$values[] = $args['object_type'];
		}

		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$values[] = (int) $args['user_id'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'details LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_sql = implode( ' AND ', $where );
		$offset    = ( (int) $args['page'] - 1 ) * (int) $args['per_page'];

		// Count total.
		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $values ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}

		// Get items.
		$query = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";

		$query_values   = array_merge( $values, array( (int) $args['per_page'], $offset ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $query, $query_values ) );

		return array(
			'items' => ! empty( $items ) ? $items : array(),
			'total' => $total,
		);
	}
}
