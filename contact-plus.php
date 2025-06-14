<?php
/**
 * Plugin Name: Contact Plus
 * Description: Plugin hiển thị nút liên hệ nổi có tùy chỉnh thiết lập
 * Version: 2.4.6
 * Author: JiangVux (Updated)
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
    echo '<div class="wrap"><h1>Thiết lập Liên Hệ</h1>';

    echo '<form method="post" action="options.php">';
    settings_fields('contact_plus_settings');
    do_settings_sections('contact-plus');
    submit_button('Lưu thay đổi');
    echo '</form></div>';
}

add_action('admin_init', function() {
    $fields = [
        'zalo_enable', 'zalo_img', 'zalo_link', 'zalo_phone', 'zalo_position_side', 'zalo_position_offset'
    ];
    foreach ($fields as $field) {
        register_setting('contact_plus_settings', $field);
    }

    add_settings_section('main', 'Cấu hình', null, 'contact-plus');

    add_settings_field('zalo_enable', 'Bật Zalo', function() {
        echo '<input type="checkbox" name="zalo_enable" value="1" ' . checked(get_option('zalo_enable'), '1', false) . '> Hiển thị Zalo';
    }, 'contact-plus', 'main');

    add_settings_field('zalo_img', 'Hình ảnh Zalo', function() {
        $field = 'zalo_img';
        $value = esc_attr(get_option($field));
        echo "<input type='text' name='{$field}' id='{$field}' value='{$value}' size='60'>
              <button class='button select-media' data-target='{$field}'>Chọn ảnh</button>";
    }, 'contact-plus', 'main');

    add_settings_field('zalo_link', 'Link Zalo', function() {
        echo '<input type="text" name="zalo_link" value="' . esc_attr(get_option('zalo_link')) . '" size="60">';
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
        echo '<input type="number" name="zalo_position_offset" value="' . esc_attr(get_option('zalo_position_offset', 90)) . '">';
    }, 'contact-plus', 'main');
});

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'toplevel_page_contact-plus') return;
    wp_enqueue_media();
    wp_register_script('contact-plus-media', '');
    wp_enqueue_script('contact-plus-media');
    wp_add_inline_script('contact-plus-media', "
        jQuery(document).ready(function($){
            $('.select-media').click(function(e){
                e.preventDefault();
                let target = $(this).data('target');
                const frame = wp.media({
                    title: 'Chọn ảnh',
                    button: { text: 'Chọn' },
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

add_action('wp_footer', function() {
    if (get_option('zalo_enable') !== '1') return;
    $phone = esc_attr(get_option('zalo_phone'));
    $side = get_option('zalo_position_side', 'right');
    $offset = intval(get_option('zalo_position_offset', 90));
    $style = $side === 'left' ? "left:12px;" : "right:12px;";
    $style .= " bottom:{$offset}px;";
    $img = esc_url(get_option('zalo_img') ?: plugins_url('default-zalo.png', __FILE__));
    $link = esc_url(get_option('zalo_link') ?: ('https://zalo.me/' . preg_replace('/[^0-9]/', '', $phone)));

    echo "<div class='zalo-hotline' style='{$style}'>
            <a href='{$link}' target='_blank'><img src='{$img}' alt='Zalo' style='width:64px;height:auto;'></a>
          </div>";
}, 100);
