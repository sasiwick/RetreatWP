<?php
// File: /classes/class-listing-details-handler.php

class Listeo_Listing_Details_Handler
{
    private $post;
    private $details_list;
    private $include_taxonomy_fields;

    public function __construct($post, $include_taxonomy_fields = true)
    {
        $this->post = $post;
        $this->include_taxonomy_fields = $include_taxonomy_fields;
        $this->details_list = $this->getAllDetailsFields();
    }

    private function getAllDetailsFields()
    {
        $all_fields = [];

        // Get standard meta box fields
        $standard_fields = $this->getDetailsListByType();
        if (!empty($standard_fields['fields'])) {
            $all_fields = array_merge($all_fields, $standard_fields['fields']);
        }

        // Get taxonomy-specific fields if enabled
        if ($this->include_taxonomy_fields) {
            $taxonomy_fields = $this->getCategoryFields(); // Keep method name for now to avoid breaking changes
            $all_fields = array_merge($all_fields, $taxonomy_fields);
        }

        return ['fields' => $all_fields];
    }

    private function getDetailsListByType()
    {
        $type = get_post_meta($this->post->ID, '_listing_type', true);

        $type_mappings = [
            'service' => 'meta_boxes_service',
            'rental' => 'meta_boxes_rental',
            'event' => 'meta_boxes_event',
            'classifieds' => 'meta_boxes_classifieds'
        ];

        if (!isset($type_mappings[$type])) {
            return [];
        }

        $details_list = Listeo_Core_Meta_Boxes::{$type_mappings[$type]}();

        // Special handling for classifieds
        if ($type === 'classifieds' && !empty($details_list)) {
            unset($details_list['fields']['_classifieds_price']);
        }

        return $details_list;
    }

    private function getCategoryFields()
    {
        $taxonomy_fields = [];

        // Get all taxonomies registered for the listing post type
        $listing_taxonomies = get_object_taxonomies('listing', 'names');

        // Filter out unwanted taxonomies if needed (you can customize this)
        $excluded_taxonomies = apply_filters('listeo_exclude_taxonomies_from_fields', [
            'post_tag', // Example: exclude if you don't want tags
            // Add any other taxonomies you want to exclude
        ]);

        $listing_taxonomies = array_diff($listing_taxonomies, $excluded_taxonomies);

        foreach ($listing_taxonomies as $taxonomy) {
            $taxonomy_terms = wp_get_post_terms($this->post->ID, $taxonomy, ['fields' => 'ids']);

            if (is_wp_error($taxonomy_terms) || empty($taxonomy_terms)) {
                continue;
            }

            foreach ($taxonomy_terms as $term_id) {
                $option_key = "listeo_tax-{$taxonomy}_term_{$term_id}_fields";
                $fields = get_option($option_key, []);

                if (!empty($fields) && is_array($fields)) {
                    foreach ($fields as $field_key => $field_config) {
                        // Create unique field key to avoid conflicts between taxonomies
                        $unique_field_key = "{$taxonomy}_{$term_id}_{$field_key}";

                        // Normalize the field config
                        $field_config = $this->normalizeTaxonomyField($field_config, $field_key, $term_id, $taxonomy);
                        $taxonomy_fields[$unique_field_key] = $field_config;
                    }
                }
            }
        }

        return $taxonomy_fields;
    }

    private function normalizeTaxonomyField($field_config, $field_key, $term_id, $taxonomy)
    {
        // Get term info for additional context
        $term = get_term($term_id, $taxonomy);
        $term_name = $term && !is_wp_error($term) ? $term->name : "Term {$term_id}";

        // Ensure required properties exist
        $defaults = [
            'id' => $field_key, // Keep original field ID for meta queries
            'name' => $field_config['name'] ?? ucfirst(str_replace('_', ' ', $field_key)),
            'type' => $field_config['type'] ?? 'text',
            'icon' => $field_config['icon'] ?? '',
            'options' => $field_config['options'] ?? [],
            'invert' => $field_config['invert'] ?? false,
            'term_id' => $term_id,
            'taxonomy' => $taxonomy,
            'term_name' => $term_name,
            'is_taxonomy_field' => true,
            'field_source' => "{$taxonomy}:{$term_id}" // Easy way to identify source
        ];

        return array_merge($defaults, $field_config);
    }

    public function getProcessedDetails()
    {
        
        if (empty($this->details_list['fields'])) {
            return [];
        }

        $processed_details = [];

        foreach ($this->details_list['fields'] as $detail_key => $field_config) {
            $detail_data = $this->processField($detail_key, $field_config);

            if ($detail_data) {
                $processed_details[] = $detail_data;
            }
        }

        return $processed_details;
    }

