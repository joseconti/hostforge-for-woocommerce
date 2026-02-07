<?php
/**
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @since   1.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

$settings = array(
	'general' => array(

		// >>>>>>>>>>>>>>>>> General Settings.

		'section_general_settings' => array(
			'name' => esc_html__( 'General settings', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_section_general',
		),

		'enable_subscriptions_multiple' => array(
			'name'      => esc_html__( 'User can add to cart', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose if a user can add only one or more subscription products to the cart.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_enable_subscriptions_multiple',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'no'  => esc_html__( 'Only one subscription product', 'yith-woocommerce-subscription' ),
				'yes' => esc_html__( 'Unlimited subscription products', 'yith-woocommerce-subscription' ),
			),
			'default'   => 'yes',
		),

		'enable_manual_renews_gateways' => array(
			'name'      => esc_html__( 'Allow user to manually renew a subscription', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose whether a user can renew a subscription if the payment gateway does not support automatic payments.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_enable_manual_renews',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'yes' => esc_html__( 'Yes, the customer will be able to pay the renewal order on My Account page, if the payment gateway does not support automatic payments.', 'yith-woocommerce-subscription' ),
				'no'  => esc_html__( 'No, only use the supported gateways enabled for automatic payments.', 'yith-woocommerce-subscription' ),
			),
			'default'   => 'yes',
		),

		'disable_the_reduction_of_order_stock_in_renew' => array(
			'name'      => esc_html__( 'Stock management with recurring payments', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose if the recurring payments will reduce the stock count of a subscription product.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_disable_the_reduction_of_order_stock_in_renew',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'no'  => esc_html__( 'Reduce stock of subscription products', 'yith-woocommerce-subscription' ),
				'yes' => esc_html__( 'Do not reduce stock of subscription products', 'yith-woocommerce-subscription' ),
			),
			'default'   => 'no',
		),

		'force_account_with_subscription' => array(
			'name'      => esc_html__( 'Force account registration when purchasing a subscription', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable to force the customer to create an account when purchasing a subscription product.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_force_account_with_subscription',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),

		'change_status_after_renew_order_creation' => array(
			'name'      => esc_html__( ' If a recurring payment is not paid', 'yith-woocommerce-subscription' ),
			'desc'      => sprintf( '<div class="hide-overdue">%s</div>', esc_html__( 'Choose how to manage the subscription when a recurring payment is not paid.', 'yith-woocommerce-subscription' ) ),
			'class'     => 'renew_order_step1',
			'id'        => 'ywsbs_change_status_after_renew_order_creation',
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'fields'    => array(
				'html0'    => array(
					'type' => 'html',
					'html' => esc_html__( 'after', 'yith-woocommerce-subscription' ),
				),
				'wait_for' => array(
					'type'              => 'number',
					'std'               => 48,
					'custom_attributes' => 'style="width:40px"',
				),
				'html'     => array(
					'type' => 'html',
					'html' => esc_html__( 'hours, put the subscription status in', 'yith-woocommerce-subscription' ),
				),
				'status'   => array(
					'type'              => 'select',
					'class'             => 'short-field',
					'custom_attributes' => 'style="width: 150px!important;"',
					'options'           => array(
						'overdue'   => esc_html__( 'Overdue', 'yith-woocommerce-subscription' ),
						'suspended' => esc_html__( 'Suspended', 'yith-woocommerce-subscription' ),
						'cancelled' => esc_html__( 'Cancelled', 'yith-woocommerce-subscription' ),
					),
					'std'               => 'suspended',
				),
				'break'    => array(
					'type'  => 'html',
					'class' => 'flex-break',
					'html'  => '',
				),
				'html3'    => array(
					'type'  => 'html',
					'class' => 'show-if-overdue show-if-suspended',
					'html'  => esc_html__( 'for', 'yith-woocommerce-subscription' ),
				),
				'length'   => array(
					'type'              => 'number',
					'class'             => 'show-if-overdue show-if-suspended ',
					'std'               => 20,
					'custom_attributes' => 'style="width:40px"',
				),
				'html4'    => array(
					'type'  => 'html',
					'class' => 'show-if-overdue',
					'html'  => esc_html__( 'days.', 'yith-woocommerce-subscription' ),
				),
				'html5'    => array(
					'type'  => 'html',
					'class' => 'show-if-suspended',
					'html'  => esc_html__( 'days before cancelling it.', 'yith-woocommerce-subscription' ),
				),
			),
		),

		'change_status_after_renew_order_creation_step_2' => array(
			'name'      => '',
			'id'        => 'ywsbs_change_status_after_renew_order_creation_step_2',
			'desc'      => esc_html__( 'Choose how to manage the subscription when a recurring payment is not paid.', 'yith-woocommerce-subscription' ),
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'class'     => 'without-padding',
			'fields'    => array(
				'html'   => array(
					'type' => 'html',
					'html' => esc_html__( 'After that, put it as', 'yith-woocommerce-subscription' ),
				),
				'status' => array(
					'type'              => 'select',
					'class'             => 'short-field',
					'custom_attributes' => 'style="width: 150px!important;"',
					'options'           => array(
						'suspended' => esc_html__( 'Suspended', 'yith-woocommerce-subscription' ),
						'cancelled' => esc_html__( 'Cancelled', 'yith-woocommerce-subscription' ),
					),
				),
				'html2'  => array(
					'type'  => 'html',
					'class' => 'show-if-no-cancelled-step-2',
					'html'  => esc_html__( 'for', 'yith-woocommerce-subscription' ),
				),

				'length' => array(
					'type'              => 'number',
					'std'               => 15,
					'class'             => 'show-if-no-cancelled-step-2',
					'custom_attributes' => 'style="width:40px"',
				),

				'html3' => array(
					'type'  => 'html',
					'class' => 'show-if-no-cancelled-step-2',
					'html'  => esc_html__( 'days before cancelling the subscription.', 'yith-woocommerce-subscription' ),
				),
			),
			'deps'      => array(
				'id'    => 'ywsbs_change_status_after_renew_order_creation_status',
				'value' => 'overdue',
			),
		),

		'delete_subscription_order_cancelled' => array(
			'name'      => esc_html__( 'Delete subscription if the main order is cancelled', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable if you want to delete a subscription when the main order is cancelled.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_delete_subscription_order_cancelled',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),

		'section_end_form' => array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_section_general_end_form',
		),

		'section_user_permissions' => array(
			'name' => esc_html__( 'User permissions', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_section_user_permissions',
		),

		'allow_users_to_pause_subscriptions' => array(
			'name'      => esc_html__( 'Allow the user to pause subscriptions', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose if a user can pause the subscription. And, if so, to do it with or without limits.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_allow_users_to_pause_subscriptions',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'no'      => esc_html__( 'No, never', 'yith-woocommerce-subscription' ),
				'yes'     => esc_html__( 'Yes, user can pause without limits', 'yith-woocommerce-subscription' ),
				'limited' => esc_html__( 'Yes, user can pause with certain limits', 'yith-woocommerce-subscription' ),
			),
			'default'   => 'no',
		),

		'max_pause' => array(
			'name'      => esc_html__( 'Subscription pausing limits', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_max_pause',
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'class'     => 'without-bottom-padding',
			'fields'    => array(
				'html'  => array(
					'type' => 'html',
					'html' => esc_html__( 'The user can pause a subscription a maximum of', 'yith-woocommerce-subscription' ),
				),
				'value' => array(
					'type'              => 'number',
					'std'               => 2,
					'custom_attributes' => 'style="width:40px"',
				),
				'html2' => array(
					'type' => 'html',
					'html' => esc_html__( 'times.', 'yith-woocommerce-subscription' ),
				),
			),
			'deps'      => array(
				'id'    => 'ywsbs_allow_users_to_pause_subscriptions',
				'value' => 'limited',
			),
		),

		'max_pause_duration' => array(
			'name'      => '',
			'id'        => 'ywsbs_max_pause_duration',
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'class'     => 'without-padding',
			'fields'    => array(
				'html'  => array(
					'type' => 'html',
					'html' => esc_html__( 'Each pause can last a maximum of', 'yith-woocommerce-subscription' ),
				),
				'value' => array(
					'type'              => 'number',
					'std'               => 30,
					'custom_attributes' => 'style="width:40px"',
				),
				'html2' => array(
					'type' => 'html',
					'html' => esc_html__( 'days.', 'yith-woocommerce-subscription' ),
				),
				'break'    => array(
					'type'  => 'html',
					'class' => 'flex-break',
					'html'  => '',
				),
				'html3' => array(
					'type' => 'html',
					'html' => esc_html__( 'After which, the subscription will reactivate automatically.', 'yith-woocommerce-subscription' ),
				),
			),
			'deps'      => array(
				'id'    => 'ywsbs_allow_users_to_pause_subscriptions',
				'value' => 'limited',
			),
		),

		'allow_users_to_cancel_subscriptions' => array(
			'name'      => esc_html__( 'Allow the user to cancel subscriptions', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose if a user can cancel this subscription. And, if so, to do it with or without limits. This option can be overridden in each subscription product edit page.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_allow_customer_cancel_subscription',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'no'      => esc_html__( 'No, never', 'yith-woocommerce-subscription' ),
				'yes'     => esc_html__( 'Yes, user can cancel without limits', 'yith-woocommerce-subscription' ),
				'limited' => esc_html__( 'Yes, user can cancel with certain limits', 'yith-woocommerce-subscription' ),
			),
			'default'   => 'yes',
		),

		'cancel_subscriptions_after_creation' => array(
			'name'      => esc_html__( 'Subscription cancel limits', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_cancel_subscription_limit_after_creation',
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'class'     => 'without-bottom-padding',
			'fields'    => array(
				'html'  => array(
					'type' => 'html',
					'html' => esc_html__( 'The user can cancel a subscription', 'yith-woocommerce-subscription' ),
				),
				'days'  => array(
					'type'              => 'number',
					'std'               => 0,
					'min'               => 0,
					'custom_attributes' => 'style="width:40px"',
				),
				'html2' => array(
					'type' => 'html',
					'html' => esc_html__( 'day(s) after creation.', 'yith-woocommerce-subscription' ),
				),
			),
			'deps'      => array(
				'id'    => 'ywsbs_allow_customer_cancel_subscription',
				'value' => 'limited',
			),
		),

		'cancel_subscriptions_before_renew' => array(
			'name'      => '',
			'id'        => 'ywsbs_cancel_subscription_limit_before_renew',
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'class'     => 'without-padding',
			'fields'    => array(
				'html'  => array(
					'type' => 'html',
					'html' => esc_html__( 'The user can cancel a subscription within', 'yith-woocommerce-subscription' ),
				),
				'days'  => array(
					'type'              => 'number',
					'std'               => 0,
					'min'               => 0,
					'custom_attributes' => 'style="width:40px"',
				),
				'html2' => array(
					'type' => 'html',
					'html' => esc_html__( 'day(s) before renew date.', 'yith-woocommerce-subscription' ),
				),
			),
			'deps'      => array(
				'id'    => 'ywsbs_allow_customer_cancel_subscription',
				'value' => 'limited',
			),
		),

		'section_user_permissions_end' => array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_section_user_permissions_end',
		),

		// >>>>>>>>>>>>>>>>> Extra settings.

		'section_extra_settings' => array(
			'name' => esc_html__( 'Extra settings', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_section_extra',
		),

		'enable_shop_manager' => array(
			'name'      => esc_html__( 'Shop manager can control subscription settings', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable to allow the shop manager to access and edit the plugin options.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_enable_shop_manager',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),

		'site_staging' => array(
			'name'      => esc_html__( 'Staging mode', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable if you want to use this installation as a test site and avoid generating duplicate orders.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_site_staging',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),


		'enable_log' => array(
			'name'      => esc_html__( 'Enable log', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable to generate a list of plugin actions. Note: This is a useful option to develop improvements and to provide support.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_enable_log',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),

		'section_extra_end_form' => array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_section_extra_end_form',
		),

		// >>>>>>>>>>>>>>>>> GDPR

		'privacy_settings' => array(
			'name' => esc_html__( 'GPDR & Privacy', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_privacy_settings',
		),

		'erasure_request' => array(
			'name'      => esc_html__( 'Delete personal information after account deletion requests', 'yith-woocommerce-subscription' ),
			'desc'      => sprintf( '%s <br> %s', esc_html__( 'Enable to remove the personal information of a subscription when a request for account deletion is made.', 'yith-woocommerce-subscription' ), esc_html__( 'Note: all affected subscription status\' will be changed to "Cancelled".', 'yith-woocommerce-subscription' ) ),
			'id'        => 'ywsbs_erasure_request',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),

		'delete_unused_subscription' => array(
			'name'      => esc_html__( 'Delete pending and cancelled subscriptions', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose if pending and/or cancelled subscriptions can be deleted after a specified duration.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_delete_personal_info',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),

		'trash_pending_subscriptions' => array(
			'title'     => esc_html__( 'Delete pending subscriptions after', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose when to delete pending subscriptions.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_trash_pending_subscriptions',
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'fields'    => array(
				'number' => array(
					'type'              => 'number',
					'class'             => 'short-field',
					'custom_attributes' => 'style="width:100px"',
				),
				'unit'   => array(
					'type'              => 'select',
					'class'             => 'short-field',
					'custom_attributes' => 'style="width: 150px!important;"',
					'options'           => array(
						'days'   => esc_html__( 'days', 'yith-woocommerce-subscription' ),
						'weeks'  => esc_html__( 'weeks', 'yith-woocommerce-subscription' ),
						'months' => esc_html__( 'months', 'yith-woocommerce-subscription' ),
						'years'  => esc_html__( 'years', 'yith-woocommerce-subscription' ),
					),
				),
			),
			'deps'      => array(
				'id'    => 'ywsbs_delete_personal_info',
				'value' => 'yes',
			),
		),

		'trash_cancelled_subscriptions' => array(
			'title'     => esc_html__( 'Delete cancelled subscriptions after', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose when to delete cancelled subscriptions.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_trash_cancelled_subscriptions',
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'fields'    => array(
				'number' => array(
					'type'              => 'number',
					'custom_attributes' => 'style="width:100px"',
				),
				'unit'   => array(
					'type'              => 'select',
					'class'             => 'short-field',
					'custom_attributes' => 'style="width: 150px!important;"',
					'options'           => array(
						'days'   => esc_html__( 'days', 'yith-woocommerce-subscription' ),
						'weeks'  => esc_html__( 'weeks', 'yith-woocommerce-subscription' ),
						'months' => esc_html__( 'months', 'yith-woocommerce-subscription' ),
						'years'  => esc_html__( 'years', 'yith-woocommerce-subscription' ),
					),
				),
			),
			'deps'      => array(
				'id'    => 'ywsbs_delete_personal_info',
				'value' => 'yes',
			),
		),

		'section_end_privacy_settings' => array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_section_end_privacy_settings',
		),
	),
);

return apply_filters( 'yith_ywsbs_panel_settings_options', $settings );
