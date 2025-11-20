<?php
/**
 * Test Customer_Order_Add with freight line item (SD-2204)
 *
 * Usage: php test-order-with-freight.php
 *
 * Tests if SD-2204 freight item works in API orders
 */

// API Credentials
$company_sn = '20060511100251-006';
$ticket = '9b6547d3-f45a-482f-b264-2a616a6ec0fb';
$base_url = 'https://cloud3.evosus.com/api';

// Test order data
$customer_id = '6687'; // Mike Sewell
$bill_to_location_id = '124892';
$ship_to_location_id = '124892';
$distribution_method_id = '1';

echo "Testing Customer_Order_Add with FREIGHTE2 freight item\n\n";

// Test 1: Order WITHOUT SalesTax_PK (let Evosus use customer default)
echo "=== Test 1: Order WITHOUT SalesTax_PK ===\n";
$order_data_1 = [
    'args' => [
        'Customer_ID' => $customer_id,
        'BillTo_CustomerLocationID' => $bill_to_location_id,
        'ShipTo_CustomerLocationID' => $ship_to_location_id,
        'DistributionMethodID' => $distribution_method_id,
        'ExpectedOrderTotal' => '250.00',
        'PONumber' => 'TEST-FREIGHT-1-' . time(),
        'Order_Note' => 'TEST order with freight - NO TAX PK - SAFE TO DELETE',
        'Internal_Note' => 'Testing FREIGHTE2 freight without SalesTax_PK',
        'LineItems' => [
            [
                'ItemCode' => 'EF-161-A',
                'Quantity' => 1,
                'UnitPrice' => 199.99,
                'Comment' => 'Test product'
            ],
            [
                'ItemCode' => 'FREIGHTE2',
                'Quantity' => 1,
                'UnitPrice' => 25.00,
                'Comment' => 'Shipping: Test Method'
            ]
        ]
    ]
];

echo "Order Data:\n";
echo json_encode($order_data_1, JSON_PRETTY_PRINT) . "\n\n";

$url = $base_url . '/method/Customer_Order_Add?CompanySN=' . urlencode($company_sn) . '&ticket=' . urlencode($ticket);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data_1));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$http_code}\n";
echo "Response:\n";
echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n\n";

$data = json_decode($response, true);
if (isset($data['code']) && $data['code'] === 'OK') {
    echo "✅ Test 1 PASSED: Order created without SalesTax_PK\n\n";
    $test1_order_id = is_array($data['response']) ? $data['response']['OrderId'] : $data['response'];
    echo "Order ID: {$test1_order_id}\n\n";
} else {
    echo "❌ Test 1 FAILED\n";
    echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n\n";

    // Check for specific error messages
    if (isset($data['message'])) {
        $msg = $data['message'];
        if (strpos($msg, 'FREIGHTE2') !== false) {
            echo "⚠️  Issue with freight item FREIGHTE2\n";
            echo "   - Item might not exist in inventory\n";
            echo "   - Item might be discontinued\n";
            echo "   - Item might not allow API orders\n\n";
        }
        if (strpos($msg, 'SalesTax') !== false || strpos($msg, 'tax') !== false) {
            echo "⚠️  Tax-related error\n\n";
        }
        if (strpos($msg, 'UnitPrice') !== false) {
            echo "⚠️  UnitPrice not accepted for this item\n\n";
        }
    }
}

echo "\n========================================\n\n";

// Test 2: Order WITH SalesTax_PK
echo "=== Test 2: Order WITH SalesTax_PK (GST 5%) ===\n";
$order_data_2 = [
    'args' => [
        'Customer_ID' => $customer_id,
        'BillTo_CustomerLocationID' => $bill_to_location_id,
        'ShipTo_CustomerLocationID' => $ship_to_location_id,
        'DistributionMethodID' => $distribution_method_id,
        'ExpectedOrderTotal' => '250.00',
        'PONumber' => 'TEST-FREIGHT-2-' . time(),
        'Order_Note' => 'TEST order with freight + TAX PK - SAFE TO DELETE',
        'Internal_Note' => 'Testing FREIGHTE2 freight WITH SalesTax_PK',
        'SalesTax_PK' => '2', // GST 5%
        'LineItems' => [
            [
                'ItemCode' => 'EF-161-A',
                'Quantity' => 1,
                'UnitPrice' => 199.99,
                'Comment' => 'Test product'
            ],
            [
                'ItemCode' => 'FREIGHTE2',
                'Quantity' => 1,
                'UnitPrice' => 25.00,
                'Comment' => 'Shipping: Test Method'
            ]
        ]
    ]
];

echo "Order Data:\n";
echo json_encode($order_data_2, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data_2));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$http_code}\n";
echo "Response:\n";
echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n\n";

$data = json_decode($response, true);
if (isset($data['code']) && $data['code'] === 'OK') {
    echo "✅ Test 2 PASSED: Order created with SalesTax_PK\n\n";
    $test2_order_id = is_array($data['response']) ? $data['response']['OrderId'] : $data['response'];
    echo "Order ID: {$test2_order_id}\n\n";
} else {
    echo "❌ Test 2 FAILED\n";
    echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n\n";
}

echo "\n========================================\n\n";

// Test 3: Order WITHOUT UnitPrice (let Evosus use default pricing)
echo "=== Test 3: Order WITHOUT UnitPrice (Evosus defaults) ===\n";
$order_data_3 = [
    'args' => [
        'Customer_ID' => $customer_id,
        'BillTo_CustomerLocationID' => $bill_to_location_id,
        'ShipTo_CustomerLocationID' => $ship_to_location_id,
        'DistributionMethodID' => $distribution_method_id,
        'ExpectedOrderTotal' => '250.00',
        'PONumber' => 'TEST-FREIGHT-3-' . time(),
        'Order_Note' => 'TEST order - NO UNIT PRICE - SAFE TO DELETE',
        'Internal_Note' => 'Testing without UnitPrice override',
        'LineItems' => [
            [
                'ItemCode' => 'EF-161-A',
                'Quantity' => 1,
                'Comment' => 'Test product'
            ],
            [
                'ItemCode' => 'FREIGHTE2',
                'Quantity' => 1,
                'Comment' => 'Shipping: Test Method'
            ]
        ]
    ]
];

echo "Order Data:\n";
echo json_encode($order_data_3, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data_3));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$http_code}\n";
echo "Response:\n";
echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n\n";

$data = json_decode($response, true);
if (isset($data['code']) && $data['code'] === 'OK') {
    echo "✅ Test 3 PASSED: Order created without UnitPrice\n\n";
    $test3_order_id = is_array($data['response']) ? $data['response']['OrderId'] : $data['response'];
    echo "Order ID: {$test3_order_id}\n\n";
} else {
    echo "❌ Test 3 FAILED\n";
    echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n\n";
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Run these tests to identify which parameter is causing the issue.\n";
echo "Check the error messages above for specific details.\n";
