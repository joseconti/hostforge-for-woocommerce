<?php
/**
 * Subscription module "synchronization" options array
 *
 * @since   3.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

return array(
	'delivery-settings' => array(
		array(
			'name' => esc_html__( 'Delivery schedules', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_delivery_general',
		),

		array(
			'name'      => esc_html_x( 'Set a delivery schedule of subscription products for:', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html_x( 'Choose if you need to set a delivery schedule for all products, only for non-virtual products or for specific categories or products', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_enable_delivery',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				// translators:placeholders are html tags.
				'all_products' => sprintf( esc_html_x( 'All products %1$sYou will be able to exclude some products or categories if this option is selected.%2$s', 'Admin option, the placeholder are tags', 'yith-woocommerce-subscription' ), '<small>', '</small>' ),
				// translators:placeholders are html tags.
				'physical'     => sprintf( esc_html_x( 'Only non-virtual products %1$sYou will be able to exclude some products or categories if this option is selected.%2$s', 'Admin option, the placeholder are tags', 'yith-woocommerce-subscription' ), '<small>', '</small>' ),
				'products'     => esc_html_x( 'Specific products', 'Admin option choice', 'yith-woocommerce-subscription' ),
				// translators:placeholders are html tags.
				'categories'   => sprintf( esc_html_x( 'Specific categories %1$sYou will be able to exclude some products if this option is selected.%2$s', 'Admin option, the placeholder are tags', 'yith-woocommerce-subscription' ), '<small>', '</small>' ),
			),
			'default'   => 'no',
		),

		array(
			'name'              => esc_html__( 'Exclude products', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html__( 'Enable if you want to exclude products.', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_delivery_exclude_category_and_product',
			'type'              => 'yith-field',
			'yith-type'         => 'onoff',
			'default'           => 'no',
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_delivery',
				'data-deps_value' => 'all_products',
			),
		),

		// All products. Exclude categories.
		array(
			'name'              => esc_html_x( 'Categories to exclude', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html_x( 'Choose the categories to exclude from delivery schedule for recurring payments.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_delivery_exclude_categories_all_products',
			'type'              => 'yith-field',
			'yith-type'         => 'show-categories',
			'placeholder'       => __( 'Search category to exclude', 'yith-woocommerce-subscription' ),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_delivery,ywsbs_delivery_exclude_category_and_product',
				'data-deps_value' => 'all_products,yes',
			),
		),

		// All products. Exclude products.
		array(
			'name'              => esc_html_x( 'Products to exclude', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html_x( 'Choose the products to exclude from delivery schedule for recurring payments.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_delivery_exclude_products_all_products',
			'type'              => 'yith-field',
			'yith-type'         => 'ywsbs-products',
			'placeholder'       => __( 'Search product to exclude', 'yith-woocommerce-subscription' ),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_delivery,ywsbs_delivery_exclude_category_and_product',
				'data-deps_value' => 'all_products,yes',
			),
		),

		// Physical products. Enable exclude products.
		array(
			'name'              => esc_html__( 'Exclude products', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html__( 'Enable if you want to exclude products.', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_delivery_exclude_category_and_product_non_virtual',
			'type'              => 'yith-field',
			'yith-type'         => 'onoff',
			'default'           => 'no',
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_delivery',
				'data-deps_value' => 'physical',
			),
		),

		// Physical products. Exclude categories.
		array(
			'name'              => esc_html_x( 'Categories to exclude', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html_x( 'Choose the categories to exclude from delivery schedule for recurring payments.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_delivery_exclude_categories_physical',
			'type'              => 'yith-field',
			'yith-type'         => 'show-categories',
			'placeholder'       => __( 'Search category to exclude', 'yith-woocommerce-subscription' ),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_delivery,ywsbs_delivery_exclude_category_and_product_non_virtual',
				'data-deps_value' => 'physical,yes',
			),
		),

		// Physical products. Exclude products.
		array(
			'name'              => esc_html_x( 'Products to exclude', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html_x( 'Choose the products to exclude from delivery schedule for recurring payments.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_delivery_exclude_products_physical',
			'type'              => 'yith-field',
			'yith-type'         => 'ywsbs-products',
			'placeholder'       => __( 'Search product to exclude', 'yith-woocommerce-subscription' ),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_delivery,ywsbs_delivery_exclude_category_and_product_non_virtual',
				'data-deps_value' => 'physical,yes',
			),
		),


		// Products.
		array(
			'name'              => esc_html_x( 'Products to include', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html_x( 'Choose the products that allow a specific delivery schedule to be set.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_delivery_include_product',
			'type'              => 'yith-field',
			'yith-type'         => 'ywsbs-products',
			'placeholder'       => __( 'Search products to include', 'yith-woocommerce-subscription' ),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_delivery',
				'data-deps_value' => 'products',
			),
		),

		// Categories.
		array(
			'name'        => esc_html_x( 'Categories to include', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'        => esc_html_x( 'Choose the categories that allow a specific delivery schedule to be set.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'          => 'ywsbs_delivery_include_categories',
			'type'        => 'yith-field',
			'yith-type'   => 'show-categories',
			'placeholder' => __( 'Search category to include', 'yith-woocommerce-subscription' ),
			'deps'        => array(
				'id'     => 'ywsbs_enable_delivery',
				'values' => 'categories',
			),
		),

		array(
			'name'      => esc_html__( 'Exclude products', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable if you want to exclude products from the category list.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_delivery_include_categories_enable_exclude_products',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
			'deps'      => array(
				'id'     => 'ywsbs_enable_delivery',
				'values' => 'categories',
			),

		),

		array(
			'name'              => esc_html_x( 'Products to exclude', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html_x( 'Choose the products to exclude from delivery schedule for recurring payments.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_delivery_exclude_products_from_categories',
			'type'              => 'yith-field',
			'yith-type'         => 'ywsbs-products',
			'placeholder'       => __( 'Search product to exclude', 'yith-woocommerce-subscription' ),
			'default'           => array(),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_delivery,ywsbs_delivery_include_categories_enable_exclude_products',
				'data-deps_value' => 'categories,yes',
			),
		),

		array(
			'name'              => esc_html__( 'Default delivery schedule', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html__( 'Set a default delivery schedule. You can override this option and set a different schedule on the "Edit Product" page.', 'yith-woocommerce-subscription' ),
			'class'             => 'default_schedule1',
			'id'                => 'ywsbs_delivery_default_schedule',
			'type'              => 'yith-field',
			'yith-type'         => 'inline-fields',
			'fields'            => array(
				'html0'           => array(
					'type' => 'html',
					'html' => esc_html_x( 'Deliver the subscription products every', 'Part of an option text', 'yith-woocommerce-subscription' ),
				),
				'delivery_gap'    => array(
					'type'              => 'number',
					'std'               => 1,
					'custom_attributes' => 'style="width:40px"',
				),
				'delivery_period' => array(
					'type'              => 'select',
					'class'             => 'short-field',
					'custom_attributes' => 'style="width: 150px!important;"',
					'options'           => array(
						'days'   => esc_html__( 'Days', 'yith-woocommerce-subscription' ),
						'weeks'  => esc_html__( 'Weeks', 'yith-woocommerce-subscription' ),
						'months' => esc_html__( 'Months', 'yith-woocommerce-subscription' ),
						'years'  => esc_html__( 'Years', 'yith-woocommerce-subscription' ),
					),
					'std'               => 'months',
				),
			),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_delivery',
				'data-deps_value' => 'categories|products|physical|all_products',
			),
		),

		array(
			'name'              => esc_html__( 'Show delivery schedule info in product page', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html__( 'Enable if you want to show information about the delivery schedule on the product page.', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_delivery_show_product_info',
			'type'              => 'yith-field',
			'yith-type'         => 'onoff',
			'default'           => 'yes',
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_delivery',
				'data-deps_value' => 'all_products|virtual|products|categories',
			),
		),

		array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_section_delivery_settings_end',
		),

		array(
			'name' => esc_html__( 'Delivery synchronization', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_delivery_synchronization',
		),

		array(
			'name'              => esc_html__( 'Synchronize delivery schedules', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html__( 'Enable if you want to ship the product on a specific day.', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_delivery_sync_delivery_schedules',
			'type'              => 'yith-field',
			'yith-type'         => 'onoff',
			'default'           => 'no',
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_delivery_default_schedule[delivery_period]',
				'data-deps_value' => 'weeks|months|years',
			),
		),

		array(
			'name'              => esc_html__( 'Synchronize delivery on', 'yith-woocommerce-subscription' ),
			'desc'              => sprintf( '<div class="hide-if-days">%s</div>', esc_html__( 'Set a default delivery schedule synchronization. You can override this option on the edit product page.', 'yith-woocommerce-subscription' ) ),
			'class'             => 'without-padding',
			'id'                => 'ywsbs_delivery_default_schedule2',
			'type'              => 'yith-field',
			'yith-type'         => 'inline-fields',
			'fields'            => array(
				'sych_weeks'                => array(
					'type'              => 'select',
					'class'             => 'short-field show-if-weeks',
					'custom_attributes' => 'style="width: 150px!important;"',
					'options'           => ywsbs_get_period_options( 'day_weeks' ),
					'std'               => 'suspended',
				),
				'months'                    => array(
					'type'              => 'select',
					'class'             => 'short-field show-if-months',
					'custom_attributes' => 'style="width: 150px!important;"',
					'options'           => ywsbs_get_period_options( 'day_months' ),
					'std'               => 'suspended',
				),
				'delivery_sych_months_text' => array(
					'type'  => 'html',
					'class' => 'show-if-months',
					'html'  => esc_html_x( 'of each month', 'Part of an option text', 'yith-woocommerce-subscription' ),
				),
				'years_day'                 => array(
					'type'              => 'select',
					'class'             => 'short-field show-if-years',
					'custom_attributes' => 'style="width: 100px!important;"',
					'options'           => ywsbs_get_period_options( 'day_months' ),
					'std'               => 'suspended',
				),
				'years_month'               => array(
					'type'              => 'select',
					'class'             => 'short-field show-if-years',
					'custom_attributes' => 'style="width: 100px!important;"',
					'options'           => ywsbs_get_period_options( 'months' ),
					'std'               => 'suspended',
				),
			),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_delivery_default_schedule[delivery_period],ywsbs_delivery_sync_delivery_schedules',
				'data-deps_value' => 'weeks|months|years,yes',
			),
		),

		array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_section_delivery_synchronization_end',
		),
	),
);
