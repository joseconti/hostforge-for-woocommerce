<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle Admin post types.
 * 
 * @class SUMOSubs_Admin_Post_Types
 */
class SUMOSubs_Admin_Post_Types {

    protected static $custom_post_types = array(
        'sumosubscriptions'    => 'subscription',
        'sumomasterlog'        => 'masterlog',
        'sumosubs_cron_events' => 'cron_event',
    );

    /**
     * Init SUMOSubs_Admin_Post_Types.
     */
    public static function init() {

        foreach ( self::$custom_post_types as $post_type => $meant_for ) {
            add_filter( "manage_{$post_type}_posts_columns", __CLASS__ . "::define_{$meant_for}_columns" );
            add_filter( "manage_edit-{$post_type}_sortable_columns", __CLASS__ . '::define_sortable_columns' );
            add_filter( "bulk_actions-edit-{$post_type}", __CLASS__ . '::define_bulk_actions' );
            add_filter( "handle_bulk_actions-edit-{$post_type}", __CLASS__ . '::handle_bulk_actions', 10, 3 );
            add_action( "manage_{$post_type}_posts_custom_column", __CLASS__ . "::render_{$meant_for}_columns", 10, 2 );
        }

        add_filter( 'post_row_actions', __CLASS__ . '::row_actions', 99, 2 );
        add_action( 'restrict_manage_posts', __CLASS__ . '::render_filters' );
        add_action( 'manage_posts_extra_tablenav', __CLASS__ . '::extra_tablenav' );
        add_filter( 'request', __CLASS__ . '::request_query' );

        add_filter( 'get_search_query', __CLASS__ . '::search_label' );
        add_filter( 'query_vars', __CLASS__ . '::add_custom_query_var' );
        add_action( 'parse_query', __CLASS__ . '::search_custom_fields' );
        add_action( 'admin_notices', __CLASS__ . '::bulk_admin_notices' );
    }

