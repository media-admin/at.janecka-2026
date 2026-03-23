<div class="wpallimport-collapsed closed">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">			
			<h3><?php _e('Other User Info','wp_all_import_user_add_on');?></h3>	
		</div>		
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner wpallimport-user-data">
				<table class="form-table pmui-other-fields">
					<tr class="pmui-quarter">
						<td class="pmui-quarter-piece" colspan="2">
							<div class="input">
                                <p style="margin-bottom:5px;"><?php _e('<b>Role</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('A string with role slug used to set the user\'s role. Default role is subscriber. Multiple roles must be separated by pipes: e.g. subscriber|editor|contributor ', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input id="user_role" name="pmui[role]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['role']) ?>"/>
              </div>
						</td>
					</tr>
					<tr class="pmui-half">
						<td class="pmui-half-left">
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Nickname</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('The user\'s nickname, defaults to the user\'s username. ', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[nickname]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['nickname']) ?>"/>				
							</div>
						</td>
						<td class="pmui-half-right">
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Display Name</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('A string that will be shown on the site. Defaults to user\'s username. It is likely that you will want to change this, for both appearance and security through obscurity.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[display_name]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['display_name']); ?>"/>
							</div>
						</td>
					</tr>
					<tr class="pmui-full">
						<td colspan="2">
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Description</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('A string containing content about the user.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<textarea name="pmui[description]" class="widefat rad4" style="width:100%;margin-bottom:5px;"><?php if (!empty($post['pmui']['description'])) echo esc_html($post['pmui']['description']); ?></textarea>
							</div>
						</td>
					</tr>
					<tr class="pmui-half">
						<td class="pmui-half-left">
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Registered</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('The date the user registered. Format is Y-m-d H:i:s', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[registered]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['registered']); ?>"/>
							</div>
						</td>
						<td class="pmui-half-right">
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Nicename</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('A string that contains a URL-friendly name for the user. The default is the user\'s username.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[nicename]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['nicename']); ?>"/>
							</div>
						</td>
					</tr>
					<tr class="pmui-half">
						<td>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>URL</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" title="<?php _e('A string containing the user\'s URL for the user\'s web site. ', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[url]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['url']); ?>"/>
							</div>					
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
</div>
