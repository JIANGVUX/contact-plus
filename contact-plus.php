<?php
/**
 * Plugin Name: Contact Plus
 * Description: Plugin contact plus - tạo nút liên hệ cho WordPress chuyên nghiệp
 * Version: 1.0.3
 * Author: JiangVux
 */

if (!defined('ABSPATH')) exit;

if (file_exists(plugin_dir_path(__FILE__) . 'config.php')) {
    require_once plugin_dir_path(__FILE__) . 'config.php';
} else {
    error_log('[Contact Plus] Missing config.php');
}

function contact_plus_get_api_url() {
    return defined('CONTACT_PLUS_LICENSE_API')
        ? CONTACT_PLUS_LICENSE_API
        : 'https://script.google.com/macros/s/AKfycbzdBovh2y1PRa_SKpbmQt9TvaPri0a25e-cI4PVHJt5Ahb2fSBhC6bgTZVORLx-qqKw/exec';
}


require plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    defined('CONTACT_PLUS_UPDATE_URL')
        ? CONTACT_PLUS_UPDATE_URL
        : 'https://github.com/JIANGVUX/contact-plus/',
    __FILE__,
    'contact-plus'
);

register_activation_hook(__FILE__, 'contact_plus_log_activation');

function contact_plus_log_activation() {
    $site = parse_url(get_site_url(), PHP_URL_HOST);
    $version = get_plugin_data(__FILE__)['Version'];
    $update_url = contact_plus_get_api_url();

    wp_remote_post($update_url, [
        'body' => [
            'action' => 'log_install',
            'domain' => $site,
            'version' => $version,
        ]
    ]);
}


add_action('admin_menu', function() {
    add_menu_page('Liên Hệ', 'Liên Hệ', 'manage_options', 'contact-plus', 'contact_plus_settings_page','dashicons-format-chat
');
});

function contact_plus_settings_page() {
    $script_url = contact_plus_get_api_url();

    error_log('[DEBUG] script_url = ' . $script_url);

    $error_message = '';
    if (isset($_POST['license_key'])) {
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
            $error_message = '❌ Kích hoạt không thành công. Mã không hợp lệ hoặc bị từ chối.';
        }
    }

    echo '<div class="wrap"><h1>Thiết lập Liên Hệ</h1>';

    if (isset($_GET['activated']) && $_GET['activated'] === '1') {
        echo "<div class='notice notice-success is-dismissible'><p>Kích hoạt thành công!</p></div>";
    }

    if (!empty($error_message)) {
        echo "<div class='notice notice-error is-dismissible'><p>{$error_message}</p></div>";
    }

    $saved_license = get_option('contact_plus_license_key', '');
    echo '<form method="post"><h2>Mã kích hoạt</h2>
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
        'zalo_toggle_img','zalo_phone','zalo_call_img','zalo_img','messenger_img','shopee_img','viber_img','whatsapp_img','lazada_img','tiki_img',
        'zalo_link','messenger_link','shopee_link','viber_link','whatsapp_link','lazada_link','tiki_link',
        'zalo_position_side','zalo_position_offset'
    ];
    foreach ($fields as $field) {
        register_setting('contact_plus_settings', $field);
    }

    add_settings_section('main', 'Display Settings', null, 'contact-plus');

    foreach (['zalo','messenger','shopee','viber','whatsapp','lazada','tiki'] as $key) {
        add_settings_field($key.'_enable', "Show $key", function() use ($key) {
            echo '<input type="checkbox" name="'.$key.'_enable" value="1" ' . checked(get_option($key.'_enable'), '1', false) . '> Hiển thị '.ucfirst($key);
        }, 'contact-plus', 'main');

        add_settings_field($key.'_img', "Image $key", function() use ($key) {
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

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'toplevel_page_contact-plus') return;
    wp_enqueue_media();
    wp_add_inline_script('jquery-core', "
        jQuery(document).ready(function($){
            $('.select-media').click(function(e){
                e.preventDefault();
                let target = $(this).data('target');
                const frame = wp.media({
                    title: 'Chọn ảnh',
                    button: { text: 'Chọn ảnh này' },
                    multiple: false
                });
                frame.on('select', function(){
                    const url = frame.state().get('selection').first().toJSON().url;
                    $('#' + target).val(url);
                });
                frame.open();
            });
        });
    ");
});

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('contact-plus-style', plugins_url('assets/css/contact-plus.css', __FILE__));
    wp_enqueue_script('contact-plus-script', plugins_url('assets/js/contact-plus.js', __FILE__), [], null, true);
});

