<?php
/**
 * Test Customer_Add API endpoint
 *
 * Usage: php test-customer-create.php
 *
 * Tests creating a new customer in Evosus with proper data
 */

// API Credentials
$company_sn = '20060511100251-006';
$ticket = '9b6547d3-f45a-482f-b264-2a616a6ec0fb';
$base_url = 'https://cloud3.evosus.com/api';

// Test customer data
$customer_data = [
    'Name_First' => 'Test',
    'Name_Last' => 'Customer',
    'Name_Company' => 'Test Company Ltd',
    'BillTo_ContactName' => 'Test Customer',
    'BillTo_Address1' => '123 Test Street',
    'BillTo_Address2' => 'Suite 100',
    'BillTo_City' => 'Victoria',
    'BillTo_StateAbbr' => 'BC',
    'BillTo_PostCode' => 'V8V 1A1',
    'BillTo_Country' => 'Canada',
    'ShipTo_ContactName' => 'Test Customer',
    'ShipTo_Address1' => '123 Test Street',
    'ShipTo_Address2' => 'Suite 100',
    'ShipTo_City' => 'Victoria',
    'ShipTo_StateAbbr' => 'BC',
    'ShipTo_PostCode' => 'V8V 1A1',
    'ShipTo_Country' => 'Canada',
    'PhoneNumber_Mobile1' => '2501234567',
    'EmailAddress1' => 'test.' . time() . '@example.com', // Unique email
    'DataConversion_LegacySystemID' => 'WC_TEST_' . time(),
    'CustomerNoteText' => 'TEST customer created via API - SAFE TO DELETE',
    'CheckCustomerDuplicates' => 'FALSE' // CRITICAL: Prevent updating existing customers!
];

// Remove empty values to avoid JSON issues
$customer_data = array_filter($customer_data, function($value) {
    return $value !== '' && $value !== null;
});

echo "Testing Customer_Add\n";
echo "Customer Data:\n";
echo json_encode($customer_data, JSON_PRETTY_PRINT) . "\n\n";

// Build request
$url = $base_url . '/method/Customer_Add?CompanySN=' . urlencode($company_sn) . '&ticket=' . urlencode($ticket);

$body = [
    'args' => $customer_data
];

echo "Making request to: {$url}\n\n";

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

if ($data) {
    echo "Parsed Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

    if (isset($data['code']) && $data['code'] === 'OK' && isset($data['response'])) {
        $customer_id = $data['response'];
        echo "✅ Customer created successfully!\n";
        echo "Customer ID: {$customer_id}\n\n";

        // Now fetch the addresses for this new customer
        echo "Fetching addresses for new customer...\n";
        $addr_url = $base_url . '/method/Customer_Addresses_Get?CompanySN=' . urlencode($company_sn) . '&ticket=' . urlencode($ticket);
        $addr_body = [
            'args' => [
                'Customer_ID' => $customer_id
            ]
        ];

        $ch = curl_init($addr_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($addr_body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $addr_response = curl_exec($ch);
        curl_close($ch);

        echo "Addresses Response:\n";
        $addr_data = json_decode($addr_response, true);
        echo json_encode($addr_data, JSON_PRETTY_PRINT) . "\n\n";

        if ($addr_data && isset($addr_data['response']) && !empty($addr_data['response'])) {
            echo "Found " . count($addr_data['response']) . " address(es):\n";
            foreach ($addr_data['response'] as $addr) {
                echo "  - LocationID: {$addr['CustomerLocationID']}\n";
                echo "    Name: {$addr['LocationName']}\n";
                echo "    Address: {$addr['Address1']}, {$addr['City']}, {$addr['State']} {$addr['PostCode']}\n";
                echo "    IsDefaultBillTo: {$addr['IsDefaultBillTo']}\n";
                echo "    IsDefaultShipTo: {$addr['IsDefaultShipTo']}\n\n";
            }
        }
    } else {
        echo "❌ Customer creation failed\n";
        echo "Code: " . ($data['code'] ?? 'N/A') . "\n";
        echo "Message: " . ($data['message'] ?? 'N/A') . "\n";
    }
} else {
    echo "Error: Could not parse response\n";
}
