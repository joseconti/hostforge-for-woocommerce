<?php
$admin_dismiss_url = add_query_arg( array( 'sumosubs_action' => 'dismiss_background_updates_notice', 'sumosubs_nonce' => wp_create_nonce( 'sumosubs-background-updates' ) ), sumosubs_get_current_admin_url() );
?>
<div id="message" class="updated woocommerce-message">
    <h4><?php esc_html_e( 'SUMO Subscriptions Background Process Status', 'sumosubscriptions' ); ?></h4>
    <p>
        <?php
        foreach ( $notice_updates as $action_key => $_action ) {
            switch ( $action_key ) {
                case 'product_update':
                    if ( 'completed' === $_action[ 'action_status' ] ) {
                        esc_html_e( '- The plugin settings configuration was successfully updated', 'sumosubscriptions' );
                    } elseif ( 'sumosubscriptions_find_products_to_bulk_update' === $_action[ 'current_action' ] ) {
                        esc_html_e( '- Preparing to assign the subscription settings to selected product(s)/categories by a bulk update', 'sumosubscriptions' );
                    } else {
                        esc_html_e( '- The plugin settings configuration is updating.', 'sumosubscriptions' );
                    }
                    break;
                case 'subscription_update':
                    if ( 'completed' === $_action[ 'action_status' ] ) {
                        esc_html_e( '- The deleted product was successfully replaced with the new product in the subscriptions.', 'sumosubscriptions' );
                    } else {
                        if ( 'sumosubscriptions_find_subscriptions_to_bulk_update' === $_action[ 'current_action' ] ) {
                            esc_html_e( '- Preparing to replace the deleted product with the new product in the subscriptions.', 'sumosubscriptions' );
                        } else {
                            esc_html_e( '- Replacing the deleted product with the new product in the subscriptions.', 'sumosubscriptions' );
                        }
                    }
                    break;
            }

            if ( 'in_progress' === $_action[ 'action_status' ] ) {
                ?>
                &nbsp;<a target="_blank" href="<?php echo esc_url( admin_url( "admin.php?page=wc-status&tab=action-scheduler&s={$_action[ 'current_action' ]}&status=pending" ) ); ?>"><?php esc_html_e( 'View progress &rarr;', 'sumosubscriptions' ); ?></a>
                <?php
            }

            echo '<br>';
        }
        ?>
        <a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( $admin_dismiss_url ); ?>"><?php esc_html_e( 'dismiss', 'sumosubscriptions' ); ?></a>
    </p>
</div>
