<?php

namespace wpai_woocommerce_add_on\importer\orders;

use wpai_woocommerce_add_on\helpers\ImporterOptions;
use wpai_woocommerce_add_on\importer\ImporterIndex;

/**
 *
 * Import Order items
 *
 * Class ImportOrderItemsBase
 * @package wpai_woocommerce_add_on\importer
 */
abstract class ImportOrderItemsBase extends ImportOrderBase {

    /**
     * @var int
     */
    public $prices_include_tax = 0;

    /**
     * @var array
     */
    public $tax_rates = array();

    public function __construct(ImporterIndex $index, ImporterOptions $options, $order, $data = array()) {

        parent::__construct($index, $options, $order, $data);

        $this->prices_include_tax = ('yes' === get_option('woocommerce_prices_include_tax', 'no'));

        $tax_classes = \WC_Tax::get_tax_classes();

        if ($tax_classes) {
            // Add Standard tax class
            if (!in_array('', $tax_classes)) {
                $tax_classes[] = '';
            }

            foreach ($tax_classes as $class) {
                foreach (\WC_Tax::get_rates_for_tax_class(sanitize_title($class)) as $rate_key => $rate) {
                    $this->tax_rates[$rate->tax_rate_id] = $rate;
                }
            }
        }
    }

    protected function calculateItemTaxes($item, $type) {
        $item_taxes = [
            'item_tax_class' => '',
            'item_subtotal_tax' => 0,
            'line_taxes' => [],
        ];
        if (!empty($item['tax_rates'])) {
            foreach ($item['tax_rates'] as $key => $tax_rate) {
                if (empty($tax_rate['code'])) {
                    continue;
                }

				// Trim values.
	            $tax_rate = array_map('trim', $tax_rate);

                $taxes_delimiter = $this->getImport()->options['pmwi_order'][$type . '_repeater_mode_item_separator'] ?? '#';
                $codes = explode($taxes_delimiter, $tax_rate['code']);
                if (!empty($codes)) {
                    $amounts = explode($taxes_delimiter, $tax_rate['amount_per_unit']);
                    foreach ($codes as $i => $code) {
                        $tax_class = FALSE;
                        if (!empty($this->tax_rates[$code])) {
                            $tax_class = $this->tax_rates[$code];
                        } else {
                            foreach ($this->tax_rates as $rate_id => $rate) {
                                if (strtolower($rate->tax_rate_name) == strtolower($code) || strtolower($rate->tax_rate_class) == strtolower($code)) {
                                    $tax_class = $rate;
                                    break;
                                }
                            }
                        }
                        if (!empty($amounts[$i])) {
                            $line_tax = \WC_Tax::round($amounts[$i]);
                            $item_taxes['item_subtotal_tax'] += $line_tax;
                            if ($tax_class) {
                                $item_taxes['item_tax_class'] = $tax_class->tax_rate_class;
                                $item_taxes['line_taxes'][$tax_class->tax_rate_id] = $line_tax;
                            }
                        }
                    }
                }
            }
        }
        return $item_taxes;
    }

    protected function getTaxClassFromTaxData($item_taxes) {
        $tax_class = '';
        if (!empty($item_taxes['line_taxes'])) {
            $rate_ids = array_keys($item_taxes['line_taxes']);
            if (!empty($rate_ids)) {
                $rate_id = array_shift($rate_ids);
                $rate = \WC_Tax::_get_tax_rate($rate_id);
                if (!empty($rate)) {
                    $tax_class = $rate['tax_rate_class'];
                }
            }
        }
        return $tax_class;
    }

