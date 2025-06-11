<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

delete_option('contact_plus_license_key');
delete_option('zalo_toggle_img');
delete_option('zalo_phone');
delete_option('zalo_call_img');
delete_option('zalo_zalo_img');
delete_option('zalo_position_side');
delete_option('zalo_position_offset');
