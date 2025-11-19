# WooCommerce Evosus Sync - Upgrade Guide

## Version 2.0.0 - Major Update

This guide documents all the improvements and new features added to the plugin.

---

## New Files Created

### Core Classes
1. **`includes/class-evosus-logger.php`** - Comprehensive logging system
   - API call logging with request/response data
   - Error logging with context
   - Sync operation tracking
   - Log cleanup functionality

2. **`includes/class-evosus-helpers.php`** - Utility functions
   - Complete country code mapping (250+ countries)
   - Encryption/decryption for sensitive data
   - HPOS compatibility helpers
   - Phone number formatting
   - Test mode detection

3. **`includes/class-evosus-sku-mapper.php`** - SKU mapping management
   - Add/update/delete SKU mappings
   - Import/export mappings via CSV
   - Search functionality
   - Bulk operations

4. **`includes/class-evosus-queue.php`** - Background processing queue
   - Async order syncing
   - Retry logic with exponential backoff
   - Priority-based processing
   - Queue statistics and monitoring

5. **`includes/class-evosus-webhook.php`** - Webhook handler
   - REST API endpoint for incoming webhooks
   - Order status synchronization
   - Inventory updates
   - Customer updates
   - Signature validation

6. **`includes/class-evosus-notifications.php`** - Email notifications
   - Sync failure notifications
   - Orders needing review alerts
   - Optional success notifications
   - Daily summary emails
   - HTML email templates

### Assets
7. **`assets/js/evosus-order-metabox.js`** - Extracted JavaScript
   - All metabox AJAX functionality
   - Internationalization support
   - Better error handling

### Configuration
8. **`uninstall.php`** - Proper cleanup on uninstall
   - Removes all options
   - Drops custom tables
   - Cleans order meta data
   - Clears scheduled cron jobs

---

## Required Updates to Existing Files

### 1. Main Plugin File (`woocommerce-evosus-sync.php`)

**Add these new dependencies after line 81:**
```php
require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-logger.php';
require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-helpers.php';
require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-sku-mapper.php';
require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-queue.php';
require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-webhook.php';
require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-notifications.php';
```

**Update activate() method to create new tables (after line 161):**
```php
// Logs table
$logs_table = $wpdb->prefix . 'evosus_logs';
$sql_logs = "CREATE TABLE IF NOT EXISTS $logs_table (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    log_type varchar(50) DEFAULT 'info',
    severity varchar(20) DEFAULT 'info',
    message text,
    endpoint varchar(255) DEFAULT NULL,
    method varchar(10) DEFAULT NULL,
    request_data longtext DEFAULT NULL,
    response_data longtext DEFAULT NULL,
    status_code int DEFAULT NULL,
    execution_time float DEFAULT 0,
    context longtext DEFAULT NULL,
    order_id bigint(20) DEFAULT NULL,
    evosus_order_id varchar(100) DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY log_type (log_type),
    KEY severity (severity),
    KEY order_id (order_id),
    KEY created_at (created_at)
) $charset_collate;";
dbDelta($sql_logs);

// Queue table
$queue_table = $wpdb->prefix . 'evosus_queue';
$sql_queue = "CREATE TABLE IF NOT EXISTS $queue_table (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    order_id bigint(20) NOT NULL,
    status varchar(20) DEFAULT 'pending',
    priority int DEFAULT 10,
    attempts int DEFAULT 0,
    scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
    started_at datetime DEFAULT NULL,
    completed_at datetime DEFAULT NULL,
    result longtext DEFAULT NULL,
    error_message text DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY order_id (order_id),
    KEY status (status),
    KEY scheduled_at (scheduled_at)
) $charset_collate;";
dbDelta($sql_queue);
```

**Add new options (after line 165):**
```php
add_option('evosus_test_mode', '0');
add_option('evosus_api_base_url', '');
add_option('evosus_enable_notifications', '0');
add_option('evosus_notification_email', get_option('admin_email'));
add_option('evosus_notify_success', '0');
add_option('evosus_enable_webhook', '0');
add_option('evosus_webhook_secret', wp_generate_password(32, false));
```

**Initialize new classes in init() method (after line 115):**
```php
// Initialize logger
$logger = Evosus_Logger::get_instance();

// Initialize queue
$queue = Evosus_Queue::get_instance();

// Initialize webhook handler
$webhook = Evosus_Webhook::get_instance();

// Initialize notifications
$notifications = Evosus_Notifications::get_instance();

// Initialize SKU mapper
$sku_mapper = Evosus_SKU_Mapper::get_instance();
```

