<?php

namespace Vendidero\StoreaBill\REST;

defined( 'ABSPATH' ) || exit;

use Vendidero\StoreaBill\Document\Attachment;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\eInvoice\Helper;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Invoice\Query;
use Vendidero\StoreaBill\TaxRate;

use WP_Error;
use WC_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WC_Data;
use WP_REST_Response;
use WC_Data_Exception;
use WC_REST_Exception;

/**
 * Invoice Controller class.
 */
class InvoiceController extends DocumentController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'invoices';

	protected function get_data_type() {
		return 'invoice';
	}

	protected function get_type() {
		return 'simple';
	}

	/**
	 * Register the routes for invoices.
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/cancel',
			array(
				'args' => array(
					'id' => array(
						'description' => _x( 'Unique identifier for the resource.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'cancel_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => _x( 'Unique identifier for the resource.', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			)
		);
	}

	/**
	 * @param int $id
	 *
	 * @return bool|Invoice|WC_Data|WP_Error
	 */
	public function get_object( $id ) {
		return sab_get_invoice( $id, $this->get_type() );
	}

	/**
	 * Cancel an invoice.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function cancel_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new WP_Error( "storeabill_rest_{$this->get_data_type()}_invalid_id", _x( 'Invalid ID.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 400 ) );
		}

		$cancellation = $object->cancel();

		if ( is_wp_error( $cancellation ) ) {
			return $cancellation;
		}

		$request->set_param( 'context', 'edit' );

		if ( $controller = Server::instance()->get_controller( 'cancellations', $this->namespace ) ) {
			$response = $controller->prepare_object_for_response( $cancellation, $request );
		} else {
			$response = $this->prepare_object_for_response( $object, $request );
		}

		return rest_ensure_response( $response );
	}

	protected function get_price_fields() {
		return array(
			'discount_total',
			'discount_tax',
			'discount_net',
			'additional_costs_discount_total',
			'additional_costs_discount_tax',
			'additional_costs_discount_net',
			'shipping_total',
			'shipping_tax',
			'shipping_net',
			'product_total',
			'product_tax',
			'product_net',
			'fee_total',
			'fee_tax',
			'fee_net',
			'voucher_total',
			'voucher_net',
			'voucher_tax',
			'shipping_subtotal',
			'shipping_subtotal_tax',
			'shipping_subtotal_net',
			'product_subtotal',
			'product_subtotal_tax',
			'product_subtotal_net',
			'fee_subtotal',
			'fee_subtotal_tax',
			'fee_subtotal_net',
			'total',
			'total_net',
			'total_tax',
			'subtotal',
			'subtotal_net',
			'subtotal_tax',
			'total_paid',
		);
	}

	protected function get_date_fields() {
		return array(
			'date_created',
			'date_modified',
			'date_due',
			'date_paid',
			'date_sent',
			'date_of_service',
			'date_of_service_end',
		);
	}

	protected function get_item_price_fields() {
		return array(
			'line_subtotal',
			'subtotal_tax',
			'line_total',
			'total_tax',
			'total_net',
			'subtotal_net',
			'price',
			'price_net',
			'price_tax',
			'price_subtotal',
			'price_subtotal_net',
			'price_subtotal_tax',
			'total',
			'total_net',
			'subtotal',
			'subtotal_net',
			'discount_total',
			'discount_net',
			'discount_tax',
		);
	}

	/**
	 * Create a new attachment.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @param bool $creating
	 *
	 * @return WP_Error|Attachment
	 */
	public function save_attachment( $request, $creating = false ) {
		$type = isset( $request['type'] ) ? wc_clean( $request['type'] ) : '';

		if ( $creating && 'einvoice' === $type ) {
			$document_id   = isset( $request['document_id'] ) ? absint( $request['document_id'] ) : 0;
			$specification = isset( $request['specification'] ) ? wc_clean( $request['specification'] ) : '';
			$profile       = isset( $request['profile'] ) ? wc_clean( $request['profile'] ) : '';
			$merge_pdf     = isset( $request['merge_pdf'] ) ? sab_string_to_bool( $request['merge_pdf'] ) : false;

			if ( ! $profile_data = Helper::get_profile( $specification, $profile ) ) {
				return new WP_Error( "storeabill_rest_{$this->get_data_type()}_attachment_error", _x( 'Cannot create attachment.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 400 ) );
			}

			$additional_meta = array(
				'merge_pdf' => $merge_pdf,
			);

			$error = new WP_Error();

			foreach ( $profile_data['fields'] as $field ) {
				$field = wp_parse_args(
					$field,
					array(
						'id'        => '',
						'mandatory' => false,
						'label'     => '',
						'default'   => '',
					)
				);

				$field_value = sab_clean( isset( $request[ $field['id'] ] ) ? $request[ $field['id'] ] : $field['default'] );

				if ( $field['mandatory'] && empty( $field_value ) ) {
					$error->add( 'meta_error', sprintf( _x( '%s is a mandatory field.', 'storeabill-core', 'woocommerce-germanized-pro' ), $field['label'] ) );
				}

				if ( ! empty( $field_value ) ) {
					$additional_meta[ $field['id'] ] = $field_value;
				}
			}

			if ( sab_wp_error_has_errors( $error ) ) {
				return $error;
			}

			if ( ! $invoice = $this->get_object( $document_id ) ) {
				return new WP_Error( "storeabill_rest_{$this->get_data_type()}_attachment_error", _x( 'Cannot create attachment.', 'storeabill-core', 'woocommerce-germanized-pro' ), array( 'status' => 400 ) );
			}

			$result = $invoice->create_einvoice( $specification, $profile, $additional_meta );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return parent::save_attachment( $request, $creating );
	}

	protected function get_formatted_item_data( $object ) {
		$data = parent::get_formatted_item_data( $object );

		if ( isset( $data['tax_totals'] ) ) {
			$data['tax_totals'] = $this->get_tax_total_data( $data['tax_totals'], $object );
		}

		return $data;
	}

	protected function get_tax_total_data( $tax_totals, $object ) {
		$is_preview = $this->is_preview_request();

		foreach ( $tax_totals as $key => $totals ) {
			$totals             = $totals->get_data();
			$tax_totals[ $key ] = $totals;

			foreach ( $totals as $inner_key => $total ) {
				if ( is_numeric( $total ) ) {
					$tax_totals[ $key ][ $inner_key ] = sab_format_decimal( $tax_totals[ $key ][ $inner_key ], $this->request['dp'] );

					if ( $is_preview ) {
						$tax_totals[ $key ][ $inner_key . '_formatted' ] = sab_format_price( $tax_totals[ $key ][ $inner_key ] );
					}
				} elseif ( is_a( $total, '\Vendidero\StoreaBill\TaxRate' ) ) {
					$tax_totals[ $key ][ $inner_key ] = $this->get_tax_rate_data( $total, $object );
				} elseif ( is_array( $total ) ) {
					foreach ( $total as $t_key => $t ) {
						$tax_totals[ $key ][ $inner_key ][ $t_key ] = sab_format_decimal( $tax_totals[ $key ][ $inner_key ][ $t_key ], $this->request['dp'] );

						if ( $is_preview ) {
							$tax_totals[ $key ][ $inner_key ][ $t_key . '_formatted' ] = sab_format_price( $tax_totals[ $key ][ $inner_key ][ $t_key ] );
						}
					}
				}
			}
		}

		return $tax_totals;
	}

	/**
	 * @param Invoice $invoice
	 * @param  WP_REST_Request $request Request object.
	 */
	protected function sync( &$invoice, $request ) {
		if ( isset( $request['order_id'] ) && isset( $request['order_type'] ) ) {
			$ref_id   = absint( $request['order_id'] );
			$ref_type = sab_clean( $request['order_type'] );

			$invoice->set_order_id( $ref_id );
			$invoice->set_order_type( $ref_type );
		}

		if ( $order = $invoice->get_order() ) {
			$order->sync( $invoice );
		}
	}

	/**
	 * Save an object data.
	 *
	 * @since  3.0.0
	 * @throws WC_REST_Exception But all errors are validated before returning any data.
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @param  bool            $creating If is creating a new object.
	 * @return WC_Data|WP_Error
	 */
	protected function save_object( $request, $creating = false ) {
		try {
			$object = $this->prepare_object_for_database( $request, $creating );

			if ( is_wp_error( $object ) ) {
				return $object;
			}

			if ( ! is_null( $request['customer_id'] ) && 0 !== $request['customer_id'] ) {
				// Make sure customer exists.
				if ( false === get_user_by( 'id', $request['customer_id'] ) ) {
					throw new WC_REST_Exception( 'storeabill_rest_invalid_customer_id', _x( 'Customer ID is invalid.', 'storeabill-core', 'woocommerce-germanized-pro' ), 400 );
				}

				// Make sure customer is part of blog.
				if ( is_multisite() && ! is_user_member_of_blog( $request['customer_id'] ) ) {
					add_user_to_blog( get_current_blog_id(), $request['customer_id'], 'customer' );
				}
			}

			if ( $creating ) {
				$object->set_created_via( 'rest-api' );

				if ( ! $this->is_sync( $request ) ) {
					if ( ! isset( $request['prices_include_tax'] ) ) {
						$object->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
					}

					$object->calculate_totals();
				}
			} else {
				$item_types    = $object->get_item_types();
				$items_changed = false;

				foreach ( $item_types as $item_type ) {
					if ( isset( $request[ $item_type . '_items' ] ) ) {
						$items_changed = true;
						break;
					}
				}

				// Recalculate invoice totals.
				if ( ! $this->is_sync( $request ) && ( isset( $request['address'] ) || $items_changed || isset( $request['prices_include_tax'] ) ) ) {
					$object->calculate_totals( true );
				}
			}

			if ( $order = $object->get_order() ) {
				$order->add_document( $object, true );
				$order->validate();

				$object = $order->get_document( $object->get_id() );

				if ( $object ) {
					// Set status.
					if ( ! empty( $request['status'] ) ) {
						$object->set_status( $request['status'] );
					}

					if ( 'closed' === $request['status'] && ! $object->is_finalized() ) {
						if ( $this->is_sync( $request ) ) {
							$order->finalize();
						} else {
							$object->finalize();
						}
					}
				} elseif ( ! $order->needs_billing() ) {
						throw new WC_REST_Exception( 'storeabill_rest_invalid_invoice', _x( 'This order does not need an invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
				} else {
					throw new WC_REST_Exception( 'storeabill_rest_invalid_invoice', _x( 'The invoice was cancelled immediately or could not be validated.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
				}

				return $object;
			} else {
				// Set status.
				if ( ! empty( $request['status'] ) ) {
					$object->set_status( $request['status'] );
				}

				if ( 'closed' === $request['status'] && ! $object->is_finalized() ) {
					$object->finalize();
				} else {
					$object->save();
				}

				return $this->get_object( $object->get_id() );
			}
		} catch ( WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( \WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Get objects.
	 *
	 * @since  3.0.0
	 * @param  array $query_args Query args.
	 * @return array
	 */
	protected function get_objects( $query_args ) {
		$query  = new Query( $query_args );
		$result = $query->get_invoices();
		$total  = $query->get_total();

		if ( $total < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['page'] );

			$count_query = new Query( $query_args );
			$count_query->get_invoices();

			$total = $count_query->get_total();
		}

		return array(
			'objects' => $result,
			'total'   => (int) $total,
			'pages'   => $query->get_max_num_pages(),
		);
	}

	protected function get_item_data( $item, $object ) {
		$data = parent::get_item_data( $item, $object );

		// Format tax data
		if ( isset( $data['taxes'] ) ) {
			$data['taxes'] = array_values(
				array_map(
					function ( $item ) use ( $object ) {
						return $this->get_item_data( $item, $object );
					},
					$data['taxes']
				)
			);
		}

		// Format tax rates
		if ( isset( $data['tax_rates'] ) ) {
			$data['tax_rates'] = array_values(
				array_map(
					function ( $item ) use ( $object ) {
						return $this->get_tax_rate_data( $item, $object );
					},
					$data['tax_rates']
				)
			);
		}

		return $data;
	}

	/**
	 * Expands a document item to get its data.
	 *
	 * @param TaxRate $rate Rate data.
	 *
	 * @return array
	 */
	protected function get_tax_rate_data( $rate, $object ) {
		$data = $rate->get_data();

		return $data;
	}

	protected function get_additional_collection_params() {
		$params = parent::get_additional_collection_params();

		$params['payment_status'] = array(
			'default'           => 'any',
			'description'       => _x( 'Limit result set to invoices which have a specific payment statuses.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array_merge( array( 'any' ), array_keys( sab_get_invoice_payment_statuses() ) ),
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		if ( isset( $request['payment_status'] ) ) {
			$args['payment_status'] = $request['payment_status'];
		}

		return $args;
	}

	/**
	 * Get the Invoice's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'invoice',
			'type'       => 'object',
			'properties' => array_merge(
				$this->get_document_base_properties_schema(),
				array(
					'order_id'                        => array(
						'description' => _x( 'The order ID linked to the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'integer',
						'label'       => _x( 'Order ID', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'default'     => 0,
						'context'     => array( 'view', 'edit' ),
					),
					'order_number'                    => array(
						'description' => _x( 'The formatted order number linked to the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Order number', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'default'     => '',
						'context'     => array( 'view', 'edit' ),
					),
					'order_type'                      => array(
						'description' => _x( 'The order resource type, e.g. woocommerce.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Order type', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'default'     => 'woocommerce',
						'context'     => array( 'view', 'edit' ),
					),
					'sync'                            => array(
						'default'     => false,
						'type'        => 'boolean',
						'description' => _x( 'Whether to automatically sync the invoice with it\'s order if possible.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					),
					'payment_status'                  => array(
						'description' => _x( 'Invoice payment status.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Payment status', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'default'     => 'pending',
						'enum'        => array_keys( sab_get_invoice_payment_statuses() ),
						'context'     => array( 'view', 'edit' ),
					),
					'payment_method_name'             => array(
						'description' => _x( 'The payment method slug.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Payment method slug', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'payment_method_title'            => array(
						'description' => _x( 'The payment method title.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Payment method', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'payment_transaction_id'          => array(
						'description' => _x( 'The payment transaction id.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Payment transaction id', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'currency'                        => array(
						'description' => _x( 'Currency the invoice was created with, in ISO format.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Currency', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'default'     => sab_get_default_currency(),
						'enum'        => array_keys( sab_get_currencies() ),
						'context'     => array( 'view', 'edit' ),
					),
					'date_paid'                       => array(
						'description' => _x( "The date the invoice was marked as paid, in the site's timezone.", 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'label'       => _x( 'Date paid', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_paid_gmt'                   => array(
						'description' => _x( 'The date the invoice was marked as paid, as GMT.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'label'       => _x( 'Date paid (GMT)', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_due'                        => array(
						'description' => _x( "The date until which the invoice is due for payment, in the site's timezone.", 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'label'       => _x( 'Date due', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'date_due_gmt'                    => array(
						'description' => _x( 'The date until which the invoice is due for payment, as GMT.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'label'       => _x( 'Date due (GMT)', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'date_of_service'                 => array(
						'description' => _x( "The date of service, in the site's timezone.", 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'label'       => _x( 'Date of service', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'date_of_service_gmt'             => array(
						'description' => _x( 'The date of service, as GMT.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'label'       => _x( 'Date of service (GMT)', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'date_of_service_end'             => array(
						'description' => _x( "The end date of service, in the site's timezone.", 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'label'       => _x( 'Date of service end', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'date_of_service_end_gmt'         => array(
						'description' => _x( 'The end date of service, as GMT.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'label'       => _x( 'Date of service end (GMT)', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'product_total'                   => array(
						'description' => _x( 'Product total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Product total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'product_subtotal'                => array(
						'description' => _x( 'Product subtotal (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Product subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'shipping_total'                  => array(
						'description' => _x( 'Shipping total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Shipping total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'shipping_subtotal'               => array(
						'description' => _x( 'Shipping subtotal (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Shipping subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'fee_total'                       => array(
						'description' => _x( 'Fee total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Fee total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'fee_subtotal'                    => array(
						'description' => _x( 'Fee subtotal (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Fee subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'discount_total'                  => array(
						'description' => _x( 'Discount total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Discount total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'discount_percentage'             => array(
						'description' => _x( 'Discount percentage for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Discount percentage', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'discount_notice'                 => array(
						'description' => _x( 'A notice on discounts e.g. coupon codes used.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Discount Notice', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'default'     => '',
						'context'     => array( 'view', 'edit' ),
					),
					'additional_costs_discount_total' => array(
						'description' => _x( 'Discount on additional costs e.g. shipping and fees.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Additional costs discount', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'voucher_total'                   => array(
						'description' => _x( 'Voucher total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Voucher total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'voucher_net'                     => array(
						'description' => _x( 'Voucher net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Voucher net total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'voucher_tax'                     => array(
						'description' => _x( 'Voucher tax amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Voucher tax', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'total_tax'                       => array(
						'description' => _x( 'Tax total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Tax total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'subtotal_tax'                    => array(
						'description' => _x( 'Subtotal tax (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Tax subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'product_tax'                     => array(
						'description' => _x( 'Product tax total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Product tax total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'product_subtotal_tax'            => array(
						'description' => _x( 'Product subtotal tax (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Product tax subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'shipping_tax'                    => array(
						'description' => _x( 'Shipping tax total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Shipping tax total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'shipping_subtotal_tax'           => array(
						'description' => _x( 'Shipping subtotal tax (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Shipping tax subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'fee_tax'                         => array(
						'description' => _x( 'Fee tax total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Fee tax total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'fee_subtotal_tax'                => array(
						'description' => _x( 'Fee subtotal tax (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Fee tax subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'discount_tax'                    => array(
						'description' => _x( 'Discount tax total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Discount tax total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'additional_costs_discount_tax'   => array(
						'description' => _x( 'Additional costs discount tax total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Additional costs discount tax total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'total'                           => array(
						'description' => _x( 'Total amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'subtotal'                        => array(
						'description' => _x( 'Subtotal (before discounts) amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'total_net'                       => array(
						'description' => _x( 'Total net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Net total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'subtotal_net'                    => array(
						'description' => _x( 'Subtotal net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Subtotal net', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'product_net'                     => array(
						'description' => _x( 'Product net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Product net total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'product_subtotal_net'            => array(
						'description' => _x( 'Product subtotal net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Product subtotal net total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'shipping_net'                    => array(
						'description' => _x( 'Shipping net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Shipping net total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'shipping_subtotal_net'           => array(
						'description' => _x( 'Shipping subtotal net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Shipping subtotal net', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'fee_net'                         => array(
						'description' => _x( 'Fee net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Fee net total', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'fee_subtotal_net'                => array(
						'description' => _x( 'Fee subtotal net amount for the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Fee subtotal net', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'total_paid'                      => array(
						'description' => _x( 'Total paid amount of the invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Total amount paid', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'tax_rate_percentages'            => array(
						'description' => _x( 'Tax rate percentages.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'label'       => _x( 'List of tax rate percentages.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'items'       => array(
							'type' => 'number',
						),
						'readonly'    => true,
					),
					'prices_include_tax'              => array(
						'description' => _x( 'True in case prices include tax.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'boolean',
						'label'       => _x( 'Prices include tax', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
					),
					'vat_id'                          => array(
						'description' => _x( 'VAT ID', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'is_reverse_charge'               => array(
						'description' => _x( 'Is a reverse of charge?', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'is_vat_exempt'                   => array(
						'description' => _x( 'Is a vat exempt?', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
					),
					'is_oss'                          => array(
						'description' => _x( 'Is a OSS invoice?', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
					),
					'is_eu'                           => array(
						'description' => _x( 'Is taxable in EU?', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'is_b2b'                          => array(
						'description' => _x( 'Is a b2b transaction?', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'is_eu_intra_community_supply'    => array(
						'description' => _x( 'Is a EU intra-community supply?', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'is_eu_vat'                       => array(
						'description' => _x( 'Is taxable in a EU VAT country?', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'is_third_country'                => array(
						'description' => _x( 'Is taxable in a third country?', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'is_taxable'                      => array(
						'description' => _x( 'Is taxable?', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
					),
					'round_tax_at_subtotal'           => array(
						'description' => _x( 'Round tax at subtotal?', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
					),
					'relative_path'                   => array(
						'description' => _x( 'Relative path to PDF file.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Relative path', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'default'     => '',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'path'                            => array(
						'description' => _x( 'Absolute path to PDF file.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Absolute path', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'default'     => '',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'formatted_shipping_address'      => array(
						'description' => _x( 'Formatted shipping address data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'label'       => _x( 'Formatted shipping address', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'shipping_address'                => array(
						'description' => _x( 'Shipping Address data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'properties'  => $this->get_address_property_schema(),
					),
					'taxable_country'                 => array(
						'description' => _x( 'Taxable country code in ISO 3166-1 alpha-2 format.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'label'       => _x( 'Taxable country code', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
					),
					'taxable_postcode'                => array(
						'description' => _x( 'Taxable postcode.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
					),
					'taxable_company'                 => array(
						'description' => _x( 'Taxable company.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
					),
					'product_items'                   => array(
						'description' => _x( 'Product items data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array_merge(
								$this->get_taxable_item_property_schema(),
								array(
									'product_id' => array(
										'description' => _x( 'Product ID.', 'storeabill-core', 'woocommerce-germanized-pro' ),
										'type'        => array( 'string', 'null' ),
										'context'     => array( 'view', 'edit' ),
									),
									'sku'        => array(
										'description' => _x( 'SKU.', 'storeabill-core', 'woocommerce-germanized-pro' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'is_virtual' => array(
										'description' => _x( 'Whether this is a virtual item.', 'storeabill-core', 'woocommerce-germanized-pro' ),
										'type'        => 'boolean',
										'context'     => array( 'view', 'edit' ),
									),
									'is_service' => array(
										'description' => _x( 'Whether this item is a service or not.', 'storeabill-core', 'woocommerce-germanized-pro' ),
										'type'        => 'boolean',
										'context'     => array( 'view', 'edit' ),
									),
									'has_differential_taxation' => array(
										'description' => _x( 'Whether this item differential taxed or not.', 'storeabill-core', 'woocommerce-germanized-pro' ),
										'type'        => 'boolean',
										'context'     => array( 'view', 'edit' ),
									),
								)
							),
						),
					),
					'tax_items'                       => array(
						'description' => _x( 'Tax items.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'items'       => array(
							'type'       => 'object',
							'properties' => $this->get_tax_item_property_schema(),
						),
					),
					'shipping_items'                  => array(
						'description' => _x( 'Shipping items.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array_merge(
								$this->get_taxable_item_property_schema(),
								array(
									'enable_split_tax' => array(
										'description' => _x( 'Whether to enable split-tax calculation.', 'storeabill-core', 'woocommerce-germanized-pro' ),
										'type'        => 'boolean',
										'context'     => array( 'view', 'edit' ),
									),
								)
							),
						),
					),
					'fee_items'                       => array(
						'description' => _x( 'Fee lines data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array_merge(
								$this->get_taxable_item_property_schema(),
								array(
									'enable_split_tax' => array(
										'description' => _x( 'Whether to enable split-tax calculation.', 'storeabill-core', 'woocommerce-germanized-pro' ),
										'type'        => 'boolean',
										'context'     => array( 'view', 'edit' ),
									),
								)
							),
						),
					),
					'voucher_items'                   => array(
						'description' => _x( 'Voucher items.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array_merge(
								$this->get_item_properties_schema(),
								array(
									'code'       => array(
										'description' => _x( 'Coupon code.', 'storeabill-core', 'woocommerce-germanized-pro' ),
										'type'        => array( 'string', 'null' ),
										'context'     => array( 'view', 'edit' ),
									),
									'line_total' => array(
										'description' => _x( 'Line total (after discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'price'      => array(
										'description' => _x( 'Product price.', 'storeabill-core', 'woocommerce-germanized-pro' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'total'      => array(
										'description' => _x( 'Product total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
								)
							),
						),
					),
					'totals'                          => array(
						'description' => _x( 'Total data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'properties'  => $this->get_totals_property_schema(),
					),
					'tax_totals'                      => $this->get_tax_totals_schema(),
				)
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	protected function get_tax_totals_schema() {
		return array(
			'description' => _x( 'Tax total data.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'        => 'array',
			'context'     => array( 'view', 'edit' ),
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'total_net'   => array(
						'description' => _x( 'Total net.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'total_tax'   => array(
						'description' => _x( 'Total tax.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'total_gross' => array(
						'description' => _x( 'Total gross.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'tax_rate'    => array(
						'type'       => 'object',
						'properties' => $this->get_tax_rate_property_schema(),
						'readonly'   => true,
					),
					'net_totals'  => array(
						'description' => _x( 'Net totals.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties'  => array(
							'product'  => array(
								'description' => _x( 'Product net total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'shipping' => array(
								'description' => _x( 'Shipping net total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'fee'      => array(
								'description' => _x( 'Fee net total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
					'tax_totals'  => array(
						'description' => _x( 'Tax totals.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties'  => array(
							'product'  => array(
								'description' => _x( 'Product tax total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'shipping' => array(
								'description' => _x( 'Shipping tax total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'fee'      => array(
								'description' => _x( 'Fee tax total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
			),
		);
	}

	protected function get_taxable_item_property_schema() {
		return array_merge(
			$this->get_item_properties_schema(),
			array(
				'is_taxable'          => array(
					'description' => _x( 'Whether the item is taxable or not.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'line_subtotal'       => array(
					'description' => _x( 'Line subtotal (before discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'discount_total'      => array(
					'description' => _x( 'Discount total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'discount_net'        => array(
					'description' => _x( 'Discount net total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'discount_tax'        => array(
					'description' => _x( 'Discount tax total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'discount_percentage' => array(
					'description' => _x( 'Discount percentage.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subtotal_tax'        => array(
					'description' => _x( 'Line subtotal tax (before discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'line_total'          => array(
					'description' => _x( 'Line total (after discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'total_tax'           => array(
					'description' => _x( 'Line total tax (after discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'taxes'               => array(
					'description' => _x( 'Product taxes.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => $this->get_tax_item_property_schema(),
					),
				),
				'price'               => array(
					'description' => _x( 'Product price.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'price_net'           => array(
					'description' => _x( 'Product net price.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'price_tax'           => array(
					'description' => _x( 'Product price tax.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'price_subtotal'      => array(
					'description' => _x( 'Product price (before discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'price_subtotal_tax'  => array(
					'description' => _x( 'Product price tax (before discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'price_subtotal_net'  => array(
					'description' => _x( 'Product net price (before discounts).', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total'               => array(
					'description' => _x( 'Product total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_net'           => array(
					'description' => _x( 'Product net total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subtotal'            => array(
					'description' => _x( 'Product subtotal.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subtotal_net'        => array(
					'description' => _x( 'Product net subtotal.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
	}

	protected function get_tax_item_property_schema() {
		$base_schema = $this->get_item_properties_schema();

		return array_merge(
			$base_schema,
			array(
				'tax_type'              => array(
					'description' => _x( 'Tax type e.g. product.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'round_tax_at_subtotal' => array(
					'description' => _x( 'True in case tax is rounded at subtotal.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'is_oss'                => array(
					'description' => _x( 'Is OSS tax?', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_tax'             => array(
					'description' => _x( 'Tax total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'total_net'             => array(
					'description' => _x( 'Tax net total.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'subtotal_tax'          => array(
					'description' => _x( 'Tax subtotal.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'subtotal_net'          => array(
					'description' => _x( 'Tax net subtotal.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'tax_rate'              => array(
					'type'       => 'object',
					'context'    => array( 'view', 'edit' ),
					'properties' => $this->get_tax_rate_property_schema(),
				),
			)
		);
	}

	protected function get_tax_rate_property_schema() {
		return array(
			'percent'              => array(
				'description' => _x( 'Tax percentage.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'formatted_percentage' => array(
				'description' => _x( 'Formatted tax percentage.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'country'              => array(
				'description' => _x( 'Tax country.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'priority'             => array(
				'description' => _x( 'Tax priority.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			),
			'is_compound'          => array(
				'description' => _x( 'Is compound?', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
			),
			'is_oss'               => array(
				'description' => _x( 'Is OSS?', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
			),
			'label'                => array(
				'description' => _x( 'Tax rate label.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		);
	}
}
