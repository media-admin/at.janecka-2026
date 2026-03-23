<?php

function pmui_admin_notices() {
	// notify user if history folder is not writable
	if ( ! class_exists( 'PMXI_Plugin' ) ) {
		?>
		<div class="error"><p>
			<?php printf(
					__('<b>%s Plugin</b>: WP All Import must be installed. Free edition of WP All Import at <a href="http://wordpress.org/plugins/wp-all-import/" target="_blank">http://wordpress.org/plugins/wp-all-import/</a> and the paid edition at <a href="http://www.wpallimport.com/">http://www.wpallimport.com/</a>', 'pmui_plugin'),
					PMUI_Plugin::getInstance()->getName()
			) ?>
		</p></div>
		<?php

		deactivate_plugins( PMUI_ROOT_DIR . '/wpai-user-add-on.php');

	}

	if ( class_exists( 'PMXI_Plugin' ) and ( version_compare(PMXI_VERSION, '4.7.8') < 0 and PMXI_EDITION == 'paid' or version_compare(PMXI_VERSION, '3.6.9') < 0 and PMXI_EDITION == 'free') ) {
		?>
		<div class="error"><p>
			<?php printf(
					__('<b>%s Plugin</b>: The latest version of WP All Import is required to use this add-on. Any imports that require this add-on will not run correctly until you update WP All Import.', 'wp_all_import_user_add_on'),
					PMUI_Plugin::getInstance()->getName()
			) ?>
		</p></div>
		<?php
	}

	$input = new PMUI_Input();
	$messages = $input->get('pmui_nt', array());
	if ($messages) {
		is_array($messages) or $messages = array($messages);
		foreach ($messages as $type => $m) {
			in_array((string)$type, array('updated', 'error')) or $type = 'updated';
			?>
			<div class="<?php echo $type ?>"><p><?php echo $m ?></p></div>
			<?php
		}
	}

	if ( ! empty($_GET['type']) and $_GET['type'] == 'user'){
		?>
		<script type="text/javascript">
			(function($){$(function () {
				$('#toplevel_page_pmxi-admin-home').find('.wp-submenu').find('li').removeClass('current');
				$('#toplevel_page_pmxi-admin-home').find('.wp-submenu').find('a').removeClass('current');
				$('#toplevel_page_pmxi-admin-home').find('.wp-submenu').find('li').eq(2).addClass('current').find('a').addClass('current');
			});})(jQuery);
		</script>
		<?php
	}
}
