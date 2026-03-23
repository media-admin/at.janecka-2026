<?php

namespace Vendidero\StoreaBill\eInvoice;

use Vendidero\StoreaBill\Interfaces\EInvoiceParty;

defined( 'ABSPATH' ) || exit;

abstract class Party implements EInvoiceParty {

	protected $address = array(
		'first_name'                   => '',
		'last_name'                    => '',
		'company'                      => '',
		'address_1'                    => '',
		'address_2'                    => '',
		'postcode'                     => '',
		'city'                         => '',
		'country'                      => '',
		'state'                        => '',
		'vat_id'                       => '',
		'email'                        => '',
		'phone'                        => '',
		'customer_id'                  => '',
		'local_tax_number'             => '',
		'company_registration_number'  => '',
		'company_id'                   => '',
		'company_id_scheme'            => '',
		'electronic_address_id'        => '',
		'electronic_address_id_scheme' => '',
	);

	public function set_address( $address ) {
		foreach ( (array) $address as $address_field => $address_value ) {
			$setter = "set_{$address_field}";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->{ $setter }( $address_value );
			} elseif ( array_key_exists( $address_field, $this->address ) ) {
				$this->address[ $address_field ] = $address_value;
			}
		}
	}

	public function get_legal_name() {
		return $this->get_company() ? $this->get_company() : $this->get_formatted_full_name();
	}

	public function get_formatted_full_name() {
		return trim( $this->get_first_name() . ' ' . $this->get_last_name() );
	}

	public function get_first_name() {
		return $this->address['first_name'];
	}

	public function get_last_name() {
		return $this->address['last_name'];
	}

	public function get_country() {
		return $this->address['country'];
	}

	public function get_company() {
		return $this->address['company'];
	}

	public function get_company_registration_number() {
		return $this->address['company_registration_number'];
	}

	public function get_customer_id() {
		return $this->address['customer_id'];
	}

	public function get_postcode() {
		return $this->address['postcode'];
	}

	public function get_city() {
		return $this->address['city'];
	}

	public function get_address_1() {
		return $this->address['address_1'];
	}

	public function get_address_2() {
		return $this->address['address_2'];
	}

	public function get_email() {
		return $this->address['email'];
	}

	public function get_phone() {
		return $this->address['phone'];
	}

	public function get_vat_id() {
		return $this->address['vat_id'];
	}

	public function get_local_tax_number() {
		return $this->address['local_tax_number'];
	}

	public function get_company_id() {
		return $this->address['company_id'];
	}

	public function get_company_id_scheme() {
		return $this->address['company_id_scheme'];
	}

	public function set_company_id( $company_id ) {
		$this->address['company_id'] = $company_id;
	}

	public function set_company_id_scheme( $company_id_scheme ) {
		$this->address['company_id_scheme'] = $company_id_scheme;
	}

	public function get_electronic_address() {
		return $this->get_electronic_address_id() && $this->get_electronic_address_id_scheme() ? $this->get_electronic_address_id_scheme() . ':' . $this->get_electronic_address_id() : '';
	}

	public function get_electronic_address_id() {
		if ( empty( $this->address['electronic_address_id'] ) && ! empty( $this->address['vat_id'] ) ) {
			return strtolower( trim( $this->address['vat_id'] ) );
		} else {
			return $this->address['electronic_address_id'];
		}
	}

	public function get_electronic_address_id_scheme() {
		if ( empty( $this->address['electronic_address_id_scheme'] ) && ! empty( $this->address['vat_id'] ) ) {
			return '9914';
		} else {
			return $this->address['electronic_address_id_scheme'];
		}
	}

	public function set_electronic_address( $electronic_address ) {
		$this->set_electronic_address_id( $electronic_address );
	}

	public function set_electronic_address_id( $electronic_address_id ) {
		if ( strstr( $electronic_address_id, ':' ) ) {
			$electronic_address_id_parts = explode( ':', $electronic_address_id );

			if ( 2 === count( $electronic_address_id_parts ) ) {
				$electronic_address_id        = trim( $electronic_address_id_parts[1] );
				$electronic_address_id_scheme = trim( $electronic_address_id_parts[0] );

				$this->set_electronic_address_id_scheme( $electronic_address_id_scheme );
			}
		}

		$this->address['electronic_address_id'] = $electronic_address_id;
	}

	public function set_electronic_address_id_scheme( $electronic_address_id_scheme ) {
		$this->address['electronic_address_id_scheme'] = $electronic_address_id_scheme;
	}
}
