<?php
use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        $GLOBALS['added_actions'][] = array(
            'hook'          => $hook,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }
}

if ( ! interface_exists( 'WC_Logger_Interface' ) ) {
    interface WC_Logger_Interface {
        public function emergency( $message, array $context = array() );
        public function alert( $message, array $context = array() );
        public function critical( $message, array $context = array() );
        public function error( $message, array $context = array() );
        public function warning( $message, array $context = array() );
        public function notice( $message, array $context = array() );
        public function info( $message, array $context = array() );
        public function debug( $message, array $context = array() );
        public function log( $level, $message, array $context = array() );
    }
}

require_once __DIR__ . '/../scheduler.php';

class FailedActionManagerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['added_actions'] = array();
    }

    public function test_init_failed_action_manager_sets_hooks_and_returns_singleton() {
        $logger = new class implements WC_Logger_Interface {
            public function emergency( $message, array $context = array() ) {}
            public function alert( $message, array $context = array() ) {}
            public function critical( $message, array $context = array() ) {}
            public function error( $message, array $context = array() ) {}
            public function warning( $message, array $context = array() ) {}
            public function notice( $message, array $context = array() ) {}
            public function info( $message, array $context = array() ) {}
            public function debug( $message, array $context = array() ) {}
            public function log( $level, $message, array $context = array() ) {}
        };

        $instance1 = ASWC_Scheduler_API::init_failed_action_manager( $logger );
        $instance2 = ASWC_Scheduler_API::init_failed_action_manager( $logger );

        $this->assertInstanceOf( ASWC_Scheduler_Failed_Action_Manager::class, $instance1 );
        $this->assertSame( $instance1, $instance2 );
    }

    public function test_get_action_hook_label_strips_new_prefix() {
        $logger = new class implements WC_Logger_Interface {
            public function emergency( $message, array $context = array() ) {}
            public function alert( $message, array $context = array() ) {}
            public function critical( $message, array $context = array() ) {}
            public function error( $message, array $context = array() ) {}
            public function warning( $message, array $context = array() ) {}
            public function notice( $message, array $context = array() ) {}
            public function info( $message, array $context = array() ) {}
            public function debug( $message, array $context = array() ) {}
            public function log( $level, $message, array $context = array() ) {}
        };

        $manager   = new ASWC_Scheduler_Failed_Action_Manager( $logger );
        $method    = new ReflectionMethod( $manager, 'get_action_hook_label' );
        $method->setAccessible( true );

        $label = $method->invoke( $manager, 'advanced_scheduled_subscription_payment' );
        $this->assertSame( 'subscription payment', $label );
    }

    public function test_option_prefix_uses_aswc_namespace() {
        $this->assertSame(
            'advanced_subscriptions_woocommerce',
            ASWC_Scheduler_Failed_Action_Manager::OPTION_PREFIX
        );
    }

    public function test_log_uses_injected_logger() {
        $logger = new class implements WC_Logger_Interface {
            public $logs = array();

            public function emergency( $message, array $context = array() ) {}
            public function alert( $message, array $context = array() ) {}
            public function critical( $message, array $context = array() ) {}
            public function error( $message, array $context = array() ) { $this->logs[] = array( $message, $context ); }
            public function warning( $message, array $context = array() ) {}
            public function notice( $message, array $context = array() ) {}
            public function info( $message, array $context = array() ) {}
            public function debug( $message, array $context = array() ) {}
            public function log( $level, $message, array $context = array() ) {}
        };

        $manager = new ASWC_Scheduler_Failed_Action_Manager( $logger );
        $method  = new ReflectionMethod( $manager, 'log' );
        $method->setAccessible( true );
        $method->invoke( $manager, 'msg', array( 'foo' => 'bar' ) );

        $this->assertCount( 1, $logger->logs );
        $this->assertSame( 'msg', $logger->logs[0][0] );
        $this->assertSame( 'bar', $logger->logs[0][1]['foo'] );
        $this->assertSame( 'failed-scheduled-actions', $logger->logs[0][1]['source'] );
    }
}

