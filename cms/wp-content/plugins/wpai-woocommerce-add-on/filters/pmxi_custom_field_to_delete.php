<?php

/**
 *
 * Check if custom field needs to be deleted.
 *
 * @param $field_to_delete
 * @param $pid
 * @param $post_type
 * @param $options
 * @param $cur_meta_key
 *
 * @return bool
 */
function pmwi_pmxi_custom_field_to_delete($field_to_delete, $pid, $post_type, $options, $cur_meta_key){

	if ($field_to_delete === false || $post_type != "product") {
        return $field_to_delete;
    }

	if (in_array($cur_meta_key, ['total_sales', '_stock_status'])) {
        return false;
    }

    // Check if the field is handled internally and should not be deleted unless marked for update
    if (class_exists('wpai_woocommerce_add_on\XmlImportWooCommerceService') && $options['update_all_data'] == 'no' && !empty($options['is_using_new_product_import_options'])) {
        $custom_fields_handled_internally = wpai_woocommerce_add_on\XmlImportWooCommerceService::$custom_fields_handled_internally;
        if(isset($custom_fields_handled_internally[$post_type]) && array_key_exists($cur_meta_key, $custom_fields_handled_internally[$post_type])) {
            $internal_field = $custom_fields_handled_internally[$post_type][$cur_meta_key];

            // Special handling for _product_attributes when using specific attribute logic
            if ($cur_meta_key == '_product_attributes' && isset($options['update_attributes_logic']) && in_array($options['update_attributes_logic'], ['all_except', 'only'])) {
                // For "all_except" and "only" modes, we need to let the existing logic below handle this
                // Don't return early here, let it fall through to the existing attribute logic
            } else {
                if(isset($options[$internal_field[0]]) && $options[$internal_field[0]] && isset($options[$internal_field[1]]) && $options[$internal_field[1]]) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

	if ($cur_meta_key == '_is_first_variation_created') {
		delete_post_meta($pid, $cur_meta_key);
		return false;
	}

	// Do not update attributes.
	if ($options['update_all_data'] == 'no' && !$options['is_update_attributes'] && (in_array($cur_meta_key, array('_default_attributes', '_product_attributes')) || strpos($cur_meta_key, "attribute_") === 0)) {
        return false;
    }
    // Don't touch existing, only add new attributes.
    if ($options['update_all_data'] == 'no' && $options['is_update_attributes'] && $options['update_attributes_logic'] == 'add_new') {
        if (in_array($cur_meta_key, array('_default_attributes', '_product_attributes')) || strpos($cur_meta_key, "attribute_") === 0 ) {
            return false;
        }
    }

	// Update only these Attributes, leave the rest alone.
	if ($options['update_all_data'] == 'no' && $options['is_update_attributes'] && $options['update_attributes_logic'] == 'only') {
		if ($cur_meta_key == '_product_attributes') {
			$current_product_attributes = get_post_meta($pid, '_product_attributes', true);
			if ( ! empty($current_product_attributes) && ! empty($options['attributes_list']) && is_array($options['attributes_list'])) {
                foreach ($current_product_attributes as $attr_name => $attr_value) {
                    if ( in_array($attr_name, array_filter($options['attributes_list'], 'trim'))) unset($current_product_attributes[$attr_name]);
                }
            }
			update_post_meta($pid, '_product_attributes', $current_product_attributes);
			return false;
		}
		if ( strpos($cur_meta_key, "attribute_") === 0 && !empty($options['attributes_list']) && is_array($options['attributes_list']) && !in_array(str_replace("attribute_", "", $cur_meta_key), array_filter($options['attributes_list'], 'trim'))) {
            return false;
        }
		if (in_array($cur_meta_key, array('_default_attributes'))) {
            return false;
        }
	}

	// Leave these attributes alone, update all other Attributes.
	if ($options['update_all_data'] == 'no' && $options['is_update_attributes'] && $options['update_attributes_logic'] == 'all_except') {
		if ($cur_meta_key == '_product_attributes') {
			if (empty($options['attributes_list'])) {
			    delete_post_meta($pid, $cur_meta_key); return false;
			}
			$current_product_attributes = get_post_meta($pid, '_product_attributes', true);
			if ( ! empty($current_product_attributes) && ! empty($options['attributes_list']) && is_array($options['attributes_list'])) {
                foreach ($current_product_attributes as $attr_name => $attr_value) {
                    if ( ! in_array($attr_name, array_filter($options['attributes_list'], 'trim'))) unset($current_product_attributes[$attr_name]);
                }
            }
			update_post_meta($pid, '_product_attributes', $current_product_attributes);
			return false;
		}
		if ( strpos($cur_meta_key, "attribute_") === 0 && !empty($options['attributes_list']) && is_array($options['attributes_list']) && in_array(str_replace("attribute_", "", $cur_meta_key), array_filter($options['attributes_list'], 'trim'))) {
            return false;
        }
		if (in_array($cur_meta_key, array('_default_attributes'))) {
            return false;
        }
	}

	return true;		
}
