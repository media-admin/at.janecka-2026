<?php 
// Check if $isWizard is defined
$isWizard = isset($isWizard) ? $isWizard : false;

// Initialize $hidden_data_to_update_options if not set
$hidden_data_to_update_options = isset($hidden_data_to_update_options) ? $hidden_data_to_update_options : array();

// If not using new product import options, set is_update fields in the custom fields handled internally list to 0
// unless they're in the custom_fields_list and is_update_custom_fields is true
if (empty($post['is_using_new_product_import_options']) && class_exists('wpai_woocommerce_add_on\XmlImportWooCommerceService')) {
    $internal_fields = wpai_woocommerce_add_on\XmlImportWooCommerceService::$custom_fields_handled_internally['product'] ?? [];

    // Only process if we're not updating all custom fields
    if (empty($post['is_update_custom_fields']) || $post['update_custom_fields_logic'] !== 'full_update') {
        foreach ($internal_fields as $meta_key => $field_options) {
            $should_update = false;

            // Check if we should update this field based on custom fields settings
            if (!empty($post['is_update_custom_fields'])) {
                if ($post['update_custom_fields_logic'] === 'only' && 
                    !empty($post['custom_fields_list']) && 
                    in_array($meta_key, $post['custom_fields_list'])) {
                    $should_update = true;
                } elseif ($post['update_custom_fields_logic'] === 'all_except' && 
                         (empty($post['custom_fields_list']) || !in_array($meta_key, $post['custom_fields_list']))) {
                    $should_update = true;
                }
            }

            // Set to 0 only if we shouldn't update this field
            if (!$should_update) {
                $post[$field_options[0]] = 0;
            }
        }
    }
}

if ( ! $isWizard  or ! empty(PMXI_Plugin::$session->deligate) and PMXI_Plugin::$session->deligate == 'wpallexport' or $isWizard and "new" != $post['wizard_type']): ?>
    <h4><?php _e('When WP All Import finds new or changed data...', 'wpai_woocommerce_addon_plugin'); ?></h4>
<?php else: ?>
    <h4><?php _e('If this import is run again and WP All Import finds new or changed data...', 'wpai_woocommerce_addon_plugin'); ?></h4>
<?php endif; ?>
<?php

$post_type = $post['custom_type'];
if (!empty($post['custom_type'])){
	$custom_type = get_post_type_object( $post['custom_type'] );
	switch($post['custom_type']) {
		case 'product':
			$cpt_name = 'WooCommerce products';
			break;
		case 'shop_coupon':
			$cpt_name = 'WooCommerce coupons';
			break;
		case 'shop_order':
			$cpt_name = 'WooCommerce orders';
			break;
		default:
			if (in_array($post['custom_type'], ['post', 'page'])) {
				$cpt_name = 'WordPress ' . strtolower($custom_type->label);
			} else {
				$cpt_name = ( ! empty($custom_type)) ? strtolower($custom_type->label) : '';
			}
			break;
	}
}
else{
	$cpt_name = '';
}

?>
<div class="input">
    <input type="hidden" name="create_new_records" value="0" />
    <input type="checkbox" id="create_new_records" name="create_new_records" value="1" <?php echo $post['create_new_records'] ? 'checked="checked"' : '' ?> />
    <label for="create_new_records"><?php printf(__('Create new %s from records newly present in your file', 'wpai_woocommerce_addon_plugin'), $cpt_name); ?></label>
	<?php if ( ! empty(PMXI_Plugin::$session->deligate) and PMXI_Plugin::$session->deligate == 'wpallexport' ): ?>
        <a href="#help" class="wpallimport-help" title="<?php printf(__('New %s will only be created when ID column is present and value in ID column is unique.', 'wpai_woocommerce_addon_plugin'), $cpt_name) ?>" style="top: -1px;">?</a>
	<?php endif; ?>
