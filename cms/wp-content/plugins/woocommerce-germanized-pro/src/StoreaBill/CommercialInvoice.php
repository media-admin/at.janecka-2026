<?php

namespace Vendidero\Germanized\Pro\StoreaBill;

use Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Helper;
use Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Shipment;
use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Total;
use Vendidero\StoreaBill\Interfaces\Order;
use Vendidero\StoreaBill\Interfaces\Summable;
use Vendidero\StoreaBill\Interfaces\TotalsContainable;
use Vendidero\StoreaBill\Utilities\Numbers;

defined( 'ABSPATH' ) || exit;

class CommercialInvoice extends Document implements TotalsContainable, Summable {

	protected $extra_data = array(
		'order_id'         => 0,
		'order_number'     => '',
		'invoice_type'     => 'commercial',
		'total'            => 0.0,
		'shipping_total'   => 0.0,
		'fee_total'        => 0.0,
		'product_total'    => 0.0,
		'insurance_total'  => 0.0,
		'discount_total'   => 0.0,
		'shipping_address' => array(),
		'sender_address'   => array(),
		'weight'           => 0.0,
		'net_weight'       => 0.0,
		'packaging_weight' => 0.0,
		'currency'         => '',
		'export_type'      => '',
		'incoterms'        => '',
		'export_reason'    => '',
		'length'           => 0.0,
		'width'            => 0.0,
		'height'           => 0.0,
	);

	protected $data_store_name = 'commercial_invoice';

	/**
	 * @var null|Shipment
	 */
	protected $shipment = null;

	/**
	 * @var null|\WC_Order
	 */
	protected $order = null;

	public function get_type() {
		return 'commercial_invoice';
	}

	public function get_item_types() {
		return apply_filters(
			$this->get_hook_prefix() . 'item_types',
			array(
				'product',
			),
			$this
		);
	}

	public function get_type_title( $label_type = 'singular' ) {
		$types      = Helper::get_available_invoice_types();
		$type_title = _x( 'Commercial Invoice', 'commercial-invoice', 'woocommerce-germanized-pro' );

		if ( array_key_exists( $this->get_invoice_type(), $types ) ) {
			$type_title = $types[ $this->get_invoice_type() ];
		}

		return $type_title;
	}

	public function get_data() {
		$data = parent::get_data();

		// Force core address data to exist
		$address_fields = apply_filters(
			"{$this->get_general_hook_prefix()}shipping_address_fields",
			array(
				'first_name' => '',
				'last_name'  => '',
				'company'    => '',
				'address_1'  => '',
				'address_2'  => '',
				'city'       => '',
				'state'      => '',
				'postcode'   => '',
				'country'    => '',
				'vat_id'     => '',
				'email'      => '',
			),
			$this
		);

		foreach ( $address_fields as $field => $default_value ) {
			if ( ! isset( $data['shipping_address'][ $field ] ) ) {
				$data['shipping_address'][ $field ] = $default_value;
			}
		}

		$data['shipment_id']                = $this->get_shipment_id();
		$data['shipment_number']            = $this->get_shipment_number();
		$data['formatted_shipping_address'] = $this->get_formatted_shipping_address();
		$data['formatted_sender_address']   = $this->get_formatted_sender_address();
		$data['shipping_provider_title']    = $this->get_shipping_provider_title();
		$data['formatted_incoterms']        = $this->get_formatted_incoterms();
		$data['formatted_export_type']      = $this->get_formatted_export_type();
		$data['formatted_export_reason']    = $this->get_formatted_export_reason();
		$data['tracking_id']                = $this->get_tracking_id();
		$data['totals']                     = $this->get_totals();
		$data['order_id']                   = $this->get_order_id();
		$data['order_number']               = $this->get_order_number();
		$data['packaging_weight']           = $this->get_packaging_weight();
		$data['countries_of_origin']        = $this->get_countries_of_origin();
		$data['formatted_full_sender_name'] = $this->get_formatted_full_sender_name();

		return $data;
	}

