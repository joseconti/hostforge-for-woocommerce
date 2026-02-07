# Enhanced Logging Implementation
## Advanced Subscriptions for WooCommerce - Phase 1.5

**Date:** 2026-01-06
**Status:** ✅ COMPLETE
**Priority:** HIGH (Critical for debugging payment/renewal issues)

---

## 🎯 OBJECTIVE

Implement comprehensive logging for all payment and renewal operations, capturing every step, every data change, and all critical values (prices, dates, intervals) to enable complete debugging of any payment/renewal issues.

---

## ✅ WHAT WAS IMPLEMENTED

### 1. Enhanced Logging Methods in ASWC_Log Class

**File:** `includes/class-aswc-log.php`

#### New Method: `log_data_change()`

Logs before/after values for any data change:

```php
ASWC_Log::log_data_change(
    $subscription_id,
    'aswc_subscription_status',
    'on-hold',
    'active',
    'payment-success'
);
```

**Output Example:**
```
[DATA_CHANGE] [payment-success] Subscription #123 - aswc_subscription_status: on-hold → active
```

**Use Cases:**
- Status changes (active, cancelled, on-hold)
- Price updates (recurring_total changes)
- Date modifications (next_payment_date, trial_end_date)
- Interval changes (subscription_interval, interval_count)

#### New Method: `log_payment_processing()`

Logs payment processing phases with context:

```php
ASWC_Log::log_payment_processing(
    $subscription_id,
    $order_id,
    'pre-payment',
    array(
        'amount'   => 19.99,
        'currency' => 'USD',
        'status'   => 'pending',
    )
);
```

**Output Example:**
```
[PAYMENT_PRE_PAYMENT] Subscription #123, Order #456 | amount=$19.99 (19.99), currency=USD, status=pending
```

**Phases Logged:**
- `pre-payment` - Before gateway is triggered
- `payment-attempt` - During gateway processing
- `post-payment` - After payment completes (success or failure)

#### New Method: `log_subscription_snapshot()`

Captures complete subscription state at a point in time:

```php
ASWC_Log::log_subscription_snapshot(
    $subscription_id,
    'before-payment'
);
```

**Output Example:**
```
[SUBSCRIPTION_SNAPSHOT] [before-payment] Subscription #123: status=active, recurring_total=$19.99 (19.99), next_payment_date=2026-01-15 10:00:00 (1736935200), interval=month, interval_count=1, payment_count=5, trial_end=(empty), subscription_end=(empty)
```

**Snapshots Taken:**
- `before-payment` - Before any payment processing starts
- `after-payment-success` - After successful payment
- `after-cancel` - When subscription is cancelled due to max retries

#### New Method: `format_value_for_log()`

Context-aware value formatting:

**Date/Time Fields:**
- Input: `1736935200`
- Output: `2026-01-15 10:00:00 (1736935200)`
- Shows human-readable date + original timestamp

**Price/Amount Fields:**
- Input: `19.99`
- Output: `$19.99 (19.99)`
- Shows formatted price with currency symbol + raw value

**Boolean Fields:**
- Input: `true` or `false`
- Output: `true` or `false`

**Empty Values:**
- Input: `null` or `''`
- Output: `(empty)`

**Arrays/Objects:**
- Input: Array or object
- Output: JSON encoded string

---

### 2. Integration into Payment Processing

**File:** `scheduler-api/payments/class-aswc-scheduler-payments.php`

#### Location 1: Before Payment Processing (Line ~178)

**Added:**
```php
// Log complete subscription state before payment processing.
if ( method_exists( 'ASWC_Log', 'log_subscription_snapshot' ) ) {
    ASWC_Log::log_subscription_snapshot( $sub_id, 'before-payment' );
}
```

**Captures:** All subscription metadata before any changes are made.

#### Location 2: Pre-Payment Phase (Line ~355)

**Added:**
```php
// Log pre-payment processing details.
if ( method_exists( 'ASWC_Log', 'log_payment_processing' ) ) {
    ASWC_Log::log_payment_processing(
        $subscription->get_id(),
        $order_id,
        'pre-payment',
        array(
            'amount'   => $order_tot,
            'currency' => $order_cur,
            'status'   => $order_stat,
        )
    );
}
```

**Captures:** Order amount, currency, and status before payment gateway is triggered.

#### Location 3: Post-Payment Phase (Line ~393)

**Added:**
```php
// Log post-payment processing details.
if ( method_exists( 'ASWC_Log', 'log_payment_processing' ) && $order_after ) {
    ASWC_Log::log_payment_processing(
        $subscription->get_id(),
        $order_after->get_id(),
        'post-payment',
        array(
            'success'       => $success ? 'yes' : 'no',
            'status'        => $order_after->get_status(),
            'needs_payment' => $order_after->needs_payment() ? 'yes' : 'no',
        )
    );
}
```

