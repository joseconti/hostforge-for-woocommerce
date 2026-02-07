<?php
use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        return $value;
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type, $gmt = 0 ) {
        return time();
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        // No-op for tests.
    }
}

if ( ! class_exists( 'WC_Subscription' ) ) {
    class WC_Subscription {}
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        protected $code;
        public function __construct( $code = '', $message = '' ) { $this->code = $code; }
        public function get_error_code() { return $this->code; }
    }
}

class LifecycleFunctionsTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscription_fallback() {
        if ( ! function_exists( 'wc_get_order' ) ) {
            function wc_get_order( $id ) {
                return new class( $id ) {
                    private $id;
                    public function __construct( $id ) { $this->id = $id; }
                    public function get_id() { return $this->id; }
                    public function has_status( $statuses = array() ) { return true; }
                };
            }
        }
        require_once __DIR__ . '/../scheduler.php';
        $subscription = aswc_get_subscription( 25 );
        $this->assertSame( 25, $subscription->get_id() );
        $subscription = ASWC_Scheduler_API::get_subscription( 25 );
        $this->assertSame( 25, $subscription->get_id() );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscriptions_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( array(), aswc_get_subscriptions( array() ) );
        $this->assertSame( array(), ASWC_Scheduler_API::get_subscriptions( array() ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscriptions_for_order_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( array(), aswc_get_subscriptions_for_order( 1 ) );
        $this->assertSame( array(), ASWC_Scheduler_API::get_subscriptions_for_order( 1 ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscriptions_for_renewal_order_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( array(), aswc_get_subscriptions_for_renewal_order( 1 ) );
        $this->assertSame( array(), ASWC_Scheduler_API::get_subscriptions_for_renewal_order( 1 ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_order_wrapper() {
        if ( ! function_exists( 'wc_get_order' ) ) {
            function wc_get_order( $id ) {
                return 'order-' . $id;
            }
        }
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( 'order-7', aswc_get_order( 7 ) );
        $this->assertSame( 'order-7', ASWC_Scheduler_API::get_order( 7 ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_order_fallback_returns_false() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertFalse( aswc_get_order( 7 ) );
        $this->assertFalse( ASWC_Scheduler_API::get_order( 7 ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_payment_gateway_by_order_wrapper() {
        if ( ! function_exists( 'wc_get_payment_gateway_by_order' ) ) {
            function wc_get_payment_gateway_by_order( $order ) {
                if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
                    return 'gateway-' . $order->get_id();
                }

                return 'gateway-' . $order;
            }
        }
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( 'gateway-1', aswc_get_payment_gateway_by_order( 1 ) );
        $this->assertSame( 'gateway-1', ASWC_Scheduler_API::get_payment_gateway_by_order( 1 ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_payment_gateway_by_order_fallback_returns_false() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertFalse( aswc_get_payment_gateway_by_order( 1 ) );
        $this->assertFalse( ASWC_Scheduler_API::get_payment_gateway_by_order( 1 ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_canonical_product_id_fallback() {
        $item = new class {
            public function get_product_id() { return 12; }
        };
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( 12, aswc_get_canonical_product_id( $item ) );
        $this->assertSame( 12, ASWC_Scheduler_API::get_canonical_product_id( $item ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_order_item_fallback() {
        $order = new class {
            public function get_item( $item_id ) { return 'item-' . $item_id; }
        };
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( 'item-10', aswc_get_order_item( 10, $order ) );
        $this->assertSame( 'item-10', ASWC_Scheduler_API::get_order_item( 10, $order ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_create_renewal_order_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $subscription = new WC_Subscription();
        $result       = aswc_create_renewal_order( $subscription );
        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'aswc_create_renewal_order_unavailable', $result->get_error_code() );
        $this->assertInstanceOf( WP_Error::class, ASWC_Scheduler_API::create_renewal_order( $subscription ) );
    }

}
