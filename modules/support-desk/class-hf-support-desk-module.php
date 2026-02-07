<?php
/**
 * Support Desk Module.
 *
 * Full support ticket system with knowledge base, canned responses,
 * email piping and auto-close functionality.
 *
 * @package HostForge\Modules\SupportDesk
 */

namespace HostForge\Modules\SupportDesk;

use HostForge\Abstracts\HF_Module;
use HostForge\Traits\HF_Has_Logs;
use HostForge\Traits\HF_Has_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Support_Desk_Module
 */
class HF_Support_Desk_Module extends HF_Module {

	use HF_Has_Logs;
	use HF_Has_Settings;

	/**
	 * Get the module identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'support-desk';
	}

	/**
	 * Get the module display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Support Desk', 'hostforge' );
	}

	/**
	 * Get the module description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Support ticket system with knowledge base, canned responses, email piping and auto-close.', 'hostforge' );
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
		// Register CPTs and taxonomy.
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );

		// Admin hooks.
		if ( is_admin() ) {
			$admin = new Admin\HF_Ticket_Admin( $this );
			$admin->init();
		}

		// Frontend hooks.
		if ( ! is_admin() || wp_doing_ajax() ) {
			$frontend = new HF_Ticket_Frontend();
			$frontend->init();
		}

		// Scheduled actions.
		$this->register_scheduled_actions();

		// Action Scheduler callbacks.
		add_action( 'hostforge_auto_close_tickets', array( $this, 'run_auto_close' ) );
		add_action( 'hostforge_ticket_auto_close_warning', array( $this, 'send_auto_close_warning' ) );
		add_action( 'hostforge_check_imap_email', array( $this, 'process_imap_emails' ) );

		// Filter out ticket replies from normal comments.
		add_action( 'pre_get_comments', array( $this, 'exclude_ticket_replies' ) );

		// Ticket status update on reply.
		add_action( 'wp_insert_comment', array( $this, 'on_reply_inserted' ), 10, 2 );
	}

	/**
	 * Called when the module is activated.
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->register_post_types();
		$this->register_taxonomies();
		flush_rewrite_rules();

		// Schedule auto-close check daily.
		if ( function_exists( 'as_has_scheduled_action' ) ) {
			if ( ! as_has_scheduled_action( 'hostforge_auto_close_tickets' ) ) {
				as_schedule_recurring_action(
					time() + 300,
					DAY_IN_SECONDS,
					'hostforge_auto_close_tickets',
					array(),
					'hostforge-support-desk'
				);
			}
		}

		// Create default departments.
		$this->create_default_departments();

		$this->log_info( 'Support Desk module activated.' );
	}

	/**
	 * Called when the module is deactivated.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'hostforge_auto_close_tickets', array(), 'hostforge-support-desk' );
			as_unschedule_all_actions( 'hostforge_check_imap_email', array(), 'hostforge-support-desk' );
		}

		$this->log_info( 'Support Desk module deactivated.' );
	}

	/**
	 * Register post types for tickets, KB articles and canned responses.
	 *
	 * @return void
	 */
	public function register_post_types(): void {
		// Tickets.
		register_post_type(
			'hf_ticket',
			array(
				'labels'              => array(
					'name'               => __( 'Tickets', 'hostforge' ),
					'singular_name'      => __( 'Ticket', 'hostforge' ),
					'add_new'            => __( 'Add New Ticket', 'hostforge' ),
					'add_new_item'       => __( 'Add New Ticket', 'hostforge' ),
					'edit_item'          => __( 'Edit Ticket', 'hostforge' ),
					'new_item'           => __( 'New Ticket', 'hostforge' ),
					'view_item'          => __( 'View Ticket', 'hostforge' ),
					'search_items'       => __( 'Search Tickets', 'hostforge' ),
					'not_found'          => __( 'No tickets found.', 'hostforge' ),
					'not_found_in_trash' => __( 'No tickets found in Trash.', 'hostforge' ),
					'all_items'          => __( 'Tickets', 'hostforge' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'capability_type'     => 'post',
				'capabilities'        => array(
					'edit_post'          => 'manage_hostforge_tickets',
					'read_post'          => 'manage_hostforge_tickets',
					'delete_post'        => 'manage_hostforge_tickets',
					'edit_posts'         => 'manage_hostforge_tickets',
					'edit_others_posts'  => 'manage_hostforge_tickets',
					'publish_posts'      => 'manage_hostforge_tickets',
					'read_private_posts' => 'manage_hostforge_tickets',
				),
				'map_meta_cap'        => false,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'comments' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => false,
				'exclude_from_search' => true,
			)
		);

		// Knowledge Base articles.
		register_post_type(
			'hf_kb_article',
			array(
				'labels'              => array(
					'name'               => __( 'KB Articles', 'hostforge' ),
					'singular_name'      => __( 'KB Article', 'hostforge' ),
					'add_new'            => __( 'Add New Article', 'hostforge' ),
					'add_new_item'       => __( 'Add New Article', 'hostforge' ),
					'edit_item'          => __( 'Edit Article', 'hostforge' ),
					'new_item'           => __( 'New Article', 'hostforge' ),
					'view_item'          => __( 'View Article', 'hostforge' ),
					'search_items'       => __( 'Search Articles', 'hostforge' ),
					'not_found'          => __( 'No articles found.', 'hostforge' ),
					'not_found_in_trash' => __( 'No articles found in Trash.', 'hostforge' ),
					'all_items'          => __( 'KB Articles', 'hostforge' ),
				),
				'public'              => true,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'capability_type'     => 'post',
				'capabilities'        => array(
					'edit_post'          => 'manage_hostforge_tickets',
					'read_post'          => 'read',
					'delete_post'        => 'manage_hostforge_tickets',
					'edit_posts'         => 'manage_hostforge_tickets',
					'edit_others_posts'  => 'manage_hostforge_tickets',
					'publish_posts'      => 'manage_hostforge_tickets',
					'read_private_posts' => 'manage_hostforge_tickets',
				),
				'map_meta_cap'        => false,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'thumbnail' ),
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'knowledge-base' ),
				'query_var'           => true,
				'can_export'          => true,
				'exclude_from_search' => false,
			)
		);

		// Canned responses.
		register_post_type(
			'hf_canned_response',
			array(
				'labels'              => array(
					'name'               => __( 'Canned Responses', 'hostforge' ),
					'singular_name'      => __( 'Canned Response', 'hostforge' ),
					'add_new'            => __( 'Add New Response', 'hostforge' ),
					'add_new_item'       => __( 'Add New Response', 'hostforge' ),
					'edit_item'          => __( 'Edit Response', 'hostforge' ),
					'new_item'           => __( 'New Response', 'hostforge' ),
					'view_item'          => __( 'View Response', 'hostforge' ),
					'search_items'       => __( 'Search Responses', 'hostforge' ),
					'not_found'          => __( 'No responses found.', 'hostforge' ),
					'not_found_in_trash' => __( 'No responses found in Trash.', 'hostforge' ),
					'all_items'          => __( 'Canned Responses', 'hostforge' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'capability_type'     => 'post',
				'capabilities'        => array(
					'edit_post'          => 'manage_hostforge_tickets',
					'read_post'          => 'manage_hostforge_tickets',
					'delete_post'        => 'manage_hostforge_tickets',
					'edit_posts'         => 'manage_hostforge_tickets',
					'edit_others_posts'  => 'manage_hostforge_tickets',
					'publish_posts'      => 'manage_hostforge_tickets',
					'read_private_posts' => 'manage_hostforge_tickets',
				),
				'map_meta_cap'        => false,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => true,
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Register taxonomies.
	 *
	 * @return void
	 */
	public function register_taxonomies(): void {
		// Departments for tickets.
		register_taxonomy(
			'hf_department',
			'hf_ticket',
			array(
				'labels'            => array(
					'name'          => __( 'Departments', 'hostforge' ),
					'singular_name' => __( 'Department', 'hostforge' ),
					'search_items'  => __( 'Search Departments', 'hostforge' ),
					'all_items'     => __( 'All Departments', 'hostforge' ),
					'edit_item'     => __( 'Edit Department', 'hostforge' ),
					'update_item'   => __( 'Update Department', 'hostforge' ),
					'add_new_item'  => __( 'Add New Department', 'hostforge' ),
					'new_item_name' => __( 'New Department Name', 'hostforge' ),
					'menu_name'     => __( 'Departments', 'hostforge' ),
				),
				'public'            => false,
				'show_ui'           => false,
				'show_in_menu'      => false,
				'show_admin_column' => false,
				'hierarchical'      => true,
				'rewrite'           => false,
				'show_in_rest'      => false,
			)
		);

		// KB categories.
		register_taxonomy(
			'hf_kb_category',
			'hf_kb_article',
			array(
				'labels'            => array(
					'name'          => __( 'KB Categories', 'hostforge' ),
					'singular_name' => __( 'KB Category', 'hostforge' ),
					'search_items'  => __( 'Search KB Categories', 'hostforge' ),
					'all_items'     => __( 'All KB Categories', 'hostforge' ),
					'edit_item'     => __( 'Edit KB Category', 'hostforge' ),
					'update_item'   => __( 'Update KB Category', 'hostforge' ),
					'add_new_item'  => __( 'Add New KB Category', 'hostforge' ),
					'new_item_name' => __( 'New KB Category Name', 'hostforge' ),
					'menu_name'     => __( 'KB Categories', 'hostforge' ),
				),
				'public'            => true,
				'show_ui'           => false,
				'show_in_menu'      => false,
				'show_admin_column' => false,
				'hierarchical'      => true,
				'rewrite'           => array( 'slug' => 'kb-category' ),
				'show_in_rest'      => false,
			)
		);
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

		if ( ! as_has_scheduled_action( 'hostforge_auto_close_tickets' ) ) {
			as_schedule_recurring_action(
				time() + 300,
				DAY_IN_SECONDS,
				'hostforge_auto_close_tickets',
				array(),
				'hostforge-support-desk'
			);
		}

		// Email piping if enabled.
		$imap_enabled = get_option( 'hf_support_imap_enabled', 'no' );
		if ( 'yes' === $imap_enabled && ! as_has_scheduled_action( 'hostforge_check_imap_email' ) ) {
			as_schedule_recurring_action(
				time() + 60,
				300, // 5 minutes.
				'hostforge_check_imap_email',
				array(),
				'hostforge-support-desk'
			);
		}
	}

	/**
	 * Create default departments on activation.
	 *
	 * @return void
	 */
	private function create_default_departments(): void {
		$defaults = array(
			__( 'General', 'hostforge' ),
			__( 'Technical Support', 'hostforge' ),
			__( 'Sales', 'hostforge' ),
			__( 'Billing', 'hostforge' ),
		);

		foreach ( $defaults as $name ) {
			if ( ! term_exists( $name, 'hf_department' ) ) {
				wp_insert_term( $name, 'hf_department' );
			}
		}
	}

	/**
	 * Run auto-close check on inactive tickets.
	 *
	 * @return void
	 */
	public function run_auto_close(): void {
		$auto_close_days = absint( get_option( 'hf_support_auto_close_days', 7 ) );

		if ( 0 === $auto_close_days ) {
			return;
		}

		$warning_days = $auto_close_days - 1;
		$now          = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		// Find tickets that should be closed (inactive > auto_close_days).
		$auto_close_args = array(
			'post_type'      => 'hf_ticket',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_hf_status',
					'value'   => array( 'open', 'customer_reply', 'staff_reply', 'in_progress' ),
					'compare' => 'IN',
				),
				array(
					'key'     => '_hf_last_reply_at',
					'value'   => gmdate( 'Y-m-d H:i:s', $now - ( $auto_close_days * DAY_IN_SECONDS ) ),
					'compare' => '<',
					'type'    => 'DATETIME',
				),
			),
		);

		/**
		 * Filters the query arguments for finding tickets eligible for auto-close.
		 *
		 * @since 1.0.0
		 *
		 * @param array $auto_close_args WP_Query arguments.
		 * @param int   $auto_close_days Number of inactive days before auto-close.
		 */
		$auto_close_args = apply_filters( 'hostforge_auto_close_query', $auto_close_args, $auto_close_days );

		$tickets_to_close = get_posts( $auto_close_args );

		foreach ( $tickets_to_close as $ticket ) {
			update_post_meta( $ticket->ID, '_hf_status', 'closed' );

			$this->log_info(
				'Auto-closed inactive ticket.',
				array( 'ticket_id' => $ticket->ID )
			);

			/**
			 * Fires when a ticket is auto-closed.
			 *
			 * @param int $ticket_id The ticket post ID.
			 */
			do_action( 'hostforge_ticket_closed', $ticket->ID );
		}

		// Find tickets to warn (inactive > warning_days but < auto_close_days).
		$tickets_to_warn = get_posts(
			array(
				'post_type'      => 'hf_ticket',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_hf_status',
						'value'   => array( 'open', 'customer_reply', 'staff_reply', 'in_progress' ),
						'compare' => 'IN',
					),
					array(
						'key'     => '_hf_last_reply_at',
						'value'   => gmdate( 'Y-m-d H:i:s', $now - ( $warning_days * DAY_IN_SECONDS ) ),
						'compare' => '<',
						'type'    => 'DATETIME',
					),
					array(
						'key'     => '_hf_last_reply_at',
						'value'   => gmdate( 'Y-m-d H:i:s', $now - ( $auto_close_days * DAY_IN_SECONDS ) ),
						'compare' => '>=',
						'type'    => 'DATETIME',
					),
					array(
						'key'     => '_hf_auto_close_warned',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		foreach ( $tickets_to_warn as $ticket ) {
			update_post_meta( $ticket->ID, '_hf_auto_close_warned', '1' );

			/**
			 * Fires when a ticket is about to be auto-closed.
			 *
			 * @param int $ticket_id The ticket post ID.
			 */
			do_action( 'hostforge_ticket_auto_close_warning', $ticket->ID );
		}
	}

	/**
	 * Send auto-close warning email.
	 *
	 * @param int $ticket_id Ticket post ID.
	 * @return void
	 */
	public function send_auto_close_warning( int $ticket_id ): void {
		$client_id = absint( get_post_meta( $ticket_id, '_hf_client_user_id', true ) );
		$user      = get_user_by( 'id', $client_id );

		if ( ! $user ) {
			return;
		}

		$ticket  = get_post( $ticket_id );
		$subject = sprintf(
			/* translators: %1$s ticket ID, %2$s ticket subject */
			__( '[Ticket #%1$s] %2$s - Will be closed due to inactivity', 'hostforge' ),
			$ticket_id,
			$ticket ? $ticket->post_title : ''
		);

		$message = sprintf(
			/* translators: %1$s customer name, %2$s ticket ID, %3$s ticket subject */
			__(
				"Hello %1\$s,\n\nYour ticket #%2\$s \"%3\$s\" will be automatically closed in 24 hours due to inactivity.\n\nIf you still need assistance, please reply to keep it open.\n\nBest regards.",
				'hostforge'
			),
			$user->display_name,
			$ticket_id,
			$ticket ? $ticket->post_title : ''
		);

		wp_mail( $user->user_email, $subject, $message );
	}

	/**
	 * Process IMAP emails and create tickets/replies.
	 *
	 * @return void
	 */
	public function process_imap_emails(): void {
		$imap_enabled = get_option( 'hf_support_imap_enabled', 'no' );

		if ( 'yes' !== $imap_enabled ) {
			return;
		}

		$host     = get_option( 'hf_support_imap_host', '' );
		$port     = absint( get_option( 'hf_support_imap_port', 993 ) );
		$username = get_option( 'hf_support_imap_username', '' );
		$password = get_option( 'hf_support_imap_password', '' );
		$ssl      = get_option( 'hf_support_imap_ssl', 'yes' );

		if ( empty( $host ) || empty( $username ) || empty( $password ) ) {
			return;
		}

		if ( ! function_exists( 'imap_open' ) ) {
			$this->log_warning( 'IMAP extension not available.' );
			return;
		}

		$ssl_flag = 'yes' === $ssl ? '/ssl' : '';
		$mailbox  = '{' . $host . ':' . $port . '/imap' . $ssl_flag . '}INBOX';

		$encryption = new \HostForge\HF_Encryption();
		$decrypted  = $encryption->decrypt( $password );

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$inbox = @imap_open( $mailbox, $username, $decrypted );

		if ( ! $inbox ) {
			$this->log_warning( 'Failed to connect to IMAP server.', array( 'host' => $host ) );
			return;
		}

		$emails = imap_search( $inbox, 'UNSEEN' );

		if ( ! $emails ) {
			imap_close( $inbox );
			return;
		}

		foreach ( $emails as $email_number ) {
			$this->process_single_email( $inbox, $email_number );
		}

		imap_close( $inbox );
	}

	/**
	 * Process a single IMAP email.
	 *
	 * @param resource $inbox       IMAP connection.
	 * @param int      $email_number Email number.
	 * @return void
	 */
	private function process_single_email( $inbox, int $email_number ): void {
		$header  = imap_headerinfo( $inbox, $email_number );
		$body    = imap_fetchbody( $inbox, $email_number, '1' );
		$subject = isset( $header->subject ) ? imap_utf8( $header->subject ) : '';
		$from    = isset( $header->from[0] ) ? $header->from[0]->mailbox . '@' . $header->from[0]->host : '';

		if ( empty( $from ) ) {
			return;
		}

		$user = get_user_by( 'email', $from );

		if ( ! $user ) {
			return;
		}

		/**
		 * Fires after an IMAP email has been parsed, before processing as ticket/reply.
		 *
		 * @since 1.0.0
		 *
		 * @param string    $subject The email subject.
		 * @param string    $body    The email body.
		 * @param string    $from    The sender email address.
		 * @param \WP_User  $user    The matched WordPress user.
		 */
		do_action( 'hostforge_imap_email_parsed', $subject, $body, $from, $user );

		// Check if this is a reply to existing ticket (subject contains #TICKET_ID).
		if ( preg_match( '/#(\d+)/', $subject, $matches ) ) {
			$ticket_id = absint( $matches[1] );
			$ticket    = get_post( $ticket_id );

			if ( $ticket && 'hf_ticket' === $ticket->post_type ) {
				$this->add_reply( $ticket_id, $user->ID, wp_kses_post( $body ), false );
				imap_setflag_full( $inbox, (string) $email_number, '\\Seen' );
				return;
			}
		}

		// Create new ticket.
		$ticket_id = wp_insert_post(
			array(
				'post_type'    => 'hf_ticket',
				'post_title'   => sanitize_text_field( $subject ),
				'post_content' => wp_kses_post( $body ),
				'post_status'  => 'publish',
				'post_author'  => $user->ID,
			)
		);

		if ( ! is_wp_error( $ticket_id ) ) {
			update_post_meta( $ticket_id, '_hf_status', 'open' );
			update_post_meta( $ticket_id, '_hf_priority', 'medium' );
			update_post_meta( $ticket_id, '_hf_client_user_id', $user->ID );
			update_post_meta( $ticket_id, '_hf_last_reply_at', current_time( 'mysql' ) );
			update_post_meta( $ticket_id, '_hf_last_reply_by', $user->ID );

			/**
			 * Fires when a new ticket is created.
			 *
			 * @param int $ticket_id The ticket post ID.
			 * @param int $user_id   The user who created the ticket.
			 */
			do_action( 'hostforge_ticket_created', $ticket_id, $user->ID );
		}

		imap_setflag_full( $inbox, (string) $email_number, '\\Seen' );
	}

	/**
	 * Add a reply to a ticket.
	 *
	 * @param int    $ticket_id   Ticket post ID.
	 * @param int    $user_id     User ID.
	 * @param string $content     Reply content.
	 * @param bool   $is_private  Whether this is a private note.
	 * @param bool   $is_staff    Whether this is a staff reply.
	 * @param array  $attachments Attachment IDs.
	 * @return int|false Comment ID on success, false on failure.
	 */
	public function add_reply( int $ticket_id, int $user_id, string $content, bool $is_private = false, bool $is_staff = false, array $attachments = array() ) {
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return false;
		}

		/**
		 * Filters the reply content before saving to the database.
		 *
		 * @since 1.0.0
		 *
		 * @param string $content   The reply content.
		 * @param int    $ticket_id The ticket post ID.
		 * @param int    $user_id   The user ID posting the reply.
		 * @param bool   $is_staff  Whether this is a staff reply.
		 */
		$content = apply_filters( 'hostforge_ticket_reply_content', $content, $ticket_id, $user_id, $is_staff );

		$comment_data = array(
			'comment_post_ID'      => $ticket_id,
			'comment_content'      => $content,
			'comment_type'         => 'hf_ticket_reply',
			'user_id'              => $user_id,
			'comment_author'       => $user->display_name,
			'comment_author_email' => $user->user_email,
			'comment_approved'     => 1,
		);

		$comment_id = wp_insert_comment( $comment_data );

		if ( ! $comment_id ) {
			return false;
		}

		if ( $is_private ) {
			update_comment_meta( $comment_id, '_hf_is_private_note', '1' );
		}

		if ( $is_staff ) {
			update_comment_meta( $comment_id, '_hf_is_staff_reply', '1' );
		}

		if ( ! empty( $attachments ) ) {
			$attachment_ids = array_map( 'absint', $attachments );
			update_comment_meta( $comment_id, '_hf_attachments', $attachment_ids );
		}

		// Update ticket meta.
		update_post_meta( $ticket_id, '_hf_last_reply_at', current_time( 'mysql' ) );
		update_post_meta( $ticket_id, '_hf_last_reply_by', $user_id );
		delete_post_meta( $ticket_id, '_hf_auto_close_warned' );

		// Update ticket status.
		if ( ! $is_private ) {
			$new_status = $is_staff ? 'staff_reply' : 'customer_reply';
			update_post_meta( $ticket_id, '_hf_status', $new_status );
		}

		/**
		 * Fires when a ticket reply is added.
		 *
		 * @param int  $ticket_id The ticket post ID.
		 * @param int  $comment_id The reply comment ID.
		 * @param bool $is_staff   Whether the reply is from staff.
		 */
		do_action( 'hostforge_ticket_replied', $ticket_id, $comment_id, $is_staff );

		return $comment_id;
	}

	/**
	 * Exclude ticket replies from normal comment queries.
	 *
	 * @param \WP_Comment_Query $query Comment query.
	 * @return void
	 */
	public function exclude_ticket_replies( \WP_Comment_Query $query ): void {
		if ( ! is_admin() ) {
			return;
		}

		// Only exclude from non-ticket contexts.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && 'hostforge' !== substr( $screen->id ?? '', 0, 9 ) ) {
			$type_not_in = $query->query_vars['type__not_in'] ?? array();

			if ( ! is_array( $type_not_in ) ) {
				$type_not_in = array( $type_not_in );
			}

			$type_not_in[]                     = 'hf_ticket_reply';
			$query->query_vars['type__not_in'] = $type_not_in;
		}
	}

	/**
	 * Handle reply insertion for ticket status updates.
	 *
	 * @param int         $comment_id Comment ID.
	 * @param \WP_Comment $comment    Comment object.
	 * @return void
	 */
	public function on_reply_inserted( int $comment_id, \WP_Comment $comment ): void {
		if ( 'hf_ticket_reply' !== $comment->comment_type ) {
			return;
		}

		$ticket = get_post( $comment->comment_post_ID );

		if ( ! $ticket || 'hf_ticket' !== $ticket->post_type ) {
			return;
		}

		// Reopen closed ticket on customer reply.
		$status   = get_post_meta( $ticket->ID, '_hf_status', true );
		$is_staff = get_comment_meta( $comment_id, '_hf_is_staff_reply', true );

		if ( 'closed' === $status && ! $is_staff ) {
			update_post_meta( $ticket->ID, '_hf_status', 'customer_reply' );
		}
	}

	/**
	 * Get admin menu items.
	 *
	 * @return array
	 */
	public function get_admin_menu_items(): array {
		return array(
			array(
				'title'      => __( 'Tickets', 'hostforge' ),
				'slug'       => 'hostforge-tickets',
				'capability' => 'manage_hostforge_tickets',
				'callback'   => array( new Admin\HF_Ticket_Admin( $this ), 'render_tickets_page' ),
			),
			array(
				'title'      => __( 'Knowledge Base', 'hostforge' ),
				'slug'       => 'hostforge-knowledge-base',
				'capability' => 'manage_hostforge_tickets',
				'callback'   => array( new Admin\HF_Ticket_Admin( $this ), 'render_kb_page' ),
			),
		);
	}

	/**
	 * Get My Account endpoints for this module.
	 *
	 * @return array
	 */
	public function get_myaccount_endpoints(): array {
		return array(
			array(
				'endpoint' => 'support-tickets',
				'title'    => __( 'Support Tickets', 'hostforge' ),
			),
		);
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$controller = new Api\HF_REST_Ticket_Controller();
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
				'id'       => 'hf-tickets-overview',
				'title'    => __( 'Support Tickets', 'hostforge' ),
				'callback' => array( $this, 'render_dashboard_widget' ),
			),
		);
	}

	/**
	 * Render the tickets dashboard widget.
	 *
	 * @return void
	 */
	public function render_dashboard_widget(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value AS status, COUNT(*) AS total
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
				WHERE p.post_type = %s AND p.post_status = 'publish'
				GROUP BY pm.meta_value",
				'_hf_status',
				'hf_ticket'
			)
		);

