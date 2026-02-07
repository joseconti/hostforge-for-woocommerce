<?php
/**
 * Domain Expiry Reminder Email.
 *
 * Sent to customer when their domain is expiring soon.
 *
 * @package HostForge\Modules\Notifications\Emails
 */

namespace HostForge\Modules\Notifications\Emails;

use HostForge\Modules\Notifications\HF_Merge_Tags;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Email_Domain_Expiry
 */
class HF_Email_Domain_Expiry extends \WC_Email {

	/**
	 * Domain post ID.
	 *
	 * @var int
	 */
	public int $domain_id = 0;

	/**
	 * Domain name.
	 *
	 * @var string
	 */
	public string $domain_name = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'hf_domain_expiry';
		$this->title          = __( 'Domain Expiry Reminder', 'hostforge' );
		$this->description    = __( 'Sent when a domain is approaching its expiry date.', 'hostforge' );
		$this->heading        = __( 'Domain {domain_name} Expiring Soon', 'hostforge' );
		$this->subject        = __( '[{site_title}] Domain expiring: {domain_name}', 'hostforge' );
		$this->template_base  = HOSTFORGE_PLUGIN_DIR . 'templates/';
		$this->template_html  = 'emails/domain-expiry-reminder.php';
		$this->template_plain = 'emails/plain/domain-expiry-reminder.php';
		$this->customer_email = true;

		parent::__construct();
	}

	/**
	 * Trigger the email.
	 *
	 * @param int    $domain_id   Domain post ID.
	 * @param string $domain_name Domain name.
	 * @return void
	 */
	public function trigger( int $domain_id, string $domain_name = '' ): void {
		$this->domain_id   = $domain_id;
		$this->domain_name = $domain_name;

		$user_id = (int) get_post_meta( $domain_id, '_hf_user_id', true );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$this->recipient = $user->user_email;

		/**
		 * Filter the recipient for the domain expiry email.
		 *
		 * @since 1.0.0
		 *
		 * @param string $recipient Email recipient address.
		 * @param int    $domain_id Domain post ID.
		 */
		$this->recipient = apply_filters( 'hostforge_email_domain_expiry_recipient', $this->recipient, $domain_id );

		$tags               = HF_Merge_Tags::get_domain_tags( $domain_id );
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
		$tags    = HF_Merge_Tags::get_domain_tags( $this->domain_id );
		$user_id = (int) get_post_meta( $this->domain_id, '_hf_user_id', true );
		$user    = get_userdata( $user_id );

		$email_data = array(
			'email_heading'     => HF_Merge_Tags::process( $this->get_heading(), $tags ),
			'email'             => $this,
			'customer_name'     => $user ? $user->display_name : '',
			'domain_name'       => $this->domain_name,
			'domain_expiry'     => ! empty( $tags['{domain_expiry}'] ) ? $tags['{domain_expiry}'] : '',
			'domain_auto_renew' => ! empty( $tags['{domain_auto_renew}'] ) ? $tags['{domain_auto_renew}'] : '',
			'domain_url'        => ! empty( $tags['{domain_url}'] ) ? $tags['{domain_url}'] : '',
		);

		/**
		 * Filter email template data before rendering the domain expiry email.
		 *
		 * @since 1.0.0
		 *
		 * @param array $email_data Template data key-value pairs.
		 * @param int   $domain_id  Domain post ID.
		 */
		$email_data = apply_filters( 'hostforge_email_domain_expiry_data', $email_data, $this->domain_id );

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
