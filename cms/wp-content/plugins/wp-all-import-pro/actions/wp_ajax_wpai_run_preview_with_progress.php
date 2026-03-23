<?php

function wpai_get_import_category($import_type) {
    if ($import_type === 'taxonomies') {
        return 'taxonomies';
    }

    if ($import_type === 'gf_entries') {
        return 'gravity_forms';
    }

    if (in_array($import_type, array('import_users', 'shop_customer'))) {
        return 'users';
    }

    if (in_array($import_type, array('comments', 'woo_reviews'))) {
        return 'comments';
    }

    return 'posts';
}

function wpai_find_unique_key_for_preview($options, $file_path) {
    $uniqueKey = '';

    if (empty($options['unique_key'])) {
        $keys_black_list = array('programurl');

        if ($options['custom_type'] == 'import_users') {
            $uniqueKey = isset($options['pmui']['login']) ? $options['pmui']['login'] : '';
        } elseif ($options['custom_type'] == 'shop_customer') {
            $uniqueKey = isset($options['pmsci_customer']['login']) ? $options['pmsci_customer']['login'] : '';
        } else {
            $uniqueKey = isset($options['title']) ? $options['title'] : '';
        }

        try {
            $absolute_path = wp_all_import_get_absolute_path($file_path);
            if (file_exists($absolute_path)) {
                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->loadXML(file_get_contents($absolute_path));

                $unique_keys = array();
                wpai_find_unique_key_recursive($dom->documentElement, $unique_keys);

                if (!empty($unique_keys)) {
                    foreach ($keys_black_list as $key => $value) {
                        $uniqueKey = str_replace('{' . $value . '[1]}', "", $uniqueKey);
                    }

                    foreach ($unique_keys as $key) {
                        if (stripos($key, 'id') !== false) {
                            $uniqueKey .= ' - {' . $key . '[1]}';
                            break;
                        }
                    }

                    foreach ($unique_keys as $key) {
                        if (stripos($key, 'url') !== false || stripos($key, 'sku') !== false || stripos($key, 'ref') !== false) {
                            if (!in_array($key, $keys_black_list)) {
                                $uniqueKey .= ' - {' . $key . '[1]}';
                                break;
                            }
                        }
                    }
                }

                $uniqueKey = apply_filters('pmxi_unique_key', $uniqueKey, $options);
            }
        } catch (Exception $e) {
        }
    } else {
        $uniqueKey = $options['unique_key'];
    }

    return $uniqueKey;
}

function wpai_find_unique_key_recursive($el, &$unique_keys) {
    if ($el->hasChildNodes()) {
        if ($el->childNodes->length) {
            foreach ($el->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    if (!in_array($child->nodeName, $unique_keys)) {
                        $unique_keys[] = $child->nodeName;
                    }
                    wpai_find_unique_key_recursive($child, $unique_keys);
                }
            }
        }
    }
}

/**
 * Apply preview-specific modifications to import options to avoid conflicts with existing records.
 *
 * For taxonomies: Appends '-preview' to the slug field to prevent duplicate slug conflicts.
 * For users: Appends '-preview' to the login field and prepends 'preview-' to email to prevent conflicts.
 *
 * @param array $options The import options array
 * @param string $import_type The import type (taxonomies, import_users, shop_customer, etc.)
 * @return array Modified options array
 */
function wpai_apply_preview_modifications($options, $import_type) {
    // Handle taxonomy imports - append -preview to slug
    if ($import_type === 'taxonomies') {
        // Check if manual slug is enabled and has a value
        if (!empty($options['taxonomy_slug']) && $options['taxonomy_slug'] === 'xpath' && !empty($options['taxonomy_slug_xpath'])) {
            // Append -preview to the slug XPath value
            $options['taxonomy_slug_xpath'] .= '-preview';
        } else {
            // If slug is set to auto, WordPress generates it from the term name
            // So we need to modify the term name (title field) to append -preview
            if (!empty($options['title'])) {
                $options['title'] .= '-preview';
            }
        }
    }

    // Handle user imports - append -preview to login and prepend preview- to email
    if ($import_type === 'import_users') {
        if (!empty($options['pmui']['login'])) {
            $options['pmui']['login'] .= '-preview';
        }
        if (!empty($options['pmui']['email'])) {
            $options['pmui']['email'] = 'preview-' . $options['pmui']['email'];
        }
    }

    // Handle WooCommerce customer imports - append -preview to login and prepend preview- to email
    if ($import_type === 'shop_customer') {
        if (!empty($options['pmsci_customer']['login'])) {
            $options['pmsci_customer']['login'] .= '-preview';
        }
        if (!empty($options['pmsci_customer']['email'])) {
            $options['pmsci_customer']['email'] = 'preview-' . $options['pmsci_customer']['email'];
        }
    }

    return $options;
}