**Captures:** Payment result, order status, and whether order still needs payment.

#### Location 4: Status Change on Success (Line ~428)

**Added:**
```php
// Log status change with before/after values.
if ( method_exists( 'ASWC_Log', 'log_data_change' ) ) {
    ASWC_Log::log_data_change(
        $subscription->get_id(),
        'aswc_subscription_status',
        $current_status,
        'active',
        'payment-success'
    );
}
```

**Captures:** Subscription status change from on-hold/cancelled to active.

#### Location 5: After Successful Payment (Line ~447)

**Added:**
```php
// Log final subscription state after successful payment.
if ( method_exists( 'ASWC_Log', 'log_subscription_snapshot' ) ) {
    ASWC_Log::log_subscription_snapshot( $subscription->get_id(), 'after-payment-success' );
}
```

**Captures:** Complete subscription state after successful payment and status updates.

#### Location 6: Cancellation Due to Max Retries (Line ~494)

**Added:**
```php
// Log status change with before/after values.
if ( method_exists( 'ASWC_Log', 'log_data_change' ) ) {
    ASWC_Log::log_data_change(
        $subscription->get_id(),
        'aswc_subscription_status',
        $old_status,
        'cancelled',
        'max-retries-reached'
    );
}

// Log final subscription state.
if ( method_exists( 'ASWC_Log', 'log_subscription_snapshot' ) ) {
    ASWC_Log::log_subscription_snapshot( $subscription->get_id(), 'after-cancel' );
}
```

**Captures:** Status change to cancelled and final state of subscription.

---

## 📊 WHAT IS NOW LOGGED

### For Every Payment/Renewal:

✅ **Subscription State Before Payment:**
- Status (active, on-hold, etc.)
- Recurring total (price)
- Next payment date
- Interval (day, week, month, year)
- Interval count (1, 2, 3, etc.)
- Payment count (how many payments made)
- Trial end date
- Subscription end date

✅ **Pre-Payment Details:**
- Order ID
- Order amount
- Order currency
- Order status

✅ **Post-Payment Details:**
- Payment success (yes/no)
- Order status after payment
- Whether order still needs payment

✅ **Status Changes:**
- Old status → New status
- Context (payment-success, max-retries-reached)

✅ **Subscription State After Payment:**
- All fields again (see changes from before-payment snapshot)

### Additional Existing Logging (Already Present):

- Entry/exit of payment function
- Hook context (normal payment vs retry)
- Subscription ID resolution
- Order resolution and creation
- Retry scheduling
- Gateway hook triggering
- Payment result analysis
- Retry attempt counting
- Race condition detection

---

## 🔍 HOW TO USE THE LOGS

### Enable Logging:

In WordPress Admin:
1. Navigate to WooCommerce → Settings → Subscriptions
2. Enable "Subscription Log" option
3. Save changes

Or via code:
```php
update_option( 'aswc_enable_subscription_log', 'yes' );
```

### Access Logs:

**Option 1: WordPress Admin**
- WooCommerce → Status → Logs
- Select log file starting with "aswc-"

**Option 2: Server File System**
- Location: `/wp-content/uploads/wc-logs/aswc-*.log`

### Search for Specific Information:

**Find all payment attempts for subscription #123:**
```bash
grep "Subscription #123" /path/to/wc-logs/aswc-*.log
```

**Find all price changes:**
```bash
grep "\[DATA_CHANGE\].*recurring_total" /path/to/wc-logs/aswc-*.log
```

**Find all payment failures:**
```bash
grep "\[PAYMENT_POST_PAYMENT\].*success=no" /path/to/wc-logs/aswc-*.log
```

**Find all status changes:**
```bash
grep "\[DATA_CHANGE\].*aswc_subscription_status" /path/to/wc-logs/aswc-*.log
```

**Find subscription state before a specific payment:**
```bash
grep "\[SUBSCRIPTION_SNAPSHOT\] \[before-payment\] Subscription #123" /path/to/wc-logs/aswc-*.log
```

---

## 🐛 DEBUGGING SCENARIOS

### Scenario 1: Price Not Matching

**Problem:** Customer says they were charged wrong amount.

**Solution:**
1. Search for `[SUBSCRIPTION_SNAPSHOT] [before-payment] Subscription #123`
2. Check `recurring_total` value
3. Search for `[PAYMENT_PRE_PAYMENT] Subscription #123`
4. Check `amount` value
5. Compare both - if different, find where price was changed:
   ```bash
   grep "[DATA_CHANGE].*recurring_total.*Subscription #123" aswc-*.log
   ```

### Scenario 2: Next Payment Date Not Updating

