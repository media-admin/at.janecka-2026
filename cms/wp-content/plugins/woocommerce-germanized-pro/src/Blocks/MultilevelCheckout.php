<?php
namespace Vendidero\Germanized\Pro\Blocks;

use Vendidero\Germanized\Pro\Package;

final class MultilevelCheckout {

	public function __construct() {
		$this->adjust_checkout_block();
		$this->register_integrations();
	}

	private function register_integrations() {
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( new \Vendidero\Germanized\Pro\Blocks\Integrations\MultilevelCheckout() );
			}
		);
	}

	public function has_dependencies() {
		return class_exists( 'DOMDocument' );
	}

	private function adjust_checkout_block() {
		add_filter( 'woocommerce_gzd_disable_checkout_block_adjustments', '__return_true' );

		add_filter(
			'render_block',
			function ( $content, $block ) {
				/**
				 * Whether to disable the (structural) adjustments applied to the WooCommerce checkout block.
				 *
				 * @param boolean Whether to disable the checkout adjustments or not.
				 *
				 * @since 3.14.0
				 */
				if ( 'woocommerce/checkout' === $block['blockName'] && ! strstr( $content, '[woocommerce_checkout]' ) ) {
					if ( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
						return $content;
					}

					$content = $this->parse_blocks( $content );
				}

				return $content;
			},
			1000,
			2
		);
	}

	/**
	 * @param string $content
	 * @param \DOMDocument $dom
	 *
	 * @return array
	 */
	private function get_block_configuration( $content, $dom ) {
		$configuration = array(
			'contact'      => array(
				'blocks' => array(
					'woocommerce/checkout-express-payment-block',
					'woocommerce/checkout-contact-information-block',
					'woocommerce/checkout-shipping-address-block',
					'woocommerce/checkout-billing-address-block',
				),
			),
			'shipping'     => array(
				'blocks' => array(
					'woocommerce/checkout-shipping-method-block',
					'woocommerce/checkout-pickup-options-block',
					'woocommerce/checkout-shipping-methods-block',
				),
			),
			'payment'      => array(
				'blocks' => array(
					'woocommerce/checkout-payment-block',
					'woocommerce/checkout-billing-address-block',
					'woocommerce/checkout-order-note-block',
				),
			),
			'confirmation' => array(
				'blocks' => array(
					'woocommerce-germanized/checkout-photovoltaic-system-notice',
					'woocommerce/checkout-terms-block',
					'woocommerce-germanized/checkout-checkboxes',
					'woocommerce/checkout-order-summary-block',
					'woocommerce/checkout-actions-block',
				),
			),
		);

		$xpath        = new \DOMXPath( $dom );
		$known_blocks = array();
		$field_blocks = $xpath->query( "//div[@data-block-name='woocommerce/checkout-fields-block']/div[@data-block-name]" );

		foreach ( $configuration as $step => $data ) {
			$known_blocks = array_merge( $known_blocks, $data['blocks'] );
		}

		foreach ( $field_blocks as $block_priority => $field_block ) {
			$block_name = $field_block->getAttribute( 'data-block-name' );

			if ( ! in_array( $block_name, $known_blocks, true ) ) {
				$unknown_step = 'payment';

				if ( $block_priority < 3 ) {
					$unknown_step = 'contact';
				} elseif ( $block_priority < 6 || strstr( $block_name, 'shipping' ) ) {
					$unknown_step = 'shipping';
				}

				$unknown_step = apply_filters( 'woocommerce_gzdp_multilevel_checkout_unknown_block_step', $unknown_step, $block_name, $content, $dom );

				if ( array_key_exists( $unknown_step, $configuration ) ) {
					$block_count           = count( $configuration[ $unknown_step ]['blocks'] );
					$unknown_step_priority = apply_filters( 'woocommerce_gzdp_multilevel_checkout_unknown_block_step_priority', (int) ceil( $block_priority / $block_count ), $block_name, $content, $dom );

					array_splice( $configuration[ $unknown_step ]['blocks'], $unknown_step_priority, 0, $block_name );

					if ( apply_filters( 'woocommerce_gzdp_multilevel_checkout_unknown_block_disallow_duplicates', true, $block_name ) ) {
						$known_blocks[] = $block_name;
					}
				}
			}
		}

		return apply_filters( 'woocommerce_gzdp_multilevel_checkout_step_configuration', $configuration, $content, $dom );
	}

	private function parse_blocks( $content ) {
		if ( $dom = Package::load_html_dom( $content ) ) {
			$configuration = $this->get_block_configuration( $content, $dom );
			$new_content   = '';
			$xpath         = new \DOMXPath( $dom );

			foreach ( $configuration as $step => $data ) {
				$new_content .= '<div class="wp-block-woocommerce-germanized-pro-multilevel-checkout-step wp-block-woocommerce-germanized-pro-multilevel-checkout-' . esc_attr( $step ) . '-step" data-block-name="woocommerce-germanized-pro/multilevel-checkout-' . esc_attr( $step ) . '-step">';
				$new_content .= '<div class="wp-block-woocommerce-germanized-pro-multilevel-checkout-step-summary" data-block-name="woocommerce-germanized-pro/multilevel-checkout-step-summary"></div>';
				$new_content  = apply_filters( 'woocommerce_gzdp_multilevel_checkout_before_step_content', $new_content, $step, $content );

				/**
				 * Mobile coupon display
				 */
				if ( 'payment' === $step ) {
					$new_content .= '<div data-block-name="woocommerce/checkout-order-summary-block" class="wp-block-woocommerce-checkout-order-summary-block"><div data-block-name="woocommerce/checkout-order-summary-coupon-form-block" class="wp-block-woocommerce-checkout-order-summary-coupon-form-block"></div></div>';
				}

				foreach ( $data['blocks'] as $block_name ) {
					$block_content = $this->get_block_content( $block_name, $dom );

					if ( 'woocommerce/checkout-billing-address-block' === $block_name ) {
						$block_content = '<div data-block-name="woocommerce-germanized-pro/multilevel-checkout-billing-address-block">' . $block_content . '</div>';
					} elseif ( 'woocommerce/checkout-actions-block' === $block_name ) {
						$block_content = '<div data-block-name="woocommerce-germanized-pro/multilevel-checkout-submit">' . $block_content . '</div>';
					}

					$new_content .= apply_filters( 'woocommerce_gzdp_multilevel_checkout_block_content', $block_content, $step, $content );
				}

				$new_content  = apply_filters( 'woocommerce_gzdp_multilevel_checkout_after_step_content', $new_content, $step, $content );
				$new_content .= '<div class="wp-block-woocommerce-germanized-pro-multilevel-checkout-step-footer" data-block-name="woocommerce-germanized-pro/multilevel-checkout-step-footer"></div></div>';
			}

			$sidebar_content = apply_filters( 'woocommerce_gzdp_multilevel_checkout_sidebar_content', $this->get_block_content( 'woocommerce/checkout-order-summary-block', $dom ), $content );
			$checkout        = $xpath->query( "//div[@data-block-name='woocommerce/checkout']" );

			if ( $checkout->count() > 0 ) {
				/**
				 * Append the checkout block (outer only) to a new dom
				 * to extract the markup.
				 */
				$checkout_dom = new \DOMDocument();
				$checkout_dom->appendChild( $checkout_dom->importNode( $checkout[0] ) );

				$checkout_content = Package::get_dom_html_content( $checkout_dom );

				if ( is_wp_error( $checkout_content ) ) {
					$checkout_content = '<div data-block-name="woocommerce/checkout" class="wp-block-woocommerce-checkout wc-gzd-checkout alignwide wc-block-checkout is-loading"></div>';
				}

				$checkout_outer_html_open = str_replace( '</div>', '', $checkout_content );

				$new_content = "
					$checkout_outer_html_open
						<div data-block-name=\"woocommerce-germanized-pro/multilevel-checkout\" class=\"wp-block-woocommerce-germanized-pro-multilevel-checkout is-loading\">
							<div data-block-name=\"woocommerce-germanized-pro/multilevel-checkout-fields-block\" class=\"wp-block-woocommerce-checkout-fields-block\">
								$new_content
							</div>
							<div class=\"wp-block-woocommerce-germanized-pro-multilevel-checkout-sidebar\" data-block-name=\"woocommerce-germanized-pro/multilevel-checkout-sidebar\">
								$sidebar_content
							</div>
						</div>
					</div>
				";
			}
		}

		return $new_content;
	}

	protected function get_block_content( $block_name, $dom ) {
		$xpath   = new \DOMXPath( $dom );
		$blocks  = $xpath->query( "//div[@data-block-name='" . $block_name . "']" );
		$content = '';

		if ( $blocks->count() > 0 ) {
			$block        = $blocks[0];
			$html_content = Package::get_dom_html_content( $block );

			if ( ! is_wp_error( $html_content ) ) {
				$content = $html_content;
			}
		}

		return $content;
	}
}
