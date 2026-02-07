<?php
/**
 * My Subscriptions > View Subscription > Form Add or Change Payment Method.
 * 
 * Shows the details of a particular subscription on the My account view subscription page.
 *
 * This template can be overridden by copying it to yourtheme/sumosubscriptions/form-add-or-change-payment-method.php.
 * 
 * @since 15.5.0
 */
defined( 'ABSPATH' ) || exit;

$customer_subscription_ids = sumosubs_get_subscriptions_by_user( $order->get_customer_id() );
$product                   = wc_get_product( $subscription->get_subscribed_product() );
$subscription_plan         = sumo_get_subscription_plan( $subscription_id, 0, 0, true );
$currency                  = $order ? $order->get_currency() : '';
?>
<table class="shop_table sumosubs_add_or_change_payment_method_details sumosubs_plan_details">  
    <tbody>     
        <tr class="subscription_product_title" style="margin-top: 20px;">
            <td><b><?php esc_html_e( 'Subscribed Product :', 'sumosubscriptions' ); ?></b></td>               
            <td>
                <?php echo wp_kses_post( sumo_display_subscription_name( $subscription_id, true, true ) ); ?>
            </td>
        </tr>
        <tr class="subscription_plan_message">
            <td><b><?php esc_html_e( 'Current Subscription Plan :', 'sumosubscriptions' ); ?></b></td>                
            <td>
                <?php
                echo wp_kses_post( sumo_display_subscription_plan( $subscription_id ) );
                if ( SUMOSubs_Coupons::subscription_contains_recurring_coupon( $subscription_plan ) ) {
                    echo '<p>' . wp_kses_post( SUMOSubs_Coupons::get_recurring_discount_amount_to_display( $subscription_plan[ 'subscription_discount' ][ 'coupon_code' ], $subscription_plan[ 'subscription_fee' ], $subscription_plan[ 'subscription_product_qty' ], $currency, $subscription_id ) ) . '</p>';
                }
                ?>
            </td>
        </tr>     
        <tr class="subscription_payment_method">        
            <td><b><?php esc_html_e( 'Subscription Payment Method :', 'sumosubscriptions' ); ?></b></td>                
            <td><?php
                /* translators: 1: payment method title */
                printf( esc_html__( 'Payment %s', 'sumosubscriptions' ), ( $subscription->get_payment_method_to_display( 'customer' ) ) );
                ?></td>
        </tr>
    </tbody>
</table>

<form id="order_review" method="post">
    <div id="payment">          
        <ul class="wc_payment_methods payment_methods methods">
            <?php
            if ( count( $available_gateways ) ) {
                current( $available_gateways )->set_current();
            }

            if ( ! empty( $available_gateways ) ) {
                foreach ( $available_gateways as $gateway ) {
                    wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
                }
            } else {
                /**
                 * Get no available payment methods to display text.
                 * 
                 * @since 15.5.0
                 */
                echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . esc_html( apply_filters( 'woocommerce_no_available_payment_methods_message', esc_html__( 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'sumosubscriptions' ) ) ) . '</li>';
            }
            ?>
        </ul>       

        <?php if ( $available_gateways ) : ?>             
            <?php if ( count( $customer_subscription_ids ) > 1 ) : ?>
                <span class="update-all-subscriptions-payment-method">
                    <?php
                    //translators: $1: opening <strong> tag, $2: closing </strong> tag
                    $label = sprintf( esc_html__( 'Update the payment method used for %1$sall%2$s of my current subscriptions', 'sumosubscriptions' ), '<strong>', '</strong>' );

                    woocommerce_form_field(
                            'update_payment_method_for_all_valid_subscriptions',
                            array(
                                'type'    => 'checkbox',
                                'class'   => array( 'form-row-wide' ),
                                'label'   => $label,
                                'default' => false,
                            )
                    );
                    ?>
                </span>       
                <?php
            endif;
            /**
             * Add/Change payment method before submit.
             * 
             * @since 15.5.0
             */
            do_action( 'sumosubscriptions_add_or_change_payment_method_before_submit' );
            ?>
            <div class="form-row">          
                <?php wp_nonce_field( 'sumosubs_add_or_change_payment_method', '_sumosubs_nonce', true, true ); ?>                                
                <input type="submit" class="button alt" id="place_order" value="<?php echo esc_attr( $payment_button_text ) ?>" data-value="<?php echo esc_attr( $payment_button_text ) ?>" />
                <input type="hidden" name="sumosubs_add_or_change_payment" value="<?php echo esc_attr( $subscription_id ); ?>" />
            </div>
        <?php endif; ?>
    </div>
</form>
