<?php

defined( 'ABSPATH' ) || exit;

/**
 * Post Types
 * 
 * Registers post types
 * 
 * @class SUMOSubs_Post_Types
 * @package Class
 */
class SUMOSubs_Post_Types {

	/**
	 * Init SUMOSubs_Post_Types.
	 */
	public static function init() {
		add_action( 'init', __CLASS__ . '::register_post_types' );
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types() {
		if ( ! post_type_exists( 'sumosubscriptions' ) ) {
			register_post_type( 'sumosubscriptions', array(
				'labels'          => array(
					'name'               => __( 'Subscriptions', 'sumosubscriptions' ),
					'singular_name'      => _x( 'Subscription', 'singular name', 'sumosubscriptions' ),
					'menu_name'          => _x( 'Subscriptions', 'admin menu', 'sumosubscriptions' ),
					'add_new'            => __( 'Add subscription', 'sumosubscriptions' ),
					'add_new_item'       => __( 'Add new subscription', 'sumosubscriptions' ),
					'new_item'           => __( 'New subscription', 'sumosubscriptions' ),
					'edit_item'          => __( 'Edit subscription', 'sumosubscriptions' ),
					'view_item'          => __( 'View subscription', 'sumosubscriptions' ),
					'search_items'       => __( 'Search subscriptions', 'sumosubscriptions' ),
					'not_found'          => __( 'No subscription found.', 'sumosubscriptions' ),
					'not_found_in_trash' => __( 'No subscription found in trash.', 'sumosubscriptions' ),
				),
				'description'     => __( 'This is where store subscriptions are stored.', 'sumosubscriptions' ),
				'public'          => false,
				'show_ui'         => true,
				'capability_type' => 'post',
				'show_in_menu'    => 'sumosubscriptions',
				'rewrite'         => false,
				'has_archive'     => false,
				'supports'        => false,
				'map_meta_cap'    => true,
				'capabilities'    => array(
					'create_posts' => 'do_not_allow',
				),
			) );
		}

		if ( ! post_type_exists( 'sumosubs_cron_events' ) ) {
			register_post_type( 'sumosubs_cron_events', array(
				'labels'              => array(
					'name'         => __( 'Cron events', 'sumosubscriptions' ),
					'menu_name'    => _x( 'Cron events', 'admin menu', 'sumosubscriptions' ),
					'search_items' => __( 'Search cron events', 'sumosubscriptions' ),
					'not_found'    => __( 'No cron event found.', 'sumosubscriptions' ),
				),
				'description'         => __( 'This is where scheduled cron events are stored.', 'sumosubscriptions' ),
				'public'              => false,
				'capability_type'     => 'post',
				/**
				 * Need to show scheduled crons menu?
				 * 
				 * @since 1.0
				 */
				'show_ui'             => apply_filters( 'sumosubscriptions_show_cron_events_post_type_ui', false ),
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_in_menu'        => 'sumosubscriptions',
				'hierarchical'        => false,
				'show_in_nav_menus'   => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => false,
				'has_archive'         => false,
				'map_meta_cap'        => true,
				'capabilities'        => array(
					'create_posts' => 'do_not_allow',
				),
			) );
		}

		if ( ! post_type_exists( 'sumomasterlog' ) ) {
			register_post_type( 'sumomasterlog', array(
				'labels'          => array(
					'name'         => __( 'Master log', 'sumosubscriptions' ),
					'menu_name'    => _x( 'Master log', 'admin menu', 'sumosubscriptions' ),
					'search_items' => __( 'Search log', 'sumosubscriptions' ),
					'not_found'    => __( 'No logs found.', 'sumosubscriptions' ),
				),
				'description'     => __( 'This is where subscription logs are stored.', 'sumosubscriptions' ),
				'public'          => false,
				'show_ui'         => apply_filters( 'sumosubscriptions_show_masterlog_post_type_ui', false ),
				'capability_type' => 'post',
				'show_in_menu'    => 'sumosubscriptions',
				'rewrite'         => false,
				'has_archive'     => false,
				'supports'        => false,
				'map_meta_cap'    => true,
				'capabilities'    => array(
					'create_posts' => 'do_not_allow',
				),
			) );
		}
	}
}

SUMOSubs_Post_Types::init();
