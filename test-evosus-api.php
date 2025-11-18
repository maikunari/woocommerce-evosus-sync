#!/usr/bin/env php
<?php
/**
 * Standalone Evosus API Test Script
 *
 * Tests API connectivity and basic operations without WordPress
 * Run: php test-evosus-api.php
 */

// ANSI color codes for terminal output
define('COLOR_GREEN', "\033[0;32m");
define('COLOR_RED', "\033[0;31m");
define('COLOR_YELLOW', "\033[1;33m");
define('COLOR_BLUE', "\033[0;34m");
define('COLOR_RESET', "\033[0m");

class Evosus_API_Tester {
    private $company_sn;
    private $ticket;
    private $api_base_url = 'https://cloud3.evosus.com/api';

    public function __construct($company_sn, $ticket) {
        $this->company_sn = $company_sn;
        $this->ticket = $ticket;
    }

    /**
     * Make API request
     */
    private function api_request($endpoint, $method = 'GET', $data = null) {
        // Add authentication as URL parameters (not headers!)
        $url = $this->api_base_url . $endpoint;
        $url .= '?CompanySN=' . urlencode($this->company_sn);
        $url .= '&ticket=' . urlencode($this->ticket);

        $headers = [
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'http_code' => $http_code,
            'response' => $response,
            'error' => $error,
            'data' => json_decode($response, true)
        ];
    }

    /**
     * Test 1: Connection Test
     */
    public function test_connection() {
        $this->print_header("Test 1: Connection & Authentication");

        // Search for a known customer to verify authentication and data retrieval
        $result = $this->api_request('/method/Customer_Search', 'POST', [
            'args' => [
                'EmailAddress_List' => 'mike@friendlyfires.ca'
            ]
        ]);

        if ($result['http_code'] === 200) {
            $this->print_success("✓ API connection successful!");
            $this->print_info("HTTP Code: " . $result['http_code']);

            if (!empty($result['data'])) {
                $customer = $result['data'][0];
                $this->print_success("✓ Found customer: mike@friendlyfires.ca");
                $this->print_info("Customer ID: " . ($customer['CustomerID'] ?? 'N/A'));
                $this->print_info("Name: " . ($customer['Name'] ?? 'N/A'));
                $this->print_info("Email: " . ($customer['Email'] ?? 'N/A'));
                $this->print_info("Phone: " . ($customer['Phone'] ?? 'N/A'));

                // Show more details if available
                if (isset($customer['Address1'])) {
                    $this->print_info("Address: " . $customer['Address1']);
                }
                if (isset($customer['City'])) {
                    $this->print_info("City: " . $customer['City'] . ', ' . ($customer['State'] ?? ''));
                }
            } else {
                $this->print_warning("⚠ Customer mike@friendlyfires.ca not found in Evosus");
                $this->print_info("Authentication worked, but customer doesn't exist");
            }
            return true;
        } else {
            $this->print_error("✗ API connection failed!");
            $this->print_error("HTTP Code: " . $result['http_code']);
            if (!empty($result['error'])) {
                $this->print_error("cURL Error: " . $result['error']);
            }
            $this->print_error("Response: " . substr($result['response'], 0, 500));

            // Debug: Show the full URL being used (mask sensitive parts)
            $debug_url = $this->api_base_url . '/method/Customer_Search?CompanySN=' . $this->company_sn . '&ticket=' . substr($this->ticket, 0, 6) . '...';
            $this->print_info("URL attempted: " . $debug_url);

            $this->print_warning("\nPossible issues:");
            $this->print_warning("1. Ticket may have expired - check Evosus dashboard for a fresh ticket");
            $this->print_warning("2. CompanySN format might be incorrect");
            $this->print_warning("3. API credentials might need to be regenerated");
            return false;
        }
    }

    /**
     * Test 2: Search for a customer (read-only)
     */
    public function test_customer_search($email) {
        $this->print_header("Test 2: Customer Search (Read-Only)");
        $this->print_info("Searching for: {$email}");

        $result = $this->api_request('/method/Customer_Search', 'POST', [
            'args' => [
                'EmailAddress_List' => $email
            ]
        ]);

        if ($result['http_code'] === 200) {
            if (!empty($result['data'])) {
                $customer = $result['data'][0];
                $this->print_success("✓ Customer found!");
                $this->print_info("Customer ID: " . ($customer['CustomerID'] ?? 'N/A'));
                $this->print_info("Name: " . ($customer['Name'] ?? 'N/A'));
                $this->print_info("Email: " . ($customer['Email'] ?? 'N/A'));
                $this->print_info("Phone: " . ($customer['Phone'] ?? 'N/A'));
                return $customer;
            } else {
                $this->print_warning("⚠ Customer not found (this is OK - we can test creation)");
                return null;
            }
        } else {
            $this->print_error("✗ Customer search failed!");
            $this->print_error("HTTP Code: " . $result['http_code']);
            $this->print_error("Response: " . $result['response']);
            return false;
        }
    }

