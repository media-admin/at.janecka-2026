<?php

namespace wpai_woocommerce_add_on;

/**
 * Interface XmlImportWooCommerceInterface
 */
interface XmlImportWooCommerceInterface {

    /**
     * @return mixed
     */
    public function parse();

    /**
     * @param $importData
     * @return mixed
     */
    public function import($importData);

    /**
     * @param $importData
     * @return mixed
     */
    public function after_save_post($importData);
}