<?php
namespace AICF\Security;

if (!defined('ABSPATH')) {
    exit;
}

class SecurityManager {

    const NONCE_ACTION = 'aicf_admin_nonce';

    public static function get_encryption_key() {
        if (defined('AICF_ENCRYPTION_KEY') && !empty(AICF_ENCRYPTION_KEY)) {
            return AICF_ENCRYPTION_KEY;
        }

        $key = get_option('aicf_encryption_key');
        if (!$key) {
            $key = wp_generate_password(64, true, true);
            update_option('aicf_encryption_key', $key);
        }
        return $key;
    }

    public static function encrypt($plain_text) {
        if (empty($plain_text)) return '';
        $key = substr(hash('sha256', self::get_encryption_key()), 0, 32);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($plain_text, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt($encrypted_text) {
        if (empty($encrypted_text)) return '';
        $data = base64_decode($encrypted_text);
        if (strlen($data) < 17) return '';
        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);
        $key = substr(hash('sha256', self::get_encryption_key()), 0, 32);
        return openssl_decrypt($cipher, 'aes-256-cbc', $key, 0, $iv);
    }

    public static function verify_nonce($nonce, $action = self::NONCE_ACTION) {
        return wp_verify_nonce($nonce, $action);
    }

    public static function check_capability($capability = 'manage_options') {
        return current_user_can($capability);
    }
}