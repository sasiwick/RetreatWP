<?php

if (!defined('ABSPATH')) exit;

class Listeo_Core_Site_Health
{

    /**
     * Returns the instance.
     *
     * @since 2.0.0
     */
    public static function get_instance()
    {
        static $instance = null;
        if (is_null($instance)) {
            $instance = new self;
        }
        return $instance;
    }


    /**
     * Constructor.
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        add_filter('site_health_navigation_tabs',array($this,'listeo_site_health_navigation_tabs'));
        add_action('site_health_tab_content', array($this, 'listeo_site_health_tab_content'));
        add_action('admin_enqueue_scripts', array($this, 'listeo_site_health_enqueue_admin_scripts'));
        add_filter('admin_body_class', array($this, 'listeo_add_health_check_body_class'));
        add_action('admin_bar_menu', array($this, 'listeo_add_admin_bar_health_check'), 100);

        add_action('wp_ajax_listeo_recreate_page', array($this, 'listeo_recreate_page'));
        add_action('wp_ajax_listeo_update_memory_limit', array($this, 'listeo_update_memory_limit'));
        add_action('wp_ajax_listeo_toggle_debug_mode', array($this, 'listeo_toggle_debug_mode'));
        add_action('wp_ajax_listeo_test_email', array($this, 'listeo_test_email'));
        
    }

    
    function listeo_site_health_enqueue_admin_scripts( $hook ) {
        
        if ('site-health.php' == $hook ) {
            
            wp_enqueue_script('listeo_site_health_script', LISTEO_CORE_URL . 'assets/js/listeo.sitehealth.js', array('wp-util', 'jquery'), 1.0, true);
            
            // Localize script with nonce
            wp_localize_script('listeo_site_health_script', 'listeo_site_health_vars', array(
                'memory_limit_nonce' => wp_create_nonce('listeo_memory_limit_nonce'),
                'debug_toggle_nonce' => wp_create_nonce('listeo_debug_toggle_nonce'),
                'test_email_nonce' => wp_create_nonce('listeo_test_email_nonce'),
                'admin_email' => get_option('admin_email')
            ));
            
        }
        
    }

    function listeo_add_health_check_body_class($classes)
    {
        // Get current screen
        $screen = get_current_screen();
        
        // Check if we're on the site health page and on the Listeo tab
        if ($screen && $screen->id === 'site-health' && 
            isset($_GET['tab']) && $_GET['tab'] === 'listeo-site-health-tab') {
            $classes .= ' listeo-health-check-page';
        }
        
        return $classes;
    }

    function listeo_site_health_navigation_tabs($tabs)
    {
        // translators: Tab heading for Site Health navigation.
        $tabs['listeo-site-health-tab'] = esc_html_x('Listeo', 'Site Health', 'listeo_core');

        return $tabs;
    }

    function listeo_site_health_tab_content( $tab ) {
        // Do nothing if this is not our tab.
        if ('listeo-site-health-tab' !== $tab ) {
            return;
        }
    
        // Include the interface, kept in a separate file just to differentiate code from views.
        include trailingslashit( plugin_dir_path( __FILE__ ) ) . '/views/site-health-tab.php';
    }


    function listeo_recreate_page(){
        $pages = listeo_core_get_dashboard_pages_list();
        
        if(!empty($_POST['page'])){
            $page = $pages[$_POST['page']];
            $title = $page['title'];
            $content = $page['content'];
            delete_option($page['option']);
            $page_args = array(
                'comment_status' => 'close',
                'ping_status'    => 'close',
                'post_author'    => 1,
                'post_title'     => $title,
                'post_name'      => strtolower(str_replace(' ', '-', trim($title))),
                'post_status'    => 'publish',
                'post_content'   => $content,
                'post_type'      => 'page',
                'page_template'  => 'template-dashboard.php'
            );
            if(in_array($_POST['page'],array('listeo_lost_password_page', 'listeo_reset_password_page'))){
               unset($page_args['page_template']);
            }
            $page_id = wp_insert_post(
                $page_args
            );
            
            if($page_id){
                update_option($page['option'],$page_id);
                wp_send_json_success();
            }
        } else {
            wp_send_json_error();
        }
    
        
    }

    function listeo_update_memory_limit() {
        // Security checks
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'listeo_core')));
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'listeo_memory_limit_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'listeo_core')));
            return;
        }

        $memory_limit = sanitize_text_field($_POST['memory_limit']);
        
        // Validate memory limit
        if (!in_array($memory_limit, array('256M', '512M'))) {
            wp_send_json_error(array('message' => __('Invalid memory limit value', 'listeo_core')));
            return;
        }

        $wp_config_path = ABSPATH . 'wp-config.php';
        
        // Check if wp-config.php exists and is writable
        if (!file_exists($wp_config_path) || !is_writable($wp_config_path)) {
            wp_send_json_error(array('message' => __('wp-config.php is not writable', 'listeo_core')));
            return;
        }

        // Create backup
        $backup_path = $wp_config_path . '.backup.' . time();
        if (!copy($wp_config_path, $backup_path)) {
            wp_send_json_error(array('message' => __('Could not create backup', 'listeo_core')));
            return;
        }

        // Read wp-config.php
        $wp_config_content = file_get_contents($wp_config_path);
        
        if ($wp_config_content === false) {
            wp_send_json_error(array('message' => __('Could not read wp-config.php', 'listeo_core')));
            return;
        }

        $memory_definitions = array(
            "define('WP_MEMORY_LIMIT', '{$memory_limit}');",
            "define('WP_MAX_MEMORY_LIMIT', '{$memory_limit}');"
        );

        $updated_content = $wp_config_content;
        $added_definitions = array();

        foreach ($memory_definitions as $definition) {
            $constant_name = $definition === "define('WP_MEMORY_LIMIT', '{$memory_limit}');" ? 'WP_MEMORY_LIMIT' : 'WP_MAX_MEMORY_LIMIT';
            
            // Check if constant already exists
            $pattern = '/define\s*\(\s*[\'"]' . preg_quote($constant_name, '/') . '[\'"]\s*,\s*[\'"][^\'\"]*[\'"]\s*\)\s*;/';
            
            if (preg_match($pattern, $updated_content)) {
                // Update existing definition
                $updated_content = preg_replace($pattern, $definition, $updated_content);
            } else {
                // Add new definition
                $added_definitions[] = $definition;
            }
        }

        // Add new definitions before "/* That's all, stop editing! Happy publishing. */"
        if (!empty($added_definitions)) {
            $insert_point = "/* That's all, stop editing! Happy publishing. */";
            $new_definitions = "\n// WordPress Memory Limits\n" . implode("\n", $added_definitions) . "\n\n";
            $updated_content = str_replace($insert_point, $new_definitions . $insert_point, $updated_content);
        }

