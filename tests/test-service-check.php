#!/usr/bin/env php
<?php
/**
 * Test the ServiceCheck endpoint (simplest possible test)
 */

echo "\n=== Testing ServiceCheck Endpoint ===\n\n";

echo "CompanySN: ";
$company_sn = trim(fgets(STDIN));

echo "Ticket: ";
$ticket = trim(fgets(STDIN));

// Use GET as per spec
$url = 'https://cloud3.evosus.com/api/method/ServiceCheck';
$url .= '?CompanySN=' . urlencode($company_sn);
$url .= '&ticket=' . urlencode($ticket);

echo "\nURL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n\n";

if ($http_code === 200) {
    echo "✓ SUCCESS! API credentials are working!\n\n";
} else {
    echo "✗ FAILED\n\n";
}
