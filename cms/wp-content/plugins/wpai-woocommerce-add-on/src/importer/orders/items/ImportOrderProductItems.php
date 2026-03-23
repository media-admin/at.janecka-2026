<?php

namespace wpai_woocommerce_add_on\importer\orders\items;

use wpai_woocommerce_add_on\importer\orders\ImportOrderItemsBase;
use wpai_woocommerce_add_on\XmlImportWooCommerceService;

/**
 * Class ImportOrderProductItems
 * @package wpai_woocommerce_add_on\importer
 */
class ImportOrderProductItems extends ImportOrderItemsBase {

    /**
     *  Importing fee items
     */
    public function import() {

        if ($this->isNewOrder() || $this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_products']) {
            if (!$this->isNewOrder() && ($this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_products'] && $this->getImport()->options['update_products_logic'] == 'full_update')) {
                $previously_updated_order = get_option('wp_all_import_previously_updated_order_' . $this->getImport()->id, FALSE);
                if (empty($previously_updated_order) || $previously_updated_order != $this->getArticleData('ID')) {
                    $this->getOrder()->remove_order_items('line_item');
                    $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->wpdb->prefix}pmxi_posts WHERE import_id = %d AND post_id = %d AND unique_key LIKE %s;", $this->getImport()->id, $this->getOrderID(), '%' . $this->wpdb->esc_like('line-item') . '%'));
                }
            }
            $this->import_line_items();
        }
    }

