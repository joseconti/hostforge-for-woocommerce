<?php
/**
 * Ticket Admin.
 *
 * Handles admin screens for the Support Desk module:
 * ticket list, ticket detail, new ticket, departments,
 * canned responses, knowledge base management.
 *
 * @package HostForge\Modules\SupportDesk\Admin
 */

namespace HostForge\Modules\SupportDesk\Admin;

use HostForge\Modules\SupportDesk\HF_Support_Desk_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Ticket_Admin
 */
class HF_Ticket_Admin {

	/**
	 * Module instance.
	 *
	 * @var HF_Support_Desk_Module
	 */
	private HF_Support_Desk_Module $module;

	/**
	 * Constructor.
	 *
	 * @param HF_Support_Desk_Module $module Module instance.
	 */
	public function __construct( HF_Support_Desk_Module $module ) {
		$this->module = $module;
	}

	/**
	 * Initialize admin hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Ticket AJAX.
		add_action( 'wp_ajax_hf_save_ticket', array( $this, 'ajax_save_ticket' ) );
		add_action( 'wp_ajax_hf_ticket_reply', array( $this, 'ajax_ticket_reply' ) );
		add_action( 'wp_ajax_hf_ticket_update_status', array( $this, 'ajax_update_status' ) );
		add_action( 'wp_ajax_hf_ticket_assign', array( $this, 'ajax_assign_ticket' ) );

		// Department AJAX.
		add_action( 'wp_ajax_hf_save_department', array( $this, 'ajax_save_department' ) );
		add_action( 'wp_ajax_hf_delete_department', array( $this, 'ajax_delete_department' ) );

		// KB article AJAX.
		add_action( 'wp_ajax_hf_save_kb_article', array( $this, 'ajax_save_kb_article' ) );
		add_action( 'wp_ajax_hf_delete_kb_article', array( $this, 'ajax_delete_kb_article' ) );

		// Canned response AJAX.
		add_action( 'wp_ajax_hf_save_canned_response', array( $this, 'ajax_save_canned_response' ) );
		add_action( 'wp_ajax_hf_delete_canned_response', array( $this, 'ajax_delete_canned_response' ) );

		// Public AJAX (logged-in and non-logged-in users).
		add_action( 'wp_ajax_hf_kb_vote', array( $this, 'ajax_kb_vote' ) );
		add_action( 'wp_ajax_nopriv_hf_kb_vote', array( $this, 'ajax_kb_vote' ) );
		add_action( 'wp_ajax_hf_kb_search', array( $this, 'ajax_kb_search' ) );
		add_action( 'wp_ajax_nopriv_hf_kb_search', array( $this, 'ajax_kb_search' ) );
	}

	/**
	 * Enqueue assets on ticket and KB admin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		if ( ! str_contains( $screen->id, 'hostforge-tickets' ) && ! str_contains( $screen->id, 'hostforge-knowledge-base' ) ) {
			return;
		}

		wp_enqueue_style(
			'hostforge-ticket-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/css/ticket-admin.css',
			array( 'hostforge-admin' ),
			HOSTFORGE_VERSION
		);

		wp_enqueue_script(
			'hostforge-ticket-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/js/ticket-admin.js',
			array( 'hostforge-admin' ),
			HOSTFORGE_VERSION,
			true
		);

		wp_localize_script(
			'hostforge-ticket-admin',
			'hostforgeTicket',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hf_ticket_nonce' ),
				'i18n'    => array(
					'saving'              => __( 'Saving...', 'hostforge' ),
					'saved'               => __( 'Saved successfully.', 'hostforge' ),
					'sending'             => __( 'Sending reply...', 'hostforge' ),
					'sent'                => __( 'Reply sent.', 'hostforge' ),
					'updating'            => __( 'Updating...', 'hostforge' ),
					'updated'             => __( 'Updated successfully.', 'hostforge' ),
					'deleting'            => __( 'Deleting...', 'hostforge' ),
					'deleted'             => __( 'Deleted successfully.', 'hostforge' ),
					'confirmDelete'       => __( 'Are you sure you want to delete this item? This cannot be undone.', 'hostforge' ),
					'confirmClose'        => __( 'Are you sure you want to close this ticket?', 'hostforge' ),
					'error'               => __( 'An error occurred.', 'hostforge' ),
					'replyRequired'       => __( 'Please enter a reply.', 'hostforge' ),
					'subjectRequired'     => __( 'Please enter a subject.', 'hostforge' ),
					'contentRequired'     => __( 'Please enter content.', 'hostforge' ),
					'titleRequired'       => __( 'Please enter a title.', 'hostforge' ),
					'departmentRequired'  => __( 'Please enter a department name.', 'hostforge' ),
					'searchPlaceholder'   => __( 'Search knowledge base...', 'hostforge' ),
					'noResults'           => __( 'No articles found.', 'hostforge' ),
					'uploading'           => __( 'Uploading...', 'hostforge' ),
				),
			)
		);
	}

	/**
	 * Render the main tickets page.
	 *
	 * Routes to sub-views based on the action parameter.
	 *
	 * @return void
	 */
	public function render_tickets_page(): void {
		if ( ! current_user_can( 'manage_hostforge_tickets' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hostforge' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ticket_id = isset( $_GET['ticket_id'] ) ? absint( $_GET['ticket_id'] ) : 0;

		switch ( $action ) {
			case 'detail':
				if ( $ticket_id > 0 ) {
					$this->render_ticket_detail( $ticket_id );
				} else {
					$this->render_ticket_list();
				}
				break;

			case 'new':
				$this->render_new_ticket();
				break;

			case 'departments':
				$this->render_departments();
				break;

			case 'canned':
				$this->render_canned_responses();
				break;

			default:
				$this->render_ticket_list();
				break;
		}
	}

	/**
	 * Render the knowledge base admin page.
	 *
	 * Routes to sub-views based on the action parameter.
	 *
	 * @return void
	 */
	public function render_kb_page(): void {
		if ( ! current_user_can( 'manage_hostforge_tickets' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hostforge' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$article_id = isset( $_GET['article_id'] ) ? absint( $_GET['article_id'] ) : 0;

		switch ( $action ) {
			case 'edit':
				if ( $article_id > 0 ) {
					$this->render_kb_edit( $article_id );
				} else {
					$this->render_kb_list();
				}
				break;

			case 'new':
				$this->render_kb_edit( 0 );
				break;

			default:
				$this->render_kb_list();
				break;
		}
	}

	/**
	 * Render the ticket list.
	 *
	 * @return void
	 */
	private function render_ticket_list(): void {
		$list_table = new HF_Ticket_List_Table();
		$list_table->prepare_items();

		$template = $this->module->get_module_dir() . 'admin/templates/ticket-list.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the ticket detail page.
	 *
	 * @param int $ticket_id Ticket post ID.
	 * @return void
	 */
	private function render_ticket_detail( int $ticket_id ): void {
		$ticket = get_post( $ticket_id );

		if ( ! $ticket || 'hf_ticket' !== $ticket->post_type ) {
			wp_die( esc_html__( 'Ticket not found.', 'hostforge' ) );
		}

		// Gather ticket meta.
		$meta = array();
		foreach ( array_keys( HF_Support_Desk_Module::get_ticket_meta_keys() ) as $key ) {
			$meta[ $key ] = get_post_meta( $ticket_id, $key, true );
		}

		// Get ticket replies.
		$replies = HF_Support_Desk_Module::get_replies( $ticket_id, true );

		// Get the client user.
		$client = ! empty( $meta['_hf_client_user_id'] ) ? get_user_by( 'id', absint( $meta['_hf_client_user_id'] ) ) : null;

		// Get assigned staff.
		$assigned_to = ! empty( $meta['_hf_assigned_to'] ) ? get_user_by( 'id', absint( $meta['_hf_assigned_to'] ) ) : null;

		// Get department.
		$departments = wp_get_object_terms( $ticket_id, 'hf_department' );
		if ( is_wp_error( $departments ) ) {
			$departments = array();
		}

		// Get all canned responses for the insert dropdown.
		$canned_responses = get_posts(
			array(
				'post_type'      => 'hf_canned_response',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		// Statuses and priorities for dropdowns.
		$statuses   = HF_Support_Desk_Module::get_statuses();
		$priorities = HF_Support_Desk_Module::get_priorities();

		// Get staff users for assignment dropdown.
		$staff_users = get_users(
			array(
				'role__in' => array( 'administrator', 'shop_manager' ),
				'orderby'  => 'display_name',
				'order'    => 'ASC',
			)
		);

		$template = $this->module->get_module_dir() . 'admin/templates/ticket-detail.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the new ticket form.
	 *
	 * @return void
	 */
	private function render_new_ticket(): void {
		$departments = get_terms(
			array(
				'taxonomy'   => 'hf_department',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $departments ) ) {
			$departments = array();
		}

		$priorities = HF_Support_Desk_Module::get_priorities();

		$template = $this->module->get_module_dir() . 'admin/templates/ticket-new.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the departments management page.
	 *
	 * @return void
	 */
	private function render_departments(): void {
		$departments = get_terms(
			array(
				'taxonomy'   => 'hf_department',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $departments ) ) {
			$departments = array();
		}

		$template = $this->module->get_module_dir() . 'admin/templates/departments.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the canned responses management page.
	 *
	 * @return void
	 */
	private function render_canned_responses(): void {
		$canned_responses = get_posts(
			array(
				'post_type'      => 'hf_canned_response',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$template = $this->module->get_module_dir() . 'admin/templates/canned-responses.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the knowledge base articles list.
	 *
	 * @return void
	 */
	private function render_kb_list(): void {
		$articles = get_posts(
			array(
				'post_type'      => 'hf_kb_article',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$categories = get_terms(
			array(
				'taxonomy'   => 'hf_kb_category',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}

		$template = $this->module->get_module_dir() . 'admin/templates/kb-list.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the knowledge base article edit form.
	 *
	 * @param int $article_id Article post ID (0 for new).
	 * @return void
	 */
	private function render_kb_edit( int $article_id ): void {
		$article = null;
		$meta    = array();

		if ( $article_id > 0 ) {
			$article = get_post( $article_id );

			if ( ! $article || 'hf_kb_article' !== $article->post_type ) {
				wp_die( esc_html__( 'Article not found.', 'hostforge' ) );
			}

			$meta = array(
				'_hf_visibility'       => get_post_meta( $article_id, '_hf_visibility', true ),
				'_hf_related_articles' => get_post_meta( $article_id, '_hf_related_articles', true ),
				'_hf_helpful_yes'      => absint( get_post_meta( $article_id, '_hf_helpful_yes', true ) ),
				'_hf_helpful_no'       => absint( get_post_meta( $article_id, '_hf_helpful_no', true ) ),
			);
		}

		$categories = get_terms(
			array(
				'taxonomy'   => 'hf_kb_category',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}

		$current_categories = array();
		if ( $article_id > 0 ) {
			$current_categories = wp_get_object_terms( $article_id, 'hf_kb_category', array( 'fields' => 'ids' ) );
			if ( is_wp_error( $current_categories ) ) {
				$current_categories = array();
			}
		}

		$template = $this->module->get_module_dir() . 'admin/templates/kb-edit.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * AJAX: Save (create) a new ticket.
	 *
	 * @return void
	 */
	public function ajax_save_ticket(): void {
		check_ajax_referer( 'hf_ticket_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$subject       = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$content       = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$priority      = isset( $_POST['priority'] ) ? sanitize_text_field( wp_unslash( $_POST['priority'] ) ) : 'medium';
		$department_id = isset( $_POST['department'] ) ? absint( $_POST['department'] ) : 0;
		$client_id     = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;

		if ( empty( $subject ) ) {
			wp_send_json_error( array( 'message' => __( 'Subject is required.', 'hostforge' ) ) );
		}

		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Content is required.', 'hostforge' ) ) );
		}

		// Validate priority.
		$valid_priorities = array_keys( HF_Support_Desk_Module::get_priorities() );
		if ( ! in_array( $priority, $valid_priorities, true ) ) {
			$priority = 'medium';
		}

		$ticket_id = wp_insert_post(
			array(
				'post_type'    => 'hf_ticket',
				'post_title'   => $subject,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_author'  => $client_id > 0 ? $client_id : get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $ticket_id ) ) {
			wp_send_json_error( array( 'message' => $ticket_id->get_error_message() ) );
		}

		// Set ticket meta.
		update_post_meta( $ticket_id, '_hf_status', 'open' );
		update_post_meta( $ticket_id, '_hf_priority', $priority );
		update_post_meta( $ticket_id, '_hf_client_user_id', $client_id > 0 ? $client_id : get_current_user_id() );
		update_post_meta( $ticket_id, '_hf_last_reply_at', current_time( 'mysql' ) );

		// Set department taxonomy.
		if ( $department_id > 0 ) {
			update_post_meta( $ticket_id, '_hf_department', $department_id );
			wp_set_object_terms( $ticket_id, $department_id, 'hf_department' );
		}

		/**
		 * Fires when a new ticket is created from admin.
		 *
		 * @param int $ticket_id The ticket post ID.
		 * @param int $client_id The client user ID.
		 */
		do_action( 'hostforge_ticket_created', $ticket_id, $client_id > 0 ? $client_id : get_current_user_id() );

		wp_send_json_success(
			array(
				'message'   => __( 'Ticket created successfully.', 'hostforge' ),
				'ticket_id' => $ticket_id,
				'redirect'  => admin_url( 'admin.php?page=hostforge-tickets&action=detail&ticket_id=' . $ticket_id ),
			)
		);
	}

	/**
	 * AJAX: Add a reply to a ticket.
	 *
	 * @return void
	 */
	public function ajax_ticket_reply(): void {
		check_ajax_referer( 'hf_ticket_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$ticket_id  = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;
		$content    = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$is_private = isset( $_POST['is_private'] ) && '1' === $_POST['is_private'];

		if ( $ticket_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ticket ID.', 'hostforge' ) ) );
		}

		$ticket = get_post( $ticket_id );
		if ( ! $ticket || 'hf_ticket' !== $ticket->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Ticket not found.', 'hostforge' ) ) );
		}

		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Reply content is required.', 'hostforge' ) ) );
		}

		// Handle file uploads.
		$attachment_ids = array();
		if ( ! empty( $_FILES['attachments'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$files = $_FILES['attachments']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

			if ( is_array( $files['name'] ) ) {
				$file_count = count( $files['name'] );
				for ( $i = 0; $i < $file_count; $i++ ) {
					if ( UPLOAD_ERR_OK !== $files['error'][ $i ] ) {
						continue;
					}

					$file_array = array(
						'name'     => sanitize_file_name( $files['name'][ $i ] ),
						'type'     => $files['type'][ $i ],
						'tmp_name' => $files['tmp_name'][ $i ],
						'error'    => $files['error'][ $i ],
						'size'     => $files['size'][ $i ],
					);

					$upload = wp_handle_upload( $file_array, array( 'test_form' => false ) );

					if ( ! empty( $upload['file'] ) ) {
						$attach_id = wp_insert_attachment(
							array(
								'post_mime_type' => $upload['type'],
								'post_title'     => sanitize_file_name( $files['name'][ $i ] ),
								'post_content'   => '',
								'post_status'    => 'inherit',
							),
							$upload['file'],
							$ticket_id
						);

						if ( $attach_id && ! is_wp_error( $attach_id ) ) {
							$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
							wp_update_attachment_metadata( $attach_id, $attach_data );
							$attachment_ids[] = $attach_id;
						}
					}
				}
			}
		}

		$comment_id = $this->module->add_reply(
			$ticket_id,
			get_current_user_id(),
			$content,
			$is_private,
			true, // is_staff.
			$attachment_ids
		);

		if ( ! $comment_id ) {
			wp_send_json_error( array( 'message' => __( 'Failed to add reply.', 'hostforge' ) ) );
		}

		// Build reply HTML for live update.
		$user = wp_get_current_user();
		$reply_html = $this->build_reply_html( $comment_id, $user, $content, $is_private, $attachment_ids );

		wp_send_json_success(
			array(
				'message'    => __( 'Reply added successfully.', 'hostforge' ),
				'comment_id' => $comment_id,
				'reply_html' => $reply_html,
			)
		);
	}

	/**
	 * Build reply HTML for AJAX response.
	 *
	 * @param int      $comment_id    Comment ID.
	 * @param \WP_User $user          User object.
	 * @param string   $content       Reply content.
	 * @param bool     $is_private    Whether it is a private note.
	 * @param array    $attachments   Attachment IDs.
	 * @return string
	 */
	private function build_reply_html( int $comment_id, \WP_User $user, string $content, bool $is_private, array $attachments ): string {
		$class = $is_private ? 'hf-reply hf-reply--private' : 'hf-reply hf-reply--staff';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $class ); ?>" data-reply-id="<?php echo esc_attr( $comment_id ); ?>">
			<div class="hf-reply__header">
				<strong><?php echo esc_html( $user->display_name ); ?></strong>
				<?php if ( $is_private ) : ?>
					<span class="hf-reply__badge hf-reply__badge--private"><?php esc_html_e( 'Private Note', 'hostforge' ); ?></span>
				<?php else : ?>
					<span class="hf-reply__badge hf-reply__badge--staff"><?php esc_html_e( 'Staff', 'hostforge' ); ?></span>
				<?php endif; ?>
				<span class="hf-reply__date"><?php echo esc_html( current_time( 'M j, Y g:i a' ) ); ?></span>
			</div>
			<div class="hf-reply__content">
				<?php echo wp_kses_post( wpautop( $content ) ); ?>
			</div>
			<?php if ( ! empty( $attachments ) ) : ?>
				<div class="hf-reply__attachments">
					<strong><?php esc_html_e( 'Attachments:', 'hostforge' ); ?></strong>
					<ul>
						<?php foreach ( $attachments as $attach_id ) : ?>
							<li>
								<a href="<?php echo esc_url( wp_get_attachment_url( $attach_id ) ); ?>" target="_blank">
									<?php echo esc_html( get_the_title( $attach_id ) ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX: Update ticket status.
	 *
	 * @return void
	 */
	public function ajax_update_status(): void {
		check_ajax_referer( 'hf_ticket_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;
		$status    = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		if ( $ticket_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ticket ID.', 'hostforge' ) ) );
		}

		$ticket = get_post( $ticket_id );
		if ( ! $ticket || 'hf_ticket' !== $ticket->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Ticket not found.', 'hostforge' ) ) );
		}

		// Validate status.
		$valid_statuses = array_keys( HF_Support_Desk_Module::get_statuses() );
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status.', 'hostforge' ) ) );
		}

		$old_status = get_post_meta( $ticket_id, '_hf_status', true );
		update_post_meta( $ticket_id, '_hf_status', $status );

		// Fire event when ticket is closed.
		if ( 'closed' === $status && 'closed' !== $old_status ) {
			/**
			 * Fires when a ticket is closed.
			 *
			 * @param int $ticket_id The ticket post ID.
			 */
			do_action( 'hostforge_ticket_closed', $ticket_id );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Ticket status updated.', 'hostforge' ),
				'status'  => $status,
			)
		);
	}

	/**
	 * AJAX: Assign ticket to a staff member.
	 *
	 * @return void
	 */
	public function ajax_assign_ticket(): void {
		check_ajax_referer( 'hf_ticket_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$ticket_id   = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;
		$assigned_to = isset( $_POST['assigned_to'] ) ? absint( $_POST['assigned_to'] ) : 0;

		if ( $ticket_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ticket ID.', 'hostforge' ) ) );
		}

		$ticket = get_post( $ticket_id );
		if ( ! $ticket || 'hf_ticket' !== $ticket->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Ticket not found.', 'hostforge' ) ) );
		}

		update_post_meta( $ticket_id, '_hf_assigned_to', $assigned_to );

		/**
		 * Fires when a ticket is assigned to a staff member.
		 *
		 * @param int $ticket_id   The ticket post ID.
		 * @param int $assigned_to The staff user ID.
		 */
		do_action( 'hostforge_ticket_assigned', $ticket_id, $assigned_to );

		$assigned_user = $assigned_to > 0 ? get_user_by( 'id', $assigned_to ) : null;
		$assigned_name = $assigned_user ? $assigned_user->display_name : __( 'Unassigned', 'hostforge' );

		wp_send_json_success(
			array(
				'message'       => __( 'Ticket assigned successfully.', 'hostforge' ),
				'assigned_to'   => $assigned_to,
				'assigned_name' => $assigned_name,
			)
		);
	}

	/**
	 * AJAX: Save (create or update) a department.
	 *
	 * @return void
	 */
	public function ajax_save_department(): void {
		check_ajax_referer( 'hf_ticket_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$department_id = isset( $_POST['department_id'] ) ? absint( $_POST['department_id'] ) : 0;
		$name          = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$description   = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Department name is required.', 'hostforge' ) ) );
		}

		if ( $department_id > 0 ) {
			// Update existing department.
			$result = wp_update_term(
				$department_id,
				'hf_department',
				array(
					'name'        => $name,
					'description' => $description,
				)
			);
		} else {
			// Create new department.
			$result = wp_insert_term(
				$name,
				'hf_department',
				array(
					'description' => $description,
				)
			);
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$term_id = is_array( $result ) ? $result['term_id'] : $department_id;

		wp_send_json_success(
			array(
				'message'       => __( 'Department saved successfully.', 'hostforge' ),
				'department_id' => $term_id,
			)
		);
	}

	/**
	 * AJAX: Delete a department.
	 *
	 * @return void
	 */
	public function ajax_delete_department(): void {
		check_ajax_referer( 'hf_ticket_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$department_id = isset( $_POST['department_id'] ) ? absint( $_POST['department_id'] ) : 0;

		if ( $department_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid department ID.', 'hostforge' ) ) );
		}

		$result = wp_delete_term( $department_id, 'hf_department' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Department not found.', 'hostforge' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Department deleted.', 'hostforge' ),
			)
		);
	}

	/**
	 * AJAX: Save (create or update) a knowledge base article.
	 *
	 * @return void
	 */
	public function ajax_save_kb_article(): void {
		check_ajax_referer( 'hf_ticket_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$article_id       = isset( $_POST['article_id'] ) ? absint( $_POST['article_id'] ) : 0;
		$title            = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$content          = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$status           = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'publish';
		$visibility       = isset( $_POST['visibility'] ) ? sanitize_text_field( wp_unslash( $_POST['visibility'] ) ) : 'public';
		$categories       = isset( $_POST['categories'] ) ? array_map( 'absint', (array) $_POST['categories'] ) : array();
		$related_articles = isset( $_POST['related_articles'] ) ? array_map( 'absint', (array) $_POST['related_articles'] ) : array();

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Article title is required.', 'hostforge' ) ) );
		}

		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Article content is required.', 'hostforge' ) ) );
		}

		// Validate status.
		if ( ! in_array( $status, array( 'publish', 'draft', 'pending' ), true ) ) {
			$status = 'draft';
		}

		$post_data = array(
			'post_type'    => 'hf_kb_article',
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $status,
			'post_author'  => get_current_user_id(),
		);

		if ( $article_id > 0 ) {
			$post_data['ID'] = $article_id;
			$result = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$article_id = (int) $result;

		// Save meta.
		update_post_meta( $article_id, '_hf_visibility', $visibility );
		update_post_meta( $article_id, '_hf_related_articles', $related_articles );

		// Initialize vote counts if new.
		if ( '' === get_post_meta( $article_id, '_hf_helpful_yes', true ) ) {
			update_post_meta( $article_id, '_hf_helpful_yes', 0 );
		}
		if ( '' === get_post_meta( $article_id, '_hf_helpful_no', true ) ) {
			update_post_meta( $article_id, '_hf_helpful_no', 0 );
		}

		// Set categories.
		wp_set_object_terms( $article_id, $categories, 'hf_kb_category' );

		wp_send_json_success(
			array(
				'message'    => __( 'Article saved successfully.', 'hostforge' ),
				'article_id' => $article_id,
				'redirect'   => admin_url( 'admin.php?page=hostforge-knowledge-base&action=edit&article_id=' . $article_id ),
			)
		);
	}

	/**
	 * AJAX: Delete a knowledge base article.
	 *
	 * @return void
	 */
	public function ajax_delete_kb_article(): void {
		check_ajax_referer( 'hf_ticket_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$article_id = isset( $_POST['article_id'] ) ? absint( $_POST['article_id'] ) : 0;

		if ( $article_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid article ID.', 'hostforge' ) ) );
		}

		$article = get_post( $article_id );
		if ( ! $article || 'hf_kb_article' !== $article->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Article not found.', 'hostforge' ) ) );
		}

		wp_delete_post( $article_id, true );

		wp_send_json_success(
			array(
				'message'  => __( 'Article deleted.', 'hostforge' ),
				'redirect' => admin_url( 'admin.php?page=hostforge-knowledge-base' ),
			)
		);
	}

	/**
	 * AJAX: Save (create or update) a canned response.
	 *
	 * @return void
	 */
	public function ajax_save_canned_response(): void {
		check_ajax_referer( 'hf_ticket_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$response_id = isset( $_POST['response_id'] ) ? absint( $_POST['response_id'] ) : 0;
		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$content     = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Response title is required.', 'hostforge' ) ) );
		}

		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Response content is required.', 'hostforge' ) ) );
		}

		$post_data = array(
			'post_type'    => 'hf_canned_response',
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
		);

		if ( $response_id > 0 ) {
			$post_data['ID'] = $response_id;
			$result = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'     => __( 'Canned response saved successfully.', 'hostforge' ),
				'response_id' => (int) $result,
			)
		);
	}

	/**
	 * AJAX: Delete a canned response.
	 *
	 * @return void
	 */
	public function ajax_delete_canned_response(): void {
		check_ajax_referer( 'hf_ticket_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$response_id = isset( $_POST['response_id'] ) ? absint( $_POST['response_id'] ) : 0;

		if ( $response_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid response ID.', 'hostforge' ) ) );
		}

		$response = get_post( $response_id );
		if ( ! $response || 'hf_canned_response' !== $response->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Canned response not found.', 'hostforge' ) ) );
		}

		wp_delete_post( $response_id, true );

		wp_send_json_success(
			array(
				'message' => __( 'Canned response deleted.', 'hostforge' ),
			)
		);
	}

	/**
	 * AJAX: Vote on a knowledge base article (helpful yes/no).
	 *
	 * Public endpoint - no capability check required.
	 * Uses separate nonce: hf_kb_vote_nonce.
	 *
	 * @return void
	 */
	public function ajax_kb_vote(): void {
		check_ajax_referer( 'hf_kb_vote_nonce', 'nonce' );

		$article_id = isset( $_POST['article_id'] ) ? absint( $_POST['article_id'] ) : 0;
		$vote       = isset( $_POST['vote'] ) ? sanitize_text_field( wp_unslash( $_POST['vote'] ) ) : '';

		if ( $article_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid article ID.', 'hostforge' ) ) );
		}

		$article = get_post( $article_id );
		if ( ! $article || 'hf_kb_article' !== $article->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Article not found.', 'hostforge' ) ) );
		}

		if ( ! in_array( $vote, array( 'yes', 'no' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid vote.', 'hostforge' ) ) );
		}

		$meta_key = 'yes' === $vote ? '_hf_helpful_yes' : '_hf_helpful_no';
		$current  = absint( get_post_meta( $article_id, $meta_key, true ) );
		update_post_meta( $article_id, $meta_key, $current + 1 );

		$yes_count = absint( get_post_meta( $article_id, '_hf_helpful_yes', true ) );
		$no_count  = absint( get_post_meta( $article_id, '_hf_helpful_no', true ) );

		// Re-read the updated value.
		if ( 'yes' === $vote ) {
			$yes_count = $current + 1;
		} else {
			$no_count = $current + 1;
		}

		wp_send_json_success(
			array(
				'message'   => __( 'Thank you for your feedback!', 'hostforge' ),
				'yes_count' => $yes_count,
				'no_count'  => $no_count,
			)
		);
	}

	/**
	 * AJAX: Search knowledge base articles.
	 *
	 * Public endpoint - no capability check required.
	 * Uses separate nonce: hf_kb_search_nonce.
	 *
	 * @return void
	 */
	public function ajax_kb_search(): void {
		check_ajax_referer( 'hf_kb_search_nonce', 'nonce' );

		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';

		if ( empty( $keyword ) ) {
			wp_send_json_success( array( 'results' => array() ) );
		}

		$articles = get_posts(
			array(
				'post_type'      => 'hf_kb_article',
				'post_status'    => 'publish',
				'posts_per_page' => 10,
				's'              => $keyword,
				'orderby'        => 'relevance',
				'order'          => 'DESC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'   => '_hf_visibility',
						'value' => 'public',
					),
					array(
						'key'     => '_hf_visibility',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$results = array();
		foreach ( $articles as $article ) {
			$results[] = array(
				'id'      => $article->ID,
				'title'   => $article->post_title,
				'excerpt' => wp_trim_words( $article->post_content, 30, '...' ),
				'url'     => get_permalink( $article->ID ),
			);
		}

		wp_send_json_success(
			array(
				'results' => $results,
			)
		);
	}
}
