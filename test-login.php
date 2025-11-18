#!/usr/bin/env php
<?php
/**
 * Test Login endpoint to see if we need to authenticate first
 */

echo "\n=== Testing Evosus Login Endpoint ===\n\n";

echo "CompanySN: ";
$company_sn = trim(fgets(STDIN));

echo "API Key (ticket): ";
$api_key = trim(fgets(STDIN));

// Try ServiceLogin endpoint
$url = 'https://cloud3.evosus.com/api/ServiceLogin';
$url .= '?CompanySN=' . urlencode($company_sn);
$url .= '&ticket=' . urlencode($api_key);

echo "\n--- Attempting ServiceLogin ---\n";
echo "URL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response:\n$response\n\n";

$decoded = json_decode($response, true);
if ($decoded) {
    echo "Decoded JSON:\n";
    print_r($decoded);

    if (isset($decoded['response']) && isset($decoded['response']['ticket'])) {
        echo "\n✓ SUCCESS! Got session ticket: " . $decoded['response']['ticket'] . "\n";
        echo "\nUse this ticket for subsequent API calls!\n";
    }
}