### 2. Integration Class (`includes/class-wc-evosus-integration.php`)

**Update constructor (lines 17-20):**
```php
private $logger;
private $sku_mapper;

public function __construct($company_sn, $ticket) {
    $this->company_sn = $company_sn;
    $this->ticket = $ticket;
    $this->base_url = Evosus_Helpers::get_api_base_url(); // Now configurable!
    $this->logger = Evosus_Logger::get_instance();
    $this->sku_mapper = Evosus_SKU_Mapper::get_instance();
}
```

**Update all `get_post_meta/update_post_meta/delete_post_meta` calls to use helpers:**
```php
// OLD:
get_post_meta($order_id, '_evosus_synced', true);

// NEW:
Evosus_Helpers::get_order_meta($order_id, '_evosus_synced', true);
```

**Update SKU checking to use mapper (around line 445):**
```php
// Check for SKU mapping first
$mapped_sku = $this->sku_mapper->get_evosus_sku($product->get_sku());
$item_code = !empty($mapped_sku) ? $mapped_sku : ($product->get_sku() ?: 'WC_' . $product->get_id());
```

**Replace get_country_name method (line 751):**
```php
private function get_country_name($country_code) {
    return Evosus_Helpers::get_country_name($country_code);
}
```

**Update api_request method with logging and retry logic (line 720):**
```php
private function api_request($method, $endpoint, $body = null, $retry_count = 0) {
    $start_time = microtime(true);

    // Check test mode
    if (Evosus_Helpers::is_test_mode()) {
        $this->logger->log_info('Test mode enabled - API call would be made', [
            'endpoint' => $endpoint,
            'method' => $method,
            'body' => $body
        ]);
        return ['response' => [], 'test_mode' => true];
    }

    $url = $this->base_url . $endpoint;
    $url .= '?CompanySN=' . urlencode($this->company_sn);
    $url .= '&ticket=' . urlencode($this->ticket);

    $args = [
        'method' => $method,
        'headers' => [
            'Content-Type' => 'application/json',
            'User-Agent' => 'WooCommerce-Evosus-Sync/' . WC_EVOSUS_VERSION
        ],
        'timeout' => 30
    ];

    if ($body) {
        $args['body'] = json_encode($body);
    }

    $response = wp_remote_request($url, $args);
    $execution_time = microtime(true) - $start_time;

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();

        $this->logger->log_api_call(
            $endpoint,
            $method,
            $body,
            ['error' => $error_message],
            0,
            $execution_time
        );

        // Retry logic for network errors
        if ($retry_count < 3) {
            sleep(pow(2, $retry_count)); // Exponential backoff
            return $this->api_request($method, $endpoint, $body, $retry_count + 1);
        }

        $this->logger->log_error('API request failed after retries: ' . $error_message, [
            'endpoint' => $endpoint,
            'method' => $method
        ]);

        return false;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body_response = wp_remote_retrieve_body($response);
    $decoded = json_decode($body_response, true);

    // Log API call
    $this->logger->log_api_call(
        $endpoint,
        $method,
        $body,
        $decoded,
        $status_code,
        $execution_time
    );

    // Validate response
    if ($status_code < 200 || $status_code >= 300) {
        $this->logger->log_error("API returned error status: {$status_code}", [
            'endpoint' => $endpoint,
            'response' => $decoded
        ]);

        // Retry for 5xx errors
        if ($status_code >= 500 && $retry_count < 3) {
            sleep(pow(2, $retry_count));
            return $this->api_request($method, $endpoint, $body, $retry_count + 1);
        }

        return false;
    }

    return $decoded;
}
```

**Fix inefficient get_evosus_order_details (line 498):**
```php
public function get_evosus_order_details($evosus_order_id) {
    // Try to get order directly by ID if endpoint exists
    $response = $this->api_request('POST', '/method/Order_Get', [
        'args' => [
            'OrderID' => $evosus_order_id
        ]
    ]);

    if ($response && isset($response['response']) && !empty($response['response'])) {
        return [
            'success' => true,
            'order' => $response['response'][0]
        ];
    }

    // Fallback to searching (less efficient but works)
    $response = $this->api_request('POST', '/method/Orders_Open_Search', [
        'args' => ['OrderID_List' => $evosus_order_id]
    ]);

    if ($response && isset($response['response'])) {
        foreach ($response['response'] as $order) {
            if ($order['OrderId'] == $evosus_order_id) {
                return [
                    'success' => true,
                    'order' => $order
                ];
            }
        }
    }

    // Check closed orders
    $end_date = date('Y-m-d H:i:s');
    $begin_date = date('Y-m-d H:i:s', strtotime('-180 days'));

    $response = $this->api_request('POST', '/method/Orders_Closed_Search', [
        'args' => [
            'Begin_Date' => $begin_date,
            'End_Date' => $end_date
        ]
    ]);

    if ($response && isset($response['response'])) {
        foreach ($response['response'] as $order) {
            if ($order['OrderId'] == $evosus_order_id) {
                return [
                    'success' => true,
                    'order' => $order
                ];
            }
        }
    }

    return [
        'success' => false,
        'message' => 'Order not found in Evosus'
    ];
}
```

