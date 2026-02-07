<?php
/**
 * Ticket Closed Email.
 *
 * Sent to customer when their support ticket is closed.
 *
 * @package HostForge\Modules\Notifications\Emails
 */

namespace HostForge\Modules\Notifications\Emails;

use HostForge\Modules\Notifications\HF_Merge_Tags;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Email_Ticket_Closed
 */
class HF_Email_Ticket_Closed extends \WC_Email {

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
		$this->id             = 'hf_ticket_closed';
		$this->title          = __( 'Ticket Closed', 'hostforge' );
		$this->description    = __( 'Sent to customer when their support ticket is closed.', 'hostforge' );
		$this->heading        = __( 'Ticket #{ticket_id} Closed', 'hostforge' );
		$this->subject        = __( '[{site_title}] Ticket closed: {ticket_subject}', 'hostforge' );
		$this->template_base  = HOSTFORGE_PLUGIN_DIR . 'templates/';
		$this->template_html  = 'emails/ticket-closed.php';
		$this->template_plain = 'emails/plain/ticket-closed.php';
		$this->customer_email = true;

		parent::__construct();
	}

	/**
	 * Trigger the email.
	 *
	 * @param int $ticket_id Ticket post ID.
	 * @return void
	 */
	public function trigger( int $ticket_id ): void {
		$this->ticket_id = $ticket_id;

		$user_id = (int) get_post_meta( $ticket_id, '_hf_client_user_id', true );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$this->recipient    = $user->user_email;
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
		$user_id = (int) get_post_meta( $this->ticket_id, '_hf_client_user_id', true );
		$user    = get_userdata( $user_id );

		return wc_get_template_html(
			$this->template_html,
			array(
				'email_heading'  => HF_Merge_Tags::process( $this->get_heading(), $tags ),
				'email'          => $this,
				'ticket_id'      => $this->ticket_id,
				'customer_name'  => $user ? $user->display_name : '',
				'ticket_subject' => ! empty( $tags['{ticket_subject}'] ) ? $tags['{ticket_subject}'] : '',
				'ticket_url'     => ! empty( $tags['{ticket_url}'] ) ? $tags['{ticket_url}'] : '',
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
}
