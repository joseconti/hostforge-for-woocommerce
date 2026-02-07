<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Abstract Subscription Cron Event
 * 
 * @abstract SUMOSubs_Abstract_Cron_Event
 */
abstract class SUMOSubs_Abstract_Cron_Event {

	/**
	 * The Subscription Cron Event post ID. Handles each Subscription Cron.
	 * 
	 * @var int 
	 */
	public $event_id = 0;

	/**
	 * The Subscription post ID
	 * 
	 * @var int 
	 */
	public $subscription_id = 0;

	/**
	 * Cron Events for the Subscription
	 * 
	 * @var array 
	 */
	public $cron_events = array();

	/**
	 * Post Type for Cron Events
	 * 
	 * @var string 
	 */
	protected $post_type = 'sumosubs_cron_events';

	/**
	 * Cron Events query
	 * 
	 * @var WP_Query 
	 */
	public $query;

	/**
	 * Constructor.
	 */
	public function __construct( $subscription ) {
		$this->populate( $subscription );
	}

	/**
	 * Populate the Subscription Cron Event
	 *
	 * @param int $subscription The Subscription post ID
	 */
	protected function populate( $subscription ) {
		if ( ! $subscription ) {
			return false;
		}

		$this->subscription_id = absint( $subscription );
		$this->init_event_id();
		$this->event_id        = $this->get_event_id();
		$this->cron_events     = $this->get_cron_events();
	}

	/**
	 * Init Event for the Subscription
	 */
	public function init_event_id() {
		//Fire once for the Subscription
		if ( ! $this->exists() ) {
			$this->event_id = wp_insert_post( array(
				'post_type'   => $this->post_type,
				'post_status' => 'publish',
				'post_author' => 1,
				'post_title'  => __( 'Subscription Cron Job', 'sumosubscriptions' ),
					) );

			add_post_meta( $this->event_id, '_sumo_subscription_id', $this->subscription_id );
		}
	}

	/**
	 * Get Cron Event ID
	 */
	public function get_event_id() {
		if ( $this->exists() ) {
			$this->get_events_query();

			foreach ( $this->query->posts as $event ) {
				$this->event_id = $event->ID;
				break;
			}
		}
		return $this->event_id;
	}

	/**
	 * Check whether Cron Event exists
	 *
	 * @return boolean
	 */
	public function exists() {
		if ( ! $this->get_events_query() ) {
			return false;
		}

		return $this->query->have_posts();
	}

	/**
	 * Get Cron Events WP_Query data
	 *
	 * @return \WP_Query|boolean
	 */
	public function get_events_query() {
		if ( ! $this->subscription_id ) {
			return false;
		}

		$this->query = sumosubscriptions()->query->get( array(
			'type'         => $this->post_type,
			'status'       => 'publish',
			'return'       => 'q',
			'limit'        => 1,
			'meta_key'     => '_sumo_subscription_id',
			'meta_value'   => $this->subscription_id,
			'meta_compare' => '=',
				) );
		return $this->query;
	}

	/**
	 * Get Cron Events associated for the Subscription
	 *
	 * @return array
	 */
	public function get_cron_events() {
		$_cron_events = get_post_meta( $this->event_id, '_sumo_subscription_cron_events', true );

		if ( isset( $_cron_events[ $this->subscription_id ] ) && is_array( $_cron_events[ $this->subscription_id ] ) ) {
			$this->cron_events = $_cron_events[ $this->subscription_id ];
		}

		return $this->cron_events;
	}

	/**
	 * Set Cron Event to Schedule. It may be elapsed by wp_schedule_event
	 *
	 * @param int $timestamp
	 * @param string $event_name
	 * @param array $args
	 * @return boolean true on success
	 */
	public function set_cron_event( $timestamp, $event_name, $args = array() ) {
		if ( ! is_numeric( $timestamp ) || ! $timestamp || ! is_array( $args ) ) {
			return false;
		}

		$new_arg           = array( absint( $timestamp ) => $args );
		$this->cron_events = $this->get_cron_events();

		if ( $this->exists() ) {
			//may the Event has multiple timestamps so that we are doing this way 
			if ( isset( $this->cron_events[ $event_name ] ) && is_array( $this->cron_events[ $event_name ] ) ) {
				$this->cron_events[ $event_name ] += $new_arg;
			} else {
				//may the new Event is registering
				$this->cron_events[ $event_name ] = $new_arg;
			}

			if ( $this->set_events() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Update Cron events
	 *
	 * @return boolean true on success
	 */
	public function set_events() {
		if ( ! is_array( $this->cron_events ) ) {
			return;
		}

		update_post_meta( $this->event_id, '_sumo_subscription_cron_events', array(
			$this->subscription_id => $this->cron_events,
		) );

		return true;
	}

	/**
	 * UnSchedule Cron events.
	 *
	 * @param array $events unsetting every Cron events if left empty
	 */
	public function unset_events( $events = array() ) {
		if ( empty( $events ) ) {
			$events = array(
				'start_subscription',
				'create_renewal_order',
				'notify_invoice_reminder',
				'notify_expiry_reminder',
				'notify_overdue',
				'notify_suspend',
				'notify_cancel',
				'notify_expire',
				'automatic_pay',
				'automatic_resume',
				'switch_to_manual_pay_mode',
				'retry_automatic_pay_in_overdue',
				'retry_automatic_pay_in_suspended',
			);
		}
		$events            = ( array ) $events;
		$this->cron_events = $this->get_cron_events();

		if ( $this->exists() ) {
			foreach ( $this->cron_events as $event_name => $event_args ) {
				if ( in_array( $event_name, $events ) ) {
					unset( $this->cron_events[ $event_name ] );
				}
			}

			$this->set_events();
		}
	}
}
