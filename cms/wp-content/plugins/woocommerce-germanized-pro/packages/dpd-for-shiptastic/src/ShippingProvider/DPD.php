<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Shiptastic\DPD\ShippingProvider;

use Vendidero\Shiptastic\DPD\Package;
use Vendidero\Shiptastic\DPD\ShippingProvider\Services\HigherInsurance;
use Vendidero\Shiptastic\DPD\ShippingProvider\Services\ParcelShopDelivery;
use Vendidero\Shiptastic\DPD\ShippingProvider\Services\Predict;
use Vendidero\Shiptastic\Admin\Settings;
use Vendidero\Shiptastic\Labels\ConfigurationSet;
use Vendidero\Shiptastic\PickupDelivery;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShippingMethod\MethodHelper;
use Vendidero\Shiptastic\ShippingProvider\Auto;

defined( 'ABSPATH' ) || exit;

class DPD extends Auto {

	protected function get_default_label_default_print_format() {
		if ( 'web_connect' === $this->get_api_type() ) {
			return 'A6';
		} elseif ( 'cloud' === $this->get_api_type() ) {
			return 'PDF_A6';
		} else {
			return '';
		}
	}

	public function get_title( $context = 'view' ) {
		return _x( 'DPD', 'dpd', 'woocommerce-germanized-pro' );
	}

	public function get_name( $context = 'view' ) {
		return 'dpd';
	}

	public function get_description( $context = 'view' ) {
		return _x( 'Create DPD labels and return labels conveniently.', 'dpd', 'woocommerce-germanized-pro' );
	}

	public function get_default_tracking_url_placeholder() {
		if ( 'AT' === Package::get_base_country() ) {
			return 'https://www.mydpd.at/?f=parcel.load&p={tracking_id}';
		} else {
			return 'https://my.dpd.de/redirect.aspx?action=1&locale=de_DE&parcelno={tracking_id}';
		}
	}

	public function is_sandbox() {
		return Package::get_api()->is_sandbox();
	}

	public function get_supported_label_reference_types( $shipment_type = 'simple' ) {
		$reference_types = array(
			'Reference1' => array(
				'label'      => _x( 'Reference 1', 'dpd', 'woocommerce-germanized-pro' ),
				'default'    => _x( 'Shipment {shipment_number}', 'dpd', 'woocommerce-germanized-pro' ),
				'max_length' => 'web_connect' === $this->get_api_type() ? 50 : 35,
			),
			'Reference2' => array(
				'label'      => _x( 'Reference 2', 'dpd', 'woocommerce-germanized-pro' ),
				'default'    => _x( 'Order {order_number}', 'dpd', 'woocommerce-germanized-pro' ),
				'max_length' => 35,
			),
		);

		return $reference_types;
	}

	public function get_label_classname( $type ) {
		if ( 'return' === $type ) {
			return '\Vendidero\Shiptastic\DPD\Label\Retoure';
		} else {
			return '\Vendidero\Shiptastic\DPD\Label\Simple';
		}
	}

	/**
	 * @param string $label_type
	 * @param false|Shipment $shipment
	 *
	 * @return bool
	 */
	public function supports_labels( $label_type, $shipment = false ) {
		$label_types = array( 'simple', 'return' );

		/**
		 * DPD does not support return labels for third countries
		 */
		if ( $shipment && 'return' === $label_type && $shipment->is_shipping_international() ) {
			return false;
		}

		return in_array( $label_type, $label_types, true );
	}

	public function supports_customer_return_requests() {
		return true;
	}

	public function hide_return_address() {
		return false;
	}

	public function get_api_username( $context = 'view' ) {
		return $this->get_meta( 'api_username', true, $context );
	}

	public function get_api_type( $context = 'view' ) {
		$api_type            = $this->get_meta( 'api_type', true, $context );
		$available_api_types = $this->get_available_api_types();

		if ( count( $available_api_types ) > 1 ) {
			if ( 'view' === $context && empty( $api_type ) ) {
				$api_type = 'cloud';
			}
		} elseif ( 1 === count( $available_api_types ) ) {
			$api_type = array_keys( $available_api_types )[0];
		}

		return defined( 'WC_STC_DPD_API_TYPE' ) ? WC_STC_DPD_API_TYPE : $api_type;
	}

	public function set_api_username( $username ) {
		$this->update_meta_data( 'api_username', $username );
	}