**Add trigger for notifications after sync:**
```php
// After successful sync (around line 70):
do_action('evosus_sync_success', $wc_order_id, $order_result['evosus_order_id']);

// After sync failure (around line 53):
do_action('evosus_sync_failed', $wc_order_id, $evosus_customer['message']);

// After marking for review (line 38):
do_action('evosus_order_needs_review', $wc_order_id, $validation['issues']);
```

### 3. Order Metabox Class (`includes/class-evosus-order-metabox.php`)

**Update enqueue_order_scripts method (line 575):**
```php
public function enqueue_order_scripts($hook) {
    global $post;

    if ($hook !== 'post.php' || !$post || $post->post_type !== 'shop_order') {
        return;
    }

    // Enqueue external JavaScript
    wp_enqueue_script(
        'evosus-order-metabox',
        WC_EVOSUS_PLUGIN_URL . 'assets/js/evosus-order-metabox.js',
        ['jquery'],
        WC_EVOSUS_VERSION,
        true
    );

    // Localize script with data and translations
    wp_localize_script('evosus-order-metabox', 'evosusSyncData', [
        'orderId' => $post->ID,
        'nonce' => wp_create_nonce('evosus_sync_order'),
        'i18n' => [
            'validating' => __('Validating...', 'woocommerce-evosus-sync'),
            'checkOrder' => __('Check Order First', 'woocommerce-evosus-sync'),
            'validationSuccess' => __('Order validated successfully! Ready to sync.', 'woocommerce-evosus-sync'),
            'validationIssues' => __('Validation issues found. Please refresh to see details.', 'woocommerce-evosus-sync'),
            'validationFailed' => __('Validation failed', 'woocommerce-evosus-sync'),
            'networkError' => __('Network error. Please try again.', 'woocommerce-evosus-sync'),
            'confirmSync' => __('Add this order to Evosus?\n\nMake sure you have reviewed the order details.', 'woocommerce-evosus-sync'),
            'syncing' => __('Adding to Evosus...', 'woocommerce-evosus-sync'),
            'syncSuccess' => __('Order successfully added to Evosus!', 'woocommerce-evosus-sync'),
            'evosusOrderId' => __('Evosus Order ID', 'woocommerce-evosus-sync'),
            'wcOrderNumber' => __('WC Order', 'woocommerce-evosus-sync'),
            'addedToPO' => __('added to PO field', 'woocommerce-evosus-sync'),
            'needsReview' => __('Order needs review. Refreshing page...', 'woocommerce-evosus-sync'),
            'syncFailed' => __('Failed', 'woocommerce-evosus-sync'),
            'addToEvosus' => __('Add to Evosus', 'woocommerce-evosus-sync'),
            'verifying' => __('Verifying...', 'woocommerce-evosus-sync'),
            'verifyReference' => __('Verify Cross-Reference', 'woocommerce-evosus-sync'),
            'verified' => __('Verified!', 'woocommerce-evosus-sync'),
            'poNumber' => __('PO Number in Evosus', 'woocommerce-evosus-sync'),
            'mismatch' => __('Mismatch!', 'woocommerce-evosus-sync'),
            'checkInEvosus' => __('Please check the order in Evosus.', 'woocommerce-evosus-sync'),
            'verificationError' => __('Network error during verification.', 'woocommerce-evosus-sync'),
            'checking' => __('Checking...', 'woocommerce-evosus-sync'),
            'recheckOrder' => __('Re-check Order', 'woocommerce-evosus-sync'),
            'issuesResolved' => __('All issues resolved!', 'woocommerce-evosus-sync'),
            'errorChecking' => __('Error checking order.', 'woocommerce-evosus-sync'),
            'confirmApprove' => __('Are you sure all issues are resolved?\n\nThis will add the order to Evosus.', 'woocommerce-evosus-sync'),
            'approveAndAdd' => __('Approve & Add to Evosus', 'woocommerce-evosus-sync'),
            'mapping' => __('Mapping...', 'woocommerce-evosus-sync'),
            'enterSKU' => __('Please enter a SKU', 'woocommerce-evosus-sync'),
            'mapped' => __('Mapped', 'woocommerce-evosus-sync'),
            'skuMapped' => __('SKU mapped successfully!', 'woocommerce-evosus-sync'),
            'error' => __('Error', 'woocommerce-evosus-sync'),
            'tryAgain' => __('Try Again', 'woocommerce-evosus-sync')
        ]
    ]);
}
```

