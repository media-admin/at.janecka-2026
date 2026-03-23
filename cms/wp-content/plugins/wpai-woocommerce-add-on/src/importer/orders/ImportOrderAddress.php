<?php

namespace wpai_woocommerce_add_on\importer\orders;

/**
 *
 * Import Order billing & shipping details
 *
 * Class ImportOrderAddress
 * @package wpai_woocommerce_add_on\importer
 */
class ImportOrderAddress extends ImportOrderBase {

    /**
     * @var array
     */
    public $billing_fields = array(
        'billing_first_name',
        'billing_last_name',
        'billing_company',
        'billing_address_1',
        'billing_address_2',
        'billing_city',
        'billing_postcode',
        'billing_country',
        'billing_state',
        'billing_phone',
        'billing_email'
    );

    /**
     * @var array
     */
    public $billing_data = array();

    /**
     * @return int|\WP_Error
     */
    public function import() {
        $this->importBilling();
        $this->importShipping();
    }

    /**
     *
     * Importing billing information
     *
     */
    public function importBilling() {

        if ($this->isNewOrder() || $this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_billing_details']) {
            $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('- Importing billing & shipping information for Order ID `%s`.', 'wpai_woocommerce_addon_plugin'), $this->getOrderID()));
            $customer = FALSE;
            switch ($this->getImport()->options['pmwi_order']['billing_source']) {
                // Load details from existing customer
                case 'existing':
                    $customer = $this->getParser()->get_existing_customer('billing_source', $this->getIndex());
                    if ($customer) {
                        $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('- Existing customer with ID `%s` founded for Order `%s`.', 'wpai_woocommerce_addon_plugin'), $customer->ID, $this->getOrderID()));
                        foreach ($this->billing_fields as $billing_field) {
                            $this->billing_data[$billing_field] = get_user_meta($customer->ID, $billing_field, TRUE);
                            $setter = 'set_' . $billing_field;
                            if ( is_callable( array( $this->getOrder(), $setter ) ) ) {
                                $this->getOrder()->{$setter}( $this->billing_data[$billing_field] );
                                $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('- Billing field `%s` has been updated with value `%s` for order `%s` ...', 'wpai_woocommerce_addon_plugin'), $billing_field, $this->billing_data[$billing_field], $this->getOrderID()));
                            }
                        }
                        $this->getOrder()->set_customer_id($customer->ID);
                    } else {
                        if ($this->getImport()->options['pmwi_order']['is_guest_matching']) {
                            foreach ($this->billing_fields as $billing_field) {
                                $this->billing_data[$billing_field] = $this->getValue('guest_' . $billing_field);
                                $setter = 'set_' . $billing_field;
                                if ( is_callable( array( $this->getOrder(), $setter ) ) ) {
                                    $this->getOrder()->{$setter}( $this->billing_data[$billing_field] );
                                    $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('- Billing field `%s` has been updated with value `%s` for order `%s` ...', 'wpai_woocommerce_addon_plugin'), $billing_field, $this->getValue('guest_' . $billing_field), $this->getOrderID()));
                                }
                            }
                            $this->getOrder()->set_customer_id(0);
                        } else {
                            $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('<b>WARNING</b>: Existing customer not found for Order `%s`.', 'wpai_woocommerce_addon_plugin'), $this->order_data['post_title']));
                        }
                    }
                    break;

                // Use guest customer
                default:

                    foreach ($this->billing_fields as $billing_field) {
                        $this->billing_data[$billing_field] = $this->getValue($billing_field);
                        $setter = 'set_' . $billing_field;
                        if ( is_callable( array( $this->getOrder(), $setter ) ) ) {
                            $this->getOrder()->{$setter}( $this->billing_data[$billing_field] );
                            $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('- Billing field `%s` has been updated with value `%s` for order `%s` ...', 'wpai_woocommerce_addon_plugin'), $billing_field, $this->getValue($billing_field), $this->getOrderID()));
                        }
                    }
                    $this->getOrder()->set_customer_id(0);

                    break;
            }

            if (version_compare(\WC()->version, '3.5.0') >= 0 && $customer) {
                wp_update_post(array(
                    'ID' => $this->getOrderID(),
                    'post_author' => $customer->ID
                ));
            }
        }
    }

    /**
     *
     * Importing shipping information
     *
     */
    public function importShipping() {

        if ($this->isNewOrder() || $this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_shipping_details']) {
            switch ($this->getImport()->options['pmwi_order']['shipping_source']) {
                // Copy from billing
                case 'copy':

                    $this->getLogger() and call_user_func($this->getLogger(), __('- Copying shipping from billing information...', 'wpai_woocommerce_addon_plugin'));

                    if (!empty($this->billing_data)) {
                        foreach ($this->billing_data as $key => $value) {
                            $shipping_field = str_replace('billing', 'shipping', $key);
                            $setter = 'set_' . $shipping_field;
                            if ( is_callable( array( $this->getOrder(), $setter ) ) ) {
                                $this->getOrder()->{$setter}( $value );
                                $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('- Shipping field `%s` has been updated with value `%s` for order `%s` ...', 'wpai_woocommerce_addon_plugin'), $shipping_field, $value, $this->getOrderID()));
                            }
                        }
                    }

                    break;

                // Import shipping address
                default:
                    foreach ($this->billing_fields as $billing_field) {
                        $shipping_field = str_replace('billing', 'shipping', $billing_field);
                        $shipping_value = '';
                        if ($this->getValue($shipping_field) != '') {
                            $shipping_value = $this->getValue($shipping_field);
                        } elseif ($this->getImport()->options['pmwi_order']['copy_from_billing']) {
                            $shipping_value = empty($this->billing_data[$billing_field]) ? '' : $this->billing_data[$billing_field];
                        }
                        $setter = 'set_' . $shipping_field;
                        if ( is_callable( array( $this->getOrder(), $setter ) ) ) {
                            $this->getOrder()->{$setter}( $shipping_value );
                            $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('- Shipping field `%s` has been updated with value `%s` for order `%s` ...', 'wpai_woocommerce_addon_plugin'), $shipping_field, $shipping_value, $this->getOrderID()));
                        }
                    }

                    break;
            }
        }
    }
}
