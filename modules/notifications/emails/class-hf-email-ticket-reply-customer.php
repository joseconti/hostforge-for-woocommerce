<?php
/**
 * Ticket Reply to Customer Email.
 *
 * Sent to customer when staff replies to their ticket.
 *
 * @package HostForge\Modules\Notifications\Emails
 */

namespace HostForge\Modules\Notifications\Emails;

use HostForge\Modules\Notifications\HF_Merge_Tags;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Email_Ticket_Reply_Customer
 */
class HF_Email_Ticket_Reply_Customer extends \WC_Email {

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
		$this->id             = 'hf_ticket_reply_customer';
		$this->title          = __( 'Ticket Reply (to Customer)', 'hostforge' );
		$this->description    = __( 'Sent to customer when staff replies to their support ticket.', 'hostforge' );
		$this->heading        = __( 'Reply to Your Ticket #{ticket_id}', 'hostforge' );
		$this->subject        = __( '[{site_title}] Reply to: {ticket_subject}', 'hostforge' );
		$this->template_base  = HOSTFORGE_PLUGIN_DIR . 'templates/';
		$this->template_html  = 'emails/ticket-reply.php';
		$this->template_plain = 'emails/plain/ticket-reply.php';
		$this->customer_email = true;

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

		$user_id = (int) get_post_meta( $ticket_id, '_hf_client_user_id', true );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$this->recipient = $user->user_email;

		/**
		 * Filter the recipient for the ticket reply customer email.
		 *
		 * @since 1.0.0
		 *
		 * @param string $recipient Email recipient address.
		 * @param int    $ticket_id Ticket post ID.
		 */
		$this->recipient = apply_filters( 'hostforge_email_ticket_reply_customer_recipient', $this->recipient, $ticket_id );

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
		$tags    = HF_Merge_Tags::get_ticket_tags( $this->ticket_id );
		$reply   = get_comment( $this->reply_id );
		$user_id = (int) get_post_meta( $this->ticket_id, '_hf_client_user_id', true );
		$user    = get_userdata( $user_id );

		$email_data = array(
			'email_heading'  => HF_Merge_Tags::process( $this->get_heading(), $tags ),
			'email'          => $this,
			'ticket_id'      => $this->ticket_id,
			'customer_name'  => $user ? $user->display_name : '',
			'reply_content'  => $reply ? $reply->comment_content : '',
			'reply_author'   => $reply ? $reply->comment_author : '',
			'ticket_url'     => ! empty( $tags['{ticket_url}'] ) ? $tags['{ticket_url}'] : '',
			'ticket_subject' => ! empty( $tags['{ticket_subject}'] ) ? $tags['{ticket_subject}'] : '',
		);

		/**
		 * Filter email template data before rendering the ticket reply customer email.
		 *
		 * @since 1.0.0
		 *
		 * @param array $email_data Template data key-value pairs.
		 * @param int   $ticket_id  Ticket post ID.
		 */
		$email_data = apply_filters( 'hostforge_email_ticket_reply_customer_data', $email_data, $this->ticket_id );

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
}
