<div class="panel woocommerce_options_panel" id="order_refunds" style="display:none;">
	<div class="options_group hide_if_grouped">
        <?php
        $refund = [];
        if (!empty($post['pmwi_order']['refunds'])) {
            $refund = array_shift($post['pmwi_order']['refunds']);
        }
        $refund += array(
            'full_reason' => '',
            'partial_reason' => '',
            'full_date' => '',
            'partial_date' => '',
            'order_item_name' => '',
            'item_refund_quantity' => '',
            'item_refund_amount' => '',
            'item_refund_tax_amount' => '',
            'shipping_name' => '',
            'shipping_refund_amount' => '',
            'shipping_refund_tax_amount' => '',
            'fee_name' => '',
            'fee_refund_amount' => '',
            'fee_refund_tax_amount' => '',
            'partial_amount' => '',
            'full_amount' => '',
        );
        ?>
        <div class="form-field wpallimport-radio-field">
            <input type="radio" id="order_refund_type_full" name="pmwi_order[order_refund_type]" value="full" <?php echo 'full' == $post['pmwi_order']['order_refund_type'] ? 'checked="checked"' : '' ?> class="switcher"/>
            <label for="order_refund_type_full" style="width:auto;"><?php _e('Refund Full Amount', 'wpai_woocommerce_addon_plugin') ?></label>
        </div>
        <span class="wpallimport-clear"></span>
        <div class="switcher-target-order_refund_type_full" style="padding-left:45px;">
			<span class="wpallimport-slide-content" style="padding-left:0;">
				<table class="wpallimport_variable_table" style="width:100%;">
                    <tr>
                        <td>
                            <div style="float:left; width:50%;">
                                <label><?php _e( 'Date', 'wpai_woocommerce_addon_plugin' ); ?></label>
                                <div class="date">
                                    <input type="text" class="datepicker rad4" name="pmwi_order[refunds][0][full_date]"
                                           value="<?php echo esc_attr( $refund['full_date'] ) ?>" style="width:95%;"/>
                                </div>
                            </div>
                            <div style="float:right; width:50%;">
                                <label><?php _e( 'Reason', 'wpai_woocommerce_addon_plugin' ); ?></label>
                                <div class="clear">
                                    <input type="text" class="rad4" name="pmwi_order[refunds][0][full_reason]"
                                           value="<?php echo esc_attr( $refund['full_reason'] ) ?>" style="width:95%;"/>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="float:right; width:50%;">
					            <label><?php _e( 'Refund Amount', 'wpai_woocommerce_addon_plugin' ); ?></label>
					            <div class="clear">
						            <input type="text" class="rad4" name="pmwi_order[refunds][0][full_amount]"
                                           value="<?php echo esc_attr( $refund['full_amount'] ) ?>"
                                           style="width:95%;"/>
					            </div>
                            </div>
				        </td>
                    </tr>
                </table>
            </span>
        </div>
        <div class="form-field wpallimport-radio-field">
            <input type="radio" id="order_refund_type_partial" name="pmwi_order[order_refund_type]" value="partial" <?php echo 'partial' == $post['pmwi_order']['order_refund_type'] ? 'checked="checked"' : '' ?> class="switcher"/>
            <label for="order_refund_type_partial" style="width:auto;"><?php _e('Partial Refund', 'wpai_woocommerce_addon_plugin') ?></label>
        </div>
        <span class="wpallimport-clear"></span>
        <div class="switcher-target-order_refund_type_partial" style="padding-left:45px;">
			<span class="wpallimport-slide-content" style="padding-left:0;">
				<table class="wpallimport_variable_table" style="width:100%;">
                    <tr>
                        <td>
                            <div style="float:left; width:50%;">
                                <label><?php _e('Order Item Name', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <div class="date">
                                    <input type="text" class="rad4" name="pmwi_order[refunds][0][order_item_name]" value="<?php echo esc_attr($refund['order_item_name']) ?>" style="width:95%;"/>
                                </div>
                            </div>
                            <div style="float:right; width:50%;">
                                <label><?php _e('Item Refund Quantity', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <div class="clear">
                                    <input type="text" class="rad4" name="pmwi_order[refunds][0][item_refund_quantity]" value="<?php echo esc_attr($refund['item_refund_quantity']) ?>" style="width:95%;"/>
                                </div>
                                <label><?php _e('Item Refund Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <div class="clear">
                                    <input type="text" class="rad4" name="pmwi_order[refunds][0][item_refund_amount]" value="<?php echo esc_attr($refund['item_refund_amount']) ?>" style="width:95%;"/>
                                </div>
                                <label><?php _e('Item Refund Tax Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <div class="clear">
                                    <input type="text" class="rad4" name="pmwi_order[refunds][0][item_refund_tax_amount]" value="<?php echo esc_attr($refund['item_refund_tax_amount']) ?>" style="width:95%;"/>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="float:left; width:50%;">
                                <label><?php _e('Shipping Name', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <div class="date">
                                    <input type="text" class="rad4" name="pmwi_order[refunds][0][shipping_name]" value="<?php echo esc_attr($refund['shipping_name']) ?>" style="width:95%;"/>
                                </div>
                            </div>
                            <div style="float:right; width:50%;">
                                <label><?php _e('Shipping Refund Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <div class="clear">
                                    <input type="text" class="rad4" name="pmwi_order[refunds][0][shipping_refund_amount]" value="<?php echo esc_attr($refund['shipping_refund_amount']) ?>" style="width:95%;"/>
                                </div>
                                <label><?php _e('Shipping Refund Tax Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <div class="clear">
                                    <input type="text" class="rad4" name="pmwi_order[refunds][0][shipping_refund_tax_amount]" value="<?php echo esc_attr($refund['shipping_refund_tax_amount']) ?>" style="width:95%;"/>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="float:left; width:50%;">
                                <label><?php _e('Fee Name', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <div class="date">
                                    <input type="text" class="rad4" name="pmwi_order[refunds][0][fee_name]" value="<?php echo esc_attr($refund['fee_name']) ?>" style="width:95%;"/>
                                </div>
                            </div>
                            <div style="float:right; width:50%;">
                                <label><?php _e('Fee Refund Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <div class="clear">
                                    <input type="text" class="rad4" name="pmwi_order[refunds][0][fee_refund_amount]" value="<?php echo esc_attr($refund['fee_refund_amount']) ?>" style="width:95%;"/>
                                </div>
                                <label><?php _e('Fee Refund Tax Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <div class="clear">
                                    <input type="text" class="rad4" name="pmwi_order[refunds][0][fee_refund_tax_amount]" value="<?php echo esc_attr($refund['fee_refund_tax_amount']) ?>" style="width:95%;"/>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="float:left; width:50%;">
                                <label><?php _e('Date', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <div class="date">
                                    <input type="text" class="datepicker rad4" name="pmwi_order[refunds][0][partial_date]" value="<?php echo esc_attr($refund['partial_date']) ?>" style="width:95%;"/>
                                </div>
                            </div>
                            <div style="float:right; width:50%;">
                                <label><?php _e('Reason', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <div class="clear">
                                    <input type="text" class="rad4" name="pmwi_order[refunds][0][partial_reason]" value="<?php echo esc_attr($refund['partial_reason']) ?>" style="width:95%;"/>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="float:right; width:50%;">
					            <label><?php _e( 'Refund Amount', 'wpai_woocommerce_addon_plugin' ); ?></label>
					            <div class="clear">
						            <input type="text" class="rad4" name="pmwi_order[refunds][0][partial_amount]"
                                           value="<?php echo esc_attr( $refund['partial_amount'] ) ?>"
                                           style="width:95%;"/>
					            </div>
                            </div>
				        </td>
                    </tr>
                </table>
            </span>
        </div>
        <h3 class="form-field"><?php _e('Refund Issued By', 'wpai_woocommerce_addon_plugin'); ?></h3>
        <div class="form-field wpallimport-radio-field">
            <input type="radio" id="order_refund_issued_source_existing" name="pmwi_order[order_refund_issued_source]" value="existing" <?php echo 'existing' == $post['pmwi_order']['order_refund_issued_source'] ? 'checked="checked"' : '' ?> class="switcher"/>
            <label for="order_refund_issued_source_existing" style="width:auto;"><?php _e('Load details from existing user', 'wpai_woocommerce_addon_plugin') ?></label>
            <a href="#help" class="wpallimport-help" title="<?php _e('If no user is matched, refund issuer will be left blank.', 'wpai_woocommerce_addon_plugin') ?>" style="position:relative;left:-10px;">?</a>
        </div>
        <span class="wpallimport-clear"></span>
        <div class="switcher-target-order_refund_issued_source_existing" style="padding-left:7px;">
            <span class="wpallimport-slide-content" style="padding-left:0;">
                <p class="form-field"><?php _e('Match user by:', 'wpai_woocommerce_addon_plugin'); ?></p>
                <!-- Match user by Username -->
                <div class="form-field wpallimport-radio-field">
                    <input type="radio" id="order_refund_issued_match_by_username" name="pmwi_order[order_refund_issued_match_by]" value="username" <?php echo 'username' == $post['pmwi_order']['order_refund_issued_match_by'] ? 'checked="checked"' : '' ?> class="switcher"/>
                    <label for="order_refund_issued_match_by_username"><?php _e('Username', 'wpai_woocommerce_addon_plugin') ?></label>
                    <span class="wpallimport-clear"></span>
                    <div class="switcher-target-order_refund_issued_match_by_username set_with_xpath">
                        <span class="wpallimport-slide-content" style="padding-left:0;">
                            <input type="text" class="short rad4" name="pmwi_order[order_refund_issued_username]" style="" value="<?php echo esc_attr($post['pmwi_order']['order_refund_issued_username']) ?>"/>
                        </span>
                    </div>
                </div>
                <div class="clear"></div>
                <!-- Match user by Email -->
                <div class="form-field wpallimport-radio-field">
                    <input type="radio" id="order_refund_issued_match_by_email" name="pmwi_order[order_refund_issued_match_by]" value="email" <?php echo 'email' == $post['pmwi_order']['order_refund_issued_match_by'] ? 'checked="checked"' : '' ?> class="switcher"/>
                    <label for="order_refund_issued_match_by_email"><?php _e('Email', 'wpai_woocommerce_addon_plugin') ?></label>
                    <span class="wpallimport-clear"></span>
                    <div class="switcher-target-order_refund_issued_match_by_email set_with_xpath">
                        <span class="wpallimport-slide-content" style="padding-left:0;">
                            <input type="text" class="short rad4" name="pmwi_order[order_refund_issued_email]" style="" value="<?php echo esc_attr($post['pmwi_order']['order_refund_issued_email']) ?>"/>
                        </span>
                    </div>
                </div>
                <div class="clear"></div>
                <!-- Match user by Custom Field -->
                <div class="form-field wpallimport-radio-field">
                    <input type="radio" id="order_refund_issued_by_cf" name="pmwi_order[order_refund_issued_match_by]" value="cf" <?php echo 'cf' == $post['pmwi_order']['order_refund_issued_match_by'] ? 'checked="checked"' : '' ?> class="switcher"/>
                    <label for="order_refund_issued_by_cf"><?php _e('Custom Field', 'wpai_woocommerce_addon_plugin') ?></label>
                    <span class="wpallimport-clear"></span>
                    <div class="switcher-target-order_refund_issued_by_cf set_with_xpath">
                        <span class="wpallimport-slide-content" style="padding-left:0;">
                            <p>
                                <label style="width: 30px;"><?php _e('Name', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4" name="pmwi_order[order_refund_issued_cf_name]" style="" value="<?php echo esc_attr($post['pmwi_order']['order_refund_issued_cf_name']) ?>"/>
                            </p>
                            <p>
                                <label style="width: 30px;"><?php _e('Value', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4" name="pmwi_order[order_refund_issued_cf_value]" style="" value="<?php echo esc_attr($post['pmwi_order']['order_refund_issued_cf_value']) ?>"/>
                            </p>
                        </span>
                    </div>
                </div>
                <div class="clear"></div>
                <!-- Match user by user ID -->
                <div class="form-field wpallimport-radio-field">
                    <input type="radio" id="order_refund_issued_by_id" name="pmwi_order[order_refund_issued_match_by]" value="id" <?php echo 'id' == $post['pmwi_order']['order_refund_issued_match_by'] ? 'checked="checked"' : '' ?> class="switcher"/>
                    <label for="order_refund_issued_by_id"><?php _e('User ID', 'wpai_woocommerce_addon_plugin') ?></label>
                    <span class="wpallimport-clear"></span>
                    <div class="switcher-target-order_refund_issued_by_id set_with_xpath">
                        <span class="wpallimport-slide-content" style="padding-left:0;">
                            <input type="text" class="short rad4" name="pmwi_order[order_refund_issued_id]" style="" value="<?php echo esc_attr($post['pmwi_order']['order_refund_issued_id']) ?>"/>
                        </span>
                    </div>
                </div>
            </span>
        </div>
        <div class="clear"></div>
        <div class="form-field wpallimport-radio-field">
            <input type="radio" id="order_refund_issued_source_blank" name="pmwi_order[order_refund_issued_source]" value="blank" <?php echo 'blank' == $post['pmwi_order']['order_refund_issued_source'] ? 'checked="checked"' : '' ?> class="switcher"/>
            <label for="order_refund_issued_source_blank" style="width:auto;"><?php _e('Leave refund issuer blank', 'wpai_woocommerce_addon_plugin') ?></label>
        </div>
	</div>
    <div class="wpallimport-collapsed closed wpallimport-section order-imports">
        <div style="margin:0; background: #FAFAFA;" class="wpallimport-content-section rad4 order-imports">
            <div class="wpallimport-collapsed-header">
                <h3 style="color:#40acad; font-size: 14px;"><?php _e("Advanced Options",'wpai_woocommerce_addon_plugin'); ?></h3>
            </div>
            <div style="padding: 0px;" class="wpallimport-collapsed-content">
                <div class="wpallimport-collapsed-content-inner">
                    <?php if ( empty(PMXI_Plugin::$session->options['delimiter']) ): ?>
                        <div class="form-field wpallimport-radio-field wpallimport-clear">
                            <input type="radio" id="refunds_repeater_mode_variable_csv" name="pmwi_order[refunds_repeater_mode]" value="csv" <?php echo 'csv' == $post['pmwi_order']['refunds_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
                            <label for="refunds_repeater_mode_variable_csv" style="width:auto; float: none;"><?php _e('Fixed Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
                            <div class="switcher-target-refunds_repeater_mode_variable_csv wpallimport-clear" style="padding: 0 0 0 25px; overflow: hidden;">
							<span class="wpallimport-slide-content" style="padding-left:0;">
                                <div class="input">
                                    <label class="order-separator-label" style="line-height: 30px;"><?php _e('Multiple Tax values separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
								    <input type="text" class="short rad4 order-separator-input" name="pmwi_order[refunds_repeater_mode_tax_separator]" value="<?php echo esc_attr($post['pmwi_order']['refunds_repeater_mode_tax_separator']) ?>" style="width:10%; text-align: center;"/>
                                </div>
                                <p>&nbsp;</p>
                                <div class="input">
                                    <label class="order-separator-label" style="line-height: 30px;"><?php _e('Multiple Item, Shipping or Fee values separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
								    <input type="text" class="short rad4 order-separator-input" name="pmwi_order[refunds_repeater_mode_item_separator]" value="<?php echo esc_attr($post['pmwi_order']['refunds_repeater_mode_item_separator']) ?>" style="width:10%; text-align: center;"/>
                                </div>
                                <p>&nbsp;</p>
                                <div class="input">
                                    <label class="order-separator-label" style="line-height: 30px;"><?php _e('Multiple Refunds separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                    <input type="text" class="short rad4 order-separator-input" name="pmwi_order[refunds_repeater_mode_separator]" value="<?php echo esc_attr($post['pmwi_order']['refunds_repeater_mode_separator']) ?>" style="width:10%; text-align: center;left:16px;"/>
                                    <a href="#help" class="wpallimport-help" style="top:12px;left:24px;" title="For example, two refunds would be imported like this 'Refund 1|Refund 2' and the refund amounts like this 10|20">?</a>
                                </div>
							</span>
                            </div>
                        </div>
                        <div class="form-field wpallimport-radio-field wpallimport-clear">
                            <input type="radio" id="refunds_repeater_mode_variable_xml" name="pmwi_order[refunds_repeater_mode]" value="xml" <?php echo 'xml' == $post['pmwi_order']['refunds_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
                            <label for="refunds_repeater_mode_variable_xml" style="width:auto; float: none;"><?php _e('Variable Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
                            <div class="switcher-target-refunds_repeater_mode_variable_xml wpallimport-clear" style="padding: 10px 0 10px 25px; overflow: hidden;">
							<span class="wpallimport-slide-content" style="padding-left:0;">
								<label style="width: 60px; line-height: 30px;"><?php _e('For each', 'wpai_woocommerce_addon_plugin'); ?></label>
								<input type="text" class="short rad4" name="pmwi_order[refunds_repeater_mode_foreach]" value="<?php echo esc_attr($post['pmwi_order']['refunds_repeater_mode_foreach']) ?>" style="width:50%;"/>
								<label class="foreach-do" style="padding-left: 12px; line-height: 30px;"><?php _e('do...', 'wpai_woocommerce_addon_plugin'); ?></label>
							</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="pmwi_order[refunds_repeater_mode]" value="csv"/>
                        <div class="form-field input" style="margin-bottom: 20px;">
                            <div class="input">
                                <label class="order-separator-label" style="line-height: 30px;"><?php _e('Multiple Tax values separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4 order-separator-input" name="pmwi_order[refunds_repeater_mode_tax_separator]" value="<?php echo esc_attr($post['pmwi_order']['refunds_repeater_mode_tax_separator']) ?>" style="width:10%; text-align: center;"/>
                            </div>
                            <p>&nbsp;</p>
                            <div class="input">
                                <label class="order-separator-label" style="line-height: 30px;"><?php _e('Multiple Item, Shipping or Fee values separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4 order-separator-input" name="pmwi_order[refunds_repeater_mode_item_separator]" value="<?php echo esc_attr($post['pmwi_order']['refunds_repeater_mode_item_separator']) ?>" style="width:10%; text-align: center;"/>
                            </div>
                            <p>&nbsp;</p>
                            <div class="input">
                                <label class="order-separator-label" style="line-height: 30px;"><?php _e('Multiple refunds separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4 order-separator-input" name="pmwi_order[refunds_repeater_mode_separator]" value="<?php echo esc_attr($post['pmwi_order']['refunds_repeater_mode_separator']) ?>" style="width:10%; text-align: center;left:16px;"/>
                                <a href="#help" class="wpallimport-help" style="top:12px;left:24px;" title="For example, two refunds would be imported like this 'Refund 1|Refund 2' and the refund amounts like this 10|20">?</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Compatibility settings -->
    <input type="hidden" name="pmwi_order[order_refund_reason]" value="<?php echo $post['pmwi_order']['order_refund_reason'] ?? ''; ?>">
    <input type="hidden" name="pmwi_order[order_refund_amount]" value="<?php echo $post['pmwi_order']['order_refund_amount'] ?? ''; ?>">
    <input type="hidden" name="pmwi_order[order_refund_date]" value="<?php echo $post['pmwi_order']['order_refund_date'] ?? ''; ?>">
</div>