</div>
<div class="input">
    <input type="hidden" id="is_using_new_product_import_options" name="is_using_new_product_import_options" value="1" />
    <input type="hidden" id="is_keep_former_posts" name="is_keep_former_posts" value="yes" />
    <input type="checkbox" id="is_not_keep_former_posts" name="is_keep_former_posts" value="no" <?php echo "yes" != $post['is_keep_former_posts'] ? 'checked="checked"': '' ?> class="switcher" />
    <label for="is_not_keep_former_posts"><?php printf(__('Update existing %s with changed data in your file', 'wpai_woocommerce_addon_plugin'), $cpt_name); ?></label>
	<?php if ( $isWizard and "new" == $post['wizard_type'] and empty(PMXI_Plugin::$session->deligate)): ?>
        <a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php _e('These options will only be used if you run this import again later. All data is imported the first time you run an import.', 'wpai_woocommerce_addon_plugin') ?>">?</a>
	<?php endif; ?>
    <div class="switcher-target-is_not_keep_former_posts" style="padding-left:20px;">

        <div class="input" style="margin-left: 4px;">
            <input type="hidden" name="is_selective_hashing" value="0" />
            <input type="checkbox" id="is_selective_hashing" name="is_selective_hashing" value="1" <?php echo $post['is_selective_hashing'] ? 'checked="checked"': '' ?> />
            <label for="is_selective_hashing"><?php printf(__('Skip %s if their data in your file has not changed', 'wpai_woocommerce_addon_plugin'), $cpt_name); ?></label>
            <a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php _e('When enabled, WP All Import will keep track of every post\'s data as it is imported. When the import is run again, posts will be skipped if their data in the import file has not changed since the last run.<br/><br/>Posts will not be skipped if the import template or settings change, or if you make changes to the custom code in the Function Editor.', 'wpai_woocommerce_addon_plugin') ?>">?</a>
        </div>

        <input type="radio" id="update_all_data" class="switcher" name="update_all_data" value="yes" <?php echo 'no' != $post['update_all_data'] ? 'checked="checked"': '' ?>/>
        <label for="update_all_data"><?php _e('Update all data', 'wpai_woocommerce_addon_plugin' )?></label><br>

        <input type="radio" id="update_choosen_data" class="switcher" name="update_all_data" value="no" <?php echo 'no' == $post['update_all_data'] ? 'checked="checked"': '' ?>/>
        <label for="update_choosen_data"><?php _e('Choose which data to update', 'wpai_woocommerce_addon_plugin' )?></label><br>
        <div class="switcher-target-update_choosen_data"  style="padding-left:28px;">
            <div class="input">
                <input type="checkbox" id="wpallimport-select-all-checkbox" />
                <label for="wpallimport-select-all-checkbox" class="wpallimport-trigger-options wpallimport-select-all" rel="<?php _e("Unselect All", 'wpai_woocommerce_addon_plugin'); ?>" style="font-weight: normal; font-size: 14px;"><?php _e("Select All", 'wpai_woocommerce_addon_plugin'); ?></label>
            </div>

            <!-- General Product Data Section -->
            <h4 class="woocommerce-import-section-heading"><?php _e('General Product Data', 'wpai_woocommerce_addon_plugin') ?></h4>
            <div class="woocommerce-import-section">
                <div class="input">
                    <input type="hidden" name="is_update_title" value="0" />
                    <input type="checkbox" id="is_update_title" name="is_update_title" value="1" <?php echo $post['is_update_title'] ? 'checked="checked"': '' ?> aria-describedby="desc-is_update_title" />
                    <label for="is_update_title">
                        <?php _e('Product Name', 'wpai_woocommerce_addon_plugin') ?>
                    </label>
                </div>
                <div class="input">
                    <input type="hidden" name="is_update_content" value="0" />
                    <input type="checkbox" id="is_update_content" name="is_update_content" value="1" <?php echo $post['is_update_content'] ? 'checked="checked"': '' ?> aria-describedby="desc-is_update_content" />
                    <label for="is_update_content">
                        <?php _e('Description', 'wpai_woocommerce_addon_plugin') ?>
                    </label>
                </div>
                <div class="input">
                    <input type="hidden" name="is_update_excerpt" value="0" />
                    <input type="checkbox" id="is_update_excerpt" name="is_update_excerpt" value="1" <?php echo $post['is_update_excerpt'] ? 'checked="checked"': '' ?> aria-describedby="desc-is_update_excerpt" />
                    <label for="is_update_excerpt">
                        <?php _e('Short Description', 'wpai_woocommerce_addon_plugin') ?>
                    </label>
                </div>
                <div class="input">
                    <input type="hidden" name="is_update_images" value="0" />
                    <input type="checkbox" id="is_update_images" name="is_update_images" value="1" <?php echo $post['is_update_images'] ? 'checked="checked"': '' ?> class="switcher" />
                    <label for="is_update_images">
			            <?php _e('Images', 'wpai_woocommerce_addon_plugin') ?>
                    </label>
                    <div class="switcher-target-is_update_images" style="padding-left:17px;">
                        <div class="input" style="">
                            <input type="radio" id="update_images_logic_full_update" name="update_images_logic" value="full_update" <?php echo ( "full_update" == $post['update_images_logic'] ) ? 'checked="checked"': '' ?> />
                            <label for="update_images_logic_full_update"><?php _e('Update all images', 'wpai_woocommerce_addon_plugin') ?></label>
                        </div>
			            <?php $is_show_add_new_images = apply_filters('wp_all_import_is_show_add_new_images', true, $post_type); ?>
			            <?php if ($is_show_add_new_images): ?>
                            <div class="input" style="">
                                <input type="radio" id="update_images_logic_add_new" name="update_images_logic" value="add_new" <?php echo ( "add_new" == $post['update_images_logic'] ) ? 'checked="checked"': '' ?> />
                                <label for="update_images_logic_add_new"><?php _e('Don\'t touch existing images, append new images', 'wpai_woocommerce_addon_plugin') ?></label>
                            </div>
			            <?php endif; ?>
                    </div>
                </div>
                <div class="input">
                    <input type="hidden" name="is_update_status" value="0" />
                    <input type="checkbox" id="is_update_status" name="is_update_status" value="1" <?php echo $post['is_update_status'] ? 'checked="checked"': '' ?> />
                    <label for="is_update_status">
                        <?php _e('Product Status', 'wpai_woocommerce_addon_plugin') ?>
                    </label>
                    <a href="#help" class="wpallimport-help" title="<?php _e('Controls the publication status of the product. If you\'re seeing products removed from trash and republished then you likely need to uncheck this option.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                </div>
                <div class="input">
                    <input type="hidden" name="is_update_attributes" value="0" />
                    <input type="checkbox" id="is_update_attributes<?php echo $post_type; ?>" name="is_update_attributes" value="1" <?php echo $post['is_update_attributes'] ? 'checked="checked"': '' ?>  class="switcher"/>
                    <label for="is_update_attributes<?php echo $post_type; ?>">
                        <?php _e('Product Attributes', 'wpai_woocommerce_addon_plugin') ?>
                    </label>
                    <a href="#help" class="wpallimport-help" title="<?php _e('Product attributes are pieces of data that can add more technical information to your product and help you filter products in your store. They can be used for creating variable products and providing additional product specifications.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                    <div class="switcher-target-is_update_attributes<?php echo $post_type; ?>" style="padding-left:17px;">
                        <div class="input">
                            <input type="radio" id="update_attributes_logic_full_update<?php echo $post_type; ?>" name="update_attributes_logic" value="full_update" <?php echo ( "full_update" == $post['update_attributes_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
                            <label for="update_attributes_logic_full_update<?php echo $post_type; ?>"><?php _e('Update all Attributes', 'wpai_woocommerce_addon_plugin') ?></label>
                        </div>
                        <div class="input">
                            <input type="radio" id="update_attributes_logic_only<?php echo $post_type; ?>" name="update_attributes_logic" value="only" <?php echo ( "only" == $post['update_attributes_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
                            <label for="update_attributes_logic_only<?php echo $post_type; ?>"><?php _e('Update only these Attributes, leave the rest alone', 'wpai_woocommerce_addon_plugin') ?></label>
                            <div class="switcher-target-update_attributes_logic_only<?php echo $post_type; ?> pmxi_choosen" style="padding-left:17px;">
                                <span class="hidden choosen_values"><?php if (!empty($all_existing_attributes)) echo implode(',', $all_existing_attributes);?></span>
                                <input class="choosen_input" value="<?php if (!empty($post['attributes_list']) and "only" == $post['update_attributes_logic']) echo implode(',', $post['attributes_list']); ?>" type="hidden" name="attributes_only_list"/>
                            </div>
                        </div>
                        <div class="input">
                            <input type="radio" id="update_attributes_logic_all_except<?php echo $post_type; ?>" name="update_attributes_logic" value="all_except" <?php echo ( "all_except" == $post['update_attributes_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
                            <label for="update_attributes_logic_all_except<?php echo $post_type; ?>"><?php _e('Leave these Attributes alone, update all other Attributes', 'wpai_woocommerce_addon_plugin') ?></label>
                            <div class="switcher-target-update_attributes_logic_all_except<?php echo $post_type; ?> pmxi_choosen" style="padding-left:17px;">
                                <span class="hidden choosen_values"><?php if (!empty($all_existing_attributes)) echo implode(',', $all_existing_attributes);?></span>
                                <input class="choosen_input" value="<?php if (!empty($post['attributes_list']) and "all_except" == $post['update_attributes_logic']) echo implode(',', $post['attributes_list']); ?>" type="hidden" name="attributes_except_list"/>
                            </div>
                        </div>
                        <div class="input">
                            <input type="radio" id="update_attributes_logic_add_new<?php echo $post_type; ?>" name="update_attributes_logic" value="add_new" <?php echo ( "add_new" == $post['update_attributes_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
                            <label for="update_attributes_logic_add_new<?php echo $post_type; ?>"><?php _e('Don\'t touch existing Attributes, add new Attributes', 'wpai_woocommerce_addon_plugin') ?></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Price & Stock Section -->
            <h4 class="woocommerce-import-section-heading"><?php _e('Price & Stock', 'wpai_woocommerce_addon_plugin') ?></h4>
            <div class="woocommerce-import-section">
                <div class="input">
                    <input type="hidden" name="is_update_regular_price" value="0" />
                    <input type="checkbox" id="is_update_regular_price<?php echo $post_type; ?>" name="is_update_regular_price" value="1" <?php echo $post['is_update_regular_price'] ? 'checked="checked"': '' ?>  class="switcher"/>
                    <label for="is_update_regular_price<?php echo $post_type; ?>">
                        <?php _e('Regular Price', 'wpai_woocommerce_addon_plugin') ?>
                    </label>
                </div>
                <div class="input">
                    <input type="hidden" name="is_update_sale_price" value="0" />
                    <input type="checkbox" id="is_update_sale_price<?php echo $post_type; ?>" name="is_update_sale_price" value="1" <?php echo $post['is_update_sale_price'] ? 'checked="checked"': '' ?>  class="switcher"/>
                    <label for="is_update_sale_price<?php echo $post_type; ?>">
                        <?php _e('Sale Price', 'wpai_woocommerce_addon_plugin') ?>
                    </label>
                    <div style="padding-left:17px;">
                        <div class="input sub-input">
                            <input type="hidden" name="is_update_sale_price_dates_from" value="0" />
                            <input type="checkbox" id="is_update_sale_price_dates_from<?php echo $post_type; ?>" name="is_update_sale_price_dates_from" value="1" <?php echo isset($post['is_update_sale_price_dates_from']) ? $post['is_update_sale_price_dates_from'] ? 'checked="checked"': '' : 'checked="checked"' ?>  class="switcher"/>
                            <label for="is_update_sale_price_dates_from<?php echo $post_type; ?>">
                                <?php _e('Sale Price Dates From', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                        </div>
                        <div class="input sub-input">
                            <input type="hidden" name="is_update_sale_price_dates_to" value="0" />
                            <input type="checkbox" id="is_update_sale_price_dates_to<?php echo $post_type; ?>" name="is_update_sale_price_dates_to" value="1" <?php echo isset($post['is_update_sale_price_dates_to']) ? $post['is_update_sale_price_dates_to'] ? 'checked="checked"': '' : 'checked="checked"' ?>  class="switcher"/>
                            <label for="is_update_sale_price_dates_to<?php echo $post_type; ?>">
                                <?php _e('Sale Price Dates To', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="input" style="display:none;">
                    <input type="hidden" name="is_update_price" value="0" id="is_update_price<?php echo $post_type; ?>" />
                </div>

                <div class="input">
                    <input type="hidden" name="is_update_manage_stock" value="0" />
                    <input type="checkbox" id="is_update_manage_stock<?php echo $post_type; ?>" name="is_update_manage_stock" value="1" <?php echo $post['is_update_manage_stock'] ? 'checked="checked"': '' ?>  class="switcher"/>
                    <label for="is_update_manage_stock<?php echo $post_type; ?>">
                        <?php _e('Manage Stock', 'wpai_woocommerce_addon_plugin') ?>
                    </label>
                    <a href="#help" class="wpallimport-help" title="<?php _e('Enables or disables stock management at product level. When enabled, stock quantities can be managed and WooCommerce will display whether a product is in stock or out of stock.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                </div>
                <div class="input">
                    <input type="hidden" name="is_update_stock_status" value="0" />
                    <input type="checkbox" id="is_update_stock_status<?php echo $post_type; ?>" name="is_update_stock_status" value="1" <?php echo $post['is_update_stock_quantity'] ? 'checked="checked"': '' ?>  disabled="disabled" class="switcher"/>
                    <label for="is_update_stock_status<?php echo $post_type; ?>">
                        <?php _e('Stock Status', 'wpai_woocommerce_addon_plugin') ?>
                    </label>
                    <a href="#help" class="wpallimport-help" title="<?php _e('As of WooCommerce 3.0 stock status is automatically updated when stock quantity is updated.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                </div>
                <div class="input">
                    <input type="hidden" name="is_update_stock_quantity" value="0" />
                    <input type="checkbox" id="is_update_stock_quantity<?php echo $post_type; ?>" name="is_update_stock_quantity" value="1" <?php echo isset($post['is_update_stock_quantity']) ? $post['is_update_stock_quantity'] ? 'checked="checked"': '' : 'checked="checked"' ?>  class="switcher"/>
                    <label for="is_update_stock_quantity<?php echo $post_type; ?>">
                        <?php _e('Stock Quantity', 'wpai_woocommerce_addon_plugin') ?>
                    </label>
                </div>
            </div>

            <!-- Other Product Data Section (Collapsible) -->
            <div class="woocommerce-import-other-section-container">
                <h4 style="margin-bottom:0;" class="woocommerce-import-section-heading woocommerce-import-other-section-heading" id="other-product-data-heading" tabindex="0" role="button" aria-expanded="false" aria-controls="other-product-data-content">
                    <img src="<?php echo PMWI_ROOT_URL; ?>/static/img/caret.png" class="woocommerce-import-section-arrow" aria-hidden="true" />
                    <?php _e('Other Product Data', 'wpai_woocommerce_addon_plugin') ?>
                </h4>
                <span class="field-description">Click to expand for more options</span>
                <div class="woocommerce-import-section" id="other-product-data-content" style="display: none;" aria-hidden="true">
                    <!-- Admin Section -->
                    <h4 class="woocommerce-import-subsection-heading"><?php _e('Admin', 'wpai_woocommerce_addon_plugin') ?></h4>
                    <div class="woocommerce-import-subsection">
                        <div class="input">
                            <input type="hidden" name="is_update_slug" value="0" />
                            <input type="checkbox" id="is_update_slug" name="is_update_slug" value="1" <?php echo $post['is_update_slug'] ? 'checked="checked"': '' ?> />
                            <label for="is_update_slug">
                                <?php _e('Slug', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('The slug is the URL-friendly version of the product name. It is usually all lowercase and contains only letters, numbers, and hyphens. This is used in the product\'s permalink URL.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_parent" value="0" />
                            <input type="checkbox" id="is_update_parent" name="is_update_parent" value="1" <?php echo $post['is_update_parent'] ? 'checked="checked"': '' ?> />
                            <label for="is_update_parent">
                                <?php _e('Parent Product', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('The parent product that this product belongs to. Used for grouped products or variations.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_author" value="0" />
                            <input type="checkbox" id="is_update_author" name="is_update_author" value="1" <?php echo $post['is_update_author'] ? 'checked="checked"': '' ?> />
                            <label for="is_update_author">
                                <?php _e('Author', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_attachments" value="0" />
                            <input type="checkbox" id="is_update_attachments" name="is_update_attachments" value="1" <?php echo $post['is_update_attachments'] ? 'checked="checked"': '' ?> />
                            <label for="is_update_attachments">
                                <?php _e('Attachments', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_menu_order" value="0" />
                            <input type="checkbox" id="is_update_menu_order" name="is_update_menu_order" value="1" <?php echo $post['is_update_menu_order'] ? 'checked="checked"': '' ?> />
                            <label for="is_update_menu_order">
                                <?php _e('Menu Order', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Controls the order in which products are displayed in the catalog. Products with lower menu order values will appear first.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_purchase_note" value="0" />
                            <input type="checkbox" id="is_update_purchase_note<?php echo $post_type; ?>" name="is_update_purchase_note" value="1" <?php echo isset($post['is_update_purchase_note']) ? $post['is_update_purchase_note'] ? 'checked="checked"': '' : '' ?>  class="switcher"/>
                            <label for="is_update_purchase_note<?php echo $post_type; ?>">
                                <?php _e('Purchase Note', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_comment_status" value="0" />
                            <input type="checkbox" id="is_update_comment_status" name="is_update_comment_status" value="1" <?php echo $post['is_update_comment_status'] ? 'checked="checked"': '' ?> />
                            <label for="is_update_comment_status">
                                <?php _e('Enable Reviews', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                        </div>
	                    <?php if ( !in_array('is_update_ping_status', $hidden_data_to_update_options) ): ?>
                            <div class="input">
                                <input type="hidden" name="is_update_ping_status" value="0" />
                                <input type="checkbox" id="is_update_ping_status" name="is_update_ping_status" value="1" <?php echo $post['is_update_ping_status'] ? 'checked="checked"': '' ?> />
                                <label for="is_update_ping_status"><?php _e('Trackbacks and pingbacks', 'wp-all-import-pro') ?></label>
                            </div>
	                    <?php endif; ?>
                        <div class="input">
                            <input type="hidden" name="is_update_dates" value="0" />
                            <input type="checkbox" id="is_update_dates" name="is_update_dates" value="1" <?php echo $post['is_update_dates'] ? 'checked="checked"': '' ?> />
                            <label for="is_update_dates">
                                <?php _e('Dates', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Controls the product\'s publish date, creation date, and modification date. These dates affect when products appear in your store and their sorting order.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                    </div>

                    <!-- Status & Visibility Section -->
                    <h4 class="woocommerce-import-subsection-heading"><?php _e('Status & Visibility', 'wpai_woocommerce_addon_plugin') ?></h4>
                    <div class="woocommerce-import-subsection">
                        <div class="input">
                            <input type="hidden" name="is_update_catalog_visibility" value="0" />
                            <input type="checkbox" id="is_update_catalog_visibility<?php echo $post_type; ?>" name="is_update_catalog_visibility" value="1" <?php echo $post['is_update_catalog_visibility'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_catalog_visibility<?php echo $post_type; ?>">
                                <?php _e('Catalog Visibility', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Controls where this product is displayed in your store. Options include showing the product in both shop pages and search results, only in shop pages, only in search results, or hiding it completely.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_featured_status" value="0" />
                            <input type="checkbox" id="is_update_featured_status<?php echo $post_type; ?>" name="is_update_featured_status" value="1" <?php echo $post['is_update_featured_status'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_featured_status<?php echo $post_type; ?>">
                                <?php _e('Featured Status', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Featured products are displayed prominently in your store using the Featured Products blocks or widgets. They can be used to highlight specific products on your homepage or in other areas of your site.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                    </div>

                    <!-- Product Type Section -->
                    <h4 class="woocommerce-import-subsection-heading"><?php _e('Product Type', 'wpai_woocommerce_addon_plugin') ?></h4>
                    <div class="woocommerce-import-subsection">
                        <div class="input">
                            <input type="hidden" name="is_update_product_type" value="0" />
                            <input type="checkbox" id="is_update_product_type<?php echo $post_type; ?>" name="is_update_product_type" value="1" <?php echo $post['is_update_product_type'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_product_type<?php echo $post_type; ?>">
                                <?php _e('Product Type', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('WooCommerce supports several product types: Simple, Variable, Grouped, and External/Affiliate. Each type has different features and settings. The product type determines which options and fields are available for the product.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_virtual" value="0" />
                            <input type="checkbox" id="is_update_virtual<?php echo $post_type; ?>" name="is_update_virtual" value="1" <?php echo $post['is_update_virtual'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_virtual<?php echo $post_type; ?>">
                                <?php _e('Virtual', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Virtual products are intangible items that don\'t require shipping or physical handling. When a product is marked as virtual, shipping options and related fields are hidden during checkout.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_downloadable" value="0" />
                            <input type="checkbox" id="is_update_downloadable<?php echo $post_type; ?>" name="is_update_downloadable" value="1" <?php echo isset($post['is_update_downloadable']) ? $post['is_update_downloadable'] ? 'checked="checked"': '' : 'checked="checked"' ?>  class="switcher"/>
                            <label for="is_update_downloadable<?php echo $post_type; ?>">
                                <?php _e('Downloadable', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Downloadable products provide digital content that customers can download after purchase. When enabled, you can specify downloadable files, set download limits, and expiry periods for the product.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                            <div style="padding-left:17px;">
                                <div class="input sub-input">
                                    <input type="hidden" name="is_update_download_limit" value="0" />
                                    <input type="checkbox" id="is_update_download_limit<?php echo $post_type; ?>" name="is_update_download_limit" value="1" <?php echo isset($post['is_update_download_limit']) ? $post['is_update_download_limit'] ? 'checked="checked"': '' : 'checked="checked"' ?>  class="switcher"/>
                                    <label for="is_update_download_limit<?php echo $post_type; ?>">
                                        <?php _e('Download Limit', 'wpai_woocommerce_addon_plugin') ?>
                                    </label>
                                    <a href="#help" class="wpallimport-help" title="<?php _e('This option requires that the product be set to \'Downloadable\' in the import template.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                                </div>
                                <div class="input sub-input">
                                    <input type="hidden" name="is_update_download_expiry" value="0" />
                                    <input type="checkbox" id="is_update_download_expiry<?php echo $post_type; ?>" name="is_update_download_expiry" value="1" <?php echo isset($post['is_update_download_expiry']) ? $post['is_update_download_expiry'] ? 'checked="checked"': '' : 'checked="checked"' ?>  class="switcher"/>
                                    <label for="is_update_download_expiry<?php echo $post_type; ?>">
                                        <?php _e('Download Expiry', 'wpai_woocommerce_addon_plugin') ?>
                                    </label>
                                    <a href="#help" class="wpallimport-help" title="<?php _e('This option requires that the product be set to \'Downloadable\' in the import template.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                                </div>
                                <div class="input sub-input">
                                    <input type="hidden" name="is_update_downloadable_files" value="0" />
                                    <input type="checkbox" id="is_update_downloadable_files<?php echo $post_type; ?>" name="is_update_downloadable_files" value="1" <?php echo isset($post['is_update_downloadable_files']) ? $post['is_update_downloadable_files'] ? 'checked="checked"': '' : 'checked="checked"' ?>  class="switcher"/>
                                    <label for="is_update_downloadable_files<?php echo $post_type; ?>">
                                        <?php _e('Downloadable Files', 'wpai_woocommerce_addon_plugin') ?>
                                    </label>
                                    <a href="#help" class="wpallimport-help" title="<?php _e('This option requires that the product be set to \'Downloadable\' in the import template.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tax & Shipping Section -->
                    <h4 class="woocommerce-import-subsection-heading"><?php _e('Tax & Shipping', 'wpai_woocommerce_addon_plugin') ?></h4>
                    <div class="woocommerce-import-subsection">
                        <div class="input">
                            <input type="hidden" name="is_update_tax_class" value="0" />
                            <input type="checkbox" id="is_update_tax_class<?php echo $post_type; ?>" name="is_update_tax_class" value="1" <?php echo $post['is_update_tax_class'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_tax_class<?php echo $post_type; ?>">
			                    <?php _e('Tax Class', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Tax classes are used to apply different tax rates to specific types of products. The tax class assigned to a product determines which tax rate is applied when the product is purchased.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_tax_status" value="0" />
                            <input type="checkbox" id="is_update_tax_status<?php echo $post_type; ?>" name="is_update_tax_status" value="1" <?php echo $post['is_update_tax_status'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_tax_status<?php echo $post_type; ?>">
                                <?php _e('Tax Status', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Determines whether or not the product is taxable. Options include taxable, shipping only (where only shipping is taxed), and none (where no taxes are applied to the product).', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_shipping_class" value="0" />
                            <input type="checkbox" id="is_update_shipping_class<?php echo $post_type; ?>" name="is_update_shipping_class" value="1" <?php echo $post['is_update_shipping_class'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_shipping_class<?php echo $post_type; ?>">
			                    <?php _e('Shipping Class', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Shipping classes allow you to group similar products and apply specific shipping methods and rates to them. For example, you might have different shipping rates for heavy items, fragile items, or oversized items.<br/><br/> <b>The \'product_shipping_class\' taxonomy must also be set to update or this setting is ignored.</b>', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_weight" value="0" />
                            <input type="checkbox" id="is_update_weight<?php echo $post_type; ?>" name="is_update_weight" value="1" <?php echo $post['is_update_weight'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_weight<?php echo $post_type; ?>">
                                <?php _e('Weight', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_length" value="0" />
                            <input type="checkbox" id="is_update_length<?php echo $post_type; ?>" name="is_update_length" value="1" <?php echo $post['is_update_length'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_length<?php echo $post_type; ?>">
                                <?php _e('Length', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_width" value="0" />
                            <input type="checkbox" id="is_update_width<?php echo $post_type; ?>" name="is_update_width" value="1" <?php echo $post['is_update_width'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_width<?php echo $post_type; ?>">
                                <?php _e('Width', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_height" value="0" />
                            <input type="checkbox" id="is_update_height<?php echo $post_type; ?>" name="is_update_height" value="1" <?php echo $post['is_update_height'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_height<?php echo $post_type; ?>">
                                <?php _e('Height', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                        </div>
                    </div>

                    <!-- Inventory Section -->
                    <h4 class="woocommerce-import-subsection-heading"><?php _e('Inventory', 'wpai_woocommerce_addon_plugin') ?></h4>
                    <div class="woocommerce-import-subsection">
                        <div class="input">
                            <input type="hidden" name="is_update_sku" value="0" />
                            <input type="checkbox" id="is_update_sku<?php echo $post_type; ?>" name="is_update_sku" value="1" <?php echo $post['is_update_sku'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_sku<?php echo $post_type; ?>">
                                <?php _e('SKU', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('SKU (Stock Keeping Unit) is a unique identifier for each product in your store. It helps with inventory management and can be used to track products across different systems. SKUs must be unique across your entire store.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_global_unique_id" value="0" />
                            <input type="checkbox" id="is_update_global_unique_id<?php echo $post_type; ?>" name="is_update_global_unique_id" value="1" <?php echo $post['is_update_global_unique_id'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_global_unique_id<?php echo $post_type; ?>">
                                <?php _e('GTIN, UPC, EAN, or ISBN', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('These are standardized global product identifiers. GTIN (Global Trade Item Number), UPC (Universal Product Code), EAN (European Article Number), and ISBN (International Standard Book Number) are used for product identification in retail and e-commerce. They help with product listings on marketplaces and search engines.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_backorders" value="0" />
                            <input type="checkbox" id="is_update_backorders<?php echo $post_type; ?>" name="is_update_backorders" value="1" <?php echo $post['is_update_backorders'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_backorders<?php echo $post_type; ?>">
                                <?php _e('Allow Backorders', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Backorders allow customers to purchase products that are out of stock. You can choose to allow backorders but notify customers, allow backorders silently, or not allow backorders at all. This setting only applies when stock management is enabled.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_sold_individually" value="0" />
                            <input type="checkbox" id="is_update_sold_individually<?php echo $post_type; ?>" name="is_update_sold_individually" value="1" <?php echo $post['is_update_sold_individually'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_sold_individually<?php echo $post_type; ?>">
                                <?php _e('Sold Individually', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('When enabled, customers can only purchase one of this product in a single order. This is useful for products that have limited availability or for products that don\'t make sense to buy in quantity (like a customized product).', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                    </div>

                    <!-- Linked Products Section -->
                    <h4 class="woocommerce-import-subsection-heading"><?php _e('Linked Products', 'wpai_woocommerce_addon_plugin') ?></h4>
                    <div class="woocommerce-import-subsection">
                        <div class="input">
                            <input type="hidden" name="is_update_up_sells" value="0" />
                            <input type="checkbox" id="is_update_up_sells<?php echo $post_type; ?>" name="is_update_up_sells" value="1" <?php echo $post['is_update_up_sells'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_up_sells<?php echo $post_type; ?>">
                                <?php _e('Up-Sells', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Up-sells are products that you recommend instead of the currently viewed product. They are displayed on the product page and encourage customers to purchase a higher-end product instead of the one they\'re considering.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_cross_sells" value="0" />
                            <input type="checkbox" id="is_update_cross_sells<?php echo $post_type; ?>" name="is_update_cross_sells" value="1" <?php echo $post['is_update_cross_sells'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_cross_sells<?php echo $post_type; ?>">
                                <?php _e('Cross-Sells', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Cross-sells are products that you promote in addition to the product the customer is viewing. They are displayed on the cart page and encourage customers to buy complementary or related items along with their current selection.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                        <div class="input">
                            <input type="hidden" name="is_update_grouping" value="0" />
                            <input type="checkbox" id="is_update_grouping<?php echo $post_type; ?>" name="is_update_grouping" value="1" <?php echo $post['is_update_grouping'] ? 'checked="checked"': '' ?>  class="switcher"/>
                            <label for="is_update_grouping<?php echo $post_type; ?>">
                                <?php _e('Grouping', 'wpai_woocommerce_addon_plugin') ?>
                            </label>
                            <a href="#help" class="wpallimport-help" title="<?php _e('Grouping allows you to link related products as part of a group. This is used for grouped products in WooCommerce, where multiple related products are displayed together on a single product page, allowing customers to purchase them individually.', 'wpai_woocommerce_addon_plugin') ?>" style="position: relative; top: -1px;">?</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden fields to maintain compatibility with old structure -->
            <input type="hidden" name="is_update_woocommerce_general_options" value="0" />
            <input type="hidden" name="is_update_woocommerce_inventory_options" value="0" />
            <input type="hidden" name="is_update_woocommerce_shipping_options" value="0" />
            <input type="hidden" name="is_update_woocommerce_linked_product_options" value="0" />

            <!-- Add styles for the new sections -->
            <style type="text/css">
                .woocommerce-import-section-heading {

                }
                .woocommerce-import-section {
                }

                #other-product-data-content {
                }

                #other-product-data-heading {
                    margin-left: -15px;
                }

                .woocommerce-import-subsection-heading {

                }
                .woocommerce-import-subsection {
                }
                .woocommerce-import-other-section-heading {
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    user-select: none;
                }
                .woocommerce-import-section-arrow {
                    margin-right: 5px;
                    transition: transform 0.3s ease;
                    vertical-align: middle;
                    transform: rotate(180deg);
                    width: 9px;
                    height: auto;
                }
                .woocommerce-import-section-arrow.open {
                    transform: rotate(0deg);
                }
                .woocommerce-import-other-section-container {
                    margin-bottom: 20px;
                }

                /* Field description subtext */
                .field-description {
                    display: block;
                    color: #666;
                    font-size: 0.9em;
                    font-style: italic;
                }
                /* Improve spacing between fields */
                .woocommerce-import-section .input,
                .woocommerce-import-subsection .input {
                }

                .woocommerce-import-section .sub-input {
                    margin-left: 8px;
                }

                .woocommerce-import-section .input:has(> input[type="radio"]) {
                    margin-left: 6px;
                }

                /* Improve accessibility */
                .woocommerce-import-section input[type=checkbox]:focus + label {
                    outline: 2px solid #0073aa;
                    outline-offset: 1px;
                }
                .woocommerce-import-other-section-heading:focus {
                    outline: 2px solid #0073aa;
                    outline-offset: 1px;
                }
            </style>

            <!-- Add JavaScript for the collapsible section and Select All/Deselect All functionality -->
            <script type="text/javascript">
                (function($) {
                    'use strict';

                    $(document).ready(function() {
                        // Toggle section functionality
                        const setupAccordion = () => {
                            $('#other-product-data-heading').on('click keypress', function(e) {
                                // Only trigger on click or Enter/Space key
                                if (e.type === 'click' || e.which === 13 || e.which === 32) {
                                    e.preventDefault();

                                    const contentId = $(this).attr('aria-controls');
                                    const $content = $(`#${contentId}`);
                                    const isExpanded = $(this).attr('aria-expanded') === 'true';
                                    const $fieldDescription = $(this).next('.field-description');

                                    // Toggle content and update UI
                                    $content.slideToggle();
                                    $(this).find('.woocommerce-import-section-arrow').toggleClass('open');

                                    // Animate field description - show when closed, hide when open
                                    if (!isExpanded) {
                                        $fieldDescription.slideUp();
                                    } else {
                                        $fieldDescription.slideDown();
                                    }

                                    // Update ARIA attributes
                                    $(this).attr('aria-expanded', !isExpanded);
                                    $content.attr('aria-hidden', isExpanded);
                                }
                            });
                        };

                        // Taxonomies section toggle functionality
                        const setupTaxonomiesToggle = () => {
                            const $taxonomiesContent = $('#taxonomies-content');
                            const $updateCategories = $('#is_update_categories');

                            // Initialize based on initial state
                            $taxonomiesContent.toggle($updateCategories.is(':checked'));

                            // Handle changes
                            $updateCategories.on('change', function() {
                                $(this).is(':checked') ? $taxonomiesContent.slideDown() : $taxonomiesContent.slideUp();
                            });
                        };

                        // Section fields configuration
                        const fieldGroups = {
                            general: [
                                'is_update_title', 'is_update_content', 'is_update_excerpt',
                                'is_update_status', 'is_update_catalog_visibility',
                                'is_update_featured_status', 'is_update_attributes',
                                'is_update_images', 'is_update_virtual', 'is_update_downloadable',
                                'is_update_download_limit', 'is_update_download_expiry', 'is_update_downloadable_files',
                                'is_update_tax_status', 'is_update_tax_class',
                                'is_update_slug', 'is_update_author', 'is_update_attachments',
                                'is_update_menu_order', 'is_update_product_type',
                                'is_update_purchase_note', 'is_update_comment_status',
                                'is_update_dates'
                            ],
                            inventory: [
                                'is_update_sku', 'is_update_global_unique_id', 'is_update_manage_stock',
                                'is_update_stock_status', 'is_update_stock_quantity',
                                'is_update_backorders', 'is_update_sold_individually'
                            ],
                            shipping: [
                                'is_update_weight', 'is_update_length', 'is_update_width',
                                'is_update_height', 'is_update_shipping_class'
                            ],
                            linkedProduct: [
                                'is_update_up_sells', 'is_update_cross_sells', 'is_update_grouping'
                            ]
                        };

                        // Update section flags based on checkbox states
                        const updateSectionFlags = () => {
                            // For each group, check if any field is checked
                            Object.entries(fieldGroups).forEach(([section, fields]) => {
                                const isAnyChecked = fields.some(field =>
                                    $(`input[name="${field}"]`).is(':checked')
                                );

                                // Update the corresponding hidden field
                                $(`input[name="is_update_woocommerce_${section}_options"]`).val(isAnyChecked ? '1' : '0');
                            });
                        };

                        // Remove any existing event handlers first
                        $('#wpallimport-select-all-checkbox, .wpallimport-select-all').off();

                        // Function to handle the select all toggle
                        function handleSelectAllToggle(isChecked) {
                            const selectAllCheckbox = $('#wpallimport-select-all-checkbox');

                            // If isChecked is not provided, toggle based on current state
                            if (typeof isChecked !== 'boolean') {
                                isChecked = !selectAllCheckbox.prop('checked');
                            }

                            // Update the checkbox state visually
                            selectAllCheckbox.prop('checked', isChecked);

                            // Update label text
                            const label = $('.wpallimport-select-all');
                            label.attr('rel', isChecked ? 'Select All' : 'Unselect All');
                            label.text(isChecked ? 'Unselect All' : 'Select All');

                            // Toggle all other checkboxes
                            $('.switcher-target-update_choosen_data input[type="checkbox"]')
                                .not('#wpallimport-select-all-checkbox')
                                .prop('checked', isChecked)
                                .trigger('change');
                        }

                        // Function to update the "Select All" checkbox state based on other checkboxes
                        function updateSelectAllState() {
                            const totalCheckboxes = $('.switcher-target-update_choosen_data input[type="checkbox"]')
                                .not('#wpallimport-select-all-checkbox').length;
                            const checkedCheckboxes = $('.switcher-target-update_choosen_data input[type="checkbox"]:checked')
                                .not('#wpallimport-select-all-checkbox').length;

                            const selectAllCheckbox = $('#wpallimport-select-all-checkbox');
                            const allChecked = (totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);

                            // Update select all checkbox without triggering its change event
                            selectAllCheckbox.prop('checked', allChecked);

                            // Update label text
                            const label = $('.wpallimport-select-all');
                            label.attr('rel', allChecked ? 'Select All' : 'Unselect All');
                            label.text(allChecked ? 'Unselect All' : 'Select All');
                        }

                        // Handle label clicks for select all
                        $('.wpallimport-select-all').on('click', function(e) {
                            e.preventDefault();
                            handleSelectAllToggle();
                            return false;
                        });

                        // Handle checkbox clicks directly
                        $('#wpallimport-select-all-checkbox').on('click', function() {
                            // Use the actual checkbox state after click
                            const isChecked = $(this).prop('checked');

                            // Toggle all checkboxes to match
                            $('.switcher-target-update_choosen_data input[type="checkbox"]')
                                .not('#wpallimport-select-all-checkbox')
                                .prop('checked', isChecked)
                                .trigger('change');

                            // Update the label text
                            const label = $('.wpallimport-select-all');
                            label.attr('rel', isChecked ? 'Select All' : 'Unselect All');
                            label.text(isChecked ? 'Unselect All' : 'Select All');
                        });

                        // Handle individual checkbox changes
                        $('.switcher-target-update_choosen_data input[type="checkbox"]').not('#wpallimport-select-all-checkbox').on('change', function() {
                            updateSelectAllState();
                        });

                        // Sync when Stock Quantity checkbox changes
                        $('#is_update_stock_quantity<?php echo $post_type; ?>').on('change', function() {
                            syncStockStatusWithQuantity();
                        });

                        function syncStockStatusWithQuantity() {
                            var stockQuantityChecked = $('#is_update_stock_quantity<?php echo $post_type; ?>').is(':checked');
                            $('#is_update_stock_status<?php echo $post_type; ?>').prop('checked', stockQuantityChecked);

                            updateSelectAllState();
                        }

                        // Function to update the price hidden field
                        function updatePriceField() {
                            var regularPriceChecked = $('#is_update_regular_price<?php echo $post_type; ?>').is(':checked');
                            var salePriceChecked = $('#is_update_sale_price<?php echo $post_type; ?>').is(':checked');

                            // If either regular price or sale price is checked, also check the price field
                            if (regularPriceChecked || salePriceChecked) {
                                $('#is_update_price<?php echo $post_type; ?>').val('1');
                            } else {
                                $('#is_update_price<?php echo $post_type; ?>').val('0');
                            }
                        }

                        // Add event listeners for changes
                        $('#is_update_regular_price<?php echo $post_type; ?>, #is_update_sale_price<?php echo $post_type; ?>').on('change', function() {
                            updatePriceField();
                        });

                        // Initialize everything
                        const init = () => {
                            setupAccordion();
                            setupTaxonomiesToggle();
                            updateSectionFlags();
                            updateSelectAllState();
                            syncStockStatusWithQuantity();
                            updatePriceField();
                        };

                        init();
                    });
                })(jQuery);
            </script>

            <!-- Add-Ons Section -->
            <?php
            // Start output buffering to capture any content rendered by the action
            ob_start();
            // add-ons re-import options
            do_action('pmxi_reimport', $post_type, $post);
            // Get the buffered content
            $addon_content = ob_get_clean();

            // Only display the section if content was rendered
            if (!empty(trim($addon_content))):
            ?>
            <h4 class="woocommerce-import-section-heading"><?php _e('Add-Ons', 'wpai_woocommerce_addon_plugin') ?></h4>
            <div class="woocommerce-import-section">
                <?php echo $addon_content; ?>
            </div>
            <?php endif; ?>
            <!-- Metadata Section -->
            <h4 class="woocommerce-import-section-heading"><?php _e('Metadata', 'wpai_woocommerce_addon_plugin') ?></h4>
            <div class="woocommerce-import-section">
	            <?php if ( !in_array('is_update_custom_fields', $hidden_data_to_update_options) ): ?>
                    <div class="input">
                        <input type="hidden" name="custom_fields_list" value="0" />
                        <input type="hidden" name="is_update_custom_fields" value="0" />
                        <input type="checkbox" id="is_update_custom_fields" name="is_update_custom_fields" value="1" <?php echo $post['is_update_custom_fields'] ? 'checked="checked"': '' ?>  class="switcher"/>
                        <label for="is_update_custom_fields">
                            <?php _e('Custom Fields', 'wpai_woocommerce_addon_plugin') ?>
                        </label>
                        <!--a href="#help" class="wpallimport-help" title="<?php _e('If Keep Custom Fields box is checked, it will keep all Custom Fields, and add any new Custom Fields specified in Custom Fields section, as long as they do not overwrite existing fields. If \'Only keep this Custom Fields\' is specified, it will only keep the specified fields.', 'wpai_woocommerce_addon_plugin') ?>">?</a-->

                        <?php
                        // Check if any explicitly handled fields are in the custom_fields_list
                        $handled_internally_fields = [];

                        // Check both "only" and "all_except" lists to ensure we catch fields regardless of which option is chosen
                        $custom_fields_only_list = !empty($post['custom_fields_list']) && "only" == $post['update_custom_fields_logic'] ? $post['custom_fields_list'] : [];
                        $custom_fields_except_list = !empty($post['custom_fields_list']) && "all_except" == $post['update_custom_fields_logic'] ? $post['custom_fields_list'] : [];

                        // Also check the raw input values from the UI
                        $custom_fields_only_input = !empty($post['custom_fields_only_list']) ? explode(',', $post['custom_fields_only_list']) : [];
                        $custom_fields_except_input = !empty($post['custom_fields_except_list']) ? explode(',', $post['custom_fields_except_list']) : [];

                        // Combine all possible sources of custom fields and remove duplicates
                        $all_custom_fields = array_unique(array_merge(
                            (array)$custom_fields_only_list, 
                            (array)$custom_fields_except_list,
                            (array)$custom_fields_only_input,
                            (array)$custom_fields_except_input
                        ));

                        $post_type = $post['custom_type'];
                        $internal_fields = [];

                        if (class_exists('wpai_woocommerce_add_on\XmlImportWooCommerceService')) {
                            $internal_fields = wpai_woocommerce_add_on\XmlImportWooCommerceService::$custom_fields_handled_internally[$post_type] ?? [];

                            if (!empty($all_custom_fields)) {
                                foreach ($all_custom_fields as $field) {
                                    $field = trim($field);
                                    if (!empty($field) && array_key_exists($field, $internal_fields)) {
                                        $handled_internally_fields[] = $field;
                                    }
                                }
                            }
                        }

                        // Create a JavaScript array of internally handled fields
                        $js_internal_fields = [];
                        foreach ($internal_fields as $field_key => $field_options) {
                            $js_internal_fields[] = $field_key;
                        }

                        if (!empty($handled_internally_fields)):
                        ?>
                        <div id="wpai-custom-fields-warning" class="wpai-warning-message" style="margin-top: 10px; margin-bottom: 10px; padding: 10px; background-color: #fff8e5; border-left: 4px solid #ffb900; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                            <p><strong><?php _e('Important:', 'wpai_woocommerce_addon_plugin'); ?></strong> <?php _e('The following fields are now managed through explicit product options and not through Custom Fields:', 'wpai_woocommerce_addon_plugin'); ?></p>
                            <ul id="wpai-handled-fields-list" style="list-style-type: disc; margin-left: 20px;">
                                <?php foreach ($handled_internally_fields as $field): ?>
                                <li><code><?php echo esc_html($field); ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                            <p><?php _e('Please use the corresponding options in the sections above to control these fields.', 'wpai_woocommerce_addon_plugin'); ?></p>
                        </div>

                        <script type="text/javascript">
                        jQuery(document).ready(function($) {
                            // List of internally handled fields
                            var internalFields = <?php echo json_encode($js_internal_fields); ?>;

                            // Function to check if any entered fields are handled internally
                            function checkForInternalFields() {
                                var handledFields = [];

                                // Check if the Custom Fields checkbox is checked
                                if (!$('#is_update_custom_fields').is(':checked')) {
                                    $('#wpai-custom-fields-warning').slideUp(300);
                                    return;
                                }

                                // Check which option is selected
                                var selectedOption = $('input[name="update_custom_fields_logic"]:checked').val();

                                // If "Update all Custom Fields" is selected, don't show any warning
                                if (selectedOption === "full_update") {
                                    $('#wpai-custom-fields-warning').slideUp(300);
                                    return;
                                }

                                // Get values from the appropriate custom fields input based on selected option
                                var fieldsToCheck = [];
                                if (selectedOption === "only") {
                                    fieldsToCheck = $('input[name="custom_fields_only_list"]').val().split(',');
                                } else if (selectedOption === "all_except") {
                                    fieldsToCheck = $('input[name="custom_fields_except_list"]').val().split(',');
                                }

                                // Check fields
                                for (var i = 0; i < fieldsToCheck.length; i++) {
                                    var field = fieldsToCheck[i].trim();
                                    if (field && internalFields.indexOf(field) !== -1 && handledFields.indexOf(field) === -1) {
                                        handledFields.push(field);
                                    }
                                }

                                // Update the warning message
                                if (handledFields.length > 0) {
                                    var listHtml = '';
                                    for (var j = 0; j < handledFields.length; j++) {
                                        listHtml += '<li><code>' + handledFields[j] + '</code></li>';
                                    }
                                    $('#wpai-handled-fields-list').html(listHtml);
                                    $('#wpai-custom-fields-warning').slideDown(300);
                                } else {
                                    $('#wpai-custom-fields-warning').slideUp(300);
                                }
                            }

                            // Monitor changes to the custom fields inputs
                            // These are typically enhanced with select2/chosen, so we need to monitor both the original input
                            // and any UI events from the enhancement

                            // For the original inputs
                            $('input[name="custom_fields_only_list"], input[name="custom_fields_except_list"]').on('change', checkForInternalFields);

                            // For select2/chosen events
                            $(document).on('change', '.pmxi_choosen .chosen-select', function() {
                                setTimeout(checkForInternalFields, 100); // Small delay to ensure values are updated
                            });

                            // For direct typing in the search box
                            $(document).on('keyup', '.chosen-search input, .chosen-container input', function() {
                                setTimeout(checkForInternalFields, 500); // Longer delay for typing
                            });

                            // For changes to the Custom Fields option selection
                            $('input[name="update_custom_fields_logic"]').on('change', checkForInternalFields);

                            // For toggling the Custom Fields checkbox itself
                            $('#is_update_custom_fields').on('change', checkForInternalFields);

                            // Initial check
                            checkForInternalFields();
                        });
                        </script>
                        <?php endif; ?>
                        <div class="switcher-target-is_update_custom_fields" style="padding-left:17px;">
                            <div class="input">
                                <input type="radio" id="update_custom_fields_logic_full_update" name="update_custom_fields_logic" value="full_update" <?php echo ( "full_update" == $post['update_custom_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
                                <label for="update_custom_fields_logic_full_update"><?php _e('Update all Custom Fields', 'wpai_woocommerce_addon_plugin') ?></label>
                            </div>
                            <div class="input">
                                <input type="radio" id="update_custom_fields_logic_only" name="update_custom_fields_logic" value="only" <?php echo ( "only" == $post['update_custom_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
                                <label for="update_custom_fields_logic_only"><?php _e('Update only these Custom Fields, leave the rest alone', 'wpai_woocommerce_addon_plugin') ?></label>
                                <div class="switcher-target-update_custom_fields_logic_only pmxi_choosen" style="padding-left:17px;">
                                    <span class="hidden choosen_values"><?php if (!empty($existing_meta_keys)) echo esc_html(implode(',', $existing_meta_keys));?></span>
                                    <input class="choosen_input" value="<?php if (!empty($post['custom_fields_list']) and "only" == $post['update_custom_fields_logic']) echo esc_html(implode(',', $post['custom_fields_list'])); ?>" type="hidden" name="custom_fields_only_list"/>
                                </div>
                            </div>
                            <div class="input">
                                <input type="radio" id="update_custom_fields_logic_all_except" name="update_custom_fields_logic" value="all_except" <?php echo ( "all_except" == $post['update_custom_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
                                <label for="update_custom_fields_logic_all_except"><?php _e('Leave these fields alone, update all other Custom Fields', 'wpai_woocommerce_addon_plugin') ?></label>
                                <div class="switcher-target-update_custom_fields_logic_all_except pmxi_choosen" style="padding-left:17px;">
                                    <span class="hidden choosen_values"><?php if (!empty($existing_meta_keys)) echo esc_html(implode(',', $existing_meta_keys));?></span>
                                    <input class="choosen_input" value="<?php if (!empty($post['custom_fields_list']) and "all_except" == $post['update_custom_fields_logic']) echo esc_html(implode(',', $post['custom_fields_list'])); ?>" type="hidden" name="custom_fields_except_list"/>
                                </div>
                            </div>
                        </div>
                    </div>
	            <?php endif; ?>

	            <?php if ( !in_array('is_update_taxonomies', $hidden_data_to_update_options) ): ?>
                    <div class="input">
                        <input type="hidden" name="taxonomies_list" value="0" />
                        <input type="hidden" name="is_update_categories" value="0" />
                        <input type="checkbox" id="is_update_categories" name="is_update_categories" value="1" class="switcher" <?php echo $post['is_update_categories'] ? 'checked="checked"': '' ?> />
                        <label for="is_update_categories" id="taxonomies-heading">
                            <?php _e('Taxonomies (incl. Categories and Tags)', 'wpai_woocommerce_addon_plugin') ?>
                        </label>
                        <div class="switcher-target-is_update_categories" id="taxonomies-content" style="padding-left:17px; display: none;">
				            <?php
				            $existing_taxonomies = array();
				            $hide_taxonomies = (class_exists('PMWI_Plugin')) ? array('product_type', 'product_visibility') : array();
				            $post_taxonomies = array_diff_key(get_taxonomies_by_object_type($post['is_override_post_type'] ? array_keys(get_post_types( '', 'names' )) : array($post_type), 'object'), array_flip($hide_taxonomies));
				            if (!empty($post_taxonomies)):
					            foreach ($post_taxonomies as $ctx):  if ("" == $ctx->labels->name or (class_exists('PMWI_Plugin') and $post_type == "product" and strpos($ctx->name, "pa_") === 0)) continue;
						            $existing_taxonomies[] = $ctx->name;
					            endforeach;
				            endif;
				            ?>
                                <div class="input" style="">
                                    <input type="radio" id="update_categories_logic_all_except" name="update_categories_logic" value="all_except" <?php echo ( "all_except" == $post['update_categories_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
                                    <label for="update_categories_logic_all_except"><?php _e('Leave these taxonomies alone, update all others', 'wpai_woocommerce_addon_plugin') ?></label>
                                    <div class="switcher-target-update_categories_logic_all_except pmxi_choosen" style="padding-left:17px;">
                                        <span class="hidden choosen_values"><?php if (!empty($existing_taxonomies)) echo esc_html(implode(',', $existing_taxonomies));?></span>
                                        <input class="choosen_input" value="<?php if (!empty($post['taxonomies_list']) and "all_except" == $post['update_categories_logic']) echo esc_html(implode(',', $post['taxonomies_list'])); ?>" type="hidden" name="taxonomies_except_list"/>
                                    </div>
                                </div>
                                <div class="input" style="">
                                    <input type="radio" id="update_categories_logic_only" name="update_categories_logic" value="only" <?php echo ( "only" == $post['update_categories_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
                                    <label for="update_categories_logic_only"><?php _e('Update only these taxonomies, leave the rest alone', 'wpai_woocommerce_addon_plugin') ?></label>
                                    <div class="switcher-target-update_categories_logic_only pmxi_choosen" style="padding-left:17px;">
                                        <span class="hidden choosen_values"><?php if (!empty($existing_taxonomies)) echo esc_html(implode(',', $existing_taxonomies));?></span>
                                        <input class="choosen_input" value="<?php if (!empty($post['taxonomies_list']) and "only" == $post['update_categories_logic']) echo esc_html(implode(',', $post['taxonomies_list'])); ?>" type="hidden" name="taxonomies_only_list"/>
                                    </div>
                                </div>
                                <div class="input" style="">
                                    <input type="radio" id="update_categories_logic_full_update" name="update_categories_logic" value="full_update" <?php echo ( "full_update" == $post['update_categories_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
                                    <label for="update_categories_logic_full_update"><?php _e('Remove existing taxonomies, add new taxonomies', 'wpai_woocommerce_addon_plugin') ?></label>
                                </div>
                                <div class="input" style="">
                                    <input type="radio" id="update_categories_logic_add_new" name="update_categories_logic" value="add_new" <?php echo ( "add_new" == $post['update_categories_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
                                    <label for="update_categories_logic_add_new"><?php _e('Only add new', 'wpai_woocommerce_addon_plugin') ?></label>
                                </div>
                            </div>
                        </div>
			            <?php
			            // add-ons re-import options
			            do_action('pmxi_reimport_options_after_taxonomies', $post_type, $post);
			            ?>
                    </div>
	            <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<div class="switcher-target-auto_matching">
	<?php
	if (file_exists(WP_ALL_IMPORT_ROOT_DIR . '/views/admin/import/options/_delete_missing_options.php')) {
		include( WP_ALL_IMPORT_ROOT_DIR . '/views/admin/import/options/_delete_missing_options.php' );
	}
	?>
</div>
