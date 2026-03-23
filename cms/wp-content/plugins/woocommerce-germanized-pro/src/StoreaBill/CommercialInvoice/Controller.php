<?php

namespace Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;

use Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;
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
	protected $rest_base = 'commercial_invoices';

	protected function get_data_type() {
		return 'commercial_invoice';
	}

	protected function get_type() {
		return 'simple';
	}

	/**
	 * Get object.
	 *
	 * @param  int $id Object ID.
	 * @return \WC_Data
	 */
	protected function get_object( $id ) {
		return sab_get_document( $id, 'commercial_invoice' );
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

	protected function get_price_fields() {
		return array(
			'shipping_total',
			'insurance_total',
			'fee_total',
			'total',
			'price',
			'price_subtotal',
			'subtotal',
		);
	}

	protected function get_item_price_fields() {
		return $this->get_price_fields();
	}

	protected function get_decimal_fields() {
		return array(
			'packaging_weight',
			'net_weight',
			'weight',
			'length',
			'width',
			'height',
		);
	}

	protected function get_item_decimal_fields() {
		return $this->get_decimal_fields();
	}

	public function prepare_object_for_response( $object, $request ) {
		Helper::switch_to_english_locale();
		$response = parent::prepare_object_for_response( $object, $request );
		Helper::restore_locale();

		return $response;
	}

	public function prepare_object_for_database( $request, $creating = false ) {
		Helper::switch_to_english_locale();
		$response = parent::prepare_object_for_database( $request, $creating );
		Helper::restore_locale();

		return $response;
	}

	/**
	 * @param CommercialInvoice $commercial_invoice
	 * @param  \WP_REST_Request $request Request object.
	 */
	protected function sync( &$commercial_invoice, $request ) {
		if ( isset( $request['shipment_id'] ) ) {
			$ref_id = absint( $request['shipment_id'] );

			$commercial_invoice->set_shipment_id( $ref_id );
			$commercial_invoice->set_reference_type( 'germanized' );
		}

		if ( $shipment = $commercial_invoice->get_shipment() ) {
			$shipment->sync( $commercial_invoice );
		}
	}

	protected function save_data_object( $object, $creating = false ) {
		parent::save_data_object( $object, $creating );

		$object->calculate_totals();
		$object->calculate_weights();
	}

	protected function get_objects( $query_args ) {
		$query  = new Query( $query_args );
		$result = $query->get_commercial_invoices();
		$total  = $query->get_total();

		if ( $total < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['page'] );

			$count_query = new Query( $query_args );
			$count_query->get_commercial_invoices();

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
			'title'      => 'commercial_invoice',
			'type'       => 'object',
			'properties' => $this->get_document_base_properties_schema(),
			array(
				'shipment_id'                => array(
					'description' => _x( 'The shipment ID linked to the commercial invoice.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'integer',
					'label'       => _x( 'Shipment ID', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'shipment_number'            => array(
					'description' => _x( 'The shipment number linked to the commercial invoice.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'integer',
					'label'       => _x( 'Shipment Number', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'order_id'                   => array(
					'description' => _x( 'The order ID linked to the commercial invoice.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'integer',
					'label'       => _x( 'Order ID', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'order_number'               => array(
					'description' => _x( 'The formatted order number linked to the commercial invoice.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'label'       => __( 'Order Number', 'woocommerce-germanized-pro' ),
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'incoterms'                  => array(
					'description' => _x( 'The incoterms.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array_keys( Helper::get_available_incoterms() ),
				),
				'export_reason'              => array(
					'description' => _x( 'The export reason.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array_keys( Helper::get_available_export_reasons() ),
				),
				'export_type'                => array(
					'description' => _x( 'The export type.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array_keys( Helper::get_available_export_types() ),
				),
				'invoice_type'               => array(
					'description' => _x( 'The invoice type.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'default'     => 'commercial',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array_keys( Helper::get_available_invoice_types() ),
				),
				'shipping_total'             => array(
					'description' => _x( 'Shipping total amount.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'insurance_total'            => array(
					'description' => _x( 'Insurance total amount.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'fee_total'                  => array(
					'description' => _x( 'Fee total amount.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'discount_total'             => array(
					'description' => _x( 'Discount total amount.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'total'                      => array(
					'description' => _x( 'Total amount.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'sync'                       => array(
					'default'     => false,
					'type'        => 'boolean',
					'description' => _x( 'Whether to automatically sync the commercial invoice with it\'s shipment if possible.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
				),
				'formatted_shipping_address' => array(
					'description' => _x( 'Formatted shipping address data.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_address'           => array(
					'description' => _x( 'Shipping Address data.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => $this->get_address_property_schema(),
				),
				'formatted_sender_address'   => array(
					'description' => _x( 'Formatted sender address data.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'sender_address'             => array(
					'description' => _x( 'Sender Address data.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => $this->get_address_property_schema(),
				),
				'packaging_weight'           => array(
					'description' => _x( 'Packaging weight in kg.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'weight'                     => array(
					'description' => _x( 'Weight in kg.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'net_weight'                 => array(
					'description' => _x( 'Net weight in kg.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'length'                     => array(
					'description' => _x( 'Length in cm.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'width'                      => array(
					'description' => _x( 'Width in cm.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'height'                     => array(
					'description' => _x( 'Height in cm.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'currency'                   => array(
					'description' => _x( 'Currency.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'product_items'              => array(
					'description' => _x( 'Product items data.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array_merge(
							$this->get_item_properties_schema(),
							array(
								'sku'                 => array(
									'description' => _x( 'SKU.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'global_unique_id'    => array(
									'description' => _x( 'Global Unique ID.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'price'               => array(
									'description' => _x( 'Item price.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'total'               => array(
									'description' => _x( 'Item total.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'weight'              => array(
									'description' => _x( 'Item weight in kg.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'length'              => array(
									'description' => _x( 'Item length in cm.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'width'               => array(
									'description' => _x( 'Item width in cm.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'height'              => array(
									'description' => _x( 'Item height in cm.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'hs_code'             => array(
									'description' => _x( 'HS Code.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'default'     => '',
								),
								'mid_code'            => array(
									'description' => _x( 'MID Code.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'default'     => '',
								),
								'manufacture_country' => array(
									'description' => _x( 'Manufacture country as ISO code.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'default'     => '',
								),
							)
						),
					),
				),
				'totals'                     => array(
					'description' => _x( 'Total data.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => $this->get_totals_property_schema(),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
