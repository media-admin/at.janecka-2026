<?php 
/**
 * Import configuration wizard
 * 
 * @author Max Tsiplyakov <makstsiplyakov@gmail.com>
 */

class PMUI_Admin_Import extends PMUI_Controller_Admin {				

	public function index( $post = array() ) {			

		$this->data['post'] =& $post;	
		
		switch ($post['custom_type']) 
		{
			case 'import_users':
				$this->render('admin/import/import_users/index');
				break;

			case 'shop_customer':
				$this->render('admin/import/shop_customer/index');			
				break;

			default:
				# code...
				break;
		}

	}	
	
	public function options( $isWizard = false, $post = array() ){		

		$this->data['isWizard'] = $isWizard;	

		$this->data['post'] =& $post;	

        global $wpdb;
		// Get All meta keys in the system
		$this->data['existing_meta_keys'] = array();
		$meta_keys = new PMXI_Model_List();
		$meta_keys->setTable($wpdb->usermeta);
		$meta_keys->setColumns('umeta_id', 'meta_key')->getBy(NULL, "umeta_id", NULL, NULL, "meta_key");	

		if ( $post['custom_type'] == 'shop_customer' ) {

			$user_fields = array('first_name', 'last_name', 'nickname', 'description', PMXI_Plugin::getInstance()->getWPPrefix() . 'capabilities');
			$billing_fields = array('billing_first_name','billing_last_name','billing_company','billing_address_1','billing_address_2','billing_city','billing_postcode','billing_country','billing_state','billing_phone','billing_email');
			$shipping_fields = array('shipping_first_name','shipping_last_name','shipping_company','shipping_address_1','shipping_address_2','shipping_city','shipping_postcode','shipping_country','shipping_state');

			$hide_fields = array_merge($user_fields, $billing_fields, $shipping_fields);

		} else {

			$hide_fields = array('first_name', 'last_name', 'nickname', 'description', PMXI_Plugin::getInstance()->getWPPrefix() . 'capabilities');

		}

		if ( ! empty($meta_keys) and $meta_keys->count() ){
			foreach ($meta_keys as $meta_key) { if (in_array($meta_key['meta_key'], $hide_fields) or strpos($meta_key['meta_key'], '_wp') === 0) continue;
				$this->data['existing_meta_keys'][] = $meta_key['meta_key'];
			}
		}

		$this->render();
	}

    public function confirm( $isWizard = false, $post = array() ){

        $this->data['isWizard'] = $isWizard;

        $this->data['post'] =& $post;

        $this->render();
    }
}