    /**
     * Test 3: Check inventory item (read-only)
     */
    public function test_inventory_check($sku) {
        $this->print_header("Test 3: Inventory Check (Read-Only)");
        $this->print_info("Checking SKU: {$sku}");

        $result = $this->api_request('/method/Inventory_Item_List', 'POST', [
            'args' => [
                'SKU' => $sku
            ]
        ]);

        if ($result['http_code'] === 200) {
            if (!empty($result['data'])) {
                $item = $result['data'][0];
                $this->print_success("✓ Item found!");
                $this->print_info("Item ID: " . ($item['ItemID'] ?? 'N/A'));
                $this->print_info("Description: " . ($item['Description'] ?? 'N/A'));
                $this->print_info("Price: $" . ($item['Price'] ?? 'N/A'));
                $this->print_info("Qty on Hand: " . ($item['QtyOnHand'] ?? 'N/A'));
                $this->print_info("Status: " . ($item['Status'] ?? 'Active'));
                return $item;
            } else {
                $this->print_error("✗ SKU not found in inventory!");
                $this->print_warning("Please verify SKU '{$sku}' exists in Evosus");
                return null;
            }
        } else {
            $this->print_error("✗ Inventory check failed!");
            $this->print_error("HTTP Code: " . $result['http_code']);
            $this->print_error("Response: " . $result['response']);
            return false;
        }
    }

    /**
     * Test 4: Update customer email (WRITE operation - careful!)
     */
    public function test_customer_update($customer_id, $new_email) {
        $this->print_header("Test 4: Customer Email Update (WRITE - Careful!)");
        $this->print_warning("⚠ This will UPDATE a real customer in Evosus");
        $this->print_info("Customer ID: {$customer_id}");
        $this->print_info("New Email: {$new_email}");

        echo "\n" . COLOR_YELLOW . "Proceed with update? (yes/no): " . COLOR_RESET;
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        if (strtolower($line) !== 'yes') {
            $this->print_warning("⊘ Update cancelled by user");
            return null;
        }

        $result = $this->api_request('/method/Customer_Update', 'POST', [
            'args' => [
                'CustomerID' => $customer_id,
                'Email' => $new_email
            ]
        ]);

        if ($result['http_code'] === 200) {
            $this->print_success("✓ Customer email updated successfully!");
            $this->print_info("Response: " . json_encode($result['data'], JSON_PRETTY_PRINT));
            return $result['data'];
        } else {
            $this->print_error("✗ Customer update failed!");
            $this->print_error("HTTP Code: " . $result['http_code']);
            $this->print_error("Response: " . $result['response']);
            return false;
        }
    }

    /**
     * Test 5: Create test customer (WRITE operation)
     */
    public function test_customer_create($test_data) {
        $this->print_header("Test 5: Create Test Customer (WRITE - Careful!)");
        $this->print_warning("⚠ This will CREATE a real customer in Evosus");
        $this->print_info("Name: " . $test_data['Name']);
        $this->print_info("Email: " . $test_data['Email']);

        echo "\n" . COLOR_YELLOW . "Proceed with creation? (yes/no): " . COLOR_RESET;
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        if (strtolower($line) !== 'yes') {
            $this->print_warning("⊘ Creation cancelled by user");
            return null;
        }

        $result = $this->api_request('/method/Customer_Add', 'POST', [
            'args' => $test_data
        ]);

        if ($result['http_code'] === 200) {
            $this->print_success("✓ Test customer created successfully!");
            $this->print_info("Customer ID: " . ($result['data']['CustomerID'] ?? 'N/A'));
            $this->print_info("Response: " . json_encode($result['data'], JSON_PRETTY_PRINT));
            return $result['data'];
        } else {
            $this->print_error("✗ Customer creation failed!");
            $this->print_error("HTTP Code: " . $result['http_code']);
            $this->print_error("Response: " . $result['response']);
            return false;
        }
    }

    // Helper output methods
    private function print_header($text) {
        echo "\n" . COLOR_BLUE . str_repeat("=", 60) . COLOR_RESET . "\n";
        echo COLOR_BLUE . $text . COLOR_RESET . "\n";
        echo COLOR_BLUE . str_repeat("=", 60) . COLOR_RESET . "\n";
    }

    private function print_success($text) {
        echo COLOR_GREEN . $text . COLOR_RESET . "\n";
    }

    private function print_error($text) {
        echo COLOR_RED . $text . COLOR_RESET . "\n";
    }

    private function print_warning($text) {
        echo COLOR_YELLOW . $text . COLOR_RESET . "\n";
    }

    private function print_info($text) {
        echo "  " . $text . "\n";
    }
}

// =============================================================================
// MAIN EXECUTION
// =============================================================================

echo COLOR_BLUE . "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         Evosus API Direct Connection Test                   ║\n";
echo "║         Safe Read/Write Testing Before WordPress            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo COLOR_RESET . "\n";

