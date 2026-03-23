<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Shiptastic\Hermes\ShippingProvider;

use Vendidero\Shiptastic\Hermes\ShippingProvider\Services\CustomerAlertService;
use Vendidero\Shiptastic\Hermes\Package;
use Vendidero\Shiptastic\Hermes\ShippingProvider\Services\IdentService;
use Vendidero\Shiptastic\Hermes\ShippingProvider\Services\ParcelShopDeliveryService;
use Vendidero\Shiptastic\Hermes\ShippingProvider\Services\SignatureService;
use Vendidero\Shiptastic\PickupDelivery;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShippingProvider\Auto;

defined( 'ABSPATH' ) || exit;

class Hermes extends Auto {

	public function get_title( $context = 'view' ) {
		return _x( 'Hermes', 'hermes', 'woocommerce-germanized-pro' );
	}

	public function get_name( $context = 'view' ) {
		return 'hermes';
	}

	public function get_description( $context = 'view' ) {
		return _x( 'Create Hermes labels and return labels conveniently.', 'hermes', 'woocommerce-germanized-pro' );
	}

	public function get_default_tracking_url_placeholder() {
		return 'https://www.myhermes.de/empfangen/sendungsverfolgung/sendungsinformation/#{tracking_id}';
	}

	public function is_sandbox() {
		return Package::get_api()->is_sandbox();
	}

	public function get_supported_label_reference_types( $shipment_type = 'simple' ) {
		$reference_types = array(
			'clientReference'  => array(
				'label'      => _x( 'Reference 1', 'hermes', 'woocommerce-germanized-pro' ),
				'default'    => 'return' === $shipment_type ? _x( 'Return #{shipment_number}, order {order_number}', 'hermes', 'woocommerce-germanized-pro' ) : _x( '#{shipment_number}, order {order_number}', 'hermes', 'woocommerce-germanized-pro' ),
				'max_length' => 20,
			),
			'clientReference2' => array(
				'label'      => _x( 'Reference 2', 'hermes', 'woocommerce-germanized-pro' ),
				'default'    => '',
				'max_length' => 20,
			),
		);

		return $reference_types;
	}

	public function get_label_classname( $type ) {
		if ( 'return' === $type ) {
			return '\Vendidero\Shiptastic\Hermes\Label\Retoure';
		} else {
			return '\Vendidero\Shiptastic\Hermes\Label\Simple';
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

	public function set_api_username( $username ) {
		$this->update_meta_data( 'api_username', $username );
	}

	public function get_setting_sections() {
		$sections = parent::get_setting_sections();

		return $sections;
	}

	protected function register_products() {
		$this->register_product(
			'PARCEL',
			array(
				'label'          => _x( 'Parcel', 'hermes', 'woocommerce-germanized-pro' ),
				'shipment_types' => array( 'simple' ),
			)
		);

		$this->register_product(
			'Label',
			array(
				'label'          => _x( 'Label', 'hermes', 'woocommerce-germanized-pro' ),
				'shipment_types' => array( 'return' ),
			)
		);

		$this->register_product(
			'QR',
			array(
				'label'          => _x( 'QR Code', 'hermes', 'woocommerce-germanized-pro' ),
				'shipment_types' => array( 'return' ),
			)
		);
	}

	protected function register_services() {
		$this->register_service(
			'bulkGoodService',
			array(
				'label'     => _x( 'Bulky Goods', 'hermes', 'woocommerce-germanized-pro' ),
				'countries' => array( 'DE' ),
				'zones'     => array( 'dom' ),
			)
		);

		$this->register_service( new CustomerAlertService( $this ) );
		$this->register_service( new ParcelShopDeliveryService( $this ) );
		$this->register_service( new SignatureService( $this ) );

		if ( ! Package::is_self_service_customer() ) {
			$this->register_service( new IdentService( $this ) );

			$this->register_service(
				'compactParcelService',
				array(
					'label'     => _x( 'Compact Parcel', 'hermes', 'woocommerce-germanized-pro' ),
					'countries' => array( 'DE' ),
					'zones'     => array( 'dom' ),
				)
			);

			$this->register_service(
				'nextDayService',
				array(
					'label'     => _x( 'Next day', 'hermes', 'woocommerce-germanized-pro' ),
					'countries' => array( 'DE' ),
					'zones'     => array( 'dom' ),
				)
			);

			$this->register_service(
				'lateInjectionService',
				array(
					'label'     => _x( 'Late injection', 'hermes', 'woocommerce-germanized-pro' ),
					'countries' => array( 'DE' ),
					'zones'     => array( 'dom' ),
				)
			);

			$this->register_service(
				'limitedQuantitiesService',
				array(
					'label'     => _x( 'Dangerous goods', 'hermes', 'woocommerce-germanized-pro' ),
					'countries' => array( 'DE' ),
					'zones'     => array( 'dom' ),
				)
			);
		}
	}

	protected function get_available_base_countries() {
		return Package::get_supported_countries();
	}

	protected function get_general_settings( $for_shipping_method = false ) {
		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'hermes_api_options',
			),

			array(
				'title'             => _x( 'Username', 'hermes', 'woocommerce-germanized-pro' ),
				'type'              => 'text',
				'id'                => 'api_username',
				'default'           => '',
				'value'             => $this->get_setting( 'api_username', '' ),
				'custom_attributes' => array(
					'autocomplete' => 'new-password',
				),
			),

			array(
				'title'             => _x( 'Password', 'hermes', 'woocommerce-germanized-pro' ),
				'type'              => 'password',
				'desc'              => '',
				'id'                => 'api_password',
				'value'             => $this->get_setting( 'api_password', '' ),
				'custom_attributes' => array(
					'autocomplete' => 'new-password',
				),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'hermes_api_options',
			),
		);

		$general_settings = parent::get_general_settings();

		return array_merge( $settings, $general_settings );
	}