function pmxi_wp_ajax_wpai_run_preview_with_progress() {
    while (ob_get_level()) {
        ob_end_clean();
    }

    if (!check_ajax_referer('wp_all_import_secure', 'security', false)) {
        wp_send_json(array('success' => false, 'data' => array('message' => __('Security check failed', 'wp-all-import-pro'))), 403);
    }

    if (!current_user_can(PMXI_Plugin::$capabilities)) {
        wp_send_json(array('success' => false, 'data' => array('message' => __('Insufficient permissions', 'wp-all-import-pro'))), 403);
    }

    wpai_ensure_preview_column_exists();

    if (false === get_transient('wpai_last_orphan_cleanup')) {
        wpai_cleanup_orphaned_preview_imports();
        set_transient('wpai_last_orphan_cleanup', true, 5 * MINUTE_IN_SECONDS);
    }

    $preview_session_id = isset($_POST['preview_session_id']) ? sanitize_text_field($_POST['preview_session_id']) : '';
    if (empty($preview_session_id)) {
        $preview_session_id = wp_generate_password(32, false);
    }

    wpai_register_preview_session($preview_session_id);

    $input = new PMXI_Input();

    $import_id = isset($_POST['import_id']) ? intval($_POST['import_id']) : 0;
    $is_new_import = empty($import_id) || $import_id === 'new';

    $default = PMXI_Plugin::get_default_import_options();

    if ($is_new_import) {
        foreach (PMXI_Admin_Addons::get_active_addons() as $class) {
            if (class_exists($class)) $default += call_user_func(array($class, "get_default_import_options"));
        }
        if (!empty(PMXI_Plugin::$session)) {
            $default['wizard_type'] = PMXI_Plugin::$session->wizard_type ?? '';
            if (empty($default['custom_type'])) $default['custom_type'] = PMXI_Plugin::$session->custom_type ?? '';
            if (empty($default['taxonomy_type'])) $default['taxonomy_type'] = PMXI_Plugin::$session->taxonomy_type ?? '';
            if (empty($default['delimiter'])) $default['delimiter'] = PMXI_Plugin::$session->is_csv ?? '';
        }
        $DefaultOptions = (isset(PMXI_Plugin::$session->options)) ? array_replace_recursive($default, PMXI_Plugin::$session->options) : $default;
    } else {
        $original_import = new PMXI_Import_Record();
        $original_import->getById($import_id);

        if ($original_import->isEmpty()) {
            wp_send_json(array('success' => false, 'data' => array('message' => __('Import not found', 'wp-all-import-pro'))), 404);
        }

        foreach (PMXI_Admin_Addons::get_active_addons() as $class) {
            if (class_exists($class)) $default += call_user_func(array($class, "get_default_import_options"));
        }
        $DefaultOptions = (is_array($original_import->options)) ? array_replace_recursive($default, $original_import->options) : $default;
    }

    $post = $input->post( apply_filters('pmxi_options_options', $DefaultOptions, $is_new_import) );

    $post['import_id'] = $import_id;

    if (isset($_POST['fields']) && is_array($_POST['fields'])) {
        $post['fields'] = $_POST['fields'];
    }

    $post['preview_mode'] = isset($_POST['preview_mode']) ? $_POST['preview_mode'] : 'first';
    $post['specific_record'] = isset($_POST['specific_record']) ? $_POST['specific_record'] : 1;
    $post['range_start'] = isset($_POST['range_start']) ? $_POST['range_start'] : 1;
    $post['range_end'] = isset($_POST['range_end']) ? $_POST['range_end'] : 1;
    $post['multiple_records'] = isset($_POST['multiple_records']) ? $_POST['multiple_records'] : '1';
    $post['post_status'] = isset($_POST['post_status']) ? $_POST['post_status'] : 'draft';

    if (isset($_POST['wpai_preview_unique_key']) && !empty($_POST['wpai_preview_unique_key'])) {
        $post['unique_key'] = $_POST['wpai_preview_unique_key'];
    }

    // Save the preview unique key to session so it can be used as default in Step 4
    // This captures the unique key that was actually used for running previews
    if (!empty($post['unique_key'])) {
        PMXI_Plugin::$session->set('preview_unique_key', $post['unique_key']);
        PMXI_Plugin::$session->save_data();
    }

    try {
        $import_type = $post['custom_type'] ?? '';
        $import_category = wpai_get_import_category($import_type);

        if ($import_category === 'unsupported') {
            wp_send_json(array(
                'success' => false,
                'data' => array(
                    'message' => sprintf(
                        __('Preview is not currently supported for %s imports.', 'wp-all-import-pro'),
                        $import_type
                    )
                )
            ), 400);
        }

        if ($is_new_import) {
            if (empty(PMXI_Plugin::$session) || empty(PMXI_Plugin::$session->filePath)) {
                wp_send_json(array('success' => false, 'data' => array('message' => __('No import session found. Please start from Step 1 to upload a file first.', 'wp-all-import-pro'))), 400);
            }

            $file_path = PMXI_Plugin::$session->filePath;
        } else {
            $history_file = new PMXI_File_Record();
            $history_file->getBy(array('import_id' => $original_import->id), 'id DESC');

            if (!$history_file->isEmpty()) {
                $file_path = $history_file->path;

                $absolute_path = wp_all_import_get_absolute_path($file_path);
                if (preg_match('%\W(xlsx|xls)$%i', $absolute_path)) {
                    $dir = dirname($absolute_path);
                    $basename = pathinfo($absolute_path, PATHINFO_FILENAME);

                    $xml_file = $dir . '/' . $basename . '.xml';
                    if (file_exists($xml_file)) {
                        $uploads_dir = wp_upload_dir();
                        $file_path = str_replace($uploads_dir['basedir'] . '/wpallimport/uploads/', '', $xml_file);
                    } else {
                        $xml_files = glob($dir . '/*.xml');
                        if (!empty($xml_files)) {
                            $uploads_dir = wp_upload_dir();
                            $file_path = str_replace($uploads_dir['basedir'] . '/wpallimport/uploads/', '', $xml_files[0]);
                        }
                    }
                }
            } else {
                $file_path = $original_import->path;
            }
        }

        // Apply preview-specific modifications to template fields BEFORE processing
        // This modifies the raw XPath expressions to append -preview suffix
        $post = wpai_apply_preview_modifications($post, $import_type);

        $import_record = null;
        if (!$is_new_import) {
            $import_record = new PMXI_Import_Record();
            $import_record->getById($import_id);
        }

        $processor = new PMXI_Template_Processor($import_record);
        $post = $processor->process($post);

        if ($post === false || $processor->errors->get_error_codes()) {
            $error_messages = array();
            foreach ($processor->errors->get_error_codes() as $code) {
                $error_messages[] = $processor->errors->get_error_message($code);
            }

            wp_send_json(array(
                'success' => false,
                'data' => array(
                    'message' => __('Template validation failed', 'wp-all-import-pro'),
                    'errors' => $error_messages
                )
            ), 400);
        }

        if (empty($post['unique_key'])) {
            $post['unique_key'] = wpai_find_unique_key_for_preview($post, $file_path);
        }

        $preview_options = $post;

        $preview_options['wizard_type'] = 'new';
        $preview_options['duplicate_matching'] = 'auto';
        $preview_options['create_new_records'] = 1;
        $preview_options['is_keep_former_posts'] = 'no';
        $preview_options['update_all_data'] = 'yes';

        if (!empty($post['post_status'])) {
            $preview_options['status'] = $post['post_status'];
        }

        if (!empty($post['unique_key'])) {
            $preview_options['unique_key'] = $post['unique_key'];
        }

        $records_to_import = wpai_calculate_preview_records($post);

        if (empty($records_to_import)) {
            wp_send_json(array('success' => false, 'data' => array('message' => __('No valid records specified for preview', 'wp-all-import-pro'))), 400);
        }

        $form_xpath = isset($post['xpath']) && !empty($post['xpath']) ? $post['xpath'] : null;
        $form_root_element = isset($post['root_element']) && !empty($post['root_element']) ? $post['root_element'] : null;

        $preview_data = array(
            'xpath' => $form_xpath ?: ($is_new_import ? PMXI_Plugin::$session->xpath : $original_import->xpath),
            'root_element' => $form_root_element ?: ($is_new_import ?
                (PMXI_Plugin::$session->source['root_element'] ?? PMXI_Plugin::$session->root_element) :
                $original_import->root_element),
            'options' => $preview_options
        );

        $async_requested = !empty($_POST['async_preview']) && in_array(strtolower((string) $_POST['async_preview']), array('1','true','yes'), true);
        $async_threshold = (int) apply_filters('wpai_preview_async_threshold', 50);
        $should_async = $async_requested || (count($records_to_import) > $async_threshold);

        if ($should_async) {
            $job_id = uniqid('prv_', true);
            $job = array(
                'id' => $job_id,
                'status' => 'queued',
                'import_category' => $import_category,
                'preview_data' => $preview_data,
                'file_path' => $file_path,
                'records_queue' => array_values($records_to_import),
                'total' => count($records_to_import),
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'deleted' => 0,
                'log' => array(),
                'all_record_ids' => array(),
                'started_at' => time(),
            );
            update_option('wpai_preview_job_' . $job_id, $job, false);

            wp_send_json(array(
                'success' => true,
                'data' => array(
                    'preview_import_id' => $job_id,
                )
            ));
        }

        $log_messages = array();
        $logger = function($m) use (&$log_messages) {
            $log_messages[] = "[". date("H:i:s") ."] " . $m;
        };

        ob_start();

        $result = wpai_execute_preview_import_with_progress($preview_data, $file_path, $records_to_import, $logger, $import_category, $import_type, $preview_session_id);

        $stray_output = ob_get_contents();
        ob_end_clean();

        while (ob_get_level()) {
            ob_end_clean();
        }

        $log_data = "";
        if (!empty($log_messages)) {
            foreach ($log_messages as $message) {
                $log_data .= "<div class='progress-msg'>" . wp_all_import_filter_html_kses($message) . "</div>\n";
            }
        }

        $total_records = 0;
        if (!empty($result['total_available_records'])) {
            $total_records = $result['total_available_records'];
        } else {
            if ($is_new_import) {
                $total_records = !empty(PMXI_Plugin::$session->count) ? PMXI_Plugin::$session->count : 0;
            } else {
                $total_records = !empty($original_import->count) ? $original_import->count : 0;
            }
        }

        if ($result['success']) {
            wp_send_json(array(
                'imported' => 1,
                'created' => $result['post_count'] ?? 0,
                'updated' => 0,
                'skipped' => 0,
                'deleted' => 0,
                'changed_missing' => 0,
                'percentage' => 100,
                'warnings' => 0,
                'errors' => 0,
                'log' => $log_data,
                'done' => true,
                'post_id' => $result['post_id'] ?? null,
                'post_ids' => $result['post_ids'] ?? array(),
                'post_count' => $result['post_count'] ?? 0,
                'is_unexpected_multiple' => $result['is_unexpected_multiple'] ?? false,
                'warning_context' => $result['warning_context'] ?? array(),
                'post_title' => $result['post_title'] ?? '',
                'post_status' => $result['post_status'] ?? '',
                'edit_url' => $result['edit_url'] ?? '',
                'view_url' => $result['view_url'] ?? '',
                'was_skipped' => $result['was_skipped'] ?? false,
                'skip_reason' => $result['skip_reason'] ?? '',
                'total_records' => $total_records,
                'preview_session_id' => $preview_session_id
            ));
        } else {
            wp_send_json(array(
                'message' => $result['message'] ?? 'Unknown error',
                'log' => $log_data,
                'total_records' => $total_records,
                'preview_session_id' => $preview_session_id
            ), 500);
        }

    } catch (Exception $e) {
        wp_send_json(array('success' => false, 'data' => array('message' => $e->getMessage())), 500);
    }
}