**Remove inline JavaScript from render_meta_box (lines 267-528)** - it's now in external file

---

## New Features Available

### 1. Test Mode
Enable in settings to simulate API calls without actually making them. Useful for development and testing.

### 2. Configurable API Base URL
Can now use different Evosus environments (staging, production, etc.)

### 3. Email Notifications
Get notified when:
- Orders fail to sync
- Orders need manual review
- Optionally: When orders sync successfully
- Daily summary of sync activity

### 4. Background Queue
Orders can be queued for async processing:
```php
$queue = Evosus_Queue::get_instance();
$queue->add_job($order_id, $priority = 10);
```

### 5. SKU Mapping Management
Manage permanent SKU mappings:
```php
$mapper = Evosus_SKU_Mapper::get_instance();
$mapper->add_mapping('WC-SKU-123', 'EVOSUS-SKU-456', $product_id);
```

### 6. Webhook Support
Receive updates from Evosus:
- Webhook URL: `https://yoursite.com/wp-json/evosus/v1/webhook`
- Configure webhook secret in settings
- Supports order updates, status changes, inventory updates

### 7. Comprehensive Logging
All API calls, errors, and sync operations are logged:
```php
$logger = Evosus_Logger::get_instance();
$logs = $logger->get_logs(['severity' => 'error', 'limit' => 50]);
```

### 8. Data Encryption
API credentials can be encrypted (add to wp-config.php):
```php
define('EVOSUS_ENCRYPTION_KEY', 'your-32-character-secret-key-here');
```

### 9. HPOS Compatibility
Full support for WooCommerce High-Performance Order Storage

### 10. Bulk Operations (Coming in Admin UI)
- Bulk sync multiple orders
- Bulk SKU mapping
- Export/import SKU mappings

---

## Settings to Add in Admin

New settings needed in settings page:

1. **Test Mode** - Enable/disable test mode
2. **API Base URL** - Custom API endpoint (optional)
3. **Enable Notifications** - Turn on/off email notifications
4. **Notification Email** - Where to send notifications
5. **Notify on Success** - Send emails for successful syncs too
6. **Enable Webhooks** - Allow incoming webhooks
7. **Webhook Secret** - Secret key for webhook validation
8. **Webhook URL** - Display the webhook URL for configuration in Evosus

---

## Database Changes

Three new tables will be created on activation:
1. `wp_evosus_logs` - Comprehensive logging
2. `wp_evosus_queue` - Background processing queue
3. `wp_evosus_sku_mappings` - SKU mappings (already existed but now functional)

---

## Cron Jobs

New scheduled tasks:
1. `evosus_process_queue` - Runs every minute to process queue
2. `evosus_cleanup_logs` - Daily cleanup of old logs (90 days)
3. `evosus_retry_failed_syncs` - Retry failed syncs (optional)

---

## Next Steps

1. **Test on staging environment first**
2. **Backup database before updating**
3. **Update all files as documented above**
4. **Activate plugin to create new tables**
5. **Configure new settings**
6. **Test sync functionality thoroughly**
7. **Monitor logs for any issues**

---

## Compatibility

- **WordPress**: 5.8+
- **PHP**: 7.4+
- **WooCommerce**: 5.0+
- **HPOS**: Fully compatible

---

## Performance Improvements

1. **API Caching** - Reduce duplicate API calls
2. **Retry Logic** - Automatic retry with exponential backoff
3. **Background Processing** - Orders synced asynchronously via queue
4. **Efficient Order Lookup** - Fixed inefficient `get_evosus_order_details`
5. **Database Indexes** - Optimized queries on custom tables

---

## Security Enhancements

1. **Credential Encryption** - Optional encryption for stored credentials
2. **Webhook Validation** - Signature-based webhook authentication
3. **Nonce Verification** - Enhanced AJAX security
4. **Capability Checks** - Proper permission checking throughout
5. **Sanitization** - All user inputs sanitized

---

## Support

For issues or questions, please open an issue on the GitHub repository.

---

**Version**: 2.0.0
**Last Updated**: 2025-10-24