    /**
     * @return bool
     */
    protected function _calculate_fee_taxes() {

        $tax_total = 0;
        $shipping_tax_total = 0;
        $taxes = array();
        $shipping_taxes = array();
        $tax_based_on = get_option('woocommerce_tax_based_on');

        // If is_vat_exempt is 'yes', or wc_tax_enabled is false, return and do nothing.
        if (!wc_tax_enabled()) {
            return FALSE;
        }

        $order = $this->getOrder();

        if ('billing' === $tax_based_on) {
            $country = $order->get_billing_country();
            $state = $order->get_billing_state();
            $postcode = $order->get_billing_postcode();
            $city = $order->get_billing_city();
        } elseif ('shipping' === $tax_based_on) {
            $country = $order->get_shipping_country();
            $state = $order->get_shipping_state();
            $postcode = $order->get_shipping_postcode();
            $city = $order->get_shipping_city();
        }

        // Default to base
        if ('base' === $tax_based_on || empty($country)) {
            $default = wc_get_base_location();
            $country = $default['country'];
            $state = $default['state'];
            $postcode = '';
            $city = '';
        }

        // Get items
        foreach ($order->get_items(array('fee')) as $item_id => $item) {

            $line_total = isset($item['line_total']) ? $item['line_total'] : 0;
            $line_subtotal = isset($item['line_subtotal']) ? $item['line_subtotal'] : 0;
            $tax_class = $item['tax_class'];
            $item_tax_status = $item->get_tax_status();

            if ('0' !== $tax_class && 'taxable' === $item_tax_status) {

                $tax_rates = \WC_Tax::find_rates(array(
                    'country' => $country,
                    'state' => $state,
                    'postcode' => $postcode,
                    'city' => $city,
                    'tax_class' => $tax_class
                ));

                $line_subtotal_taxes = \WC_Tax::calc_tax($line_subtotal, $tax_rates, FALSE);
                $line_taxes = \WC_Tax::calc_tax($line_total, $tax_rates, FALSE);
                $line_subtotal_tax = max(0, array_sum($line_subtotal_taxes));
                $line_tax = max(0, array_sum($line_taxes));
                $tax_total += $line_tax;

                wc_update_order_item_meta($item_id, '_line_subtotal_tax', wc_format_decimal($line_subtotal_tax));
                wc_update_order_item_meta($item_id, '_line_tax', wc_format_decimal($line_tax));
                wc_update_order_item_meta($item_id, '_line_tax_data', array(
                    'total' => $line_taxes,
                    'subtotal' => $line_subtotal_taxes
                ));

                // Sum the item taxes
                foreach (array_keys($taxes + $line_taxes) as $key) {
                    $taxes[$key] = (isset($line_taxes[$key]) ? $line_taxes[$key] : 0) + (isset($taxes[$key]) ? $taxes[$key] : 0);
                }
            }
        }
    }

    protected function _calculate_shipping_taxes() {

        $tax_total = 0;
        $shipping_tax_total = 0;
        $taxes = array();
        $shipping_taxes = array();
        $tax_based_on = get_option('woocommerce_tax_based_on');

        // If is_vat_exempt is 'yes', or wc_tax_enabled is false, return and do nothing.
        if (!wc_tax_enabled()) {
            return FALSE;
        }

        $order = $this->getOrder();

        if ('billing' === $tax_based_on) {
            $country = $order->get_billing_country();
            $state = $order->get_billing_state();
            $postcode = $order->get_billing_postcode();
            $city = $order->get_billing_city();
        } elseif ('shipping' === $tax_based_on) {
            $country = $order->get_shipping_country();
            $state = $order->get_shipping_state();
            $postcode = $order->get_billing_postcode();
            $city = $order->get_shipping_city();
        }

        // Calc taxes for shipping
        foreach ($order->get_shipping_methods() as $item_id => $item) {

            $shipping_tax_class = get_option('woocommerce_shipping_tax_class');

            // Inherit tax class from items
            if ('' === $shipping_tax_class) {
                $tax_classes = \WC_Tax::get_tax_classes();
                $found_tax_classes = $order->get_items_tax_classes();

                foreach ($tax_classes as $tax_class) {
                    $tax_class = sanitize_title($tax_class);
                    if (in_array($tax_class, $found_tax_classes)) {
                        $tax_rates = \WC_Tax::find_shipping_rates(array(
                            'country' => $country,
                            'state' => $state,
                            'postcode' => $postcode,
                            'city' => $city,
                            'tax_class' => $tax_class,
                        ));
                        break;
                    }
                }
            } else {
                $tax_rates = \WC_Tax::find_shipping_rates(array(
                    'country' => $country,
                    'state' => $state,
                    'postcode' => $postcode,
                    'city' => $city,
                    'tax_class' => 'standard' === $shipping_tax_class ? '' : $shipping_tax_class,
                ));
            }

            $line_taxes = \WC_Tax::calc_tax($item['cost'], $tax_rates, FALSE);
            $line_tax = max(0, array_sum($line_taxes));
            $shipping_tax_total += $line_tax;

            wc_update_order_item_meta($item_id, '_line_tax', wc_format_decimal($line_tax));
            wc_update_order_item_meta($item_id, '_line_tax_data', array('total' => $line_taxes));

            // Sum the item taxes
            foreach (array_keys($shipping_taxes + $line_taxes) as $key) {
                $shipping_taxes[$key] = (isset($line_taxes[$key]) ? $line_taxes[$key] : 0) + (isset($shipping_taxes[$key]) ? $shipping_taxes[$key] : 0);
            }
            wc_update_order_item_meta($item_id, 'taxes', $shipping_taxes);
        }
        // Save tax totals
        $order->set_shipping_tax($shipping_tax_total);
    }
}
