<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_GZDP_VAT_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
			$this->frontend_hooks();
		}

		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'set_vat_field' ), 0, 1 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'add_vat_field' ), 0, 1 );

		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'set_formatted_billing_address' ), 0, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'set_formatted_shipping_address' ), 0, 2 );

		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'set_admin_billing_address' ), 0, 1 );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'set_admin_shipping_address' ), 0, 1 );

		add_filter( 'woocommerce_get_country_locale', array( $this, 'hide_vat_field' ), 0, 1 );
		add_filter( 'woocommerce_country_locale_field_selectors', array( $this, 'hide_vat_field_js' ), 0, 1 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'add_vat_address' ), 0, 2 );

		add_filter( 'woocommerce_customer_meta_fields', array( $this, 'add_vat_field_profile' ), 0, 1 );

		add_action( 'edit_user_profile_update', array( $this, 'save_billing_vat_id_field_profile' ), 5 );
		add_action( 'edit_user_profile_update', array( $this, 'save_shipping_vat_id_field_profile' ), 5 );

		add_action( 'personal_options_update', array( $this, 'save_billing_vat_id_field_profile' ), 5 );
		add_action( 'personal_options_update', array( $this, 'save_shipping_vat_id_field_profile' ), 5 );

		// Save VAT result data from transient to corresponding order
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_vat_result_data' ), 10, 1 );
		/*
		 * Some payment gateways (e.g. Amazon pay) retrieve order data within process_payment method.
		 * Use this Germanized hook which fires right before confirming the order to make sure we process the order again after the data might be available.
		 */
		add_action( 'woocommerce_gzd_checkout_order_before_confirmation', array( $this, 'save_vat_result_data_after_payment' ), 10, 1 );

		add_filter( 'woocommerce_ajax_get_customer_details', array( $this, 'customer_details_load_vat_id' ), 10, 3 );

		add_filter( 'woocommerce_ajax_calc_line_taxes', array( $this, 'calc_order_taxes' ), 0, 4 );
		add_action( 'woocommerce_before_order_object_save', array( $this, 'calc_order_taxes_v3' ), 10, 2 );
		add_filter( 'woocommerce_ajax_get_customer_details', array( $this, 'load_customer_vat_id' ), 10, 3 );

		// Add VAT result meta box
		add_action( 'add_meta_boxes', array( $this, 'add_vat_result_meta_box' ), 30, 2 );

		// New Woo Order vat exempt filter
		add_filter( 'woocommerce_order_is_vat_exempt', array( $this, 'order_has_vat_exempt_filter' ), 10, 2 );
		add_action(
			'woocommerce_order_before_calculate_taxes',
			function ( $args, $order ) {
				$order->update_meta_data( 'is_vat_exempt', wc_bool_to_string( $this->order_has_vat_exempt( $order ) ) );
			},
			0,
			2
		);

		// Add VAT ID to order address data
		add_filter( 'woocommerce_get_order_address', array( $this, 'add_order_address_data' ), 10, 3 );

		add_action( 'woocommerce_gzd_register_legal_checkboxes', array( $this, 'register_virtual_vat_location_checkbox' ), 10 );

		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_remove_shipping_vat_id' ), 20 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'update_field_required_status' ), 10 );

		add_filter( 'woocommerce_shiptastic_shipment_shipper_address_default_field_value', array( $this, 'register_shipper_vat_id' ), 10, 2 );
	}

	public function register_shipper_vat_id( $value, $prop ) {
		if ( 'vat_id' === $prop ) {
			$value = $this->set_vat_id_format( $this->get_requester_vat_id() );
		}

		return $value;
	}

	/**
	 * Before validating checkout fields, make sure to update the required
	 * status for the vat id fields as the required status may be subject to
	 * the current customer's location.
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function update_field_required_status( $fields ) {
		if ( isset( $fields['shipping']['shipping_vat_id'] ) ) {
			$fields['shipping']['shipping_vat_id']['required'] = $this->vat_field_is_required();
		}

		if ( isset( $fields['billing']['billing_vat_id'] ) ) {
			$fields['billing']['billing_vat_id']['required'] = $this->vat_field_is_required();
		}

		return $fields;
	}

	public function maybe_remove_shipping_vat_id( $fields ) {
		if ( ! apply_filters( 'woocommerce_gzdp_checkout_supports_shipping_vat_id', true ) && isset( $fields['shipping']['shipping_vat_id'] ) ) {
			unset( $fields['shipping']['shipping_vat_id'] );
		}

		return $fields;
	}

	/**
	 * @param WC_GZD_Legal_Checkbox_Manager $checkbox_manager
	 *
	 * @return void
	 */
	public function register_virtual_vat_location_checkbox( $checkbox_manager ) {
		wc_gzd_register_legal_checkbox(
			'virtual_vat_location',
			array(
				'html_id'              => 'data-virtual-vat-location',
				'html_name'            => 'virtual_vat_location',
				'html_wrapper_classes' => array( 'legal' ),
				'label'                => __( 'I am established, have my permanent address, or usually reside in {country_name}.', 'woocommerce-germanized-pro' ),
				'error_message'        => __( 'Please confirm your permanent address or residence.', 'woocommerce-germanized-pro' ),
				'label_args'           => array( '{country_name}' => '' ),
				'is_mandatory'         => true,
				'priority'             => 10,
				'is_enabled'           => true,
				'is_core'              => true,
				'refresh_fragments'    => true,
				'is_shown'             => false,
				'admin_name'           => __( 'Virtual VAT location', 'woocommerce-germanized-pro' ),
				'admin_desc'           => __( 'Let the customer confirm their residence in case of a VAT exempt with deviating IP location.', 'woocommerce-germanized-pro' ),
				'locations'            => array( 'checkout' ),
			)
		);
	}

	public function add_order_address_data( $data, $type, $order ) {
		if ( 'shipping' === $type && ( $vat_id = $this->get_order_shipping_vat_id( $order ) ) ) {
			$data['vat_id'] = $vat_id;
		} elseif ( 'billing' === $type && ( $vat_id = $this->get_order_billing_vat_id( $order ) ) ) {
			$data['vat_id'] = $vat_id;
		}

		return $data;
	}

	public function add_vat_result_meta_box( $post_type_or_screen_id, $post ) {
		if ( WC_GZDP_Admin::instance()->get_order_screen_id() === $post_type_or_screen_id ) {
			$order = false;

			if ( is_a( $post, 'WC_Order' ) ) {
				$order = wc_get_order( $post );
			} elseif ( $post && is_a( $post, 'WP_Post' ) && 'shop_order' === $post->post_type ) {
				$order = wc_get_order( $post );
			}

			if ( $order && $this->get_order_vat_id( $order ) && $this->get_order_vat_result_data( $order ) ) {
				add_meta_box( 'woocommerce-gzdp-vat-result', __( 'VAT ID validation result', 'woocommerce-germanized-pro' ), array( $this, 'vat_result_meta_box' ), $post_type_or_screen_id, 'side' );
			}
		}
	}

	public function vat_result_meta_box( $post ) {
		if ( $order = wc_get_order( $post ) ) {
			$billing_result  = $this->get_order_vat_result_data( $order, 'billing' );
			$shipping_result = $this->get_order_vat_result_data( $order, 'shipping' );
			$results         = array( $billing_result );

			if ( ! empty( $shipping_result ) && $shipping_result !== $billing_result ) {
				$results[] = $shipping_result;
			}

			foreach ( $results as $result ) {
				if ( false === $result ) {
					continue;
				}

				$qualified         = (array) $result['qualified'];
				$ip_location       = (array) wp_parse_args( $result['ip_location'], array( 'country' => '' ) );
				$qualified_current = array();

				foreach ( $qualified as $q ) {
					$qualified_current[] = ucfirst( $this->get_vat_id_address_field_title( $q ) );
				}
				?>
				<ul class="vat-data">
					<li><strong><?php esc_html_e( 'VAT ID', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( ! empty( $result['vat_id'] ) ? $result['vat_id'] : $this->get_order_vat_id( $order ) ); ?></li>
					<?php if ( ! empty( $result['name'] ) ) : ?>
						<li><strong><?php esc_html_e( 'Name', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( $result['name'] ); ?></li>
					<?php endif; ?>
					<?php if ( ! empty( $result['identifier'] ) ) : ?>
						<li><strong><?php esc_html_e( 'Request ID', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( $result['identifier'] ); ?></li>
					<?php endif; ?>
					<?php if ( ! empty( $result['company'] ) ) : ?>
						<li><strong><?php esc_html_e( 'Company', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( $result['company'] ); ?></li>
					<?php endif; ?>
					<?php if ( ! empty( $result['address'] ) ) : ?>
						<li><strong><?php esc_html_e( 'Address', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( $result['address'] ); ?></li>
					<?php endif; ?>
					<?php if ( ! empty( $result['ip'] ) ) : ?>
						<li><strong><?php esc_html_e( 'IP', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( $result['ip'] ); ?><?php echo ( ! empty( $ip_location['country'] ) ? ' (' . esc_attr( $ip_location['country'] ) . ')' : '' ); ?></li>
					<?php endif; ?>
					<?php
					if ( ! empty( $result['date'] ) ) :
						if ( class_exists( 'WC_DateTime' ) ) {
							$date           = new WC_DateTime( $result['date'] );
							$result['date'] = $date->date_i18n( 'd.m.Y H:i' );
						}
						?>
						<li><strong><?php esc_html_e( 'Date', 'woocommerce-germanized-pro' ); ?></strong> <?php echo esc_attr( $result['date'] ); ?></li>
					<?php endif; ?>
					<?php if ( isset( $result['is_qualified'] ) && $result['is_qualified'] ) : ?>
						<li><strong><?php esc_html_e( 'Qualified', 'woocommerce-germanized-pro' ); ?> <span class="woocommerce-help-tip" style="margin-left: 3px; top: 2px;" data-tip="<?php echo esc_attr( sprintf( __( 'Successfully checked fields: %s', 'woocommerce-germanized-pro' ), implode( ', ', $qualified_current ) ) ); ?>"></span></strong> <span class="dashicons dashicons-yes"></span></li>
					<?php endif; ?>
				</ul>
				<?php
			}
		}
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return boolean
	 */
	public function save_vat_result_data( $order ) {
		$updated = false;

		if ( ( $billing_vat_id = $this->get_order_billing_vat_id( $order ) ) && ! $order->get_meta( '_billing_vat_id_validation_result', true ) ) {
			$order->update_meta_data( '_billing_vat_id_validation_result', $this->aggregate_vat_result_data_for_order( $billing_vat_id, $order, 'billing' ) );
			$updated = true;
		}

		if ( ( $shipping_vat_id = $this->get_order_shipping_vat_id( $order ) ) && ! $order->get_meta( '_shipping_vat_id_validation_result', true ) ) {
			$order->update_meta_data( '_shipping_vat_id_validation_result', $this->aggregate_vat_result_data_for_order( $shipping_vat_id, $order, 'shipping' ) );
			$updated = true;
		}

		return $updated;
	}

	/**
	 * @param WC_Order $order
	 */
	public function save_vat_result_data_after_payment( $order ) {
		if ( $this->save_vat_result_data( $order ) ) {
			$order->save();
		}
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return bool|array
	 */
	public function get_order_vat_result_data( $order, $type = '' ) {
		$legacy_meta = $order->get_meta( '_vat_id_validation_result' );
		$type        = empty( $type ) ? $this->get_vat_address_type_by_order( $order ) : $type;
		$type_meta   = $order->get_meta( "_{$type}_vat_id_validation_result" );

		if ( $legacy_meta && ! empty( $legacy_meta ) ) {
			return $this->parse_vat_validation_data( $legacy_meta );
		} elseif ( $type_meta && ! empty( $type_meta ) ) {
			return $this->parse_vat_validation_data( $type_meta );
		}

		return false;
	}

	public function load_customer_vat_id( $data, $customer, $user_id ) {
		$types = array( 'shipping', 'billing' );

		foreach ( $types as $type ) {
			if ( ! isset( $data[ $type ] ) ) {
				continue;
			}

			$data[ $type ]['vat_id'] = get_user_meta( $user_id, $type . '_vat_id', true );
		}

		return $data;
	}

	public function frontend_hooks() {
		// Make sure that taxes for fees are removed
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'maybe_remove_fee_taxes' ), 10, 1 );

		add_filter( 'woocommerce_process_myaccount_field_billing_vat_id', array( $this, 'user_save_billing_vat_id' ), 0, 1 );
		add_filter( 'woocommerce_process_myaccount_field_shipping_vat_id', array( $this, 'user_save_shipping_vat_id' ), 0, 1 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'set_user_formatted_address' ), 10, 3 );

		// If is VAT exempt (and net prices are used) set tax rounding precision to 2
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_tax_rounding' ) );

		// If prices do not include tax, set taxes to zero if vat exempt
		add_filter( 'woocommerce_calc_tax', array( $this, 'set_price_excluding_tax' ), 0, 5 );

		// Set min max prices for variable products to exclude tax if is vat exempt (pre 1.4.8)
		add_filter( 'woocommerce_variation_prices', array( $this, 'set_variable_exempt' ), 10, 3 );

		// Validate checkout and maybe set VAT exempt before re-calculating totals
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_vat_prices_process_checkout' ), 0 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'additional_checkout_validation' ), 10, 2 );

		add_filter( 'woocommerce_process_checkout_field_billing_vat_id', array( $this, 'set_billing_vat_id_format' ), 10, 1 );
		add_filter( 'woocommerce_process_checkout_field_shipping_vat_id', array( $this, 'set_shipping_vat_id_format' ), 10, 1 );

		add_filter( 'default_checkout_billing_vat_id', array( $this, 'get_checkout_vat_id_from_session' ), 10, 2 );
		add_filter( 'default_checkout_shipping_vat_id', array( $this, 'get_checkout_vat_id_from_session' ), 10, 2 );

		add_action( 'wp_head', array( $this, 'maybe_set_customer_vat_exempt' ), 10 );

		// Register Form
		if ( 'yes' === get_option( 'woocommerce_gzdp_enable_vat_check_register' ) ) {
			add_action( 'woocommerce_register_form', array( $this, 'register_form_input' ), 10 );
			add_filter( 'woocommerce_process_registration_errors', array( $this, 'validate_register_vat_id' ), 10, 4 );
			add_action( 'woocommerce_created_customer', array( $this, 'register_vat_id_customer' ), 10, 3 );
		}
	}

	public function register_vat_id_customer( $customer_id, $new_customer_data, $password_generated ) {
		$vat_id = isset( $_POST['vat_id'] ) ? wc_clean( wp_unslash( $_POST['vat_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! empty( $vat_id ) ) {
			$vat_id_fragments = $this->get_vat_id_from_string( $vat_id );

			if ( $this->validate( $vat_id_fragments['country'], $vat_id_fragments['number'] ) === true ) {
				$country = $this->get_country_by_vat_id_prefix( $vat_id_fragments['country'] );

				add_user_meta( $customer_id, 'billing_vat_id', $this->set_vat_id_format( $vat_id_fragments['number'], $vat_id_fragments['country'] ) );
				add_user_meta( $customer_id, 'billing_country', $country );

				// Make sure customer is a vat exempt
				$this->set_vat_exempt( $country, ( 'XI' === $vat_id_fragments['country'] ? 'BT1234' : '' ) );
			}
		}
	}

	public function validate_register_vat_id( $validation_error, $username, $password, $email ) {
		$vat_id = isset( $_POST['vat_id'] ) ? wc_clean( wp_unslash( $_POST['vat_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $vat_id ) && $this->vat_field_is_required( true ) ) {
			$validation_error->add( 'wc_gzdp_registration_vat_id_missing', __( 'A valid VAT ID is required to register.', 'woocommerce-germanized-pro' ) );
		}

		if ( ! empty( $vat_id ) ) {
			$vat_id_fragments = $this->get_vat_id_from_string( $vat_id );

			if ( ! $this->country_supports_vat_id( $vat_id_fragments['country'] ) ) {
				$country_name = isset( WC()->countries->countries[ $vat_id_fragments['country'] ] ) ? WC()->countries->countries[ $vat_id_fragments['country'] ] : '';

				if ( ! empty( $country_name ) ) {
					$validation_error->add( 'wc_gzdp_registration_vat_id_country_not_supported', sprintf( __( 'Sorry but we do not support VAT IDs within %s.', 'woocommerce-germanized-pro' ), $country_name ) );
				} else {
					$validation_error->add( 'wc_gzdp_registration_vat_id_country_not_supported', __( 'Sorry but we do not support VAT IDs within your country.', 'woocommerce-germanized-pro' ) );
				}
			}

			$address_data = apply_filters( 'woocommerce_gzdp_register_vat_validation_address_data', array() );

			if ( ! isset( $address_data['company'] ) ) {
				add_filter( 'woocommerce_gzdp_vat_validation_company_required', '__return_false', 10 );
			}

			$result = $this->validate( $vat_id_fragments['country'], $vat_id_fragments['number'], $address_data );

			if ( ! isset( $address_data['company'] ) ) {
				remove_filter( 'woocommerce_gzdp_vat_validation_company_required', '__return_false', 10 );
			}

			if ( is_wp_error( $result ) ) {
				foreach ( $result->get_error_messages() as $message ) {
					$validation_error->add( 'wc_gzdp_registration_vat_id_invalid', $message );
				}
			}
		}

		return $validation_error;
	}

	public function register_form_input() {
		wc_get_template( 'myaccount/form-register-vat-id.php', array( 'required' => $this->vat_field_is_required( true ) ) );
	}

	public function get_checkout_vat_id_from_session( $value, $input ) {
		if ( WC()->session && WC()->session->get( $input ) ) {
			return WC()->session->get( $input );
		}

		return $value;
	}

	public function maybe_set_customer_vat_exempt() {
		if ( 'no' === get_option( 'woocommerce_gzdp_enable_vat_check_login' ) ) {
			return;
		}

		if ( ! function_exists( 'is_woocommerce' ) || ! function_exists( 'is_cart' ) || ! function_exists( 'is_checkout' ) || ! function_exists( 'is_account_page' ) ) {
			return;
		}

		if ( ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) && WC()->customer ) {
			WC()->customer->set_is_vat_exempt( false );

			if ( $vat_id = $this->get_user_vat_id() ) {
				$elements     = $this->get_vat_id_from_string( $vat_id );
				$address_data = $this->get_user_address_data();

				if ( $this->country_supports_vat_exempt( $address_data['country'], $address_data['postcode'] ) ) {
					if ( $this->validate( $elements['country'], $elements['number'], $address_data ) === true ) {
						$this->set_vat_exempt( $elements['country'], $address_data['postcode'] );
					}
				}
			}
		}
	}

	public function maybe_remove_fee_taxes( $cart ) {
		if ( WC()->customer && WC()->customer->is_vat_exempt() ) {
			foreach ( $cart->get_fees() as $fee ) {
				$fee->tax      = 0;
				$fee->tax_data = array();
			}
		}
	}

	public function get_vat_address_type_by_checkout_data( $posted ) {
		// Use billing_country as fallback if no shipping_country was supplied (e.g. Amazon Pay)
		$shipping_default_country = isset( $posted['shipping_country'] ) ? $posted['shipping_country'] : $posted['billing_country'];
		$billing_country          = isset( $posted['billing_country'] ) ? $posted['billing_country'] : WC()->countries->get_base_country();
		$shipping_country         = ( isset( $posted['ship_to_different_address'] ) && $posted['ship_to_different_address'] ) ? $shipping_default_country : '';

		$type = $this->get_relevant_address_type_for_vat_exempt(
			array(
				'billing_country'  => $billing_country,
				'billing_vat_id'   => ! empty( $posted['billing_vat_id'] ) ? $posted['billing_vat_id'] : '',
				'shipping_country' => $shipping_country,
				'shipping_vat_id'  => ! empty( $posted['shipping_vat_id'] ) ? $posted['shipping_vat_id'] : '',
			)
		);

		return $type;
	}

	public function customer_details_load_vat_id( $customer_data, $customer, $user_id ) {
		$customer_data['shipping']['vat_id'] = get_user_meta( $user_id, 'shipping_vat_id', true );
		$customer_data['billing']['vat_id']  = get_user_meta( $user_id, 'billing_vat_id', true );
		return $customer_data;
	}

	public function get_vat_id_prefix_by_country( $country ) {
		$country = strtoupper( $country );

		// Treat Isle of Man as UK and Monaco as FR
		$map = array(
			'GR' => 'EL',
			'MC' => 'FR',
			'IM' => 'UK',
		);

		if ( isset( $map[ $country ] ) ) {
			return $map[ $country ];
		}

		return $country;
	}

	public function get_country_by_vat_id_prefix( $vat_id_prefix ) {
		$country = strtoupper( $vat_id_prefix );

		// Northern Ireland belongs to the UK
		$map = array(
			'XI' => 'GB',
			'UK' => 'GB',
			'EL' => 'GR',
		);

		if ( isset( $map[ $vat_id_prefix ] ) ) {
			$country = $map[ $vat_id_prefix ];
		}

		return $country;
	}

	public function get_vat_id_from_string( $number, $expected_country = '' ) {
		// Make sure all letters are uppercase
		$number                    = strtoupper( $number );
		$number                    = trim( preg_replace( '/[^a-z0-9]+/i', '', sanitize_text_field( $number ) ) );
		$original_expected_country = $expected_country;

		/**
		 * Allow skipping country validation (use country provided from VAT ID string)
		 * which might be useful for certain use-cases.
		 */
		if ( ! empty( $expected_country ) && WC()->countries->get_base_country() !== $expected_country && apply_filters( 'woocommerce_gzdp_skip_vat_id_country_validation', false, $expected_country ) ) {
			$expected_country = '';
		}

		if ( ! empty( $expected_country ) ) {
			$expected_country = $this->get_vat_id_prefix_by_country( $expected_country );
		}

		$maybe_country = substr( $number, 0, 2 );

		// Explicitly whitelist Norther Ireland prefix although it is not a country
		if ( 'XI' === $maybe_country ) {
			$expected_country = 'XI';
		}

		if ( empty( $expected_country ) ) {
			preg_match( '/^([A-Z]+)/', $maybe_country, $matches );

			if ( ! empty( $matches ) ) {
				$expected_country = $this->get_vat_id_prefix_by_country( $maybe_country );

				if ( $expected_country === $maybe_country ) {
					$number = substr( $number, 2 );
				}
			} else {
				$expected_country = $this->get_vat_id_prefix_by_country( WC()->countries->get_base_country() );
			}
		} elseif ( $maybe_country === $this->get_vat_id_prefix_by_country( $expected_country ) ) {
			$number = substr( $number, 2 );
		}

		return apply_filters(
			'woocommerce_gzdp_vat_id_fragments',
			array(
				'number'  => $number,
				'country' => $expected_country,
			),
			$number,
			$original_expected_country
		);
	}

	public function set_vat_id_format( $number, $country = '' ) {
		if ( is_array( $number ) ) {
			$elements = wp_parse_args(
				$number,
				array(
					'number'  => '',
					'country' => '',
				)
			);
		} else {
			$elements = $this->get_vat_id_from_string( $number, $country );
		}

		if ( empty( $elements['number'] ) ) {
			return '';
		}

		return apply_filters( 'woocommerce_gzdp_vat_id_format', $elements['country'] . $elements['number'], $number, $country );
	}

	public function set_billing_vat_id_format( $data = '' ) {
		if ( '' === $data ) {
			return $data;
		}

		return $this->set_vat_id_format( $data, ( isset( $_POST['billing_country'] ) ? wc_clean( wp_unslash( $_POST['billing_country'] ) ) : '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	public function set_shipping_vat_id_format( $data = '' ) {
		if ( '' === $data ) {
			return $data;
		}

		return $this->set_vat_id_format( $data, ( isset( $_POST['shipping_country'] ) ? wc_clean( wp_unslash( $_POST['shipping_country'] ) ) : '' ) );  // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	public function set_variable_exempt( $prices_array, $product, $display ) {
		if ( WC()->customer && WC()->customer->is_vat_exempt() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
			foreach ( $prices_array as $type => $variations ) {
				foreach ( $variations as $variation_id => $price ) {
					$variation                              = wc_get_product( $variation_id );
					$prices_array[ $type ][ $variation_id ] = wc_get_price_excluding_tax(
						$variation,
						array(
							'qty'   => 1,
							'price' => $price,
						)
					);
				}
				asort( $prices_array[ $type ] );
			}

			// Prevent multiple vat removal
			remove_filter( 'woocommerce_variation_prices', array( $this, 'set_variable_exempt' ), 10 );
		}
		return $prices_array;
	}

	public function set_price_excluding_tax( $taxes, $price, $rates, $price_includes_tax, $suppress_rounding ) {
		if ( ! wc_prices_include_tax() && ! $price_includes_tax && is_object( WC()->customer ) && WC()->customer->is_vat_exempt() ) {
			$taxes = array();
		}

		return $taxes;
	}

	public function set_tax_rounding() {
		if ( is_object( WC()->customer ) && WC()->customer->is_vat_exempt() && WC()->cart->prices_include_tax ) {
			add_filter( 'woocommerce_calc_tax', array( $this, 'tax_round' ), 0, 5 );
		} else {
			remove_filter( 'woocommerce_calc_tax', array( $this, 'tax_round' ), 0 );
		}
	}

	public function tax_round( $taxes, $price, $rates, $price_includes_tax, $suppress_rounding ) {
		if ( apply_filters( 'woocommerce_gzdp_vat_id_disable_inc_tax_rounding', false ) ) {
			return $taxes;
		}

		foreach ( $taxes as $key => $tax ) {
			$taxes[ $key ] = WC_Tax::round( $tax );
		}

		return $taxes;
	}

	protected function user_save_vat_id( $vat_id, $country, $address_type = 'billing' ) {
		$number    = wc_clean( $vat_id );
		$elements  = $this->get_vat_id_from_string( $number, $country );
		$post_data = $_POST;  // phpcs:ignore WordPress.Security.NonceVerification.Missing

		/**
		 * Add default billing data as billing data is not available while saving the
		 * shipping address and may be needed for validation purposes.
		 */
		if ( 'shipping' === $address_type ) {
			if ( $customer = new WC_Customer( get_current_user_id() ) ) {
				$post_data = array_merge(
					$post_data,
					array(
						'billing_country'  => $customer->get_billing_country(),
						'billing_company'  => $customer->get_billing_company(),
						'billing_postcode' => $customer->get_billing_postcode(),
						'billing_city'     => $customer->get_billing_city(),
					)
				);
			}
		}

		$address_data = $this->get_address_data( $post_data, $address_type );
		$result       = $this->validate( $elements['country'], $elements['number'], $address_data );

		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $message ) {
				wc_add_notice( $message, 'error', array( 'id' => "{$address_type}_vat_id" ) );
			}

			return '';
		}

		return $vat_id;
	}

	public function set_user_formatted_address( $address, $customer_id, $address_type ) {
		$customer = new WC_Customer( $customer_id );

		if ( $customer ) {
			$country  = 'shipping' === $address_type ? $customer->get_shipping_country() : $customer->get_billing_country();
			$postcode = 'shipping' === $address_type ? $customer->get_shipping_postcode() : $customer->get_billing_postcode();
			$vat_id   = $customer->get_meta( "{$address_type}_vat_id", true );

			if ( $vat_id && $this->country_supports_vat_id( $country, $postcode ) ) {
				$address['vat_id'] = $vat_id;
			}
		}

		return $address;
	}

	public function user_save_shipping_vat_id( $vat_id = '' ) {
		if ( ! empty( $vat_id ) ) {
			$shipping_country = ( isset( $_POST['shipping_country'] ) ? wc_clean( wp_unslash( $_POST['shipping_country'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( empty( $shipping_country ) ) {
				wc_add_notice( __( 'Please choose a shipping country before saving your VAT ID.', 'woocommerce-germanized-pro' ), 'error' );
				return '';
			}

			return $this->user_save_vat_id( $vat_id, $shipping_country, 'shipping' );
		}
		return $vat_id;
	}

	public function user_save_billing_vat_id( $vat_id = '' ) {
		if ( ! empty( $vat_id ) ) {
			$billing_country = ( isset( $_POST['billing_country'] ) ? wc_clean( wp_unslash( $_POST['billing_country'] ) ) : WC()->countries->get_base_country() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			return $this->user_save_vat_id( $vat_id, $billing_country, 'billing' );
		}
		return $vat_id;
	}

	public function get_vat_address_type_by_countries( $billing_country, $shipping_country = '' ) {
		wc_deprecated_function( 'WC_GZDP_VAT_Helper::get_vat_address_type_by_countries', '4.2.3' );

		$type = $this->get_relevant_address_type_for_vat_exempt(
			array(
				'billing_country'  => $billing_country,
				'shipping_country' => $shipping_country,
			)
		);

		$type = apply_filters( 'woocommerce_gzdp_vat_address_type_by_countries', $type, $billing_country, $shipping_country );

		return $type;
	}

	public function get_address_differing_fields() {
		return wc_gzdp_get_order_address_differing_fields();
	}

	public function order_has_differing_shipping_address( $order ) {
		return wc_gzdp_order_has_differing_shipping_address( $order );
	}

	public function get_relevant_address_type_for_vat_exempt( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'billing_country'  => '',
				'billing_vat_id'   => '',
				'shipping_country' => '',
				'shipping_vat_id'  => '',
			)
		);

		$args['billing_vat_id']  = $this->set_vat_id_format( $args['billing_vat_id'] );
		$args['shipping_vat_id'] = $this->set_vat_id_format( $args['shipping_vat_id'] );
		$type                    = 'shipping';
		$shipping_country_exists = ( ! empty( $args['shipping_country'] ) ? true : false );
		$shipping_country        = $args['shipping_country'];

		if ( empty( $shipping_country ) ) {
			$shipping_country = $args['billing_country'];
		}

		if ( $args['billing_country'] === $shipping_country && ! $shipping_country_exists ) {
			$type = 'billing';
		}

		if ( 'yes' === get_option( 'woocommerce_gzdp_allow_vat_exempt_with_missing_shipping_vat_id' ) ) {
			if ( $args['billing_country'] === $shipping_country && empty( $args['shipping_vat_id'] ) && ! empty( $args['billing_vat_id'] ) ) {
				$type = 'billing';
			}
		}

		return apply_filters( 'woocommerce_gzdp_relevant_address_type_for_vat_exempt', $type, $args );
	}

	public function get_vat_address_type_by_order( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		$billing_country  = $order->get_billing_country();
		$shipping_country = '';

		if ( $this->order_has_differing_shipping_address( $order ) ) {
			$shipping_country = $order->get_shipping_country();

			// Fallback to billing country
			if ( empty( $shipping_country ) ) {
				$shipping_country = $billing_country;
			}
		}

		$type = $this->get_relevant_address_type_for_vat_exempt(
			array(
				'billing_country'  => $billing_country,
				'billing_vat_id'   => $this->get_order_billing_vat_id( $order ),
				'shipping_country' => $shipping_country,
				'shipping_vat_id'  => $this->get_order_shipping_vat_id( $order ),
			)
		);

		return $type;
	}

	public function order_supports_vat_id( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		// Order type does not allow checking billing country e.g. POS
		if ( ! is_callable( array( $order, 'get_billing_country' ) ) ) {
			return false;
		}

		$eu            = wc_gzdp_get_eu_vat_countries();
		$type          = $this->get_vat_address_type_by_order( $order );
		$vat_id        = $this->get_order_vat_id( $order );
		$user_country  = 'shipping' === $type ? $order->get_shipping_country() : $order->get_billing_country();
		$user_postcode = 'shipping' === $type ? $order->get_shipping_postcode() : $order->get_billing_postcode();

		return apply_filters( 'woocommerce_gzdp_order_supports_vat_id', ( $vat_id && in_array( WC()->countries->get_base_country(), $eu, true ) && $this->country_supports_vat_id( $user_country, $user_postcode ) ), $order );
	}

	public function order_has_vat_exempt_filter( $is_exempt, $order ) {
		$is_exempt = $this->order_has_vat_exempt( $order );

		return $is_exempt;
	}

	public function order_has_vat_exempt( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		// Order does not support billing_country e.g. POS
		if ( ! is_callable( array( $order, 'get_billing_country' ) ) ) {
			return false;
		}

		$type          = $this->get_vat_address_type_by_order( $order );
		$user_country  = 'shipping' === $type ? $order->get_shipping_country() : $order->get_billing_country();
		$user_postcode = 'shipping' === $type ? $order->get_shipping_postcode() : $order->get_billing_postcode();

		return apply_filters( 'woocommerce_gzdp_order_has_vat_exempt', ( $this->order_supports_vat_id( $order ) && $this->country_supports_vat_exempt( $user_country, $user_postcode ) ), $order );
	}

	public function get_order_billing_vat_id( $order ) {
		$vat_id = $order->get_meta( '_billing_vat_id' );

		return ! empty( $vat_id ) ? $vat_id : false;
	}

	public function get_order_shipping_vat_id( $order ) {
		$vat_id = $order->get_meta( '_shipping_vat_id' );

		return ! empty( $vat_id ) ? $vat_id : false;
	}

	public function get_order_vat_id( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		// Order does not support country selection e.g. POS
		if ( ! is_callable( array( $order, 'get_billing_country' ) ) ) {
			return false;
		}

		$type   = $this->get_vat_address_type_by_order( $order );
		$vat_id = $order->get_meta( "_{$type}_vat_id" );

		return ! empty( $vat_id ) ? $vat_id : false;
	}

	public function user_has_differing_shipping_address( $user_id = 0 ) {
		$customer = false;

		if ( empty( $user_id ) && WC()->customer ) {
			$customer = WC()->customer;
		} elseif ( ! empty( $user_id ) ) {
			$customer = new WC_Customer( $user_id );
		}

		if ( ! $customer ) {
			return false;
		}

		$shipping_vat_id = $customer->get_meta( 'shipping_vat_id' );
		$billing_vat_id  = $customer->get_meta( 'billing_vat_id' );

		if ( empty( $user_id ) && WC()->session ) {
			if ( ! is_null( WC()->session->get( 'shipping_vat_id' ) ) ) {
				$shipping_vat_id = WC()->session->get( 'shipping_vat_id' );
			}

			if ( ! is_null( WC()->session->get( 'billing_vat_id' ) ) ) {
				$billing_vat_id = WC()->session->get( 'billing_vat_id' );
			}
		}

		// We do not need to check for differing addresses if the user has no shipping_vat_id
		if ( empty( $shipping_vat_id ) ) {
			return false;
		} elseif ( $shipping_vat_id !== $billing_vat_id ) {
			return true;
		}

		foreach ( $this->get_address_differing_fields() as $field ) {
			$b_getter = "get_billing_{$field}";
			$s_getter = "get_shipping_{$field}";
			$b_data   = '';
			$s_data   = '';

			if ( is_callable( array( $customer, $b_getter ) ) && is_callable( array( $customer, $s_getter ) ) ) {
				$b_data = $customer->$b_getter();
				$s_data = $customer->$s_getter();
			}

			if ( ! empty( $s_data ) && $b_data !== $s_data ) {
				return true;
			}
		}

		return false;
	}

	protected function get_user_vat_address_type( $user_id = false ) {
		$customer = false;
		$type     = 'billing';

		if ( ! $user_id ) {
			$customer = WC()->customer;
		} else {
			$customer = new WC_Customer( $user_id );
		}

		if ( $customer ) {
			$type = $this->get_relevant_address_type_for_vat_exempt(
				array(
					'billing_country'  => $customer->get_billing_country(),
					'billing_vat_id'   => $this->get_user_vat_id( $user_id, 'billing' ),
					'shipping_country' => ( $this->user_has_differing_shipping_address( $user_id ) ? $customer->get_shipping_country() : '' ),
					'shipping_vat_id'  => $this->get_user_vat_id( $user_id, 'shipping' ),
				)
			);
		}

		return $type;
	}

	public function get_user_vat_id( $user_id = false, $address_type = '' ) {
		if ( ! $user_id ) {
			$customer = WC()->customer;
		} else {
			$customer = new WC_Customer( $user_id );
		}

		if ( $customer ) {
			$type     = empty( $address_type ) ? $this->get_user_vat_address_type( $user_id ) : $address_type;
			$country  = 'shipping' === $type ? $customer->get_shipping_country() : $this->get_customer_billing_country();
			$postcode = 'shipping' === $type ? $customer->get_shipping_postcode() : $this->get_customer_billing_postcode();

			if ( ! $this->country_supports_vat_id( $country, $postcode ) ) {
				return false;
			}

			if ( ! $user_id && WC()->session ) {
				if ( is_checkout() && WC()->checkout()->get_value( "{$type}_vat_id" ) ) {
					return WC()->checkout()->get_value( "{$type}_vat_id" );
				} elseif ( is_null( WC()->session->get( "{$type}_vat_id" ) ) ) {
					$the_customer = $customer;

					if ( is_user_logged_in() ) {
						$user_customer = new WC_Customer( get_current_user_id() );

						if ( $user_customer->get_id() > 0 ) {
							$the_customer = $user_customer;
						}
					}

					return $the_customer->get_meta( "{$type}_vat_id", true );
				} else {
					return WC()->session->get( "{$type}_vat_id" );
				}
			} else {
				return $customer->get_meta( "{$type}_vat_id", true );
			}
		}

		return false;
	}

	public function disable_base_rates() {
		return false;
	}

	protected function get_taxable_address() {
		add_filter( 'woocommerce_apply_base_tax_for_local_pickup', array( $this, 'disable_base_rates' ), 10 );
		if ( WC()->customer ) {
			$taxable_address = WC()->customer->get_taxable_address();
			$address         = array(
				'country'  => $taxable_address[0],
				'state'    => $taxable_address[1],
				'postcode' => $taxable_address[2],
				'city'     => $taxable_address[3],
				'company'  => '',
			);
		} else {
			$address = array(
				'country'  => WC()->countries->get_base_country(),
				'state'    => WC()->countries->get_base_state(),
				'postcode' => WC()->countries->get_base_postcode(),
				'city'     => WC()->countries->get_base_city(),
				'company'  => '',
			);
		}
		remove_filter( 'woocommerce_apply_base_tax_for_local_pickup', array( $this, 'disable_base_rates' ), 10 );

		// Override taxable data with current form data
		if ( is_checkout() ) {
			$data           = array();
			$form_was_shown = isset( $_POST['woocommerce-process-checkout-nonce'] ); // phpcs:disable WordPress.Security.NonceVerification.Missing

			if ( $form_was_shown ) {
				if ( isset( $_POST['post_data'] ) ) {
					// Parse Array
					parse_str( wp_unslash( $_POST['post_data'] ), $data ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$data = wc_clean( $data );
				} else {
					// Prevent infinite loops
					remove_filter( 'woocommerce_default_address_fields', array( $this, 'add_vat_field' ), 0 );
					$data = WC()->checkout()->get_posted_data();
					add_filter( 'woocommerce_default_address_fields', array( $this, 'add_vat_field' ), 0 );
				}

				/**
				 * In case data is empty (e.g. while first-time visiting checkout)
				 * use WC()->checkout data as fallback (which uses customer/session data).
				 */
				if ( empty( $data['billing_country'] ) ) {
					$address_type = $this->get_user_vat_address_type();

					$address['country']  = WC()->checkout()->get_value( $address_type . '_country' );
					$address['state']    = WC()->checkout()->get_value( $address_type . '_state' );
					$address['postcode'] = WC()->checkout()->get_value( $address_type . '_postcode' );
					$address['city']     = WC()->checkout()->get_value( $address_type . '_city' );
					$address['company']  = WC()->checkout()->get_value( $address_type . '_company' );
				} else {
					$address_type = $this->get_vat_address_type_by_checkout_data( $data );

					$address['country']  = isset( $data[ "{$address_type}_country" ] ) ? $data[ "{$address_type}_country" ] : '';
					$address['state']    = isset( $data[ "{$address_type}_state" ] ) ? $data[ "{$address_type}_state" ] : '';
					$address['postcode'] = isset( $data[ "{$address_type}_postcode" ] ) ? $data[ "{$address_type}_postcode" ] : '';
					$address['city']     = isset( $data[ "{$address_type}_city" ] ) ? $data[ "{$address_type}_city" ] : '';
					$address['company']  = isset( $data[ "{$address_type}_company" ] ) ? $data[ "{$address_type}_company" ] : '';
				}
			}
		}

		return $address;
	}

	public function set_vat_exempt( $country = '', $postcode = '' ) {
		if ( empty( $country ) ) {
			$address  = $this->get_taxable_address();
			$country  = $address['country'];
			$postcode = $address['postcode'];
		}

		if ( $this->country_supports_vat_exempt( $country, $postcode ) ) {
			WC()->customer->set_is_vat_exempt( true );

			do_action( 'woocommerce_gzdp_customer_is_vat_exempt' );

			/**
			 * Newer versions (>= 4.4) support customer vat exempt status checking. No need to set manually
			 */
			if ( WC()->cart && ! is_callable( array( WC()->cart, 'get_tax_price_display_mode' ) ) ) {
				WC()->cart->tax_display_cart = 'excl';
			}
		}
	}

	public function save_billing_vat_id_field_profile() {
		if ( isset( $_POST['billing_vat_id'] ) && ! empty( $_POST['billing_vat_id'] ) ) {
			$vat_id       = wc_clean( wp_unslash( $_POST['billing_vat_id'] ) );
			$country      = ( isset( $_POST['billing_country'] ) ? wc_clean( wp_unslash( $_POST['billing_country'] ) ) : '' );
			$elements     = $this->get_vat_id_from_string( $vat_id, $country );
			$post_data    = $_POST;
			$address_data = $this->get_address_data( $post_data, 'billing' );

			if ( apply_filters( 'woocommerce_gzdp_validate_admin_profile_vat_id', true, $elements ) && $this->validate( $elements['country'], $elements['number'], $address_data ) !== true ) {
				add_action( 'user_profile_update_errors', array( $this, 'save_vat_field_profile_error' ), 5, 3 );
			}
		}
	}

	public function save_shipping_vat_id_field_profile() {
		if ( isset( $_POST['shipping_vat_id'] ) && ! empty( $_POST['shipping_vat_id'] ) ) {
			$vat_id       = wc_clean( wp_unslash( $_POST['shipping_vat_id'] ) );
			$country      = ( isset( $_POST['shipping_country'] ) ? wc_clean( wp_unslash( $_POST['shipping_country'] ) ) : '' );
			$elements     = $this->get_vat_id_from_string( $vat_id, $country );
			$post_data    = $_POST;
			$address_data = $this->get_address_data( $post_data, 'shipping' );

			if ( apply_filters( 'woocommerce_gzdp_validate_admin_profile_vat_id', true, $elements ) && $this->validate( $elements['country'], $elements['number'], $address_data ) !== true ) {
				add_action( 'user_profile_update_errors', array( $this, 'save_vat_field_profile_error' ), 5, 3 );
			}
		}
	}

	public function save_vat_field_profile_error( $errors, $update, $user ) {
		$errors->add( 'billing_vat_id', __( 'VAT ID seems to be invalid but was still saved. Please check the ID again.', 'woocommerce-germanized-pro' ) );
	}

	public function add_vat_field_profile( $fields ) {
		$fields['billing']['fields']['billing_vat_id'] = array(
			'label'       => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'description' => '',
		);

		$fields['shipping']['fields']['shipping_vat_id'] = array(
			'label'       => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'description' => '',
		);

		return $fields;
	}

	public function set_admin_billing_address( $fields ) {

		$fields['vat_id'] = array(
			'label' => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'show'  => false,
		);

		return $fields;
	}

	public function set_admin_shipping_address( $fields ) {
		$fields['vat_id'] = array(
			'label' => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'show'  => false,
		);

		return $fields;
	}

	public function set_formatted_billing_address( $fields, $order ) {
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return $fields;
		}

		$fields['vat_id'] = '';

		if ( $order->get_meta( '_billing_vat_id' ) ) {
			$fields['vat_id'] = apply_filters( 'woocommerce_gzdp_address_vat_id', $order->get_meta( '_billing_vat_id' ) );
		}

		return $fields;
	}

	/**
	 * @param $fields
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public function set_formatted_shipping_address( $fields, $order ) {
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return $fields;
		}

		$fields['vat_id'] = '';

		if ( $order->get_meta( '_shipping_vat_id' ) ) {
			$fields['vat_id'] = apply_filters( 'woocommerce_gzdp_address_vat_id', $order->get_meta( '_shipping_vat_id' ) );
		}

		return $fields;
	}

	/**
	 * @param array $data
	 * @param WP_Error $errors
	 *
	 * @return void
	 */
	public function additional_checkout_validation( $data, $errors ) {
		$billing_vat_id  = '';
		$shipping_vat_id = '';

		if ( isset( $data['billing_vat_id'] ) && ! empty( $data['billing_vat_id'] ) ) {
			$billing_vat_id = $this->set_vat_id_format( wc_clean( $data['billing_vat_id'] ) );
		}

		if ( isset( $data['ship_to_different_address'] ) && true === $data['ship_to_different_address'] ) {
			if ( isset( $data['shipping_vat_id'] ) && ! empty( $data['shipping_vat_id'] ) ) {
				$shipping_vat_id = $this->set_vat_id_format( wc_clean( $data['shipping_vat_id'] ) );

				/**
				 * Check whether checkout contains billing address (some checkout plugins may revert the shipping vs. billing address logic)
				 * and do only enforce in case there actually is a billing address.
				 */
				if ( $shipping_vat_id && ( empty( $billing_vat_id ) && ! empty( $data['billing_country'] ) ) && apply_filters( 'woocommerce_gzdp_differing_shipping_force_billing_vat_id_existence', true ) ) {
					$errors->add( 'validation', __( 'Please supply a valid VAT ID for your billing address.', 'woocommerce-germanized-pro' ), array( 'id' => 'billing_vat_id' ) );
				}
			}
		}

		if ( ! empty( $billing_vat_id ) ) {
			$address_data = $this->get_address_data( $data, 'billing' );
			$result       = $this->validate( $billing_vat_id, '', $address_data );

			if ( is_wp_error( $result ) ) {
				/**
				 * Prevent duplicate notices via check_vat_exemption
				 */
				if ( ! wc_has_notice( $result->get_error_message(), 'error' ) ) {
					$errors->add( 'validation', $result->get_error_message(), array( 'id' => 'billing_vat_id' ) );
				}
			}
		}

		if ( ! empty( $shipping_vat_id ) ) {
			$address_data = $this->get_address_data( $data, 'shipping' );
			$result       = $this->validate( $shipping_vat_id, '', $address_data );

			if ( is_wp_error( $result ) ) {
				/**
				 * Prevent duplicate notices via check_vat_exemption
				 */
				if ( ! wc_has_notice( $result->get_error_message(), 'error' ) ) {
					$errors->add( 'validation', $result->get_error_message(), array( 'id' => 'shipping_vat_id' ) );
				}
			}
		}
	}

	public function set_vat_prices_process_checkout() {
		if ( is_checkout() && ! is_cart() ) {
			$data           = array();
			$prevent_errors = true;

			if ( isset( $_POST['post_data'] ) ) {
				// Parse Array
				parse_str( wp_unslash( $_POST['post_data'] ), $data ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$data           = wc_clean( $data );
				$prevent_errors = false;

				$data['ship_to_different_address'] = ! empty( $data['ship_to_different_address'] ) ? true : false;
			} else {
				$data = WC()->checkout()->get_posted_data();

				/**
				 * Different to the classic checkout, the block-based checkout has the shipping address as primary address.
				 */
				if ( $this->current_checkout_is_block_based() ) {
					$data['ship_to_different_address'] = $this->ship_to_different_address_default();
				}
			}

			/**
			 * In case data is empty (e.g. while first-time visiting checkout)
			 * use WC()->checkout data as fallback (which uses customer/session data).
			 */
			if ( empty( $data['billing_country'] ) ) {
				$address_type                        = $this->get_user_vat_address_type();
				$data[ $address_type . '_country' ]  = WC()->checkout()->get_value( $address_type . '_country' );
				$data[ $address_type . '_company' ]  = WC()->checkout()->get_value( $address_type . '_company' );
				$data[ $address_type . '_city' ]     = WC()->checkout()->get_value( $address_type . '_city' );
				$data[ $address_type . '_postcode' ] = WC()->checkout()->get_value( $address_type . '_postcode' );
				$data[ $address_type . '_vat_id' ]   = $this->get_user_vat_id();
			} else {
				$address_type = $this->get_vat_address_type_by_checkout_data( $data );
			}

			if ( isset( $data[ "{$address_type}_vat_id" ] ) ) {
				$this->check_vat_exemption( $data, false, $prevent_errors );
			}
		}
	}

	public function get_customer_billing_country() {
		if ( is_callable( array( WC()->customer, 'get_billing_country' ) ) ) {
			return WC()->customer->get_billing_country();
		} else {
			return WC()->customer->get_country();
		}
	}

	public function get_customer_billing_postcode() {
		if ( is_callable( array( WC()->customer, 'get_billing_postcode' ) ) ) {
			return WC()->customer->get_billing_postcode();
		} else {
			return WC()->customer->get_postcode();
		}
	}

	protected function get_address_data( $data, $address_type = 'billing' ) {
		$address_data = array(
			'company'  => isset( $data[ "{$address_type}_company" ] ) ? wc_clean( wp_unslash( $data[ "{$address_type}_company" ] ) ) : '',
			'city'     => isset( $data[ "{$address_type}_city" ] ) ? wc_clean( wp_unslash( $data[ "{$address_type}_city" ] ) ) : '',
			'postcode' => isset( $data[ "{$address_type}_postcode" ] ) ? wc_clean( wp_unslash( $data[ "{$address_type}_postcode" ] ) ) : '',
		);

		if ( 'shipping' === $address_type && isset( $data['billing_country'], $data['shipping_country'] ) && $data['shipping_country'] === $data['billing_country'] ) {
			$billing_data = $this->get_address_data( $data, 'billing' );

			if ( $billing_data['company'] === $address_data['company'] ) {
				$address_data['billing_city']     = $billing_data['city'];
				$address_data['billing_postcode'] = $billing_data['postcode'];
			}
		}

		return $address_data;
	}

	protected function current_checkout_is_block_based() {
		return \Vendidero\Germanized\Utilities\CartCheckout::uses_checkout_block() && ! wc_gzd_post_content_has_shortcode( 'woocommerce_checkout' );
	}

	protected function ship_to_different_address_default() {
		return apply_filters( 'woocommerce_gzdp_vat_helper_ship_to_different_address_default', $this->current_checkout_is_block_based() && WC()->cart->needs_shipping() ? true : false );
	}

	public function check_vat_exemption( $post_data = array(), $errors = false, $prevent_errors = false ) {
		$post_data = wp_parse_args(
			$post_data,
			array(
				'billing_country'           => $this->get_customer_billing_country(),
				'billing_postcode'          => $this->get_customer_billing_postcode(),
				'billing_city'              => WC()->customer ? WC()->customer->get_billing_city() : '',
				'billing_company'           => WC()->customer ? WC()->customer->get_billing_company() : '',
				'shipping_country'          => WC()->customer ? WC()->customer->get_shipping_country() : '',
				'shipping_postcode'         => WC()->customer ? WC()->customer->get_shipping_postcode() : '',
				'shipping_city'             => WC()->customer ? WC()->customer->get_shipping_city() : '',
				'shipping_company'          => WC()->customer ? WC()->customer->get_shipping_company() : '',
				'ship_to_different_address' => $this->ship_to_different_address_default(),
			)
		);

		if ( WC()->customer ) {
			WC()->customer->set_is_vat_exempt( false );
		}

		// Refresh tax display as Woo no checks the customer vat exempt status on load
		if ( WC()->cart && ! is_callable( array( WC()->cart, 'get_tax_price_display_mode' ) ) ) {
			WC()->cart->tax_display_cart = get_option( 'woocommerce_tax_display_cart' );
		}

		if ( WC()->cart ) {
			wc_gzd_update_legal_checkbox(
				'virtual_vat_location',
				array(
					'is_shown' => false,
				)
			);
		}

		$customer_country  = $post_data['billing_country'];
		$customer_postcode = $post_data['billing_postcode'];
		$address_type      = $this->get_vat_address_type_by_checkout_data( $post_data );

		if ( 'shipping' === $address_type ) {
			if ( ! empty( $post_data['shipping_country'] ) ) {
				$customer_country = $post_data['shipping_country'];
			}

			if ( ! empty( $post_data['shipping_postcode'] ) ) {
				$customer_postcode = $post_data['shipping_postcode'];
			}
		}

		if ( WC()->session && WC()->session->get( "{$address_type}_vat_id" ) ) {
			WC()->session->set( "{$address_type}_vat_id", '' );
		}

		if ( isset( $post_data[ "{$address_type}_vat_id" ] ) && ! empty( $customer_country ) && ! empty( $post_data[ "{$address_type}_vat_id" ] ) && $this->country_supports_vat_id( $customer_country, $customer_postcode ) ) {
			$address_data    = $this->get_address_data( $post_data, $address_type );
			$vat_id_elements = $this->get_vat_id_from_string( $post_data[ "{$address_type}_vat_id" ], $customer_country );
			$result          = $this->validate( $vat_id_elements['country'], $vat_id_elements['number'], $address_data );

			if ( ! is_wp_error( $result ) ) {
				if ( WC()->session ) {
					WC()->session->set( "{$address_type}_vat_id", $vat_id_elements['country'] . $vat_id_elements['number'] );
				}

				$this->set_vat_exempt( $customer_country, $customer_postcode );

				if ( WC()->cart && apply_filters( 'woocommerce_gzdp_enable_virtual_vat_location_check', 'yes' === get_option( 'woocommerce_gzdp_virtual_vat_location_check' ) ) ) {
					$items      = WC()->cart->get_cart();
					$is_virtual = false;

					if ( ! empty( $items ) ) {
						foreach ( $items as $cart_item_key => $values ) {
							$_product = apply_filters( 'woocommerce_cart_item_product', $values['data'], $values, $cart_item_key );

							if ( wc_gzd_product_matches_extended_type( array( 'service', 'downloadable', 'virtual' ), $_product ) ) {
								$is_virtual = true;
								break;
							}
						}
					}

					if ( apply_filters( 'woocommerce_gzdp_virtual_vat_location_cart_is_virtual', $is_virtual ) ) {
						$ip_address  = WC_Geolocation::get_ip_address();
						$ip_location = WC_Geolocation::geolocate_ip();

						/**
						 * Check if geolocation check worked and maybe show a checkbox.
						 */
						if ( ! empty( $ip_location['country'] ) ) {
							if ( $ip_location['country'] !== $customer_country ) {
								if ( $checkbox = WC_GZD_Legal_Checkbox_Manager::instance()->get_checkbox( 'virtual_vat_location' ) ) {
									if ( $checkbox->is_enabled() ) {
										wc_gzd_update_legal_checkbox(
											'virtual_vat_location',
											array(
												'is_shown' => true,
												'label_args' => array( '{country_name}' => WC()->countries->get_countries()[ $customer_country ] ),
											)
										);
									}
								}
							}
						}
					}
				}
			} elseif ( ! $prevent_errors ) {
				if ( $errors ) {
					foreach ( $result->get_error_messages() as $message ) {
						$errors->add( 'validation', $message, 'error', array( 'id' => "{$address_type}_vat_id" ) );
					}
				} else {
					foreach ( $result->get_error_messages() as $message ) {
						/**
						 * Prevent double-showing message
						 */
						if ( wc_has_notice( $message, 'error' ) ) {
							continue;
						}

						wc_add_notice( $message, 'error', array( 'id' => "{$address_type}_vat_id" ) );
					}
				}
			}
		}
	}

	public function add_vat_address( $replacements, $args ) {
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

	public function hide_vat_field_js( $fields ) {
		$fields['vat_id'] = '#billing_vat_id_field, #shipping_vat_id_field';
		return $fields;
	}

	protected function is_northern_ireland( $country, $postcode = '' ) {
		return Vendidero\StoreaBill\Countries::is_northern_ireland( $country, $postcode );
	}

	public function country_supports_vat_id( $country, $postcode = '' ) {
		$supports_vat = false;

		if ( WC()->countries->get_base_country() !== $country && ( in_array( $country, wc_gzdp_get_eu_vat_countries(), true ) || $this->is_northern_ireland( $country, $postcode ) ) ) {
			$supports_vat = true;
		}

		if ( 'yes' === get_option( 'woocommerce_gzdp_vat_id_base_country_included' ) && WC()->countries->get_base_country() === $country ) {
			$supports_vat = true;
		}

		/**
		 * Exclude certain postcodes from allowing VAT exemptions
		 */
		if ( Vendidero\StoreaBill\Countries::is_eu_vat_postcode_exemption( $country, $postcode ) ) {
			$supports_vat = false;
		}

		if ( 'CH' === $country ) {
			$supports_vat = true;
		}

		return apply_filters( 'woocommerce_gzdp_country_supports_vat_id', $supports_vat, $country, $postcode );
	}

	protected function base_country_supports_vat_exempts() {
		$base_country       = WC()->countries->get_base_country();
		$base_country_is_eu = in_array( $base_country, wc_gzdp_get_eu_vat_countries(), true );

		return apply_filters( 'woocommerce_gzdp_base_country_supports_vat_exempts', $base_country_is_eu );
	}

	public function country_supports_vat_exempt( $country, $postcode = '' ) {
		$supports_vat_exempt = false;
		$base_country        = WC()->countries->get_base_country();

		if ( $this->base_country_supports_vat_exempts() ) {
			if ( $base_country !== $country && ( in_array( $country, wc_gzdp_get_eu_vat_countries(), true ) || $this->is_northern_ireland( $country, $postcode ) ) ) {
				$supports_vat_exempt = true;
			}

			/**
			 * Exclude certain postcodes from allowing VAT exemptions
			 */
			if ( Vendidero\StoreaBill\Countries::is_eu_vat_postcode_exemption( $country, $postcode ) ) {
				$supports_vat_exempt = false;
			}
		}

		return apply_filters( 'woocommerce_gzdp_country_supports_vat_exempt', $supports_vat_exempt, $country, $postcode );
	}

	public function hide_vat_field( $locale ) {
		$applicable = array_merge( WC()->countries->get_allowed_countries(), WC()->countries->get_shipping_countries() );

		foreach ( $applicable as $country => $name ) {
			if ( ! $this->country_supports_vat_id( $country ) ) {
				if ( ! isset( $locale[ $country ] ) ) {
					$locale[ $country ] = array();
				}

				$locale[ $country ]['vat_id'] = array(
					'required' => false,
					'hidden'   => true,
				);

				if ( 'GB' === $country ) {
					$locale[ $country ]['vat_id'] = array(
						'required' => $this->vat_field_is_required(
							false,
							array(
								'country'  => $country,
								'postcode' => 'BT123',
							)
						),
						'hidden'   => true,
					);
				}
			} elseif ( ! $this->vat_field_is_required( false, array( 'country' => $country ) ) ) {
				$locale[ $country ]['vat_id'] = array(
					'required' => false,
				);
			} else {
				$locale[ $country ]['vat_id'] = array(
					'required' => true,
				);
			}
		}

		return $locale;
	}

	public function set_vat_field( $countries ) {
		foreach ( $countries as $country => $value ) {
			$countries[ $country ] .= "\n{vat_id}";
		}

		return $countries;
	}

	public function add_vat_field( $fields ) {
		$fields['vat_id'] = array(
			'label'       => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'placeholder' => __( 'VAT ID', 'woocommerce-germanized-pro' ),
			'required'    => $this->vat_field_is_required(),
			'clear'       => true,
			'class'       => array( 'form-row-wide' ),
			'priority'    => apply_filters( 'woocommerce_gzdp_vat_field_priority', 100 ),
		);

		return $fields;
	}

	public function vat_field_is_required( $for_registration = false, $location = array() ) {
		$is_required = false;

		if ( empty( $location ) ) {
			$location = $this->get_taxable_address();
		} else {
			$location = wp_parse_args(
				$location,
				array(
					'country'  => '',
					'postcode' => '',
				)
			);
		}

		if ( $this->country_supports_vat_id( $location['country'], $location['postcode'] ) ) {
			if ( 'yes' === get_option( 'woocommerce_gzdp_vat_id_required' ) ) {
				$is_required = true;
			}

			// Check if VAT ID is not forced
			if ( ! $is_required && 'yes' === get_option( 'woocommerce_gzdp_force_virtual_product_business' ) ) {
				$force_for_virtual = 'CH' !== $location['country'] && $this->country_supports_vat_exempt( $location['country'], $location['postcode'] );

				if ( apply_filters( 'woocommerce_gzdp_vat_id_required_for_virtual_cart', $force_for_virtual, $location ) ) {
					// If it is forced check whether current cart contains virtual/downloadable product
					$items = ( WC()->cart ? WC()->cart->get_cart() : array() );

					if ( ! empty( $items ) ) {
						foreach ( $items as $cart_item_key => $values ) {
							$_product = $values['data'];

							if ( ! is_callable( array( $_product, 'is_downloadable' ) ) ) {
								$_product = wc_get_product( $_product );
							}

							if ( $_product->is_downloadable() || $_product->is_virtual() ) {
								$is_required = true;
								break;
							}
						}
					}
				}
			}

			if ( $for_registration ) {
				$is_required = ( 'yes' === get_option( 'woocommerce_gzdp_vat_id_registration_required' ) ) ? true : false;
			}
		}

		return apply_filters( 'woocommerce_gzdp_vat_id_field_is_required', $is_required, $for_registration, $location );
	}

	protected function get_user_address_data( $user_id = false ) {
		$customer = false;

		if ( empty( $user_id ) && WC()->customer ) {
			$customer = WC()->customer;
		} elseif ( ! empty( $user_id ) ) {
			$customer = new WC_Customer( $user_id );
		}

		if ( ! $customer ) {
			return false;
		}

		$type = $this->get_user_vat_address_type( $user_id );

		$address_data = array(
			'company'  => 'shipping' === $type ? $customer->get_shipping_company() : $customer->get_billing_company(),
			'city'     => 'shipping' === $type ? $customer->get_shipping_city() : $customer->get_billing_city(),
			'postcode' => 'shipping' === $type ? $customer->get_shipping_postcode() : $customer->get_billing_postcode(),
			'country'  => 'shipping' === $type ? $customer->get_shipping_country() : $customer->get_billing_country(),
		);

		if ( 'shipping' === $type ) {
			$billing_country  = $customer->get_billing_country();
			$shipping_country = $customer->get_shipping_country();

			if ( $billing_country === $shipping_country && $address_data['company'] === $customer->get_billing_company() ) {
				$address_data['billing_city']     = $customer->get_billing_city();
				$address_data['billing_postcode'] = $customer->get_billing_postcode();
				$address_data['billing_country']  = $customer->get_billing_country();
			}
		}

		return $address_data;
	}

	/**
	 * @param WC_Order $order
	 * @param string $type
	 *
	 * @return mixed
	 */
	protected function get_order_address_data( $order, $type = '' ) {
		$address_type    = empty( $type ) ? $this->get_vat_address_type_by_order( $order ) : $type;
		$getter          = 'get_' . $address_type . '_';
		$getter_company  = $getter . 'company';
		$getter_city     = $getter . 'city';
		$getter_postcode = $getter . 'postcode';

		$address_data = array(
			'company'  => is_callable( array( $order, $getter_company ) ) ? $order->$getter_company() : '',
			'city'     => is_callable( array( $order, $getter_city ) ) ? $order->$getter_city() : '',
			'postcode' => is_callable( array( $order, $getter_postcode ) ) ? $order->$getter_postcode() : '',
		);

		if ( 'shipping' === $address_type ) {
			$billing_country  = $order->get_billing_country();
			$shipping_country = $order->get_shipping_country();

			if ( $billing_country === $shipping_country && $address_data['company'] === $order->get_billing_company() ) {
				$address_data['billing_city']     = $order->get_billing_city();
				$address_data['billing_postcode'] = $order->get_billing_postcode();
			}
		}

		return $address_data;
	}

	/**
	 * @param $vat_id
	 * @param $order
	 *
	 * @return array|false
	 */
	protected function aggregate_vat_result_data_for_order( $vat_id, $order, $type = '' ) {
		$address_data = $this->get_order_address_data( $order, $type );
		$result       = $this->get_vat_validation_data( $vat_id );

		if ( false !== $result && true === $result['valid'] ) {
			$result['is_qualified'] = false;
			$address_data           = $this->validate_vat_id_address_fields( $vat_id, $address_data, $result );

			if ( ! is_wp_error( $address_data ) && ! empty( $address_data ) ) {
				$result['is_qualified'] = true;
				$result['qualified']    = $address_data;
			}

			if ( apply_filters( 'woocommerce_gzdp_virtual_vat_location_store_ip_location', true ) ) {
				$result['ip']          = WC_Geolocation::get_ip_address();
				$result['ip_location'] = WC_Geolocation::geolocate_ip();
			}

			return $result;
		}

		return false;
	}

	protected function get_vat_id_cache_duration() {
		$days = get_option( 'woocommerce_gzdp_vat_check_cache' ) ? (int) get_option( 'woocommerce_gzdp_vat_check_cache', 7 ) : 0;

		if ( $days < 0 ) {
			$days = 0;
		}

		return $days;
	}

	public function vat_id_is_syntactically_valid( $country, $nr ) {
		$formatted_number = trim( preg_replace( '[^A-Z0-9]', '', strtoupper( $country ) . strtoupper( $nr ) ) );
		$is_valid         = true;

		if ( 'XI' === $country ) {
			$regex = '/^XI[A-Z0-9]{5,9}$/';
		} elseif ( 'CH' === $country ) {
			$regex = '/^CHE\d{9}$/';
		} else {
			$regex = '/^(ATU[0-9]{8}|BE[01][0-9]{9}|BG[0-9]{9,10}|HR[0-9]{11}|CY[A-Z0-9]{9}|CZ[0-9]{8,10}|DK[0-9]{8}|EE[0-9]{9}|FI[0-9]{8}|FR[0-9A-Z]{2}[0-9]{9}|DE[0-9]{9}|EL[0-9]{9}|HU[0-9]{8}|IE([0-9]{7}[A-Z]{1,2}|[0-9][A-Z][0-9]{5}[A-Z])|IT[0-9]{11}|LV[0-9]{11}|LT([0-9]{9}|[0-9]{12})|LU[0-9]{8}|MT[0-9]{8}|NL[0-9]{9}B[0-9]{2}|PL[0-9]{10}|PT[0-9]{9}|RO[0-9]{2,10}|SK[0-9]{10}|SI[0-9]{8}|ES(([A-Z])([0-9]{7})([A-Z]|[0-9])|([0-9])([0-9]{7})([A-Z]))|SE[0-9]{12}|GB([0-9]{9}|[0-9]{12}|GD[0-4][0-9]{2}|HA[5-9][0-9]{2}))$/';
		}

		$result = preg_match( $regex, $formatted_number, $matches );

		if ( false === $result || empty( $matches ) || $matches[0] !== $formatted_number ) {
			$is_valid = false;
		}

		return apply_filters( 'woocommerce_gzdp_vat_id_is_syntactically_valid', $is_valid, $formatted_number, $country, $nr );
	}

	public function get_requester_vat_id( $country_to_validate = '' ) {
		$requester_vat_id = get_option( 'woocommerce_gzdp_vat_requester_vat_id', '' );
		$vat_id           = array(
			'number'  => '',
			'country' => '',
		);

		if ( ! empty( $requester_vat_id ) ) {
			$vat_id = $this->get_vat_id_from_string( $requester_vat_id );
		}

		return apply_filters( 'woocommerce_gzdp_requester_vat_id', $vat_id, $country_to_validate );
	}

	/**
	 * Validates a VAT ID without using cached data.
	 *
	 * @param $vat_id
	 *
	 * @return array|WP_Error
	 */
	protected function validate_vat_id( $vat_id ) {
		$vat_validator = false;
		$vat_id_parts  = $this->get_vat_id_from_string( $vat_id );
		$country       = $vat_id_parts['country'];
		$number        = $vat_id_parts['number'];
		$vat_country   = $this->get_country_by_vat_id_prefix( $country );

		if ( ! $this->vat_id_is_syntactically_valid( $country, $number ) ) {
			return new WP_Error( 'vat-id-invalid', __( 'The VAT ID you\'ve provided is invalid.', 'woocommerce-germanized-pro' ) );
		}

		$max_tries_per_day = apply_filters( 'woocommerce_gzdp_vat_validation_max_tries_per_day', current_user_can( 'manage_woocommerce' ) ? -1 : 10 );

		if ( -1 !== (int) $max_tries_per_day ) {
			$ip_address = WC_Geolocation::get_ip_address();

			if ( ! empty( $ip_address ) && '::1' !== $ip_address && '127.0.0.1' !== $ip_address ) {
				$transient_key = 'vat_id_validation_tries_' . $ip_address;
				$current_tries = get_transient( $transient_key );

				if ( false === $current_tries ) {
					$current_tries = 0;
				}

				$current_tries = absint( $current_tries ) + 1;

				if ( $current_tries > $max_tries_per_day ) {
					return new WP_Error( 'vat-request-error', __( 'You\'ve submitted too many different VAT IDs. Please get in touch with us.', 'woocommerce-germanized-pro' ) );
				} else {
					set_transient( $transient_key, $current_tries, apply_filters( 'woocommerce_gzdp_vat_validation_max_tries_per', DAY_IN_SECONDS ) );
				}
			}
		}

		if ( 'CH' === $vat_country ) {
			$vat_validator = new WC_GZDP_VAT_Validation_Switzerland();
		} elseif ( in_array( $vat_country, wc_gzdp_get_eu_vat_countries(), true ) || 'XI' === $country ) {
			$request_vat_id = $this->get_requester_vat_id( $vat_country );

			if ( apply_filters( 'woocommerce_gzdp_vat_validator_use_soap', class_exists( 'SoapClient' ) ) ) {
				$vat_validator = new WC_GZDP_VAT_Validation(
					array(
						'requester_vat_id' => $request_vat_id,
					)
				);
			} else {
				$vat_validator = new WC_GZDP_VAT_Validation_Rest(
					array(
						'requester_vat_id' => $request_vat_id,
					)
				);
			}
		}

		/**
		 * @var WC_GZDP_VAT_Validation|WC_GZDP_VAT_Validation_Rest|WC_GZDP_VAT_Validation_Switzerland $vat_validator
		 */
		$vat_validator = apply_filters( 'woocommerce_gzdp_vat_validator', $vat_validator, $vat_country, $vat_id );

		if ( $vat_validator ) {
			$vat_check_result = apply_filters( 'woocommerce_gzdp_vat_validation_result', $vat_validator->check( $country, $number ), $country, $number );

			if ( true === $vat_check_result ) {
				$validation_data = wp_parse_args( $this->parse_vat_validation_data( $vat_validator->get_data() ), array( 'vat_id' => $vat_id ) );

				if ( $this->get_vat_id_cache_duration() > 0 ) {
					set_transient( 'vat_id_validation_' . $vat_id, $validation_data, $this->get_vat_id_cache_duration() * DAY_IN_SECONDS );
				}

				return $validation_data;
			} elseif ( $errors = $vat_validator->get_error_messages() ) {
				/**
				 * Do only cache wrong VAT IDs in case the ID is really invalid and it was not a general error with the request.
				 */
				if ( 1 === count( $errors->get_error_codes() ) && 'vat-id-invalid' === $errors->get_error_code() ) {
					if ( $this->get_vat_id_cache_duration() > 0 ) {
						set_transient( 'vat_id_validation_' . $vat_id, $this->parse_vat_validation_data( array( 'valid' => false ) ), $this->get_vat_id_cache_duration() * DAY_IN_SECONDS );
					}
				}

				return $errors;
			}
		} else {
			return apply_filters( 'woocommerce_gzdp_vat_id_missing_validator_is_valid', new WP_Error( 'vat-id-invalid', __( 'The VAT ID you\'ve provided is invalid.', 'woocommerce-germanized-pro' ) ), $country, $number );
		}

		return new WP_Error( 'vat-id-invalid', __( 'The VAT ID you\'ve provided is invalid.', 'woocommerce-germanized-pro' ) );
	}

	/**
	 * Validates a VAT ID and it's corresponding address data. Uses cached data if available.
	 *
	 * @param $country
	 * @param $number
	 * @param $address_expected
	 *
	 * @return WP_Error|true
	 */
	public function validate( $country, $number = '', $address_expected = array() ) {
		if ( empty( $number ) ) {
			$vat_id       = $country;
			$vat_id_parts = $this->get_vat_id_from_string( $vat_id );

			$country = $vat_id_parts['country'];
			$number  = $vat_id_parts['number'];
		}

		$country          = $this->get_vat_id_prefix_by_country( $country );
		$vat_id           = $country . $number;
		$company          = isset( $address_expected['company'] ) ? trim( strtolower( $address_expected['company'] ) ) : '';
		$company_required = apply_filters( 'woocommerce_gzdp_vat_validation_company_required', ( 'yes' === get_option( 'woocommerce_gzdp_vat_id_company_required' ) ) );

		/**
		 * Force company existence.
		 */
		if ( $company_required && empty( $company ) ) {
			return new WP_Error( 'vat-id-company-missing', __( 'Please provide your company name to check your VAT ID.', 'woocommerce-germanized-pro' ) );
		}

		$vat_check_result = $this->get_cached_vat_validation_data( $vat_id );

		/**
		 * Do only validate in case caching data is missing or invalid.
		 */
		if ( false === $vat_check_result ) {
			$vat_check_result = $this->validate_vat_id( $vat_id );
		}

		/**
		 * Validate address data in case the VAT ID exists and is valid.
		 */
		if ( ! is_wp_error( $vat_check_result ) && true === $vat_check_result['valid'] ) {
			$address_validation = $this->validate_vat_id_address_fields( $vat_id, $address_expected, $vat_check_result );

			if ( is_wp_error( $address_validation ) ) {
				return $address_validation;
			} else {
				return true;
			}
		} elseif ( ! is_wp_error( $vat_check_result ) ) {
				return new WP_Error( 'vat-id-invalid', __( 'The VAT ID you\'ve provided is invalid.', 'woocommerce-germanized-pro' ) );
		} else {
			return $vat_check_result;
		}
	}

	public function clean_vat_id_address_data( $str ) {
		// WC_Customer may store html encoded strings which may contain &amp; instead of &.
		$str = htmlspecialchars_decode( $str );

		// Parse
		$str = trim( strtolower( str_replace( '-', ' ', $str ) ) );

		// Remove punctuation
		$str = preg_replace( '/[[:punct:]]+/', '', $str );

		// Maybe remove duplicate empty spaces
		$str = preg_replace( '/\s+/u', ' ', $str );

		return $str;
	}

	protected function get_vat_id_address_fields() {
		return array(
			'company'  => _x( 'company', 'vat-validation-field-name', 'woocommerce-germanized-pro' ),
			'postcode' => _x( 'postcode', 'vat-validation-field-name', 'woocommerce-germanized-pro' ),
			'city'     => _x( 'city', 'vat-validation-field-name', 'woocommerce-germanized-pro' ),
		);
	}

	protected function get_vat_id_address_field_title( $field ) {
		$fields = $this->get_vat_id_address_fields();

		return array_key_exists( $field, $fields ) ? $fields[ $field ] : $field;
	}

	/**
	 * @param $vat_id
	 * @param $address_expected
	 * @param null $validation_data
	 *
	 * @return array|WP_Error
	 */
	public function validate_vat_id_address_fields( $vat_id, $address_expected = array(), $result = false ) {
		$result           = false === $result ? $this->get_vat_validation_data( $vat_id ) : $this->parse_vat_validation_data( $result );
		$error            = new WP_Error();
		$validated_fields = array();
		$vat_id_parts     = $this->get_vat_id_from_string( $vat_id );

		if ( false === $result || ! isset( $result['valid'] ) || ! $result['valid'] ) {
			$error->add( 'vat-id-invalid', __( 'The VAT ID you\'ve provided is invalid.', 'woocommerce-germanized-pro' ) );
		} elseif ( ! empty( $result['name'] ) || ! empty( $result['company'] ) ) {
			$formatted_address_data = array_filter( array( trim( $result['name'] ), trim( $result['company'] ), trim( $result['address'] ) ) );
			$address_expected       = wp_parse_args(
				$address_expected,
				array(
					'company'          => '',
					'postcode'         => '',
					'city'             => '',
					'billing_postcode' => '',
					'billing_city'     => '',
				)
			);
			$formatted_address      = $this->clean_vat_id_address_data( implode( ' ', $formatted_address_data ) );

			/**
			 * Clean and convert to array
			 */
			foreach ( $address_expected as $key => $address_data ) {
				$address_expected[ $key ] = array( $this->clean_vat_id_address_data( $address_expected[ $key ] ) );

				if ( ! empty( $address_expected[ $key ] ) ) {
					/**
					 * Additional search for data without whitespace, e.g. formatted NL postcode 7324 AG -> 7324AG
					 */
					$formatted_without_whitespace = preg_replace( '/\s+/', '', $address_expected[ $key ][0] );

					if ( $formatted_without_whitespace !== $address_expected[ $key ][0] ) {
						$address_expected[ $key ][] = $formatted_without_whitespace;
					}

					/**
					 * Additional search for whitespace between strings and numbers, e.g. formatted MT STJ1062 -> STJ 1062
					 */
					$formatted_whitespace_numbers = preg_replace( '/(?<=\D)(?=\d)|(?<=\d)(?=\D)/', ' ', $address_expected[ $key ][0] );

					if ( $formatted_whitespace_numbers !== $address_expected[ $key ][0] ) {
						$address_expected[ $key ][] = $formatted_whitespace_numbers;
					}
				}
			}

			if ( empty( $address_expected['billing_postcode'] ) || $address_expected['billing_postcode'] === $address_expected['postcode'] ) {
				unset( $address_expected['billing_postcode'] );
			}

			if ( empty( $address_expected['billing_city'] ) || $address_expected['billing_city'] === $address_expected['city'] ) {
				unset( $address_expected['billing_city'] );
			}

			$fields_to_check = array_filter( (array) get_option( 'woocommerce_gzdp_vat_id_additional_field_check', array() ) );

			/**
			 * Some countries (e.g. Ireland) do not provide postcode in formatted address responses
			 */
			if ( in_array( $vat_id_parts['country'], array( 'IE' ), true ) ) {
				$fields_to_check = array_diff( $fields_to_check, array( 'postcode' ) );
			}

			if ( ! empty( $formatted_address ) && ! empty( $fields_to_check ) ) {
				foreach ( $fields_to_check as $field_name ) {
					/**
					 * Cannot validate address data in case no address data exists for this vat id.
					 */
					if ( empty( $result['address'] ) && ( 'city' === $field_name || 'postcode' === $field_name ) ) {
						continue;
					}

					$field_name_title = $this->get_vat_id_address_field_title( $field_name );

					if ( array_key_exists( $field_name, $address_expected ) && ! empty( $address_expected[ $field_name ] ) ) {
						$field_values         = $address_expected[ $field_name ];
						$billing_field_values = array_key_exists( "billing_{$field_name}", $address_expected ) ? $address_expected[ "billing_{$field_name}" ] : array();
						$is_field_valid       = false;

						foreach ( array_merge( $field_values, $billing_field_values ) as $field_value ) {
							if ( ! empty( $field_value ) ) {
								$pattern = '/\b' . $field_value . '\b/u';

								if ( preg_match( $pattern, $formatted_address ) ) {
									$is_field_valid = true;
									break;
								}
							}
						}

						if ( ! $is_field_valid ) {
							$error->add( 'vat-id-' . $field_name . '-invalid', sprintf( __( 'The %1$s linked to your VAT ID doesn\'t seem to match the %1$s you\'ve provided.', 'woocommerce-germanized-pro' ), esc_html( $field_name_title ) ) );
						} else {
							$validated_fields[] = $field_name;
						}
					}
				}
			}
		}

		if ( wc_gzd_wp_error_has_errors( $error ) ) {
			return $error;
		} else {
			return $validated_fields;
		}
	}

	/**
	 * Returns cached validation data (if available).
	 *
	 * @param $vat_id
	 *
	 * @return array|false
	 */
	protected function get_cached_vat_validation_data( $vat_id ) {
		if ( $this->get_vat_id_cache_duration() <= 0 ) {
			return false;
		}

		$transient_name = 'vat_id_validation_' . $vat_id;
		$result         = get_transient( $transient_name );

		if ( $result ) {
			return $this->parse_vat_validation_data( $result );
		} else {
			return false;
		}
	}

	/**
	 * Returns validation data (if available) in case the VAT ID is known and valid.
	 * Validates the ID in case no cached data is available (yet).
	 *
	 * @param $vat_id
	 *
	 * @return array|false
	 */
	protected function get_vat_validation_data( $vat_id ) {
		$result = $this->get_cached_vat_validation_data( $vat_id );

		if ( false === $result ) {
			$result = $this->validate_vat_id( $vat_id );

			if ( is_wp_error( $result ) ) {
				return false;
			}
		}

		return $result;
	}

	protected function parse_vat_validation_data( $result ) {
		$defaults = array(
			'valid'        => true,
			'name'         => '',
			'identifier'   => '',
			'company'      => '',
			'address'      => '',
			'date'         => '',
			'vat_id'       => '',
			'is_qualified' => false,
			'qualified'    => array(),
			'raw'          => array(),
			'ip'           => '',
			'ip_location'  => array(),
		);

		$result = wp_parse_args( $result, $defaults );

		return $result;
	}

	/**
	 * @param WC_Order $order
	 * @param $data_store
	 */
	public function calc_order_taxes_v3( $order, $data_store ) {
		if ( ! empty( $_POST['action'] ) && ( 'woocommerce_calc_line_taxes' === wc_clean( wp_unslash( $_POST['action'] ) ) ) ) {
			$vat_ids = array_unique(
				array_filter(
					array(
						$this->get_order_billing_vat_id( $order ),
						$this->get_order_shipping_vat_id( $order ),
					)
				)
			);

			/**
			 * Force update VAT protocol
			 */
			$order->delete_meta_data( '_billing_vat_id_validation_result' );
			$order->delete_meta_data( '_shipping_vat_id_validation_result' );

			if ( ! empty( $vat_ids ) ) {
				$result = true;

				foreach ( $vat_ids as $vat_id ) {
					$vat_id_elements = $this->get_vat_id_from_string( $vat_id );
					$inner_result    = self::instance()->validate( $vat_id_elements['country'], $vat_id_elements['number'] );

					if ( true !== $inner_result ) {
						$result = false;
						break;
					}
				}

				if ( true === $result && $this->order_has_vat_exempt( $order ) ) {
					$order->update_meta_data( 'is_vat_exempt', 'yes' );
					$order->remove_order_items( 'tax' );
					$order->set_shipping_tax( 0 );
					$order->set_cart_tax( 0 );
				}

				$this->save_vat_result_data( $order );
			}
		}
	}

	/**
	 * Item filter when manually recalculating order taxes to check for vat id - WC pre 3.X only
	 *
	 * @param  array $items
	 * @param  int $order_id
	 * @param  string $country
	 * @param  array $data post data
	 * @return array
	 */
	public function calc_order_taxes( $items, $order_id, $country, $data ) {
		remove_filter( 'get_post_metadata', array( $this, 'product_vat_exempt' ), 0 );

		if ( isset( $data['vat_id'] ) && $this->country_supports_vat_id( $country ) ) {
			$vat_id_elements = $this->get_vat_id_from_string( sanitize_text_field( $data['vat_id'] ), $country );

			// Is VAT exempt
			if ( $this->validate( $vat_id_elements['country'], $vat_id_elements['number'] ) ) {
				// Remove product taxable status
				add_filter( 'get_post_metadata', array( $this, 'product_vat_exempt' ), 0, 4 );
				// Remove order taxes
				add_action( 'woocommerce_saved_order_items', array( $this, 'remove_order_vat' ), 0, 2 );
			}
		}
		return $items;
	}

	public function remove_order_vat( $order_id, $items ) {
		$order = wc_get_order( $order_id );
		$order->remove_order_items( 'tax' );
	}

	/**
	 * Temporarily adds a filter to stop products from being taxable - for admin order tax calculation only
	 */
	public function product_vat_exempt( $metadata, $object_id, $meta_key, $single ) {
		if ( '_tax_status' === $meta_key ) {
			return 'none';
		}
	}
}
return WC_GZDP_VAT_Helper::instance();
