<?php

namespace Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;

use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Utilities\Numbers;

defined( 'ABSPATH' ) || exit;

class ProductItem extends \Vendidero\Germanized\Pro\StoreaBill\Shipments\ProductItem {

	protected $extra_data = array(
		'sku'                 => '',
		'price'               => 0.0,
		'total'               => 0.0,
		'weight'              => 0.0,
		'hs_code'             => '',
		'mid_code'            => '',
		'manufacture_country' => '',
		'length'              => 0.0,
		'width'               => 0.0,
		'height'              => 0.0,
	);

	public function get_item_type() {
		return 'product';
	}

	public function get_document_group() {
		return 'customs';
	}

	public function get_total( $context = 'view' ) {
		return $this->get_prop( 'total', $context );
	}

	public function set_total( $value ) {
		$this->set_prop( 'total', Numbers::round_to_precision( sab_format_decimal( $value ) ) );
	}

	public function get_price( $context = 'view' ) {
		return $this->get_prop( 'price', $context );
	}

	public function set_price( $value ) {
		$this->set_prop( 'price', Numbers::round_to_precision( sab_format_decimal( $value ) ) );
	}

	public function get_weight( $context = 'view' ) {
		return $this->get_prop( 'weight', $context );
	}

	public function set_weight( $value ) {
		$this->set_prop( 'weight', Numbers::round_to_precision( sab_format_decimal( $value ), 3 ) );
	}

	public function get_length( $context = 'view' ) {
		return $this->get_prop( 'length', $context );
	}

	public function set_length( $value ) {
		$this->set_prop( 'length', Numbers::round_to_precision( sab_format_decimal( $value ), 2 ) );
	}

	public function get_width( $context = 'view' ) {
		return $this->get_prop( 'width', $context );
	}

	public function set_width( $value ) {
		$this->set_prop( 'width', Numbers::round_to_precision( sab_format_decimal( $value ), 2 ) );
	}

	public function get_height( $context = 'view' ) {
		return $this->get_prop( 'height', $context );
	}

	public function set_height( $value ) {
		$this->set_prop( 'height', Numbers::round_to_precision( sab_format_decimal( $value ), 2 ) );
	}

	public function get_hs_code( $context = 'view' ) {
		return $this->get_prop( 'hs_code', $context );
	}

	public function set_hs_code( $value ) {
		$this->set_prop( 'hs_code', $value );
	}

	public function get_mid_code( $context = 'view' ) {
		return $this->get_prop( 'mid_code', $context );
	}

	public function set_mid_code( $value ) {
		$this->set_prop( 'mid_code', $value );
	}

	public function get_manufacture_country( $context = 'view' ) {
		return $this->get_prop( 'manufacture_country', $context );
	}

	public function set_manufacture_country( $value ) {
		$this->set_prop( 'manufacture_country', $value );
	}

	public function get_formatted_weight( $weight ) {
		if ( $document = $this->get_document() ) {
			return $document->get_formatted_weight( $weight );
		}

		return $weight;
	}
}
