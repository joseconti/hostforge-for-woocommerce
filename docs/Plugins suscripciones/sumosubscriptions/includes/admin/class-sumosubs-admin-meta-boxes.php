<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle Admin metaboxes.
 * 
 * @class SUMOSubs_Admin_Metaboxes
 */
class SUMOSubs_Admin_Metaboxes {

    /**
     * SUMOSubs_Admin_Metaboxes constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ) );
        add_action( 'admin_head', array( $this, 'set_default_metaboxes_position' ), 99999 );
        add_action( 'post_updated_messages', array( $this, 'display_admin_post_messages' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 3 );
        add_action( 'admin_init', array( $this, 'revoke_subscription_cancel_request' ) );
    }

    /**
     * Add Metaboxes.
     *
     * @global object $post
     */
    public function add_meta_boxes() {
        global $post, $theorder;
        $order_id = $theorder instanceof WC_Order ? $theorder->get_id() : ( $post ? $post->ID : 0 );

        add_meta_box( 'sumosubscription_details', __( 'Subscription Details', 'sumosubscriptions' ), array( $this, 'render_subscription_details' ), 'sumosubscriptions', 'normal', 'high' );
        add_meta_box( 'sumosubscription_actions', __( 'Actions', 'sumosubscriptions' ), array( $this, 'render_subscription_actions' ), 'sumosubscriptions', 'side', 'default' );
        add_meta_box( 'woocommerce-order-items', __( 'Subscription Item(s)', 'sumosubscriptions' ), array( $this, 'render_subscription_items' ), 'sumosubscriptions', 'normal', 'default' );
        add_meta_box( 'sumosubscription_log_information', __( 'Log History', 'sumosubscriptions' ), array( $this, 'render_subscription_notes' ), 'sumosubscriptions', 'side', 'default' );
        add_meta_box( 'sumosubscription_recurring_info', __( 'Recurring Information', 'sumosubscriptions' ), array( $this, 'render_subscription_recurring_info' ), 'sumosubscriptions', 'side', 'default' );
        add_meta_box( 'sumosubscription_successful_renewals', __( 'Successful Renewal Orders', 'sumosubscriptions' ), array( $this, 'render_successful_renewal_orders' ), 'sumosubscriptions', 'normal', 'default' );
        add_meta_box( 'sumosubscription_cancel_methods', __( 'Subscription Cancel Methods', 'sumosubscriptions' ), array( $this, 'render_subscription_cancel_methods' ), 'sumosubscriptions', 'side', 'default' );

        if ( $post && ( sumo_is_subscription_product( $post->ID ) || sumo_is_product_contains_subscription_variations( $post->ID ) ) ) {
            add_meta_box( 'sumosubscription_synced_next_payment_dates', __( 'Synchronized Payment Dates', 'sumosubscriptions' ), array( $this, 'render_subscription_recurring_info' ), 'product', 'side', 'low' );
            add_meta_box( 'sumosubscription_send_payment_reminder_email', __( 'Send Payment Reminder Email', 'sumosubscriptions' ), array( $this, 'render_payment_reminder_email_actions' ), 'product', 'side', 'low' );
        }

        // Only display the meta box if an order relates to a Subscription
        if ( $order_id && function_exists( 'wc_get_page_screen_id' ) && ( 'shop_order' === WC_Data_Store::load( 'order' )->get_order_type( $order_id ) && sumo_order_contains_subscription( $order_id ) ) ) {
            add_meta_box( 'sumosubscription_related_orders', __( 'Related Orders', 'sumosubscriptions' ), array( $this, 'render_related_orders' ), wc_get_page_screen_id( 'shop-order' ), 'normal', 'low' );
        }
    }