	/**
	 * Returns item types used to calculate totals.
	 *
	 * @return array
	 */
	public function get_item_types_for_totals() {
		$item_types = apply_filters(
			$this->get_hook_prefix() . 'item_types_for_total',
			array(
				'product',
			),
			$this
		);

		return $item_types;
	}

	public function get_countries_of_origin() {
		$countries = array();

		foreach ( $this->get_items() as $item ) {
			$countries[] = $item->get_manufacture_country();
		}

		return array_unique( $countries );
	}

	public function calculate_weights() {
		$item_net_weights = array();
		$net_weight       = (int) ceil( (float) wc_get_weight( $this->get_net_weight(), 'g', 'kg' ) );
		$items            = $this->get_items( 'product' );

		foreach ( $items as $item ) {
			$item_net_weights[ $item->get_id() ] = (int) ceil( wc_get_weight( (float) ( $item->get_weight() * $item->get_quantity() ), 'g', 'kg' ) );
		}

		$item_total_net_weight = array_sum( $item_net_weights );

		if ( $item_total_net_weight < $net_weight ) {
			$remaining          = absint( $net_weight - $item_total_net_weight );
			$item_keys          = array_keys( $items );
			$current_item_index = 0;

			if ( count( $item_keys ) > 0 ) {
				/**
				 * Apply the remaining weight per 1g
				 */
				for ( $i = 0; $i < $remaining; $i++ ) {
					if ( ! isset( $items[ $current_item_index ] ) ) {
						$current_item_index = 0;
					}

					$current_item_key = $item_keys[ $current_item_index ];
					$items[ $current_item_key ]->set_weight( (float) $item->get_weight() + 0.001 );
				}
			}
		} elseif ( $item_total_net_weight > $net_weight ) {
			$this->set_net_weight( (float) wc_get_weight( $item_total_net_weight, 'kg', 'g' ) );
		}

		$this->set_weight( $this->get_net_weight() + $this->get_packaging_weight() );
	}

	public function calculate_totals() {
		do_action( "{$this->get_general_hook_prefix()}before_calculate_totals", $this );

		$totals            = array_fill_keys( array_keys( array_flip( $this->get_item_types_for_totals() ) ), 0 );
		$round_at_subtotal = 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
		$errors            = new \WP_Error();

		foreach ( $this->get_items( $this->get_item_types_for_totals() ) as $item ) {
			if ( is_a( $item, '\Vendidero\StoreaBill\Interfaces\Summable' ) && array_key_exists( $item->get_item_type(), $totals ) ) {
				$item->calculate_totals();

				$total                             = sab_add_number_precision( $item->get_total(), false );
				$totals[ $item->get_item_type() ] += ( ! $round_at_subtotal ) ? Numbers::round( $total ) : $total;
			}
		}

		foreach ( $totals as $key => $item_total ) {
			$item_total = sab_remove_number_precision( $item_total );

			try {
				$setter = "set_{$key}_total";
				if ( is_callable( array( $this, $setter ) ) ) {
					$reflection = new \ReflectionMethod( $this, $setter );

					if ( $reflection->isPublic() ) {
						$this->{$setter}( $item_total );
					}
				} else {
					// Save as meta data
					$this->update_meta_data( $key . '_total', sab_format_decimal( $item_total ) );
				}
			} catch ( \Exception $e ) {
				$errors->add( $e->getErrorCode(), $e->getMessage() );
			}
		}

		$this->update_total();

		return count( $errors->get_error_codes() ) ? $errors : true;
	}

