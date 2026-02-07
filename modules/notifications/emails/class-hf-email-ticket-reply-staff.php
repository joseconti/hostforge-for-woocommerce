<?php
/**
 * Ticket Reply to Staff Email.
 *
 * Sent to staff when a customer replies to a ticket.
 *
 * @package HostForge\Modules\Notifications\Emails
 */

namespace HostForge\Modules\Notifications\Emails;

use HostForge\Modules\Notifications\HF_Merge_Tags;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Email_Ticket_Reply_Staff
 */
class HF_Email_Ticket_Reply_Staff extends \WC_Email {

	/**
	 * Ticket post ID.
	 *
	 * @var int
	 */
	public int $ticket_id = 0;

	/**
	 * Reply comment ID.
	 *
	 * @var int
	 */
	public int $reply_id = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'hf_ticket_reply_staff';
		$this->title          = __( 'Ticket Reply (to Staff)', 'hostforge' );
		$this->description    = __( 'Sent to admin when a customer replies to a support ticket.', 'hostforge' );
		$this->heading        = __( 'Customer Reply on Ticket #{ticket_id}', 'hostforge' );
		$this->subject        = __( '[{site_title}] Customer reply: {ticket_subject}', 'hostforge' );
		$this->template_base  = HOSTFORGE_PLUGIN_DIR . 'templates/';
		$this->template_html  = 'emails/ticket-reply.php';
		$this->template_plain = 'emails/plain/ticket-reply.php';
		$this->recipient      = $this->get_option( 'recipient', get_option( 'admin_email' ) );

		parent::__construct();
	}

	/**
	 * Trigger the email.
	 *
	 * @param int $ticket_id Ticket post ID.
	 * @param int $reply_id  Reply comment ID.
	 * @return void
	 */
	public function trigger( int $ticket_id, int $reply_id = 0 ): void {
		$this->ticket_id = $ticket_id;
		$this->reply_id  = $reply_id;

		// Also send to assigned staff if different from admin.
		$assigned_to = (int) get_post_meta( $ticket_id, '_hf_assigned_to', true );
		$recipients  = array( $this->get_recipient() );

		if ( $assigned_to ) {
			$assigned_user = get_userdata( $assigned_to );
			if ( $assigned_user && ! in_array( $assigned_user->user_email, $recipients, true ) ) {
				$recipients[] = $assigned_user->user_email;
			}
		}

		$tags               = HF_Merge_Tags::get_ticket_tags( $ticket_id );
		$this->placeholders = array_merge( $this->placeholders, $tags );

		/**
		 * Filter the recipient for the ticket reply staff email.
		 *
		 * @since 1.0.0
		 *
		 * @param string $recipient Email recipient address (comma-separated).
		 * @param int    $ticket_id Ticket post ID.
		 */
		$recipient_string = apply_filters( 'hostforge_email_ticket_reply_staff_recipient', implode( ',', $recipients ), $ticket_id );

		if ( $this->is_enabled() ) {
			$this->send(
				$recipient_string,
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
		$tags  = HF_Merge_Tags::get_ticket_tags( $this->ticket_id );
		$reply = get_comment( $this->reply_id );

		$email_data = array(
			'email_heading'  => HF_Merge_Tags::process( $this->get_heading(), $tags ),
			'email'          => $this,
			'ticket_id'      => $this->ticket_id,
			'customer_name'  => ! empty( $tags['{customer_name}'] ) ? $tags['{customer_name}'] : '',
			'reply_content'  => $reply ? $reply->comment_content : '',
			'reply_author'   => $reply ? $reply->comment_author : '',
			'ticket_url'     => admin_url( 'admin.php?page=hostforge-tickets&action=view&id=' . $this->ticket_id ),
			'ticket_subject' => ! empty( $tags['{ticket_subject}'] ) ? $tags['{ticket_subject}'] : '',
		);

		/**
		 * Filter email template data before rendering the ticket reply staff email.
		 *
		 * @since 1.0.0
		 *
		 * @param array $email_data Template data key-value pairs.
		 * @param int   $ticket_id  Ticket post ID.
		 */
		$email_data = apply_filters( 'hostforge_email_ticket_reply_staff_data', $email_data, $this->ticket_id );

		return wc_get_template_html(
			$this->template_html,
			$email_data,
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
