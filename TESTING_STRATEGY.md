# WooCommerce Evosus Sync - Testing Strategy

**Last Updated:** 2025-11-05
**Plugin Version:** 2.0.0
**Status:** 95% Complete - Ready for Safe Testing

---

## Executive Summary

This plugin is production-ready with comprehensive built-in testing features. Since only the **live Evosus instance** is available for testing, this document outlines a multi-layered approach to safely test without risking production data.

**Key Safety Features:**
- ✅ Built-in Test Mode (simulates API calls)
- ✅ Pre-flight order validation
- ✅ Review queue for problematic orders
- ✅ Comprehensive logging (all API calls logged)
- ✅ SKU mapping system
- ✅ Retry logic with exponential backoff

---

## API Endpoints Reference

The plugin interacts with these 10 Evosus API endpoints:

### Inventory Endpoints
```
POST /method/Inventory_Item_Get
├─ Purpose: Check if SKU exists in Evosus inventory
├─ Returns: Item details, quantity available, discontinued status
└─ Used in: Order validation, SKU checking
```

### Customer Endpoints
```
POST /method/Customer_Search
├─ Purpose: Find existing customer by email/name
├─ Returns: Customer ID, locations
└─ Used in: Duplicate customer checking

POST /method/Customer_Addresses_Get
├─ Purpose: Get customer's billing/shipping addresses
├─ Returns: Location IDs for billing/shipping
└─ Used in: Address verification

POST /method/Customer_Add
├─ Purpose: Create new customer in Evosus
├─ Returns: New customer ID, bill-to and ship-to location IDs
└─ Used in: First-time customer creation
```

### Order Endpoints
```
POST /method/Customer_Order_Add
├─ Purpose: Create new order in Evosus
├─ Request: Customer ID, line items, shipping, billing, totals
├─ Returns: Evosus Order ID
└─ Used in: Order synchronization

POST /method/Order_Get
├─ Purpose: Get single order details by Order ID
├─ Returns: Complete order data
└─ Used in: Order verification

POST /method/Orders_Open_Search
├─ Purpose: Search open orders
├─ Returns: List of open orders
└─ Used in: Order lookup, verification

POST /method/Orders_Closed_Search
├─ Purpose: Search closed orders
├─ Returns: List of closed orders
└─ Used in: Historical order lookup
```

### Reference Data Endpoints
```
POST /method/Distribution_Method_Get
├─ Purpose: Get shipping/distribution method reference data
├─ Returns: Distribution method ID
└─ Used in: Order creation (required field)
```

### API Configuration
```php
// Base URL (configurable via evosus_api_base_url option)
Default: https://cloud3.evosus.com/api

// Authentication (all requests)
?CompanySN={company_sn}&ticket={ticket}

// Request Format
Content-Type: application/json
User-Agent: WooCommerce-Evosus-Sync/{version}
Timeout: 30 seconds
```

---

## Testing Approach - Three Layers

### Layer 1: Test Mode (Built-in, Zero Risk) ✅

**What It Does:**
- Simulates all API calls without contacting Evosus
- Validates order data, SKUs, and business logic
- Logs everything as if it worked
- Returns mock success responses

**How to Enable:**
```php
// Via WordPress admin
update_option('evosus_test_mode', '1');

// Or via WP-CLI
wp option update evosus_test_mode 1
```

