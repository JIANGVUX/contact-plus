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
PucFactory::buildUpdateChecker(
    'https://github.com/JIANGVUX/contact-plus/',
    __FILE__,
    'contact-plus'
);

/* ===== ADMIN MENU ===== */
add_action('admin_menu', function() {
    add_menu_page('Contact Plus', 'Contact Plus', 'manage_options', 'contact-plus', 'contact_plus_settings_page');
});

/* ===== SETTINGS PAGE ===== */
function contact_plus_settings_page() {
    ?>
    <div class="wrap">
        <h1>Contact Plus</h1>

        <!-- Form kích hoạt license -->
        <?php
        if (isset($_POST['license_key'])) {
            check_admin_referer('cp_activate');
            if (!current_user_can('manage_options')) wp_die('No access');
            $license = sanitize_text_field($_POST['license_key']);
            $domain = $_SERVER['HTTP_HOST'];
            $script_url = 'https://.../exec';
            $resp = wp_remote_get("$script_url?license=".urlencode($license)."&domain=".urlencode($domain));
            $body = wp_remote_retrieve_body($resp);
            if ($body==='VALID') {
                update_option('contact_plus_license', $license);
                echo '<div class="notice notice-success"><p>Activated!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Invalid license.</p></div>';
            }
        }
        ?>
        <form method="post">
            <?php wp_nonce_field('cp_activate'); ?>
            <table class="form-table">
                <tr>
                    <th><label>Mã kích hoạt</label></th>
                    <td><input type="text" name="license_key" value="<?php echo esc_attr(get_option('contact_plus_license','')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button('Kích hoạt'); ?>
        </form>

        <?php if (get_option('contact_plus_license')): ?>
        <!-- Form cấu hình -->
        <form method="post" action="options.php">
            <?php
            settings_fields('contact_plus_settings');
            do_settings_sections('contact-plus');
            submit_button('Lưu thay đổi');
            ?>
        </form>
        <?php endif; ?>
    </div>
    <?php
}

/* ===== REGISTER SETTINGS ===== */
add_action('admin_init', function() {
    $fields = [
        'zalo_enable','zalo_img','zalo_link',
        'zalo_toggle_img','zalo_call_img','zalo_phone','zalo_position_side','zalo_position_offset',
        // other platforms: messenger, shopee...
    ];
    foreach ($fields as $f) register_setting('contact_plus_settings', $f);
    add_settings_section('main','Cấu hình','', 'contact-plus');
    foreach (['zalo'] as $key) {
        add_settings_field("{$key}_enable","Bật $key",'cp_field_checkbox','contact-plus','main',[$key]);
        add_settings_field("{$key}_img","Ảnh {$key}",'cp_field_media','contact-plus','main',[$key]);
        add_settings_field("{$key}_link","Link {$key}",'cp_field_text','contact-plus','main',[$key]);
    }
    // Toggle, call, phone, offset
    add_settings_field('zalo_toggle_img','Ảnh Toggle','cp_field_media','contact-plus','main',['zalo_toggle_img']);
    add_settings_field('zalo_call_img','Ảnh Gọi','cp_field_media','contact-plus','main',['zalo_call_img']);
    add_settings_field('zalo_phone','Số điện thoại','cp_field_text','contact-plus','main',['zalo_phone']);
    add_settings_field('zalo_position_side','Vị trí','cp_field_select','contact-plus','main',['zalo_position_side',['left'=>'Trái','right'=>'Phải']]);
    add_settings_field('zalo_position_offset','Cách đáy','cp_field_text','contact-plus','main',['zalo_position_offset']);
});

/* ===== FIELD CALLBACKS ===== */
function cp_field_checkbox($args) {
    $key = $args[0];
    echo "<input type='checkbox' name='$key' value='1' ".checked(get_option($key),1,false).">";
}
function cp_field_text($args) {
    list($key,$opt)= $args;
    $val = esc_attr(get_option($key,''));
    echo "<input type='text' name='$key' value='$val' />";
}
function cp_field_select($args) {
    list($key,$opts)= $args;
    $sel = get_option($key,'');
    echo "<select name='$key'>";
    foreach ($opts as $v=>$l) echo "<option value='$v' ".selected($sel,$v,false).">$l</option>";
    echo "</select>";
}
function cp_field_media($args) {
    $key = $args[0];
    $val = esc_attr(get_option($key,''));
    echo "<input type='text' id='$key' name='$key' value='$val' size='60' />";
    echo " <button class='button select-media' data-target='$key'>Chọn ảnh</button>";
}

/* ===== ENQUEUE MEDIA SCRIPT ===== */
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook!=='toplevel_page_contact-plus') return;
    wp_enqueue_media();
    wp_register_script('cp_admin', '');
    wp_enqueue_script('cp_admin');
    wp_add_inline_script('cp_admin', "
        jQuery(function($){
            $('.select-media').click(function(e){
                e.preventDefault();
                let trg = $(this).data('target');
                let frame = wp.media({multiple:false});
                frame.on('select', function(){
                    let url = frame.state().get('selection').first().toJSON().url;
                    $('#' + trg).val(url);
                });
                frame.open();
            });
        });
    ");
});
