<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Shiptastic\GLS\ShippingProvider;

use Vendidero\Shiptastic\GLS\Package;
use Vendidero\Shiptastic\GLS\ShippingProvider\Services\AddonLiability;
use Vendidero\Shiptastic\GLS\ShippingProvider\Services\BaseService;
use Vendidero\Shiptastic\GLS\ShippingProvider\Services\Deposit;
use Vendidero\Shiptastic\Labels\ConfigurationSet;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShippingProvider\Auto;

defined( 'ABSPATH' ) || exit;

class GLS extends Auto {

	public function get_title( $context = 'view' ) {
		return _x( 'GLS', 'gls', 'woocommerce-germanized-pro' );
	}

	public function get_name( $context = 'view' ) {
		return 'gls';
	}

	public function get_description( $context = 'view' ) {
		return _x( 'Create GLS labels and return labels conveniently.', 'gls', 'woocommerce-germanized-pro' );
	}

	public function get_supported_label_reference_types( $shipment_type = 'simple' ) {
		$reference_types = array(
			'ShipmentReference'     => array(
				'label'      => _x( 'Shipment reference', 'gls', 'woocommerce-germanized-pro' ),
				'default'    => _x( '{shipment_number}', 'gls', 'woocommerce-germanized-pro' ),
				'max_length' => 40,
			),
			'ShipmentUnitReference' => array(
				'label'      => _x( 'Shipment unit reference', 'gls', 'woocommerce-germanized-pro' ),
				'default'    => _x( 'Shipment {shipment_number}', 'gls', 'woocommerce-germanized-pro' ),
				'max_length' => 40,
			),
			'Note1'                 => array(
				'label'      => _x( 'Note 1', 'gls', 'woocommerce-germanized-pro' ),
				'default'    => '',
				'max_length' => 50,
			),
			'Note2'                 => array(
				'label'      => _x( 'Note 2', 'gls', 'woocommerce-germanized-pro' ),
				'default'    => '',
				'max_length' => 50,
			),
		);

		return $reference_types;
	}

	public function get_default_tracking_url_placeholder() {
		return 'https://gls-group.eu/track/{tracking_id}';
	}

	public function is_sandbox() {
		return Package::get_api()->is_sandbox();
	}

