<?php

namespace wpai_woocommerce_add_on\importer\orders\items;

use wpai_woocommerce_add_on\importer\orders\ImportOrderItemsBase;

/**
 * Class ImportOrderCouponItems
 * @package wpai_woocommerce_add_on\importer
 */
class ImportOrderCouponItems extends ImportOrderItemsBase {

    /**
     *  Importing coupons items.
     */
    public function import() {
        if ($this->isNewOrder() || $this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_coupons']) {
            if (!$this->isNewOrder() && ($this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_coupons'])) {
                $previously_updated_order = get_option('wp_all_import_previously_updated_order_' . $this->getImport()->id, FALSE);
                if (empty($previously_updated_order) || $previously_updated_order != $this->getArticleData('ID')) {
                    $this->getOrder()->remove_order_items('coupon');
                    $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->wpdb->prefix}pmxi_posts WHERE import_id = %d AND post_id = %d AND unique_key LIKE %s;", $this->getImport()->id, $this->getOrderID(), '%' . $this->wpdb->esc_like('coupon-item') . '%'));
                }
            }
            $this->import_coupons_items();
        }
    }

    /**
     *  Import order coupon items.
     */
    protected function import_coupons_items() {
        $total_discount_amount = 0;

        $coupons = $this->getValue('coupons');
        if (!empty($coupons)) {
            foreach ($coupons as $couponIndex => $coupon) {
                if (empty($coupon['code'])) {
                    continue;
                }

                $coupon += [
                    'code'   => '',
                    'amount' => '',
                ];

                $order_item = new \PMXI_Post_Record();
                $order_item->getBy( [
                    'import_id'  => $this->getImport()->id,
                    'post_id'    => $this->getOrderID(),
                    'unique_key' => 'coupon-item-' . $couponIndex
                ] );

                $absAmount = abs((float)$coupon['amount']);

                if ( ! empty($absAmount) ) {
                    $total_discount_amount += $absAmount;
                }

                if ($order_item->isEmpty()) {
                    $item_id = FALSE;
                    if (!$this->isNewOrder()) {
                        $order_items = $this->getOrder()->get_items('coupon');
                        foreach ($order_items as $order_item_id => $order_item_coupon) {
                            if ($order_item_coupon['name'] == $coupon['code']) {
                                $item_id = $order_item_id;
                                break;
                            }
                        }
                    }

                    if ( ! $item_id ) {
                        $item = new \WC_Order_Item_Coupon();
                        $item->set_props( [
                            'code'          => $coupon['code'],
                            'discount'      => isset($coupon['amount']) ? $absAmount : 0,
                            'discount_tax'  => 0,
                            'order_id'      => $this->getOrderID(),
                        ] );
                        $item_id = $item->save();
                    }

                    if ( ! $item_id ) {
                        $this->getLogger() and call_user_func($this->getLogger(), __('- <b>WARNING</b> Unable to create order coupon line.', 'wpai_woocommerce_addon_plugin'));
                    } else {
                        $order_item->set( [
                            'import_id'   => $this->getImport()->id,
                            'post_id'     => $this->getOrderID(),
                            'unique_key'  => 'coupon-item-' . $couponIndex,
                            'product_key' => 'coupon-item-' . $item_id,
                            'iteration'   => $this->getImport()->iteration
                        ] )->save();
                    }
                } else {

                    $item_id = str_replace('coupon-item-', '', $order_item->product_key);

                    $item = new \WC_Order_Item_Coupon($item_id);

                    if (isset($coupon['code'])) {
                        $item->set_code($coupon['code']);
                    }
                    if (isset($coupon['amount'])) {
                        $item->set_discount($absAmount);
                    }
                    $is_updated = $item->save();

                    if ($is_updated) {
                        $order_item->set( [
                            'iteration' => $this->getImport()->iteration
                        ] )->save();
                    }
                }
            }
        }
        $this->getOrder()->set_discount_total($total_discount_amount);
    }
}
