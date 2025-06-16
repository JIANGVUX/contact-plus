<?php
if (!defined('ABSPATH')) exit;

add_action('plugins_loaded', function () {
    if (get_transient('contact_plus_license_checked') === 'yes') return;

    $license_key = get_option('contact_plus_license_key');
    $domain = $_SERVER['HTTP_HOST'];

    if (!$license_key) {
        wp_die('Plugin chưa được kích hoạt. Thiếu mã kích hoạt.');
    }

    $url = 'https://script.google.com/macros/s/AKfycbwdkbBHu3AI0ghcoo7MIWTTLizX9f03Ye4dyqcufys3nMyL0JVXZqUsMD2_43V5QmmQ/exec';
    $check_url = $url . '?license=' . urlencode($license_key) . '&domain=' . urlencode($domain);

    $resp = wp_remote_get($check_url);
    if (is_wp_error($resp) || wp_remote_retrieve_body($resp) !== 'VALID') {
        wp_die('Plugin chưa được kích hoạt hợp lệ.');
    }

    set_transient('contact_plus_license_checked', 'yes', 12 * HOUR_IN_SECONDS);
}, 1); // priority 1 để chạy sớm nhất
