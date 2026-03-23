<?php

namespace Vendidero\StoreaBill;

use Vendidero\EUTaxHelper\Helper;

defined( 'ABSPATH' ) || exit;

class Countries {

	/**
	 * @return array|string[]
	 */
	public static function get_base_location() {
		return array(
			'state'   => BusinessInformation::get_base_state(),
			'country' => BusinessInformation::get_base_country(),
		);
	}

	public static function get_base_country() {
		return BusinessInformation::get_base_country();
	}

	public static function base_country_supports_oss_procedure() {
		return apply_filters( 'storeabill_base_country_supports_oss_procedure', self::base_country_is_eu() );
	}

	public static function base_country_is_eu() {
		$base_country     = self::get_base_country();
		$eu_vat_countries = self::get_eu_vat_countries();

		return apply_filters( 'storeabill_base_country_is_eu', in_array( $base_country, $eu_vat_countries, true ) );
	}

	public static function get_base_state() {
		return BusinessInformation::get_base_state();
	}

	public static function get_base_address() {
		return BusinessInformation::get_base_address();
	}

	public static function get_base_address_2() {
		return BusinessInformation::get_base_address_2();
	}

	public static function get_base_city() {
		return BusinessInformation::get_base_city();
	}

	public static function get_base_postcode() {
		return BusinessInformation::get_base_postcode();
	}

	public static function get_base_company_name() {
		return BusinessInformation::get_base_company_name();
	}

	public static function get_base_email() {
		return BusinessInformation::get_contact_email();
	}

	public static function get_base_bank_account_data() {
		return BusinessInformation::get_bank_account_data();
	}

	public static function has_base_bank_account() {
		return BusinessInformation::has_base_bank_account();
	}

	public static function get_base_address_data( $include_company = true ) {
		$address_data = BusinessInformation::get_base_address_data();

		if ( ! $include_company ) {
			$address_data['company'] = '';
		}

		return apply_filters( 'storeabill_base_address_data', $address_data );
	}

	public static function get_formatted_base_address( $separator = '<br/>', $include_company = true ) {
		return self::get_formatted_address( self::get_base_address_data( $include_company ), $separator );
	}

	public static function is_small_business() {
		return BusinessInformation::is_small_business();
	}

	public static function register_custom_address_replacements( $replacements, $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'vat_id' => null,
			)
		);

		if ( null !== $args['vat_id'] ) {
			$replacements['{vat_id}'] = $args['vat_id'];
		} else {
			$replacements['{vat_id}'] = '';
		}

		return $replacements;
	}

	public static function register_custom_address_formats( $countries ) {
		foreach ( $countries as $country => $value ) {
			if ( ! strstr( $value, '{vat_id}' ) ) {
				$countries[ $country ] .= "\n{vat_id}";
			}
		}

		return $countries;
	}

	public static function get_formatted_address( $address = array(), $separator = '<br/>', $type = 'complete' ) {
		$force = apply_filters( 'storeabill_document_address_force_country_display', false );

		if ( 'short' === $type ) {
			if ( ! empty( $address['company'] ) ) {
				$address['first_name'] = '';
				$address['last_name']  = '';
			}

			$address = array_intersect_key(
				$address,
				array(
					'first_name' => '',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => '',
					'address_2'  => '',
					'state'      => '',
					'city'       => '',
					'country'    => '',
					'postcode'   => '',
				)
			);
		}

		// Make sure that Woo reloads address formats
		WC()->countries->address_formats = array();

		add_filter( 'woocommerce_localisation_address_formats', array( __CLASS__, 'register_custom_address_formats' ), 1000 );
		add_filter( 'woocommerce_formatted_address_replacements', array( __CLASS__, 'register_custom_address_replacements' ), 15, 2 );

		if ( $force ) {
			add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true', 1, 2000 );
		}

		$formatted_address = WC()->countries->get_formatted_address( $address, $separator );

		if ( $force ) {
			remove_filter( 'woocommerce_formatted_address_force_country_display', '__return_true', 2000 );
		}

		remove_filter( 'woocommerce_localisation_address_formats', array( __CLASS__, 'register_custom_address_formats' ), 1000 );
		remove_filter( 'woocommerce_formatted_address_replacements', array( __CLASS__, 'register_custom_address_replacements' ), 15 );

		// Make sure that Woo reloads address formats
		WC()->countries->address_formats = array();

		return $formatted_address;
	}

	public static function is_third_country( $country, $postcode = '' ) {
		return apply_filters( 'storeabill_is_third_country', Helper::is_third_country( $country, $postcode ), $country, $postcode );
	}

	public static function get_eu_countries() {
		return Helper::get_eu_countries();
	}

	public static function get_eu_vat_countries() {
		return apply_filters( 'storeabill_eu_vat_countries', Helper::get_eu_vat_countries() );
	}

	public static function is_northern_ireland( $country, $postcode = '' ) {
		return Helper::is_northern_ireland( $country, $postcode );
	}

	public static function is_eu_vat_postcode_exemption( $country, $postcode = '' ) {
		return Helper::is_eu_vat_postcode_exemption( $country, $postcode );
	}

	public static function is_eu_vat_country( $country, $postcode = '' ) {
		$is_eu_vat_country = Helper::is_eu_vat_country( $country, $postcode );

		return apply_filters( 'storeabill_is_eu_vat_country', $is_eu_vat_country, $country, $postcode );
	}

	public static function get_eu_vat_postcode_exemptions() {
		return apply_filters( 'storeabill_eu_vat_postcode_exemptions', Helper::get_vat_postcode_exemptions() );
	}

	public static function is_eu_country( $country ) {
		return in_array( $country, self::get_eu_countries(), true );
	}
}
