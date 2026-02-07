<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Abstract for all plugin emails
 *
 * @class   YITH_WC_Subscription_Email
 * @package YITH\Subscription
 * @since   4.8.0
 * @author YITH
 */

if ( ! class_exists( 'YITH_WC_Subscription_Email' ) ) {
	/**
	 * YITH_WC_Subscription_Email
	 *
	 * @since 1.0.0
	 */
	abstract class YITH_WC_Subscription_Email extends WC_Email {

		/**
		 * Order subscription this email is for.
		 *
		 * @var object|bool
		 */
		public $order;

		/**
		 * Get the right email args
		 *
		 * @return array
		 */
		protected function get_template_args() {
			return array(
				'subscription'       => $this->get_subscription(),
				'email_heading'      => $this->get_heading(),
				'sent_to_admin'      => ! $this->is_customer_email(),
				'additional_content' => $this->get_additional_content(),
				'plain_text'         => false,
				'email'              => $this,
			);
		}

		/**
		 * Get email main object
		 *
		 * @return bool|object
		 */
		public function get_subscription() {
			return has_filter( 'woocommerce_is_email_preview' ) ? $this->get_dummy_subscription() : $this->object;
		}

		/**
		 * Get email order
		 *
		 * @return bool|object
		 */
		public function get_order() {
			return has_filter( 'woocommerce_is_email_preview' ) ? $this->get_dummy_order() : $this->order;
		}

		/**
		 * Initialise settings form fields
		 *
		 * @access public
		 * @return void
		 */
		public function init_form_fields() {
			parent::init_form_fields();
			unset( $this->form_fields['email_type'] );
		}

		/**
		 * Get HTML content for the mail
		 *
		 * @return string HTML content of the mail
		 * @author Bluehost
		 */
		public function get_content_html() {
			ob_start();
			\wc_get_template(
				$this->template_html,
				$this->get_template_args(),
				'',
				$this->template_base
			);

			return ob_get_clean();
		}

		/**
		 * Get a dummy subscription.
		 *
		 * @return YWSBS_Subscription
		 */
		protected function get_dummy_subscription() {

			$subscription = new YWSBS_Subscription();

			$order = $this->get_dummy_order();
			$item  = current( $order->get_items() );

			$product = $this->get_dummy_product();

			$subscription->id      = 12345;
			$subscription->order   = $order;
			$subscription->product = $product;

			$subscription->populate_prop(
				array(
					'number'                  => 12345,
					'status'                  => 'active',
					'product_id'              => 495959,
					'start_date'              => strtotime( '-1 month' ),
					'payment_due_date'        => strtotime( '+1 month' ),
					'product_name'            => $item->get_name(),
					'quantity'                => $item->get_quantity(),
					'currency'                => $order->get_currency(),
					'line_total'              => $order->get_line_total( $item ),
					'line_tax'                => $order->get_line_tax( $item ),
					'order_item_id'           => $item->get_id(),
					'subscription_total'      => $order->get_total(),
					'subscriptions_shippings' => array(
						'cost' => $order->get_shipping_total(),
						'name' => $order->get_shipping_method(),
					),
					'order_ids'               => array( 4567, 7890 ),
				)
			);

			return $subscription;
		}

		/**
		 * Get a dummy product.
		 *
		 * @return \WC_Product
		 */
		protected function get_dummy_product() {
			$product = new \WC_Product();
			$product->set_id( 62345 );
			$product->set_name( __( 'Dummy Product', 'woocommerce' ) );
			$product->set_price( 25 );

			return $product;
		}

		/**
		 * Get a dummy order object without the need to create in the database.
		 *
		 * @return \WC_Order
		 */
		protected function get_dummy_order() {
			$product = $this->get_dummy_product();

			$order = new \WC_Order();
			$order->add_product( $product, 2 );
			$order->set_id( 42345 );
			$order->set_date_created( time() );
			$order->set_currency( 'USD' );
			$order->set_shipping_total( 5 );
			$order->set_total( 65 );
			$order->set_payment_method_title( __( 'Direct bank transfer', 'woocommerce' ) );
			$order->set_customer_note( __( "This is a customer note. Customers can add a note to their order on checkout.\n\nIt can be multiple lines. If there’s no note, this section is hidden.", 'woocommerce' ) );

			$address = $this->get_dummy_address();
			$order->set_billing_address( $address );
			$order->set_shipping_address( $address );

			return $order;
		}

		/**
		 * Get a dummy address.
		 *
		 * @return array
		 */
		protected function get_dummy_address() {
			$address = array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'company'    => 'Company',
				'email'      => 'john@company.com',
				'phone'      => '555-555-5555',
				'address_1'  => '123 Fake Street',
				'city'       => 'Faketown',
				'postcode'   => '12345',
				'country'    => 'US',
				'state'      => 'CA',
			);

			return $address;
		}
	}
}
