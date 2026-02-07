<?php
/**
 * Service Terminated Email.
 *
 * Sent to customer when a hosting service is terminated.
 *
 * @package HostForge\Modules\Notifications\Emails
 */

namespace HostForge\Modules\Notifications\Emails;

use HostForge\Modules\Notifications\HF_Merge_Tags;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Email_Service_Terminated
 */
class HF_Email_Service_Terminated extends \WC_Email {

	/**
	 * Service post ID.
	 *
	 * @var int
	 */
	public int $service_id = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'hf_service_terminated';
		$this->title          = __( 'Service Terminated', 'hostforge' );
		$this->description    = __( 'Sent when a hosting service is terminated and data removed.', 'hostforge' );
		$this->heading        = __( 'Your Hosting Service Has Been Terminated', 'hostforge' );
		$this->subject        = __( '[{site_title}] Service terminated: {service_domain}', 'hostforge' );
		$this->template_base  = HOSTFORGE_PLUGIN_DIR . 'templates/';
		$this->template_html  = 'emails/service-terminated.php';
		$this->template_plain = 'emails/plain/service-terminated.php';
		$this->customer_email = true;

		parent::__construct();
	}

	/**
	 * Trigger the email.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function trigger( int $service_id ): void {
		$this->service_id = $service_id;

		$user_id = (int) get_post_meta( $service_id, '_hf_user_id', true );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$this->recipient    = $user->user_email;
		$tags               = HF_Merge_Tags::get_service_tags( $service_id );
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
		$tags    = HF_Merge_Tags::get_service_tags( $this->service_id );
		$user_id = (int) get_post_meta( $this->service_id, '_hf_user_id', true );
		$user    = get_userdata( $user_id );

		return wc_get_template_html(
			$this->template_html,
			array(
				'email_heading' => $this->get_heading(),
				'email'         => $this,
				'customer_name' => $user ? $user->display_name : '',
				'domain'        => ! empty( $tags['{service_domain}'] ) ? $tags['{service_domain}'] : '',
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
