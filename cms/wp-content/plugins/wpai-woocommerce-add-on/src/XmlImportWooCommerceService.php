<?php

namespace wpai_woocommerce_add_on;

use wpai_woocommerce_add_on\services\XmlImportWooPriceService;
use wpai_woocommerce_add_on\services\XmlImportWooTaxonomyService;

/**
 * Class XmlImportWooCommerceService
 */
final class XmlImportWooCommerceService {

    /**
     * Singletone instance
     * @var XmlImportWooCommerceService
     */
    protected static $instance;

    /**
     *  Product custom field name to keep information about is product
     *  was created or updated by import.
     *
     */
    const FLAG_IS_NEW_PRODUCT = '__is_newly_created_product';

    /**
     *  Store all originally parsed data in product meta.
     */
    const PARSED_DATA_KEY = '__originally_parsed_data';

    /**
     *  Store ID of variation created from parent row in product meta.
     */
    const FIRST_VARIATION = '__first_variation_id';

	public static $custom_fields_handled_internally = [
		'product' => [
			'_regular_price' => ['is_update_regular_price','is_update_regular_price'],
			'_sale_price' => ['is_update_sale_price','is_update_sale_price'],
			'_sale_price_dates_from' => ['is_update_sale_price_dates_from','is_update_sale_price_dates_from'],
			'_sale_price_dates_to' => ['is_update_sale_price_dates_to','is_update_sale_price_dates_to'],
			'_price' => ['is_update_price','is_update_price'],
			'_virtual' => ['is_update_virtual','is_update_virtual'],
			'_downloadable' => ['is_update_downloadable','is_update_downloadable'],
			'_download_limit' => ['is_update_download_limit','is_update_download_limit'],
			'_download_expiry' => ['is_update_download_expiry','is_update_download_expiry'],
			'_downloadable_files' => ['is_update_downloadable_files','is_update_downloadable_files'],
			'_files' => ['is_update_downloadable_files', 'is_update_downloadable_files'],
			'_files_names' => ['is_update_downloadable_files', 'is_update_downloadable_files'],
			'_downloads' => ['is_update_downloadable_files', 'is_update_downloadable_files'],
			'_tax_status' => ['is_update_tax_status','is_update_tax_status'],
			'_tax_class' => ['is_update_tax_class','is_update_tax_class'],
			'_sku' => ['is_update_sku','is_update_sku'],
			'_global_unique_id' => ['is_update_global_unique_id','is_update_global_unique_id'],
			'_manage_stock' => ['is_update_manage_stock','is_update_manage_stock'],
			'_stock_status' => ['is_update_stock_status','is_update_stock_status'],
			'_stock' => ['is_update_stock_quantity','is_update_stock_quantity'],
			'_backorders' => ['is_update_backorders', 'is_update_backorders'],
			'_sold_individually' => ['is_update_sold_individually','is_update_sold_individually'],
			'_weight' => ['is_update_weight','is_update_weight'],
			'_length' => ['is_update_length','is_update_length'],
			'_width' => ['is_update_width','is_update_width'],
			'_height' => ['is_update_height','is_update_height'],
			'_shipping_class_id' => ['is_update_shipping_class','is_update_shipping_class'],
			'_shipping_class' => ['is_update_shipping_class','is_update_shipping_class'],
			'_upsell_ids' => ['is_update_up_sells','is_update_up_sells'],
			'_crosssell_ids' => ['is_update_cross_sells','is_update_cross_sells'],
			'_children' => ['is_update_grouping','is_update_grouping'],
			'_purchase_note' => ['is_update_purchase_note','is_update_purchase_note'],
			'_featured' => ['is_update_featured_status','is_update_featured_status'],
			'_catalog_visibility' => ['is_update_catalog_visibility','is_update_catalog_visibility'],
			'_product_attributes' => ['is_update_attributes','is_update_attributes'],
			'_product_image_gallery' => ['is_update_images','is_update_images'],
			
		]
	];

    /**
     * @var XmlImportWooTaxonomyService
     */
    public $taxonomiesService;

    /**
     * @var XmlImportWooPriceService
     */
    public $priceService;

    /**
     * @var \PMXI_Image_Record
     */
    public $import;

    /**
     * @var array
     */
    public $product_taxonomies;

