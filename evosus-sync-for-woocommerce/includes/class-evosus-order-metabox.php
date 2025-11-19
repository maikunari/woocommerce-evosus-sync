<?php
/**
 * Evosus Order Meta Box
 * Adds Evosus sync functionality directly to the WooCommerce order edit screen
 */

class Evosus_Order_Metabox {
    
    private $integration;
    
    public function __construct($integration) {
        $this->integration = $integration;
        
        // Add meta box to order edit screen
        add_action('add_meta_boxes', [$this, 'add_evosus_meta_box']);
        
        // Add sync status column to orders list
        add_filter('manage_edit-shop_order_columns', [$this, 'add_evosus_column']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'render_evosus_column'], 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_evosus_sync_from_order', [$this, 'ajax_sync_from_order']);
        add_action('wp_ajax_evosus_validate_order', [$this, 'ajax_validate_order']);
        add_action('wp_ajax_evosus_check_customer', [$this, 'ajax_check_customer']);
        add_action('wp_ajax_evosus_verify_cross_reference', [$this, 'ajax_verify_cross_reference']);
        add_action('wp_ajax_map_order_sku', [$this, 'ajax_map_order_sku']);
        
        // Enqueue scripts for order edit screen
        add_action('admin_enqueue_scripts', [$this, 'enqueue_order_scripts']);
    }
    
    /**
     * Add Evosus meta box to order edit screen
     */
    public function add_evosus_meta_box() {
        add_meta_box(
            'evosus_sync_metabox',
            '🔄 Evosus Sync',
            [$this, 'render_meta_box'],
            'shop_order',
            'side',
            'high'
        );
    }
    
