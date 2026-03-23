<?php

namespace wpai_woocommerce_add_on\parser;

/**
 * Class OrdersParser
 * @package wpai_woocommerce_add_on\parser
 */
class OrdersParser extends Parser {

    /**
     * Get complete XPath expression for parser factory.
     *
     * @return string
     */
    public function getCompleteXPath() {
        return $this->getXpath() . $this->getImport()->xpath;
    }

    /**
     * @param $option
     * @param $index
     * @return mixed
     */
    public function getValue($option, $index) {
        return $this->data['pmwi_order'][$option][$index];
    }

    /**
     *
     * Parse WooCommerce Order Import Template
     *
     * @return array
     * @throws \XmlImportException
     */
    public function parse() {

        $this->data = [];

        $this->getChunk() == 1 and $this->log(__('Composing shop order data...', 'wpai_woocommerce_addon_plugin'));

        $default = \PMWI_Plugin::get_default_import_options();

        foreach ($default['pmwi_order'] as $option => $default_value) {
            if (in_array($option, [
                'status_xpath',
                'payment_method_xpath',
                'order_note_visibility_xpath',
                'billing_source',
                'billing_source_match_by',
                'shipping_source',
                'products_source',
                'order_taxes_logic',
                'order_refund_issued_source',
                'order_refund_issued_match_by',
                'order_total_logic',
                'order_note_separate_logic',
                'order_note_separator',
                'is_guest_matching',
                'copy_from_billing'
            ]) or strpos($option, 'is_update_') !== FALSE or strpos($option, '_repeater_mode') !== FALSE
            ) {
                continue;
            }

            switch ($option) {
                case 'date':
                case 'order_refund_date':
                case 'payment_date':

                    if (!empty($this->getImport()->options['pmwi_order'][$option])) {
                        $dates = \XmlImportParser::factory($this->getXml(), $this->getCompleteXPath(), $this->getImport()->options['pmwi_order'][$option], $file)
                            ->parse();
                        $this->tmp_files[] = $file;

                        foreach ($dates as $i => $d) {
                            if ($d == 'now') {
                                $d = current_time('mysql');
                            } // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
                            $time = strtotime($d);
                            if (FALSE === $time) {
                                $time = time();
                            }
                            $this->data['pmwi_order'][$option][$i] = date('Y-m-d H:i:s', $time);
                        }
                    } else {
                        $this->getCount() and $this->data['pmwi_order'][$option] = array_fill(0, $this->getCount(), date('Y-m-d H:i:s'));
                    }

                    break;

                case 'status':
                case 'payment_method':
                case 'order_note_visibility':

                    if ($this->getImport()->options['pmwi_order'][$option] == 'xpath' && $this->getImport()->options['pmwi_order'][$option . '_xpath'] != "") {
                        $this->data['pmwi_order'][$option] = \XmlImportParser::factory($this->getXml(), $this->getCompleteXPath(), $this->getImport()->options['pmwi_order'][$option . '_xpath'], $file)
                            ->parse();
                        $this->tmp_files[] = $file;
                    } else {
                        $this->getCount() and $this->data['pmwi_order'][$option] = array_fill(0, $this->getCount(), $this->getImport()->options['pmwi_order'][$option]);
                    }

                    break;

                case 'products':
                case 'manual_products':
                case 'product_items':

                    $this->data['pmwi_order'][$option] = [];

                    switch ($this->getImport()->options['pmwi_order']['products_repeater_mode']) {
                        case 'xml':
                            if ( ! empty($this->getImport()->options['pmwi_order']['products_repeater_mode_foreach']) ) {
                                foreach ($this->getImport()->options['pmwi_order'][$option] as $key => $row) {
                                    for ($k = 0; $k < $this->getCount(); $k++) {

                                        $base_xpath = '[' . ($k + 1) . ']/' . ltrim(trim($this->getImport()->options['pmwi_order']['products_repeater_mode_foreach'], '{}!'), '/');

                                        $rows = \XmlImportParser::factory($this->getXml(), $this->getCompleteXPath() . $base_xpath, "{.}", $file)
                                            ->parse();
                                        $this->tmp_files[] = $file;

                                        $row_data = $this->parse_item_row($row, $this->getCompleteXPath() . $base_xpath, count($rows));

                                        $products = [];

                                        if ( ! empty($row_data) ) {
                                            for ($j = 0; $j < count($rows); $j++) {
                                                $products[] = [
                                                    'unique_key' => $row_data['unique_key'][$j] ?? '',
                                                    'sku' => $row_data['sku'][$j] ?? '',
                                                    'qty' => $row_data['qty'][$j] ?? '',
                                                    'price_per_unit' => $row_data['price_per_unit'][$j] ?? 0,
                                                    'tax_rates' => []
                                                ];

                                                if ( ! empty($row_data['tax_rates']) ) {
                                                    foreach ($row_data['tax_rates'] as $tax_rate) {
                                                        $products[$j]['tax_rates'][] = [
                                                            'code' => $tax_rate['code'][$j],
                                                            'calculate_logic' => $tax_rate['calculate_logic'][$j] ?? '',
                                                            'percentage_value' => $tax_rate['percentage_value'][$j] ?? '',
                                                            'amount_per_unit' => $tax_rate['amount_per_unit'][$j]
                                                        ];
                                                    }
                                                }

                                                if ( ! empty($row_data['meta_name']) ) {
                                                    foreach ($row_data['meta_name'] as $kk => $meta_name) {
                                                        if ( ! empty($meta_name[$j]) ){
                                                            $products[$j]['meta_name'][] = $meta_name[$j];
                                                            $products[$j]['meta_value'][] = $row_data['meta_value'][$kk][$j] ?? '';
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        $this->data['pmwi_order'][$option][] = $products;
                                    }
                                    break;
                                }
                            }
                            break;
                        case 'csv':
                            if ( ! empty($this->getImport()->options['pmwi_order'][$option]) ) {
                                foreach ($this->getImport()->options['pmwi_order'][$option] as $key => $row) {
                                    if (empty($this->getImport()->options['pmwi_order']['products_repeater_mode_separator'])) {
                                        break;
                                    }
                                    $row_data = $this->parse_item_row($row, $this->getCompleteXPath(), $this->getCount());
                                    for ($k = 0; $k < $this->getCount(); $k++) {
                                        $products = [];
                                        $item_unique_key = $row_data['unique_key'][$k] ?? '';
                                        $unique_key = [];
                                        if (!empty($item_unique_key)) {
                                            $unique_key = explode($this->getImport()->options['pmwi_order']['products_repeater_mode_separator'], $row_data['unique_key'][$k]);
                                            $unique_key = array_filter($unique_key);
                                        }

                                        $skus = explode($this->getImport()->options['pmwi_order']['products_repeater_mode_separator'], $row_data['sku'][$k]);
                                        $skus = array_filter($skus);
                                        $qtys = explode($this->getImport()->options['pmwi_order']['products_repeater_mode_separator'], $row_data['qty'][$k]);
                                        $prices = isset($row_data['price_per_unit'][$k]) ? explode($this->getImport()->options['pmwi_order']['products_repeater_mode_separator'], $row_data['price_per_unit'][$k]) : array();

										$unique_key = is_countable($unique_key) ? $unique_key : [];
										$skus = is_countable($skus) ? $skus : [];
                                        $count = max(count($unique_key), count($skus));

                                        if ( $count > 0 ) {

                                            for ($j = 0; $j < $count; $j++) {

                                                $products[] = [
                                                    'unique_key' => $unique_key[$j] ?? '',
                                                    'sku' => $skus[$j] ?? '',
                                                    'qty' => $qtys[$j] ?? '',
                                                    'price_per_unit' => $prices[$j] ?? 0,
                                                    'tax_rates' => []
                                                ];

                                                if ( ! empty($row_data['tax_rates']) ) {
                                                    foreach ($row_data['tax_rates'] as $tax_rate) {
                                                        $tax_codes = explode($this->getImport()->options['pmwi_order']['products_repeater_mode_separator'], $tax_rate['code'][$k]);
                                                        $tax_codes = array_filter($tax_codes);
                                                        $tax_amount = explode($this->getImport()->options['pmwi_order']['products_repeater_mode_separator'], $tax_rate['amount_per_unit'][$k]);
                                                        $tax_amount = array_filter($tax_amount);
                                                        if (!empty($tax_codes[$j]) && !empty($tax_amount[$j])) {
                                                            $products[$j]['tax_rates'][] = [
                                                                'code' => $tax_codes[$j],
                                                                'amount_per_unit' => $tax_amount[$j]
                                                            ];
                                                        }
                                                    }
                                                }

                                                if ( ! empty($row_data['meta_name']) ) {
                                                    foreach ($row_data['meta_name'] as $meta_name) {
                                                        $meta_names = explode($this->getImport()->options['pmwi_order']['products_repeater_mode_separator'], $meta_name[$k]);
                                                        if ( ! empty($meta_names) && count($meta_names) > 1 ) {
                                                            if ( isset($meta_names[$j]) ) {
                                                                $products[$j]['meta_name'][] = $meta_names[$j];
                                                            }
                                                        } else {
                                                            $products[$j]['meta_name'][] = $meta_name[0] ?? '';
                                                        }
                                                    }
                                                }

                                                if ( ! empty($row_data['meta_value']) ) {
                                                    foreach ($row_data['meta_value'] as $meta_value) {
                                                        $meta_values = explode($this->getImport()->options['pmwi_order']['products_repeater_mode_separator'], $meta_value[$k]);
                                                        if ( ! empty($meta_values) && count($meta_values) > 1 ) {
                                                            if (isset($meta_values[$j])) {
                                                                $products[$j]['meta_value'][] = $meta_values[$j];
                                                            }
                                                        } else {
                                                            $products[$j]['meta_value'][] = $meta_values[0] ?? '';
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        $this->data['pmwi_order'][$option][] = $products;
                                    }
                                    break;
                                }
                            }
                            break;
                        default:
                            $row_data = [];
                            foreach ($this->getImport()->options['pmwi_order'][$option] as $key => $row) {
                                $row_data[] = $this->parse_item_row($row, $this->getCompleteXPath(), $this->getCount());
                            }
                            for ($j = 0; $j < $this->getCount(); $j++) {
                                $products = [];
                                foreach ($row_data as $k => $product) {
                                    $products[] = [
                                        'unique_key' => $product['unique_key'][$j] ?? '',
                                        'sku' => $product['sku'][$j] ?? '',
                                        'qty' => $product['qty'][$j] ?? '',
                                        'price_per_unit' => $product['price_per_unit'][$j] ?? 0,
                                        'tax_rates' => []
                                    ];
                                    if ( ! empty($product['tax_rates']) ) {
                                        foreach ($product['tax_rates'] as $tax_rate) {
                                            $products[$k]['tax_rates'][] = [
                                                'code' => $tax_rate['code'][$j],
                                                'calculate_logic' => $tax_rate['calculate_logic'][$j],
                                                'percentage_value' => $tax_rate['percentage_value'][$j],
                                                'amount_per_unit' => $tax_rate['amount_per_unit'][$j]
                                            ];
                                        }
                                    }
                                    if ( ! empty($product['meta_name']) ) {
                                        foreach ($product['meta_name'] as $meta_name) {
                                            $products[$k]['meta_name'][] = $meta_name[$k];
                                        }
                                    }
                                    if ( ! empty($product['meta_value']) ) {
                                        foreach ($product['meta_value'] as $meta_value) {
                                            $products[$k]['meta_value'][] = $meta_value[$k];
                                        }
                                    }
                                }
                                $this->data['pmwi_order'][$option][] = $products;
                            }
                            break;
                    }
                    break;
                case 'fees':
                case 'refunds':
                case 'coupons':
                case 'shipping':
                case 'taxes':
                case 'notes':
                    $this->data['pmwi_order'][$option] = [];
	                $repeater_mode = $this->getImport()->options['pmwi_order'][$option . '_repeater_mode'] ?? 'csv';
                    switch ($repeater_mode) {
                        case 'xml':
                            if ( ! empty($this->getImport()->options['pmwi_order'][$option . '_repeater_mode_foreach']) ) {
                                foreach ($this->getImport()->options['pmwi_order'][$option] as $key => $row) {
                                    for ($k = 0; $k < $this->getCount(); $k++) {
                                        $base_xpath = '[' . ($k + 1) . ']/' . ltrim(trim($this->getImport()->options['pmwi_order'][$option . '_repeater_mode_foreach'], '{}!'), '/');
                                        $rows = \XmlImportParser::factory($this->getXml(), $this->getCompleteXPath() . $base_xpath, "{.}", $file)
                                            ->parse();
                                        $this->tmp_files[] = $file;
                                        $row_data = $this->parse_item_row($row, $this->getCompleteXPath() . $base_xpath, count($rows));
                                        $items = [];
                                        if ( ! empty($row_data) ) {
                                            for ($j = 0; $j < count($rows); $j++) {
                                                foreach ($row_data as $itemkey => $values) {
                                                    $items[$j][$itemkey] = $values[$j] ?? '';
                                                }

                                                if ( ! empty($row_data['tax_rates']) ) {
                                                    foreach ($row_data['tax_rates'] as $tax_rate) {
	                                                    $items[$j]['tax_rates'] = !is_array($items[$j]['tax_rates']) ? [] : $items[$j]['tax_rates'];
                                                        $items[$j]['tax_rates'][] = [
                                                            'code' => $tax_rate['code'][$j],
                                                            'amount_per_unit' => $tax_rate['amount_per_unit'][$j]
                                                        ];
                                                    }
                                                }

                                                if ( ! empty($row_data['meta_name']) ) {
                                                    foreach ($row_data['meta_name'] as $kk => $meta_name) {
                                                        if ( ! empty($meta_name[$j]) ) {
                                                            $items[$j]['meta_name'][] = $meta_name[$j];
                                                            $items[$j]['meta_value'][] = $row_data['meta_value'][$kk][$j] ?? '';
                                                        }
                                                    }
                                                }

                                            }
                                        }
                                        $this->data['pmwi_order'][$option][] = $items;
                                    }
                                    break;
                                }
                            }
                            break;
                        case 'csv':
                            $separator = $this->getImport()->options['pmwi_order'][$option . '_repeater_mode_separator'] ?? '|';
                            foreach ($this->getImport()->options['pmwi_order'][$option] as $key => $row) {
                                if (empty($separator)) {
                                    break;
                                }
                                $row_data = $this->parse_item_row($row, $this->getCompleteXPath(), $this->getCount(), $separator);
                                for ($k = 0; $k < $this->getCount(); $k++) {
                                    $items = [];
                                    $count = 0;
                                    // Find first text field, and calculate total records count.
                                    foreach ($row_data as $values) {
                                        if (isset($values[$k]) && !is_array($values[$k])) {
                                            $maxCountRows = count(explode($separator, $values[$k]));
                                            if ($count < $maxCountRows) {
                                                $count = $maxCountRows;
                                            }
                                        }
                                    }
                                    if ($count) {
                                        for ($j = 0; $j < $count; $j++) {
                                            foreach ($row_data as $itemkey => $values) {
                                                if (isset($values[$k]) && !is_array($values[$k])) {
                                                    $rows = explode($separator, $values[$k]);
                                                    $items[$j][$itemkey] = $rows[$j] ?? $rows[0];
                                                }
                                            }
                                            if ( ! empty($row_data['tax_rates']) ) {
                                                $items[$j]['tax_rates'] = [];
                                                foreach ($row_data['tax_rates'] as $tax_rate) {
                                                    $tax_rate_codes = explode($separator, $tax_rate['code'][$k]);
                                                    $tax_rate_amount = explode($separator, $tax_rate['amount_per_unit'][$k]);
                                                    if ( ! empty($tax_rate_codes[$j]) && ! empty($tax_rate_amount[$j])) {
                                                        $items[$j]['tax_rates'][] = [
                                                            'code' => $tax_rate_codes[$j],
                                                            'amount_per_unit' => $tax_rate_amount[$j]
                                                        ];
                                                    }
                                                }
                                            }

//                                            if ( ! empty($row_data['meta_name']) ) {
//                                                foreach ($row_data['meta_name'] as $kk => $meta_name) {
//                                                    if ( ! empty($meta_name[$j]) ) {
//                                                        $items[$j]['meta_name'][] = $meta_name[$j];
//                                                        $items[$j]['meta_value'][] = $row_data['meta_value'][$kk][$j] ?? '';
//                                                    }
//                                                }
//                                            }

                                            if ( ! empty($row_data['meta_name']) ) {
                                                $items[$j]['meta_name'] = [];
                                                foreach ($row_data['meta_name'] as $meta_name) {
                                                    $meta_names = explode($separator, $meta_name[$k]);
                                                    if ( ! empty($meta_names) && count($meta_names) > 1 ) {
                                                        if ( isset($meta_names[$j]) ) {
                                                            $items[$j]['meta_name'][] = $meta_names[$j];
                                                        }
                                                    } else {
                                                        $items[$j]['meta_name'][] = $meta_name[0] ?? '';
                                                    }
                                                }
                                            }

                                            if ( ! empty($row_data['meta_value']) ) {
                                                $items[$j]['meta_value'] = [];
                                                foreach ($row_data['meta_value'] as $meta_value) {
                                                    $meta_values = explode($separator, $meta_value[$k]);
                                                    if ( ! empty($meta_values) && count($meta_values) > 1 ) {
                                                        if (isset($meta_values[$j])) {
                                                            $items[$j]['meta_value'][] = $meta_values[$j];
                                                        }
                                                    } else {
                                                        $items[$j]['meta_value'][] = $meta_values[0] ?? '';
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    $this->data['pmwi_order'][$option][] = $items;
                                }
                                break;
                            }
                            break;
                        default:
                            $row_data = [];
                            foreach ($this->getImport()->options['pmwi_order'][$option] as $key => $row) {
                                $row_data[] = $this->parse_item_row($row, $this->getCompleteXPath(), $this->getCount());
                            }
                            for ($j = 0; $j < $this->getCount(); $j++) {
                                $items = [];
                                $itemIndex = 0;
                                foreach ($row_data as $k => $item) {
                                    foreach ($item as $itemkey => $values) {
                                        $items[$itemIndex][$itemkey] = $values[$j];
                                    }
                                    $itemIndex++;
                                }
                                $this->data['pmwi_order'][$option][] = $items;
                            }
                            break;
                    }
                    break;
                default:
                    if (!empty($this->getImport()->options['pmwi_order'][$option])) {
                        $this->data['pmwi_order'][$option] = \XmlImportParser::factory($this->getXml(), $this->getCompleteXPath(), $this->getImport()->options['pmwi_order'][$option], $file)
                            ->parse();
                        $this->tmp_files[] = $file;
                    } else {
                        $this->getCount() and $this->data['pmwi_order'][$option] = array_fill(0, $this->getCount(), $default_value);
                    }
                    break;
            }
        }
        // Remove all temporary files created.
        $this->unlinkTempFiles();
        return $this->data;
    }

    /**
     *
     * Helper method to parse repeated options
     *
     * @param $row
     * @param $cxpath
     * @param $count
     *
     * @param bool $separator
     *
     * @return array
     * @throws \XmlImportException
     */
    protected function parse_item_row($row, $cxpath, $count, $separator = FALSE) {
        $row_data = array();
        foreach ($row as $opt => $value) {
            switch ($opt) {
                case 'class_xpath':
                case 'tax_code_xpath':
                case 'visibility_xpath':
                    // skipp this field(s)
                    break;
                case 'tax_rates':
                    foreach ($value as $i => $tax_rate_row) {
                        $tax_rate_data = array();
                        foreach ($tax_rate_row as $tax_rate_row_opt => $tax_rate_row_value) {
                            if (!empty($tax_rate_row_value)) {
                                $tax_rate_data[$tax_rate_row_opt] = \XmlImportParser::factory($this->getXml(), $cxpath, $tax_rate_row_value, $file)
                                    ->parse();
                                $this->tmp_files[] = $file;
                            } else {
                                $count and $tax_rate_data[$tax_rate_row_opt] = array_fill(0, $count, $tax_rate_row_value);
                            }
                        }
                        $row_data[$opt][] = $tax_rate_data;
                    }
                    break;
                case 'meta_name':
                case 'meta_value':
                    foreach ($value as $meta) {
                        if (!empty($meta)) {
                            $row_data[$opt][] = \XmlImportParser::factory($this->getXml(), $cxpath, $meta, $file)
                                ->parse();
                            $this->tmp_files[] = $file;
                        } else {
                            $row_data[$opt][] = array_fill(0, $count, $meta);
                        }
                    }
                    break;
                case 'class':
                case 'tax_code':
                case 'visibility':
                    if ($value == 'xpath' and $row[$opt . '_xpath'] != '') {
                        $row_data[$opt] = \XmlImportParser::factory($this->getXml(), $cxpath, $row[$opt . '_xpath'], $file)
                            ->parse();
                        $this->tmp_files[] = $file;
                    } else {
                        $count and $row_data[$opt] = array_fill(0, $count, $value);
                    }
                    break;
                case 'date':
                case 'full_date':
                case 'partial_date':
                    if (!empty($value)) {
                        $dates = \XmlImportParser::factory($this->getXml(), $cxpath, $value, $file)
                            ->parse();
                        $this->tmp_files[] = $file;
                        foreach ($dates as $i => $d) {
                            $dates[$i] = $separator ? explode($separator, $d) : array($d);
                        }
                        foreach ($dates as $i => $date) {
                            $times = array();
                            foreach ($date as $d) {
                                if ($d == 'now') {
                                    $d = current_time('mysql');
                                } // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
                                $time = strtotime($d);
                                if (FALSE === $time) {
                                    $time = time();
                                }
                                $times[] = date('Y-m-d H:i:s', $time);
                            }
                            $row_data[$opt][$i] = $separator ? implode($separator, $times) : array_shift($times);
                        }
                    } else {
                        $count and $row_data[$opt] = array_fill(0, $count, date('Y-m-d H:i:s'));
                    }
                    break;
                default:
                    if (!empty($value)) {
                        $row_data[$opt] = \XmlImportParser::factory($this->getXml(), $cxpath, $value, $file)
                            ->parse();
                        $this->tmp_files[] = $file;
                    } else {
                        $count and $row_data[$opt] = array_fill(0, $count, $value);
                    }
                    break;
            }
        }
        // remove all temporary files created
        $this->unlinkTempFiles();
        return $row_data;
    }

    /**
     * @param $option_slug
     * @param $index
     * @return bool|false|mixed|\WP_User
     */
    public function get_existing_customer($option_slug, $index) {
        $customer = FALSE;
        switch ($this->getImport()->options['pmwi_order'][$option_slug . '_match_by']) {
            case 'username':
                $search_by = $this->getValue($option_slug . '_username', $index);
                $customer = get_user_by('login', $search_by) or $customer = get_user_by('slug', $search_by);
                break;
            case 'email':
                $search_by = $this->getValue($option_slug . '_email', $index);
                $customer = get_user_by('email', $search_by);
                break;
            case 'cf':
                $cf_name = $this->getValue($option_slug . '_cf_name', $index);
                $cf_value = $this->getValue($option_slug . '_cf_value', $index);
                $user_query = new \WP_User_Query(array(
                    'meta_key' => $cf_name,
                    'meta_value' => $cf_value
                ));
                if (!empty($user_query->results)) {
                    // ignore nuisance error since we don't really want to change the 'results' value itself, just the value we are saving to $customer
                    $customer = @array_shift($user_query->results);
                }
                break;
            case 'id':
                $search_by = $this->getValue($option_slug . '_id', $index);
                $customer = get_user_by('id', $search_by);
                break;
        }
        return $customer;
    }

    /**
     * @param $option_slug
     * @param $index
     * @return string
     */
    public function get_existing_customer_for_logger($option_slug, $index ) {
        $log = __("Search customer by ", 'wpai_woocommerce_addon_plugin');
        switch ($this->getImport()->options['pmwi_order'][$option_slug . '_match_by']){
            case 'username':
                $log .= __("username", 'wpai_woocommerce_addon_plugin') . " `" . $this->getValue($option_slug . '_username', $index) . "`";
                break;
            case 'email':
                $log .= __("email", 'wpai_woocommerce_addon_plugin') . " `" . $this->getValue($option_slug . '_email', $index) . "`";
                break;
            case 'cf':
                $log .= __("custom field", 'wpai_woocommerce_addon_plugin') . ": `" . $this->getValue($option_slug . '_cf_name', $index) . "` equals to `" . $this->getValue($option_slug . '_cf_value', $index) . "`";
                break;
            case 'id':
                $log .= __("ID", 'wpai_woocommerce_addon_plugin') . " `" . $this->getValue($option_slug . '_id', $index) . "`";
                break;
        }
        return $log . ".";
    }
}
