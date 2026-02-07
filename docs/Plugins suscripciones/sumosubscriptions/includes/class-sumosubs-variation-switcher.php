<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handle subscription variation switcher.
 * 
 * @class SUMOSubs_Variation_Switcher
 */
class SUMOSubs_Variation_Switcher {

	/**
	 * Get Product Variations of Subscription placed Plan matched with Subscription Product Variation Plan. 
	 * Provide $subscription_id and $product_id and check whether those Subscription plans matched or not.
	 *
	 * @param int $subscription_id The Subscription post ID
	 * @param int $product_id The Product post ID
	 * @param array $selected_attributes
	 * @return array
	 */
	public static function get_matched_variations( $subscription_id, $product_id, $selected_attributes = array() ) {
		$_product = wc_get_product( $product_id );
		if ( ! $_product || 'variable' !== $_product->get_type() ) {
			return array();
		}

		$subscription_variations = sumo_get_available_subscription_variations( $product_id );
		if ( ! is_array( $subscription_variations ) ) {
			return array();
		}

		$matched_variations   = array();
		$subscription_plan    = sumo_get_subscription_plan( $subscription_id, 0, 0, false );
		$order_item_meta_data = sumo_pluck_order_items_by( get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ), 'meta_data' );
		$saved_variation_id   = $subscription_plan[ 'subscription_product_id' ] ? $subscription_plan[ 'subscription_product_id' ] : '';

		unset( $subscription_plan[ 'subscription_product_id' ], $subscription_plan[ 'subscription_product_qty' ], $subscription_plan[ 'subscription_order_item_fee' ], $subscription_plan[ 'variable_product_id' ] );

		foreach ( $_product->get_variation_attributes() as $key => $options ) {
			if ( is_array( $selected_attributes ) && ! empty( $selected_attributes ) ) {
				foreach ( $selected_attributes as $attribute_key => $attribute_value ) {
					if ( in_array( $attribute_value, $options ) ) {
						array_unshift( $options, $attribute_value );
					}
				}
			}

			$product_attributes[ sanitize_title( "attribute_$key" ) ] = array_unique( $options );
		}

		foreach ( $subscription_variations as $variation_id ) {
			$_variation = wc_get_product( $variation_id );

			if ( ! $_variation || ! $_variation->is_in_stock() || ! sumo_is_subscription_product( $variation_id ) ) {
				continue;
			}

			$subscription_variation_plan = sumo_get_subscription_plan( 0, $variation_id );
			unset( $subscription_variation_plan[ 'subscription_product_id' ], $subscription_variation_plan[ 'subscription_product_qty' ], $subscription_variation_plan[ 'subscription_order_item_fee' ], $subscription_variation_plan[ 'variable_product_id' ] );

			if ( $subscription_plan == $subscription_variation_plan && $saved_variation_id && ( $saved_variation_id != $variation_id || empty( array_filter( $_variation->get_variation_attributes() ) ) ) ) {
				$variation_attributes = $_variation->get_variation_attributes();
				$attributes_valid     = true;

				foreach ( $product_attributes as $attribute_key => $attribute_value ) {
					if ( isset( $variation_attributes[ $attribute_key ] ) && '' == $variation_attributes[ $attribute_key ] ) {
						$variation_attributes[ $attribute_key ] = $attribute_value;
					}
				}

				foreach ( $order_item_meta_data as $item_data ) {
					if ( is_array( $item_data ) && array_values( $item_data ) == array_values( $variation_attributes ) ) {
						$attributes_valid = false;
					}
				}

				if ( $attributes_valid ) {
					$matched_variations[ $variation_id ] = $variation_attributes;
				}
			}
		}

