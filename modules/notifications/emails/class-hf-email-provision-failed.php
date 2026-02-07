<?php
/**
 * Provision Failed Email.
 *
 * Sent to admin when a hosting service provisioning fails.
 *
 * @package HostForge\Modules\Notifications\Emails
 */

namespace HostForge\Modules\Notifications\Emails;

use HostForge\Modules\Notifications\HF_Merge_Tags;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Email_Provision_Failed
 */
class HF_Email_Provision_Failed extends \WC_Email {

	/**
	 * Service post ID.
	 *
	 * @var int
	 */
	public int $service_id = 0;

	/**
	 * Error message.
	 *
	 * @var string
	 */
	public string $error_message = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'hf_provision_failed';
		$this->title          = __( 'Provisioning Failed (Admin)', 'hostforge' );
		$this->description    = __( 'Sent to admin when hosting service provisioning fails.', 'hostforge' );
		$this->heading        = __( 'Provisioning Failed for Service #{service_id}', 'hostforge' );
		$this->subject        = __( '[{site_title}] Provisioning failed: {service_domain}', 'hostforge' );
		$this->template_base  = HOSTFORGE_PLUGIN_DIR . 'templates/';
		$this->template_html  = 'emails/provision-failed.php';
		$this->template_plain = 'emails/plain/provision-failed.php';
		$this->recipient      = $this->get_option( 'recipient', get_option( 'admin_email' ) );

		parent::__construct();
	}

	/**
	 * Trigger the email.
	 *
	 * @param int    $service_id Service post ID.
	 * @param string $error      Error message.
	 * @return void
	 */
	public function trigger( int $service_id, string $error = '' ): void {
		$this->service_id    = $service_id;
		$this->error_message = $error;

		$tags               = HF_Merge_Tags::get_service_tags( $service_id );
		$this->placeholders = array_merge( $this->placeholders, $tags );

		/**
		 * Filter the recipient for the provision failed email.
		 *
		 * @since 1.0.0
		 *
		 * @param string $recipient  Email recipient address.
		 * @param int    $service_id Service post ID.
		 */
		$this->recipient = apply_filters( 'hostforge_email_provision_failed_recipient', $this->get_recipient(), $service_id );

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
		$tags = HF_Merge_Tags::get_service_tags( $this->service_id );

		$email_data = array(
			'email_heading' => HF_Merge_Tags::process( $this->get_heading(), $tags ),
			'email'         => $this,
			'service_id'    => $this->service_id,
			'domain'        => ! empty( $tags['{service_domain}'] ) ? $tags['{service_domain}'] : '',
			'error_message' => $this->error_message,
			'server_name'   => ! empty( $tags['{server_name}'] ) ? $tags['{server_name}'] : '',
			'panel_type'    => ! empty( $tags['{panel_type}'] ) ? $tags['{panel_type}'] : '',
			'admin_url'     => admin_url( 'admin.php?page=hostforge-services&action=view&id=' . $this->service_id ),
		);

		/**
		 * Filter email template data before rendering the provision failed email.
		 *
		 * @since 1.0.0
		 *
		 * @param array $email_data Template data key-value pairs.
		 * @param int   $service_id Service post ID.
		 */
		$email_data = apply_filters( 'hostforge_email_provision_failed_data', $email_data, $this->service_id );

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
