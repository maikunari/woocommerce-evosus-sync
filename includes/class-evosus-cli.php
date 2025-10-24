<?php
/**
 * Evosus WP-CLI Commands
 *
 * Usage:
 * wp evosus sync <order_id>         - Sync a single order
 * wp evosus sync-all                - Sync all pending orders
 * wp evosus validate <order_id>     - Validate an order
 * wp evosus queue-status            - View queue status
 * wp evosus process-queue           - Manually process queue
 * wp evosus logs --severity=error   - View logs
 * wp evosus cleanup-logs --days=90  - Cleanup old logs
 * wp evosus test-connection         - Test API connection
 * wp evosus test-email              - Send test notification email
 */

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

class Evosus_CLI {

    private $integration;
    private $logger;
    private $queue;

    public function __construct() {
        $company_sn = get_option('evosus_company_sn', '');
        $ticket = get_option('evosus_ticket', '');

        if (!empty($company_sn) && !empty($ticket)) {
            $this->integration = new WooCommerce_Evosus_Integration($company_sn, $ticket);
        }

        $this->logger = Evosus_Logger::get_instance();
        $this->queue = Evosus_Queue::get_instance();
    }

    /**
     * Sync a single order to Evosus
     *
     * ## OPTIONS
     *
     * <order_id>
     * : The order ID to sync
     *
     * [--skip-validation]
     * : Skip validation before syncing
     *
     * ## EXAMPLES
     *
     *     wp evosus sync 123
     *     wp evosus sync 123 --skip-validation
     */
    public function sync($args, $assoc_args) {
        if (!$this->integration) {
            WP_CLI::error('Evosus API credentials not configured');
        }

        $order_id = absint($args[0]);
        $skip_validation = isset($assoc_args['skip-validation']);

        $order = wc_get_order($order_id);

        if (!$order) {
            WP_CLI::error("Order #{$order_id} not found");
        }

        WP_CLI::log("Syncing order #{$order_id}...");

        $result = $this->integration->sync_order_to_evosus($order_id, $skip_validation);

        if ($result['success']) {
            WP_CLI::success("Order synced successfully! Evosus Order ID: {$result['evosus_order_id']}");
        } else if (isset($result['needs_review']) && $result['needs_review']) {
            WP_CLI::warning("Order needs review. Issues found:");
            foreach ($result['issues'] as $issue) {
                WP_CLI::log("  - {$issue['message']}");
            }
        } else {
            WP_CLI::error("Sync failed: {$result['message']}");
        }
    }