    /**
     * Return singletone instance
     * @return XmlImportWooCommerceService
     */
    static public function getInstance() {
        if (self::$instance == NULL) {
            self::$instance = new self();
        }
        self::$instance->setImport();
        return self::$instance;
    }

    /**
     * XmlImportWooCommerceService constructor.
     */
    protected function __construct() {
        try {
            // Init current import instance.
            $this->setImport();
            $this->taxonomiesService = new XmlImportWooTaxonomyService($this->import);
            $this->priceService = new XmlImportWooPriceService($this->import);
            $taxonomies = array('post_format', 'product_type', 'product_shipping_class', 'product_visibility');
            $taxonomies = apply_filters('wp_all_import_variation_taxonomies', $taxonomies);
            $this->product_taxonomies = array_diff_key(get_taxonomies_by_object_type(array('product'), 'object'), array_flip($taxonomies));

			// Bypass the new field handling if the import configuration hasn't yet been updated to use it.
	        // In that case, retain the previous behavior until it is updated. 
			if(!($this->getImport()->options['is_using_new_product_import_options'] ?? 0) && $this->getImport()->options['custom_type'] == 'product'){
				error_log('Bypassing the new field handling for WooCommerce products.');
				self::$custom_fields_handled_internally['product'] = [];
			}

        } catch(\Exception $e) {
            self::getLogger() && call_user_func(self::getLogger(), '<b>ERROR:</b> ' . $e->getMessage());
        }
    }

    /**
     * Init import object form request data.
     */
    public function setImport() {
        // Init current import instance.
        $this->import = new \PMXI_Import_Record();
        $input = new \PMXI_Input();
        $importID = $input->get('id');
        if (empty($importID)) {
            $importID = $input->get('import_id');
        }
        if (empty($importID) && !empty(\PMXI_Plugin::$session)) {
            $importID = \PMXI_Plugin::$session->import_id;
        }
        if (empty($importID) && php_sapi_name() === 'cli') {
            global $argv;
            // First check for the ID set by the WP_CLI code.
            $temp_id = apply_filters('wp_all_import_cli_import_id', false);
            if($temp_id !== false && is_numeric($temp_id)){
                $importID = $temp_id;
            } else {
                // Try to get the ID from the CLI arguments if it's not found otherwise.
                $import_id_arr = array_filter( $argv, function ( $a ) {
                    return ( is_numeric( $a ) ) ? true : false;
                } );

                if ( ! empty( $import_id_arr ) ) {
                    $importID = reset( $import_id_arr );
                }
            }
        }
        if ($importID && ($this->import->isEmpty() || $this->import->id != $importID)) {
            $this->import->getById($importID);
        }
    }

    /**
     * @return XmlImportWooTaxonomyService
     */
    public function getTaxonomiesService() {
        return $this->taxonomiesService;
    }

    /**
     * @return XmlImportWooPriceService
     */
    public function getPriceService() {
        return $this->priceService;
    }

    /**
     * @return \PMXI_Image_Record
     */
    public function getImport() {
        return $this->import;
    }

    /**
     * @return array
     */
    public function getProductTaxonomies() {
        return $this->product_taxonomies;
    }

    /**
     * @param $productID
     *
     * @return mixed
     */
    public function getAllOriginallyParsedData($productID) {
        $data = get_post_meta($productID, self::PARSED_DATA_KEY, true);
        return $data;
    }

    /**
     * @param $productID
     * @param $key
     *
     * @return mixed
     */
    public function getOriginallyParsedData($productID, $key) {
        $data = $this->getAllOriginallyParsedData($productID);
        return isset($data[$key]) ? $data[$key] : NULL;
    }