/**
 * Execute preview import with progress tracking
 */
function wpai_execute_preview_import_with_progress($preview_data, $file_path, $records_to_import, $logger = null, $import_category = 'posts', $import_type = '', $preview_session_id = '') {
    try {
        if (!$logger) {
            $logger = function($m) { /* Silent logger for preview */ };
        }

        $logger(__('Starting preview import...', 'wp-all-import-pro'));

        // Use the unified preview execution system that works for all import types
        return wpai_execute_unified_preview($preview_data, $file_path, $records_to_import, $logger, $import_category, $import_type, $preview_session_id);
    } catch (Exception $e) {
        return array('success' => false, 'message' => $e->getMessage());
    }
}

/**
 * Register a preview session to protect its records from cleanup
 * Session expires after 2 minutes, but is kept alive by heartbeat every ~60 seconds
 *
 * @param string $session_id Unique session identifier
 */
function wpai_register_preview_session($session_id, $preview_import_id = null) {
    set_transient('wpai_preview_session_' . $session_id, array(
        'preview_import_id' => $preview_import_id,
        'started' => time()
    ), 2 * MINUTE_IN_SECONDS);
}

/**
 * Update the preview import ID for a session
 * This protects the preview import from cleanup while the session is active
 * Session expires after 2 minutes, but is kept alive by heartbeat every ~60 seconds
 *
 * @param string $session_id Session identifier
 * @param int $preview_import_id Preview import ID to protect
 */
function wpai_update_preview_session_import_id($session_id, $preview_import_id) {
    $session_data = get_transient('wpai_preview_session_' . $session_id);
    if ($session_data === false) {
        // Session expired or doesn't exist, recreate it
        $session_data = array(
            'preview_import_id' => null,
            'started' => time()
        );
    }

    // Set the preview import ID
    $session_data['preview_import_id'] = intval($preview_import_id);

    // Refresh the transient (2 minutes since heartbeat keeps it alive)
    set_transient('wpai_preview_session_' . $session_id, $session_data, 2 * MINUTE_IN_SECONDS);
}

/**
 * Clean up orphaned preview imports from previous sessions
 * Deletes preview imports older than 5 minutes and all their associated records
 * This leverages the import's delete() method which properly handles all record types
 * Preview imports in active sessions are protected from deletion
 */
function wpai_cleanup_orphaned_preview_imports() {
    global $wpdb;

    $table_prefix = PMXI_Plugin::getInstance()->getTablePrefix();

    // Get active session IDs to protect their preview imports
    $active_session_ids = wpai_get_active_preview_session_import_ids();

    // Find preview imports older than 5 minutes
    // This gives plenty of time for navigation within a session while cleaning up old orphans
    $five_minutes_ago = date('Y-m-d H:i:s', strtotime('-5 minutes'));

    $query = "
        SELECT id
        FROM {$table_prefix}imports
        WHERE is_preview = 1
        AND last_activity < %s
    ";

    // Exclude preview imports from active sessions
    if (!empty($active_session_ids)) {
        $placeholders = implode(',', array_fill(0, count($active_session_ids), '%d'));
        $query .= " AND id NOT IN ($placeholders)";
        $orphaned_import_ids = $wpdb->get_col($wpdb->prepare($query, array_merge(array($five_minutes_ago), $active_session_ids)));
    } else {
        $orphaned_import_ids = $wpdb->get_col($wpdb->prepare($query, $five_minutes_ago));
    }

    // Delete each orphaned preview import and all its associated records
    if (!empty($orphaned_import_ids)) {
        foreach ($orphaned_import_ids as $import_id) {
            wpai_delete_preview_import($import_id);
        }
    }
}

/**
 * Unified preview execution that works for all import types
 * Leverages the existing import system's process() method
 */
