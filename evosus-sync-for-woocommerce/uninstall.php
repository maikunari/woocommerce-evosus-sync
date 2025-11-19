<?php
/**
 * Uninstall WooCommerce Evosus Sync
 *
 * Cleanup database tables and options when plugin is deleted
 */

// Exit if accessed directly or not in uninstall context
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete plugin options
delete_option('evosus_company_sn');
delete_option('evosus_ticket');
delete_option('evosus_distribution_method_id');
delete_option('evosus_auto_sync');
delete_option('evosus_test_mode');
delete_option('evosus_api_base_url');
delete_option('evosus_enable_notifications');
delete_option('evosus_notification_email');
delete_option('evosus_enable_webhook');
delete_option('evosus_webhook_secret');

// Delete all order meta data created by the plugin
$meta_keys = [
    '_evosus_order_id',
    '_evosus_sync_date',
    '_evosus_synced',
    '_evosus_needs_review',
    '_evosus_review_issues',
    '_evosus_review_date',
    '_evosus_sku_override'
];

foreach ($meta_keys as $meta_key) {
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
        $meta_key
    ));

    // Also clean HPOS meta if enabled
    if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wc_orders_meta'") === $wpdb->prefix . 'wc_orders_meta') {
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = %s",
            $meta_key
        ));
    }
}

// Drop custom database tables
$table_names = [
    $wpdb->prefix . 'evosus_sku_mappings',
    $wpdb->prefix . 'evosus_logs',
    $wpdb->prefix . 'evosus_queue'
];

foreach ($table_names as $table_name) {
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
}

// Clear any scheduled cron jobs
wp_clear_scheduled_hook('evosus_cleanup_logs');
wp_clear_scheduled_hook('evosus_process_queue');
wp_clear_scheduled_hook('evosus_retry_failed_syncs');

// Optionally: Remove any transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_evosus_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_evosus_%'");
