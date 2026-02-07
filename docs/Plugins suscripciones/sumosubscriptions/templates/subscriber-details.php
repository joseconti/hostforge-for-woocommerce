<?php
/**
 * My Subscriptions > Subscriber Details.
 * 
 * Shows the details of a particular subscriber order details on the my account page.
 *
 * This template can be overridden by copying it to yourtheme/sumosubscriptions/subscriber-details.php.
 * 
 * @since 15.2.4
 */
defined( 'ABSPATH' ) || exit;
?>
<section class="woocommerce-customer-details sumosubs-subscriber-details">
    <?php
    /**
     * Before Subscriber Details.
     * 
     * @since 15.2.4
     */
    do_action( 'sumosubscriptions_before_subscriber_details', $order, $subscription_id );
    ?>    
        <section class="woocommerce-columns woocommerce-columns--2 woocommerce-columns--addresses col2-set addresses">

            <div class="woocommerce-column woocommerce-column--1 woocommerce-column--billing-address col-1">
          
            <h2 class="subscriber-billing-address"><?php esc_html_e( 'Billing address', 'sumosubscriptions' ); ?></h2>

            <address>
                <?php echo wp_kses_post( $order->get_formatted_billing_address( esc_html__( 'N/A', 'sumosubscriptions' ) ) ); ?>

                <?php if ( $order->get_billing_phone() ) : ?>
                    <p class="woocommerce-customer-details--phone"><?php echo esc_html( $order->get_billing_phone() ); ?></p>
                <?php endif; ?>

                <?php if ( $order->get_billing_email() ) : ?>
                    <p class="woocommerce-customer-details--email"><?php echo esc_html( $order->get_billing_email() ); ?></p>
                <?php endif; ?>
            </address>
            </div>

            <div class="woocommerce-column woocommerce-column--2 woocommerce-column--shipping-address col-2">
                <h2 class="woocommerce-column__title"><?php esc_html_e( 'Shipping address', 'sumosubscriptions' ); ?></h2>
                <address>
                    <?php echo wp_kses_post( SUMOSubs_Shipping::is_updated( get_current_user_id(), $subscription_id ) ? WC()->countries->get_formatted_address( SUMOSubs_Shipping::get_address( get_current_user_id(), $subscription_id ) ) : $order->get_formatted_shipping_address( esc_html__( 'N/A', 'sumosubscriptions' ) )  ); ?>

                    <?php if ( $order->get_shipping_phone() ) : ?>
                        <p class="woocommerce-customer-details--phone"><?php echo esc_html( $order->get_shipping_phone() ); ?></p>
                    <?php endif; ?>
                </address>
            </div>

        </section>
        <?php   

    /**
     * After Subscriber Details.
     * 
     * @since 15.2.4
     */
    do_action( 'sumosubscriptions_after_subscriber_details', $order, $subscription_id );
    ?>
</section>
