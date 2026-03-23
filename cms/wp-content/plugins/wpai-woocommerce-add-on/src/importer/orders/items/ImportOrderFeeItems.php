<?php

namespace wpai_woocommerce_add_on\importer\orders\items;

use wpai_woocommerce_add_on\importer\orders\ImportOrderItemsBase;

/**
 * Class ImportOrderFeeItems
 * @package wpai_woocommerce_add_on\importer
 */
class ImportOrderFeeItems extends ImportOrderItemsBase {

    /**
     *  Importing fee items
     */
    public function import() {
        if ($this->isNewOrder() || $this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_fees']) {
            if (!$this->isNewOrder() && ($this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_fees'])) {
                $previously_updated_order = get_option('wp_all_import_previously_updated_order_' . $this->getImport()->id, FALSE);
                if (empty($previously_updated_order) || $previously_updated_order != $this->getArticleData('ID')) {
                    $this->getOrder()->remove_order_items('fee');
                    $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->wpdb->prefix}pmxi_posts WHERE import_id = %d AND post_id = %d AND unique_key LIKE %s;", $this->getImport()->id, $this->getOrderID(), '%' . $this->wpdb->esc_like('fee-item') . '%'));
                }
            }
            $this->import_fee_items();
        }
    }

    /**
     *  Import order fee items
     */
    protected function import_fee_items() {

        $fees = $this->getValue('fees');

        if ( ! empty( $fees ) ) {

            foreach ( $fees as $feeIndex => $fee ) {

                if ( empty( $fee['name'] ) ) {
                    continue;
                }

                $item_taxes = $this->calculateItemTaxes($fee, 'fee');

                $tax_class = $this->getTaxClassFromTaxData($item_taxes);

                $fee_item = new \PMXI_Post_Record();
                $fee_item->getBy( [
                    'import_id' => $this->getImport()->id,
                    'post_id' => $this->getOrderID(),
                    'unique_key' => 'fee-item-' . $feeIndex
                ] );

                if ( $fee_item->isEmpty() ) {
                    $item_id = FALSE;
                    if ( ! $this->isNewOrder() ) {
                        $order_items = $this->getOrder()->get_items('fee');
                        foreach ( $order_items as $order_item_id => $order_item ) {
                            if ( $order_item['name'] == $fee['name'] ) {
                                $item_id = $order_item_id;
                                break;
                            }
                        }
                    }
                    if ( ! $item_id ) {
                        $item = new \WC_Order_Item_Fee();
                        $item->set_props( [
                            'name'      => wc_clean($fee['name']),
                            'tax_class' => $tax_class,
                            'total'     => isset($fee['amount']) ? floatval($fee['amount']) : 0,
                            'taxes'     => [
                                'total'    => $item_taxes['line_taxes'],
                                'subtotal' => $item_taxes['line_taxes'],
                            ],
                            'order_id'  => $this->getOrderID(),
                        ] );
                        if ( ! empty($tax_class) ) {
                            $item->set_tax_status('taxable');
                        }
                        $item_id = $item->save();
                        $this->getOrder()->add_item($item);
                    }

                    if ( ! $item_id ) {
                        $this->getLogger() and call_user_func($this->getLogger(), __('- <b>WARNING</b> order line fee is not added.', 'wpai_woocommerce_addon_plugin'));
                    } else {
                        $fee_item->set( [
                            'import_id'   => $this->getImport()->id,
                            'post_id'     => $this->getOrderID(),
                            'unique_key'  => 'fee-item-' . $feeIndex,
                            'product_key' => 'fee-item-' . $item_id,
                            'iteration'   => $this->getImport()->iteration
                        ] )->save();
                    }

                } else {

                    $item_id = str_replace('fee-item-', '', $fee_item->product_key);

                    $item = new \WC_Order_Item_Fee($item_id);
                    $item->set_name(wc_clean($fee['name']));
                    $item->set_total(floatval($fee['amount']));
                    $item->set_tax_class($tax_class);
                    $item->set_tax_status('taxable');
                    $item->set_taxes( [
                        'total'    => $item_taxes['line_taxes'],
                        'subtotal' => $item_taxes['line_taxes'],
                    ] );
                    $is_updated = $item->save();

                    if ( $is_updated ) {
                        $fee_item->set( [
                            'iteration' => $this->getImport()->iteration
                        ] )->save();
                    }
                }
            }
        }
    }
}
