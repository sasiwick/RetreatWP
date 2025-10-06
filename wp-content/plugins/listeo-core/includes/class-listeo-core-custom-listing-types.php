<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Listeo Core Custom Listing Types Management
 * 
 * Handles custom listing types database operations, migration, and management
 * 
 * @since 1.0.0
 */
class Listeo_Core_Custom_Listing_Types {

    /**
     * The single instance of the class.
     */
    private static $_instance = null;

    /**
     * Database table name for custom listing types
     */
    private $table_name;

    /**
     * Database version for tracking updates
     */
    const DB_VERSION = '1.0.0';

    /**
     * Default listing types (for backwards compatibility)
     */
    const DEFAULT_TYPES = array(
        array(
            'slug' => 'service',
            'name' => 'Service',
            'plural_name' => 'Services',
            'description' => 'Service-based listings with booking functionality',
            'booking_enabled' => 1,
            'booking_type' => 'standard',
            'supports_pricing' => 1,
            'supports_calendar' => 1,
            'supports_time_slots' => 1,
            'supports_guests' => 1,
            'supports_services' => 1,
            'menu_order' => 1,
            'is_active' => 1,
            'is_default' => 1
        ),
        array(
            'slug' => 'rental',
            'name' => 'Rental',
            'plural_name' => 'Rentals',
            'description' => 'Rental-based listings with instant booking',
            'booking_enabled' => 1,
            'booking_type' => 'instant',
            'supports_pricing' => 1,
            'supports_calendar' => 1,
            'supports_time_slots' => 0,
            'supports_guests' => 1,
            'supports_services' => 1,
            'menu_order' => 2,
            'is_active' => 1,
            'is_default' => 1
        ),
        array(
            'slug' => 'event',
            'name' => 'Event',
            'plural_name' => 'Events',
            'description' => 'Event-based listings with date-specific functionality',
            'booking_enabled' => 1,
            'booking_type' => 'standard',
            'supports_pricing' => 1,
            'supports_calendar' => 1,
            'supports_time_slots' => 1,
            'supports_guests' => 1,
            'supports_services' => 1,
            'menu_order' => 3,
            'is_active' => 1,
            'is_default' => 1
        ),
        array(
            'slug' => 'classifieds',
            'name' => 'Classified',
            'plural_name' => 'Classifieds',
            'description' => 'Classified ad listings without booking functionality',
            'booking_enabled' => 0,
            'booking_type' => 'none',
            'supports_pricing' => 0,
            'supports_calendar' => 0,
            'supports_time_slots' => 0,
            'supports_guests' => 0,
            'supports_services' => 0,
            'menu_order' => 4,
            'is_active' => 1,
            'is_default' => 1
        )
    );

    /**
     * Allows for accessing single instance of class. Class should only be constructed once per call.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->table_name = $wpdb->prefix . 'listeo_listing_types';
        
        // Hook into WordPress
        add_action('plugins_loaded', array($this, 'init'));
        
        // Activation/deactivation hooks
        register_activation_hook(LISTEO_CORE_PLUGIN_DIR . '/listeo-core.php', array($this, 'activate'));
        register_deactivation_hook(LISTEO_CORE_PLUGIN_DIR . '/listeo-core.php', array($this, 'deactivate'));
    }

    /**
     * Initialize the custom listing types system
     */
    public function init() {
        // Check if we need to create/update the database
        $this->maybe_create_table();
        
        // Check if we need to run migration
        $this->maybe_migrate_data();
        
        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Admin hooks
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    /**
     * Admin initialization
     */
    public function admin_init() {
        // Check database version and update if needed
        $installed_ver = get_option('listeo_custom_types_db_version');
        
        if ($installed_ver != self::DB_VERSION) {
            $this->create_table();
            update_option('listeo_custom_types_db_version', self::DB_VERSION);
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_table();
        $this->migrate_default_types();
        
        // Set database version
        update_option('listeo_custom_types_db_version', self::DB_VERSION);
        
        // Set migration flag
        update_option('listeo_custom_types_migration_needed', '1');
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        // Note: We don't drop the table to preserve custom data
    }

    /**
     * Create the custom listing types database table
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            slug varchar(50) NOT NULL UNIQUE,
            name varchar(100) NOT NULL,
            plural_name varchar(100) NOT NULL,
            description text,
            icon_id int(11) DEFAULT NULL,
            booking_enabled tinyint(1) DEFAULT 0,
            booking_type varchar(20) DEFAULT 'standard',
            supports_pricing tinyint(1) DEFAULT 1,
            supports_calendar tinyint(1) DEFAULT 1,
            supports_time_slots tinyint(1) DEFAULT 0,
            supports_guests tinyint(1) DEFAULT 1,
            supports_services tinyint(1) DEFAULT 1,
            menu_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            is_default tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_slug (slug),
            KEY idx_active (is_active),
            KEY idx_menu_order (menu_order)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $result = dbDelta($sql);
        
        // Log the result for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Listeo Custom Types Table Creation Result: ' . print_r($result, true));
        }
    }

    /**
     * Check if table needs to be created
     */
    private function maybe_create_table() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        
        if (!$table_exists) {
            $this->create_table();
        }
    }

    /**
     * Check if data migration is needed
     */
    private function maybe_migrate_data() {
        $migration_needed = get_option('listeo_custom_types_migration_needed');
        
        if ($migration_needed === '1') {
            $this->migrate_default_types();
            update_option('listeo_custom_types_migration_needed', '0');
        }
    }

    /**
     * Migrate default listing types to the new system
     */
    public function migrate_default_types() {
        global $wpdb;

        // Check if we already have types in the database
        $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        if ($existing_count > 0) {
            return; // Already migrated
        }

        // Insert default types
        foreach (self::DEFAULT_TYPES as $type_data) {
            $this->insert_listing_type($type_data);
        }

        // Migrate existing type icons from theme options
        $this->migrate_type_icons();

        // Log migration completion
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Listeo Custom Types: Default types migration completed');
        }
    }

