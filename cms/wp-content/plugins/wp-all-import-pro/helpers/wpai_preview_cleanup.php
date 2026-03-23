<?php

/**
 * Preview Import Cleanup Functions
 *
 * Handles cleanup of orphaned preview imports and their associated posts
 */

/**
 * Check if there are any active preview sessions
 * Returns true if ANY preview session is active, regardless of whether it has an import ID yet
 *
 * @return bool True if any preview session is active
 */
function wpai_has_active_preview_sessions() {
    global $wpdb;

    // Find all preview session transient NAMES (not values)
    $transient_names = $wpdb->get_col("
        SELECT REPLACE(option_name, '_transient_', '')
        FROM {$wpdb->options}
        WHERE option_name LIKE '_transient_wpai_preview_session_%'
    ");

    // Use get_transient() to properly check expiration
    foreach ($transient_names as $transient_name) {
        $data = get_transient($transient_name);

        // get_transient() returns false if expired or doesn't exist
        // If we find ANY active session, return true
        if ($data !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Get all preview import IDs from active preview sessions
 * These preview imports should be excluded from cleanup
 *
 * @return array Array of preview import IDs that are currently in use
 */
function wpai_get_active_preview_session_import_ids() {
    global $wpdb;

    $active_import_ids = array();

    // Find all preview session transient NAMES (not values)
    $transient_names = $wpdb->get_col("
        SELECT REPLACE(option_name, '_transient_', '')
        FROM {$wpdb->options}
        WHERE option_name LIKE '_transient_wpai_preview_session_%'
    ");

    // Use get_transient() to properly check expiration
    foreach ($transient_names as $transient_name) {
        $data = get_transient($transient_name);

        // get_transient() returns false if expired or doesn't exist
        if ($data !== false && is_array($data) && !empty($data['preview_import_id'])) {
            $active_import_ids[] = intval($data['preview_import_id']);
        }
    }

    return array_unique($active_import_ids);
}

/**
 * Cleanup preview records on admin page load when not on template page
 * This catches any records that were missed due to async timing issues
 *
 * Only runs when:
 * - User is in WP admin
 * - User has appropriate permissions
 * - User is NOT on the template page (step 3) where preview is actively used
 * - There are NO active preview sessions (to avoid deleting records while user is previewing)
 * - Cleanup hasn't run recently (throttled to once per minute)
 *
 * IMPORTANT: This function will NOT run at all if ANY preview session is active.
 * This prevents race conditions where cleanup might delete records before the session
 * has a chance to register its import ID.
 */
function wpai_cleanup_preview_records_on_admin_load() {
    // Only run in admin
    if (!is_admin()) {
        return;
    }

    // Only run for users with import permissions
    if (!current_user_can(PMXI_Plugin::$capabilities)) {
        return;
    }

    // Check if is_preview column exists
    if (!wpai_is_preview_column_exists()) {
        return;
    }

    // Get current page
    $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    $current_action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

    // Don't run on template page (step 3) where preview is actively used
    // Template page is: page=pmxi-admin-import&action=template
    if ($current_page === 'pmxi-admin-import' && $current_action === 'template') {
        return;
    }

    // CRITICAL: Don't run cleanup if ANY preview session is active
    // This prevents race conditions and ensures we never delete records while user is previewing
    $has_active_sessions = wpai_has_active_preview_sessions();
    if ($has_active_sessions) {
        return;
    }

    // Throttle cleanup to once per minute to ensure quick cleanup without performance impact
    $throttle_key = 'wpai_preview_cleanup_last_run';
    $last_run = get_transient($throttle_key);

    if ($last_run !== false) {
        // Cleanup ran recently, skip
        return;
    }

    // Set throttle transient (1 minute)
    set_transient($throttle_key, time(), MINUTE_IN_SECONDS);

    // Run the cleanup
    wpai_cleanup_all_preview_records();
}
add_action('admin_init', 'wpai_cleanup_preview_records_on_admin_load', 999);

/**
 * AJAX handler to register a preview session when modal opens
 * This creates the session transient immediately so heartbeat can keep it alive
 */
function wpai_ajax_register_preview_session() {
    check_ajax_referer('wpai_preview_session', 'security');

    if (!current_user_can(PMXI_Plugin::$capabilities)) {
        wp_send_json_error('Insufficient permissions');
    }

    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    if (empty($session_id)) {
        wp_send_json_error('No session ID provided');
    }

    // Create the session transient
    // Set to 5 minutes to account for heartbeat interval variations (can be 1-2 minutes)
    set_transient('wpai_preview_session_' . $session_id, array(
        'preview_import_id' => null,
        'started' => time()
    ), 5 * MINUTE_IN_SECONDS);

    wp_send_json_success();
}
add_action('wp_ajax_wpai_register_preview_session', 'wpai_ajax_register_preview_session');

/**
 * Keep preview sessions alive via WordPress heartbeat
 * This ensures active preview sessions don't expire while users are on the template page
 * Heartbeat runs every ~60 seconds, so we set transient to 2 minutes for safety margin
 */
function wpai_preview_heartbeat_received($response, $data) {

    // Check if this is a preview heartbeat
    if (isset($data['wpai_preview_session_id']) && !empty($data['wpai_preview_session_id'])) {
        $session_id = sanitize_text_field($data['wpai_preview_session_id']);

        // Get existing session data
        $session_data = get_transient('wpai_preview_session_' . $session_id);

        if ($session_data !== false) {
            // Refresh the transient to keep it alive
            // Set to 5 minutes to account for heartbeat interval variations (can be 1-2 minutes)
            set_transient('wpai_preview_session_' . $session_id, $session_data, 5 * MINUTE_IN_SECONDS);
            $response['wpai_preview_session_alive'] = true;
        } else {
            $response['wpai_preview_session_alive'] = false;
        }
    }

    return $response;
}
add_filter('heartbeat_received', 'wpai_preview_heartbeat_received', 10, 2);

/**
 * Cleanup all preview records from all preview imports
 * This is an efficient cleanup that only deletes records, not the preview imports themselves
 *
 * This catches records that were missed due to:
 * - Async timing issues
 * - In-flight preview operations when modal was closed
 * - Browser crashes or page refreshes during preview
 *
 * IMPORTANT: This function protects active preview sessions from cleanup.
 * It will NOT clean up preview imports that are currently being used by any user.
 *
 * PERFORMANCE: Optimized to be near-instant when there's nothing to clean up.
 * Uses efficient queries to check for records before attempting cleanup.
 */
function wpai_cleanup_all_preview_records() {
    global $wpdb;

    $table_prefix = PMXI_Plugin::getInstance()->getTablePrefix();

    // OPTIMIZATION: First check if there are ANY preview imports with records
    // This makes the function instant when there's nothing to clean up
    $has_preview_records = $wpdb->get_var("
        SELECT 1
        FROM {$table_prefix}imports i
        INNER JOIN {$table_prefix}posts p ON i.id = p.import_id
        WHERE i.is_preview = 1
        LIMIT 1
    ");

    if (!$has_preview_records) {
        // No preview records exist at all - instant return
        return;
    }

    // Get active preview session import IDs to protect them from cleanup
    // This ensures we don't delete records from previews that are currently in use by any user
    $active_preview_import_ids = array();
    if (function_exists('wpai_get_active_preview_session_import_ids')) {
        $active_preview_import_ids = wpai_get_active_preview_session_import_ids();
    }

    // Get only preview imports that have records AND are not active
    if (!empty($active_preview_import_ids)) {
        $placeholders = implode(',', array_fill(0, count($active_preview_import_ids), '%d'));
        $preview_import_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT i.id
            FROM {$table_prefix}imports i
            INNER JOIN {$table_prefix}posts p ON i.id = p.import_id
            WHERE i.is_preview = 1
            AND i.id NOT IN ($placeholders)
        ", $active_preview_import_ids));
    } else {
        $preview_import_ids = $wpdb->get_col("
            SELECT DISTINCT i.id
            FROM {$table_prefix}imports i
            INNER JOIN {$table_prefix}posts p ON i.id = p.import_id
            WHERE i.is_preview = 1
        ");
    }

    if (empty($preview_import_ids)) {
        // All preview imports are currently active, nothing to clean up
        return;
    }

    $total_deleted = 0;
    $total_attachments = 0;
    $total_terms = 0;

    foreach ($preview_import_ids as $preview_import_id) {
        // Load the import to get the custom_type
        $import = new PMXI_Import_Record();
        $import->getById($preview_import_id);

        if ($import->isEmpty() || !$import->is_preview) {
            continue;
        }

        // Get all record IDs for this preview import
        $record_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$table_prefix}posts WHERE import_id = %d",
            $preview_import_id
        ));

        if (empty($record_ids)) {
            // No records to clean up, but reset counters anyway
            $import->set(array(
                'imported' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'deleted' => 0,
                'processing' => 0,
                'executing' => 0
            ))->update();
            continue;
        }

        $deleted_count = 0;
        $custom_type = $import->options['custom_type'];

        // Delete records based on type
        foreach ($record_ids as $record_id) {
            $record_id = intval($record_id);

            if ($record_id <= 0) {
                continue;
            }

            if (in_array($custom_type, array('import_users', 'shop_customer'))) {
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                if (wp_delete_user($record_id)) {
                    $deleted_count++;
                }
            } elseif ($custom_type === 'taxonomies') {
                $term = get_term($record_id);
                if ($term && !is_wp_error($term)) {
                    $result = wp_delete_term($record_id, $term->taxonomy);
                    if ($result && !is_wp_error($result)) {
                        $deleted_count++;
                    }
                }
            } elseif (in_array($custom_type, array('comments', 'woo_reviews'))) {
                if (wp_delete_comment($record_id, true)) {
                    $deleted_count++;
                }
            } elseif ($custom_type === 'gf_entries') {
                if (class_exists('GFAPI')) {
                    $result = GFAPI::delete_entry($record_id);
                    if (!is_wp_error($result)) {
                        $deleted_count++;
                    }
                }
            } elseif ($custom_type === 'shop_order') {
                if (function_exists('wc_get_order')) {
                    $order = wc_get_order($record_id);
                    if ($order && !is_wp_error($order)) {
                        $result = $order->delete(true);
                        if ($result !== false && !is_wp_error($result)) {
                            $deleted_count++;
                            if (function_exists('wc_delete_shop_order_transients')) {
                                wc_delete_shop_order_transients($record_id);
                            }
                        }
                    }
                }
            } else {
                // Posts and CPTs
                if (wp_delete_post($record_id, true)) {
                    $deleted_count++;
                }
            }
        }

        // Delete attachments for this preview import
        $preview_attachments = get_posts(array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_wpai_preview_post',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_wpai_preview_import_id',
                    'value' => $preview_import_id,
                    'compare' => '='
                )
            )
        ));

        $attachment_count = 0;
        foreach ($preview_attachments as $attachment_id) {
            $is_preview = get_post_meta($attachment_id, '_wpai_preview_post', true);
            $attachment_import_id = get_post_meta($attachment_id, '_wpai_preview_import_id', true);
            if ($is_preview && $attachment_import_id == $preview_import_id) {
                wp_delete_attachment($attachment_id, true);
                $attachment_count++;
            }
        }

        // Delete taxonomy terms created during preview
        $preview_terms = $wpdb->get_results($wpdb->prepare(
            "SELECT tm1.term_id, tt.taxonomy
             FROM {$wpdb->termmeta} tm1
             INNER JOIN {$wpdb->termmeta} tm2 ON tm1.term_id = tm2.term_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tm1.term_id = tt.term_id
             WHERE tm1.meta_key = '_wpai_preview_term'
             AND tm2.meta_key = '_wpai_preview_import_id'
             AND tm2.meta_value = %d",
            $preview_import_id
        ));

        $term_count = 0;
        foreach ($preview_terms as $term_data) {
            $term_id = intval($term_data->term_id);
            $is_preview = get_term_meta($term_id, '_wpai_preview_term', true);
            $term_import_id = get_term_meta($term_id, '_wpai_preview_import_id', true);

            if ($is_preview && $term_import_id == $preview_import_id) {
                $term = get_term($term_id);
                if ($term && !is_wp_error($term)) {
                    $result = wp_delete_term($term_id, $term->taxonomy);
                    if ($result && !is_wp_error($result)) {
                        $term_count++;
                    }
                }
            }
        }

        // Clear pmxi_posts table entries
        $wpdb->delete(
            $table_prefix . 'posts',
            array('import_id' => $preview_import_id),
            array('%d')
        );

        // Reset import counters
        $import->set(array(
            'imported' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'deleted' => 0,
            'processing' => 0,
            'executing' => 0
        ))->update();

        $total_deleted += $deleted_count;
        $total_attachments += $attachment_count;
        $total_terms += $term_count;
    }

    // Log cleanup if any records were removed
    if ($total_deleted > 0 || $total_attachments > 0 || $total_terms > 0) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'WP All Import: Admin page load cleanup removed %d preview records, %d attachments, %d terms from %d preview imports',
                $total_deleted,
                $total_attachments,
                $total_terms,
                count($preview_import_ids)
            ));
        }
    }
}



