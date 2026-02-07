#!/bin/bash
################################################################################
# ASWC Race Condition Monitoring Script
# Advanced Subscriptions for WooCommerce - Phase 1.2
#
# Usage: ./monitor-race-conditions.sh [log-path]
# Example: ./monitor-race-conditions.sh /path/to/wordpress/wp-content/uploads/wc-logs
################################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default log path (adjust as needed)
LOG_PATH="${1:-/wp-content/uploads/wc-logs}"

echo "=========================================="
echo "ASWC Race Condition Monitor"
echo "Date: $(date)"
echo "=========================================="
echo ""

# Check if log path exists
if [ ! -d "$LOG_PATH" ]; then
    echo -e "${RED}❌ Error: Log path not found: $LOG_PATH${NC}"
    echo ""
    echo "Usage: $0 [log-path]"
    echo "Example: $0 /var/www/html/wp-content/uploads/wc-logs"
    exit 1
fi

# Find ASWC log files
echo "🔍 Searching for log files in: $LOG_PATH"
LOG_FILES=$(find "$LOG_PATH" -name "*aswc*.log" -o -name "*.log" 2>/dev/null)

if [ -z "$LOG_FILES" ]; then
    echo -e "${RED}❌ No log files found${NC}"
    echo ""
    echo "Possible locations to check:"
    echo "  - /wp-content/uploads/wc-logs/"
    echo "  - /wp-content/debug.log"
    echo "  - /wp-content/uploads/logs/"
    exit 1
fi

echo -e "${GREEN}✓ Found log files${NC}"
echo ""

# Initialize counters
TOTAL_LOCKS_ACQUIRED=0
TOTAL_LOCKS_RELEASED=0
TOTAL_RACE_CONDITIONS=0

# Process each log file
for LOG_FILE in $LOG_FILES; do
    echo "📄 Analyzing: $(basename $LOG_FILE)"

    # Count locks acquired
    LOCKS_ACQUIRED=$(grep -c "Monitoring lock acquired" "$LOG_FILE" 2>/dev/null || echo 0)
    TOTAL_LOCKS_ACQUIRED=$((TOTAL_LOCKS_ACQUIRED + LOCKS_ACQUIRED))

    # Count locks released
    LOCKS_RELEASED=$(grep -c "Monitoring lock released" "$LOG_FILE" 2>/dev/null || echo 0)
    TOTAL_LOCKS_RELEASED=$((TOTAL_LOCKS_RELEASED + LOCKS_RELEASED))

    # Count race conditions
    RACE_CONDITIONS=$(grep -c "RACE CONDITION DETECTED" "$LOG_FILE" 2>/dev/null || echo 0)
    TOTAL_RACE_CONDITIONS=$((TOTAL_RACE_CONDITIONS + RACE_CONDITIONS))

    if [ $LOCKS_ACQUIRED -gt 0 ] || [ $RACE_CONDITIONS -gt 0 ]; then
        echo "  - Locks Acquired: $LOCKS_ACQUIRED"
        echo "  - Locks Released: $LOCKS_RELEASED"
        if [ $RACE_CONDITIONS -gt 0 ]; then
            echo -e "  - ${RED}Race Conditions: $RACE_CONDITIONS ⚠️${NC}"
        fi
    fi
done

echo ""
echo "=========================================="
echo "SUMMARY"
echo "=========================================="
echo ""

# Display totals
echo -e "${BLUE}📊 Monitoring Statistics:${NC}"
echo "  • Total Payments Processed: $TOTAL_LOCKS_ACQUIRED"
echo "  • Total Locks Released: $TOTAL_LOCKS_RELEASED"

# Check for orphaned locks
ORPHANED_LOCKS=$((TOTAL_LOCKS_ACQUIRED - TOTAL_LOCKS_RELEASED))
if [ $ORPHANED_LOCKS -gt 0 ]; then
    echo -e "  • ${YELLOW}Orphaned Locks: $ORPHANED_LOCKS ⚠️${NC}"
    echo -e "    ${YELLOW}(Locks acquired but not released)${NC}"
else
    echo -e "  • ${GREEN}Orphaned Locks: 0 ✓${NC}"
