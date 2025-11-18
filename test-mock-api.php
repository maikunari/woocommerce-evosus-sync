<?php
/**
 * Mock API Test Script
 *
 * Tests the Evosus Mock API responses for all scenarios
 * Run: php test-mock-api.php
 */

// Load the mock API class
require_once __DIR__ . '/includes/class-evosus-mock-api.php';

echo "=== Evosus Mock API Test Suite ===\n\n";

// Test counters
$tests_run = 0;
$tests_passed = 0;
$tests_failed = 0;

/**
 * Test helper function
 */
function test_scenario($name, $endpoint, $body, $expected_checks) {
    global $tests_run, $tests_passed, $tests_failed;

    $tests_run++;
    echo "Test #{$tests_run}: {$name}\n";
    echo str_repeat('-', 60) . "\n";

    // Get mock response
    $response = Evosus_Mock_API::get_mock_response($endpoint, $body);

    // Display response
    echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";

    // Run checks
    $all_passed = true;
    foreach ($expected_checks as $check_name => $check_fn) {
        $result = $check_fn($response);
        $status = $result ? '✅ PASS' : '❌ FAIL';
        echo "{$status}: {$check_name}\n";
        if (!$result) {
            $all_passed = false;
        }
    }

    if ($all_passed) {
        $tests_passed++;
        echo "Result: ✅ PASSED\n";
    } else {
        $tests_failed++;
        echo "Result: ❌ FAILED\n";
    }

    echo "\n";
}

// ============================================================================
// Scenario A: Valid SKU - Should Pass
// ============================================================================
test_scenario(
    'Scenario A: Valid SKU (TEST-PROD-123)',
    '/method/Inventory_Item_Get',
    ['args' => ['ItemCode' => 'TEST-PROD-123']],
    [
        'Response has data' => function($r) {
            return !empty($r['response']);
        },
        'SKU found' => function($r) {
            return isset($r['response'][0]['ItemCode']);
        },
        'Not discontinued' => function($r) {
            return $r['response'][0]['Discontinued'] === 'No';
        },
        'Has good stock (100 units)' => function($r) {
            return $r['response'][0]['QuantityAvailable'] == 100;
        }
    ]
);

// ============================================================================
// Scenario B: Invalid SKU - Should Fail
// ============================================================================
test_scenario(
    'Scenario B: Invalid SKU (INVALID-SKU-999)',
    '/method/Inventory_Item_Get',
    ['args' => ['ItemCode' => 'INVALID-SKU-999']],
    [
        'Response is empty' => function($r) {
            return empty($r['response']);
        },
        'No item found' => function($r) {
            return !isset($r['response'][0]);
        }
    ]
);

// ============================================================================
// Scenario C: Discontinued Item - Warning
// ============================================================================
test_scenario(
    'Scenario C: Discontinued Item (DISCONTINUED-ITEM-456)',
    '/method/Inventory_Item_Get',
    ['args' => ['ItemCode' => 'DISCONTINUED-ITEM-456']],
    [
        'Response has data' => function($r) {
            return !empty($r['response']);
        },
        'Item is marked discontinued' => function($r) {
            return $r['response'][0]['Discontinued'] === 'Yes';
        },
        'Has zero stock' => function($r) {
            return $r['response'][0]['QuantityAvailable'] == 0;
        }
    ]
);

// ============================================================================
// Scenario D: Low Stock - Warning
// ============================================================================
test_scenario(
    'Scenario D: Low Stock (LOWSTOCK-PROD-789)',
    '/method/Inventory_Item_Get',
    ['args' => ['ItemCode' => 'LOWSTOCK-PROD-789']],
    [
        'Response has data' => function($r) {
            return !empty($r['response']);
        },
        'Not discontinued' => function($r) {
            return $r['response'][0]['Discontinued'] === 'No';
        },
        'Has low stock (2 units)' => function($r) {
            return $r['response'][0]['QuantityAvailable'] == 2;
        }
    ]
);

