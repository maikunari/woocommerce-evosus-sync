<?php
/**
 * Get actual Evosus tax codes for mapping
 *
 * Usage: php get-tax-codes.php
 */

// API Credentials
$company_sn = '20060511100251-006';
$ticket = '9b6547d3-f45a-482f-b264-2a616a6ec0fb';
$base_url = 'https://cloud3.evosus.com/api';

echo "Fetching Evosus Tax Codes...\n\n";

$url = $base_url . '/method/TaxCodes_Get?CompanySN=' . urlencode($company_sn) . '&ticket=' . urlencode($ticket);

$body = ['args' => []];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$http_code}\n\n";

$data = json_decode($response, true);

if ($data && isset($data['response'])) {
    echo "TAX CODE MAPPING FOR PLUGIN:\n";
    echo "========================================\n\n";
    echo "Copy this into class-wc-evosus-integration.php:\n\n";
    echo "\$tax_rate_map = [\n";

    foreach ($data['response'] as $tax_code) {
        $rate = floatval($tax_code['TaxRate']);
        $pk = $tax_code['SalesTax_PK'];
        $name = $tax_code['TaxCode'];

        printf("    %.2f => %d,   // %s\n", $rate, $pk, $name);
    }

    echo "];\n\n";
    echo "========================================\n\n";
    echo "FULL TAX CODE DETAILS:\n";
    echo json_encode($data['response'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Error fetching tax codes\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
}