    /**
     * Sync all pending orders
     *
     * ## OPTIONS
     *
     * [--status=<status>]
     * : Order status to filter by (default: processing)
     *
     * [--limit=<number>]
     * : Maximum number of orders to sync (default: 50)
     *
     * [--use-queue]
     * : Add orders to queue instead of syncing immediately
     *
     * ## EXAMPLES
     *
     *     wp evosus sync-all
     *     wp evosus sync-all --status=completed --limit=100
     *     wp evosus sync-all --use-queue
     */
    public function sync_all($args, $assoc_args) {
        if (!$this->integration) {
            WP_CLI::error('Evosus API credentials not configured');
        }

        $status = isset($assoc_args['status']) ? $assoc_args['status'] : 'processing';
        $limit = isset($assoc_args['limit']) ? absint($assoc_args['limit']) : 50;
        $use_queue = isset($assoc_args['use-queue']);

        $orders = wc_get_orders([
            'status' => $status,
            'limit' => $limit,
            'meta_query' => [
                [
                    'key' => '_evosus_synced',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);

        $total = count($orders);

        if ($total === 0) {
            WP_CLI::log('No orders found to sync');
            return;
        }

        WP_CLI::log("Found {$total} orders to sync");

        $progress = \WP_CLI\Utils\make_progress_bar('Syncing orders', $total);

        $success_count = 0;
        $failed_count = 0;
        $review_count = 0;

        foreach ($orders as $order) {
            if ($use_queue) {
                $this->queue->add_job($order->get_id());
                $success_count++;
            } else {
                $result = $this->integration->sync_order_to_evosus($order->get_id());

                if ($result['success']) {
                    $success_count++;
                } else if (isset($result['needs_review'])) {
                    $review_count++;
                } else {
                    $failed_count++;
                }
            }

            $progress->tick();
        }

        $progress->finish();

        if ($use_queue) {
            WP_CLI::success("Added {$success_count} orders to queue");
        } else {
            WP_CLI::success("Sync complete:");
            WP_CLI::log("  - Success: {$success_count}");
            WP_CLI::log("  - Needs Review: {$review_count}");
            WP_CLI::log("  - Failed: {$failed_count}");
        }
    }

    /**
     * Validate an order
     *
     * ## OPTIONS
     *
     * <order_id>
     * : The order ID to validate
     *
     * ## EXAMPLES
     *
     *     wp evosus validate 123
     */
    public function validate($args, $assoc_args) {
        if (!$this->integration) {
            WP_CLI::error('Evosus API credentials not configured');
        }

        $order_id = absint($args[0]);
        $order = wc_get_order($order_id);

        if (!$order) {
            WP_CLI::error("Order #{$order_id} not found");
        }

        WP_CLI::log("Validating order #{$order_id}...");

        $validation = $this->integration->validate_order($order);

        if ($validation['valid']) {
            WP_CLI::success("Order validated successfully - ready to sync");
        } else {
            WP_CLI::warning("Validation issues found:");
            foreach ($validation['issues'] as $issue) {
                $icon = $issue['severity'] === 'error' ? '✗' : '⚠';
                WP_CLI::log("  {$icon} [{$issue['severity']}] {$issue['message']}");
            }
        }
    }

    /**
     * View queue status
     *
     * ## EXAMPLES
     *
     *     wp evosus queue-status
     */
    public function queue_status($args, $assoc_args) {
        $stats = $this->queue->get_stats();

        WP_CLI::log("Queue Status:");
        WP_CLI::log("-------------------");

        foreach ($stats as $stat) {
            WP_CLI::log(ucfirst($stat->status) . ": " . $stat->count);
        }
    }

    /**
     * Process queue manually
     *
     * ## EXAMPLES
     *
     *     wp evosus process-queue
     */
    public function process_queue($args, $assoc_args) {
        WP_CLI::log("Processing queue...");
        $this->queue->process_queue();
        WP_CLI::success("Queue processed");
    }

    /**
     * View logs
     *
     * ## OPTIONS
     *
     * [--severity=<severity>]
     * : Filter by severity (info|warning|error)
     *
     * [--limit=<number>]
     * : Number of logs to display (default: 20)
     *
     * [--format=<format>]
     * : Output format (table|json|csv)
     *
     * ## EXAMPLES
     *
     *     wp evosus logs
     *     wp evosus logs --severity=error --limit=50
     *     wp evosus logs --format=json
     */
    public function logs($args, $assoc_args) {
        $severity = isset($assoc_args['severity']) ? $assoc_args['severity'] : null;
        $limit = isset($assoc_args['limit']) ? absint($assoc_args['limit']) : 20;
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';

        $logs = $this->logger->get_logs([
            'severity' => $severity,
            'limit' => $limit
        ]);

        if (empty($logs)) {
            WP_CLI::log('No logs found');
            return;
        }

        $output = [];
        foreach ($logs as $log) {
            $output[] = [
                'ID' => $log->id,
                'Type' => $log->log_type,
                'Severity' => $log->severity,
                'Message' => substr($log->message, 0, 50),
                'Order ID' => $log->order_id ?: '-',
                'Created' => $log->created_at
            ];
        }

        WP_CLI\Utils\format_items($format, $output, ['ID', 'Type', 'Severity', 'Message', 'Order ID', 'Created']);
    }

    /**
     * Cleanup old logs
     *
     * ## OPTIONS
     *
     * [--days=<number>]
     * : Delete logs older than X days (default: 90)
     *
     * [--yes]
     * : Skip confirmation
     *
     * ## EXAMPLES
     *
     *     wp evosus cleanup-logs
     *     wp evosus cleanup-logs --days=30 --yes
     */
    public function cleanup_logs($args, $assoc_args) {
        $days = isset($assoc_args['days']) ? absint($assoc_args['days']) : 90;

        if (!isset($assoc_args['yes'])) {
            WP_CLI::confirm("Delete logs older than {$days} days?");
        }

        $deleted = $this->logger->cleanup_old_logs($days);

        WP_CLI::success("Deleted {$deleted} log entries");
    }

    /**
     * Test API connection
     *
     * ## EXAMPLES
     *
     *     wp evosus test-connection
     */
    public function test_connection($args, $assoc_args) {
        if (!$this->integration) {
            WP_CLI::error('Evosus API credentials not configured');
        }

        WP_CLI::log("Testing API connection...");

        // Try to search for a customer (simple test)
        $result = $this->integration->search_customer_by_email('test@example.com');

        if ($result !== false) {
            WP_CLI::success("API connection successful!");
        } else {
            WP_CLI::error("API connection failed - check credentials and logs");
        }
    }

    /**
     * Send test email
     *
     * ## EXAMPLES
     *
     *     wp evosus test-email
     */
    public function test_email($args, $assoc_args) {
        $notifications = Evosus_Notifications::get_instance();

        WP_CLI::log("Sending test email...");

        $result = $notifications->send_test_email();

        if ($result['success']) {
            WP_CLI::success("Test email sent successfully");
        } else {
            WP_CLI::error("Failed to send test email: {$result['message']}");
        }
    }

    /**
     * View SKU mappings
     *
     * ## OPTIONS
     *
     * [--search=<term>]
     * : Search term
     *
     * [--limit=<number>]
     * : Number of mappings to display (default: 50)
     *
     * ## EXAMPLES
     *
     *     wp evosus sku-mappings
     *     wp evosus sku-mappings --search=ABC
     */
    public function sku_mappings($args, $assoc_args) {
        $mapper = Evosus_SKU_Mapper::get_instance();
        $search = isset($assoc_args['search']) ? $assoc_args['search'] : null;
        $limit = isset($assoc_args['limit']) ? absint($assoc_args['limit']) : 50;

        if ($search) {
            $mappings = $mapper->search_mappings($search);
        } else {
            $mappings = $mapper->get_all_mappings($limit, 0);
        }

        if (empty($mappings)) {
            WP_CLI::log('No SKU mappings found');
            return;
        }

        $output = [];
        foreach ($mappings as $mapping) {
            $output[] = [
                'ID' => $mapping->id,
                'WC SKU' => $mapping->wc_sku,
                'Evosus SKU' => $mapping->evosus_sku,
                'Product ID' => $mapping->product_id ?: '-',
                'Created' => $mapping->created_at
            ];
        }

        WP_CLI\Utils\format_items('table', $output, ['ID', 'WC SKU', 'Evosus SKU', 'Product ID', 'Created']);
    }

    /**
     * Get plugin stats
     *
     * ## EXAMPLES
     *
     *     wp evosus stats
     */
    public function stats($args, $assoc_args) {
        WP_CLI::log("Evosus Sync Statistics");
        WP_CLI::log("======================");

        // Synced orders today
        $integration = $this->integration ?: new WooCommerce_Evosus_Integration(
            get_option('evosus_company_sn'),
            get_option('evosus_ticket')
        );

        $today = $integration->get_orders_synced_today();
        $this_week = $integration->get_orders_synced_this_week();

        WP_CLI::log("Orders synced today: " . count($today));
        WP_CLI::log("Orders synced this week: " . count($this_week));

        // Queue stats
        $queue_stats = $this->queue->get_stats();
        WP_CLI::log("\nQueue Status:");
        foreach ($queue_stats as $stat) {
            WP_CLI::log("  " . ucfirst($stat->status) . ": " . $stat->count);
        }

        // Log stats
        $log_stats = $this->logger->get_stats(7);
        WP_CLI::log("\nLogs (last 7 days):");
        foreach ($log_stats as $stat) {
            WP_CLI::log("  " . ucfirst($stat->log_type) . " ({$stat->severity}): " . $stat->count);
        }

        // SKU mappings
        $mapper = Evosus_SKU_Mapper::get_instance();
        $mapping_count = $mapper->count_mappings();
        WP_CLI::log("\nTotal SKU mappings: {$mapping_count}");
    }
}

WP_CLI::add_command('evosus', 'Evosus_CLI');
