<?php

namespace wpai_woocommerce_add_on\parser;

use wpai_woocommerce_add_on\helpers\ParserOptions;

/**
 * Interface ParserInterface
 * @package wpai_woocommerce_add_on\parser
 */
interface ParserInterface {

    /**
     * @return mixed
     */
    public function parse();

    /**
     * @return mixed
     */
    public function getData();

    /**
     * @return \PMXI_Import_Record
     */
    public function getImport();

    /**
     * @return ParserOptions
     */
    public function getOptions();

    /**
     * @return mixed
     */
    public function getLogger();

}