**Problem:** Next payment date didn't update after successful payment.

**Solution:**
1. Search for `[SUBSCRIPTION_SNAPSHOT] [before-payment] Subscription #123`
2. Note the `next_payment_date` value
3. Search for `[SUBSCRIPTION_SNAPSHOT] [after-payment-success] Subscription #123`
4. Compare `next_payment_date` values
5. If same, check if payment actually succeeded:
   ```bash
   grep "[PAYMENT_POST_PAYMENT].*Subscription #123" aswc-*.log
   ```

### Scenario 3: Subscription Cancelled Unexpectedly

**Problem:** Subscription shows as cancelled but customer doesn't know why.

**Solution:**
1. Search for status change to cancelled:
   ```bash
   grep "[DATA_CHANGE].*aswc_subscription_status.*cancelled.*#123" aswc-*.log
   ```
2. Check the context field (e.g., `max-retries-reached`)
3. Find all previous payment attempts:
   ```bash
   grep "[PAYMENT.*Subscription #123" aswc-*.log
   ```
4. Count failures vs successes

### Scenario 4: Payment Processed Multiple Times

**Problem:** Customer charged twice for same renewal.

**Solution:**
1. Search for all payments for the subscription on the specific date:
   ```bash
   grep "2026-01-06.*Subscription #123" aswc-*.log
   ```
2. Look for `RACE CONDITION DETECTED` messages
3. Check if multiple `[PAYMENT_PRE_PAYMENT]` entries with same order
4. Review lock acquisition/release messages

---

## 📝 LOG FORMAT REFERENCE

### Data Change Format:
```
[DATA_CHANGE] [context] Subscription #ID - field_name: old_value → new_value
```

### Payment Processing Format:
```
[PAYMENT_PHASE] Subscription #ID, Order #ID | key1=value1, key2=value2, ...
```

### Subscription Snapshot Format:
```
[SUBSCRIPTION_SNAPSHOT] [context] Subscription #ID: field1=value1, field2=value2, ...
```

---

## ✅ VERIFICATION CHECKLIST

- [x] ASWC_Log class methods added
- [x] PHP syntax validation passed
- [x] Coding standards compliance verified
- [x] Integration into scheduler payment processing
- [x] Subscription snapshot before payment
- [x] Pre-payment logging
- [x] Post-payment logging
- [x] Status change logging
- [x] Subscription snapshot after payment
- [x] Cancellation logging
- [x] Context-aware value formatting (dates, prices)
- [x] Documentation created

---

## 🚀 DEPLOYMENT NOTES

### Files Modified:
1. `includes/class-aswc-log.php` - Added 4 new methods
2. `scheduler-api/payments/class-aswc-scheduler-payments.php` - Added 6 logging integration points

### Backward Compatibility:
✅ **100% Backward Compatible**
- Uses `method_exists()` checks before calling new methods
- Old logging continues to work if new methods don't exist
- No breaking changes to existing functionality

### Performance Impact:
⚡ **Minimal**
- Logging only happens when enabled via option
- Log writes are handled by WooCommerce's efficient WC_Logger
- No database queries added (reads metadata already being accessed)

### Testing Recommendations:
1. Enable logging in test environment
2. Process a test renewal payment
3. Check log file contains all new log entries
4. Verify values are formatted correctly (dates, prices)
5. Test scenario: successful payment
6. Test scenario: failed payment with retry
7. Test scenario: max retries reached and cancelled

---

## 📞 SUPPORT

### If Logs Are Not Appearing:

**Check 1:** Verify logging is enabled
```php
get_option( 'aswc_enable_subscription_log' );
// Should return 'yes'
```

**Check 2:** Verify WooCommerce logging is working
```php
if ( class_exists( 'WC_Logger' ) ) {
    $logger = wc_get_logger();
    $logger->debug( 'Test message', array( 'source' => 'aswc' ) );
}
```

**Check 3:** Check file permissions on log directory
```bash
ls -la /path/to/wp-content/uploads/wc-logs/
```

**Check 4:** Verify ASWC_Log class is loaded
```php
if ( class_exists( 'ASWC_Log' ) ) {
    echo 'Class loaded';
}
```

---

## 🔗 RELATED DOCUMENTATION

- [TODO_REMAINING_WORK.md](TODO_REMAINING_WORK.md) - Overall roadmap
- [PHASE1.2_MONITORING_GUIDE.md](PHASE1.2_MONITORING_GUIDE.md) - Race condition monitoring
- [PHASE1_VERIFICATION_CHECKLIST.md](PHASE1_VERIFICATION_CHECKLIST.md) - Security testing

---

**Status:** ✅ Ready for Production
**Next Review:** After first production deployment
**Responsible:** Development Team

---

Good logging! 📝
