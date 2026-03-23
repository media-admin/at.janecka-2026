
<?php
$custom_type = get_post_type_object( $post['custom_type'] );
// Check if $existing_meta_keys is defined
$existing_meta_keys = isset($existing_meta_keys) ? $existing_meta_keys : array();
// Check if $isWizard is defined
$isWizard = isset($isWizard) ? $isWizard : false;

// Set up $cpt_name for consistent labeling
if (!empty($post['custom_type'])){
    switch($post['custom_type']) {
        case 'import_users':
            $cpt_name = 'WordPress users';
            break;
        case 'shop_customer':
            $cpt_name = 'WooCommerce customers';
            break;
        default:
            $cpt_name = ( ! empty($custom_type)) ? strtolower($custom_type->label) : '';
            break;
    }
}
else{
    $cpt_name = '';
}
?>
<div class="wpallimport-collapsed wpallimport-section">
	<script type="text/javascript">
		__META_KEYS = <?php echo json_encode($existing_meta_keys) ?>;
	</script>
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">
			<h3><?php printf(__('Configure how WP All Import will handle %s matching', 'wp_all_import_user_add_on'), $custom_type->labels->name); ?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<table class="form-table" style="max-width:none;">
					<tr>
						<td>
							<input type="hidden" name="duplicate_matching" value="<?php echo esc_attr($post['duplicate_matching']); ?>"/>
							<!-- First Radio Option: New Items Import -->
							<div>
								<label>
									<input type="radio" id="wizard_type_new" name="wizard_type" value="new" <?php echo ($post['wizard_type'] == 'new' || empty($post['wizard_type'])) ? 'checked="checked"' : ''; ?> class="switcher"/>
									<h4 style="margin: 0; display: inline-block; font-size: 14px;"><?php printf(__('Create new %s for each record in this import file', 'wp_all_import_user_add_on'), $cpt_name); ?></h4>
								</label>
							</div>
							<div class="wpallimport-wizard-description">
								<?php printf(__('First run creates new %s. Re-running this import will update those same %s using the Unique Identifier.', 'wp_all_import_user_add_on'), $cpt_name, $cpt_name); ?>
							</div>
							<div class="switcher-target-wizard_type_new" <?php if ($post['wizard_type'] != 'new' && !empty($post['wizard_type'])) { ?> style="display: none;" <?php } ?>>
									<div class="wpallimport-wizard-content-wrapper">
										<div class="wpallimport-unique-key-wrapper" <?php if (!empty(PMXI_Plugin::$session->deligate)):?>style="display:none;"<?php endif; ?>>
											<label style="font-weight: bold;"><?php _e("Unique Identifier", "wp_all_import_user_add_on"); ?></label>
											<input type="text" class="smaller-text wpallimport-unique-key-input" name="unique_key" value="<?php if ( ! $isWizard ) echo esc_attr($post['unique_key']); elseif ($post['tmp_unique_key']) echo esc_attr($post['unique_key']); ?>" <?php echo  ( ! $isWizard and ! empty($post['unique_key']) ) ? 'disabled="disabled"' : '' ?>/>

											<?php if ( $isWizard ): ?>
											<input type="hidden" name="tmp_unique_key" value="<?php echo ($post['unique_key']) ? esc_attr($post['unique_key']) : esc_attr($post['tmp_unique_key']); ?>"/>
											<a href="javascript:void(0);" class="wpallimport-auto-detect-unique-key"><?php _e('Auto-detect', 'wp_all_import_user_add_on'); ?></a>
											<a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php printf(__('Adjusting the Unique Identifier<br/><br/>If you run this import again with an updated file, the Unique Identifier allows WP All Import to correctly link the records in your updated file with the %s it will create right now. If multiple records in this import file have the same Unique Identifier, only the first will be created. The others will be detected as duplicates.<br/><br/>If you find that the autodetected Unique Identifier is not unique enough you can drag in any combination of elements. The Unique Identifier should be unique for each record in this import file, and should stay the same even if this import file is updated. Things like product IDs, titles, and SKUs are good Unique Identifiers because they probably won\'t change. Don\'t use a description or price, since that might later change.', 'wp_all_import_user_add_on'), $custom_type->labels->name); ?>">?</a>
											<?php else: ?>
												<?php if ( ! empty($post['unique_key']) ): ?>
												<a href="javascript:void(0);" class="wpallimport-change-unique-key"><?php _e('Edit', 'wp_all_import_user_add_on'); ?></a>
												<div id="dialog-confirm" title="<?php _e('Warning: Are you sure you want to edit the Unique Identifier?','wp_all_import_user_add_on');?>" style="display:none;">
													<p><?php printf(__('It is recommended you delete all %s associated with this import before editing the unique identifier.', 'wp_all_import_user_add_on'), strtolower($custom_type->labels->name)); ?></p>
													<p><?php printf(__('Editing the unique identifier will dissociate all existing %s linked to this import. Future runs of the import will result in duplicates, as WP All Import will no longer be able to update these %s.', 'wp_all_import_user_add_on'), strtolower($custom_type->labels->name), strtolower($custom_type->labels->name)); ?></p>
													<p><?php _e('You really should just re-create your import, and pick the right unique identifier to start with.', 'wp_all_import_user_add_on'); ?></p>
												</div>
												<?php else:?>
												<input type="hidden" name="tmp_unique_key" value="<?php echo ($post['unique_key']) ? esc_attr($post['unique_key']) : esc_attr($post['tmp_unique_key']); ?>"/>
												<a href="javascript:void(0);" class="wpallimport-auto-detect-unique-key"><?php _e('Auto-detect', 'wp_all_import_user_add_on'); ?></a>
												<a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php printf(__('Adjusting the Unique Identifier<br/><br/>If you run this import again with an updated file, the Unique Identifier allows WP All Import to correctly link the records in your updated file with the %s it will create right now. If multiple records in this import file have the same Unique Identifier, only the first will be created. The others will be detected as duplicates.<br/><br/>If you find that the autodetected Unique Identifier is not unique enough you can drag in any combination of elements. The Unique Identifier should be unique for each record in this import file, and should stay the same even if this import file is updated. Things like product IDs, titles, and SKUs are good Unique Identifiers because they probably won\'t change. Don\'t use a description or price, since that might later change.', 'wp_all_import_user_add_on'), $custom_type->labels->name); ?>">?</a>
												<?php endif; ?>
											<?php endif; ?>


										</div>
									</div>
								</div>
							</div>

							<!-- Second Radio Option: Existing Items Import -->
							<div class="wpallimport-wizard-section-spacing">
								<label>
									<input type="radio" id="wizard_type_matching" name="wizard_type" value="matching" <?php echo ($post['wizard_type'] == 'matching') ? 'checked="checked"' : ''; ?> class="switcher"/>
									<h4 style="margin: 0; display: inline-block; font-size: 14px;"><?php printf(__('Attempt to match to existing %s before creating new ones', 'wp_all_import_user_add_on'), $cpt_name); ?></h4>
								</label>
							</div>
							<div class="wpallimport-wizard-description">
								<?php printf(__('Records in this import file will be matched with %s on your site based on...', 'wp_all_import_user_add_on'), $cpt_name); ?>
							</div>
							<div class="switcher-target-wizard_type_matching" <?php if ($post['wizard_type'] != 'matching') { ?> style="display: none;" <?php } ?>>
									<div class="wpallimport-wizard-type-options">
										<input type="radio" id="duplicate_indicator_title" class="switcher" name="duplicate_indicator" value="title" <?php echo 'title' == $post['duplicate_indicator'] ? 'checked="checked"': '' ?>/>
										<label for="duplicate_indicator_title"><?php _e('Login', 'wp_all_import_user_add_on' )?></label><br>

										<input type="radio" id="duplicate_indicator_content" class="switcher" name="duplicate_indicator" value="content" <?php echo 'content' == $post['duplicate_indicator'] ? 'checked="checked"': '' ?>/>
										<label for="duplicate_indicator_content"><?php _e('Email', 'wp_all_import_user_add_on' )?></label><br>

										<input type="radio" id="duplicate_indicator_custom_field" class="switcher" name="duplicate_indicator" value="custom field" <?php echo 'custom field' == $post['duplicate_indicator'] ? 'checked="checked"': '' ?>/>
										<label for="duplicate_indicator_custom_field"><?php _e('Custom field', 'wp_all_import_user_add_on' )?></label><br>
										<span class="switcher-target-duplicate_indicator_custom_field" style="padding-left: 24px;">
											<?php _e('Name', 'wp_all_import_user_add_on') ?>
											<input type="text" name="custom_duplicate_name" value="<?php echo esc_attr($post['custom_duplicate_name']) ?>" />
											<?php _e('Value', 'wp_all_import_user_add_on') ?>
											<input type="text" name="custom_duplicate_value" value="<?php echo esc_attr($post['custom_duplicate_value']) ?>" />
										</span>

										<input type="radio" id="duplicate_indicator_pid" class="switcher" name="duplicate_indicator" value="pid" <?php echo 'pid' == $post['duplicate_indicator'] ? 'checked="checked"': '' ?>/>
										<label for="duplicate_indicator_pid"><?php
                                            if( 'shop_customer' == $post['custom_type'] ) {
	                                            _e( 'Customer ID', 'wp_all_import_user_add_on' );
                                            } else {
	                                            _e( 'User ID', 'wp_all_import_user_add_on' );
                                            }
                                            ?></label><br>
										<span class="switcher-target-duplicate_indicator_pid" style="padding-left: 24px;">
											<input type="text" name="pid_xpath" value="<?php echo esc_attr($post['pid_xpath']) ?>" />
										</span>
										</div>
									</div>
								</div>
							</div>

							<?php
                            if( file_exists(__DIR__.'/_reimport_options_'.$post['custom_type'].'.php')){
                                include('_reimport_options_'.$post['custom_type'].'.php');
                            }else {
                                include( '_reimport_options.php' );
                            }

                            ?>

							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>



