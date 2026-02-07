<?php
use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        return $value;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        // No-op placeholder; callbacks are tracked manually in tests.
    }
}

if ( ! function_exists( 'has_action' ) ) {
    function has_action( $hook ) {
        global $hook_callbacks;
        return ! empty( $hook_callbacks[ $hook ] );
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( $hook, ...$args ) {
        global $last_hook, $last_hook_args;
        $last_hook      = $hook;
        $last_hook_args = $args;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) {
        return $url;
    }
}

if ( ! function_exists( '_x' ) ) {
    function _x( $text, $context = null, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'wc_get_order_status_name' ) ) {
    function wc_get_order_status_name( $status ) {
        return ucfirst( $status );
    }
}

if ( ! function_exists( 'WC' ) ) {
    class WC_Payment_Gateways_Stub {
        public $calls = 0;
        public function payment_gateways() {
            $this->calls++;
        }
    }
    $GLOBALS['wc_stub'] = new WC_Payment_Gateways_Stub();
    function WC() {
        return $GLOBALS['wc_stub'];
    }
}

require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';
require_once __DIR__ . '/../payments/class-aswc-scheduler-payments.php';
require_once __DIR__ . '/../scheduler.php';

class ASWC_Scheduler_Payments_Test extends ASWC_Scheduler_Payments {
    public $last_hook = null;

    public function trigger_gateway_renewal_payment_hook( $renewal_order ) {
        parent::trigger_gateway_renewal_payment_hook( $renewal_order );
        global $last_hook;
        $this->last_hook = $last_hook;
    }
}

class ASWC_Scheduler_Payments_Custom_Retry extends ASWC_Scheduler_Payments_Test {
    private $subscription_for_hook;

    public function gateway_scheduled_subscription_payment( $subscription, $deprecated = null ) {
        $this->subscription_for_hook = $subscription;
        parent::gateway_scheduled_subscription_payment( $subscription, $deprecated );
    }

    public function trigger_gateway_renewal_payment_hook( $renewal_order ) {
        parent::trigger_gateway_renewal_payment_hook( $renewal_order );
        if ( $this->subscription_for_hook ) {
            $this->schedule_retry( $this->subscription_for_hook, time() + 60, 'gateway_group' );
        }
    }
}

class Gateway_Test_Scheduler_Core extends ASWC_Scheduler_Core {
    public $scheduled = array();

    protected function build_key( $hook, $args, $group ) {
        ksort( $args );
        return ( $group ?? self::ACTION_GROUP ) . '|' . $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args, $group ?? self::ACTION_GROUP );
        $this->scheduled[ $key ] = $timestamp;
        return 1;
    }

    public function unschedule_actions( $action_hook, $action_args, $group = null ) {
        if ( false === $group ) {
            foreach ( array_keys( $this->scheduled ) as $key ) {
                if ( str_contains( $key, '|' . $action_hook . ':' . json_encode( $action_args ) ) ) {
                    unset( $this->scheduled[ $key ] );
                }
            }
            return;
        }

        $key = $this->build_key( $action_hook, $action_args, $group );
        unset( $this->scheduled[ $key ] );
    }

    public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
        if ( false === $group ) {
            $matches = array();
            foreach ( $this->scheduled as $key => $timestamp ) {
                if ( str_contains( $key, '|' . $action_hook . ':' . json_encode( $action_args ) ) ) {
                    $matches[] = $timestamp;
                }
            }
            return empty( $matches ) ? false : min( $matches );
        }

        $key = $this->build_key( $action_hook, $action_args, $group );
        return $this->scheduled[ $key ] ?? false;
    }

    public function has_scheduled_action( $action_hook, $action_args, $group = null ) {
        return false !== $this->next_scheduled_action( $action_hook, $action_args, $group );
    }
}

class Gateway_Mock_Order {
    private $id;
    private $needs_payment;
    private $total;
    private $gateway;

    public function __construct( $id = 5, $needs_payment = true, $total = 10, $gateway = 'dummy' ) {
        $this->id            = $id;
        $this->needs_payment = $needs_payment;
        $this->total         = $total;
        $this->gateway       = $gateway;
    }

    public function get_id() {
        return $this->id;
    }

