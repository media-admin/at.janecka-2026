<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_GZDP_Theme_Astra extends WC_GZDP_Theme {

	protected $payment_wrap_priority = null;

	public function __construct( $template ) {
		parent::__construct( $template );

		add_filter( 'woocommerce_gzd_shopmark_single_product_filters', array( $this, 'single_product_filters' ), 10, 2 );
		add_filter( 'woocommerce_gzd_shopmark_single_product_defaults', array( $this, 'single_product_defaults' ), 10 );

		add_filter( 'woocommerce_gzd_shopmark_product_loop_filters', array( $this, 'product_loop_filters' ), 10, 2 );
		add_filter( 'woocommerce_gzd_shopmark_product_loop_defaults', array( $this, 'product_loop_defaults' ), 10 );

		add_action( 'astra_woo_quick_view_product_summary', array( $this, 'quick_view_summary_hooks' ), 10 );

		add_action( 'wp', array( $this, 'modern_checkout_compatibility' ), 50 );
		add_filter( 'astra_woo_shop_product_structure', array( $this, 'adjust_shop_structure' ), 10 );
	}

	public function adjust_shop_structure( $shop_structure ) {
		if ( is_array( $shop_structure ) && ! empty( $shop_structure ) ) {
			if ( 'no' === get_option( 'woocommerce_gzd_display_listings_add_to_cart' ) ) {
				$shop_structure = array_diff( $shop_structure, array( 'add_cart' ) );
			}
		}

		return $shop_structure;
	}

	protected function get_astra_option( $option_name ) {
		if ( function_exists( 'astra_get_option' ) ) {
			return astra_get_option( $option_name );
		}

		return false;
	}

	public function modern_checkout_compatibility() {
		if ( ! function_exists( 'wc_gzd_checkout_adjustments_disabled' ) ) {
			return;
		}

		if ( $this->extension_is_enabled() && ! defined( 'CARTFLOWS_VER' ) && ! wc_gzd_checkout_adjustments_disabled() && 'modern' === $this->get_astra_option( 'checkout-layout-type' ) ) {
			remove_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_checkout_payment_title' );

			remove_action( 'woocommerce_after_order_notes', 'woocommerce_checkout_payment', 20 );
			remove_action( 'woocommerce_checkout_after_order_review', 'woocommerce_checkout_payment', 10 );

			remove_action( 'woocommerce_review_order_before_cart_contents', 'woocommerce_gzd_template_checkout_table_content_replacement' );
			remove_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_table_product_hide_filter_removal' );

			/**
			 * Re-add astra thumbnails which are disabled by Astra's Germanized compatibility script.
			 */
			add_filter( 'woocommerce_cart_item_name', array( $this, 'add_cart_product_image' ), 10, 3 );

			add_action(
				'woocommerce_checkout_order_review',
				function () {
					if ( ! wc_gzd_checkout_adjustments_disabled() ) {
						if ( doing_action( 'woocommerce_before_checkout_form' ) ) {
							$this->payment_wrap_priority = WC_GZD_Hook_Priorities::instance()->get_priority( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );

							remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', $this->payment_wrap_priority );
						} elseif ( ! is_null( $this->payment_wrap_priority ) ) {
								add_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', $this->payment_wrap_priority );
								$this->payment_wrap_priority = null;
						}
					}
				},
				1
			);

			/**
			 * Prevent the order submit button to be shown within the invisible mobile summary wrap.
			 * Showing the button twice might lead to issues with PayPal Payments Smart Buttons.
			 */
			add_action(
				'woocommerce_before_checkout_form',
				function () {
					if ( ! wc_gzd_checkout_adjustments_disabled() ) {
						global $wc_gzd_astra_adjustments;

						$wc_gzd_astra_adjustments = array(
							'payment'    => false,
							'checkboxes' => false,
							'submit'     => false,
						);

						if ( $current_priority = has_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_order_submit' ) ) {
							remove_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_order_submit', $current_priority );

							$wc_gzd_astra_adjustments['submit'] = array(
								'woocommerce_checkout_order_review',
								$current_priority,
							);
						}

						if ( $current_priority = has_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_render_checkout_checkboxes' ) ) {
							remove_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_render_checkout_checkboxes', $current_priority );

							$wc_gzd_astra_adjustments['checkboxes'] = array(
								'woocommerce_checkout_order_review',
								$current_priority,
							);
						}

						if ( $current_priority = has_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment' ) ) {
							remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', $current_priority );

							$wc_gzd_astra_adjustments['payment'] = array(
								'woocommerce_checkout_order_review',
								$current_priority,
							);
						}
					}
				},
				1
			);

			add_action(
				'woocommerce_before_checkout_form',
				function () {
					if ( ! wc_gzd_checkout_adjustments_disabled() ) {
						global $wc_gzd_astra_adjustments;

						if ( ! empty( $wc_gzd_astra_adjustments ) ) {
							if ( $wc_gzd_astra_adjustments['checkboxes'] ) {
								add_action( $wc_gzd_astra_adjustments['checkboxes'][0], 'woocommerce_gzd_template_render_checkout_checkboxes', $wc_gzd_astra_adjustments['checkboxes'][1] );
							}

							if ( $wc_gzd_astra_adjustments['payment'] ) {
								add_action( $wc_gzd_astra_adjustments['payment'][0], 'woocommerce_checkout_payment', $wc_gzd_astra_adjustments['payment'][1] );
							}

							if ( $wc_gzd_astra_adjustments['submit'] ) {
								add_action( $wc_gzd_astra_adjustments['submit'][0], 'woocommerce_gzd_template_order_submit', $wc_gzd_astra_adjustments['submit'][1] );
							}
						}
					}
				},
				500
			);
		}
	}

	/**
	 * Add or remove cart product image.
	 *
	 * @param string $product_name product name.
	 * @param object $cart_item cart item.
	 * @param string $cart_item_key cart item key.
	 * @return string HTML markup.
	 */
	public function add_cart_product_image( $product_name, $cart_item, $cart_item_key ) {
		$image              = '';
		$is_thumbnail_class = 'ast-disable-image';

		if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && 'modern' === $this->get_astra_option( 'checkout-layout-type' ) && $this->get_astra_option( 'checkout-order-review-product-images', false ) ) {
			// Get product object.
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			// Get product thumbnail.
			$thumbnail = $_product->get_image();

			$is_thumbnail_class = isset( $thumbnail ) ? 'ast-enable-image' : 'ast-disable-image';

			// Add wrapper to image and add some css.
			$image = '<div class="ast-product-thumbnail">' . $thumbnail . ' </div>';
		}

		$product_name = '<div class="ast-product-image ' . $is_thumbnail_class . '"> ' . $image . ' <div class="ast-product-name">' . $product_name . '</div></div>';

		return $product_name;
	}

	public function quick_view_summary_hooks() {
		foreach ( wc_gzd_get_single_product_shopmarks() as $shopmark ) {
			$shopmark->execute();
		}
	}

	public function set_single_product_filter( $filter ) {
		return 'astra_woo_single_price_after';
	}

	public function has_custom_shopmarks() {
		return true;
	}

	public function single_product_defaults( $defaults ) {
		$count = 10;

		foreach ( $defaults as $type => $type_data ) {
			if ( in_array( $type_data['default_filter'], array( 'woocommerce_gzd_single_product_safety_information', 'woocommerce_product_additional_information' ), true ) ) {
				continue;
			}

			$defaults[ $type ]['default_filter']   = 'astra_woo_single_price_after';
			$defaults[ $type ]['default_priority'] = $count++;
		}

		return $defaults;
	}

	public function product_loop_defaults( $defaults ) {
		$count = 10;

		foreach ( $defaults as $type => $type_data ) {
			$defaults[ $type ]['default_filter']   = 'astra_woo_shop_price_after';
			$defaults[ $type ]['default_priority'] = $count++;
		}

		return $defaults;
	}

	public function product_loop_filters( $filters, $load_translations = true ) {
		$filters['astra_woo_shop_price_after'] = array(
			'title'            => $load_translations ? __( 'Astra - After Price', 'woocommerce-germanized-pro' ) : 'Astra - After Price',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		$filters['astra_woo_shop_rating_after'] = array(
			'title'            => $load_translations ? __( 'Astra - After Rating', 'woocommerce-germanized-pro' ) : 'Astra - After Rating',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		$filters['astra_woo_shop_short_description_after'] = array(
			'title'            => $load_translations ? __( 'Astra - After Short Description', 'woocommerce-germanized-pro' ) : 'Astra - After Short Description',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		$filters['astra_woo_shop_add_to_cart_after'] = array(
			'title'            => $load_translations ? __( 'Astra - After Add to Cart', 'woocommerce-germanized-pro' ) : 'Astra - After Add to Cart',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		$filters['astra_woo_shop_category_after'] = array(
			'title'            => $load_translations ? __( 'Astra - After Product Category', 'woocommerce-germanized-pro' ) : 'Astra - After Product Category',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		return $filters;
	}

	public function single_product_filters( $filters, $load_translations = true ) {
		$filters['astra_woo_single_price_after'] = array(
			'title'            => $load_translations ? __( 'Astra - After Price', 'woocommerce-germanized-pro' ) : 'Astra - After Price',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		$filters['astra_woo_single_title_after'] = array(
			'title'            => $load_translations ? __( 'Astra - After Title', 'woocommerce-germanized-pro' ) : 'Astra - After Title',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		$filters['astra_woo_single_short_description_after'] = array(
			'title'            => $load_translations ? __( 'Astra - After Short Description', 'woocommerce-germanized-pro' ) : 'Astra - After Short Description',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		$filters['astra_woo_single_add_to_cart_after'] = array(
			'title'            => $load_translations ? __( 'Astra - After Add to Cart', 'woocommerce-germanized-pro' ) : 'Astra - After Add to Cart',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		$filters['astra_woo_single_category_after'] = array(
			'title'            => $load_translations ? __( 'Astra - After Meta', 'woocommerce-germanized-pro' ) : 'Astra - After Meta',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		$filters['astra_woo_single_product_category_after'] = array(
			'title'            => $load_translations ? __( 'Astra - After Product Category', 'woocommerce-germanized-pro' ) : 'Astra - After Product Category',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		return $filters;
	}

	protected function extension_is_enabled() {
		return is_callable( array( 'Astra_Ext_Extension', 'is_active' ) ) && Astra_Ext_Extension::is_active( 'woocommerce' );
	}
}
