<?php
/**
 * New Ticket Staff Email.
 *
 * Sent to staff when a new support ticket is created.
 *
 * @package HostForge\Modules\Notifications\Emails
 */

namespace HostForge\Modules\Notifications\Emails;

use HostForge\Modules\Notifications\HF_Merge_Tags;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Email_Ticket_New_Staff
 */
class HF_Email_Ticket_New_Staff extends \WC_Email {

	/**
	 * Ticket post ID.
	 *
	 * @var int
	 */
	public int $ticket_id = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'hf_ticket_new_staff';
		$this->title          = __( 'New Support Ticket (Staff)', 'hostforge' );
		$this->description    = __( 'Sent to admin when a new support ticket is submitted.', 'hostforge' );
		$this->heading        = __( 'New Support Ticket #{ticket_id}', 'hostforge' );
		$this->subject        = __( '[{site_title}] New ticket: {ticket_subject}', 'hostforge' );
		$this->template_base  = HOSTFORGE_PLUGIN_DIR . 'templates/';
		$this->template_html  = 'emails/ticket-new-staff.php';
		$this->template_plain = 'emails/plain/ticket-new-staff.php';
		$this->recipient      = $this->get_option( 'recipient', get_option( 'admin_email' ) );

		parent::__construct();
	}

	/**
	 * Trigger the email.
	 *
	 * @param int $ticket_id Ticket post ID.
	 * @param int $user_id   Customer user ID.
	 * @return void
	 */
	public function trigger( int $ticket_id, int $user_id = 0 ): void {
		$this->ticket_id = $ticket_id;

		$tags               = HF_Merge_Tags::get_ticket_tags( $ticket_id );
		$this->placeholders = array_merge( $this->placeholders, $tags );

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send(
				$this->get_recipient(),
				$this->get_subject(),
				$this->get_content(),
				$this->get_headers(),
				$this->get_attachments()
			);
		}
	}

	/**
	 * Get content HTML.
	 *
	 * @return string
	 */
	public function get_content_html(): string {
		$tags   = HF_Merge_Tags::get_ticket_tags( $this->ticket_id );
		$ticket = get_post( $this->ticket_id );

		return wc_get_template_html(
			$this->template_html,
			array(
				'email_heading'    => HF_Merge_Tags::process( $this->get_heading(), $tags ),
				'email'            => $this,
				'ticket_id'        => $this->ticket_id,
				'ticket_subject'   => $ticket ? $ticket->post_title : '',
				'ticket_message'   => $ticket ? $ticket->post_content : '',
				'customer_name'    => ! empty( $tags['{customer_name}'] ) ? $tags['{customer_name}'] : '',
				'customer_email'   => ! empty( $tags['{customer_email}'] ) ? $tags['{customer_email}'] : '',
				'ticket_priority'  => ! empty( $tags['{ticket_priority}'] ) ? $tags['{ticket_priority}'] : '',
				'ticket_department' => ! empty( $tags['{ticket_department}'] ) ? $tags['{ticket_department}'] : '',
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain(): string {
		return $this->get_content_html();
	}

	/**
	 * Initialise settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields(): void {
		parent::init_form_fields();

		$this->form_fields['recipient'] = array(
			'title'       => __( 'Recipient(s)', 'hostforge' ),
			'type'        => 'text',
			'description' => __( 'Enter recipients (comma separated) for this email.', 'hostforge' ),
			'placeholder' => get_option( 'admin_email' ),
			'default'     => get_option( 'admin_email' ),
		);
	}
}
