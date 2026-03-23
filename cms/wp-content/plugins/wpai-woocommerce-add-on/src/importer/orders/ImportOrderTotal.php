<?php

namespace wpai_woocommerce_add_on\importer\orders;

/**
 *
 * Import Order Total Information
 *
 * Class ImportOrderTotal
 * @package wpai_woocommerce_add_on\importer
 */
class ImportOrderTotal extends ImportOrderBase {

    /**
     * @throws \WC_Data_Exception
     */
    public function import() {
        if ($this->isNewOrder() || $this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_total']) {
            $this->getOrder()->set_total($this->getValue('order_total_amount'));
            $this->getOrder()->set_cart_tax($this->getValue('order_total_tax_amount'));
            // This is required for refunds data import.
            $this->getOrder()->save();
        }
        // Update tax lines for the order based on the line item taxes themselves.
        //$this->getOrder()->update_taxes();
    }
}