    /**
     * Remove Metaboxes.
     */
    public function remove_meta_boxes() {
        global $post;

        remove_meta_box( 'submitdiv', 'sumosubscriptions', 'side' );
        remove_meta_box( 'commentsdiv', 'sumosubscriptions', 'normal' );

        if ( 'sumosubscriptions' === get_post_type() ) {
            $subscription_status = get_post_meta( $post->ID, 'sumo_get_status', true );
            $parent_order_id     = get_post_meta( $post->ID, 'sumo_get_parent_order_id', true );

            /**
             * Get valid subscription statuses.
             * 
             * @since 1.0
             */
            if ( ! in_array( $subscription_status, apply_filters( 'sumosubscriptions_valid_subscription_statuses_to_become_active_subscription', array( 'Active', 'Trial', 'Overdue', 'Suspended', 'Pause', 'Pending', 'Pending_Cancellation', 'Pending_Authorization' ), $post->ID, $parent_order_id ) ) ) {
                remove_meta_box( 'sumosubscription_cancel_methods', 'sumosubscriptions', 'side' );
            }
        }
    }

    /**
     * Set default metaboxes positions
     */
    public function set_default_metaboxes_position() {
        if ( 'sumosubscriptions' === get_post_type() ) {
            $user = wp_get_current_user();
            if ( ! $user ) {
                return;
            }

            if ( false === get_user_option( 'meta-box-order_sumosubscriptions', $user->ID ) ) {
                delete_user_option( $user->ID, 'meta-box-order_sumosubscriptions', true );
                update_user_option( $user->ID, 'meta-box-order_sumosubscriptions', array(
                    'side'     => 'sumosubscription_actions,sumosubscription_cancel_methods,sumosubscription_recurring_info,sumosubscription_log_information',
                    'normal'   => 'sumosubscription_details,slugdiv,sumosubscription_successful_renewals',
                    'advanced' => '',
                        ), true );
            }

            if ( false === get_user_option( 'screen_layout_sumosubscriptions', $user->ID ) ) {
                delete_user_option( $user->ID, 'screen_layout_sumosubscriptions', true );
                update_user_option( $user->ID, 'screen_layout_sumosubscriptions', 'auto', true );
            }
        }
    }

