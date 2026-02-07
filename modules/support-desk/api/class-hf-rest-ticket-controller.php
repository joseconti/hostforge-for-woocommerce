<?php
/**
 * REST Ticket Controller.
 *
 * Provides REST API endpoints for support tickets and knowledge base.
 * Namespace: hostforge/v1/tickets and hostforge/v1/kb
 *
 * @package HostForge\Modules\SupportDesk\Api
 */

namespace HostForge\Modules\SupportDesk\Api;

use HostForge\Abstracts\HF_REST_Controller;
use HostForge\Modules\SupportDesk\HF_Support_Desk_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_REST_Ticket_Controller
 */
class HF_REST_Ticket_Controller extends HF_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'tickets';

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /tickets - List tickets.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'status'     => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'priority'   => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'department' => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'page'       => array(
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page'   => array(
							'type'              => 'integer',
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
						'search'     => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		// GET /tickets/{id} - Get single ticket.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// POST /tickets - Create ticket.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'subject'        => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'message'        => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'wp_kses_post',
						),
						'priority'       => array(
							'type'              => 'string',
							'default'           => 'medium',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'department_id'  => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'client_user_id' => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// POST /tickets/{id}/reply - Add reply.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)/reply',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_reply' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'id'         => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'content'    => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'wp_kses_post',
						),
						'is_private' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'is_staff'   => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
				),
			)
		);

		// PUT /tickets/{id}/status - Update status.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)/status',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_status' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'id'     => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'status' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		// GET /kb - List KB articles.
		register_rest_route(
			$this->namespace,
			'/kb',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_kb_items' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'category' => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'search'   => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'page'     => array(
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page' => array(
							'type'              => 'integer',
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// GET /kb/{id} - Get single KB article.
		register_rest_route(
			$this->namespace,
			'/kb/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_kb_item' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// POST /kb/{id}/vote - Vote on KB article.
		register_rest_route(
			$this->namespace,
			'/kb/(?P<id>\d+)/vote',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'vote_kb_article' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'id'   => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'vote' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Get tickets list.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ): \WP_REST_Response|\WP_Error {
		$args = array(
			'post_type'      => 'hf_ticket',
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ),
			'paged'          => $request->get_param( 'page' ),
		);

		$meta_query = array();

		$status = $request->get_param( 'status' );
		if ( ! empty( $status ) ) {
			$valid_statuses = array_keys( HF_Support_Desk_Module::get_statuses() );
			if ( ! in_array( $status, $valid_statuses, true ) ) {
				return $this->error( 'invalid_status', __( 'Invalid ticket status.', 'hostforge' ) );
			}
			$meta_query[] = array(
				'key'   => '_hf_status',
				'value' => $status,
			);
		}

		$priority = $request->get_param( 'priority' );
		if ( ! empty( $priority ) ) {
			$valid_priorities = array_keys( HF_Support_Desk_Module::get_priorities() );
			if ( ! in_array( $priority, $valid_priorities, true ) ) {
				return $this->error( 'invalid_priority', __( 'Invalid ticket priority.', 'hostforge' ) );
			}
			$meta_query[] = array(
				'key'   => '_hf_priority',
				'value' => $priority,
			);
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$args['meta_query']     = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$department = $request->get_param( 'department' );
		if ( ! empty( $department ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'hf_department',
					'field'    => 'term_id',
					'terms'    => $department,
				),
			);
		}

		$search = $request->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query   = new \WP_Query( $args );
		$tickets = array();

		foreach ( $query->posts as $post ) {
			$tickets[] = $this->prepare_ticket( $post );
		}

		/**
		 * Filters the REST response data for a list of tickets.
		 *
		 * @since 1.0.0
		 *
		 * @param array            $tickets Array of prepared ticket data.
		 * @param \WP_REST_Request $request The REST request object.
		 */
		$tickets = apply_filters( 'hostforge_rest_ticket_response', $tickets, $request );

		$response = new \WP_REST_Response( $tickets, 200 );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}

	/**
	 * Get a single ticket with replies.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ): \WP_REST_Response|\WP_Error {
		$ticket = get_post( $request->get_param( 'id' ) );

		if ( ! $ticket || 'hf_ticket' !== $ticket->post_type ) {
			return $this->error( 'not_found', __( 'Ticket not found.', 'hostforge' ), 404 );
		}

		$data = $this->prepare_ticket( $ticket );

		// Include full content.
		$data['content'] = wp_kses_post( $ticket->post_content );

		// Include replies.
		$replies         = HF_Support_Desk_Module::get_replies( $ticket->ID, true );
		$data['replies'] = array_map( array( $this, 'prepare_reply' ), $replies );

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Create a new ticket.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ): \WP_REST_Response|\WP_Error {
		$subject        = $request->get_param( 'subject' );
		$message        = $request->get_param( 'message' );
		$priority_param = $request->get_param( 'priority' );
		$priority       = ! empty( $priority_param ) ? $priority_param : 'medium';
		$department_id  = $request->get_param( 'department_id' );
		$client_user_id = $request->get_param( 'client_user_id' );

		// Validate priority.
		$valid_priorities = array_keys( HF_Support_Desk_Module::get_priorities() );
		if ( ! in_array( $priority, $valid_priorities, true ) ) {
			return $this->error( 'invalid_priority', __( 'Invalid ticket priority.', 'hostforge' ) );
		}

		// Validate client user if provided.
		if ( $client_user_id ) {
			$client = get_user_by( 'id', $client_user_id );
			if ( ! $client ) {
				return $this->error( 'invalid_user', __( 'Client user not found.', 'hostforge' ), 404 );
			}
		} else {
			$client_user_id = get_current_user_id();
		}

		// Validate department if provided.
		if ( $department_id ) {
			$term = get_term( $department_id, 'hf_department' );
			if ( ! $term || is_wp_error( $term ) ) {
				return $this->error( 'invalid_department', __( 'Department not found.', 'hostforge' ), 404 );
			}
		}

		$create_data = array(
			'subject'        => $subject,
			'message'        => $message,
			'priority'       => $priority,
			'department_id'  => $department_id,
			'client_user_id' => $client_user_id,
		);

		/**
		 * Filters the data used to create a ticket via the REST API.
		 *
		 * @since 1.0.0
		 *
		 * @param array            $create_data Ticket creation data.
		 * @param \WP_REST_Request $request     The REST request object.
		 */
		$create_data = apply_filters( 'hostforge_rest_ticket_create_data', $create_data, $request );

		$subject        = $create_data['subject'];
		$message        = $create_data['message'];
		$priority       = $create_data['priority'];
		$department_id  = $create_data['department_id'];
		$client_user_id = $create_data['client_user_id'];

		$ticket_id = wp_insert_post(
			array(
				'post_type'    => 'hf_ticket',
				'post_title'   => $subject,
				'post_content' => $message,
				'post_status'  => 'publish',
				'post_author'  => $client_user_id,
			)
		);

		if ( is_wp_error( $ticket_id ) ) {
			return $this->error(
				'create_failed',
				__( 'Failed to create ticket.', 'hostforge' ),
				500
			);
		}

		// Set ticket meta.
		update_post_meta( $ticket_id, '_hf_status', 'open' );
		update_post_meta( $ticket_id, '_hf_priority', $priority );
		update_post_meta( $ticket_id, '_hf_client_user_id', $client_user_id );
		update_post_meta( $ticket_id, '_hf_last_reply_at', current_time( 'mysql' ) );
		update_post_meta( $ticket_id, '_hf_last_reply_by', $client_user_id );

		// Assign department.
		if ( $department_id ) {
			wp_set_object_terms( $ticket_id, array( $department_id ), 'hf_department' );
			update_post_meta( $ticket_id, '_hf_department', $department_id );
		}

		/**
		 * Fires when a new ticket is created.
		 *
		 * @param int $ticket_id The ticket post ID.
		 * @param int $user_id   The user who created the ticket.
		 */
		do_action( 'hostforge_ticket_created', $ticket_id, $client_user_id );

		$ticket = get_post( $ticket_id );

		return new \WP_REST_Response( $this->prepare_ticket( $ticket ), 201 );
	}

	/**
	 * Add a reply to a ticket.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function add_reply( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$ticket_id  = $request->get_param( 'id' );
		$content    = $request->get_param( 'content' );
		$is_private = (bool) $request->get_param( 'is_private' );
		$is_staff   = (bool) $request->get_param( 'is_staff' );

		$ticket = get_post( $ticket_id );
		if ( ! $ticket || 'hf_ticket' !== $ticket->post_type ) {
			return $this->error( 'not_found', __( 'Ticket not found.', 'hostforge' ), 404 );
		}

		$module = \HostForge\HostForge::instance()->module_manager()->get_module( 'support-desk' );

		if ( ! $module ) {
			return $this->error(
				'module_unavailable',
				__( 'Support Desk module is not available.', 'hostforge' ),
				500
			);
		}

		$user_id    = get_current_user_id();
		$comment_id = $module->add_reply( $ticket_id, $user_id, $content, $is_private, $is_staff );

		if ( ! $comment_id ) {
			return $this->error(
				'reply_failed',
				__( 'Failed to add reply.', 'hostforge' ),
				500
			);
		}

		$comment = get_comment( $comment_id );

		return new \WP_REST_Response( $this->prepare_reply( $comment ), 201 );
	}

	/**
	 * Update ticket status.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_status( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$ticket_id  = $request->get_param( 'id' );
		$new_status = $request->get_param( 'status' );

		$ticket = get_post( $ticket_id );
		if ( ! $ticket || 'hf_ticket' !== $ticket->post_type ) {
			return $this->error( 'not_found', __( 'Ticket not found.', 'hostforge' ), 404 );
		}

		// Validate status.
		$valid_statuses = array_keys( HF_Support_Desk_Module::get_statuses() );
		if ( ! in_array( $new_status, $valid_statuses, true ) ) {
			return $this->error( 'invalid_status', __( 'Invalid ticket status.', 'hostforge' ) );
		}

		update_post_meta( $ticket_id, '_hf_status', $new_status );

		/**
		 * Fires when a ticket status is updated via API.
		 *
		 * @param int    $ticket_id  The ticket post ID.
		 * @param string $new_status The new status.
		 */
		do_action( 'hostforge_ticket_status_updated', $ticket_id, $new_status );

		// Return the updated ticket.
		$ticket = get_post( $ticket_id );

		return new \WP_REST_Response( $this->prepare_ticket( $ticket ), 200 );
	}

	/**
	 * Get KB articles list.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_kb_items( \WP_REST_Request $request ): \WP_REST_Response {
		$args = array(
			'post_type'      => 'hf_kb_article',
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ),
			'paged'          => $request->get_param( 'page' ),
		);

		$category = $request->get_param( 'category' );
		if ( ! empty( $category ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'hf_kb_category',
					'field'    => 'term_id',
					'terms'    => $category,
				),
			);
		}

		$search = $request->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Only return articles with public visibility.
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'OR',
			array(
				'key'     => '_hf_visibility',
				'value'   => 'public',
				'compare' => '=',
			),
			array(
				'key'     => '_hf_visibility',
				'compare' => 'NOT EXISTS',
			),
		);

		$query    = new \WP_Query( $args );
		$articles = array();

		foreach ( $query->posts as $post ) {
			$articles[] = $this->prepare_kb_article( $post );
		}

		/**
		 * Filters the REST response data for KB articles.
		 *
		 * @since 1.0.0
		 *
		 * @param array            $articles Array of prepared KB article data.
		 * @param \WP_REST_Request $request  The REST request object.
		 */
		$articles = apply_filters( 'hostforge_rest_kb_response', $articles, $request );

		$response = new \WP_REST_Response( $articles, 200 );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}

	/**
	 * Get a single KB article.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_kb_item( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$article = get_post( $request->get_param( 'id' ) );

		if ( ! $article || 'hf_kb_article' !== $article->post_type ) {
			return $this->error( 'not_found', __( 'Article not found.', 'hostforge' ), 404 );
		}

		if ( 'publish' !== $article->post_status ) {
			return $this->error( 'not_found', __( 'Article not found.', 'hostforge' ), 404 );
		}

		$data = $this->prepare_kb_article( $article );

		// Include full content for single article view.
		$data['content'] = wp_kses_post( $article->post_content );

		// Include category details.
		$categories         = wp_get_object_terms( $article->ID, 'hf_kb_category' );
		$data['categories'] = array();
		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $term ) {
				$data['categories'][] = array(
					'id'   => $term->term_id,
					'name' => esc_html( $term->name ),
					'slug' => esc_html( $term->slug ),
				);
			}
		}

		// Include vote counts.
		$data['helpful_yes'] = absint( get_post_meta( $article->ID, '_hf_helpful_yes', true ) );
		$data['helpful_no']  = absint( get_post_meta( $article->ID, '_hf_helpful_no', true ) );

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Vote on a KB article.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function vote_kb_article( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$article_id = $request->get_param( 'id' );
		$vote       = $request->get_param( 'vote' );

		$article = get_post( $article_id );

		if ( ! $article || 'hf_kb_article' !== $article->post_type ) {
			return $this->error( 'not_found', __( 'Article not found.', 'hostforge' ), 404 );
		}

		if ( 'publish' !== $article->post_status ) {
			return $this->error( 'not_found', __( 'Article not found.', 'hostforge' ), 404 );
		}

		if ( ! in_array( $vote, array( 'yes', 'no' ), true ) ) {
			return $this->error( 'invalid_vote', __( 'Vote must be "yes" or "no".', 'hostforge' ) );
		}

		// Rate limit voting by IP.
		$ip         = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$identifier = 'kb_vote_' . $article_id . '_' . $ip;
		if ( ! $this->check_rate_limit( $identifier, 5, 3600 ) ) {
			return $this->error(
				'rate_limited',
				__( 'Too many vote requests. Please try again later.', 'hostforge' ),
				429
			);
		}

		$meta_key = 'yes' === $vote ? '_hf_helpful_yes' : '_hf_helpful_no';
		$current  = absint( get_post_meta( $article_id, $meta_key, true ) );
		update_post_meta( $article_id, $meta_key, $current + 1 );

		return new \WP_REST_Response(
			array(
				'helpful_yes' => absint( get_post_meta( $article_id, '_hf_helpful_yes', true ) ),
				'helpful_no'  => absint( get_post_meta( $article_id, '_hf_helpful_no', true ) ),
			),
			200
		);
	}

	/**
	 * Prepare a ticket for API response.
	 *
	 * @param \WP_Post $post Ticket post.
	 * @return array<string, mixed>
	 */
	private function prepare_ticket( \WP_Post $post ): array {
		$client_id   = absint( get_post_meta( $post->ID, '_hf_client_user_id', true ) );
		$assigned_id = absint( get_post_meta( $post->ID, '_hf_assigned_to', true ) );
		$client      = $client_id ? get_user_by( 'id', $client_id ) : null;
		$assigned    = $assigned_id ? get_user_by( 'id', $assigned_id ) : null;

		$departments = wp_get_object_terms( $post->ID, 'hf_department', array( 'fields' => 'all' ) );
		$department  = ( ! is_wp_error( $departments ) && ! empty( $departments ) ) ? $departments[0] : null;

		$ticket_status   = get_post_meta( $post->ID, '_hf_status', true );
		$ticket_priority = get_post_meta( $post->ID, '_hf_priority', true );
		$last_reply_at   = get_post_meta( $post->ID, '_hf_last_reply_at', true );

		return array(
			'id'         => $post->ID,
			'subject'    => esc_html( $post->post_title ),
			'status'     => esc_html( ! empty( $ticket_status ) ? $ticket_status : 'open' ),
			'priority'   => esc_html( ! empty( $ticket_priority ) ? $ticket_priority : 'medium' ),
			'department' => $department ? array(
				'id'   => $department->term_id,
				'name' => esc_html( $department->name ),
			) : null,
			'customer'   => $client ? array(
				'id'    => $client->ID,
				'name'  => esc_html( $client->display_name ),
				'email' => esc_html( $client->user_email ),
			) : null,
			'assigned'   => $assigned ? array(
				'id'   => $assigned->ID,
				'name' => esc_html( $assigned->display_name ),
			) : null,
			'last_reply' => esc_html( ! empty( $last_reply_at ) ? $last_reply_at : '' ),
			'created'    => esc_html( $post->post_date ),
		);
	}

	/**
	 * Prepare a reply (comment) for API response.
	 *
	 * @param \WP_Comment $comment Reply comment.
	 * @return array<string, mixed>
	 */
	private function prepare_reply( \WP_Comment $comment ): array {
		$attachments     = get_comment_meta( $comment->comment_ID, '_hf_attachments', true );
		$attachment_data = array();

		if ( ! empty( $attachments ) && is_array( $attachments ) ) {
			foreach ( $attachments as $attachment_id ) {
				$attachment_id = absint( $attachment_id );
				$url           = wp_get_attachment_url( $attachment_id );
				$attached_file = get_attached_file( $attachment_id );
				if ( $url ) {
					$attachment_data[] = array(
						'id'       => $attachment_id,
						'url'      => esc_url( $url ),
						'filename' => esc_html( basename( ! empty( $attached_file ) ? $attached_file : '' ) ),
					);
				}
			}
		}

		return array(
			'id'          => $comment->comment_ID,
			'author'      => array(
				'id'   => absint( $comment->user_id ),
				'name' => esc_html( $comment->comment_author ),
			),
			'content'     => wp_kses_post( $comment->comment_content ),
			'is_private'  => (bool) get_comment_meta( $comment->comment_ID, '_hf_is_private_note', true ),
			'is_staff'    => (bool) get_comment_meta( $comment->comment_ID, '_hf_is_staff_reply', true ),
			'attachments' => $attachment_data,
			'created'     => esc_html( $comment->comment_date ),
		);
	}

	/**
	 * Prepare a KB article for API response.
	 *
	 * @param \WP_Post $post KB article post.
	 * @return array<string, mixed>
	 */
	private function prepare_kb_article( \WP_Post $post ): array {
		$categories    = wp_get_object_terms( $post->ID, 'hf_kb_category', array( 'fields' => 'all' ) );
		$category_data = array();

		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $term ) {
				$category_data[] = array(
					'id'   => $term->term_id,
					'name' => esc_html( $term->name ),
					'slug' => esc_html( $term->slug ),
				);
			}
		}

		return array(
			'id'          => $post->ID,
			'title'       => esc_html( $post->post_title ),
			'excerpt'     => esc_html( wp_trim_words( $post->post_content, 30 ) ),
			'categories'  => $category_data,
			'helpful_yes' => absint( get_post_meta( $post->ID, '_hf_helpful_yes', true ) ),
			'helpful_no'  => absint( get_post_meta( $post->ID, '_hf_helpful_no', true ) ),
			'created'     => esc_html( $post->post_date ),
			'modified'    => esc_html( $post->post_modified ),
		);
	}
}
