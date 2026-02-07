<?php
/**
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH\Subscription
 * @since   4.0.0
 * @author  YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

return array(
	'subscription-box' => array(

		array(
			'name' => esc_html__( 'Subscription box settings', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_section_subscription_box',
		),

		array(
			'name'      => esc_html__( 'Sold individually', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable to ensure that customers can only have one subscription box in their cart at a time.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_subscription_box_sold_individually',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),

		array(
			'name'      => esc_html__( 'Show site logo', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable to show the site logo in box creation setup.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_subscription_box_show_site_logo',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),

		array(
			'name'      => esc_html__( 'Logo', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Upload the site logo to show inside the box creation setup.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_subscription_box_site_logo',
			'type'      => 'yith-field',
			'yith-type' => 'media',
			'default'   => '',
			'deps'      => array(
				'id'    => 'ywsbs_subscription_box_show_site_logo',
				'value' => 'yes',
			),
		),

		array(
			'name'      => esc_html__( '"Add to cart" label in subscription box products', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Replace the "Add to cart" button label in subscription box products.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_subscription_box_add_to_cart_label',
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'default'   => esc_html_x( 'Create your box', 'Box button label', 'yith-woocommerce-subscription' ),
		),

		array(
			'name'         => esc_html__( 'Colors', 'yith-woocommerce-subscription' ),
			'id'           => 'ywsbs_subscription_box_colors',
			'type'         => 'yith-field',
			'yith-type'    => 'multi-colorpicker',
			'colorpickers' => array(
				array(
					array(
						'id'            => 'primary',
						'name'          => esc_html_x( 'Primary', '[Admin]Color option label', 'yith-woocommerce-subscription' ),
						'default'       => '#27b39a',
						'alpha_enabled' => false,
					),
					array(
						'id'            => 'primary-darker',
						'name'          => esc_html_x( 'Primary Darker', '[Admin]Color option label', 'yith-woocommerce-subscription' ),
						'default'       => '#2e8e73',
						'alpha_enabled' => false,
					),
				),
				array(
					array(
						'id'      => 'button-bg',
						'name'    => esc_html_x( 'Button background', '[Admin]Color option label', 'yith-woocommerce-subscription' ),
						'default' => '#000000',
					),
					array(
						'id'      => 'button-bg-hover',
						'name'    => esc_html_x( 'Button background hover', '[Admin]Color option label', 'yith-woocommerce-subscription' ),
						'default' => '#10ac7b',
					),
				),
				array(
					array(
						'id'      => 'button-text',
						'name'    => esc_html_x( 'Button text', '[Admin]Color option label', 'yith-woocommerce-subscription' ),
						'default' => '#ffffff',
					),
					array(
						'id'      => 'button-text-hover',
						'name'    => esc_html_x( 'Button text hover', '[Admin]Color option label', 'yith-woocommerce-subscription' ),
						'default' => '#ffffff',
					),
				),
				array(
					array(
						'id'      => 'header-bg',
						'name'    => esc_html_x( 'Header background', '[Admin]Color option label', 'yith-woocommerce-subscription' ),
						'default' => '#f0f0f0',
					),
					array(
						'id'      => 'footer-bg',
						'name'    => esc_html_x( 'Footer background', '[Admin]Color option label', 'yith-woocommerce-subscription' ),
						'default' => '#f0f0f0',
					),
				),
			),
		),

		array(
			'name'      => esc_html__( 'Pagination', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable to use pagination and show a specific number of products at a time.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_subscription_box_enable_pagination',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),

		array(
			'name'      => esc_html__( 'Products to show', 'yith-woocommerce-subscription' ),
			'desc'      => '',
			'id'        => 'ywsbs_subscription_box_products_per_page',
			'type'      => 'yith-field',
			'yith-type' => 'number',
			'min'       => '1',
			'default'   => '25',
			'deps'      => array(
				'id'    => 'ywsbs_subscription_box_enable_pagination',
				'value' => 'yes',
			),
		),

		array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_section_end_subscription_box',
		),

		array(
			'name' => esc_html__( 'Subscription box edition', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_section_subscription_box_edit',
		),

		array(
			'name'      => esc_html__( 'Allow customers to edit box content', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable to allow your customers to edit the box content from their accounts.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_subscription_box_editable',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),

		array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_section_end_subscription_box_edit',
		),
	),
);
