<?php

namespace wpai_woocommerce_add_on\importer\products;

/**
 * Import Variation Product.
 *
 * Class ImportVariationProduct
 * @package wpai_woocommerce_add_on\importer
 */
class ImportVariationProduct extends ImportVariationBase {

    /**
     * Set variation properties.
     *
     * @return mixed
     */
    public function setProperties() {
        // Set variation description.
        $this->setProperty('description', $this->getValue('product_variation_description'));
        // Is variation enabled.
        if ($this->getImportService()->isUpdateDataAllowed('is_update_status', $this->isNewProduct())) {
            // For existing items imports where variations are imported as simple products,
            // check if WP All Import core has already set a specific status that differs from product_enabled default
            $current_post_status = get_post_status($this->product->get_id());
            $product_enabled_status = $this->getValue('product_enabled') == 'yes' ? 'publish' : 'private';

            // If this is an existing items import and the current post status is 'private'
            // but product_enabled would set it to 'publish', respect the current status
            if ($this->getImport()->options['wizard_type'] == 'matching' &&
                $current_post_status == 'private' &&
                $product_enabled_status == 'publish') {
                $post_status = 'private';
            } else {
                // Use the traditional product_enabled logic
                $post_status = $product_enabled_status;
            }

            $this->product->set_status($post_status);
        }
        if ($this->getImportService()->isUpdateDataAllowed('is_update_attributes', $this->isNewProduct())) {
            // Force updating variation attributes.
            $attributes = $this->product->get_attributes();
            if (!empty($attributes)) {
                foreach ($attributes as $attribute_name => $attribute_value) {
                    if ($this->getImportService()->isUpdateAttribute($attribute_name, $this->isNewProduct())) {
                        unset($attributes[$attribute_name]);
                    }
                }
            }
            $this->product->set_attributes($attributes);
        }
        // Set variation basic properties.
        parent::setProperties();
    }
}