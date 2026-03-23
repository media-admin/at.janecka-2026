<?php

use wpai_woocommerce_add_on\XmlImportWooCommerceService;

/**
 * @param $pid
 */
function pmwi_pmxi_missing_post($pid) {
    if ('product_variation' == get_post_type($pid)) {
        $variation = new WC_Product_Variation($pid);

	    // If the parent product no longer exists then we do nothing.
	    // get_post would normally be used, but this is already tested so we'll go with it.
	    if(empty(get_post_status($variation->get_parent_id()))){

			// Remove these orphaned variations from WP All Import's table so they aren't processed again.
		    if (!empty($pid) && is_numeric($pid)) {
			    $post    = new PMXI_Post_Record();
			    $is_post = ! $post->getBy( 'post_id', $pid )->isEmpty();
			    if ( $is_post ) {
				    $post->delete();
			    }
		    }

		    return;
	    }

	    // Variations should be set to 'private' instead of 'draft'
	    // when using 'Instead of deletion, change post status to Draft'.
        $options = XmlImportWooCommerceService::getInstance()->getImport()->options;
	    if ($options['is_send_removed_to_trash'] || $options['is_change_post_status_of_removed'] && $options['status_of_removed'] != 'publish') {
		    $variation->set_status('private');
	    }
        $attributes = $variation->get_attributes();
        if (!empty($attributes)) {
            $parentAttributes = get_post_meta($variation->get_parent_id(), '_product_attributes', true);
            foreach ($attributes as $attributeName => $attribute_value) {
                if (isset($parentAttributes[$attributeName])) {
                    $values = explode("|", $parentAttributes[$attributeName]['value']);
                    if ($parentAttributes[$attributeName]['is_taxonomy']) {
                        $attribute = get_term_by("slug", $attribute_value, $attributeName);
                        if ($attribute && !is_wp_error($attribute)) {
                            $values[] = $attribute->term_taxonomy_id;
                        }
                    } else {
                        $values[] = $attribute_value;
                    }
                    $values = array_unique($values);
                    $parentAttributes[$attributeName]['value'] = implode("|", $values);
                }
            }
            update_post_meta($variation->get_parent_id(), '_product_attributes', $parentAttributes);
        }
        $variation->save();
        // Add parent product to sync circle after import completed.
        $productStack = get_option('wp_all_import_product_stack_' . XmlImportWooCommerceService::getInstance()->getImport()->id, array());
        if (!in_array($variation->get_parent_id(), $productStack)) {
            $productStack[] = $variation->get_parent_id();
            update_option('wp_all_import_product_stack_' . XmlImportWooCommerceService::getInstance()->getImport()->id, $productStack);
        }
    }
}