    private function processField($detail_key, $field_config)
    {
        $meta_value = $this->getMetaValue($field_config);
        
        if ($field_config['type'] === 'header') {
            return [
                'key' => $detail_key,
                'config' => $field_config,
                'processed_value' => null,
                'icon' => '',
                'display_type' => 'header',
                'is_checkbox' => false,
                'is_inverted' => false,
                'css_classes' => ['listing-header']
            ];
        }
        // Skip if no value
        if ($this->isEmpty($meta_value)) {
            return null;
        }

        $result = [
            'key' => $detail_key,
            'config' => $field_config,
            'raw_value' => $meta_value,
            'processed_value' => $this->processValue($meta_value, $field_config, $detail_key),
            'icon' => $this->getIcon($field_config),
            'display_type' => $this->getDisplayType($meta_value, $field_config),
            'is_checkbox' => $this->isCheckboxField($meta_value),
            'is_inverted' => isset($field_config['invert']) && $field_config['invert'],
            'css_classes' => $this->getCssClasses($detail_key, $field_config)
        ];
        
        return $result;
    }

    private function getMetaValue($field_config)
    {
        $multi_types = ['select_multiple', 'multicheck_split', 'multicheck'];

        if (in_array($field_config['type'], $multi_types)) {
            // For multi-select types, always get as array first
            $value = get_post_meta($this->post->ID, $field_config['id'], false);

            // Normalize the value to ensure we have a proper flat array
            $normalized_value = [];

            if (!empty($value)) {
                foreach ($value as $item) {
                    if (is_array($item)) {
                        // If item is an array, merge its values
                        $normalized_value = array_merge($normalized_value, $item);
                    } else {
                        // If item is a single value, add it to the array
                        $normalized_value[] = $item;
                    }
                }
            }

            // Remove duplicates and empty values
            $normalized_value = array_filter(array_unique($normalized_value), function ($item) {
                return !empty($item) && $item !== '' && $item !== null;
            });

            $value = array_values($normalized_value); // Re-index array
        } else {
            // For single value types, get as single value
            $value = get_post_meta($this->post->ID, $field_config['id'], true);
        }

        // Handle special ID fields
        if (in_array($field_config['id'], ['_id', '_ID', '_Id'])) {
            $value = apply_filters('listeo_listing_id', $this->post->ID);
        }

        return $value;
    }


    private function processValue($meta_value, $field_config, $detail_key)
    {
        // Handle datetime fields
        if ($field_config['type'] === 'datetime' || in_array($field_config['id'], ['_event_date', '_event_date_end'])) {
            
            return $this->formatDateTime($meta_value);
        }

        // Handle area field with scale
        if ($field_config['id'] === '_area') {
            return [
                'value' => $this->formatUrl($meta_value),
                'scale' => apply_filters('listeo_scale', get_option('listeo_scale', 'sq ft'))
            ];
        }

        // Handle file fields
        if ($field_config['type'] === 'file') {
            return [
                'url' => $meta_value,
                'filename' => wp_basename($meta_value),
                'download_text' => esc_html__('Download', 'listeo_core')
            ];
        }

        // Handle fields with options
        if (!empty($field_config['options'])) {
            
            return $this->processOptionsValue($field_config, $meta_value);
        }

        // Handle URLs
        return $this->formatUrl($meta_value);
    }

    private function processOptionsValue($field_config, $meta_value)
    {
        // Handle single values first
        if (!is_array($meta_value)) {
            return $field_config['options'][$meta_value] ?? $meta_value;
        }

        // Handle array values
        $processed = [];

        // For group and repeatable field types that have nested structure
        if (in_array($field_config['type'], ['repeatable', 'group'], true)) {
            foreach ($meta_value as $value) {
                if (is_array($value)) {
                    $group_data = [];
                    foreach ($value as $key => $val) {
                        $group_data[] = [
                            'label' => (is_array($field_config['options']) && isset($field_config['options'][$key]))
                                ? $field_config['options'][$key]
                                : $key,
                            'value' => $val
                        ];
                    }
                    $processed[] = $group_data;
                } else {
                    $processed[] = [
                        'label' => $value,
                        'value' => $value
                    ];
                }
            }
        }
        // For simple multi-select types
        else if (in_array($field_config['type'], ['select_multiple', 'multicheck_split', 'multicheck'], true)) {
            foreach ($meta_value as $value) {
                // Map each value using options if available
                $processed[] = $field_config['options'][$value] ?? $value;
            }
        }
        // For other array types
        else {
            foreach ($meta_value as $value) {
                $processed[] = $field_config['options'][$value] ?? $value;
            }
        }

        return $processed;
    }

