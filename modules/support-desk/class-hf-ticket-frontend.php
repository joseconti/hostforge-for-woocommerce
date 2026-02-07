<?php
/**
 * Ticket Frontend.
 *
 * Handles WooCommerce My Account integration for support tickets
 * and public Knowledge Base page templates.
 *
 * @package HostForge\Modules\SupportDesk
 */

namespace HostForge\Modules\SupportDesk;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Ticket_Frontend
 */
class HF_Ticket_Frontend {

	/**
	 * Endpoint slug.
	 *
	 * @var string
	 */
	private const ENDPOINT = 'support-tickets';

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Register endpoint.
		add_action( 'init', array( $this, 'register_endpoints' ) );

		// My Account menu item.
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_items' ), 50 );

		// Endpoint content.
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render_support_tickets' ) );

		// Enqueue frontend assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_hf_frontend_new_ticket', array( $this, 'ajax_create_ticket' ) );
		add_action( 'wp_ajax_hf_frontend_ticket_reply', array( $this, 'ajax_ticket_reply' ) );
		add_action( 'wp_ajax_hf_frontend_cancel_ticket', array( $this, 'ajax_cancel_ticket' ) );

		// Knowledge Base template override.
		add_filter( 'template_include', array( $this, 'kb_template' ) );
	}

	/**
	 * Register the rewrite endpoint for support tickets.
	 *
	 * @return void
	 */
	public function register_endpoints(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add Support Tickets menu item to WooCommerce My Account.
	 *
	 * @param array $items Menu items.
	 * @return array
	 */
	public function add_menu_items( array $items ): array {
		$new_items = array();

		foreach ( $items as $key => $label ) {
			if ( 'customer-logout' === $key ) {
				$new_items[ self::ENDPOINT ] = __( 'Support Tickets', 'hostforge' );
			}
			$new_items[ $key ] = $label;
		}

		return $new_items;
	}

	/**
	 * Enqueue frontend assets on My Account and Knowledge Base pages.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$is_account = function_exists( 'is_account_page' ) && is_account_page();
		$is_kb      = is_singular( 'hf_kb_article' ) || is_post_type_archive( 'hf_kb_article' ) || is_tax( 'hf_kb_category' );

		if ( ! $is_account && ! $is_kb ) {
			return;
		}

		wp_enqueue_style(
			'hostforge-ticket-frontend',
			HOSTFORGE_PLUGIN_URL . 'assets/css/ticket-frontend.css',
			array(),
			HOSTFORGE_VERSION
		);

		wp_enqueue_script(
			'hostforge-ticket-frontend',
			HOSTFORGE_PLUGIN_URL . 'assets/js/ticket-frontend.js',
			array(),
			HOSTFORGE_VERSION,
			true
		);

		wp_localize_script(
			'hostforge-ticket-frontend',
			'hostforgeTicketFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hf_frontend_ticket_nonce' ),
				'i18n'    => array(
					'loading'         => __( 'Loading...', 'hostforge' ),
					'sending'         => __( 'Sending...', 'hostforge' ),
					'ticketCreated'   => __( 'Ticket created successfully.', 'hostforge' ),
					'replySent'       => __( 'Reply sent successfully.', 'hostforge' ),
					'ticketClosed'    => __( 'Ticket has been closed.', 'hostforge' ),
					'confirmClose'    => __( 'Are you sure you want to close this ticket?', 'hostforge' ),
					'error'           => __( 'An error occurred. Please try again.', 'hostforge' ),
					'subjectRequired' => __( 'Please enter a subject.', 'hostforge' ),
					'messageRequired' => __( 'Please enter a message.', 'hostforge' ),
					'fileTooLarge'    => __( 'File is too large. Maximum size is 10MB.', 'hostforge' ),
					'invalidFileType' => __( 'Invalid file type.', 'hostforge' ),
				),
			)
		);
	}

	/**
	 * Main endpoint handler for support tickets.
	 *
	 * Routes to ticket list, ticket detail, or new ticket form.
	 *
	 * @param string $value Endpoint value (ticket ID, 'new', or empty for list).
	 * @return void
	 */
	public function render_support_tickets( string $value = '' ): void {
		if ( is_numeric( $value ) && absint( $value ) > 0 ) {
			$this->render_ticket_detail( absint( $value ) );
			return;
		}

		if ( 'new' === $value ) {
			$this->render_new_ticket();
			return;
		}

		$this->render_ticket_list();
	}

	/**
	 * Render the ticket list for the current user.
	 *
	 * @return void
	 */
	private function render_ticket_list(): void {
		$user_id = get_current_user_id();

		$query_args = array(
			'post_type'      => 'hf_ticket',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_hf_client_user_id',
					'value' => $user_id,
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		/**
		 * Filters the query arguments for listing user tickets on the frontend.
		 *
		 * @since 1.0.0
		 *
		 * @param array $query_args WP_Query arguments.
		 * @param int   $user_id    The current user ID.
		 */
		$query_args = apply_filters( 'hostforge_ticket_list_query', $query_args, $user_id );

		$tickets = get_posts( $query_args );

		$statuses   = HF_Support_Desk_Module::get_statuses();
		$priorities = HF_Support_Desk_Module::get_priorities();

		$template = hf_locate_template( 'frontend/ticket-list.php' );
		if ( $template ) {
			include $template;
		}
	}

	/**
	 * Render a single ticket detail page.
	 *
	 * Verifies that the current user owns the ticket before displaying.
	 *
	 * @param int $ticket_id Ticket post ID.
	 * @return void
	 */
	private function render_ticket_detail( int $ticket_id ): void {
		$user_id = get_current_user_id();
		$ticket  = get_post( $ticket_id );

		if ( ! $ticket || 'hf_ticket' !== $ticket->post_type ) {
			echo '<p>' . esc_html__( 'Ticket not found.', 'hostforge' ) . '</p>';
			return;
		}

		// Verify ownership.
		$client_user_id = absint( get_post_meta( $ticket_id, '_hf_client_user_id', true ) );
		if ( $client_user_id !== $user_id ) {
			echo '<p>' . esc_html__( 'You do not have access to this ticket.', 'hostforge' ) . '</p>';
			return;
		}

		// Get non-private replies only.
		$replies = HF_Support_Desk_Module::get_replies( $ticket_id, false );

		$status     = get_post_meta( $ticket_id, '_hf_status', true );
		$priority   = get_post_meta( $ticket_id, '_hf_priority', true );
		$department = '';
		$terms      = wp_get_object_terms( $ticket_id, 'hf_department' );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$department = $terms[0]->name;
		}

		$statuses   = HF_Support_Desk_Module::get_statuses();
		$priorities = HF_Support_Desk_Module::get_priorities();

		$template = hf_locate_template( 'frontend/ticket-detail.php' );
		if ( $template ) {
			include $template;
		}
	}

	/**
	 * Render the new ticket form.
	 *
	 * Provides departments list and user services for linking.
	 *
	 * @return void
	 */
	private function render_new_ticket(): void {
		$user_id = get_current_user_id();

		// Get available departments.
		$departments = get_terms(
			array(
				'taxonomy'   => 'hf_department',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $departments ) ) {
			$departments = array();
		}

		// Get user's services for linking.
		$services = get_posts(
			array(
				'post_type'      => 'hf_service',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_hf_user_id',
						'value' => $user_id,
					),
				),
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$priorities = HF_Support_Desk_Module::get_priorities();

		$form_fields = array(
			'departments' => $departments,
			'services'    => $services,
			'priorities'  => $priorities,
		);

		/**
		 * Filters the new ticket form fields and data available to the template.
		 *
		 * @since 1.0.0
		 *
		 * @param array $form_fields Array of form field data (departments, services, priorities).
		 * @param int   $user_id     The current user ID.
		 */
		$form_fields = apply_filters( 'hostforge_ticket_form_fields', $form_fields, $user_id );

		$departments = $form_fields['departments'];
		$services    = $form_fields['services'];
		$priorities  = $form_fields['priorities'];

		$template = hf_locate_template( 'frontend/ticket-new.php' );
		if ( $template ) {
			include $template;
		}
	}

	/**
	 * Override template for Knowledge Base post type pages.
	 *
	 * Loads custom KB templates via hf_locate_template when viewing
	 * KB articles, archives, or category pages.
	 *
	 * @param string $template Default template path.
	 * @return string
	 */
	public function kb_template( string $template ): string {
		if ( is_singular( 'hf_kb_article' ) ) {
			$kb_template = hf_locate_template( 'frontend/kb-single.php' );
			if ( $kb_template ) {
				return $kb_template;
			}
		}

		if ( is_post_type_archive( 'hf_kb_article' ) ) {
			$kb_template = hf_locate_template( 'frontend/kb-archive.php' );
			if ( $kb_template ) {
				return $kb_template;
			}
		}

		if ( is_tax( 'hf_kb_category' ) ) {
			$kb_template = hf_locate_template( 'frontend/kb-category.php' );
			if ( $kb_template ) {
				return $kb_template;
			}
		}

		return $template;
	}

	/**
	 * AJAX: Create a new support ticket.
	 *
	 * @return void
	 */
	public function ajax_create_ticket(): void {
		check_ajax_referer( 'hf_frontend_ticket_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in to create a ticket.', 'hostforge' ) ) );
		}

		$user_id = get_current_user_id();
		$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$message = isset( $_POST['message'] ) ? wp_kses_post( wp_unslash( $_POST['message'] ) ) : '';

		if ( empty( $subject ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a subject.', 'hostforge' ) ) );
		}

		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a message.', 'hostforge' ) ) );
		}

		$department_id   = isset( $_POST['department'] ) ? absint( $_POST['department'] ) : 0;
		$related_service = isset( $_POST['related_service'] ) ? absint( $_POST['related_service'] ) : 0;

		// Validate related service ownership if provided.
		if ( $related_service > 0 ) {
			$service_owner = absint( get_post_meta( $related_service, '_hf_user_id', true ) );
			if ( $service_owner !== $user_id ) {
				$related_service = 0;
			}
		}

		// Create the ticket post.
		$ticket_id = wp_insert_post(
			array(
				'post_type'    => 'hf_ticket',
				'post_title'   => $subject,
				'post_content' => $message,
				'post_status'  => 'publish',
				'post_author'  => $user_id,
			)
		);

		if ( is_wp_error( $ticket_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not create ticket. Please try again.', 'hostforge' ) ) );
		}

		// Set ticket meta.
		update_post_meta( $ticket_id, '_hf_status', 'open' );
		update_post_meta( $ticket_id, '_hf_priority', 'medium' );
		update_post_meta( $ticket_id, '_hf_client_user_id', $user_id );
		update_post_meta( $ticket_id, '_hf_last_reply_at', current_time( 'mysql' ) );
		update_post_meta( $ticket_id, '_hf_last_reply_by', $user_id );

		// Set department.
		if ( $department_id > 0 ) {
			update_post_meta( $ticket_id, '_hf_department', $department_id );
			wp_set_object_terms( $ticket_id, $department_id, 'hf_department' );
		}

		// Set related service.
		if ( $related_service > 0 ) {
			update_post_meta( $ticket_id, '_hf_related_service', $related_service );
		}

		// Handle file attachments.
		$attachment_ids = $this->handle_file_uploads( $ticket_id );

		if ( ! empty( $attachment_ids ) ) {
			update_post_meta( $ticket_id, '_hf_attachments', $attachment_ids );
		}

		/**
		 * Fires when a new ticket is created from the frontend.
		 *
		 * @param int $ticket_id The ticket post ID.
		 * @param int $user_id   The user who created the ticket.
		 */
		do_action( 'hostforge_ticket_created', $ticket_id, $user_id );

		/**
		 * Fires after a ticket is submitted from the frontend form.
		 *
		 * This fires in addition to hostforge_ticket_created and provides
		 * additional context about the frontend submission (department, service, attachments).
		 *
		 * @since 1.0.0
		 *
		 * @param int   $ticket_id      The ticket post ID.
		 * @param int   $user_id        The user who submitted the ticket.
		 * @param int   $department_id  The selected department term ID.
		 * @param int   $related_service The related service post ID (0 if none).
		 * @param array $attachment_ids  Array of attachment IDs.
		 */
		do_action( 'hostforge_ticket_submitted', $ticket_id, $user_id, $department_id, $related_service, $attachment_ids );

		$redirect_url = wc_get_account_endpoint_url( self::ENDPOINT ) . $ticket_id . '/';

		wp_send_json_success(
			array(
				'message'  => __( 'Ticket created successfully.', 'hostforge' ),
				'redirect' => $redirect_url,
			)
		);
	}

	/**
	 * AJAX: Add a reply to an existing ticket.
	 *
	 * @return void
	 */
	public function ajax_ticket_reply(): void {
		check_ajax_referer( 'hf_frontend_ticket_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in to reply.', 'hostforge' ) ) );
		}

		$user_id   = get_current_user_id();
		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;
		$message   = isset( $_POST['message'] ) ? wp_kses_post( wp_unslash( $_POST['message'] ) ) : '';

		if ( ! $this->verify_ticket_ownership( $ticket_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have access to this ticket.', 'hostforge' ) ) );
		}

		/**
		 * Filters whether the current user can reply to a ticket.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $can_reply  Whether the user can reply. Default true.
		 * @param int  $ticket_id  The ticket post ID.
		 * @param int  $user_id    The user ID attempting to reply.
		 */
		$can_reply = apply_filters( 'hostforge_ticket_can_reply', true, $ticket_id, $user_id );

		if ( ! $can_reply ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to reply to this ticket.', 'hostforge' ) ) );
		}

		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a message.', 'hostforge' ) ) );
		}

		// Check that ticket is not closed.
		$status = get_post_meta( $ticket_id, '_hf_status', true );
		if ( 'closed' === $status ) {
			// Reopen the ticket on customer reply.
			update_post_meta( $ticket_id, '_hf_status', 'customer_reply' );
		}

		// Handle file uploads.
		$attachment_ids = $this->handle_file_uploads( $ticket_id );

		// Add reply via the module method.
		$module = $this->get_support_module();
		if ( $module ) {
			$comment_id = $module->add_reply(
				$ticket_id,
				$user_id,
				$message,
				false, // Not private.
				false, // Not staff.
				$attachment_ids
			);
		} else {
			// Fallback if module instance is not available.
			$user         = get_user_by( 'id', $user_id );
			$comment_data = array(
				'comment_post_ID'      => $ticket_id,
				'comment_content'      => $message,
				'comment_type'         => 'hf_ticket_reply',
				'user_id'              => $user_id,
				'comment_author'       => $user ? $user->display_name : '',
				'comment_author_email' => $user ? $user->user_email : '',
				'comment_approved'     => 1,
			);

			$comment_id = wp_insert_comment( $comment_data );

			if ( $comment_id && ! empty( $attachment_ids ) ) {
				update_comment_meta( $comment_id, '_hf_attachments', $attachment_ids );
			}

			if ( $comment_id ) {
				update_post_meta( $ticket_id, '_hf_last_reply_at', current_time( 'mysql' ) );
				update_post_meta( $ticket_id, '_hf_last_reply_by', $user_id );
				update_post_meta( $ticket_id, '_hf_status', 'customer_reply' );
				delete_post_meta( $ticket_id, '_hf_auto_close_warned' );

				/**
				 * Fires when a ticket reply is added.
				 *
				 * @param int  $ticket_id  The ticket post ID.
				 * @param int  $comment_id The reply comment ID.
				 * @param bool $is_staff   Whether the reply is from staff.
				 */
				do_action( 'hostforge_ticket_replied', $ticket_id, $comment_id, false );
			}
		}

		if ( ! empty( $comment_id ) ) {
			/**
			 * Fires after a ticket reply is submitted from the frontend.
			 *
			 * @since 1.0.0
			 *
			 * @param int   $ticket_id      The ticket post ID.
			 * @param int   $comment_id     The reply comment ID.
			 * @param int   $user_id        The user who submitted the reply.
			 * @param array $attachment_ids  Array of attachment IDs for the reply.
			 */
			do_action( 'hostforge_ticket_reply_submitted', $ticket_id, $comment_id, $user_id, $attachment_ids );

			wp_send_json_success( array( 'message' => __( 'Reply sent successfully.', 'hostforge' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Could not send reply. Please try again.', 'hostforge' ) ) );
		}
	}

	/**
	 * AJAX: Close/cancel a ticket.
	 *
	 * @return void
	 */
	public function ajax_cancel_ticket(): void {
		check_ajax_referer( 'hf_frontend_ticket_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'hostforge' ) ) );
		}

		$user_id   = get_current_user_id();
		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;

		if ( ! $this->verify_ticket_ownership( $ticket_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have access to this ticket.', 'hostforge' ) ) );
		}

		$status = get_post_meta( $ticket_id, '_hf_status', true );

		if ( 'closed' === $status ) {
			wp_send_json_error( array( 'message' => __( 'This ticket is already closed.', 'hostforge' ) ) );
		}

		update_post_meta( $ticket_id, '_hf_status', 'closed' );

		/**
		 * Fires when a ticket is closed by the customer.
		 *
		 * @param int $ticket_id The ticket post ID.
		 */
		do_action( 'hostforge_ticket_closed', $ticket_id );

		wp_send_json_success( array( 'message' => __( 'Ticket has been closed.', 'hostforge' ) ) );
	}

	/**
	 * Verify that the current user owns the given ticket.
	 *
	 * @param int $ticket_id Ticket post ID.
	 * @param int $user_id   User ID.
	 * @return bool
	 */
	private function verify_ticket_ownership( int $ticket_id, int $user_id ): bool {
		if ( $ticket_id <= 0 || $user_id <= 0 ) {
			return false;
		}

		$ticket = get_post( $ticket_id );

		if ( ! $ticket || 'hf_ticket' !== $ticket->post_type ) {
			return false;
		}

		$client_user_id = absint( get_post_meta( $ticket_id, '_hf_client_user_id', true ) );

		return $client_user_id === $user_id;
	}

	/**
	 * Handle file uploads for tickets and replies.
	 *
	 * Processes uploaded files and creates WordPress media attachments.
	 *
	 * @param int $ticket_id Ticket post ID to attach files to.
	 * @return array<int> Array of attachment IDs.
	 */
	private function handle_file_uploads( int $ticket_id ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
		if ( empty( $_FILES['attachments'] ) ) {
			return array();
		}

		// Ensure media functions are available.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$attachment_ids = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		$files = $_FILES['attachments'];

		// Allowed file types.
		$allowed_types = array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
			'application/pdf',
			'text/plain',
			'application/zip',
			'application/x-zip-compressed',
			'application/msword',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		);

		/**
		 * Filters the allowed MIME types for ticket attachments.
		 *
		 * @param array $allowed_types Array of allowed MIME types.
		 */
		$allowed_types = apply_filters( 'hostforge_ticket_allowed_file_types', $allowed_types );

		$max_size = 10 * MB_IN_BYTES; // 10MB.

		// Normalize the files array for multiple uploads.
		if ( is_array( $files['name'] ) ) {
			$file_count = count( $files['name'] );

			for ( $i = 0; $i < $file_count; $i++ ) {
				if ( UPLOAD_ERR_OK !== $files['error'][ $i ] ) {
					continue;
				}

				if ( $files['size'][ $i ] > $max_size ) {
					continue;
				}

				// Verify MIME type.
				$file_type = wp_check_filetype( $files['name'][ $i ] );
				if ( empty( $file_type['type'] ) || ! in_array( $file_type['type'], $allowed_types, true ) ) {
					continue;
				}

				$_FILES['hf_upload'] = array(
					'name'     => $files['name'][ $i ],
					'type'     => $files['type'][ $i ],
					'tmp_name' => $files['tmp_name'][ $i ],
					'error'    => $files['error'][ $i ],
					'size'     => $files['size'][ $i ],
				);

				$attachment_id = media_handle_upload( 'hf_upload', $ticket_id );

				if ( ! is_wp_error( $attachment_id ) ) {
					$attachment_ids[] = $attachment_id;
				}
			}

			unset( $_FILES['hf_upload'] );
		} elseif ( UPLOAD_ERR_OK === $files['error'] && $files['size'] <= $max_size ) {
			// Single file upload.
			$file_type = wp_check_filetype( $files['name'] );

			if ( ! empty( $file_type['type'] ) && in_array( $file_type['type'], $allowed_types, true ) ) {
				$_FILES['hf_upload'] = $files;
				$attachment_id       = media_handle_upload( 'hf_upload', $ticket_id );

				if ( ! is_wp_error( $attachment_id ) ) {
					$attachment_ids[] = $attachment_id;
				}

				unset( $_FILES['hf_upload'] );
			}
		}

		return $attachment_ids;
	}

	/**
	 * Get the Support Desk module instance.
	 *
	 * @return HF_Support_Desk_Module|null
	 */
	private function get_support_module(): ?HF_Support_Desk_Module {
		$module_manager = \HostForge\HostForge::instance()->module_manager();
		$module         = $module_manager->get_module( 'support-desk' );

		if ( $module instanceof HF_Support_Desk_Module ) {
			return $module;
		}

		return null;
	}
}