    /**
     * Render the meta box content
     */
    public function render_meta_box($post) {
        $order = wc_get_order($post->ID);
        $is_synced = get_post_meta($post->ID, '_evosus_synced', true) === 'yes';
        $needs_review = get_post_meta($post->ID, '_evosus_needs_review', true) === 'yes';
        $evosus_order_id = get_post_meta($post->ID, '_evosus_order_id', true);
        $sync_date = get_post_meta($post->ID, '_evosus_sync_date', true);
        
        wp_nonce_field('evosus_sync_order', 'evosus_sync_nonce');
        ?>
        
        <div id="evosus-sync-container" style="padding: 10px 0;">
            
            <?php if ($is_synced): ?>
                <!-- ORDER ALREADY SYNCED -->
                <div class="evosus-status-success" style="padding: 12px; background: #d4edda; border-left: 4px solid #28a745; margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="font-size: 20px;">✅</span>
                        <strong style="color: #155724;">Synced to Evosus</strong>
                    </div>
                    <div style="font-size: 12px; color: #155724; line-height: 1.6;">
                        <strong>Evosus Order ID:</strong> <code style="background: #c3e6cb; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($evosus_order_id); ?></code><br>
                        <strong>Synced:</strong> <?php echo date('M j, Y g:i A', strtotime($sync_date)); ?><br>
                        <strong>WC Order #<?php echo $order->get_order_number(); ?></strong> added to Evosus PO field
                    </div>
                </div>
                
                <button type="button" 
                        id="evosus-verify-btn" 
                        class="button button-secondary button-large" 
                        style="width: 100%; margin-bottom: 8px;">
                    🔍 Verify Cross-Reference
                </button>
                
                <button type="button" class="button button-secondary button-large" style="width: 100%;" disabled>
                    Already Synced
                </button>
                
                <div id="evosus-verify-result" style="margin-top: 10px; display: none;"></div>
                
                <p style="margin: 10px 0 0 0; font-size: 11px; color: #666; text-align: center;">
                    This order has already been added to Evosus
                </p>
                
            <?php elseif ($needs_review): ?>
                <!-- ORDER NEEDS REVIEW -->
                <?php 
                $issues = get_post_meta($post->ID, '_evosus_review_issues', true);
                $has_errors = false;
                foreach ($issues as $issue) {
                    if ($issue['severity'] === 'error') {
                        $has_errors = true;
                        break;
                    }
                }
                ?>
                
                <div class="evosus-status-warning" style="padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="font-size: 20px;">⚠️</span>
                        <strong style="color: #856404;">Needs Review</strong>
                    </div>
                    <div style="font-size: 12px; color: #856404;">
                        <?php echo count($issues); ?> issue(s) found during validation
                    </div>
                </div>
                
                <div id="evosus-issues-list" style="max-height: 300px; overflow-y: auto; margin-bottom: 15px;">
                    <?php foreach ($issues as $issue): ?>
                        <div class="evosus-issue-item" style="padding: 10px; background: #f8f9fa; border-radius: 4px; margin-bottom: 10px; border-left: 3px solid <?php echo $issue['severity'] === 'error' ? '#dc3545' : '#ffc107'; ?>;">
                            <div style="font-weight: bold; margin-bottom: 5px; font-size: 12px;">
                                <?php echo $issue['severity'] === 'error' ? '❌' : '⚠️'; ?>
                                <?php echo esc_html($issue['product_name']); ?>
                            </div>
                            <div style="font-size: 11px; color: #666; margin-bottom: 8px;">
                                <?php echo esc_html($issue['message']); ?>
                            </div>
                            
                            <?php if ($issue['type'] === 'sku_not_found' && !empty($issue['suggestions'])): ?>
                                <div style="font-size: 11px; margin-top: 8px;">
                                    <strong>Suggestions:</strong>
                                    <?php foreach ($issue['suggestions'] as $suggestion): ?>
                                        <div style="margin: 5px 0; padding: 5px; background: white; border-radius: 3px;">
                                            <code style="font-size: 10px;"><?php echo esc_html($suggestion['sku']); ?></code>
                                            <button type="button" 
                                                    class="button button-small use-sku-btn" 
                                                    data-item-id="<?php echo $issue['item_id']; ?>"
                                                    data-new-sku="<?php echo esc_attr($suggestion['sku']); ?>"
                                                    style="float: right; padding: 2px 8px; font-size: 10px;">
                                                Use This
                                            </button>
                                            <div style="clear: both;"></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($issue['type'] === 'sku_not_found' || $issue['type'] === 'missing_sku'): ?>
                                <div style="margin-top: 8px;">
                                    <input type="text" 
                                           class="manual-sku-input" 
                                           placeholder="Enter Evosus SKU"
                                           data-item-id="<?php echo $issue['item_id']; ?>"
                                           style="width: 100%; padding: 4px; font-size: 11px; margin-bottom: 4px;">
                                    <button type="button" 
                                            class="button button-small map-sku-btn"
                                            data-item-id="<?php echo $issue['item_id']; ?>"
                                            style="width: 100%; font-size: 11px;">
                                        Map SKU
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" 
                        id="evosus-revalidate-btn" 
                        class="button button-secondary button-large" 
                        style="width: 100%; margin-bottom: 8px;">
                    🔄 Re-check Order
                </button>
                
                <button type="button" 
                        id="evosus-approve-sync-btn" 
                        class="button button-primary button-large" 
                        style="width: 100%;"
                        <?php echo $has_errors ? 'disabled' : ''; ?>>
                    <?php echo $has_errors ? '⚠️ Fix Errors First' : '✓ Approve & Add to Evosus'; ?>
                </button>
                
            <?php else: ?>
                <!-- ORDER NOT SYNCED YET -->
                <div class="evosus-status-pending" style="padding: 12px; background: #e7f3ff; border-left: 4px solid #2271b1; margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="font-size: 20px;">⏳</span>
                        <strong style="color: #135e96;">Ready to Sync</strong>
                    </div>
                    <div style="font-size: 12px; color: #135e96;">
                        Order has not been added to Evosus yet
                    </div>
                </div>
                
                <!-- PRE-SYNC VALIDATION INFO -->
                <div id="evosus-validation-info" style="display: none; padding: 10px; background: #f8f9fa; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
                    <div style="margin-bottom: 8px;">
                        <strong>Pre-Sync Check:</strong>
                    </div>
                    <div id="evosus-customer-check" style="margin-bottom: 5px;">
                        <span class="spinner" style="float: left; margin: 0 5px 0 0;"></span>
                        Checking customer...
                    </div>
                    <div id="evosus-sku-check">
                        <span class="spinner" style="float: left; margin: 0 5px 0 0;"></span>
                        Validating SKUs...
                    </div>
                </div>
                
                <button type="button" 
                        id="evosus-validate-btn" 
                        class="button button-secondary button-large" 
                        style="width: 100%; margin-bottom: 8px;">
                    🔍 Check Order First
                </button>
                
                <button type="button" 
                        id="evosus-sync-btn" 
                        class="button button-primary button-large" 
                        style="width: 100%;">
                    ➡️ Add to Evosus
                </button>
                
                <p style="margin: 10px 0 0 0; font-size: 11px; color: #666; text-align: center;">
                    Click "Check Order First" to validate before syncing
                </p>
            <?php endif; ?>
            
            <!-- Status Messages -->
            <div id="evosus-sync-message" style="margin-top: 15px; display: none;"></div>
            
        </div>
        
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

        <?php
        // JavaScript is now in external file: assets/js/evosus-order-metabox.js
        // Loaded via enqueue_order_scripts() method
    }

