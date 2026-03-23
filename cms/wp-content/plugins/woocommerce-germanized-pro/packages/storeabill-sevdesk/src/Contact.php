<?php

namespace Vendidero\StoreaBill\sevDesk;

use Vendidero\StoreaBill\ExternalSync\SyncData;
use Vendidero\StoreaBill\References\Customer;
use Vendidero\StoreaBill\sevDesk\API\Models;

defined( 'ABSPATH' ) || exit;

class Contact {

	protected $data = array(
		'first_name'       => '',
		'last_name'        => '',
		'vat_id'           => '',
		'is_vat_exempt'    => false,
		'number'           => '',
		'title'            => '',
		'academic_title'   => '',
		'address'          => array(
			'street'   => '',
			'zip'      => '',
			'city'     => '',
			'category' => '',
			'type'     => '',
			'country'  => '',
		),
		'shipping_address' => array(
			'street'   => '',
			'zip'      => '',
			'city'     => '',
			'category' => '',
			'type'     => '',
			'country'  => '',
		),
		'phone'            => '',
		'email'            => '',
		'company'          => '',
		'company_number'   => '',
	);

	protected $sync_data = array(
		'id'                  => 0,
		'company_id'          => 0,
		'address_id'          => 0,
		'shipping_address_id' => 0,
		'phone_id'            => 0,
		'email_id'            => 0,
	);

	protected $current_customer = null;

	protected $current_company = null;

	/**
	 * @var null|Models
	 */
	protected $api = null;

	/**
	 * @var null|Customer
	 */
	protected $customer = null;

	/**
	 * Contact constructor.
	 *
	 * @param $args
	 * @param $api
	 * @param bool|SyncData|array $sync_data
	 */
	public function __construct( $args, $api, $sync_data = false ) {
		$this->api = $api;

		foreach ( $args as $key => $arg ) {
			if ( 'customer' === $key ) {
				$this->customer = $arg;
			} else {
				$this->set( $key, $arg );
			}
		}

		$sync_props = array();

		if ( $sync_data && is_a( $sync_data, '\Vendidero\StoreaBill\ExternalSync\SyncData' ) ) {
			$sync_props = $sync_data->get_data();
		} elseif ( is_array( $sync_data ) ) {
			$sync_props = $sync_data;
		}

		foreach ( $sync_props as $prop => $data ) {
			if ( array_key_exists( $prop, $this->sync_data ) ) {
				$setter = "set_{$prop}";

				if ( is_callable( array( $this, $setter ) ) ) {
					$this->$setter( $data );
				} else {
					$this->sync_data[ $prop ] = $data;
				}
			}
		}
	}

	public function set_customer( $customer ) {
		$this->customer = $customer;
	}

	/**
	 * @return Customer|null
	 */
	public function get_customer() {
		return $this->customer;
	}

	public function is_new() {
		$this->current_customer = null;

		if ( $this->get_id() <= 0 ) {
			return true;
		} else {
			$api_result = $this->api->get_contact( $this->get_id() );

			if ( $this->api->is_404( $api_result ) ) {
				return true;
			} else {
				if ( ! $this->api->has_failed( $api_result ) ) {
					$this->current_customer = $api_result['objects'][0];
				}

				return false;
			}
		}
	}

	public function is_new_company() {
		$this->current_company = null;

		if ( $this->get_company_id() <= 0 ) {
			return true;
		} else {
			$api_result = $this->api->get_contact( $this->get_company_id() );

			if ( $this->api->is_404( $api_result ) ) {
				return true;
			} else {
				if ( ! $this->api->has_failed( $api_result ) ) {
					$this->current_company = $api_result['objects'][0];
				}

				return false;
			}
		}
	}

	public function is_vat_exempt() {
		return true === $this->get( 'is_vat_exempt' );
	}

	public function has_company() {
		return ! empty( $this->get_company() );
	}

	public function get_first_name() {
		return $this->get( 'first_name' );
	}

	public function get_academic_title() {
		return $this->get( 'academic_title' );
	}

	public function get_last_name() {
		return $this->get( 'last_name' );
	}

