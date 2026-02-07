<?php
/**
 * Failed Scheduled Action Manager for subscription events.
 *
 * Migrated from the legacy scheduler implementation to the Scheduler API so that
 * failures triggered by Action Scheduler can be logged centrally.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI
 */

class ASWC_Scheduler_Failed_Action_Manager {
    /**
     * Option prefix used to store notice state.
     */
    const OPTION_PREFIX = 'advanced_subscriptions_woocommerce';

    /**
     * Action hooks we're interested in tracking.
     *
     * Populated from the `advanced_subscriptions_woocommerce_failed_action_hooks`
     * option so integrators can customise which hooks are monitored. Defaults
     * to the core subscription events when the option is absent.
     *
     * @var array
     */
    protected $tracked_scheduled_actions = array();

    /**
     * WC Logger instance for logging messages.
     *
     * @var WC_Logger_Interface
     */
    protected $logger;

    /**
     * Exceptions caught by WC while this class is listening to the
     * `woocommerce_caught_exception` action.
     *
     * @var Exception[]
     */
    protected $exceptions = array();

    /**
     * Constructor.
     *
     * @param WC_Logger_Interface $logger The WC Logger instance.
     */
    public function __construct( WC_Logger_Interface $logger ) {
        $this->logger = $logger;

        $default_hooks = array(
            'advanced_scheduled_subscription_trial_end',
            'advanced_scheduled_subscription_payment',
            'advanced_scheduled_subscription_payment_retry',
            'advanced_scheduled_subscription_expiration',
            'advanced_scheduled_subscription_end_of_prepaid_term',
        );

        $configured_hooks = (array) get_option( self::OPTION_PREFIX . '_failed_action_hooks', $default_hooks );

        $this->tracked_scheduled_actions = array_fill_keys( $configured_hooks, 1 );
    }

    /**
     * Attach callbacks.
     */
    public function init() {
        add_action( 'action_scheduler_failed_action', array( $this, 'log_action_scheduler_failure' ), 10, 2 );
        add_action( 'action_scheduler_failed_execution', array( $this, 'log_action_scheduler_failure' ), 10, 2 );
        add_action( 'action_scheduler_unexpected_shutdown', array( $this, 'log_action_scheduler_failure' ), 10, 2 );
        add_action( 'admin_notices', array( $this, 'maybe_show_admin_notice' ) );
        add_action( 'action_scheduler_begin_execute', array( $this, 'maybe_attach_exception_listener' ) );
    }

    /**
     * Log a message to the failed-scheduled-actions log.
     *
     * @param string $message Log message.
     * @param array  $context Context to include in the log.
     */
    protected function log( $message, $context = array() ) {
        $context['source'] = 'failed-scheduled-actions';
        $this->logger->error( $message, $context );
    }

    /**
     * When a scheduled action failure is triggered, log information about the failed action to a WC logger.
     *
     * @param int                 $action_id The ID of the action which failed.
     * @param int|Exception|array $error     Timeout seconds or the error/exception that caused the failure.
     */
    public function log_action_scheduler_failure( $action_id, $error ) {
        $action = ASWC_Scheduler_API::get_action( $action_id );

        if ( ! isset( $this->tracked_scheduled_actions[ ASWC_Scheduler_API::get_action_hook( $action ) ] ) ) {
            return;
        }

        $subscription_action = $this->get_action_hook_label( ASWC_Scheduler_API::get_action_hook( $action ) );
        $context             = $this->get_context_from_action_error( $action, $error );

        switch ( current_filter() ) {
            case 'action_scheduler_failed_action':
                $this->log( sprintf( 'scheduled action %s (%s) failed to finish processing after %s seconds', $action_id, $subscription_action, absint( $error ) ), $context );
                break;
            case 'action_scheduler_failed_execution':
                $this->log( sprintf( 'scheduled action %s (%s) failed to finish processing due to the following exception: %s', $action_id, $subscription_action, $this->get_message_from_exception( $error ) ), $context );
                break;
            case 'action_scheduler_unexpected_shutdown':
                $this->log( sprintf( 'scheduled action %s (%s) failed to finish processing due to the following error: %s', $action_id, $subscription_action, $this->get_message_from_error( $error ) ), $context );
                break;
        }

        if ( ASWC_Scheduler_API::is_woocommerce_pre( '8.6' ) ) {
            foreach ( $context as $key => $value ) {
                if ( is_array( $value ) ) {
                    $value = implode( PHP_EOL, $value );
                }
                $this->log( "{$key}: {$value}" );
            }
        }

        // Store information about the scheduled action for displaying an admin notice.
        $failed_scheduled_actions = get_option( self::OPTION_PREFIX . '_failed_scheduled_actions', array() );

        $failed_scheduled_actions[ $action_id ] = array(
            'args' => ASWC_Scheduler_API::get_action_args_from_action( $action ),
            'type' => $subscription_action,
        );

        update_option( self::OPTION_PREFIX . '_failed_scheduled_actions', $failed_scheduled_actions );
    }