    /**
     * @return bool
     */
    protected function import_line_items() {

        $is_product_founded = FALSE;

        foreach ($this->getValue('product_items') as $productIndex => $productItem) {

			// Trim whitespace so that further checks work properly.
	        $productItem['qty'] = isset($productItem['qty']) ? trim($productItem['qty']) : '';
	        $productItem['sku'] = isset($productItem['sku']) ? trim($productItem['sku']) : '';
	        $productItem['unique_key'] = isset($productItem['unique_key']) ? trim($productItem['unique_key']) : '';

            if ( (empty($productItem['qty']) || empty($productItem['sku'])) && empty($productItem['unique_key'])) {
                $this->getLogger() and call_user_func($this->getLogger(), __('- <b>WARNING</b> Product item skipped because quantity or SKU and unique key are empty.', 'wpai_woocommerce_addon_plugin'));
                continue;
            }
            $productItem['sku'] = empty($productItem['sku']) ? '' : trim($productItem['sku']);
            $productItem['unique_key'] = empty($productItem['unique_key']) ? '' : trim($productItem['unique_key']);
            $productItem['price_per_unit'] = empty($productItem['price_per_unit']) ? 0 : trim($productItem['price_per_unit']);
            $productItem['qty'] = empty($productItem['qty']) ? 0 : trim($productItem['qty']);

			// Provide int values if empty.
            $productItem['price_per_unit'] = empty($productItem['price_per_unit']) ? 0 : trim($productItem['price_per_unit']);
            $productItem['qty'] = empty($productItem['qty']) ? 0 : trim($productItem['qty']);

            $product = FALSE;
            if ( ! empty($productItem['sku']) ) {
                $product = XmlImportWooCommerceService::getProductByIdentifier($productItem['sku']);
            }

            if ( $product ) {

                $is_product_founded = TRUE;
                $item_price = empty($productItem['price_per_unit']) ? $product->get_price() : $productItem['price_per_unit'];
                $item_qty = $productItem['qty'];
                $item_subtotal = (float) $item_price * (int) $item_qty;
                $item_taxes = $this->calculateItemTaxes($productItem, 'products');

                $variation = [];

                $variation_str = '';

                if ( $product instanceOf \WC_Product_Variation ) {
                    $variation = $product->get_variation_attributes();
                    if ( ! empty($variation) ) {
                        foreach ($variation as $key => $value) {
                            $variation_str .= $key . '-' . $value;
                        }
                    }
                }

                $product_item_unique_key = 'line-item-' . $product->get_id() . '-' . $variation_str;
                if ( ! empty($productItem['unique_key'] )) {
                    $product_item_unique_key .= $productItem['unique_key'];
                }

                $product_item_unique_key = apply_filters('wp_all_import_order_item_unique_key', $product_item_unique_key, $product, $this->getOrder());

                $product_item = new \PMXI_Post_Record();
                $product_item->getBy([
                    'import_id'  => $this->getImport()->id,
                    'post_id'    => $this->getOrderID(),
                    'unique_key' => $product_item_unique_key
                ]);

                $item_args = [
                    'order_id'     => $this->getOrderID(),
                    'variation'    => $variation,
                    'quantity'     => $item_qty,
                    'total'        => $item_subtotal,
                    'subtotal'     => $item_subtotal,
                    'tax_class'    => $item_taxes['item_tax_class'],
                    'subtotal_tax' => $item_taxes['item_subtotal_tax'],
                    'total_tax'    => $item_taxes['item_subtotal_tax'],
                    'taxes'        => [
                        'total'    => $item_taxes['line_taxes'],
                        'subtotal' => $item_taxes['line_taxes'],
                    ]
                ];

                if ( $product_item->isEmpty() ) {
                    // In case when this is new order just add new line items.
                    $item = new \WC_Order_Item_Product();
                    $item->set_product($product);
                    $item->set_props($item_args);
                    $item_id = $item->save();

                    if ( ! $item_id ) {
                        $this->getLogger() and call_user_func($this->getLogger(), __('- <b>WARNING</b> Unable to create order line product.', 'wpai_woocommerce_addon_plugin'));
                    } else {
                        $product_item->set([
                            'import_id' => $this->getImport()->id,
                            'post_id' => $this->getOrderID(),
                            'unique_key' => $product_item_unique_key,
                            'product_key' => 'line-item-' . $item_id,
                            'iteration' => $this->getImport()->iteration
                        ])->save();

                        $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('Order item `%s` has been added for order `%s`', 'wpai_woocommerce_addon_plugin'), $productItem['unique_key'], $this->getOrderID()));

                        if ( ! empty($productItem['meta_name']) ) {
                            foreach ($productItem['meta_name'] as $key => $meta_name) {
                                wc_add_order_item_meta($item_id, $meta_name, isset($productItem['meta_value'][$key]) ? $productItem['meta_value'][$key] : '');
                            }
                        }
                    }
                } else {
                    $item_id = str_replace('line-item-', '', $product_item->product_key);

                    $item = new \WC_Order_Item_Product($item_id);
                    $item->set_product($product);
                    $item->set_props($item_args);
                    $is_updated = $item->save();

                    if ( $is_updated ) {
                        $product_item->set([
                            'iteration' => $this->getImport()->iteration
                        ])->save();

                        $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('Order item `%s` has been updated for order `%s`', 'wpai_woocommerce_addon_plugin'), $productItem['unique_key'], $this->getOrderID()));

                        if ( ! empty($productItem['meta_name']) ) {
                            foreach ($productItem['meta_name'] as $key => $meta_name) {
                                wc_update_order_item_meta($item_id, $meta_name, isset($productItem['meta_value'][$key]) ? $productItem['meta_value'][$key] : '');
                            }
                        }
                    }
                }
            } else {

                $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('- <b>WARNING</b> Could not link order item with identifier `%s` to existing product.', 'wpai_woocommerce_addon_plugin'), $productItem['sku']));

                $product_item_name = '';
                if (!empty($productItem['unique_key'])) {
                    $product_item_name = $productItem['unique_key'];
                } elseif (!empty($productItem['sku'])) {
                    $product_item_name = $productItem['sku'];
                }

                $product_item_unique_key = 'manual-line-item-' . $productIndex . '-' . $product_item_name;

                $product_item = new \PMXI_Post_Record();
                $product_item->getBy([
                    'import_id' => $this->getImport()->id,
                    'post_id' => $this->getOrderID(),
                    'unique_key' => $product_item_unique_key
                ]);

                $item_price = empty($productItem['price_per_unit']) ? 0 : $productItem['price_per_unit'];
                $item_qty = $productItem['qty'];
                $item_subtotal = (float) $item_price * (int) $item_qty;
                $item_taxes = $this->calculateItemTaxes($productItem, 'products');

                if ($product_item->isEmpty()) {

                    $item_id = wc_add_order_item($this->getOrderID(), [
                        'order_item_name' => $product_item_name,
                        'order_item_type' => 'line_item'
                    ]);

                    if (!$item_id) {
                        $this->getLogger() and call_user_func($this->getLogger(), __('- <b>WARNING</b> Unable to create order line product.', 'wpai_woocommerce_addon_plugin'));
                    } else {
                        wc_add_order_item_meta($item_id, '_qty', wc_stock_amount($item_qty));
                        wc_add_order_item_meta($item_id, '_tax_class', $item_taxes['item_tax_class']);

                        wc_add_order_item_meta($item_id, '_line_subtotal', wc_format_decimal($item_subtotal));
                        wc_add_order_item_meta($item_id, '_line_total', wc_format_decimal($item_subtotal));
                        wc_add_order_item_meta($item_id, '_line_subtotal_tax', wc_format_decimal($item_taxes['item_subtotal_tax']));
                        wc_add_order_item_meta($item_id, '_line_tax', wc_format_decimal($item_taxes['item_subtotal_tax']));
                        wc_add_order_item_meta($item_id, '_line_tax_data', [
                            'total'    => $item_taxes['line_taxes'],
                            'subtotal' => $item_taxes['line_taxes']
                        ]);

                        if ( ! empty($productItem['meta_name']) ) {
                            foreach ($productItem['meta_name'] as $key => $meta_name) {
                                wc_add_order_item_meta($item_id, $meta_name, isset($productItem['meta_value'][$key]) ? $productItem['meta_value'][$key] : '');
                            }
                        }

                        $product_item->set([
                            'import_id' => $this->getImport()->id,
                            'post_id' => $this->getOrderID(),
                            'unique_key' => $product_item_unique_key,
                            'product_key' => 'manual-line-item-' . $item_id,
                            'iteration' => $this->getImport()->iteration
                        ])->save();

                        $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('Order item `%s` has been added for order `%s`', 'wpai_woocommerce_addon_plugin'), $productItem['unique_key'], $this->getOrderID()));
                    }

                } else {

                    $item_id = str_replace('manual-line-item-', '', $product_item->product_key);

                    if (is_numeric($item_id)) {

                        wc_update_order_item($item_id, [
                            'order_item_name' => $product_item_name,
                            'order_item_type' => 'line_item'
                        ]);

                        wc_update_order_item_meta($item_id, '_qty', wc_stock_amount($item_qty));
                        wc_update_order_item_meta($item_id, '_tax_class', '');

                        wc_update_order_item_meta($item_id, '_line_subtotal', wc_format_decimal($item_subtotal));
                        wc_update_order_item_meta($item_id, '_line_total', wc_format_decimal($item_subtotal));
                        wc_update_order_item_meta($item_id, '_line_subtotal_tax', wc_format_decimal($item_taxes['item_subtotal_tax']));
                        wc_update_order_item_meta($item_id, '_line_tax', wc_format_decimal($item_taxes['item_subtotal_tax']));
                        wc_update_order_item_meta($item_id, '_line_tax_data', [
                            'total'    => $item_taxes['line_taxes'],
                            'subtotal' => $item_taxes['line_taxes']
                        ]);

                        if ( ! empty($productItem['meta_name']) ) {
                            foreach ($productItem['meta_name'] as $key => $meta_name) {
                                wc_update_order_item_meta($item_id, $meta_name, isset($productItem['meta_value'][$key]) ? $productItem['meta_value'][$key] : '');
                            }
                        }
                        $product_item->set([
                            'iteration' => $this->getImport()->iteration
                        ])->save();

                        $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('Order item `%s` has been updated for order `%s`', 'wpai_woocommerce_addon_plugin'), $productItem['unique_key'], $this->getOrderID()));
                    }
                }
            }
        }

        return $is_product_founded;
    }

}
