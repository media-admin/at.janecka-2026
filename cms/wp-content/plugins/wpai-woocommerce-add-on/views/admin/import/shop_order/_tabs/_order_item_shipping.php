<div class="panel woocommerce_options_panel" id="order_shipping" style="display:none;">
	<div class="options_group hide_if_grouped">
		<!-- Shipping matching mode -->
		<!-- <div class="form-field wpallimport-radio-field wpallimport-clear">
			<input type="radio" id="shipping_repeater_mode_fixed" name="pmwi_order[shipping_repeater_mode]" value="fixed" <?php echo 'fixed' == $post['pmwi_order']['shipping_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
			<label for="shipping_repeater_mode_fixed" style="width:auto;"><?php _e('Fixed Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
		</div>	 -->
		<table class="form-field wpallimport_variable_table" style="width:100%;">
            <?php
            $shipping = [];
            if (!empty($post['pmwi_order']['shipping'])) {
                $shipping = array_shift($post['pmwi_order']['shipping']);
            }
            $shipping += array('name' => '', 'amount' => '', 'class' => '', 'class_xpath' => '', 'tax_rates' => [], 'meta_name' => [], 'meta_value' => []);
            ?>

            <tr>
                <td>
                    <label><?php _e('Shipping Name', 'wpai_woocommerce_addon_plugin'); ?></label>
                    <div class="clear">
                        <input type="text" class="rad4" name="pmwi_order[shipping][0][name]" value="<?php echo esc_attr($shipping['name']) ?>" style="width:95%;"/>
                    </div>
                </td>
                <td>
                    <label><?php _e('Shipping Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                    <div class="clear">
                        <input type="text" class="rad4" name="pmwi_order[shipping][0][amount]" value="<?php echo esc_attr($shipping['amount']) ?>" style="width:95%;"/>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <label><?php _e('Shipping Method', 'wpai_woocommerce_addon_plugin'); ?></label>
                    <span class="wpallimport-clear"></span>
                    <select name="pmwi_order[shipping][0][class]" id="order_shipping_class_0" class="rad4 switcher" style="font-size: 14px !important;">
                        <?php
                        $shipping_for_tooltip = array();
                        foreach ( WC()->shipping->get_shipping_methods() as $method_key => $method ) {
                            echo '<option value="'. $method_key .'" '. ( ($shipping['class'] == $method_key) ? 'selected="selected"' : '' ) .'>' . $method->method_title . '</option>';
                            $shipping_for_tooltip[] = $method_key;
                        }
                        ?>
                        <option value="xpath" <?php if ("xpath" == $shipping['class']) echo 'selected="selected"';?>><?php _e("Set with XPath", 'wpai_woocommerce_addon_plugin'); ?></option>
                    </select>
                    <span class="wpallimport-clear"></span>
                    <div class="switcher-target-order_shipping_class_0" style="margin-top:10px;">
                                    <span class="wpallimport-slide-content" style="padding-left:0;">
                                        <input type="text" class="short rad4" name="pmwi_order[shipping][0][class_xpath]" value="<?php echo esc_attr($shipping['class_xpath']) ?>"/>
                                        <a href="#help" class="wpallimport-help" title="<?php printf(__('Shipping method can be matched by Name or ID: %s. If shipping method is not found then no shipping information will be imported.', 'wpai_woocommerce_addon_plugin'), implode(", ", $shipping_for_tooltip)); ?>" style="position:relative; top:12px;left:8px;">?</a>
                                    </span>
                    </div>
                    <span class="wpallimport-clear"></span>

                    <table class="form-field add-product-meta">
                        <?php foreach ($shipping['meta_name'] as $j => $meta_name): if (empty($meta_name)) continue; ?>
                            <tr class="form-field">
                                <td style="padding-right:10px;">
                                    <label><?php _e('Meta Name', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <input type="text" class="short rad4" name="pmwi_order[shipping][0][meta_name][]" value="<?php echo esc_attr($meta_name); ?>" style="width:100%;"/>
                                </td>
                                <td style="padding-left:10px;">
                                    <label><?php _e('Meta Value', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <input type="text" class="short rad4" name="pmwi_order[shipping][0][meta_value][]" value="<?php echo esc_attr($shipping['meta_value'][$j]); ?>" style="width:100%;"/>
                                </td>
                                <td class="action remove"><a href="#remove" style="top: 35px; right: 17px;"></a></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="form-field template">
                            <td style="padding-right:10px;">
                                <label><?php _e('Meta Name', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4" name="pmwi_order[shipping][0][meta_name][]" value="" style="width:100%;"/>
                            </td>
                            <td style="padding-left:10px;">
                                <label><?php _e('Meta Value', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4" name="pmwi_order[shipping][0][meta_value][]" value="" style="width:100%;"/>
                            </td>
                            <td class="action remove"><a href="#remove" style="top: 35px; right: 17px;"></a></td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <a class="add-new-line" title="Add Shipping Meta" href="javascript:void(0);" style="display:block;margin: 10px 0 20px 0;width:140px;top:0;padding-top:4px;"><?php empty($shipping['meta_name']) ? _e("Add Shipping Meta", 'wpai_woocommerce_addon_plugin'): _e("Add Shipping Meta", 'wpai_woocommerce_addon_plugin'); ?></a>
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
                        if (!empty($shipping['tax_rates'])) {
                            $tax_rate = array_shift($shipping['tax_rates']);
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
                                    <label><?php _e('Shipping Tax Code', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <div class="clear"></div>
                                    <input type="text" class="short rad4" name="pmwi_order[shipping][0][tax_rates][0][code]" style="width:100%;" value="<?php echo esc_attr($tax_rate['code'] ?? '') ?>"/>
                                </div>
                            </td>
                            <td>
                                <div class="form-field">
                                    <label><?php _e('Shipping Tax Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <div class="clear"></div>
                                    <input type="text" class="short rad4" name="pmwi_order[shipping][0][tax_rates][0][amount_per_unit]"  style="width:100%;" value="<?php echo esc_attr($tax_rate['amount_per_unit'] ?? '') ?>"/>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <hr>
					<?php if ( empty(PMXI_Plugin::$session->options['delimiter']) ): ?>
                        <div class="form-field wpallimport-radio-field wpallimport-clear">
                            <input type="radio" id="shipping_repeater_mode_variable_csv" name="pmwi_order[shipping_repeater_mode]" value="csv" <?php echo 'csv' == $post['pmwi_order']['shipping_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
                            <label for="shipping_repeater_mode_variable_csv" style="width:auto; float: none;"><?php _e('Fixed Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
                            <div class="switcher-target-shipping_repeater_mode_variable_csv wpallimport-clear" style="padding: 0 0 0 25px; overflow: hidden;">
                                <span class="wpallimport-slide-content" style="padding-left:0;">
                                    <div class="input">
                                        <label class="order-separator-label" style="line-height: 30px; min-width: 200px;"><?php _e('Multiple tax values separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                        <input type="text" class="short rad4 order-separator-input" name="pmwi_order[shipping_repeater_mode_item_separator]" value="<?php echo esc_attr($post['pmwi_order']['shipping_repeater_mode_item_separator']) ?>" style="width:10%; text-align: center; left:7px;"/>
                                    </div>
                                    <p>&nbsp;</p>
                                    <div class="input">
                                        <label class="order-separator-label" style="line-height: 30px; min-width: 200px;"><?php _e('Multiple shipping costs separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                        <input type="text" class="short rad4 order-separator-input" name="pmwi_order[shipping_repeater_mode_separator]" value="<?php echo esc_attr($post['pmwi_order']['shipping_repeater_mode_separator']) ?>" style="width:10%; text-align: center;"/>
                                        <a href="#help" class="wpallimport-help" style="top:12px;left:7px;" title="For example, two shipping names would be imported like this 'Shipping 1|Shipping 2' and the shipping amounts like this 10|20">?</a>
                                    </div>
                                </span>
                            </div>
                        </div>
                        <div class="form-field wpallimport-radio-field wpallimport-clear">
                            <input type="radio" id="shipping_repeater_mode_variable_xml" name="pmwi_order[shipping_repeater_mode]" value="xml" <?php echo 'xml' == $post['pmwi_order']['shipping_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
                            <label for="shipping_repeater_mode_variable_xml" style="width:auto; float: none;"><?php _e('Variable Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
                            <div class="switcher-target-shipping_repeater_mode_variable_xml wpallimport-clear" style="padding: 10px 0 10px 25px; overflow: hidden;">
                                <span class="wpallimport-slide-content" style="padding-left:0;">
                                    <label style="width: 60px; line-height: 30px;"><?php _e('For each', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <input type="text" class="short rad4" name="pmwi_order[shipping_repeater_mode_foreach]" value="<?php echo esc_attr($post['pmwi_order']['shipping_repeater_mode_foreach']) ?>" style="width:50%;"/>
                                    <label class="foreach-do" style="padding-left: 12px; line-height: 30px;"><?php _e('do...', 'wpai_woocommerce_addon_plugin'); ?></label>
                                </span>
                            </div>
                        </div>
					<?php else: ?>
                        <input type="hidden" name="pmwi_order[shipping_repeater_mode]" value="csv"/>
                        <div class="form-field input" style="margin-bottom: 20px;">
                            <div class="input">
                                <label class="order-separator-label" style="line-height: 30px; min-width: 200px;"><?php _e('Multiple tax values separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4 order-separator-input" name="pmwi_order[shipping_repeater_mode_item_separator]" value="<?php echo esc_attr($post['pmwi_order']['shipping_repeater_mode_item_separator']) ?>" style="width:10%; text-align: center; left:7px;"/>
                            </div>
                            <p>&nbsp;</p>
                            <div class="input">
                                <label class="order-separator-label" style="line-height: 30px; min-width: 200px;"><?php _e('Multiple shipping costs separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4 order-separator-input" name="pmwi_order[shipping_repeater_mode_separator]" value="<?php echo esc_attr($post['pmwi_order']['shipping_repeater_mode_separator']) ?>" style="width:10%; text-align: center;"/>
                                <a href="#help" class="wpallimport-help" style="top:12px;left:7px;" title="For example, two shipping names would be imported like this 'Shipping 1|Shipping 2' and the shipping amounts like this 10|20">?</a>
                            </div>
                        </div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
