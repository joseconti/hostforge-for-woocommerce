<?php
/**
 * Product Add-ons System.
 *
 * Optional extras for hosting products: dedicated IP, backup, SSL, extra storage.
 * Displays on the product page and adds to the cart price.
 *
 * @package HostForge\Products
 */

namespace HostForge\Products;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Product_Addons
 */
class HF_Product_Addons {

	/**
	 * Meta key for storing add-ons configuration.
	 *
	 * @var string
	 */
	private const META_KEY = '_hf_addons';

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Admin: add-ons panel in product editor.
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_addons_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_addons_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_addons' ) );

		// Frontend: display add-ons on product page.
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render_frontend_addons' ) );
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_addon_data_to_cart' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'adjust_cart_price' ) );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_addon_in_cart' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_addons_to_order_item' ), 20, 4 );
	}

	/**
	 * Add Add-ons tab to product editor.
	 *
	 * @param array<string, array> $tabs Product data tabs.
	 * @return array<string, array>
	 */
	public static function add_addons_tab( array $tabs ): array {
		$hf_types = HF_Product_Types::get_type_slugs();
		$classes  = array_map(
			function ( string $type ): string {
				return 'show_if_' . $type;
			},
			$hf_types
		);

		$tabs['hf_addons'] = array(
			'label'    => esc_html__( 'Add-ons', 'hostforge' ),
			'target'   => 'hf_addons_data',
			'class'    => $classes,
			'priority' => 22,
		);

		return $tabs;
	}

	/**
	 * Render add-ons panel in admin.
	 *
	 * @return void
	 */
	public static function render_addons_panel(): void {
		global $post;

		$product_id = $post ? $post->ID : 0;
		$addons     = self::get_addons( $product_id );
		?>
		<div id="hf_addons_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<p class="form-field">
					<label><?php esc_html_e( 'Product Add-ons', 'hostforge' ); ?></label>
					<span class="description"><?php esc_html_e( 'Optional extras customers can add to their order.', 'hostforge' ); ?></span>
				</p>

				<div id="hf-addons-list">
					<?php
					if ( ! empty( $addons ) ) {
						foreach ( $addons as $index => $addon ) {
							self::render_addon_row( $index, $addon );
						}
					}
					?>
				</div>

				<p class="form-field">
					<button type="button" class="button" id="hf-add-addon">
						<?php esc_html_e( 'Add New Add-on', 'hostforge' ); ?>
					</button>
				</p>
			</div>

			<script type="text/template" id="hf-addon-template">
				<?php self::render_addon_row( '{{INDEX}}', array() ); ?>
			</script>

			<script>
				(function() {
					var addonIndex = <?php echo count( $addons ); ?>;

					document.getElementById('hf-add-addon').addEventListener('click', function() {
						var template = document.getElementById('hf-addon-template').innerHTML;
						template = template.replace(/\{\{INDEX\}\}/g, addonIndex);
						var container = document.getElementById('hf-addons-list');
						container.insertAdjacentHTML('beforeend', template);
						addonIndex++;
					});

					document.getElementById('hf-addons-list').addEventListener('click', function(e) {
						if (e.target.classList.contains('hf-remove-addon')) {
							e.target.closest('.hf-addon-row').remove();
						}
					});
				})();
			</script>
		</div>
		<?php
	}

	/**
	 * Render a single add-on row in admin.
	 *
	 * @param int|string $index Index.
	 * @param array      $addon Add-on data.
	 * @return void
	 */
	private static function render_addon_row( $index, array $addon ): void {
		$addon = wp_parse_args(
			$addon,
			array(
				'name'        => '',
				'description' => '',
				'price'       => '',
				'type'        => 'one_time',
			)
		);
		?>
		<div class="hf-addon-row" style="border: 1px solid #ddd; padding: 10px; margin: 5px 12px 10px; background: #fafafa;">
			<p class="form-field">
				<label><?php esc_html_e( 'Name', 'hostforge' ); ?></label>
				<input type="text" name="hf_addons[<?php echo esc_attr( $index ); ?>][name]"
					value="<?php echo esc_attr( $addon['name'] ); ?>" style="width:50%;" />
			</p>
			<p class="form-field">
				<label><?php esc_html_e( 'Description', 'hostforge' ); ?></label>
				<input type="text" name="hf_addons[<?php echo esc_attr( $index ); ?>][description]"
					value="<?php echo esc_attr( $addon['description'] ); ?>" style="width:50%;" />
			</p>
			<p class="form-field">
				<label><?php esc_html_e( 'Price', 'hostforge' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</label>
				<input type="number" name="hf_addons[<?php echo esc_attr( $index ); ?>][price]"
					value="<?php echo esc_attr( $addon['price'] ); ?>" step="0.01" min="0" style="width:120px;" />
			</p>
			<p class="form-field">
				<label><?php esc_html_e( 'Pricing Type', 'hostforge' ); ?></label>
				<select name="hf_addons[<?php echo esc_attr( $index ); ?>][type]" style="width:200px;">
					<option value="one_time" <?php selected( $addon['type'], 'one_time' ); ?>><?php esc_html_e( 'One-time', 'hostforge' ); ?></option>
					<option value="recurring" <?php selected( $addon['type'], 'recurring' ); ?>><?php esc_html_e( 'Recurring', 'hostforge' ); ?></option>
				</select>
			</p>
			<p>
				<button type="button" class="button hf-remove-addon"><?php esc_html_e( 'Remove', 'hostforge' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Save add-ons from admin.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public static function save_addons( int $product_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC handles nonce.
		$product = wc_get_product( $product_id );
		if ( ! $product || ! HF_Product_Types::is_hf_type( $product->get_type() ) ) {
			return;
		}

		$addons = array();

		if ( isset( $_POST['hf_addons'] ) && is_array( $_POST['hf_addons'] ) ) {
			foreach ( $_POST['hf_addons'] as $addon ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$name = isset( $addon['name'] ) ? sanitize_text_field( wp_unslash( $addon['name'] ) ) : '';
				if ( empty( $name ) ) {
					continue;
				}

				$addons[] = array(
					'name'        => $name,
					'description' => isset( $addon['description'] ) ? sanitize_text_field( wp_unslash( $addon['description'] ) ) : '',
					'price'       => isset( $addon['price'] ) ? wc_format_decimal( wp_unslash( $addon['price'] ) ) : '0',
					'type'        => isset( $addon['type'] ) && 'recurring' === $addon['type'] ? 'recurring' : 'one_time',
				);
			}
		}

		$product->update_meta_data( self::META_KEY, wp_json_encode( $addons ) );
		$product->save();
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Display add-ons on the product page.
	 *
	 * @return void
	 */
	public static function render_frontend_addons(): void {
		global $product;

		if ( ! $product || ! HF_Product_Types::is_hf_type( $product->get_type() ) ) {
			return;
		}

		$addons = self::get_addons( $product->get_id() );

		if ( empty( $addons ) ) {
			return;
		}

		/**
		 * Filters the product add-ons before rendering on the frontend.
		 *
		 * Allows modifying, adding, or removing add-on options displayed
		 * on the product page before customers see them.
		 *
		 * @since 1.0.0
		 *
		 * @param array       $addons  Array of add-on definition arrays.
		 * @param \WC_Product $product The current product object.
		 */
		$addons = apply_filters( 'hostforge_product_addons', $addons, $product );

		if ( empty( $addons ) ) {
			return;
		}

		echo '<div class="hf-product-addons">';
		echo '<h4>' . esc_html__( 'Optional Add-ons', 'hostforge' ) . '</h4>';

		foreach ( $addons as $index => $addon ) {
			$price_display = wc_price( (float) $addon['price'] );
			if ( 'recurring' === $addon['type'] ) {
				/* translators: %s: price */
				$price_display .= ' <small>' . esc_html__( '/ billing cycle', 'hostforge' ) . '</small>';
			}

			echo '<div class="hf-addon-option">';
			echo '<label>';
			echo '<input type="checkbox" name="hf_selected_addons[]" value="' . esc_attr( $index ) . '" /> ';
			echo esc_html( $addon['name'] );
			echo ' — ' . wp_kses_post( $price_display );
			echo '</label>';

			if ( ! empty( $addon['description'] ) ) {
				echo '<p class="description">' . esc_html( $addon['description'] ) . '</p>';
			}

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Add selected add-ons to cart item data.
	 *
	 * @param array<string,mixed> $cart_item_data Cart item data.
	 * @param int                 $product_id     Product ID.
	 * @return array<string,mixed>
	 */
	public static function add_addon_data_to_cart( array $cart_item_data, int $product_id ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['hf_selected_addons'] ) || ! is_array( $_POST['hf_selected_addons'] ) ) {
			return $cart_item_data;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || ! HF_Product_Types::is_hf_type( $product->get_type() ) ) {
			return $cart_item_data;
		}

		$addons          = self::get_addons( $product_id );
		$selected        = array_map( 'absint', $_POST['hf_selected_addons'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$selected_addons = array();

		foreach ( $selected as $index ) {
			if ( isset( $addons[ $index ] ) ) {
				$selected_addons[] = $addons[ $index ];
			}
		}

		if ( ! empty( $selected_addons ) ) {
			/**
			 * Filters the add-on data saved to the cart item.
			 *
			 * Allows modifying the add-on data before it is stored in
			 * the cart item, for example to add custom metadata fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array $selected_addons Array of selected add-on definitions.
			 * @param int   $product_id      The product ID.
			 * @param array $cart_item_data   The existing cart item data.
			 */
			$selected_addons = apply_filters( 'hostforge_addon_cart_data', $selected_addons, $product_id, $cart_item_data );

			$cart_item_data['hf_addons'] = $selected_addons;
		}

		return $cart_item_data;
	}

	/**
	 * Adjust cart item price based on selected add-ons.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public static function adjust_cart_price( \WC_Cart $cart ): void {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['hf_addons'] ) ) {
				continue;
			}

			$product     = $cart_item['data'];
			$base_price  = (float) $product->get_price();
			$addon_total = 0;

			foreach ( $cart_item['hf_addons'] as $addon ) {
				$addon_price = (float) $addon['price'];

				/**
				 * Filters the price of a single add-on during cart calculation.
				 *
				 * Allows modifying add-on pricing dynamically, for example
				 * to apply discounts or currency conversions.
				 *
				 * @since 1.0.0
				 *
				 * @param float       $addon_price The add-on price.
				 * @param array       $addon       The add-on definition array.
				 * @param array       $cart_item   The cart item data.
				 * @param \WC_Product $product     The product object.
				 */
				$addon_price = (float) apply_filters( 'hostforge_addon_price', $addon_price, $addon, $cart_item, $product );

				$addon_total += $addon_price;
			}

			$product->set_price( $base_price + $addon_total );
		}
	}

	/**
	 * Display add-on info in cart/checkout.
	 *
	 * @param array               $item_data Cart item display data.
	 * @param array<string,mixed> $cart_item Cart item.
	 * @return array
	 */
	public static function display_addon_in_cart( array $item_data, array $cart_item ): array {
		if ( ! empty( $cart_item['hf_addons'] ) ) {
			foreach ( $cart_item['hf_addons'] as $addon ) {
				$item_data[] = array(
					'key'   => esc_html( $addon['name'] ),
					'value' => wp_strip_all_tags( wc_price( (float) $addon['price'] ) ),
				);
			}
		}

		return $item_data;
	}

	/**
	 * Save add-on data to order item meta.
	 *
	 * @param \WC_Order_Item_Product $item          Order item.
	 * @param string                 $cart_item_key Cart item key.
	 * @param array                  $values        Cart item values.
	 * @param \WC_Order              $order         Order object.
	 * @return void
	 */
	public static function save_addons_to_order_item( \WC_Order_Item_Product $item, string $cart_item_key, array $values, \WC_Order $order ): void {
		if ( empty( $values['hf_addons'] ) ) {
			return;
		}

		$item->add_meta_data( '_hf_addons', wp_json_encode( $values['hf_addons'] ) );

		foreach ( $values['hf_addons'] as $addon ) {
			$item->add_meta_data( '_hf_addon_' . sanitize_title( $addon['name'] ), wc_price( (float) $addon['price'] ) );
		}
	}

	/**
	 * Get add-ons for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array<int, array{name: string, description: string, price: string, type: string}>
	 */
	public static function get_addons( int $product_id ): array {
		$raw = get_post_meta( $product_id, self::META_KEY, true );

		if ( is_string( $raw ) && ! empty( $raw ) ) {
			$decoded = json_decode( $raw, true );
			return is_array( $decoded ) ? $decoded : array();
		}

		return is_array( $raw ) ? $raw : array();
	}
}