    /**
     * Define which subscription columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_subscription_columns( $existing_columns ) {
        $columns = array(
            'cb'                                => $existing_columns[ 'cb' ],
            'status'                            => __( 'Status', 'sumosubscriptions' ),
            'subscription_no'                   => __( 'Subscription Number', 'sumosubscriptions' ),
            'subscribed_product'                => __( 'Subscribed Product with Quantity', 'sumosubscriptions' ),
            'subscription_plan'                 => __( 'Subscription Plan', 'sumosubscriptions' ),
            'order_id'                          => __( 'Order ID', 'sumosubscriptions' ),
            'buyer_email'                       => __( 'Buyer Email', 'sumosubscriptions' ),
            'billing_name'                      => __( 'Billing Name', 'sumosubscriptions' ),
            'payment_mode'                      => __( 'Payment Mode', 'sumosubscriptions' ),
            'start_date'                        => __( 'Subscription Start Date', 'sumosubscriptions' ),
            'end_date'                          => __( 'Subscription End Date', 'sumosubscriptions' ),
            'expired_date'                      => __( 'Subscription Expired Date', 'sumosubscriptions' ),
            'trial_end_date'                    => __( 'Trial End Date', 'sumosubscriptions' ),
            'last_payment_date'                 => __( 'Last Payment Date', 'sumosubscriptions' ),
            'next_payment_date'                 => __( 'Next Payment Date', 'sumosubscriptions' ),
            'total_installments/renewals_count' => __( 'Total Installments / Renewal Count', 'sumosubscriptions' ),
        );
        return $columns;
    }

    /**
     * Define which masterlog columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_masterlog_columns( $existing_columns ) {
        $columns = array(
            'cb'                 => $existing_columns[ 'cb' ],
            'user_name'          => __( 'User Name', 'sumosubscriptions' ),
            'action'             => __( 'Action', 'sumosubscriptions' ),
            'result'             => __( 'Result', 'sumosubscriptions' ),
            'subscription_no'    => __( 'Subscription Number', 'sumosubscriptions' ),
            'subscribed_product' => __( 'Subscription Product Name', 'sumosubscriptions' ),
            'order_id'           => __( 'Order ID', 'sumosubscriptions' ),
            'event'              => __( 'Event', 'sumosubscriptions' ),
            'date'               => __( 'Date', 'sumosubscriptions' ),
        );
        return $columns;
    }

    /**
     * Define which cron event columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_cron_event_columns( $existing_columns ) {
        $columns = array(
            'cb'              => $existing_columns[ 'cb' ],
            'event_id'        => __( 'Event ID', 'sumosubscriptions' ),
            'subscription_no' => __( 'Subscription Number', 'sumosubscriptions' ),
            'event_name'      => __( 'Event Name', 'sumosubscriptions' ),
            'next_run'        => __( 'Next Run', 'sumosubscriptions' ),
            'arguments'       => __( 'Arguments', 'sumosubscriptions' ),
        );
        return $columns;
    }

    /**
     * Define which columns are sortable.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_sortable_columns( $existing_columns ) {
        global $current_screen;

        if ( empty( $current_screen->post_type ) ) {
            return $existing_columns;
        }

        $columns = array();
        switch ( $current_screen->post_type ) {
            case 'sumosubscriptions':
                $columns = array(
                    'subscription_no'   => 'ID',
                    'order_id'          => 'parent_order_id',
                    'start_date'        => 'sub_start_date',
                    'trial_end_date'    => 'trial_end_date',
                    'end_date'          => 'sub_end_date',
                    'last_payment_date' => 'last_payment_date',
                    'next_payment_date' => 'next_payment_date',
                    'expired_date'      => 'sub_exp_date',
                    'buyer_email'       => 'buyer_email',
                );
                break;
            case 'sumomasterlog':
                $columns = array(
                    'subscription_no'    => 'subscription_no',
                    'order_id'           => 'orderid',
                    'user_name'          => 'user_name',
                    'subscribed_product' => 'subscription_name',
                    'date'               => 'date',
                );
                break;
            case 'sumosubs_cron_events':
                $columns = array(
                    'event_id'        => 'ID',
                    'subscription_no' => 'ID',
                );
                break;
        }
        return wp_parse_args( $columns, $existing_columns );
    }

    /**
     * Define bulk actions.
     *
     * @param array $actions Existing actions.
     * @return array
     */
    public static function define_bulk_actions( $actions ) {
        global $current_screen;
        unset( $actions[ 'edit' ] );

        if ( 'sumosubscriptions' === $current_screen->post_type ) {
            $actions[ 'pause_subscription' ]  = __( 'Change status to Pause', 'sumosubscriptions' );
            $actions[ 'resume_subscription' ] = __( 'Change status to Resume', 'sumosubscriptions' );
        }

        return $actions;
    }

    /**
     * Handle bulk actions.
     *
     * @param  string $redirect_to URL to redirect to.
     * @param  string $action      Action name.
     * @param  array  $ids         List of ids.
     * @return string
     */
    public static function handle_bulk_actions( $redirect_to, $action, $ids ) {
        $changed = 0;

        if ( 'pause_subscription' === $action ) {
            $report_action = 'subscription_paused';

            foreach ( $ids as $id ) {
                if ( in_array( get_post_meta( $id, 'sumo_get_status', true ), array( 'Active', 'Trial' ) ) ) {
                    sumo_pause_subscription( $id, '', 'admin-in-bulk' );
                    $changed ++;
                }
            }
        } elseif ( 'resume_subscription' === $action ) {
            $report_action = 'subscription_resumed';

            foreach ( $ids as $id ) {
                if ( 'Pause' === get_post_meta( $id, 'sumo_get_status', true ) ) {
                    sumo_resume_subscription( $id, 'admin-in-bulk' );
                    $changed ++;
                }
            }
        }

        if ( $changed ) {
            $redirect_to = add_query_arg( array(
                'post_type'   => 'sumosubscriptions',
                'bulk_action' => $report_action,
                'changed'     => $changed,
                'ids'         => join( ',', $ids ),
                    ), $redirect_to );
        }

        return esc_url_raw( $redirect_to );
    }