<?php if ($post['custom_type'] == 'import_users'): ?>

<div class="wpallimport-collapsed wpallimport-section">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">	
			<h3><?php _e('Email Notifications For Imported Users', 'wp_all_import_user_add_on'); ?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<div class="input">
					<input type="hidden" name="do_not_send_password_notification" value="0" />
					<input type="checkbox" id="do_not_send_password_notification" name="do_not_send_password_notification" value="1" <?php echo empty($post['do_not_send_password_notification']) ? '': 'checked="checked"' ?> class="switcher"/>
					<label for="do_not_send_password_notification"><?php _e('Block email notifications during import', 'wp_all_import_user_add_on') ?></label>
					<a href="#help" class="wpallimport-help" title="<?php _e('If enabled, WP All Import will prevent WordPress from sending notification emails to imported users while the import is processing.', 'wp_all_import_user_add_on') ?>" style="position:relative; top: 0;">?</a>
				</div>	
			</div>
		</div>			
	</div>
</div>

<?php endif; ?>

<?php if ($post['custom_type'] == 'shop_customer'): ?>

<div class="wpallimport-collapsed wpallimport-section">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">	
			<h3><?php _e('Email Notifications For Imported Customers', 'wp_all_import_user_add_on'); ?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<div class="input">
					<input type="hidden" name="do_not_send_password_notification" value="0" />
					<input type="checkbox" id="do_not_send_password_notification" name="do_not_send_password_notification" value="1" <?php echo empty($post['do_not_send_password_notification']) ? '': 'checked="checked"' ?> class="switcher"/>
					<label for="do_not_send_password_notification"><?php _e('Block email notifications during import', 'wp_all_import_user_add_on') ?></label>
					<a href="#help" class="wpallimport-help" title="<?php _e('If enabled, WP All Import will prevent WordPress from sending notification emails to imported customers while the import is processing.', 'wp_all_import_user_add_on') ?>" style="position:relative; top: 0;">?</a>
				</div>	
			</div>
		</div>			
	</div>
</div>

<?php endif; ?>