fi

echo ""

# Race condition analysis
echo -e "${BLUE}🔍 Race Condition Analysis:${NC}"
if [ $TOTAL_RACE_CONDITIONS -eq 0 ]; then
    echo -e "  ${GREEN}✓ No race conditions detected ✅${NC}"
    echo "  Your system is processing payments safely."
elif [ $TOTAL_RACE_CONDITIONS -le 2 ]; then
    echo -e "  ${YELLOW}⚠️  Rare race conditions detected: $TOTAL_RACE_CONDITIONS${NC}"
    echo "  This is uncommon but may not require immediate action."
    echo "  Continue monitoring and check for patterns."
else
    echo -e "  ${RED}❌ Multiple race conditions detected: $TOTAL_RACE_CONDITIONS${NC}"
    echo "  This indicates a genuine concurrency problem."
    echo "  RECOMMEND: Implement full lock system immediately."
fi

echo ""

# Show race condition details if any found
if [ $TOTAL_RACE_CONDITIONS -gt 0 ]; then
    echo "=========================================="
    echo "RACE CONDITION DETAILS"
    echo "=========================================="
    echo ""

    for LOG_FILE in $LOG_FILES; do
        RACE_LINES=$(grep "RACE CONDITION DETECTED" "$LOG_FILE" 2>/dev/null)
        if [ ! -z "$RACE_LINES" ]; then
            echo "📄 File: $(basename $LOG_FILE)"
            echo "$RACE_LINES" | while read -r line; do
                echo "  $line"
            done
            echo ""
        fi
    done
fi

# Recommendations
echo "=========================================="
echo "RECOMMENDATIONS"
echo "=========================================="
echo ""

if [ $TOTAL_LOCKS_ACQUIRED -eq 0 ]; then
    echo -e "${YELLOW}⚠️  No monitoring activity detected${NC}"
    echo ""
    echo "Possible causes:"
    echo "  1. No payments have been processed yet"
    echo "  2. Logs are in a different location"
    echo "  3. ASWC_Log class is not working"
    echo ""
    echo "Action: Verify monitoring is active by checking:"
    echo "  - WooCommerce → Status → Logs"
    echo "  - Trigger a test payment if possible"
    echo ""
elif [ $TOTAL_RACE_CONDITIONS -eq 0 ]; then
    echo -e "${GREEN}✓ System is working correctly${NC}"
    echo ""
    echo "Action: Continue monitoring for 1-2 weeks"
    echo "  - Run this script daily or weekly"
    echo "  - Keep helper functions available"
    echo "  - No immediate changes needed"
    echo ""
elif [ $TOTAL_RACE_CONDITIONS -le 2 ]; then
    echo -e "${YELLOW}⚠️  Minor concurrency issues detected${NC}"
    echo ""
    echo "Action: Extended monitoring recommended"
    echo "  - Continue monitoring for 2-4 weeks"
    echo "  - Check if specific subscriptions are affected"
    echo "  - Look for patterns (time of day, server load)"
    echo "  - Review Action Scheduler settings"
    echo ""
else
    echo -e "${RED}❌ Implement full lock system${NC}"
    echo ""
    echo "Action: Follow upgrade guide in PHASE1.2_OPTION_A_COMPLETED.md"
    echo "  1. Modify Scheduler API to enable blocking"
    echo "  2. Add payment amount validation"
    echo "  3. Test thoroughly in staging"
    echo "  4. Deploy to production"
    echo ""
fi

# Database check hint
echo "=========================================="
echo "ADDITIONAL CHECKS"
echo "=========================================="
echo ""
echo "To check for orphaned locks in database:"
echo "  SELECT * FROM wp_options WHERE option_name LIKE 'aswc_payment_lock_%';"
echo ""
echo "To check for duplicate orders:"
echo "  SELECT meta_value, COUNT(*) FROM wp_postmeta"
echo "  WHERE meta_key = '_aswc_subscription'"
echo "  GROUP BY meta_value HAVING COUNT(*) > 1;"
echo ""

echo "=========================================="
echo "Report generated: $(date)"
echo "=========================================="
