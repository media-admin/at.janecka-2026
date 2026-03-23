<?php

namespace Vendidero\StoreaBill\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Total class
 */
class Total {

	protected $total = 0.0;

	/**
	 * @var null|Document
	 */
	protected $document = null;

	protected $type = '';

	protected $placeholders = array();

	protected $label = '';

	protected $unit = '';

	protected $unit_type = 'currency';

	public function __construct( $document, $args = array() ) {
		$this->document = $document;

		foreach ( $args as $key => $arg ) {
			$setter = 'set_' . $key;

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->$setter( $arg );
			}
		}
	}

	public function get_type() {
		return $this->type;
	}

	public function set_type( $type ) {
		$this->type = $type;
	}

	protected function get_general_hook_prefix() {
		return "storabill_{$this->document->get_type()}_total_";
	}

	protected function get_hook_prefix() {
		return "{$this->get_general_hook_prefix()}get_";
	}

	public function set_unit( $unit ) {
		$this->unit = $unit;
	}

	/**
	 * @return Document|null
	 */
	public function get_document() {
		return $this->document;
	}

	public function get_unit() {
		return apply_filters( "{$this->get_hook_prefix()}unit", $this->unit, $this );
	}

	public function set_unit_type( $unit_type ) {
		$this->unit_type = $unit_type;
	}

	public function get_unit_type() {
		return apply_filters( "{$this->get_hook_prefix()}unit_type", $this->unit_type, $this );
	}

	/**
	 * @return float
	 */
	public function get_total() {
		return (float) $this->total;
	}

	public function set_total( $total ) {
		$this->total = (float) $total;
	}

	public function get_placeholders() {
		return apply_filters( "{$this->get_hook_prefix()}placeholders", $this->placeholders, $this );
	}

	public function set_placeholders( $placeholders ) {
		$this->placeholders = (array) $placeholders;
	}

	public function replace( $str ) {
		$placeholders = $this->get_placeholders();

		/**
		 * In case this seems to be a default title (containing print arguments e.g. %s)
		 * replace with placeholders.
		 */
		if ( ! empty( $placeholders ) && strpos( $str, '%s' ) !== false ) {
			$str = vsprintf( $str, array_keys( $placeholders ) );
		}

		$str = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $str );

		return $str;
	}

	public function get_label() {
		$label = $this->label;

		if ( empty( $label ) ) {
			/**
			 * Search the default label
			 */
			if ( $document_type = sab_get_document_type( $this->document->get_type() ) ) {
				$types = $document_type->total_types;

				if ( array_key_exists( $this->get_type(), $types ) ) {
					$label = $types[ $this->get_type() ]['title'];
				}
			}
		}

		return apply_filters( "{$this->get_hook_prefix()}label", $label, $this );
	}

	public function get_formatted_label() {
		return apply_filters( "{$this->get_hook_prefix()}formatted_label", $this->replace( $this->get_label() ), $this );
	}

	public function set_label( $label ) {
		$this->label = $label;
	}

	public function get_formatted_total() {
		$formatted_total = sab_format_localized_decimal( $this->get_total() );

		if ( 'currency' === $this->get_unit_type() ) {
			$formatted_total = ( is_callable( array( $this->document, 'get_formatted_price' ) ) ? $this->document->get_formatted_price( $this->get_total(), $this->get_type() ) : sab_format_price( $this->get_total() ) );
		} elseif ( 'weight' === $this->get_unit_type() ) {
			$formatted_total = ( is_callable( array( $this->document, 'get_formatted_weight' ) ) ? $this->document->get_formatted_weight( $this->get_total(), $this->get_unit() ) : $formatted_total );
		} elseif ( 'dimension' === $this->get_unit_type() ) {
			$formatted_total = ( is_callable( array( $this->document, 'get_formatted_dimension' ) ) ? $this->document->get_formatted_dimension( $this->get_total(), $this->get_unit() ) : $formatted_total );
		} elseif ( 'quantity' === $this->get_unit_type() ) {
			$formatted_total = absint( $this->get_total() );
		}

		return $formatted_total;
	}

	public function get_data() {
		return array(
			'total'           => $this->get_total(),
			'total_formatted' => $this->get_formatted_total(),
			'label'           => $this->get_label(),
			'label_formatted' => $this->get_formatted_label(),
			'unit'            => $this->get_unit(),
			'unit_type'       => $this->get_unit_type(),
			'placeholders'    => $this->get_placeholders(),
			'type'            => $this->get_type(),
		);
	}
}
