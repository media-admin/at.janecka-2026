<?php

namespace Vendidero\Germanized\Pro\StoreaBill\DataStores;

use Vendidero\StoreaBill\DataStores\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Commercial Invoice data store.
 *
 * @version 1.0.0
 */
class CommercialInvoice extends Document {

	/**
	 * Data stored in meta keys, but not considered "meta" for an invoice.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_address',
		'_created_via',
		'_version',
		'_reference_number',
		'_external_sync_handlers',
		'_order_id',
		'_order_number',
		'_total',
		'_discount_total',
		'_shipping_total',
		'_product_total',
		'_insurance_total',
		'_shipping_address',
		'_sender_address',
		'_weight',
		'_net_weight',
		'_packaging_weight',
		'_currency',
		'_invoice_type',
		'_export_type',
		'_incoterms',
		'_export_reason',
		'_length',
		'_width',
		'_height',
	);
}
