<?php
/**
 * Test Customer_Order_Add with UnitPrice in LineItems
 *
 * Usage: php test-order-with-price.php
 *
 * Tests if we can pass UnitPrice and SalesTax_PK to override Evosus pricing
 */

// API Credentials
$company_sn = '20060511100251-006';
$ticket = '9b6547d3-f45a-482f-b264-2a616a6ec0fb';
$base_url = 'https://cloud3.evosus.com/api';

// Test order data (using known good values from previous test)
$customer_id = '6687'; // Mike Sewell
$bill_to_location_id = '124892'; // Correct location ID for customer 6687
$ship_to_location_id = '124892';
$distribution_method_id = '1';

echo "Testing Customer_Order_Add WITH UnitPrice in LineItems\n\n";

// First, get tax codes to see what's available
echo "Step 1: Getting available tax codes...\n";
$tax_url = $base_url . '/method/TaxCodes_Get?CompanySN=' . urlencode($company_sn) . '&ticket=' . urlencode($ticket);

$ch = curl_init($tax_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['args' => []]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$tax_response = curl_exec($ch);
curl_close($ch);

$tax_data = json_decode($tax_response, true);
echo "Tax Codes Response:\n";
echo json_encode($tax_data, JSON_PRETTY_PRINT) . "\n\n";

// Extract SalesTax_PK for first non-exempt tax
$sales_tax_pk = null;
$tax_rate = 0;
if ($tax_data && isset($tax_data['response'])) {
    foreach ($tax_data['response'] as $tax_code) {
        if ($tax_code['TaxCode'] !== 'Exempt' && $tax_code['TaxRate'] > 0) {
            $sales_tax_pk = $tax_code['SalesTax_PK'];
            $tax_rate = $tax_code['TaxRate'];
            echo "Found tax code: {$tax_code['TaxCode']} (Rate: {$tax_rate}%, PK: {$sales_tax_pk})\n\n";
            break;
        }
    }
}

// Test order with pricing information
$order_data = [
    'args' => [
        'Customer_ID' => $customer_id,
        'BillTo_CustomerLocationID' => $bill_to_location_id,
        'ShipTo_CustomerLocationID' => $ship_to_location_id,
        'DistributionMethodID' => $distribution_method_id,
        'ExpectedOrderTotal' => '250.00',
        'PONumber' => 'TEST-PRICE-' . time(),
        'Order_Note' => 'TEST order with custom pricing - SAFE TO DELETE',
        'Internal_Note' => 'Testing UnitPrice override from WooCommerce',
        'LineItems' => [
            [
                'ItemCode' => 'EF-161-A',
                'Quantity' => 1,
                'UnitPrice' => 199.99, // WooCommerce price (different from Evosus default)
                'Comment' => 'Custom WooCommerce pricing test'
            ]
        ]
    ]
];

// Add SalesTax_PK if we found one
if ($sales_tax_pk) {
    $order_data['args']['SalesTax_PK'] = $sales_tax_pk;
    echo "Adding SalesTax_PK: {$sales_tax_pk}\n";
}

echo "\nStep 2: Creating test order with custom pricing...\n";
echo "Order Data:\n";
echo json_encode($order_data, JSON_PRETTY_PRINT) . "\n\n";

// Build request
$url = $base_url . '/method/Customer_Order_Add?CompanySN=' . urlencode($company_sn) . '&ticket=' . urlencode($ticket);

echo "Making request to: {$url}\n\n";

// Make request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$http_code}\n";
echo "Raw Response:\n";
echo $response . "\n\n";

// Parse response
$data = json_decode($response, true);

if ($data) {
    echo "Parsed Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

    if (isset($data['code']) && $data['code'] === 'OK') {
        echo "✅ Order created successfully!\n";

        if (is_array($data['response']) && isset($data['response']['OrderId'])) {
            $order_id = $data['response']['OrderId'];
        } else {
            $order_id = $data['response'];
        }

        echo "Order ID: {$order_id}\n\n";

        echo "Now verify the order in Evosus to check:\n";
        echo "1. Does the line item show UnitPrice of \$199.99 instead of default \$228.80?\n";
        echo "2. Is the correct tax rate applied?\n";
        echo "3. Does the total match WooCommerce expectations?\n";
    } else {
        echo "❌ Order creation failed\n";
        echo "Code: " . ($data['code'] ?? 'N/A') . "\n";
        echo "Message: " . ($data['message'] ?? 'N/A') . "\n";

        if (isset($data['message']) && strpos($data['message'], 'UnitPrice') !== false) {
            echo "\n⚠️  UnitPrice field not accepted - API may only use Evosus's pricing\n";
        }
        if (isset($data['message']) && strpos($data['message'], 'SalesTax_PK') !== false) {
            echo "\n⚠️  SalesTax_PK field not accepted - may need different approach\n";
        }
    }
} else {
    echo "Error: Could not parse response\n";
}