function wpai_execute_unified_preview($preview_data, $file_path, $records_to_import, $logger, $import_category = 'posts', $import_type = '', $preview_session_id = '') {
    ob_start();



    try {
        // Determine if this is a new import (Step 3) or existing import
        $is_new_import = empty($_POST['import_id']) || $_POST['import_id'] === 'new';
        // Convert relative path to absolute if needed
        if (!file_exists($file_path)) {
            $absolute_path = wp_all_import_get_absolute_path($file_path);
            if (file_exists($absolute_path)) {
                $file_path = $absolute_path;
            } else {
                if (ob_get_level()) {
                    ob_end_clean();
                }
                return array('success' => false, 'message' => __('Source file not found', 'wp-all-import-pro'));
            }
        }

        $logger(__('Loading source file...', 'wp-all-import-pro'));

        // Use PMXI_Chunk for XML processing
        $file = new PMXI_Chunk($file_path, array(
            'element' => $preview_data['root_element'],
            'encoding' => 'UTF-8'
        ));

        $logger(sprintf(__('Extracting records %s for preview...', 'wp-all-import-pro'), implode(', ', $records_to_import)));

        $feed = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . "\n" . "<pmxi_records>";
        $current_record = 0;
        $found_records = 0;
        $total_available_records = 0;

        $expected = count($records_to_import);
        $done_collecting = false;

        while (($xml = $file->read())) {
            if (!empty($xml)) {
                $xml_chunk = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . "\n" . $xml;
                $dom = new DOMDocument('1.0', 'UTF-8');
                $old = libxml_use_internal_errors(true);
                $dom->loadXML($xml_chunk);
                libxml_use_internal_errors($old);
                $xpath = new DOMXPath($dom);

                if (($elements = @$xpath->query($preview_data['xpath'])) && $elements->length) {
                    for ($i = 0; $i < $elements->length; $i++) {
                        $current_record++;
                        $total_available_records++;

                        if (!$done_collecting && in_array($current_record, $records_to_import, true)) {
                            $nodeXml = $dom->saveXML($elements->item($i));
                            if (!empty($nodeXml)) {
                                $feed .= $nodeXml;
                                $found_records++;
                                if ($found_records >= $expected) {
                                    $done_collecting = true;
                                }
                            }
                        }
                    }
                }
                unset($dom, $xpath, $elements);
            }
        }

        $feed .= "</pmxi_records>";



        if ($found_records == 0) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            return array('success' => false, 'message' => __('No records found for preview', 'wp-all-import-pro'));
        }

        $logger(__('Processing preview records...', 'wp-all-import-pro'));

        $default_options = PMXI_Plugin::get_default_import_options();
        $merged_options = array_merge($default_options, $preview_data['options']);

        $merged_options['xpath'] = '/*';
        $merged_options['root_element'] = 'pmxi_records';

        $merged_options['is_import_specified'] = 0;
        $merged_options['import_specified'] = '';

        $merged_options['wizard_type'] = 'new';
        $merged_options['duplicate_matching'] = 'auto';
        $merged_options['create_new_records'] = 1;
        $merged_options['is_keep_former_posts'] = 'no';
        $merged_options['update_all_data'] = 'yes';

        $merged_options['is_selective_hashing'] = 0;

        $merged_options['records_per_request'] = max((int)($merged_options['records_per_request'] ?? 20), (int)$found_records);

        if (isset($merged_options['tmp_unique_key']) && !empty($merged_options['tmp_unique_key']) && empty($merged_options['unique_key'])) {
            $merged_options['unique_key'] = $merged_options['tmp_unique_key'];
        }

        if (isset($preview_options['unique_key']) && !empty($preview_options['unique_key'])) {
            $merged_options['unique_key'] = $preview_options['unique_key'];
        }

        if ($is_new_import) {
            if (isset($merged_options['custom_name']) && is_array($merged_options['custom_name'])) {
                if (count($merged_options['custom_name']) === 1 && $merged_options['custom_name'][0] === '') {
                    $merged_options['custom_name'] = array();
                    $merged_options['custom_value'] = array();
                }
            }

            if (isset($merged_options['tax_mapping']) && is_array($merged_options['tax_mapping'])) {
                foreach ($merged_options['tax_mapping'] as $taxonomy => $mapping) {
                    if (empty($mapping)) {
                        $merged_options['tax_mapping'][$taxonomy] = '[]';
                    }
                }
            }

            if (isset($merged_options['post_taxonomies']) && is_array($merged_options['post_taxonomies'])) {
                foreach ($merged_options['post_taxonomies'] as $taxonomy => $structure) {
                    if (empty($structure)) {
                        unset($merged_options['post_taxonomies'][$taxonomy]);
                    }
                }
            }
        }

        $preview_data_for_feed = $preview_data;
        $preview_data_for_feed['xpath'] = '/*';
        $preview_data_for_feed['root_element'] = 'pmxi_records';

        $import = findOrCreatePreviewImport($preview_data_for_feed, $merged_options, $file_path, $found_records);

        if (!empty($preview_session_id)) {
            wpai_update_preview_session_import_id($preview_session_id, $import->id);
        }

        $absolute_file_path = wp_all_import_get_absolute_path($file_path);
        if (file_exists($absolute_file_path)) {
        }

        $created_records = array();

        try {
            ob_start();

            $log_messages = array();
            $debug_logger = function($m) use (&$log_messages, $logger) {
                $log_messages[] = $m;
                $logger($m);
            };

            $import_options = $merged_options;

            if (isset($import_options['make_simple_product'])) {
                $import_options['make_simple_product'] = 0;
            }

            $process_start_time = time();

            $use_custom_fields_for_marking = !in_array($import_type, array('shop_order', 'gf_entries'));

            if ($use_custom_fields_for_marking) {
                if (!isset($import_options['custom_name'])) {
                    $import_options['custom_name'] = array();
                }
                if (!isset($import_options['custom_value'])) {
                    $import_options['custom_value'] = array();
                }
                if (!isset($import_options['custom_format'])) {
                    $import_options['custom_format'] = array();
                }
                if (!isset($import_options['custom_mapping'])) {
                    $import_options['custom_mapping'] = array();
                }

                $preview_field_name = wpai_get_preview_meta_key($import_type);
                $preview_created_field_name = wpai_get_preview_created_meta_key($import_type);

                $import_options['custom_name'][] = $preview_field_name;
                $import_options['custom_value'][] = '1';
                $import_options['custom_format'][] = 0;
                $import_options['custom_mapping'][] = array();

                $import_options['custom_name'][] = $preview_created_field_name;
                $import_options['custom_value'][] = (string)$process_start_time;
                $import_options['custom_format'][] = 0;
                $import_options['custom_mapping'][] = array();
            }

            $import->set(array('options' => $import_options))->update();

            if (!$use_custom_fields_for_marking) {
                $preview_import_id = $import->id;
                $preview_marker_hook = function($post_id, $xml_node, $is_update) use ($import_type, $process_start_time, $preview_import_id) {
                    global $wpdb;
                    $table_prefix = PMXI_Plugin::getInstance()->getTablePrefix();

                    $record_import_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT import_id FROM {$table_prefix}posts WHERE post_id = %d LIMIT 1",
                        $post_id
                    ));

                    if ($record_import_id == $preview_import_id && !$is_update) {
                        wpai_mark_preview_record($post_id, $import_type);
                        wpai_set_preview_created_meta($post_id, $import_type, $process_start_time);
                    }
                };
                add_action('pmxi_saved_post', $preview_marker_hook, 10, 3);
            }

            $preview_import_id = $import->id;
            $attachment_marker_hook = function($attachment_id) use ($preview_import_id) {
                update_post_meta($attachment_id, '_wpai_preview_post', '1');
                update_post_meta($attachment_id, '_wpai_preview_created', time());
                update_post_meta($attachment_id, '_wpai_preview_import_id', $preview_import_id);
            };
            add_action('wp_all_import_add_attachment', $attachment_marker_hook, 10, 1);

            // Hook to mark taxonomy terms created during preview
            // This catches terms created when assigning taxonomies to posts (not taxonomy imports)
            // Uses WP All Import specific hook to ensure we only mark terms created by this import
            $term_marker_hook = function($term_id, $taxonomy, $import_id) use ($preview_import_id) {
                // Only mark terms created by this specific preview import
                if ($import_id == $preview_import_id) {
                    update_term_meta($term_id, '_wpai_preview_term', '1');
                    update_term_meta($term_id, '_wpai_preview_created', time());
                    update_term_meta($term_id, '_wpai_preview_import_id', $preview_import_id);
                }
            };
            add_action('wp_all_import_created_term', $term_marker_hook, 10, 3);

            $counters_before = array(
                'created' => $import->created,
                'updated' => $import->updated,
                'skipped' => $import->skipped
            );

            // Save original session values before preview import modifies them
            // The process() method sets session->import_id to the preview import's ID,
            // which breaks subsequent operations that expect the original import ID
            $original_session_import_id = null;
            $original_session_file_path = null;
            if (!empty(PMXI_Plugin::$session)) {
                $original_session_import_id = PMXI_Plugin::$session->import_id;
                $original_session_file_path = PMXI_Plugin::$session->filePath;
            }

            do_action('pmxi_before_xml_import', $import->id);

            $import->process($feed, $debug_logger, 1, false, '/pmxi_records', $found_records);

            do_action('pmxi_after_xml_import', $import->id, $import);

            // Restore original session values after preview import completes
            if (!empty(PMXI_Plugin::$session) && $original_session_import_id !== null) {
                PMXI_Plugin::$session->import_id = $original_session_import_id;
                if ($original_session_file_path !== null) {
                    PMXI_Plugin::$session->filePath = $original_session_file_path;
                }
                PMXI_Plugin::$session->save_data();
            }

            if (!$use_custom_fields_for_marking && isset($preview_marker_hook)) {
                remove_action('pmxi_saved_post', $preview_marker_hook, 10);
            }

            if (isset($attachment_marker_hook)) {
                remove_action('wp_all_import_add_attachment', $attachment_marker_hook, 10);
            }

            if (isset($term_marker_hook)) {
                remove_action('wp_all_import_created_term', $term_marker_hook, 10);
            }

            ob_end_clean();

            $post_list = new PMXI_Post_List();
            $all_post_records = $post_list->getBy(array('import_id' => $import->id));

            $logger(sprintf(__('Found %d record(s) in import tracking table', 'wp-all-import-pro'), count($all_post_records)));

            $import->getById($import->id);

            $logger(sprintf(__('Import counters - created: %d, updated: %d, skipped: %d', 'wp-all-import-pro'),
                $import->created, $import->updated, $import->skipped));

            $records_created_this_run = $import->created - $counters_before['created'];
            $records_updated_this_run = $import->updated - $counters_before['updated'];
            $records_skipped_this_run = $import->skipped - $counters_before['skipped'];

            $logger(sprintf(__('This run - created: %d, updated: %d, skipped: %d', 'wp-all-import-pro'),
                $records_created_this_run, $records_updated_this_run, $records_skipped_this_run));

            foreach ($all_post_records as $post_record) {
                $record_id = $post_record['post_id'];

                $preview_created = wpai_get_preview_created_meta($record_id, $import_type);
                $preview_created_ts = is_numeric($preview_created) ? (int) $preview_created : 0;

                if (!empty($preview_created_ts) && $preview_created_ts >= $process_start_time) {
                    $created_records[] = $record_id;
                    $logger(sprintf(__('Found preview record %d', 'wp-all-import-pro'), $record_id));
                }
            }

        } catch (Exception $e) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            return array(
                'success' => false,
                'message' => 'Preview import failed: ' . $e->getMessage()
            );
        }

        $actual_created = count($created_records);
        $is_unexpected_multiple = false;
        $warning_context = array();

        $post_types = array();
        $main_post_count = 0;
        foreach ($created_records as $rid) {
            $ptype = get_post_type($rid);
            if (!empty($ptype)) {
                $post_types[$ptype] = true;
                if (!in_array($ptype, array('attachment', 'product_variation'), true)) {
                    $main_post_count++;
                }
            }
        }

        if ($main_post_count > 1) {
            $is_unexpected_multiple = true;
            if (!empty($post_types)) {
                $warning_context['post_types'] = array_keys($post_types);
            }
            $warning_context['main_post_count'] = $main_post_count;
            $warning_context['total_post_count'] = $actual_created;
        }

        $logger(__('Preview completed: ' . count($created_records) . ' record(s) created', 'wp-all-import-pro'));

        $was_skipped = false;
        $skip_reason = '';

        if (empty($created_records)) {
            if ($records_skipped_this_run > 0) {
                $was_skipped = true;

                $unique_key = !empty($merged_options['unique_key']) ? $merged_options['unique_key'] : '';

                // Provide taxonomy-specific skip message
                if ($import_type === 'taxonomies') {
                    $skip_reason = sprintf(
                        __('This term already exists and cannot be previewed. Taxonomies cannot have duplicate slugs. To preview this term, you can set a custom slug in the template settings under the "Other" section by enabling "Set slug manually" and providing a unique slug value.', 'wp-all-import-pro')
                    );
                } else {
                    $skip_reason = sprintf(
                        __('This record was skipped because a matching record already exists with the unique identifier: %s', 'wp-all-import-pro'),
                        '<code>' . esc_html($unique_key) . '</code>'
                    );
                }

                $logger($skip_reason);
            } else {
                if (ob_get_level()) {
                    ob_end_clean();
                }
                return array('success' => false, 'message' => __('No records were created during preview', 'wp-all-import-pro'));
            }
        }

        $record_info = array();
        if (!empty($created_records)) {
            $primary_record_id = wpai_get_primary_display_record($created_records);
            $record_info = wpai_get_preview_record_info($primary_record_id, $import_type);
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        $mapped_info = array();
        if (!empty($record_info)) {
            $mapped_info = array(
                'post_title' => $record_info['record_title'] ?? '',
                'post_status' => $record_info['record_status'] ?? '',
                'edit_url' => $record_info['edit_url'] ?? '',
                'view_url' => $record_info['view_url'] ?? ''
            );
        }

        $response = array(
            'success' => true,
            'message' => 'Preview completed successfully',
            'post_id' => !empty($created_records) ? $primary_record_id : null,
            'post_ids' => $created_records,
            'post_count' => count($created_records),
            'is_unexpected_multiple' => $is_unexpected_multiple,
            'warning_context' => $warning_context,
            'record_id' => !empty($created_records) ? $primary_record_id : null,
            'record_ids' => $created_records,
            'record_count' => count($created_records),
            'was_skipped' => $was_skipped,
            'skip_reason' => $skip_reason,
            'total_available_records' => $total_available_records
        );

        return array_merge($response, $mapped_info);

    } catch (Exception $e) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        if ($logger) {
            $logger(sprintf(__('Preview import failed: %s', 'wp-all-import-pro'), $e->getMessage()));
        }
        return array('success' => false, 'message' => $e->getMessage());
    }
}