    /**
     * Render individual subscription columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_subscription_columns( $column, $post_id ) {
        $subscription_plan = sumo_get_subscription_plan( $post_id, 0, 0, true );
        $parent_order_id   = get_post_meta( $post_id, 'sumo_get_parent_order_id', true );

        switch ( $column ) {
            case 'status':
                echo wp_kses_post( sumo_display_subscription_status( $post_id ) );
                break;
            case 'subscription_no':
                echo wp_kses_post( sumo_display_subscription_ID( $post_id ) );
                break;
            case 'subscribed_product':
                echo wp_kses_post( sumo_display_subscription_name( $post_id, true ) );
                break;
            case 'subscription_plan':
                echo wp_kses_post( sumo_display_subscription_plan( $post_id ) );

                if ( SUMOSubs_Coupons::subscription_contains_recurring_coupon( $subscription_plan ) ) {
                    $parent_order = wc_get_order( $parent_order_id );
                    $currency     = $parent_order ? $parent_order->get_currency() : '';

                    echo '<p>' . wp_kses_post( SUMOSubs_Coupons::get_recurring_discount_amount_to_display( $subscription_plan[ 'subscription_discount' ][ 'coupon_code' ], $subscription_plan[ 'subscription_fee' ], $subscription_plan[ 'subscription_product_qty' ], $currency, $post_id ) ) . '</p>';
                }
                break;
            case 'order_id':
                echo '<a href="' . esc_url( admin_url( "post.php?post={$parent_order_id}&action=edit" ) ) . '">#' . esc_html( $parent_order_id ) . '</a>';
                break;
            case 'buyer_email':
                echo esc_html( get_post_meta( $post_id, 'sumo_buyer_email', true ) );
                break;
            case 'billing_name':
                echo esc_html( sumosubs_get_billing_name( $post_id ) );
                break;
            case 'payment_mode':
                'auto' === sumo_get_payment_type( $post_id ) ? esc_html_e( 'Automatic', 'sumosubscriptions' ) : esc_html_e( 'Manual', 'sumosubscriptions' );
                break;
            case 'start_date':
                echo esc_html( sumo_display_start_date( $post_id ) );
                break;
            case 'end_date':
                echo esc_html( sumo_display_end_date( $post_id ) );
                break;
            case 'expired_date':
                echo esc_html( sumo_display_expired_date( $post_id ) );
                break;
            case 'trial_end_date':
                echo esc_html( sumo_display_trial_end_date( $post_id ) );
                break;
            case 'last_payment_date':
                echo esc_html( sumo_display_last_payment_date( $post_id ) );
                break;
            case 'next_payment_date':
                echo esc_html( sumo_display_next_due_date( $post_id ) );
                break;
            case 'total_installments/renewals_count':
                $renewed_count      = sumosubs_get_renewed_count( $post_id );
                $renewed_count      = $renewed_count > 0 ? '<a href="' . admin_url( "post.php?post={$post_id}&action=edit#sumosubscription_successful_renewals" ) . '">' . $renewed_count . '</a>' : '0';
                $total_installments = '0' === $subscription_plan[ 'subscription_recurring' ] ? __( 'Indefinite', 'sumosubscriptions' ) : $subscription_plan[ 'subscription_recurring' ];
                echo wp_kses_post( "{$total_installments} / {$renewed_count}" );
                break;
        }
    }

    /**
     * Render individual masterlog columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_masterlog_columns( $column, $post_id ) {

        switch ( $column ) {
            case 'user_name':
            case 'action':
            case 'result':
                echo wp_kses_post( get_post_meta( $post_id, $column, true ) );
                break;
            case 'subscription_no':
                $subscription_no = get_post_meta( $post_id, 'subscription_no', true );
                $subscription_id = sumo_get_wp_subscriptions( array(
                    'post_status' => 'publish',
                    'meta_key'    => 'sumo_get_subscription_number',
                    'meta_value'  => $subscription_no,
                        ), true );

                echo isset( $subscription_id[ 0 ] ) ? '<a href="' . esc_url( admin_url( "post.php?post={$subscription_id[ 0 ]}&action=edit" ) ) . '">#' . esc_html( $subscription_no ) . '</a>' : esc_html( "#{$subscription_no}" );
                break;
            case 'subscribed_product':
                echo esc_html( get_post_meta( $post_id, 'subscription_name', true ) );
                break;
            case 'order_id':
                $order_id = get_post_meta( $post_id, 'orderid', true );
                echo '<a href="' . esc_url( admin_url( "post.php?post={$order_id}&action=edit" ) ) . '">#' . esc_html( $order_id ) . '</a>';
                break;
            case 'event':
            case 'date':
                echo wp_kses_post( get_post_meta( $post_id, $column, true ) );
                break;
        }
    }

    /**
     * Render individual cron event columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_cron_event_columns( $column, $post_id ) {
        $subscription_id = absint( get_post_meta( $post_id, '_sumo_subscription_id', true ) );
        $cron_events     = get_post_meta( $post_id, '_sumo_subscription_cron_events', true );
        $event_name      = array();
        $next_run        = array();
        $arguments       = array();

        if ( isset( $cron_events[ $subscription_id ] ) && is_array( $cron_events[ $subscription_id ] ) ) {
            foreach ( $cron_events[ $subscription_id ] as $_event_name => $args ) {
                if ( ! is_array( $args ) ) {
                    continue;
                }

                $event_name[] = $_event_name;

                $event_time = '';
                foreach ( $args as $event_timestamp => $event_args ) {
                    if ( ! is_numeric( $event_timestamp ) ) {
                        continue;
                    }

                    $event_time .= sumo_get_subscription_date( $event_timestamp ) . nl2br( "\n[" . sumo_get_subscription_date_difference( $event_timestamp ) . "]\n\n" );
                }
                $next_run[] = $event_time;

                $arg = '';
                foreach ( $args as $event_timestamp => $event_args ) {
                    if ( ! is_array( $event_args ) ) {
                        continue;
                    }
                    $arg .= '"' . implode( ', ', $event_args ) . '",&nbsp;<br>';
                }

                if ( '' !== $arg ) {
                    $arguments[] = $arg;
                }
            }
        }

        switch ( $column ) {
            case 'event_id':
                echo esc_html( "#{$post_id}" );
                break;
            case 'subscription_no':
                echo wp_kses_post( sumo_display_subscription_ID( $subscription_id ) );
                break;
            case 'event_name':
                echo $event_name ? wp_kses_post( implode( ',' . str_repeat( '</br>', 4 ), $event_name ) ) : 'None';
                break;
            case 'next_run':
                echo $next_run ? '<b>*</b>' . wp_kses_post( implode( '<b>*</b> ', $next_run ) ) : 'None';
                break;
            case 'arguments':
                echo $arguments ? wp_kses_post( implode( str_repeat( '</br>', 4 ), $arguments ) ) : 'None';
                break;
        }
    }

    /**
     * Set row actions.
     *
     * @param array   $actions Array of actions.
     * @param WP_Post $post Current post object.
     * @return array
     */
    public static function row_actions( $actions, $post ) {
        switch ( $post->post_type ) {
            case 'sumosubscriptions':
                unset( $actions[ 'inline hide-if-no-js' ], $actions[ 'view' ] );
                break;
            case 'sumomasterlog':
                unset( $actions[ 'inline hide-if-no-js' ], $actions[ 'edit' ] );
                break;
            case 'sumosubs_cron_events':
                unset( $actions[ 'inline hide-if-no-js' ], $actions[ 'trash' ], $actions[ 'edit' ] );
                break;
        }

        return $actions;
    }

