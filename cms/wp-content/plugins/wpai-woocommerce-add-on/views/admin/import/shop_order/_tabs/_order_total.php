<div class="panel woocommerce_options_panel" id="order_total" style="display:none;">
	<div class="options_group hide_if_grouped">
        <table class="form-field wpallimport_variable_table" style="width:100%;">
            <tr>
                <td>
                    <label><?php _e('Tax Amount', 'wpai_woocommerce_addon_plugin'); ?></label>
                    <div class="clear">
                        <input type="text" class="rad4" name="pmwi_order[order_total_tax_amount]" value="<?php echo esc_attr($post['pmwi_order']['order_total_tax_amount']) ?>" style="width:95%;"/>
                    </div>
                </td>
                <td>
                    <label><?php _e('Order Total', 'wpai_woocommerce_addon_plugin'); ?><a href="#help" class="wpallimport-help" style="top:-1px;left:6px;" title="Provide the Order Total amount before any refunds are applied.">?</a></label>
                    <div class="clear">
                        <input type="text" class="rad4" name="pmwi_order[order_total_amount]" value="<?php echo esc_attr($post['pmwi_order']['order_total_amount']) ?>" style="width:95%;"/>
                    </div>
                </td>
            </tr>
        </table>
	</div>
    <!-- Compatibility settings -->
    <input type="hidden" name="pmwi_order[order_total_logic]" value="<?php echo $post['pmwi_order']['order_total_logic'] ?? ''; ?>">
    <input type="hidden" name="pmwi_order[order_total_xpath]" value="<?php echo $post['pmwi_order']['order_total_xpath'] ?? ''; ?>">
    <input type="hidden" name="pmwi_order[taxes]" value="<?php echo isset($post['pmwi_order']['taxes']) ? esc_attr(maybe_serialize($post['pmwi_order']['taxes'])) : ''; ?>">
</div>
