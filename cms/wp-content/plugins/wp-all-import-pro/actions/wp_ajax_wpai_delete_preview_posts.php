<?php
/**
 * AJAX handler to delete preview posts created during a preview modal session
 */

function pmxi_wp_ajax_wpai_delete_preview_posts() {
    // Security check
    if (!check_ajax_referer('wp_all_import_secure', 'security', false)) {
        wp_send_json_error(array('message' => __('Security check failed', 'wp-all-import-pro')), 403);
    }

    if (!current_user_can(PMXI_Plugin::$capabilities)) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-all-import-pro')), 403);
    }

    try {
        $record_ids = isset($_POST['post_ids']) ? $_POST['post_ids'] : array();

        // Ensure we have an array
        if (!is_array($record_ids)) {
            $record_ids = array();
        }

        if (empty($record_ids)) {
            wp_send_json_success(array('message' => __('No records to delete', 'wp-all-import-pro'), 'deleted' => 0));
        }

        $deleted_count = 0;
        $failed_ids = array();

        foreach ($record_ids as $record_id) {
            $record_id = intval($record_id);

            if ($record_id <= 0) {
                continue;
            }

            // Try to determine what type of record this is and delete accordingly
            $deleted = false;

            // Check if it's a post
            $is_preview_post = get_post_meta($record_id, '_wpai_preview_post', true);
            if ($is_preview_post) {
                $result = wp_delete_post($record_id, true);
                if ($result) {
                    $deleted = true;
                    $deleted_count++;
                }
            }

            // Check if it's a user
            if (!$deleted) {
                $is_preview_user = get_user_meta($record_id, '_wpai_preview_user', true);
                if ($is_preview_user) {
                    require_once(ABSPATH . 'wp-admin/includes/user.php');
                    $result = wp_delete_user($record_id);
                    if ($result) {
                        $deleted = true;
                        $deleted_count++;
                    }
                }
            }

            // Check if it's a term
            if (!$deleted) {
                $is_preview_term = get_term_meta($record_id, '_wpai_preview_term', true);
                if ($is_preview_term) {
                    $term = get_term($record_id);
                    if ($term && !is_wp_error($term)) {
                        $result = wp_delete_term($record_id, $term->taxonomy);
                        if ($result && !is_wp_error($result)) {
                            $deleted = true;
                            $deleted_count++;
                        }
                    }
                }
            }

            // Check if it's a comment
            if (!$deleted) {
                $is_preview_comment = get_comment_meta($record_id, '_wpai_preview_comment', true);
                if ($is_preview_comment) {
                    $result = wp_delete_comment($record_id, true);
                    if ($result) {
                        $deleted = true;
                        $deleted_count++;
                    }
                }
            }

            // Check if it's a Gravity Forms entry
            if (!$deleted) {
                if (class_exists('GFAPI')) {
                    $is_preview_entry = gform_get_meta($record_id, '_wpai_preview_entry');
                    if ($is_preview_entry) {
                        $result = GFAPI::delete_entry($record_id);
                        if (!is_wp_error($result)) {
                            $deleted = true;
                            $deleted_count++;
                        }
                    }
                }
            }

            // Check if it's a WooCommerce order (HPOS or legacy)
            if (!$deleted) {
                if (function_exists('wc_get_order')) {
                    $order = wc_get_order($record_id);
                    if ($order && !is_wp_error($order)) {
                        $is_preview_order = $order->get_meta('_wpai_preview_order', true);
                        if ($is_preview_order) {
                            $result = $order->delete(true);
                            if ($result) {
                                $deleted = true;
                                $deleted_count++;
                            }
                        }
                    }
                }
            }

            // If nothing was deleted, add to failed list
            if (!$deleted) {
                $failed_ids[] = $record_id;
            }
        }

        if ($deleted_count > 0) {
            wp_send_json_success(array(
                'message' => sprintf(__('Deleted %d preview record(s)', 'wp-all-import-pro'), $deleted_count),
                'deleted' => $deleted_count,
                'failed' => $failed_ids
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No preview records were deleted', 'wp-all-import-pro'),
                'failed' => $failed_ids
            ));
        }

    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()), 500);
    }
}

add_action('wp_ajax_wpai_delete_preview_posts', 'pmxi_wp_ajax_wpai_delete_preview_posts');