	public function get_company() {
		return $this->get( 'company' );
	}

	public function get_phone() {
		return $this->get( 'phone' );
	}

	public function get_email() {
		return $this->get( 'email' );
	}

	public function get_number() {
		return $this->get( 'number' );
	}

	public function get_formatted_number() {
		return $this->get_number();
	}

	public function get_company_number() {
		return $this->get( 'company_number' );
	}

	public function get_formatted_company_number() {
		return $this->get_company_number();
	}

	public function get_vat_id() {
		return $this->get( 'vat_id' );
	}

	public function get_id() {
		return $this->sync_data['id'];
	}

	public function set_id( $id ) {
		$this->sync_data['id'] = absint( $id );
	}

	public function get_address_id() {
		return $this->sync_data['address_id'];
	}

	public function set_address_id( $id ) {
		$this->sync_data['address_id'] = absint( $id );
	}

	public function get_shipping_address_id() {
		return $this->sync_data['shipping_address_id'];
	}

	public function set_shipping_address_id( $id ) {
		$this->sync_data['shipping_address_id'] = absint( $id );
	}

	public function get_phone_id() {
		return $this->sync_data['phone_id'];
	}

	public function set_phone_id( $id ) {
		$this->sync_data['phone_id'] = absint( $id );
	}

	public function get_email_id() {
		return $this->sync_data['email_id'];
	}

	public function set_email_id( $id ) {
		$this->sync_data['email_id'] = absint( $id );
	}

	public function get_company_id() {
		return $this->sync_data['company_id'];
	}

	public function set_company_id( $id ) {
		$this->sync_data['company_id'] = absint( $id );
	}

	public function get_gender() {
		$title  = $this->get( 'title' );
		$gender = '';

		if ( 'Herr' === $title || 1 === (int) $title ) {
			$gender = 'm';
		} elseif ( 'Frau' === $title || 2 === (int) $title ) {
			$gender = 'w';
		}

		return $gender;
	}

	public function get_address() {
		return $this->get( 'address' );
	}

	public function get_shipping_address() {
		return $this->get( 'shipping_address' );
	}

	public function has_shipping_address() {
		$street  = $this->get_address_prop( 'street', 'shipping' );
		$country = $this->get_address_prop( 'country', 'shipping' );

		return ! empty( $street ) && ! empty( $country );
	}

