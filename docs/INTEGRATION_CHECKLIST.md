# Integration Checklist

Use this checklist to integrate all the new improvements into your WooCommerce Evosus Sync plugin.

---

## Pre-Integration

- [ ] **Backup your entire project**
- [ ] **Backup your database**
- [ ] **Create a staging environment** (highly recommended)
- [ ] **Document current settings** (screenshot settings page)
- [ ] **Note any custom modifications** you've made

---

## File Integration Order

Follow this order to minimize issues:

### Phase 1: Add New Files (No Breaking Changes)

These files are completely new and won't affect existing functionality:

- [ ] Copy `includes/class-evosus-logger.php`
- [ ] Copy `includes/class-evosus-helpers.php`
- [ ] Copy `includes/class-evosus-sku-mapper.php`
- [ ] Copy `includes/class-evosus-queue.php`
- [ ] Copy `includes/class-evosus-webhook.php`
- [ ] Copy `includes/class-evosus-notifications.php`
- [ ] Copy `includes/class-evosus-cli.php`
- [ ] Copy `assets/js/evosus-order-metabox.js`
- [ ] Copy `uninstall.php`

**Test:** Plugin should still work normally at this point.

### Phase 2: Update Main Plugin File

- [ ] Open `woocommerce-evosus-sync.php`
- [ ] **Line 26** - Update version:
  ```php
  define('WC_EVOSUS_VERSION', '2.0.0');
  ```
- [ ] **After line 80** - Add new file includes:
  ```php
  require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-logger.php';
  require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-helpers.php';
  require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-sku-mapper.php';
  require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-queue.php';
  require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-webhook.php';
  require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-notifications.php';

  if (defined('WP_CLI') && WP_CLI) {
      require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-cli.php';
  }
  ```

- [ ] **Lines 109-119** - Update `init()` method, add after line 115:
  ```php
  // Initialize new components
  Evosus_Logger::get_instance();
  Evosus_Queue::get_instance();
  Evosus_Webhook::get_instance();
  Evosus_Notifications::get_instance();
  Evosus_SKU_Mapper::get_instance();
  ```

