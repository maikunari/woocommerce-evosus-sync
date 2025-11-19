<?php
/**
 * Evosus Helpers
 * Utility functions and helpers for the plugin
 */

class Evosus_Helpers {

    /**
     * Get country code to name mapping
     * Only includes USA and Canada as required
     */
    public static function get_country_mapping() {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
        ];
    }

    /**
     * Get country name from country code
     */
    public static function get_country_name($country_code) {
        $countries = self::get_country_mapping();
        return isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
    }

    /**
     * Format phone number for API
     */
    public static function format_phone_number($phone) {
        // Remove all non-numeric characters
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Validate email address
     */
    public static function validate_email($email) {
        return is_email($email);
    }

    /**
     * Sanitize SKU
     */
    public static function sanitize_sku($sku) {
        return sanitize_text_field(trim($sku));
    }

    /**
     * Check if credentials are configured
     */
    public static function has_credentials() {
        $company_sn = get_option('evosus_company_sn', '');
        $ticket = get_option('evosus_ticket', '');
        return !empty($company_sn) && !empty($ticket);
    }

    /**
     * Encrypt sensitive data using AES-256-GCM (authenticated encryption)
     */
    public static function encrypt($data) {
        if (!function_exists('openssl_encrypt')) {
            // Log error instead of using insecure fallback
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Evosus Sync: OpenSSL not available for encryption. Data not encrypted.');
            }
            return false;
        }

        $key = self::get_encryption_key();

        // Derive a proper 256-bit key using hash
        $key = hash('sha256', $key, true);

        // Generate random IV (12 bytes for GCM)
        $iv = openssl_random_pseudo_bytes(12);

        // Encrypt with authenticated encryption (GCM mode)
        $tag = '';
        $encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($encrypted === false) {
            return false;
        }

        // Return: base64(iv + tag + ciphertext)
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Decrypt sensitive data
     */
    public static function decrypt($data) {
        if (!function_exists('openssl_decrypt')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Evosus Sync: OpenSSL not available for decryption.');
            }
            return false;
        }

        $key = self::get_encryption_key();

        // Derive the same 256-bit key
        $key = hash('sha256', $key, true);

        $data = base64_decode($data);

        if ($data === false || strlen($data) < 28) { // 12 (iv) + 16 (tag) minimum
            return false;
        }

        // Extract components
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $decrypted !== false ? $decrypted : false;
    }

    /**
     * Get encryption key (stored in wp-config or generated)
     */
    private static function get_encryption_key() {
        if (defined('EVOSUS_ENCRYPTION_KEY')) {
            return EVOSUS_ENCRYPTION_KEY;
        }

        // Use WordPress salt as fallback
        return wp_salt('auth');
    }

    /**
     * Check if test mode is enabled
     */
    public static function is_test_mode() {
        return get_option('evosus_test_mode', '0') === '1';
    }

    /**
     * Get base API URL (configurable)
     */
    public static function get_api_base_url() {
        $custom_url = get_option('evosus_api_base_url', '');
        if (!empty($custom_url)) {
            return rtrim($custom_url, '/');
        }
        return 'https://cloud3.evosus.com/api';
    }

    /**
     * Format price for API
     */
    public static function format_price($price) {
        return number_format((float)$price, 2, '.', '');
    }

    /**
     * Get plugin version
     */
    public static function get_version() {
        return WC_EVOSUS_VERSION;
    }

    /**
     * Check if WooCommerce HPOS is enabled
     */
    public static function is_hpos_enabled() {
        if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil')) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }

    /**
     * Get order meta (compatible with HPOS)
     */
    public static function get_order_meta($order_id, $key, $single = true) {
        if (self::is_hpos_enabled()) {
            $order = wc_get_order($order_id);
            return $order ? $order->get_meta($key, $single) : '';
        }
        return get_post_meta($order_id, $key, $single);
    }

    /**
     * Update order meta (compatible with HPOS)
     */
    public static function update_order_meta($order_id, $key, $value) {
        if (self::is_hpos_enabled()) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_meta_data($key, $value);
                $order->save();
                return true;
            }
            return false;
        }
        return update_post_meta($order_id, $key, $value);
    }

    /**
     * Delete order meta (compatible with HPOS)
     */
    public static function delete_order_meta($order_id, $key) {
        if (self::is_hpos_enabled()) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->delete_meta_data($key);
                $order->save();
                return true;
            }
            return false;
        }
        return delete_post_meta($order_id, $key);
    }
}