	public function get_address_prop( $prop, $address_type = '' ) {
		if ( 'shipping' === $address_type ) {
			$address = $this->get_shipping_address();
		} else {
			$address = $this->get_address();
		}

		return array_key_exists( $prop, $address ) ? $address[ $prop ] : null;
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function save() {
		/**
		 * For new customers: Lookup customer data in sevDesk and map a sevDesk user to the new user, e.g. for guest checkouts
		 * and/or new users. Improve lookup for company data too, e.g. when a new user in WP is being created, mapped to an existing
		 * sevDesk user and a company name is chosen (which is not yet mapped with a sevDesk company).
		 */
		if ( $this->is_new() && apply_filters( 'storeabill_external_sync_sevdesk_contact_enable_account_matching', true ) ) {
			$contact = $this->api->find_contact( $this );

			if ( ! is_wp_error( $contact ) ) {
				$this->set_id( $contact['id'] );

				if ( ! empty( $contact['parent'] ) ) {
					$this->set_company_id( $contact['parent']['id'] );
				}
			}
		}

		if ( $this->has_company() && $this->is_new_company() && apply_filters( 'storeabill_external_sync_sevdesk_contact_enable_account_matching', true ) ) {
			$company = $this->api->find_company( $this );

			if ( ! is_wp_error( $company ) ) {
				$this->set_company_id( $company['id'] );
			}
		}

		$contact = array(
			'familyname' => $this->get_last_name(),
			'surename'   => $this->get_first_name(),
			'vatNumber'  => $this->get_vat_id(),
			'gender'     => $this->get_gender(),
			'category'   => array(
				'id'         => apply_filters( 'storeabill_external_sync_sevdesk_contact_category_id', 3, $this ),
				'objectName' => 'Category',
			),
		);

		if ( '' !== $this->get_academic_title() ) {
			$contact['academicTitle'] = $this->get_academic_title();
		}

		if ( $this->has_company() ) {
			$company = array(
				'name'      => $this->get_company(),
				'category'  => array(
					'id'         => apply_filters( 'storeabill_external_sync_sevdesk_contact_category_id', 3, $this ),
					'objectName' => 'Category',
				),
				'vatNumber' => $this->get_vat_id(),
			);

			if ( $this->is_new_company() ) {
				$company['customerNumber'] = $this->get_formatted_company_number();

				$company = apply_filters( 'storeabill_external_sync_sevdesk_company_contact', $company, $this );
				$result  = $this->api->create_contact( $company );

				if ( ! is_wp_error( $result ) ) {
					$this->set_company_id( $result->get( 'objects' )['id'] );
				} else {
					$this->set_company_id( 0 );
				}
			} elseif ( $this->current_company ) {
				$company['category'] = $this->current_company['category'];

				$company = apply_filters( 'storeabill_external_sync_sevdesk_company_contact', $company, $this );
				$result  = $this->api->update_contact( $this->get_company_id(), $company );
			} else {
				$result = new \WP_Error( 500, 'Error while updating the company.' );
			}

			if ( $this->get_company_id() > 0 ) {
				$contact['parent'] = array(
					'id'         => $this->get_company_id(),
					'objectName' => 'Contact',
				);
			}
		}

		if ( $this->is_new() ) {
			$contact['customerNumber'] = $this->get_formatted_number();

			$contact = apply_filters( 'storeabill_external_sync_sevdesk_contact', $contact, $this );
			$result  = $this->api->create_contact( $contact );
		} elseif ( $this->current_customer ) {
			/**
			 * Every update call overrides the contact's category,
			 * therefor explicitly include the current category in the update request.
			 */
			$contact['category'] = $this->current_customer['category'];

			$contact = apply_filters( 'storeabill_external_sync_sevdesk_contact', $contact, $this );
			$result  = $this->api->update_contact( $this->get_id(), $contact );
		} else {
			$result = new \WP_Error( 500, 'Error while updating the contact.' );
		}

		if ( ! is_wp_error( $result ) ) {
			$this->set_id( $result->get( 'objects' )['id'] );
			$this->save_addresses();

			if ( ! empty( $this->get_email() ) ) {
				$this->save_email();
			}

			if ( ! empty( $this->get_phone() ) ) {
				$this->save_phone();
			}

			return true;
		}

		return $result;
	}

	protected function is_new_address( $address_data, $address_type = 'billing' ) {
		$id = 'shipping' === $address_type ? $this->get_shipping_address_id() : $this->get_address_id();

		if ( empty( $id ) || $id <= 0 ) {
			return true;
		} else {
			$api_result = $this->api->get_address( $id );

			if ( $this->api->is_404( $api_result ) ) {
				return true;
			} elseif ( ! $this->api->has_failed( $api_result ) ) {
				if ( (string) $api_result['objects'][0]['country']['id'] !== (string) $address_data['country']['id'] ) {
					return true;
				}

				return false;
			} else {
				return false;
			}
		}
	}

	protected function save_address( $type = 'billing' ) {
		$id = 'shipping' === $type ? $this->get_shipping_address_id() : $this->get_address_id();

		if ( empty( $id ) || $id <= 0 ) {
			$addresses = $this->api->get_contact_addresses( $this->get_id() );

			if ( ! is_wp_error( $addresses ) ) {
				foreach ( $addresses['objects'] as $address ) {
					$current_address_type = 47 === absint( $address['category']['id'] ) ? 'billing' : 'shipping';

					if ( $current_address_type !== $type ) {
						continue;
					}

					$to_compare = array(
						array(
							$address['zip'],
							$this->get_address_prop( 'zip', $type ),
						),
						array(
							$address['street'],
							$this->get_address_prop( 'street', $type ),
						),
					);

					$equals = true;

					foreach ( $to_compare as $pair ) {
						if ( sanitize_key( $pair[0] ) !== sanitize_key( $pair[1] ) ) {
							$equals = false;
							break;
						}
					}

					if ( $equals ) {
						$id = $address['id'];

						if ( 'shipping' === $type ) {
							$this->set_shipping_address_id( $id );
						} else {
							$this->set_address_id( $id );
						}
						break;
					}
				}
			}
		}

		$id           = 'shipping' === $type ? $this->get_shipping_address_id() : $this->get_address_id();
		$address_data = 'shipping' === $type ? $this->get_shipping_address() : $this->get_address();

		$address_data['country']  = array(
			'objectName' => 'StaticCountry',
			'id'         => $this->api->get_country_id_by_code( $address_data['country'] ),
		);
		$address_data['category'] = array(
			'objectName' => 'Category',
			'id'         => 'shipping' === $type ? 48 : 47,
		);
		$address_data['contact']  = array(
			'objectName' => 'Contact',
			'id'         => $this->get_id(),
		);

		$address_data = apply_filters( 'storeabill_external_sync_sevdesk_address', $address_data, $type, $this );

		if ( ! $this->is_new_address( $address_data, $type ) ) {
			$response = $this->api->update_address( $id, $address_data );
		} else {
			$response = $this->api->create_address( $address_data );

			if ( ! is_wp_error( $response ) ) {
				$id = $response->get( 'objects' )['id'];
			} else {
				$id = 0;
			}

			if ( 'shipping' === $type ) {
				$this->set_shipping_address_id( $id );
			} else {
				$this->set_address_id( $id );
			}
		}

		return $response;
	}

	protected function save_addresses() {
		$response = $this->save_address();

		if ( $this->has_shipping_address() ) {
			$response = $this->save_address( 'shipping' );
		}

		return $response;
	}

	protected function needs_new_communication_way( $current_value, $type = 'EMAIL' ) {
		$existing = $this->api->get_communication_ways( $this->get_id(), $type );

		if ( ! $this->api->has_failed( $existing ) ) {
			foreach ( $existing as $communication_data ) {
				if ( $communication_data['value'] === $current_value ) {
					return false;
				}
			}

			return true;
		} elseif ( false === $existing ) {
			return true;
		}

		return false;
	}

	protected function save_email() {
		$data = array(
			'type'    => 'EMAIL',
			'key'     => array(
				'id'         => 2,
				'objectName' => 'CommunicationWayKey',
			),
			'contact' => array(
				'id'         => $this->get_id(),
				'objectName' => 'Contact',
			),
			'value'   => $this->get_email(),
		);

		$response = false;

		if ( $this->needs_new_communication_way( $this->get_email(), 'EMAIL' ) ) {
			$response = $this->api->create_email( $data );

			if ( ! is_wp_error( $response ) ) {
				$this->set_email_id( $response->get( 'objects' )['id'] );
			} else {
				$this->set_email_id( 0 );
			}
		}

		return $response;
	}

	protected function save_phone() {
		$id   = $this->get_phone_id();
		$data = array(
			'type'    => 'PHONE',
			'key'     => array(
				'id'         => 2,
				'objectName' => 'CommunicationWayKey',
			),
			'contact' => array(
				'id'         => $this->get_id(),
				'objectName' => 'Contact',
			),
			'value'   => $this->get_phone(),
		);

		$response = false;

		if ( $this->needs_new_communication_way( $this->get_phone(), 'PHONE' ) ) {
			$response = $this->api->create_phone( $data );

			if ( ! is_wp_error( $response ) ) {
				$this->set_phone_id( $response->get( 'objects' )['id'] );
			} else {
				$this->set_phone_id( 0 );
			}
		}

		return $response;
	}

	public function get_sync_data() {
		return $this->sync_data;
	}

	protected function set( $prop, $value ) {
		$this->data[ $prop ] = $value;
	}

	protected function get( $prop ) {
		if ( array_key_exists( $prop, $this->data ) ) {
			return $this->data[ $prop ];
		} else {
			return null;
		}
	}
}