- [ ] **Lines 142-169** - Update `activate()` method:

  **After line 161, ADD:**
  ```php
  // Create logs table
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

  // Create queue table
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

  **After line 165, ADD:**
  ```php
  add_option('evosus_test_mode', '0');
  add_option('evosus_api_base_url', '');
  add_option('evosus_enable_notifications', '0');
  add_option('evosus_notification_email', get_option('admin_email'));
  add_option('evosus_notify_success', '0');
  add_option('evosus_enable_webhook', '0');
  add_option('evosus_webhook_secret', wp_generate_password(32, false));
  ```

- [ ] **Save file**

**Test:** Navigate to WordPress admin. Plugin should load without errors.

### Phase 3: Update Integration Class

- [ ] Open `includes/class-wc-evosus-integration.php`

- [ ] **Lines 12-20** - Update class properties and constructor:
  ```php
  private $company_sn;
  private $ticket;
  private $base_url;
  private $logger;
  private $sku_mapper;

  public function __construct($company_sn, $ticket) {
      $this->company_sn = $company_sn;
      $this->ticket = $ticket;
      $this->base_url = Evosus_Helpers::get_api_base_url();
      $this->logger = Evosus_Logger::get_instance();
      $this->sku_mapper = Evosus_SKU_Mapper::get_instance();
  }
  ```

- [ ] **Search and replace throughout file:**
  - Find: `get_post_meta($wc_order_id,`
  - Replace: `Evosus_Helpers::get_order_meta($wc_order_id,`

  - Find: `update_post_meta($wc_order_id,`
  - Replace: `Evosus_Helpers::update_order_meta($wc_order_id,`

  - Find: `delete_post_meta($wc_order_id,`
  - Replace: `Evosus_Helpers::delete_order_meta($wc_order_id,`

- [ ] **Line 751** - Replace `get_country_name()` method body:
  ```php
  private function get_country_name($country_code) {
      return Evosus_Helpers::get_country_name($country_code);
  }
  ```

- [ ] **Line 445** - Update `prepare_line_items()` to use SKU mapper:
  ```php
  // After getting $sku_override, add:
  if (empty($sku_override)) {
      $mapped_sku = $this->sku_mapper->get_evosus_sku($product->get_sku());
      $item_code = !empty($mapped_sku) ? $mapped_sku : ($product->get_sku() ?: 'WC_' . $product->get_id());
  } else {
      $item_code = $sku_override;
  }
  ```

- [ ] **Line 720-746** - Replace `api_request()` method with the enhanced version from UPGRADE_GUIDE.md

- [ ] **Line 498-541** - Replace `get_evosus_order_details()` method with the optimized version from UPGRADE_GUIDE.md

- [ ] **Add action hooks for notifications:**
  - After successful sync (around line 70): `do_action('evosus_sync_success', $wc_order_id, $order_result['evosus_order_id']);`
  - After sync failure (around line 53): `do_action('evosus_sync_failed', $wc_order_id, $evosus_customer['message']);`
  - After marking for review (line 38): `do_action('evosus_order_needs_review', $wc_order_id, $validation['issues']);`

- [ ] **Save file**

**Test:** Try syncing an order. Check logs table for entries.

### Phase 4: Update Metabox Class

- [ ] Open `includes/class-evosus-order-metabox.php`

- [ ] **Line 575-584** - Replace `enqueue_order_scripts()` method with the version from UPGRADE_GUIDE.md (includes localization)

- [ ] **Lines 267-528** - Delete the inline `<script>` block (it's now in external JS file)

- [ ] **Before the closing `?>` at line 529, ADD:**
  ```php
  <style>
      .evosus-spinner {
          display: inline-block;
          width: 16px;
          height: 16px;
          border: 2px solid #f3f3f3;
          border-top: 2px solid #2271b1;
          border-radius: 50%;
          animation: evosus-spin 1s linear infinite;
      }
      @keyframes evosus-spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
      }
      .evosus-message-success {
          padding: 10px;
          background: #d4edda;
          border-left: 4px solid #28a745;
          color: #155724;
          border-radius: 4px;
      }
      .evosus-message-error {
          padding: 10px;
          background: #f8d7da;
          border-left: 4px solid #dc3545;
          color: #721c24;
          border-radius: 4px;
      }
      .evosus-message-info {
          padding: 10px;
          background: #d1ecf1;
          border-left: 4px solid #17a2b8;
          color: #0c5460;
          border-radius: 4px;
      }
  </style>
  ```

- [ ] **Save file**

**Test:** Edit an order. Metabox should load with external JavaScript.

### Phase 5: Update Admin Class (Optional but Recommended)

- [ ] Open `includes/class-evosus-sync-admin.php`

- [ ] **Line 227-305** - Update `render_settings()` to add new fields

  **After line 275 (Distribution Method field), ADD:**
  ```php
  <tr>
      <th scope="row">
          <label for="test_mode"><?php _e('Test Mode', 'woocommerce-evosus-sync'); ?></label>
      </th>
      <td>
          <label>
              <input type="checkbox" id="test_mode" name="test_mode" value="1" <?php checked(get_option('evosus_test_mode'), '1'); ?>>
              <?php _e('Enable test mode (simulates API calls without actually making them)', 'woocommerce-evosus-sync'); ?>
          </label>
      </td>
  </tr>
  <tr>
      <th scope="row">
          <label for="api_base_url"><?php _e('Custom API URL', 'woocommerce-evosus-sync'); ?></label>
      </th>
      <td>
          <input type="text" id="api_base_url" name="api_base_url" value="<?php echo esc_attr(get_option('evosus_api_base_url')); ?>" class="regular-text">
          <p class="description"><?php _e('Leave blank to use default (https://cloud3.evosus.com/api)', 'woocommerce-evosus-sync'); ?></p>
      </td>
  </tr>
  <tr>
      <th scope="row">
          <label for="enable_notifications"><?php _e('Email Notifications', 'woocommerce-evosus-sync'); ?></label>
      </th>
      <td>
          <label>
              <input type="checkbox" id="enable_notifications" name="enable_notifications" value="1" <?php checked(get_option('evosus_enable_notifications'), '1'); ?>>
              <?php _e('Enable email notifications for sync events', 'woocommerce-evosus-sync'); ?>
          </label>
          <p class="description">
              <input type="email" name="notification_email" value="<?php echo esc_attr(get_option('evosus_notification_email')); ?>" class="regular-text" placeholder="admin@example.com">
              <br><small><?php _e('Email address for notifications', 'woocommerce-evosus-sync'); ?></small>
          </p>
      </td>
  </tr>
  <tr>
      <th scope="row">
          <label for="enable_webhook"><?php _e('Webhooks', 'woocommerce-evosus-sync'); ?></label>
      </th>
      <td>
          <label>
              <input type="checkbox" id="enable_webhook" name="enable_webhook" value="1" <?php checked(get_option('evosus_enable_webhook'), '1'); ?>>
              <?php _e('Enable incoming webhooks from Evosus', 'woocommerce-evosus-sync'); ?>
          </label>
          <p class="description">
              <strong><?php _e('Webhook URL:', 'woocommerce-evosus-sync'); ?></strong><br>
              <code><?php echo Evosus_Webhook::get_webhook_url(); ?></code><br>
              <strong><?php _e('Webhook Secret:', 'woocommerce-evosus-sync'); ?></strong><br>
              <input type="text" readonly value="<?php echo esc_attr(get_option('evosus_webhook_secret')); ?>" class="regular-text" onclick="this.select();">
          </p>
      </td>
  </tr>
  ```

- [ ] **Line 228-236** - Update save handler to include new fields:
  ```php
  if (isset($_POST['save_evosus_settings'])) {
      check_admin_referer('evosus_settings_nonce');

      update_option('evosus_company_sn', sanitize_text_field($_POST['company_sn']));
      update_option('evosus_ticket', sanitize_text_field($_POST['ticket']));
      update_option('evosus_auto_sync', isset($_POST['auto_sync']) ? '1' : '0');
      update_option('evosus_distribution_method_id', sanitize_text_field($_POST['distribution_method_id']));
      // NEW:
      update_option('evosus_test_mode', isset($_POST['test_mode']) ? '1' : '0');
      update_option('evosus_api_base_url', esc_url_raw($_POST['api_base_url']));
      update_option('evosus_enable_notifications', isset($_POST['enable_notifications']) ? '1' : '0');
      update_option('evosus_notification_email', sanitize_email($_POST['notification_email']));
      update_option('evosus_enable_webhook', isset($_POST['enable_webhook']) ? '1' : '0');

      echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'woocommerce-evosus-sync') . '</p></div>';
  }
  ```

- [ ] **Save file**

**Test:** Navigate to Evosus Sync settings page. New fields should appear.

---

## Post-Integration Testing

### 1. Activation Test
- [ ] Deactivate plugin
- [ ] Reactivate plugin
- [ ] Check that new database tables exist:
  ```sql
  SHOW TABLES LIKE 'wp_evosus_logs';
  SHOW TABLES LIKE 'wp_evosus_queue';
  ```

### 2. Basic Functionality Test
- [ ] Configure API credentials
- [ ] Try to sync an order
- [ ] Check order notes
- [ ] Verify sync metadata saved

### 3. Logging Test
- [ ] Check logs table has entries:
  ```sql
  SELECT * FROM wp_evosus_logs ORDER BY created_at DESC LIMIT 10;
  ```
- [ ] Verify API calls are logged
- [ ] Check error logging works

### 4. Queue Test
- [ ] Add job to queue via WP-CLI:
  ```bash
  # First, find an order ID
  wp evosus queue-status
  ```
- [ ] Wait 1 minute for cron to run
- [ ] Check queue status again

### 5. Notification Test
- [ ] Enable notifications in settings
- [ ] Send test email:
  ```bash
  wp evosus test-email
  ```
- [ ] Check email received

### 6. WP-CLI Test
```bash
wp evosus test-connection
wp evosus stats
wp evosus logs --limit=5
wp evosus queue-status
```

### 7. Webhook Test
- [ ] Enable webhooks in settings
- [ ] Copy webhook URL and secret
- [ ] Send test POST request:
  ```bash
  curl -X POST https://yoursite.com/wp-json/evosus/v1/webhook \
    -H "Content-Type: application/json" \
    -H "X-Evosus-Secret: YOUR_SECRET" \
    -d '{"event_type":"order.updated","evosus_order_id":"12345"}'
  ```

---

## Troubleshooting

### JavaScript not loading?
- Clear browser cache
- Check browser console for errors
- Verify file path: `assets/js/evosus-order-metabox.js`

### Database tables not created?
- Deactivate and reactivate plugin
- Check MySQL user has CREATE TABLE permission
- Check WordPress debug log

### Queue not processing?
- Check cron is running: `wp cron event list`
- Manually trigger: `wp cron event run evosus_process_queue`
- Check for PHP errors in log

### Notifications not sending?
- Test WordPress mail: `wp evosus test-email`
- Check spam folder
- Verify SMTP configuration
- Check notification email address in settings

### Webhooks not working?
- Verify permalink structure (must not be "Plain")
- Check .htaccess rules
- Test with: `wp evosus webhook-test`
- Check webhook secret matches

---

## Rollback Plan

If something goes wrong:

1. **Immediate rollback:**
   - Replace all modified files with backed up versions
   - Deactivate/reactivate plugin

2. **Database rollback:**
   - Restore database from backup
   - OR manually drop new tables:
     ```sql
     DROP TABLE wp_evosus_logs;
     DROP TABLE wp_evosus_queue;
     ```

3. **Partial rollback:**
   - Keep new files but don't load them (comment out requires)
   - Remove new database tables
   - Remove new options

---

## Success Criteria

You know integration is successful when:

- [ ] Plugin activates without errors
- [ ] Settings page loads with new fields
- [ ] Order sync works as before
- [ ] New logs appear in `wp_evosus_logs` table
- [ ] WP-CLI commands respond
- [ ] No PHP errors in debug log
- [ ] Cron jobs are scheduled
- [ ] Test email sends successfully

---

## Post-Integration Cleanup

After successful integration:

- [ ] Remove backup files
- [ ] Update version in readme.txt
- [ ] Test uninstall process (on staging only!)
- [ ] Update changelog
- [ ] Document any customizations made

---

## Getting Help

If you encounter issues:

1. Check `UPGRADE_GUIDE.md` for detailed examples
2. Check `IMPLEMENTATION_SUMMARY.md` for feature overview
3. Review WordPress debug log
4. Check browser console for JavaScript errors
5. Use WP-CLI for diagnostics: `wp evosus stats`

---

**Estimated Integration Time**: 1-2 hours
**Recommended Environment**: Staging first, then production
**Backup Required**: Yes (database + files)
**Rollback Difficulty**: Easy (if backups exist)

---

Good luck with the integration! Take your time and test thoroughly at each phase.
