# PHASE 1.2 - MONITORING GUIDE
## Race Condition Detection in Production

**Status:** ✅ Ready for Monitoring
**Start Date:** 2026-01-06
**Duration:** 1-2 weeks minimum
**Decision Date:** After monitoring period

---

## 🎯 OBJECTIVE

Monitor production logs to detect if race conditions actually occur during subscription payment processing. This data-driven approach will determine if full lock implementation is necessary.

---

## 📊 WHAT WE'RE MONITORING

### Lock Acquisition (Normal Operations)
Every time the Scheduler processes a payment, it should log:
```
[gateway_scheduled_subscription_payment] 🔒 Monitoring lock acquired for subscription 123
[gateway_scheduled_subscription_payment] 🔓 Monitoring lock released for subscription 123
```

**Expected:** These messages appear for every payment processed.

### Race Condition Detection (Problem Indicator)
If two processes try to charge the same subscription simultaneously:
```
[gateway_scheduled_subscription_payment] ⚠️ RACE CONDITION DETECTED: Subscription 123 payment lock exists - another process may be running simultaneously
```

**Expected:** ZERO occurrences (or very rare).
**If Found:** Indicates genuine concurrency issues that need full lock implementation.

---

## 🔎 HOW TO MONITOR

### Method 1: Direct Log Search (Recommended)

#### Find Your Log File Location:

**Option A: WooCommerce Logs (Most Common)**
```bash
# Location (WordPress default)
/wp-content/uploads/wc-logs/

# Find latest ASWC log
cd /path/to/wordpress/wp-content/uploads/wc-logs/
ls -lt | grep aswc
```

**Option B: Debug Log**
```bash
# Location
/wp-content/debug.log

# Check if WP_DEBUG_LOG is enabled in wp-config.php
grep WP_DEBUG_LOG wp-config.php
```

**Option C: Custom Log Location**
Check if ASWC has custom log path in settings or code.

#### Search for Race Conditions:

```bash
# Search for race condition warnings
grep "⚠️ RACE CONDITION DETECTED" /path/to/log/file

# Or search for the text without emoji
grep "RACE CONDITION DETECTED" /path/to/log/file

# Count occurrences
grep -c "RACE CONDITION DETECTED" /path/to/log/file
```

#### Verify Monitoring is Working:

```bash
# Check if locks are being acquired (should see many)
grep "🔒 Monitoring lock acquired" /path/to/log/file | tail -20

# Or without emoji
grep "Monitoring lock acquired" /path/to/log/file | tail -20

# Count how many payments have been processed
grep -c "Monitoring lock acquired" /path/to/log/file
```

### Method 2: WordPress Admin (If Available)

If you have a log viewer plugin or ASWC has a built-in log viewer:

1. Navigate to WooCommerce → Status → Logs
2. Select the ASWC log file
3. Search for "RACE CONDITION DETECTED"
4. Search for "Monitoring lock acquired" to verify it's working

### Method 3: Automated Monitoring Script

Create a daily monitoring script:

```bash
#!/bin/bash
# File: monitor_race_conditions.sh

LOG_FILE="/path/to/wordpress/wp-content/uploads/wc-logs/aswc-*.log"
REPORT_FILE="/path/to/reports/race_condition_report_$(date +%Y%m%d).txt"

echo "==================================" > $REPORT_FILE
echo "Race Condition Monitoring Report" >> $REPORT_FILE
echo "Date: $(date)" >> $REPORT_FILE
echo "==================================" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Count payments processed
PAYMENTS=$(grep -h "Monitoring lock acquired" $LOG_FILE 2>/dev/null | wc -l)
echo "Total Payments Processed: $PAYMENTS" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Check for race conditions
RACE_CONDITIONS=$(grep -h "RACE CONDITION DETECTED" $LOG_FILE 2>/dev/null | wc -l)
echo "Race Conditions Detected: $RACE_CONDITIONS" >> $REPORT_FILE
echo "" >> $REPORT_FILE

if [ $RACE_CONDITIONS -gt 0 ]; then
    echo "⚠️ WARNING: Race conditions found!" >> $REPORT_FILE
    echo "" >> $REPORT_FILE
    echo "Details:" >> $REPORT_FILE
    grep -h "RACE CONDITION DETECTED" $LOG_FILE >> $REPORT_FILE
fi

# Display report
cat $REPORT_FILE

# Optional: Email report
# mail -s "ASWC Race Condition Report" admin@example.com < $REPORT_FILE
```