add_action('wp_footer', function() {
    if (!get_option('contact_plus_license_key')) return;

    $phone = esc_attr(get_option('zalo_phone'));
    $side = esc_attr(get_option('zalo_position_side', 'right'));
    $bottom = intval(get_option('zalo_position_offset', 90));
    $position_css = $side === 'left' ? 'left:12px;right:auto;' : 'right:12px;left:auto;';
    $style_attr = $position_css . " bottom:{$bottom}px;";

    $default_imgs = [
        'zalo' => 'default-zalo.png',
        'messenger' => 'default-messenger.png',
        'shopee' => 'default-shopee.png',
        'viber' => 'default-viber.png',
        'whatsapp' => 'default-whatsapp.png',
        'lazada' => 'default-lazada.png',
        'tiki' => 'default-tiki.png',
        'call' => 'default-call.png',
        'toggle' => 'default-toggle.png',
    ];

    $toggle_img = esc_url(get_option('zalo_toggle_img') ?: plugins_url('assets/images/' . $default_imgs['toggle'], __FILE__));
    $call_img = esc_url(get_option('zalo_call_img') ?: plugins_url('assets/images/' . $default_imgs['call'], __FILE__));

    echo "<div class='zalo-hotline' style='{$style_attr}'>
            <div id='zalo-toggle' class='zalo-main-button' onclick='toggleZaloOptions(true)'>
                <img src='{$toggle_img}' alt='Zalo Toggle' />
            </div>
            <div id='zalo-options' class='zalo-options'>
                <a href='tel:{$phone}' target='_blank'><div class='zalo-option'><img src='{$call_img}' alt='Call' /></div></a>";

    foreach (['zalo','messenger','shopee','viber','whatsapp','lazada','tiki'] as $btn) {
        if (get_option($btn.'_enable') === '1') {
            $img = esc_url(get_option($btn.'_img') ?: plugins_url('assets/images/' . $default_imgs[$btn], __FILE__));
            $link = esc_url(get_option($btn.'_link'));
            if ($btn === 'zalo' && empty($link)) {
                $phone_raw = preg_replace('/[^0-9]/', '', get_option('zalo_phone'));
                $link = "https://zalo.me/{$phone_raw}";
            }
            echo "<a href='{$link}' target='_blank'><div class='zalo-option'><img src='{$img}' alt='{$btn}' /></div></a>";
        }
    }

    echo "<div class='zalo-option' onclick='toggleZaloOptions(false)'>❌</div>
            </div>
        </div>";
}, 100);

add_action('upgrader_process_complete', 'contact_plus_log_update', 10, 2);

function contact_plus_log_update($upgrader_object, $options) {
    if (
        $options['action'] === 'update' &&
        $options['type'] === 'plugin' &&
        isset($options['plugins']) &&
        in_array(plugin_basename(__FILE__), $options['plugins'])
    ) {
        $site = parse_url(get_site_url(), PHP_URL_HOST); 
        $version = get_plugin_data(__FILE__)['Version'];
        $update_url = contact_plus_get_api_url();

        wp_remote_post($update_url, [
            'body' => [
                'action' => 'log_update',
                'domain' => $site,
                'version' => $version,
            ]
        ]);
    }
}

add_action('plugins_loaded', function () {
    if (!get_option('contact_plus_installed') && get_option('contact_plus_license_key')) {
        contact_plus_log_activation(); 
        update_option('contact_plus_installed', 1);
    }
});

