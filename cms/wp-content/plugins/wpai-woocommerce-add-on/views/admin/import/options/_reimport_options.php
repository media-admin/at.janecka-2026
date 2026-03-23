<?php 
// Check if $isWizard is defined
$isWizard = isset($isWizard) ? $isWizard : false;

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
	<input type="hidden" id="is_keep_former_posts" name="is_keep_former_posts" value="yes" />
	<input type="checkbox" id="is_not_keep_former_posts" name="is_keep_former_posts" value="no" <?php echo "yes" != $post['is_keep_former_posts'] ? 'checked="checked"': '' ?> class="switcher" />
	<label for="is_not_keep_former_posts"><?php printf(__('Update existing %s with changed data in your file', 'wpai_woocommerce_addon_plugin'), $cpt_name); ?></label>
	<?php if ( $isWizard and "new" == $post['wizard_type'] and empty(PMXI_Plugin::$session->deligate)): ?>
	<a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php _e('These options will only be used if you run this import again later. All data is imported the first time you run an import.', 'wpai_woocommerce_addon_plugin') ?>">?</a>
	<?php endif; ?>
	<div class="switcher-target-is_not_keep_former_posts" style="padding-left:17px;">

        <div class="input" style="margin-left: 4px;">
            <input type="hidden" name="is_selective_hashing" value="0" />
            <input type="checkbox" id="is_selective_hashing" name="is_selective_hashing" value="1" <?php echo $post['is_selective_hashing'] ? 'checked="checked"': '' ?> />
            <label for="is_selective_hashing"><?php printf(__('Skip %s if their data in your file has not changed', 'wp_all_import_plugin'), $cpt_name); ?></label>
            <a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php _e('When enabled, WP All Import will keep track of every post\'s data as it is imported. When the import is run again, posts will be skipped if their data in the import file has not changed since the last run.<br/><br/>Posts will not be skipped if the import template or settings change, or if you make changes to the custom code in the Function Editor.', 'wp_all_import_plugin') ?>">?</a>
        </div>

		<input type="radio" id="update_all_data" class="switcher" name="update_all_data" value="yes" <?php echo 'no' != $post['update_all_data'] ? 'checked="checked"': '' ?>/>
		<label for="update_all_data"><?php _e('Update all data', 'wpai_woocommerce_addon_plugin' )?></label><br>

		<input type="radio" id="update_choosen_data" class="switcher" name="update_all_data" value="no" <?php echo 'no' == $post['update_all_data'] ? 'checked="checked"': '' ?>/>
		<label for="update_choosen_data"><?php _e('Choose which data to update', 'wpai_woocommerce_addon_plugin' )?></label><br>
		<div class="switcher-target-update_choosen_data"  style="padding-left:27px;">
			<div class="input">
				<h4 class="wpallimport-trigger-options wpallimport-select-all" rel="<?php _e("Unselect All", 'wpai_woocommerce_addon_plugin'); ?>"><?php _e("Select All", 'wpai_woocommerce_addon_plugin'); ?></h4>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_status" value="0" />
				<input type="checkbox" id="is_update_status" name="is_update_status" value="1" <?php echo $post['is_update_status'] ? 'checked="checked"': '' ?> />
				<label for="is_update_status"><?php _e('Order status', 'wpai_woocommerce_addon_plugin') ?></label>
				<a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php printf(__('Hint: uncheck this box to keep trashed %s in the trash.', 'wpai_woocommerce_addon_plugin'), $cpt_name); ?>">?</a>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_excerpt" value="0" />
				<input type="checkbox" id="is_update_excerpt" name="is_update_excerpt" value="1" <?php echo $post['is_update_excerpt'] ? 'checked="checked"': '' ?> />
				<label for="is_update_excerpt"><?php _e('Customer Note', 'wpai_woocommerce_addon_plugin') ?></label>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_dates" value="0" />
				<input type="checkbox" id="is_update_dates" name="is_update_dates" value="1" <?php echo $post['is_update_dates'] ? 'checked="checked"': '' ?> />
				<label for="is_update_dates"><?php _e('Dates', 'wpai_woocommerce_addon_plugin') ?></label>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_billing_details" value="0" />
				<input type="checkbox" id="is_update_billing_details" name="is_update_billing_details" value="1" <?php echo $post['is_update_billing_details'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_billing_details"><?php _e('Billing Details', 'wpai_woocommerce_addon_plugin') ?></label>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_shipping_details" value="0" />
				<input type="checkbox" id="is_update_shipping_details" name="is_update_shipping_details" value="1" <?php echo $post['is_update_shipping_details'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_shipping_details"><?php _e('Shipping Details', 'wpai_woocommerce_addon_plugin') ?></label>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_payment" value="0" />
				<input type="checkbox" id="is_update_payment" name="is_update_payment" value="1" <?php echo $post['is_update_payment'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_payment"><?php _e('Payment Details', 'wpai_woocommerce_addon_plugin') ?></label>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_notes" value="0" />
				<input type="checkbox" id="is_update_notes" name="is_update_notes" value="1" <?php echo $post['is_update_notes'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_notes"><?php _e('Order Notes', 'wpai_woocommerce_addon_plugin') ?></label>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_products" value="0" />
				<input type="checkbox" id="is_update_products" name="is_update_products" value="1" <?php echo $post['is_update_products'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_products"><?php _e('Product Items', 'wpai_woocommerce_addon_plugin') ?></label>
				<div class="switcher-target-is_update_products" style="padding-left:17px;">
					<div class="input" style="margin-bottom:3px;">
						<input type="radio" id="update_products_logic_full_update" name="update_products_logic" value="full_update" <?php echo ( "full_update" == $post['update_products_logic'] ) ? 'checked="checked"': '' ?> />
						<label for="update_products_logic_full_update"><?php _e('Update all products', 'wpai_woocommerce_addon_plugin') ?></label>
					</div>
					<div class="input" style="margin-bottom:3px;">
						<input type="radio" id="update_products_logic_add_new" name="update_products_logic" value="add_new" <?php echo ( "add_new" == $post['update_products_logic'] ) ? 'checked="checked"': '' ?> />
						<label for="update_products_logic_add_new"><?php _e('Don\'t touch existing products, append new products', 'wpai_woocommerce_addon_plugin') ?></label>
					</div>
				</div>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_fees" value="0" />
				<input type="checkbox" id="is_update_fees" name="is_update_fees" value="1" <?php echo $post['is_update_fees'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_fees"><?php _e('Fees Items', 'wpai_woocommerce_addon_plugin') ?></label>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_coupons" value="0" />
				<input type="checkbox" id="is_update_coupons" name="is_update_coupons" value="1" <?php echo $post['is_update_coupons'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_coupons"><?php _e('Coupon Items', 'wpai_woocommerce_addon_plugin') ?></label>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_shipping" value="0" />
				<input type="checkbox" id="is_update_shipping" name="is_update_shipping" value="1" <?php echo $post['is_update_shipping'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_shipping"><?php _e('Shipping Items', 'wpai_woocommerce_addon_plugin') ?></label>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_taxes" value="0" />
				<input type="checkbox" id="is_update_taxes" name="is_update_taxes" value="1" <?php echo $post['is_update_taxes'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_taxes"><?php _e('Tax Items', 'wpai_woocommerce_addon_plugin') ?></label>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_refunds" value="0" />
				<input type="checkbox" id="is_update_refunds" name="is_update_refunds" value="1" <?php echo $post['is_update_refunds'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_refunds"><?php _e('Refunds', 'wpai_woocommerce_addon_plugin') ?></label>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_total" value="0" />
				<input type="checkbox" id="is_update_total" name="is_update_total" value="1" <?php echo $post['is_update_total'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_total"><?php _e('Order Total', 'wpai_woocommerce_addon_plugin') ?></label>
			</div>

			<!-- Do not update order custom fields -->
			<!-- <input type="hidden" name="is_update_custom_fields" value="0" /> -->

			<div class="input">
				<input type="hidden" name="custom_fields_list" value="0" />
				<input type="hidden" name="is_update_custom_fields" value="0" />
				<input type="checkbox" id="is_update_custom_fields" name="is_update_custom_fields" value="1" <?php echo $post['is_update_custom_fields'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_custom_fields"><?php _e('Custom Fields', 'wpai_woocommerce_addon_plugin') ?></label>
				<!--a href="#help" class="wpallimport-help" title="<?php _e('If Keep Custom Fields box is checked, it will keep all Custom Fields, and add any new Custom Fields specified in Custom Fields section, as long as they do not overwrite existing fields. If \'Only keep this Custom Fields\' is specified, it will only keep the specified fields.', 'wpai_woocommerce_addon_plugin') ?>">?</a-->
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
			<?php
			// add-ons re-import options
			do_action('pmxi_reimport', $post['custom_type'], $post);
			?>
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
