<?php

/**
 * Do not update password for current user, otherwise import will fail.
 *
 * @param $articleData
 * @param $import
 * @param $post_to_update
 * @param $current_xml_node
 *
 * @return mixed
 */
function pmui_pmxi_article_data($articleData, $import, $post_to_update, $current_xml_node) {
	if (in_array($import->options['custom_type'], ['import_users', 'shop_customer']) && !empty($articleData['ID']) && $articleData['ID'] == get_current_user_id()) {
		unset($articleData['user_pass']);
	} elseif (in_array($import->options['custom_type'], ['import_users', 'shop_customer']) && empty($import->options['is_hashed_wordpress_password'])) {
        // Fix for passwords that contains quotes.
        $articleData['user_pass'] = addslashes($articleData['user_pass']);
    }
	return $articleData;
}
