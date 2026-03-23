<?php

namespace Vendidero\StoreaBill\eInvoice;

use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

class Helper {

	public static function is_base_country_supported() {
		return apply_filters( 'storeabill_base_country_supports_einvoices', in_array( Countries::get_base_country(), array( 'DE', 'AT' ), true ) );
	}

	/**
	 * @param \Vendidero\StoreaBill\Invoice\Invoice $invoice
	 *
	 * @return string
	 */
	public static function get_default_specification_by_invoice( $invoice ) {
		$base_country  = Countries::get_base_country();
		$specification = '';

		if ( 'DE' === $base_country ) {
			$specification = 'zugferd';
		} elseif ( 'AT' === $base_country ) {
			$specification = 'cius';
		}

		return apply_filters( 'storeabill_einvoice_default_specification_by_invoice', $specification, $invoice );
	}

	/**
	 * @param \Vendidero\StoreaBill\Invoice\Invoice $invoice
	 *
	 * @return string[]
	 */
	public static function get_available_specifications_by_invoice( $invoice ) {
		return apply_filters( 'storeabill_einvoice_available_specifications_by_invoice', self::get_available_specifications(), $invoice );
	}

	public static function get_settings() {
		$profile_options = array();

		foreach ( self::get_available_specifications() as $specification_name ) {
			$specification = self::get_specification( $specification_name );
			$profiles      = array();

			foreach ( $specification['profiles'] as $profile_name => $profile ) {
				$profile = self::get_profile( $specification_name, $profile_name );

				if ( $profile['supports_auto'] ) {
					$profiles[ $profile_name ] = $profile['title'];
				}
			}

			if ( ! empty( $profiles ) ) {
				$profiles = array_merge( array( '' => '' ), $profiles );

				$profile_options[] = array(
					'title'             => sprintf( _x( '%1$s profile', 'storeabill-core', 'woocommerce-germanized-pro' ), $specification['title'] ),
					'id'                => 'storeabill_invoice_auto_create_einvoices_' . $specification_name . '_profile',
					'default'           => '',
					'type'              => 'select',
					'class'             => 'sab-enhanced-select',
					'options'           => $profiles,
					'custom_attributes' => array(
						'data-placeholder' => sprintf( _x( 'Do not create %1$s invoices automatically', 'storeabill-core', 'woocommerce-germanized-pro' ), $specification['title'] ),
						'data-allow-clear' => true,
						'data-show_if_storeabill_invoice_auto_create_einvoices' => 'yes',
					),
				);

				if ( $specification['supports_pdf'] ) {
					$profile_options[] = array(
						'title'             => _x( 'Merge PDF?', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'desc'              => sprintf( _x( 'Merge the to be created %1$s eInvoice with the PDF and create a PDF/A.', 'storeabill-core', 'woocommerce-germanized-pro' ), $specification['title'] ),
						'id'                => 'storeabill_invoice_auto_create_einvoices_' . $specification_name . '_merge',
						'default'           => 'no',
						'type'              => 'sab_toggle',
						'custom_attributes' => array(
							'data-show_if_storeabill_invoice_auto_create_einvoices' => 'yes',
						),
					);
				}
			}
		}

		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'einvoice_general_settings',
			),

			array(
				'title'   => _x( 'Automation', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'    => _x( 'Automatically create eInvoices.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id'      => 'storeabill_invoice_auto_create_einvoices',
				'default' => 'no',
				'type'    => 'sab_toggle',
			),
		);

		$settings = array_merge( $settings, $profile_options );
		$settings = array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'einvoice_general_settings',
				),
			)
		);

		return $settings;
	}

	public static function get_specifications() {
		return apply_filters(
			'storeabill_einvoice_specifications',
			array(
				'zugferd' => array(
					'class_name'      => '\Vendidero\StoreaBill\eInvoice\ZugFeRD\Invoice',
					'title'           => _x( 'ZugFeRD', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'supports_pdf'    => true,
					'default_profile' => 'extended',
					'profiles'        => array(
						'basic'       => array(
							'title'         => _x( 'Basic', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'fields'        => array(),
							'extension'     => 'xml',
							'supports_auto' => true,
						),
						'comfort'     => array(
							'title'         => _x( 'Comfort', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'fields'        => array(
								'buyer_reference' => array(
									'default'     => '',
									'type'        => 'text',
									'id'          => 'buyer_reference',
									'label'       => _x( 'Routing id', 'storeabill-core', 'woocommerce-germanized-pro' ),
									'desc_tip'    => true,
									'mandatory'   => false,
									'mappings'    => array( 'invoice:routing_id' ),
									'description' => _x( 'The routing ID enables electronic addressing and forwarding of the e-invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								),
							),
							'extension'     => 'xml',
							'supports_auto' => true,
						),
						'extended'    => array(
							'title'         => _x( 'Extended', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'fields'        => array(
								'buyer_reference' => array(
									'default'     => '',
									'type'        => 'text',
									'id'          => 'buyer_reference',
									'label'       => _x( 'Routing id', 'storeabill-core', 'woocommerce-germanized-pro' ),
									'desc_tip'    => true,
									'mandatory'   => false,
									'mappings'    => array( 'invoice:routing_id' ),
									'description' => _x( 'The routing ID enables electronic addressing and forwarding of the e-invoice.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								),
							),
							'extension'     => 'xml',
							'supports_auto' => true,
						),
						'xrechnung'   => array(
							'title'         => _x( 'XRechnung', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'fields'        => array(
								'buyer_reference' => array(
									'default'     => '',
									'type'        => 'text',
									'id'          => 'buyer_reference',
									'label'       => _x( 'Routing id', 'storeabill-core', 'woocommerce-germanized-pro' ),
									'desc_tip'    => true,
									'mandatory'   => true,
									'mappings'    => array( 'invoice:routing_id' ),
									'description' => _x( 'The routing ID enables electronic addressing and forwarding of the e-invoice by the federal government\'s central invoice receipt platforms', 'storeabill-core', 'woocommerce-germanized-pro' ),
								),
							),
							'extension'     => 'xml',
							'supports_auto' => false,
						),
						'xrechnung_2' => array(
							'title'         => _x( 'XRechnung 2', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'fields'        => array(
								'buyer_reference' => array(
									'default'     => '',
									'type'        => 'text',
									'id'          => 'buyer_reference',
									'label'       => _x( 'Routing id', 'storeabill-core', 'woocommerce-germanized-pro' ),
									'desc_tip'    => true,
									'mandatory'   => true,
									'mappings'    => array( 'invoice:routing_id' ),
									'description' => _x( 'The routing ID enables electronic addressing and forwarding of the e-invoice by the federal government\'s central invoice receipt platforms', 'storeabill-core', 'woocommerce-germanized-pro' ),
								),
							),
							'extension'     => 'xml',
							'supports_auto' => false,
						),
						'xrechnung_3' => array(
							'title'         => _x( 'XRechnung 3', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'fields'        => array(
								'buyer_reference' => array(
									'default'     => '',
									'type'        => 'text',
									'id'          => 'buyer_reference',
									'label'       => _x( 'Routing id', 'storeabill-core', 'woocommerce-germanized-pro' ),
									'desc_tip'    => true,
									'mandatory'   => true,
									'mappings'    => array( 'invoice:routing_id' ),
									'description' => _x( 'The routing ID enables electronic addressing and forwarding of the e-invoice by the federal government\'s central invoice receipt platforms', 'storeabill-core', 'woocommerce-germanized-pro' ),
								),
							),
							'extension'     => 'xml',
							'supports_auto' => false,
						),
					),
				),
				'cius'    => array(
					'class_name'      => '\Vendidero\StoreaBill\eInvoice\CIUS\Invoice',
					'title'           => _x( 'CIUS', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'supports_pdf'    => false,
					'default_profile' => 'peppol',
					'profiles'        => array(
						'peppol' => array(
							'title'         => _x( 'Peppol BIS 3.0', 'storeabill-core', 'woocommerce-germanized-pro' ),
							'fields'        => array(
								'order_reference'          => array(
									'default'     => 'NA',
									'type'        => 'text',
									'id'          => 'order_reference',
									'label'       => _x( 'Order reference', 'storeabill-core', 'woocommerce-germanized-pro' ),
									'desc_tip'    => true,
									'mandatory'   => false,
									'mappings'    => array( 'invoice:order_reference' ),
									'description' => _x( 'An identifier of a referenced purchase order, issued by the Buyer.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								),
								'buyer_reference'          => array(
									'default'     => '',
									'type'        => 'text',
									'id'          => 'buyer_reference',
									'label'       => _x( 'Buyer reference', 'storeabill-core', 'woocommerce-germanized-pro' ),
									'desc_tip'    => true,
									'mandatory'   => false,
									'mappings'    => array( 'invoice:buyer_reference' ),
									'description' => _x( 'An identifier assigned by the Buyer used for internal routing purposes.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								),
								'buyer_electronic_address' => array(
									'default'     => '',
									'type'        => 'text',
									'id'          => 'buyer_electronic_address',
									'label'       => _x( 'Buyer electronic address', 'storeabill-core', 'woocommerce-germanized-pro' ),
									'desc_tip'    => true,
									'mandatory'   => false,
									'mappings'    => array( 'buyer:electronic_address' ),
									'description' => _x( 'Provide the buyer\'s electronic address, e.g.: 9914:atu53672905. Defaults to the VAT ID, if available.', 'storeabill-core', 'woocommerce-germanized-pro' ),
								),
							),
							'extension'     => 'xml',
							'supports_auto' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * @param string $specification
	 * @param string $profile
	 * @param string $invoice_type
	 *
	 * @return false|Invoice
	 */
	public static function get_specification_instance( $specification, $profile, $invoice_type = 'invoice' ) {
		$instance = null;

		if ( $specification_data = self::get_specification( $specification ) ) {
			$classname = $specification_data['class_name'];

			if ( ! class_exists( $classname ) ) {
				return false;
			}

			if ( ! array_key_exists( $profile, $specification_data['profiles'] ) ) {
				return false;
			}

			$instance = new $classname( $profile, $invoice_type );
		}

		return $instance ? $instance : false;
	}

	public static function get_profiles_by_specification( $specification = 'zugferd' ) {
		$specifications = self::get_specifications();
		$profiles       = array();

		if ( array_key_exists( $specification, $specifications ) ) {
			$profiles = array_keys( $specifications[ $specification ]['profiles'] );
		}

		return $profiles;
	}

	public static function get_available_specifications() {
		return array_keys( self::get_specifications() );
	}

	public static function get_specification( $specification ) {
		$default_specification = array(
			'class_name'      => '',
			'title'           => '',
			'supports_pdf'    => false,
			'profiles'        => array(),
			'default_profile' => '',
		);

		if ( is_array( $specification ) ) {
			return wp_parse_args( $specification, $default_specification );
		}

		$specifications = self::get_specifications();

		if ( array_key_exists( $specification, $specifications ) ) {
			return wp_parse_args( $specifications[ $specification ], $default_specification );
		}

		return false;
	}

	public static function get_profile( $specification, $profile ) {
		if ( $specification_data = self::get_specification( $specification ) ) {
			$default_profile = array(
				'title'         => '',
				'fields'        => array(),
				'extension'     => 'xml',
				'supports_auto' => true,
			);

			if ( is_array( $profile ) ) {
				return wp_parse_args( $profile, $default_profile );
			}

			if ( array_key_exists( $profile, $specification_data['profiles'] ) ) {
				return wp_parse_args( $specification_data['profiles'][ $profile ], $default_profile );
			}
		}

		return false;
	}

	/**
	 * @param \Vendidero\StoreaBill\Invoice\Invoice $invoice
	 *
	 * @return array[]
	 */
	public static function get_form_fields( $invoice ) {
		$specification_options = array();

		foreach ( self::get_available_specifications_by_invoice( $invoice ) as $specification_name ) {
			if ( $specification = self::get_specification( $specification_name ) ) {
				$specification_options[ $specification_name ] = $specification['title'];
			}
		}

		$fields = array(
			array(
				'id'          => 'einvoice_type',
				'label'       => _x( 'Specification', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'description' => '',
				'options'     => $specification_options,
				'value'       => self::get_default_specification_by_invoice( $invoice ),
				'type'        => 'select',
			),
		);

		$specifications_supporting_pdf = array();

		foreach ( self::get_available_specifications_by_invoice( $invoice ) as $specification_name ) {
			$specification = self::get_specification( $specification_name );

			if ( ! $specification ) {
				continue;
			}

			$profile_options   = array();
			$additional_fields = array();

			if ( $specification['supports_pdf'] ) {
				$specifications_supporting_pdf[] = $specification_name;
			}

			foreach ( $specification['profiles'] as $profile_name => $profile_data ) {
				$profile_data = self::get_profile( $specification, $profile_data );

				$profile_options[ $profile_name ] = $profile_data['title'];

				if ( ! empty( $profile_data['fields'] ) ) {
					$profile_fields = array_values( $profile_data['fields'] );

					foreach ( $profile_fields as $key => $profile_field ) {
						$profile_field = wp_parse_args(
							$profile_field,
							array(
								'custom_attributes' => array(),
								'id'                => '',
							)
						);

						$profile_field['id'] = $profile_name . '_' . $profile_field['id'];
						$profile_field['custom_attributes'][ 'data-show-if-einvoice_profile_' . $specification_name ] = $profile_name;
						$profile_fields[ $key ] = $profile_field;
					}

					$additional_fields = array_merge( $additional_fields, $profile_fields );
				}
			}

			$fields = array_merge(
				$fields,
				array(
					array(
						'id'                => 'einvoice_profile_' . $specification_name,
						'label'             => _x( 'Profile', 'storeabill-core', 'woocommerce-germanized-pro' ),
						'description'       => '',
						'options'           => $profile_options,
						'value'             => $specification['default_profile'],
						'type'              => 'select',
						'custom_attributes' => array(
							'data-show-if-einvoice_type' => $specification_name,
						),
					),
				)
			);

			$fields = array_merge( $fields, $additional_fields );
		}

		$fields = array_merge(
			$fields,
			array(
				array(
					'id'                => 'einvoice_merge_pdf',
					'label'             => _x( 'Merge the to be created eInvoice with the PDF and create a PDF/A.', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'value'             => '',
					'type'              => 'checkbox',
					'custom_attributes' => array(
						'data-show-if-einvoice_type' => implode( ',', $specifications_supporting_pdf ),
					),
				),
			)
		);

		return $fields;
	}

	public static function get_title( $specification, $profile ) {
		$title = _x( 'eInvoice', 'storeabill-core', 'woocommerce-germanized-pro' );

		if ( $specification_data = self::get_specification( $specification ) ) {
			$specification_title = $specification_data['title'];
			$profile_title       = '';

			if ( array_key_exists( $profile, $specification_data['profiles'] ) ) {
				$profile_title = $specification_data['profiles'][ $profile ]['title'];
			}

			$title = sprintf( _x( '%1$s (%2$s)', 'storeabill-einvoice-title', 'woocommerce-germanized-pro' ), $specification_title, $profile_title );
		}

		return $title;
	}
}
