<?php
/**
 * Test if Evosus API actually uses UnitPrice and SalesTax_PK overrides
 *
 * Usage: php test-tax-and-price-override.php
 *
 * This will create a test order with:
 * - Custom UnitPrice different from Evosus default
 * - Custom SalesTax_PK (GST 5% instead of default HST 13%)
 */

// API Credentials
$company_sn = '20060511100251-006';
$ticket = '9b6547d3-f45a-482f-b264-2a616a6ec0fb';
$base_url = 'https://cloud3.evosus.com/api';

$customer_id = '6687'; // Mike Sewell
$bill_to_location_id = '124892';
$ship_to_location_id = '124892';
$distribution_method_id = '1';

echo "Testing Evosus API: Does it honor UnitPrice and SalesTax_PK overrides?\n\n";

// Item: EF-161-A normally costs $228.80 in Evosus
// We'll try to override it to $150.00
// Customer default tax: 13% HST
// We'll try to override to: 5% GST (SalesTax_PK = 2)

$custom_price = 150.00;
$custom_tax_pk = '2'; // GST 5%

$order_data = [
    'args' => [
        'Customer_ID' => $customer_id,
        'BillTo_CustomerLocationID' => $bill_to_location_id,
        'ShipTo_CustomerLocationID' => $ship_to_location_id,
        'DistributionMethodID' => $distribution_method_id,
        'SalesTax_PK' => $custom_tax_pk, // Override to GST 5%
        'ExpectedOrderTotal' => '157.50', // $150 + 5% tax
        'PONumber' => 'TEST-OVERRIDE-' . time(),
        'Order_Note' => 'TEST order - Price & Tax Override Test - SAFE TO DELETE',
        'Internal_Note' => 'Testing if API honors UnitPrice=$150 and SalesTax_PK=2 (GST 5%)',
        'LineItems' => [
            [
                'ItemCode' => 'EF-161-A',
                'Quantity' => 1,
                'UnitPrice' => $custom_price, // Override default $228.80 to $150.00
                'Comment' => 'Price override test: $150 instead of $228.80'
            ]
        ]
    ]
];

echo "Test Parameters:\n";
echo "================\n";
echo "Item: EF-161-A (normally $228.80 in Evosus)\n";
echo "Override Price: $" . number_format($custom_price, 2) . "\n";
echo "Customer Default Tax: HST 13% (Ontario)\n";
echo "Override Tax: GST 5% (SalesTax_PK = {$custom_tax_pk})\n";
echo "Expected Total: $157.50 ($150 + 5% tax)\n\n";

echo "Sending order data:\n";
echo json_encode($order_data, JSON_PRETTY_PRINT) . "\n\n";

$url = $base_url . '/method/Customer_Order_Add?CompanySN=' . urlencode($company_sn) . '&ticket=' . urlencode($ticket);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$http_code}\n";
echo "Response:\n";
echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n\n";

$data = json_decode($response, true);
if (isset($data['code']) && $data['code'] === 'OK') {
    echo "✅ Order created successfully!\n\n";
    $order_id = is_array($data['response']) ? $data['response']['OrderId'] : $data['response'];
    echo "Evosus Order ID: {$order_id}\n\n";

    echo "========================================\n";
    echo "MANUAL VERIFICATION REQUIRED\n";
    echo "========================================\n";
    echo "Please check this order in Evosus dashboard:\n\n";
    echo "1. Search for Order ID: {$order_id}\n";
    echo "2. Check Tax Code:\n";
    echo "   - Expected: GST (5%)\n";
    echo "   - If showing: Harmonized Sales Tax (13%) = API IGNORED SalesTax_PK ❌\n\n";
    echo "3. Check Line Item Price:\n";
    echo "   - Expected: $150.00\n";
    echo "   - If showing: $228.80 = API IGNORED UnitPrice ❌\n\n";
    echo "4. Check Order Total:\n";
    echo "   - Expected: ~$157.50 (if both overrides work)\n";
    echo "   - If showing: ~$258.54 = Both overrides ignored ❌\n";
    echo "   - If showing: ~$169.50 = Only UnitPrice worked, tax ignored\n";
    echo "   - If showing: ~$240.35 = Only SalesTax_PK worked, price ignored\n";

} else {
    echo "❌ Order creation failed\n";
    echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n";
}