/**
 * Get the preview created timestamp for a record
 */
function wpai_get_preview_created_meta($record_id, $import_type) {
    switch ($import_type) {
        case 'taxonomies':
            return get_term_meta($record_id, '_wpai_preview_term_created', true);
        case 'import_users':
        case 'shop_customer':
            return get_user_meta($record_id, '_wpai_preview_user_created', true);
        case 'comments':
        case 'woo_reviews':
            return get_comment_meta($record_id, '_wpai_preview_comment_created', true);
        case 'gf_entries':
            // For Gravity Forms, check if entry is in the preview list
            if (class_exists('GFAPI')) {
                $entry = GFAPI::get_entry($record_id);
                if (!is_wp_error($entry) && !empty($entry)) {
                    // Check if this entry has our preview marker in entry meta
                    return gform_get_meta($record_id, '_wpai_preview_entry_created');
                }
            }
            return false;
        case 'shop_order':
            // For WooCommerce orders, use wc_get_order which handles both HPOS and legacy
            if (function_exists('wc_get_order')) {
                $order = wc_get_order($record_id);
                if ($order && !is_wp_error($order)) {
                    return $order->get_meta('_wpai_preview_order_created', true);
                }
            }
            return false;
        default:
            // All post-based imports
            return get_post_meta($record_id, '_wpai_preview_post_created', true);
    }
}

/**
 * Set the preview created timestamp for a record
 */
function wpai_set_preview_created_meta($record_id, $import_type, $timestamp) {
    switch ($import_type) {
        case 'taxonomies':
            update_term_meta($record_id, '_wpai_preview_term_created', $timestamp);
            break;
        case 'import_users':
        case 'shop_customer':
            update_user_meta($record_id, '_wpai_preview_user_created', $timestamp);
            break;
        case 'comments':
        case 'woo_reviews':
            update_comment_meta($record_id, '_wpai_preview_comment_created', $timestamp);
            break;
        case 'gf_entries':
            // For Gravity Forms, use entry meta
            if (class_exists('GFAPI')) {
                gform_update_meta($record_id, '_wpai_preview_entry_created', $timestamp);
            }
            break;
        case 'shop_order':
            // For WooCommerce orders, use wc_get_order which handles both HPOS and legacy
            if (function_exists('wc_get_order')) {
                $order = wc_get_order($record_id);
                if ($order && !is_wp_error($order)) {
                    $order->update_meta_data('_wpai_preview_order_created', $timestamp);
                    $order->save();
                }
            }
            break;
        default:
            // All post-based imports
            update_post_meta($record_id, '_wpai_preview_post_created', $timestamp);
            break;
    }
}

