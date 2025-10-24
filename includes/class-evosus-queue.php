<?php
/**
 * Evosus Queue
 * Background processing queue for async sync operations
 */

class Evosus_Queue {

    private static $instance = null;
    private $table_name;
    private $logger;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'evosus_queue';
        $this->logger = Evosus_Logger::get_instance();

        // Schedule cron job to process queue
        add_action('evosus_process_queue', [$this, 'process_queue']);

        if (!wp_next_scheduled('evosus_process_queue')) {
            wp_schedule_event(time(), 'every_minute', 'evosus_process_queue');
        }

        // Add custom cron interval
        add_filter('cron_schedules', [$this, 'add_cron_interval']);
    }

    /**
     * Add custom cron interval
     */
    public function add_cron_interval($schedules) {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display'  => __('Every Minute', 'woocommerce-evosus-sync')
        ];
        return $schedules;
    }

    /**
     * Add job to queue
     */
    public function add_job($order_id, $priority = 10, $scheduled_at = null) {
        global $wpdb;

        if ($scheduled_at === null) {
            $scheduled_at = current_time('mysql');
        }

        $result = $wpdb->insert(
            $this->table_name,
            [
                'order_id' => $order_id,
                'status' => self::STATUS_PENDING,
                'priority' => $priority,
                'scheduled_at' => $scheduled_at,
                'created_at' => current_time('mysql'),
                'attempts' => 0
            ],
            ['%d', '%s', '%d', '%s', '%s', '%d']
        );

        if ($result) {
            $this->logger->log_info("Order #{$order_id} added to sync queue");
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Process queue
     */
    public function process_queue() {
        global $wpdb;

        // Get pending jobs
        $jobs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name}
                WHERE status = %s
                AND scheduled_at <= %s
                ORDER BY priority DESC, created_at ASC
                LIMIT 10",
                self::STATUS_PENDING,
                current_time('mysql')
            )
        );

        foreach ($jobs as $job) {
            $this->process_job($job);
        }

        // Retry failed jobs (up to 3 attempts)
        $failed_jobs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name}
                WHERE status = %s
                AND attempts < 3
                AND scheduled_at <= %s
                ORDER BY created_at ASC
                LIMIT 5",
                self::STATUS_FAILED,
                current_time('mysql')
            )
        );

        foreach ($failed_jobs as $job) {
            $this->retry_job($job);
        }
    }

    /**
     * Process a single job
     */
    private function process_job($job) {
        global $wpdb;

        // Mark as processing
        $wpdb->update(
            $this->table_name,
            [
                'status' => self::STATUS_PROCESSING,
                'started_at' => current_time('mysql'),
                'attempts' => $job->attempts + 1
            ],
            ['id' => $job->id],
            ['%s', '%s', '%d'],
            ['%d']
        );

        // Check if already synced
        if (Evosus_Helpers::get_order_meta($job->order_id, '_evosus_synced', true) === 'yes') {
            $this->complete_job($job->id, 'Already synced');
            return;
        }

        // Get integration instance
        $company_sn = get_option('evosus_company_sn', '');
        $ticket = get_option('evosus_ticket', '');

        if (empty($company_sn) || empty($ticket)) {
            $this->fail_job($job->id, 'Missing API credentials');
            return;
        }

        $integration = new WooCommerce_Evosus_Integration($company_sn, $ticket);

        // Attempt sync
        $result = $integration->sync_order_to_evosus($job->order_id);

        if ($result['success']) {
            $this->complete_job($job->id, 'Synced successfully', $result);
        } else {
            $this->fail_job($job->id, $result['message'], $result);
        }
    }

    /**
     * Retry a failed job
     */
    private function retry_job($job) {
        global $wpdb;

        // Reset to pending with exponential backoff
        $delay_minutes = pow(2, $job->attempts) * 5; // 5, 10, 20 minutes
        $scheduled_at = date('Y-m-d H:i:s', strtotime("+{$delay_minutes} minutes"));

        $wpdb->update(
            $this->table_name,
            [
                'status' => self::STATUS_PENDING,
                'scheduled_at' => $scheduled_at
            ],
            ['id' => $job->id],
            ['%s', '%s'],
            ['%d']
        );

        $this->logger->log_info("Queue job #{$job->id} scheduled for retry at {$scheduled_at}");
    }

    /**
     * Mark job as completed
     */
    private function complete_job($job_id, $message = '', $result = []) {
        global $wpdb;

        $wpdb->update(
            $this->table_name,
            [
                'status' => self::STATUS_COMPLETED,
                'completed_at' => current_time('mysql'),
                'result' => maybe_serialize($result),
                'error_message' => $message
            ],
            ['id' => $job_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        $this->logger->log_info("Queue job #{$job_id} completed: {$message}");
    }

    /**
     * Mark job as failed
     */
    private function fail_job($job_id, $error_message, $result = []) {
        global $wpdb;

        $wpdb->update(
            $this->table_name,
            [
                'status' => self::STATUS_FAILED,
                'completed_at' => current_time('mysql'),
                'result' => maybe_serialize($result),
                'error_message' => $error_message
            ],
            ['id' => $job_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        $this->logger->log_error("Queue job #{$job_id} failed: {$error_message}");
    }

    /**
     * Get queue stats
     */
    public function get_stats() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT status, COUNT(*) as count
            FROM {$this->table_name}
            GROUP BY status"
        );
    }

    /**
     * Get pending jobs count
     */
    public function get_pending_count() {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s",
                self::STATUS_PENDING
            )
        );
    }

    /**
     * Get jobs by status
     */
    public function get_jobs_by_status($status, $limit = 50) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name}
                WHERE status = %s
                ORDER BY created_at DESC
                LIMIT %d",
                $status,
                $limit
            )
        );
    }

    /**
     * Clear completed jobs older than X days
     */
    public function cleanup_old_jobs($days = 30) {
        global $wpdb;

        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name}
                WHERE status = %s
                AND completed_at < %s",
                self::STATUS_COMPLETED,
                $date
            )
        );
    }

    /**
     * Cancel a job
     */
    public function cancel_job($job_id) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            ['id' => $job_id],
            ['%d']
        );
    }

    /**
     * Get job by order ID
     */
    public function get_job_by_order($order_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name}
                WHERE order_id = %d
                ORDER BY created_at DESC
                LIMIT 1",
                $order_id
            )
        );
    }
}