    /**
     * Add Evosus column to orders list
     */
    public function add_evosus_column($columns) {
        $new_columns = [];

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            // Add Evosus column after order status
            if ($key === 'order_status') {
                $new_columns['evosus_status'] = '🔄 Evosus';
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render Evosus column content
     */
    public function render_evosus_column($column, $post_id) {
        if ($column !== 'evosus_status') {
            return;
        }
        
        $is_synced = get_post_meta($post_id, '_evosus_synced', true) === 'yes';
        $needs_review = get_post_meta($post_id, '_evosus_needs_review', true) === 'yes';
        $evosus_order_id = get_post_meta($post_id, '_evosus_order_id', true);
        
        if ($is_synced) {
            echo '<span style="color: #28a745; font-weight: bold;">✅ Synced</span><br>';
            echo '<small style="color: #666;">ID: ' . esc_html($evosus_order_id) . '</small>';
        } elseif ($needs_review) {
            echo '<span style="color: #ffc107; font-weight: bold;">⚠️ Review</span>';
        } else {
            echo '<span style="color: #6c757d;">⏳ Pending</span>';
        }
    }
    
    /**
     * Enqueue scripts for order edit screen
     */
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
    
    /**
     * AJAX: Sync order from edit screen
     */
    public function ajax_sync_from_order() {
        check_ajax_referer('evosus_sync_order', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $order_id = intval($_POST['order_id']);
        $skip_validation = isset($_POST['skip_validation']) && $_POST['skip_validation'];
        
        $result = $this->integration->sync_order_to_evosus($order_id, $skip_validation);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Validate order
     */
    public function ajax_validate_order() {
        check_ajax_referer('evosus_sync_order', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found']);
        }
        
        // Use the public validate_order method
        $validation = $this->integration->validate_order($order);
        
        if (!$validation['valid']) {
            $this->integration->mark_order_for_review($order_id, $validation['issues']);
        }
        
        wp_send_json_success($validation);
    }
    
    /**
     * AJAX: Check if customer exists
     */
    public function ajax_check_customer() {
        check_ajax_referer('evosus_sync_order', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found']);
        }
        
        $email = $order->get_billing_email();
        
        // Check if customer exists in Evosus
        $result = $this->integration->search_customer_by_email($email);
        
        wp_send_json_success([
            'exists' => $result['found'],
            'customer_id' => $result['found'] ? $result['customer_id'] : null
        ]);
    }
    
    /**
     * AJAX: Verify cross-reference between WooCommerce and Evosus
     */
    public function ajax_verify_cross_reference() {
        check_ajax_referer('evosus_sync_order', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $order_id = intval($_POST['order_id']);
        
        $result = $this->integration->verify_cross_reference($order_id);
        
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
        check_ajax_referer('evosus_sync_order', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
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
}