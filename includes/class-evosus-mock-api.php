<?php
/**
 * Evosus Mock API - Returns realistic API responses for testing
 *
 * This class provides sample responses that match Evosus API structure
 * allowing complete workflow testing without live API access.
 */

class Evosus_Mock_API {

    /**
     * Get mock response for API endpoint
     */
    public static function get_mock_response($endpoint, $body = null) {
        $responses = self::get_response_templates();

        // Extract method name from endpoint
        // Example: "/method/Inventory_Item_Get" -> "Inventory_Item_Get"
        $method = str_replace('/method/', '', $endpoint);

        if (isset($responses[$method])) {
            return $responses[$method]($body);
        }

        // Default empty response
        return ['response' => []];
    }

    /**
     * Response templates for each endpoint
     */
    private static function get_response_templates() {
        return [

            // Inventory Item Get - Check if SKU exists
            'Inventory_Item_Get' => function($body) {
                $sku = $body['args']['ItemCode'] ?? 'UNKNOWN';

                // Simulate different scenarios based on SKU pattern
                if (strpos($sku, 'INVALID') !== false) {
                    // SKU not found
                    return ['response' => []];
                }

                if (strpos($sku, 'DISCONTINUED') !== false) {
                    // Discontinued item
                    return [
                        'response' => [[
                            'ItemCode' => $sku,
                            'Description' => 'Test Product (Discontinued)',
                            'Discontinued' => 'Yes',
                            'QuantityAvailable' => 0,
                            'Price' => 99.99
                        ]]
                    ];
                }

                if (strpos($sku, 'LOWSTOCK') !== false) {
                    // Low stock item
                    return [
                        'response' => [[
                            'ItemCode' => $sku,
                            'Description' => 'Test Product (Low Stock)',
                            'Discontinued' => 'No',
                            'QuantityAvailable' => 2,
                            'Price' => 149.99
                        ]]
                    ];
                }

                // Normal item with good stock
                return [
                    'response' => [[
                        'ItemCode' => $sku,
                        'Description' => 'Test Product',
                        'Discontinued' => 'No',
                        'QuantityAvailable' => 100,
                        'Price' => 199.99
                    ]]
                ];
            },

            // Customer Search - Find existing customer
            'Customer_Search' => function($body) {
                $email = $body['args']['Email'] ?? '';

                // Simulate existing customer for specific test emails
                if (in_array($email, ['test@example.com', 'existing@customer.com'])) {
                    return [
                        'response' => [[
                            'CustomerId' => 'MOCK-CUST-' . substr(md5($email), 0, 8),
                            'FirstName' => 'Test',
                            'LastName' => 'Customer',
                            'Email' => $email,
                            'BillToLocationId' => 'MOCK-LOC-BILL-1',
                            'ShipToLocationId' => 'MOCK-LOC-SHIP-1'
                        ]]
                    ];
                }

                // New customer - return empty
                return ['response' => []];
            },

            // Customer Addresses Get
            'Customer_Addresses_Get' => function($body) {
                $customer_id = $body['args']['CustomerId'] ?? 'MOCK-CUST-123';

                return [
                    'response' => [
                        'BillTo' => [
                            'LocationId' => 'MOCK-LOC-BILL-1',
                            'Address1' => '123 Test Street',
                            'Address2' => 'Suite 100',
                            'City' => 'Test City',
                            'State' => 'CA',
                            'Zip' => '12345',
                            'Country' => 'United States'
                        ],
                        'ShipTo' => [
                            'LocationId' => 'MOCK-LOC-SHIP-1',
                            'Address1' => '456 Shipping Ave',
                            'Address2' => '',
                            'City' => 'Ship City',
                            'State' => 'NY',
                            'Zip' => '67890',
                            'Country' => 'United States'
                        ]
                    ]
                ];
            },

            // Customer Add - Create new customer
            'Customer_Add' => function($body) {
                $email = $body['Customer']['Email'] ?? 'unknown@example.com';

                return [
                    'response' => [
                        'CustomerId' => 'MOCK-CUST-' . substr(md5($email . time()), 0, 8),
                        'BillToLocationId' => 'MOCK-LOC-BILL-' . substr(md5('bill' . time()), 0, 6),
                        'ShipToLocationId' => 'MOCK-LOC-SHIP-' . substr(md5('ship' . time()), 0, 6),
                        'Message' => 'Customer created successfully'
                    ]
                ];
            },

            // Customer Order Add - Create order
            'Customer_Order_Add' => function($body) {
                return [
                    'response' => [
                        'OrderId' => 'MOCK-ORD-' . time() . '-' . rand(1000, 9999),
                        'OrderNumber' => 'SO-' . date('Ymd') . '-' . rand(1000, 9999),
                        'Message' => 'Order created successfully'
                    ]
                ];
            },

            // Order Get - Get order details
            'Order_Get' => function($body) {
                $order_id = $body['args']['OrderId'] ?? 'MOCK-ORD-UNKNOWN';

                return [
                    'response' => [[
                        'OrderId' => $order_id,
                        'OrderNumber' => 'SO-20251105-1234',
                        'PoNo' => '12345', // WooCommerce order number
                        'CustomerId' => 'MOCK-CUST-123',
                        'Status' => 'Open',
                        'OrderDate' => date('Y-m-d H:i:s'),
                        'SubTotal' => 199.99,
                        'TaxTotal' => 15.00,
                        'ShippingTotal' => 10.00,
                        'GrandTotal' => 224.99
                    ]]
                ];
            },

            // Orders Open Search
            'Orders_Open_Search' => function($body) {
                return [
                    'response' => [
                        [
                            'OrderId' => 'MOCK-ORD-001',
                            'OrderNumber' => 'SO-20251105-1001',
                            'PoNo' => '12340',
                            'Status' => 'Open',
                            'GrandTotal' => 150.00
                        ],
                        [
                            'OrderId' => 'MOCK-ORD-002',
                            'OrderNumber' => 'SO-20251105-1002',
                            'PoNo' => '12341',
                            'Status' => 'Open',
                            'GrandTotal' => 275.50
                        ]
                    ]
                ];
            },

            // Orders Closed Search
            'Orders_Closed_Search' => function($body) {
                return [
                    'response' => [
                        [
                            'OrderId' => 'MOCK-ORD-100',
                            'OrderNumber' => 'SO-20251104-2001',
                            'PoNo' => '12300',
                            'Status' => 'Closed',
                            'GrandTotal' => 450.00,
                            'CompletedDate' => date('Y-m-d', strtotime('-1 day'))
                        ]
                    ]
                ];
            },

            // Distribution Method Get
            'Distribution_Method_Get' => function($body) {
                return [
                    'response' => [
                        [
                            'DistributionMethodId' => '1',
                            'Name' => 'Standard Shipping'
                        ],
                        [
                            'DistributionMethodId' => '2',
                            'Name' => 'Express Shipping'
                        ]
                    ]
                ];
            }
        ];
    }
}
