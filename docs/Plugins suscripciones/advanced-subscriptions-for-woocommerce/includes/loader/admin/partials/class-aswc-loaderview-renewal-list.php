<?php
/**
 * Exit if accessed directly
 *
 * @since      1.0.0
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * This is construct of class where all susbcriptions listed.
 *
 * @name Aswc_LoaderView_Renewal_List
 * @since      1.0.0
 * @category Class
 */
class Aswc_LoaderView_Renewal_List extends WP_List_Table {
	/**
	 * This is variable which is used for the store all the data.
	 *
	 * @var array $example_data variable for store data.
	 */
	public $example_data;

	/**
	 * This is variable which is used for the total count.
	 *
	 * @var array $aswc_total_count variable for total count.
	 */
	public $aswc_total_count;


	/**
	 * This construct colomns in susbcription table.
	 *
	 * @name get_columns.
	 * @since      1.0.0
	 */
	public function get_columns() {

		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'order_id'       => __( 'Order ID', 'advanced-subscriptions-for-woocommerce' ),
			'status'         => __( 'Status', 'advanced-subscriptions-for-woocommerce' ),
			'date'           => __( 'Date', 'advanced-subscriptions-for-woocommerce' ),
			'order_total'    => __( 'Order Total', 'advanced-subscriptions-for-woocommerce' ),
			'retry_attempts' => __( 'Retry Failed Attempts', 'advanced-subscriptions-for-woocommerce' ),
		);
		return $columns;
	}

	/**
	 * This show susbcriptions table list.
	 *
	 * @name column_default.
	 * @since      1.0.0
	 * @param array  $item  array of the items.
	 * @param string $column_name name of the colmn.
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'order_id':
				if ( aswc_loader_is_hpos_enabled() ) {
					$html = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $item[ $column_name ] ) ) . '">' . $item[ $column_name ] . '</a>';
				} else {
					$html = '<a href="' . esc_url( get_edit_post_link( $item[ $column_name ] ) ) . '">' . $item[ $column_name ] . '</a>';
				}
				return $html;
			case 'status':
				return $item[ $column_name ];
			case 'date':
				return $item[ $column_name ];
			case 'order_total':
				return $item[ $column_name ];
			case 'retry_attempts':
				$failed_attempts = aswc_get_meta_data( $item['order_id'], 'aswc_no_of_retry_attempt', true );
				return $failed_attempts ? $failed_attempts : '---';
			default:
				return false;
		}
	}

	/**
	 * Perform admin bulk action setting for susbcription table.
	 *
	 * @name process_bulk_action.
	 */
	public function process_bulk_action() {

		if ( 'bulk-delete' === $this->current_action() ) {

			if ( isset( $_POST['aswc_susbcription_list_table'] ) ) {
				$aswc_susbcription_list_table = sanitize_text_field( wp_unslash( $_POST['aswc_susbcription_list_table'] ) );
				if ( wp_verify_nonce( $aswc_susbcription_list_table, 'aswc_susbcription_list_table' ) ) {
					if ( isset( $_POST['aswc_order_ids'] ) && ! empty( $_POST['aswc_order_ids'] ) ) {
						$all_id               = map_deep( wp_unslash( $_POST['aswc_order_ids'] ), 'sanitize_text_field' );
						$aswc_subscription_id = isset( $_GET['aswc_subscription_id'] ) ? sanitize_text_field( wp_unslash( $_GET['aswc_subscription_id'] ) ) : '';

						$aswc_renewal_order_data = aswc_get_meta_data( $aswc_subscription_id, 'aswc_renewal_order_data', true );

						foreach ( $all_id as $key => $value ) {
							if ( in_array( $value, $aswc_renewal_order_data ) ) {
								$delet_key = array_search( $value, $aswc_renewal_order_data );
								if ( $delet_key ) {
									unset( $aswc_renewal_order_data[ $delet_key ] );
									$aswc_renewal_order_data = array_values( $aswc_renewal_order_data );
									aswc_update_order_meta( $aswc_subscription_id, 'aswc_renewal_order_data', $aswc_renewal_order_data );
								}
							}
							wp_delete_post( $value, true );
						}

						?>
						<div class="notice notice-success is-dismissible"> 
							<p><strong><?php esc_html_e( 'Order Deleted Successfully', 'advanced-subscriptions-for-woocommerce' ); ?></strong></p>
						</div>
						<?php
					}
				}
			}
		}
	}
	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @name process_bulk_action.
	 * @since      1.0.0
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'advanced-subscriptions-for-woocommerce' ),
		);
		return apply_filters( 'aswc_bulk_option', $actions );
	}

	/**
	 * Returns an associative array containing the bulk action for sorting.
	 *
	 * @name get_sortable_columns.
	 * @since      1.0.0
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'order_id' => array( 'order_id', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Prepare items for sorting.
	 *
	 * @name prepare_items.
	 * @since      1.0.0
	 */
	public function prepare_items() {
		$per_page              = 10;
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();
		$current_page = $this->get_pagenum();

		$this->example_data = $this->aswc_get_subscription_list();
		$data               = $this->example_data;
		usort( $data, array( $this, 'aswc_usort_reorder' ) );
		$data        = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$total_items = $this->aswc_total_count;
		$this->items = $data;
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}



	/**
	 * Return sorted associative array.
	 *
	 * @name aswc_usort_reorder.
	 * @since      1.0.0
	 * @return array
	 * @param array $cloumna column of the susbcriptions.
	 * @param array $cloumnb column of the susbcriptions.
	 */
	public function aswc_usort_reorder( $cloumna, $cloumnb ) {

		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'order_id';
		$order   = ( ! empty( $_REQUEST['order'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'desc';

		if ( is_numeric( $cloumna[ $orderby ] ) && is_numeric( $cloumnb[ $orderby ] ) ) {
			if ( $cloumna[ $orderby ] == $cloumnb[ $orderby ] ) {
				return 0;
			} elseif ( $cloumna[ $orderby ] < $cloumnb[ $orderby ] ) {
				$result = -1;
				return ( 'asc' === $order ) ? $result : -$result;
			} elseif ( $cloumna[ $orderby ] > $cloumnb[ $orderby ] ) {
				$result = 1;
				return ( 'asc' === $order ) ? $result : -$result;
			}
		} else {
			$result = strcmp( $cloumna[ $orderby ], $cloumnb[ $orderby ] );
			return ( 'asc' === $order ) ? $result : -$result;
		}
	}

	/**
	 * THis function is used for the add the checkbox.
	 *
	 * @name column_cb.
	 * @since      1.0.0
	 * @return array
	 * @param array $item array of the items.
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="aswc_order_ids[]" value="%s" />',
			$item['order_id']
		);
	}


	/**
	 * This function used to get all susbcriptions list.
	 *
	 * @name aswc_get_subscription_list.
	 * @since      1.0.0
	 * @return array
	 */
	public function aswc_get_subscription_list() {
		$aswc_subscriptions_data = array();
		$total_count             = 0;
		if ( isset( $_GET['aswc_subscription_id'] ) && ! empty( $_GET['aswc_subscription_id'] ) ) {

			$aswc_subscription_id = isset( $_GET['aswc_subscription_id'] ) ? sanitize_text_field( wp_unslash( $_GET['aswc_subscription_id'] ) ) : '';

			$aswc_renewal_order_data = aswc_get_meta_data( $aswc_subscription_id, 'aswc_renewal_order_data', true );

			if ( isset( $aswc_renewal_order_data ) && ! empty( $aswc_renewal_order_data ) && is_array( $aswc_renewal_order_data ) ) {

				/*search*/
				if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
					$data = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );

					if ( in_array( $data, $aswc_renewal_order_data ) ) {
						$aswc_renewal_order_data = array( $data );
					} else {
						$aswc_renewal_order_data = array();
					}
				}

				$total_count = count( $aswc_renewal_order_data );
				foreach ( $aswc_renewal_order_data as $key => $order_id ) {
					$order = wc_get_order( $order_id );
					if ( $order ) {
						$order_timestamp           = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : '';
						$order_total               = $order->get_formatted_order_total();
						$order_status              = $order->get_status();
						$aswc_subscriptions_data[] = array(
							'order_id'    => $order_id,
							'status'      => $order_status,
							'date'        => aswc_get_the_wordpress_date_format( $order_timestamp ),
							'order_total' => $order_total,
						);
					}
				}
			}
		}
		$this->aswc_total_count = $total_count;
		return $aswc_subscriptions_data;
	}
}

?>
	<h3 class="wp-heading-inline" id="aswc_heading"><?php esc_html_e( 'Subscriptions Renewal Order', 'advanced-subscriptions-for-woocommerce' ); ?></h3>
		<form method="post">
		<input type="hidden" name="page" value="<?php esc_html_e( 'aswc_susbcription_list_table', 'advanced-subscriptions-for-woocommerce' ); ?>">
		<?php wp_nonce_field( 'aswc_susbcription_list_table', 'aswc_susbcription_list_table' ); ?>
		<div class="aswc_list_table">
			<?php
			$mylisttable = new Aswc_LoaderView_Renewal_List();
			$mylisttable->prepare_items();
			$mylisttable->search_box( __( 'Search Order', 'advanced-subscriptions-for-woocommerce' ), 'aswc-order' );
			$mylisttable->display();
			?>
		</div>
	</form>
	<a  href="<?php echo esc_url( admin_url( 'admin.php?page=aswc_subscriptions_for_woocommerce_menu&sfw_tab=aswc-subscriptions-table' ) ); ?>" style="line-height: 2" class="button button-primary aswc_go_back"><?php esc_html_e( 'Go Back', 'advanced-subscriptions-for-woocommerce' ); ?></a>
	<?php


