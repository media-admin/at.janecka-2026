<div class="panel woocommerce_options_panel" id="order_products">
	<div class="options_group hide_if_grouped">
        <table class="wpallimport_variable_table form-field" style="width:100%;">
            <?php
            $product = [];
            if (!empty($post['pmwi_order']['product_items'])) {
                $product = array_shift($post['pmwi_order']['product_items']);
            }
            $product += array('unique_key' => '', 'sku' => '', 'qty' => '', 'price_per_unit' => '', 'tax_rates' => [], 'meta_name' => [], 'meta_value' => []);
            ?>
            <tr>
                <td colspan="2">
                    <div style="float:left; width:50%;">
                        <label><?php _e('Name', 'wpai_woocommerce_addon_plugin'); ?></label>
                        <input type="text" class="short rad4" name="pmwi_order[product_items][0][unique_key]" value="<?php echo esc_attr($product['unique_key']) ?>" style="width:95%;"/>
                    </div>
                    <div style="float:right; width:50%;">
                        <label><?php _e('Quantity', 'wpai_woocommerce_addon_plugin'); ?></label>
                        <input type="text" class="short rad4" name="pmwi_order[product_items][0][qty]" value="<?php echo esc_attr($product['qty']) ?>" style="width:95%;"/>
                    </div>
                    <div class="wpallimport-clear"></div>
                    <div style="float:left; width:50%;margin-top:10px;">
                        <label><?php _e('Existing Product Identifier', 'wpai_woocommerce_addon_plugin'); ?></label>
                        <input type="text" class="short rad4" name="pmwi_order[product_items][0][sku]" value="<?php echo esc_attr($product['sku']) ?>" style="width:95%;"/>
                        <a href="#help" style="left:1px;" class="wpallimport-help" title="Provide the WordPress ID, SKU, or Title of an existing Product to link it to this Order Item.">?</a>
                    </div>
                    <div style="float:right; width:50%;margin-top:10px;">
                        <label><?php _e('Price per unit', 'wpai_woocommerce_addon_plugin'); ?></label>
                        <input type="text" class="short rad4" name="pmwi_order[product_items][0][price_per_unit]" value="<?php echo esc_attr($product['price_per_unit']) ?>" style="width:95%;"/>
                    </div>
                    <span class="wpallimport-clear"></span>

                    <table class="form-field add-product-meta">
                        <?php foreach ($product['meta_name'] as $j => $meta_name): if (empty($meta_name)) continue; ?>
                            <tr class="form-field">
                                <td style="padding-right:10px;">
                                    <label><?php _e('Meta Name', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <input type="text" class="short rad4" name="pmwi_order[product_items][0][meta_name][]" value="<?php echo esc_attr($meta_name); ?>" style="width:100%;"/>
                                </td>
                                <td style="padding-left:10px;">
                                    <label><?php _e('Meta Value', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <input type="text" class="short rad4" name="pmwi_order[product_items][0][meta_value][]" value="<?php echo esc_attr($product['meta_value'][$j]); ?>" style="width:100%;"/>
                                </td>
                                <td class="action remove"><a href="#remove" style="top: 35px; right: 17px;"></a></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="form-field template">
                            <td style="padding-right:10px;">
                                <label><?php _e('Meta Name', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4" name="pmwi_order[product_items][0][meta_name][]" value="" style="width:100%;"/>
                            </td>
                            <td style="padding-left:10px;">
                                <label><?php _e('Meta Value', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4" name="pmwi_order[product_items][0][meta_value][]" value="" style="width:100%;"/>
                            </td>
                            <td class="action remove"><a href="#remove" style="top: 35px; right: 17px;"></a></td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <a class="add-new-line" title="Add Product Meta" href="javascript:void(0);" style="display:block;margin: 10px 0 20px 0;width:140px;top:0;padding-top:4px;"><?php empty($product['meta_name']) ? _e("Add Product Meta", 'wpai_woocommerce_addon_plugin'): _e("Add Product Meta", 'wpai_woocommerce_addon_plugin'); ?></a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
	</div>
	<div class="wpallimport-collapsed closed wpallimport-section order-imports">
		<div style="margin:0; background: #FAFAFA;" class="wpallimport-content-section rad4 order-imports">
			<div class="wpallimport-collapsed-header">
				<h3 style="color:#40acad; font-size: 14px;"><?php _e("Advanced Options",'wpai_woocommerce_addon_plugin'); ?></h3>
			</div>
			<div style="padding: 0px;" class="wpallimport-collapsed-content">
				<div class="wpallimport-collapsed-content-inner">
                    <table style="width:100%;" class="taxes-form-table">
                        <?php
                        $tax_rate = [];
                        if (!empty($product['tax_rates'])) {
                            $tax_rate = array_shift($product['tax_rates']);
                        }
                        $tax_rate += array(
                            'code' => '',
                            'calculate_logic' => 'percentage',
                            'meta_name' => array(),
                            'meta_value' => array(),
                            'percentage_value' => '',
                            'amount_per_unit' => ''
                        );
                        ?>
                        <tr class="form-field">
                            <td>
                                <div class="form-field">
                                    <label><?php _e('Tax Rate Name or ID', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <div class="clear"></div>
                                    <input type="text" class="short rad4" name="pmwi_order[product_items][0][tax_rates][0][code]" style="width:100%;" value="<?php echo esc_attr($tax_rate['code'] ?? '') ?>"/>
                                </div>
                            </td>
                            <td>
                                <div class="form-field">
                                    <label><?php _e('Tax Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <div class="clear"></div>
                                    <input type="text" class="short rad4" name="pmwi_order[product_items][0][tax_rates][0][amount_per_unit]"  style="width:100%;" value="<?php echo esc_attr($tax_rate['amount_per_unit'] ?? '') ?>"/>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <hr>

					<?php if ( empty(PMXI_Plugin::$session->options['delimiter']) ): ?>
                        <div class="form-field wpallimport-radio-field wpallimport-clear">
                            <input type="radio" id="products_repeater_mode_variable_csv" name="pmwi_order[products_repeater_mode]" value="csv" <?php echo 'csv' == $post['pmwi_order']['products_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
                            <label for="products_repeater_mode_variable_csv" style="width:auto; float: none;"><?php _e('Fixed Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
                            <div class="switcher-target-products_repeater_mode_variable_csv wpallimport-clear" style="padding: 0 0 0 25px;">
                                <span class="wpallimport-slide-content" style="padding-left:0;">
                                    <div class="input">
                                        <label class="order-separator-label" style="line-height: 30px; min-width: 180px;"><?php _e('Multiple tax values separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                        <input type="text" class="short rad4 order-separator-input" name="pmwi_order[products_repeater_mode_item_separator]" value="<?php echo esc_attr($post['pmwi_order']['products_repeater_mode_item_separator']) ?>" style="width:10%; text-align: center;"/>
                                    </div>
                                    <p>&nbsp;</p>
                                    <div class="input">
                                        <label class="order-separator-label" style="line-height: 30px; min-width: 180px;"><?php _e('Multiple products separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                        <input type="text" class="short rad4 order-separator-input" name="pmwi_order[products_repeater_mode_separator]" value="<?php echo esc_attr($post['pmwi_order']['products_repeater_mode_separator']) ?>" style="width:10%; text-align: center;left:3px;"/>
                                        <a href="#help" class="wpallimport-help" style="top:12px;left:11px;" title="For example, two products would be imported like this SKU1|SKU2, and their quantities like this 15|20">?</a>
                                    </div>
                                </span>
                            </div>
                        </div>
                        <div class="form-field wpallimport-radio-field wpallimport-clear">
                            <input type="radio" id="products_repeater_mode_variable_xml" name="pmwi_order[products_repeater_mode]" value="xml" <?php echo 'xml' == $post['pmwi_order']['products_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
                            <label for="products_repeater_mode_variable_xml" style="width:auto; float: none;"><?php _e('Variable Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
                            <div class="switcher-target-products_repeater_mode_variable_xml wpallimport-clear" style="padding: 10px 0 10px 25px;">
                                <span class="wpallimport-slide-content" style="padding-left:0;">
                                    <label style="width: 60px; line-height: 30px;"><?php _e('For each', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <input type="text" class="short rad4" name="pmwi_order[products_repeater_mode_foreach]" value="<?php echo esc_attr($post['pmwi_order']['products_repeater_mode_foreach']) ?>" style="width:50%;"/>
                                    <label class="foreach-do" style="padding-left: 10px; line-height: 30px;"><?php _e('do...', 'wpai_woocommerce_addon_plugin'); ?></label>
                                </span>
                            </div>
                        </div>
					<?php else: ?>
                        <input type="hidden" name="pmwi_order[products_repeater_mode]" value="csv"/>
                        <div class="form-field input" style="margin-bottom: 20px;">
                            <div class="input">
                                <label class="order-separator-label" style="line-height: 30px; min-width: 180px;"><?php _e('Multiple tax values separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4 order-separator-input" name="pmwi_order[products_repeater_mode_item_separator]" value="<?php echo esc_attr($post['pmwi_order']['products_repeater_mode_item_separator']) ?>" style="width:10%; text-align: center;"/>
                            </div>
                            <p>&nbsp;</p>
                            <div class="input">
                                <label class="order-separator-label" style="line-height: 30px; min-width: 180px;"><?php _e('Multiple products separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4 order-separator-input" name="pmwi_order[products_repeater_mode_separator]" value="<?php echo esc_attr($post['pmwi_order']['products_repeater_mode_separator']) ?>" style="width:10%; text-align: center;left:3px;"/>
                                <a href="#help" class="wpallimport-help" style="top:12px;left:11px;" title="For example, two products would be imported like this SKU1|SKU2, and their quantities like this 15|20">?</a>
                            </div>
                        </div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
    <!-- Compatibility settings -->
    <input type="hidden" name="pmwi_order[products_source]" value="<?php echo $post['pmwi_order']['products_source'] ?? ''; ?>">
    <input type="hidden" name="pmwi_order[products]" value="<?php echo isset($post['pmwi_order']['products']) ? esc_attr(maybe_serialize($post['pmwi_order']['products'])) : ''; ?>">
    <input type="hidden" name="pmwi_order[manual_products]" value="<?php echo isset($post['pmwi_order']['manual_products']) ? esc_attr(maybe_serialize($post['pmwi_order']['manual_products'])) : ''; ?>">
</div>