    /**
     * Display updated Subscription post message.
     *
     * @param array $messages
     * @return string
     */
    public function display_admin_post_messages( $messages ) {
        $messages[ 'sumosubscriptions' ] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'Subscription updated.', 'sumosubscriptions' ),
            2 => __( 'Custom field updated.', 'sumosubscriptions' ),
            4 => __( 'Subscription updated.', 'sumosubscriptions' ),
        );

        return $messages;
    }

    /**
     * Revoke Subscription Cancel request by Admin.
     */
    public function revoke_subscription_cancel_request() {
        if ( isset( $_GET[ '_sumosubsnonce' ], $_GET[ 'post' ], $_GET[ 'request' ] ) ) {
            $subscription_id = wc_clean( wp_unslash( $_GET[ 'post' ] ) );

            if ( wp_verify_nonce( wc_clean( wp_unslash( $_GET[ '_sumosubsnonce' ] ) ), $subscription_id ) && 'revoke_cancel' === wc_clean( wp_unslash( $_GET[ 'request' ] ) ) ) {
                sumosubs_revoke_cancel_request( $subscription_id, __( 'Cancel request revoked by admin.', 'sumosubscriptions' ) );
            }

            wp_safe_redirect( remove_query_arg( array( 'request', '_sumosubsnonce' ) ) );
        }
    }

    /**
     * Meta Box showing Subscription actions.
     *
     * @param object $post The post object.
     */
    public function render_subscription_actions( $post ) {
        $parent_order_id      = get_post_meta( $post->ID, 'sumo_get_parent_order_id', true );
        $renewal_order_id     = get_post_meta( $post->ID, 'sumo_get_renewal_id', true );
        $subscription_status  = 'Pending_Cancellation' === get_post_meta( $post->ID, 'sumo_get_status', true ) ? get_post_meta( $post->ID, 'sumo_subscription_previous_status', true ) : get_post_meta( $post->ID, 'sumo_get_status', true );
        $is_invoice_present   = is_numeric( $renewal_order_id ) && $renewal_order_id > 0 ? true : false;
        $is_automatic_payment = 'auto' === sumo_get_payment_type( $post->ID ) ? true : false;
        include 'views/html-admin-subscription-actions.php';
    }

    /**
     * Meta Box showing Subscription details.
     *
     * @param object $post The post object.
     */
    public function render_subscription_details( $post ) {
        $subscription_plan   = sumo_get_subscription_plan( $post->ID );
        $parent_order_id     = get_post_meta( $post->ID, 'sumo_get_parent_order_id', true );
        $subscription_status = get_post_meta( $post->ID, 'sumo_get_status', true );
        $sub_due_date        = get_post_meta( $post->ID, 'sumo_get_next_payment_date', true );
        $trial_end_date      = get_post_meta( $post->ID, 'sumo_get_trial_end_date', true );
        $subscriber_id       = get_post_meta( $post->ID, 'sumo_get_user_id', true );
        $renewal_order_id    = get_post_meta( $post->ID, 'sumo_get_renewal_id', true );
        $subscription_fee    = SUMOSubs_Order_Subscription::is_subscribed( $post->ID ) ? 0 : sumo_get_recurring_fee( $post->ID, array(), 0, false );
        $last_renewed_order  = sumo_get_last_renewed_order( $post->ID );
        $parent_order        = wc_get_order( $parent_order_id );
        $renewal_order       = wc_get_order( $renewal_order_id );
        $payment_method      = sumo_get_subscription_payment_method( $post->ID );
        $payment_method      = ! empty( $payment_method ) ? $payment_method : ( $parent_order ? $parent_order->get_payment_method() : '' );
        $product_qty         = ! empty( $subscription_plan[ 'subscription_product_qty' ] ) ? absint( $subscription_plan[ 'subscription_product_qty' ] ) : 1;
        $currency            = $parent_order ? $parent_order->get_currency() : '';

        /**
         * Get valid subscription statuses.
         * 
         * @since 1.0
         */
        $valid_subscription_statuses = apply_filters( 'sumosubscriptions_valid_subscription_statuses_to_become_active_subscription', array( 'Active', 'Trial', 'Overdue', 'Suspended', 'Pause', 'Pending', 'Pending_Cancellation', 'Pending_Authorization' ), $post->ID, $parent_order_id );

        /**
         * Need readonly mode?
         * 
         * @since 1.0
         */
        $is_read_only_mode = apply_filters( 'sumosubscriptions_edit_subscription_page_readonly_mode', ( 'Pending_Cancellation' === $subscription_status ), $post->ID, $parent_order_id ) ? true : false;

        wp_nonce_field( 'sumosubscriptions_save_data', 'sumosubscriptions_meta_nonce' );
        include 'views/html-admin-subscription-details.php';
    }

    /**
     * Meta Box showing Subscription items.
     *
     * @param object $post The post object.
     */
    public function render_subscription_items( $post ) {
        $subscription_id       = $post->ID;
        $subscription_plan     = sumo_get_subscription_plan( $subscription_id );
        $is_order_subscription = SUMOSubs_Order_Subscription::is_subscribed( $subscription_id );
        $parent_order_id       = get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true );
        $renewal_order_id      = absint( get_post_meta( $subscription_id, 'sumo_get_renewal_id', true ) );
        $order_id              = $renewal_order_id > 0 ? $renewal_order_id : $parent_order_id;
        $order                 = wc_get_order( $order_id );

        if ( $order ) {
            include 'views/html-admin-order-items.php';
        }
    }

    /**
     * Meta Box showing Subscription next possible forthcoming payment dates.
     *
     * @param object $post
     */
    public function render_subscription_recurring_info( $post ) {
        switch ( $post->post_type ) {
            case 'sumosubscriptions':
                $next_payment_dates  = sumosubs_get_possible_next_payment_dates( $post->ID, 0, true );
                $next_payment_dates  = array_map( 'sumo_display_subscription_date', $next_payment_dates );
                $subscription_status = get_post_meta( $post->ID, 'sumo_get_status', true );

                if ( $next_payment_dates && in_array( $subscription_status, array( 'Active', 'Trial' ) ) ) {
                    $label = 1 === count( $next_payment_dates ) ? __( 'Next Payment on: ', 'sumosubscriptions' ) : __( 'Next Payments on: ', 'sumosubscriptions' );
                    echo '<b>' . wp_kses_post( $label ) . '<br></b>' . wp_kses_post( implode( '<br>', $next_payment_dates ) );
                } else {
                    echo '--';
                }
                break;
            case 'product':
                $product = wc_get_product( $post->ID );
                switch ( $product->get_type() ) {
                    case 'variable':
                        ?>
                        <table>
                            <tbody>
                                <?php
                                $subscription_variation = sumo_get_available_subscription_variations( $product->get_id(), 10 );
                                foreach ( $subscription_variation as $variation_id ) {
                                    $_variation = wc_get_product( $variation_id );
                                    ?>
                                    <tr>
                                        <th><?php echo wp_kses_post( $_variation->get_formatted_name() ); ?></th>
                                    <tr>
                                        <td>
                                            <?php
                                            if ( SUMOSubs_Synchronization::is_subscription_synced( $variation_id ) ) {
                                                $next_payment_dates = sumosubs_get_possible_next_payment_dates( 0, $variation_id, true );
                                                $next_payment_dates = array_map( 'sumo_display_subscription_date', $next_payment_dates );
                                                echo wp_kses_post( implode( '<br>', $next_payment_dates ) );
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php
                        break;
                    default:
                        if ( SUMOSubs_Synchronization::is_subscription_synced( $product->get_id() ) ) {
                            $next_payment_dates = sumosubs_get_possible_next_payment_dates( 0, $product->get_id(), true );
                            $next_payment_dates = array_map( 'sumo_display_subscription_date', $next_payment_dates );
                            echo wp_kses_post( implode( '<br>', $next_payment_dates ) );
                        } else {
                            echo '--';
                        }
                        break;
                }
                break;
        }
    }

    /**
     * Meta Box showing Subscription renewed order date information.
     *
     * @param object $post
     */
    public function render_successful_renewal_orders( $post ) {
        $renewal_orders = get_post_meta( $post->ID, 'sumo_get_every_renewal_ids', true );
        $renewed_count  = sumosubs_get_renewed_count( $post->ID );
        include 'views/html-admin-renewed-orders.php';
    }

    /**
     * Meta Box showing Subscription Cancel method status
     *
     * @param object $post
     */
    public function render_subscription_cancel_methods( $post ) {
        $subscription_status     = get_post_meta( $post->ID, 'sumo_get_status', true );
        $requested_cancel_method = get_post_meta( $post->ID, 'sumo_subscription_requested_cancel_method', true );
        $next_payment_date       = get_post_meta( $post->ID, 'sumo_get_next_payment_date', true );
        $saved_due_date          = get_post_meta( $post->ID, 'sumo_get_saved_due_date', true );
        $persistent_due_date     = '--' === $next_payment_date ? $saved_due_date : $next_payment_date;
        $cancel_scheduled_on     = 'end_of_billing_cycle' === $requested_cancel_method ? $persistent_due_date : get_post_meta( $post->ID, 'sumo_subscription_cancellation_scheduled_on', true );
        include 'views/html-admin-subscription-cancel-methods.php';
    }

    /**
     * Meta Box showing Subscription payment reminder email whether to send or not to the specific product.
     *
     * @param object $post
     */
    public function render_payment_reminder_email_actions( $post ) {
        $product                = wc_get_product( $post->ID );
        $subscription_variation = sumo_get_available_subscription_variations( $post->ID, 10 );
        include 'views/html-admin-product-subscription-reminder-email-actions.php';
    }

    /**
     * Meta Box showing Subscription log information.
     *
     * @param object $post The post object.
     */
    public function render_subscription_notes( $post ) {
        $notes = sumosubs_get_subscription_notes( array( 'subscription_id' => $post->ID ) );
        include 'views/html-admin-subscription-notes.php';
    }

    /**
     * Subscription Related Order(s).
     * 
     * @param object $post The post object.
     */
    public function render_related_orders( $post ) {
        $current_order_id = $post instanceof WC_Order ? $post->get_id() : $post->ID;
        $current_order    = wc_get_order( $current_order_id );

        if ( ! $current_order ) {
            return;
        }

        $subscriptions = sumosubscriptions()->query->get( array(
            'type'       => 'sumosubscriptions',
            'status'     => 'publish',
            'meta_key'   => 'sumo_get_parent_order_id',
            'meta_value' => sumosubs_get_parent_order_id( $current_order ),
                ) );

        if ( empty( $subscriptions ) ) {
            return;
        }

        $related_orders = array();
        if ( sumosubs_is_parent_order( $current_order ) ) {
            foreach ( $subscriptions as $subscription_id ) {
                $subscription_number = sumo_get_subscription_number( $subscription_id );

                $related_orders[ $subscription_id ] = array(
                    'order_id' => $subscription_number,
                    'relation' => __( 'Subscription', 'sumosubscriptions' ),
                    'date'     => sumo_display_start_date( $subscription_id ),
                    'status'   => sumo_display_subscription_status( $subscription_id ),
                    'total'    => sumo_display_subscription_plan( $subscription_id ),
                );

                $renewal_orders = get_post_meta( $subscription_id, 'sumo_get_every_renewal_ids', true );
                if ( empty( $renewal_orders ) ) {
                    continue;
                }

                foreach ( $renewal_orders as $renewal_order_id ) {
                    $renewal_order = wc_get_order( $renewal_order_id );
                    if ( ! $renewal_order ) {
                        continue;
                    }

                    $related_orders[ $renewal_order_id ] = array(
                        'order_id'     => $renewal_order_id,
                        /* translators: 1: subscription url */
                        'relation'     => sprintf( __( 'Renewal Order of Subscription %s', 'sumosubscriptions' ), "<a href='" . esc_url( admin_url( "post.php?post={$subscription_id}&action=edit" ) ) . "'>#{$subscription_number}</a>" ),
                        'date'         => $renewal_order->get_date_created()->date( 'Y-m-d H:i:s' ),
                        'status'       => $renewal_order->get_status(),
                        'status_label' => wc_get_order_status_name( $renewal_order->get_status() ),
                        'total'        => $renewal_order->get_formatted_order_total(),
                    );
                }
            }
        } else if ( sumosubs_is_renewal_order( $current_order ) ) {
            $subscription_id     = $current_order->get_meta( 'sumo_subscription_id', true );
            $subscription_number = sumo_get_subscription_number( $subscription_id );

            $related_orders[ $subscription_id ] = array(
                'order_id' => $subscription_number,
                'relation' => __( 'Subscription', 'sumosubscriptions' ),
                'date'     => sumo_display_start_date( $subscription_id ),
                'status'   => sumo_display_subscription_status( $subscription_id ),
                'total'    => sumo_display_subscription_plan( $subscription_id ),
            );

            $parent_order_id = $current_order->get_parent_id();
            $parent_order    = wc_get_order( $parent_order_id );

            if ( $parent_order ) {
                $subscriptions_link = array();

                foreach ( $subscriptions as $id ) {
                    $_subscription_number = sumo_get_subscription_number( $id );
                    $subscriptions_link[] = "<a href='" . esc_url( admin_url( "post.php?post={$id}&action=edit" ) ) . "'>#{$_subscription_number}</a>";
                }

                $related_orders[ $parent_order_id ] = array(
                    'order_id'     => $parent_order_id,
                    /* translators: 1: subscriptions url */
                    'relation'     => sprintf( __( 'Parent Order of Subscription %s', 'sumosubscriptions' ), wp_kses_post( implode( ', ', $subscriptions_link ) ) ),
                    'date'         => $parent_order->get_date_created()->date( 'Y-m-d H:i:s' ),
                    'status'       => $parent_order->get_status(),
                    'status_label' => wc_get_order_status_name( $parent_order->get_status() ),
                    'total'        => $parent_order->get_formatted_order_total(),
                );
            }

            $renewal_orders = get_post_meta( $subscription_id, 'sumo_get_every_renewal_ids', true );
            if ( empty( $renewal_orders ) ) {
                return;
            }

            foreach ( $renewal_orders as $renewal_order_id ) {
                if ( $renewal_order_id == $current_order_id ) {
                    continue;
                }

                $renewal_order = wc_get_order( $renewal_order_id );
                if ( ! $renewal_order ) {
                    continue;
                }

                $related_orders[ $renewal_order_id ] = array(
                    'order_id'     => $renewal_order_id,
                    /* translators: 1: subscription url */
                    'relation'     => sprintf( __( 'Renewal Order of Subscription %s', 'sumosubscriptions' ), "<a href='" . esc_url( admin_url( "post.php?post={$subscription_id}&action=edit" ) ) . "'>#{$subscription_number}</a>" ),
                    'date'         => $renewal_order->get_date_created()->date( 'Y-m-d H:i:s' ),
                    'status'       => $renewal_order->get_status(),
                    'status_label' => wc_get_order_status_name( $renewal_order->get_status() ),
                    'total'        => $renewal_order->get_formatted_order_total(),
                );
            }
        }

        /**
         * Get subscription related orders for display.
         * 
         * @since 1.0
         */
        $related_orders = apply_filters( 'sumosubscriptions_admin_related_orders_to_display', $related_orders );
        include 'views/html-admin-related-orders.php';
    }

    /**
     * Save subscription metabox data.
     *
     * @param int $post_id The post ID.
     * @param object $post The post object.
     * @param bool $update Whether this is an existing post being updated or not.
     */
    public function save_meta_boxes( $post_id, $post, $update ) {
        // $post_id and $post are required
        if ( empty( $post_id ) || empty( $post ) ) {
            return;
        }

        // Dont' save meta boxes for revisions or autosaves
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
            return;
        }

        // Check the nonce
        if ( ! isset( $_POST[ 'sumosubscriptions_meta_nonce' ] ) || empty( $_POST[ 'sumosubscriptions_meta_nonce' ] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_POST[ 'sumosubscriptions_meta_nonce' ] ) ), 'sumosubscriptions_save_data' ) ) {
            return;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if ( empty( $_POST[ 'post_ID' ] ) || wc_clean( wp_unslash( $_POST[ 'post_ID' ] ) ) != $post_id ) {
            return;
        }

        // Check user has permission to edit
        if ( 'sumosubscriptions' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $subscription_status = get_post_meta( $post_id, 'sumo_get_status', true );
        $next_payment_date   = get_post_meta( $post_id, 'sumo_get_next_payment_date', true );
        $parent_order_id     = get_post_meta( $post_id, 'sumo_get_parent_order_id', true );
        $buyer_email         = get_post_meta( $post_id, 'sumo_buyer_email', true );
        $parent_order        = wc_get_order( $parent_order_id );
        $payment_method      = sumo_get_subscription_payment_method( $post_id );
        $payment_method      = ! empty( $payment_method ) ? $payment_method : ( $parent_order ? $parent_order->get_payment_method() : '' );

        //Subscription Buyer email updation by the Admin
        if ( isset( $_POST[ 'subscription_buyer_email' ] ) && ! empty( $_POST[ 'subscription_buyer_email' ] ) ) {
            $new_email_address_raw = wc_clean( wp_unslash( $_POST[ 'subscription_buyer_email' ] ) );
            $new_email_address     = $new_email_address_raw != $buyer_email ? $new_email_address_raw : '';

            if ( false === ! filter_var( $new_email_address, FILTER_VALIDATE_EMAIL ) ) {
                update_post_meta( $post_id, 'sumo_buyer_email', $new_email_address );
                /* translators: 1: email ID */
                sumo_add_subscription_note( sprintf( __( 'Admin has changed the subscriber email to %s.', 'sumosubscriptions' ), $new_email_address ), $post_id, sumo_note_status( $subscription_status ), __( 'Subscriber email changed by admin', 'sumosubscriptions' ) );
            }
        }

        //Update renewal price.
        if ( isset( $_POST[ 'subscription_recurring_fee' ] ) ) {
            sumo_change_subscription_renewal_price( $post_id, wc_clean( wp_unslash( $_POST[ 'subscription_recurring_fee' ] ) ) );
        }

        //Update subscription qty.
        if ( isset( $_POST[ 'subscription_qty' ] ) ) {
            sumo_change_subscription_qty( $post_id, absint( wp_unslash( $_POST[ 'subscription_qty' ] ) ) );
        }

        //Next Due Date updation by the Admin
        if ( ! SUMOSubs_Synchronization::is_subscription_synced( $post_id ) && in_array( $subscription_status, array( 'Active', 'Trial' ) ) ) {
            if ( isset( $_POST[ 'subscription_next_due_date' ] ) && ! empty( $_POST[ 'subscription_next_due_date' ] ) ) {
                $new_renewal_date_raw  = wc_clean( wp_unslash( $_POST[ 'subscription_next_due_date' ] ) );
                $new_renewal_hh        = ! empty( $_POST[ 'subscription_due_hour' ] ) ? absint( wp_unslash( $_POST[ 'subscription_due_hour' ] ) ) : '00';
                $new_renewal_mm        = ! empty( $_POST[ 'subscription_due_minute' ] ) ? absint( wp_unslash( $_POST[ 'subscription_due_minute' ] ) ) : '00';
                $new_renewal_timestamp = sumo_get_subscription_timestamp( $new_renewal_date_raw . ' ' . $new_renewal_hh . ':' . $new_renewal_mm . ':' . absint( gmdate( 's', sumo_get_subscription_timestamp( $next_payment_date ) ) ) );

                if ( $new_renewal_timestamp > sumo_get_subscription_timestamp() ) {
                    if ( sumo_get_subscription_timestamp( $next_payment_date ) != $new_renewal_timestamp ) {
                        $cron_event = new SUMOSubs_Cron_Event( $post_id );
                        $cron_event->unset_events( array(
                            'create_renewal_order',
                            'automatic_pay',
                            'notify_overdue',
                            'notify_suspend',
                            'notify_cancel',
                            'switch_to_manual_pay_mode',
                        ) );

                        $new_renewal_date = sumo_get_subscription_date( $new_renewal_timestamp );
                        SUMOSubs_Order::set_next_payment_date( $post_id, $new_renewal_date );
                        /* translators: 1: due date */
                        sumo_add_subscription_note( sprintf( __( 'Admin has changed the subscription due date to %s.', 'sumosubscriptions' ), $new_renewal_date ), $post_id, sumo_note_status( $subscription_status ), __( 'Due date changed by admin', 'sumosubscriptions' ) );
                    }
                }
            }
        }

        //Subscription Status updation by the Admin
        if ( isset( $_POST[ 'subscription_status' ] ) ) {
            $new_status_raw = wc_clean( wp_unslash( $_POST[ 'subscription_status' ] ) );
            $new_status     = $new_status_raw !== $subscription_status ? $new_status_raw : '';

            switch ( $new_status ) {
                case 'Pause':
                    sumo_pause_subscription( $post_id, '', 'admin' );

                    /**
                     * Trigger after Subscription gets paused.
                     * 
                     * @since 1.0
                     */
                    do_action( 'sumosubscriptions_pause_subscription', $post_id, $parent_order_id );
                    break;
                case 'Resume':
                    sumo_resume_subscription( $post_id, 'admin' );

                    /**
                     * Trigger after Subscription gets resumed.
                     * 
                     * @since 1.0
                     */
                    do_action( 'sumosubscriptions_active_subscription', $post_id, $parent_order_id );
                    break;
                case 'Activate-Trial':
                    if ( sumosubs_free_trial_awaiting_admin_approval( $post_id ) ) {
                        SUMOSubs_Order::maybe_activate_subscription( $post_id, $parent_order, 'pending', 'free-trial' );
                    }
                    break;
                case 'Active':
                    /**
                     * Trigger when the subscription is manually activated.
                     * 
                     * @since 1.0
                     */
                    $cron_event = new SUMOSubs_Cron_Event( $post_id );
                    $cron_event->unset_events();
                    SUMOSubs_Order::maybe_activate_subscription( $post_id, $parent_order, 'pending', 'Active', true );
                    break;
                default:
                    /**
                     * Trigger when the subscription is manually change to new status.
                     * 
                     * @since 1.0
                     */
                    do_action( 'sumosubscriptions_manual_' . strtolower( $new_status ) . '_subscription', $post_id, $parent_order_id, $subscription_status );
                    break;
            }
        }

        // Schedule the subscription.
        if ( sumo_subscription_awaiting_admin_approval( $post_id ) && ! empty( $_POST[ 'subscription_start_date' ] ) ) {
            $start_date_raw          = wc_clean( wp_unslash( $_POST[ 'subscription_start_date' ] ) );
            $start_date_hh           = ! empty( $_POST[ 'subscription_start_hour' ] ) ? absint( wp_unslash( $_POST[ 'subscription_start_hour' ] ) ) : '00';
            $start_date_mm           = ! empty( $_POST[ 'subscription_start_minute' ] ) ? absint( wp_unslash( $_POST[ 'subscription_start_minute' ] ) ) : '00';
            $subscription_start_time = sumo_get_subscription_timestamp( $start_date_raw . ' ' . $start_date_hh . ':' . $start_date_mm );

            if ( $subscription_start_time < sumo_get_subscription_timestamp() ) {
                return;
            }

            $cron_event = new SUMOSubs_Cron_Event( $post_id );
            $cron_event->unset_events();
            $cron_event->schedule_to_start_subscription( $subscription_start_time );

            $existing_scheduled_time = get_post_meta( $post_id, 'sumo_subcription_activation_scheduled_on', true );
            if ( '' !== $existing_scheduled_time ) {
                /* translators: 1: from date 2: to date */
                sumo_add_subscription_note( sprintf( __( 'Subscription activation rescheduled from %1$s to %2$s.', 'sumosubscriptions' ), sumo_get_subscription_date( $existing_scheduled_time ), sumo_get_subscription_date( $subscription_start_time ) ), $post_id, sumo_note_status( 'Pending' ), __( 'Subscription activation rescheduled', 'sumosubscriptions' ) );
            } else {
                /* translators: 1: scheduled date */
                sumo_add_subscription_note( sprintf( __( 'Subscription is scheduled to activate on %s.', 'sumosubscriptions' ), sumo_get_subscription_date( $subscription_start_time ) ), $post_id, sumo_note_status( 'Pending' ), __( 'Subscription activation scheduled', 'sumosubscriptions' ) );
            }

            update_post_meta( $post_id, 'sumo_subcription_activation_scheduled_on', $subscription_start_time );
        }

        // Trigger Manual Subscription Emails
        if ( isset( $_POST[ 'subscription_action' ] ) && ! empty( $_POST[ 'subscription_action' ] ) ) {
            $action = wc_clean( wp_unslash( $_POST[ 'subscription_action' ] ) );

            if ( strstr( $action, 'send_email_' ) ) {
                // Ensure gateways are loaded in case they need to insert data into the emails
                WC()->payment_gateways();
                WC()->shipping();

                $template_key = str_replace( 'send_email_', '', $action );
                // Trigger mailer.
                sumo_trigger_subscription_email( $template_key, 0, $post_id, true );
            }
        }
    }
}

new SUMOSubs_Admin_Metaboxes();