/**
 * Check if is_preview column exists in imports table
 * @param bool $force_recheck Force a fresh check instead of using cached result
 */
function wpai_is_preview_column_exists($force_recheck = false) {
    global $wpdb;
    static $column_exists = null;

    if (!$force_recheck && $column_exists !== null) {
        return $column_exists;
    }

    $table_prefix = PMXI_Plugin::getInstance()->getTablePrefix();
    $table = $table_prefix . 'imports';

    // Check if column exists
    $column = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'is_preview'");
    $column_exists = !empty($column);

    return $column_exists;
}

/**
 * Ensure is_preview column exists in imports table
 * If it doesn't exist, add it
 */
function wpai_ensure_preview_column_exists() {
    global $wpdb;

    // Check if column already exists
    if (wpai_is_preview_column_exists()) {
        return true;
    }

    // Check if user has ALTER privileges
    $grands = $wpdb->get_results("SELECT * FROM information_schema.user_privileges WHERE grantee LIKE \"'" . DB_USER . "'%\" AND PRIVILEGE_TYPE = 'ALTER' AND IS_GRANTABLE = 'YES';");

    if (empty($grands)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WP All Import: Cannot add is_preview column - user ' . DB_USER . ' does not have ALTER privileges');
        }
        return false;
    }

    $table_prefix = PMXI_Plugin::getInstance()->getTablePrefix();
    $table = $table_prefix . 'imports';

    // Add the column
    $result = $wpdb->query("ALTER TABLE {$table} ADD `is_preview` BOOL NOT NULL DEFAULT 0;");

    if ($result !== false) {
        // Force recheck to update the cache
        wpai_is_preview_column_exists(true);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WP All Import: Successfully added is_preview column to imports table');
        }
        return true;
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WP All Import: Failed to add is_preview column to imports table');
        }
        return false;
    }
}

