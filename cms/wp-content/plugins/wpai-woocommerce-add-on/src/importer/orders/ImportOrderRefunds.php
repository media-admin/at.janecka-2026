<?php

namespace wpai_woocommerce_add_on\importer\orders;

/**
 *
 * Import Order Refunds
 *
 * Class ImportOrderRefunds
 * @package wpai_woocommerce_add_on\importer
 */
class ImportOrderRefunds extends ImportOrderBase {

    /**
     * @throws \Exception
     */
    public function import() {

        if ($this->isNewOrder() || $this->getImport()->options['update_all_data'] == 'yes' || $this->getImport()->options['is_update_refunds']) {

            $refunds = $this->getValue('refunds');

            if ( ! empty( $refunds ) ) {

                foreach ($refunds as $refundIndex => $refund) {

                    $refund += [
                        'partial_date'   => '',
                        'partial_reason' => '',
                        'full_date'      => '',
                        'full_reason'    => '',
                        'order_item_name'    => '',
                        'item_refund_quantity'    => '',
                        'item_refund_amount'    => '',
                        'item_refund_tax_amount'    => '',
                        'shipping_name'    => '',
                        'shipping_refund_amount'    => '',
                        'shipping_refund_tax_amount'    => '',
                        'fee_name'    => '',
                        'fee_refund_amount'    => '',
                        'fee_refund_tax_amount'    => '',
	                    'total_refund_amount' => '',
                        'partial_amount' => '',
                        'full_amount' => '',
                    ];

                    if ($this->getImport()->options['pmwi_order']['order_refund_type'] == 'partial') {
                        $refund['date'] = $refund['partial_date'];
                        $refund['reason'] = $refund['partial_reason'];
                        $refund['total_refund_amount'] = abs((float)$refund['partial_amount']);
                    } else {
                        $refund['date'] = $refund['full_date'];
                        $refund['reason'] = $refund['full_reason'];
                        $refund['total_refund_amount'] = abs((float)$refund['full_amount']);
                    }

                    // Only import refunds if a total amount was provided.
                    if (empty($refund['total_refund_amount'])) {
                        continue;
                    }

                    if (!empty($this->getImport()->options['do_not_send_order_notifications'])) {
                        remove_all_actions('woocommerce_order_partially_refunded');
                        remove_all_actions('woocommerce_order_fully_refunded');
                        remove_all_actions('woocommerce_order_status_refunded_notification');
                        remove_all_actions('woocommerce_order_partially_refunded_notification');
                        remove_action('woocommerce_order_status_refunded', array(
                            'WC_Emails',
                            'send_transactional_email'
                        ));
                        remove_action('woocommerce_order_partially_refunded', array(
                            'WC_Emails',
                            'send_transactional_email'
                        ));
                    }

                    $refund_item = new \PMXI_Post_Record();
                    $refund_item->getBy( [
                        'import_id'  => $this->getImport()->id,
                        'post_id'    => $this->getOrderID(),
                        'unique_key' => 'refund-item-' . $refundIndex
                    ] );

                    $args = array(
                        'amount' => $refund['total_refund_amount'],
                        'reason' => $refund['reason'],
                        'order_id' => $this->getOrderID(),
                        'refund_id' => 0,
                        'line_items' => array(),
                        'date_created' => $refund['date']
                    );

                    if ($this->getImport()->options['pmwi_order']['order_refund_type'] == 'full') {
//                        $line_items = $this->getOrder()->get_items( array( 'line_item', 'fee', 'shipping' ) );
//                        if (!empty($line_items)) {
//                            foreach ($line_items as $line_item_id => $line_item) {
//                                $args['line_items'][$line_item_id] = [
//                                    'qty'          => 0,
//                                    'refund_total' => 0,
//                                    'refund_tax'   => array(),
//                                ];
//                                $args['amount'] += $line_item->get_total();
//                            }
//                        }
//                        $args['amount'] = wc_format_decimal( $this->getOrder()->get_total() - $this->getOrder()->get_total_refunded() );

                    } else {


                        $separator = $this->getImport()->options['pmwi_order']['refunds_repeater_mode_item_separator'];
                        $tax_separator = $this->getImport()->options['pmwi_order']['refunds_repeater_mode_tax_separator'];

                        if (!empty($refund['order_item_name'])) {
                            $items = $this->getOrder()->get_items( array( 'line_item' ) );
                            $order_item_names = explode($separator, $refund['order_item_name']);
                            $item_refund_quantities = explode($separator, $refund['item_refund_quantity']);
                            $item_refund_amounts = explode($separator, $refund['item_refund_amount']);
                            $item_refund_tax_amounts = explode($separator, $refund['item_refund_tax_amount']);
                            foreach ($order_item_names as $j => $order_item_name) {
                                foreach ($items as $line_item) {
                                    $line_item_product = $line_item->get_product();
                                    if ($line_item->get_name() === trim($order_item_name) || $line_item_product && $line_item_product->get_id() === trim($order_item_name) || $line_item_product && $line_item_product->get_sku() === trim($order_item_name)) {
                                        $refund_tax = [];
                                        if (!empty($item_refund_tax_amounts[$j])) {
                                            $multiple_tax_amounts = explode($tax_separator, $item_refund_tax_amounts[$j]);
                                            $taxes = $line_item->get_taxes();
                                            if (!empty($taxes['total'])) {
                                                $loop = 0;
                                                foreach ($taxes['total'] as $tax_id => $tax_amount) {
                                                    if (!empty($multiple_tax_amounts[$loop])) {
                                                        $refund_tax[$tax_id] = abs((float)$multiple_tax_amounts[$loop]);
                                                        //$args['amount'] += abs($multiple_tax_amounts[$loop]);
                                                    }
                                                    $loop++;
                                                }
                                            }
                                        }
                                        $args['line_items'][$line_item->get_id()] = [
                                            'qty'          => isset($item_refund_quantities[$j]) ? abs((int)$item_refund_quantities[$j]) : 0,
                                            'refund_total' => isset($item_refund_amounts[$j]) ? abs((float)$item_refund_amounts[$j]) : 0,
                                            'refund_tax'   => $refund_tax,
                                        ];
                                        //$args['amount'] += $args['line_items'][$line_item->get_id()]['refund_total'];
                                        break;
                                    }
                                }
                            }
                        }

                        if (!empty($refund['shipping_name'])) {
                            $items = $this->getOrder()->get_items( array( 'shipping' ) );
                            $shipping_names = explode($separator, $refund['shipping_name']);
                            $shipping_refund_amounts = explode($separator, $refund['shipping_refund_amount']);
                            $shipping_refund_tax_amounts = explode($separator, $refund['shipping_refund_tax_amount']);
                            foreach ($shipping_names as $j => $shipping_name) {
                                foreach ($items as $line_item) {
                                    if ($line_item->get_name() === trim($shipping_name) || $line_item->get_method_title() === trim($shipping_name)|| $line_item->get_method_id() === trim($shipping_name)) {
                                        $refund_tax = [];
                                        if (!empty($shipping_refund_tax_amounts[$j])) {
                                            $multiple_tax_amounts = explode($tax_separator, $shipping_refund_tax_amounts[$j]);
                                            $taxes = $line_item->get_taxes();
                                            if (!empty($taxes['total'])) {
                                                $loop = 0;
                                                foreach ($taxes['total'] as $tax_id => $tax_amount) {
                                                    if (!empty($multiple_tax_amounts[$loop])) {
                                                        $refund_tax[$tax_id] = abs((float)$multiple_tax_amounts[$loop]);
                                                        //$args['amount'] += abs($multiple_tax_amounts[$loop]);
                                                    }
                                                    $loop++;
                                                }
                                            }
                                        }
                                        $args['line_items'][$line_item->get_id()] = [
                                            'refund_total' => isset($shipping_refund_amounts[$j]) ? abs((float)$shipping_refund_amounts[$j]) : 0,
                                            'refund_tax'   => $refund_tax,
                                        ];
                                        //$args['amount'] += $args['line_items'][$line_item->get_id()]['refund_total'];
                                        break;
                                    }
                                }
                            }
                        }

                        if (!empty($refund['fee_name'])) {
                            $items = $this->getOrder()->get_items( array( 'fee' ) );
                            $fee_names = explode($separator, $refund['fee_name']);
                            $fee_refund_amounts = explode($separator, $refund['fee_refund_amount']);
                            $fee_refund_tax_amounts = explode($separator, $refund['fee_refund_tax_amount']);
                            foreach ($fee_names as $j => $fee_name) {
                                foreach ($items as $line_item) {
                                    if ($line_item->get_name() === trim($fee_name) ) {
                                        $refund_tax = [];
                                        if (!empty($fee_refund_tax_amounts[$j])) {
                                            $multiple_tax_amounts = explode($tax_separator, $fee_refund_tax_amounts[$j]);
                                            $taxes = $line_item->get_taxes();
                                            if (!empty($taxes['total'])) {
                                                $loop = 0;
                                                foreach ($taxes['total'] as $tax_id => $tax_amount) {
                                                    if (!empty($multiple_tax_amounts[$loop])) {
                                                        $refund_tax[$tax_id] = abs((float)$multiple_tax_amounts[$loop]);
                                                        //$args['amount'] += abs($multiple_tax_amounts[$loop]);
                                                    }
                                                    $loop++;
                                                }
                                            }
                                        }
                                        $args['line_items'][$line_item->get_id()] = [
                                            'refund_total' => isset($fee_refund_amounts[$j]) ? abs((float)$fee_refund_amounts[$j]) : 0,
                                            'refund_tax'   => $refund_tax,
                                        ];
                                        //$args['amount'] += $args['line_items'][$line_item->get_id()]['refund_total'];
                                        break;
                                    }
                                }
                            }
                        }

                    }

	                if (!$refund_item->isEmpty()) {
		                // Only use the refund ID reference if it's valid.
		                $tmp_refund_id = str_replace('refund-item-', '', $refund_item->product_key);
		                $orderRefund = wc_get_order( $tmp_refund_id );
		                if ( $orderRefund && 'shop_order_refund' === $orderRefund->get_type() ) {
			                $args['refund_id'] = $tmp_refund_id;
		                }
	                }

					if( !isset($orderRefund) || !$orderRefund ) {
						$orderRefund = \wc_create_refund( $args );
					}

                    if (is_wp_error($orderRefund)) {
                        $this->getLogger() and call_user_func($this->getLogger(), sprintf(__('- <b>ERROR</b> %s', 'wpai_woocommerce_addon_plugin'), $orderRefund->get_error_message()));
                    }

                    if ($orderRefund instanceOf \WC_Order_Refund) {

                        $refund_item->set(array(
                            'import_id' => $this->getImport()->id,
                            'post_id' => $this->getOrderID(),
                            'unique_key' => 'refund-item-' . $refundIndex,
                            'product_key' => 'refund-item-' . $orderRefund->get_id(),
                            'iteration' => $this->getImport()->iteration
                        ))->save();

                        $customer = FALSE;
                        if ($this->getImport()->options['pmwi_order']['order_refund_issued_source'] == 'existing') {
                            $customer = $this->getParser()->get_existing_customer('order_refund_issued', $this->getIndex());
                        }

                        $post_author = $customer ? $customer->ID : 0;

                        wp_update_post(array(
                            'ID' => $orderRefund->get_id(),
                            'post_author' => $post_author
                        ));
                        $orderRefund->set_refunded_by($post_author);
                        $orderRefund->save();

                    }
                }
            }
        }
    }
}
