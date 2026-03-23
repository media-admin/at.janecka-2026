<div class="panel woocommerce_options_panel" id="order_coupons" style="display:none;">
	<div class="options_group hide_if_grouped">
		<!-- Coupons matching mode -->
		<!-- <div class="form-field wpallimport-radio-field wpallimport-clear">
			<input type="radio" id="coupons_repeater_mode_fixed" name="pmwi_order[coupons_repeater_mode]" value="fixed" <?php echo 'fixed' == $post['pmwi_order']['coupons_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
			<label for="coupons_repeater_mode_fixed" style="width:auto;"><?php _e('Fixed Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
		</div>	 -->
		<table class="form-field wpallimport_variable_table" style="width:100%;">
			<?php
            $coupon = [];
            if (!empty($post['pmwi_order']['coupons'])) {
                $coupon = array_shift($post['pmwi_order']['coupons']);
            }
            $coupon += array('code' => '', 'amount' => '', 'amount_tax' => '');
            ?>
            <tr>
                <td>
                    <label><?php _e('Coupon Code', 'wpai_woocommerce_addon_plugin'); ?></label>
                    <div class="clear">
                        <input type="text" class="rad4" name="pmwi_order[coupons][0][code]" value="<?php echo esc_attr($coupon['code']) ?>" style="width:95%;"/>
                    </div>
                </td>
                <td>
                    <label><?php _e('Discount Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                    <div class="clear">
                        <input type="text" class="rad4" name="pmwi_order[coupons][0][amount]" value="<?php echo esc_attr($coupon['amount']) ?>" style="width:95%;"/>
                        <input type="hidden" class="rad4" name="pmwi_order[coupons][0][amount_tax]" value="<?php echo esc_attr($coupon['amount_tax']) ?>"/>
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
					<?php if ( empty(PMXI_Plugin::$session->options['delimiter']) ): ?>
					<div class="form-field wpallimport-radio-field wpallimport-clear">
						<input type="radio" id="coupons_repeater_mode_variable_csv" name="pmwi_order[coupons_repeater_mode]" value="csv" <?php echo 'csv' == $post['pmwi_order']['coupons_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
						<label for="coupons_repeater_mode_variable_csv" style="width:auto; float: none;"><?php _e('Fixed Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
						<div class="switcher-target-coupons_repeater_mode_variable_csv wpallimport-clear" style="padding: 0 0 0 25px; overflow: hidden;">
							<span class="wpallimport-slide-content" style="padding-left:0;">
                                <label class="order-separator-label" style="line-height: 30px; min-width: 180px;"><?php _e('Multiple coupons separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                                <input type="text" class="short rad4 order-separator-input" name="pmwi_order[coupons_repeater_mode_separator]" value="<?php echo esc_attr($post['pmwi_order']['coupons_repeater_mode_separator']) ?>" style="width:10%; text-align: center;"/>
                                <a href="#help" class="wpallimport-help" style="top:12px;left:8px;" title="For example, two coupons would be imported like this coupon1|coupon2">?</a>
							</span>
						</div>
					</div>
					<div class="form-field wpallimport-radio-field wpallimport-clear">
						<input type="radio" id="coupons_repeater_mode_variable_xml" name="pmwi_order[coupons_repeater_mode]" value="xml" <?php echo 'xml' == $post['pmwi_order']['coupons_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
						<label for="coupons_repeater_mode_variable_xml" style="width:auto; float: none;"><?php _e('Variable Repeater Mode', 'wpai_woocommerce_addon_plugin') ?></label>
						<div class="switcher-target-coupons_repeater_mode_variable_xml wpallimport-clear" style="padding: 10px 0 10px 25px; overflow: hidden;">
							<span class="wpallimport-slide-content" style="padding-left:0;">
								<label style="width: 60px; line-height: 30px;"><?php _e('For each', 'wpai_woocommerce_addon_plugin'); ?></label>
								<input type="text" class="short rad4" name="pmwi_order[coupons_repeater_mode_foreach]" value="<?php echo esc_attr($post['pmwi_order']['coupons_repeater_mode_foreach']) ?>" style="width:50%;"/>
								<label class="foreach-do" style="padding-left: 12px; line-height: 30px;"><?php _e('do...', 'wpai_woocommerce_addon_plugin'); ?></label>
							</span>
						</div>
					</div>
					<?php else: ?>
					<input type="hidden" name="pmwi_order[coupons_repeater_mode]" value="csv"/>
					<div class="form-field input" style="margin-bottom: 20px;">
                        <label class="order-separator-label" style="line-height: 30px; min-width: 180px;"><?php _e('Multiple coupons separated by', 'wpai_woocommerce_addon_plugin'); ?></label>
                        <input type="text" class="short rad4 order-separator-input" name="pmwi_order[coupons_repeater_mode_separator]" value="<?php echo esc_attr($post['pmwi_order']['coupons_repeater_mode_separator']) ?>" style="width:10%; text-align: center;"/>
                        <a href="#help" class="wpallimport-help" style="top:12px;left:8px;" title="For example, two coupons would be imported like this coupon1|coupon2">?</a>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