    /**
     * Creates a new exception listener when processing subscription-related scheduled actions.
     *
     * @param int $action_id The ID of the scheduled action being run.
     */
    public function maybe_attach_exception_listener( $action_id ) {
        $action = ASWC_Scheduler_API::get_action( $action_id );

        if ( ! $action || ! isset( $this->tracked_scheduled_actions[ ASWC_Scheduler_API::get_action_hook( $action ) ] ) ) {
            return;
        }

        add_action( 'action_scheduler_after_execute', array( $this, 'clear_exceptions_and_detach_listener' ) );
        add_action( 'woocommerce_caught_exception', array( $this, 'handle_exception' ) );
    }

    /**
     * Adds an exception to the list of exceptions caught by WC.
     *
     * @param Exception $exception The exception that was caught.
     */
    public function handle_exception( $exception ) {
        $this->exceptions[] = $exception;
    }

    /**
     * Clears the list of exceptions caught by WC and detaches the listener.
     */
    public function clear_exceptions_and_detach_listener() {
        $this->exceptions = array();
        remove_action( 'woocommerce_caught_exception', array( $this, 'handle_exception' ) );
    }

    /**
     * Display an admin notice when a scheduled action failure has occurred.
     */
    public function maybe_show_admin_notice() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $this->maybe_disable_admin_notice();
        $failed_scheduled_actions = get_option( self::OPTION_PREFIX . '_failed_scheduled_actions', array() );

        if ( empty( $failed_scheduled_actions ) ) {
            return;
        }

        $affected_subscription_events = $separator = '';

        foreach ( array_slice( $failed_scheduled_actions, -10, 10 ) as $action ) {
            $id = false;

            if ( isset( $action['args']['subscription_id'] ) && ASWC_Scheduler_API::is_subscription( $action['args']['subscription_id'] ) ) {
                $id = $action['args']['subscription_id'];
            } elseif ( isset( $action['args']['order_id'] ) && ASWC_Scheduler_API::get_order( $action['args']['order_id'] ) ) {
                $id = $action['args']['order_id'];
            }

            if ( $id ) {
                $subject = '<a href="' . ASWC_Scheduler_API::get_edit_post_link( $id ) . '">#' . $id . '</a>';
            } else {
                $subject = 'unknown';
            }

            $affected_subscription_events .= $separator . $action['type'] . ' for ' . $subject;
            $separator = "\n";
        }

        aswc_display_failed_actions_notice( $failed_scheduled_actions, $affected_subscription_events );
    }

    /**
     * Handle requests to disable the failed scheduled actions admin notice.
     */
    protected function maybe_disable_admin_notice() {
        if ( isset( $_GET['_aswcnonce'] ) && wp_verify_nonce( wc_clean( wp_unslash( $_GET['_aswcnonce'] ) ), 'aswc_scheduled_action_timeout_error_notice' ) && isset( $_GET['aswc_scheduled_action_timeout_error_notice'] ) ) {
            delete_option( self::OPTION_PREFIX . '_failed_scheduled_actions' );
        }
    }

    /**
     * Retrieve a user friendly description of the scheduled action from the action hook.
     *
     * @param string $hook The scheduled action hook.
     *
     * @return string
     */
    protected function get_action_hook_label( $hook ) {
        return str_replace( array( 'advanced_scheduled_', '_' ), array( '', ' ' ), $hook );
    }

    /**
     * Retrieve a list of scheduled action args as a string.
     *
     * @param mixed $args The scheduled action args.
     *
     * @return string
     */
    protected function get_action_args_string( $args ) {
        $args_string = $separator = '';

        foreach ( $args as $key => $value ) {
            if ( is_scalar( $value ) ) {
                $args_string .= $separator . $key . ': ' . $value;
                $separator    = ', ';
            }
        }

        return $args_string;
    }

    /**
     * Generates a message from an exception.
     *
     * @param Exception $exception The exception to generate a message from.
     * @return string The message.
     */
    protected function get_message_from_exception( $exception ) {
        $previous  = $exception->getPrevious();
        $exception = $previous ? $previous : $exception;
        return $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine();
    }

    /**
     * Generates a message from an error array.
     *
     * @param array $error The error data to generate a message from.
     * @return string The message including the file and line number if available.
     */
    protected function get_message_from_error( $error ) {
        $message = $error['message'];

        if ( isset( $error['file'] ) ) {
            $message .= " in {$error['file']}";

            if ( isset( $error['line'] ) ) {
                $message .= ":{$error['line']}";
            }
        }

        return $message;
    }

    /**
     * Generates the additional context data that will be recorded with the error log entry.
     *
     * @param object               $action Scheduled action object that failed.
     * @param int|Exception|array  $error  The error data that caused the failure.
     */
    protected function get_context_from_action_error( $action, $error ) {
        $context = array(
            'action_args' => $this->get_action_args_string( ASWC_Scheduler_API::get_action_args_from_action( $action ) ),
            'attempts'    => ASWC_Scheduler_API::get_action_attempts_from_action( $action ),
            'claim_id'    => ASWC_Scheduler_API::get_action_claim_id_from_action( $action ),
        );

        if ( is_a( $error, 'Exception' ) ) {
            $previous              = $error->getPrevious();
            $context['error_trace'] = $previous ? $previous->getTraceAsString() : $error->getTraceAsString();
        }

        if ( ! empty( $this->exceptions ) ) {
            foreach ( $this->exceptions as $exception ) {
                $context['exceptions'][] = $this->get_message_from_exception( $exception );
            }
        }

        return $context;
    }
}

