<?php

namespace wpai_woocommerce_add_on\importer\orders;

use WC_Payment_Gateways;

/**
 *
 * Import Order payment details
 *
 * Class ImportOrderDetails
 * @package wpai_woocommerce_add_on\importer
 */
class ImportOrderPayment extends ImportOrderBase {

    public $payment_gateways;

    /**
     * @return void
     */
    public function import() {

        $this->payment_gateways = WC_Payment_Gateways::instance()
            ->payment_gateways();

        if ($this->isNewOrder() || $this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_payment']) {
            $payment_method = $this->getValue('payment_method');
            $payment_date = $this->getValue('payment_date');
            $this->getOrder()->set_date_paid($payment_date);
            if (!empty($payment_method)) {
                if (!empty($this->payment_gateways[$payment_method])) {
                    $this->getOrder()->set_payment_method($payment_method);
                    $this->getOrder()->set_payment_method_title($this->payment_gateways[$payment_method]->title);
                } else {
                    $method = FALSE;
                    if (!empty($this->payment_gateways)) {
                        foreach ($this->payment_gateways as $payment_gateway_slug => $payment_gateway) {
                            if (strtolower($payment_gateway->method_title) == strtolower(trim($payment_method))) {
                                $method = $payment_method;
                                break;
                            }
                        }
                    }
                    if ($method) {
                        $this->getOrder()->set_payment_method($payment_method);
                        $this->getOrder()->set_payment_method_title($method->method_title);
                    }
                }
            } else {
                $this->getOrder()->set_payment_method('N/A');
            }
            $this->getOrder()->set_transaction_id($this->getValue('transaction_id'));
        }
    }
}
