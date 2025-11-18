<?php
/**
 * Test Evosus Inventory_Item_Get endpoint
 * Tests if we can retrieve inventory item by SKU
 */

$company_sn = '20060511100251-006';
$ticket = '9b6547d3-f45a-482f-b264-2a616a6ec0fb';
$sku = 'EF-161-A';

echo "=== Testing Inventory_Item_Get Endpoint ===\n\n";
echo "CompanySN: $company_sn\n";
echo "Ticket: " . substr($ticket, 0, 8) . "...\n";
echo "SKU: $sku\n\n";

$url = 'https://cloud3.evosus.com/api/method/Inventory_Item_Get';
$url .= '?CompanySN=' . urlencode($company_sn);
$url .= '&ticket=' . urlencode($ticket);

$data = json_encode([
    'args' => [
        'ItemCode' => $sku
    ]
]);

echo "--- REQUEST DETAILS ---\n";
echo "URL: $url\n";
echo "Method: POST\n";
echo "Body: $data\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "--- RESPONSE DETAILS ---\n";
echo "HTTP Code: $http_code\n";

if ($curl_error) {
    echo "cURL Error: $curl_error\n";
}

echo "Response Body:\n$response\n\n";

$decoded = json_decode($response, true);

if ($decoded) {
    echo "--- DECODED RESPONSE ---\n";
    print_r($decoded);
    echo "\n";

    if (isset($decoded['code']) && $decoded['code'] === 'OK') {
        echo "\n✓ SUCCESS! Inventory item found!\n\n";

        if (isset($decoded['response'][0])) {
            $item = $decoded['response'][0];
            echo "--- INVENTORY ITEM DETAILS ---\n";
            echo "ItemCode: " . ($item['ItemCode'] ?? 'N/A') . "\n";
            echo "Description: " . ($item['Description'] ?? 'N/A') . "\n";
            echo "Quantity Available: " . ($item['QuantityAvailable'] ?? 'N/A') . "\n";
            echo "Discontinued: " . ($item['Discontinued'] ?? 'N/A') . "\n";
            echo "Unit Price: " . ($item['UnitPrice'] ?? 'N/A') . "\n";

            if (isset($item['ProductLineName'])) {
                echo "Product Line: " . $item['ProductLineName'] . "\n";
            }
            if (isset($item['VendorName'])) {
                echo "Vendor: " . $item['VendorName'] . "\n";
            }
        }
    } else {
        echo "\n✗ ERROR: " . ($decoded['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "Failed to decode JSON response\n";
}

echo "\n=== END ===\n";
