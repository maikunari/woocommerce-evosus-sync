<?php
/**
 * Evosus Webhook Handler
 * Handles incoming webhooks from Evosus for bidirectional sync
 */

class Evosus_Webhook {

    private static $instance = null;
    private $logger;

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
        $this->logger = Evosus_Logger::get_instance();

        // Register webhook endpoint
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
    }

    /**
     * Register REST API endpoint for webhooks
     */
    public function register_webhook_endpoint() {
        register_rest_route('evosus/v1', '/webhook', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => [$this, 'validate_webhook_request']
        ]);
    }

    /**
     * Validate webhook request
     */
    public function validate_webhook_request($request) {
        // Check if webhooks are enabled
        if (get_option('evosus_enable_webhook', '0') !== '1') {
            return new WP_Error(
                'webhooks_disabled',
                __('Webhooks are not enabled', 'woocommerce-evosus-sync'),
                ['status' => 403]
            );
        }

        // Verify signature/secret
        $webhook_secret = get_option('evosus_webhook_secret', '');
        $provided_secret = $request->get_header('X-Evosus-Secret');

        if (empty($webhook_secret)) {
            $this->logger->log_warning('Webhook secret not configured');
            return true; // Allow if not configured
        }

        if ($provided_secret !== $webhook_secret) {
            $this->logger->log_error('Invalid webhook secret provided');
            return new WP_Error(
                'invalid_secret',
                __('Invalid webhook secret', 'woocommerce-evosus-sync'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Handle incoming webhook
     */
    public function handle_webhook($request) {
        $data = $request->get_json_params();

        if (empty($data)) {
            $this->logger->log_error('Empty webhook data received');
            return new WP_Error(
                'empty_data',
                __('Empty webhook data', 'woocommerce-evosus-sync'),
                ['status' => 400]
            );
        }

        $this->logger->log_info('Webhook received', $data);

        // Determine webhook type
        $event_type = isset($data['event_type']) ? $data['event_type'] : 'unknown';

        switch ($event_type) {
            case 'order.updated':
                $result = $this->handle_order_update($data);
                break;

            case 'order.status_changed':
                $result = $this->handle_order_status_change($data);
                break;

            case 'inventory.updated':
                $result = $this->handle_inventory_update($data);
                break;

            case 'customer.updated':
                $result = $this->handle_customer_update($data);
                break;

            default:
                $this->logger->log_warning("Unknown webhook event type: {$event_type}", $data);
                $result = [
                    'success' => false,
                    'message' => 'Unknown event type'
                ];
        }

        if ($result['success']) {
            return new WP_REST_Response([
                'success' => true,
                'message' => $result['message']
            ], 200);
        } else {
            return new WP_Error(
                'webhook_processing_failed',
                $result['message'],
                ['status' => 500]
            );
        }
    }

    /**
     * Handle order update webhook
     */
    private function handle_order_update($data) {
        if (!isset($data['evosus_order_id'])) {
            return [
                'success' => false,
                'message' => 'Missing evosus_order_id'
            ];
        }

        $evosus_order_id = $data['evosus_order_id'];

        // Find WooCommerce order by Evosus order ID
        $wc_order_id = $this->find_order_by_evosus_id($evosus_order_id);

        if (!$wc_order_id) {
            return [
                'success' => false,
                'message' => "Order not found for Evosus ID: {$evosus_order_id}"
            ];
        }

        $order = wc_get_order($wc_order_id);

        if (!$order) {
            return [
                'success' => false,
                'message' => 'Invalid order'
            ];
        }

        // Update order note
        $order->add_order_note(
            sprintf(
                __('Evosus webhook: Order updated. Data: %s', 'woocommerce-evosus-sync'),
                wp_json_encode($data)
            )
        );

        $this->logger->log_info("Order #{$wc_order_id} updated via webhook", $data);

        return [
            'success' => true,
            'message' => 'Order updated successfully'
        ];
    }

    /**
     * Handle order status change webhook
     */
    private function handle_order_status_change($data) {
        if (!isset($data['evosus_order_id']) || !isset($data['new_status'])) {
            return [
                'success' => false,
                'message' => 'Missing required fields'
            ];
        }

        $evosus_order_id = $data['evosus_order_id'];
        $new_status = $data['new_status'];

        // Find WooCommerce order
        $wc_order_id = $this->find_order_by_evosus_id($evosus_order_id);

        if (!$wc_order_id) {
            return [
                'success' => false,
                'message' => "Order not found for Evosus ID: {$evosus_order_id}"
            ];
        }

        $order = wc_get_order($wc_order_id);

        if (!$order) {
            return [
                'success' => false,
                'message' => 'Invalid order'
            ];
        }

        // Map Evosus status to WooCommerce status
        $wc_status = $this->map_evosus_status_to_wc($new_status);

        if ($wc_status) {
            $order->update_status($wc_status, sprintf(
                __('Status updated via Evosus webhook: %s', 'woocommerce-evosus-sync'),
                $new_status
            ));

            $this->logger->log_info(
                "Order #{$wc_order_id} status changed to {$wc_status} via webhook",
                $data
            );

            return [
                'success' => true,
                'message' => 'Order status updated successfully'
            ];
        }

        return [
            'success' => false,
            'message' => 'Could not map Evosus status to WooCommerce status'
        ];
    }

    /**
     * Handle inventory update webhook
     */
    private function handle_inventory_update($data) {
        if (!isset($data['item_code']) || !isset($data['quantity_available'])) {
            return [
                'success' => false,
                'message' => 'Missing required fields'
            ];
        }

        $sku = $data['item_code'];
        $quantity = intval($data['quantity_available']);

        // Find product by SKU
        $product_id = wc_get_product_id_by_sku($sku);

        if (!$product_id) {
            // Check SKU mappings
            $mapper = Evosus_SKU_Mapper::get_instance();
            $mapping = $mapper->get_mapping($sku);

            if ($mapping) {
                $product_id = $mapping->product_id;
            }
        }

        if (!$product_id) {
            return [
                'success' => false,
                'message' => "Product not found for SKU: {$sku}"
            ];
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            return [
                'success' => false,
                'message' => 'Invalid product'
            ];
        }

        // Update stock quantity
        $product->set_stock_quantity($quantity);
        $product->set_stock_status($quantity > 0 ? 'instock' : 'outofstock');
        $product->save();

        $this->logger->log_info(
            "Product #{$product_id} stock updated to {$quantity} via webhook",
            $data
        );

        return [
            'success' => true,
            'message' => 'Inventory updated successfully'
        ];
    }

    /**
     * Handle customer update webhook
     */
    private function handle_customer_update($data) {
        // This would update customer data in WooCommerce based on Evosus changes
        // Implementation depends on specific requirements

        $this->logger->log_info('Customer update webhook received', $data);

        return [
            'success' => true,
            'message' => 'Customer update acknowledged'
        ];
    }

    /**
     * Find WooCommerce order by Evosus order ID
     */
    private function find_order_by_evosus_id($evosus_order_id) {
        global $wpdb;

        // Try post meta first
        $order_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_evosus_order_id'
                AND meta_value = %s
                LIMIT 1",
                $evosus_order_id
            )
        );

        if ($order_id) {
            return $order_id;
        }

        // Try HPOS if enabled
        if (Evosus_Helpers::is_hpos_enabled()) {
            $order_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT order_id FROM {$wpdb->prefix}wc_orders_meta
                    WHERE meta_key = '_evosus_order_id'
                    AND meta_value = %s
                    LIMIT 1",
                    $evosus_order_id
                )
            );
        }

        return $order_id;
    }

    /**
     * Map Evosus status to WooCommerce status
     */
    private function map_evosus_status_to_wc($evosus_status) {
        $status_map = [
            'open' => 'processing',
            'closed' => 'completed',
            'cancelled' => 'cancelled',
            'on-hold' => 'on-hold',
            'pending' => 'pending'
        ];

        $evosus_status = strtolower($evosus_status);

        return isset($status_map[$evosus_status]) ? $status_map[$evosus_status] : null;
    }

    /**
     * Get webhook URL
     */
    public static function get_webhook_url() {
        return rest_url('evosus/v1/webhook');
    }

    /**
     * Test webhook (for debugging)
     */
    public function test_webhook($event_type = 'order.updated', $data = []) {
        $default_data = [
            'event_type' => $event_type,
            'evosus_order_id' => '12345',
            'timestamp' => current_time('mysql')
        ];

        $test_data = array_merge($default_data, $data);

        return $this->handle_webhook(new WP_REST_Request('POST', '/evosus/v1/webhook', ['body' => $test_data]));
    }
}