        // Write updated content
        if (file_put_contents($wp_config_path, $updated_content) === false) {
            wp_send_json_error(array('message' => __('Could not write to wp-config.php', 'listeo_core')));
            return;
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Memory limit successfully updated to %s', 'listeo_core'), $memory_limit),
            'backup_created' => basename($backup_path)
        ));
    }

    function listeo_toggle_debug_mode() {
        // Security checks
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'listeo_core')));
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'listeo_debug_toggle_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'listeo_core')));
            return;
        }

        $action = sanitize_text_field($_POST['debug_action']);
        
        // Validate action
        if (!in_array($action, array('enable_full', 'disable_all', 'enable_logging', 'disable_display'))) {
            wp_send_json_error(array('message' => __('Invalid action', 'listeo_core')));
            return;
        }

        $wp_config_path = ABSPATH . 'wp-config.php';
        
        // Check if wp-config.php exists and is writable
        if (!file_exists($wp_config_path) || !is_writable($wp_config_path)) {
            wp_send_json_error(array('message' => __('wp-config.php is not writable', 'listeo_core')));
            return;
        }

        // Create backup
        $backup_path = $wp_config_path . '.backup.' . time();
        if (!copy($wp_config_path, $backup_path)) {
            wp_send_json_error(array('message' => __('Could not create backup', 'listeo_core')));
            return;
        }

        // Read wp-config.php
        $wp_config_content = file_get_contents($wp_config_path);
        
        if ($wp_config_content === false) {
            wp_send_json_error(array('message' => __('Could not read wp-config.php', 'listeo_core')));
            return;
        }

        $updated_content = $wp_config_content;
        $debug_definitions = array();
        $added_definitions = array();
        
        // Define debug settings based on action
        switch ($action) {
            case 'enable_full':
                $debug_definitions = array(
                    'WP_DEBUG' => "define( 'WP_DEBUG', true );",
                    'WP_DEBUG_LOG' => "define( 'WP_DEBUG_LOG', true );",
                    'WP_DEBUG_DISPLAY' => "define( 'WP_DEBUG_DISPLAY', true );",
                    'SCRIPT_DEBUG' => "define( 'SCRIPT_DEBUG', true );"
                );
                break;
                
            case 'disable_all':
                $debug_definitions = array(
                    'WP_DEBUG' => "define( 'WP_DEBUG', false );",
                    'WP_DEBUG_LOG' => "define( 'WP_DEBUG_LOG', false );",
                    'WP_DEBUG_DISPLAY' => "define( 'WP_DEBUG_DISPLAY', false );",
                    'SCRIPT_DEBUG' => "define( 'SCRIPT_DEBUG', false );"
                );
                break;
                
            case 'enable_logging':
                $debug_definitions = array(
                    'WP_DEBUG' => "define( 'WP_DEBUG', true );",
                    'WP_DEBUG_LOG' => "define( 'WP_DEBUG_LOG', true );",
                    'WP_DEBUG_DISPLAY' => "define( 'WP_DEBUG_DISPLAY', false );"
                );
                break;
                
            case 'disable_display':
                $debug_definitions = array(
                    'WP_DEBUG_DISPLAY' => "define( 'WP_DEBUG_DISPLAY', false );"
                );
                break;
        }

        // Update or add debug definitions
        foreach ($debug_definitions as $constant_name => $definition) {
            $pattern = '/define\s*\(\s*[\'"]' . preg_quote($constant_name, '/') . '[\'"]\s*,\s*[^)]+\)\s*;/';
            
            if (preg_match($pattern, $updated_content)) {
                // Update existing definition
                $updated_content = preg_replace($pattern, $definition, $updated_content);
            } else {
                // Add new definition
                $added_definitions[] = $definition;
            }
        }

        // Add new definitions before "/* That's all, stop editing! Happy publishing. */"
        if (!empty($added_definitions)) {
            $insert_point = "/* That's all, stop editing! Happy publishing. */";
            $new_definitions = "\n// Debug Mode Settings\n" . implode("\n", $added_definitions) . "\n\n";
            $updated_content = str_replace($insert_point, $new_definitions . $insert_point, $updated_content);
        }

        // Write updated content
        if (file_put_contents($wp_config_path, $updated_content) === false) {
            wp_send_json_error(array('message' => __('Could not write to wp-config.php', 'listeo_core')));
            return;
        }

        // Create success message based on action
        $messages = array(
            'enable_full' => __('Full debug mode enabled. All debugging features are now active.', 'listeo_core'),
            'disable_all' => __('All debug features disabled. Site is now in production mode.', 'listeo_core'),
            'enable_logging' => __('Error logging enabled. Errors will be logged but not displayed to visitors.', 'listeo_core'),
            'disable_display' => __('Frontend error display disabled. Errors will not be shown to visitors.', 'listeo_core')
        );
        
        $message = isset($messages[$action]) ? $messages[$action] : __('Debug settings updated successfully.', 'listeo_core');

        wp_send_json_success(array(
            'message' => $message,
            'backup_created' => basename($backup_path)
        ));
    }

    function listeo_test_email() {
        // Security checks
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'listeo_core')));
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'listeo_test_email_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'listeo_core')));
            return;
        }

        $test_email = sanitize_email($_POST['test_email']);
        
        // Validate email
        if (!is_email($test_email)) {
            wp_send_json_error(array('message' => __('Invalid email address', 'listeo_core')));
            return;
        }

        // Prepare test email
        $subject = __('Test Email from Listeo Site Health', 'listeo_core');
        $message = sprintf(
            __('This is a test email sent from your Listeo site at %s to verify email functionality. If you received this email, your mail system is working correctly.', 'listeo_core'),
            home_url()
        );

        // Add additional diagnostic info
        $message .= "\n\n" . __('Email Configuration Details:', 'listeo_core') . "\n";
        $message .= sprintf(__('- WordPress Version: %s', 'listeo_core'), get_bloginfo('version')) . "\n";
        $message .= sprintf(__('- PHP Version: %s', 'listeo_core'), phpversion()) . "\n";
        $message .= sprintf(__('- Server Time: %s', 'listeo_core'), current_time('Y-m-d H:i:s')) . "\n";

        // Attempt to send email
        $sent = wp_mail($test_email, $subject, $message);

        if ($sent) {
            wp_send_json_success(array(
                'message' => sprintf(__('Test email sent successfully to %s. Please check your inbox (and spam folder).', 'listeo_core'), $test_email)
            ));
        } else {
            // Get the last error
            global $phpmailer;
            $error_message = '';
            if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
                $error_message = ' Error: ' . $phpmailer->ErrorInfo;
            }
            
            wp_send_json_error(array(
                'message' => sprintf(__('Failed to send test email to %s.%s', 'listeo_core'), $test_email, $error_message)
            ));
        }
    }

    function listeo_add_admin_bar_health_check($wp_admin_bar) {
        // Only show to users who can manage options
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Add Listeo Health Check to admin bar
        $wp_admin_bar->add_menu(array(
            'id'    => 'listeo-health-check',
            'title' => '<span class="ab-icon dashicons dashicons-yes-alt" style="margin-top: 2px;"></span><span class="ab-label">' . __('Listeo Health', 'listeo_core') . '</span>',
            'href'  => admin_url('site-health.php?tab=listeo-site-health-tab'),
            'meta'  => array(
                'title' => __('Listeo Site Health Check - Monitor your site status', 'listeo_core'),
                'class' => 'listeo-health-admin-bar'
            ),
        ));
    }

}


