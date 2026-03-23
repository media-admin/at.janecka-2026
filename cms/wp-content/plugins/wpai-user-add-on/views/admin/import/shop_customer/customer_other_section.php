<div class="wpallimport-collapsed closed">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">			
			<h3><?php _e('Other Customer Info','wp_all_import_user_add_on');?></h3>	
		</div>		
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner wpallimport-customer-data">
				<table class="form-table pmsci-other-fields">
                    <tr class="pmui-quarter">
                        <td class="pmui-quarter-piece" colspan="2">
                            <div class="input">
                                <p style="margin-bottom:5px;"><?php _e('<b>Role</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('A string with role slug used to set the customer\'s role. Default role is customer. Multiple roles must be separated by pipes: e.g. subscriber|editor|contributor ', 'wp_all_import_user_add_on'); ?>">?</a></p>
                                <input id="customer_role" name="pmsci_customer[role]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['role'])  ?>"/>
                            </div>
                        </td>
					<tr class="pmsci-half">
						<td class="pmsci-half-left">
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Nickname</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('The user\'s nickname, defaults to the user\'s username. ', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmsci_customer[nickname]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['nickname']) ?>"/>				
							</div>
						</td>
						<td class="pmsci-half-right">
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Display Name</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('A string that will be shown on the site. Defaults to user\'s username. It is likely that you will want to change this, for both appearance and security through obscurity.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmsci_customer[display_name]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['display_name']); ?>"/>
							</div>
						</td>
					</tr>
					<tr class="pmsci-full">
						<td colspan="2">
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Description</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('A string containing content about the user.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<textarea name="pmsci_customer[description]" class="widefat rad4" style="width:100%;margin-bottom:5px;"><?php if (!empty($post['pmsci_customer']['description'])) echo esc_html($post['pmsci_customer']['description']); ?></textarea>
							</div>
						</td>
					</tr>
					<tr class="pmsci-half">
						<td class="pmsci-half-left">
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Registered</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('The date the user registered. Format is Y-m-d H:i:s', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmsci_customer[registered]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['registered']); ?>"/>
							</div>
						</td>
						<td class="pmsci-half-right">
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Nicename</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('A string that contains a URL-friendly name for the user. The default is the user\'s username.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmsci_customer[nicename]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['nicename']); ?>"/>
							</div>
						</td>
					</tr>
					<tr class="pmsci-half">
						<td>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>URL</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('A string containing the user\'s URL for the user\'s web site. ', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmsci_customer[url]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmsci_customer']['url']); ?>"/>
							</div>					
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
</div>
