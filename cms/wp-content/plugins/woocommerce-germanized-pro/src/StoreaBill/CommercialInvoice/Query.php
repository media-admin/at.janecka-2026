<?php

namespace Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;

use Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;
use Vendidero\StoreaBill\Document\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Commercial Invoice Query Class
 *
 * Extended by classes to provide a query abstraction layer for safe object searching.
 *
 * @version  1.0.0
 * @package  StoreaBill/Abstracts
 */
class Query extends \Vendidero\StoreaBill\Document\Query {

	public function get_document_type() {
		return 'commercial_invoice';
	}

	/**
	 * Retrieve packing slips.
	 *
	 * @return CommercialInvoice[]|Document[]
	 */
	public function get_commercial_invoices() {
		return $this->get_documents();
	}
}
