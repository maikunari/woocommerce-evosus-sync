<?php
/**
 * Admin Dashboard for Evosus Sync
 * Optional dashboard for viewing and managing synced orders
 * 
 * NOTE: This file is OPTIONAL. The main functionality works without it.
 * The order metabox is the primary interface.
 */

class Evosus_Sync_Admin {
    
    private $integration;
    
    public function __construct($integration) {
        $this->integration = $integration;
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // AJAX handlers
        add_action('wp_ajax_sync_single_order', [$this, 'ajax_sync_single_order']);
        add_action('wp_ajax_map_order_sku', [$this, 'ajax_map_order_sku']);
        add_action('wp_ajax_approve_and_sync_order', [$this, 'ajax_approve_and_sync_order']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Evosus Sync',
            'Evosus Sync',
            'manage_woocommerce',
            'evosus-sync',
            [$this, 'render_dashboard'],
            'dashicons-update',
            56
        );
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'today';
        ?>
        <div class="wrap">
            <h1>Evosus Sync Dashboard</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=evosus-sync&tab=today" class="nav-tab <?php echo $active_tab === 'today' ? 'nav-tab-active' : ''; ?>">
                    Today
                </a>
                <a href="?page=evosus-sync&tab=week" class="nav-tab <?php echo $active_tab === 'week' ? 'nav-tab-active' : ''; ?>">
                    This Week
                </a>
                <a href="?page=evosus-sync&tab=month" class="nav-tab <?php echo $active_tab === 'month' ? 'nav-tab-active' : ''; ?>">
                    This Month
                </a>
                <a href="?page=evosus-sync&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    Settings
                </a>
            </h2>
            
            <div class="evosus-sync-content">
                <?php
                switch ($active_tab) {
                    case 'today':
                        $this->render_synced_orders_table('today', 'Today');
                        break;
                    case 'week':
                        $this->render_synced_orders_table('this_week', 'This Week');
                        break;
                    case 'month':
                        $this->render_synced_orders_table('this_month', 'This Month');
                        break;
                    case 'settings':
                        $this->render_settings();
                        break;
                }
                ?>
            </div>
        </div>
        
        <style>
            .evosus-sync-content {
                margin-top: 20px;
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .sync-stats {
                display: flex;
                gap: 20px;
                margin-bottom: 30px;
            }
            .stat-box {
                flex: 1;
                padding: 20px;
                background: #f0f6fc;
                border-left: 4px solid #2271b1;
                border-radius: 4px;
            }
            .stat-box h3 {
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #666;
                text-transform: uppercase;
            }
            .stat-box .stat-number {
                font-size: 32px;
                font-weight: bold;
                color: #2271b1;
            }
            .sync-table {
                width: 100%;
                border-collapse: collapse;
            }
            .sync-table th {
                text-align: left;
                padding: 12px;
                background: #f9f9f9;
                border-bottom: 2px solid #ddd;
                font-weight: 600;
            }
            .sync-table td {
                padding: 12px;
                border-bottom: 1px solid #eee;
            }
            .sync-table tr:hover {
                background: #f9f9f9;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 500;
            }
            .status-synced {
                background: #d4edda;
                color: #155724;
            }
        </style>
        <?php
    }
    
    /**
     * Render synced orders table
     */
    private function render_synced_orders_table($range, $title) {
        $orders = $this->integration->get_orders_synced_in_range($range);
        $total_revenue = array_sum(array_column($orders, 'order_total'));
        ?>
        <div class="sync-stats">
            <div class="stat-box">
                <h3>Total Orders Synced</h3>
                <div class="stat-number"><?php echo count($orders); ?></div>
            </div>
            <div class="stat-box">
                <h3>Total Revenue</h3>
                <div class="stat-number"><?php echo wc_price($total_revenue); ?></div>
            </div>
            <div class="stat-box">
                <h3>Last Sync</h3>
                <div class="stat-number" style="font-size: 16px;">
                    <?php 
                    if (!empty($orders)) {
                        echo human_time_diff(strtotime($orders[0]['sync_date']), current_time('timestamp')) . ' ago';
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <h2>Orders Synced - <?php echo $title; ?></h2>
        
        <?php if (empty($orders)): ?>
            <p>No orders synced in this time period.</p>
        <?php else: ?>
            <table class="sync-table">
                <thead>
                    <tr>
                        <th>WC Order #</th>
                        <th>Evosus Order ID</th>
                        <th>Customer</th>
                        <th>Order Date</th>
                        <th>Sync Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <a href="<?php echo admin_url('post.php?post=' . $order['wc_order_id'] . '&action=edit'); ?>" target="_blank">
                                    #<?php echo $order['wc_order_number']; ?>
                                </a>
                            </td>
                            <td><strong><?php echo $order['evosus_order_id']; ?></strong></td>
                            <td><?php echo esc_html($order['customer_name']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['sync_date'])); ?></td>
                            <td><?php echo wc_price($order['order_total']); ?></td>
                            <td>
                                <span class="status-badge status-synced">Synced</span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('post.php?post=' . $order['wc_order_id'] . '&action=edit'); ?>" class="button button-small">
                                    View Order
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Render settings page
     */
    private function render_settings() {
        if (isset($_POST['save_evosus_settings'])) {
            check_admin_referer('evosus_settings_nonce');

            update_option('evosus_company_sn', sanitize_text_field($_POST['company_sn']));
            update_option('evosus_ticket', sanitize_text_field($_POST['ticket']));
            update_option('evosus_auto_sync', isset($_POST['auto_sync']) ? '1' : '0');
            update_option('evosus_distribution_method_id', sanitize_text_field($_POST['distribution_method_id']));

            // New settings
            update_option('evosus_test_mode', isset($_POST['test_mode']) ? '1' : '0');
            update_option('evosus_api_base_url', esc_url_raw($_POST['api_base_url']));
            update_option('evosus_enable_notifications', isset($_POST['enable_notifications']) ? '1' : '0');
            update_option('evosus_notification_email', sanitize_email($_POST['notification_email']));
            update_option('evosus_notify_success', isset($_POST['notify_success']) ? '1' : '0');
            update_option('evosus_enable_webhook', isset($_POST['enable_webhook']) ? '1' : '0');

            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'woocommerce-evosus-sync') . '</p></div>';
        }
        
        $company_sn = get_option('evosus_company_sn', '');
        $ticket = get_option('evosus_ticket', '');
        $auto_sync = get_option('evosus_auto_sync', '0');
        $distribution_method_id = get_option('evosus_distribution_method_id', '1');

        // New settings
        $test_mode = get_option('evosus_test_mode', '0');
        $api_base_url = get_option('evosus_api_base_url', '');
        $enable_notifications = get_option('evosus_enable_notifications', '0');
        $notification_email = get_option('evosus_notification_email', get_option('admin_email'));
        $notify_success = get_option('evosus_notify_success', '0');
        $enable_webhook = get_option('evosus_enable_webhook', '0');
        $webhook_secret = get_option('evosus_webhook_secret', '');
        ?>
        <h2>Evosus Integration Settings</h2>
        
        <form method="post">
            <?php wp_nonce_field('evosus_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="company_sn">Company Serial Number</label>
                    </th>
                    <td>
                        <input type="text" id="company_sn" name="company_sn" value="<?php echo esc_attr($company_sn); ?>" class="regular-text" required>
                        <p class="description">Your Evosus CompanySN provided by Evosus support.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ticket">API Ticket</label>
                    </th>
                    <td>
                        <input type="text" id="ticket" name="ticket" value="<?php echo esc_attr($ticket); ?>" class="regular-text" required>
                        <p class="description">Your Evosus API ticket/token provided by Evosus support.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="distribution_method_id">Distribution Method ID</label>
                    </th>
                    <td>
                        <input type="text" id="distribution_method_id" name="distribution_method_id" value="<?php echo esc_attr($distribution_method_id); ?>" class="small-text" required>
                        <p class="description">The default distribution method for orders (1 = Customer Pickup, etc.)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="auto_sync"><?php _e('Auto-Sync Orders', 'woocommerce-evosus-sync'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="auto_sync" name="auto_sync" value="1" <?php checked($auto_sync, '1'); ?>>
                            <?php _e('Automatically sync orders when they are marked as Processing or Completed', 'woocommerce-evosus-sync'); ?>
                        </label>
                        <p class="description"><strong><?php _e('Note:', 'woocommerce-evosus-sync'); ?></strong> <?php _e('Manual review is recommended. Use the order edit screen to sync individually.', 'woocommerce-evosus-sync'); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php _e('Advanced Settings', 'woocommerce-evosus-sync'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="test_mode"><?php _e('Test Mode', 'woocommerce-evosus-sync'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="test_mode" name="test_mode" value="1" <?php checked($test_mode, '1'); ?>>
                            <?php _e('Enable test mode (simulates API calls without actually making them)', 'woocommerce-evosus-sync'); ?>
                        </label>
                        <p class="description"><?php _e('Useful for development and testing. No actual data will be sent to Evosus.', 'woocommerce-evosus-sync'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="api_base_url"><?php _e('Custom API URL', 'woocommerce-evosus-sync'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="api_base_url" name="api_base_url" value="<?php echo esc_attr($api_base_url); ?>" class="regular-text" placeholder="https://cloud3.evosus.com/api">
                        <p class="description"><?php _e('Leave blank to use default. Only change if using a different Evosus environment.', 'woocommerce-evosus-sync'); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php _e('Email Notifications', 'woocommerce-evosus-sync'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enable_notifications"><?php _e('Enable Notifications', 'woocommerce-evosus-sync'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="enable_notifications" name="enable_notifications" value="1" <?php checked($enable_notifications, '1'); ?>>
                            <?php _e('Send email notifications for sync events', 'woocommerce-evosus-sync'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="notification_email"><?php _e('Notification Email', 'woocommerce-evosus-sync'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="notification_email" name="notification_email" value="<?php echo esc_attr($notification_email); ?>" class="regular-text" placeholder="admin@example.com">
                        <p class="description"><?php _e('Email address for sync notifications. Defaults to site admin email.', 'woocommerce-evosus-sync'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="notify_success"><?php _e('Notify on Success', 'woocommerce-evosus-sync'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="notify_success" name="notify_success" value="1" <?php checked($notify_success, '1'); ?>>
                            <?php _e('Send email notifications for successful syncs (not recommended for high volume)', 'woocommerce-evosus-sync'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <h3><?php _e('Webhooks', 'woocommerce-evosus-sync'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enable_webhook"><?php _e('Enable Webhooks', 'woocommerce-evosus-sync'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="enable_webhook" name="enable_webhook" value="1" <?php checked($enable_webhook, '1'); ?>>
                            <?php _e('Enable incoming webhooks from Evosus for bidirectional sync', 'woocommerce-evosus-sync'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Webhook URL', 'woocommerce-evosus-sync'); ?>
                    </th>
                    <td>
                        <code style="background: #f0f0f0; padding: 5px 10px; display: inline-block; border-radius: 3px;"><?php echo esc_html(Evosus_Webhook::get_webhook_url()); ?></code>
                        <p class="description"><?php _e('Configure this URL in your Evosus webhook settings.', 'woocommerce-evosus-sync'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Webhook Secret', 'woocommerce-evosus-sync'); ?>
                    </th>
                    <td>
                        <input type="text" readonly value="<?php echo esc_attr($webhook_secret); ?>" class="regular-text" onclick="this.select();" style="background: #f0f0f0;">
                        <p class="description"><?php _e('Use this secret key when configuring webhooks in Evosus. Click to copy.', 'woocommerce-evosus-sync'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="save_evosus_settings" class="button button-primary">Save Settings</button>
            </p>
        </form>
        
        <hr>
        
        <h3>Demo Credentials (For Testing)</h3>
        <p>If you don't have credentials yet, you can test with:</p>
        <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px;">
CompanySN: 20101003171313*999
Ticket: a71279ea-1362-45be-91df-d179925a0cb1
        </pre>
        <?php
    }
    
    /**
     * AJAX: Sync single order
     */
    public function ajax_sync_single_order() {
        check_ajax_referer('evosus_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $order_id = intval($_POST['order_id']);
        
        if ($this->integration->is_order_synced($order_id)) {
            wp_send_json_error(['message' => 'Order already synced']);
        }
        
        $result = $this->integration->sync_order_to_evosus($order_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Map order SKU
     */
    public function ajax_map_order_sku() {
        check_ajax_referer('evosus_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $order_id = intval($_POST['order_id']);
        $item_id = intval($_POST['item_id']);
        $new_sku = sanitize_text_field($_POST['new_sku']);
        
        $result = $this->integration->update_order_item_sku($order_id, $item_id, $new_sku);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Approve and sync order
     */
    public function ajax_approve_and_sync_order() {
        check_ajax_referer('evosus_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $order_id = intval($_POST['order_id']);
        
        $result = $this->integration->approve_order_for_sync($order_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}