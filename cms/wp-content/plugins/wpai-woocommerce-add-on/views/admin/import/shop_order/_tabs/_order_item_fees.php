<div class="panel woocommerce_options_panel" id="order_fees" style="display:none;">
	<div class="options_group hide_if_grouped">
		<!-- Fees matching mode -->
		<!-- <div class="form-field wpallimport-radio-field wpallimport-clear">
			<input type="radio" id="fees_repeater_mode_fixed" name="pmwi_order[fees_repeater_mode]" value="fixed" <?php echo 'fixed' == $post['pmwi_order']['fees_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
			<label for="fees_repeater_mode_fixed" style="width:auto;"><?php _e('Fixed Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
		</div> -->
		<table class="form-field wpallimport_variable_table" style="width:100%;">
			<?php
            $fee = [];
            if (!empty($post['pmwi_order']['fees'])) {
                $fee = array_shift($post['pmwi_order']['fees']);
            }
            $fee += array('name' => '', 'amount' => '', 'tax_rates' => []);
            ?>

            <tr>
                <td>
                    <label><?php _e('Fee Name', 'wpai_woocommerce_addon_plugin'); ?></label>
                    <div class="clear">
                        <input type="text" class="rad4" name="pmwi_order[fees][0][name]" value="<?php echo esc_attr($fee['name']) ?>" style="width:95%;"/>
                    </div>
                </td>
                <td>
                    <label><?php _e('Fee Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                    <div class="clear">
                        <input type="text" class="rad4" name="pmwi_order[fees][0][amount]" value="<?php echo esc_attr($fee['amount']) ?>" style="width:95%;"/>
                    </div>
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
                        if (!empty($fee['tax_rates'])) {
                            $tax_rate = array_shift($fee['tax_rates']);
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
                                    <label><?php _e('Fee Tax Rate Name or ID', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <a href="#help" style="left:-6px;" class="wpallimport-help" title="The Tax Rate must already exist in WooCommerce or it will not show up correctly in the imported Orders.">?</a>
                                    <div class="clear"></div>
                                    <input type="text" class="short rad4" name="pmwi_order[fees][0][tax_rates][0][code]" style="width:100%;" value="<?php echo esc_attr($tax_rate['code'] ?? '') ?>"/>
                                </div>
                            </td>
                            <td>
                                <div class="form-field">
                                    <label><?php _e('Fee Tax Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <div class="clear"></div>
                                    <input type="text" class="short rad4" name="pmwi_order[fees][0][tax_rates][0][amount_per_unit]"  style="width:100%;" value="<?php echo esc_attr($tax_rate['amount_per_unit'] ?? '') ?>"/>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <hr>
					<?php if ( empty(PMXI_Plugin::$session->options['delimiter']) ): ?>
                        <div class="form-field wpallimport-radio-field wpallimport-clear">
                            <input type="radio" id="fees_repeater_mode_variable_csv" name="pmwi_order[fees_repeater_mode]" value="csv" <?php echo 'csv' == $post['pmwi_order']['fees_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
                            <label for="fees_repeater_mode_variable_csv" style="width:auto; float: none;"><?php _e('Fixed Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
                            <div class="switcher-target-fees_repeater_mode_variable_csv wpallimport-clear" style="padding: 0 0 0 25px; overflow: hidden;">
                                <span class="wpallimport-slide-content" style="padding-left:0;">
                                    <div class="input">
                                        <label class="order-separator-label" style="line-height: 30px; min-width: 180px;"><?php _e('Multiple tax values separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                        <input type="text" class="short rad4 order-separator-input" name="pmwi_order[fees_repeater_mode_item_separator]" value="<?php echo esc_attr($post['pmwi_order']['fees_repeater_mode_item_separator']) ?>" style="width:10%; text-align: center;"/>
                                    </div>
                                    <p>&nbsp;</p>
                                    <div class="input">
                                        <label class="order-separator-label" style="line-height: 30px; min-width: 180px;"><?php _e('Multiple fees separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                        <input type="text" class="short rad4 order-separator-input" name="pmwi_order[fees_repeater_mode_separator]" value="<?php echo esc_attr($post['pmwi_order']['fees_repeater_mode_separator']) ?>" style="width:10%; text-align: center;left:2px;"/>
                                        <a href="#help" class="wpallimport-help" style="top:12px;left:9px;" title="For example, two fees would be imported like this 'Fee 1|Fee 2' and the fee amounts like this 10|20">?</a>
                                    </div>
                                </span>
                            </div>
                        </div>
                        <div class="form-field wpallimport-radio-field wpallimport-clear">
                            <input type="radio" id="fees_repeater_mode_variable_xml" name="pmwi_order[fees_repeater_mode]" value="xml" <?php echo 'xml' == $post['pmwi_order']['fees_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
                            <label for="fees_repeater_mode_variable_xml" style="width:auto; float: none;"><?php _e('Variable Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
                            <div class="switcher-target-fees_repeater_mode_variable_xml wpallimport-clear" style="padding: 10px 0 10px 25px; overflow: hidden;">
                                <span class="wpallimport-slide-content" style="padding-left:0;">
                                    <label style="width: 60px; line-height: 30px;"><?php _e('For each', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <input type="text" class="short rad4" name="pmwi_order[fees_repeater_mode_foreach]" value="<?php echo esc_attr($post['pmwi_order']['fees_repeater_mode_foreach']) ?>" style="width:50%;"/>
                                    <label class="foreach-do" style="padding-left: 12px; line-height: 30px;"><?php _e('do...', 'wpai_woocommerce_addon_plugin'); ?></label>
                                </span>
                            </div>
                        </div>
					<?php else: ?>
                        <input type="hidden" name="pmwi_order[fees_repeater_mode]" value="csv"/>
                        <div class="form-field input" style="margin-bottom: 20px;">
                            <div class="input">
                                <label class="order-separator-label" style="line-height: 30px; min-width: 180px;"><?php _e('Multiple tax values separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4 order-separator-input" name="pmwi_order[fees_repeater_mode_item_separator]" value="<?php echo esc_attr($post['pmwi_order']['fees_repeater_mode_item_separator']) ?>" style="width:10%; text-align: center;"/>
                            </div>
                            <p>&nbsp;</p>
                            <div class="input">
                                <label class="order-separator-label" style="line-height: 30px; min-width: 180px;"><?php _e('Multiple fees separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4 order-separator-input" name="pmwi_order[fees_repeater_mode_separator]" value="<?php echo esc_attr($post['pmwi_order']['fees_repeater_mode_separator']) ?>" style="width:10%; text-align: center;left:2px;"/>
                                <a href="#help" class="wpallimport-help" style="top:12px;left:9px;" title="For example, two fees would be imported like this 'Fee 1|Fee 2' and the fee amounts like this 10|20">?</a>
                            </div>
                        </div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