    /**
     * Render filters
     */
    public static function render_filters() {
        global $typenow;

        if ( 'sumosubscriptions' !== $typenow ) {
            return;
        }

        $selected_status       = isset( $_REQUEST[ 'sumo_subscription_status' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'sumo_subscription_status' ] ) ) : '';
        $selected_from_date    = isset( $_REQUEST[ 'sumo_subscription_from_date' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'sumo_subscription_from_date' ] ) ) : '';
        $selected_to_date      = isset( $_REQUEST[ 'sumo_subscription_to_date' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'sumo_subscription_to_date' ] ) ) : '';
        $subscription_statuses = array_merge( array( '' => 'All' ), sumo_get_subscription_statuses() );
        ?>
        <select name="sumo_subscription_status">
            <?php foreach ( $subscription_statuses as $status => $status_label ) { ?>
                <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $selected_status, $status, false ); ?>><?php echo esc_html( $status_label ); ?></option>
            <?php } ?>
        </select>
        <input type="text" id="sumo_subscription_from_date" placeholder="<?php esc_attr_e( 'Enter From Date', 'sumosubscriptions' ); ?>" name="sumo_subscription_from_date" value="<?php echo esc_attr( $selected_from_date ); ?>">
        <input type="text" id="sumo_subscription_to_date" placeholder="<?php esc_attr_e( 'Enter To Date', 'sumosubscriptions' ); ?>" name="sumo_subscription_to_date" value="<?php echo esc_attr( $selected_to_date ); ?>">
        <?php
    }

    /**
     * Render blank slate.
     * 
     * @param string $which String which tablenav is being shown.
     */
    public static function extra_tablenav( $which ) {
        global $typenow;

        if ( 'top' === $which && 'sumosubscriptions' === $typenow ) {
            echo '<a class="button-primary" target="blank" href="' . esc_url( SUMOSubs_Admin_Subscriptions_Exporter::get_exporter_page_url() ) . '">' . esc_html__( 'Export', 'sumosubscriptions' ) . '</a>';
        }
    }

    /**
     * Handle any filters.
     *
     * @param array $query_vars Query vars.
     * @return array
     */
    public static function request_query( $query_vars ) {
        global $typenow;

        if ( ! in_array( $typenow, array_keys( self::$custom_post_types ) ) ) {
            return $query_vars;
        }

        //Sorting
        if ( empty( $query_vars[ 'orderby' ] ) ) {
            $query_vars[ 'orderby' ] = 'ID';
        }

        if ( empty( $query_vars[ 'order' ] ) ) {
            $query_vars[ 'order' ] = 'DESC';
        }

        if ( ! empty( $query_vars[ 'orderby' ] ) ) {
            switch ( $query_vars[ 'orderby' ] ) {
                case 'sub_start_date':
                case 'trial_end_date':
                case 'sub_end_date':
                case 'last_payment_date':
                case 'next_payment_date':
                case 'sub_exp_date':
                    $query_vars[ 'meta_key' ]  = sprintf( 'sumo_get_%s', $query_vars[ 'orderby' ] );
                    $query_vars[ 'meta_type' ] = 'DATETIME';
                    $query_vars[ 'orderby' ]   = 'meta_value';
                    break;
                case 'parent_order_id':
                    $query_vars[ 'meta_key' ]  = sprintf( 'sumo_get_%s', $query_vars[ 'orderby' ] );
                    $query_vars[ 'orderby' ]   = 'meta_value_num';
                    break;
                case 'orderid':
                    $query_vars[ 'meta_key' ]  = $query_vars[ 'orderby' ];
                    $query_vars[ 'orderby' ]   = 'meta_value_num';
                    break;
                case 'user_name':
                case 'subscription_name':
                case 'subscription_no':
                    $query_vars[ 'meta_key' ]  = $query_vars[ 'orderby' ];
                    $query_vars[ 'orderby' ]   = 'meta_value';
                    break;
                case 'buyer_email':
                    $query_vars[ 'meta_key' ]  = sprintf( 'sumo_%s', $query_vars[ 'orderby' ] );
                    $query_vars[ 'orderby' ]   = 'meta_value';
                    break;
            }
        }

        //Search
        if ( ! empty( $_REQUEST[ 'sumo_subscription_status' ] ) ) {
            $query_vars[ 'meta_key' ]   = 'sumo_get_status';
            $query_vars[ 'meta_value' ] = wc_clean( wp_unslash( $_REQUEST[ 'sumo_subscription_status' ] ) );
        }

        if ( ! empty( $_REQUEST[ 'sumo_subscription_from_date' ] ) ) {
            $query_vars[ 'date_query' ][ 'inclusive' ] = true;
            $query_vars[ 'date_query' ][ 'after' ]     = wc_clean( wp_unslash( $_REQUEST[ 'sumo_subscription_from_date' ] ) ) . ' 00:00:00';

            if ( ! empty( $_REQUEST[ 'sumo_subscription_to_date' ] ) ) {
                $query_vars[ 'date_query' ][ 'before' ] = wc_clean( wp_unslash( $_REQUEST[ 'sumo_subscription_to_date' ] ) ) . ' 23:59:59';
            } else {
                $query_vars[ 'date_query' ][ 'before' ] = gmdate( 'Y-m-d' ) . ' 23:59:59';
            }
        } else if ( ! empty( $_REQUEST[ 'sumo_subscription_to_date' ] ) ) {
            $query_vars[ 'date_query' ][ 'before' ]    = wc_clean( wp_unslash( $_REQUEST[ 'sumo_subscription_to_date' ] ) ) . ' 23:59:59';
            $query_vars[ 'date_query' ][ 'inclusive' ] = true;
        }
        return $query_vars;
    }

    /**
     * Change the label when searching posts.
     *
     * @param mixed $query Current search query.
     * @return string
     */
    public static function search_label( $query ) {
        global $pagenow, $typenow;

        if ( 'edit.php' === $pagenow && in_array( $typenow, array_keys( self::$custom_post_types ) ) && get_query_var( "{$typenow}_search" ) && isset( $_GET[ 's' ] ) ) {
            return wc_clean( wp_unslash( $_GET[ 's' ] ) );
        }

        return $query;
    }

    /**
     * Query vars for custom searches.
     *
     * @param mixed $public_query_vars Array of query vars.
     * @return array
     */
    public static function add_custom_query_var( $public_query_vars ) {
        return array_merge( $public_query_vars, array_map( function( $type ) {
                    return "{$type}_search";
                }, array_keys( self::$custom_post_types ) ) );
    }

    /**
     * Search custom fields as well as content.
     *
     * @param WP_Query $wp Query object.
     */
    public static function search_custom_fields( $wp ) {
        global $pagenow, $wpdb;

        if ( 'edit.php' !== $pagenow || empty( $wp->query_vars[ 's' ] ) || ! in_array( $wp->query_vars[ 'post_type' ], array_keys( self::$custom_post_types ) ) || ! isset( $_GET[ 's' ] ) ) { // WPCS: input var ok.
            return;
        }

        $term     = str_replace( '#', '', wc_clean( wp_unslash( $_GET[ 's' ] ) ) );
        $_wpdb    = &$wpdb;
        $post_ids = array();

        switch ( $wp->query_vars[ 'post_type' ] ) {
            case 'sumosubscriptions':
                $search_fields = array(
                    'sumo_get_subscription_number',
                    'sumo_product_name',
                    'sumo_get_parent_order_id',
                    'sumo_buyer_email',
                    'sumo_get_sub_start_date',
                    'sumo_get_sub_end_date',
                    'sumo_get_sub_exp_date',
                    'sumo_get_trial_end_date',
                    'sumo_get_last_payment_date',
                    'sumo_get_next_payment_date',
                    'sumo_get_renewal_id',
                );

                $order_search_fields = array(
                    '_billing_address_index',
                    '_billing_first_name',
                    '_billing_last_name',
                );
                break;
            case 'sumomasterlog':
                $search_fields       = array(
                    'subscription_name',
                    'subscription_no',
                    'user_name',
                    'event',
                    'orderid',
                    'date',
                    'action',
                );
                break;
            case 'sumosubs_cron_events':
                $search_fields       = array(
                    '_sumo_subscription_id',
                );
                break;
        }

        if ( ! empty( $search_fields ) ) {
            if ( is_numeric( $term ) ) {
                $post_ids = array_unique(
                        array_merge( array( absint( $term ) ), $_wpdb->get_col(
                                        $_wpdb->prepare(
                                                "SELECT DISTINCT p1.post_id FROM {$_wpdb->postmeta} p1 WHERE p1.meta_value LIKE %s AND p1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $search_fields ) ) . "')", '%' . $_wpdb->esc_like( wc_clean( $term ) ) . '%'
                                        )
                                )
                        ) );
            } else {
                //may be subscription is searched based on billing details so that we are using as like WC Order search
                if ( ! empty( $order_search_fields ) ) {
                    $maybe_order_ids = array_unique(
                            $_wpdb->get_col(
                                    $_wpdb->prepare(
                                            "SELECT DISTINCT p1.post_id FROM {$_wpdb->postmeta} p1 WHERE p1.meta_value LIKE %s AND p1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $order_search_fields ) ) . "')", '%' . $_wpdb->esc_like( wc_clean( $term ) ) . '%'
                                    )
                            ) );

                    $post_ids = $_wpdb->get_col(
                            $_wpdb->prepare(
                                    "SELECT DISTINCT p1.post_id FROM {$_wpdb->postmeta} p1 WHERE p1.meta_key LIKE %s AND p1.meta_value IN ('" . implode( "','", array_map( 'esc_sql', $maybe_order_ids ) ) . "')", 'sumo_get_parent_order_id'
                            ) );
                }

                $post_ids = array_unique(
                        array_merge(
                                $post_ids, $_wpdb->get_col(
                                        $_wpdb->prepare(
                                                "SELECT DISTINCT p1.post_id FROM {$_wpdb->postmeta} p1 WHERE p1.meta_value LIKE %s AND p1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $search_fields ) ) . "')", '%' . $_wpdb->esc_like( wc_clean( $term ) ) . '%'
                                        )
                                )
                        ) );
            }
        }

        if ( ! empty( $post_ids ) ) {
            // Remove "s" - we don't want to search post name.
            unset( $wp->query_vars[ 's' ] );

            // so we know we're doing this.
            $wp->query_vars[ "{$wp->query_vars[ 'post_type' ]}_search" ] = true;

            // Search by found posts.
            $wp->query_vars[ 'post__in' ] = array_merge( $post_ids, array( 0 ) );
        }
    }

    /**
     * Show confirmation message that subscription status changed for number of subscriptions.
     */
    public static function bulk_admin_notices() {
        global $post_type, $pagenow;

        // Bail out if not on shop order list page.
        if ( 'edit.php' !== $pagenow || 'sumosubscriptions' !== $post_type || ! isset( $_REQUEST[ 'bulk_action' ] ) ) {
            return;
        }

        $number  = isset( $_REQUEST[ 'changed' ] ) ? absint( $_REQUEST[ 'changed' ] ) : 0;
        // Check if any status changes happened.
        /* translators: %d: subscriptions count */
        $message = sprintf( _n( '%d subscription status changed.', '%d subscription statuses changed.', $number, 'sumosubscriptions' ), number_format_i18n( $number ) );
        echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
    }
}

SUMOSubs_Admin_Post_Types::init();