/**
 * Cleanup orphaned preview imports
 *
 * Removes preview imports where:
 * 1. Parent import no longer exists
 * 2. Preview imports without parent_import_id that are older than 1 hour (for new imports)
 * 3. Preview imports that haven't been used recently (older than 24 hours)
 */
function wpai_cleanup_preview_imports() {
    global $wpdb;

    // Check if is_preview column exists - if not, skip cleanup
    // This can happen on older installations that haven't run the migration yet
    if (!wpai_is_preview_column_exists()) {
        return;
    }

    $table_prefix = PMXI_Plugin::getInstance()->getTablePrefix();

    // Find orphaned preview imports (parent import no longer exists)
    $orphaned_previews = $wpdb->get_results("
        SELECT p.id
        FROM {$table_prefix}imports p
        LEFT JOIN {$table_prefix}imports parent ON p.parent_import_id = parent.id
        WHERE p.is_preview = 1
        AND p.parent_import_id IS NOT NULL
        AND parent.id IS NULL
    ");

    // Find old preview imports without parent (from new imports that were never saved)
    $old_previews_no_parent = $wpdb->get_results($wpdb->prepare(
        "SELECT id FROM {$table_prefix}imports
         WHERE is_preview = 1
         AND parent_import_id IS NULL
         AND registered_on < %s",
        date('Y-m-d H:i:s', strtotime('-1 hour'))
    ));

    // Find stale preview imports that haven't been used recently (older than 24 hours)
    $stale_previews = $wpdb->get_results($wpdb->prepare(
        "SELECT id FROM {$table_prefix}imports
         WHERE is_preview = 1
         AND last_activity < %s",
        date('Y-m-d H:i:s', strtotime('-24 hours'))
    ));

    $all_previews_to_delete = array_merge($orphaned_previews, $old_previews_no_parent, $stale_previews);

    // Remove duplicates (same preview might be in multiple categories)
    $unique_previews = array();
    $seen_ids = array();
    foreach ($all_previews_to_delete as $preview) {
        if (!in_array($preview->id, $seen_ids)) {
            $unique_previews[] = $preview;
            $seen_ids[] = $preview->id;
        }
    }

    $deleted_count = 0;
    foreach ($unique_previews as $preview) {
        if (wpai_delete_preview_import($preview->id)) {
            $deleted_count++;
        }
    }

    // Log cleanup if any previews were removed
    if ($deleted_count > 0 && defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            'WP All Import: Cleaned up %d preview imports and all their associated records (orphaned: %d, old no-parent: %d, stale: %d)',
            $deleted_count,
            count($orphaned_previews),
            count($old_previews_no_parent),
            count($stale_previews)
        ));
    }
}

