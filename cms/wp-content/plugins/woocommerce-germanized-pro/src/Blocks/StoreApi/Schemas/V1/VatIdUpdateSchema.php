<?php
namespace Vendidero\Germanized\Pro\Blocks\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;

/**
 * CartFeeSchema class.
 */
class VatIdUpdateSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'vat_id_update';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'vat-id-update';

	/**
	 * Cart schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return array(
			'vat_id'         => array(
				'description' => __( 'The vat id validated.', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'has_vat_exempt' => array(
				'description' => __( 'Has a vat exempt?', 'woocommerce-germanized-pro' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	public function get_item_response( $item ) {
		return array(
			'vat_id'         => $item->vat_id,
			'has_vat_exempt' => $item->has_vat_exempt,
		);
	}
}
