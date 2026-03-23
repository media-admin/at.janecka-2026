<div class="wpallimport-switcher-section">
	<div class="form-field wpallimport-radio-field">
		<input type="radio" id="shipping_source_copy" name="pmsci_customer[shipping_source]" value="copy" <?php echo 'copy' == $post['pmsci_customer']['shipping_source'] ? 'checked="checked"' : '' ?> class="switcher">
		<label for="shipping_source_copy" style="width:auto;"><?php _e('Copy from billing', 'wp_all_import_plugin') ?></label>
	</div>
	<div class="form-field wpallimport-radio-field">
		<input type="radio" id="shipping_source_import" name="pmsci_customer[shipping_source]" value="import" <?php echo 'import' == $post['pmsci_customer']['shipping_source'] ? 'checked="checked"' : '' ?> class="switcher">
		<label for="shipping_source_import" style="width:auto;"><?php _e('Import shipping address', 'wp_all_import_plugin') ?></label>
	</div>
</div>
<div class="switcher-target-shipping_source_import">
	<div class="pmsci-half" style="margin-top: 5px;">
		<div class="pmsci-half-left">
			<div class="input">
				<p style="margin-bottom:5px;"><b><?php _e('First Name', 'wp_all_import_plugin'); ?></b></p>
				<input name="pmsci_customer[shipping_first_name]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['shipping_first_name']) ?>"/>
			</div>
		</div>
		<div class="pmsci-half-right">
			<div class="input">
				<p style="margin-bottom:5px;"><b><?php _e('Last Name', 'wp_all_import_plugin'); ?></b></p>
				<input name="pmsci_customer[shipping_last_name]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['shipping_last_name']) ?>"/>
			</div>
		</div>
	</div>
	<div class="pmsci-full">
		<div class="input">
			<p style="margin-bottom:5px;"><b><?php _e('Company', 'wp_all_import_plugin'); ?></b></p>
			<input name="pmsci_customer[shipping_company]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['shipping_company']) ?>"/>
		</div>
	</div>
	<div class="pmsci-half">
		<div class="pmsci-half-left">
			<div class="input">
				<p style="margin-bottom:5px;"><b><?php _e('Address 1', 'wp_all_import_plugin'); ?></b></p>
				<input name="pmsci_customer[shipping_address_1]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['shipping_address_1']) ?>"/>
			</div>
		</div>
		<div class="pmsci-half-right">
			<div class="input">
				<p style="margin-bottom:5px;"><b><?php _e('Address 2', 'wp_all_import_plugin'); ?></b></p>
				<input name="pmsci_customer[shipping_address_2]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['shipping_address_2']) ?>"/>
			</div>
		</div>
	</div>
	<div class="pmsci-half">
		<div class="pmsci-half-left">
			<div class="input">
				<p style="margin-bottom:5px;"><b><?php _e('City', 'wp_all_import_plugin'); ?></b></p>
				<input name="pmsci_customer[shipping_city]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['shipping_city']) ?>"/>
			</div>
		</div>
		<div class="pmsci-half-right">
			<div class="input">
				<p style="margin-bottom:5px;"><b><?php _e('Postcode', 'wp_all_import_plugin'); ?></b></p>
				<input name="pmsci_customer[shipping_postcode]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['shipping_postcode']) ?>"/>
			</div>
		</div>
	</div>
	<div class="pmsci-half">
		<div class="pmsci-half-left">
			<div class="input">
				<p style="margin-bottom:5px;"><b><?php _e('Country', 'wp_all_import_plugin'); ?></b><a class="wpallimport-help" href="#help" title="<?php _e('Accepts a two-letter (ISO 3166-1 alpha-2) country code. For example: <br><br> US = United States <br> DE = Germany', 'wp_all_import_user_add_on'); ?>">?</a></p>
				<input name="pmsci_customer[shipping_country]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['shipping_country']) ?>"/>
			</div>
		</div>
		<div class="pmsci-half-right">
			<div class="input">
				<p style="margin-bottom:5px;"><b><?php _e('State/County', 'wp_all_import_plugin'); ?></b></p>
				<input name="pmsci_customer[shipping_state]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['shipping_state']) ?>"/>
			</div>
		</div>
	</div>
</div>
