<?php 
/**
 * YWSBS_Membership class to add compatibility with YITH WooCommerce Multivendor
 *
 * @class   YWSBS_Membership
 * @since   1.1.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Membership' ) ) {
	/**
	 * Class YWSBS_Membership
	 */
	class YWSBS_Membership {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Constructor
		 *
		 * Initialize class and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		private function __construct() {
			add_action( 'init', array( $this, 'init' ) );
		}

		/**
		 * Init class hooks and filters
		 *
		 * @since 3.2.0
		 * @return void
		 */
		public function init() {
			if ( ! function_exists( 'YITH_WCMBS_Membership_Helper' ) ) {
				return;
			}

			add_filter( 'ywsbs_subscription_table_list_columns', array( $this, 'subscription_table_list_columns' ) );
			add_filter( 'ywsbs_column_default', array( $this, 'subscription_column_default' ), 10, 3 );
		}

		/**
		 * Add a column inside subscription list table.
		 *
		 * @param array $columns Columns list.
		 *
		 * @return array
		 */
		public function subscription_table_list_columns( $columns ) {
			$columns['membership'] = __( 'Membership Status', 'yith-woocommerce-subscription' );

			return $columns;
		}

		/**
		 * Fill the new column with the Membership status.
		 *
		 * @param string  $result      Value to fill.
		 * @param WP_Post $item        Current item.
		 * @param string  $column_name Column name.
		 *
		 * @return string
		 */
		public function subscription_column_default( $result, $item, $column_name ) {
			if ( 'membership' === $column_name ) {
				$memberships = YITH_WCMBS_Membership_Helper()->get_memberships_by_subscription( $item->ID );
				if ( $memberships ) {
					$result = $memberships[0]->get_status_text();
				}
			}

			return $result;
		}
	}
}

/**
 * Unique access to instance of YWSBS_Membership class
 *
 * @return YWSBS_Membership
 */
function YWSBS_Membership() { // phpcs:ignore
	return YWSBS_Membership::get_instance();
}