    private function formatDateTime($meta_value)
    {
        $meta_value_date = explode(' ', $meta_value, 2);
        
        // Get WordPress date format and convert to PHP format for parsing
        $wp_date_format = get_option('date_format', 'd/m/Y');
        $php_format = listeo_date_time_wp_format_php();
        
        // Try the WordPress format first (most likely to be correct)
        $meta_value_obj = DateTime::createFromFormat($php_format, $meta_value_date[0]);
        
        // Validate the parsing
        if ($meta_value_obj !== false) {
            $errors = DateTime::getLastErrors();
            if ($errors && ($errors['error_count'] > 0 || $errors['warning_count'] > 0)) {
                $meta_value_obj = false;
            } else {
                // Double-check by formatting back
                $test_formatted = $meta_value_obj->format($php_format);
                if ($test_formatted !== $meta_value_date[0]) {
                    $meta_value_obj = false;
                }
            }
        }
        
        // If WordPress format failed, try common fallback formats
        if (!$meta_value_obj) {
            $fallback_formats = ['m/d/Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'd-m-Y', 'm-d-Y'];
            
            foreach ($fallback_formats as $format) {
                // Skip if it's the same as the WordPress format we already tried
                if ($format === $php_format) {
                    continue;
                }
                
                $meta_value_obj = DateTime::createFromFormat($format, $meta_value_date[0]);
                if ($meta_value_obj !== false) {
                    $errors = DateTime::getLastErrors();
                    if ($errors && ($errors['error_count'] > 0 || $errors['warning_count'] > 0)) {
                        $meta_value_obj = false;
                        continue;
                    }
                    
                    // Validate round-trip
                    $test_formatted = $meta_value_obj->format($format);
                    if ($test_formatted === $meta_value_date[0]) {
                        break; // This format worked
                    }
                    $meta_value_obj = false;
                }
            }
        }

        // Final fallback
        if (!$meta_value_obj || is_string($meta_value_obj)) {
            return $meta_value_date[0];
        }

        $formatted = date_i18n(get_option('date_format'), $meta_value_obj->getTimestamp());

        if (isset($meta_value_date[1])) {
            $time = str_replace('-', '', $meta_value_date[1]);
            $formatted .= esc_html__(' at ', 'listeo_core') . date_i18n(get_option('time_format'), strtotime($time));
        }

        return $formatted;
    }

    private function formatUrl($meta_value)
    {
        if (is_array($meta_value)) {
            return implode(', ', $meta_value);
        }

        return filter_var($meta_value, FILTER_VALIDATE_URL) !== false
            ? ['url' => $meta_value, 'is_link' => true]
            : ['value' => $meta_value, 'is_link' => false];
    }

    private function getIcon($field_config)
    {
        if (empty($field_config['icon'])) {
            return 'fas fa-check';
        }

        return substr($field_config['icon'], 0, 3) === 'im '
            ? $field_config['icon']
            : 'fa ' . $field_config['icon'];
    }

    private function getDisplayType($meta_value, $field_config)
    {
        if ($this->isCheckboxField($meta_value)) {
            return 'checkbox';
        }

        if ($field_config['id'] === '_area') {
            return 'area';
        }

        if ($field_config['type'] === 'file') {
            return 'file';
        }

        if ($field_config['type'] === 'datetime' || in_array($field_config['id'], ['_event_date', '_event_date_end'])) {
            return 'datetime';
        }

        if (!empty($field_config['options'])) {
            return is_array($meta_value) ? 'options_multiple' : 'options_single';
        }

        return 'regular';
    }

    private function isCheckboxField($meta_value)
    {
        return $meta_value === 'check_on' || $meta_value === 'on';
    }

    private function isEmpty($value)
    {
        // Checkbox fields are never empty if they have checkbox values
        if ($this->isCheckboxField($value)) {
            return false;
        }

        if (empty($value)) {
            return true;
        }

        if (is_array($value)) {
            // Check if array is empty
            if (count($value) === 0) {
                return true;
            }

            // Check if array contains only empty values
            $filtered = array_filter($value, function ($item) {
                return !empty($item) && $item !== '' && $item !== null;
            });

            return count($filtered) === 0;
        }

        return false;
    }

    private function getCssClasses($detail_key, $field_config)
    {
        $classes = ['main-detail-' . $field_config['id']];

        if ($field_config['type'] === 'file') {
            $classes[] = 'listeo-download-detail';
        }

        if ($this->isCheckboxField($this->getMetaValue($field_config))) {
            //$classes[] = 'checkboxed';
            $classes[] = 'checkboxed-single single-property-detail-' . $field_config['id'];
        }

        // Add taxonomy-specific classes if this is a taxonomy field
        if (isset($field_config['is_taxonomy_field']) && $field_config['is_taxonomy_field']) {
            $classes[] = 'taxonomy-field';
            $classes[] = 'taxonomy-field-' . $field_config['taxonomy'];
            $classes[] = 'taxonomy-field-' . $field_config['taxonomy'] . '-' . $field_config['term_id'];
        }

        return $classes;
    }
}

// Template Helper Functions (for backward compatibility and ease of use)
function listeo_get_listing_details($post, $include_taxonomy_fields = true)
{
    $handler = new Listeo_Listing_Details_Handler($post, $include_taxonomy_fields);
    return $handler->getProcessedDetails();
}

function listeo_get_standard_listing_details($post)
{
    return listeo_get_listing_details($post, false);
}

function listeo_get_taxonomy_listing_details($post, $specific_taxonomy = null)
{
    $handler = new Listeo_Listing_Details_Handler($post, true);
    $all_details = $handler->getProcessedDetails();

    // Filter to only return taxonomy fields
    return array_filter($all_details, function ($detail) use ($specific_taxonomy) {
        $is_taxonomy_field = isset($detail['config']['is_taxonomy_field']) && $detail['config']['is_taxonomy_field'];

        if (!$is_taxonomy_field) {
            return false;
        }

        // If specific taxonomy is requested, filter by that
        if ($specific_taxonomy !== null) {
            return $detail['config']['taxonomy'] === $specific_taxonomy;
        }

        return true;
    });
}

// Backward compatibility
function listeo_get_category_listing_details($post)
{
    return listeo_get_taxonomy_listing_details($post, 'listing_category');
}

function listeo_render_detail_value($detail)
{
    // Handle checkbox fields
    if ($detail['display_type'] === 'checkbox') {
        $field_config = $detail['config'];
        $default_value = isset($field_config['default']) ? $field_config['default'] : esc_html__('Yes', 'listeo_core');
        return esc_html($default_value);
    }

    if (is_array($detail['processed_value'])) {
        // Handle complex values
        if ($detail['display_type'] === 'area') {
            $value = $detail['processed_value'];
            if (is_array($value['value']) && $value['value']['is_link']) {
                return '<a href="' . esc_url($value['value']['url']) . '" target="_blank">' . esc_html($value['value']['url']) . '</a>';
            }
            return esc_html($value['value']['value'] ?? $value['value']);
        }

        if ($detail['display_type'] === 'file') {
            $file = $detail['processed_value'];
            return '<a href="' . esc_url($file['url']) . '">' . esc_html($file['download_text']) . ' ' . esc_html($file['filename']) . '</a>';
        }

        if ($detail['display_type'] === 'options_multiple') {
            if (isset($detail['processed_value'][0]) && is_array($detail['processed_value'][0])) {
                // Group/repeatable field
                $output = '';
                
               if($detail['config']['type'] == 'multicheck_split'){
                   foreach ($detail['processed_value'] as $item) {
                     if (is_array($item)) {
                        $values = [];
                        foreach ($item as $key => $value) {
                            $values[] = esc_html($value['value']);
                        }
                        $output .= implode(', ', $values);
                     } else {
                       $output .=  esc_html($item['value']);
                        }
                   }
               } else {
                foreach ($detail['processed_value'] as $group) {
                    foreach ($group as $item) {
                        $output .= '<dl><dt>' . esc_html($item['label']) . '</dt><dd>' . esc_html($item['value']) . '</dd></dl>';
                    }
                }
                }
                return $output;
            }
            return implode(', ', array_map('esc_html', $detail['processed_value']));
        }
    }

    if (is_array($detail['processed_value']) && isset($detail['processed_value']['is_link'])) {
        if ($detail['processed_value']['is_link']) {
            return '<a href="' . esc_url($detail['processed_value']['url']) . '" target="_blank">' . esc_html($detail['processed_value']['url']) . '</a>';
        }
        return esc_html($detail['processed_value']['value']);
    }

    return esc_html($detail['processed_value']);
}

?>