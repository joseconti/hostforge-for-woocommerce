<?php

/**
 * General Settings.
 * 
 * @class SUMOSubs_Admin_Settings_General
 */
class SUMOSubs_Admin_Settings_General extends SUMOSubs_Abstract_Admin_Settings {

    /**
     * SUMOSubs_Admin_Settings_General constructor.
     */
    public function __construct() {
        $this->id            = 'general';
        $this->label         = __( 'General', 'sumosubscriptions' );
        $this->custom_fields = array(
            'get_shortcodes',
        );
        $this->settings      = $this->get_settings();
        $this->init();
    }

    /**
     * Get settings array.
     *
     * @return array
     */
    public function get_settings() {
        global $current_section;
        $wp_user_roles = sumosubs_get_user_roles();

        /**
         * Get the admin settings.
         * 
         * @since 1.0
         */
        return apply_filters( 'sumosubscriptions_get_' . $this->id . '_settings', array(
            array( 'type' => $this->get_custom_field_type( 'get_shortcodes' ) ),
            array(
                'name' => __( 'Button Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'button_section',
            ),
            array(
                'name'     => __( 'Add to Cart Button Label', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'subscribe_text' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'subscribe_text' ),
                'type'     => 'text',
                'desc_tip' => true,
                'desc'     => __( 'It changes the "Add to Cart" button label for subscription products.', 'sumosubscriptions' ),
            ),
            array( 'type' => 'sectionend', 'id' => 'button_section' ),
            array(
                'name' => __( 'Renewal Order Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'renewals_section',
            ),
            array(
                'name'              => __( 'Create Renewal Order', 'sumosubscriptions' ),
                'id'                => SUMOSubs_Admin_Options::prepare_option_name( 'renewal_order_creation_schedule' ),
                'default'           => SUMOSubs_Admin_Options::get_option_default( 'renewal_order_creation_schedule' ),
                'type'              => 'number',
                'desc'              => __( 'day(s) before due date', 'sumosubscriptions' ),
                'desc_tip'          => __( 'This option controls when the renewal order for the subscription needs to be created.', 'sumosubscriptions' ),
                'custom_attributes' => array(
                    'min'      => 0,
                    'required' => 'required',
                ),
            ),
            array(
                'name'    => __( 'Charge Shipping Cost During Renewals', 'sumosubscriptions' ),
                'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'charge_shipping_during_renewals' ),
                'default' => SUMOSubs_Admin_Options::get_option_default( 'charge_shipping_during_renewals' ),
                'type'    => 'checkbox',
                'desc'    => __( 'If enabled, shipping cost from the parent order will be added to the renewal order.', 'sumosubscriptions' ),
            ),
            array(
                'name'    => __( 'Charge Tax Cost During Renewals', 'sumosubscriptions' ),
                'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'charge_tax_during_renewals' ),
                'default' => SUMOSubs_Admin_Options::get_option_default( 'charge_tax_during_renewals' ),
                'type'    => 'checkbox',
                'desc'    => __( 'If enabled, tax cost from the parent order will be added to the renewal order.', 'sumosubscriptions' ),
            ),
            array( 'type' => 'sectionend', 'id' => 'renewals_section' ),
            array(
                'name' => __( 'Subscription User Role Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'subscriber_user_role_section',
            ),
            array(
                'name'     => __( 'Active User Role(s)', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'active_user_role' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'active_user_role' ),
                'class'    => 'wc-enhanced-select',
                'type'     => 'select',
                'desc'     => __( 'role(s)', 'sumosubscriptions' ),
                'desc_tip' => __( 'Active subscriber user role.', 'sumosubscriptions' ),
                'options'  => $wp_user_roles,
            ),
            array(
                'name'     => __( 'Inactive User Role(s)', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'inactive_user_role' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'inactive_user_role' ),
                'class'    => 'wc-enhanced-select',
                'type'     => 'select',
                'desc'     => __( 'role(s)', 'sumosubscriptions' ),
                'desc_tip' => __( 'Inctive subscriber user role.', 'sumosubscriptions' ),
                'options'  => $wp_user_roles,
            ),
            array( 'type' => 'sectionend', 'id' => 'subscriber_user_role_section' ),
            array(
                'name' => __( 'Overdue and Suspend Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'grace_schedules_section',
            ),
            array(
                'name'              => __( 'Overdue Period', 'sumosubscriptions' ),
                'id'                => SUMOSubs_Admin_Options::prepare_option_name( 'overdue_schedule' ),
                'default'           => SUMOSubs_Admin_Options::get_option_default( 'overdue_schedule' ),
                'type'              => 'number',
                'desc'              => __( 'day(s)', 'sumosubscriptions' ),
                'desc_tip'          => __( 'This option controls how long the subscription needs to be in "Overdue" status until the subscriber pays for the renewal or else it was unable to charge for the renewal automatically in case of automatic renewals. For example, if it is set as 2 then, the subscription will be in "Overdue" status for 2 days from the subscription due date. During overdue period, subscriber still have access to their subscription.', 'sumosubscriptions' ),
                'custom_attributes' => array(
                    'min'      => 0,
                    'required' => 'required',
                ),
            ),
            array(
                'name'              => __( 'Suspend Period', 'sumosubscriptions' ),
                'id'                => SUMOSubs_Admin_Options::prepare_option_name( 'suspend_schedule' ),
                'default'           => SUMOSubs_Admin_Options::get_option_default( 'suspend_schedule' ),
                'type'              => 'number',
                'desc'              => __( 'day(s)', 'sumosubscriptions' ),
                'desc_tip'          => __( 'This option controls how long the subscription needs to be in "Suspended" status after the overdue period until the subscriber pays for the renewal or else it was unable to charge for the renewal automatically in case of automatic renewals. For example, if it is set as 2 then, the subscription will be in "Suspended" status for 2 days from the overdue period. During suspend period, subscriber is not allowed to access their subscription.', 'sumosubscriptions' ),
                'custom_attributes' => array(
                    'min'      => 0,
                    'required' => 'required',
                ),
            ),
            array( 'type' => 'sectionend', 'id' => 'grace_schedules_section' ),
            array(
                'name' => __( 'Email Notification Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'email_notifications_section',
            ),
            array(
                'name'     => __( 'Send Manual Payment Reminder Email', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'manual_renewal_due_reminder_schedule' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'manual_renewal_due_reminder_schedule' ),
                'type'     => 'text',
                'desc'     => __( 'day(s) before due date', 'sumosubscriptions' ),
                'desc_tip' => __( 'This option controls when the renewal reminder email for manual subscriptions needs to be sent. For example, if set as 3,2,1 then, the first reminder email will be sent 3 days before due date, second email before 2 days and third email  1 day before due date. If set 0 or else left empty, reminder email will be sent on the subscription renewal date. Note: Reminder emails will be sent only after renewal order is created.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Send Automatic Payment Reminder Email', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'auto_renewal_due_reminder_schedule' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'auto_renewal_due_reminder_schedule' ),
                'type'     => 'text',
                'desc'     => __( 'day(s) before due date', 'sumosubscriptions' ),
                'desc_tip' => __( 'This option controls when the renewal reminder email for automatic subscriptions needs to be sent. For example, if set as 3,2,1 then, the first reminder email will be sent 3 days before due date, second email before 2 days and third email  1 day before due date. If set 0 or else left empty, reminder email will be sent on the subscription renewal date. Note: Reminder emails will be sent only after renewal order is created.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Send Overdue Reminder Email', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'overdue_reminder_schedule' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'overdue_reminder_schedule' ),
                'type'     => 'text',
                'desc'     => __( 'day(s) after due date', 'sumosubscriptions' ),
                'desc_tip' => __( 'As soon as the subscription status becomes "Overdue", a reminder email will be sent immediately. In addition to that if you want to sent additional overdue reminder emails, then you can set on what days after the subscription becomes overdue, you want to sent the emails. For example, if set as 1,2,3 then, the first reminder email will be sent 1 day after overdue date, second email after 2 days and third email after 3 days of overdue date. If set 0 or else left empty, no additional overdue reminder emails will be sent.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Send Suspend Reminder Email', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'suspend_reminder_schedule' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'suspend_reminder_schedule' ),
                'type'     => 'text',
                'desc'     => __( 'day(s) after due date', 'sumosubscriptions' ),
                'desc_tip' => __( 'As soon as the subscription status becomes "Suspended", a reminder email will be sent immediately. In addition to that if you want to sent additional suspended reminder emails, then you can set on what days after the subscription becomes suspended, you want to sent the emails. For example, if set as 1,2,3 then, the first reminder email will be sent 1 day after suspended date, second email after 2 days and third email after 3 days of suspended date. If set 0 or else left empty, no additional suspended reminder emails will be sent.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Send Expiry Reminder Email', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'expiration_reminder_schedule' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'expiration_reminder_schedule' ),
                'type'     => 'text',
                'desc'     => __( 'day(s) before expiry date', 'sumosubscriptions' ),
                'desc_tip' => __( 'This option controls when the expiry reminder emailneeds to be sent. For example, if set as 3,2,1 then, the first reminder email will be sent 3 days before expiry date, second email before 2 days and third email 1 day before expiry date. If set 0 or else left empty, reminder email will be sent on expiry date. Note: Expiry reminder emails won’t be sent for the subscriptions without an expiry date.', 'sumosubscriptions' ),
            ),
            array( 'type' => 'sectionend', 'id' => 'email_notifications_section' ),
            array(
                'name' => __( 'Restriction Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'restrictions_section',
            ),
            array(
                'name'    => __( 'Mixed Checkout', 'sumosubscriptions' ),
                'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_mixed_checkout' ),
                'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_mixed_checkout' ),
                'type'    => 'checkbox',
                'desc'    => __( 'Purchase non-subscription products along with subscription products in same order', 'sumosubscriptions' ),
            ),
            array(
                'name'    => __( 'Cancel Subscription if Product is Out of Stock', 'sumosubscriptions' ),
                'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'restrict_outofstock_product' ),
                'default' => SUMOSubs_Admin_Options::get_option_default( 'restrict_outofstock_product' ),
                'type'    => 'checkbox',
                'desc'    => __( 'If enabled, subscription will be cancelled automatically on the due date if stock is not available on the due date. If subscribers tries to make the payment before due date, then they won\'t be able to place the order.', 'sumosubscriptions' ),
            ),
            array(
                'name'    => __( 'Restrict Multiple Subscriptions in Single Checkout', 'sumosubscriptions' ),
                'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'restrict_multiple_subscriptions_in_checkout' ),
                'default' => SUMOSubs_Admin_Options::get_option_default( 'restrict_multiple_subscriptions_in_checkout' ),
                'type'    => 'checkbox',
                'desc'    => __( 'If enabled, purchasing multiple subscription products in single checkout will be restricted', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Limit Subscription for Each Subscriber', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'limit_subscription_by' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'limit_subscription_by' ),
                'type'     => 'select',
                'options'  => array(
                    'no-limit'     => __( 'No limit', 'sumosubscriptions' ),
                    'product-wide' => __( 'One active subscription per product', 'sumosubscriptions' ),
                    'site-wide'    => __( 'One active subscription throughout the site', 'sumosubscriptions' ),
                ),
                'desc'     => __( 'This option controls the number of subscriptions each subscriber can purchase in the site. If the subscription status becomes Cancelled, Failed, Expired or the subscription is Trashed by admin then, the limit will be relaxed and they can purchase another subscription.', 'sumosubscriptions' ),
                'desc_tip' => true,
            ),
            array(
                'name'     => __( 'Limit Trial for Each Subscriber', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'limit_trial_by' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'limit_trial_by' ),
                'type'     => 'select',
                'options'  => array(
                    'no-limit'     => __( 'No limit', 'sumosubscriptions' ),
                    'product-wide' => __( 'One trial per product', 'sumosubscriptions' ),
                    'site-wide'    => __( 'One trial throughout the site', 'sumosubscriptions' ),
                ),
                'desc'     => __( 'This option controls the number of trials each subscriber can obtain on the site.', 'sumosubscriptions' ),
                'desc_tip' => true,
            ),
            array(
                'name'     => __( 'Limit Variable Subscription Products at', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'limit_variable_subscription_at' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'limit_variable_subscription_at' ),
                'type'     => 'select',
                'options'  => array(
                    'variant'  => __( 'Variant level', 'sumosubscriptions' ),
                    'variable' => __( 'Product level', 'sumosubscriptions' ),
                ),
                'desc'     => __( 'If "Variant level" is selected, subscription and trial will be limited from variant level for variable subscription products. If "Product level" is selected, subscription and trial will be limited from product level which means user cannot purchase all the variations of the variable subscription product.', 'sumosubscriptions' ),
                'desc_tip' => true,
            ),
            array( 'type' => 'sectionend', 'id' => 'restrictions_section' ),
            array(
                'name' => __( 'Subscription Payment Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'payments_section',
            ),
            array(
                'name'    => __( 'Enable Manual Payment Mode', 'sumosubscriptions' ),
                'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'accept_manual_payments' ),
                'default' => SUMOSubs_Admin_Options::get_option_default( 'accept_manual_payments' ),
                'type'    => 'checkbox',
                'desc'    => __( 'If enabled, manual payment methods will be displayed in checkout page for subscription orders', 'sumosubscriptions' ),
            ),
            array(
                'name'    => __( 'Disable Automatic Payment Mode', 'sumosubscriptions' ),
                'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'disable_auto_payments' ),
                'default' => SUMOSubs_Admin_Options::get_option_default( 'disable_auto_payments' ),
                'type'    => 'checkbox',
                'desc'    => __( 'If enabled, automatic payment methods will be hidden in checkout page for subscription orders', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Payment Mode for Automatic Supported Payment Methods', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'subscription_payment_gateway_mode' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'subscription_payment_gateway_mode' ),
                'type'     => 'select',
                'options'  => array(
                    'auto-r-manual' => __( 'Allow customer to choose automatic/manual', 'sumosubscriptions' ),
                    'force-auto'    => __( 'Force automatic', 'sumosubscriptions' ),
                    'force-manual'  => __( 'Force manual', 'sumosubscriptions' ),
                ),
                'desc'     => __( 'This option controls in which payment mode payment methods which are supported for automatic mode should work', 'sumosubscriptions' ),
                'desc_tip' => true,
            ),
            array(
                'name'    => __( 'Show Automatic Payment Methods when Order Amount is 0', 'sumosubscriptions' ),
                'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'show_subscription_payment_gateways_when_order_amt_0' ),
                'default' => SUMOSubs_Admin_Options::get_option_default( 'show_subscription_payment_gateways_when_order_amt_0' ),
                'type'    => 'checkbox',
            ),
            array(
                'name'     => __( 'Automatic Payment Retry in Overdue Status', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'payment_retries_in_overdue' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'payment_retries_in_overdue' ),
                'type'     => 'number',
                'desc'     => __( 'times per day', 'sumosubscriptions' ),
                'desc_tip' => __( 'This option controls the number of times automatic payment retry will happen when the subscription is in overdue status in case of payment failure in automatic payment mode.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Automatic Payment Retry in Suspended Status', 'sumosubscriptions' ),
                'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'payment_retries_in_suspend' ),
                'default'  => SUMOSubs_Admin_Options::get_option_default( 'payment_retries_in_suspend' ),
                'type'     => 'number',
                'desc'     => __( 'times per day', 'sumosubscriptions' ),
                'desc_tip' => __( 'This option controls the number of times automatic payment retry will happen when the subscription is in suspended status in case of payment failure in automatic payment mode.', 'sumosubscriptions' ),
            ),
            array( 'type' => 'sectionend', 'id' => 'payments_section' ),
                ) );
    }

    /**
     * Custom type field.
     */
    public function get_shortcodes() {
        $shortcodes = array(
            '[sumo_my_subscriptions]' => __( 'Use this shortcode to display My Subscriptions.', 'sumosubscriptions' ),
        );
        ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Shortcode', 'sumosubscriptions' ); ?></th>
                    <th><?php esc_html_e( 'Purpose', 'sumosubscriptions' ); ?></th>
                </tr>
            </thead>
            <tbody>                
                <?php foreach ( $shortcodes as $shortcode => $purpose ) { ?>
                    <tr>
                        <td><?php echo esc_html( $shortcode ); ?></td>
                        <td><?php echo esc_html( $purpose ); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
    }
}

return new SUMOSubs_Admin_Settings_General();
