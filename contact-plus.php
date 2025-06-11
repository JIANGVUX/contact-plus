<?php
/**
 * Plugin Name: Contact Plus
 * Description: Plugin hiển thị nút liên hệ nổi có tùy chỉnh thiết lập
 * Version: 2.1.6
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
    $script_url = 'https://script.google.com/macros/s/AKfycbxJPjsXB5p2MY1tiG-bQWekyFQOv6R-IB74FwV2RsE9AWzjTnLYLcM2ttOF7YacFWnK/exec';

    if (isset($_POST['license_key'])) {
        $license = sanitize_text_field($_POST['license_key']);
        $domain = $_SERVER['HTTP_HOST'];
        $response = wp_remote_get($script_url . '?license=' . urlencode($license) . '&domain=' . urlencode($domain));
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
    echo '<form method="post"><h2>Mã kích hoạt</h2>
        <input name="license_key" value="' . esc_attr($saved_license) . '" placeholder="Nhập mã kích hoạt" style="width:300px;">
        <button type="submit" class="button button-primary">Kích hoạt</button>
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
        'zalo_enable','messenger_enable','shopee_enable',
        'zalo_toggle_img','zalo_phone','zalo_call_img','zalo_zalo_img','messenger_img','shopee_img',
        'messenger_link','shopee_link',
        'zalo_position_side','zalo_position_offset'
    ];
    foreach ($fields as $field) {
        register_setting('contact_plus_settings', $field);
    }

    add_settings_section('main', 'Cấu hình hiển thị', null, 'contact-plus');

    add_settings_field('zalo_enable', 'Bật Zalo', function() {
        echo '<input type="checkbox" name="zalo_enable" value="1" ' . checked(get_option('zalo_enable'), '1', false) . '> Hiển thị Zalo';
    }, 'contact-plus', 'main');

    add_settings_field('messenger_enable', 'Bật Messenger', function() {
        echo '<input type="checkbox" name="messenger_enable" value="1" ' . checked(get_option('messenger_enable'), '1', false) . '> Hiển thị Messenger';
    }, 'contact-plus', 'main');

    add_settings_field('shopee_enable', 'Bật Shopee', function() {
        echo '<input type="checkbox" name="shopee_enable" value="1" ' . checked(get_option('shopee_enable'), '1', false) . '> Hiển thị Shopee';
    }, 'contact-plus', 'main');

    $fields_img = [
        'zalo_toggle_img' => 'Ảnh nút chính',
        'zalo_call_img' => 'Ảnh gọi',
        'zalo_zalo_img' => 'Ảnh Zalo',
        'messenger_img' => 'Ảnh Messenger',
        'shopee_img' => 'Ảnh Shopee',
        'messenger_link' => 'Link Messenger',
        'shopee_link' => 'Link Shopee'
    ];

    foreach ($fields_img as $key => $label) {
        add_settings_field($key, $label, function() use ($key) {
            echo '<input type="text" name="' . $key . '" value="' . esc_attr(get_option($key)) . '" size="60">';
        }, 'contact-plus', 'main');
    }

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

add_action('wp_footer', function() {
    if (!get_option('contact_plus_license_key')) return;

    $toggle_img     = get_option('zalo_toggle_img');
    $call_img       = get_option('zalo_call_img');
    $zalo_img       = get_option('zalo_zalo_img');
    $messenger_img  = get_option('messenger_img');
    $shopee_img     = get_option('shopee_img');

    $toggle_img     = esc_url($toggle_img ?: plugins_url('assets/default-toggle.png', __FILE__));
    $call_img       = esc_url($call_img   ?: plugins_url('assets/default-call.png', __FILE__));
    $zalo_img       = esc_url($zalo_img   ?: plugins_url('assets/default-zalo.png', __FILE__));
    $messenger_img  = esc_url($messenger_img ?: plugins_url('assets/default-messenger.png', __FILE__));
    $shopee_img     = esc_url($shopee_img ?: plugins_url('assets/default-shopee.png', __FILE__));

    $messenger_link = esc_url(get_option('messenger_link'));
    $shopee_link    = esc_url(get_option('shopee_link'));

    $phone = esc_attr(get_option('zalo_phone'));
    $side = esc_attr(get_option('zalo_position_side', 'right'));
    $bottom = intval(get_option('zalo_position_offset', 90));

    $show_zalo = get_option('zalo_enable') === '1';
    $show_mess = get_option('messenger_enable') === '1';
    $show_shop = get_option('shopee_enable') === '1';

    echo <<<HTML
<style>
.zalo-hotline {
    position: fixed;
    {$side}: 12px;
    bottom: {$bottom}px;
    z-index: 9999;
    display: flex;
    flex-direction: column-reverse;
    align-items: center;
    animation: fadeInUp 0.5s ease;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}
.zalo-main-button img {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    box-shadow: 0 8px 20px rgba(0,0,0,.25);
    cursor: pointer;
    transition: transform .3s, box-shadow .3s;
}
.zalo-main-button img:hover {
    transform: scale(1.1);
    box-shadow: 0 12px 30px rgba(0,0,0,0.3);
}
.zalo-options {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    opacity: 0;
    transform: translateY(20px);
    transition: all .3s ease;
    pointer-events: none;
}
.zalo-options.active {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}
.zalo-option {
    width: 52px;
    height: 52px;
    background: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 16px rgba(0,0,0,.2);
    cursor: pointer;
    transition: transform .25s ease, background .25s;
}
.zalo-option:hover {
    transform: scale(1.1);
    background: #f2f2f2;
}
.zalo-option img {
    width: 28px;
    height: 28px;
    object-fit: contain;
    transition: transform 0.3s;
}
.zalo-option img:hover {
    transform: rotate(10deg);
}
.zalo-main-button {
    position: relative;
}

.zalo-main-button::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100%;
    height: 100%;
    background: rgba(0, 136, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    z-index: 0;
    animation: wave-pulse 2.5s ease-out infinite;
}

@keyframes wave-pulse {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.5;
    }
    70% {
        transform: translate(-50%, -50%) scale(1.6);
        opacity: 0.2;
    }
    100% {
        transform: translate(-50%, -50%) scale(2.1);
        opacity: 0;
    }
}

.zalo-main-button img {
    position: relative;
    z-index: 1;
}

</style>
<div class="zalo-hotline">
  <div id="zalo-toggle" class="zalo-main-button" onclick="toggleZaloOptions(true)">
    <img src="{$toggle_img}" alt="Zalo Toggle" />
  </div>
  <div id="zalo-options" class="zalo-options">
    <a href="tel:{$phone}" target="_blank"><div class="zalo-option"><img src="{$call_img}" alt="Call" /></div></a>
HTML;
    if ($show_zalo) echo "<a href=\"https://zalo.me/{$phone}\" target=\"_blank\"><div class='zalo-option'><img src='{$zalo_img}' alt='Zalo' /></div></a>";
    if ($show_mess) echo "<a href=\"{$messenger_link}\" target=\"_blank\"><div class='zalo-option'><img src='{$messenger_img}' alt='Messenger' /></div></a>";
    if ($show_shop) echo "<a href=\"{$shopee_link}\" target=\"_blank\"><div class='zalo-option'><img src='{$shopee_img}' alt='Shopee' /></div></a>";
    echo <<<HTML
    <div class="zalo-option" onclick="toggleZaloOptions(false)">❌</div>
  </div>
</div>
<script>
function toggleZaloOptions(show) {
    document.getElementById('zalo-options').classList.toggle('active', show);
    document.getElementById('zalo-toggle').style.display = show ? 'none' : 'block';
}
</script>
HTML;
}, 100);
