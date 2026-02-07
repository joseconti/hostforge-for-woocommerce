<?php

/**
 * SUMOSubscriptions Uninstall
 *
 * Uninstalling SUMOSubscriptions deletes Cron hooks.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit ;
}

wp_clear_scheduled_hook( 'sumosubscriptions_background_updater' ) ;
