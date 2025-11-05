# Tomorrow's Execution Plan - Evosus Plugin

**Date:** 2025-11-06
**Priority:** Second project of the day (after woo-orders)
**Estimated Time:** 2 hours
**Status:** 95% complete, ready for testing

---

## Decision Made: Mock API Testing Approach ✅

We're building the mock API system (Layer 2) for safe, thorough testing.

**Why:**
- Only live Evosus instance available (no test environment)
- Mock API provides realistic responses without risk
- Tests complete workflow including response parsing
- Builds confidence before live test
- Zero risk to production Evosus data

---

## Execution Sequence

### 1. Build Mock API System (30 minutes)
**File:** `includes/class-evosus-mock-api.php`
**Status:** Implementation code ready in [TESTING_STRATEGY.md](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/TESTING_STRATEGY.md)

**Tasks:**
- [ ] Create includes/class-evosus-mock-api.php
- [ ] Copy implementation code from TESTING_STRATEGY.md lines 47-276
- [ ] Load class in woocommerce-evosus-sync.php
- [ ] Update api_request() method to use mock API in test mode
- [ ] Test plugin activation - no errors

**Expected Result:**
- Mock API returns realistic Evosus responses in test mode
- All 10 endpoints have mock implementations

---

### 2. Test Complete Workflows (30 minutes)
**Approach:** Test all scenarios with realistic mock responses

**Test Scenarios:**

**A. Valid SKU - Should Pass**
- Product SKU: TEST-PROD-123
- Expected: Found in inventory, 100 qty available
- Result: Order validates, sync succeeds

**B. Invalid SKU - Should Fail**
- Product SKU: INVALID-SKU-999
- Expected: Not found in inventory
- Result: Order validation fails, flagged for review

**C. Discontinued Item - Warning**
- Product SKU: DISCONTINUED-ITEM-456
- Expected: Found but marked discontinued
- Result: Warning logged, sync allowed

**D. Low Stock - Warning**
- Product SKU: LOWSTOCK-PROD-789
- Expected: Only 2 units available, order needs 5
- Result: Warning logged, sync allowed (Evosus handles backorders)

**E. Existing Customer**
- Email: existing@customer.com
- Expected: Customer found via Customer_Search
- Result: Uses existing customer ID

**F. New Customer**
- Email: newcustomer@example.com
- Expected: Customer not found, new customer created
- Result: New customer ID returned

**Validation:**
- [ ] Check logs: `wp evosus logs --limit=50`
- [ ] Verify mock responses logged correctly
- [ ] Confirm validation logic working
- [ ] Review request/response data format

---

### 3. Optional Polish (20 minutes)
**IF TIME PERMITS** - Core works without these

- [ ] Clean up metabox JavaScript (5 min)
  - File: includes/class-evosus-order-metabox.php
  - Remove lines 270-530 (duplicate inline JS)

- [ ] Add admin settings UI (15 min)
  - File: includes/class-evosus-sync-admin.php
  - Add test mode checkbox
  - Add notification settings
  - Add webhook URL display

---

### 4. Live Test - ONE Order (15 minutes)
**Preparation:**
- [ ] Create test WooCommerce order with known-good SKUs
- [ ] Pre-validate: `wp evosus validate-order <id>`
- [ ] Confirm validation passes
- [ ] Review exact data to be sent (check logs)

**Execution:**
- [ ] Disable test mode: `wp option update evosus_test_mode 0`
- [ ] Sync order via metabox or: `wp evosus sync-order <id>`
- [ ] Monitor logs in real-time: `wp evosus logs --limit=10`

**Verification in Evosus:**
- [ ] Order exists in Evosus
- [ ] Customer created/found correctly
- [ ] Line items match (SKU, quantity, price)
- [ ] Billing address correct
- [ ] Shipping address correct
- [ ] Order totals match
- [ ] PO Number = WooCommerce order number

---

### 5. Deploy to Production (10 minutes)
**WordPress Plugin Installation:**
- [ ] Backup friendlyfires.ca site
- [ ] Upload to `/wp-content/plugins/woocommerce-evosus-sync/`
- [ ] Activate plugin
- [ ] Verify database tables created
- [ ] Configure API credentials in settings
- [ ] Test mode OFF for production use
- [ ] Monitor first few syncs

---

### 6. Post-Deploy Monitoring (10 minutes)
**Verification:**
- [ ] Check logs: `wp evosus logs --severity=error`
- [ ] Review queue: `wp evosus review-queue`
- [ ] Monitor stats: `wp evosus stats`
- [ ] Test on 2-3 more orders
- [ ] Document any SKU mappings needed

---

## Rollback Plan

**If something goes wrong:**
1. Don't panic - orders can be deleted in Evosus
2. Check error logs: `wp evosus logs --severity=error`
3. Enable test mode: `wp option update evosus_test_mode 1`
4. Contact Evosus support if order cleanup needed
5. Review logs to identify issue
6. Fix issue in plugin/configuration
7. Test again with new order

---

## Success Criteria

- ✅ Mock API system built and working
- ✅ All test scenarios pass with realistic responses
- ✅ ONE live test order syncs successfully
- ✅ Order verified in Evosus
- ✅ Plugin deployed to production
- ✅ Monitoring in place

---

## WP-CLI Quick Reference

```bash
# Enable/disable test mode
wp option update evosus_test_mode 1  # Enable
wp option update evosus_test_mode 0  # Disable

# Validate order (doesn't sync)
wp evosus validate-order 12345

# Sync order
wp evosus sync-order 12345

# View logs
wp evosus logs --limit=20
wp evosus logs --severity=error
wp evosus logs --order-id=12345

# Check stats
wp evosus stats
wp evosus stats --range=today

# Queue status
wp evosus queue-status

# Test connection
wp evosus test-connection
```

---

## Files Reference

**Key Files:**
- [TESTING_STRATEGY.md](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/TESTING_STRATEGY.md) - Complete testing guide with mock API code
- [CURRENT_STATUS.md](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/CURRENT_STATUS.md) - What's done/remaining
- [woocommerce-evosus-sync.php](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/woocommerce-evosus-sync.php) - Main plugin file
- [class-wc-evosus-integration.php](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/includes/class-wc-evosus-integration.php) - Core integration

**To Create Tomorrow:**
- includes/class-evosus-mock-api.php (copy from TESTING_STRATEGY.md)

---

**Ready to ship! 🚀**