    /**
     * Sync parent product prices & attributes with variations.
     *
     * @param $parentID
     */
    public function syncVariableProductData($parentID) {
        $product = new \WC_Product_Variable($parentID);

		do_action('wp_all_import_before_variable_product_import', $product->get_id());

        $variations = array();
        $variationIDs = $product->get_children();
        // Collect product variations.
        foreach ($variationIDs as $key => $variationID) {
            $variations[] = new \WC_Product_Variation($variationID);
        }
        $parentAttributes = get_post_meta($product->get_id(), '_product_attributes', TRUE);
        // Sync attribute terms with parent product.
        if (!empty($parentAttributes)) {
            $variation_attributes = [];
            foreach ($variations as $variation) {
                $attributes = $variation->get_attributes();
                if (!empty($attributes)) {
                    foreach ($attributes as $attribute_name => $attribute_value) {
                        if (!isset($variation_attributes[$attribute_name])) {
                            $variation_attributes[$attribute_name] = [];
                        }
                        if (!in_array($attribute_value, $variation_attributes[$attribute_name])) {
                            $variation_attributes[$attribute_name][] = $attribute_value;
                        }
                    }
                }
            }
            foreach ($parentAttributes as $name => $parentAttribute) {
                // Only in case if attribute marked to import as taxonomy terms.
                if ($parentAttribute['is_taxonomy']) {
                    $taxonomy_name = strpos($name, "%") !== FALSE ? urldecode($name) : $name;
                    $terms = [];
                    if (isset($variation_attributes[$name]) && is_array($variation_attributes[$name])) {
	                    $variation_attributes[$name] = array_filter($variation_attributes[$name], 'strlen');
                    }
                    if (!empty($variation_attributes[$name])) {
                        foreach ($variation_attributes[$name] as $attribute_term_slug) {
                            $term = get_term_by('slug', $attribute_term_slug, $taxonomy_name);
                            if ($term && !is_wp_error($term)) {
                                $terms[] = $term->term_taxonomy_id;
                            }
                        }
						if (!empty($parentAttribute['value'])) {
							$parent_terms = is_array($parentAttribute['value']) ? $parentAttribute['value'] : explode("|", $parentAttribute['value']);
							$parent_terms = array_filter($parent_terms);
							if (!empty($parent_terms)) {
								foreach ($parent_terms as $parent_term) {
									if ( ! in_array($parent_term, $terms) ) {
										$terms[] = $parent_term;
									}
								}
							}
						}
                    } else {
                        $terms = is_array($parentAttribute['value']) ? $parentAttribute['value'] : explode("|", $parentAttribute['value']);
                        $terms = array_filter($terms);
                    }
                    if (!empty($terms)) {
                        $this->getTaxonomiesService()->associateTerms($parentID, $terms, $taxonomy_name);
                    }
                }
            }
        }
        $isNewProduct = get_post_meta($product->get_id(), self::FLAG_IS_NEW_PRODUCT, true);
        // Make product simple it has less than minimum number of variations.
        $minimumVariations = apply_filters('wp_all_import_minimum_number_of_variations', 2, $product->get_id(), $this->getImport()->id);
        // Sync parent product with variation if at least one variation exist.
        if (!empty($variations)) {
            /** @var \WC_Product_Variable_Data_Store_CPT $data_store */
            if (!$this->getImport()->options['link_all_variations'] && (count($variationIDs) >= $minimumVariations || !$this->getImport()->options['make_simple_product'])) {
                $data_store = \WC_Data_Store::load( 'product-' . $product->get_type() );
                $data_store->sync_price( $product );
                $data_store->sync_stock_status( $product );
            }
            // Set product default attributes.
            if ($isNewProduct || $this->isUpdateCustomField('_default_attributes')) {
                $defaultAttributes = [];
                if ($this->getImport()->options['is_default_attributes']) {
                    $defaultVariation = FALSE;
                    // Set first variation as the default selection.
                    if ($this->getImport()->options['default_attributes_type'] == 'first') {
                        $defaultVariation = array_shift($variations);
                    }
                    // Set first in stock variation as the default selection.
                    if ($this->getImport()->options['default_attributes_type'] == 'instock') {
                        /** @var \WC_Product_Variation $variation */
                        foreach ($variations as $variation) {
                            if ($variation->get_stock_status() == 'instock') {
                                $defaultVariation = $variation;
                                break;
                            }
                        }
                    }
                    if ($defaultVariation) {
                        foreach ($defaultVariation->get_attributes() as $key => $value) {
                            if (!empty($value)) {
                                $defaultAttributes[$key] = $value;
                            } else {
                                // Variation can be applied to any value of this attribute.
                                if (isset($parentAttributes[$key])) {
                                    // Get first value from parent product.
                                    if ($parentAttributes[$key]['is_taxonomy']) {
                                        $terms = explode("|", $parentAttributes[$key]['value']);
                                        $terms = array_filter($terms);
                                        if (!empty($terms)) {
                                            $term = \WP_Term::get_instance($terms[0]);
                                            if ($term) {
                                                $defaultAttributes[$key] = $term->slug;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $default_attributes = $this->getOriginallyParsedData($product->get_id(), '_default_attributes');
                        if (!empty($default_attributes)) {
                            $defaultAttributes = maybe_unserialize($default_attributes);
                        }
                    }
                }
                $product->set_default_attributes($defaultAttributes);
            }
            $product->save();
        }
        // Sync custom fields for variation created from parent row.
        $firstVariationID = get_post_meta($product->get_id(), self::FIRST_VARIATION, TRUE);
        if ($firstVariationID && in_array($this->getImport()->options['matching_parent'], array('first_is_parent_id', 'first_is_variation')) ) {
            $parentMeta = get_post_meta($product->get_id(), '');
            if ("manual" !== $this->getImport()->options['duplicate_matching']) {
                foreach ($this->getImport()->options['custom_name'] as $customFieldName) {
                    if ($isNewProduct || $this->isUpdateCustomField($customFieldName)) {
                        update_post_meta($firstVariationID, $customFieldName, maybe_unserialize($parentMeta[$customFieldName][0]));
                    }
                }

				// Sync specific fields even if not configured in the import explicitly.
	            $specific_fields = ['_global_unique_id'];

				foreach ($specific_fields as $specific_field) {
					!empty(($parentMeta[$specific_field][0] ?? null)) && update_post_meta($firstVariationID, $specific_field, maybe_unserialize($parentMeta[$specific_field][0]));
				}
            }
            $sync_parent_acf_with_first_variation = apply_filters('wp_all_import_sync_parent_acf_with_first_variation', true);
            if ($sync_parent_acf_with_first_variation) {
	            // Sync all ACF fields.
	            foreach ($parentMeta as $parentMetaKey => $parentMetaValue) {
		            if (strpos($parentMetaValue[0], 'field_') === 0) {
			            update_post_meta($firstVariationID, $parentMetaKey, $parentMetaValue[0]);
			            $acfFieldKey = preg_replace("%^_(.*)%", "$1", $parentMetaKey);
			            foreach ($parentMeta as $key => $value) {
				            if (strpos($key, $acfFieldKey) === 0) {
					            update_post_meta($firstVariationID, $key, $value[0]);
				            }
			            }
		            }
	            }
            }
	        delete_post_meta($firstVariationID, '_variation_updated');
        }

        update_post_meta($product->get_id(), '_product_attributes', $parentAttributes);

        if (count($variationIDs) < $minimumVariations) {
            $this->maybeMakeProductSimple($product, $variationIDs);
        }
        if ($this->isUpdateDataAllowed('is_update_attributes', $isNewProduct)) {
            $this->recountAttributes($product);
        }
        do_action('wp_all_import_variable_product_imported', $product->get_id());
        // Delete originally parsed data, which was temporary stored in
        // product meta.
        delete_post_meta($product->get_id(), self::PARSED_DATA_KEY);
    }

    /**
     * Convert variable product into simple.
     *
     * @param $product \WC_Product_Variable
     * @param $variationIDs
     */
    public function maybeMakeProductSimple($product, $variationIDs) {
        $isNewProduct = get_post_meta($product->get_id(), self::FLAG_IS_NEW_PRODUCT, true);
        if (empty($isNewProduct)) {
	        $isNewProduct = FALSE;
        }
        if ($this->isUpdateDataAllowed('is_update_product_type', $isNewProduct) && $this->getImport()->options['make_simple_product']) {
            do_action('wp_all_import_before_make_product_simple', $product->get_id(), $this->getImport()->id);
            $product_type_term = is_exists_term('simple', 'product_type', 0);
            if (!empty($product_type_term) && !is_wp_error($product_type_term)) {
                $this->getTaxonomiesService()->associateTerms($product->get_id(), array( (int) $product_type_term['term_taxonomy_id'] ), 'product_type');
                $simpleProduct = new \WC_Product_Simple($product->get_id());
                $simpleProduct->save();
            }
        }
        // Sync prices after conversion to simple product or if product has less than 2 variations.
        $getPricesFromFirstVariation = apply_filters('wp_all_import_get_prices_from_first_variation', FALSE, $product->get_id(), $this->getImport()->id);
        $parsedData = $this->getAllOriginallyParsedData($product->get_id());
        if ($getPricesFromFirstVariation && !empty($variationIDs)) {
            $firstVariationID = get_post_meta($product->get_id(), self::FIRST_VARIATION, TRUE);
            $parsedData['regular_price'] = get_post_meta($firstVariationID, '_regular_price', TRUE);
            $parsedData['sale_price'] = get_post_meta($firstVariationID, '_sale_price', TRUE);
            $price = get_post_meta($firstVariationID, '_price', TRUE);
        }
        if (!empty($parsedData)) {
            if (empty($variationIDs) && $this->getImport()->options['make_simple_product']) {
                // Sync product data in case variations weren't created for this product.
                $simpleProduct = new \WC_Product_Simple($product->get_id());
                $simpleProduct->set_stock_status($parsedData['stock_status']);
	            if (isset($parsedData['downloadable']) && ($isNewProduct || $this->isUpdateCustomField('_downloadable'))) {
		            $simpleProduct->set_downloadable($parsedData['downloadable']);
	            }
	            if (isset($parsedData['virtual']) && ($isNewProduct || $this->isUpdateCustomField('_virtual'))) {
		            $simpleProduct->set_virtual($parsedData['virtual']);
	            }
                $simpleProduct->save();
            }
            if (empty($price)) {
                if (!$this->isUpdateCustomField('_sale_price')) {
                    $parsedData['sale_price'] = get_post_meta($product->get_id(), '_sale_price', TRUE);
                }
                $price = isset($parsedData['sale_price']) ? $parsedData['sale_price'] : '';
                if ($price == '' && isset($parsedData['regular_price'])) {
                    $price = $parsedData['regular_price'];
                }
            }
            if (isset($parsedData['regular_price'])) {
                XmlImportWooCommerceService::getInstance()->pushMeta($product->get_id(), '_regular_price', $parsedData['regular_price'], $isNewProduct);
            }
            if (isset($parsedData['sale_price'])) {
                XmlImportWooCommerceService::getInstance()->pushMeta($product->get_id(), '_sale_price', $parsedData['sale_price'], $isNewProduct);
            }
            XmlImportWooCommerceService::getInstance()->pushMeta($product->get_id(), '_price', $price, $isNewProduct);
            // Recover original SKU.
            if (isset($parsedData['original_sku']) && $this->getImport()->options['make_simple_product']) {
                XmlImportWooCommerceService::getInstance()->pushMeta($product->get_id(), '_sku', $parsedData['original_sku'], $isNewProduct);
            }
        }
        if ($this->isUpdateDataAllowed('is_update_product_type', $isNewProduct) && $this->getImport()->options['make_simple_product']) {
            try {
                $stock_quantity = get_post_meta($product->get_id(), '_stock', TRUE);
                $data_store = \WC_Data_Store::load( 'product' );
                $data_store->update_product_stock( $product->get_id(), $stock_quantity, 'set' );
            } catch(\Exception $e) {
                self::getLogger() && call_user_func(self::getLogger(), '<b>ERROR:</b> ' . $e->getMessage());
            }
            // Delete all variations.
            $children = get_posts(array(
                'post_parent' => $product->get_id(),
                'posts_per_page' => -1,
                'post_type' => 'product_variation',
                'fields' => 'ids',
                'post_status' => 'any'
            ));
            if (!empty($children)) {
                foreach ($children as $child) {
                    wp_delete_post($child, TRUE);
                }
            }
            do_action('wp_all_import_make_product_simple', $product->get_id(), $this->getImport()->id);
        }
    }

    /**
     * Get All orders IDs for a given product ID.
     *
     * @param  integer  $product_id (required)
     * @return array
     */
    public function getOrdersIdsByProductId( $product_id ){
        global $wpdb;
        $results = $wpdb->get_col("
            SELECT order_items.order_id
            FROM {$wpdb->prefix}woocommerce_order_items as order_items
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
            LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
            WHERE posts.post_type = 'shop_order'            
            AND order_items.order_item_type = 'line_item'
            AND order_item_meta.meta_key = '_product_id'
            AND order_item_meta.meta_value = '$product_id'
        ");
        return $results;
    }

    /**
     * Re-count product attributes.
     *
     * @param \WC_Product $product
     */
    public function recountAttributes(\WC_Product $product) {
        $attributes = $product->get_attributes();
        /** @var \WC_Product_Attribute $attribute */
        foreach ($attributes as $attributeName => $attribute) {
            if ( ! empty( $attribute ) ) {
                if ($attribute->is_taxonomy()) {
                    $attribute_values = $attribute->get_terms();
                    if (!empty($attribute_values)) {
	                    $terms = [];
	                    foreach ($attribute_values as $key => $object) {
	                        $terms[] = $object->term_id;
	                    }
	                    wp_update_term_count_now($terms, $attributeName);
                    }
                }
            }
        }
    }

    /**
     * @param string $option
     * @param bool $isNewProduct
     * @return bool
     */
	public function isUpdateDataAllowed($option = '', $isNewProduct = TRUE) {
		// Allow update data for newly created products.
		if ($isNewProduct) {
			return TRUE;
		}
		// `Update existing posts with changed data in your file` option disabled.
		if ($this->getImport()->options['is_keep_former_posts'] == 'yes') {
			return FALSE;
		}
		// `Update all data` option enabled
		if ($this->getImport()->options['update_all_data'] == 'yes') {
			return TRUE;
		}

		// For our fields that are handled internally we must check that both the section
		// and the specific field are allowed to update
		$post_type = $this->getImport()->options['custom_type'];
		if (isset(self::$custom_fields_handled_internally[$post_type])) {
			foreach (self::$custom_fields_handled_internally[$post_type] as $meta_key => $field_options) {
				if ($field_options[0] === $option) {
					return !empty($this->getImport()->options[$option]) && !empty($this->getImport()->options[$field_options[1]]);
				}
			}
		}

		return empty($this->getImport()->options[$option]) ? FALSE : TRUE;
	}

    /**
     * @param $tx_name
     * @param bool $isNewProduct
     * @return bool
     */
    public function isUpdateTaxonomy($tx_name, $isNewProduct = TRUE) {

        if (!$isNewProduct) {
            if ($this->getImport()->options['update_all_data'] == 'yes'){
                return TRUE;
            }
            if ( ! $this->getImport()->options['is_update_categories'] ) {
                return FALSE;
            }
            if ($this->getImport()->options['update_all_data'] == "no" && $this->getImport()->options['update_categories_logic'] == "all_except" && !empty($this->getImport()->options['taxonomies_list'])
                && is_array($this->getImport()->options['taxonomies_list']) && in_array($tx_name, $this->getImport()->options['taxonomies_list'])) {
                return FALSE;
            }
            if ($this->getImport()->options['update_all_data'] == "no" && $this->getImport()->options['update_categories_logic'] == "only" && ((!empty($this->getImport()->options['taxonomies_list'])
				&& is_array($this->getImport()->options['taxonomies_list']) && ! in_array($tx_name, $this->getImport()->options['taxonomies_list'])) || empty($this->getImport()->options['taxonomies_list']))) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * @param $attributeName
     * @param bool $isNewProduct
     *
     * @return bool
     */
    public function isUpdateAttribute($attributeName, $isNewProduct = TRUE) {
        $is_update_attributes = TRUE;
        // Update only these Attributes, leave the rest alone.
        if ( ! $isNewProduct && $this->getImport()->options['update_all_data'] == "no" && $this->getImport()->options['is_update_attributes'] && $this->getImport()->options['update_attributes_logic'] == 'only') {
            if ( ! empty($this->getImport()->options['attributes_list']) && is_array($this->getImport()->options['attributes_list'])) {
                if ( ! in_array( $attributeName , array_filter($this->getImport()->options['attributes_list'], 'trim'))) {
                    $is_update_attributes = FALSE;
                }
            }
        }
        // Leave these attributes alone, update all other Attributes.
        if ( ! $isNewProduct && $this->getImport()->options['update_all_data'] == "no" && $this->getImport()->options['is_update_attributes'] && $this->getImport()->options['update_attributes_logic'] == 'all_except') {
            if ( ! empty($this->getImport()->options['attributes_list']) && is_array($this->getImport()->options['attributes_list'])) {
                if ( in_array( $attributeName , array_filter($this->getImport()->options['attributes_list'], 'trim'))) {
                    $is_update_attributes = FALSE;
                }
            }
        }
        return $is_update_attributes;
    }

    /**
     * @param $meta_key
     * @return bool
     */
    public function isUpdateCustomField($meta_key) {
	    $options = $this->getImport()->options;
		
        if ($options['update_all_data'] == 'yes') {
            return TRUE;
        }

	    $post_type = $options['custom_type'];
	    if(isset(self::$custom_fields_handled_internally[$post_type]) && array_key_exists($meta_key, self::$custom_fields_handled_internally[$post_type])) {
		    $internal_field = self::$custom_fields_handled_internally[$post_type][$meta_key];
		    if(isset($options[$internal_field[0]]) && $options[$internal_field[0]] && isset($options[$internal_field[1]]) && $options[$internal_field[1]]) {
			    return true;
		    }else{
			    return false;
		    }
	    }
		
        if (!$options['is_update_custom_fields']) {
            return FALSE;
        }
        if ($options['update_custom_fields_logic'] == "full_update") {
            return TRUE;
        }
        if ($options['update_custom_fields_logic'] == "only"
            && !empty($options['custom_fields_list'])
            && is_array($options['custom_fields_list'])
            && in_array($meta_key, $options['custom_fields_list'])
        ) {
            return TRUE;
        }
        if ($options['update_custom_fields_logic'] == "all_except"
            && (empty($options['custom_fields_list']) || !in_array($meta_key, $options['custom_fields_list']))
        ) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param $pid
     * @param $meta_key
     * @param $meta_value
     * @param bool $isNewPost
     * @return mixed
     */
    public function pushMeta($pid, $meta_key, $meta_value, $isNewPost = TRUE) {
        if (!empty($meta_key) && ($isNewPost || $this->isUpdateCustomField($meta_key))) {
            update_post_meta($pid, $meta_key, $meta_value);
        } elseif (in_array($meta_key, ['_product_image_gallery']) && $this->isUpdateDataAllowed('is_update_images', $isNewPost)) {
	        // Update gallery custom field if images is set to be updated.
	        update_post_meta($pid, $meta_key, $meta_value);
        }
    }

    /**
     * Find existing product by SKU, ID or title.
     *
     * @param $identifier
     * @return bool|\WC_Product
     */
    public static function getProductByIdentifier($identifier) {
        $product_id = wc_get_product_id_by_sku($identifier);
        if ( empty($product_id) ) {
            $result = wp_all_import_get_page_by_title($identifier, ['product', 'product_variation']);
            if ( $result && !is_wp_error($result) ) {
                $product_id = $result->ID;
            }
            if ( empty($product_id) && is_numeric($identifier) ) {
                $product_id = (int) $identifier;
            }
        }
        $product = FALSE;
        if ( ! empty($product_id) ) {
            $product = WC()->product_factory->get_product($product_id);
        }
        return $product;
    }

    /**
     * @param $input
     * @return array
     */
    public static function arrayCartesian($input) {
        $result = array();
        foreach ($input as $key => $values) {
	        // If a sub-array is empty, it doesn't affect the cartesian product
	        if ( empty( $values ) ) {
		        continue;
	        }
	        // Special case: seeding the product array with the values from the first sub-array
	        if ( empty( $result ) ) {
		        foreach ( $values as $value ) {
			        $result[] = array( $key => $value );
		        }
	        } else {
		        // Second and subsequent input sub-arrays work like this:
		        //   1. In each existing array inside $product, add an item with
		        //      key == $key and value == first item in input sub-array
		        //   2. Then, for each remaining item in current input sub-array,
		        //      add a copy of each existing array inside $product with
		        //      key == $key and value == first item in current input sub-array

		        // Store all items to be added to $product here; adding them on the spot
		        // inside the foreach will result in an infinite loop
		        $append = array();
		        foreach( $result as &$product ) {
			        // Do step 1 above. array_shift is not the most efficient, but it
			        // allows us to iterate over the rest of the items with a simple
			        // foreach, making the code short and familiar.
			        $product[ $key ] = array_shift( $values );
			        // $product is by reference (that's why the key we added above
			        // will appear in the end result), so make a copy of it here
			        $copy = $product;
			        // Do step 2 above.
			        foreach( $values as $item ) {
				        $copy[ $key ] = $item;
				        $append[] = $copy;
			        }
			        // Undo the side effecst of array_shift
			        array_unshift( $values, $product[ $key ] );
		        }
		        // Out of the foreach, we can add to $results now
		        $result = array_merge( $result, $append );
	        }
        }
        return $result;
    }

    /**
     * @return bool|\Closure
     */
    public static function getLogger() {
        $logger = FALSE;
        if (\PMXI_Plugin::is_ajax()) {
            $logger = function($m) {echo "<div class='progress-msg'>[". date("H:i:s") ."] ".wp_all_import_filter_html_kses($m)."</div>\n";flush();};
        }
        return $logger;
    }
}
