<?php
/**
 * Template Processor Helper Class
 * 
 * This class extracts the template validation and processing logic from PMXI_Admin_Import
 * so it can be used in AJAX contexts (like preview) without triggering redirects or
 * requiring full controller initialization.
 */
class PMXI_Template_Processor {
    
    public $errors;
    public $warnings;
    public $import_record;
    
    public function __construct($import_record = null) {
        $this->errors = new WP_Error();
        $this->warnings = new WP_Error();
        $this->import_record = $import_record;
    }
    
    /**
     * Validate a template string for XPath/PHP syntax errors
     * This is the EXACT same logic as PMXI_Admin_Import::_validate_template() (line 1879)
     *
     * @param string $template The template string to validate
     * @param string $field_name The field name for error messages
     */
    protected function _validate_template($template, $field_name) {
        try {
            if ($template != '') {
                $scanner = new XmlImportTemplateScanner();
                $tokens = $scanner->scan(new XmlImportStringReader($template));
                $parser = new XmlImportTemplateParser($tokens);
                $parser->parse();
            }
        } catch (XmlImportException $e) {
            $this->errors->add('form-validation', sprintf(__('%s template is invalid: %s', 'wp-all-import-pro'), $field_name, $e->getMessage()));
        }
    }
    
    /**
     * Process and validate template options
     * This replicates the exact logic from PMXI_Admin_Import::template() method (lines 1584-1680)
     * but without any redirects or nonce checks.
     * 
     * @param array $post The template options to process
     * @return array|false Processed options on success, false on validation error
     */
    public function process($post) {
        // Load functions file (same as controller does at lines 1588-1592)
        $wp_uploads = wp_upload_dir();
        $functions = $wp_uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_IMPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php';
        $functions = apply_filters('import_functions_file_path', $functions);
        if (@file_exists($functions) && PMXI_Plugin::$is_php_allowed) {
            \Wpai\Integrations\CodeBox::requireFunctionsFile();
        }

        // Validate title (lines 1594-1599)
        if (!empty($post['title'])) {
            $this->_validate_template($post['title'], 'Post title');
        } elseif (wp_all_import_is_title_required($post['custom_type'])) {
            $this->warnings->add('1', __('<strong>Warning:</strong> your title is blank.', 'wp-all-import-pro'));
        }

        // Validate content (lines 1601-1606)
        if (!empty($post['content'])) {
            $this->_validate_template($post['content'], 'Post content');
        } elseif (wp_all_import_is_title_required($post['custom_type'])) {
            $this->warnings->add('2', __('<strong>Warning:</strong> your content is blank.', 'wp-all-import-pro'));
        }

        // Check if BOTH title and content are empty - this should be an ERROR, not just a warning
        if (empty($post['title']) && empty($post['content']) && wp_all_import_is_title_required($post['custom_type'])) {
            $this->errors->add('form-validation', __('<strong>Error:</strong> Both title and content are blank. At least one must be set.', 'wp-all-import-pro'));
        }

        // If there are validation errors, return false
        if ($this->errors->get_error_codes()) {
            return false;
        }
        
        // Apply the pmxi_save_options filter - CRITICAL for WooCommerce and other addons (line 1611)
        $post = apply_filters('pmxi_save_options', $post, $this->import_record);
        
        // Validate post excerpt (line 1614)
        if (!empty($post['post_excerpt'])) {
            $this->_validate_template($post['post_excerpt'], __('Excerpt', 'wp-all-import-pro'));
        }
        
        // Validate images (lines 1615-1620)
        if (isset($post['download_images']) && $post['download_images'] == 'yes') {
            if (!empty($post['download_featured_image'])) {
                $this->_validate_template($post['download_featured_image'], __('Images', 'wp-all-import-pro'));
            }
        } else {
            if (!empty($post['featured_image'])) {
                $this->_validate_template($post['featured_image'], __('Images', 'wp-all-import-pro'));
            }
        }
        
        // Validate image meta data (lines 1621-1627)
        foreach (array('title', 'caption', 'alt', 'decription') as $section) {
            if (!empty($post['set_image_meta_' . $section])) {
                if (!empty($post['image_meta_' . $section])) {
                    $this->_validate_template($post['image_meta_' . $section], __('Images meta ' . $section, 'wp-all-import-pro'));
                }
            }
        }
        
        // Remove entries where both custom_name and custom_value are empty (lines 1629-1633)
        if (isset($post['custom_name']) && is_array($post['custom_name']) && 
            isset($post['custom_value']) && is_array($post['custom_value'])) {
            $not_empty = array_flip(array_values(array_merge(
                array_keys(array_filter($post['custom_name'], 'strlen')), 
                array_keys(array_filter($post['custom_value'], 'strlen'))
            )));
            $post['custom_name'] = array_intersect_key($post['custom_name'], $not_empty);
            $post['custom_value'] = array_intersect_key($post['custom_value'], $not_empty);
        }
        
        // Validate custom field names and values (lines 1636-1643)
        if (isset($post['custom_name']) && is_array($post['custom_name'])) {
            foreach ($post['custom_name'] as $custom_name) {
                $this->_validate_template($custom_name, __('Custom Field Name', 'wp-all-import-pro'));
            }
        }
        if (isset($post['custom_value']) && is_array($post['custom_value'])) {
            foreach ($post['custom_value'] as $key => $custom_value) {
                if (empty($post['custom_format'][$key])) {
                    $this->_validate_template($custom_value, __('Custom Field Value', 'wp-all-import-pro'));
                }
            }
        }
        
        // For WooCommerce products, remove empty attribute entries and validate (lines 1645-1661)
        if (isset($post['type']) && $post['type'] == "post" && 
            isset($post['custom_type']) && $post['custom_type'] == "product" && 
            class_exists('PMWI_Plugin')) {
            if (isset($post['attribute_name']) && is_array($post['attribute_name']) && 
                isset($post['attribute_value']) && is_array($post['attribute_value'])) {
                $not_empty = array_flip(array_values(array_merge(
                    array_keys(array_filter($post['attribute_name'], 'strlen')), 
                    array_keys(array_filter($post['attribute_value'], 'strlen'))
                )));
                $post['attribute_name'] = array_intersect_key($post['attribute_name'], $not_empty);
                $post['attribute_value'] = array_intersect_key($post['attribute_value'], $not_empty);
                
                // Validate that both name and value are set
                if (array_keys(array_filter($post['attribute_name'], 'strlen')) != array_keys(array_filter($post['attribute_value'], 'strlen'))) {
                    $this->errors->add('form-validation', __('Both name and value must be set for all woocommerce attributes', 'wp-all-import-pro'));
                } else {
                    foreach ($post['attribute_name'] as $attribute_name) {
                        $this->_validate_template($attribute_name, __('Attribute Field Name', 'wp-all-import-pro'));
                    }
                    foreach ($post['attribute_value'] as $custom_value) {
                        $this->_validate_template($custom_value, __('Attribute Field Value', 'wp-all-import-pro'));
                    }
                }
            }
        }
        
        // Validate tags (lines 1663-1666)
        if (isset($post['type']) && $post['type'] == 'post' && isset($post['tags']) && $post['tags'] !== '') {
            $this->_validate_template($post['tags'], __('Tags', 'wp-all-import-pro'));
        }
        
        // Validate dates (lines 1667-1672)
        if (isset($post['date_type']) && $post['date_type'] == 'specific') {
            if (isset($post['date']) && $post['date'] !== '') {
                $this->_validate_template($post['date'], __('Date', 'wp-all-import-pro'));
            }
        } else {
            if (isset($post['date_start']) && $post['date_start'] !== '') {
                $this->_validate_template($post['date_start'], __('Start Date', 'wp-all-import-pro'));
            }
            if (isset($post['date_end']) && $post['date_end'] !== '') {
                $this->_validate_template($post['date_end'], __('End Date', 'wp-all-import-pro'));
            }
        }
        
        // Apply validation filter (line 1674)
        $this->errors = apply_filters('pmxi_options_validation', $this->errors, $post, $this->import_record);
        
        // If there are validation errors, return false
        if ($this->errors->get_error_codes()) {
            return false;
        }
        
        // Assign date defaults if empty (lines 1678-1680)
        if (!isset($post['date']) || $post['date'] === '') {
            $post['date'] = 'now';
        }
        if (!isset($post['date_start']) || $post['date_start'] === '') {
            $post['date_start'] = 'now';
        }
        if (!isset($post['date_end']) || $post['date_end'] === '') {
            $post['date_end'] = 'now';
        }
        
        return $post;
    }
}