    public function needs_payment() {
        return $this->needs_payment;
    }

    public function get_total() {
        return $this->total;
    }

    public function get_payment_method() {
        return $this->gateway;
    }

    public function get_edit_order_url() {
        return 'http://example.com/order/' . $this->id;
    }

    public function get_order_number() {
        return $this->id;
    }

    public function get_status() {
        return 'completed';
    }
}

class Gateway_Mock_Subscription {
    private $id;
    private $order;
    private $supports_gateway;
    private $manual;
    private $status;
    private $notes = array();

    public function __construct( $id, $order, $supports_gateway = false, $manual = false, $status = 'active' ) {
        $this->id               = $id;
        $this->order            = $order;
        $this->supports_gateway = $supports_gateway;
        $this->manual           = $manual;
        $this->status           = $status;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_last_order( $type = 'ids', $context = 'renewal' ) {
        if ( 'ids' === $type ) {
            return $this->order ? $this->order->get_id() : 0;
        }
        return $this->order;
    }

    public function payment_method_supports( $feature ) {
        return 'gateway_scheduled_payments' === $feature ? $this->supports_gateway : false;
    }

    public function is_manual() {
        return $this->manual;
    }

    public function has_status( $statuses ) {
        return in_array( $this->status, (array) $statuses, true );
    }

    public function add_order_note( $note ) {
        $this->notes[] = $note;
    }

    public function get_notes() {
        return $this->notes;
    }

    public function get_time( $type ) {
        return time() + ( 'payment_retry' === $type ? 60 : 30 );
    }
}

class PaymentGatewayProcessingTest extends TestCase {
    protected $core;
    protected $payments;

    protected function setUp(): void {
        global $hook_callbacks, $last_hook, $last_hook_args, $wc_stub;
        $hook_callbacks = array();
        $last_hook      = null;
        $last_hook_args = array();
        $wc_stub->calls = 0;

        $this->core     = new Gateway_Test_Scheduler_Core();
        $this->payments = new ASWC_Scheduler_Payments_Test( $this->core );
    }

    public function test_gateway_payment_triggers_hook_and_unschedules_retry() {
        global $wc_stub;
        $order        = new Gateway_Mock_Order( 5, true, 10, 'dummy' );
        $subscription = new Gateway_Mock_Subscription( 10, $order );

        $this->payments->schedule_retry( $subscription );
        $this->assertTrue( $this->payments->has_scheduled_retry( $subscription ) );

        $this->payments->gateway_scheduled_subscription_payment( $subscription );

        $this->assertFalse( $this->payments->has_scheduled_retry( $subscription ) );
        $this->assertSame( 'advanced_scheduled_subscription_payment_dummy', $this->payments->last_hook );
        $this->assertSame( 1, $wc_stub->calls );
    }

    public function test_gateway_payment_unschedules_retry_in_custom_group() {
        $order        = new Gateway_Mock_Order( 5, true, 10, 'dummy' );
        $subscription = new Gateway_Mock_Subscription( 10, $order );
        $group        = 'custom';

        $this->payments->schedule_retry( $subscription, null, $group );
        $this->assertTrue( $this->payments->has_scheduled_retry( $subscription, $group ) );

        $this->payments->gateway_scheduled_subscription_payment( $subscription );

        $this->assertFalse( $this->payments->has_scheduled_retry( $subscription, $group ) );
    }

    public function test_has_gateway_renewal_payment_hook_detection() {
        global $hook_callbacks;
        $hook_callbacks['advanced_scheduled_subscription_payment_dummy'][]    = true;
        $hook_callbacks['woocommerce_scheduled_subscription_payment_old'][] = true;
        $this->assertTrue( $this->payments->has_gateway_renewal_payment_hook( 'dummy' ) );
        $this->assertFalse( $this->payments->has_gateway_renewal_payment_hook( 'old' ) );
        $this->assertFalse( $this->payments->has_gateway_renewal_payment_hook( 'other' ) );
    }

