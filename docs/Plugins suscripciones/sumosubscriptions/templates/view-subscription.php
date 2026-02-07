<?php
/**
 * My Subscriptions > View Subscription.
 * 
 * Shows the details of a particular subscription on the account page.
 *
 * This template can be overridden by copying it to yourtheme/sumosubscriptions/view-subscription.php.
 */
defined( 'ABSPATH' ) || exit;

$subscription_status = get_post_meta( $subscription_id, 'sumo_get_status', true );
$parent_order_id     = get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true );
$next_payment_date   = get_post_meta( $subscription_id, 'sumo_get_next_payment_date', true );
$cancel_requested_by = get_post_meta( $subscription_id, 'sumo_subscription_cancel_requested_by', true );
$renewal_order_id    = get_post_meta( $subscription_id, 'sumo_get_renewal_id', true );
$parent_order        = wc_get_order( $parent_order_id );
$renewal_order       = wc_get_order( $renewal_order_id );

$synced             = SUMOSubs_Synchronization::is_subscription_synced( $subscription_id ) ? 'yes' : '';
$show_pause         = $synced ? 'yes' === $allow_subscribers_to_pause_synced : 'yes' === $allow_subscribers_to_pause;
$matched_attributes = 'yes' === $allow_subscribers_to_switch_bw_identical_variations ? SUMOSubs_Variation_Switcher::get_matched_attributes( $subscription_id ) : array();

