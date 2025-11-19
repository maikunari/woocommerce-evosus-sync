#!/usr/bin/env php
<?php
/**
 * Simple API Request Debugger
 * Shows exactly what's being sent/received
 */

echo "\n=== Evosus API Request Debugger ===\n\n";

// Get credentials
echo "CompanySN: ";
$company_sn = trim(fgets(STDIN));

echo "Ticket: ";
$ticket = trim(fgets(STDIN));

// Build URL
$base_url = 'https://cloud3.evosus.com/api';
$endpoint = '/method/Customer_Search';
$url = $base_url . $endpoint . '?CompanySN=' . urlencode($company_sn) . '&ticket=' . urlencode($ticket);

// Build request body
$body = [
    'args' => [
        'EmailAddress_List' => 'mike@friendlyfires.ca'
    ]
];
$json_body = json_encode($body);

echo "\n--- REQUEST DETAILS ---\n";
echo "URL: " . $url . "\n";
echo "Method: POST\n";
echo "Content-Type: application/json\n";
echo "Body: " . $json_body . "\n";

// Make request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Capture verbose output
$verbose = fopen('php://temp', 'rw+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

echo "\n--- RESPONSE DETAILS ---\n";
echo "HTTP Code: " . $http_code . "\n";
if ($error) {
    echo "cURL Error: " . $error . "\n";
}
echo "Response Body:\n" . $response . "\n";

// Show verbose cURL output
rewind($verbose);
$verbose_log = stream_get_contents($verbose);
echo "\n--- VERBOSE cURL LOG ---\n";
echo $verbose_log . "\n";

curl_close($ch);
fclose($verbose);

// Try to decode JSON response
if ($response) {
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "\n--- DECODED RESPONSE ---\n";
        print_r($decoded);
    }
}

echo "\n=== END ===\n\n";
