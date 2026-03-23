<?php

namespace wpai_woocommerce_add_on\services;

/**
 * Class XmlImportWooPriceService
 */
class XmlImportWooPriceService extends XmlImportWooServiceBase {

    /**
     *
     * Adjust price and prepare it to valid format.
     *
     * @param $price
     * @param $field
     *
     * @param $options
     * @return float|int|string
     */
    public function adjustPrice($price, $field, $options = array()) {

    	// Ensure $price is stored as a double to avoid PHP 8+ issues.
	    if( !empty($price) )
	        $price = (double) $price;

        if (empty($options)){
            $options = $this->getImport()->options;
        }
        switch ($field) {
            case 'variable_regular_price':
            case 'regular_price':
                if (!empty($options['single_product_regular_price_adjust'])) {
                    switch ($options['single_product_regular_price_adjust_type']) {
                        case '%':
                            if (empty($price)) {
                                return $price;
                            }
                            $price = ($price / 100) * (double) $options['single_product_regular_price_adjust'];
                            break;
                        case '$':
                            $price += (double) $options['single_product_regular_price_adjust'];
                            break;
                    }
                    $price = ((double) $price > 0) ? number_format((double) $price, 2, '.', '') : 0;
                }
                break;
            case 'variable_sale_price':
            case 'sale_price':
                if (!empty($options['single_product_sale_price_adjust'])) {
                    switch ($options['single_product_sale_price_adjust_type']) {
                        case '%':
                            if (empty($price)) {
                                return $price;
                            }
                            $price = ($price / 100) * (double) $options['single_product_sale_price_adjust'];
                            break;
                        case '$':
                            $sale_price_adjust = (double) $options['single_product_sale_price_adjust'];
                            if (empty($price) && $sale_price_adjust <= 0) {
                                return $price;
                            }
                            $price += $sale_price_adjust;
                            break;
                    }
                    $price = ((double) $price > 0) ? number_format((double) $price, 2, '.', '') : 0;
                }
                break;
        }
        return $price;
    }

    /**
     *
     * Prepare price to valid format.
     *
     * @param $price
     * @param $disable_prepare_price
     * @param $prepare_price_to_woo_format
     * @param $convert_decimal_separator
     *
     * @return float|int|string
     */
    public function preparePrice($price, $disable_prepare_price, $prepare_price_to_woo_format, $convert_decimal_separator) {

        if ($disable_prepare_price) {
            $price = preg_replace("/[^0-9\.,]/", "", $price);
        }

	    // Special case where there is one digit after a comma decimal separator: 2,9
	    if (preg_match('/^\d+,\d$/', $price)) {
		    $price = str_replace(".","", $price);
		    $price = str_replace(',', '.', $price);
	    }

        if ($convert_decimal_separator && strlen($price) > 3) {
            $comma_position = strrpos($price, ",", strlen($price) - 3);
            if ($comma_position !== FALSE) {
                $price = str_replace(".", "", $price);
                $comma_position = strrpos($price, ",");
                $price = str_replace(",", "", substr_replace($price, ".", $comma_position, 1));
            } else {
                $comma_position = strrpos($price, ".", strlen($price) - 3);
                if ($comma_position !== FALSE) {
                    $price = str_replace(",", "", $price);
                } elseif (strlen($price) > 4) {
                    $comma_position = strrpos($price, ",", strlen($price) - 4);
                    if ($comma_position and strlen($price) - $comma_position == 4) {
                        $price = str_replace(",", "", $price);
                    } else {
                        $comma_position = strrpos($price, ".", strlen($price) - 4);
                        if ($comma_position and strlen($price) - $comma_position == 4) {
                            $price = str_replace(".", "", $price);
                        }
                    }
                }
            }
        }

        if ($prepare_price_to_woo_format) {
            $price = str_replace(",", ".", $price);
            $price = str_replace(",", ".", str_replace(".", "", preg_replace("%\.([0-9]){1,2}?$%", ",$0", $price)));
            $price = ("" != $price) ? number_format((double) $price, 2, '.', '') : "";
        }
        return apply_filters('pmxi_price', $price);
    }
}
