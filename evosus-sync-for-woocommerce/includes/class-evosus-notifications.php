<?php
/**
 * Evosus Notifications
 * Email notification system for sync events
 */

class Evosus_Notifications {

    private static $instance = null;

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
        // Hook into sync events
        add_action('evosus_sync_failed', [$this, 'notify_sync_failed'], 10, 2);
        add_action('evosus_order_needs_review', [$this, 'notify_order_needs_review'], 10, 2);
        add_action('evosus_sync_success', [$this, 'notify_sync_success'], 10, 2);
    }

    /**
     * Check if notifications are enabled
     */
    private function are_notifications_enabled() {
        return get_option('evosus_enable_notifications', '0') === '1';
    }

    /**
     * Get notification email address
     */
    private function get_notification_email() {
        $email = get_option('evosus_notification_email', '');
        if (empty($email)) {
            $email = get_option('admin_email');
        }
        return $email;
    }

    /**
     * Notify when sync fails
     */
    public function notify_sync_failed($order_id, $error_message) {
        if (!$this->are_notifications_enabled()) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $to = $this->get_notification_email();
        $subject = sprintf(
            __('[%s] Evosus Sync Failed - Order #%s', 'woocommerce-evosus-sync'),
            get_bloginfo('name'),
            $order->get_order_number()
        );

        $message = $this->get_email_template([
            'heading' => __('Sync Failed', 'woocommerce-evosus-sync'),
            'content' => sprintf(
                __('Order #%s failed to sync to Evosus.', 'woocommerce-evosus-sync'),
                $order->get_order_number()
            ),
            'details' => [
                __('Order ID', 'woocommerce-evosus-sync') => $order->get_id(),
                __('Order Number', 'woocommerce-evosus-sync') => $order->get_order_number(),
                __('Customer', 'woocommerce-evosus-sync') => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                __('Order Total', 'woocommerce-evosus-sync') => $order->get_formatted_order_total(),
                __('Error Message', 'woocommerce-evosus-sync') => $error_message,
                __('Order Link', 'woocommerce-evosus-sync') => admin_url('post.php?post=' . $order_id . '&action=edit')
            ]
        ]);

        wp_mail($to, $subject, $message, $this->get_email_headers());
    }

    /**
     * Notify when order needs review
     */
    public function notify_order_needs_review($order_id, $issues) {
        if (!$this->are_notifications_enabled()) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $to = $this->get_notification_email();
        $subject = sprintf(
            __('[%s] Order Needs Review - Order #%s', 'woocommerce-evosus-sync'),
            get_bloginfo('name'),
            $order->get_order_number()
        );

        $issues_list = '';
        foreach ($issues as $issue) {
            $issues_list .= '- ' . $issue['message'] . "\n";
        }

        $message = $this->get_email_template([
            'heading' => __('Order Needs Review', 'woocommerce-evosus-sync'),
            'content' => sprintf(
                __('Order #%s requires manual review before syncing to Evosus.', 'woocommerce-evosus-sync'),
                $order->get_order_number()
            ),
            'details' => [
                __('Order ID', 'woocommerce-evosus-sync') => $order->get_id(),
                __('Order Number', 'woocommerce-evosus-sync') => $order->get_order_number(),
                __('Customer', 'woocommerce-evosus-sync') => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                __('Order Total', 'woocommerce-evosus-sync') => $order->get_formatted_order_total(),
                __('Issues Found', 'woocommerce-evosus-sync') => count($issues),
                __('Order Link', 'woocommerce-evosus-sync') => admin_url('post.php?post=' . $order_id . '&action=edit')
            ],
            'extra_content' => __('Issues:', 'woocommerce-evosus-sync') . "\n\n" . $issues_list
        ]);

        wp_mail($to, $subject, $message, $this->get_email_headers());
    }

    /**
     * Notify when sync succeeds (optional, usually disabled)
     */
    public function notify_sync_success($order_id, $evosus_order_id) {
        if (!$this->are_notifications_enabled()) {
            return;
        }

        // Only send success emails if explicitly enabled
        if (get_option('evosus_notify_success', '0') !== '1') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $to = $this->get_notification_email();
        $subject = sprintf(
            __('[%s] Order Synced - Order #%s', 'woocommerce-evosus-sync'),
            get_bloginfo('name'),
            $order->get_order_number()
        );

        $message = $this->get_email_template([
            'heading' => __('Sync Successful', 'woocommerce-evosus-sync'),
            'content' => sprintf(
                __('Order #%s has been successfully synced to Evosus.', 'woocommerce-evosus-sync'),
                $order->get_order_number()
            ),
            'details' => [
                __('Order ID', 'woocommerce-evosus-sync') => $order->get_id(),
                __('Order Number', 'woocommerce-evosus-sync') => $order->get_order_number(),
                __('Evosus Order ID', 'woocommerce-evosus-sync') => $evosus_order_id,
                __('Customer', 'woocommerce-evosus-sync') => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                __('Order Total', 'woocommerce-evosus-sync') => $order->get_formatted_order_total(),
                __('Order Link', 'woocommerce-evosus-sync') => admin_url('post.php?post=' . $order_id . '&action=edit')
            ]
        ]);

        wp_mail($to, $subject, $message, $this->get_email_headers());
    }

    /**
     * Send daily summary email
     */
    public function send_daily_summary() {
        if (!$this->are_notifications_enabled()) {
            return;
        }

        $logger = Evosus_Logger::get_instance();
        $stats = $logger->get_stats(1); // Last 24 hours

        $queue = Evosus_Queue::get_instance();
        $queue_stats = $queue->get_stats();

        $to = $this->get_notification_email();
        $subject = sprintf(
            __('[%s] Evosus Sync Daily Summary', 'woocommerce-evosus-sync'),
            get_bloginfo('name')
        );

        $stats_details = [];
        foreach ($stats as $stat) {
            $key = ucfirst($stat->log_type) . ' (' . ucfirst($stat->severity) . ')';
            $stats_details[$key] = $stat->count;
        }

        $queue_details = [];
        foreach ($queue_stats as $stat) {
            $queue_details[ucfirst($stat->status)] = $stat->count;
        }

        $message = $this->get_email_template([
            'heading' => __('Daily Sync Summary', 'woocommerce-evosus-sync'),
            'content' => __('Here is your daily summary of Evosus sync activity.', 'woocommerce-evosus-sync'),
            'details' => array_merge(
                ['--- ' . __('Activity', 'woocommerce-evosus-sync') . ' ---' => ''],
                $stats_details,
                ['--- ' . __('Queue Status', 'woocommerce-evosus-sync') . ' ---' => ''],
                $queue_details
            )
        ]);

        wp_mail($to, $subject, $message, $this->get_email_headers());
    }

    /**
     * Get email headers
     */
    private function get_email_headers() {
        return [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
    }

    /**
     * Get email template
     */
    private function get_email_template($args) {
        $defaults = [
            'heading' => '',
            'content' => '',
            'details' => [],
            'extra_content' => ''
        ];

        $args = wp_parse_args($args, $defaults);

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($args['heading']); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background: #2271b1;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    background: #f9f9f9;
                    padding: 20px;
                    border: 1px solid #ddd;
                }
                .details {
                    background: white;
                    padding: 15px;
                    margin: 15px 0;
                    border-left: 4px solid #2271b1;
                }
                .details table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .details td {
                    padding: 8px;
                    border-bottom: 1px solid #eee;
                }
                .details td:first-child {
                    font-weight: bold;
                    width: 40%;
                }
                .footer {
                    text-align: center;
                    padding: 20px;
                    color: #666;
                    font-size: 12px;
                }
                .button {
                    display: inline-block;
                    padding: 10px 20px;
                    background: #2271b1;
                    color: white;
                    text-decoration: none;
                    border-radius: 3px;
                    margin: 10px 0;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo esc_html($args['heading']); ?></h1>
            </div>
            <div class="content">
                <p><?php echo esc_html($args['content']); ?></p>

                <?php if (!empty($args['details'])): ?>
                    <div class="details">
                        <table>
                            <?php foreach ($args['details'] as $key => $value): ?>
                                <tr>
                                    <td><?php echo esc_html($key); ?></td>
                                    <td><?php echo is_array($value) ? esc_html(wp_json_encode($value)) : esc_html($value); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (!empty($args['extra_content'])): ?>
                    <div style="margin: 15px 0;">
                        <?php echo nl2br(esc_html($args['extra_content'])); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="footer">
                <p>
                    <?php echo esc_html(get_bloginfo('name')); ?><br>
                    <?php _e('This is an automated message from WooCommerce Evosus Sync', 'woocommerce-evosus-sync'); ?>
                </p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Send test email
     */
    public function send_test_email() {
        if (!$this->are_notifications_enabled()) {
            return [
                'success' => false,
                'message' => __('Notifications are not enabled', 'woocommerce-evosus-sync')
            ];
        }

        $to = $this->get_notification_email();
        $subject = sprintf(
            __('[%s] Evosus Sync Test Email', 'woocommerce-evosus-sync'),
            get_bloginfo('name')
        );

        $message = $this->get_email_template([
            'heading' => __('Test Email', 'woocommerce-evosus-sync'),
            'content' => __('This is a test email from WooCommerce Evosus Sync. If you received this, your notifications are working correctly.', 'woocommerce-evosus-sync'),
            'details' => [
                __('Sent At', 'woocommerce-evosus-sync') => current_time('mysql'),
                __('Sent To', 'woocommerce-evosus-sync') => $to,
                __('Site URL', 'woocommerce-evosus-sync') => get_bloginfo('url')
            ]
        ]);

        $result = wp_mail($to, $subject, $message, $this->get_email_headers());

        return [
            'success' => $result,
            'message' => $result
                ? __('Test email sent successfully', 'woocommerce-evosus-sync')
                : __('Failed to send test email', 'woocommerce-evosus-sync')
        ];
    }
}
