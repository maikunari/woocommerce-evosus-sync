<?php
/**
 * WooCommerce to Evosus API Integration
 *
 * This class handles syncing WooCommerce orders to Evosus, including:
 * - Customer duplicate checking
 * - Order creation
 * - Sync tracking
 * - Enhanced logging and error handling
 * - SKU mapping support
 */

class WooCommerce_Evosus_Integration {

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

    /**
     * Main function to sync a WooCommerce order to Evosus
     */
    public function sync_order_to_evosus($wc_order_id, $skip_validation = false) {
        $order = wc_get_order($wc_order_id);

        if (!$order) {
            return ['success' => false, 'message' => __('Order not found', 'woocommerce-evosus-sync')];
        }

        $this->logger->log_info("Starting sync for order #{$wc_order_id}", [], $wc_order_id);

        // Step 1: Validate SKUs and check for issues
        if (!$skip_validation) {
            $validation = $this->validate_order($order);

            if (!$validation['valid']) {
                // Mark order for review
                $this->mark_order_for_review($wc_order_id, $validation['issues']);
                do_action('evosus_order_needs_review', $wc_order_id, $validation['issues']);

                return [
                    'success' => false,
                    'needs_review' => true,
                    'issues' => $validation['issues'],
                    'message' => __('Order needs review before syncing', 'woocommerce-evosus-sync')
                ];
            }
        }

        // Step 2: Check if customer exists in Evosus
        $customer_data = $this->prepare_customer_data($order);
        $evosus_customer = $this->find_or_create_customer($customer_data);

        if (!$evosus_customer['success']) {
            $this->logger->log_error("Failed to find/create customer for order #{$wc_order_id}", $evosus_customer, $wc_order_id);
            do_action('evosus_sync_failed', $wc_order_id, $evosus_customer['message']);
            return $evosus_customer;
        }

        // Step 3: Get required reference data
        $distribution_method_id = $this->get_distribution_method_id();

        // Step 4: Create the order in Evosus
        $order_result = $this->create_evosus_order(
            $order,
            $evosus_customer['customer_id'],
            $evosus_customer['bill_to_location_id'],
            $evosus_customer['ship_to_location_id'],
            $distribution_method_id
        );

        if ($order_result['success']) {
            // Step 5: Save sync metadata to WooCommerce order
            $this->save_sync_metadata($wc_order_id, $order_result['evosus_order_id']);

            // Clear review flag if it was set
            Evosus_Helpers::delete_order_meta($wc_order_id, '_evosus_needs_review');
            Evosus_Helpers::delete_order_meta($wc_order_id, '_evosus_review_issues');

            $this->logger->log_sync($wc_order_id, 'success', 'Order synced successfully', $order_result['evosus_order_id']);
            do_action('evosus_sync_success', $wc_order_id, $order_result['evosus_order_id']);
        } else {
            $this->logger->log_sync($wc_order_id, 'failed', $order_result['message']);
            do_action('evosus_sync_failed', $wc_order_id, $order_result['message']);
        }

        return $order_result;
    }