/**
 * Verify that a record actually exists
 */
function wpai_verify_record_exists($record_id, $import_type) {
    switch ($import_type) {
        case 'taxonomies':
            // For taxonomies, check directly in the database to avoid cache issues
            // term_exists() relies on WordPress cache which may not be updated immediately after creation
            global $wpdb;
            $term = $wpdb->get_row($wpdb->prepare(
                "SELECT t.term_id FROM {$wpdb->terms} t
                 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                 WHERE t.term_id = %d",
                $record_id
            ));
            return !empty($term);
        case 'import_users':
        case 'shop_customer':
            return get_userdata($record_id) !== false;
        case 'comments':
        case 'woo_reviews':
            return get_comment($record_id) !== null;
        case 'gf_entries':
            if (class_exists('GFAPI')) {
                $entry = GFAPI::get_entry($record_id);
                return !is_wp_error($entry) && !empty($entry);
            }
            return false;
        case 'shop_order':
            // For WooCommerce orders, use wc_get_order which handles both HPOS and legacy
            if (function_exists('wc_get_order')) {
                $order = wc_get_order($record_id);
                return $order && !is_wp_error($order);
            }
            return false;
        default:
            // All post-based imports
            return get_post($record_id) !== null;
    }
}

/**
 * Delete a single preview record
 */
function wpai_delete_preview_record($record_id, $import_type) {
    switch ($import_type) {
        case 'taxonomies':
            wp_delete_term($record_id, ''); // Taxonomy will be determined by WP
            break;
        case 'import_users':
        case 'shop_customer':
            wp_delete_user($record_id);
            break;
        case 'comments':
        case 'woo_reviews':
            wp_delete_comment($record_id, true);
            break;
        case 'gf_entries':
            if (class_exists('GFAPI')) {
                GFAPI::delete_entry($record_id);
            }
            break;
        case 'shop_order':
            // For WooCommerce orders, use wc_get_order which handles both HPOS and legacy
            if (function_exists('wc_get_order')) {
                $order = wc_get_order($record_id);
                if ($order && !is_wp_error($order)) {
                    $order->delete(true);
                }
            }
            break;
        default:
            // All post-based imports
            wp_delete_post($record_id, true);
            break;
    }
}

/**
 * Get the meta key name for preview marker based on import type
 */
function wpai_get_preview_meta_key($import_type) {
    switch ($import_type) {
        case 'taxonomies':
            return '_wpai_preview_term';
        case 'import_users':
        case 'shop_customer':
            return '_wpai_preview_user';
        case 'comments':
        case 'woo_reviews':
            return '_wpai_preview_comment';
        case 'gf_entries':
            return '_wpai_preview_entry';
        case 'shop_order':
            return '_wpai_preview_order';
        default:
            // All post-based imports (posts, pages, products, etc.)
            return '_wpai_preview_post';
    }
}

/**
 * Get the meta key name for preview created timestamp based on import type
 */
function wpai_get_preview_created_meta_key($import_type) {
    switch ($import_type) {
        case 'taxonomies':
            return '_wpai_preview_term_created';
        case 'import_users':
        case 'shop_customer':
            return '_wpai_preview_user_created';
        case 'comments':
        case 'woo_reviews':
            return '_wpai_preview_comment_created';
        case 'gf_entries':
            return '_wpai_preview_entry_created';
        case 'shop_order':
            return '_wpai_preview_order_created';
        default:
            // All post-based imports (posts, pages, products, etc.)
            return '_wpai_preview_post_created';
    }
}

/**
 * Mark a record as a preview record for cleanup
 */
function wpai_mark_preview_record($record_id, $import_type) {
    switch ($import_type) {
        case 'taxonomies':
            update_term_meta($record_id, '_wpai_preview_term', true);
            break;
        case 'import_users':
        case 'shop_customer':
            update_user_meta($record_id, '_wpai_preview_user', true);
            break;
        case 'comments':
        case 'woo_reviews':
            update_comment_meta($record_id, '_wpai_preview_comment', true);
            break;
        case 'gf_entries':
            // For Gravity Forms, use entry meta
            if (class_exists('GFAPI')) {
                gform_update_meta($record_id, '_wpai_preview_entry', true);
            }
            break;
        case 'shop_order':
            // For WooCommerce orders, use wc_get_order which handles both HPOS and legacy
            if (function_exists('wc_get_order')) {
                $order = wc_get_order($record_id);
                if ($order && !is_wp_error($order)) {
                    $order->update_meta_data('_wpai_preview_order', true);
                    $order->save();
                }
            }
            break;
        default:
            // All post-based imports (posts, pages, products, orders, etc.)
            update_post_meta($record_id, '_wpai_preview_post', true);
            break;
    }
}

/**
 * Get the primary record to display from a list of created records
 *
 * For imports that create parent-child relationships (like product variations),
 * we want to display the parent record, not the child.
 *
 * Priority order:
 * 1. Parent posts (posts without post_parent and not child post types)
 * 2. Non-child post types (even if they have a parent)
 * 3. Parent of child posts (if parent was created in this preview)
 * 4. Parent of child posts (if parent exists but wasn't created in this preview)
 * 5. First created record (fallback)
 *
 * @param array $created_records Array of post IDs created during preview
 * @return int The primary record ID to display
 */
function wpai_get_primary_display_record($created_records) {
    if (empty($created_records)) {
        return 0;
    }

    // Define child post types that should defer to their parent for display
    $child_post_types = array('product_variation', 'attachment');

    // First pass: Look for true parent records (no parent, not a child type)
    foreach ($created_records as $record_id) {
        $post = get_post($record_id);
        if (!$post) {
            continue;
        }

        // True parent: not a child post type and has no parent
        if (!in_array($post->post_type, $child_post_types, true) && empty($post->post_parent)) {
            return $record_id;
        }
    }

    // Second pass: Look for non-child post types (even if they have a parent)
    foreach ($created_records as $record_id) {
        $post = get_post($record_id);
        if (!$post) {
            continue;
        }

        if (!in_array($post->post_type, $child_post_types, true)) {
            return $record_id;
        }
    }

    // Third pass: For child records, try to find their parent
    foreach ($created_records as $record_id) {
        $post = get_post($record_id);
        if (!$post || empty($post->post_parent)) {
            continue;
        }

        // If the parent was created in this preview, use it
        if (in_array($post->post_parent, $created_records, true)) {
            return $post->post_parent;
        }

        // If the parent exists (but wasn't created in this preview), still use it for display
        // This handles cases where variations are imported to an existing product
        $parent_post = get_post($post->post_parent);
        if ($parent_post) {
            return $post->post_parent;
        }
    }

    // Fallback: return first created record
    return $created_records[0];
}

/**
 * Get preview record information for display
 */