do_action( 'sumosubscriptions_before_view_subscription_table', $subscription_id, $parent_order_id );
?>
<table class="sumo_subscription_details" data-subscription_id="<?php echo esc_attr( $subscription_id ); ?>" data-subscription_status="<?php echo esc_attr( $subscription_status ); ?>" data-next_payment_date="<?php echo esc_attr( $next_payment_date ); ?>" data-is_synced="<?php echo esc_attr( $synced ); ?>">

    <tr class="subscription_status">
        <td><b><?php esc_html_e( 'Subscription Status', 'sumosubscriptions' ); ?></b></td>
        <td>:</td>
        <td><?php echo wp_kses_post( sumo_display_subscription_status( $subscription_id ) ); ?></td>
    </tr>

    <?php if ( $show_pause && sumosubs_is_subscription_eligible_for_pause( $subscription_id ) && apply_filters( 'sumosubscriptions_my_subscription_table_pause_action', true, $subscription_id, $parent_order_id ) ) { ?>

        <tr class="subscription_pause_r_resume">
            <td><b><?php esc_html_e( 'Pause/Resume', 'sumosubscriptions' ); ?></b></td>
            <td>:</td>
            <td>
                <?php if ( 'Pause' === $subscription_status ) { ?>
                    <?php $auto_resume_on = get_post_meta( $subscription_id, 'sumo_subscription_auto_resume_scheduled_on', true ); ?>
                    <?php if ( ! empty( $auto_resume_on ) ) { ?>
                        <?php
                        /* translators: 1: scheduled date */
                        printf( wp_kses_post( __( 'Your Subscription will be Automatically Resume on <code>%s</code>/', 'sumosubscriptions' ) ), esc_html( $auto_resume_on ) );
                        ?>
                        <input type="button" class="button subscription-action" data-action="resume" value="<?php esc_attr_e( 'Resume Now', 'sumosubscriptions' ); ?>" />
                    <?php } else { ?>
                        <input type="button" class="button subscription-action" data-action="resume" value="<?php esc_attr_e( 'Resume', 'sumosubscriptions' ); ?>" />
                    <?php } ?>
                <?php } else { ?>
                    <?php if ( 'yes' === $allow_subscribers_to_select_resume_date ) { ?>
                        <input type="button" class="button subscription-action" data-action="pause" data-resume_before="<?php echo $max_pause_duration_for_subscribers > 0 ? esc_attr( sumo_get_subscription_date( "+{$max_pause_duration_for_subscribers} days" ) ) : ''; ?>" value="<?php esc_attr_e( 'Pause', 'sumosubscriptions' ); ?>" />
                    <?php } else { ?>
                        <input type="button" class="button subscription-action" data-action="pause-submit" data-resume_before="<?php echo $max_pause_duration_for_subscribers > 0 ? esc_attr( sumo_get_subscription_date( "+{$max_pause_duration_for_subscribers} days" ) ) : ''; ?>" value="<?php esc_attr_e( 'Pause', 'sumosubscriptions' ); ?>" />
                    <?php } ?>
                <?php } ?>
            </td>
        </tr>

        <?php if ( 'Pause' !== $subscription_status && 'yes' === $allow_subscribers_to_select_resume_date ) { ?>

            <tr class="subscription_resume_date" style="display:none">
                <td><b><?php esc_html_e( 'Resume Date', 'sumosubscriptions' ); ?></b></td>
                <td>:</td>
                <td>
                    <input type="text" id="auto-resume-subscription-on" placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'sumosubscriptions' ); ?>" value="" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])"/>
                    <input type="button" class="button subscription-action" id="subscription-pause-submit" data-action="pause-submit" value="<?php esc_attr_e( 'Submit', 'sumosubscriptions' ); ?>"/>
                </td>
            </tr>
        <?php } ?>
    <?php } ?>

    <?php if ( 'yes' === $allow_subscribers_to_cancel && 'admin' !== $cancel_requested_by && sumosubs_is_subscription_eligible_for_cancel( $subscription_id ) && apply_filters( 'sumosubscriptions_my_subscription_table_cancel_action', true, $subscription_id, $parent_order_id ) ) { ?>

        <tr class="subscription_cancel">
            <td><b><?php esc_html_e( 'Cancel/Revoke', 'sumosubscriptions' ); ?></b></td>
            <td>:</td>
            <td>
                <?php if ( in_array( $subscription_status, array( 'Trial', 'Active' ) ) ) { ?>
                    <?php $subscription_cancel_methods = sumosubs_get_subscription_cancel_methods(); ?>
                    <?php if ( ! empty( $subscription_cancel_methods ) ) { ?>

                        <input type="button" class="button subscription-action" data-action="cancel" value="<?php esc_attr_e( 'Cancel', 'sumosubscriptions' ); ?>" />
                        <select id="subscription-cancel-selector" style="display:none">
                            <?php foreach ( $subscription_cancel_methods as $method_key => $method ) : ?>
                                <option value="<?php echo esc_attr( $method_key ); ?>"><?php echo esc_html( $method ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="subscription-cancel-scheduled-on" placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'sumosubscriptions' ); ?>" value="" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" style="display:none"/>
                        <input type="button" class="button subscription-action" id="subscription-cancel-submit" data-action="cancel-submit" value="<?php esc_attr_e( 'Submit', 'sumosubscriptions' ); ?>" style="display:none"/>

                    <?php } ?>
                <?php } else if ( in_array( $subscription_status, array( 'Pending_Cancellation' ) ) ) { ?>
                    <?php $requested_cancel_method = get_post_meta( $subscription_id, 'sumo_subscription_requested_cancel_method', true ); ?>
                    <?php if ( 'end_of_billing_cycle' === $requested_cancel_method ) { ?>
                        <?php esc_html_e( 'Your Subscription will be Cancelled at End of this Billing Cycle.', 'sumosubscriptions' ); ?>

                        <input type="button" class="button subscription-action" data-action="cancel-revoke" value="<?php esc_attr_e( 'Revoke Cancel Request', 'sumosubscriptions' ); ?>"/>

                    <?php } else if ( 'scheduled_date' === $requested_cancel_method ) { ?>
                        <?php
                        /* translators: 1: scheduled date */
                        printf( wp_kses_post( __( 'Your Subscription will be Cancelled on <code>%s</code>.', 'sumosubscriptions' ) ), esc_html( get_post_meta( $subscription_id, 'sumo_subscription_cancellation_scheduled_on', true ) ) );
                        ?>

                        <input type="button" class="button subscription-action" data-action="cancel-revoke" value="<?php esc_attr_e( 'Revoke Cancel Request', 'sumosubscriptions' ); ?>"/>

                    <?php } ?>
                <?php } else if ( in_array( $subscription_status, array( 'Pending', 'Pause', 'Suspended', 'Overdue', 'Pending_Authorization' ) ) ) { ?>

                    <input type="button" class="button subscription-action" data-action="cancel" value="<?php esc_attr_e( 'Cancel', 'sumosubscriptions' ); ?>" />

                <?php } ?>
            </td>
        </tr>
    <?php } ?>

    <?php if ( 'yes' === $allow_subscribers_to_turnoff_auto_renewals && 'auto' === sumo_get_payment_type( $subscription_id ) && 'Active' === $subscription_status ) { ?>

        <tr class="subscription_turnoff_auto">
            <td><b><?php esc_html_e( 'Turn off Automatic', 'sumosubscriptions' ); ?></b></td>
            <td>:</td>
            <td><input type="button" class="button subscription-action" data-action="turnoff-auto" value="<?php esc_attr_e( 'Turn Off automatic', 'sumosubscriptions' ); ?>" /></td>
        </tr>

    <?php } ?>

    <?php if ( SUMOSubs_Resubscribe::can_subscriber_resubscribe( $subscription_id ) ) { ?>

        <tr class="subscription_resubscribe">
            <td><b><?php esc_html_e( 'Resubscribe', 'sumosubscriptions' ); ?></b></td>
            <td>:</td>
            <td><input type="button" class="button subscription-action" data-action="resubscribe" value="<?php esc_attr_e( 'Resubscribe', 'sumosubscriptions' ); ?>" /></td>
        </tr>

    <?php } ?>

    <?php if ( ! empty( $matched_attributes ) ) { ?>
        <tr class="subscription_variation_swapper">
            <td><b><?php esc_html_e( 'Switch Subscription Variation', 'sumosubscriptions' ); ?></b></td>
            <td>:</td>
            <td><?php SUMOSubs_Variation_Switcher::display( $subscription_id, $matched_attributes ); ?></td>
        </tr>

    <?php } ?>    

    <?php if ( 'yes' === $allow_subscribers_to_change_shipping_address && wc_shipping_enabled() && ! in_array( $subscription_status, array( 'Pending_Cancellation', 'Cancelled', 'Expired', 'Failed' ) ) ) { ?>

        <tr class="change_subscription_shipping_address">
            <td><b><?php esc_html_e( 'Change Shipping Address', 'sumosubscriptions' ); ?></b></td>
            <td>:</td>
            <td><a href="<?php echo esc_url( SUMOSubs_Shipping::get_shipping_endpoint_url( $subscription_id ) ); ?>" class="button view"><?php esc_html_e( 'Change Shipping Address', 'sumosubscriptions' ); ?></a></td>
        </tr>

    <?php } ?>

    <tr class="subscription_product_title" style="margin-top: 20px;">
        <td><b><?php esc_html_e( 'Subscribed Product', 'sumosubscriptions' ); ?></b></td>
        <td>:</td>
        <td>
            <?php
            echo wp_kses_post( sumo_display_subscription_name( $subscription_id, true, true ) );

            if ( $parent_order ) {
                foreach ( $parent_order->get_items() as $item_id => $item ) {
                    $canonical_product_id = $item->get_variation_id() > 0 ? $item->get_variation_id() : $item->get_product_id();

                    if ( $subscription->get_subscribed_product() === absint( $canonical_product_id ) ) {
                        /**
                         * Order Item Meta Start
                         * 
                         * @since 14.8.0
                         * @hook:woocommerce_order_item_meta_start
                         */
                        do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $parent_order, false );

                        wc_display_item_meta( $item );

                        /**
                         * Order Item Meta End
                         * 
                         * @since 14.8.0
                         * @hook:woocommerce_order_item_meta_end
                         */
                        do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $parent_order, false );
                    }
                }
            }
            ?>

            <?php if ( SUMOSubs_Switcher::can_switch( $subscription_id ) ) { ?>
                <a href="<?php echo esc_url( SUMOSubs_Switcher::get_switch_url( $subscription_id ) ); ?>" class="button"><?php echo esc_html( SUMOSubs_Switcher::get_switch_button_text() ); ?></a>
            <?php } ?>
        </td>
    </tr>

    <?php if ( 'yes' === $allow_subscribers_to_update_subscription_qty && ! SUMOSubs_Order_Subscription::is_subscribed( $subscription_id ) && in_array( $subscription_status, array( 'Active', 'Trial' ) ) ) { ?> 
        <tr class="subscription_product_quantity" >
            <td><b><?php esc_html_e( 'Subscribed Product Qty', 'sumosubscriptions' ); ?></b></td>
            <td>:</td>
            <td>
                <input type="number" name="subscription_qty" id="subscription_qty" min="1" value="<?php echo esc_attr( $subscription->get_subscribed_qty() ); ?>" />
                <button class="button subscription-action" data-action="quantity-change"><?php esc_html_e( 'Update', 'sumosubscriptions' ); ?></button>
            </td>
        </tr>
    <?php } ?>

    <tr class="subscription_plan_message">
        <td><b><?php esc_html_e( 'Current Subscription Plan', 'sumosubscriptions' ); ?></b></td>
        <td>:</td>
        <td>
            <?php echo wp_kses_post( sumo_display_subscription_plan( $subscription_id ) ); ?>
            <?php
            $subscription_plan = sumo_get_subscription_plan( $subscription_id, 0, 0, true );
            $currency          = $parent_order ? $parent_order->get_currency() : '';

            if ( SUMOSubs_Coupons::subscription_contains_recurring_coupon( $subscription_plan ) ) {
                echo '<p>' . wp_kses_post( SUMOSubs_Coupons::get_recurring_discount_amount_to_display( $subscription_plan[ 'subscription_discount' ][ 'coupon_code' ], $subscription_plan[ 'subscription_fee' ], $subscription_plan[ 'subscription_product_qty' ], $currency, $subscription_id ) ) . '</p>';
            }
            ?>
        </td>
    </tr>

    <tr class="subscription_start_date">
        <td><b><?php esc_html_e( 'Subscription Start Date', 'sumosubscriptions' ); ?></b></td>
        <td>:</td>
        <td><?php echo esc_html( sumo_display_start_date( $subscription_id ) ); ?></td>
    </tr>

    <tr class="subscription_due_date">
        <td><b><?php esc_html_e( 'Subscription Next Due Date', 'sumosubscriptions' ); ?></b></td>
        <td>:</td>
        <td><?php echo esc_html( sumo_display_next_due_date( $subscription_id ) ); ?></td>
    </tr>

    <tr class="subscription_end_date">
        <td><b><?php esc_html_e( 'Subscription End Date', 'sumosubscriptions' ); ?></b></td>
        <td>:</td>
        <td><?php echo esc_html( sumo_display_end_date( $subscription_id ) ); ?></td>
    </tr>

    <tr class="subscription_payment_method">        
        <td><b><?php esc_html_e( 'Subscription Payment Method', 'sumosubscriptions' ); ?></b></td>
        <td>:</td>
        <td>
            <?php
            /* translators: 1: payment method title */
            printf( esc_html__( 'Payment %s', 'sumosubscriptions' ), $subscription->get_payment_method_to_display( 'customer' ) );
            ?>
        </td>
    </tr>

    <?php
    $valid_statuses = apply_filters( 'sumosubscriptions_add_or_change_payment_valid_statuses', array( 'Trial', 'Active', 'Pending', 'Pause', 'Overdue', 'Suspended', 'Pending_Authorization' ) );
    if ( in_array( $subscription_status, ( array ) $valid_statuses ) ) {
        ?>
        <tr class="subscription_actions">
            <td><b><?php esc_html_e( 'Actions', 'sumosubscriptions' ); ?></b></td>
            <td>:</td>
            <td>
                <?php
                if ( $renewal_order && ! sumosubs_is_order_paid( $renewal_order ) ):
                    printf(
                            '<a class="woocommerce-button button pay" href="%s">%s</a>',
                            $renewal_order->get_checkout_payment_url(), __( 'Pay Now', 'sumosubscriptions' ) );
                endif;

                if ( $parent_order ) :
                    $args = array(
                        'sumosubs_add_or_change_payment' => $subscription_id,
                        '_sumosubs_nonce'                => wp_create_nonce( $subscription_id ),
                    );
                    $url  = add_query_arg( $args, $parent_order->get_checkout_payment_url() );
                    if ( 'auto' === sumo_get_payment_type( $subscription_id ) ) :
                        printf(
                                '<a class="woocommerce-button button sumosubs-change-payment" href="%s">%s</a>',
                                $url, __( 'Change Payment Method', 'sumosubscriptions' ) );
                    else:
                        printf(
                                '<a class="woocommerce-button button sumosubs-add-payment" href="%s">%s</a>',
                                $url, __( 'Add Payment Method', 'sumosubscriptions' ) );
                    endif;
                endif;
                ?>
            </td>
        </tr>  
    <?php } ?>