// ============================================================================
// Scenario E: Existing Customer
// ============================================================================
test_scenario(
    'Scenario E: Existing Customer (existing@customer.com)',
    '/method/Customer_Search',
    ['args' => ['Email' => 'existing@customer.com']],
    [
        'Response has data' => function($r) {
            return !empty($r['response']);
        },
        'Customer found' => function($r) {
            return isset($r['response'][0]['CustomerId']);
        },
        'Has correct email' => function($r) {
            return $r['response'][0]['Email'] === 'existing@customer.com';
        },
        'Has bill-to location' => function($r) {
            return isset($r['response'][0]['BillToLocationId']);
        },
        'Has ship-to location' => function($r) {
            return isset($r['response'][0]['ShipToLocationId']);
        }
    ]
);

// ============================================================================
// Scenario F: New Customer
// ============================================================================
test_scenario(
    'Scenario F: New Customer (newcustomer@example.com)',
    '/method/Customer_Search',
    ['args' => ['Email' => 'newcustomer@example.com']],
    [
        'Response is empty' => function($r) {
            return empty($r['response']);
        },
        'No customer found' => function($r) {
            return !isset($r['response'][0]);
        }
    ]
);

// ============================================================================
// Scenario G: Customer Creation
// ============================================================================
test_scenario(
    'Scenario G: Create New Customer',
    '/method/Customer_Add',
    ['Customer' => ['Email' => 'newcustomer@example.com']],
    [
        'Response has data' => function($r) {
            return !empty($r['response']);
        },
        'Customer ID returned' => function($r) {
            return isset($r['response']['CustomerId']);
        },
        'Bill-to location returned' => function($r) {
            return isset($r['response']['BillToLocationId']);
        },
        'Ship-to location returned' => function($r) {
            return isset($r['response']['ShipToLocationId']);
        }
    ]
);

// ============================================================================
// Scenario H: Order Creation
// ============================================================================
test_scenario(
    'Scenario H: Create Order',
    '/method/Customer_Order_Add',
    ['Order' => ['CustomerId' => 'MOCK-CUST-123']],
    [
        'Response has data' => function($r) {
            return !empty($r['response']);
        },
        'Order ID returned' => function($r) {
            return isset($r['response']['OrderId']);
        },
        'Order number returned' => function($r) {
            return isset($r['response']['OrderNumber']);
        }
    ]
);

// ============================================================================
// Scenario I: Get Order Details
// ============================================================================
test_scenario(
    'Scenario I: Get Order Details',
    '/method/Order_Get',
    ['args' => ['OrderId' => 'MOCK-ORD-12345']],
    [
        'Response has data' => function($r) {
            return !empty($r['response']);
        },
        'Order details returned' => function($r) {
            return isset($r['response'][0]['OrderId']);
        },
        'Has order totals' => function($r) {
            return isset($r['response'][0]['GrandTotal']);
        }
    ]
);

// ============================================================================
// Scenario J: Distribution Method
// ============================================================================
test_scenario(
    'Scenario J: Get Distribution Methods',
    '/method/Distribution_Method_Get',
    [],
    [
        'Response has data' => function($r) {
            return !empty($r['response']);
        },
        'Multiple methods returned' => function($r) {
            return count($r['response']) >= 2;
        },
        'Has method IDs' => function($r) {
            return isset($r['response'][0]['DistributionMethodId']);
        }
    ]
);

// ============================================================================
// Test Summary
// ============================================================================
echo "\n" . str_repeat('=', 60) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat('=', 60) . "\n";
echo "Total Tests:  {$tests_run}\n";
echo "Passed:       {$tests_passed} ✅\n";
echo "Failed:       {$tests_failed} " . ($tests_failed > 0 ? '❌' : '✅') . "\n";
echo "Success Rate: " . round(($tests_passed / $tests_run) * 100, 1) . "%\n";
echo "\n";

if ($tests_failed === 0) {
    echo "🎉 All tests passed! Mock API is working correctly.\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the mock API implementation.\n";
    exit(1);
}