    public function test_trigger_gateway_renewal_payment_hook_ignores_legacy_hook() {
        global $hook_callbacks;
        $hook_callbacks['woocommerce_scheduled_subscription_payment_dummy'][] = true;
        $order = new Gateway_Mock_Order( 5, true, 10, 'dummy' );
        $this->payments->trigger_gateway_renewal_payment_hook( $order );
        $this->assertNull( $this->payments->last_hook );
    }

    public function test_gateway_scheduled_payments_feature_skips_processing() {
        $order        = new Gateway_Mock_Order();
        $subscription = new Gateway_Mock_Subscription( 10, $order, true );

        $this->payments->schedule_retry( $subscription );
        $this->payments->gateway_scheduled_subscription_payment( $subscription );

        $this->assertFalse( $this->payments->has_scheduled_retry( $subscription ) );
        $this->assertNull( $this->payments->last_hook );
    }

    public function test_manual_subscription_skips_processing() {
        $order        = new Gateway_Mock_Order();
        $subscription = new Gateway_Mock_Subscription( 10, $order, false, true );

        $this->payments->schedule_retry( $subscription );
        $this->payments->gateway_scheduled_subscription_payment( $subscription );

        $this->assertFalse( $this->payments->has_scheduled_retry( $subscription ) );
        $this->assertNull( $this->payments->last_hook );
    }

    public function test_adds_note_when_no_renewal_order_found() {
        $subscription = new Gateway_Mock_Subscription( 10, null );

        $this->payments->schedule_retry( $subscription );
        $this->payments->gateway_scheduled_subscription_payment( $subscription );

        $notes = $subscription->get_notes();
        $this->assertNotEmpty( $notes );
        $this->assertStringContainsString( 'Renewal order payment processing was skipped', $notes[0] );
    }

    public function test_adds_note_when_order_already_paid() {
        $order        = new Gateway_Mock_Order( 5, false, 10, 'dummy' );
        $subscription = new Gateway_Mock_Subscription( 10, $order );

        $this->payments->gateway_scheduled_subscription_payment( $subscription );

        $notes = $subscription->get_notes();
        $this->assertNotEmpty( $notes );
        $this->assertNull( $this->payments->last_hook );
        $this->assertStringContainsString( 'already paid', $notes[0] );
    }

    public function test_zero_total_order_skips_processing_and_unschedules_retry() {
        $order        = new Gateway_Mock_Order( 5, false, 0, 'dummy' );
        $subscription = new Gateway_Mock_Subscription( 10, $order );

        $this->payments->schedule_retry( $subscription );
        $this->assertTrue( $this->payments->has_scheduled_retry( $subscription ) );

        $this->payments->gateway_scheduled_subscription_payment( $subscription );

        $this->assertFalse( $this->payments->has_scheduled_retry( $subscription ) );
        $this->assertNull( $this->payments->last_hook );
        $this->assertEmpty( $subscription->get_notes() );
    }

    public function test_ended_status_subscription_unschedules_retry_and_skips_processing() {
        $order        = new Gateway_Mock_Order();
        $subscription = new Gateway_Mock_Subscription( 10, $order, false, false, 'switched' );

        $this->payments->schedule_retry( $subscription );
        $this->assertTrue( $this->payments->has_scheduled_retry( $subscription ) );

        $this->payments->gateway_scheduled_subscription_payment( $subscription );

        $this->assertFalse( $this->payments->has_scheduled_retry( $subscription ) );
        $this->assertNull( $this->payments->last_hook );
        $this->assertEmpty( $subscription->get_notes() );
    }

    public function test_gateway_can_schedule_retry_in_custom_group_after_processing() {
        $order        = new Gateway_Mock_Order( 5, true, 10, 'dummy' );
        $subscription = new Gateway_Mock_Subscription( 10, $order );
        $payments     = new ASWC_Scheduler_Payments_Custom_Retry( $this->core );

        $payments->schedule_retry( $subscription );
        $this->assertTrue( $payments->has_scheduled_retry( $subscription ) );

        $payments->gateway_scheduled_subscription_payment( $subscription );

        $this->assertFalse( $payments->has_scheduled_retry( $subscription ) );
        $this->assertTrue( $payments->has_scheduled_retry( $subscription, 'gateway_group' ) );
        $this->assertTrue( $payments->has_scheduled_retry( $subscription, false ) );
    }
}
