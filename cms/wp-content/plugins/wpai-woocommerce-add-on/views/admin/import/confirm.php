
<?php if($post['custom_type'] == 'shop_order'): ?>
<ul style="padding-left: 35px;">
    <?php if ( $post['is_update_status']): ?>
        <li> <?php _e('Order status', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_excerpt']): ?>
        <li> <?php _e('Customer Note', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_dates']): ?>
        <li> <?php _e('Dates', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_billing_details']): ?>
        <li> <?php _e('Billing Details', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_shipping_details']): ?>
        <li> <?php _e('Shipping Details', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_payment']): ?>
        <li> <?php _e('Payment Details', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_notes']): ?>
        <li> <?php _e('Order Notes', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_products']): ?>
        <li>
            <?php
            switch($post['update_products_logic']){
                case 'full_update':
                    _e('Update all products', 'wpai_woocommerce_addon_plugin');
                    break;
                case 'add_new':
                    _e('Don\'t touch existing products, append new products', 'wpai_woocommerce_addon_plugin');
                    break;
            } ?>
        </li>
    <?php endif; ?>
    <?php if ( $post['is_update_fees']): ?>
        <li> <?php _e('Fees Items', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_coupons']): ?>
        <li> <?php _e('Coupon Items', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_shipping']): ?>
        <li> <?php _e('Shipping Items', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_taxes']): ?>
        <li> <?php _e('Tax Items', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_refunds']): ?>
        <li> <?php _e('Refunds', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_total']): ?>
        <li> <?php _e('Order Total', 'wpai_woocommerce_addon_plugin'); ?></li>
    <?php endif; ?>
    <?php if ( ! empty($post['is_update_acf'])): ?>
        <li>
            <?php
            switch($post['update_acf_logic']){
                case 'full_update':
                    _e('All advanced custom fields', 'wpai_woocommerce_addon_plugin');
                    break;
                case 'mapped':
                    _e('Only ACF presented in import options', 'wpai_woocommerce_addon_plugin');
                    break;
                case 'only':
                    printf(__('Only these ACF : %s', 'wpai_woocommerce_addon_plugin'), $post['acf_only_list']);
                    break;
                case 'all_except':
                    printf(__('All ACF except these: %s', 'wpai_woocommerce_addon_plugin'), $post['acf_except_list']);
                    break;
            } ?>
        </li>
    <?php endif; ?>
    <?php if ( ! empty($post['is_update_custom_fields'])): ?>
        <li>
            <?php
            switch($post['update_custom_fields_logic']){
                case 'full_update':
                    _e('All custom fields', 'wpai_woocommerce_addon_plugin');
                    break;
                case 'only':
                    printf(__('Only these custom fields : %s', 'wpai_woocommerce_addon_plugin'), $post['custom_fields_only_list']);
                    break;
                case 'all_except':
                    printf(__('All custom fields except these: %s', 'wpai_woocommerce_addon_plugin'), $post['custom_fields_except_list']);
                    break;
            } ?>
        </li>
    <?php endif; ?>
</ul>
<?php endif;
if($post['custom_type'] == 'product'): 
    // Check if we're using new product import options
    $is_using_new_options = !empty($post['is_using_new_product_import_options']);

    // Get the list of internally handled fields if the class exists
    $internal_fields = [];
    if (class_exists('wpai_woocommerce_add_on\XmlImportWooCommerceService')) {
        $internal_fields = wpai_woocommerce_add_on\XmlImportWooCommerceService::$custom_fields_handled_internally['product'] ?? [];
    }

    // Function to check if a field should be displayed
    function should_display_field($field_name, $is_using_new_options, $internal_fields) {
        // If we're using new options, display normally
        if ($is_using_new_options) {
            return true;
        }

        // If not using new options, only display if not in internal fields
        foreach ($internal_fields as $meta_key => $field_options) {
            if ($field_options[0] === $field_name) {
                return false;
            }
        }

        return true;
    }
?>
<ul style="padding-left: 35px;">
        <?php if (!empty($post['is_update_title']) && should_display_field('is_update_title', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Product Name', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_content']) && should_display_field('is_update_content', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Description', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_excerpt']) && should_display_field('is_update_excerpt', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Short Description', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_status']) && should_display_field('is_update_status', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Product Status', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_catalog_visibility']) && should_display_field('is_update_catalog_visibility', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Catalog Visibility', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_featured_status']) && should_display_field('is_update_featured_status', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Featured Status', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_attributes']) && should_display_field('is_update_attributes', $is_using_new_options, $internal_fields)): ?>
            <li>
                <?php
                switch($post['update_attributes_logic']){
                    case 'full_update':
                        _e('Update all Attributes', 'wpai_woocommerce_addon_plugin');
                        break;
                    case 'only':
                        printf(__('Update only these Attributes: %s', 'wpai_woocommerce_addon_plugin'), !empty($post['attributes_list']) ? implode(', ', $post['attributes_list']) : '');
                        break;
                    case 'all_except':
                        printf(__('Leave these Attributes alone: %s', 'wpai_woocommerce_addon_plugin'), !empty($post['attributes_list']) ? implode(', ', $post['attributes_list']) : '');
                        break;
                    case 'add_new':
                        _e('Don\'t touch existing Attributes, add new Attributes', 'wpai_woocommerce_addon_plugin');
                        break;
                } ?>
            </li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_images'])): ?>
            <li>
                <?php
                switch($post['update_images_logic']){
                    case 'full_update':
                        _e('Update all images', 'wpai_woocommerce_addon_plugin');
                        break;
                    case 'add_new':
                        _e('Don\'t touch existing images, append new images', 'wpai_woocommerce_addon_plugin');
                        break;
                } ?>
            </li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_regular_price']) && should_display_field('is_update_regular_price', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Regular Price', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_sale_price']) && should_display_field('is_update_sale_price', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Sale Price', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_sale_price_dates_from']) && should_display_field('is_update_sale_price_dates_from', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Sale Price Dates From', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_sale_price_dates_to']) && should_display_field('is_update_sale_price_dates_to', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Sale Price Dates To', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
       	<?php if (!empty($post['is_update_manage_stock']) && should_display_field('is_update_manage_stock', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Manage Stock', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
        <?php if (!empty($post['is_update_stock_status']) && should_display_field('is_update_stock_status', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Stock Status', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_stock_quantity']) && should_display_field('is_update_stock_quantity', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Stock Quantity', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <!-- Meta & Admin -->
        <?php if (!empty($post['is_update_slug'])): ?>
            <li><?php _e('Slug', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_author'])): ?>
            <li><?php _e('Author', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_attachments'])): ?>
            <li><?php _e('Attachments', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_menu_order'])): ?>
            <li><?php _e('Menu Order', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_product_type'])): ?>
            <li><?php _e('Product Type', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_purchase_note']) && should_display_field('is_update_purchase_note', $is_using_new_options, $internal_fields)): ?>
            <li><?php _e('Purchase Note', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_comment_status'])): ?>
            <li><?php _e('Enable Reviews', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_dates'])): ?>
            <li><?php _e('Dates', 'wpai_woocommerce_addon_plugin'); ?></li>
        <?php endif; ?>

    <!-- Product Options -->
	<?php if (!empty($post['is_update_virtual']) && should_display_field('is_update_virtual', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Virtual', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_downloadable']) && should_display_field('is_update_downloadable', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Downloadable', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_download_limit']) && should_display_field('is_update_download_limit', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Download Limit', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_download_expiry']) && should_display_field('is_update_download_expiry', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Download Expiry', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_downloadable_files']) && should_display_field('is_update_downloadable_files', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Downloadable Files', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>

    <!-- Tax Settings -->
	<?php if (!empty($post['is_update_tax_status']) && should_display_field('is_update_tax_status', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Tax Status', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_tax_class']) && should_display_field('is_update_tax_class', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Tax Class', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>

    <!-- Inventory Details -->
	<?php if (!empty($post['is_update_sku']) && should_display_field('is_update_sku', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('SKU', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_global_unique_id']) && should_display_field('is_update_global_unique_id', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('GTIN, UPC, EAN, or ISBN', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_backorders']) && should_display_field('is_update_backorders', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Allow Backorders', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_sold_individually']) && should_display_field('is_update_sold_individually', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Sold Individually', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>

    <!-- Shipping Dimensions -->
	<?php if (!empty($post['is_update_weight']) && should_display_field('is_update_weight', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Weight', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_length']) && should_display_field('is_update_length', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Length', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_width']) && should_display_field('is_update_width', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Width', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_height']) && should_display_field('is_update_height', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Height', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_shipping_class']) && should_display_field('is_update_shipping_class', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Shipping Class', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>

    <!-- Linked Product -->
	<?php if (!empty($post['is_update_up_sells']) && should_display_field('is_update_up_sells', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Up-Sells', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_cross_sells']) && should_display_field('is_update_cross_sells', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Cross-Sells', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
	<?php if (!empty($post['is_update_grouping']) && should_display_field('is_update_grouping', $is_using_new_options, $internal_fields)): ?>
        <li><?php _e('Grouping', 'wpai_woocommerce_addon_plugin'); ?></li>
	<?php endif; ?>
        <?php if (!empty($post['is_update_custom_fields'])): ?>
            <li>
                <?php
                switch($post['update_custom_fields_logic']){
                    case 'full_update':
                        _e('All custom fields', 'wpai_woocommerce_addon_plugin');
                        break;
                    case 'only':
                        printf(__('Only these custom fields : %s', 'wpai_woocommerce_addon_plugin'), $post['custom_fields_only_list']);
                        break;
                    case 'all_except':
                        printf(__('All custom fields except these: %s', 'wpai_woocommerce_addon_plugin'), $post['custom_fields_except_list']);
                        break;
                } ?>
            </li>
        <?php endif; ?>
        <?php if (!empty($post['is_update_categories'])): ?>
            <li>
                <?php
                switch($post['update_categories_logic']){
                    case 'full_update':
                        _e('Remove existing taxonomies, add new taxonomies', 'wpai_woocommerce_addon_plugin');
                        break;
                    case 'only':
                        printf(__('Update only these taxonomies: %s', 'wpai_woocommerce_addon_plugin'), !empty($post['taxonomies_list']) ? implode(', ', $post['taxonomies_list']) : '');
                        break;
                    case 'all_except':
                        printf(__('Leave these taxonomies alone: %s', 'wpai_woocommerce_addon_plugin'), !empty($post['taxonomies_list']) ? implode(', ', $post['taxonomies_list']) : '');
                        break;
                    case 'add_new':
                        _e('Only add new', 'wpai_woocommerce_addon_plugin');
                        break;
                } ?>
            </li>
        <?php endif; ?>
    </ul>
<?php endif; ?>