Make it executable and run daily:
```bash
chmod +x monitor_race_conditions.sh

# Add to crontab (runs daily at 9am)
crontab -e
# Add: 0 9 * * * /path/to/monitor_race_conditions.sh
```

---

## 📈 WHAT TO LOOK FOR

### Scenario 1: No Race Conditions Found ✅

**Evidence:**
- Zero "RACE CONDITION DETECTED" messages
- Many "Monitoring lock acquired" messages (normal operations)
- All locks properly released

**Interpretation:**
- Race conditions are NOT occurring in production
- Current monitoring is sufficient
- Full lock implementation may not be necessary

**Action:**
- Continue monitoring for full 2 weeks
- Keep helper functions available for future use
- Consider this phase successful

### Scenario 2: Rare Race Conditions (1-2 per week)

**Evidence:**
- Occasional "RACE CONDITION DETECTED" messages
- Most payments process normally
- No customer complaints about duplicate charges

**Interpretation:**
- Minor concurrency issues exist but are rare
- Current monitoring catches them
- May not justify full lock implementation yet

**Action:**
- Extend monitoring period to 4 weeks
- Check if patterns exist (specific subscriptions, times of day)
- Investigate why: server load, cron overlap, manual triggers?
- Decide based on business impact

### Scenario 3: Frequent Race Conditions (Multiple per day) ⚠️

**Evidence:**
- Multiple "RACE CONDITION DETECTED" messages daily
- Same subscriptions appearing repeatedly
- Potential customer complaints about duplicate charges

**Interpretation:**
- Genuine concurrency problem
- Full lock system needed immediately

**Action:**
- Implement full lock system (instructions in PHASE1.2_OPTION_A_COMPLETED.md)
- Add payment amount validation
- Monitor for duplicate charges in orders table
- Consider reviewing cron schedule and Action Scheduler setup

---

## 🧪 TESTING THE MONITORING

Before starting the monitoring period, verify it's working:

### Test 1: Verify Logs Are Being Written

1. Trigger a test payment (if possible)
2. Check logs immediately after
3. Should see "Monitoring lock acquired" message

```bash
# Check last 50 lines of log
tail -50 /path/to/log/file | grep "Monitoring lock"
```

### Test 2: Verify Lock Release

Every "lock acquired" should have a corresponding "lock released":

```bash
# Count acquired locks
grep -c "Monitoring lock acquired" /path/to/log/file

# Count released locks
grep -c "Monitoring lock released" /path/to/log/file

# These numbers should be equal (or very close)
```

### Test 3: Check for Orphaned Locks

If locks are acquired but not released, you'll have deadlocks:

```bash
# Show last 100 lock operations
grep "Monitoring lock" /path/to/log/file | tail -100

# Look for "acquired" without matching "released"
```

---

## 📋 MONITORING CHECKLIST

### Week 1:

- [ ] **Day 1:** Verify monitoring is active
  - [ ] Check logs exist and are being written
  - [ ] Confirm "Monitoring lock acquired" messages appear
  - [ ] Verify lock releases are logged

- [ ] **Day 3:** First check for race conditions
  - [ ] Run search for "RACE CONDITION DETECTED"
  - [ ] Count total payments processed
  - [ ] Document findings

- [ ] **Day 5:** Mid-week check
  - [ ] Repeat race condition search
  - [ ] Check for any patterns
  - [ ] Verify no orphaned locks

- [ ] **Day 7:** End of week 1 review
  - [ ] Comprehensive log analysis
  - [ ] Count race conditions (if any)
  - [ ] Decide if extending monitoring or implementing locks

### Week 2:

- [ ] **Day 10:** Continued monitoring
  - [ ] Check for new race conditions
  - [ ] Compare with week 1 data

- [ ] **Day 14:** Final analysis
  - [ ] Complete data collection
  - [ ] Make decision on full implementation
  - [ ] Document findings

---

## 🎯 DECISION CRITERIA

After 1-2 weeks of monitoring, decide:

### Implement Full Lock System IF:

✅ Race conditions detected **3 or more times**
✅ Same subscription affected multiple times
✅ Customer complaints about duplicate charges
✅ High-traffic periods show concurrency issues

