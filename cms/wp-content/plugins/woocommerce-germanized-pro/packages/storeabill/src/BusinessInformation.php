<?php

namespace Vendidero\StoreaBill;

defined( 'ABSPATH' ) || exit;

class BusinessInformation {

	public static function get_setting_fields_to_observe() {
		return apply_filters(
			'storeabill_business_information_observable_setting_fields',
			array(
				'woocommerce_store_address',
				'woocommerce_store_address_2',
				'woocommerce_store_city',
				'woocommerce_store_postcode',
				'woocommerce_default_country',
				'woocommerce_email_from_address',
			)
		);
	}

	public static function get_base_address_fields() {
		$fields = array(
			'company'   => array(
				'default' => apply_filters( 'storeabill_default_base_company', get_option( 'blogname', '' ) ),
				'type'    => 'text',
				'id'      => 'storeabill_base_company',
				'title'   => _x( 'Company', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'address_1' => array(
				'default' => apply_filters( 'storeabill_default_base_address', get_option( 'woocommerce_store_address' ) ),
				'type'    => 'text',
				'id'      => 'storeabill_base_address_1',
				'title'   => _x( 'Address 1', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'address_2' => array(
				'default' => apply_filters( 'storeabill_default_base_address_2', get_option( 'woocommerce_store_address_2' ) ),
				'type'    => 'text',
				'id'      => 'storeabill_base_address_2',
				'title'   => _x( 'Address 2', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'city'      => array(
				'default' => apply_filters( 'storeabill_default_base_city', get_option( 'woocommerce_store_city' ) ),
				'type'    => 'text',
				'id'      => 'storeabill_base_city',
				'title'   => _x( 'City', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'postcode'  => array(
				'default' => apply_filters( 'storeabill_default_base_postcode', get_option( 'woocommerce_store_postcode' ) ),
				'type'    => 'text',
				'id'      => 'storeabill_base_postcode',
				'title'   => _x( 'Postcode', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'country'   => array(
				'default' => apply_filters( 'storeabill_default_base_country_state', apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country', 'US:CA' ) ) ),
				'type'    => 'single_select_country',
				'id'      => 'storeabill_base_country',
				'title'   => _x( 'Country', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
		);

		return apply_filters( 'storeabill_base_address_fields', $fields );
	}

	/**
	 * @return string[]
	 */
	public static function get_base_address_data() {
		$address = array();

		foreach ( self::get_base_address_fields() as $field_name => $field ) {
			$value       = $field['default'];
			$field_value = get_option( $field['id'], false );

			if ( false !== $field_value ) {
				$value = $field_value;
			}

			if ( 'country' === $field_name ) {
				$country_data     = wc_format_country_state_string( $value );
				$value            = $country_data['country'];
				$address['state'] = $country_data['state'];
			}

			$address[ $field_name ] = $value;
		}

		return apply_filters( 'storeabill_base_address_data', $address );
	}

	public static function get_formatted_base_address( $separator = '<br/>', $skip_company = false ) {
		$base_address = self::get_base_address_data();

		if ( $skip_company ) {
			$base_address['company'] = '';
		}

		return Countries::get_formatted_address( $base_address, $separator );
	}

	public static function get_base_address() {
		return apply_filters( 'storeabill_base_address', self::get_base_address_data()['address_1'] );
	}

	public static function get_base_address_1() {
		return self::get_base_address();
	}

	public static function get_base_address_2() {
		return apply_filters( 'storeabill_base_address_2', self::get_base_address_data()['address_2'] );
	}

	public static function get_base_city() {
		return apply_filters( 'storeabill_base_city', self::get_base_address_data()['city'] );
	}

	public static function get_base_postcode() {
		return apply_filters( 'storeabill_base_postcode', self::get_base_address_data()['postcode'] );
	}

	public static function get_base_company_name() {
		return apply_filters( 'storeabill_base_company_name', self::get_base_address_data()['company'] );
	}

	public static function get_base_country() {
		return apply_filters( 'storeabill_base_country', wc_format_country_state_string( self::get_base_address_data()['country'] )['country'] );
	}

	public static function get_base_state() {
		return apply_filters( 'storeabill_base_state', wc_format_country_state_string( self::get_base_address_data()['country'] )['state'] );
	}

	public static function get_contact_fields() {
		$fields = array(
			'first_name' => array(
				'default' => apply_filters( 'storeabill_default_contact_first_name', '' ),
				'type'    => 'text',
				'id'      => 'storeabill_contact_first_name',
				'title'   => _x( 'First name', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'last_name'  => array(
				'default' => apply_filters( 'storeabill_default_contact_last_name', '' ),
				'type'    => 'text',
				'id'      => 'storeabill_contact_last_name',
				'title'   => _x( 'Last name', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'email'      => array(
				'default' => apply_filters( 'storeabill_default_contact_email', get_option( 'woocommerce_email_from_address' ) ),
				'type'    => 'email',
				'id'      => 'storeabill_contact_email',
				'title'   => _x( 'E-Mail', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'phone'      => array(
				'default' => apply_filters( 'storeabill_default_contact_phone', '' ),
				'type'    => 'text',
				'id'      => 'storeabill_contact_phone',
				'title'   => _x( 'Phone', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
		);

		return apply_filters( 'storeabill_contact_fields', $fields );
	}

	/**
	 * @return string[]
	 */
	public static function get_contact_data() {
		$contact = array();

		foreach ( self::get_contact_fields() as $field_name => $field ) {
			$value       = $field['default'];
			$field_value = get_option( $field['id'], false );

			if ( false !== $field_value ) {
				$value = $field_value;
			}

			$contact[ $field_name ] = $value;
		}

		return apply_filters( 'storeabill_contact_data', $contact );
	}

	public static function get_contact_full_name() {
		return apply_filters( 'storeabill_contact_full_name', sprintf( _x( '%1$s %2$s', 'storeabill-fullname', 'woocommerce-germanized-pro' ), self::get_contact_data()['first_name'], self::get_contact_data()['last_name'] ) );
	}

	public static function get_contact_email() {
		return apply_filters( 'storeabill_contact_email', self::get_contact_data()['email'] );
	}

	public static function get_contact_first_name() {
		return apply_filters( 'storeabill_contact_first_name', self::get_contact_data()['first_name'] );
	}

	public static function get_contact_last_name() {
		return apply_filters( 'storeabill_contact_last_name', self::get_contact_data()['last_name'] );
	}

	public static function get_contact_phone() {
		return apply_filters( 'storeabill_contact_phone', self::get_contact_data()['phone'] );
	}

	public static function get_legal_fields() {
		$company_free_text_placeholders = array(
			'{registration_number}' => _x( 'Registration number', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'{local_tax_number}'    => _x( 'Local tax number', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'{vat_id}'              => _x( 'VAT ID', 'storeabill-core', 'woocommerce-germanized-pro' ),
		);

		$placeholder_html = '<ul class="sab-document-number-placeholders">';

		foreach ( $company_free_text_placeholders as $placeholder => $title ) {
			$placeholder_html .= sprintf( '<li><code>%s</code>: %s</li>', esc_attr( $placeholder ), esc_html( $title ) );
		}

		$placeholder_html .= '</ul>';

		$fields = array(
			'company_registration_number' => array(
				'default'  => apply_filters( 'storeabill_default_company_registration_number', '' ),
				'type'     => 'text',
				'id'       => 'storeabill_company_registration_number',
				'title'    => _x( 'Registration number', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc_tip' => _x( 'E.g. Commercial register number', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'company_legal_free_text'     => array(
				'default'  => apply_filters( 'storeabill_default_company_legal_free_text', '' ),
				'type'     => 'textarea',
				'id'       => 'storeabill_company_legal_free_text',
				'title'    => _x( 'Legal free text', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'     => '<div class="sab-additional-desc">' . sprintf( _x( 'You may use the following placeholders: %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $placeholder_html ) . '</div>',
				'desc_tip' => _x( 'E.g. Managing director, local court', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'css'      => 'min-height: 100px;',
			),
			'local_tax_number'            => array(
				'default'  => apply_filters( 'storeabill_default_local_tax_number', '' ),
				'type'     => 'text',
				'id'       => 'storeabill_local_tax_number',
				'title'    => _x( 'Local tax number', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc_tip' => _x( 'The number assigned to your company by your local tax office.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'vat_id'                      => array(
				'default' => apply_filters( 'storeabill_default_vat_id', '' ),
				'type'    => 'text',
				'id'      => 'storeabill_vat_id',
				'title'   => _x( 'VAT ID', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
		);

		return apply_filters( 'storeabill_legal_fields', $fields );
	}

	/**
	 * @return string[]
	 */
	public static function get_legal_data() {
		$legal = array();

		foreach ( self::get_legal_fields() as $field_name => $field ) {
			$value       = $field['default'];
			$field_value = get_option( $field['id'], false );

			if ( false !== $field_value ) {
				$value = $field_value;
			}

			$legal[ $field_name ] = $value;
		}

		return apply_filters( 'storeabill_legal_data', $legal );
	}

	public static function get_local_tax_number() {
		return apply_filters( 'storeabill_local_tax_number', self::get_legal_data()['local_tax_number'] );
	}

	public static function get_vat_id() {
		return apply_filters( 'storeabill_vat_id', self::get_legal_data()['vat_id'] );
	}

	public static function get_company_registration_number() {
		return apply_filters( 'storeabill_company_registration_number', self::get_legal_data()['company_registration_number'] );
	}

	public static function get_company_legal_free_text( $separator = '<br/>' ) {
		$free_text           = self::get_legal_data()['company_legal_free_text'];
		$registration_number = self::get_company_registration_number();

		if ( empty( $free_text ) && ! empty( $registration_number ) ) {
			$free_text = sprintf( _x( 'Register number: %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $registration_number );
		}

		$placeholders = array(
			'{registration_number}' => self::get_company_registration_number(),
			'{local_tax_number}'    => self::get_local_tax_number(),
			'{vat_id}'              => self::get_vat_id(),
		);

		$free_text = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $free_text );
		$free_text = array_filter( explode( "\r\n", $free_text ) );
		$free_text = implode( $separator, $free_text );

		return apply_filters( 'storeabill_company_legal_free_text', $free_text );
	}

	public static function is_small_business() {
		return apply_filters( 'storeabill_is_small_business', false );
	}

	public static function get_small_business_notice() {
		return apply_filters( 'storeabill_small_business_notice', function_exists( 'wc_gzd_get_small_business_notice' ) ? wc_gzd_get_small_business_notice() : _x( 'Value added tax is not collected, as small businesses according to §19 (1) UStG.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
	}

	public static function get_bank_account_fields() {
		$fallback_accounts = get_option( 'woocommerce_bacs_accounts' );
		$default_data      = array(
			'iban'      => '',
			'holder'    => '',
			'bank_name' => '',
			'bic'       => '',
		);

		if ( ! empty( $fallback_accounts ) ) {
			$default_data = wp_parse_args(
				$fallback_accounts[0],
				$default_data
			);

			$default_data['holder'] = $default_data['account_name'];
		}

		$fields = array(
			'holder'    => array(
				'title'    => _x( 'Holder', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc_tip' => _x( 'Choose an account holder', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id'       => 'storeabill_bank_account_holder',
				'default'  => apply_filters( 'storeabill_default_bank_account_holder', $default_data['holder'] ),
				'type'     => 'text',
			),
			'bank_name' => array(
				'title'    => _x( 'Bank Name', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc_tip' => _x( 'Choose the name of your bank', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id'       => 'storeabill_bank_account_bank_name',
				'default'  => apply_filters( 'storeabill_default_bank_account_bank_name', $default_data['bank_name'] ),
				'type'     => 'text',
			),
			'iban'      => array(
				'title'   => _x( 'IBAN', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id'      => 'storeabill_bank_account_iban',
				'default' => apply_filters( 'storeabill_default_bank_account_iban', $default_data['iban'] ),
				'type'    => 'text',
			),
			'bic'       => array(
				'title'   => _x( 'BIC', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id'      => 'storeabill_bank_account_bic',
				'default' => apply_filters( 'storeabill_default_bank_account_bic', $default_data['bic'] ),
				'type'    => 'text',
			),
		);

		return apply_filters( 'storeabill_bank_account_fields', $fields );
	}

	/**
	 * @return string[]
	 */
	public static function get_bank_account_data() {
		$legal = array();

		foreach ( self::get_bank_account_fields() as $field_name => $field ) {
			$value       = $field['default'];
			$field_value = get_option( $field['id'], false );

			if ( false !== $field_value ) {
				$value = $field_value;
			}

			$legal[ $field_name ] = $value;
		}

		return apply_filters( 'storeabill_bank_account_data', $legal );
	}

	public static function get_bank_account_holder() {
		return apply_filters( 'storeabill_bank_account_holder', self::get_bank_account_data()['holder'] );
	}

	public static function get_bank_account_iban() {
		return apply_filters( 'storeabill_bank_account_iban', self::get_bank_account_data()['iban'] );
	}

	public static function get_bank_account_bic() {
		return apply_filters( 'storeabill_bank_account_bic', self::get_bank_account_data()['bic'] );
	}

	public static function has_base_bank_account() {
		$data = self::get_bank_account_data();

		return ! empty( $data['iban'] ) ? true : false;
	}

	/**
	 * @return string[]
	 */
	public static function get_data() {
		return array_merge( self::get_base_address_data(), self::get_contact_data(), self::get_legal_data(), self::get_bank_account_data() );
	}
}
