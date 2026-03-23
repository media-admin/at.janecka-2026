<?php
/**
 * AJAX handler to cleanup orphaned preview posts
 * This runs as a fallback to clean up preview posts that weren't deleted properly
 */

function pmxi_wp_ajax_wpai_cleanup_orphaned_previews() {
    // Security check
    if (!check_ajax_referer('wp_all_import_secure', 'security', false)) {
        wp_send_json_error(array('message' => __('Security check failed', 'wp-all-import-pro')), 403);
    }

    if (!current_user_can(PMXI_Plugin::$capabilities)) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-all-import-pro')), 403);
    }

    try {
        // Find all preview posts older than 1 hour
        $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $args = array(
            'post_type' => 'any',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_wpai_preview_post',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'date_query' => array(
                array(
                    'before' => $one_hour_ago,
                    'inclusive' => true,
                )
            ),
            'fields' => 'ids'
        );

        $orphaned_posts = get_posts($args);
        $deleted_count = 0;
        $failed_count = 0;

        if (!empty($orphaned_posts)) {
            foreach ($orphaned_posts as $post_id) {
                // Force delete (bypass trash)
                $result = wp_delete_post($post_id, true);
                
                if ($result) {
                    $deleted_count++;
                } else {
                    $failed_count++;
                }
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(
                __('Cleanup complete: %d orphaned preview posts deleted, %d failed', 'wp-all-import-pro'),
                $deleted_count,
                $failed_count
            ),
            'deleted' => $deleted_count,
            'failed' => $failed_count,
            'total_found' => count($orphaned_posts)
        ));

    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()), 500);
    }
}

add_action('wp_ajax_wpai_cleanup_orphaned_previews', 'pmxi_wp_ajax_wpai_cleanup_orphaned_previews');