function wpai_get_preview_record_info($record_id, $import_type) {
    switch ($import_type) {
        case 'taxonomies':
            $term = get_term($record_id);
            if (!$term || is_wp_error($term)) {
                return array();
            }
            return array(
                'record_title' => $term->name,
                'record_status' => 'published',
                'edit_url' => admin_url('term.php?taxonomy=' . $term->taxonomy . '&tag_ID=' . $record_id),
                'view_url' => get_term_link($term)
            );
        case 'import_users':
        case 'shop_customer':
            $user = get_user_by('id', $record_id);
            if (!$user) {
                return array();
            }
            return array(
                'record_title' => $user->display_name,
                'record_status' => 'active',
                'edit_url' => admin_url('user-edit.php?user_id=' . $record_id),
                'view_url' => get_author_posts_url($record_id)
            );
        case 'comments':
        case 'woo_reviews':
            $comment = get_comment($record_id);
            if (!$comment) {
                return array();
            }
            return array(
                'record_title' => wp_trim_words($comment->comment_content, 10),
                'record_status' => $comment->comment_approved,
                'edit_url' => admin_url('comment.php?action=editcomment&c=' . $record_id),
                'view_url' => get_comment_link($comment)
            );
        case 'gf_entries':
            if (class_exists('GFAPI')) {
                $entry = GFAPI::get_entry($record_id);
                if (!is_wp_error($entry)) {
                    return array(
                        'record_title' => 'Gravity Forms Entry #' . $record_id,
                        'record_status' => $entry['status'],
                        'edit_url' => admin_url('admin.php?page=gf_entries&view=entry&id=' . $entry['form_id'] . '&lid=' . $record_id),
                        'view_url' => ''
                    );
                }
            }
            return array();
        case 'shop_order':
            // For WooCommerce orders, use wc_get_order which handles both HPOS and legacy
            if (function_exists('wc_get_order')) {
                $order = wc_get_order($record_id);
                if ($order && !is_wp_error($order)) {
                    return array(
                        'record_title' => 'Order #' . $order->get_order_number(),
                        'record_status' => $order->get_status(),
                        'edit_url' => $order->get_edit_order_url(),
                        'view_url' => ''
                    );
                }
            }
            return array();
        default:
            // All post-based imports
            $post = get_post($record_id);
            if (!$post) {
                return array();
            }
            return array(
                'record_title' => $post->post_title,
                'record_status' => $post->post_status,
                'edit_url' => admin_url('post.php?post=' . $record_id . '&action=edit'),
                'view_url' => get_permalink($record_id)
            );
    }
}

/**
 * Find or create a preview import record based on context
 */
function findOrCreatePreviewImport($preview_data, $merged_options, $file_path, $found_records) {
    global $wpdb;

    // Determine if this is a new import (step 3) or existing import
    $input = new PMXI_Input();
    $post = $input->post(array('import_id' => 0));
    $is_new_import = empty($post['import_id']) || $post['import_id'] === 'new';

    if ($is_new_import) {
        // For new imports (step 3), create or reuse session-based preview import
        return findOrCreateSessionPreviewImport($preview_data, $merged_options, $file_path, $found_records);
    } else {
        // For existing imports, create or reuse persistent preview import
        return findOrCreatePersistentPreviewImport($post['import_id'], $preview_data, $merged_options, $file_path, $found_records);
    }
}

/**
 * Find or create a session-based preview import for new imports (step 3)
 */
