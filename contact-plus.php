<?php
/**
 * Plugin Name: Contact Plus
 * Description: Plugin hiển thị nút liên hệ nổi có tùy chỉnh thiết lập
 * Version: 2.4.5
 * Author: JiangVux
 */

if (!defined('ABSPATH')) exit;

require plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/JIANGVUX/contact-plus/',
    __FILE__,
    'contact-plus'
);

add_action('admin_menu', function() {
    add_menu_page('Liên Hệ', 'Liên Hệ', 'manage_options', 'contact-plus', 'contact_plus_settings_page');
});

function contact_plus_settings_page() {
    $script_url = 'https://script.google.com/macros/s/AKfycbwdkbBHu3AI0ghcoo7MIWTTLizX9f03Ye4dyqcufys3nMyL0JVXZqUsMD2_43V5QmmQ/exec';

    if (isset($_POST['license_key'])) {
        if (!current_user_can('manage_options')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        if (!check_admin_referer('contact_plus_activate')) {
            wp_die('Xác thực không hợp lệ.');
        }

        $license = sanitize_text_field($_POST['license_key']);
        $domain = $_SERVER['HTTP_HOST'];

        $full_url = $script_url . '?license=' . urlencode($license) . '&domain=' . urlencode($domain);
        error_log('[Contact Plus] License check URL: ' . $full_url);

        $response = wp_remote_get($full_url);
        $body = !is_wp_error($response) ? wp_remote_retrieve_body($response) : '';

        if ($body === 'VALID') {
            update_option('contact_plus_license_key', $license);
            wp_safe_redirect(admin_url('admin.php?page=contact-plus&activated=1'));
            exit;
        } else {
            add_action('admin_notices', function() {
                echo "<div class='notice notice-error is-dismissible'><p>Kích hoạt không thành công. Mã không hợp lệ hoặc bị từ chối.</p></div>";
            });
        }
    }

    echo '<div class="wrap"><h1>Thiết lập Liên Hệ</h1>';

    if (isset($_GET['activated']) && $_GET['activated'] === '1') {
        echo "<div class='notice notice-success is-dismissible'><p>Kích hoạt thành công!</p></div>";
    }

    $saved_license = get_option('contact_plus_license_key', '');
    echo '<form method="post">';
    wp_nonce_field('contact_plus_activate');
    echo '<h2>Mã kích hoạt</h2>
        <input name="license_key" value="' . esc_attr($saved_license) . '" placeholder="Nhập mã kích hoạt" style="width:300px;">
        <button type="submit" class="button button-primary">Kích hoạt</button>
        <div style="margin-top:12px; color:#0073aa; font-weight:600;">Hướng dẫn lấy mã kích hoạt tại: Jiangvux.weebly.com</div>
    </form><hr>';

    if ($saved_license) {
        echo '<h2>Cấu hình</h2><form method="post" action="options.php">';
        settings_fields('contact_plus_settings');
        do_settings_sections('contact-plus');
        submit_button('Lưu thay đổi');
        echo '</form>';
    }

    echo '</div>';
}

add_action('admin_init', function() {
    $fields = [
        'zalo_enable','messenger_enable','shopee_enable','viber_enable','whatsapp_enable','lazada_enable','tiki_enable',
        'zalo_toggle_img','zalo_call_img','zalo_zalo_img',
        'messenger_img','shopee_img','viber_img','whatsapp_img','lazada_img','tiki_img',
        'zalo_link','messenger_link','shopee_link','viber_link','whatsapp_link','lazada_link','tiki_link',
        'zalo_position_side','zalo_position_offset','zalo_phone'
    ];
    foreach ($fields as $field) {
        register_setting('contact_plus_settings', $field);
    }

    add_settings_section('main', 'Cấu hình hiển thị', null, 'contact-plus');

    foreach (['zalo','messenger','shopee','viber','whatsapp','lazada','tiki'] as $key) {
        add_settings_field($key.'_enable', "Bật $key", function() use ($key) {
            echo '<input type="checkbox" name="'.$key.'_enable" value="1" ' . checked(get_option($key.'_enable'), '1', false) . '> Hiển thị '.ucfirst($key);
        }, 'contact-plus', 'main');

        add_settings_field($key.'_img', "Ảnh $key", function() use ($key) {
            $field = $key.'_img';
            $value = esc_attr(get_option($field));
            echo "<input type='text' name='{$field}' id='{$field}' value='{$value}' size='60'>
                  <button class='button select-media' data-target='{$field}'>Chọn ảnh</button>";
        }, 'contact-plus', 'main');

        add_settings_field($key.'_link', "Link $key", function() use ($key) {
            echo '<input type="text" name="'.$key.'_link" value="' . esc_attr(get_option($key.'_link')) . '" size="60">';
        }, 'contact-plus', 'main');
    }

    add_settings_field('zalo_toggle_img', 'Ảnh Toggle', function() {
        $field = 'zalo_toggle_img';
        $value = esc_attr(get_option($field));
        echo "<input type='text' name='{$field}' id='{$field}' value='{$value}' size='60'>
              <button class='button select-media' data-target='{$field}'>Chọn ảnh</button>";
    }, 'contact-plus', 'main');

    add_settings_field('zalo_call_img', 'Ảnh Gọi', function() {
        $field = 'zalo_call_img';
        $value = esc_attr(get_option($field));
        echo "<input type='text' name='{$field}' id='{$field}' value='{$value}' size='60'>
              <button class='button select-media' data-target='{$field}'>Chọn ảnh</button>";
    }, 'contact-plus', 'main');

    add_settings_field('zalo_phone', 'Số điện thoại', function() {
        echo '<input type="text" name="zalo_phone" value="' . esc_attr(get_option('zalo_phone')) . '">';
    }, 'contact-plus', 'main');

    add_settings_field('zalo_position_side', 'Vị trí hiển thị', function() {
        $side = get_option('zalo_position_side', 'right');
        echo '<select name="zalo_position_side">
            <option value="left"' . selected($side, 'left', false) . '>Trái</option>
            <option value="right"' . selected($side, 'right', false) . '>Phải</option>
        </select>';
    }, 'contact-plus', 'main');

    add_settings_field('zalo_position_offset', 'Cách đáy (px)', function() {
        echo '<input type="number" name="zalo_position_offset" value="' . esc_attr(get_option('zalo_position_offset', 90)) . '" min="0">';
    }, 'contact-plus', 'main');
});