	public function get_setting_sections() {
		$sections = parent::get_setting_sections();

		return $sections;
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_return_label_fields( $shipment ) {
		$settings     = parent::get_return_label_fields( $shipment );
		$default_args = $this->get_default_label_props( $shipment );

		return $settings;
	}

	protected function register_print_formats() {
		if ( 'cloud' === $this->get_api_type() ) {
			$page_formats = array(
				'PDF_A4' => _x( 'A4', 'dpd', 'woocommerce-germanized-pro' ),
				'PDF_A6' => _x( 'A6', 'dpd', 'woocommerce-germanized-pro' ),
			);
		} elseif ( 'web_connect' === $this->get_api_type() ) {
			$page_formats = array(
				'A4' => _x( 'A4', 'dpd', 'woocommerce-germanized-pro' ),
				'A6' => _x( 'A6', 'dpd', 'woocommerce-germanized-pro' ),
				'A7' => _x( 'A7', 'dpd', 'woocommerce-germanized-pro' ),
			);
		} else {
			$page_formats = array();
		}

		foreach ( $page_formats as $format_id => $format_label ) {
			$this->register_print_format(
				$format_id,
				array(
					'label' => $format_label,
				)
			);
		}
	}

	protected function register_products() {
		if ( 'cloud' === $this->get_api_type() ) {
			$dom_products = array(
				'Classic_Predict'     => _x( 'DPD Classic Predict', 'dpd', 'woocommerce-germanized-pro' ),
				'Express_830'         => _x( 'DPD Express 8:30', 'dpd', 'woocommerce-germanized-pro' ),
				'Express_12'          => _x( 'DPD Express 12:00', 'dpd', 'woocommerce-germanized-pro' ),
				'Express_18'          => _x( 'DPD Express 18:00', 'dpd', 'woocommerce-germanized-pro' ),
				'Express_12_Saturday' => _x( 'DPD Express 12:00 (Saturday)', 'dpd', 'woocommerce-germanized-pro' ),
			);

			$this->register_product(
				'Classic',
				array(
					'label'          => _x( 'DPD Classic', 'dpd', 'woocommerce-germanized-pro' ),
					'zones'          => array( 'dom', 'eu', 'int' ),
					'shipment_types' => array( 'simple' ),
					'countries'      => array( 'ALL_EU', 'CH', 'GB', 'NO' ),
				)
			);

			foreach ( $dom_products as $product_id => $label ) {
				$this->register_product(
					$product_id,
					array(
						'label'          => $label,
						'zones'          => array( 'dom' ),
						'shipment_types' => array( 'simple' ),
					)
				);
			}

			$this->register_product(
				'Shop_Delivery',
				array(
					'label'          => _x( 'DPD Shop Delivery', 'dpd', 'woocommerce-germanized-pro' ),
					'zones'          => array( 'dom', 'eu', 'int' ),
					'shipment_types' => array( 'simple' ),
				)
			);

			$this->register_product(
				'Express_International',
				array(
					'label'          => _x( 'DPD Express', 'dpd', 'woocommerce-germanized-pro' ),
					'zones'          => array( 'eu', 'int' ),
					'shipment_types' => array( 'simple' ),
				)
			);

			$this->register_product(
				'Classic_Return',
				array(
					'label'          => _x( 'DPD Classic Return', 'dpd', 'woocommerce-germanized-pro' ),
					'zones'          => array( 'dom', 'eu', 'int' ),
					'shipment_types' => array( 'return' ),
				)
			);

			$this->register_product(
				'Shop_Return',
				array(
					'label'          => _x( 'DPD Shop Return', 'dpd', 'woocommerce-germanized-pro' ),
					'zones'          => array( 'dom', 'eu', 'int' ),
					'shipment_types' => array( 'return' ),
				)
			);
		} elseif ( 'web_connect' === $this->get_api_type() ) {
			$this->register_product(
				'CL',
				array(
					'label'          => _x( 'DPD Classic', 'dpd', 'woocommerce-germanized-pro' ),
					'zones'          => array( 'dom', 'eu', 'int' ),
					'countries'      => array( 'ALL_EU', 'CH', 'GB', 'NO' ),
					'shipment_types' => array( 'simple', 'return' ),
				)
			);

			$this->register_product(
				'IE2',
				array(
					'label'          => _x( 'DPD Express', 'dpd', 'woocommerce-germanized-pro' ),
					'zones'          => array( 'dom', 'eu', 'int' ),
					'shipment_types' => array( 'simple', 'return' ),
				)
			);

			$this->register_product(
				'E12',
				array(
					'label'          => _x( 'DPD 12:00', 'dpd', 'woocommerce-germanized-pro' ),
					'zones'          => array( 'dom', 'eu' ),
					'countries'      => array( 'DE', 'BE', 'NL', 'LU' ),
					'shipment_types' => array( 'simple', 'return' ),
				)
			);

			$this->register_product(
				'E18',
				array(
					'label'          => _x( 'DPD 18:00', 'dpd', 'woocommerce-germanized-pro' ),
					'zones'          => array( 'dom', 'eu', 'int' ),
					'countries'      => array( 'DE', 'BE', 'NL', 'LU', 'CH', 'LI' ),
					'shipment_types' => array( 'simple', 'return' ),
				)
			);

			$dom_products = array(
				'E18'  => _x( 'DPD 18:00', 'dpd', 'woocommerce-germanized-pro' ),
				'E830' => _x( 'DPD 08:30', 'dpd', 'woocommerce-germanized-pro' ),
				'MAX'  => _x( 'DPD MAX', 'dpd', 'woocommerce-germanized-pro' ),
				'PL'   => _x( 'DPD PARCELLetter', 'dpd', 'woocommerce-germanized-pro' ),
				'PM4'  => _x( 'DPD Priority', 'dpd', 'woocommerce-germanized-pro' ),
			);

			foreach ( $dom_products as $product_id => $label ) {
				$this->register_product(
					$product_id,
					array(
						'label'          => $label,
						'zones'          => array( 'dom' ),
						'shipment_types' => array( 'simple', 'return' ),
					)
				);
			}
		} elseif ( 'webservice' === $this->get_api_type() ) {
			$core_products = array(
				'B2C' => _x( 'B2C', 'dpd', 'woocommerce-germanized-pro' ),
				'DPD' => _x( 'B2B', 'dpd', 'woocommerce-germanized-pro' ),
				'2S'  => _x( 'Shop Delivery', 'dpd', 'woocommerce-germanized-pro' ),
			);

			foreach ( $core_products as $product_id => $label ) {
				$this->register_product(
					$product_id,
					array(
						'label'          => $label,
						'zones'          => array( 'dom', 'eu', 'int' ),
						'countries'      => array( 'ALL_EU', 'CH', 'GB', 'LI', 'RS', 'IS', 'BA' ),
						'shipment_types' => '2S' === $product_id ? array( 'simple' ) : array( 'simple', 'return' ),
					)
				);
			}

			$dom_eu_products = array(
				'PT_AM1'   => _x( 'PrimeTime 10:00', 'dpd', 'woocommerce-germanized-pro' ),
				'PT_AM2'   => _x( 'PrimeTime 12:00', 'dpd', 'woocommerce-germanized-pro' ),
				'PT_PM2'   => _x( 'PrimeTime 17:00', 'dpd', 'woocommerce-germanized-pro' ),
				'PT_TFR'   => _x( 'PrimeTime 12:00-17:00', 'dpd', 'woocommerce-germanized-pro' ),
				'PT_AM1-6' => _x( 'PrimeTime Saturday 10:00', 'dpd', 'woocommerce-germanized-pro' ),
				'PT_AM2-6' => _x( 'PrimeTime Saturday 12:00', 'dpd', 'woocommerce-germanized-pro' ),
			);

			foreach ( $dom_eu_products as $product_id => $label ) {
				$this->register_product(
					$product_id,
					array(
						'label'          => $label,
						'zones'          => array( 'dom', 'eu' ),
						'shipment_types' => array( 'simple' ),
					)
				);
			}
		}
	}

	protected function register_services() {
		if ( 'web_connect' === $this->get_api_type() ) {
			$this->register_service(
				'saturday_delivery',
				array(
					'label'    => _x( 'Saturday Delivery', 'dpd', 'woocommerce-germanized-pro' ),
					'products' => array( 'E12' ),
				)
			);

			$this->register_service( new ParcelShopDelivery( $this ) );
			$this->register_service( new Predict( $this ) );

			$this->register_service(
				'international_guarantee',
				array(
					'label'    => _x( 'International Guarantee', 'dpd', 'woocommerce-germanized-pro' ),
					'products' => array( 'CL', 'E18' ),
					'zones'    => array( 'eu', 'int' ),
				)
			);
		} elseif ( 'webservice' === $this->get_api_type() ) {
			$this->register_service( new Predict( $this ) );
			$this->register_service( new HigherInsurance( $this ) );
		}
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_simple_label_fields( $shipment ) {
		$settings     = parent::get_simple_label_fields( $shipment );
		$default_args = $this->get_default_label_props( $shipment );

		if ( 'cloud' === $this->get_api_type() ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'id'          => 'pickup_date',
						'label'       => _x( 'Pickup date', 'dpd', 'woocommerce-germanized-pro' ),
						'description' => '',
						'type'        => 'date',
						'value'       => isset( $default_args['pickup_date'] ) ? $default_args['pickup_date'] : '',
					),
					array(
						'id'                => 'parcel_shop_id',
						'label'             => _x( 'Parcel Shop Number', 'dpd', 'woocommerce-germanized-pro' ),
						'description'       => '',
						'type'              => 'text',
						'custom_attributes' => array( 'data-products-supported' => 'Shop_Delivery' ),
						'value'             => isset( $default_args['parcel_shop_id'] ) ? $default_args['parcel_shop_id'] : '',
					),
				)
			);
		} elseif ( 'web_connect' === $this->get_api_type() ) {
			if ( 'FR' === $shipment->get_country() ) {
				$settings = array_merge(
					$settings,
					array(
						array(
							'id'          => 'business_unit',
							'label'       => _x( 'Routing via', 'dpd', 'woocommerce-germanized-pro' ),
							'description' => '',
							'type'        => 'select',
							'options'     => array(
								'auto' => _x( 'Let DPD decide', 'dpd', 'woocommerce-germanized-pro' ),
								'002'  => _x( 'Chronopost', 'dpd', 'woocommerce-germanized-pro' ),
								'038'  => _x( 'DPD France', 'dpd', 'woocommerce-germanized-pro' ),
							),
							'value'       => isset( $default_args['business_unit'] ) ? $default_args['business_unit'] : 'auto',
						),
					)
				);
			}

			if ( $shipment->is_shipping_international() ) {
				$terms = Package::get_api()->get_international_customs_terms();

				if ( 'GB' === $shipment->get_country() && $shipment->get_total() <= 135 ) {
					$terms = array_intersect_key( $terms, array( '07' => '' ) );
				}

				$settings = array_merge(
					$settings,
					array(
						array(
							'id'          => 'customs_terms',
							'label'       => _x( 'Customs terms', 'dpd', 'woocommerce-germanized-pro' ),
							'description' => '',
							'type'        => 'select',
							'options'     => $terms,
							'value'       => isset( $default_args['customs_terms'] ) ? $default_args['customs_terms'] : '',
						),
						array(
							'id'          => 'customs_paper',
							'label'       => _x( 'Customs paper', 'dpd', 'woocommerce-germanized-pro' ),
							'description' => '',
							'type'        => 'multiselect',
							'options'     => Package::get_api()->get_international_customs_paper(),
							'value'       => isset( $default_args['customs_paper'] ) ? $default_args['customs_paper'] : '',
						),
					)
				);
			}
		} elseif ( 'webservice' === $this->get_api_type() ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'id'          => 'pickup_date',
						'label'       => _x( 'Pickup date', 'dpd', 'woocommerce-germanized-pro' ),
						'description' => '',
						'type'        => 'date',
						'value'       => isset( $default_args['pickup_date'] ) ? $default_args['pickup_date'] : '',
					),
					array(
						'id'                => 'parcel_shop_id',
						'label'             => _x( 'Parcel Shop Number', 'dpd', 'woocommerce-germanized-pro' ),
						'description'       => '',
						'type'              => 'text',
						'custom_attributes' => array( 'data-products-supported' => '2S' ),
						'value'             => isset( $default_args['parcel_shop_id'] ) ? $default_args['parcel_shop_id'] : '',
					),
				)
			);
		}

		return $settings;
	}

	protected function get_default_customs_terms() {
		return '06';
	}

	protected function get_default_customs_paper() {
		return array( 'B', 'G' );
	}

	/**
	 * @param Shipment $shipment
	 * @param $props
	 *
	 * @return \WP_Error|mixed
	 */
	protected function validate_label_request( $shipment, $args = array() ) {
		$args  = wp_parse_args( $args, 'return' === $shipment->get_type() ? $this->get_default_return_label_props( $shipment ) : $this->get_default_simple_label_props( $shipment ) );
		$error = new \WP_Error();

		if ( 'web_connect' === $this->get_api_type() && $shipment->is_shipping_international() ) {
			if ( ! in_array( $args['customs_terms'], array_keys( Package::get_api()->get_international_customs_terms() ), true ) ) {
				$error->add( 'customs_terms', _x( 'Please choose a customs term.', 'dpd', 'woocommerce-germanized-pro' ) );
			}
		} elseif ( 'cloud' === $this->get_api_type() ) {
			if ( empty( $args['pickup_date'] ) || ! \Vendidero\Shiptastic\Package::is_valid_datetime( $args['pickup_date'], 'Y-m-d' ) ) {
				$error->add( 500, _x( 'Error while parsing pickup date.', 'dpd', 'woocommerce-germanized-pro' ) );
			}
		}

		if ( wc_stc_shipment_wp_error_has_errors( $error ) ) {
			return $error;
		}

		return $args;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_default_label_props( $shipment ) {
		if ( 'return' === $shipment->get_type() ) {
			$dpd_defaults = $this->get_default_return_label_props( $shipment );
		} else {
			$dpd_defaults = $this->get_default_simple_label_props( $shipment );
		}

		$defaults = parent::get_default_label_props( $shipment );

		return array_replace_recursive( $defaults, $dpd_defaults );
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_default_return_label_props( $shipment ) {
		$product_id = $this->get_default_label_product( $shipment );
		$defaults   = array();

		if ( 'cloud' === $this->get_api_type() ) {
			if ( $pickup_date = Package::get_api()->get_next_available_pickup_date( $product_id ) ) {
				$defaults = array_merge(
					$defaults,
					array(
						'pickup_date' => $pickup_date->format( 'Y-m-d' ),
					)
				);
			}
		} elseif ( 'webservice' === $this->get_api_type() ) {
			if ( $pickup_date = Package::get_api()->get_next_available_pickup_date( $product_id ) ) {
				$defaults = array_merge(
					$defaults,
					array(
						'pickup_date' => $pickup_date->format( 'Y-m-d' ),
					)
				);
			}
		}

		return $defaults;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_default_simple_label_props( $shipment ) {
		$product_id = $this->get_default_label_product( $shipment );
		$defaults   = array();

		if ( 'web_connect' === $this->get_api_type() ) {
			$defaults = array_merge(
				$defaults,
				array(
					'customs_terms' => $this->get_setting( 'label_default_customs_terms', $this->get_default_customs_terms() ),
					'customs_paper' => $this->get_setting( 'label_default_customs_paper', $this->get_default_customs_paper() ),
				)
			);
		} elseif ( 'cloud' === $this->get_api_type() ) {
			if ( $pickup_date = Package::get_api()->get_next_available_pickup_date( $product_id ) ) {
				$defaults = array_merge(
					$defaults,
					array(
						'pickup_date' => $pickup_date->format( 'Y-m-d' ),
					)
				);
			}

			if ( $shipment->has_pickup_location() ) {
				$defaults['parcel_shop_id'] = $shipment->get_pickup_location_code();
			}
		} elseif ( 'webservice' === $this->get_api_type() ) {
			if ( $pickup_date = Package::get_api()->get_next_available_pickup_date( $product_id ) ) {
				$defaults = array_merge(
					$defaults,
					array(
						'pickup_date' => $pickup_date->format( 'Y-m-d' ),
					)
				);
			}

			if ( $shipment->has_pickup_location() ) {
				$defaults['parcel_shop_id'] = $shipment->get_pickup_location_code();
			}
		}

		return $defaults;
	}

	protected function get_available_base_countries() {
		return Package::get_supported_countries();
	}

	protected function get_available_api_types() {
		if ( 'AT' === Package::get_base_country() ) {
			$api_types = array(
				'webservice'  => _x( 'Webservice AT', 'dpd', 'woocommerce-germanized-pro' ),
				'web_connect' => _x( 'WebConnect', 'dpd', 'woocommerce-germanized-pro' ),
			);
		} else {
			$api_types = array(
				'cloud'       => _x( 'Cloud Webservice', 'dpd', 'woocommerce-germanized-pro' ),
				'web_connect' => _x( 'WebConnect', 'dpd', 'woocommerce-germanized-pro' ),
			);
		}

		return $api_types;
	}

	protected function get_general_settings( $for_shipping_method = false ) {
		$available_api_types = $this->get_available_api_types();
		$default_api_type    = 'AT' === Package::get_base_country() ? 'webservice' : 'cloud';

		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'dpd_api_options',
			),
			array(
				'title'   => _x( 'API', 'dpd', 'woocommerce-germanized-pro' ),
				'type'    => 'select',
				'id'      => 'api_type',
				'default' => $default_api_type,
				'value'   => $this->get_setting( 'api_type', $default_api_type ),
				'desc'    => in_array( 'cloud', array_keys( $available_api_types ), true ) ? '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'DPD offers two different API\'s. Many DPD customers may only have access to the Cloud Webservice. <a href="%1$s">Learn more</a>', 'dpd', 'woocommerce-germanized-pro' ), 'https://vendidero.de/doc/woocommerce-germanized/dpd-integration-einrichten#api-typen' ) . '</div>' : '',
				'options' => $available_api_types,
			),
			array(
				'title'             => _x( 'Username (Delis ID)', 'dpd', 'woocommerce-germanized-pro' ),
				'type'              => 'text',
				'desc'              => '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'Please use your WebConnect username (Delis ID) and password to connect your shop to the <a href="%1$s">DPD WebConnect API</a>.', 'dpd', 'woocommerce-germanized-pro' ), 'https://vendidero.de/doc/woocommerce-germanized/dpd-integration-einrichten#dpd-webconnect' ) . '</div>',
				'id'                => 'api_username',
				'default'           => '',
				'value'             => $this->get_setting( 'api_username', '' ),
				'custom_attributes' => array(
					'data-show_if_api_type' => 'web_connect',
					'autocomplete'          => 'new-password',
				),
			),

			array(
				'title'             => _x( 'Password', 'dpd', 'woocommerce-germanized-pro' ),
				'type'              => 'password',
				'desc'              => '',
				'id'                => 'api_password',
				'value'             => $this->get_setting( 'api_password', '' ),
				'custom_attributes' => array(
					'data-show_if_api_type' => 'web_connect',
					'autocomplete'          => 'new-password',
				),
			),

			array(
				'title'             => _x( 'Username (Cloud User ID)', 'dpd', 'woocommerce-germanized-pro' ),
				'type'              => 'text',
				'desc'              => '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'Please use your Cloud User ID and password to connect your shop to the <a href="%1$s">DPD Cloud Webservice</a>.', 'dpd', 'woocommerce-germanized-pro' ), 'https://vendidero.de/doc/woocommerce-germanized/dpd-integration-einrichten#dpd-cloud-webservice' ) . '</div>',
				'id'                => 'cloud_api_username',
				'default'           => '',
				'value'             => $this->get_setting( 'cloud_api_username', '' ),
				'custom_attributes' => array(
					'data-show_if_api_type' => 'cloud',
					'autocomplete'          => 'new-password',
				),
			),

			array(
				'title'             => _x( 'Password (Token)', 'dpd', 'woocommerce-germanized-pro' ),
				'type'              => 'password',
				'desc'              => '',
				'id'                => 'cloud_api_password',
				'value'             => $this->get_setting( 'cloud_api_password', '' ),
				'custom_attributes' => array(
					'data-show_if_api_type' => 'cloud',
					'autocomplete'          => 'new-password',
				),
			),

			array(
				'title'             => _x( 'Client', 'dpd', 'woocommerce-germanized-pro' ),
				'type'              => 'text',
				'id'                => 'webservice_api_client',
				'default'           => '',
				'value'             => $this->get_setting( 'webservice_api_client', '' ),
				'custom_attributes' => array(
					'data-show_if_api_type' => 'webservice',
				),
			),
			array(
				'title'             => _x( 'Username', 'dpd', 'woocommerce-germanized-pro' ),
				'type'              => 'text',
				'desc'              => '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'Please contact <a href="mailto:%1$s?subject=Credentials for WEB.Service REST API">GWP DPD IT support</a> to retrieve your credentials or <a href="mailto:%2$s">General support</a> if you are not yet a customer.', 'dpd', 'woocommerce-germanized-pro' ), 'it-kundenberatung@gwp.dpd.at', 'neukunden@gwp.dpd.at' ) . '</div>',
				'id'                => 'webservice_api_username',
				'default'           => '',
				'value'             => $this->get_setting( 'webservice_api_username', '' ),
				'custom_attributes' => array(
					'data-show_if_api_type' => 'webservice',
					'autocomplete'          => 'new-password',
				),
			),

			array(
				'title'             => _x( 'Password', 'dpd', 'woocommerce-germanized-pro' ),
				'type'              => 'password',
				'desc'              => '',
				'id'                => 'webservice_api_password',
				'value'             => $this->get_setting( 'webservice_api_password', '' ),
				'custom_attributes' => array(
					'data-show_if_api_type' => 'webservice',
					'autocomplete'          => 'new-password',
				),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'dpd_api_options',
			),
		);

		$general_settings = parent::get_general_settings();

		return array_merge( $settings, $general_settings );
	}

	/**
	 * @param ConfigurationSet $configuration_set
	 *
	 * @return mixed
	 */
	protected function get_label_settings_by_zone( $configuration_set ) {
		$settings = parent::get_label_settings_by_zone( $configuration_set );

		if ( 'web_connect' === $this->get_api_type() && 'shipping_provider' === $configuration_set->get_setting_type() ) {
			if ( 'int' === $configuration_set->get_zone() && 'simple' === $configuration_set->get_shipment_type() ) {
				$settings = array_merge(
					$settings,
					array(
						array(
							'title'    => _x( 'Default Customs Terms', 'dpd', 'woocommerce-germanized-pro' ),
							'type'     => 'select',
							'default'  => self::get_default_customs_terms(),
							'id'       => 'label_default_customs_terms',
							'value'    => $this->get_setting( 'label_default_customs_terms', $this->get_default_customs_terms() ),
							'desc'     => _x( 'Please select your default customs terms.', 'dpd', 'woocommerce-germanized-pro' ),
							'desc_tip' => true,
							'options'  => Package::get_api()->get_international_customs_terms(),
							'class'    => 'wc-enhanced-select',
						),
						array(
							'title'    => _x( 'Default Customs Paper', 'dpd', 'woocommerce-germanized-pro' ),
							'type'     => 'multiselect',
							'default'  => self::get_default_customs_paper(),
							'id'       => 'label_default_customs_paper',
							'value'    => $this->get_setting( 'label_default_customs_paper', $this->get_default_customs_paper() ),
							'desc'     => _x( 'Please select which documents you are attaching to international shipments.', 'dpd', 'woocommerce-germanized-pro' ),
							'desc_tip' => true,
							'options'  => Package::get_api()->get_international_customs_paper(),
							'class'    => 'wc-enhanced-select',
						),
					)
				);
			}
		}

		return $settings;
	}

	public function update_settings( $section = '', $data = null, $save = true ) {
		$settings_to_save       = Settings::get_sanitized_settings( $this->get_settings( $section ), $data );
		$restore_label_defaults = false;

		if ( isset( $settings_to_save['api_type'] ) && $settings_to_save['api_type'] !== $this->get_api_type( 'edit' ) ) {
			$restore_label_defaults = true;
		}

		/**
		 * Reset pickup details transient when username changes
		 */
		if ( isset( $settings_to_save['cloud_api_username'] ) && $settings_to_save['cloud_api_username'] !== $this->get_setting( 'cloud_api_username' ) ) {
			delete_transient( 'dpd_pickup_details' );
		}

		parent::update_settings( $section, $data, $save );

		/**
		 * In case the API type has changed, make sure to restore defaults to prevent setting mismatches.
		 */
		if ( $restore_label_defaults ) {
			$this->reset_configuration_sets();

			foreach ( $this->get_printing_settings() as $setting ) {
				$type    = isset( $setting['type'] ) ? $setting['type'] : 'title';
				$default = isset( $setting['default'] ) ? $setting['default'] : null;

				if ( in_array( $type, array( 'title', 'sectionend', 'html' ), true ) || ! isset( $setting['id'] ) || empty( $setting['id'] ) ) {
					continue;
				}

				$this->update_setting( $setting['id'], $default );
			}

			$this->set_tracking_url_placeholder( $this->get_default_tracking_url_placeholder() );
			$this->save();

			foreach ( \WC_Shipping_Zones::get_zones() as $zone_data ) {
				if ( $zone = \WC_Shipping_Zones::get_zone( $zone_data['id'] ) ) {
					foreach ( $zone->get_shipping_methods() as $method ) {
						if ( $shipment_method = MethodHelper::get_provider_method( $method ) ) {
							if ( 'dpd' === $shipment_method->get_shipping_provider() ) {
								$config_sets = $shipment_method->get_configuration_sets();

								if ( ! empty( $config_sets ) ) {
									$shipment_method->reset_configuration_sets();
									$current_settings = $shipment_method->get_method()->instance_settings;

									if ( ! empty( $current_settings ) ) {
										update_option( $shipment_method->get_method()->get_instance_option_key(), apply_filters( 'woocommerce_shipping_' . $shipment_method->get_method()->id . '_instance_settings_values', $current_settings, $shipment_method->get_method() ), 'yes' );
									}
								}
							}
						}
					}
				}
			}
		}
	}

	public function get_help_link() {
		return 'https://vendidero.de/doc/woocommerce-germanized/dpd-integration-einrichten';
	}

	public function get_signup_link() {
		return 'https://www.dpd.com/de/de/versenden/angebot-fuer-geschaeftskunden/';
	}

	public function get_default_label_product( $shipment ) {
		$default_label_product = parent::get_default_label_product( $shipment );

		if ( 'cloud' === $this->get_api_type() ) {
			if ( $shipment->has_pickup_location() ) {
				$available             = $this->get_available_label_products( $shipment );
				$default_label_product = array_key_exists( 'Shop_Delivery', $available ) ? 'Shop_Delivery' : $default_label_product;
			}
		} elseif ( 'webservice' === $this->get_api_type() ) {
			if ( $shipment->has_pickup_location() ) {
				$available             = $this->get_available_label_products( $shipment );
				$default_label_product = array_key_exists( '2S', $available ) ? '2S' : $default_label_product;
			}
		}

		return $default_label_product;
	}

	public function test_connection() {
		return Package::get_api()->test_connection();
	}

	public function supports_pickup_locations() {
		return true;
	}

	public function supports_pickup_location_delivery( $address, $query_args = array() ) {
		if ( ! $this->enable_pickup_location_delivery() ) {
			return false;
		}

		$query_args       = $this->parse_pickup_location_query_args( $query_args );
		$address          = $this->parse_pickup_location_address_args( $address );
		$country_supports = false;
		$excluded         = is_callable( array( '\Vendidero\Shiptastic\PickupDelivery', 'get_excluded_gateways' ) ) ? PickupDelivery::get_excluded_gateways() : array();
		$supports         = ! in_array( $query_args['payment_gateway'], $excluded, true );

		if ( 'web_connect' === $this->get_api_type() ) {
			if ( $supports && ! empty( $address['country'] ) ) {
				$transient_name      = "dpd_for_shiptastic_country_has_pickup_locations_{$address['country']}";
				$cached_availability = get_transient( $transient_name );

				if ( false === $cached_availability ) {
					$pickup_locations = $this->fetch_pickup_locations( array( 'country' => $address['country'] ), array( 'limit' => 1 ) );
					$country_supports = ! empty( $pickup_locations ) ? true : false;

					set_transient( $transient_name, wc_bool_to_string( $country_supports ) );
				} else {
					$country_supports = wc_string_to_bool( $cached_availability );
				}
			}
		} elseif ( in_array( $this->get_api_type(), array( 'cloud', 'webservice' ), true ) ) {
			/**
			 * Cloud API does not support querying parcel shops on a per-country basis without providing a postcode.
			 */
			$country_supports = \Vendidero\Shiptastic\Package::country_belongs_to_eu_customs_area( $address['country'] ) || in_array( $address['country'], array( 'NO', 'CH', 'GB' ), true );
		}

		return $supports && $country_supports;
	}

	protected function fetch_single_pickup_location( $location_code, $address = array() ) {
		$address       = $this->get_address_by_pickup_location_code( $location_code, $address );
		$location_code = $this->parse_pickup_location_code( $location_code );

		if ( empty( $location_code ) ) {
			return false;
		}

		$result          = Package::get_api()->get_parcel_shop_by_id( $location_code, $address );
		$pickup_location = null;

		if ( null !== $result ) {
			$pickup_location = $this->get_pickup_location_from_api_response( $result );
		}

		return $pickup_location;
	}

	protected function format_street_and_number( $street, $number, $country ) {
		return function_exists( 'wc_stc_get_address_from_street_and_number' ) ? wc_stc_get_address_from_street_and_number( $street, $number, $country ) : $street . ' ' . $number;
	}

	protected function get_pickup_location_from_api_response( $location ) {
		$shop_data = array();

		if ( isset( $location['ShopAddress'] ) ) { // cloud
			$country   = \Vendidero\Shiptastic\Package::get_country_iso_alpha2( wc_clean( $location['ShopAddress']['Country'] ) );
			$shop_data = array(
				'shop_id'   => wc_clean( $location['ParcelShopID'] ),
				'pudo_id'   => Package::get_pudo_id_from_cloud_parcel_shop( $location ),
				'latitude'  => wc_clean( $location['GeoData']['Latitude'] ),
				'longitude' => wc_clean( $location['GeoData']['Longitude'] ),
				'address'   => array(
					'company'   => wc_clean( $location['ShopAddress']['Company'] ),
					'country'   => $country,
					'postcode'  => wc_clean( $location['ShopAddress']['ZipCode'] ),
					'address_1' => $this->format_street_and_number( wc_clean( $location['ShopAddress']['Street'] ), wc_clean( $location['ShopAddress']['HouseNo'] ), $country ),
					'city'      => wc_clean( $location['ShopAddress']['City'] ),
				),
			);

			$main_code = $shop_data['shop_id'];
		} elseif ( isset( $location['company'] ) ) { // webconnect
			$country = wc_clean( $location['country'] );

			$shop_data = array(
				'shop_id'   => wc_clean( $location['parcelShopId'] ),
				'pudo_id'   => wc_clean( isset( $location['pudoId'] ) ? $location['pudoId'] : '' ),
				'latitude'  => wc_clean( $location['latitude'] ),
				'longitude' => wc_clean( $location['longitude'] ),
				'address'   => array(
					'company'   => wc_clean( $location['company'] ),
					'country'   => $country,
					'postcode'  => wc_clean( $location['zipCode'] ),
					'address_1' => $this->format_street_and_number( wc_clean( $location['street'] ), wc_clean( $location['houseNo'] ), $country ),
					'city'      => wc_clean( $location['city'] ),
				),
			);

			$main_code = $shop_data['pudo_id'];
		} elseif ( isset( $location['id'] ) ) { // webservice
			$country = wc_clean( $location['country'] );

			$shop_data = array(
				'shop_id'   => wc_clean( isset( $location['id'] ) ? $location['id'] : '' ),
				'pudo_id'   => wc_clean( isset( $location['id'] ) ? $location['id'] : '' ),
				'latitude'  => wc_clean( $location['latitude'] ),
				'longitude' => wc_clean( $location['longitude'] ),
				'address'   => array(
					'company'   => wc_clean( $location['name'] ),
					'country'   => $country,
					'postcode'  => wc_clean( $location['postcode'] ),
					'address_1' => wc_clean( $location['street'] ),
					'city'      => wc_clean( $location['city'] ),
				),
			);

			$main_code = $shop_data['pudo_id'];
		}

		if ( empty( $shop_data ) || empty( $main_code ) ) {
			return false;
		}

		return $this->get_pickup_location_instance(
			array(
				'code'                     => $main_code,
				'shop_id'                  => $shop_data['shop_id'],
				'pudo_id'                  => $shop_data['pudo_id'],
				'type'                     => 'parcel_shop',
				'label'                    => $shop_data['address']['company'],
				'latitude'                 => $shop_data['latitude'],
				'longitude'                => $shop_data['longitude'],
				'supports_customer_number' => false,
				'address'                  => $shop_data['address'],
				'address_replacement_map'  => array(
					'address_1' => 'address_1',
					'company'   => 'label',
					'country'   => 'country',
					'postcode'  => 'postcode',
					'city'      => 'city',
				),
			)
		);
	}

	protected function fetch_pickup_locations( $address, $query_args = array() ) {
		$locations = array();

		try {
			$location_data = Package::get_api()->get_parcel_shops( $address, $query_args['limit'] );
		} catch ( \Exception $e ) {
			return null;
		}

		foreach ( $location_data as $location ) {
			if ( $pickup_location = $this->get_pickup_location_from_api_response( $location ) ) {
				$locations[] = $pickup_location;
			}
		}

		return $locations;
	}
}