/**
 * Delete a preview import and all its associated data
 *
 * Preview imports have their own isolated directory with a copy of the source file,
 * so we can safely use the standard import deletion logic which will delete everything
 * including the preview directory.
 */
function wpai_delete_preview_import($preview_import_id) {
    // Load the import record
    $import = new PMXI_Import_Record();
    $import->getById($preview_import_id);

    // Verify this is actually a preview import
    if ($import->isEmpty() || !$import->is_preview) {
        return false;
    }

    // Use the standard deletion logic - this is now safe because preview imports
    // have their own isolated files in uploads/wpallimport/previews/{import_id}/
    // Parameters: delete($keepPosts, $is_deleted_images, $is_delete_attachments, $is_delete_import)
    // $keepPosts = false: Delete all associated records (posts, terms, users, etc.)
    // $is_deleted_images = 'auto': Use default image deletion logic
    // $is_delete_attachments = 'auto': Use default attachment deletion logic
    // $is_delete_import = true: Delete the import record and files
    $import->delete(false, 'auto', 'auto', true);

    // Also delete the preview directory to ensure complete cleanup
    // (The standard delete() may not remove the directory itself, only the files)
    if (function_exists('wpai_delete_preview_directory')) {
        wpai_delete_preview_directory($preview_import_id);
    }

    return true;
}

