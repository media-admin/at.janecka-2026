<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class WC_GZDP_Elementor_Widget_Helper {

	/**
	 * @param WC_GZD_Elementor_Widget $widget
	 *
	 * @return void
	 */
	public static function register_controls( $widget ) {
		$is_price_widget = in_array( $widget->get_postfix(), array( 'unit_price', 'deposit' ), true );

		$widget->add_responsive_control(
			'text_align',
			array(
				'label'     => __( 'Alignment', 'woocommerce-germanized-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => __( 'Left', 'woocommerce-germanized-pro' ),
						'icon'  => 'fa fa-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'woocommerce-germanized-pro' ),
						'icon'  => 'fa fa-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'woocommerce-germanized-pro' ),
						'icon'  => 'fa fa-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}}' => 'text-align: {{VALUE}}',
				),
			)
		);

		if ( $is_price_widget ) {
			$widget->add_control(
				'notice_color',
				array(
					'label'     => __( 'Color', 'woocommerce-germanized-pro' ),
					'type'      => Controls_Manager::COLOR,
					'global'    => array(
						'default' => class_exists( 'Elementor\Core\Kits\Documents\Tabs\Global_Colors' ) ? \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_PRIMARY : \Elementor\Scheme_Color::COLOR_1,
					),
					'selectors' => array(
						'.woocommerce {{WRAPPER}} .price' => 'color: {{VALUE}}',
					),
				)
			);

			$widget->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => 'typography',
					'selector' => '.woocommerce {{WRAPPER}} .price',
					'global'   => array(
						'default' => class_exists( 'Elementor\Core\Kits\Documents\Tabs\Global_Typography' ) ? \Elementor\Core\Kits\Documents\Tabs\Global_Typography::TYPOGRAPHY_PRIMARY : \Elementor\Scheme_Typography::TYPOGRAPHY_1,
					),
				)
			);

			$widget->add_control(
				'sale_heading',
				array(
					'label'     => esc_html__( 'Sale Price', 'woocommerce-germanized-pro' ),
					'type'      => Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);

			$widget->add_control(
				'sale_price_color',
				array(
					'label'     => __( 'Color', 'woocommerce-germanized-pro' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array(
						'.woocommerce {{WRAPPER}} .price ins' => 'color: {{VALUE}};',
					),
				)
			);

			$widget->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => 'sale_price_typography',
					'selector' => '.woocommerce {{WRAPPER}} .price ins',
				)
			);
		} else {
			$widget->add_control(
				'notice_color',
				array(
					'label'     => __( 'Color', 'woocommerce-germanized-pro' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}}' => 'color: {{VALUE}}',
					),
					'global'    => array(
						'default' => class_exists( 'Elementor\Core\Kits\Documents\Tabs\Global_Colors' ) ? \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_TEXT : \Elementor\Scheme_Color::COLOR_1,
					),
				)
			);

			$widget->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => 'typography',
					'selector' => '{{WRAPPER}}',
					'global'   => array(
						'default' => class_exists( 'Elementor\Core\Kits\Documents\Tabs\Global_Typography' ) ? \Elementor\Core\Kits\Documents\Tabs\Global_Typography::TYPOGRAPHY_TEXT : \Elementor\Scheme_Typography::TYPOGRAPHY_1,
					),
				)
			);
		}
	}

	public static function render( $product, $widget ) {
		$html = do_shortcode( '[gzd_product_' . $widget->get_postfix() . ']' );

		if ( empty( trim( $html ) ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			if ( is_callable( array( $widget, 'get_preview_content' ) ) ) {
				$html = $widget->get_preview_content();
			}
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
