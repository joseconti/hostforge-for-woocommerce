<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handle subscription variation data.
 * 
 * @class SUMOSubs_Variation_Data
 */
class SUMOSubs_Variation_Data {

	/**
	 * Get the variation data template.
	 * 
	 * @var string
	 */
	protected static $template;

	/**
	 * Init SUMOSubs_Variation_Data.
	 */
	public static function init() {
		if ( 'from-plugin' === self::get_template() ) {
			add_filter( 'sumosubscriptions_get_single_variation_data_to_display', __CLASS__ . '::get_variation_data', 9, 2 );
			add_action( 'woocommerce_before_variations_form', __CLASS__ . '::get_variation_data', 10 );
			add_action( 'woocommerce_before_single_variation', __CLASS__ . '::get_variation_data', 10 );
			add_action( 'woocommerce_after_single_variation', __CLASS__ . '::get_variation_data', 10 );
		} else {
			add_filter( 'woocommerce_available_variation', __CLASS__ . '::get_variation_data_via_wc', 10, 3 );
		}
	}

	/**
	 * Get the variation data template.
	 * 
	 * @return string
	 */
	public static function get_template() {
		if ( ! is_null( self::$template ) ) {
			return self::$template;
		}

		self::$template = SUMOSubs_Admin_Options::get_option( 'variation_data_template' );
		return self::$template;
	}

	/**
	 * Subscription options for Variation product.
	 * 
	 * @param array $variation_data
	 * @param array $variable
	 * @param array $variation
	 * @return array
	 */
	public static function get_variation_data_via_wc( $variation_data, $variable, $variation ) {
		$variation = new SUMOSubs_Product( $variation );

		if ( $variation->exists() && $variation->is_subscription() ) {
			if ( sumo_can_purchase_subscription( $variation->get_id() ) ) {
				$variation_data[ 'sumosubs_plan_message' ]      = sumo_display_subscription_plan( 0, $variation->get_id() );
				$variation_data[ 'sumosubs_add_to_cart_label' ] = SUMOSubs_Admin_Options::get_option( 'subscribe_text' );
			} elseif ( 'yes' === SUMOSubs_Admin_Options::get_option( 'show_error_messages_in_product_page' ) ) {
					$variation_data[ 'sumosubs_restricted_message' ] = '<span id="sumosubs_restricted_message">' . SUMOSubs_Restrictions::add_error_notice() . '</span>';
			}

			$variation_data = apply_filters( 'sumosubscriptions_get_single_variation_data_via_wc_to_display', $variation_data, $variation );
		}

		return $variation_data;
	}

	/**
	 * Legacy.
	 * 
	 * Subscription options for Variation product.
	 * 
	 * @global WC_Product $product
	 * @param array $data
	 * @param mixed $variation
	 * @return mixed
	 */
	public static function get_variation_data( $data = array(), $variation = null ) {
		global $product;

		if ( 'sumosubscriptions_get_single_variation_data_to_display' === current_filter() ) {
			if ( $variation && $variation->exists() && $variation->is_subscription() ) {
				if ( sumo_can_purchase_subscription( $variation->get_id() ) ) {
					$data[ 'sumosubs_plan_message' ] = sumo_display_subscription_plan( 0, $variation->get_id() );
				} elseif ( 'yes' === SUMOSubs_Admin_Options::get_option( 'show_error_messages_in_product_page' ) ) {
						$data[ 'sumosubs_restricted_message' ] = '<span id="sumosubs_restricted_message">' . SUMOSubs_Restrictions::add_error_notice() . '</span>';
				}
			}

			return $data;
		} else if ( doing_action( 'woocommerce_before_variations_form' ) ) {
			$children = $product->get_visible_children();

			if ( ! empty( $children ) ) {
				$variation_data = array();

				foreach ( $children as $child_id ) {
					$product_variation = new SUMOSubs_Product( $child_id );

					if ( $product_variation->exists() && $product_variation->product->variation_is_visible() ) {
						$_variation_data = apply_filters( 'sumosubscriptions_get_single_variation_data_to_display', array(), $product_variation );

						if ( ! empty( $_variation_data ) ) {
							$variation_data[ $child_id ] = $_variation_data;
						}
					}
				}

				if ( ! empty( $variation_data ) && function_exists( 'wc_esc_json' ) ) {
					$variations = wp_json_encode( array_keys( $variation_data ) );
					?>
					<input type="hidden" id="sumosubs_single_variations" data-variations="<?php echo wc_esc_json( $variations ); ?>"/>
					<input type="hidden" id="sumosubs_single_variation_data" 
						   <?php
							foreach ( $variation_data as $variation_id => $data ) {
								foreach ( $data as $key => $message ) {
									?>
								   data-<?php echo esc_attr( $key . '_' . $variation_id ); ?>="<?php echo wc_esc_json( $message ); ?>" 
									<?php
								}
							}
							?>
						   />
					<?php
				}
			}
		} else if ( doing_action( 'woocommerce_before_single_variation' ) ) {
			echo '<span id="sumosubs_before_single_variation"></span>';
		} else {
			echo '<span id="sumosubs_after_single_variation"></span>';
		}
	}
}

SUMOSubs_Variation_Data::init();
