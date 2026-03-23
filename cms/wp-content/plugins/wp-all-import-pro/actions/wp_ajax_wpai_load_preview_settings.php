<?php
/**
 * AJAX handler to load step 4 import settings for the preview modal
 */

function pmxi_wp_ajax_wpai_load_preview_settings() {
    // Security check
    if (!check_ajax_referer('wp_all_import_secure', 'security', false)) {
        wp_send_json_error(array('message' => __('Security check failed', 'wp-all-import-pro')), 403);
    }

    if (!current_user_can(PMXI_Plugin::$capabilities)) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-all-import-pro')), 403);
    }

    try {
        $input = new PMXI_Input();
        $import_id = isset($_POST['import_id']) ? intval($_POST['import_id']) : 0;
        $is_new_import = empty($import_id) || $import_id === 0;

        // Get post data
        if ($is_new_import) {
            // For new imports, get from session
            if (empty(PMXI_Plugin::$session)) {
                wp_send_json_error(array('message' => __('No import session found', 'wp-all-import-pro')), 400);
            }

            // Get default options
            $default_options = PMXI_Plugin::get_default_import_options();
            $session_options = PMXI_Plugin::$session->options ?: array();
            $post = array_merge($default_options, $session_options);

            // Add custom type
            $post['custom_type'] = PMXI_Plugin::$session->options['custom_type'] ?? 'post';

            // Auto-detect unique key if not set (same logic as real imports)
            $auto_detected_key = '';
            if (empty($post['unique_key'])) {
                $file_path = PMXI_Plugin::$session->filePath ?? '';
                if (!empty($file_path) && function_exists('wpai_find_unique_key_for_preview')) {
                    $auto_detected_key = wpai_find_unique_key_for_preview($post, $file_path);
                    // Set as default value for new imports
                    $post['unique_key'] = $auto_detected_key;
                }
            } else {
                // If unique_key is already set, use it as the auto-detected value
                $auto_detected_key = $post['unique_key'];
            }

            // Set tmp_unique_key for auto-detect functionality
            $post['tmp_unique_key'] = $auto_detected_key;
        } else {
            // For existing imports, load from database
            $import = new PMXI_Import_Record();
            $import->getById($import_id);

            if ($import->isEmpty()) {
                wp_send_json_error(array('message' => __('Import not found', 'wp-all-import-pro')), 404);
            }

            $post = $import->options;
            $post['custom_type'] = $import->options['custom_type'] ?? 'post';

            // Auto-detect unique key if not set (same logic as real imports)
            $auto_detected_key = '';
            if (empty($post['unique_key']) && function_exists('wpai_find_unique_key_for_preview')) {
                // Get the file path from import record
                $history_file = new PMXI_File_Record();
                $history_file->getBy(array('import_id' => $import->id), 'id DESC');

                if (!$history_file->isEmpty()) {
                    $file_path = $history_file->path;
                    $auto_detected_key = wpai_find_unique_key_for_preview($post, $file_path);
                    // Set as default value if not already set
                    $post['unique_key'] = $auto_detected_key;
                } else {
                    $file_path = $import->path;
                    if (!empty($file_path)) {
                        $auto_detected_key = wpai_find_unique_key_for_preview($post, $file_path);
                        // Set as default value if not already set
                        $post['unique_key'] = $auto_detected_key;
                    }
                }
            } else {
                // If unique_key is already set, use it as the auto-detected value
                $auto_detected_key = $post['unique_key'];
            }

            // Set tmp_unique_key for auto-detect functionality
            $post['tmp_unique_key'] = $auto_detected_key;
        }

        // Set up context variables
        $post_type = $post['custom_type'];
        $custom_type = get_post_type_object($post_type);
        $cpt_name = $custom_type ? $custom_type->labels->name : 'items';

        // Set up visible sections
        $visible_sections = apply_filters('pmxi_visible_options_sections', array('reimport', 'settings'), $post_type);

        // Set wizard context
        $isWizard = $is_new_import;

        // Set up existing_meta_keys for the template (empty for preview)
        $existing_meta_keys = array();

        // Create a template renderer class to provide $this context
        $renderer = new class($isWizard) {
            public $isWizard;
            public $errors;
            public $baseUrl;

            public function __construct($isWizard) {
                $this->isWizard = $isWizard;
                $this->errors = new WP_Error(); // Empty error object for preview
                $this->baseUrl = admin_url('admin.php'); // Base URL for links
            }

            public function render($template_file, $vars = array()) {
                extract($vars);
                include($template_file);
            }
        };

        // Start output buffering
        ob_start();

        ?>
        <div class="wpai-preview-settings-wrapper">
            <div class="wpai-unique-identifier-section">
                <div class="wpallimport-unique-key-wrapper">
                    <label style="font-weight: bold; display: block; margin-bottom: 4px;"><?php _e("Unique Identifier", "wp-all-import-pro"); ?></label>

                    <div class="wpai-unique-key-actions" style="margin-bottom: 8px;">
                        <a href="javascript:void(0);" class="wpai-auto-detect-link" id="wpai-auto-detect-unique-key" data-auto-detected-key="<?php echo esc_attr($post['tmp_unique_key'] ?? ''); ?>">
                            <?php _e('Auto-detect', 'wp-all-import-pro'); ?>
                        </a>
                        <span style="margin: 0 4px; color: #ddd;">|</span>
                        <a href="javascript:void(0);" class="wpai-toggle-field-list" data-state="closed">
                            <?php _e('Show Available Fields', 'wp-all-import-pro'); ?>
                        </a>
                    </div>

                    <div class="wpai-unique-key-input-wrapper">
                        <input type="text"
                               class="smaller-text wpallimport-unique-key-input wpai-preview-unique-key"
                               name="unique_key"
                               value="<?php echo esc_attr($post['unique_key'] ?? ''); ?>" />
                        <input type="hidden" id="wpai-tmp-unique-key" value="<?php echo esc_attr($post['tmp_unique_key'] ?? ''); ?>" />
                    </div>

                    <p class="description wpai-unique-identifier-description" style="margin-top: 8px; color: #646970; font-size: 13px;">
                        <?php if ($is_new_import): ?>
                            <?php _e('Build your unique identifier using available fields from your import file.', 'wp-all-import-pro'); ?>
                        <?php else: ?>
                            <?php _e('Test different unique identifiers for preview purposes. Changes here only affect preview records, not your actual import configuration.', 'wp-all-import-pro'); ?>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Field List Sidebar (hidden by default) -->
                <div class="wpai-field-list-sidebar" style="display: none;">
                    <div class="wpai-field-list-header">
                        <h4><?php _e('Available Fields', 'wp-all-import-pro'); ?></h4>
                        <p class="description"><?php _e('Drag fields into the Unique Identifier field above', 'wp-all-import-pro'); ?></p>
                    </div>
                    <div class="wpai-field-list-content">
                        <div id="wpai-preview-field-container">
                            <p style="text-align: center; padding: 20px; color: #666;">
                                <?php _e('Loading available fields...', 'wp-all-import-pro'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // Show post status selector for post-based imports (not users, taxonomies, comments, orders, etc.)
            // Orders have their own status system (wc-pending, wc-processing, etc.) managed by WooCommerce
            $import_category = wpai_get_import_category($post_type);
            if ($import_category === 'posts' && $post_type !== 'shop_order'):
                // Get all registered post statuses
                // get_post_stati() returns all registered statuses, not just those with existing posts
                $all_statuses = get_post_stati(array(), 'objects');

                // Build available statuses array, excluding internal and irrelevant statuses
                $available_statuses = array();
                foreach ($all_statuses as $status_key => $status_obj) {
                    // Skip internal statuses (like 'auto-draft', 'inherit', etc.)
                    if ($status_obj->internal) {
                        continue;
                    }

                    // Skip WooCommerce order statuses (wc-*) unless we're importing orders
                    if (strpos($status_key, 'wc-') === 0 && $post_type !== 'shop_order') {
                        continue;
                    }

                    $available_statuses[$status_key] = $status_obj;
                }

                // Apply filter to allow post-type-specific status filtering
                $available_statuses = apply_filters('wpai_preview_post_statuses', $available_statuses, $post_type);

                // Get the template's configured post status
                // If status is 'xpath', we can't determine it statically, so leave empty (will use template value)
                $template_status = '';
                if (!empty($post['status']) && $post['status'] !== 'xpath') {
                    $template_status = $post['status'];
                }
            ?>
            <div class="wpai-post-status-section">
                <div class="wpallimport-post-status-wrapper">
                    <label style="font-weight: bold;"><?php _e("Preview Post Status", "wp-all-import-pro"); ?></label>
                    <select name="preview_post_status" id="wpai-preview-post-status" class="wpai-preview-post-status-select" data-template-status="<?php echo esc_attr($template_status); ?>">
                        <option value=""><?php _e('Use Post Status Configured in Template', 'wp-all-import-pro'); ?></option>
                        <?php foreach ($available_statuses as $status_key => $status_obj): ?>
                            <option value="<?php echo esc_attr($status_key); ?>" <?php selected('draft', $status_key); ?>>
                                <?php echo esc_html($status_obj->label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description" style="margin-top: 8px;">
                        <?php _e('Choose a post status for preview records. Select "Use Post Status Configured in Template" to use the status from your import template.', 'wp-all-import-pro'); ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Records to Preload -->
            <div class="wpai-records-to-preload-section">
                <div class="wpallimport-records-to-preload-wrapper">
                    <label style="font-weight: bold;"><?php _e("Records to Preload", "wp-all-import-pro"); ?></label>
                    <input type="number"
                           class="smaller-text wpai-preview-records-to-preload"
                           name="records_to_preload"
                           value="10"
                           min="1"
                           max="100"
                           step="1"
                           placeholder="10" />
                    <p class="description" style="margin-top: 8px;">
                        <?php _e('Number of preview records to generate and cache in the background (1-100).', 'wp-all-import-pro'); ?>
                    </p>
                </div>
            </div>

            <!-- Refresh Preview Button -->
            <div class="wpai-refresh-preview-section">
                <button type="button" id="wpai-refresh-preview-btn" class="button button-primary button-hero wpallimport-large-button">
                    <?php _e('Refresh Preview with Updated Settings', 'wp-all-import-pro'); ?>
                </button>
                <p class="description" style="margin-top: 12px;">
                    <?php _e('Click to regenerate all preview records using the updated settings above.', 'wp-all-import-pro'); ?>
                </p>
            </div>
        </div>

        <style>
            .wpai-preview-settings-wrapper {
                max-width: 800px;
                margin: 0 auto;
                padding: 30px;
            }

            .wpai-unique-identifier-section,
            .wpai-post-status-section,
            .wpai-records-to-preload-section {
                margin-bottom: 20px;
            }

            .wpai-refresh-preview-section {
                margin-top: 30px;
                padding-top: 30px;
                border-top: 1px solid #ddd;
                text-align: center;
            }

            #wpai-refresh-preview-btn {
                font-size: 16px;
                padding: 12px 24px;
                height: auto;
                line-height: 1.5;
            }

            #wpai-refresh-preview-btn.wpai-refreshing {
                opacity: 0.6;
                cursor: not-allowed;
                pointer-events: none;
            }

            .wpallimport-unique-key-wrapper,
            .wpallimport-post-status-wrapper {
                margin-top: 20px;
            }

            /* Override admin.css padding-bottom for preview context */
            .wpallimport-unique-key-wrapper {
                padding-bottom: 0;
            }

            .wpallimport-unique-key-wrapper label,
            .wpallimport-post-status-wrapper label,
            .wpallimport-records-to-preload-wrapper label {
                display: block;
                margin-bottom: 8px;
                font-weight: bold;
                color: #1d2327;
                font-size: 14px;
            }

            .wpai-preview-records-to-preload {
                width: 300px;
            }

            /* Remove spinner arrows from number input */
            .wpai-preview-records-to-preload::-webkit-outer-spin-button,
            .wpai-preview-records-to-preload::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            .wpai-preview-records-to-preload[type=number] {
                -moz-appearance: textfield;
            }

            .wpai-unique-key-input-wrapper {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .wpai-toggle-field-list,
            .wpai-auto-detect-link {
                color: #2271b1;
                text-decoration: none;
                white-space: nowrap;
                font-size: 13px;
            }

            .wpai-toggle-field-list:hover,
            .wpai-auto-detect-link:hover {
                color: #135e96;
                text-decoration: underline;
            }

            .wpai-field-list-sidebar {
                margin-top: 20px;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: #fafafa;
                max-height: 400px;
                display: flex;
                flex-direction: column;
            }

            .wpai-field-list-header {
                padding: 12px 15px;
                border-bottom: 1px solid #ddd;
                background: #fff;
            }

            .wpai-field-list-header h4 {
                margin: 0 0 5px 0;
                font-size: 14px;
                font-weight: 600;
                color: #1d2327;
            }

            .wpai-field-list-header .description {
                margin: 0;
                font-size: 12px;
                color: #646970;
            }

            .wpai-field-list-content {
                flex: 1;
                overflow-y: auto;
                padding: 10px;
            }

            #wpai-preview-field-container {
                min-height: 100px;
            }

            .wpallimport-unique-key-input {
                width: 100%;
                max-width: 500px;
                font-family: 'Courier New', Courier, monospace;
                font-size: 13px;
                padding: 8px 12px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                margin-right: 8px;
            }

            .wpallimport-unique-key-input:disabled {
                background-color: #f6f7f7;
                color: #50575e;
                cursor: not-allowed;
            }

            .wpai-preview-post-status-select {
                width: 300px;
                max-width: 500px;
                font-size: 13px;
                padding: 8px 12px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                background-color: #fff;
                color: #1d2327;
                cursor: pointer;
            }

            .wpai-preview-post-status-select:focus {
                border-color: #2271b1;
                outline: none;
                box-shadow: 0 0 0 1px #2271b1;
            }

            .wpallimport-auto-detect-unique-key,
            .wpallimport-change-unique-key {
                display: inline !important;
                visibility: visible !important;
                opacity: 1 !important;
                color: #2271b1 !important;
                text-decoration: none !important;
                font-size: 13px !important;
                font-weight: normal !important;
                white-space: nowrap !important;
                background: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                cursor: pointer !important;
                line-height: normal !important;
                vertical-align: baseline !important;
                width: auto !important;
                height: auto !important;
                text-indent: 0 !important;
                overflow: visible !important;
            }

            .wpallimport-auto-detect-unique-key:hover,
            .wpallimport-change-unique-key:hover {
                color: #135e96 !important;
                text-decoration: underline !important;
                background: none !important;
            }

            .wpallimport-help {
                display: inline-block;
                width: 20px;
                height: 20px;
                line-height: 20px;
                text-align: center;
                background: #2271b1;
                color: #fff;
                border-radius: 50%;
                text-decoration: none;
                font-size: 12px;
                font-weight: bold;
                margin-left: 4px;
            }

            .wpallimport-help:hover {
                background: #135e96;
                color: #fff;
            }

            .wpai-unique-identifier-section .description {
                color: #646970;
                font-size: 13px;
                line-height: 1.5;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Handle "Edit" button click for existing imports
            $(document).on('click', '.wpallimport-change-unique-key', function(e) {
                e.preventDefault();

                $("#dialog-confirm-preview").dialog({
                    resizable: false,
                    height: 290,
                    width: 550,
                    modal: true,
                    draggable: false,
                    buttons: {
                        "Continue": function() {
                            $(this).dialog("close");
                            $('.wpallimport-change-unique-key').hide();
                            $('input[name=unique_key]').removeAttr('disabled').trigger('focus');
                        },
                        Cancel: function() {
                            $(this).dialog("close");
                        }
                    }
                });
            });

            // Sync unique key changes with Alpine component
            $(document).on('input change', 'input[name=unique_key]', function() {
                if (window.wpaiPreviewComponent) {
                    window.wpaiPreviewComponent.uniqueKey = $(this).val();
                }
            });

            // Initialize unique key value in Alpine component
            var $uniqueKey = $('input[name=unique_key]');
            if ($uniqueKey.length && window.wpaiPreviewComponent) {
                window.wpaiPreviewComponent.uniqueKey = $uniqueKey.val();
            }
        });
        </script>
        <?php

        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'is_new_import' => $is_new_import,
            'post_type' => $post_type
        ));

    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()), 500);
    }
}

add_action('wp_ajax_wpai_load_preview_settings', 'pmxi_wp_ajax_wpai_load_preview_settings');

