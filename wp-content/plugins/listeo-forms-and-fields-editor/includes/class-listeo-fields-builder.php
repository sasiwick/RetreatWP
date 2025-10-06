<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Listeo_Fields_Editor
{

    /**
     * Stores static instance of class.
     *
     * @access protected
     * @var Listeo_Submit The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Returns static instance of class.
     *
     * @return self
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct($version = '1.0.0')
    {

        add_action('admin_menu', array($this, 'add_options_page')); //create tab pages


        add_filter('listeo_event_fields', array($this, 'add_listeo_event_fields_from_editor'));
        add_filter('listeo_service_fields', array($this, 'add_listeo_service_fields_from_editor'));
        add_filter('listeo_rental_fields', array($this, 'add_listeo_rental_fields_from_editor'));
        add_filter('listeo_classifieds_fields', array($this, 'add_listeo_classifieds_fields_from_editor'));
        add_filter('listeo_contact_fields', array($this, 'add_listeo_contact_fields_from_editor'));
        add_filter('listeo_location_fields', array($this, 'add_listeo_location_fields_from_editor'));
        add_filter('listeo_custom_fields', array($this, 'add_listeo_custom_fields_from_editor'));

        // add ajax function that gets the terms based on taxonomy selected in listeo-new-term-fields-taxonomy
        add_action('wp_ajax_listeo_get_terms', array($this, 'ajax_get_terms'));
        add_action('wp_ajax_listeo_new_term_fields_add', array($this, 'listeo_new_term_fields_add'));
    }


    function ajax_get_terms()
    {
        $terms_to_exclude = array();
        $already_saved = get_option('listeo_custom_term_fields', array());
        if (is_array($already_saved) && !empty($already_saved)) {
            foreach ($already_saved as $taxonomy => $terms) {
                $terms_to_exclude = array_merge($terms_to_exclude, array_keys($terms));
            }
        }
        if (!isset($_POST['taxonomy'])) {
            wp_send_json_error(__('Invalid request', 'listeo'));
        }

        $taxonomy = sanitize_text_field($_POST['taxonomy']);
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'exclude' => $terms_to_exclude,
        ));

        if (is_wp_error($terms)) {
            wp_send_json_error($terms->get_error_message());
        }

        $result = array_map(function ($term) {
            return ['id' => $term->term_id, 'name' => $term->name];
        }, $terms);

        wp_send_json_success(['terms' => $result]);
    }

    function listeo_new_term_fields_add()
    {

        if (!isset($_POST['term']) || !isset($_POST['taxonomy'])) {
            wp_send_json_error(__('Invalid request', 'listeo'));
        }


        $term = sanitize_text_field($_POST['term']);
        $taxonomy = sanitize_text_field($_POST['taxonomy']);


        // Add new fields for the term
        $fields = get_option("listeo_{$taxonomy}_fields", array());

        $custom_term_fields = get_option("listeo_custom_term_fields", array());

        if (is_array($custom_term_fields) && !isset($custom_term_fields[$taxonomy])) {
            $custom_term_fields[$taxonomy] = array();
        }

        // i need to save an option that will hold whhich term from which taxonomy has custom fields
        if (!isset($custom_term_fields[$taxonomy][$term])) {
            // push the term id to the custom term fields array
            $custom_term_fields[$taxonomy][$term] = array();
        }
        // Add the new fields to the custom term fields


        update_option("listeo_custom_term_fields", $custom_term_fields);

        update_option("listeo_{$taxonomy}_{$term}_fields", $fields);

        wp_send_json_success(__('Fields added successfully', 'listeo'));
    }

    function add_listeo_contact_fields_from_editor($fields)
    {
        $new_fields =  get_option('listeo_contact_tab_fields');
        if (is_array($new_fields)) {
            $new_fields = array_map(array($this, 'listeo_fields_for_cmb2'), $new_fields);
        }
        if (!empty($new_fields)) {
            $new_fields = array_filter($new_fields, function ($field) {
                return isset($field['type']) && $field['type'] !== 'header';
            });

            $fields['fields'] = $new_fields;
        }
        return $fields;
    }

    function add_listeo_event_fields_from_editor($fields)
    {
        $new_fields =  get_option('listeo_events_tab_fields');
        if (is_array($new_fields)) {
            $new_fields = array_map(array($this, 'listeo_fields_for_cmb2'), $new_fields);
        }
        if (!empty($new_fields)) {
            $new_fields = array_filter($new_fields, function ($field) {
                return isset($field['type']) && $field['type'] !== 'header';
            });

            $fields['fields'] = $new_fields;
        }
        return $fields;
    }

    function add_listeo_custom_fields_from_editor($fields)
    {
        $new_fields =  get_option('listeo_custom_tab_fields');
        if (is_array($new_fields)) {
            $new_fields = array_map(array($this, 'listeo_fields_for_cmb2'), $new_fields);
        }
        if (!empty($new_fields)) {
            $new_fields = array_filter($new_fields, function ($field) {
                return isset($field['type']) && $field['type'] !== 'header';
            });

            $fields['fields'] = $new_fields;
        }
        return $fields;
    }

    function add_listeo_service_fields_from_editor($fields)
    {
        $new_fields =  get_option('listeo_service_tab_fields');
        if (is_array($new_fields)) {
            $new_fields = array_map(array($this, 'listeo_fields_for_cmb2'), $new_fields);
        }
        if (!empty($new_fields)) {
            // in case of field set to type header, remove it
            if (is_admin()) {
                $new_fields = array_filter($new_fields, function ($field) {
                    return isset($field['type']) && $field['type'] !== 'header';
                });
            }

            $fields['fields'] = $new_fields;
        }
        return $fields;
    }
    function add_listeo_classifieds_fields_from_editor($fields)
    {
        $new_fields =  get_option('listeo_classifieds_tab_fields');
        if (is_array($new_fields)) {
            $new_fields = array_map(array($this, 'listeo_fields_for_cmb2'), $new_fields);
        }
        if (!empty($new_fields)) {
            $new_fields = array_filter($new_fields, function ($field) {
                return isset($field['type']) && $field['type'] !== 'header';
            });

            $fields['fields'] = $new_fields;
        }
        return $fields;
    }

    function add_listeo_rental_fields_from_editor($fields)
    {
        $new_fields =  get_option('listeo_rental_tab_fields');
        if (is_array($new_fields)) {
            $new_fields = array_map(array($this, 'listeo_fields_for_cmb2'), $new_fields);
        }
        if (!empty($new_fields)) {
            $new_fields = array_filter($new_fields, function ($field) {
                return isset($field['type']) && $field['type'] !== 'header';
            });

            $fields['fields'] = $new_fields;
        }

        return $fields;
    }

    function add_listeo_location_fields_from_editor($fields)
    {
        $new_fields =  get_option('listeo_locations_tab_fields');

        if (!empty($new_fields)) {
            $new_fields = array_filter($new_fields, function ($field) {
                return isset($field['type']) && $field['type'] !== 'header';
            });

            $fields['fields'] = $new_fields;
        }

        return $fields;
    }

    function listeo_fields_for_cmb2($value)
    {

        if ($value['type'] == 'select') {
            $value['show_option_none'] = true;
        }
        if (is_admin()) {
            if ($value['type'] == 'repeatable') {
                $value['type'] = 'group';
                $value['group_title'] = $value['name'];
                $value['add_button'] = __('Add', 'cmb2');
                $value['remove_button'] = __('Remove', 'cmb2');
                $value['sortable'] = false;
                $x = 0;
                $value['fields'] = array();
                foreach ($value['options'] as $key => $option) {
                    $value['fields'][$x]['name'] = $option;
                    $value['fields'][$x]['id'] = $key;
                    $value['fields'][$x]['type'] = 'text';
                    $x++;
                }
            }
        }
        return $value;
    }
    /**
     * Add menu options page
     * @since 0.1.0
     */
    public function add_options_page()
    {
        add_submenu_page('listeo-fields-and-form', 'Listing Fields', 'Listing Fields', 'manage_options', 'listeo-fields-builder', array($this, 'output'));
    }
    public function output()
    {

        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'contact_tab';
        $tab_term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        $tabs = array(
            'contact_tab'  => __('Contact Fields', 'listeo-fafe'),
            'locations_tab' => __('Locations Fields', 'listeo-fafe'),
            'events_tab'   => __('Events Fields', 'listeo-fafe'),
            'service_tab'  => __('Service Fields', 'listeo-fafe'),
            'rental_tab'   => __('Rental Fields', 'listeo-fafe'),
            'classifieds_tab'   => __('Classifieds Fields', 'listeo-fafe'),
            'custom_tab'   => __('Custom Fields', 'listeo-fafe'),
        );

        // get all taxonomies for listing post type
        $taxonomies = get_object_taxonomies('listing', 'objects');
        $taxonomies_array = array();
        if (!empty($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                $taxonomies_array[$taxonomy->name] = $taxonomy->label;
                //  $tabs[$taxonomy->name] = $taxonomy->label . ' ' . __('Fields', 'listeo-fafe');
            }
        }

        if (!empty($_GET['reset-fields']) && !empty($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'reset')) {
            delete_option("listeo_{$tab}_fields");
            echo '<div class="updated"><p>' . __('The fields were successfully reset.', 'listeo') . '</p></div>';
        }
        // handle delete-fields


        // handle delete-term-fields

        if (!empty($_GET['delete-fields']) && $tab && $tab_term) {

            // Delete the specific term fields option
            delete_option("listeo_{$tab}_term_{$tab_term}_fields");
            // remove 'tax-' from tab name
            $checktab = str_replace('tax-', '', $tab);
            // Load the full config
            $custom_terms = get_option('listeo_custom_term_fields', array());

            // Remove the term from its taxonomy group
            if (isset($custom_terms[$checktab][$tab_term])) {
                unset($custom_terms[$checktab][$tab_term]);

                // If the taxonomy has no more terms, remove it too
                if (empty($custom_terms[$checktab])) {
                    unset($custom_terms[$checktab]);
                }

                // Update the option
                update_option('listeo_custom_term_fields', $custom_terms);
            }

            // Optional: redirect to same page without query param to prevent re-deletion on refresh
            //. I need to redirect to admin.php?page=listeo-fields-builder
            wp_safe_redirect(admin_url('admin.php?page=listeo-fields-builder'));
            exit;
        }

        // handle save fields

        if (!empty($_POST)) { /* add nonce tu*/

            echo $this->form_editor_save($tab, $tab_term); //save fields
        }


        $field_types = apply_filters(
            'listeo_form_field_types',
            array(
                'text'              => __('Text', 'listeo-editor'),
                'datetime'          => __('Date time', 'listeo-editor'),
                'textarea'          => __('Textarea', 'listeo-editor'),
                'repeatable'            => __('Repeatable', 'listeo-editor'),
                'select'            => __('Select', 'listeo-editor'),
                'select_multiple'   => __('Multi Select', 'listeo-editor'),
                'checkbox'          => __('Checkbox', 'listeo-editor'),
                'multicheck_split'  => __('Multi Checkbox', 'listeo-editor'),
                'file'              => __('File upload', 'listeo-editor'),
            )
        );

        // $predefined_options = apply_filters( 'listeo_predefined_options', array(
        //     'listeo_get_property_types'     => __( 'Property Types list', 'listeo-editor' ),
        //     'listeo_get_offer_types_flat'        => __( 'Offer Types list', 'listeo-editor' ),
        //     'listeo_get_rental_period'         => __( 'Rental Period list', 'listeo-editor' ),
        // ) );
        switch ($tab) {
            case 'events_tab':
                $default_fields = Listeo_Core_Meta_Boxes::meta_boxes_event(); //filter listeo_event_fields
                break;
            case 'contact_tab':
                $default_fields = Listeo_Core_Meta_Boxes::meta_boxes_contact();
                break;
            case 'service_tab':
                $default_fields = Listeo_Core_Meta_Boxes::meta_boxes_service();
                break;
            case 'rental_tab':
                $default_fields = Listeo_Core_Meta_Boxes::meta_boxes_rental();
                break;
            case 'classifieds_tab':
                $default_fields = Listeo_Core_Meta_Boxes::meta_boxes_classifieds();
                break;

            case 'locations_tab':
                $default_fields = Listeo_Core_Meta_Boxes::meta_boxes_location();
                break;

            case 'custom_tab':
                $default_fields = Listeo_Core_Meta_Boxes::meta_boxes_custom();
                break;

            default:
                $default_fields = Listeo_Core_Meta_Boxes::meta_boxes_event();
                break;
        }

        // if tab starts with "tax-" then set $default_fields = array();
        if (strpos($tab, 'tax-') === 0) {
            $default_fields = array();
        }

        // if tab is custom_tab then set $default_fields = array();
        if ($tab == 'custom_tab') {
            $default_fields = array();
        }

        // get fields from options
        // Check if the option exists in the database
        $option_exists = false;
        $all_options = wp_load_alloptions();
        $option_key = "listeo_{$tab}_fields";
        if (array_key_exists($option_key, $all_options)) {
            $option_exists = true;
        }

        $options = get_option($option_key);

        if (!$option_exists) {
            // Option does not exist, load defaults
            $fields = $default_fields;
        } else {
            // Option exists, use its value (even if empty)
            $fields = $options;
        }

        if ($tab_term) {
            // if term is set then get fields for that term
            $term_fields = get_option("listeo_{$tab}_term_{$tab_term}_fields");

            if (!empty($term_fields)) {
                $fields = $term_fields;
            } else {
                $fields = array();
            }
        }

        if (isset($fields['fields'])) {
            $fields = $fields['fields'];
        }

        $form_action_url = 'admin.php?page=listeo-fields-builder&tab=' . esc_attr($tab);

        if (!empty($tab_term)) {
            $form_action_url .= '&term=' . esc_attr($tab_term);
        }


?>
        <div class="modal micromodal-slide" id="listeo-new-term-fields-form-dialog" aria-hidden="true">
            <div class="modal__overlay" tabindex="-1" data-micromodal-close>
                <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-term-title">
                    <header class="modal__header">
                        <h2 class="modal__title" id="modal-term-title">Add Term Fields</h2>
                        <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                    </header>
                    <main class="modal__content">
                        <p style="max-width:300px;">Define custom fields for selected taxonomy term.</p>
                        <form action="" id="listeo-new-term-fields-form">
                            <div class="form-field">
                                <label for="listeo-new-term-fields-taxonomy">Taxonomy to choose</label>
                                <select id="listeo-new-term-fields-taxonomy" name="listeo-new-term-fields-taxonomy">
                                    <?php
                                    foreach ($taxonomies_array as $key => $value) {
                                        echo '<option value="' . esc_attr($key) . '">' . esc_html($value) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="listeo-new-term-fields-term">Term</label>
                                <select id="listeo-new-term-fields-term" name="listeo-new-term-fields-term">

                                    <?php
                                    //get terms for selected taxonomy
                                    if (!empty($taxonomies_array)) {
                                        $terms_to_exclude = array();
                                        $already_saved = get_option('listeo_custom_term_fields', array());
                                        if (is_array($already_saved) && !empty($already_saved)) {
                                            foreach ($already_saved as $taxonomy => $terms) {
                                                $terms_to_exclude = array_merge($terms_to_exclude, array_keys($terms));
                                            }
                                        }
                                        $terms = get_terms(array(
                                            'taxonomy' => 'listing_category',
                                            'hide_empty' => false,
                                            'exclude' => $terms_to_exclude,
                                        ));

                                        if (!is_wp_error($terms) && !empty($terms)) {
                                            foreach ($terms as $term) {
                                                echo '<option value="' . esc_attr($term->term_id) . '">' . esc_html($term->name) . '</option>';
                                            }
                                        }
                                    }
                                    ?></select>
                            </div>
                            <?php wp_nonce_field('listeo_get_terms', 'listeo_get_terms_nonce'); ?>
                            <input type="submit" class="button button-primary" value="Create">
                            <div class="spinner"></div>
                        </form>
                    </main>
                </div>
            </div>
        </div>


        <h2>Listeo Fields Editor</h2>
        <div class="updated listeo-admin-notice">
            <p style="font-weight: 600; font-size:16px;">This fields are created to extend the custom fields that are default in theme. <br> Only fields added in Events, Service and Rental Tab are automatically displayed in the listing template. <a href=" https://docs.purethemes.net/listeo/knowledge-base/adding-custom-fields-and-displaying-them-on-listing-page/" target="_blank">Learn more ⤴</a> </p>
        </div>
        <div class="listeo-editor-wrap">
            <div class="nav-tab-container">
                <h2 class="nav-tab-wrapper  form-builder">
                    <span> Listings Fields</span>
                    <?php
                    foreach ($tabs as $key => $value) {

                        $active = ($key == $tab) ? 'nav-tab-active' : '';

                        echo '<a class="nav-tab ' . $active . '" href="' . admin_url('admin.php?page=listeo-fields-builder&tab=' . esc_attr($key)) . '">' . esc_html($value) . '</a>';
                        // if ($key == 'custom_tab') {
                        //     echo '<span class="nav-tab-title"> Taxonomy Fields</span>';
                        // }
                    }

                    $custom_terms = get_option('listeo_custom_term_fields');

                    if (!empty($custom_terms) && is_array($custom_terms)) {
                        foreach ($custom_terms as $taxonomy => $term) {
                            // $term is array I need to get keys of this array
                            $taxonomy_obj = get_taxonomy($taxonomy);
                            echo '<span class="nav-tab-subtitle">' . esc_html($taxonomy_obj->labels->name) . ' Fields</span>';
                            foreach ($term as $key => $value) {

                                $active = ($key == $tab_term) ? 'nav-tab-active' : '';
                                $term_obj = get_term($key, $taxonomy);
                                echo '<a class="nav-tab  ' . $active . '" href="' . admin_url('admin.php?page=listeo-fields-builder&tab=tax-' . esc_attr($taxonomy)) . '&term=' . $key . '">' . esc_html($term_obj->name) . ' Fields</a>';
                            }
                            // $term is id of term so we need to get term name

                        }
                    }
                    ?>

                    <a id="add-new-listeo-term-fields" class="nav-tab">+ Add term fields</a>


                </h2>
            </div>
            <div class="wrap listeo-form-editor listeo-forms-builder listeo-fields-builder">
                <form method="post" id="mainform" action="<?php echo esc_url($form_action_url); ?>">
                    <h3 class="listeo-editor-form-header">
                        <?php
                        foreach ($tabs as $key => $value) {
                            if ($active = ($key == $tab)) {
                                echo esc_html__($value);
                            }
                        } ?>
                        <input name="Submit" type="submit" class="button-primary" value="Save Settings">
                    </h3>
                    <div class="listeo-forms-builder-top">
                        <div class="form-editor-container" id="listeo-fafe-fields-editor" data-clone="<?php
                                                                                                        ob_start();
                                                                                                        $index = -2;
                                                                                                        $field_key = 'clone';
                                                                                                        $field = array(
                                                                                                            'name' => 'clone',
                                                                                                            'id' => '_clone',
                                                                                                            'type' => 'text',
                                                                                                            'invert' => '',
                                                                                                            'desc' => '',
                                                                                                            'options_source' => '',
                                                                                                            'options_cb' => '',
                                                                                                            'options' => array()
                                                                                                        ); ?>
                <div class=" form_item" data-priority="<?php echo  $index; ?>">
                            <span class="handle dashicons dashicons-editor-justify"></span>
                            <div class="element_title"><?php echo esc_attr($field['name']);  ?> <span>(<?php echo $field['type']; ?>)</span> </div>
                            <?php include(plugin_dir_path(__DIR__) . 'views/form-field-edit.php'); ?>
                            <div class="remove_item"> <span class="dashicons dashicons-remove"></span> </div>
                        </div>
                        <?php echo esc_attr(ob_get_clean()); ?>">

                        <?php
                        $index = 0;

                        foreach ($fields as $field_key => $field) {
                            $index++;

                            if (is_array($field)) { ?>
                                <div class="form_item form_item_type_<?php echo esc_attr($field['type']); ?>" data-id="<?php echo esc_attr($field['id']); ?>">
                                    <span class="handle dashicons dashicons-editor-justify"></span>
                                    <div class="element_title"><?php echo esc_attr($field['name']);  ?>
                                        <div class="element_title_edit"><span class="dashicons dashicons-edit"></span> Edit</div>
                                    </div>
                                    <?php include(plugin_dir_path(__DIR__) . 'views/form-field-edit.php'); ?>
                                    <div class="remove_item"> <span class="dashicons dashicons-remove"></span> </div>
                                </div>
                        <?php }
                        }  ?>

                        <div class="droppable-helper"></div>
                    </div>
                    <a class="add_new_item button-primary add-field" href="#"><?php _e('Add field', 'listeo'); ?></a>

                    <a class="add_new_item button-primary add-headline" href="#"><?php _e('Add Headline', 'listeo'); ?></a>
            </div>

            <?php wp_nonce_field('save-' . $tab); ?>

            <div class="listeo-forms-builder-bottom">
                <input type="hidden" name="tab_term" id="tab_term" value="<?php echo esc_attr($tab_term); ?>">
                <input type="submit" class="save-fields button-primary" value="<?php _e('Save Changes', 'listeo'); ?>" />
                <?php
                if (isset($tab_term) && !empty($tab_term)) { ?>
                    <a href="<?php echo wp_nonce_url(add_query_arg(array('delete-fields' => 1, 'term' => $tab_term)), 'reset'); ?>" class="reset button-secondary"><?php _e('Delete this term', 'listeo'); ?></a>
                <?php } else { ?>
                    <a href="<?php echo wp_nonce_url(add_query_arg('reset-fields', 1), 'reset'); ?>" class="reset button-secondary"><?php _e('Reset to defaults', 'listeo'); ?></a>
                <?php } ?>

            </div>
            </form>
        </div>
        </div>
        <div class="modal micromodal-slide" id="listeo-add-field-modal" aria-hidden="true">
            <div class="modal__overlay" tabindex="-1" data-micromodal-close>
                <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="add-field-title">
                    <header class="modal__header">
                        <h2 class="modal__title" id="add-field-title">Add New Field</h2>
                        <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                    </header>
                    <main class="modal__content">
                        <form id="listeo-add-field-form">
                            <label for="new-field-name">Field name</label>
                            <input type="text" id="new-field-name" name="new-field-name" required minlength="2" style="width: 100%; padding: 6px;">
                            <input type="hidden" id="new-field-tab" value="<?php echo esc_attr($tab); ?>" name="new-field-tab" >
                            <div style="margin-top: 1rem;">
                                <button type="submit" class="button button-primary">Add Field</button>
                            </div>
                        </form>
                    </main>
                </div>
            </div>
        </div>
        <div class="modal micromodal-slide" id="listeo-add-headline-modal" aria-hidden="true">
            <div class="modal__overlay" tabindex="-1" data-micromodal-close>
                <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="add-headline-title">
                    <header class="modal__header">
                        <h2 class="modal__title" id="add-headline-title">Add Headline</h2>
                        <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                    </header>
                    <main class="modal__content">
                        <form id="listeo-add-headline-form">
                            <label for="new-headline-title">Headline title</label>
                            <input type="text" id="new-headline-title" name="new-headline-title" required minlength="2" style="width: 100%; padding: 6px;">
                            <div style="margin-top: 1rem;">
                                <button type="submit" class="button button-primary">Add Headline</button>
                            </div>
                        </form>
                    </main>
                </div>
            </div>
        </div>

        <?php wp_nonce_field('save-fields'); ?>
<?php
    }



    private function form_editor_save($tab, $tab_term = '')
    {


        // Get tab_term from POST data if not provided as parameter
        if (empty($tab_term) && !empty($_POST['tab_term'])) {
            $tab_term = sanitize_text_field($_POST['tab_term']);
        }

        $original_tab = $tab;

        $field_name             = !empty($_POST['name']) ? array_map('sanitize_textarea_field', $_POST['name'])                     : array();
        $field_id               = !empty($_POST['id']) ? array_map('sanitize_text_field', $_POST['id'])                         : array();
        $field_icon               = !empty($_POST['icon']) ? array_map('sanitize_text_field', $_POST['icon'])                         : array();
        $field_type             = !empty($_POST['type']) ? array_map('sanitize_text_field', $_POST['type'])                     : array();
        $field_invert             = !empty($_POST['invert']) ? array_map('sanitize_text_field', $_POST['invert'])                     : array();
        $field_showonfront             = !empty($_POST['showonfront']) ? array_map('sanitize_text_field', $_POST['showonfront'])                     : array();
        $field_addtosearch             = !empty($_POST['addtosearch']) ? array_map('sanitize_text_field', $_POST['addtosearch'])                     : array();
        $field_desc             = !empty($_POST['desc']) ? array_map('sanitize_text_field', $_POST['desc'])                    : array();
        $field_options_cb       = !empty($_POST['options_cb']) ? array_map('sanitize_text_field', $_POST['options_cb'])        : array();
        $field_options_source   = !empty($_POST['options_source']) ? array_map('sanitize_text_field', $_POST['options_source']) : array();
        $field_options          = !empty($_POST['options']) ? $this->sanitize_array($_POST['options'])                : array();
        $field_default          = !empty($_POST['default']) ? $this->sanitize_array($_POST['default'])                : array();
        $new_fields             = array();
        $index                  = 0;

        foreach ($field_name as $key => $field) {

            if (empty($field_name[$key])) {
                continue;
            }
            $name            = sanitize_title($field_id[$key]);
            $options        = array();
            if (!empty($field_options[$key])) {
                foreach ($field_options[$key] as $op_key => $op_value) {
                    $options[stripslashes($op_value['name'])] = stripslashes($op_value['value']);
                }
            }

            $new_field                      = array();
            $new_field['name']              = stripslashes($field_name[$key]);
            $new_field['id']                = $field_id[$key];
            $new_field['icon']              = $field_icon[$key];
            $new_field['type']              = $field_type[$key];
            $new_field['invert']            = isset($field_invert[$key]) ? $field_invert[$key] : false;
            $new_field['showonfront']       = isset($field_showonfront[$key]) ? $field_showonfront[$key] : false;
            $new_field['addtosearch']       = isset($field_addtosearch[$key]) ? $field_addtosearch[$key] : false;
            $new_field['desc']              = $field_desc[$key];
            $new_field['default']           = $field_default[$key];
            // $new_field['options_source']    = $field_options_source[ $key ];
            // $new_field['options_cb']        = $field_options_cb[ $key ];
            if (!empty($field_options_cb[$key])) {
                $new_field['options']           = array();
            } else {
                $new_field['options']           = $options;
            }

            $new_fields[$name]       = $new_field;
        }

        // if tab starts with "tax-" then save fields for term
        if (strpos($original_tab, 'tax-') === 0 && !empty($tab_term)) {


            $tab_term = sanitize_text_field($tab_term);

            update_option("listeo_{$original_tab}_term_{$tab_term}_fields", $new_fields);
            echo '<div class="updated"><p>' . __('The fields for term were successfully saved.', 'listeo-editor') . '</p></div>';
            return;
        } else {
            
            $result = update_option("listeo_{$tab}_fields", $new_fields);
        }
        if (true === $result) {
            echo '<div class="updated"><p>' . __('The fields were successfully saved.', 'listeo-editor') . '</p></div>';
        }
    }

    /**
     * Sanitize a 2d array
     * @param  array $array
     * @return array
     */
    private function sanitize_array($input)
    {
        if (is_array($input)) {
            foreach ($input as $k => $v) {
                $input[$k] = $this->sanitize_array($v);
            }
            return $input;
        } else {
            return sanitize_text_field($input);
        }
    }
}
