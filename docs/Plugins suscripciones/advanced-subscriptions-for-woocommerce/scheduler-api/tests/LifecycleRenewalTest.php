<?php
use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        // no-op for tests
    }
}
if ( ! function_exists( 'do_action' ) ) {
    function do_action( $hook, $arg = null ) {
        // no-op for tests
    }
}
if ( ! function_exists( 'aswc_get_subscription' ) ) {
    function aswc_get_subscription( $id ) {
        return $GLOBALS['mock_subscription'];
    }
}
if ( ! function_exists( 'aswc_create_renewal_order' ) ) {
    function aswc_create_renewal_order( $subscription ) {
        $GLOBALS['create_renewal_order_called'] = true;
        return $GLOBALS['mock_order'];
    }
}
if ( ! function_exists( '_x' ) ) {
    function _x( $text, $context = null, $domain = null ) {
        return $text;
    }
}
if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}
if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {}
}

require_once __DIR__ . '/../lifecycle/class-aswc-scheduler-lifecycle-events.php';

class Mock_Subscription_Renewal {
    public $status_updates = array();
    public $has_status = true;
    public $total = 0;

    public function has_status( $status ) {
        return $this->has_status && 'active' === $status;
    }

    public function get_total() {
        return $this->total;
    }

    public function is_manual() {
        return false;
    }

    public function get_payment_method() {
        return '';
    }

    public function payment_method_supports( $feature ) {
        return false;
    }

    public function update_status( $status, $note = '' ) {
        $this->status_updates[] = array( $status, $note );
    }
}

class Mock_Order_Renewal {
    public $payment_complete_called = false;
    public $total = 0;

    public function get_total() {
        return $this->total;
    }

    public function payment_complete() {
        $this->payment_complete_called = true;
    }
}

/**
 * @runTestsInSeparateProcesses
 */
class LifecycleRenewalTest extends TestCase {
    protected function setUp(): void {
        $GLOBALS['create_renewal_order_called'] = false;
        $GLOBALS['mock_order']                  = new Mock_Order_Renewal();
        $GLOBALS['mock_subscription']           = new Mock_Subscription_Renewal();
    }

    public function test_prepare_renewal_processes_subscription() {
        ASWC_Scheduler_Lifecycle_Events::prepare_renewal( 10 );

        $this->assertSame( array( array( 'on-hold', 'Subscription renewal payment due:' ) ), $GLOBALS['mock_subscription']->status_updates );
        $this->assertTrue( $GLOBALS['create_renewal_order_called'] );
        $this->assertTrue( $GLOBALS['mock_order']->payment_complete_called );
    }

    public function test_process_renewal_returns_false_when_conditions_fail() {
        $GLOBALS['mock_subscription']->has_status = false;

        $result = ASWC_Scheduler_Lifecycle_Events::process_renewal( 10, 'active', 'note' );

        $this->assertFalse( $result );
        $this->assertSame( array(), $GLOBALS['mock_subscription']->status_updates );
        $this->assertFalse( $GLOBALS['create_renewal_order_called'] );
    }
}