    /**
     * Migrate existing type icons from theme options
     */
    private function migrate_type_icons() {
        $icon_mapping = array(
            'service' => get_option('listeo_service_type_icon'),
            'rental' => get_option('listeo_rental_type_icon'),
            'event' => get_option('listeo_event_type_icon'),
            'classifieds' => get_option('listeo_classifieds_type_icon')
        );

        foreach ($icon_mapping as $slug => $icon_id) {
            if ($icon_id) {
                $this->update_listing_type($slug, array('icon_id' => $icon_id));
            }
        }
    }

    /**
     * Insert a new listing type
     */
    public function insert_listing_type($data) {
        global $wpdb;

        $defaults = array(
            'slug' => '',
            'name' => '',
            'plural_name' => '',
            'description' => '',
            'icon_id' => null,
            'booking_enabled' => 0,
            'booking_type' => 'standard',
            'supports_pricing' => 1,
            'supports_calendar' => 1,
            'supports_time_slots' => 0,
            'supports_guests' => 1,
            'supports_services' => 1,
            'menu_order' => 0,
            'is_active' => 1,
            'is_default' => 0
        );

        $data = wp_parse_args($data, $defaults);

        // Validate required fields
        if (empty($data['slug']) || empty($data['name'])) {
            return new WP_Error('missing_required_fields', 'Slug and name are required fields.');
        }

        // Sanitize data
        $data = $this->sanitize_type_data($data);

        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array(
                '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d'
            )
        );

        if ($result === false) {
            return new WP_Error('db_insert_error', 'Failed to insert listing type: ' . $wpdb->last_error);
        }

        $type_id = $wpdb->insert_id;

        // Clear cache
        $this->clear_cache();