	public function get_help_link() {
		return 'https://vendidero.de/doc/woocommerce-germanized/hermes-integration-einrichten';
	}

	public function get_signup_link() {
		return 'https://www.myhermes.de/geschaeftskunden/kunde-werden/myhermes-business/';
	}

	public function test_connection() {
		return Package::get_api()->test_connection();
	}

	public function supports_pickup_locations() {
		return true;
	}

	public function enable_pickup_location_delivery() {
		if ( method_exists( get_parent_class( $this ), 'enable_pickup_location_delivery' ) ) {
			return parent::enable_pickup_location_delivery();
		}

		return true;
	}

	public function supports_pickup_location_delivery( $address, $query_args = array() ) {
		if ( ! $this->enable_pickup_location_delivery() ) {
			return false;
		}

		$query_args = $this->parse_pickup_location_query_args( $query_args );
		$address    = $this->parse_pickup_location_address_args( $address );
		$types      = $this->get_pickup_location_types();
		$excluded   = is_callable( array( '\Vendidero\Shiptastic\PickupDelivery', 'get_excluded_gateways' ) ) ? PickupDelivery::get_excluded_gateways() : array();
		$supports   = in_array( $address['country'], array( 'DE' ), true ) && ! empty( $types ) && ! in_array( $query_args['payment_gateway'], $excluded, true );

		return $supports;
	}

	protected function fetch_single_pickup_location( $location_code, $address = array() ) {
		$address       = $this->get_address_by_pickup_location_code( $location_code, $address );
		$location_code = $this->parse_pickup_location_code( $location_code );

		if ( empty( $location_code ) ) {
			return false;
		}

		try {
			$result          = Package::get_parcel_shop_finder_api()->find_by_id( $location_code, $address['country'] );
			$pickup_location = $this->get_pickup_location_from_api_response( $result );
		} catch ( \Exception $e ) {
			$pickup_location = null;

			if ( 404 === $e->getCode() ) {
				$pickup_location = false;
			}
		}

		return $pickup_location;
	}

	protected function parse_pickup_location_code( $location_code ) {
		$location_code = parent::parse_pickup_location_code( $location_code );
		$keyword_id    = preg_replace( '/[^0-9]/', '', $location_code );

		return $keyword_id;
	}

	protected function get_pickup_location_instance( $data ) {
		try {
			$data                           = (array) $data;
			$data['shipping_provider_name'] = $this->get_name();

			return new PickupLocation( $data );
		} catch ( \Exception $e ) {
			Package::log( $e, 'error' );

			return false;
		}
	}

	protected function get_pickup_location_from_api_response( $location ) {
		$address = array(
			'company'   => $location['name'],
			'country'   => $location['countryIsoA2Code'],
			'postcode'  => $location['zip'],
			'address_1' => $location['street'] . ' ' . $location['houseNumber'],
			'city'      => $location['city'],
		);

		/**
		 * When requesting single shops by id, the typeId parameter is missing so be aware
		 */
		$type_id = isset( $location['typeID'] ) ? absint( $location['typeID'] ) : 0;

		return $this->get_pickup_location_instance(
			array(
				'code'                     => $location['parcelShopNumber'],
				'type'                     => 0 === $type_id ? 'parcel_shop' : 'box',
				'label'                    => $location['name'],
				'latitude'                 => $location['latitude'],
				'longitude'                => $location['longitude'],
				'supports_customer_number' => false,
				'address'                  => $address,
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

	protected function get_pickup_location_types() {
		$types = array( 'box', 'parcel_shop' );

		return $types;
	}

	protected function fetch_pickup_locations( $address, $query_args = array() ) {
		$types     = $this->get_pickup_location_types();
		$locations = array();

		if ( empty( $types ) ) {
			return null;
		}

		try {
			$location_data = Package::get_parcel_shop_finder_api()->find(
				array(
					'zip'     => $address['postcode'],
					'country' => $address['country'],
					'city'    => $address['city'],
					'address' => $address['address_1'],
				),
				$types,
				$query_args['limit']
			);
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
}
