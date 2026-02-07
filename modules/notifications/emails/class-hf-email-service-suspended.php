<?php
/**
 * Service Suspended Email.
 *
 * Sent to customer when a hosting service is suspended.
 *
 * @package HostForge\Modules\Notifications\Emails
 */

namespace HostForge\Modules\Notifications\Emails;

use HostForge\Modules\Notifications\HF_Merge_Tags;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Email_Service_Suspended
 */
class HF_Email_Service_Suspended extends \WC_Email {

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
		$this->id             = 'hf_service_suspended';
		$this->title          = __( 'Service Suspended', 'hostforge' );
		$this->description    = __( 'Sent when a hosting service is suspended.', 'hostforge' );
		$this->heading        = __( 'Your Hosting Service Has Been Suspended', 'hostforge' );
		$this->subject        = __( '[{site_title}] Service suspended: {service_domain}', 'hostforge' );
		$this->template_base  = HOSTFORGE_PLUGIN_DIR . 'templates/';
		$this->template_html  = 'emails/service-suspended.php';
		$this->template_plain = 'emails/plain/service-suspended.php';
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

		$this->recipient = $user->user_email;

		/**
		 * Filter the recipient for the service suspended email.
		 *
		 * @since 1.0.0
		 *
		 * @param string $recipient  Email recipient address.
		 * @param int    $service_id Service post ID.
		 */
		$this->recipient = apply_filters( 'hostforge_email_service_suspended_recipient', $this->recipient, $service_id );

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

		$email_data = array(
			'email_heading' => $this->get_heading(),
			'email'         => $this,
			'customer_name' => $user ? $user->display_name : '',
			'domain'        => ! empty( $tags['{service_domain}'] ) ? $tags['{service_domain}'] : '',
			'panel_type'    => ! empty( $tags['{panel_type}'] ) ? $tags['{panel_type}'] : '',
		);

		/**
		 * Filter email template data before rendering the service suspended email.
		 *
		 * @since 1.0.0
		 *
		 * @param array $email_data Template data key-value pairs.
		 * @param int   $service_id Service post ID.
		 */
		$email_data = apply_filters( 'hostforge_email_service_suspended_data', $email_data, $this->service_id );

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