        return $type_id;
    }

    /**
     * Update an existing listing type
     */
    public function update_listing_type($slug, $data) {
        global $wpdb;

        // Sanitize data
        $data = $this->sanitize_type_data($data);

        $result = $wpdb->update(
            $this->table_name,
            $data,
            array('slug' => $slug),
            null,
            array('%s')
        );

        if ($result === false) {
            return new WP_Error('db_update_error', 'Failed to update listing type: ' . $wpdb->last_error);
        }

        // Clear cache
        $this->clear_cache();

        return true;
    }

    /**
     * Delete a listing type
     */
    public function delete_listing_type($slug) {
        global $wpdb;

        // Check if this is a default type
        $type = $this->get_listing_type_by_slug($slug);
        if ($type && $type->is_default) {
            return new WP_Error('cannot_delete_default', 'Default listing types cannot be deleted.');
        }

        // Check if there are any listings using this type
        $listing_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_listing_type' AND meta_value = %s
        ", $slug));

        if ($listing_count > 0) {
            return new WP_Error('type_in_use', sprintf('Cannot delete listing type. %d listings are using this type.', $listing_count));
        }

        $result = $wpdb->delete(
            $this->table_name,
            array('slug' => $slug),
            array('%s')
        );

        if ($result === false) {
            return new WP_Error('db_delete_error', 'Failed to delete listing type: ' . $wpdb->last_error);
        }

        // Clear cache
        $this->clear_cache();

        return true;
    }

    /**
     * Get all listing types
     */
    public function get_listing_types($active_only = true, $include_counts = false) {
        global $wpdb;

        $cache_key = 'listeo_listing_types_' . ($active_only ? 'active' : 'all') . ($include_counts ? '_with_counts' : '');
        $types = wp_cache_get($cache_key, 'listeo_core');

        if ($types === false) {
            $where = $active_only ? 'WHERE is_active = 1' : '';
            
            $sql = "SELECT * FROM {$this->table_name} {$where} ORDER BY menu_order ASC, name ASC";
            $types = $wpdb->get_results($sql);

            if ($include_counts && $types) {
                foreach ($types as $type) {
                    $type->listing_count = $wpdb->get_var($wpdb->prepare("
                        SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                        LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                        WHERE pm.meta_key = '_listing_type' 
                        AND pm.meta_value = %s 
                        AND p.post_status = 'publish'
                    ", $type->slug));
                }
            }

            wp_cache_set($cache_key, $types, 'listeo_core', HOUR_IN_SECONDS);
        }

        return $types;
    }

    /**
     * Get a single listing type by slug
     */
    public function get_listing_type_by_slug($slug) {
        global $wpdb;

        $cache_key = 'listeo_listing_type_' . $slug;
        $type = wp_cache_get($cache_key, 'listeo_core');

        if ($type === false) {
            $type = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$this->table_name} 
                WHERE slug = %s AND is_active = 1
            ", $slug));

            wp_cache_set($cache_key, $type, 'listeo_core', HOUR_IN_SECONDS);
        }

        return $type;
    }

    /**
     * Get listing type slugs only
     */
    public function get_listing_type_slugs($active_only = true) {
        $types = $this->get_listing_types($active_only);
        return wp_list_pluck($types, 'slug');
    }

    /**
     * Check if a listing type exists
     */
    public function listing_type_exists($slug) {
        return $this->get_listing_type_by_slug($slug) !== null;
    }

    /**
     * Sanitize listing type data
     */
    private function sanitize_type_data($data) {
        $sanitized = array();

        if (isset($data['slug'])) {
            $sanitized['slug'] = sanitize_title($data['slug']);
        }

        if (isset($data['name'])) {
            $sanitized['name'] = sanitize_text_field($data['name']);
        }

        if (isset($data['plural_name'])) {
            $sanitized['plural_name'] = sanitize_text_field($data['plural_name']);
        }

        if (isset($data['description'])) {
            $sanitized['description'] = sanitize_textarea_field($data['description']);
        }

        if (isset($data['icon_id'])) {
            $sanitized['icon_id'] = absint($data['icon_id']);
        }

        // Boolean fields
        $boolean_fields = array('booking_enabled', 'supports_pricing', 'supports_calendar', 'supports_time_slots', 'supports_guests', 'supports_services', 'is_active', 'is_default');
        foreach ($boolean_fields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = (bool) $data[$field] ? 1 : 0;
            }
        }

        if (isset($data['booking_type'])) {
            $allowed_types = array('standard', 'instant', 'request', 'none');
            $sanitized['booking_type'] = in_array($data['booking_type'], $allowed_types) ? $data['booking_type'] : 'standard';
        }

        if (isset($data['menu_order'])) {
            $sanitized['menu_order'] = absint($data['menu_order']);
        }

        return $sanitized;
    }

    /**
     * Clear cache for listing types
     */
    private function clear_cache() {
        wp_cache_delete('listeo_listing_types_active', 'listeo_core');
        wp_cache_delete('listeo_listing_types_all', 'listeo_core');
        wp_cache_delete('listeo_listing_types_active_with_counts', 'listeo_core');
        wp_cache_delete('listeo_listing_types_all_with_counts', 'listeo_core');
        
        // Clear individual type caches
        $types = $this->get_listing_types(false);
        if ($types) {
            foreach ($types as $type) {
                wp_cache_delete('listeo_listing_type_' . $type->slug, 'listeo_core');
            }
        }
    }

    /**
     * Get the table name
     */
    public function get_table_name() {
        return $this->table_name;
    }

    /**
     * Get database version
     */
    public static function get_db_version() {
        return self::DB_VERSION;
    }
}

// Initialize the custom listing types system
function listeo_core_custom_listing_types() {
    return Listeo_Core_Custom_Listing_Types::instance();
}

// Initialize when the plugin loads
add_action('plugins_loaded', 'listeo_core_custom_listing_types', 5);