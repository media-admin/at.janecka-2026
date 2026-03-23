<?php

function pmxi_wp_ajax_wpai_delete_preview_session() {
    if (!check_ajax_referer('wp_all_import_secure', 'security', false)) {
        wp_send_json_error(array('message' => __('Security check failed', 'wp-all-import-pro')), 403);
    }

    if (!current_user_can(PMXI_Plugin::$capabilities)) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-all-import-pro')), 403);
    }

    try {
        $preview_session_id = isset($_POST['preview_session_id']) ? sanitize_text_field($_POST['preview_session_id']) : '';

        if (empty($preview_session_id)) {
            wp_send_json_error(array('message' => __('No preview session ID provided', 'wp-all-import-pro')), 400);
        }

        $session_data = get_transient('wpai_preview_session_' . $preview_session_id);

        if ($session_data === false || empty($session_data['preview_import_id'])) {
            wp_send_json_success(array(
                'message' => __('No preview records found for this session', 'wp-all-import-pro'),
                'deleted' => 0
            ));
        }

        $preview_import_id = intval($session_data['preview_import_id']);

        $import = new PMXI_Import_Record();
        $import->getById($preview_import_id);

        if ($import->isEmpty()) {
            wp_send_json_error(array('message' => __('Preview import not found', 'wp-all-import-pro')), 404);
        }

        if (!$import->is_preview) {
            wp_send_json_error(array('message' => __('Not a preview import', 'wp-all-import-pro')), 400);
        }

        global $wpdb;
        $table_prefix = PMXI_Plugin::getInstance()->getTablePrefix();

        $record_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$table_prefix}posts WHERE import_id = %d",
            $preview_import_id
        ));

        $deleted_count = 0;
        $custom_type = $import->options['custom_type'];

        foreach ($record_ids as $record_id) {
            $record_id = intval($record_id);

            if ($record_id <= 0) {
                continue;
            }

            $deleted = false;

            if (in_array($custom_type, array('import_users', 'shop_customer'))) {
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                $result = wp_delete_user($record_id);
                if ($result) {
                    $deleted = true;
                    $deleted_count++;
                }
            } elseif ($custom_type === 'taxonomies') {
                $term = get_term($record_id);
                if ($term && !is_wp_error($term)) {
                    $result = wp_delete_term($record_id, $term->taxonomy);
                    if ($result && !is_wp_error($result)) {
                        $deleted = true;
                        $deleted_count++;
                    }
                }
            } elseif (in_array($custom_type, array('comments', 'woo_reviews'))) {
                $result = wp_delete_comment($record_id, true);
                if ($result) {
                    $deleted = true;
                    $deleted_count++;
                }
            } elseif ($custom_type === 'gf_entries') {
                if (class_exists('GFAPI')) {
                    $result = GFAPI::delete_entry($record_id);
                    if (!is_wp_error($result)) {
                        $deleted = true;
                        $deleted_count++;
                    }
                }
            } elseif ($custom_type === 'shop_order') {
                if (function_exists('wc_get_order')) {
                    $order = wc_get_order($record_id);
                    if ($order && !is_wp_error($order)) {
                        // For WooCommerce orders, force delete bypassing trash
                        // WC_Order::delete() returns the deleted order object on success, false on failure
                        $result = $order->delete(true);
                        // Check if result is not false (object or true means success)
                        if ($result !== false && !is_wp_error($result)) {
                            $deleted = true;
                            $deleted_count++;
                            // Clear WooCommerce caches for this order
                            if (function_exists('wc_delete_shop_order_transients')) {
                                wc_delete_shop_order_transients($record_id);
                            }
                        }
                    }
                }
            } else {
                $result = wp_delete_post($record_id, true);
                if ($result) {
                    $deleted = true;
                    $deleted_count++;
                }
            }
        }

        // Delete attachments scoped to this specific preview import
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

        // Delete taxonomy terms created during preview (supplemental to post imports)
        // These are terms created when assigning taxonomies to posts, not tracked in pmxi_posts
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
                // Get the term to find its taxonomy
                $term = get_term($term_id);
                if ($term && !is_wp_error($term)) {
                    $result = wp_delete_term($term_id, $term->taxonomy);
                    if ($result && !is_wp_error($result)) {
                        $term_count++;
                    }
                }
            }
        }

        $wpdb->delete(
            $table_prefix . 'posts',
            array('import_id' => $preview_import_id),
            array('%d')
        );

        $import->set(array(
            'imported' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'deleted' => 0,
            'processing' => 0,
            'executing' => 0
        ))->update();

        delete_transient('wpai_preview_session_' . $preview_session_id);

        $message_parts = array();
        if ($deleted_count > 0) {
            $message_parts[] = sprintf(__('%d preview record(s)', 'wp-all-import-pro'), $deleted_count);
        }
        if ($attachment_count > 0) {
            $message_parts[] = sprintf(__('%d attachment(s)', 'wp-all-import-pro'), $attachment_count);
        }
        if ($term_count > 0) {
            $message_parts[] = sprintf(__('%d taxonomy term(s)', 'wp-all-import-pro'), $term_count);
        }

        $message = !empty($message_parts)
            ? sprintf(__('Deleted %s', 'wp-all-import-pro'), implode(', ', $message_parts))
            : __('No preview records found to delete', 'wp-all-import-pro');

        wp_send_json_success(array(
            'message' => $message,
            'deleted' => $deleted_count,
            'attachments_deleted' => $attachment_count,
            'terms_deleted' => $term_count
        ));

    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()), 500);
    }
}

add_action('wp_ajax_wpai_delete_preview_session', 'pmxi_wp_ajax_wpai_delete_preview_session');

