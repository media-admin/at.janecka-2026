<?php

namespace wpai_woocommerce_add_on\importer\products;

/**
 *
 * Import Simple Product
 *
 * Class ImportSimpleProduct
 * @package wpai_woocommerce_add_on\importer
 */
class ImportSimpleProduct extends ImportProduct {

    /**
     * @var string
     */
    protected $productType = 'simple';

    /**
     * @return void
     */
    public function import() {
        parent::import();
    }
}