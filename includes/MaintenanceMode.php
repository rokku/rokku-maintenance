<?php
namespace RokkuMaintenanceMode;

/**
 * Main plugin class
 */
class MaintenanceMode {
    /**
     * Initialize the plugin
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('template_redirect', [$this, 'maybe_display_maintenance_page'], 1);
        add_action('admin_notices', [$this, 'admin_notice']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_maintenance_assets']);
        
        // Add REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('rokkmamo/v1', '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_maintenance_status'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }

    /**
     * Get maintenance mode status via REST API
     */
    public function get_maintenance_status() {
        return rest_ensure_response([
            'enabled' => $this->is_maintenance_mode_enabled()
        ]);
    }

    /**
     * Add settings page to WordPress admin
     */
    public function add_settings_page() {
        add_options_page(
            __('Maintenance Mode', 'rokku-maintenance-mode'),
            __('Maintenance Mode', 'rokku-maintenance-mode'),
            'manage_options',
            'rokkmamo-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('rokkmamo_settings', 'rokkmamo_enabled', [
            'sanitize_callback' => [$this, 'validate_maintenance_mode_toggle'],
            'default' => 0,
            'show_in_rest' => true,
        ]);
        register_setting('rokkmamo_settings', 'rokkmamo_logo_id', [
            'sanitize_callback' => 'absint',
            'default' => 0,
            'show_in_rest' => true,
        ]);
        register_setting('rokkmamo_settings', 'rokkmamo_headline', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => __('Site Maintenance', 'rokku-maintenance-mode'),
            'show_in_rest' => true,
        ]);
        register_setting('rokkmamo_settings', 'rokkmamo_message', [
            'sanitize_callback' => 'wp_kses_post',
            'default' => __('We are currently performing scheduled maintenance. We will be back online shortly!', 'rokku-maintenance-mode'),
            'show_in_rest' => true,
        ]);

        // Add settings update callback
        add_action('update_option_rokkmamo_enabled', [$this, 'on_maintenance_mode_update'], 10, 3);
    }

    /**
     * Callback when maintenance mode is updated
     */
    public function on_maintenance_mode_update($old_value, $value, $option) {
        // Clear the transient
        delete_transient('rokkmamo_status');
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Always enqueue admin bar style when maintenance mode is active
        if ($this->is_maintenance_mode_enabled()) {
            wp_enqueue_style('rokkmamo-admin-bar', ROKKMAMO_PLUGIN_URL . 'assets/css/admin-bar.css', [], ROKKMAMO_VERSION);
        }

        // Only load settings page assets on the settings page
        if ('settings_page_rokkmamo-settings' !== $hook) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('rokkmamo-admin', ROKKMAMO_PLUGIN_URL . 'assets/css/admin.css', [], ROKKMAMO_VERSION);
        wp_enqueue_script('rokkmamo-admin', ROKKMAMO_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], ROKKMAMO_VERSION, true);
        
        // Localize the script with translation strings
        wp_localize_script('rokkmamo-admin', 'rokkmamoL10n', [
            'selectLogo' => __('Select Logo', 'rokku-maintenance-mode'),
            'useLogo' => __('Use as Logo', 'rokku-maintenance-mode')
        ]);
    }

    /**
     * Enqueue maintenance page assets
     */
    public function enqueue_maintenance_assets() {
        if (!current_user_can('manage_options') && $this->is_maintenance_mode_enabled()) {
            wp_enqueue_style('rokkmamo-maintenance', ROKKMAMO_PLUGIN_URL . 'assets/css/maintenance.css', [], ROKKMAMO_VERSION);
        }
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $logo_id = get_option('rokkmamo_logo_id');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Maintenance Mode Settings', 'rokku-maintenance-mode'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('rokkmamo_settings');
                do_settings_sections('rokkmamo_settings');
                wp_nonce_field('rokkmamo_settings', 'rokkmamo_nonce');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Enable Maintenance Mode', 'rokku-maintenance-mode'); ?></th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" name="rokkmamo_enabled" value="1" <?php checked(1, get_option('rokkmamo_enabled'), true); ?> />
                                <span class="slider round"></span>
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Logo Upload', 'rokku-maintenance-mode'); ?></th>
                        <td>
                            <div id="rokkmamo-logo-preview">
                                <?php if ($logo_id) {
                                    echo wp_get_attachment_image($logo_id, 'full', false, [
                                        'style' => 'max-width:200px;margin-bottom:20px;',
                                    ]);
                                }
                                ?>
                            </div>
                            <input type="hidden" name="rokkmamo_logo_id" id="rokkmamo_logo_id" value="<?php echo esc_attr($logo_id); ?>" />
                            <button type="button" class="button" id="rokkmamo-upload-logo"><?php echo esc_html__('Upload Logo', 'rokku-maintenance-mode'); ?></button>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Headline', 'rokku-maintenance-mode'); ?></th>
                        <td>
                            <input type="text" name="rokkmamo_headline" value="<?php echo esc_attr(get_option('rokkmamo_headline')); ?>" size="50" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Message', 'rokku-maintenance-mode'); ?></th>
                        <td>
                            <?php
                            $content = get_option('rokkmamo_message');
                            $editor_id = 'rokkmamo_message';
                            wp_editor($content, $editor_id, [
                                'textarea_name' => 'rokkmamo_message',
                                'media_buttons' => true,
                                'textarea_rows' => 10,
                            ]);
                            ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Display maintenance page if enabled
     */
    public function maybe_display_maintenance_page() {
        if (!current_user_can('manage_options') && $this->is_maintenance_mode_enabled()) {
            // Add security headers
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            status_header(503);
            nocache_headers();
            
            $logo_id = get_option('rokkmamo_logo_id');
            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>
            <head>
                <meta charset="<?php bloginfo('charset'); ?>">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta name="robots" content="noindex,nofollow">
                <meta name="googlebot" content="noindex,nofollow">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <title><?php echo esc_html(get_option('rokkmamo_headline')); ?></title>
                <?php wp_head(); ?>
            </head>
            <body>
                <div class="maintenance-container">
                    <?php if ($logo_id) {
                        echo wp_get_attachment_image($logo_id, 'full', false, [
                            'class' => 'maintenance-logo',
                            'loading' => 'eager',
                        ]);
                    }
                    ?>
                    <h1><?php echo esc_html(get_option('rokkmamo_headline')); ?></h1>
                    <?php echo wp_kses_post(wpautop(get_option('rokkmamo_message'))); ?>
                </div>
                <?php 
                wp_robots_no_robots();
                wp_footer(); 
                ?>
            </body>
            </html>
            <?php
            exit;
        }
    }

    /**
     * Display admin notice when maintenance mode is active
     */
    public function admin_notice() {
        if ($this->is_maintenance_mode_enabled()) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php echo esc_html__('Maintenance Mode is Active', 'rokku-maintenance-mode'); ?></strong>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Check if maintenance mode is enabled
     * Uses transient for better performance
     */
    private function is_maintenance_mode_enabled() {
        $status = get_transient('rokkmamo_status');
        
        if (false === $status) {
            $status = (bool) get_option('rokkmamo_enabled');
            set_transient('rokkmamo_status', $status, HOUR_IN_SECONDS);
        }
        
        return $status;
    }

    /**
     * Validate maintenance mode toggle
     */
    public function validate_maintenance_mode_toggle($value) {
        // Verify nonce
        $nonce = wp_unslash(filter_input(INPUT_POST, '_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        if (!$nonce || !wp_verify_nonce($nonce, 'rokkmamo_settings-options')) {
            add_settings_error(
                'rokkmamo_enabled',
                'invalid_nonce',
                __('Security check failed. Please try again.', 'rokku-maintenance-mode')
            );
            return get_option('rokkmamo_enabled');
        }

        // Rate limiting check
        $last_toggle = get_transient('rokkmamo_last_toggle');
        if ($last_toggle) {
            add_settings_error(
                'rokkmamo_enabled',
                'rate_limit',
                __('Please wait a few seconds before toggling maintenance mode again.', 'rokku-maintenance-mode')
            );
            return get_option('rokkmamo_enabled');
        }

        // Set rate limit
        set_transient('rokkmamo_last_toggle', time(), 5); // 5 second cooldown

        return absint($value);
    }
} 