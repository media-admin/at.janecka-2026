<?php

namespace wpai_woocommerce_add_on;

use wpai_woocommerce_add_on\helpers\ImporterOptions;
use wpai_woocommerce_add_on\importer\ImporterIndex;
use wpai_woocommerce_add_on\importer\products\ImportProductBase;
use wpai_woocommerce_add_on\actions\ProductsActions;
use wpai_woocommerce_add_on\importer\ProductsImporter;
use wpai_woocommerce_add_on\parser\ParserFactory;

/**
 * Class XmlImportWooCommerceProduct
 */
class XmlImportWooCommerceProduct extends XmlImportWooCommerce{

    /**
     * @var ProductsActions
     */
    public $actions;

    /**
     * @var ImporterIndex
     */
    public $index;

    /**
     * @var
     */
    public $previousID;

    /**
     * @var ProductsImporter
     */
    public $importer;

    /**
     * XmlImportWooCommerceProduct constructor.
     *
     * @param $options
     */
    public function __construct($options) {
        parent::__construct($options);
        $this->parser = ParserFactory::generate('products', $options);
        $this->actions = new ProductsActions($this->parser);
	}

    /**
     * @return mixed
     */
	public function parse() {
        $this->data = $this->parser->parse();
		return $this->data;
	}

    /**
     * @param $importData
     */
	public function import($importData) {
        $this->index = new ImporterIndex($importData['pid'], $importData['i'], $importData['articleData']);
        $this->importer = new ProductsImporter($this->index, new ImporterOptions($this->parser));
        $this->actions->setImporter($this->importer);
        $this->importer->import();
	}

    /**
     * @param $importData
     *
     * @throws \Exception
     */
	public function after_save_post($importData) {
        $this->importer->afterPostImport();
	}

    /**
     * @return ImportProductBase
     */
    public function getImporterEngine() {
	    return $this->importer->importEngine;
    }
}