		$counts = array(
			'open'           => 0,
			'customer_reply' => 0,
			'staff_reply'    => 0,
			'in_progress'    => 0,
			'closed'         => 0,
		);

		if ( $results ) {
			foreach ( $results as $row ) {
				if ( isset( $counts[ $row->status ] ) ) {
					$counts[ $row->status ] = absint( $row->total );
				}
			}
		}

		$open_total = $counts['open'] + $counts['customer_reply'] + $counts['in_progress'];

		?>
		<div class="hf-widget-stats">
			<div class="hf-stat">
				<span class="hf-stat__number"><?php echo esc_html( $open_total ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Open Tickets', 'hostforge' ); ?></span>
			</div>
			<div class="hf-stat">
				<span class="hf-stat__number hf-stat__number--warning"><?php echo esc_html( $counts['customer_reply'] ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Awaiting Reply', 'hostforge' ); ?></span>
			</div>
			<div class="hf-stat">
				<span class="hf-stat__number hf-stat__number--success"><?php echo esc_html( $counts['closed'] ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Closed', 'hostforge' ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Get all meta keys for the hf_ticket CPT.
	 *
	 * @return array<string, string> Key => description.
	 */
	public static function get_ticket_meta_keys(): array {
		return array(
			'_hf_department'        => 'Department term ID',
			'_hf_priority'          => 'critical, high, medium, low',
			'_hf_status'            => 'open, customer_reply, staff_reply, in_progress, closed',
			'_hf_assigned_to'       => 'Staff user ID',
			'_hf_related_service'   => 'Related hf_service post ID',
			'_hf_client_user_id'    => 'Customer user ID',
			'_hf_last_reply_at'     => 'DateTime of last reply',
			'_hf_last_reply_by'     => 'User ID of last replier',
			'_hf_flagged'           => 'Whether ticket is flagged',
			'_hf_auto_close_warned' => 'Whether auto-close warning was sent',
		);
	}

	/**
	 * Get valid ticket statuses.
	 *
	 * @return array<string, string>
	 */
	public static function get_statuses(): array {
		$statuses = array(
			'open'           => __( 'Open', 'hostforge' ),
			'customer_reply' => __( 'Customer Reply', 'hostforge' ),
			'staff_reply'    => __( 'Staff Reply', 'hostforge' ),
			'in_progress'    => __( 'In Progress', 'hostforge' ),
			'closed'         => __( 'Closed', 'hostforge' ),
		);

		/**
		 * Filters the available ticket statuses.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $statuses Status slug => label pairs.
		 */
		return apply_filters( 'hostforge_ticket_statuses', $statuses );
	}

	/**
	 * Get valid ticket priorities.
	 *
	 * @return array<string, string>
	 */
	public static function get_priorities(): array {
		$priorities = array(
			'low'      => __( 'Low', 'hostforge' ),
			'medium'   => __( 'Medium', 'hostforge' ),
			'high'     => __( 'High', 'hostforge' ),
			'critical' => __( 'Critical', 'hostforge' ),
		);

		/**
		 * Filters the available ticket priorities.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $priorities Priority slug => label pairs.
		 */
		return apply_filters( 'hostforge_ticket_priorities', $priorities );
	}

	/**
	 * Get ticket replies (comments).
	 *
	 * @param int  $ticket_id    Ticket post ID.
	 * @param bool $include_private Whether to include private notes.
	 * @return array<\WP_Comment>
	 */
	public static function get_replies( int $ticket_id, bool $include_private = true ): array {
		$args = array(
			'post_id' => $ticket_id,
			'type'    => 'hf_ticket_reply',
			'orderby' => 'comment_date',
			'order'   => 'ASC',
			'status'  => 'approve',
		);

		$comments = get_comments( $args );

		if ( ! $include_private ) {
			$comments = array_filter(
				$comments,
				function ( $comment ) {
					return ! get_comment_meta( $comment->comment_ID, '_hf_is_private_note', true );
				}
			);
		}

		return array_values( $comments );
	}

	/**
	 * Get KB article data for rendering, with filter applied.
	 *
	 * @param int $article_id KB article post ID.
	 * @return array<string, mixed> Article data array.
	 */
	public static function get_kb_article_data( int $article_id ): array {
		$article = get_post( $article_id );

		if ( ! $article || 'hf_kb_article' !== $article->post_type ) {
			return array();
		}

		$categories = wp_get_object_terms( $article_id, 'hf_kb_category' );
		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}

		$data = array(
			'id'               => $article->ID,
			'title'            => $article->post_title,
			'content'          => $article->post_content,
			'excerpt'          => wp_trim_words( $article->post_content, 30, '...' ),
			'categories'       => $categories,
			'visibility'       => get_post_meta( $article_id, '_hf_visibility', true ),
			'helpful_yes'      => absint( get_post_meta( $article_id, '_hf_helpful_yes', true ) ),
			'helpful_no'       => absint( get_post_meta( $article_id, '_hf_helpful_no', true ) ),
			'related_articles' => get_post_meta( $article_id, '_hf_related_articles', true ),
			'url'              => get_permalink( $article_id ),
		);

		/**
		 * Filters KB article data before rendering.
		 *
		 * @since 1.0.0
		 *
		 * @param array    $data    Article data array.
		 * @param \WP_Post $article The KB article post object.
		 */
		return apply_filters( 'hostforge_kb_article_data', $data, $article );
	}

	/**
	 * Process merge tags in canned response content.
	 *
	 * @param string $content   Content with merge tags.
	 * @param int    $ticket_id Ticket post ID.
	 * @return string Processed content.
	 */
	public static function process_merge_tags( string $content, int $ticket_id ): string {
		$ticket    = get_post( $ticket_id );
		$client_id = absint( get_post_meta( $ticket_id, '_hf_status', true ) );
		$user      = get_user_by( 'id', get_post_meta( $ticket_id, '_hf_client_user_id', true ) );

		$service_id = absint( get_post_meta( $ticket_id, '_hf_related_service', true ) );
		$domain     = $service_id ? get_post_meta( $service_id, '_hf_domain', true ) : '';

		$replacements = array(
			'{customer_name}'  => $user ? $user->display_name : '',
			'{customer_email}' => $user ? $user->user_email : '',
			'{ticket_id}'      => (string) $ticket_id,
			'{ticket_subject}' => $ticket ? $ticket->post_title : '',
			'{service_domain}' => $domain,
			'{site_name}'      => get_bloginfo( 'name' ),
			'{site_url}'       => home_url(),
		);

		/**
		 * Filters the merge tag replacements for canned responses.
		 *
		 * @param array  $replacements Tag => value pairs.
		 * @param string $content      Original content.
		 * @param int    $ticket_id    Ticket post ID.
		 */
		$replacements = apply_filters( 'hostforge_canned_response_merge_tags', $replacements, $content, $ticket_id );

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
	}
}