</table>

<?php if ( 'yes' === $show_subscription_activities ) { ?>

    <table class="subscription_activity_logs">
        <tr> 
            <td style="font-weight: bold"><?php esc_html_e( 'Activity Logs', 'sumosubscriptions' ); ?></td>
        </tr>

        <tr>
            <td>
                <?php $subscription_notes = sumosubs_get_subscription_notes( array( 'subscription_id' => $subscription_id ) ); ?>
                <?php if ( $subscription_notes ) { ?>
                    <?php foreach ( $subscription_notes as $index => $note ) { ?>

                        <?php if ( $index < 3 ) { ?>
                            <style>.default_subscription_notes<?php echo esc_attr( $index ); ?>{
                                    display:block;
                                }</style>
                            <?php } else { ?>
                            <style>.default_subscription_notes<?php echo esc_attr( $index ); ?>{
                                    display:none;
                                }</style>
                            <?php } ?>

                        <?php $note_status = ! empty( $note->meta[ 'comment_status' ][ 0 ] ) ? $note->meta[ 'comment_status' ][ 0 ] : ''; ?>
                        <?php if ( 'success' === $note_status ) { ?> 
                            <div class="sumo_alert_box _success default_subscription_notes<?php echo esc_attr( $index ); ?>"><span><?php echo wp_kses_post( $note->content ); ?></span></div>
                        <?php } else if ( 'pending' === $note_status ) { ?> 
                            <div class="sumo_alert_box warning default_subscription_notes<?php echo esc_attr( $index ); ?>"><span><?php echo wp_kses_post( $note->content ); ?></span></div>
                        <?php } else if ( 'failure' === $note_status ) { ?>
                            <div class="sumo_alert_box error default_subscription_notes<?php echo esc_attr( $index ); ?>"><span><?php echo wp_kses_post( $note->content ); ?></span></div>
                        <?php } else { ?>
                            <div class="sumo_alert_box notice default_subscription_notes<?php echo esc_attr( $index ); ?>"><span><?php echo wp_kses_post( $note->content ); ?></span></div>
                        <?php } ?>

                    <?php } ?>

                    <?php if ( ! empty( $index ) && $index > 2 ) { ?>
                        <a data-flag="more" id="prevent-more-subscription-notes" style="cursor: pointer;"><?php esc_html_e( 'Show More', 'sumosubscriptions' ); ?></a>
                    <?php } ?>
                <?php } else { ?>

                    <div class="sumo_alert_box notice">
                        <span><?php esc_html_e( 'No Activities Yet.', 'sumosubscriptions' ); ?></span>
                    </div>

                <?php } ?>
            </td>
        </tr>
    </table>

<?php } ?>
<?php
// Display billing and shipping address     
$show_customer_details = is_user_logged_in() && $parent_order && $parent_order->get_user_id() === get_current_user_id();
if ( $show_customer_details ) {
    sumosubscriptions_get_template( 'subscriber-details.php', array( 'order' => $parent_order, 'subscription_id' => $subscription_id ) );
}

do_action( 'sumosubscriptions_after_view_subscription_table', $subscription_id, $parent_order_id );