**Code Implementation:**
The test mode check happens in the `api_request()` method ([class-wc-evosus-integration.php:801-808](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/includes/class-wc-evosus-integration.php#L801-L808)):

```php
private function api_request($method, $endpoint, $body = null, $retry_count = 0) {
    $start_time = microtime(true);

    // Check test mode
    if (Evosus_Helpers::is_test_mode()) {
        $this->logger->log_info('Test mode enabled - API call simulated', [
            'endpoint' => $endpoint,
            'method' => $method,
            'body' => $body
        ]);
        return ['response' => [], 'test_mode' => true];
    }

    // ... normal API call continues
}
```

**Test Sequence:**
1. Activate plugin on staging WordPress
2. Enable test mode
3. Configure API credentials (can be dummy values)
4. Create test WooCommerce order
5. Click "Check Order First" in metabox
6. Review validation results
7. Click "Sync to Evosus"
8. Check logs: `wp evosus logs --limit=20`

**What to Verify:**
- ✅ Plugin activates without errors
- ✅ Database tables created (`wp_evosus_logs`, `wp_evosus_queue`, `wp_evosus_sku_mappings`)
- ✅ Admin dashboard loads
- ✅ Order metabox appears on edit screen
- ✅ Validation logic catches missing SKUs
- ✅ Validation logic catches invalid data
- ✅ Logs record simulated API calls

**Limitations:**
- Returns empty responses, not realistic data
- Cannot test response parsing logic
- Cannot verify actual Evosus data formats

---

### Layer 2: Mock API Response System (Recommended - 1 Hour Setup) ⭐

**What It Does:**
- Intercepts API calls in test mode
- Returns realistic Evosus API responses
- Tests complete workflow end-to-end
- Validates response parsing logic

**Implementation Plan:**

Create new file: `includes/class-evosus-mock-api.php`

```php
<?php
/**
 * Evosus Mock API - Returns realistic API responses for testing
 *
 * This class provides sample responses that match Evosus API structure
 * allowing complete workflow testing without live API access.
 */

class Evosus_Mock_API {

    /**
     * Get mock response for API endpoint
     */
    public static function get_mock_response($endpoint, $body = null) {
        $responses = self::get_response_templates();

        // Extract method name from endpoint
        // Example: "/method/Inventory_Item_Get" -> "Inventory_Item_Get"
        $method = str_replace('/method/', '', $endpoint);

        if (isset($responses[$method])) {
            return $responses[$method]($body);
        }

        // Default empty response
        return ['response' => []];
    }

    /**
     * Response templates for each endpoint
     */
    private static function get_response_templates() {
        return [

            // Inventory Item Get - Check if SKU exists
            'Inventory_Item_Get' => function($body) {
                $sku = $body['args']['ItemCode'] ?? 'UNKNOWN';

                // Simulate different scenarios based on SKU pattern
                if (strpos($sku, 'INVALID') !== false) {
                    // SKU not found
                    return ['response' => []];
                }

                if (strpos($sku, 'DISCONTINUED') !== false) {
                    // Discontinued item
                    return [
                        'response' => [[
                            'ItemCode' => $sku,
                            'Description' => 'Test Product (Discontinued)',
                            'Discontinued' => 'Yes',
                            'QuantityAvailable' => 0,
                            'Price' => 99.99
                        ]]
                    ];
                }

                if (strpos($sku, 'LOWSTOCK') !== false) {
                    // Low stock item
                    return [
                        'response' => [[
                            'ItemCode' => $sku,
                            'Description' => 'Test Product (Low Stock)',
                            'Discontinued' => 'No',
                            'QuantityAvailable' => 2,
                            'Price' => 149.99
                        ]]
                    ];
                }

                // Normal item with good stock
                return [
                    'response' => [[
                        'ItemCode' => $sku,
                        'Description' => 'Test Product',
                        'Discontinued' => 'No',
                        'QuantityAvailable' => 100,
                        'Price' => 199.99
                    ]]
                ];
            },

            // Customer Search - Find existing customer
            'Customer_Search' => function($body) {
                $email = $body['args']['Email'] ?? '';

                // Simulate existing customer for specific test emails
                if (in_array($email, ['test@example.com', 'existing@customer.com'])) {
                    return [
                        'response' => [[
                            'CustomerId' => 'MOCK-CUST-' . substr(md5($email), 0, 8),
                            'FirstName' => 'Test',
                            'LastName' => 'Customer',
                            'Email' => $email,
                            'BillToLocationId' => 'MOCK-LOC-BILL-1',
                            'ShipToLocationId' => 'MOCK-LOC-SHIP-1'
                        ]]
                    ];
                }

                // New customer - return empty
                return ['response' => []];
            },

            // Customer Addresses Get
            'Customer_Addresses_Get' => function($body) {
                $customer_id = $body['args']['CustomerId'] ?? 'MOCK-CUST-123';

                return [
                    'response' => [
                        'BillTo' => [
                            'LocationId' => 'MOCK-LOC-BILL-1',
                            'Address1' => '123 Test Street',
                            'Address2' => 'Suite 100',
                            'City' => 'Test City',
                            'State' => 'CA',
                            'Zip' => '12345',
                            'Country' => 'United States'
                        ],
                        'ShipTo' => [
                            'LocationId' => 'MOCK-LOC-SHIP-1',
                            'Address1' => '456 Shipping Ave',
                            'Address2' => '',
                            'City' => 'Ship City',
                            'State' => 'NY',
                            'Zip' => '67890',
                            'Country' => 'United States'
                        ]
                    ]
                ];
            },

            // Customer Add - Create new customer
            'Customer_Add' => function($body) {
                $email = $body['Customer']['Email'] ?? 'unknown@example.com';

                return [
                    'response' => [
                        'CustomerId' => 'MOCK-CUST-' . substr(md5($email . time()), 0, 8),
                        'BillToLocationId' => 'MOCK-LOC-BILL-' . substr(md5('bill' . time()), 0, 6),
                        'ShipToLocationId' => 'MOCK-LOC-SHIP-' . substr(md5('ship' . time()), 0, 6),
                        'Message' => 'Customer created successfully'
                    ]
                ];
            },

            // Customer Order Add - Create order
            'Customer_Order_Add' => function($body) {
                return [
                    'response' => [
                        'OrderId' => 'MOCK-ORD-' . time() . '-' . rand(1000, 9999),
                        'OrderNumber' => 'SO-' . date('Ymd') . '-' . rand(1000, 9999),
                        'Message' => 'Order created successfully'
                    ]
                ];
            },

            // Order Get - Get order details
            'Order_Get' => function($body) {
                $order_id = $body['args']['OrderId'] ?? 'MOCK-ORD-UNKNOWN';

                return [
                    'response' => [[
                        'OrderId' => $order_id,
                        'OrderNumber' => 'SO-20251105-1234',
                        'PoNo' => '12345', // WooCommerce order number
                        'CustomerId' => 'MOCK-CUST-123',
                        'Status' => 'Open',
                        'OrderDate' => date('Y-m-d H:i:s'),
                        'SubTotal' => 199.99,
                        'TaxTotal' => 15.00,
                        'ShippingTotal' => 10.00,
                        'GrandTotal' => 224.99
                    ]]
                ];
            },

            // Orders Open Search
            'Orders_Open_Search' => function($body) {
                return [
                    'response' => [
                        [
                            'OrderId' => 'MOCK-ORD-001',
                            'OrderNumber' => 'SO-20251105-1001',
                            'PoNo' => '12340',
                            'Status' => 'Open',
                            'GrandTotal' => 150.00
                        ],
                        [
                            'OrderId' => 'MOCK-ORD-002',
                            'OrderNumber' => 'SO-20251105-1002',
                            'PoNo' => '12341',
                            'Status' => 'Open',
                            'GrandTotal' => 275.50
                        ]
                    ]
                ];
            },

            // Orders Closed Search
            'Orders_Closed_Search' => function($body) {
                return [
                    'response' => [
                        [
                            'OrderId' => 'MOCK-ORD-100',
                            'OrderNumber' => 'SO-20251104-2001',
                            'PoNo' => '12300',
                            'Status' => 'Closed',
                            'GrandTotal' => 450.00,
                            'CompletedDate' => date('Y-m-d', strtotime('-1 day'))
                        ]
                    ]
                ];
            },

            // Distribution Method Get
            'Distribution_Method_Get' => function($body) {
                return [
                    'response' => [
                        [
                            'DistributionMethodId' => '1',
                            'Name' => 'Standard Shipping'
                        ],
                        [
                            'DistributionMethodId' => '2',
                            'Name' => 'Express Shipping'
                        ]
                    ]
                ];
            }
        ];
    }
}
```

**Integration into Plugin:**

Modify `class-wc-evosus-integration.php` - Update the `api_request()` method:

```php
private function api_request($method, $endpoint, $body = null, $retry_count = 0) {
    $start_time = microtime(true);

    // Check test mode
    if (Evosus_Helpers::is_test_mode()) {
        $this->logger->log_info('Test mode enabled - API call simulated', [
            'endpoint' => $endpoint,
            'method' => $method,
            'body' => $body
        ]);

        // NEW: Use mock API for realistic responses
        if (class_exists('Evosus_Mock_API')) {
            $mock_response = Evosus_Mock_API::get_mock_response($endpoint, $body);
            $this->logger->log_info('Mock API response returned', $mock_response);
            return $mock_response;
        }

        // Fallback to empty response
        return ['response' => [], 'test_mode' => true];
    }

    // ... rest of normal API call
}
```

**Testing Different Scenarios:**

```php
// Test 1: Valid SKU
$order_item->set_sku('TEST-PROD-123'); // Returns in stock

// Test 2: Invalid SKU
$order_item->set_sku('INVALID-SKU-999'); // Returns not found, validation fails

// Test 3: Discontinued Item
$order_item->set_sku('DISCONTINUED-ITEM-456'); // Returns warning

// Test 4: Low Stock
$order_item->set_sku('LOWSTOCK-PROD-789'); // Returns insufficient stock warning

// Test 5: Existing Customer
$order->set_billing_email('existing@customer.com'); // Finds customer

// Test 6: New Customer
$order->set_billing_email('newcustomer@example.com'); // Creates customer
```

**Benefits:**
- ✅ Test complete sync workflow
- ✅ Verify response parsing logic
- ✅ Test error scenarios safely
- ✅ See exact data sent to Evosus (logged)
- ✅ Validate order creation flow
- ✅ Test customer duplicate detection
- ✅ Zero risk to live Evosus data

---

### Layer 3: Single Live Test (Minimal Risk)

**Prerequisites:**
- Layers 1 and 2 completed successfully
- All validation logic verified
- Logs reviewed and understood
- Test order prepared

**Preparation:**

1. **Create Test WooCommerce Order:**
   - Use known-good SKUs from Evosus
   - Small order (1-2 simple products)
   - Test customer (your own email/address)
   - Simple shipping method

2. **Pre-validate the Order:**
   ```bash
   # WP-CLI validation (doesn't sync)
   wp evosus validate-order 12345

   # Or use metabox "Check Order First" button
   ```

3. **Review Validation Results:**
   - ✅ All SKUs found in Evosus
   - ✅ Sufficient quantity available
   - ✅ No discontinued items
   - ✅ Customer data complete

**Execution:**

```bash
# 1. Disable test mode
wp option update evosus_test_mode 0

# 2. Clear any test data from logs
wp evosus cleanup-logs --older-than=1

# 3. Sync the order
wp evosus sync-order 12345

# 4. Monitor in real-time
wp evosus logs --limit=10 --severity=all
```

**Or via WordPress Admin:**
1. Edit test order
2. Find "Evosus Sync" metabox
3. Disable test mode in settings
4. Click "Check Order First"
5. If validation passes, click "Sync to Evosus"

**Verification in Evosus:**

Check these details match:
- ✅ Order exists in Evosus
- ✅ Customer created or found correctly
- ✅ Line items match (SKU, quantity, price)
- ✅ Billing address correct
- ✅ Shipping address correct
- ✅ Order totals match (subtotal, tax, shipping, grand total)
- ✅ PO Number = WooCommerce order number
- ✅ Payment method recorded

**Review Logs:**

```bash
# View detailed logs
wp evosus logs --limit=20

# View only errors
wp evosus logs --severity=error

# View specific order
wp evosus logs --order-id=12345

# Export logs to file
wp evosus logs --limit=100 --format=json > evosus-test-logs.json
```

**Rollback Plan:**

If something goes wrong:
1. **Don't panic** - orders can be deleted/edited in Evosus
2. Check error logs: `wp evosus logs --severity=error`
3. Enable test mode: `wp option update evosus_test_mode 1`
4. Contact Evosus support if order needs cleanup
5. Review logs to identify issue
6. Fix issue in plugin/configuration
7. Test again with new order

---

## Testing Checklist

### Phase 1: Plugin Installation ✓
- [ ] Upload plugin to `/wp-content/plugins/woocommerce-evosus-sync/`
- [ ] Activate plugin in WordPress admin
- [ ] Verify no activation errors
- [ ] Check database tables created:
  ```sql
  SHOW TABLES LIKE 'wp_evosus%';
  -- Should show:
  -- wp_evosus_logs
  -- wp_evosus_queue
  -- wp_evosus_sku_mappings
  ```
- [ ] Verify admin menu appears: "Evosus Sync"

### Phase 2: Configuration ✓
- [ ] Navigate to Settings → Evosus Sync
- [ ] Enter Company SN (serial number)
- [ ] Enter API Ticket (authentication token)
- [ ] Set Distribution Method ID (from Evosus)
- [ ] Enable test mode: `evosus_test_mode = 1`
- [ ] Save settings
- [ ] Test connection (should work in test mode)

### Phase 3: Test Mode Validation ✓
- [ ] Create test WooCommerce order
- [ ] Edit order in WordPress admin
- [ ] Verify "Evosus Sync" metabox appears
- [ ] Click "Check Order First" button
- [ ] Review validation results
- [ ] Fix any validation errors (SKUs, addresses, etc.)
- [ ] Click "Sync to Evosus" button
- [ ] Check logs: `wp evosus logs --limit=10`
- [ ] Verify test mode logged: "Test mode enabled - API call simulated"

### Phase 4: Mock API Testing (Optional but Recommended) ✓
- [ ] Create `includes/class-evosus-mock-api.php`
- [ ] Load mock API class in main plugin file
- [ ] Test valid SKU scenario
- [ ] Test invalid SKU scenario
- [ ] Test discontinued item scenario
- [ ] Test low stock scenario
- [ ] Test existing customer scenario
- [ ] Test new customer scenario
- [ ] Review all logged request/response data
- [ ] Verify response parsing works correctly

### Phase 5: Pre-Live Validation ✓
- [ ] Test order with known-good Evosus SKUs
- [ ] Pre-validate: `wp evosus validate-order <id>`
- [ ] Confirm all validation passes
- [ ] Review exact data to be sent (check logs)
- [ ] Prepare rollback plan
- [ ] Notify team of test timing

### Phase 6: Single Live Test ✓
- [ ] Disable test mode: `evosus_test_mode = 0`
- [ ] Sync ONE test order
- [ ] Monitor logs in real-time
- [ ] Verify order in Evosus immediately
- [ ] Check all order details match
- [ ] Cross-reference PO number
- [ ] Test cross-reference verification
- [ ] Document any issues found

### Phase 7: Production Deployment ✓
- [ ] Verify test order successful
- [ ] Test 2-3 more orders manually
- [ ] Configure auto-sync if desired
- [ ] Enable email notifications (optional)
- [ ] Configure webhook if needed
- [ ] Monitor logs daily for first week
- [ ] Check review queue regularly
- [ ] Document any SKU mappings needed

---

## WP-CLI Commands for Testing

```bash
# Test Evosus API connection
wp evosus test-connection

# Get plugin statistics
wp evosus stats

# Validate order without syncing
wp evosus validate-order 12345

# Sync single order
wp evosus sync-order 12345

# View logs (last 20 entries)
wp evosus logs --limit=20

# View only errors
wp evosus logs --severity=error

# View logs for specific order
wp evosus logs --order-id=12345

# Export logs to JSON
wp evosus logs --limit=100 --format=json > logs.json

# Cleanup old logs (older than 30 days)
wp evosus cleanup-logs --older-than=30

# View queue status
wp evosus queue-status

# Process queue manually
wp evosus process-queue

# Retry failed syncs
wp evosus retry-failed

# View orders needing review
wp evosus review-queue

# Get sync statistics
wp evosus stats --range=today
wp evosus stats --range=this_week
wp evosus stats --range=this_month
```

### SQL Debugging Queries

For advanced debugging, you can query the database directly to view the complete API request payloads:

```sql
-- View complete request payload with LineItems for a specific order
SELECT request_data
FROM wp_evosus_logs
WHERE order_id = 12345
  AND endpoint = '/method/Customer_Order_Add'
ORDER BY created_at DESC
LIMIT 1;

-- View all API calls made for a specific order
SELECT id, log_type, endpoint, message, created_at
FROM wp_evosus_logs
WHERE order_id = 12345
ORDER BY created_at DESC;

-- View all API call logs (shows endpoint names)
SELECT id, log_type, endpoint, message, created_at
FROM wp_evosus_logs
WHERE log_type = 'api_call'
ORDER BY created_at DESC
LIMIT 10;

-- View both request and response data for debugging
SELECT endpoint, request_data, response_data, status_code, created_at
FROM wp_evosus_logs
WHERE order_id = 12345
  AND log_type = 'api_call'
ORDER BY created_at DESC;
```

**Example Output:**
The request_data field contains the complete JSON payload sent to Evosus, including:
- Customer data (ID, locations)
- Order totals and notes
- **LineItems array** with SKU, Quantity, and Product Name for each item
- Billing/shipping information
- All metadata

**Note:** Sensitive credentials (API tickets, CompanySN) are automatically redacted in logs for security.

---

## Common Test Scenarios

### Scenario 1: Missing SKU
**Setup:**
- Create WooCommerce product without SKU
- Add to test order

**Expected Result:**
- ❌ Validation fails
- ⚠️ Error: "Product 'XXX' has no SKU assigned"
- 🚫 Order flagged for review, not synced

**Fix:**
- Add SKU to product
- Re-validate order

---

### Scenario 2: Invalid SKU (Not in Evosus)
**Setup:**
- Product has SKU: "INVALID-TEST-999"
- SKU doesn't exist in Evosus inventory

**Expected Result:**
- ❌ Validation fails
- ⚠️ Error: "SKU 'INVALID-TEST-999' not found in Evosus inventory"
- 💡 Suggestions provided (similar SKUs if any)
- 🚫 Order flagged for review

**Fix Option 1:**
- Update product SKU to match Evosus

**Fix Option 2:**
- Create SKU mapping: `INVALID-TEST-999` → `CORRECT-EVOSUS-SKU`

---

### Scenario 3: Discontinued Item
**Setup:**
- Product SKU exists in Evosus
- Item marked as "Discontinued" in Evosus

**Expected Result:**
- ⚠️ Validation warning (not error)
- ⚠️ Message: "SKU 'XXX' is marked as discontinued in Evosus"
- ✅ Sync allowed (with warning logged)

---

### Scenario 4: Insufficient Stock
**Setup:**
- Product SKU: "LOW-STOCK-ITEM"
- Evosus quantity: 5
- Order quantity: 10

**Expected Result:**
- ⚠️ Validation warning
- ⚠️ Message: "Insufficient stock: Need 10, Available 5"
- ✅ Sync allowed (Evosus handles backorders)

---

### Scenario 5: Existing Customer
**Setup:**
- Order email: "existing@customer.com"
- Customer already exists in Evosus

**Expected Result:**
- ✅ Customer found via Customer_Search
- ✅ Existing Customer ID used
- ✅ Billing/shipping locations retrieved
- ✅ Order created under existing customer

**Logged API Calls:**
1. `POST /method/Customer_Search` → Found
2. `POST /method/Customer_Addresses_Get` → Locations
3. `POST /method/Customer_Order_Add` → Order created

---

### Scenario 6: New Customer
**Setup:**
- Order email: "newcustomer@test.com"
- Customer doesn't exist in Evosus

**Expected Result:**
- ℹ️ Customer not found via Customer_Search
- ✅ New customer created via Customer_Add
- ✅ New Customer ID returned
- ✅ Order created under new customer

**Logged API Calls:**
1. `POST /method/Customer_Search` → Empty
2. `POST /method/Customer_Add` → Created
3. `POST /method/Customer_Order_Add` → Order created

---

### Scenario 7: Network Timeout
**Setup:**
- Simulate network issue (only possible on live API)

**Expected Result:**
- ⚠️ API call times out
- 🔄 Automatic retry #1 (1 second delay)
- 🔄 Automatic retry #2 (2 second delay)
- 🔄 Automatic retry #3 (4 second delay)
- ❌ Final failure after 3 retries
- 📧 Email notification sent (if enabled)
- 📋 Order added to review queue

**Logged:**
- All retry attempts
- Error message
- Execution times

---

## Troubleshooting

### Issue: "Order not found in Evosus"
**Cause:** Order sync appeared successful but can't be found

**Debug Steps:**
1. Check logs: `wp evosus logs --order-id=<id>`
2. Verify Evosus Order ID was returned
3. Try Order_Get API call with returned ID
4. Check if order in "Open" vs "Closed" status
5. Search by PO number (WooCommerce order number)

---

### Issue: "Customer duplicate created"
**Cause:** Customer_Search didn't find existing customer

**Debug Steps:**
1. Check search criteria (email, name)
2. Verify email format matches exactly
3. Check for typos or extra spaces
4. Review Customer_Search API response in logs

**Fix:**
- Use SKU mapper concept for customers (future feature)
- Manually link WooCommerce customer to Evosus ID

---

### Issue: "SKU validation always fails"
**Cause:** Test mode not returning proper mock responses

**Debug Steps:**
1. Verify test mode enabled: `wp option get evosus_test_mode`
2. Check if mock API class loaded
3. Review logs for "Mock API response" messages
4. Verify SKU follows test patterns

---

### Issue: "No logs appearing"
**Cause:** Logger not initialized or database table missing

**Debug Steps:**
1. Check table exists: `SHOW TABLES LIKE 'wp_evosus_logs'`
2. Verify logger initialized in plugin init
3. Check PHP error logs for database errors
4. Re-run activation: Deactivate → Activate plugin

---

## Next Steps After Testing

### 1. Production Deployment
- [ ] Sync 5-10 test orders successfully
- [ ] Verify all orders in Evosus
- [ ] Enable auto-sync for specific order statuses
- [ ] Configure email notifications
- [ ] Set up daily log monitoring

### 2. Ongoing Monitoring
- [ ] Check logs daily: `wp evosus logs --severity=error`
- [ ] Review queue: `wp evosus review-queue`
- [ ] Monitor sync stats: `wp evosus stats`
- [ ] Handle flagged orders promptly

### 3. Optimization
- [ ] Create SKU mappings for mismatched products
- [ ] Document common validation errors
- [ ] Fine-tune notification settings
- [ ] Set up log retention policy

### 4. Staff Training
- [ ] How to use "Evosus Sync" metabox
- [ ] How to handle validation errors
- [ ] How to approve orders for sync
- [ ] How to create SKU mappings

---

## Support Resources

**Plugin Files:**
- Main plugin: [woocommerce-evosus-sync.php](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/woocommerce-evosus-sync.php)
- Integration class: [class-wc-evosus-integration.php](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/includes/class-wc-evosus-integration.php)
- Logger: [class-evosus-logger.php](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/includes/class-evosus-logger.php)

**Documentation:**
- [CURRENT_STATUS.md](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/CURRENT_STATUS.md) - What's complete
- [IMPLEMENTATION_SUMMARY.md](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/IMPLEMENTATION_SUMMARY.md) - All features
- [UPGRADE_GUIDE.md](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/UPGRADE_GUIDE.md) - Upgrade instructions
- [INTEGRATION_CHECKLIST.md](file:///Users/michaelsewell/Projects/woocommerce-evosus-sync/INTEGRATION_CHECKLIST.md) - Setup checklist

**WP-CLI Help:**
```bash
wp evosus --help
wp evosus sync-order --help
wp evosus logs --help
```

---

**Last Updated:** 2025-11-05
**Next Review:** After initial live testing
**Status:** Ready for Layer 1 (Test Mode) testing