    /**
     * Validate order before syncing - check SKUs and data
     */
    public function validate_order($order) {
        $issues = [];
        $has_errors = false;

        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            $sku = $product->get_sku();
            $product_name = $item->get_name();

            // Check if SKU exists
            if (empty($sku)) {
                $issues[] = [
                    'type' => 'missing_sku',
                    'severity' => 'error',
                    'item_id' => $item_id,
                    'product_name' => $product_name,
                    'message' => sprintf(__("Product '%s' has no SKU assigned", 'woocommerce-evosus-sync'), $product_name)
                ];
                $has_errors = true;
                continue;
            }

            // Check for SKU mapping first
            $mapped_sku = $this->sku_mapper->get_evosus_sku($sku);
            $check_sku = $mapped_sku ?: $sku;

            // Check if SKU exists in Evosus
            $evosus_item = $this->check_sku_in_evosus($check_sku);

            if (!$evosus_item['exists']) {
                $issues[] = [
                    'type' => 'sku_not_found',
                    'severity' => 'error',
                    'item_id' => $item_id,
                    'product_name' => $product_name,
                    'sku' => $check_sku,
                    'message' => sprintf(__("SKU '%s' not found in Evosus inventory", 'woocommerce-evosus-sync'), $check_sku),
                    'suggestions' => $evosus_item['suggestions']
                ];
                $has_errors = true;
            } elseif ($evosus_item['discontinued']) {
                $issues[] = [
                    'type' => 'discontinued',
                    'severity' => 'warning',
                    'item_id' => $item_id,
                    'product_name' => $product_name,
                    'sku' => $check_sku,
                    'message' => sprintf(__("SKU '%s' is marked as discontinued in Evosus", 'woocommerce-evosus-sync'), $check_sku)
                ];
            }

            // Check if quantity available
            if ($evosus_item['exists']) {
                $qty_needed = $item->get_quantity();
                $qty_available = $evosus_item['quantity_available'];

                if ($qty_available < $qty_needed) {
                    $issues[] = [
                        'type' => 'insufficient_stock',
                        'severity' => 'warning',
                        'item_id' => $item_id,
                        'product_name' => $product_name,
                        'sku' => $check_sku,
                        'qty_needed' => $qty_needed,
                        'qty_available' => $qty_available,
                        'message' => sprintf(__("Insufficient stock: Need %d, Available %d", 'woocommerce-evosus-sync'), $qty_needed, $qty_available)
                    ];
                }
            }
        }

