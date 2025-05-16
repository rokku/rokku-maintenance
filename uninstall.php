<?php
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('mm_enabled');
delete_option('mm_logo_id');
delete_option('mm_headline');
delete_option('mm_message');

// Delete transients
delete_transient('rokku_mm_status'); 