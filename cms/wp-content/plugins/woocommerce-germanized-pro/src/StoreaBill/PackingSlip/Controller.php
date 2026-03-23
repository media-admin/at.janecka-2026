<?php

namespace Vendidero\Germanized\Pro\StoreaBill\PackingSlip;

use Vendidero\Germanized\Pro\StoreaBill\PackingSlip;
use Vendidero\StoreaBill\REST\DocumentController;

defined( 'ABSPATH' ) || exit;

/**
 * Invoice Controller class.
 */
class Controller extends DocumentController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'packing_slips';

	protected function get_data_type() {
		return 'packing_slip';
	}

	protected function get_type() {
		return 'simple';
	}

	/**
	 * Get object.
	 *
	 * @param  int $id Object ID.
	 * @return PackingSlip
	 */
	protected function get_object( $id ) {
		return sab_get_document( $id, 'packing_slip' );
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		if ( isset( $request['order_id'] ) && ! empty( $request['order_id'] ) ) {
			$args['order_id'] = $request['order_id'];
		}

		return $args;
	}

	protected function get_additional_collection_params() {
		$params = parent::get_additional_collection_params();

		$params['order_id'] = array(
			'description'       => __( 'Limit result set to packing slips belonging to a certain order.', 'woocommerce-germanized-pro' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * @param PackingSlip $packing_slip
	 * @param  \WP_REST_Request $request Request object.
	 */
	protected function sync( &$packing_slip, $request ) {
		if ( isset( $request['shipment_id'] ) ) {
			$ref_id = absint( $request['shipment_id'] );

			$packing_slip->set_shipment_id( $ref_id );
			$packing_slip->set_reference_type( 'germanized' );
		}

		if ( $shipment = $packing_slip->get_shipment() ) {
			$shipment->sync( $packing_slip );
		}
	}

	protected function get_objects( $query_args ) {
		$query  = new Query( $query_args );
		$result = $query->get_packing_slips();
		$total  = $query->get_total();

		if ( $total < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['page'] );

			$count_query = new Query( $query_args );
			$count_query->get_packing_slips();

			$total = $count_query->get_total();
		}

		return array(
			'objects' => $result,
			'total'   => (int) $total,
			'pages'   => $query->get_max_num_pages(),
		);
	}

	/**
	 * Get the Invoice's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'packing_slip',
			'type'       => 'object',
			'properties' => $this->get_document_base_properties_schema(),
			array(
				'shipment_id'     => array(
					'description' => __( 'The shipment ID linked to the packing slip.', 'woocommerce-germanized-pro' ),
					'type'        => 'integer',
					'label'       => __( 'Shipment ID', 'woocommerce-germanized-pro' ),
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'shipment_number' => array(
					'description' => __( 'The shipment number linked to the packing slip.', 'woocommerce-germanized-pro' ),
					'type'        => 'integer',
					'label'       => __( 'Shipment Number', 'woocommerce-germanized-pro' ),
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'order_id'        => array(
					'description' => __( 'The order ID linked to the packing slip.', 'woocommerce-germanized-pro' ),
					'type'        => 'integer',
					'label'       => __( 'Order ID', 'woocommerce-germanized-pro' ),
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'order_number'    => array(
					'description' => __( 'The formatted order number linked to the packing slip.', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => __( 'Order Number', 'woocommerce-germanized-pro' ),
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'sync'            => array(
					'default'     => false,
					'type'        => 'boolean',
					'description' => __( 'Whether to automatically sync the packing slip with it\'s shipment if possible.', 'woocommerce-germanized-pro' ),
				),
				'product_items'   => array(
					'description' => __( 'Product items data.', 'woocommerce-germanized-pro' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array_merge(
							$this->get_item_properties_schema(),
							array(
								'sku'              => array(
									'description' => __( 'SKU.', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'global_unique_id' => array(
									'description' => __( 'Global Unique ID.', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'price'            => array(
									'description' => __( 'Item price.', 'woocommerce-germanized-pro' ),
									'type'        => 'number',
									'context'     => array( 'view', 'edit' ),
								),
								'price_subtotal'   => array(
									'description' => __( 'Item subtotal price.', 'woocommerce-germanized-pro' ),
									'type'        => 'number',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'total'            => array(
									'description' => __( 'Total.', 'woocommerce-germanized-pro' ),
									'type'        => 'number',
									'context'     => array( 'view', 'edit' ),
								),
								'subtotal'         => array(
									'description' => __( 'Subtotal.', 'woocommerce-germanized-pro' ),
									'type'        => 'number',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							)
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