**Next Step:** Follow upgrade guide in [PHASE1.2_OPTION_A_COMPLETED.md](PHASE1.2_OPTION_A_COMPLETED.md#-how-to-upgrade-to-full-lock-system)

### Keep Current Monitoring IF:

✅ **ZERO race conditions** detected
✅ All payments processing normally
✅ All locks properly acquired and released
✅ No customer complaints

**Next Step:** Continue passive monitoring, keep helper functions available

### Extend Monitoring IF:

✅ **1-2 rare occurrences** only
✅ No clear pattern
✅ No customer impact
✅ Need more data

**Next Step:** Continue monitoring for another 1-2 weeks

---

## 📊 REPORTING TEMPLATE

Use this template for weekly reports:

```markdown
# ASWC Race Condition Monitoring Report
## Week of: [Date Range]

### Summary
- Total Payments Processed: [Number]
- Race Conditions Detected: [Number]
- Affected Subscriptions: [IDs or "None"]
- Orphaned Locks: [Number or "None"]

### Details
[If race conditions found, list details:]
- Date/Time: [timestamp]
- Subscription ID: [ID]
- Context: [what was happening]

### Observations
[Any patterns, unusual behavior, or notes]

### Recommendation
[ ] Continue monitoring
[ ] Implement full lock system
[ ] Extend monitoring period
[ ] No action needed

### Next Steps
[What will be done next]
```

---

## 🚨 EMERGENCY PROCEDURES

### If You Detect Duplicate Charges:

1. **Immediate Action:**
   ```bash
   # Check for duplicate orders
   SELECT post_id, meta_value as subscription_id, COUNT(*) as count
   FROM wp_postmeta
   WHERE meta_key = '_aswc_subscription'
   GROUP BY meta_value
   HAVING count > 1;
   ```

2. **Implement Full Lock System Immediately:**
   - Follow instructions in PHASE1.2_OPTION_A_COMPLETED.md
   - Change line 153 in Scheduler API to abort on lock detection
   - Deploy ASAP

3. **Customer Communication:**
   - Identify affected customers
   - Process refunds if needed
   - Communicate fix timeline

### If You Detect Orphaned Locks:

1. **Check Current Locks:**
   ```bash
   # In WordPress database
   SELECT option_name, option_value
   FROM wp_options
   WHERE option_name LIKE 'aswc_payment_lock_%';
   ```

2. **Manual Cleanup (if needed):**
   ```php
   // In WordPress admin or wp-cli
   global $wpdb;
   $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'aswc_payment_lock_%' AND option_value < " . time());
   ```

3. **Investigate:**
   - Why didn't locks release?
   - PHP errors?
   - Function exits before release?

---

## 🔧 TROUBLESHOOTING

### Problem: No Log Messages Appearing

**Check 1:** Verify ASWC_Log class exists and is working
```bash
grep -r "class ASWC_Log" .
```

**Check 2:** Verify logging is enabled in ASWC settings

**Check 3:** Check if logs are going to different file
```bash
find /path/to/wordpress -name "*.log" -mtime -1
```

### Problem: Can't Find Log Files

**Check 1:** WooCommerce logging location
```php
// In WordPress admin → WooCommerce → Status → Logs
// Or check: WC_Log_Handler_File::get_log_file_path()
```

**Check 2:** Check wp-config.php for debug settings
```bash
grep -E "WP_DEBUG|WP_DEBUG_LOG" wp-config.php
```

**Check 3:** Check server error logs
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log
```

### Problem: Too Many Log Messages

If logs are too verbose:

1. Filter for just race condition messages
2. Use log rotation
3. Archive old logs weekly

```bash
# Rotate logs script
mv /path/to/log/file /path/to/archive/log_$(date +%Y%m%d).log
touch /path/to/log/file
```

---

## 📞 SUPPORT

If you need help during monitoring:

1. **Check Documentation:**
   - [PHASE1.2_OPTION_A_COMPLETED.md](PHASE1.2_OPTION_A_COMPLETED.md) - Implementation details
   - [PHASE1.2_MANUAL_INSTRUCTIONS.md](PHASE1.2_MANUAL_INSTRUCTIONS.md) - Manual upgrade guide

2. **Review Helper Functions:**
   - `aswc_acquire_payment_lock()` in [includes/aswc-common-functions.php](includes/aswc-common-functions.php)
   - `aswc_release_payment_lock()`
   - `aswc_is_payment_locked()`

3. **Check Commits:**
   ```bash
   git log --grep="Phase 1.2"
   ```

---

## ✅ SUCCESS CRITERIA

This monitoring phase is successful when:

✅ **Data Collected:** At least 1-2 weeks of production data
✅ **Decision Made:** Clear path forward (implement locks or not)
✅ **No Customer Impact:** No duplicate charges or payment issues
✅ **Infrastructure Ready:** Helper functions tested and working
✅ **Documentation Complete:** All findings documented

---

**Next Review Date:** [Date + 1 week]
**Decision Deadline:** [Date + 2 weeks]
**Responsible:** Development team

---

Good luck with the monitoring! 🚀
