<?php

namespace wpai_woocommerce_add_on\importer\orders;

use wpai_woocommerce_add_on\helpers\ImporterOptions;
use wpai_woocommerce_add_on\importer\ImportBase;
use wpai_woocommerce_add_on\importer\ImporterIndex;
use wpai_woocommerce_add_on\parser\ParserInterface;

/**
 * Created by PhpStorm.
 * User: cmd
 * Date: 11/15/17
 * Time: 2:10 PM
 */
abstract class ImportOrderBase extends ImportBase {

    /**
     * @var \WC_Order
     */
    public $order;

    /**
     * @var
     */
    public $order_data;

    public function __construct(ImporterIndex $index, ImporterOptions $options, $order, $data = array()) {
        parent::__construct($index, $options, $data);
        $this->order = $order;
    }

    /**
     * @return mixed
     */
    public function getOrderID() {
        return $this->index->getPid();
    }

    /**
     * @return bool|\WC_Order
     */
    public function getOrder() {
        return $this->order;
    }

    /**
     * @return ParserInterface
     */
    public function getParser(){
        return $this->getOptions()->getParser();
    }

    /**
     * @return boolean
     */
    public function isNewOrder() {
        $orderID = $this->getArticleData('ID');
        return empty($orderID) ? TRUE : FALSE;
    }
}
