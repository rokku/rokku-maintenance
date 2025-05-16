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
        add_action('template_redirect', [$this, 'maybe_display_maintenance_page']);
        add_action('admin_notices', [$this, 'admin_notice']);
        add_action('admin_head', [$this, 'admin_header_style']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Add settings page to WordPress admin
     */
    public function add_settings_page() {
        add_options_page(
            __('Maintenance Mode', 'rokku-maintenance-mode'),
            __('Maintenance Mode', 'rokku-maintenance-mode'),
            'manage_options',
            'maintenance-mode',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('maintenance_mode_settings', 'mm_enabled', [
            'sanitize_callback' => 'absint',
            'default' => 0,
        ]);
        register_setting('maintenance_mode_settings', 'mm_logo_id', [
            'sanitize_callback' => 'absint',
            'default' => 0,
        ]);
        register_setting('maintenance_mode_settings', 'mm_headline', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => __('Site Maintenance', 'rokku-maintenance-mode'),
        ]);
        register_setting('maintenance_mode_settings', 'mm_message', [
            'sanitize_callback' => 'wp_kses_post',
            'default' => __('We are currently performing scheduled maintenance. We will be back online shortly!', 'rokku-maintenance-mode'),
        ]);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_maintenance-mode' !== $hook) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('rokku-mm-admin', ROKKU_MM_PLUGIN_URL . 'assets/css/admin.css', [], ROKKU_MM_VERSION);
        wp_enqueue_script('rokku-mm-admin', ROKKU_MM_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], ROKKU_MM_VERSION, true);
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $logo_id = get_option('mm_logo_id');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Maintenance Mode Settings', 'rokku-maintenance-mode'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('maintenance_mode_settings');
                do_settings_sections('maintenance_mode_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Enable Maintenance Mode', 'rokku-maintenance-mode'); ?></th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" name="mm_enabled" value="1" <?php checked(1, get_option('mm_enabled'), true); ?> />
                                <span class="slider round"></span>
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Logo Upload', 'rokku-maintenance-mode'); ?></th>
                        <td>
                            <div id="mm-logo-preview">
                                <?php if ($logo_id) {
                                    echo wp_get_attachment_image($logo_id, 'full', false, [
                                        'style' => 'max-width:200px;margin-bottom:20px;',
                                    ]);
                                }
                                ?>
                            </div>
                            <input type="hidden" name="mm_logo_id" id="mm_logo_id" value="<?php echo esc_attr($logo_id); ?>" />
                            <button type="button" class="button" id="mm-upload-logo"><?php echo esc_html__('Upload Logo', 'rokku-maintenance-mode'); ?></button>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Headline', 'rokku-maintenance-mode'); ?></th>
                        <td>
                            <input type="text" name="mm_headline" value="<?php echo esc_attr(get_option('mm_headline')); ?>" size="50" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Message', 'rokku-maintenance-mode'); ?></th>
                        <td>
                            <?php
                            $content = get_option('mm_message');
                            $editor_id = 'mm_message';
                            wp_editor($content, $editor_id, [
                                'textarea_name' => 'mm_message',
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
            status_header(503);
            nocache_headers();
            
            $logo_id = get_option('mm_logo_id');
            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>
            <head>
                <meta charset="<?php bloginfo('charset'); ?>">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title><?php echo esc_html(get_option('mm_headline')); ?></title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                        text-align: center;
                        padding: 50px;
                        line-height: 1.6;
                        color: #333;
                    }
                    .maintenance-container {
                        max-width: 600px;
                        margin: 0 auto;
                    }
                    .maintenance-logo {
                        max-width: 200px;
                        height: auto;
                        margin-bottom: 30px;
                    }
                    h1 {
                        font-size: 2em;
                        margin-bottom: 20px;
                    }
                    p {
                        margin-bottom: 15px;
                    }
                </style>
            </head>
            <body>
                <div class="maintenance-container">
                    <?php if ($logo_id) {
                        echo wp_get_attachment_image($logo_id, 'full', false, [
                            'class' => 'maintenance-logo',
                        ]);
                    }
                    ?>
                    <h1><?php echo esc_html(get_option('mm_headline')); ?></h1>
                    <?php echo wp_kses_post(wpautop(get_option('mm_message'))); ?>
                </div>
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
     * Add custom styles to admin header when maintenance mode is active
     */
    public function admin_header_style() {
        if ($this->is_maintenance_mode_enabled()) {
            ?>
            <style>
                #wpadminbar {
                    background: #dc3232 !important;
                }
            </style>
            <?php
        }
    }

    /**
     * Check if maintenance mode is enabled
     * Uses transient for better performance
     */
    private function is_maintenance_mode_enabled() {
        $status = get_transient('rokku_mm_status');
        
        if (false === $status) {
            $status = (bool) get_option('mm_enabled');
            set_transient('rokku_mm_status', $status, HOUR_IN_SECONDS);
        }
        
        return $status;
    }
} 