/**
 * Get preview imports for a specific parent import
 */
function wpai_get_preview_imports($parent_import_id) {
    global $wpdb;

    // Check if is_preview column exists
    if (!wpai_is_preview_column_exists()) {
        return array();
    }

    $table_prefix = PMXI_Plugin::getInstance()->getTablePrefix();

    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_prefix}imports
         WHERE parent_import_id = %d AND is_preview = 1
         ORDER BY registered_on DESC",
        $parent_import_id
    ));
}

/**
 * Check if an import is a preview import
 */
function wpai_is_preview_import($import_id) {
    global $wpdb;

    // Check if is_preview column exists
    if (!wpai_is_preview_column_exists()) {
        return false;
    }

    $table_prefix = PMXI_Plugin::getInstance()->getTablePrefix();

    return (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT is_preview FROM {$table_prefix}imports WHERE id = %d",
        $import_id
    ));
}

/**
 * Clean up preview imports for a specific parent import when it's deleted
 * This function is called via the pmxi_before_import_delete hook
 * @param PMXI_Import_Record $parent_import The import being deleted
 * @param bool $is_delete_posts Whether posts are being deleted with the import
 */
function wpai_cleanup_preview_imports_for_parent($parent_import, $is_delete_posts = true) {
    global $wpdb;

    // Check if is_preview column exists
    if (!wpai_is_preview_column_exists()) {
        return;
    }

    // Extract import ID from the import object
    $parent_import_id = is_object($parent_import) ? $parent_import->id : $parent_import;

    $table_prefix = PMXI_Plugin::getInstance()->getTablePrefix();

    // Find all preview imports for this parent
    $preview_imports = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$table_prefix}imports
         WHERE parent_import_id = %d AND is_preview = 1",
        $parent_import_id
    ));

    $deleted_count = 0;
    foreach ($preview_imports as $preview_id) {
        if (wpai_delete_preview_import($preview_id)) {
            $deleted_count++;
        }
    }

    if ($deleted_count > 0 && defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            'WP All Import: Cleaned up %d preview imports and all their associated records for deleted parent import %d',
            $deleted_count,
            $parent_import_id
        ));
    }
}

// Hook cleanup when imports are deleted (admin-only context)
add_action('pmxi_before_import_delete', 'wpai_cleanup_preview_imports_for_parent');

/**
 * Manual cleanup function for testing/debugging
 * Can be called via wp-admin/admin-ajax.php?action=wpai_manual_preview_cleanup
 */
function wpai_manual_preview_cleanup() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    wpai_cleanup_preview_imports();

    wp_die('Preview cleanup completed.');
}
add_action('wp_ajax_wpai_manual_preview_cleanup', 'wpai_manual_preview_cleanup');
