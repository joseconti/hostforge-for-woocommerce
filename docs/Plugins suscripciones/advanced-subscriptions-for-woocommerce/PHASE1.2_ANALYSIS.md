# PHASE 1.2 - OWNERSHIP VALIDATION ANALYSIS
## Advanced Subscriptions for WooCommerce

**Objective:** Add ownership validation to ALL operations that modify subscriptions
**Date:** 2026-01-06
**Branch:** develop

---

## 🎯 SCOPE OF PHASE 1.2

### Already Fixed in Phase 1.1:
✅ `aswc_cancel_recurring_payment()` - Has ownership validation
✅ `aswc_show_parent_order_for_custom_manual_callback()` - Admin only
✅ `aswc_update_subscription_items_callback()` - Admin only

### Need Analysis:

## 📋 FUNCTIONS TO ANALYZE

### Category 1: Admin Functions (Already Protected)
These functions already have capability checks from Phase 1.1:
- ✅ `aswc_admin_pause_susbcription()` - Has capability check (edit_shop_orders)
- ✅ `aswc_create_manually_recurring()` - Has capability check (edit_shop_orders)
- ✅ `aswc_export_csv_report()` - Has capability check (manage_woocommerce)

**Action Required:** None for these - already protected

---

### Category 2: Scheduler API Functions (CRITICAL)
Location: `scheduler-api/`

Need to check:
1. Payment processing functions
2. Renewal order creation
3. Subscription status updates
4. Any automated operations

**Status:** Need deep analysis ⚠️

---

### Category 3: Frontend/Customer Operations
Need to verify ownership on:

#### 3.1 Manual Subscription Operations
- `aswc_save_manual_subscription_order_details()` - Line 2104
- `aswc_save_manual_subscription_order_details_hpos()` - Line 2261

**Question:** Are these admin-only or can customers access?

#### 3.2 Renewal Payment Actions
- `aswc_handle_renewal_payment()` - Line 2431
- `aswc_add_renewal_payment_actions()` - Line 2417

**Question:** What triggers these? Customer action or automated?

#### 3.3 Subscription Updates
- `aswc_reschedule_next_payment_on_save()` - Line 2646

**Question:** Who can trigger this save action?

---

## 🔍 DETAILED ANALYSIS NEEDED

### Priority 1: Scheduler API Review
Need to examine all files in `scheduler-api/` directory to identify:
- Functions that process payments
- Functions that create renewal orders
- Functions that update subscription status
- Any race conditions or missing locks

### Priority 2: WordPress Hooks
Need to check what hooks are registered and if they validate ownership:
- `save_post` hooks
- `woocommerce_*` action hooks
- Custom AJAX actions

### Priority 3: REST API Endpoints
Check if there are any REST API endpoints that need protection.

---

## 🎯 PHASE 1.2 PLAN

Based on the audit report from Phase 1, here are the CRITICAL areas:

### From Original Audit - Scheduler API Issues:

#### Issue #1: Race Condition in Payment Processing
**File:** `scheduler-api/payments/class-aswc-scheduler-payments.php`
**Function:** `aswc_create_renewal_payment_order_and_process()`
**Problem:** No transactional locks - can process same subscription twice
**Fix Required:**
```php
// Add transactional lock
$lock_key = 'aswc_payment_' . $subscription_id;
if ( ! $this->acquire_lock( $lock_key ) ) {
    return; // Already processing
}

// ... process payment ...

$this->release_lock( $lock_key );
```

#### Issue #2: No Amount Validation Before Payment
**File:** `scheduler-api/payments/class-aswc-scheduler-payments.php`
**Problem:** Doesn't validate amount before charging
**Fix Required:**
```php
// Validate payment amount
$expected_amount = aswc_get_meta_data( $subscription_id, 'aswc_recurring_total', true );
if ( $amount != $expected_amount ) {
    // Log mismatch and abort
    return false;
}
```

#### Issue #3: Missing Ownership in Scheduler Operations
**Problem:** Scheduler operates on subscription IDs without verifying ownership
**Fix Required:** Add ownership validation before ANY subscription modification

---

## 📊 ESTIMATED CORRECTIONS FOR PHASE 1.2

Based on analysis, Phase 1.2 will include:

1. **Ownership validation helper function** (NEW)
2. **Transactional locking system** (NEW)
3. **Payment amount validation** (NEW)
4. **3-5 functions** need ownership checks
5. **Scheduler API security** enhancements

---

## ⏭️ NEXT STEPS

1. ✅ Create this analysis document
2. ⏳ Deep dive into scheduler-api/ directory
3. ⏳ Identify all functions needing ownership validation
4. ⏳ Create helper functions for ownership checks
5. ⏳ Implement transactional locking
6. ⏳ Apply fixes systematically
7. ⏳ Document all changes
8. ⏳ Create test cases

---

**Status:** Analysis in progress
**Estimated Corrections:** 5-8 functions
**Estimated Time:** 2-3 hours
**Priority:** HIGH (prevents unauthorized subscription modifications)