        return [
            'valid' => !$has_errors,
            'issues' => $issues
        ];
    }

    /**
     * Check if SKU exists in Evosus and find similar ones
     */
    private function check_sku_in_evosus($sku) {
        $response = $this->api_request('POST', '/method/Inventory_Item_Get', [
            'args' => [
                'ItemCode' => $sku
            ]
        ]);

        if ($response && isset($response['response']) && !empty($response['response'])) {
            $item = $response['response'][0];
            return [
                'exists' => true,
                'discontinued' => $item['Discontinued'] === 'Yes',
                'quantity_available' => $item['QuantityAvailable'],
                'item_data' => $item
            ];
        }

        // SKU not found - try to find similar SKUs
        $suggestions = $this->find_similar_skus($sku);

        return [
            'exists' => false,
            'suggestions' => $suggestions
        ];
    }

    /**
     * Find similar SKUs in Evosus (fuzzy matching with de-duplication)
     */
    private function find_similar_skus($sku) {
        // Try variations: uppercase, lowercase, with/without dashes, etc.
        $variations = [
            strtoupper($sku),
            strtolower($sku),
            str_replace('-', '', $sku),
            str_replace('_', '', $sku),
            str_replace(' ', '', $sku)
        ];

        // Remove duplicates and the original SKU
        $variations = array_unique($variations);
        $variations = array_filter($variations, function($variant) use ($sku) {
            return $variant !== $sku;
        });

        $suggestions = [];
        $found_skus = []; // Track to avoid duplicate suggestions

        // Limit to first 3 variations for efficiency
        $variations = array_slice($variations, 0, 3);

        foreach ($variations as $variant) {
            // Skip if we've already found this SKU
            if (in_array($variant, $found_skus)) {
                continue;
            }

            $response = $this->api_request('POST', '/method/Inventory_Item_Get', [
                'args' => [
                    'ItemCode' => $variant
                ]
            ]);

            if ($response && isset($response['response']) && !empty($response['response'])) {
                $found_skus[] = $variant;
                $suggestions[] = [
                    'sku' => $variant,
                    'description' => $response['response'][0]['Description']
                ];

                // Limit suggestions to 3 max
                if (count($suggestions) >= 3) {
                    break;
                }
            }
        }

        return $suggestions;
    }

    /**
     * Mark order for manual review
     */
    public function mark_order_for_review($wc_order_id, $issues) {
        Evosus_Helpers::update_order_meta($wc_order_id, '_evosus_needs_review', 'yes');
        Evosus_Helpers::update_order_meta($wc_order_id, '_evosus_review_issues', $issues);
        Evosus_Helpers::update_order_meta($wc_order_id, '_evosus_review_date', current_time('mysql'));

        // Add order note
        $order = wc_get_order($wc_order_id);
        $order->add_order_note(__('⚠️ Evosus Sync: Order flagged for review due to validation issues.', 'woocommerce-evosus-sync'));

        $this->logger->log_warning("Order #{$wc_order_id} marked for review", $issues, $wc_order_id);
    }

    /**
     * Check if customer exists, if not create them
     */
    private function find_or_create_customer($customer_data) {
        // Try multiple search strategies to find existing customer
        // Strategy 1: Search by email (most reliable)
        $search_result = $this->search_customer_by_email($customer_data['EmailAddress1']);

        // Strategy 2: If no email match, try phone number
        if (!$search_result['found'] && !empty($customer_data['PhoneNumber_Mobile1'])) {
            $this->logger->log_info("No email match, trying phone number search");
            $search_result = $this->search_customer_by_phone($customer_data['PhoneNumber_Mobile1']);
        }

        // Strategy 3: If still no match, try name + address (more fuzzy)
        if (!$search_result['found'] && !empty($customer_data['Name_First']) && !empty($customer_data['BillTo_Address1'])) {
            $this->logger->log_info("No email/phone match, trying name+address search");
            $search_result = $this->search_customer_by_name_and_address(
                $customer_data['Name_First'] . ' ' . $customer_data['Name_Last'],
                $customer_data['BillTo_Address1']
            );
        }

        if ($search_result['found']) {
            // Customer exists - get their location IDs
            $addresses = $this->get_customer_addresses($search_result['customer_id']);

            $this->logger->log_info("Existing customer found via {$search_result['match_type']}: {$search_result['customer_id']}");

            // Verify we have location IDs
            if (empty($addresses['bill_to_location_id']) || empty($addresses['ship_to_location_id'])) {
                $this->logger->log_error("Customer {$search_result['customer_id']} has no addresses in Evosus", $addresses);
                return [
                    'success' => false,
                    'message' => sprintf(
                        __('Customer exists in Evosus (ID: %s) but has no addresses configured. Please add an address in Evosus first.', 'woocommerce-evosus-sync'),
                        $search_result['customer_id']
                    )
                ];
            }

            return [
                'success' => true,
                'customer_id' => $search_result['customer_id'],
                'bill_to_location_id' => $addresses['bill_to_location_id'],
                'ship_to_location_id' => $addresses['ship_to_location_id'],
                'is_new' => false,
                'match_type' => $search_result['match_type']
            ];
        }

        // No match found - create new customer
        $this->logger->log_info("No existing customer found, creating new customer");
        return $this->create_customer($customer_data);
    }

    /**
     * Search for customer by email address
     */
    public function search_customer_by_email($email) {
        if (empty($email)) {
            return ['found' => false];
        }

        $response = $this->api_request('POST', '/method/Customer_Search', [
            'args' => [
                'EmailAddress_List' => $email
            ]
        ]);

        if ($response && isset($response['response']) && count($response['response']) > 0) {
            return [
                'found' => true,
                'customer_id' => $response['response'][0]['CustomerID'],
                'match_type' => 'email'
            ];
        }

        return ['found' => false];
    }

    /**
     * Search for customer by phone number
     */
    private function search_customer_by_phone($phone) {
        if (empty($phone)) {
            return ['found' => false];
        }

        // Remove all non-numeric characters
        $clean_phone = Evosus_Helpers::format_phone_number($phone);

        $response = $this->api_request('POST', '/method/Customer_Search', [
            'args' => [
                'PhoneNumber_List' => $clean_phone
            ]
        ]);

        if ($response && isset($response['response']) && count($response['response']) > 0) {
            return [
                'found' => true,
                'customer_id' => $response['response'][0]['CustomerID'],
                'match_type' => 'phone'
            ];
        }

        return ['found' => false];
    }

    /**
     * Search for customer by name and address (fuzzy matching)
     */
    private function search_customer_by_name_and_address($name, $address) {
        if (empty($name) || empty($address)) {
            return ['found' => false];
        }

        $response = $this->api_request('POST', '/method/Customer_Search', [
            'args' => [
                'Name' => $name,
                'Address1' => $address
            ]
        ]);

        if ($response && isset($response['response']) && count($response['response']) > 0) {
            // Name+address can be fuzzy, so we log when this happens
            $this->logger->log_info("Customer found via name+address (fuzzy match): " . json_encode($response['response'][0]));

            return [
                'found' => true,
                'customer_id' => $response['response'][0]['CustomerID'],
                'match_type' => 'name+address'
            ];
        }

        return ['found' => false];
    }

    /**
     * Get customer addresses (needed for order creation)
     */
    private function get_customer_addresses($customer_id) {
        $response = $this->api_request('POST', '/method/Customer_Addresses_Get', [
            'args' => [
                'Customer_ID' => $customer_id
            ]
        ]);

        $bill_to_location_id = null;
        $ship_to_location_id = null;
        $first_address_id = null;

        if ($response && isset($response['response']) && !empty($response['response'])) {
            // First pass: look for default addresses
            foreach ($response['response'] as $address) {
                // Store first address as fallback
                if ($first_address_id === null) {
                    $first_address_id = $address['CustomerLocationID'];
                }

                // Evosus API returns 'Yes' or 'No' as strings (not '1'/true)
                if ($address['IsDefaultBillTo'] === 'Yes' || $address['IsDefaultBillTo'] === '1' || $address['IsDefaultBillTo'] === true) {
                    $bill_to_location_id = $address['CustomerLocationID'];
                }
                if ($address['IsDefaultShipTo'] === 'Yes' || $address['IsDefaultShipTo'] === '1' || $address['IsDefaultShipTo'] === true) {
                    $ship_to_location_id = $address['CustomerLocationID'];
                }
            }

            // If no default addresses found, use the first address for both
            if ($bill_to_location_id === null && $first_address_id !== null) {
                $bill_to_location_id = $first_address_id;
                $this->logger->log_info("No default BillTo address found for customer {$customer_id}, using first address: {$first_address_id}");
            }
            if ($ship_to_location_id === null && $first_address_id !== null) {
                $ship_to_location_id = $first_address_id;
                $this->logger->log_info("No default ShipTo address found for customer {$customer_id}, using first address: {$first_address_id}");
            }
        }

        return [
            'bill_to_location_id' => $bill_to_location_id,
            'ship_to_location_id' => $ship_to_location_id
        ];
    }

    /**
     * Create new customer in Evosus
     */
    private function create_customer($customer_data) {
        $response = $this->api_request('POST', '/method/Customer_Add', [
            'args' => $customer_data
        ]);

        if ($response && isset($response['response'])) {
            $customer_id = $response['response'];

            $this->logger->log_info("New customer created: {$customer_id}");

            // Get the newly created customer's addresses
            $addresses = $this->get_customer_addresses($customer_id);

            return [
                'success' => true,
                'customer_id' => $customer_id,
                'bill_to_location_id' => $addresses['bill_to_location_id'],
                'ship_to_location_id' => $addresses['ship_to_location_id'],
                'is_new' => true
            ];
        }

        return [
            'success' => false,
            'message' => __('Failed to create customer', 'woocommerce-evosus-sync')
        ];
    }

    /**
     * Prepare customer data from WooCommerce order
     */
    private function prepare_customer_data($order) {
        $billing = $order->get_address('billing');
        $shipping = $order->get_address('shipping');

        // Use shipping address if available, otherwise use billing
        $has_shipping = !empty($shipping['address_1']);

        return [
            'Name_First' => $billing['first_name'],
            'Name_Last' => $billing['last_name'],
            'Name_Company' => $billing['company'],
            'BillTo_ContactName' => $billing['first_name'] . ' ' . $billing['last_name'],
            'BillTo_Address1' => $billing['address_1'],
            'BillTo_Address2' => $billing['address_2'],
            'BillTo_City' => $billing['city'],
            'BillTo_StateAbbr' => $billing['state'],
            'BillTo_PostCode' => $billing['postcode'],
            'BillTo_Country' => Evosus_Helpers::get_country_name($billing['country']),
            'ShipTo_ContactName' => $has_shipping ? ($shipping['first_name'] . ' ' . $shipping['last_name']) : ($billing['first_name'] . ' ' . $billing['last_name']),
            'ShipTo_Address1' => $has_shipping ? $shipping['address_1'] : $billing['address_1'],
            'ShipTo_Address2' => $has_shipping ? $shipping['address_2'] : $billing['address_2'],
            'ShipTo_City' => $has_shipping ? $shipping['city'] : $billing['city'],
            'ShipTo_StateAbbr' => $has_shipping ? $shipping['state'] : $billing['state'],
            'ShipTo_PostCode' => $has_shipping ? $shipping['postcode'] : $billing['postcode'],
            'ShipTo_Country' => $has_shipping ? Evosus_Helpers::get_country_name($shipping['country']) : Evosus_Helpers::get_country_name($billing['country']),
            'PhoneNumber_Mobile1' => Evosus_Helpers::format_phone_number($billing['phone']),
            'EmailAddress1' => $billing['email'],
            'DataConversion_LegacySystemID' => 'WC_' . $order->get_customer_id(),
            'CustomerNoteText' => sprintf(__('Customer created from WooCommerce Order #%s', 'woocommerce-evosus-sync'), $order->get_order_number()),
            'CheckCustomerDuplicates' => 'FALSE' // Prevent Evosus from updating existing customers
        ];
    }

    /**
     * Create order in Evosus
     */
    private function create_evosus_order($order, $customer_id, $bill_to_location_id, $ship_to_location_id, $distribution_method_id) {
        $line_items = $this->prepare_line_items($order);

        // Use WooCommerce order number in the PO Number field
        $wc_order_number = $order->get_order_number();

        // Get tax information from WooCommerce order
        $sales_tax_pk = $this->get_evosus_tax_code($order);

        $order_data = [
            'args' => [
                'Customer_ID' => (string)$customer_id,
                'BillTo_CustomerLocationID' => (string)$bill_to_location_id,
                'ShipTo_CustomerLocationID' => (string)$ship_to_location_id,
                'DistributionMethodID' => (string)$distribution_method_id,
                'ExpectedOrderTotal' => Evosus_Helpers::format_price($order->get_total()),
                'PONumber' => $wc_order_number,
                'Order_Note' => sprintf(__('Order from WooCommerce #%s', 'woocommerce-evosus-sync'), $wc_order_number),
                'Internal_Note' => sprintf(__('WooCommerce Order ID: %d | Created via API on %s', 'woocommerce-evosus-sync'), $order->get_id(), date('Y-m-d H:i:s')),
                'LineItems' => $line_items
            ]
        ];

        // Add SalesTax_PK if we have a mapped tax code
        if (!empty($sales_tax_pk)) {
            $order_data['args']['SalesTax_PK'] = (string)$sales_tax_pk;
        }

        $response = $this->api_request('POST', '/method/Customer_Order_Add', $order_data);

        if ($response && isset($response['response'])) {
            // Extract OrderId from response (handles both array and string responses)
            if (is_array($response['response']) && isset($response['response']['OrderId'])) {
                $evosus_order_id = $response['response']['OrderId'];
            } else {
                $evosus_order_id = $response['response'];
            }

            return [
                'success' => true,
                'evosus_order_id' => $evosus_order_id,
                'wc_order_number' => $wc_order_number,
                'message' => __('Order created successfully in Evosus', 'woocommerce-evosus-sync')
            ];
        }

        return [
            'success' => false,
            'message' => __('Failed to create order in Evosus', 'woocommerce-evosus-sync'),
            'api_response' => $response
        ];
    }

    /**
     * Prepare line items from WooCommerce order
     */
    private function prepare_line_items($order) {
        $line_items = [];

        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            // Check for SKU override first
            $sku_override = wc_get_order_item_meta($item_id, '_evosus_sku_override', true);

            if (!empty($sku_override)) {
                $item_code = $sku_override;
            } else {
                // Check for SKU mapping
                $wc_sku = $product->get_sku();
                $mapped_sku = $this->sku_mapper->get_evosus_sku($wc_sku);
                $item_code = !empty($mapped_sku) ? $mapped_sku : ($wc_sku ?: 'WC_' . $product->get_id());
            }

            // Calculate unit price from WooCommerce (price customer actually paid)
            // This handles discounts, sale prices, etc.
            $quantity = $item->get_quantity();
            $line_total = $item->get_total(); // Subtotal after discounts, excluding tax
            $unit_price = $quantity > 0 ? ($line_total / $quantity) : 0;

            $line_items[] = [
                'ItemCode' => $item_code,
                'Quantity' => $quantity,
                'UnitPrice' => round($unit_price, 2), // WooCommerce price (may differ from Evosus)
                'Comment' => $item->get_name()
            ];
        }

        return $line_items;
    }

    /**
     * Get Evosus tax code (SalesTax_PK) based on WooCommerce tax rate
     */
    private function get_evosus_tax_code($order) {
        // Get total tax rate from WooCommerce order
        $total_tax = $order->get_total_tax();
        $subtotal = $order->get_subtotal();

        // Calculate tax rate as decimal (e.g., 0.13 for 13%)
        $wc_tax_rate = ($subtotal > 0) ? ($total_tax / $subtotal) : 0;

        // Map WooCommerce tax rate to Evosus SalesTax_PK
        // These mappings are based on common Canadian tax rates
        // Adjust these values based on your Evosus tax code configuration
        $tax_rate_map = [
            0.00 => 1,   // Exempt
            0.05 => 2,   // GST (5%)
            0.13 => 7,   // HST (13%) - Ontario
            0.14 => 11,  // HST (14%) - Nova Scotia
            0.15 => 8,   // HST (15%) - Atlantic provinces
        ];

        // Find closest matching tax rate (within 0.5% tolerance)
        $tolerance = 0.005;
        foreach ($tax_rate_map as $rate => $tax_pk) {
            if (abs($wc_tax_rate - $rate) <= $tolerance) {
                $this->logger->log_info("Matched WooCommerce tax rate {$wc_tax_rate} to Evosus SalesTax_PK {$tax_pk}");
                return $tax_pk;
            }
        }

        // If no match found, log warning and return null (Evosus will use customer's default)
        $this->logger->log_warning("No Evosus tax code match for WooCommerce tax rate: {$wc_tax_rate}. Using customer default.");
        return null;
    }

    /**
     * Get distribution method ID
     */
    private function get_distribution_method_id() {
        return get_option('evosus_distribution_method_id', '1');
    }

    /**
     * Save sync metadata to WooCommerce order
     */
    private function save_sync_metadata($wc_order_id, $evosus_order_id) {
        Evosus_Helpers::update_order_meta($wc_order_id, '_evosus_order_id', $evosus_order_id);
        Evosus_Helpers::update_order_meta($wc_order_id, '_evosus_sync_date', current_time('mysql'));
        Evosus_Helpers::update_order_meta($wc_order_id, '_evosus_synced', 'yes');

        // Add order note with cross-reference information
        $order = wc_get_order($wc_order_id);
        $wc_order_number = $order->get_order_number();

        $order->add_order_note(
            sprintf(
                __('✅ Synced to Evosus' . "\n" . 'Evosus Order ID: %s' . "\n" . 'WC Order #%s added to Evosus PO Number field', 'woocommerce-evosus-sync'),
                $evosus_order_id,
                $wc_order_number
            )
        );
    }

    /**
     * Get Evosus order details by Order ID (OPTIMIZED)
     */
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

        // Fallback: Search in open orders (more targeted than fetching all)
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

        // If not found in open orders, check closed orders with shorter range for efficiency
        $end_date = date('Y-m-d H:i:s');
        $begin_date = date('Y-m-d H:i:s', strtotime('-90 days')); // Reduced from 180 to 90 days

        $response = $this->api_request('POST', '/method/Orders_Closed_Search', [
            'args' => [
                'Begin_Date' => $begin_date,
                'End_Date' => $end_date,
                'OrderID_List' => $evosus_order_id // Try to filter by ID if API supports it
            ]
        ]);

        if ($response && isset($response['response'])) {
            // Use strict comparison and early return for efficiency
            foreach ($response['response'] as $order) {
                if ($order['OrderId'] === $evosus_order_id || $order['OrderId'] == $evosus_order_id) {
                    return [
                        'success' => true,
                        'order' => $order
                    ];
                }
            }
        }

        return [
            'success' => false,
            'message' => __('Order not found in Evosus', 'woocommerce-evosus-sync')
        ];
    }

    /**
     * Verify WooCommerce order number exists in Evosus PO field
     */
    public function verify_cross_reference($wc_order_id) {
        $order = wc_get_order($wc_order_id);
        $wc_order_number = $order->get_order_number();
        $evosus_order_id = Evosus_Helpers::get_order_meta($wc_order_id, '_evosus_order_id', true);

        if (empty($evosus_order_id)) {
            return [
                'success' => false,
                'message' => __('Order not synced to Evosus yet', 'woocommerce-evosus-sync')
            ];
        }

        $evosus_order = $this->get_evosus_order_details($evosus_order_id);

        if (!$evosus_order['success']) {
            return $evosus_order;
        }

        $po_number = $evosus_order['order']['PoNo'];

        // Use string comparison as both values may be strings
        $is_verified = (string)$po_number === (string)$wc_order_number;

        return [
            'success' => true,
            'verified' => $is_verified,
            'evosus_po_number' => $po_number,
            'wc_order_number' => $wc_order_number,
            'evosus_order_id' => $evosus_order_id,
            'message' => $is_verified
                ? __('Cross-reference verified successfully', 'woocommerce-evosus-sync')
                : __('PO Number mismatch detected', 'woocommerce-evosus-sync')
        ];
    }

    /**
     * Get orders synced today
     */
    public function get_orders_synced_today() {
        return $this->get_orders_synced_in_range('today');
    }

    /**
     * Get orders synced this week
     */
    public function get_orders_synced_this_week() {
        return $this->get_orders_synced_in_range('this_week');
    }

    /**
     * Get orders synced in a date range
     */
    public function get_orders_synced_in_range($range = 'today') {
        $args = [
            'limit' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_evosus_synced',
                    'value' => 'yes'
                ],
                [
                    'key' => '_evosus_sync_date',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => '_evosus_order_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ];

        // Add date range
        switch ($range) {
            case 'today':
                $args['date_created'] = '>' . strtotime('today midnight');
                break;
            case 'this_week':
                $args['date_created'] = '>' . strtotime('monday this week');
                break;
            case 'this_month':
                $args['date_created'] = '>' . strtotime('first day of this month');
                break;
        }

        $orders = wc_get_orders($args);

        $results = [];
        foreach ($orders as $order) {
            // Get meta values
            $evosus_order_id = Evosus_Helpers::get_order_meta($order->get_id(), '_evosus_order_id', true);
            $sync_date = Evosus_Helpers::get_order_meta($order->get_id(), '_evosus_sync_date', true);

            // Skip orders that don't have valid sync data
            if (empty($evosus_order_id) || empty($sync_date) || $sync_date == 0) {
                continue;
            }

            $results[] = [
                'wc_order_id' => $order->get_id(),
                'wc_order_number' => $order->get_order_number(),
                'evosus_order_id' => $evosus_order_id,
                'sync_date' => $sync_date,
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'order_total' => $order->get_total(),
                'order_date' => $order->get_date_created()->format('Y-m-d H:i:s')
            ];
        }

        return $results;
    }

    /**
     * Get orders that need review
     */
    public function get_orders_needing_review() {
        $args = [
            'limit' => -1,
            'meta_query' => [
                [
                    'key' => '_evosus_needs_review',
                    'value' => 'yes'
                ]
            ]
        ];

        $orders = wc_get_orders($args);

        $results = [];
        foreach ($orders as $order) {
            $issues = Evosus_Helpers::get_order_meta($order->get_id(), '_evosus_review_issues', true);

            $results[] = [
                'wc_order_id' => $order->get_id(),
                'wc_order_number' => $order->get_order_number(),
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'order_total' => $order->get_total(),
                'order_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'review_date' => Evosus_Helpers::get_order_meta($order->get_id(), '_evosus_review_date', true),
                'issues' => $issues
            ];
        }

        return $results;
    }

    /**
     * Update SKU mapping for an order item
     */
    public function update_order_item_sku($order_id, $item_id, $new_sku) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return ['success' => false, 'message' => __('Order not found', 'woocommerce-evosus-sync')];
        }

        // Store SKU override
        wc_update_order_item_meta($item_id, '_evosus_sku_override', $new_sku);

        $this->logger->log_info("SKU override set for order #{$order_id}, item #{$item_id}: {$new_sku}", [], $order_id);

        return ['success' => true, 'message' => __('SKU mapping updated', 'woocommerce-evosus-sync')];
    }

    /**
     * Approve order for sync after review
     */
    public function approve_order_for_sync($order_id) {
        // Re-validate to make sure issues are resolved
        $order = wc_get_order($order_id);
        $validation = $this->validate_order($order);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => __('Order still has validation errors', 'woocommerce-evosus-sync'),
                'issues' => $validation['issues']
            ];
        }

        // Clear review flag and sync
        Evosus_Helpers::delete_order_meta($order_id, '_evosus_needs_review');
        Evosus_Helpers::delete_order_meta($order_id, '_evosus_review_issues');

        return $this->sync_order_to_evosus($order_id, true);
    }

    /**
     * Check if order has already been synced
     */
    public function is_order_synced($wc_order_id) {
        return Evosus_Helpers::get_order_meta($wc_order_id, '_evosus_synced', true) === 'yes';
    }

    /**
     * Make API request to Evosus (ENHANCED with retry logic and logging)
     */
    private function api_request($method, $endpoint, $body = null, $retry_count = 0) {
        $start_time = microtime(true);

        // Check test mode
        if (Evosus_Helpers::is_test_mode()) {
            $this->logger->log_info('Test mode enabled - API call simulated', [
                'endpoint' => $endpoint,
                'method' => $method,
                'body' => $body
            ]);

            // Use mock API for realistic responses
            if (class_exists('Evosus_Mock_API')) {
                $mock_response = Evosus_Mock_API::get_mock_response($endpoint, $body);
                $this->logger->log_info('Mock API response returned', $mock_response);

                // Log the API call for debugging (same as real API calls)
                $this->logger->log_api_call(
                    $endpoint,
                    $method,
                    $body,
                    $mock_response,
                    200, // Mock status code
                    0    // No execution time in test mode
                );

                return $mock_response;
            }

            // Fallback to empty response
            $empty_response = ['response' => [], 'test_mode' => true];
            $this->logger->log_api_call($endpoint, $method, $body, $empty_response, 200, 0);
            return $empty_response;
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
                sleep(pow(2, $retry_count)); // Exponential backoff: 1s, 2s, 4s
                return $this->api_request($method, $endpoint, $body, $retry_count + 1);
            }

            $this->logger->log_error('API request failed after retries: ' . $error_message, [
                'endpoint' => $endpoint,
                'method' => $method,
                'retries' => $retry_count
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
}
