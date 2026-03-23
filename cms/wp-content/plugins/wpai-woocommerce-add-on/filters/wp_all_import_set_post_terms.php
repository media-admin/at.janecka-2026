<?php

/**
 * @param $assign_taxes
 * @param $tx_name
 * @param $pid
 * @param $import_id
 * @return array
 */
function pmwi_wp_all_import_set_post_terms($assign_taxes, $tx_name, $pid, $import_id){
	// Handle product_cat taxonomy
	if ($tx_name == 'product_cat') {
		// Get import record to check update mode
		$import = new PMXI_Import_Record();
		$import->getById($import_id);

		// Check if we're in "add_new" mode
		if (!$import->isEmpty() &&
		    isset($import->options['update_categories_logic']) &&
		    $import->options['update_categories_logic'] == 'add_new') {

			// In "add_new" mode, get existing categories
			$existing_categories = wp_get_object_terms($pid, 'product_cat', array('fields' => 'tt_ids'));

			if (!is_wp_error($existing_categories)) {
				// Get the default category ID
				$default_category_id = get_option('default_product_cat', 0);

				if (!empty($assign_taxes)) {
					// We have categories to assign - remove default category if present
					$filtered_taxes = array_diff($assign_taxes, array($default_category_id));
					if (!empty($filtered_taxes)) {
						// We have real categories after removing default, use them
						return array_values($filtered_taxes); // Re-index array
					} else {
						// Only default category in assign_taxes
						if (!empty($existing_categories)) {
							// Keep existing categories if we only had default to assign
							return $existing_categories;
						}
						// If no existing categories and only default to assign, fall through to assign default
					}
				} else {
					// No new categories to import
					// If existing categories exist, keep them; otherwise fall through to assign default
					if (!empty($existing_categories)) {
						return $existing_categories;
					}
					// If no existing categories, fall through to assign default category
				}
			}
		}

		// Not in "add_new" mode, or no existing categories - add default if no categories assigned
		if (empty($assign_taxes)) {
			// Use WooCommerce's default product category option (stores term_taxonomy_id)
			$default_category_tt_id = get_option('default_product_cat', 0);
			if ($default_category_tt_id) {
				// Get the term to validate it still exists
				$term = get_term_by('term_taxonomy_id', $default_category_tt_id, $tx_name);
				if ($term && !is_wp_error($term)) {
					$assign_taxes[] = $default_category_tt_id;
				}
			}
		}
	}

	return $assign_taxes;
}