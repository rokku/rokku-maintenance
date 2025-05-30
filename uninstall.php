<?php
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('rokkmamo_enabled');
delete_option('rokkmamo_logo_id');
delete_option('rokkmamo_headline');
delete_option('rokkmamo_message');

// Delete transients
delete_transient('rokkmamo_status'); 