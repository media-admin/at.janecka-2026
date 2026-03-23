<?php

namespace wpai_woocommerce_add_on\importer;

/**
 * Created by PhpStorm.
 * User: cmd
 * Date: 11/14/17
 * Time: 11:33 AM
 */
/**
 * Interface ImporterInterface
 * @package wpai_woocommerce_add_on\importer
 */
interface ImporterInterface {

    /**
     * @return mixed
     */
    public function import();

    /**
     * @return mixed
     */
    public function afterPostImport();

}