	public function get_label_classname( $type ) {
		if ( 'return' === $type ) {
			return '\Vendidero\Shiptastic\GLS\Label\Retoure';
		} else {
			return '\Vendidero\Shiptastic\GLS\Label\Simple';
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

	public function get_authentication_type( $context = 'view' ) {
		$auth_type = $this->get_meta( 'authentication_type', true, $context );

		if ( 'view' === $context && empty( $auth_type ) ) {
			$auth_type = ! empty( $this->get_api_username() ) ? 'password' : 'oauth';
		}

		return $auth_type;
	}

	public function set_api_username( $username ) {
		$this->update_meta_data( 'api_username', $username );
	}

	public function set_authentication_type( $username ) {
		$this->update_meta_data( 'authentication_type', $username );
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
		$return_types = Package::get_return_types();

		$settings = array_merge(
			$settings,
			array(
				array(
					'id'      => 'return_type',
					'label'   => _x( 'Return type', 'gls', 'woocommerce-germanized-pro' ),
					'type'    => 'select',
					'options' => $return_types,
					'value'   => $default_args['return_type'] && array_key_exists( $default_args['return_type'], $return_types ) ? $default_args['return_type'] : '',
				),
				array(
					'id'                => 'pickup_date',
					'label'             => _x( 'Pickup Date', 'gls', 'woocommerce-germanized-pro' ),
					'description'       => _x( 'Date when parcel should be picked up at customer.', 'gls', 'woocommerce-germanized-pro' ),
					'desc_tip'          => true,
					'type'              => 'date',
					'value'             => isset( $default_args['pickup_date'] ) ? $default_args['pickup_date'] : '',
					'custom_attributes' => array(
						'data-show-if-return_type' => 'pick_and_return',
					),
				),
			)
		);

		return $settings;
	}

	protected function register_products() {
		$this->register_product(
			'PARCEL',
			array(
				'label'          => _x( 'Parcel', 'gls', 'woocommerce-germanized-pro' ),
				'shipment_types' => array( 'simple', 'return' ),
			)
		);

		$this->register_product(
			'EXPRESS',
			array(
				'label' => _x( 'Express', 'gls', 'woocommerce-germanized-pro' ),
			)
		);
	}

	protected function register_services() {
		$this->register_service(
			new BaseService(
				$this,
				array(
					'id'       => 'ExWorks',
					'label'    => _x( 'ExWorks', 'gls', 'woocommerce-germanized-pro' ),
					'products' => array( 'PARCEL' ),
					'level'    => 'unit',
				)
			)
		);

		$this->register_service( new AddonLiability( $this ) );
		$this->register_service( new Deposit( $this ) );

		$this->register_service(
			new BaseService(
				$this,
				array(
					'id'       => 'FlexDeliveryService',
					'label'    => _x( 'Flex Delivery', 'gls', 'woocommerce-germanized-pro' ),
					'products' => array( 'PARCEL' ),
					'level'    => 'shipment',
				)
			)
		);

		$this->register_service(
			new BaseService(
				$this,
				array(
					'id'       => 'Letterbox',
					'label'    => _x( 'Letterbox', 'gls', 'woocommerce-germanized-pro' ),
					'products' => array( 'PARCEL' ),
					'level'    => 'shipment',
				)
			)
		);

		$this->register_service(
			new BaseService(
				$this,
				array(
					'id'       => 'Guaranteed24Service',
					'label'    => _x( 'Guaranteed 24', 'gls', 'woocommerce-germanized-pro' ),
					'products' => array( 'PARCEL' ),
					'level'    => 'shipment',
				)
			)
		);

		$this->register_service(
			new BaseService(
				$this,
				array(
					'id'       => 'SignatureService',
					'label'    => _x( 'Signature', 'gls', 'woocommerce-germanized-pro' ),
					'products' => array( 'PARCEL' ),
					'level'    => 'shipment',
				)
			)
		);

		$this->register_service(
			new BaseService(
				$this,
				array(
					'id'       => '0800Service',
					'label'    => _x( '08:00', 'gls', 'woocommerce-germanized-pro' ),
					'products' => array( 'EXPRESS' ),
					'level'    => 'shipment',
				)
			)
		);

		$this->register_service(
			new BaseService(
				$this,
				array(
					'id'       => '0900Service',
					'label'    => _x( '09:00', 'gls', 'woocommerce-germanized-pro' ),
					'products' => array( 'EXPRESS' ),
					'level'    => 'shipment',
				)
			)
		);

		$this->register_service(
			new BaseService(
				$this,
				array(
					'id'       => '1000Service',
					'label'    => _x( '10:00', 'gls', 'woocommerce-germanized-pro' ),
					'products' => array( 'EXPRESS' ),
					'level'    => 'shipment',
				)
			)
		);

		$this->register_service(
			new BaseService(
				$this,
				array(
					'id'       => '1200Service',
					'label'    => _x( '12:00', 'gls', 'woocommerce-germanized-pro' ),
					'products' => array( 'EXPRESS' ),
					'level'    => 'shipment',
				)
			)
		);

		$this->register_service(
			new BaseService(
				$this,
				array(
					'id'       => 'SaturdayService',
					'label'    => _x( 'Saturday', 'gls', 'woocommerce-germanized-pro' ),
					'products' => array( 'EXPRESS' ),
					'level'    => 'shipment',
				)
			)
		);

		$this->register_service(
			new BaseService(
				$this,
				array(
					'id'       => 'Saturday1000Service',
					'label'    => _x( 'Saturday 10:00', 'gls', 'woocommerce-germanized-pro' ),
					'products' => array( 'EXPRESS' ),
					'level'    => 'shipment',
				)
			)
		);

		$this->register_service(
			new BaseService(
				$this,
				array(
					'id'       => 'Saturday1200Service',
					'label'    => _x( 'Saturday 12:00', 'gls', 'woocommerce-germanized-pro' ),
					'products' => array( 'EXPRESS' ),
					'level'    => 'shipment',
				)
			)
		);
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_simple_label_fields( $shipment ) {
		$settings = parent::get_simple_label_fields( $shipment );

		if ( 'simple' === $shipment->get_type() ) {
			$default_args = $this->get_default_label_props( $shipment );
			$settings     = array_merge(
				$settings,
				array(
					array(
						'id'          => 'shipping_date',
						'label'       => _x( 'Shipping Date', 'gls', 'woocommerce-germanized-pro' ),
						'description' => _x( 'By default the next working day is used.', 'gls', 'woocommerce-germanized-pro' ),
						'desc_tip'    => true,
						'type'        => 'date',
						'value'       => isset( $default_args['shipping_date'] ) ? $default_args['shipping_date'] : '',
					),
				)
			);
		}

		return $settings;
	}

	/**
	 * @param Shipment $shipment
	 * @param $props
	 *
	 * @return \WP_Error|mixed
	 */
	protected function validate_label_request( $shipment, $props ) {
		if ( 'simple' === $shipment->get_type() ) {
			$props = wp_parse_args(
				$props,
				array(
					'shipping_date' => '',
				)
			);

			$error = new \WP_Error();

			if ( ! empty( $args['shipping_date'] ) && ! \Vendidero\Shiptastic\Package::is_valid_datetime( $args['shipping_date'], 'Y-m-d' ) ) {
				$error->add( 500, _x( 'Error while parsing shipping date.', 'gls', 'woocommerce-germanized-pro' ) );
			}

			if ( wc_stc_shipment_wp_error_has_errors( $error ) ) {
				return $error;
			}
		}

		return $props;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_default_label_props( $shipment ) {
		$defaults = parent::get_default_label_props( $shipment );

		if ( 'simple' === $shipment->get_type() ) {
			$defaults = wp_parse_args(
				$defaults,
				array(
					'shipping_date' => '',
				)
			);

			if ( $shipment->is_shipping_international() ) {
				$defaults['incoterms'] = $this->get_setting( 'label_default_incoterms', '10' );
			}
		} else {
			$return_type = '';

			if ( $config_set = $shipment->get_label_configuration_set() ) {
				$return_type = $config_set->get_setting( 'return_type', $return_type, 'additional' );
			}

			$defaults = wp_parse_args(
				$defaults,
				array(
					'return_type' => $return_type,
				)
			);
		}

		return $defaults;
	}

	protected function get_available_base_countries() {
		return Package::get_supported_countries();
	}

	protected function get_general_settings( $for_shipping_method = false ) {
		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'gls_api_options',
			),

			array(
				'title'   => _x( 'Contact ID', 'gls', 'woocommerce-germanized-pro' ),
				'type'    => 'text',
				'desc'    => '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'Enter your GLS ShipIt Contact ID here. You will receive this from your GLS contact person.', 'gls', 'woocommerce-germanized-pro' ) ) . '</div>',
				'id'      => 'api_contact_id',
				'default' => '',
				'value'   => $this->get_setting( 'api_contact_id', '' ),
			),

			array(
				'title'   => _x( 'Authentication via', 'gls', 'woocommerce-germanized-pro' ),
				'type'    => 'select',
				'options' => array(
					'oauth'    => _x( 'OAuth', 'gls', 'woocommerce-germanized-pro' ),
					'password' => _x( 'Password (legacy)', 'gls', 'woocommerce-germanized-pro' ),
				),
				'id'      => 'authentication_type',
				'default' => ! empty( $this->get_api_username() ) ? 'password' : 'oauth',
				'value'   => $this->get_setting( 'authentication_type', '' ),
			),

			array(
				'title'             => _x( 'API URL', 'gls', 'woocommerce-germanized-pro' ),
				'type'              => 'select',
				'options'           => Package::get_available_api_urls(),
				'desc'              => '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'API URL pointing to the GLS ShipIT backend.', 'gls', 'woocommerce-germanized-pro' ) ) . '</div>',
				'id'                => 'api_url',
				'default'           => 'AT' === Package::get_base_country() ? 'at01' : 'de01',
				'value'             => $this->get_setting( 'api_url', '' ),
				'custom_attributes' => array(
					'data-show_if_authentication_type' => 'password',
				),
			),

			array(
				'title'             => _x( 'Username', 'gls', 'woocommerce-germanized-pro' ),
				'type'              => 'text',
				'id'                => 'api_username',
				'default'           => '',
				'value'             => $this->get_setting( 'api_username', '' ),
				'custom_attributes' => array(
					'autocomplete'                     => 'new-password',
					'data-show_if_authentication_type' => 'password',
				),
			),

			array(
				'title'             => _x( 'Password', 'gls', 'woocommerce-germanized-pro' ),
				'type'              => 'password',
				'desc'              => '',
				'id'                => 'api_password',
				'value'             => $this->get_setting( 'api_password', '' ),
				'custom_attributes' => array(
					'autocomplete'                     => 'new-password',
					'data-show_if_authentication_type' => 'password',
				),
			),

			array(
				'title'             => _x( 'Client ID', 'gls', 'woocommerce-germanized-pro' ),
				'type'              => 'text',
				'id'                => 'api_client_id',
				'default'           => '',
				'value'             => $this->get_setting( 'api_client_id', '' ),
				'custom_attributes' => array(
					'autocomplete'                     => 'new-password',
					'data-show_if_authentication_type' => 'oauth',
				),
			),

			array(
				'title'             => _x( 'Client secret', 'gls', 'woocommerce-germanized-pro' ),
				'type'              => 'password',
				'desc'              => '',
				'id'                => 'api_client_password',
				'value'             => $this->get_setting( 'api_client_password', '' ),
				'custom_attributes' => array(
					'autocomplete'                     => 'new-password',
					'data-show_if_authentication_type' => 'oauth',
				),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'gls_api_options',
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

		if ( 'shipping_provider' === $configuration_set->get_setting_type() ) {
			if ( 'int' === $configuration_set->get_zone() && 'simple' === $configuration_set->get_shipment_type() ) {
				$settings = array_merge(
					$settings,
					array(
						array(
							'title'    => _x( 'Default Incoterms', 'gls', 'woocommerce-germanized-pro' ),
							'type'     => 'select',
							'default'  => '10',
							'value'    => $this->get_setting( 'label_default_incoterms', '10' ),
							'id'       => 'label_default_incoterms',
							'desc_tip' => _x( 'Select default incoterms for international shipments.', 'gls', 'woocommerce-germanized-pro' ),
							'options'  => Package::get_available_incoterms(),
							'class'    => 'wc-enhanced-select',
						),
					)
				);
			}
		}

		if ( 'return' === $configuration_set->get_shipment_type() ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'title'   => _x( 'Return option', 'gls', 'woocommerce-germanized-pro' ),
						'type'    => 'select',
						'default' => '',
						'value'   => $configuration_set->get_setting( 'return_type', '', 'additional' ),
						'id'      => $configuration_set->get_setting_id( 'return_type', 'additional' ),
						'desc'    => '<div class="wc-shiptastic-additional-desc">' . _x( 'Please select your default GLS return option (you can always change this within each individual shipment afterwards).', 'gls', 'woocommerce-germanized-pro' ) . '</div>',
						'options' => Package::get_return_types(),
						'class'   => 'wc-enhanced-select',
					),
				)
			);
		}

		return $settings;
	}

	public function get_help_link() {
		return 'https://vendidero.de/doc/woocommerce-germanized/gls-integration-einrichten';
	}

	public function get_signup_link() {
		return 'https://www.gls-pakete.de/geschaeftlich-versenden/geschaeftskunde-werden';
	}

	public function get_available_label_products( $shipment ) {
		if ( is_callable( 'parent::get_available_label_products' ) ) {
			return parent::get_available_label_products( $shipment );
		} else {
			return array();
		}
	}

	public function get_default_label_product( $shipment ) {
		if ( is_callable( 'parent::get_default_label_product' ) ) {
			return parent::get_default_label_product( $shipment );
		} else {
			return '';
		}
	}

	public function test_connection() {
		return Package::get_api()->test_connection();
	}
}
