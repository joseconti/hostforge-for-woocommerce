<?php

/**
 * Our Admin Functions
 */
defined( 'ABSPATH' ) || exit;

/**
 * Get our screen ids.
 *
 * @return array
 */
function sumosubs_get_screen_ids() {
    $subs_screen_id = sanitize_title( __( 'SUMO Subscriptions', 'sumosubscriptions' ) );
    $screen_ids     = array(
        'sumosubscriptions',
        'edit-sumosubscriptions',
        $subs_screen_id . '_page_sumosubs-settings',
        $subs_screen_id . '_page_' . SUMOSubs_Admin_Subscriptions_Exporter::$exporter_page,
    );

    return apply_filters( 'sumosubs_screen_ids', $screen_ids );
}
