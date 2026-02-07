<?php
/**
 * Implements admin features of YITH WooCommerce Subscription
 *
 * @class   YITH_WC_Subscription_Admin
 * @since   1.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription_Admin' ) ) {
	/**
	 * Class YITH_WC_Subscription_Admin
	 */
	class YITH_WC_Subscription_Admin extends YITH_WC_Subscription_Admin_Legacy {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Panel Object
		 *
		 * @var YIT_Plugin_Panel_WooCommerce
		 */
		protected $panel;

		/**
		 * Panel Page
		 *
		 * @var string
		 */
		protected $panel_page = 'yith_woocommerce_subscription';

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 1.0.0
		 */
		private function __construct() {

			$this->load_required();

			// Add action links.
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_YWSBS_DIR . '/' . basename( YITH_YWSBS_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );
			// Privacy.
			add_action( 'init', array( $this, 'load_privacy_dpa' ), 0 );
			// Panel.
			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );
			add_action( 'admin_notices', array( $this, 'add_notices' ) );
			add_action( 'yith_plugin_fw_panel_before_panel_header', array( $this, 'add_payment_warning' ) );
			add_action( 'yit_panel_wc_before_update', array( $this, 'check_empty_panel_options' ) );
			add_filter( 'update_option_ywsbs_enable_shop_manager', array( $this, 'maybe_regenerate_shop_manager_capabilities' ), 10, 3 );
		}

		/**
		 * Load required files and functions.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		protected function load_required() {
			// Functions.
			include_once YITH_YWSBS_INC . 'admin/functions-yith-wc-subscription-admin.php';
			// List tables.
			require_once YITH_YWSBS_INC . 'admin/class-ywsbs-subscription-list-table.php';

			// Classes.
			YWSBS_Product_Post_Type_Admin::get_instance();
			YWSBS_Shop_Order_Post_Type_Admin::get_instance();
			YWSBS_Subscription_Post_Type_Admin::get_instance();
			YWSBS_Subscription_List_Table::get_instance();
		}

		/**
		 * Maybe regenerate the capabilities for shop manager.
		 *
		 * @since  3.0.0
		 * @param mixed  $old_value The old option value.
		 * @param mixed  $value     The new option value.
		 * @param string $option    Option name.
		 * @return void
		 */
		public function maybe_regenerate_shop_manager_capabilities( $old_value, $value, $option ) {
			if ( $old_value !== $value ) {
				$method = 'no' === $value ? 'remove_capabilities' : 'add_capabilities';
				YWSBS_Subscription_Capabilities::$method( 'shop_manager' );
			}
		}

		/**
		 * Get an array of panel tabs
		 *
		 * @since  3.0.0
		 * @return array
		 */
		public function get_panel_tabs() {
			return apply_filters(
				'ywsbs_register_panel_tabs',
				array(
					'subscription'  => array(
						'title' => __( 'Subscriptions', 'yith-woocommerce-subscription' ),
						'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>',
					),
					'general'       => array(
						'title'       => __( 'General settings', 'yith-woocommerce-subscription' ),
						'description' => __( 'Set the general behaviour of the plugin.', 'yith-woocommerce-subscription' ),
						'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
					),
					'customization' => array(
						'title'       => __( 'Customization', 'yith-woocommerce-subscription' ),
						'description' => __( 'Set custom labels and colors to create your own style.', 'yith-woocommerce-subscription' ),
						'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" /></svg>',
					),
				)
			);
		}

		/**
		 * Add a panel under YITH menu item
		 *
		 * @since  1.0
		 * @use    Yit_Plugin_Panel class
		 * @return void
		 * @see    plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {

			if ( ! empty( $this->panel ) ) {
				return;
			}

			$args = array(
				'create_menu_page' => apply_filters( 'ywsbs_register_panel_create_menu_page', true ),
				'parent_slug'      => '',
				'page_title'       => 'YITH WooCommerce Subscription',
				'menu_title'       => 'Subscription',
				'capability'       => 'manage_options',
				'parent'           => '',
				'parent_page'      => apply_filters( 'ywsbs_register_panel_parent_page', 'yith_plugin_panel' ),
				'page'             => $this->panel_page,
				'admin-tabs'       => $this->get_panel_tabs(),
				'options-path'     => YITH_YWSBS_DIR . '/plugin-options',
				'position'         => apply_filters( 'ywsbs_register_panel_position', null ),
				'plugin_slug'      => YITH_YWSBS_SLUG,
				'plugin_icon'      => YITH_YWSBS_ASSETS_URL . '/images/plugin.svg',
				'plugin_version'   => YITH_YWSBS_VERSION,
				'plugin-url'       => YITH_YWSBS_URL,
				'is_extended'      => false,
				'is_premium'       => true,
				'class'            => yith_set_wrapper_class(),
				'ui_version'       => 2,
				'help_tab'         => $this->get_help_tab_options(),
				'your_store_tools' => $this->get_store_tools_tab_options(),
				'welcome_modals'   => $this->get_welcome_modals_options(),
			);

			// enable shop manager to set Manage subscriptions.
			if ( 'yes' === get_option( 'ywsbs_enable_shop_manager' ) ) {
				add_filter( 'option_page_capability_yit_' . $args['parent'] . '_options', array( $this, 'change_capability' ) );
				$args['capability'] = 'manage_woocommerce';
			}

			$args['capability'] = apply_filters( 'ywsbs_register_panel_capabilities', $args['capability'] );

			if ( ! class_exists( 'YIT_Plugin_Panel' ) ) {
				include_once YITH_YWSBS_DIR . '/plugin-fw/lib/yit-plugin-panel.php';
			}

			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				include_once YITH_YWSBS_DIR . '/plugin-fw/lib/yit-plugin-panel-wc.php';
			}

			$this->panel = new YIT_Plugin_Panel_WooCommerce( $args );

			add_filter( 'yith_plugin_fw_get_field_template_path', array( $this, 'get_yith_panel_custom_template' ), 10, 2 );
			add_filter( 'yith_plugin_fw_wc_panel_pre_field_value', array( $this, 'get_value_of_custom_type_field' ), 10, 2 );
			add_action( 'yith_ywsbs_activities_tab', array( $this, 'activities_tab' ) );
		}

		/**
		 * Get panel help tab options
		 *
		 * @since 3.0.0
		 * @return array
		 */
		protected function get_help_tab_options() {
			return array(
				'main_video' => array(
					'desc' => _x( 'Check this video to learn how to <b>configure the plugin and create a subscription product:</b>', '[HELP TAB] Video title', 'yith-woocommerce-subscription' ),
					'url'  => array(
						'en' => 'https://www.youtube.com/embed/2w_XFpIH8V0',
						'it' => 'https://www.youtube.com/embed/Zu-XBo1DtJo',
						'es' => 'https://www.youtube.com/embed/mb5jcnWxHMY',
					),
				),
				'playlists'  => array(
					'en' => 'https://www.youtube.com/watch?v=2w_XFpIH8V0&list=PLDriKG-6905nMcSTYsSYW3SK2W5bIYqBW',
					'it' => 'https://www.youtube.com/watch?v=Zu-XBo1DtJo&list=PL9c19edGMs0-OubJGV491qHWl45UEADZn',
					'es' => 'https://www.youtube.com/watch?v=7kX7nxBD2BA&list=PL9Ka3j92PYJOyeFNJRdW9oLPkhfyrXmL1',
				),
			);
		}

		/**
		 * Get panel store tools tab options
		 *
		 * @since 3.0.0
		 * @return array
		 */
		protected function get_store_tools_tab_options() {
			return array(
				'items' => array(
					'stripe'              => array(
						'name'           => 'Stripe',
						'icon_url'       => YITH_YWSBS_ASSETS_URL . '/images/plugins/stripe.svg',
						'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-stripe/',
						'description'    => __( 'Allow your customers to pay for orders placed in your shop with credit cards.', 'yith-woocommerce-subscription' ),
						'is_active'      => defined( 'YITH_WCSTRIPE_PREMIUM' ),
						'is_recommended' => true,
					),
					'membership'          => array(
						'name'           => 'Membership',
						'icon_url'       => YITH_YWSBS_ASSETS_URL . '/images/plugins/membership.svg',
						'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-membership/',
						'description'    => __( 'Activate some sections of your e-commerce with restricted access so as to create memberships in your store.', 'yith-woocommerce-subscription' ),
						'is_active'      => defined( 'YITH_WCMBS_PREMIUM' ),
						'is_recommended' => true,
					),
					'gift-card'           => array(
						'name'        => 'Gift Cards',
						'icon_url'    => YITH_YWSBS_ASSETS_URL . '/images/plugins/gift-card.svg',
						'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-gift-cards/',
						'description' => __( 'Sell gift cards to increase your store\'s revenue and win new customers.', 'yith-woocommerce-subscription' ),
						'is_active'   => defined( 'YITH_YWGC_PREMIUM' ),
					),
					'affiliate'           => array(
						'name'        => 'Affiliates',
						'icon_url'    => YITH_YWSBS_ASSETS_URL . '/images/plugins/affiliate.svg',
						'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-affiliates/',
						'description' => __( 'Run an affiliate campaign to drive traffic to your shop, get new customers and grow revenue.', 'yith-woocommerce-subscription' ),
						'is_active'   => defined( 'YITH_WCAF_PREMIUM' ),
					),
					'points'              => array(
						'name'        => 'Points and Rewards',
						'icon_url'    => YITH_YWSBS_ASSETS_URL . '/images/plugins/points.svg',
						'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-points-and-rewards/',
						'description' => __( 'Loyalize your customers with an effective points-based loyalty program and instant rewards.', 'yith-woocommerce-subscription' ),
						'is_active'   => defined( 'YITH_YWPAR_PREMIUM' ),
					),
					'product-add-ons'     => array(
						'name'        => 'Product Add-Ons & Extra Options',
						'icon_url'    => YITH_YWSBS_ASSETS_URL . '/images/plugins/product-add-ons.svg',
						'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-product-add-ons/',
						'description' => __( 'Add paid or free advanced options to your product pages using fields like radio buttons, checkboxes, drop-downs, custom text inputs, and more.', 'yith-woocommerce-subscription' ),
						'is_active'   => defined( 'YITH_WAPO_PREMIUM' ),
					),
					'customize-myaccount' => array(
						'name'        => 'Customize My Account Page',
						'icon_url'    => YITH_YWSBS_ASSETS_URL . '/images/plugins/customize-myaccount.svg',
						'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-customize-myaccount-page/',
						'description' => __( 'Customize the My Account page of your customers by creating custom sections with promotions and ad-hoc content based on your needs.', 'yith-woocommerce-subscription' ),
						'is_active'   => defined( 'YITH_WCMAP_PREMIUM' ),
					),
					'product-filter'      => array(
						'name'        => 'Ajax Product Filter',
						'icon_url'    => YITH_YWSBS_ASSETS_URL . '/images/plugins/product-filter.svg',
						'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-ajax-product-filter/',
						'description' => __( 'Help your customers to easily find the products they are looking for and improve the user experience of your shop.', 'yith-woocommerce-subscription' ),
						'is_active'   => defined( 'YITH_WCAN_PREMIUM' ),
					),
				),
			);
		}

		/**
		 * Get welcome modals options
		 *
		 * @since 3.2.0
		 * @return array
		 */
		protected function get_welcome_modals_options() {
			return array(
				'on_close' => function () {
					update_option( 'yith-ywsbs-welcome-modal', YITH_YWSBS_VERSION );
				},
				'modals'   => array(
					'welcome' => array(
						'type'        => 'welcome',
						'description' => __( 'Generate passive income and build customer loyalty by selling subscription products.', 'yith-woocommerce-subscription' ),
						'show'        => empty( get_option( 'yith-ywsbs-welcome-modal', null ) ),
						'items'       => array(
							'documentation'  => array(),
							'how-to-video'   => array(
								'url' => array(
									'en' => 'https://www.youtube.com/watch?v=2w_XFpIH8V0',
									'it' => 'https://www.youtube.com/watch?v=Zu-XBo1DtJo',
									'es' => 'https://www.youtube.com/watch?v=mb5jcnWxHMY',
								),
							),
							'feature'        => array(
								'title'       => __( '<mark>Enable the feature</mark> you need for your store', 'yith-woocommerce-subscription' ),
								'description' => __( 'Extra features to manage your subscription products.', 'yith-woocommerce-subscription' ),
								'url'         => add_query_arg(
									array(
										'page' => $this->panel_page,
										'tab'  => 'general',
									),
									admin_url( 'admin.php' )
								),
							),
							'create-product' => array(
								'title'       => __( 'Are you ready? Create your first <mark>subscription product</mark>', 'yith-woocommerce-subscription' ),
								'description' => __( '...and start the adventure!', 'yith-woocommerce-subscription' ),
								'url'         => add_query_arg( array( 'post_type' => 'product' ), admin_url( 'post-new.php' ) ),
							),
						),
					),
					'update'  => array(
						'type'          => 'update',
						'show'          => function () {
							return version_compare( get_option( 'yith-ywsbs-welcome-modal', YITH_YWSBS_VERSION ), '4.0', '<' );
						},
						'since'         => '4.0',
						'changelog_url' => 'https://docs.yithemes.com/yith-woocommerce-subscription/changelog/changelog-premium/',
						'items'         => array(
							'box' => array(
								'title'       => __( 'The new "Box" module, an exclusive feature of our plugin.', 'yith-woocommerce-subscription' ),
								'description' => __( 'The ultimate solution for selling subscription boxes of products sent periodically. Regular delivery allows you to build customer loyalty and offer an innovative shopping experience.', 'yith-woocommerce-subscription' ),
								'url'         => add_query_arg(
									array(
										'page' => $this->panel_page,
										'tab'  => 'modules',
									),
									admin_url( 'admin.php' )
								),
							),
						),
					),
				),
			);
		}

		/**
		 * Get the admin panel page name
		 *
		 * @since  3.0.0
		 * @return string
		 */
		public function get_panel_page_slug() {
			return $this->panel_page;
		}

		/**
		 * Add custom panel fields.
		 *
		 * @param string $template Template.
		 * @param array  $field    Fields array data.
		 *
		 * @return string
		 */
		public function get_yith_panel_custom_template( $template, $field ) {
			$custom_option_types = array(
				'ywsbs-products',
				'show-categories',
				'delivered-scheduled',
			);

			if ( isset( $field['type'] ) && in_array( $field['type'], $custom_option_types, true ) ) {
				$template = YITH_YWSBS_VIEWS_PATH . "/panel/types/{$field['type']}.php";
			}

			return $template;
		}

		/**
		 * Get the value of custom fields.
		 *
		 * @param mixed $value Value.
		 * @param array $field Fields array data.
		 *
		 * @return mixed|void
		 */
		public function get_value_of_custom_type_field( $value, $field ) {
			$custom_option_types = array(
				'inline-fields',
			);

			if ( isset( $field['type'] ) && in_array( $field['type'], $custom_option_types, true ) ) {
				$value = get_option( $field['id'], $field['default'] );
			}

			return $value;
		}

		/**
		 * Activities List Table
		 *
		 * Load the activities on admin page
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function activities_tab() {

			if ( YITH_WC_Activity()->is_activities_list_empty() ) {
				include_once YITH_YWSBS_VIEWS_PATH . '/activities/activities-blank-state.php';
			} else {
				include_once YITH_YWSBS_INC . 'admin/class-yith-ywsbs-activities-list-table.php';
				$this->cpt_obj_activities = new YITH_YWSBS_Activities_List_Table();

				$activities_tab = YITH_YWSBS_VIEWS_PATH . '/activities/activities-list-table.php';
				if ( file_exists( $activities_tab ) ) {
					include_once $activities_tab;
				}
			}
		}

		/**
		 * Action Links
		 *
		 * @param array $links Links plugin array.
		 * @return mixed
		 */
		public function action_links( $links ) {

			if ( function_exists( 'yith_add_action_links' ) ) {
				$links = yith_add_action_links( $links, $this->panel_page, true, YITH_YWSBS_SLUG );
			}

			return $links;
		}

		/**
		 * Add the action links to plugin admin page.
		 *
		 * @param array  $new_row_meta_args Plugin Meta New args.
		 * @param string $plugin_meta       Plugin Meta.
		 * @param string $plugin_file       Plugin file.
		 * @param array  $plugin_data       Plugin data.
		 * @param string $status            Status.
		 * @param string $init_file         Init file.
		 *
		 * @return array
		 */
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YITH_YWSBS_INIT' ) {

			if ( defined( $init_file ) && constant( $init_file ) === $plugin_file ) {
				$new_row_meta_args['slug']       = YITH_YWSBS_SLUG;
				$new_row_meta_args['is_premium'] = true;
			}

			return $new_row_meta_args;
		}

		/**
		 * Includes Privacy DPA Class.
		 */
		public function load_privacy_dpa() {
			if ( class_exists( 'YITH_Privacy_Plugin_Abstract' ) ) {
				YITH_YWSBS_Privacy_DPA::get_instance();
			}
		}

		/**
		 * Check the empty options when the panel is saved.
		 *
		 * @since 2.1
		 */
		public function check_empty_panel_options() {

			$post = $_REQUEST; //phpcs:ignore

			if ( isset( $post['yit_panel_wc_options_nonce'], $post['tab'] ) && $post['tab'] === 'general' && wp_verify_nonce( $post['yit_panel_wc_options_nonce'], 'yit_panel_wc_options_' . $this->panel_page ) ) { //phpcs:ignore
				$registered_url = get_option( 'ywsbs_registered_url' );
				$registered_url = str_replace( array( 'https://', 'http://', 'www.' ), '', $registered_url );
				$current_url    = str_replace( array( 'https://', 'http://', 'www.' ), '', get_site_url() );

				if ( isset( $post['ywsbs_site_staging'] ) ) {
					$old = get_option( 'ywsbs_site_staging' );
					if ( 'yes' !== $old ) {
						yith_subscription_log( 'Changed site staging from ' . $registered_url . ' to ' . $current_url );
					}
				} else {
					update_option( 'ywsbs_site_changed', 'no' );
					update_option( 'ywsbs_site_staging', 'no' );
					update_option( 'ywsbs_registered_url', $current_url );
				}
			}
		}

		/**
		 * Print warning notice when system detects an url change
		 *
		 * @since 2.2.0
		 * @return void
		 */
		public function add_notices() {
			$notice_dismissed = get_user_meta( get_current_user_id(), 'dismissed_ywsbs_staging_notice', true );
			if ( $notice_dismissed || ( 'no' === get_option( 'ywsbs_site_changed', 'no' ) && 'no' === get_option( 'ywsbs_site_staging', 'no' ) ) ) {
				return;
			}

			include YITH_YWSBS_VIEWS_PATH . '/notices/staging.php';
		}

		/**
		 * Add payment method warning
		 *
		 * @since 3.6.0
		 * @return void
		 */
		public function add_payment_warning() {

			if ( ! ( ywsbs_is_admin_panel_page() || ywsbs_check_valid_admin_page( YITH_YWSBS_POST_TYPE ) ) ) {
				return;
			}

			if ( ! YWSBS_Subscription_Gateways::has_available_gateways() ) {
				$supported_gateways = YWSBS_Subscription_Gateways::get_supported_gateways();
				include YITH_YWSBS_VIEWS_PATH . '/notices/payment-warning.php';
			}

			if ( YWSBS_Subscription_Gateways::is_gateway_available( 'woocommerce-stripe' ) && 'yes' === get_option( 'woocommerce_enable_guest_checkout', 'no' ) && 'no' === get_option( 'ywsbs_force_account_with_subscription', 'no' ) ) {
				include YITH_YWSBS_VIEWS_PATH . '/notices/stripe-guest-warning.php';
			}
		}
	}
}

/**
 * Unique access to instance of YITH_WC_Subscription_Admin class
 *
 * @return YITH_WC_Subscription_Admin
 */
function YITH_WC_Subscription_Admin() { //phpcs:ignore
	return YITH_WC_Subscription_Admin::get_instance();
}
