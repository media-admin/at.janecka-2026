<?php

function pmui_pmxi_options_validation($errors, $post, $importObj){

	// Skip validation at Step 3 (template) - only validate at Step 4 (options)
	// At Step 3, the user hasn't selected wizard_type/duplicate_indicator yet
	// We detect Step 4 by checking if the options nonce is present
	$is_step_4 = isset($_POST['_wpnonce_options']) || (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'options'));

	// Only validate login/email at Step 4 (options page)
	if (!$is_step_4) {
		return $errors;
	}

	// Determine if login and email are required based on wizard_type and duplicate_indicator
	// Login and email are NOT required when:
	// 1. wizard_type is 'matching' (updating existing items)
	// 2. OR when duplicate_matching is 'manual' (matching by specific field/ID)
	// 3. OR when duplicate_indicator is 'custom field' or 'pid' (matching by custom field or user ID)

	$is_matching_mode = false;

	// Check if we're in matching mode
	if (isset($post['wizard_type']) && $post['wizard_type'] == 'matching') {
		$is_matching_mode = true;
	}

	// Check if we're using manual duplicate matching
	if (isset($post['duplicate_matching']) && $post['duplicate_matching'] == 'manual') {
		$is_matching_mode = true;
	}

	// Check if we're matching by custom field or user ID
	if (isset($post['duplicate_indicator']) && in_array($post['duplicate_indicator'], array('custom field', 'pid'))) {
		$is_matching_mode = true;
	}

	if ( ! empty($post['pmui']['import_users']) && ! $is_matching_mode ) {

		if ( '' == $post['pmui']['login'] ) {
			$errors->add('form-validation', __('`Login` must be specified', 'wp_all_import_user_add_on'));
		}
		if ( '' == $post['pmui']['email'] ) {
			$errors->add('form-validation', __('`Email` must be specified', 'wp_all_import_user_add_on'));
		}

	} elseif ( ! empty($post['pmsci_customer']['import_customers']) && ! $is_matching_mode ) {

		if ( '' == $post['pmsci_customer']['login'] ) {
			$errors->add('form-validation', __('`Login` must be specified', 'wp_all_import_user_add_on'));
		}
		if ( '' == $post['pmsci_customer']['email'] ) {
			$errors->add('form-validation', __('`Email` must be specified', 'wp_all_import_user_add_on'));
		}

	}

	return $errors;
}

?>