function findOrCreateSessionPreviewImport($preview_data, $merged_options, $file_path, $found_records) {
    global $wpdb;

    // Create a session identifier based on file path and xpath
    $session_key = md5($file_path . '|' . $preview_data['xpath'] . '|' . session_id());

    // Look for existing session preview import
    $existing_id = $wpdb->get_var($wpdb->prepare("
        SELECT id FROM {$wpdb->prefix}pmxi_imports
        WHERE friendly_name = %s
        AND is_preview = 1
        AND parent_import_id = 0
        ORDER BY id DESC
        LIMIT 1
    ", 'Session Preview Import - ' . $session_key));

    if ($existing_id) {
        // Reuse existing session preview import
        $import = new PMXI_Import_Record();
        $import->getById($existing_id);

        // Copy source file to preview directory
        $preview_file_path = wpai_copy_file_to_preview_directory($file_path, $import->id);

        // Reset counters for fresh preview and ensure fields are aligned with constructed feed
        $import->set(array(
            'name' => 'Session Preview Import',
            'feed_type' => '',
            'executing' => 0,
            'imported' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'options' => $merged_options,
            'path' => $preview_file_path,
            'xpath' => $preview_data['xpath'],
            'root_element' => $preview_data['root_element'],
            'count' => $found_records,
            'last_activity' => date('Y-m-d H:i:s')
        ))->update();

        // Update file history record
        $history_file = new PMXI_File_Record();
        $history_file->getBy(array('import_id' => $import->id), 'id DESC');
        if (!$history_file->isEmpty()) {
            $history_file->set(array(
                'path' => $preview_file_path,
                'registered_on' => date('Y-m-d H:i:s'),
            ))->update();
        }

        return $import;
    }

    // Create new session preview import that mimics the real import creation process
    $import = new PMXI_Import_Record();
    $import->set(array(
        'name' => 'Session Preview Import',
        'type' => 'upload',
        'path' => '', // Will be set after we create the preview directory
        'root_element' => $preview_data['root_element'],
        'xpath' => $preview_data['xpath'],
        'options' => $merged_options,
        'count' => $found_records,
        'friendly_name' => 'Session Preview Import - ' . $session_key,
        'feed_type' => '',
        'parent_import_id' => 0,
        'queue_chunk_number' => 0,
        'triggered' => 0,
        'processing' => 0,
        'executing' => 0,
        'iteration' => 0,
        'is_preview' => 1,
        'imported' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'deleted' => 0,
        'registered_on' => date('Y-m-d H:i:s'),
        'failed_on' => '0000-00-00 00:00:00',
        'settings_update_on' => '0000-00-00 00:00:00',
        'last_activity' => date('Y-m-d H:i:s')
    ))->save();

    // Copy source file to preview directory
    $preview_file_path = wpai_copy_file_to_preview_directory($file_path, $import->id);

    // Update import with preview file path
    $import->set(array('path' => $preview_file_path))->update();

    // Create file history record with preview file path
    $history_file = new PMXI_File_Record();
    $history_file->set(array(
        'name' => $import->name,
        'import_id' => $import->id,
        'path' => $preview_file_path,
        'registered_on' => date('Y-m-d H:i:s'),
    ))->save();

    return $import;
}

/**
 * Find or create a persistent preview import for existing imports
 */
function findOrCreatePersistentPreviewImport($parent_import_id, $preview_data, $merged_options, $file_path, $found_records) {
    global $wpdb;

    // Look for existing preview import for this parent
    $existing_id = $wpdb->get_var($wpdb->prepare("
        SELECT id FROM {$wpdb->prefix}pmxi_imports
        WHERE parent_import_id = %d
        AND is_preview = 1
        ORDER BY id DESC
        LIMIT 1
    ", $parent_import_id));

    if ($existing_id) {
        // Reuse existing preview import
        $import = new PMXI_Import_Record();
        $import->getById($existing_id);

        // Copy source file to preview directory
        $preview_file_path = wpai_copy_file_to_preview_directory($file_path, $import->id);

        // Reset counters for fresh preview and align fields with constructed feed
        $import->set(array(
            'imported' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'options' => $merged_options,
            'path' => $preview_file_path,
            'xpath' => $preview_data['xpath'],
            'root_element' => $preview_data['root_element'],
            'count' => $found_records,
            'last_activity' => date('Y-m-d H:i:s')
        ))->update();

        $import->getById($import->id);

        // Update file history record
        $history_file = new PMXI_File_Record();
        $history_file->getBy(array('import_id' => $import->id), 'id DESC');
        if ($history_file->isEmpty()) {
            // Create new file history if it doesn't exist
            $history_file->set(array(
                'name' => $import->name,
                'import_id' => $import->id,
                'path' => $preview_file_path,
                'registered_on' => date('Y-m-d H:i:s'),
            ))->save();
        } else {
            // Update existing file history
            $history_file->set(array(
                'path' => $preview_file_path,
                'registered_on' => date('Y-m-d H:i:s'),
            ))->update();
        }

        return $import;
    }

    // Create new persistent preview import - mimic real import creation
    $import = new PMXI_Import_Record();
    $import->set(array(
        'name' => 'Preview Import for #' . $parent_import_id,
        'type' => 'upload',
        'path' => '', // Will be set after we create the preview directory
        'root_element' => $preview_data['root_element'],
        'xpath' => $preview_data['xpath'],
        'options' => $merged_options,
        'count' => $found_records,
        'friendly_name' => 'Preview Import for #' . $parent_import_id,
        'feed_type' => '',
        'parent_import_id' => $parent_import_id,
        'queue_chunk_number' => 0,
        'triggered' => 0,
        'processing' => 0,
        'executing' => 0,
        'iteration' => 0,
        'is_preview' => 1,
        'imported' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'deleted' => 0,
        'registered_on' => date('Y-m-d H:i:s'),
        'failed_on' => '0000-00-00 00:00:00',
        'settings_update_on' => '0000-00-00 00:00:00',
        'last_activity' => date('Y-m-d H:i:s')
    ))->save();

    // Copy source file to preview directory
    $preview_file_path = wpai_copy_file_to_preview_directory($file_path, $import->id);

    // Update import with preview file path
    $import->set(array('path' => $preview_file_path))->update();

    // Create file history record with preview file path
    $history_file = new PMXI_File_Record();
    $history_file->set(array(
        'name' => $import->name,
        'import_id' => $import->id,
        'path' => $preview_file_path,
        'registered_on' => date('Y-m-d H:i:s'),
    ))->save();

    return $import;
}

/**
 * Calculate which records to import based on preview mode
 */
function wpai_calculate_preview_records($post) {
    $records_to_import = array();

    switch ($post['preview_mode']) {
        case 'first':
            $records_to_import = array(1);
            break;

        case 'specific':
            $spec = isset($post['specific_record']) ? trim((string) $post['specific_record']) : '';
            if ($spec !== '') {
                // Mirror real import parsing: allow comma-separated numbers and ranges like 1-4
                $spec = apply_filters('wp_all_import_specified_records', $spec, 0, false);
                foreach (preg_split('% *, *%', $spec, -1, PREG_SPLIT_NO_EMPTY) as $chunk) {
                    if (preg_match('%^(\d+)\s*-\s*(\d+)$%', $chunk, $m)) {
                        $start = intval($m[1]);
                        $end = intval($m[2]);
                        if ($start > 0 && $end >= $start) {
                            $records_to_import = array_merge($records_to_import, range($start, $end));
                        }
                    } else {
                        $n = intval($chunk);
                        if ($n > 0) { $records_to_import[] = $n; }
                    }
                }
                // Deduplicate and sort
                $records_to_import = array_values(array_unique($records_to_import));
                sort($records_to_import);
            }
            break;

        case 'range':
            $range_start = intval($post['range_start']);
            $range_end = intval($post['range_end']);
            if ($range_start > 0 && $range_end >= $range_start) {
                $records_to_import = range($range_start, $range_end);
            }
            break;

        case 'multiple':
            if (!empty($post['multiple_records'])) {
                $spec = trim((string) $post['multiple_records']);
                $spec = apply_filters('wp_all_import_specified_records', $spec, 0, false);
                foreach (preg_split('% *, *%', $spec, -1, PREG_SPLIT_NO_EMPTY) as $chunk) {
                    if (preg_match('%^(\d+)\s*-\s*(\d+)$%', $chunk, $m)) {
                        $start = intval($m[1]);
                        $end = intval($m[2]);
                        if ($start > 0 && $end >= $start) {
                            $records_to_import = array_merge($records_to_import, range($start, $end));
                        }
                    } else {
                        $n = intval($chunk);
                        if ($n > 0) { $records_to_import[] = $n; }
                    }
                }
                // Remove duplicates and sort
                $records_to_import = array_values(array_unique($records_to_import));
                sort($records_to_import);
            }
            break;

        default:
            // Default to first record
            $records_to_import = array(1);
            break;
    }

    return $records_to_import;
}

/**
 * Copy source file to preview-specific directory
 *
 * Creates an isolated directory for the preview import and copies the source file there.
 * This ensures preview imports don't interfere with the parent import's files.
 *
 * @param string $source_file_path The original file path (relative or absolute)
 * @param int $preview_import_id The preview import ID
 * @return string The relative path to the copied file
 */
function wpai_copy_file_to_preview_directory($source_file_path, $preview_import_id) {
    // Get absolute path of source file
    $source_absolute_path = wp_all_import_get_absolute_path($source_file_path);

    if (!file_exists($source_absolute_path)) {
        throw new Exception(sprintf(__('Source file not found: %s', 'wp-all-import-pro'), $source_file_path));
    }

    // Create preview directory structure: uploads/wpallimport/previews/{import_id}/
    $uploads = wp_upload_dir();
    $preview_base_dir = $uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::UPLOADS_DIRECTORY . DIRECTORY_SEPARATOR . 'previews';
    $preview_import_dir = $preview_base_dir . DIRECTORY_SEPARATOR . $preview_import_id;

    // Create directory if it doesn't exist
    if (!is_dir($preview_import_dir)) {
        wp_mkdir_p($preview_import_dir);
    }

    // Get filename from source
    $filename = basename($source_absolute_path);

    // Destination path
    $dest_absolute_path = $preview_import_dir . DIRECTORY_SEPARATOR . $filename;

    // Copy the file
    if (!copy($source_absolute_path, $dest_absolute_path)) {
        throw new Exception(sprintf(__('Failed to copy file to preview directory: %s', 'wp-all-import-pro'), $dest_absolute_path));
    }

    // Return relative path for storage in database
    // Format: wpallimport/uploads/previews/{import_id}/{filename}
    $relative_path = PMXI_Plugin::UPLOADS_DIRECTORY . '/previews/' . $preview_import_id . '/' . $filename;

    return $relative_path;
}

/**
 * Delete preview directory and all its contents
 *
 * @param int $preview_import_id The preview import ID
 * @return bool True on success, false on failure
 */
function wpai_delete_preview_directory($preview_import_id) {
    $uploads = wp_upload_dir();
    $preview_import_dir = $uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::UPLOADS_DIRECTORY . DIRECTORY_SEPARATOR . 'previews' . DIRECTORY_SEPARATOR . $preview_import_id;

    if (!is_dir($preview_import_dir)) {
        return true; // Directory doesn't exist, nothing to delete
    }

    // Recursively delete directory and all contents
    return wpai_recursive_rmdir($preview_import_dir);
}

/**
 * Recursively delete a directory and all its contents
 *
 * @param string $dir Directory path
 * @return bool True on success, false on failure
 */
function wpai_recursive_rmdir($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;

        if (is_dir($path)) {
            wpai_recursive_rmdir($path);
        } else {
            @unlink($path);
        }
    }

    return @rmdir($dir);
}
