<?php
/**
 * Plugin Name: WooCommerce Evosus Sync
 * Plugin URI: https://yourwebsite.com/woocommerce-evosus-sync
 * Description: Sync WooCommerce orders and customers to Evosus Business Management Software
 * Version: 2.0.0
 * Author: maikunari
 * Author URI: https://sonicpixel.jp
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woocommerce-evosus-sync
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_EVOSUS_VERSION', '2.0.0');
define('WC_EVOSUS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_EVOSUS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_EVOSUS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'wc_evosus_woocommerce_missing_notice');
    return;
}

function wc_evosus_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('WooCommerce Evosus Sync requires WooCommerce to be installed and active.', 'woocommerce-evosus-sync'); ?></p>
    </div>
    <?php
}

/**
 * Main Plugin Class
 */
class WC_Evosus_Sync_Plugin {
    
    private static $instance = null;
    public $integration;
    public $admin;
    public $order_metabox;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core helper classes
        require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-logger.php';
        require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-helpers.php';
        require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-sku-mapper.php';

        // Main integration classes
        require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-wc-evosus-integration.php';
        require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-order-metabox.php';
        require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-sync-admin.php';

        // Advanced features
        require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-queue.php';
        require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-webhook.php';
        require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-notifications.php';

        // WP-CLI commands
        if (defined('WP_CLI') && WP_CLI) {
            require_once WC_EVOSUS_PLUGIN_DIR . 'includes/class-evosus-cli.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', [$this, 'init']);
        add_action('admin_init', [$this, 'check_requirements']);
        
        // Plugin activation/deactivation
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Auto-sync on order status change (if enabled)
        add_action('woocommerce_order_status_changed', [$this, 'auto_sync_order'], 10, 4);
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . WC_EVOSUS_PLUGIN_BASENAME, [$this, 'add_action_links']);
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('woocommerce-evosus-sync', false, dirname(WC_EVOSUS_PLUGIN_BASENAME) . '/languages');

        // Initialize core helper classes (always load)
        Evosus_Logger::get_instance();
        Evosus_Queue::get_instance();
        Evosus_Webhook::get_instance();
        Evosus_Notifications::get_instance();
        Evosus_SKU_Mapper::get_instance();

        // Initialize classes
        $company_sn = get_option('evosus_company_sn', '');
        $ticket = get_option('evosus_ticket', '');

        if (!empty($company_sn) && !empty($ticket)) {
            $this->integration = new WooCommerce_Evosus_Integration($company_sn, $ticket);
            $this->order_metabox = new Evosus_Order_Metabox($this->integration);
            $this->admin = new Evosus_Sync_Admin($this->integration);
        } else {
            // Show setup notice
            add_action('admin_notices', [$this, 'setup_notice']);
        }
    }
    
    /**
     * Check plugin requirements
     */
    public function check_requirements() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(WC_EVOSUS_PLUGIN_BASENAME);
            wp_die(__('WooCommerce Evosus Sync requires PHP 7.4 or higher.', 'woocommerce-evosus-sync'));
        }
        
        // Check WooCommerce version
        if (defined('WC_VERSION') && version_compare(WC_VERSION, '5.0', '<')) {
            deactivate_plugins(WC_EVOSUS_PLUGIN_BASENAME);
            wp_die(__('WooCommerce Evosus Sync requires WooCommerce 5.0 or higher.', 'woocommerce-evosus-sync'));
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create custom database tables if needed
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // SKU mapping table
        $table_name = $wpdb->prefix . 'evosus_sku_mappings';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wc_sku varchar(200) NOT NULL,
            evosus_sku varchar(200) NOT NULL,
            product_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY wc_sku (wc_sku),
            KEY product_id (product_id)
        ) $charset_collate;";
        dbDelta($sql);

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

        // Set default options
        add_option('evosus_distribution_method_id', '1');
        add_option('evosus_auto_sync', '0');
        add_option('evosus_test_mode', '0');
        add_option('evosus_api_base_url', '');
        add_option('evosus_enable_notifications', '0');
        add_option('evosus_notification_email', get_option('admin_email'));
        add_option('evosus_notify_success', '0');
        add_option('evosus_enable_webhook', '0');
        add_option('evosus_webhook_secret', wp_generate_password(32, false));

        // Flush rewrite rules (for REST API endpoints)
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear all scheduled cron jobs
        wp_clear_scheduled_hook('evosus_process_queue');
        wp_clear_scheduled_hook('evosus_cleanup_logs');
        wp_clear_scheduled_hook('evosus_retry_failed_syncs');
        wp_clear_scheduled_hook('evosus_daily_summary');

        // Flush rewrite rules for REST API endpoints
        flush_rewrite_rules();
    }
    
    /**
     * Auto-sync order when status changes
     */
    public function auto_sync_order($order_id, $old_status, $new_status, $order) {
        // Only run if auto-sync is enabled
        if (get_option('evosus_auto_sync', '0') !== '1') {
            return;
        }
        
        // Only sync on specific status changes
        $sync_statuses = ['processing', 'completed'];
        
        if (in_array($new_status, $sync_statuses) && !$this->integration->is_order_synced($order_id)) {
            // Run sync
            $result = $this->integration->sync_order_to_evosus($order_id);
            
            // Add order note
            if ($result['success']) {
                $order->add_order_note('✅ Automatically synced to Evosus. Order ID: ' . $result['evosus_order_id']);
            } elseif (isset($result['needs_review']) && $result['needs_review']) {
                $order->add_order_note('⚠️ Order flagged for Evosus review due to validation issues.');
            } else {
                $order->add_order_note('❌ Failed to sync to Evosus: ' . $result['message']);
            }
        }
    }
    
    /**
     * Setup notice
     */
    public function setup_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <?php _e('WooCommerce Evosus Sync is almost ready!', 'woocommerce-evosus-sync'); ?>
                <a href="<?php echo admin_url('admin.php?page=evosus-sync&tab=settings'); ?>">
                    <?php _e('Configure your API settings', 'woocommerce-evosus-sync'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Add settings link to plugins page
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=evosus-sync') . '">' . __('Dashboard', 'woocommerce-evosus-sync') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

/**
 * Initialize the plugin
 */
function wc_evosus_sync() {
    return WC_Evosus_Sync_Plugin::get_instance();
}

// Start the plugin
wc_evosus_sync();