<?php

function pmwi_admin_notices() {
	// notify user if history folder is not writable

	if ( ! class_exists( 'Woocommerce' )) {
		?>
		<div class="error"><p>
			<?php printf(
					__('<b>%s Plugin</b>: WooCommerce must be installed.', 'wpai_woocommerce_addon_plugin'),
					PMWI_Plugin::getInstance()->getName()
			) ?>
		</p></div>
		<?php

		deactivate_plugins( PMWI_ROOT_DIR . '/wpai-woocommerce-add-on.php');

	}

	if ( ! class_exists( 'PMXI_Plugin' ) ) {
		?>
		<div class="error"><p>
			<?php printf(
					__( '<b>%s Plugin</b>: WP All Import must be installed. Free edition of WP All Import at <a href="https://wordpress.org/plugins/wp-all-import/" target="_blank">https://wordpress.org/plugins/wp-all-import/</a> and the paid edition at <a href="https://www.wpallimport.com/" target="_blank">https://www.wpallimport.com/</a>', 'wpai_woocommerce_addon_plugin'),
					PMWI_Plugin::getInstance()->getName()
			) ?>
		</p></div>
		<?php

		deactivate_plugins( PMWI_ROOT_DIR . '/wpai-woocommerce-add-on.php');

	}

    if ( class_exists( 'PMXI_Plugin' ) and ( version_compare(PMXI_VERSION, '4.8.2-beta-1.0') < 0 and PMXI_EDITION == 'paid' or version_compare(PMXI_VERSION, '3.7.3') < 0 and PMXI_EDITION == 'free') ) {
        ?>
        <div class="error"><p>
                <?php printf(
                    __('<b>%s Plugin</b>: The latest version of WP All Import (4.8.2) is required to use this add-on. Any imports that require this add-on will not run correctly until you update WP All Import.', 'wp_all_import_user_add_on'),
                    PMWI_Plugin::getInstance()->getName()
                ) ?>
            </p></div>
        <?php
    }

	if ( class_exists( 'Woocommerce' ) and defined('WOOCOMMERCE_VERSION') and version_compare(WOOCOMMERCE_VERSION, '3.0') <= 0 ) {
		?>
		<div class="error"><p>
			<?php printf(
					__('<b>%s Plugin</b>: Please update your WooCommerce to the latest version', 'wpai_woocommerce_addon_plugin'),
					PMWI_Plugin::getInstance()->getName()
			) ?>
		</p></div>
		<?php

		deactivate_plugins( PMWI_ROOT_DIR . '/wpai-woocommerce-add-on.php');
	}

    $deactivation_notice = get_option('pmwi_free_deactivation_notice', false);
	if ($deactivation_notice) {
	    ?>
        <div class="error"><p>
            <?php printf(__('Pro version activated. Please de-activate and remove the free version of the WooCommerce add-on.', 'wpai_woocommerce_addon_plugin')); ?>
        </p></div>
        <?php
	    delete_option('pmwi_free_deactivation_notice');
    }

	$input = new PMWI_Input();
	$messages = $input->get('PMWI_nt', array());
	if ($messages) {
		is_array($messages) or $messages = array($messages);
		foreach ($messages as $type => $m) {
			in_array((string)$type, array('updated', 'error')) or $type = 'updated';
			?>
			<div class="<?php echo $type ?>"><p><?php echo $m ?></p></div>
			<?php
		}
	}
}
