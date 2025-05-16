<?php
/**
 * Plugin Name: Rokku Maintenance Mode
 * Description: Enables a maintenance mode with customizable logo, headline, and message. Displays admin notice when active.
 * Version: 1.2
 * Author: Mark Bridgeman
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rokku-maintenance-mode
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('ROKKU_MM_VERSION', '1.2');
define('ROKKU_MM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ROKKU_MM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'RokkuMaintenanceMode\\';
    $base_dir = ROKKU_MM_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize the plugin
function rokku_mm_init() {
    // Load text domain
    load_plugin_textdomain('rokku-maintenance-mode', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Initialize main plugin class
    $plugin = new \RokkuMaintenanceMode\MaintenanceMode();
    $plugin->init();
}
add_action('plugins_loaded', 'rokku_mm_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    // Add default options
    add_option('mm_enabled', 0);
    add_option('mm_headline', __('Site Maintenance', 'rokku-maintenance-mode'));
    add_option('mm_message', __('We are currently performing scheduled maintenance. We will be back online shortly!', 'rokku-maintenance-mode'));
    
    // Clear any existing caches
    delete_transient('rokku_mm_status');
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clear any caches
    delete_transient('rokku_mm_status');
});