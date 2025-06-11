<?php
/**
 * Plugin Name: Contact Plus
 * Description: Plugin hiển thị nút liên hệ nổi có tùy chỉnh thiết lập 
 * Version: 2.2
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
    add_menu_page('Contact Plus', 'Contact Plus', 'manage_options', 'contact-plus', 'contact_plus_settings_page');
});

function contact_plus_settings_page() {
    echo '<div class="wrap"><h1>Thiết lập Contact Plus</h1><form method="post" action="options.php">';
    settings_fields('contact_plus_settings');
    do_settings_sections('contact-plus');
    submit_button('Lưu thay đổi');
    echo '</form></div>';
}

add_action('admin_init', function() {
    $fields = [
        'toggle_img','call_img','zalo_img','mess_img','shopee_img',
        'zalo_link','mess_link','shopee_link',
        'phone','side','bottom','button_size',
        'enable_zalo','enable_mess','enable_shopee'
    ];
    foreach ($fields as $f) register_setting('contact_plus_settings', $f);

    add_settings_section('main', 'Cài đặt hiển thị', null, 'contact-plus');

    $input = function($id, $label, $type='text') {
        add_settings_field($id, $label, function() use ($id, $type) {
            $v = esc_attr(get_option($id));
            echo "<input type='$type' name='$id' value='$v' size='50'>";
        }, 'contact-plus', 'main');
    };

    $check = function($id, $label) {
        add_settings_field($id, $label, function() use ($id) {
            echo "<input type='checkbox' name='$id' value='1'" . checked(get_option($id), 1, false) . '> Cho phép';
        }, 'contact-plus', 'main');
    };

    $input('toggle_img', 'Ảnh nút chính');
    $input('call_img', 'Ảnh nút gọi');
    $input('zalo_img', 'Ảnh Zalo');
    $input('mess_img', 'Ảnh Messenger');
    $input('shopee_img', 'Ảnh Shopee');
    $input('zalo_link', 'Link Zalo');
    $input('mess_link', 'Link Messenger');
    $input('shopee_link', 'Link Shopee');
    $input('phone', 'Số điện thoại');
    $input('button_size', 'Kích thước nút (px)', 'number');
    $input('bottom', 'Khoảng cách với đáy (px)', 'number');

    add_settings_field('side', 'Vị trí hiển thị', function() {
        $side = get_option('side','right');
        echo "<select name='side'><option value='left'".selected($side,'left',false).">Trái</option><option value='right'".selected($side,'right',false).">Phải</option></select>";
    }, 'contact-plus', 'main');

    $check('enable_zalo', 'Bật Zalo');
    $check('enable_mess', 'Bật Messenger');
    $check('enable_shopee', 'Bật Shopee');
});

add_action('wp_footer', function() {
    $img = [
        'main' => esc_url(get_option('toggle_img')),
        'call' => esc_url(get_option('call_img')),
        'zalo' => esc_url(get_option('zalo_img')),
        'mess' => esc_url(get_option('mess_img')),
        'shopee' => esc_url(get_option('shopee_img')),
    ];
    $link = [
        'call' => 'tel:' . esc_attr(get_option('phone')),
        'zalo' => esc_url(get_option('zalo_link')),
        'mess' => esc_url(get_option('mess_link')),
        'shopee' => esc_url(get_option('shopee_link')),
    ];
    $enable = [
        'zalo' => get_option('enable_zalo'),
        'mess' => get_option('enable_mess'),
        'shopee' => get_option('enable_shopee'),
    ];
    $side = esc_attr(get_option('side','right'));
    $bottom = intval(get_option('bottom',90));
    $size = intval(get_option('button_size', 60));

    echo "<style>
    .zalo-hotline{position:fixed;{$side}:12px;bottom:{$bottom}px;z-index:9999;display:flex;flex-direction:column-reverse;align-items:center;font-family:sans-serif}
    .zalo-main-button img{width:{$size}px;height:{$size}px;border-radius:50%;box-shadow:0 8px 20px rgba(0,0,0,0.25);transition:transform .3s ease;cursor:pointer}
    .zalo-main-button img:hover{transform:scale(1.1)}
    .zalo-options{display:flex;flex-direction:column;align-items:center;gap:10px;margin-bottom:12px;opacity:0;transform:translateY(20px);transition:all .3s ease;pointer-events:none}
    .zalo-options.active{opacity:1;transform:translateY(0);pointer-events:auto}
    .zalo-option{width:{$size}px;height:{$size}px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(0,0,0,.2);cursor:pointer;transition:transform .25s ease,background .25s ease}
    .zalo-option:hover{transform:scale(1.1);background:#f2f2f2}
    .zalo-option img{width:28px;height:28px;object-fit:contain}
    </style>
    <div class='zalo-hotline'>
      <div id='zalo-toggle' class='zalo-main-button' onclick='toggleZaloOptions(true)'>
        <img src='{$img['main']}' alt='toggle'>
      </div>
      <div id='zalo-options' class='zalo-options'>
        <a href='{$link['call']}' target='_blank'><div class='zalo-option'><img src='{$img['call']}' alt='call'></div></a>";
    foreach(['zalo','mess','shopee'] as $k) {
        if ($enable[$k]) echo "<a href='{$link[$k]}' target='_blank'><div class='zalo-option'><img src='{$img[$k]}' alt='{$k}'></div></a>";
    }
    echo "<div class='zalo-option' onclick='toggleZaloOptions(false)'>❌</div></div></div>
    <script>
    function toggleZaloOptions(show){
      document.getElementById('zalo-options').classList.toggle('active', show);
      document.getElementById('zalo-toggle').style.display = show ? 'none' : 'block';
    }
    </script>";
}, 100);
