<?php
/*
Plugin Name: QualityHive - Website Feedback Tool
Plugin URI: https://qualityhive.com
Description: Collect website feedback for your project from clients and your team using a code-free installation of QualityHive.
Version: 1.4.8
Author: QualityHive
Author URI: https://qualityhive.com/features
License: GPLv3 or later
*/
 
// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

class QualityHivePlatformIntegration {
    private $option_name = 'qualityhive_project_key';
    private $admin_only_option_name = 'qualityhive_admin_only';

    public function __construct() {
        // Register settings
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));

        // Inject tracking code in the header
        add_action('wp_head', array($this, 'inject_tracking_code'));

        // Add settings link on plugins page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    public function add_settings_page() {
        add_options_page(
            'QualityHive Settings',
            'QualityHive',
            'manage_options',
            'qualityhive-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting(
            'qualityhive_integrationsettings',
            $this->option_name,
            array(
                'sanitize_callback' => 'sanitize_text_field' // Sanitizes text input
            )
        );
        register_setting(
            'qualityhive_integrationsettings',
            $this->admin_only_option_name,
            array(
                'sanitize_callback' => 'absint' // Sanitizes checkbox as integer (1 or 0)
            )
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('QualityHive Settings', 'qualityhive'); ?></h1>
            <p><?php esc_html_e('To use this plugin, you need to be subscribed to QualityHive.', 'qualityhive'); ?> <a href="https://app.qualityhive.com/register" target="_blank"><?php esc_html_e('Register here for a 14-day free trial', 'qualityhive'); ?></a>.</p>
            <form method="post" action="options.php">
                <?php settings_fields('qualityhive_integrationsettings'); ?>
                <?php do_settings_sections('qualityhive_integrationsettings'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Project Key', 'qualityhive'); ?></th>
                        <td>
                            <input type="text" name="<?php echo esc_attr($this->option_name); ?>" value="<?php echo esc_attr(get_option($this->option_name)); ?>" />
                            <p class="description"><?php esc_html_e('You can find your unique project key in the "Project Settings" popup which you can access on the left sidebar when viewing a project.', 'qualityhive'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Only Embed for Admins', 'qualityhive'); ?></th>
                        <td>
                            <input type="checkbox" name="<?php echo esc_attr($this->admin_only_option_name); ?>" value="1" <?php checked(1, get_option($this->admin_only_option_name), true); ?> />
                            <label for="<?php echo esc_attr($this->admin_only_option_name); ?>"><?php esc_html_e('Only embed the tracking code for admin users', 'qualityhive'); ?></label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function inject_tracking_code() {
        $project_key = esc_js(get_option($this->option_name));
        $admin_only = get_option($this->admin_only_option_name);
    
        if (!empty($project_key)) {
            if ($admin_only && !current_user_can('administrator')) {
                return; // Do not inject if only admins should see it and the user is not an admin
            }
            
            // Prepare the script with escaping
            $script = "<script type=\"text/javascript\" id=\"qh_data\">" . 
                      "window.qherrors = [];" . 
                      "window.qherrorlogging = true;" . 
                      "window.onerror = function(m, s, l, c, e) { " . 
                      "if (!window.qherrorlogging) return false;" . 
                      "window.qherrors.push({ message: m, script: s, line: l, column: c, errorStack: e ? e.stack : '', timestamp: new Date().toISOString(), userAgent: navigator.userAgent });" . 
                      "return false; };" . 
                      "window.qh_connector_settings = { projectKey: '{$project_key}', frameRoot: 'https://connector.qualityhive.com/' };" . 
                      "(function(d, s) {" . 
                      "s = d.createElement('script');" . 
                      "s.type = 'text/javascript';" . 
                      "s.async = true;" . 
                      "s.src = 'https://connector.qualityhive.com/sidebar/app.min.js';" . 
                      "d.getElementsByTagName('head')[0].appendChild(s);" . 
                      "})(document);" . 
                      "</script>";
    
            // Output the escaped script
            echo wp_kses($script, array(
                'script' => array(
                    'type' => array(),
                    'id' => array()
                )
            ));
        }
    }
    

    public function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=qualityhive-settings">Settings</a>';
        array_push($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
new QualityHivePlatformIntegration();