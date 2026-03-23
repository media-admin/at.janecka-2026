<?php

namespace wpai_woocommerce_add_on;

use wpai_woocommerce_add_on\importer\Importer;
use wpai_woocommerce_add_on\parser\ParserInterface;

/**
 * Class XmlImportWooCommerce
 */
abstract class XmlImportWooCommerce implements XmlImportWooCommerceInterface {

    /**
     * @var ParserInterface
     */
    public $parser;

    /**
     * @var Importer
     */
    public $importer;

    /**
     * @var
     */
    public $import;

    /**
     * @var
     */
    public $xml;

    /**
     * @var
     */
    public $logger;

    /**
     * @var
     */
    public $count;

    /**
     * @var
     */
    public $chunk;

    /**
     * @var
     */
    public $xpath;

    /**
     * @var
     */
    public $wpdb;

    /**
     * @var
     */
    public $data;

    /**
     * @var bool
     */
    public $articleData = FALSE;

    /**
     * XmlImportWooCommerceShopOrder constructor.
     * @param $options
     */
    public function __construct($options) {
        global $wpdb;
        $this->import = $options['import'];
        $this->count = $options['count'];
        $this->xml = $options['xml'];
        $this->logger = $options['logger'];
        $this->chunk = $options['chunk'];
        $this->xpath = $options['xpath_prefix'];
        $this->wpdb = $wpdb;
    }
}