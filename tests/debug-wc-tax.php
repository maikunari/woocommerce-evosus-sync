<?php
/**
 * Debug WooCommerce tax extraction
 *
 * Usage: Run this to see actual tax data structure from a WooCommerce order
 */

// API Credentials
$company_sn = '20060511100251-006';
$ticket = '9b6547d3-f45a-482f-b264-2a616a6ec0fb';
$base_url = 'https://cloud3.evosus.com/api';

echo "Testing WooCommerce Tax Extraction Logic\n\n";

// Simulate what WooCommerce returns
echo "Testing with 5% GST (BC rate):\n";
$test_tax_percent = "5%";  // What WC_Tax::get_rate_percent() returns
$extracted_rate = floatval(str_replace('%', '', $test_tax_percent)) / 100;
echo "Input: '{$test_tax_percent}'\n";
echo "Extracted: {$extracted_rate}\n";
echo "Match check: abs({$extracted_rate} - 0.05) = " . abs($extracted_rate - 0.05) . "\n";
echo "Within tolerance (0.005)? " . (abs($extracted_rate - 0.05) <= 0.005 ? "YES" : "NO") . "\n\n";

// Test the tax mapping
$tax_rate_map = [
    0.00 => 1,   // Exempt
    0.05 => 2,   // GST (5%)
    0.13 => 7,   // Harmonized Sales Tax (13%)
    0.14 => 11,  // Harmonized Sales Tax (14%)
    0.15 => 8,   // Harmonized Sales Tax (15%)
];

$tolerance = 0.005;
$wc_tax_rate = $extracted_rate;
$found = false;

foreach ($tax_rate_map as $rate => $tax_pk) {
    $diff = abs($wc_tax_rate - $rate);
    echo "Checking rate {$rate}: diff={$diff}, within_tolerance=" . ($diff <= $tolerance ? "YES" : "NO");
    if ($diff <= $tolerance) {
        echo " ✅ MATCH! SalesTax_PK={$tax_pk}\n";
        $found = true;
        break;
    } else {
        echo "\n";
    }
}

if (!$found) {
    echo "\n❌ No match found for rate {$wc_tax_rate}\n";
}

echo "\n========================================\n\n";

// Test with fallback calculation
echo "Testing fallback calculation:\n";
$total_tax = 11.99;  // Example: $11.99 tax
$subtotal_ex_tax = 239.80;  // Example: $239.80 before tax
$calculated_rate = ($subtotal_ex_tax > 0) ? ($total_tax / $subtotal_ex_tax) : 0;
echo "Tax: \${$total_tax}\n";
echo "Subtotal (ex tax): \${$subtotal_ex_tax}\n";
echo "Calculated rate: {$calculated_rate}\n";
echo "As percentage: " . round($calculated_rate * 100, 2) . "%\n";

$found = false;
foreach ($tax_rate_map as $rate => $tax_pk) {
    $diff = abs($calculated_rate - $rate);
    if ($diff <= $tolerance) {
        echo "✅ Would match to SalesTax_PK={$tax_pk} ({$rate})\n";
        $found = true;
        break;
    }
}

if (!$found) {
    echo "❌ No match found\n";
}
