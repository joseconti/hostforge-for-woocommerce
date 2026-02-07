<?php
/**
 * Subscription module "synchronization" options array
 *
 * @since   3.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

return array(
	'synchronization' => array(

		array(
			'name' => esc_html__( 'Signup payment options', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_synch_sign_up',
		),

		array(
			'name'      => esc_html_x( 'First payment at signup options', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html_x( 'Choose how to manage the first recurring payment at signup of the subscription products that have a synchronized renewal day set.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_sync_first_payment',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				// translators:placeholders are html tags.
				'no'      => sprintf( esc_html_x( 'Don\'t charge the first recurring amount at signup (only a signup fee, if set). %1$sWhen you create a subscription product, you can choose the day on which renewals synchronize and charge the subscription payment to your users.%2$s', 'Admin option, the placeholder are tags', 'yith-woocommerce-subscription' ), '<small>', '</small>' ),
				// translators:placeholders are html tags.
				'prorate' => sprintf( esc_html_x( 'Charge a prorated payment and, therefore, when to charge the first recurring amount to your users.%1$sThe user will pay a part of the recurring amount, calculated automatically based on the days left till the renewal. Renewal day is set on the subscription product page.%2$s', 'Admin option, the placeholder are tags', 'yith-woocommerce-subscription' ), '<small>', '</small>' ),
				'full'    => esc_html_x( 'Charge the full recurring amount on signup', 'Admin option choice', 'yith-woocommerce-subscription' ),
			),
			'default'   => 'no',
		),

		array(
			'name'      => esc_html_x( 'Postpone the first payment, if the next payment is in less than:', 'Admin option title', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html_x( 'Use this option to avoid charging the user twice in quick succession if a subscription has been bought near a renewal date.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_sync_prorate_disabled',
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'fields'    => array(
				'number_of_days' => array(
					'type'              => 'number',
					'std'               => 30,
					'custom_attributes' => 'style="width:40px"',
				),
				'html'           => array(
					'type' => 'html',
					'html' => esc_html_x( 'days until the next renewal.', 'Admin option description', 'yith-woocommerce-subscription' ),
				),
			),
			'deps'      => array(
				'id'    => 'ywsbs_sync_first_payment',
				'value' => 'prorate',
			),
		),

		array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_synch_sign_up_end',
		),

		array(
			'name' => esc_html__( 'Renewal synchronization', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_synch_general',
		),

		array(
			'name'      => esc_html_x( 'Synchronize recurring payments for:', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html_x( 'Choose if you want to synchronize subscription payments for any or specific products or categories, to a specific day of the week, month, or year. For example, each Monday or the first day of each month. You can do it for all products or for specific products/categories.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_enable_sync',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				// translators:placeholders are html tags.
				'all_products' => sprintf( esc_html_x( 'All products %1$sYou will be able to exclude some products or categories if this option is selected.%2$s', 'Admin option, the placeholder are tags', 'yith-woocommerce-subscription' ), '<small>', '</small>' ),
				// translators:placeholders are html tags.
				'virtual'      => sprintf( esc_html_x( 'Only virtual products %1$sYou will be able to exclude some products or categories if this option is selected.%2$s', 'Admin option, the placeholder are tags', 'yith-woocommerce-subscription' ), '<small>', '</small>' ),
				'products'     => esc_html_x( 'Specific products', 'Admin option choice', 'yith-woocommerce-subscription' ),
				// translators:placeholders are html tags.
				'categories'   => sprintf( esc_html_x( 'Specific categories %1$sYou will be able to exclude some products if this option is selected.%2$s', 'Admin option, the placeholder are tags', 'yith-woocommerce-subscription' ), '<small>', '</small>' ),
			),
			'default'   => 'all_products',
		),

		array(
			'name'      => esc_html__( 'Exclude products', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable if you want to exclude products.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_sync_exclude_category_and_product',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_sync',
				'data-deps_value' => 'all_products',
			),
		),

		// All products. Exclude categories.
		array(
			'name'              => esc_html_x( 'Categories to exclude', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html_x( 'Choose the categories to exclude from recurring payments synchronization.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_sync_exclude_categories_all_products',
			'type'              => 'yith-field',
			'yith-type'         => 'show-categories',
			'placeholder'       => __( 'Search category to exclude', 'yith-woocommerce-subscription' ),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_sync,ywsbs_sync_exclude_category_and_product',
				'data-deps_value' => 'all_products,yes',
			),
		),

		// All products. Exclude products.
		array(
			'name'              => esc_html_x( 'Products to exclude', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html_x( 'Choose the products to exclude from recurring payments synchronization.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_sync_exclude_products_all_products',
			'type'              => 'yith-field',
			'yith-type'         => 'ywsbs-products',
			'placeholder'       => __( 'Search product to exclude', 'yith-woocommerce-subscription' ),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_sync,ywsbs_sync_exclude_category_and_product',
				'data-deps_value' => 'all_products,yes',
			),
		),

		// Virtual products. Exclude categories.
		array(
			'name'              => esc_html__( 'Exclude products', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html__( 'Enable if you want to exclude products.', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_sync_exclude_category_and_product_virtual',
			'type'              => 'yith-field',
			'yith-type'         => 'onoff',
			'default'           => 'yes',
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_sync',
				'data-deps_value' => 'virtual',
			),

		),

		// Virtual products. Exclude categories.
		array(
			'name'              => esc_html_x( 'Categories to exclude', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html_x( 'Choose the products to exclude from recurring payments synchronization.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_sync_exclude_categories_virtual',
			'type'              => 'yith-field',
			'yith-type'         => 'show-categories',
			'placeholder'       => __( 'Search category to exclude', 'yith-woocommerce-subscription' ),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_sync,ywsbs_sync_exclude_category_and_product_virtual',
				'data-deps_value' => 'virtual,yes',
			),
		),

		// Virtual products. Exclude products.
		array(
			'name'              => esc_html_x( 'Products to exclude', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html_x( 'Choose the products to exclude from recurring payments synchronization.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_sync_exclude_products_virtual',
			'type'              => 'yith-field',
			'yith-type'         => 'ywsbs-products',
			'placeholder'       => __( 'Search product to exclude', 'yith-woocommerce-subscription' ),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_sync,ywsbs_sync_exclude_category_and_product_virtual',
				'data-deps_value' => 'virtual,yes',
			),
		),

		// Products.
		array(
			'name'              => esc_html_x( 'Products to include', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html_x( 'Choose the products that allow a specific renewal date to be set for recurring payments.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_sync_include_product',
			'type'              => 'yith-field',
			'yith-type'         => 'ywsbs-products',
			'placeholder'       => __( 'Search products to include', 'yith-woocommerce-subscription' ),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_sync',
				'data-deps_value' => 'products',
			),
		),

		// Categories.
		array(
			'name'        => esc_html_x( 'Categories to include', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'        => esc_html_x( 'Choose the categories that allow a specific renewal date to be set for recurring payments.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'          => 'ywsbs_sync_include_categories',
			'type'        => 'yith-field',
			'yith-type'   => 'show-categories',
			'placeholder' => __( 'Search category to include', 'yith-woocommerce-subscription' ),
			'deps'        => array(
				'id'     => 'ywsbs_enable_sync',
				'values' => 'categories',
			),
		),

		array(
			'name'      => esc_html__( 'Exclude products', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable if you want to exclude products from the category list.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_sync_include_categories_enable_exclude_products',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
			'deps'      => array(
				'id'     => 'ywsbs_enable_sync',
				'values' => 'categories',
			),

		),

		array(
			'name'              => esc_html_x( 'Products to exclude', 'Admin option', 'yith-woocommerce-subscription' ),
			'desc'              => esc_html_x( 'Choose the products to exclude from recurring payments synchronization.', 'Admin option description', 'yith-woocommerce-subscription' ),
			'id'                => 'ywsbs_sync_exclude_products_from_categories',
			'type'              => 'yith-field',
			'yith-type'         => 'ywsbs-products',
			'placeholder'       => __( 'Search product to exclude', 'yith-woocommerce-subscription' ),
			'default'           => array(),
			'custom_attributes' => array(
				'data-deps'       => 'ywsbs_enable_sync,ywsbs_sync_include_categories_enable_exclude_products',
				'data-deps_value' => 'categories,yes',
			),
		),

		array(
			'name'      => esc_html__( 'Display recurring payments info on the product page', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable if you want to show the information about the recurring payments to the customer on the product page.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_sync_show_product_info',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
			'deps'      => array(
				'id'    => 'ywsbs_sync_first_payment',
				'value' => 'prorate',
			),
		),

		array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_synch_general_end',
		),
	),
);
