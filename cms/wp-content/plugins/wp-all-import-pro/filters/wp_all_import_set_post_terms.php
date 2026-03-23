<?php

/**
 * @param $assign_taxes
 * @param $tx_name
 * @param $pid
 * @param $import_id
 * @return array
 */
function pmxi_wp_all_import_set_post_terms($assign_taxes, $tx_name, $pid, $import_id){

    // Handle category taxonomy and other taxonomies with default terms
    if ($tx_name == 'category' || get_option('default_term_' . $tx_name, false)) {

        // Get import record to check update mode
        $import = new PMXI_Import_Record();
        $import->getById($import_id);

        // Check if we're in "add_new" mode
        if (!$import->isEmpty() &&
            isset($import->options['update_categories_logic']) &&
            $import->options['update_categories_logic'] == 'add_new') {

            // In "add_new" mode, get existing terms
            $existing_terms = wp_get_object_terms($pid, $tx_name, array('fields' => 'tt_ids'));

            if (!is_wp_error($existing_terms)) {
                // Get the default term ID for this taxonomy
                if ($tx_name == 'category') {
                    $default_term_id = get_option('default_category', 0);
                } elseif ($tx_name == 'product_cat') {
                    $default_term_id = get_option('default_product_cat', 0);
                } else {
                    $default_term_id = get_option('default_term_' . $tx_name, 0);
                }

                if (!empty($assign_taxes)) {
                    // We have terms to assign (may include default + new terms)
                    if (!empty($existing_terms)) {
                        // Check if existing terms are ONLY the default term
                        if (count($existing_terms) == 1 && $default_term_id && in_array($default_term_id, $existing_terms)) {
                            // Only default term exists, remove it from assign_taxes if present
                            $filtered_taxes = array_diff($assign_taxes, array($default_term_id));
                            if (!empty($filtered_taxes)) {
                                return array_values($filtered_taxes); // Re-index array
                            } else {
                                // Only default term in assign_taxes, keep existing
                                return $existing_terms;
                            }
                        } else {
                            // Has real terms, use assign_taxes as-is (already merged by import logic)
                            return $assign_taxes;
                        }
                    } else {
                        // No existing terms, just use assign_taxes
                        return $assign_taxes;
                    }
                } else {
                    // No new terms to import, keep existing terms
                    return $existing_terms;
                }
            }
        }

        // Not in "add_new" mode, or no existing terms - add default if no terms assigned
        if (empty($assign_taxes)) {
            if ($tx_name == 'category') {
                $term = is_exists_term('uncategorized', $tx_name, 0);
                if ( !empty($term) and ! is_wp_error($term) ) {
                    $assign_taxes[] = $term['term_taxonomy_id'];
                }
            } else {
                // For custom taxonomies, get the default term
                $default_term_id = get_option('default_term_' . $tx_name, 0);
                if ($default_term_id) {
                    $assign_taxes[] = $default_term_id;
                }
            }
        }
    }

    return $assign_taxes;
}
