<?php

function pmui_pmxi_save_options($post){

	if( $post['custom_type'] == 'shop_customer') {
		
		// Billing fields logic
		if ($post['pmsci_update_billing_fields_logic'] == 'only' and ! empty($post['pmsci_billing_fields_only_list'])){
			$post['pmsci_billing_fields_list'] = explode(",", $post['pmsci_billing_fields_only_list']); 
		}
		elseif ($post['pmsci_update_billing_fields_logic'] == 'all_except' and ! empty($post['pmsci_billing_fields_except_list']) ){
			$post['pmsci_billing_fields_list'] = explode(",", $post['pmsci_billing_fields_except_list']); 	
		}
		
		// Shipping fields logic
		if ($post['pmsci_update_shipping_fields_logic'] == 'only' and ! empty($post['pmsci_shipping_fields_only_list'])){
			$post['pmsci_shipping_fields_list'] = explode(",", $post['pmsci_shipping_fields_only_list']); 
		}
		elseif ($post['pmsci_update_shipping_fields_logic'] == 'all_except' and ! empty($post['pmsci_shipping_fields_except_list']) ){
			$post['pmsci_shipping_fields_list'] = explode(",", $post['pmsci_shipping_fields_except_list']); 	
		}
	}

	return $post;
}