		return $matched_variations;
	}

	/**
	 * Get Matched Variation based upon Admin/User selection.
	 *
	 * @param int $subscription_id The Subscription post ID
	 * @param array $selected_attributes
	 * @param boolean $return_as_id
	 * @return array
	 */
	public static function get_matched_variation( $subscription_id, $selected_attributes = array(), $return_as_id = false ) {
		$matched_variation   = array();
		$filtered_attributes = array();
		$subscription_plan   = sumo_get_subscription_plan( $subscription_id );
		$matched_variations  = self::get_matched_variations( $subscription_id, $subscription_plan[ 'variable_product_id' ], $selected_attributes );

		foreach ( $matched_variations as $variation_id => $attributes ) {
			foreach ( $selected_attributes as $selected_attribute_key => $selected_attribute_value ) {

				if ( isset( $attributes[ $selected_attribute_key ] ) && (
						( is_array( $attributes[ $selected_attribute_key ] ) && in_array( $selected_attribute_value, $attributes[ $selected_attribute_key ] ) ) ||
						( $attributes[ $selected_attribute_key ] == $selected_attribute_value ) )
				) {
					$filtered_attributes[ $variation_id ][] = $selected_attribute_value;
				}
			}
		}

		foreach ( $filtered_attributes as $filtered_variation_id => $filtered_attribute ) {
			if ( ! is_array( $filtered_attribute ) ) {
				continue;
			}

			$diff = array_diff( array_values( $selected_attributes ), $filtered_attribute );
			if ( empty( $diff ) ) {
				$attributes          = isset( $matched_variations[ $filtered_variation_id ] ) ? $matched_variations[ $filtered_variation_id ] : '';
				$matched_variation[] = $return_as_id && $attributes ? $filtered_variation_id : $attributes;
			}
		}

		return $matched_variation;
	}

	/**
	 * Get matched variation attributes. 
	 * 
	 * @param int $subscription_id
	 * @return array
	 */
	public static function get_matched_attributes( $subscription_id ) {
		$subscription_plan = sumo_get_subscription_plan( $subscription_id );
		if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
			return array();
		}

		$matched_attributes = array();
		$matched_variations = self::get_matched_variations( $subscription_id, $subscription_plan[ 'variable_product_id' ] );
		if ( 'Active' === get_post_meta( $subscription_id, 'sumo_get_status', true ) && $matched_variations ) {
			$filtered_attributes_value = array();
			$_product                  = wc_get_product( $subscription_plan[ 'variable_product_id' ] );

			foreach ( $_product->get_variation_attributes() as $key => $options ) {
				foreach ( $matched_variations as $attributes ) {
					foreach ( $attributes as $attribute_name => $attribute_value ) {
						if ( sanitize_title( 'attribute_' . $key ) != sanitize_title( $attribute_name ) ) {
							continue;
						}

						if ( is_array( $attribute_value ) && ! empty( $attribute_value ) ) {
							foreach ( $attribute_value as $each_option ) {
								if ( ! in_array( $each_option, $filtered_attributes_value ) ) {
									$filtered_attributes_value[] = $each_option;
								}
							}
						} else {
							$filtered_attributes_value[] = $attribute_value;
						}
					}
				}

				$matched_attributes[ sanitize_title( $key ) ] = array_unique( $filtered_attributes_value );
				$filtered_attributes_value                    = array();
			}
		}

		return $matched_attributes;
	}

	/**
	 * Display Variation Switch Fields in Admin page and My Account page.
	 *
	 * @param array $matched_attributes
	 */
	public static function display( $subscription_id, $matched_attributes ) {
		if ( ! is_array( $matched_attributes ) || empty( $matched_attributes ) || ! function_exists( 'wp_json_encode' ) ) {
			return;
		}

		$matched_attributes_key = array();
		foreach ( $matched_attributes as $attribute_name => $attribute_values ) {
			if ( ! empty( $attribute_name ) ) {
				$matched_attributes_key[] = sanitize_title( 'attribute_' . $attribute_name );
			}
		}

		$item_meta = self::get_order_item_meta( $subscription_id );
		ob_start();
		?>
		<div class="sumo_subscription_variation_switcher">
			<a class="button variation_switch_button" id="variation_switch_button_<?php echo esc_attr( $subscription_id ); ?>" href="javascript:void(0)" data-post_id="<?php echo esc_attr( $subscription_id ); ?>"><?php esc_html_e( 'Switch Variation', 'sumosubscriptions' ); ?></a>
			<?php
			foreach ( $matched_attributes as $attribute_name => $attribute_values ) {
				if ( $attribute_values ) {
					?>
					<select class="variation_attribute_switch_selector variation_attribute_switch_selector_<?php echo esc_attr( $subscription_id ); ?>" id="variation_attribute_switch_selector_<?php echo esc_attr( sanitize_title( 'attribute_' . $attribute_name ) ); ?>_<?php echo esc_attr( $subscription_id ); ?>"  data-post_id="<?php echo esc_attr( $subscription_id ); ?>" data-selected_attribute_key="<?php echo esc_attr( sanitize_title( 'attribute_' . $attribute_name ) ); ?>" data-plan_matched_attributes_key="<?php echo wc_esc_json( wp_json_encode( $matched_attributes_key ) ); ?>" style="display: none">
						<option value="<?php echo esc_attr( $attribute_name ); ?>">
							<?php
							/* translators: 1: attribute name */
							printf( esc_html__( 'Select %s ..', 'sumosubscriptions' ), esc_html( $attribute_name ) );
							?>
						</option>
						<?php foreach ( $attribute_values as $attribute_value ) { ?>
							<option value="<?php echo esc_attr( $attribute_value ); ?>"><?php echo esc_html( $attribute_value ); ?></option>
						<?php } ?>
					</select>
					<?php
					if ( ! empty( $item_meta ) ) {
						foreach ( $item_meta as $meta_id => $meta ) {
							if ( $attribute_name === $meta->key ) {
								?>
								<input type="hidden" id="variation_attribute_selected_<?php echo esc_attr( sanitize_title( 'attribute_' . $attribute_name ) ); ?>_<?php echo esc_attr( $subscription_id ); ?>" value="<?php echo esc_attr( $meta->value ); ?>">
								<?php
							}
						}
					}
				}
			}
			?>
			<img id="load_variation_attributes_<?php echo esc_attr( $subscription_id ); ?>" src="<?php echo esc_url( SUMO_SUBSCRIPTIONS_PLUGIN_URL ) . '/assets/images/update.gif'; ?>" data-post_id="<?php echo esc_attr( $subscription_id ); ?>" style="display: none;width:20px;height:20px;"/>
			<a class="reset_variation_switch" id="reset_variation_switch_<?php echo esc_attr( $subscription_id ); ?>" href="javascript:void(0)" data-post_id="<?php echo esc_attr( $subscription_id ); ?>" data-plan_matched_attributes_key="<?php echo wc_esc_json( wp_json_encode( $matched_attributes_key ) ); ?>" data-plan_matched_attributes="<?php echo wc_esc_json( wp_json_encode( $matched_attributes ) ); ?>" style="display: none"><?php esc_html_e( 'Clear', 'sumosubscriptions' ); ?></a><br>
			<a class="button variation_switch_submit" id="variation_switch_submit_<?php echo esc_attr( $subscription_id ); ?>" href="javascript:void(0)" data-post_id="<?php echo esc_attr( $subscription_id ); ?>" data-plan_matched_attributes_key="<?php echo wc_esc_json( wp_json_encode( $matched_attributes_key ) ); ?>" style="display: none"><?php esc_html_e( 'Submit', 'sumosubscriptions' ); ?></a>
		</div>
		<?php
		ob_end_flush();
	}

	/**
	 * Get the formatted order item meta for Subscription.
	 *
	 * @since 14.8.0
	 * @param int $subscription_id Subscription ID.
	 * @return array
	 */
	public static function get_order_item_meta( $subscription_id ) {
		$subscription_plan = sumo_get_subscription_plan( $subscription_id );
		if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
			return array();
		}

		$parent_order_id = get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true );
		$order           = wc_get_order( $parent_order_id );
		if ( ! $order ) {
			return array();
		}

		$product = wc_get_product( $subscription_plan[ 'variable_product_id' ] );
		if ( $product && 'variable' === $product->get_type() ) {
			foreach ( $order->get_items() as $item_id => $item ) {
				if ( $product->get_id() === $item->get_product_id() ) {
					return $item->get_all_formatted_meta_data();
				}
			}
		}

		return array();
	}
}

/**
 * For Backward Compatibility.
 */
class SUMO_Subscription_Variation_Switcher extends SUMOSubs_Variation_Switcher {
	
}
