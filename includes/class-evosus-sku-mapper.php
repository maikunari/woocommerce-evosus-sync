<?php
/**
 * Evosus SKU Mapper
 * Manages SKU mappings between WooCommerce and Evosus
 */

class Evosus_SKU_Mapper {

    private static $instance = null;
    private $table_name;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'evosus_sku_mappings';

        // Validate table name to prevent SQL injection
        $this->validate_table_name();
    }

    /**
     * Validate table name matches expected pattern
     */
    private function validate_table_name() {
        global $wpdb;
        $expected_table = $wpdb->prefix . 'evosus_sku_mappings';

        if ($this->table_name !== $expected_table) {
            wp_die('Invalid table name for SKU mapper');
        }
    }

    /**
     * Add or update SKU mapping
     */
    public function add_mapping($wc_sku, $evosus_sku, $product_id = null) {
        global $wpdb;

        // Check if mapping already exists
        $existing = $this->get_mapping($wc_sku);

        if ($existing) {
            // Update existing
            return $wpdb->update(
                $this->table_name,
                [
                    'evosus_sku' => $evosus_sku,
                    'product_id' => $product_id
                ],
                ['wc_sku' => $wc_sku],
                ['%s', '%d'],
                ['%s']
            );
        } else {
            // Insert new
            return $wpdb->insert(
                $this->table_name,
                [
                    'wc_sku' => $wc_sku,
                    'evosus_sku' => $evosus_sku,
                    'product_id' => $product_id,
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%s', '%d', '%s']
            );
        }
    }

    /**
     * Get Evosus SKU from WooCommerce SKU
     */
    public function get_mapping($wc_sku) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE wc_sku = %s",
                $wc_sku
            )
        );
    }

    /**
     * Get mapped Evosus SKU (returns Evosus SKU if mapped, otherwise original)
     */
    public function get_evosus_sku($wc_sku) {
        $mapping = $this->get_mapping($wc_sku);
        return $mapping ? $mapping->evosus_sku : $wc_sku;
    }

    /**
     * Delete mapping
     */
    public function delete_mapping($wc_sku) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            ['wc_sku' => $wc_sku],
            ['%s']
        );
    }

    /**
     * Get all mappings
     */
    public function get_all_mappings($limit = 100, $offset = 0) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
    }

    /**
     * Search mappings
     */
    public function search_mappings($search_term) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name}
                WHERE wc_sku LIKE %s OR evosus_sku LIKE %s
                ORDER BY created_at DESC
                LIMIT 50",
                '%' . $wpdb->esc_like($search_term) . '%',
                '%' . $wpdb->esc_like($search_term) . '%'
            )
        );
    }

    /**
     * Get mappings by product ID
     */
    public function get_mappings_by_product($product_id) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE product_id = %d",
                $product_id
            )
        );
    }

    /**
     * Import mappings from CSV (with path traversal protection)
     */
    public function import_from_csv($file_path) {
        // Security: Validate file path to prevent path traversal
        $file_path = realpath($file_path);

        if ($file_path === false) {
            return [
                'success' => false,
                'message' => 'Invalid file path'
            ];
        }

        // Security: Ensure file is within allowed upload directory
        $upload_dir = wp_upload_dir();
        $allowed_base = realpath($upload_dir['basedir']);

        if (strpos($file_path, $allowed_base) !== 0) {
            return [
                'success' => false,
                'message' => 'File path not allowed'
            ];
        }

        if (!file_exists($file_path)) {
            return [
                'success' => false,
                'message' => 'File not found'
            ];
        }

        // Security: Validate file extension
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        if ($file_extension !== 'csv') {
            return [
                'success' => false,
                'message' => 'Only CSV files are allowed'
            ];
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return [
                'success' => false,
                'message' => 'Cannot open file'
            ];
        }

        $imported = 0;
        $errors = [];

        // Skip header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 2) {
                $wc_sku = trim($data[0]);
                $evosus_sku = trim($data[1]);
                $product_id = isset($data[2]) ? intval($data[2]) : null;

                if (!empty($wc_sku) && !empty($evosus_sku)) {
                    $result = $this->add_mapping($wc_sku, $evosus_sku, $product_id);
                    if ($result) {
                        $imported++;
                    } else {
                        $errors[] = "Failed to import: {$wc_sku} -> {$evosus_sku}";
                    }
                }
            }
        }

        fclose($handle);

        return [
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        ];
    }

    /**
     * Export mappings to CSV
     */
    public function export_to_csv() {
        $mappings = $this->get_all_mappings(999999, 0);

        $csv_data = "WooCommerce SKU,Evosus SKU,Product ID,Created At\n";

        foreach ($mappings as $mapping) {
            $csv_data .= sprintf(
                '"%s","%s","%s","%s"' . "\n",
                $mapping->wc_sku,
                $mapping->evosus_sku,
                $mapping->product_id ?: '',
                $mapping->created_at
            );
        }

        return $csv_data;
    }

    /**
     * Count total mappings
     */
    public function count_mappings() {
        global $wpdb;

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }

    /**
     * Bulk delete mappings
     */
    public function bulk_delete($mapping_ids) {
        global $wpdb;

        if (empty($mapping_ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($mapping_ids), '%d'));

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE id IN ($placeholders)",
                $mapping_ids
            )
        );
    }
}
