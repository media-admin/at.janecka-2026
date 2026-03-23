<?php

namespace wpai_woocommerce_add_on\importer\orders\items;

use wpai_woocommerce_add_on\importer\orders\ImportOrderItemsBase;

/**
 * Class ImportOrderShippingItems
 * @package wpai_woocommerce_add_on\importer
 */
class ImportOrderShippingItems extends ImportOrderItemsBase {

    /**
     * @var
     */
    public $shipping_methods;

    /**
     * @var array
     */
    public $shipping_zone_methods = array();

    /**
     *  Importing shipping items.
     */
    public function import() {

        $this->shipping_methods = WC()->shipping->get_shipping_methods();

        if (class_exists('WC_Shipping_Zones')) {
            $zones = \WC_Shipping_Zones::get_zones();
            if (!empty($zones)) {
                foreach ($zones as $zone_id => $zone) {
                    if (!empty($zone['shipping_methods'])) {
                        foreach ($zone['shipping_methods'] as $method) {
                            $this->shipping_zone_methods[] = $method;
                        }
                    }
                }
            } else {
                $zone = new \WC_Shipping_Zone(0);
                $this->shipping_zone_methods = $zone->get_shipping_methods();
            }
        }
        if ($this->isNewOrder() || $this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_shipping']) {
            if (!$this->isNewOrder() && ($this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_shipping'])) {
                $previously_updated_order = get_option('wp_all_import_previously_updated_order_' . $this->getImport()->id, FALSE);
                if (empty($previously_updated_order) || $previously_updated_order != $this->getArticleData('ID')) {
                    $this->getOrder()->remove_order_items('shipping');
                    $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->wpdb->prefix}pmxi_posts WHERE import_id = %d AND post_id = %d AND unique_key LIKE %s;", $this->getImport()->id, $this->getOrderID(), '%' . $this->wpdb->esc_like('shipping-item') . '%'));
                }
            }
            $this->import_shipping_items();
        }
    }

    /**
     *  Import order shipping items.
     */
    protected function import_shipping_items() {
        $shipping_items = $this->getValue('shipping');
        if ( ! empty($shipping_items) ) {
            $total_shipping = 0;
            foreach ( $shipping_items as $shippingIndex => $shipping ) {
                if ( empty($shipping['name']) ) {
                    continue;
                }

                $method = FALSE;

                if ($this->getImport()->options['pmwi_order']['shipping'][0]['class'] == 'xpath') {
                    if ( empty($this->shipping_methods[$shipping['class']]) ) {
                        foreach ($this->shipping_methods as $shipping_method_slug => $shipping_method) {
                            if ($shipping_method_slug == str_replace(" ", "_", strtolower(trim($shipping['class']))) || $shipping_method->method_title == $shipping['class']) {
                                $method = $shipping_method;
                                break;
                            }
                        }
                    } else {
                        $method = $this->shipping_methods[$shipping['class']];
                    }

                    if ( empty($method) && !empty($this->shipping_zone_methods) ) {
                        foreach ($this->shipping_zone_methods as $shipping_zone_method) {
                            if ($shipping_zone_method->title == $shipping['class']) {
                                $method = $shipping_zone_method;
                                break;
                            }
                        }
                    }
                } else {
                    foreach ($this->shipping_methods as $shipping_method_slug => $shipping_method) {
                        if ($shipping_method_slug == str_replace(" ", "_", strtolower(trim($shipping['class']))) || $shipping_method->method_title == $shipping['class']) {
                            $method = $shipping_method;
                            break;
                        }
                    }
                }

                if ( $method ) {

                    $item_taxes = $this->calculateItemTaxes($shipping, 'shipping');

                    $shipping_method = new \WC_Shipping_Rate($method->id, $shipping['name'], $shipping['amount']);

                    $shipping_item = new \PMXI_Post_Record();
                    $shipping_item->getBy( [
                        'import_id'  => $this->getImport()->id,
                        'post_id'    => $this->getOrderID(),
                        'unique_key' => 'shipping-item-' . $shippingIndex
                    ] );

                    $total_shipping += (float)$shipping['amount'];

                    if ( $shipping_item->isEmpty() ) {

                        $item_id = FALSE;

                        if ( ! $this->isNewOrder() ) {
                            $order_items = $this->getOrder()
                                ->get_items('shipping');

                            foreach ($order_items as $order_item_id => $order_item) {
                                if ($order_item['name'] == $shipping['name']) {
                                    $item_id = $order_item_id;
                                    break;
                                }
                            }
                        }

                        if ( ! $item_id ) {

                            $item = new \WC_Order_Item_Shipping();
                            $item->set_props( [
                                'method_title' => $shipping_method->label,
                                'method_id'    => $shipping_method->id,
                                'total'        => wc_format_decimal($shipping_method->cost),
                                'taxes'        => [
                                    'total'    => $item_taxes['line_taxes'],
                                    'subtotal' => $item_taxes['line_taxes'],
                                ],
                                'order_id'     => $this->getOrderID(),
                            ] );
                            foreach ($shipping_method->get_meta_data() as $key => $value) {
                                $item->add_meta_data($key, $value, TRUE);
                            }
                            $item->save();
                            $this->getOrder()->add_item($item);
                            $item_id = $item->get_id();

                            if ( ! empty($shipping['meta_name']) ) {
                                foreach ($shipping['meta_name'] as $key => $meta_name) {
                                    wc_add_order_item_meta($item_id, $meta_name, isset($shipping['meta_value'][$key]) ? $shipping['meta_value'][$key] : '');
                                }
                            }
                        }

                        if ( ! $item_id ) {
                            $this->getLogger() && call_user_func($this->getLogger(), __('- <b>WARNING</b> Unable to create order shipping line.', 'wpai_woocommerce_addon_plugin'));
                        } else {
                            $shipping_item->set( [
                                'import_id'   => $this->getImport()->id,
                                'post_id'     => $this->getOrderID(),
                                'unique_key'  => 'shipping-item-' . $shippingIndex,
                                'product_key' => 'shipping-item-' . $item_id,
                                'iteration'   => $this->getImport()->iteration
                            ] )->save();
                        }
                    } else {
                        $item_id = str_replace('shipping-item-', '', $shipping_item->product_key);
                        $item = $this->getOrder()->get_item($item_id);
                        if (is_object($item) && $item->is_type('shipping')) {
                            $args = [
                                'method_title' => $shipping_method->label,
                                'method_id'    => $shipping_method->id,
                                'cost'         => $shipping_method->cost,
                                'taxes'        => [
                                    'total'    => $item_taxes['line_taxes'],
                                    'subtotal' => $item_taxes['line_taxes'],
                                ],
                            ];
                            $item->set_order_id($this->getOrderID());
                            $item->set_props($args);
                            $item->save();
                            //$this->getOrder()->calculate_shipping();

                            if ($item->get_id()) {
                                $shipping_item->set( [
                                  'iteration' => $this->getImport()->iteration
                                ] )->save();

                                if ( ! empty($shipping['meta_name']) ) {
                                    foreach ($shipping['meta_name'] as $key => $meta_name) {
                                        wc_update_order_item_meta($item_id, $meta_name, isset($shipping['meta_value'][$key]) ? $shipping['meta_value'][$key] : '');
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->getOrder()->set_shipping_total($total_shipping);

            //$this->_calculate_shipping_taxes();
        }
    }
}
