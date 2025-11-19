<?php
/**
 * Test Customer_Addresses_Get API endpoint
 *
 * Usage: php test-customer-addresses.php
 *
 * Tests getting addresses for customer ID 6687 (mike@friendlyfires.ca)
 */

// API Credentials
$company_sn = '20060511100251-006';
$ticket = '9b6547d3-f45a-482f-b264-2a616a6ec0fb';
$base_url = 'https://cloud3.evosus.com/api';
$customer_id = 6687; // Mike Sewell

// Build request
$url = $base_url . '/method/Customer_Addresses_Get?CompanySN=' . urlencode($company_sn) . '&ticket=' . urlencode($ticket);

$body = [
    'args' => [
        'Customer_ID' => $customer_id
    ]
];

echo "Testing Customer_Addresses_Get for Customer ID: {$customer_id}\n";
echo "URL: {$url}\n";
echo "Body: " . json_encode($body, JSON_PRETTY_PRINT) . "\n\n";

// Make request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
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

if ($data && isset($data['response'])) {
    echo "Parsed Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

    if (is_array($data['response']) && count($data['response']) > 0) {
        echo "Found " . count($data['response']) . " address(es)\n\n";

        foreach ($data['response'] as $index => $address) {
            echo "Address #{$index}:\n";
            echo "  CustomerLocationID: " . ($address['CustomerLocationID'] ?? 'N/A') . "\n";
            echo "  IsDefaultBillTo: " . var_export($address['IsDefaultBillTo'] ?? 'N/A', true) . " (type: " . gettype($address['IsDefaultBillTo'] ?? null) . ")\n";
            echo "  IsDefaultShipTo: " . var_export($address['IsDefaultShipTo'] ?? 'N/A', true) . " (type: " . gettype($address['IsDefaultShipTo'] ?? null) . ")\n";
            echo "  LocationName: " . ($address['LocationName'] ?? 'N/A') . "\n";
            echo "  Address1: " . ($address['Address1'] ?? 'N/A') . "\n";
            echo "  City: " . ($address['City'] ?? 'N/A') . "\n";
            echo "  State: " . ($address['State'] ?? 'N/A') . "\n";
            echo "  PostCode: " . ($address['PostCode'] ?? 'N/A') . "\n";
            echo "\n";
        }
    } else {
        echo "No addresses found for this customer.\n";
    }
} else {
    echo "Error: Could not parse response or no 'response' field found.\n";
}
