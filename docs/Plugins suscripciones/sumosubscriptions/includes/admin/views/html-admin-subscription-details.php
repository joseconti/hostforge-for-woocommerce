<div class="panel-wrap sumosubscriptions">
    <input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? esc_attr__( 'Subscription', 'sumosubscriptions' ) : esc_attr( $post->post_title ); ?>" />
    <input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ); ?>" />
    <div id="order_data" class="panel">
        <h2 style="float: left;"><?php /* translators: 1: post type label 2: subscription number */ printf( esc_html__( '%1$s #%2$s details', 'sumosubscriptions' ), esc_html( get_post_type_object( $post->post_type )->labels->singular_name ), esc_html( sumo_get_subscription_number( $post->ID ) ) ); ?></h2>
        <?php echo wp_kses_post( sumo_display_subscription_status( $post->ID ) ); ?>
        <p>
            <?php echo wp_kses_post( sumo_display_subscription_plan( $post->ID ) ); ?>
        </p>
        <p>
            <?php
            if ( SUMOSubs_Coupons::subscription_contains_recurring_coupon( $subscription_plan ) ) {
                $subscription_fee_before_discount = 0;
                if ( $subscription_plan[ 'subscription_fee' ] > 0 ) {
                    $subscription_fee_before_discount = $subscription_plan[ 'subscription_fee' ];
                }

                echo wp_kses_post( SUMOSubs_Coupons::get_recurring_discount_amount_to_display( $subscription_plan[ 'subscription_discount' ][ 'coupon_code' ], $subscription_fee_before_discount, $product_qty, $currency, $post->ID ) );
            }
            ?>
        </p>
        <p class="order_number">
            <?php
            if ( $payment_method ) {
                $payment_gateways = WC()->payment_gateways() ? WC()->payment_gateways->payment_gateways() : array();

                /* translators: 1: payment method title */
                printf( esc_html__( 'Payment via %s', 'sumosubscriptions' ), ( isset( $payment_gateways[ $payment_method ] ) ? esc_html( $payment_gateways[ $payment_method ]->get_title() ) : esc_html( $payment_method ) ) );

                $transaction_id = null;
                if ( $last_renewed_order ) {
                    $transaction_id = $last_renewed_order->get_transaction_id();
                } else if ( $parent_order ) {
                    $transaction_id = $parent_order->get_transaction_id();
                }

                if ( $transaction_id ) {
                    $url = isset( $payment_gateways[ $payment_method ] ) ? $payment_gateways[ $payment_method ]->get_transaction_url( $last_renewed_order ? $last_renewed_order : $parent_order ) : '';
                    if ( $url ) {
                        echo ' (<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $transaction_id ) . '</a>)';
                    } else {
                        echo ' (' . esc_html( $transaction_id ) . ')';
                    }
                }
                echo '. ';
            }

            $ip_address = $parent_order->get_customer_ip_address();
            if ( $ip_address ) {
                esc_html_e( 'Customer IP', 'sumosubscriptions' ) . ': ' . esc_html( $ip_address );
            }
            ?>
        </p>
        <?php
        if ( $trial_end_date ) {
            $is_trial_ended = 'Trial' === $subscription_status || ( sumo_get_subscription_timestamp() <= sumo_get_subscription_timestamp( $trial_end_date ) ) ? false : true;
            ?>
            <div id="sumosubscription_trial_info" class="postbox <?php echo esc_attr( postbox_classes( 'sumosubscription_trial_info', get_current_screen()->id ) ); ?>">
                <button class="handlediv button-link" aria-expanded="true" type="button">
                    <span class="screen-reader-text"><?php esc_html_e( 'Trial Information', 'sumosubscriptions' ); ?></span>
                    <span class="toggle-indicator" aria-hidden="true"></span>
                </button>
                <h2 class="hndle">
                    <span><?php esc_html_e( 'Trial Information', 'sumosubscriptions' ); ?></span>
                </h2>
                <div class="inside">
                    <p class="form-field form-field-wide"><label for="order_date"><?php $is_trial_ended ? esc_html_e( 'Ended On: ', 'sumosubscriptions' ) : esc_html_e( 'Ends On: ', 'sumosubscriptions' ); ?></label>
                        <?php echo '<b>' . esc_html( sumo_display_subscription_date( $trial_end_date ) ) . '</b>'; ?>
                    </p>
                </div>
            </div>
        <?php } ?>
        <div class="order_data_column_container">
            <div class="order_data_column">
                <h4>
                    <?php esc_html_e( 'Parent Order', 'sumosubscriptions' ); ?>
                </h4>
                <p class="form-field form-field-wide">
                    <?php
                    echo '<a href=' . esc_url( admin_url( "post.php?post={$parent_order_id}&action=edit" ) ) . '>#' . esc_html( $parent_order_id ) . '</a>';
                    ?>
                </p><br>
                <h4>
                    <?php esc_html_e( 'General Details', 'sumosubscriptions' ); ?>
                </h4>
                <p class="form-field form-field-wide"><label for="order_date"><?php esc_html_e( 'Subscription Start date:', 'sumosubscriptions' ); ?></label>
                    <?php
                    if ( ! $is_read_only_mode && sumo_subscription_awaiting_admin_approval( $post->ID ) ) {
                        $subscription_scheduled_date = get_post_meta( $post->ID, 'sumo_subcription_activation_scheduled_on', true );
                        if ( $subscription_scheduled_date ) {
                            esc_html_e( 'Scheduled On: ', 'sumosubscriptions' ) . '<b>' . esc_html( sumo_display_subscription_date( $subscription_scheduled_date ) ) . '</b>';
                        }
                        ?>
                        <button class="subscription_start_schedule button"><?php echo $subscription_scheduled_date ? esc_html__( 'Reschedule', 'sumosubscriptions' ) : esc_html__( 'Schedule', 'sumosubscriptions' ); ?></button><br>
                        <span class="subscription_start_schedule_date_picker" style="display: none;">
                            <input type="text" class="date-picker" placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'sumosubscriptions' ); ?>"
                                   name="subscription_start_date" id="subscription_start_date" maxlength="6" style="width:180px;" 
                                   pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                            @<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', 'sumosubscriptions' ); ?>" 
                                    name="subscription_start_hour" id="subscription_start_hour" max="23" min="0" maxlength="2" size="2"
                                    pattern="\-?\d+(\.\d{0,})?" />
                            :<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', 'sumosubscriptions' ); ?>" 
                                    name="subscription_start_minute" id="subscription_start_minute" max="59" min="0" maxlength="2" size="2"
                                    pattern="\-?\d+(\.\d{0,})?" />
                        </span>
                        <?php
                    } else {
                        echo '<b>' . esc_html( sumo_display_start_date( $post->ID ) ) . '</b>';
                    }
                    ?>
                </p>
                <p class="form-field form-field-wide"><label for="order_date"><?php esc_html_e( 'Subscription End date:', 'sumosubscriptions' ); ?></label>
                    <?php
                    echo '<b>' . esc_html( sumo_display_end_date( $post->ID ) ) . '</b>';
                    ?>
                </p>
                <?php
                if ( in_array( $subscription_status, array( 'Active', 'Trial', 'Pending_Cancellation' ) ) ) :
                    ?>
                    <p class="form-field form-field-wide"><label for="order_date"><?php esc_html_e( 'Subscription Next Due date:', 'sumosubscriptions' ); ?></label> 
                        <?php
                        if ( '--' === $sub_due_date ) {
                            echo '<b>---</b>';
                        } else {
                            ?>
                            <input type="text" class="<?php echo $is_read_only_mode || SUMOSubs_Synchronization::is_subscription_synced( $post->ID ) ? '' : 'date-picker'; ?>"
                                   required placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'sumosubscriptions' ); ?>"
                                   name="subscription_next_due_date" id="subscription_next_due_date" maxlength="10" style="width:180px;" 
                                   value="<?php echo esc_attr( sumo_get_subscription_date( $sub_due_date, 0, true ) ); ?>" 
                                   <?php echo $is_read_only_mode || SUMOSubs_Synchronization::is_subscription_synced( $post->ID ) ? 'readonly' : ''; ?> 
                                   pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                            @<input type="number" class="hour" required placeholder="<?php esc_attr_e( 'h', 'sumosubscriptions' ); ?>" 
                                    name="subscription_due_hour" id="subscription_due_hour" max="23" min="0" maxlength="2" size="2" 
                                    value="<?php echo esc_attr( date_i18n( 'H', sumo_get_subscription_timestamp( $sub_due_date ) ) ); ?>" 
                                    <?php echo $is_read_only_mode || SUMOSubs_Synchronization::is_subscription_synced( $post->ID ) ? 'readonly' : ''; ?> 
                                    pattern="\-?\d+(\.\d{0,})?" />
                            :<input type="number" class="minute" required placeholder="<?php esc_attr_e( 'm', 'sumosubscriptions' ); ?>" 
                                    name="subscription_due_minute" id="subscription_due_minute" max="59" min="0" maxlength="2" size="2" 
                                    value="<?php echo esc_attr( date_i18n( 'i', sumo_get_subscription_timestamp( $sub_due_date ) ) ); ?>" 
                                    <?php echo $is_read_only_mode || SUMOSubs_Synchronization::is_subscription_synced( $post->ID ) ? 'readonly' : ''; ?> 
                                    pattern="\-?\d+(\.\d{0,})?" />
                                    <?php
                                }
                                ?>
                    </p>
                    <?php
                endif;
                ?>
                <p class="form-field form-field-wide">
                    <?php
                    if ( in_array( $subscription_status, $valid_subscription_statuses ) ) {
                        ?>
                        <label for="order_status"><?php esc_html_e( 'Subscription Status:', 'sumosubscriptions' ); ?></label>
                        <select class="wc-enhanced-select" id="subscription_status" name="subscription_status">
                            <option value=""><?php echo esc_html( str_replace( '_', ' ', $subscription_status ) ); ?></option>
                            <?php
                            $awaiting_free_trial_admin_approval = sumosubs_free_trial_awaiting_admin_approval( $post->ID );
                            $awaiting_admin_approval            = sumo_subscription_awaiting_admin_approval( $post->ID );
                            if ( apply_filters( 'sumosubscriptions_admin_can_change_subscription_statuses', ( $awaiting_free_trial_admin_approval || $awaiting_admin_approval || ! in_array( $subscription_status, array( 'Pending', 'Overdue', 'Suspended', 'Pending_Cancellation', 'Pending_Authorization' ) ) ), $post->ID ) ) {
                                ?>
                                <optgroup label="<?php esc_html_e( 'Change to', 'sumosubscriptions' ); ?>">
                                    <?php
                                    switch ( $subscription_status ) {
                                        case 'Trial':
                                        case 'Active':
                                            $statuses = array( 'Pause' => 'Pause' );
                                            break;
                                        case 'Pause':
                                            $statuses = array( 'Resume' => 'Resume' );
                                            break;
                                        case 'Pending':
                                            $statuses = array();

                                            if ( $awaiting_free_trial_admin_approval ) {
                                                $statuses[ 'Activate-Trial' ] = 'Activate Trial';
                                            } else if ( $awaiting_admin_approval ) {
                                                $statuses[ 'Active' ] = 'Active';
                                            }
                                            break;
                                        default:
                                            $statuses = array();
                                            break;
                                    }

                                    $statuses = apply_filters( 'sumosubscriptions_edit_subscription_statuses', $statuses, $post->ID, $parent_order_id, $subscription_status );
                                    if ( is_array( $statuses ) && $statuses ) {
                                        foreach ( $statuses as $_status => $status_name ) {
                                            echo '<option value="' . esc_attr( $_status ) . '" ' . selected( $_status, $subscription_status, false ) . '>' . esc_html( $status_name ) . '</option>';
                                        }
                                    }
                                    ?>
                                </optgroup>
                                <?php
                            }
                            ?>
                        </select>
                        <?php
                    } else {
                        echo '<b>' . esc_html__( 'This Subscription cannot be changed to any other status !!', 'sumosubscriptions' ) . '</b>';
                    }
                    ?>
                </p>
                <p class="form-field form-field-wide">
                    <label for="customer_user"><?php esc_html_e( 'Customer:', 'sumosubscriptions' ); ?></label>
                    <input type="text" class="subscription_buyer_email" required id="subscription_buyer_email" name="subscription_buyer_email" placeholder="<?php esc_attr_e( 'Buyer Email Address', 'sumosubscriptions' ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, 'sumo_buyer_email', true ) ); ?>" <?php echo $is_read_only_mode ? 'readonly' : ''; ?> data-allow_clear="true" />
                </p>
                <?php
                if ( $renewal_order && ! sumosubs_is_order_paid( $renewal_order ) ) :
                    ?>
                    <div class="view_unpaid_renewal_order" style="text-align : right;">
                        <a href="#" id="sumo_view_unpaid_renewal_order"><?php esc_html_e( 'View Unpaid Renewal Order', 'sumosubscriptions' ); ?></a>
                        <p id="sumo_unpaid_renewal_order" style="font-weight: bolder;">
                            <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $renewal_order_id . '&action=edit' ) ); ?>">#<?php echo esc_html( $renewal_order_id ); ?><br></a>
                        </p>
                    </div>
                    <?php
                endif;
                ?>
                <p class="form-field form-field-wide">
                    <?php
                    if ( $parent_order && in_array( $subscription_status, $valid_subscription_statuses ) && ! SUMOSubs_Order_Subscription::is_subscribed( $post->ID ) ) {
                        if ( 'auto' === sumo_get_payment_type( $post->ID ) && in_array( $payment_method, array( 'sumo_paypal_preapproval', 'sumosubscription_paypal_adaptive', 'paypal' ) ) ) {
                            $is_read_only_mode = true;
                        }
                        ?>
                        <label for="customer_user"><?php /* translators: 1: renewal price */ printf( esc_html__( 'Renewal Price: (%s)', 'sumosubscriptions' ), esc_html( get_woocommerce_currency_symbol( $currency ) ) ); ?></label>
                        <input type="text" style="width:70%" required name="subscription_recurring_fee" placeholder="<?php esc_attr_e( 'Enter the Price', 'sumosubscriptions' ); ?>" value="<?php echo esc_attr( $subscription_fee ); ?>" <?php echo $is_read_only_mode ? 'readonly' : ''; ?> data-allow_clear="true" />&nbsp;
                        <span>x<input type="number" name="subscription_qty" id="subscription_qty" min="1" required="required" placeholder="<?php esc_attr_e( 'Quantity', 'sumosubscriptions' ); ?>" value="<?php echo esc_attr( $product_qty ); ?>" style="width:20%"/></span>
                            <?php
                        }
                        ?>
                </p>
                <?php do_action( 'sumosubscriptions_admin_after_general_details', $post->ID ); ?>
            </div>
            <div class="order_data_column">
                <h4>
                    <?php esc_html_e( 'Billing Details', 'sumosubscriptions' ); ?>
                </h4>
                <div class="address">
                    <?php
                    if ( $parent_order && $parent_order->get_formatted_billing_address() ) {
                        echo '<p><strong>' . esc_html__( 'Address', 'sumosubscriptions' ) . ':</strong>' . wp_kses( $parent_order->get_formatted_billing_address(), array( 'br' => array() ) ) . '</p>';
                    } else {
                        echo '<p class="none_set"><strong>' . esc_html__( 'Address', 'sumosubscriptions' ) . ':</strong> ' . esc_html__( 'No billing address set.', 'sumosubscriptions' ) . '</p>';
                    }
                    ?>
                </div>
            </div>
            <div class="order_data_column">
                <h4>
                    <?php SUMOSubs_Shipping::is_updated( $subscriber_id, $post->ID ) ? esc_html_e( 'Old Shipping Details', 'sumosubscriptions' ) : esc_html_e( 'Shipping Details', 'sumosubscriptions' ); ?>
                </h4>
                <div class="address">
                    <?php
                    if ( $parent_order && $parent_order->get_formatted_shipping_address() ) {
                        echo '<p><strong>' . esc_html__( 'Address', 'sumosubscriptions' ) . ':</strong>' . wp_kses( $parent_order->get_formatted_shipping_address(), array( 'br' => array() ) ) . '</p>';
                    } else {
                        echo '<p class="none_set"><strong>' . esc_html__( 'Address', 'sumosubscriptions' ) . ':</strong> ' . esc_html__( 'No shipping address set.', 'sumosubscriptions' ) . '</p>';
                    }
                    ?>
                </div>
            </div>
            <?php if ( SUMOSubs_Shipping::is_updated( $subscriber_id, $post->ID ) ) { ?>
                <div class="order_data_column">
                    <h4>
                        <?php esc_html_e( 'Current Shipping Details', 'sumosubscriptions' ); ?>
                    </h4>
                    <div class="address">
                        <?php
                        $formatted_address = WC()->countries->get_formatted_address( SUMOSubs_Shipping::get_address( $subscriber_id, $post->ID ) );
                        if ( $formatted_address ) {
                            echo '<p><strong>' . esc_html__( 'Address', 'sumosubscriptions' ) . ':</strong>' . wp_kses( $formatted_address, array( 'br' => array() ) ) . '</p>';
                        } else {
                            echo '<p class="none_set"><strong>' . esc_html__( 'Address', 'sumosubscriptions' ) . ':</strong> ' . esc_html__( 'Shipping Address not changed so for.', 'sumosubscriptions' ) . '</p>';
                        }
                        ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <div class="clear"></div>
    </div>
</div>