	public function format_address( $address_data ) {
		add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true', 1, 2000 );

		// Make sure that Woo reloads address formats
		WC()->countries->address_formats = array();

		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'formatted_address_replacements_callback' ), 10, 2 );
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'localisation_address_formats_callback' ), 0, 1 );

		$formatted_address = WC()->countries->get_formatted_address( $address_data );

		// Make sure that Woo reloads address formats
		WC()->countries->address_formats = array();

		remove_filter( 'woocommerce_formatted_address_replacements', array( $this, 'formatted_address_replacements_callback' ), 10 );
		remove_filter( 'woocommerce_localisation_address_formats', array( $this, 'localisation_address_formats_callback' ), 0 );

		return $formatted_address;
	}

	public function localisation_address_formats_callback( $countries ) {
		foreach ( $countries as $country => $value ) {
			if ( ! strstr( $countries[ $country ], '{phone}' ) ) {
				$countries[ $country ] .= "\n{phone}";
			}
			if ( ! strstr( $countries[ $country ], '{customs_reference_number}' ) ) {
				$countries[ $country ] .= "\n{customs_reference_number}";
			}
		}

		return $countries;
	}

	public function formatted_address_replacements_callback( $address_formats, $args ) {
		$address_formats['{phone}']                    = ! empty( $args['phone'] ) ? _x( 'Phone: ', 'commercial-invoice', 'woocommerce-germanized-pro' ) . $args['phone'] : '';
		$address_formats['{customs_reference_number}'] = ! empty( $args['customs_reference_number'] ) ? _x( 'EORI Number: ', 'commercial-invoice', 'woocommerce-germanized-pro' ) . $args['customs_reference_number'] : '';

		return $address_formats;
	}

	public function update_total() {
		$total  = 0.0;
		$errors = new \WP_Error();

		foreach ( $this->get_item_types_for_totals() as $item_type ) {
			try {
				$getter = "get_{$item_type}_total";

				if ( is_callable( array( $this, $getter ) ) ) {
					$reflection = new \ReflectionMethod( $this, $getter );

					if ( $reflection->isPublic() ) {
						$total += (float) $this->{$getter}( 'total' );
					}
				} else {
					// Try to get total from meta
					$total += (float) $this->get_meta( $item_type . '_total', true, 'total' );
				}
			} catch ( \Exception $e ) {
				$errors->add( $e->getErrorCode(), $e->getMessage() );
			}
		}

		$this->set_total( Numbers::round_to_precision( $total + $this->get_shipping_total() + $this->get_insurance_total() + $this->get_fee_total() ) );

		return count( $errors->get_error_codes() ) ? $errors : true;
	}

	/**
	 * @return bool|Order
	 */
	public function get_reference() {
		if ( is_null( $this->shipment ) ) {
			try {
				$this->shipment = new Shipment( $this->get_shipment_id() );
			} catch ( \Exception $e ) {
				$this->shipment = false;
			}
		}

		return $this->shipment;
	}

	public function get_shipment() {
		return $this->get_reference();
	}

	public function get_shipment_id( $context = 'view' ) {
		return $this->get_reference_id( $context );
	}

	public function has_differing_shipping_address() {
		$billing_address  = $this->get_address();
		$shipping_address = $this->get_shipping_address();

		if ( ! empty( $billing_address ) && ! empty( $shipping_address ) ) {
			foreach ( $billing_address as $billing_address_key => $billing_address_value ) {
				if ( isset( $shipping_address[ $billing_address_key ] ) ) {
					$shipping_address_value = $shipping_address[ $billing_address_key ];

					if ( ! empty( $billing_address_value ) && ! empty( $shipping_address_value ) && strcmp( $billing_address_value, $shipping_address_value ) !== 0 ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * @return \Vendidero\StoreaBill\Document\Total[]
	 */
	/**
	 * @return Total[]
	 */
	public function get_totals( $type = '' ) {
		$doc_type        = sab_get_document_type( $this->get_type() );
		$total_types     = array_keys( $doc_type->total_types );
		$document_totals = array();

		if ( ! empty( $type ) ) {
			$type        = is_array( $type ) ? $type : array( $type );
			$valid_types = array_intersect( $type, $total_types );

			if ( ! empty( $valid_types ) ) {
				$total_types = $valid_types;
			}
		}

		foreach ( $total_types as $total_type ) {
			if ( has_filter( "{$this->get_hook_prefix()}total_type_{$total_type}" ) ) {
				$document_totals = array_merge( apply_filters( "{$this->get_hook_prefix()}total_type_{$total_type}", array(), $this, $total_type ), $document_totals );
			} elseif ( 'weight' === substr( $total_type, -6 ) ) {
				$getter = 'get_' . $total_type;
				$total  = false;

				if ( is_callable( array( $this, $getter ) ) ) {
					$total = (float) $this->$getter();
				}

				if ( false !== $total ) {
					$document_totals[] = new Total(
						$this,
						array(
							'total'     => $total,
							'type'      => $total_type,
							'unit'      => 'kg',
							'unit_type' => 'weight',
						)
					);
				}
			} elseif ( 'item_count' === $total_type ) {
				$total = $this->get_item_count();

				if ( false !== $total ) {
					$document_totals[] = new Total(
						$this,
						array(
							'total'     => $total,
							'type'      => $total_type,
							'unit_type' => 'quantity',
						)
					);
				}
			} elseif ( in_array( $total_type, array( 'length', 'width', 'height' ), true ) ) {
				$getter = 'get_' . $total_type;
				$total  = false;

				if ( is_callable( array( $this, $getter ) ) ) {
					$total = (float) $this->$getter();
				}

				if ( false !== $total ) {
					$document_totals[] = new Total(
						$this,
						array(
							'total'     => $total,
							'type'      => $total_type,
							'unit'      => 'cm',
							'unit_type' => 'dimension',
						)
					);
				}
			} else {
				$getter         = 'get_' . $total_type . '_total';
				$getter_reverse = 'get_total_' . $total_type;
				$total          = false;

				// Support total or subtotal type
				if ( strpos( $total_type, 'total' ) !== false ) {
					$getter = 'get_' . $total_type;
				}

				if ( is_callable( array( $this, $getter ) ) ) {
					$total = (float) $this->$getter();
				} elseif ( is_callable( array( $this, $getter_reverse ) ) ) {
					$total = (float) $this->$getter_reverse();
				}

				$placeholders = array();

				if ( false !== $total ) {
					$document_totals[] = new Total(
						$this,
						array(
							'total'        => $total,
							'type'         => $total_type,
							'placeholders' => $placeholders,
						)
					);
				}
			}
		}

		return apply_filters( "{$this->get_hook_prefix()}_totals", $document_totals, $this );
	}

	public function set_shipment_id( $id ) {
		$this->set_reference_id( $id );
	}

	public function get_shipment_number( $context = 'view' ) {
		return $this->get_reference_number( $context );
	}

	public function get_order_id( $context = 'view' ) {
		return $this->get_prop( 'order_id', $context );
	}

	public function get_total( $context = 'view' ) {
		return $this->get_prop( 'total', $context );
	}

	public function get_subtotal() {
		return $this->get_total();
	}

	public function set_total( $value ) {
		$this->set_prop( 'total', Numbers::round_to_precision( sab_format_decimal( $value ) ) );
	}

	public function get_product_total( $context = 'view' ) {
		return $this->get_prop( 'product_total', $context );
	}

	public function set_product_total( $value ) {
		$this->set_prop( 'product_total', Numbers::round_to_precision( sab_format_decimal( $value ) ) );
	}

	public function get_shipping_total( $context = 'view' ) {
		return $this->get_prop( 'shipping_total', $context );
	}

	public function set_shipping_total( $value ) {
		$this->set_prop( 'shipping_total', Numbers::round_to_precision( sab_format_decimal( $value ) ) );
	}

	public function get_insurance_total( $context = 'view' ) {
		return $this->get_prop( 'insurance_total', $context );
	}

	public function set_insurance_total( $value ) {
		$this->set_prop( 'insurance_total', Numbers::round_to_precision( sab_format_decimal( $value ) ) );
	}

	public function get_fee_total( $context = 'view' ) {
		return $this->get_prop( 'fee_total', $context );
	}

	public function set_fee_total( $value ) {
		$this->set_prop( 'fee_total', Numbers::round_to_precision( sab_format_decimal( $value ) ) );
	}

	public function get_discount_total( $context = 'view' ) {
		return $this->get_prop( 'discount_total', $context );
	}

	public function set_discount_total( $value ) {
		$this->set_prop( 'discount_total', Numbers::round_to_precision( sab_format_decimal( $value ) ) );
	}

	public function get_weight( $context = 'view' ) {
		return $this->get_prop( 'weight', $context );
	}

	public function set_weight( $value ) {
		$this->set_prop( 'weight', Numbers::round_to_precision( sab_format_decimal( $value ), 3 ) );
	}

	public function get_width( $context = 'view' ) {
		return $this->get_prop( 'width', $context );
	}

	public function set_width( $value ) {
		$this->set_prop( 'width', Numbers::round_to_precision( sab_format_decimal( $value ), 2 ) );
	}

	public function get_length( $context = 'view' ) {
		return $this->get_prop( 'length', $context );
	}

	public function set_length( $value ) {
		$this->set_prop( 'length', Numbers::round_to_precision( sab_format_decimal( $value ), 2 ) );
	}

	public function get_height( $context = 'view' ) {
		return $this->get_prop( 'height', $context );
	}

	public function set_height( $value ) {
		$this->set_prop( 'height', Numbers::round_to_precision( sab_format_decimal( $value ), 2 ) );
	}

	public function get_dimensions( $context = 'view' ) {
		return array(
			'length' => $this->get_length( $context ),
			'width'  => $this->get_width( $context ),
			'height' => $this->get_height( $context ),
		);
	}

	public function get_net_weight( $context = 'view' ) {
		return $this->get_prop( 'net_weight', $context );
	}

	public function set_net_weight( $value ) {
		$this->set_prop( 'net_weight', Numbers::round_to_precision( sab_format_decimal( $value ), 3 ) );
	}

	public function get_packaging_weight( $context = 'view' ) {
		return $this->get_prop( 'packaging_weight', $context );
	}

	public function set_packaging_weight( $value ) {
		$this->set_prop( 'packaging_weight', Numbers::round_to_precision( sab_format_decimal( $value ), 3 ) );
	}

	public function get_export_type( $context = 'view' ) {
		return $this->get_prop( 'export_type', $context );
	}

	public function get_formatted_export_type( $context = 'view' ) {
		$export_types = Helper::get_available_export_types();
		$export_type  = $this->get_export_type( $context );

		return array_key_exists( $export_type, $export_types ) ? $export_types[ $export_type ] : '';
	}

	public function set_export_type( $value ) {
		$this->set_prop( 'export_type', $value );
	}

	public function get_incoterms( $context = 'view' ) {
		return $this->get_prop( 'incoterms', $context );
	}

	public function get_formatted_incoterms( $context = 'view' ) {
		$available_incoterms = Helper::get_available_incoterms();
		$incoterms           = $this->get_incoterms( $context );

		return array_key_exists( $incoterms, $available_incoterms ) ? $available_incoterms[ $incoterms ] : '';
	}

	public function set_incoterms( $value ) {
		$this->set_prop( 'incoterms', strtoupper( $value ) );
	}

	public function get_export_reason( $context = 'view' ) {
		return $this->get_prop( 'export_reason', $context );
	}

	public function get_formatted_export_reason( $context = 'view' ) {
		$reasons = Helper::get_available_export_reasons();
		$reason  = $this->get_export_reason( $context );

		return array_key_exists( $reason, $reasons ) ? $reasons[ $reason ] : '';
	}

	public function set_export_reason( $value ) {
		$this->set_prop( 'export_reason', $value );
	}

	public function get_currency( $context = 'view' ) {
		return $this->get_prop( 'currency', $context );
	}

	public function set_currency( $currency ) {
		$this->set_prop( 'currency', $currency );
	}

	public function get_invoice_type( $context = 'view' ) {
		return $this->get_prop( 'invoice_type', $context );
	}

	public function set_invoice_type( $type ) {
		if ( ! array_key_exists( $type, Helper::get_available_invoice_types() ) ) {
			$type = 'commercial';
		}

		$this->set_prop( 'invoice_type', $type );
	}

	public function is_proforma( $context = 'view' ) {
		return 'proforma' === $this->get_invoice_type( $context );
	}

	public function get_order_number( $context = 'view' ) {
		$order_number = $this->get_prop( 'order_number', $context );

		if ( 'view' === $context && empty( $order_number ) ) {
			$order_number = $this->get_order_id();
		}

		return $order_number;
	}

	/**
	 * Returns the address properties.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string[]
	 */
	public function get_shipping_address( $context = 'view' ) {
		$address = $this->get_prop( 'shipping_address', $context );

		if ( 'view' === $context && empty( $address ) ) {
			$address = $this->get_address();
		}

		return $address;
	}

	/**
	 * Set shipping address.
	 *
	 * @param string[] $address The address props.
	 */
	public function set_shipping_address( $address ) {
		$address = empty( $address ) ? array() : (array) $address;

		foreach ( $address as $prop => $value ) {
			$setter = "set_shipping_{$prop}";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->{$setter}( $value );
			} else {
				$this->set_shipping_address_prop( $prop, $value );
			}
		}
	}

	protected function set_shipping_address_prop( $prop, $data ) {
		$address          = $this->get_shipping_address( 'edit' );
		$address[ $prop ] = $data;

		$this->set_prop( 'shipping_address', $address );
	}

	/**
	 * Returns the formatted shipping address.
	 *
	 * @param  string $empty_content Content to show if no address is present.
	 * @return string
	 */
	public function get_formatted_shipping_address( $empty_content = '' ) {
		$address = $this->format_address( $this->get_shipping_address() );

		return apply_filters( "{$this->get_hook_prefix()}formatted_shipping_address", ( $address ? $address : $empty_content ), $this );
	}

	public function get_formatted_address( $empty_content = '' ) {
		$address = $this->format_address( $this->get_address() );

		return apply_filters( "{$this->get_hook_prefix()}formatted_address", ( $address ? $address : $empty_content ), $this );
	}

	/**
	 * Returns the formatted sender address.
	 *
	 * @param  string $empty_content Content to show if no address is present.
	 * @return string
	 */
	public function get_formatted_sender_address( $empty_content = '' ) {
		$address = $this->format_address( $this->get_sender_address() );

		return apply_filters( "{$this->get_hook_prefix()}formatted_sender_address", ( $address ? $address : $empty_content ), $this );
	}

	/**
	 * Returns the formatted sender name.
	 *
	 * @return string
	 */
	public function get_formatted_full_sender_name() {
		return sprintf( _x( '%1$s %2$s', 'commercial-invoice-full-name', 'woocommerce-germanized-pro' ), $this->get_sender_first_name(), $this->get_sender_last_name() );
	}

	public function get_sender_first_name( $context = 'view' ) {
		return $this->get_sender_address_prop( 'first_name', $context );
	}

	public function get_sender_last_name( $context = 'view' ) {
		return $this->get_sender_address_prop( 'last_name', $context );
	}

	/**
	 * Returns the address properties.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string[]
	 */
	public function get_sender_address( $context = 'view' ) {
		$address = $this->get_prop( 'sender_address', $context );

		if ( 'view' === $context && empty( $address ) ) {
			$address = function_exists( 'wc_stc_get_shipment_setting_address_fields' ) ? wc_stc_get_shipment_setting_address_fields() : array();
		}

		return $address;
	}

	/**
	 * Set sender address.
	 *
	 * @param string[] $address The address props.
	 */
	public function set_sender_address( $address ) {
		$address = empty( $address ) ? array() : (array) $address;

		foreach ( $address as $prop => $value ) {
			$setter = "set_sender_{$prop}";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->{$setter}( $value );
			} else {
				$this->set_sender_address_prop( $prop, $value );
			}
		}
	}

	protected function set_sender_address_prop( $prop, $data ) {
		$address          = $this->get_sender_address( 'edit' );
		$address[ $prop ] = $data;

		$this->set_prop( 'sender_address', $address );
	}

	public function get_sender_address_prop( $prop, $context = 'view' ) {
		$value          = '';
		$sender_address = $this->get_sender_address( $context );

		if ( isset( $this->changes['sender_address'][ $prop ] ) ) {
			$value = $this->changes['sender_address'][ $prop ];
		} elseif ( array_key_exists( $prop, $sender_address ) ) {
			$value = $sender_address[ $prop ];
		}

		if ( 'view' === $context ) {
			/**
			 * Filter to adjust a commercial invoices sender address property e.g. first_name.
			 *
			 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
			 * unique hook for a document type. `$prop` refers to the actual address property e.g. first_name.
			 *
			 * Example hook name: storeabill_commercial_invoice_get_sender_address_first_name
			 *
			 * @param string   $value The address property value.
			 * @param Document $this The document object.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			$value = apply_filters( "{$this->get_hook_prefix()}sender_address_{$prop}", $value, $this );
		}

		return $value;
	}

	public function set_order_id( $id ) {
		$this->set_prop( 'order_id', absint( $id ) );

		$this->order = null;
	}

	public function set_order_number( $number ) {
		$this->set_prop( 'order_number', $number );
	}

	public function set_reference_id( $reference_id ) {
		parent::set_reference_id( $reference_id );

		$this->shipment = null;
	}

	public function get_shipping_provider_title() {
		return $this->get_shipment() ? $this->get_shipment()->get_shipping_provider_title() : '';
	}

	public function get_tracking_id() {
		return $this->get_shipment() ? $this->get_shipment()->get_tracking_id() : '';
	}

	/**
	 * @return bool|\WC_Order
	 */
	public function get_order() {
		if ( is_null( $this->order ) ) {
			$this->order = wc_get_order( $this->get_order_id() );
		}

		return $this->order;
	}

	public function get_formatted_weight( $weight, $unit = 'kg' ) {
		$weight_string = sab_format_localized_decimal( $weight );

		if ( '' !== $weight_string ) {
			$unit           = empty( $unit ) ? get_option( 'woocommerce_weight_unit' ) : $unit;
			$weight_string .= ' ' . $unit;
		} else {
			$weight_string = '';
		}

		return $weight_string;
	}

	public function get_formatted_dimension( $dimension, $unit = 'cm' ) {
		$dim_string = sab_format_localized_decimal( $dimension );

		if ( ! empty( $dim_string ) ) {
			$unit        = empty( $unit ) ? get_option( 'woocommerce_dimension_unit' ) : $unit;
			$dim_string .= ' ' . $unit;
		} else {
			$dim_string = '';
		}

		return $dim_string;
	}

	/**
	 * Returns a formatted price based on internal options.
	 *
	 * @param string $price
	 *
	 * @return string
	 */
	public function get_formatted_price( $price, $type = '' ) {
		$args = array(
			'currency' => $this->get_currency(),
		);

		return sab_format_price( $price, $args );
	}

	protected function get_additional_number_placeholders() {
		return array(
			'{shipment_number}' => $this->get_shipment_number(),
			'{order_number}'    => $this->get_order_number(),
		);
	}
}