// Check if credentials provided via command line
if ($argc === 3) {
    $company_sn = $argv[1];
    $ticket = $argv[2];
    echo COLOR_GREEN . "✓ Using credentials from command line arguments\n" . COLOR_RESET;
} else {
    // Prompt for credentials
    echo "Enter your Evosus API credentials:\n";
    echo COLOR_YELLOW . "CompanySN: " . COLOR_RESET;
    $company_sn = trim(fgets(STDIN));

    echo COLOR_YELLOW . "Ticket: " . COLOR_RESET;
    $ticket = trim(fgets(STDIN));
}

if (empty($company_sn) || empty($ticket)) {
    echo COLOR_RED . "✗ Error: CompanySN and Ticket are required!\n" . COLOR_RESET;
    echo "\nUsage: php test-evosus-api.php [CompanySN] [Ticket]\n";
    echo "   Or: php test-evosus-api.php (interactive mode)\n\n";
    exit(1);
}

$tester = new Evosus_API_Tester($company_sn, $ticket);

// =============================================================================
// RUN TESTS
// =============================================================================

echo "\n" . COLOR_BLUE . "Starting API tests...\n" . COLOR_RESET . "\n";

// Test 1: Connection
if (!$tester->test_connection()) {
    echo COLOR_RED . "\n✗ Connection test failed. Check credentials and try again.\n" . COLOR_RESET;
    exit(1);
}

echo "\n" . COLOR_GREEN . "Press Enter to continue to next test..." . COLOR_RESET;
fgets(STDIN);

// Test 2: Search for existing customer (safe, read-only)
$test_email = 'test@example.com';
echo "\n" . COLOR_YELLOW . "Enter email to search for (or press Enter for '{$test_email}'): " . COLOR_RESET;
$input = trim(fgets(STDIN));
if (!empty($input)) {
    $test_email = $input;
}

$customer = $tester->test_customer_search($test_email);

echo "\n" . COLOR_GREEN . "Press Enter to continue to next test..." . COLOR_RESET;
fgets(STDIN);

// Test 3: Check inventory item (safe, read-only)
$test_sku = 'EF-161-A';
echo "\n" . COLOR_YELLOW . "Enter SKU to check (or press Enter for '{$test_sku}'): " . COLOR_RESET;
$input = trim(fgets(STDIN));
if (!empty($input)) {
    $test_sku = $input;
}

$item = $tester->test_inventory_check($test_sku);

// Optional: Test write operations
echo "\n" . COLOR_YELLOW . str_repeat("-", 60) . COLOR_RESET . "\n";
echo COLOR_YELLOW . "OPTIONAL WRITE TESTS (modifies real data)\n" . COLOR_RESET;
echo COLOR_YELLOW . str_repeat("-", 60) . COLOR_RESET . "\n\n";

echo COLOR_YELLOW . "Do you want to run WRITE tests? (yes/no): " . COLOR_RESET;
$run_write_tests = trim(fgets(STDIN));

if (strtolower($run_write_tests) === 'yes') {

    // Test 4: Update customer email (if we found one)
    if (!empty($customer) && isset($customer['CustomerID'])) {
        echo "\n" . COLOR_GREEN . "Press Enter to test customer email update..." . COLOR_RESET;
        fgets(STDIN);

        echo "\n" . COLOR_YELLOW . "Enter new email address: " . COLOR_RESET;
        $new_email = trim(fgets(STDIN));

        if (!empty($new_email)) {
            $tester->test_customer_update($customer['CustomerID'], $new_email);
        }
    }

    // Test 5: Create new test customer
    echo "\n" . COLOR_GREEN . "Press Enter to test customer creation..." . COLOR_RESET;
    fgets(STDIN);

    $test_customer_data = [
        'Name' => 'API Test Customer - ' . date('Y-m-d H:i:s'),
        'Email' => 'apitest+' . time() . '@example.com',
        'Phone' => '555-0100',
        'Address1' => '123 Test Street',
        'City' => 'Test City',
        'State' => 'CA',
        'Zip' => '90210',
        'Country' => 'US'
    ];

    $tester->test_customer_create($test_customer_data);
}

// Summary
echo "\n" . COLOR_BLUE . str_repeat("=", 60) . COLOR_RESET . "\n";
echo COLOR_GREEN . "✓ API Testing Complete!\n" . COLOR_RESET;
echo COLOR_BLUE . str_repeat("=", 60) . COLOR_RESET . "\n\n";

echo COLOR_YELLOW . "Summary:\n" . COLOR_RESET;
echo "  • Connection test: " . COLOR_GREEN . "Passed\n" . COLOR_RESET;
echo "  • Customer search: " . COLOR_GREEN . "Completed\n" . COLOR_RESET;
echo "  • Inventory check: " . COLOR_GREEN . "Completed\n" . COLOR_RESET;
if (strtolower($run_write_tests) === 'yes') {
    echo "  • Write operations: " . COLOR_GREEN . "Tested\n" . COLOR_RESET;
}

echo "\n" . COLOR_GREEN . "You can now proceed to WordPress plugin testing with confidence!\n" . COLOR_RESET . "\n";
