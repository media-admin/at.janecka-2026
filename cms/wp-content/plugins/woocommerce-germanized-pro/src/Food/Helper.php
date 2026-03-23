<?php

namespace Vendidero\Germanized\Pro\Food;

defined( 'ABSPATH' ) || exit;

class Helper {

	public static function init() {
		add_filter( 'woocommerce_post_class', array( __CLASS__, 'register_product_wrapper_classes' ), 10, 2 );
		add_filter( 'woocommerce_gzd_get_nutrient_object', array( __CLASS__, 'get_nutrient_by_term' ), 10, 2 );

		add_filter( 'woocommerce_gzd_shortcode_product_food_html', array( __CLASS__, 'food_shortcode' ), 10, 3 );
		add_filter( 'woocommerce_gzd_get_product_nutrients', array( __CLASS__, 'get_product_nutrients' ), 10, 3 );
		add_filter( 'woocommerce_gzd_get_product_allergenic', array( __CLASS__, 'get_product_allergenic' ), 10, 3 );
		add_filter( 'woocommerce_gzd_get_product_nutrients_html', array( __CLASS__, 'get_nutrients_html' ), 10, 2 );

		add_action( 'woocommerce_gzd_template_single_nutri_score', array( __CLASS__, 'output_product_nutri_score' ) );
		add_action( 'woocommerce_gzd_template_loop_nutri_score', array( __CLASS__, 'output_product_loop_nutri_score' ) );

		add_filter( 'woocommerce_display_product_attributes', array( __CLASS__, 'additional_attributes' ), 10, 2 );
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'register_product_tabs' ), 20 );

		add_action( 'woocommerce_gzd_edit_product_food_panel', array( __CLASS__, 'edit_product_panel' ), 10 );
		add_action( 'woocommerce_gzd_edit_product_variation_food_wrapper', array( __CLASS__, 'edit_product_variation_panel' ), 10, 3 );

		/**
		 * Wrap calling WC_GZD_Food_Helper in an init hook to prevent loading textdomain too early
		 */
		add_action(
			'init',
			function () {
				foreach ( array_keys( self::get_food_attribute_types() ) as $attribute_type ) {
					add_action( 'woocommerce_gzdp_product_' . $attribute_type, array( __CLASS__, 'food_output' ), 10, 1 );
				}
			}
		);

		add_filter( 'woocommerce_gzd_product_nutrient_value', array( __CLASS__, 'product_nutrient_rounding' ), 10, 4 );

		/**
		 * Allow custom nutrient term order
		 */
		add_action( 'product_nutrient_add_form_fields', array( __CLASS__, 'add_nutrient_form_fields' ) );
		add_action( 'product_nutrient_edit_form_fields', array( __CLASS__, 'edit_nutrient_form_fields' ) );

		add_filter( 'get_terms_orderby', array( __CLASS__, 'get_terms_orderby' ), 10, 2 );
		add_action( 'create_term', array( __CLASS__, 'save_nutrient_fields' ), 10, 3 );
		add_action( 'edit_term', array( __CLASS__, 'save_nutrient_fields' ), 10, 3 );

		add_action( 'wp_ajax_reorder_nutrient_terms', array( __CLASS__, 'ajax_reordering_terms' ) );

		if ( is_blog_admin() || doing_action( 'wp_ajax_inline_save_tax' ) ) {
			if ( ! empty( $_REQUEST['taxonomy'] ) && 'product_nutrient' === $_REQUEST['taxonomy'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				add_action( 'load-edit-tags.php', array( __CLASS__, 'edit_tags' ) );
			}
		}

		add_action( 'load-edit-tags.php', array( __CLASS__, 'maybe_import_food_attributes' ) );

		/**
		 * Germanized < 3.9.2 is expecting numeric data for nutrient only, e.g. price formatted
		 * strings like 9,32 will be rejected. Filter data before passing to Germanized saving.
		 */
		add_filter( 'woocommerce_gzd_product_saveable_data', array( __CLASS__, 'filter_nutrient_legacy_data_before_save' ), 10, 2 );
	}

	public static function filter_nutrient_legacy_data_before_save( $data ) {
		if ( version_compare( WC_germanized()->version, '3.9.2', '<' ) && isset( $data['_nutrient_ids'] ) ) {
			$data['_nutrient_ids'] = (array) wc_clean( $data['_nutrient_ids'] );

			foreach ( $data['_nutrient_ids'] as $k => $value ) {
				if ( is_array( $value ) ) {
					$value = wp_parse_args(
						$value,
						array(
							'value'     => 0,
							'ref_value' => '',
						)
					);

					if ( '' === $value['value'] ) {
						unset( $data['_nutrient_ids'][ $k ] );
					} else {
						$value['value']     = wc_format_decimal( $value['value'] );
						$value['ref_value'] = is_numeric( $value['ref_value'] ) ? wc_format_decimal( $value['ref_value'] ) : '';

						$data['_nutrient_ids'][ $k ] = $value;
					}
				} elseif ( '' === $value ) {
					unset( $data['_nutrient_ids'][ $k ] );
				} else {
					$data['_nutrient_ids'][ $k ] = array(
						'value'     => wc_format_decimal( $value ),
						'ref_value' => '',
					);
				}
			}
		}

		return $data;
	}

	public static function import_food_attributes( $force = false ) {
		if ( ! taxonomy_exists( 'product_nutrient' ) ) {
			return false;
		}

		if ( ! $force ) {
			$version = get_option( 'woocommerce_gzdp_imported_food_attributes_version' );

			/**
			 * Do only re-import on food attribute version updates.
			 */
			if ( $version && version_compare( self::get_default_food_attributes_version(), $version, '<=' ) ) {
				return false;
			}
		}

		self::create_default_nutrients();
		self::create_default_allergenic();
		\Vendidero\Germanized\Pro\Food\Deposits\Helper::create_default_deposit_types();

		update_option( 'woocommerce_gzdp_imported_food_attributes_version', self::get_default_food_attributes_version(), false );

		return true;
	}

	public static function get_default_food_attributes_version() {
		return '3.5.0';
	}

	public static function maybe_import_food_attributes() {
		if ( ! empty( $_REQUEST['taxonomy'] ) && in_array( $_REQUEST['taxonomy'], array( 'product_nutrient', 'product_allergen', 'product_deposit_type' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::import_food_attributes();
		}
	}

	public static function get_nutrient_by_term( $nutrient, $term ) {
		return self::get_nutrient( $term );
	}

	public static function get_food_attribute_types() {
		return class_exists( 'WC_GZD_Food_Helper' ) ? \WC_GZD_Food_Helper::get_food_attribute_types() : array();
	}

	public static function food_shortcode( $html, $food_type, $atts ) {
		$atts = wp_parse_args(
			$atts,
			array(
				'product' => '',
			)
		);

		global $product;

		$org_product = false;

		$atts = wp_parse_args(
			$atts,
			array(
				'product' => '',
			)
		);

		if ( ! empty( $atts['product'] ) ) {
			$org_product = $product;
			$product     = wc_get_product( $atts['product'] );
		}

		if ( $product && is_a( $product, 'WC_Product' ) ) {
			ob_start();
			do_action( 'woocommerce_gzdp_product_' . $food_type );
			$html = ob_get_clean();
		}

		/**
		 * Reset global product data
		 */
		if ( $org_product ) {
			$product = $org_product;
		}

		return $html;
	}

	/**
	 * @param string|float $nutrient_value
	 * @param int $id
	 * @param \WC_GZD_Product $gzd_product
	 * @param string $context
	 *
	 * @return string|float
	 */
	public static function product_nutrient_rounding( $nutrient_value, $id, $gzd_product, $context = 'view' ) {
		if ( 'view' === $context ) {
			$nutrient = self::get_nutrient( $id );

			if ( '' === $nutrient_value && $nutrient && $nutrient->is_mandatory() ) {
				$nutrient_value = 0;
			} elseif ( '' === $nutrient_value ) {
				return '';
			}

			$nutrient_value = (float) $nutrient_value;

			if ( $nutrient && apply_filters( 'woocommerce_gzdp_nutrient_enable_rounding', true, $id, $nutrient_value ) ) {
				$nutrient_value = $nutrient->round( $nutrient_value );
			}

			$nutrient_value = is_numeric( $nutrient_value ) ? wc_format_localized_decimal( wc_format_decimal( $nutrient_value, false, apply_filters( 'woocommerce_gzdp_nutrient_round_trim_zeros', true ) ) ) : $nutrient_value;
		}

		return $nutrient_value;
	}

	/**
	 * @param array $classes
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	public static function register_product_wrapper_classes( $classes, $product ) {
		if ( wc_gzd_get_gzd_product( $product )->is_food() ) {
			$classes[] = 'is-food';
		}

		return $classes;
	}

	/**
	 * @param $attributes
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	public static function additional_attributes( $attributes, $product ) {
		$gzd_product = wc_gzd_get_gzd_product( $product );

		foreach ( self::get_food_attribute_types() as $attribute_type => $label ) {
			if ( in_array( $attribute_type, array( 'ingredients', 'nutrients', 'allergenic' ), true ) ) {
				continue;
			}

			if ( $gzd_product->is_food() ) {
				ob_start();
				do_action( "woocommerce_gzdp_product_{$attribute_type}" );
				$value = ob_get_clean();

				if ( $product->is_type( 'variable' ) || ! empty( $value ) ) {
					$attributes[ $attribute_type ] = array(
						'label' => $label,
						'value' => $value,
					);
				}
			}
		}

		return $attributes;
	}

	public static function register_product_tabs( $tabs ) {
		global $product;

		if ( $gzd_product = wc_gzd_get_gzd_product( $product ) ) {
			if ( $gzd_product->is_food() ) {
				$tabs['ingredients_nutrients'] = array(
					'title'    => __( 'Ingredients & Nutrients', 'woocommerce-germanized-pro' ),
					'priority' => 20,
					'callback' => array( __CLASS__, 'product_tab_output' ),
				);

				if ( ! isset( $tabs['additional_information'] ) ) {
					$tabs['additional_information'] = array(
						'title'    => __( 'Additional information', 'woocommerce-germanized-pro' ),
						'priority' => 20,
						'callback' => 'woocommerce_product_additional_information_tab',
					);
				}
			}
		}

		return $tabs;
	}

	public static function output_product_nutri_score() {
		do_action( 'woocommerce_gzdp_product_nutri_score' );
	}

	public static function output_product_loop_nutri_score() {
		global $product;

		if ( ! $gzd_product = wc_gzd_get_gzd_product( $product ) ) {
			return;
		}

		if ( ! $gzd_product->is_food() ) {
			return;
		}

		wc_get_template( 'loop/food/nutri-score.php' );
	}

	public static function food_output( $print_title = false ) {
		global $product;

		$current_action = str_replace( 'woocommerce_gzdp_product_', '', current_action() );
		$current_action = str_replace( 'food_', '', $current_action );
		$template       = sanitize_file_name( str_replace( '_', '-', $current_action ) . '.php' );

		if ( ! $gzd_product = wc_gzd_get_gzd_product( $product ) ) {
			return;
		}

		if ( ! $gzd_product->is_food() ) {
			return;
		}

		wc_get_template( 'single-product/food/' . $template, array( 'print_title' => $print_title ) );
	}

	public static function product_tab_output() {
		wc_get_template( 'single-product/tabs/ingredients.php' );
	}

	public static function get_nutrient_reference_values() {
		return class_exists( 'WC_GZD_Food_Helper' ) ? \WC_GZD_Food_Helper::get_nutrient_reference_values() : array();
	}

	public static function get_nutri_score_values() {
		return class_exists( 'WC_GZD_Food_Helper' ) ? \WC_GZD_Food_Helper::get_nutri_score_values() : array();
	}

	public static function get_nutrient_reference_value_title( $ref_value ) {
		$ref_values = self::get_nutrient_reference_values();
		$title      = __( 'per 100 g', 'woocommerce-germanized-pro' );

		if ( array_key_exists( $ref_value, $ref_values ) ) {
			$title = $ref_values[ $ref_value ];
		}

		return apply_filters( 'woocommerce_gzdp_nutrient_reference_value_title', sprintf( _x( 'Nutrients %1$s', 'nutrients-reference-title', 'woocommerce-germanized-pro' ), $title ), $ref_value );
	}

	/**
	 * @param Nutrient $nutrient
	 *
	 * @return string
	 */
	protected static function get_nutrient_regex( $nutrient ) {
		$regex = '(' . strtolower( $nutrient->get_name() ) . ')';

		if ( strstr( strtolower( $nutrient->get_name() ), strtolower( _x( 'Calorific value', 'nutrients', 'woocommerce-germanized-pro' ) ) ) ) {
			$regex = '(' . _x( 'Calorific value', 'nutrients', 'woocommerce-germanized-pro' ) . '|' . _x( 'Energy', 'nutrients', 'woocommerce-germanized-pro' ) . ')';
		} elseif ( strtolower( _x( 'saturated fatty acids', 'nutrients', 'woocommerce-germanized-pro' ) ) === strtolower( $nutrient->get_name() ) ) {
			$regex = '(' . _x( 'saturated fatty acids', 'nutrients', 'woocommerce-germanized-pro' ) . '|' . _x( 'sat. fatty acids', 'nutrients', 'woocommerce-germanized-pro' ) . ')';
		} elseif ( strtolower( _x( 'monounsaturated fatty acids', 'nutrients', 'woocommerce-germanized-pro' ) ) === strtolower( $nutrient->get_name() ) ) {
			$regex = '(' . _x( 'monounsaturated fatty acids', 'nutrients', 'woocommerce-germanized-pro' ) . '|' . _x( 'monounsat. fatty acids', 'nutrients', 'woocommerce-germanized-pro' ) . ')';
		} elseif ( strtolower( _x( 'polyunsaturated fatty acids', 'nutrients', 'woocommerce-germanized-pro' ) ) === strtolower( $nutrient->get_name() ) ) {
			$regex = '(' . _x( 'polyunsaturated fatty acids', 'nutrients', 'woocommerce-germanized-pro' ) . '|' . _x( 'polyunsat. fatty acids', 'nutrients', 'woocommerce-germanized-pro' ) . ')';
		}

		return strtolower( $regex );
	}

	public static function edit_product_variation_panel( $loop, $variation_data, $variation ) {
		$_product                 = wc_get_product( $variation );
		$_parent                  = wc_get_product( $_product->get_parent_id() );
		$_gzd_product             = wc_gzd_get_product( $_product );
		$_gzd_parent_product      = wc_gzd_get_product( $_parent );
		$hide_non_alcoholic_field = ! method_exists( $_gzd_product, 'get_is_non_alcoholic' ) ? true : false;
		?>
		<div class="variable_general_food_attributes">
			<p class="wc-gzd-product-settings-subtitle">
				<?php esc_html_e( 'Food labeling', 'woocommerce-germanized-pro' ); ?>
				<a class="page-title-action" href="https://vendidero.de/doc/woocommerce-germanized/lebensmittel-auszeichnen"><?php esc_html_e( 'Help', 'woocommerce-germanized-pro' ); ?></a>
			</p>
			<?php
			woocommerce_wp_text_input(
				array(
					'wrapper_class' => 'form-row form-row-first',
					'id'            => "variable_net_filling_quantity{$loop}",
					'name'          => "variable_net_filling_quantity[{$loop}]",
					'label'         => __( 'Net Filling Quantity', 'woocommerce-germanized-pro' ),
					'value'         => $_gzd_product->get_net_filling_quantity( 'edit' ),
					'placeholder'   => wc_format_localized_price( $_gzd_product->get_unit_product() ? $_gzd_product->get_unit_product() : ( $_gzd_parent_product ? ( $_gzd_parent_product->get_net_filling_quantity() ? $_gzd_parent_product->get_net_filling_quantity() : $_gzd_parent_product->get_unit_product() ) : '' ) ),
					'data_type'     => 'price',
					'style'         => 'max-width: 90%;',
					'description'   => $_gzd_parent_product ? '<span class="wc-gzd-unit-placeholder">' . $_gzd_parent_product->get_unit() . '</span>' : '',
				)
			);

			woocommerce_wp_text_input(
				array(
					'wrapper_class' => 'form-row form-row-last',
					'id'            => "variable_drained_weight{$loop}",
					'name'          => "variable_drained_weight[{$loop}]",
					'value'         => $_gzd_product->get_drained_weight( 'edit' ),
					'placeholder'   => $_gzd_parent_product ? wc_format_localized_price( $_gzd_parent_product->get_drained_weight() ) : '',
					'label'         => __( 'Drained Weight', 'woocommerce-germanized-pro' ),
					'data_type'     => 'price',
					'style'         => 'max-width: 90%;',
					'description'   => get_option( 'woocommerce_weight_unit' ),
				)
			);

			woocommerce_wp_select(
				array(
					'wrapper_class'     => 'form-row variable-nutri-score',
					'id'                => "variable_nutri_score{$loop}",
					'name'              => "variable_nutri_score[{$loop}]",
					'value'             => $_gzd_product->get_nutri_score( 'edit' ),
					'label'             => __( 'Nutri-Score', 'woocommerce-germanized-pro' ),
					'class'             => 'wc-gzd-enhanced-nutri-score wc-gzd-nutri-score-select',
					'options'           => array( '' => '' ) + self::get_nutri_score_values(),
					'style'             => 'width: 100%;',
					'custom_attributes' => array(
						'data-allow_clear' => 'true',
						'data-placeholder' => __( 'Same as Parent', 'woocommerce-germanized-pro' ),
					),
				)
			);

			woocommerce_wp_text_input(
				array(
					'wrapper_class' => 'form-row form-row-first',
					'id'            => "variable_alcohol_content{$loop}",
					'name'          => "variable_alcohol_content[{$loop}]",
					'label'         => __( 'Alcohol Content', 'woocommerce-germanized-pro' ),
					'value'         => $_gzd_product->get_alcohol_content( 'edit' ),
					'placeholder'   => $_gzd_parent_product ? wc_format_localized_price( $_gzd_parent_product->get_alcohol_content() ) : '',
					'data_type'     => 'price',
					'style'         => 'max-width: 90%;',
					'description'   => _x( '%', 'volume-percent', 'woocommerce-germanized-pro' ),
				)
			);

			if ( $_gzd_parent_product && method_exists( $_gzd_parent_product, 'is_non_alcoholic' ) && $_gzd_parent_product->is_non_alcoholic() ) {
				$hide_non_alcoholic_field = true;
			}

			if ( ! $hide_non_alcoholic_field ) {
				woocommerce_wp_checkbox(
					array(
						'wrapper_class' => 'form-row form-row-last variable-is-non-alcoholic',
						'id'            => "variable_is_non_alcoholic{$loop}",
						'name'          => "variable_is_non_alcoholic[{$loop}]",
						'label'         => __( 'Non-alcoholic?', 'woocommerce-germanized-pro' ),
						'value'         => method_exists( $_gzd_product, 'get_is_non_alcoholic' ) ? $_gzd_product->get_is_non_alcoholic( 'edit' ) : 'no',
						'description'   => _x( 'This product is non-alcoholic.', 'volume-percent', 'woocommerce-germanized-pro' ),
					)
				);
			}

			woocommerce_wp_select(
				array(
					'wrapper_class'     => 'wc-gzd-allergenic-field form-row form-row-full',
					'name'              => "variable_allergen_ids[{$loop}][]",
					'id'                => "variable_allergen_ids{$loop}",
					'value'             => $_gzd_product->get_allergen_ids( 'edit' ),
					'label'             => __( 'Allergenic', 'woocommerce-germanized-pro' ),
					'class'             => 'multiselect wc-enhanced-select',
					'options'           => self::get_allergenic(),
					'style'             => 'width: 100%',
					'custom_attributes' => array(
						'multiple'         => 'multiple',
						'class'            => 'enhanced',
						'data-placeholder' => __( 'Same as Parent', 'woocommerce-germanized-pro' ),
					),
				)
			);
			?>
		</div>
		<div class="variable_additional_food_attributes">
			<p class="form-row form-row-full">
				<label for="variable_ingredients<?php echo esc_attr( $loop ); ?>"><?php echo esc_html__( 'Ingredients', 'woocommerce-germanized-pro' ); ?> <?php echo wc_help_tip( __( 'Highlight allergens visually, e.g. print in bold or use capital letters', 'woocommerce-germanized-pro' ) ); ?></label>
				<textarea rows="2" style="width: 100%" name="variable_ingredients[<?php echo esc_attr( $loop ); ?>]" placeholder="<?php echo esc_attr( $_gzd_parent_product ? $_gzd_parent_product->get_ingredients() : '' ); ?>" id="variable_ingredients<?php echo esc_attr( $loop ); ?>"><?php echo wp_kses_post( htmlspecialchars_decode( $_gzd_product->get_ingredients( 'edit' ) ) ); ?></textarea>
			</p>

			<p class="form-row form-row-full">
				<label for="variable_food_description<?php echo esc_attr( $loop ); ?>"><?php echo esc_html_x( 'Description', 'food', 'woocommerce-germanized-pro' ); ?> <?php echo wc_help_tip( __( 'Name of the food', 'woocommerce-germanized-pro' ) ); ?></label>
				<textarea rows="2" style="width: 100%" name="variable_food_description[<?php echo esc_attr( $loop ); ?>]" placeholder="<?php echo esc_attr( $_gzd_parent_product ? $_gzd_parent_product->get_food_description() : '' ); ?>" id="variable_food_description<?php echo esc_attr( $loop ); ?>"><?php echo wp_kses_post( htmlspecialchars_decode( $_gzd_product->get_food_description( 'edit' ) ) ); ?></textarea>
			</p>

			<p class="form-row form-row-full">
				<label for="variable_food_distributor<?php echo esc_attr( $loop ); ?>"><?php echo esc_html_x( 'Distributor', 'food', 'woocommerce-germanized-pro' ); ?></label>
				<textarea rows="2" style="width: 100%" name="variable_food_distributor[<?php echo esc_attr( $loop ); ?>]" placeholder="<?php echo esc_attr( $_gzd_parent_product ? $_gzd_parent_product->get_food_distributor() : '' ); ?>" id="variable_food_distributor<?php echo esc_attr( $loop ); ?>"><?php echo wp_kses_post( htmlspecialchars_decode( $_gzd_product->get_food_distributor( 'edit' ) ) ); ?></textarea>
			</p>

			<p class="form-row form-row-full">
				<label for="variable_food_place_of_origin<?php echo esc_attr( $loop ); ?>"><?php echo esc_html_x( 'Place of Origin', 'food', 'woocommerce-germanized-pro' ); ?></label>
				<textarea rows="2" style="width: 100%" name="variable_food_place_of_origin[<?php echo esc_attr( $loop ); ?>]" placeholder="<?php echo esc_attr( $_gzd_parent_product ? $_gzd_parent_product->get_food_place_of_origin() : '' ); ?>" id="variable_food_place_of_origin<?php echo esc_attr( $loop ); ?>"><?php echo wp_kses_post( htmlspecialchars_decode( $_gzd_product->get_food_place_of_origin( 'edit' ) ) ); ?></textarea>
			</p>
		</div>
		<div class="variable_nutrients">
			<p class="wc-gzd-product-settings-subtitle">
				<?php esc_html_e( 'Nutrition Declaration', 'woocommerce-germanized-pro' ); ?>
				<a class="page-title-action" href="https://vendidero.de/doc/woocommerce-germanized/lebensmittel-auszeichnen#naehrwertdeklaration"><?php esc_html_e( 'Help', 'woocommerce-germanized-pro' ); ?></a>
				<a class="wc-gzd-product-settings-action" target="_blank" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_nutrient&post_type=product' ) ); ?>"><?php esc_html_e( 'Manage nutrients', 'woocommerce-germanized-pro' ); ?></a>
				<a class="wc-gzd-product-settings-action wc-gzdp-clear-nutrients" href="#"><?php esc_html_e( 'Clear', 'woocommerce-germanized-pro' ); ?></a>
			</p>
			<p class="wc-gzd-product-settings-desc">
				<?php esc_html_e( 'You can simply paste unstructured content (e.g. an existing nutrition table) into a field. The system will try to make an assignment automatically.', 'woocommerce-germanized-pro' ); ?>
			</p>
			<?php

			woocommerce_wp_select(
				array(
					'wrapper_class' => 'form-row form-row-full nutrient-reference-value-wrapper',
					'id'            => "variable_nutrient_reference_value{$loop}",
					'name'          => "variable_nutrient_reference_value[{$loop}]",
					'value'         => $_gzd_product->get_nutrient_reference_value( 'edit' ),
					'label'         => __( 'Reference Value', 'woocommerce-germanized-pro' ),
					'options'       => array( '' => __( 'Same as Parent', 'woocommerce-germanized-pro' ) ) + self::get_nutrient_reference_values(),
				)
			);

			$nutrient_count = 0;

			foreach ( self::get_nutrients() as $nutrient ) :
				++$nutrient_count;
				$class      = 0 === $nutrient_count % 2 && ! $nutrient->is_parent() ? 'form-row-last' : 'form-row-first';
				$is_vitamin = $nutrient->is_vitamin();

				if ( 'title' === $nutrient->get_type() ) {
					continue;
				}
				?>
				<?php

				if ( $is_vitamin ) {
					woocommerce_wp_text_input(
						array(
							'wrapper_class'     => 'form-row-nutrient form-row form-row-first',
							'name'              => "variable_nutrient_ids[{$loop}][{$nutrient->get_id()}][value]",
							'id'                => "_nutrient_ids{$loop}{$nutrient->get_id()}_value",
							'value'             => $_gzd_product->get_nutrient_value( $nutrient->get_id(), 'edit' ),
							'label'             => $nutrient->get_name() . ( $nutrient->is_mandatory() ? '<span class="mandatory">*</span>' : '' ),
							'data_type'         => 'price',
							'style'             => 'max-width: 90%;',
							'placeholder'       => $_gzd_parent_product ? wc_format_localized_price( $_gzd_parent_product->get_nutrient_value( $nutrient->get_id(), 'edit' ) ) : '',
							'description'       => $nutrient->get_unit(),
							'custom_attributes' => array(
								'data-unit'        => $nutrient->get_unit(),
								'data-regex'       => self::get_nutrient_regex( $nutrient ),
								'data-nutrient-id' => $nutrient->get_id(),
							),
						)
					);

					woocommerce_wp_text_input(
						array(
							'wrapper_class' => 'form-row-nutrient form-row form-row-last',
							'name'          => "variable_nutrient_ids[{$loop}][{$nutrient->get_id()}][ref_value]",
							'id'            => "_nutrient_ids{$loop}{$nutrient->get_id()}_ref_value",
							'value'         => $_gzd_product->get_nutrient_reference( $nutrient->get_id(), 'edit' ),
							'label'         => sprintf( __( '%1$s (Reference value)', 'woocommerce-germanized-pro' ), $nutrient->get_name() ),
							'data_type'     => 'price',
							'style'         => 'max-width: 90%;',
							'placeholder'   => $_gzd_parent_product ? wc_format_localized_price( $_gzd_parent_product->get_nutrient_reference( $nutrient->get_id(), 'edit' ) ) : '',
							'description'   => self::get_nutrient_vitamin_reference_unit(),
						)
					);
				} else {
					woocommerce_wp_text_input(
						array(
							'wrapper_class'     => 'form-row-nutrient form-row ' . $class . ' ' . ( $nutrient->is_parent() ? 'nutrient-is-parent' : '' ) . ' ' . ( $nutrient->has_parent() ? 'nutrient-is-child' : '' ),
							'name'              => "variable_nutrient_ids[{$loop}][{$nutrient->get_id()}]",
							'id'                => "_nutrient_ids{$loop}{$nutrient->get_id()}",
							'value'             => $_gzd_product->get_nutrient_value( $nutrient->get_id(), 'edit' ),
							'label'             => ( $nutrient->has_parent() ? '— ' : '' ) . $nutrient->get_name() . ( $nutrient->is_mandatory() ? '<span class="mandatory">*</span>' : '' ),
							'data_type'         => 'price',
							'style'             => 'max-width: 90%;',
							'placeholder'       => $_gzd_parent_product ? wc_format_localized_price( $_gzd_parent_product->get_nutrient_value( $nutrient->get_id(), 'edit' ) ) : '',
							'description'       => $nutrient->get_unit(),
							'custom_attributes' => array(
								'data-unit'        => $nutrient->get_unit(),
								'data-regex'       => self::get_nutrient_regex( $nutrient ),
								'data-nutrient-id' => $nutrient->get_id(),
							),
						)
					);
				}
				?>
				<?php
			endforeach;
			?>
		</div>
		<?php
	}

	public static function edit_product_panel() {
		global $post, $thepostid, $product_object;

		$_gzd_product = wc_gzd_get_product( $product_object );
		?>
			<div class="options_group general_food_attributes show_if_simple show_if_external show_if_variable">
				<p class="wc-gzd-product-settings-subtitle">
					<?php esc_html_e( 'Food labeling', 'woocommerce-germanized-pro' ); ?>
					<a class="page-title-action" href="https://vendidero.de/doc/woocommerce-germanized/lebensmittel-auszeichnen"><?php esc_html_e( 'Help', 'woocommerce-germanized-pro' ); ?></a>
				</p>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => '_net_filling_quantity',
						'label'       => __( 'Net Filling Quantity', 'woocommerce-germanized-pro' ),
						'data_type'   => 'price',
						'description' => '<span class="wc-gzd-unit-placeholder">' . $_gzd_product->get_unit() . '</span>',
						'placeholder' => $_gzd_product->get_unit_product(),
					)
				);
				?>

				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => '_drained_weight',
						'label'       => __( 'Drained Weight', 'woocommerce-germanized-pro' ),
						'data_type'   => 'price',
						'description' => get_option( 'woocommerce_weight_unit' ),
					)
				);
				?>

				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => '_alcohol_content',
						'label'       => __( 'Alcohol Content', 'woocommerce-germanized-pro' ),
						'data_type'   => 'price',
						'description' => _x( '%', 'volume-percent', 'woocommerce-germanized-pro' ),
					)
				);
				?>

				<?php
				if ( method_exists( $_gzd_product, 'get_is_non_alcoholic' ) ) {
					woocommerce_wp_checkbox(
						array(
							'id'          => '_is_non_alcoholic',
							'label'       => __( 'Non-alcoholic?', 'woocommerce-germanized-pro' ),
							'description' => __( 'In case the product does contain alcohol, e.g. 0,5 %, the value will be formatted as less-than, e.g. < 0,5 %.', 'woocommerce-germanized-pro' ),
							'desc_tip'    => true,
						)
					);
				}
				?>

				<?php
				woocommerce_wp_select(
					array(
						'id'      => '_nutri_score',
						'label'   => __( 'Nutri-Score', 'woocommerce-germanized-pro' ),
						'class'   => 'wc-gzd-enhanced-nutri-score wc-gzd-nutri-score-select',
						'options' => array( '' => _x( 'None', 'nutri-score', 'woocommerce-germanized-pro' ) ) + self::get_nutri_score_values(),
						'style'   => 'width: 50%;',
					)
				);
				?>

				<?php
				woocommerce_wp_select(
					array(
						'name'              => '_allergen_ids[]',
						'id'                => '_allergen_ids',
						'value'             => $_gzd_product->get_allergen_ids(),
						'label'             => __( 'Allergenic', 'woocommerce-germanized-pro' ),
						'class'             => 'multiselect wc-enhanced-select',
						'wrapper_class'     => 'wc-gzd-allergenic-field',
						'options'           => self::get_allergenic(),
						'description'       => '<a target="_blank" href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=product_allergen&post_type=product' ) ) . '">' . esc_html__( 'Manage allergenic', 'woocommerce-germanized-pro' ) . '</a>',
						'style'             => 'width: 50%',
						'custom_attributes' => array(
							'multiple'         => 'multiple',
							'class'            => 'enhanced',
							'data-placeholder' => _x( 'None', 'allergenic', 'woocommerce-germanized-pro' ),
						),
					)
				);
				?>
			</div>

			<div class="options_group additional_food_attributes show_if_simple show_if_external show_if_variable">
				<div class="form-field form-field-wp-editor _ingredients-field">
					<label for="_ingredients"><?php esc_html_e( 'Ingredients', 'woocommerce-germanized-pro' ); ?> <?php echo wc_help_tip( __( 'Highlight allergens visually, e.g. print in bold or use capital letters', 'woocommerce-germanized-pro' ) ); ?></label>
					<div class="wc-gzdp-product-editor-wrap">
						<?php
						wp_editor(
							htmlspecialchars_decode( $_gzd_product->get_ingredients() ),
							'_ingredients',
							array(
								'textarea_name' => '_ingredients',
								'textarea_rows' => 2,
								'media_buttons' => false,
								'teeny'         => apply_filters( 'woocommerce_gzdp_wp_editor_use_teeny', true ),
							)
						);
						?>
					</div>
				</div>

				<div class="form-field form-field-wp-editor _food_description-field">
					<label for="_food_description"><?php echo esc_html_x( 'Description', 'food', 'woocommerce-germanized-pro' ); ?> <?php echo wc_help_tip( __( 'Name of the food', 'woocommerce-germanized-pro' ) ); ?></label>
					<div class="wc-gzdp-product-editor-wrap">
						<?php
						wp_editor(
							htmlspecialchars_decode( $_gzd_product->get_food_description() ),
							'_food_description',
							array(
								'textarea_name' => '_food_description',
								'textarea_rows' => 2,
								'media_buttons' => false,
								'teeny'         => apply_filters( 'woocommerce_gzdp_wp_editor_use_teeny', true ),
							)
						);
						?>
					</div>
				</div>

				<div class="form-field form-field-wp-editor _food_distributor-field">
					<label for="_food_distributor"><?php echo esc_html_x( 'Distributor', 'food', 'woocommerce-germanized-pro' ); ?></label>
					<div class="wc-gzdp-product-editor-wrap">
						<?php
						wp_editor(
							htmlspecialchars_decode( $_gzd_product->get_food_distributor() ),
							'_food_distributor',
							array(
								'textarea_name' => '_food_distributor',
								'textarea_rows' => 2,
								'media_buttons' => false,
								'teeny'         => apply_filters( 'woocommerce_gzdp_wp_editor_use_teeny', true ),
							)
						);
						?>
					</div>
				</div>

				<div class="form-field form-field-wp-editor _food_place_of_origin-field">
					<label for="_food_place_of_origin"><?php echo esc_html_x( 'Place of Origin', 'food', 'woocommerce-germanized-pro' ); ?></label>
					<div class="wc-gzdp-product-editor-wrap">
						<?php
						wp_editor(
							htmlspecialchars_decode( $_gzd_product->get_food_place_of_origin() ),
							'_food_place_of_origin',
							array(
								'textarea_name' => '_food_place_of_origin',
								'textarea_rows' => 2,
								'media_buttons' => false,
								'teeny'         => apply_filters( 'woocommerce_gzdp_wp_editor_use_teeny', true ),
							)
						);
						?>
					</div>
				</div>
			</div>

			<div class="options_group nutrients show_if_simple show_if_external show_if_variable">
				<p class="wc-gzd-product-settings-subtitle">
					<?php esc_html_e( 'Nutrition Declaration', 'woocommerce-germanized-pro' ); ?>
					<a class="page-title-action" href="https://vendidero.de/doc/woocommerce-germanized/lebensmittel-auszeichnen#naehrwertdeklaration"><?php esc_html_e( 'Help', 'woocommerce-germanized-pro' ); ?></a>
					<a class="wc-gzd-product-settings-action wc-gzdp-clear-nutrients" href="#"><?php esc_html_e( 'Clear', 'woocommerce-germanized-pro' ); ?></a>
					<a class="wc-gzd-product-settings-action" target="_blank" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_nutrient&post_type=product' ) ); ?>"><?php esc_html_e( 'Manage nutrients', 'woocommerce-germanized-pro' ); ?></a>
				</p>
				<p class="wc-gzd-product-settings-desc">
					<?php esc_html_e( 'You can simply paste unstructured content (e.g. an existing nutrition table) into a field. The system will try to make an assignment automatically.', 'woocommerce-germanized-pro' ); ?>
				</p>
				<?php
				woocommerce_wp_select(
					array(
						'id'            => '_nutrient_reference_value',
						'value'         => $_gzd_product->get_nutrient_reference_value(),
						'label'         => __( 'Reference Value', 'woocommerce-germanized-pro' ),
						'options'       => self::get_nutrient_reference_values(),
						'wrapper_class' => 'nutrient-reference-value-wrapper',
					)
				);
				?>

				<?php
				foreach ( self::get_nutrients() as $nutrient ) :
					if ( 'title' === $nutrient->get_type() ) {
						continue;
					}
					$is_vitamin = $nutrient->is_vitamin();
					?>
					<div class="nutrient-wrapper <?php echo $nutrient->is_parent() ? 'nutrient-is-parent' : ''; ?> <?php echo $nutrient->has_parent() ? 'nutrient-is-child' : ''; ?>">
						<?php
						if ( $is_vitamin ) {
							?>
								<p class="form-field nutrient_vitamins_field form-row-nutrient">
									<label for="nutrient_<?php echo esc_attr( $nutrient->get_id() ); ?>"><?php echo esc_html( $nutrient->get_name() ); ?> <?php echo ( $nutrient->is_mandatory() ? '<span class="mandatory">*</span>' : '' ); ?></label>
									<span class="wrap">
										<input id="nutrient_<?php echo esc_attr( $nutrient->get_id() ); ?>_value" data-unit="<?php echo esc_attr( strtolower( $nutrient->get_unit() ) ); ?>" data-nutrient-id="<?php echo esc_attr( $nutrient->get_id() ); ?>" data-regex="<?php echo esc_attr( self::get_nutrient_regex( $nutrient ) ); ?>" class="input-text wc_input_price" size="6" type="text" name="_nutrient_ids[<?php echo esc_attr( $nutrient->get_id() ); ?>][value]" value="<?php echo esc_attr( wc_format_localized_decimal( $_gzd_product->get_nutrient_value( $nutrient->get_id(), 'edit' ) ) ); ?>" />
										<span class="unit"><?php echo esc_html( $nutrient->get_unit() ); ?></span>
										<input id="nutrient_<?php echo esc_attr( $nutrient->get_id() ); ?>_ref_value" class="input-text wc_input_price" size="6" type="text" name="_nutrient_ids[<?php echo esc_attr( $nutrient->get_id() ); ?>][ref_value]" value="<?php echo esc_attr( wc_format_localized_decimal( $_gzd_product->get_nutrient_reference( $nutrient->get_id(), 'edit' ) ) ); ?>" />
										<span class="unit"><?php echo esc_html( self::get_nutrient_vitamin_reference_unit() ); ?></span>
									</span>
								<?php echo wc_help_tip( sprintf( __( '%1$s in %2$s, Reference value in %3$s', 'woocommerce-germanized-pro' ), $nutrient->get_name(), $nutrient->get_unit(), self::get_nutrient_vitamin_reference_unit() ) ); ?>
								</p>
								<?php
						} else {
							woocommerce_wp_text_input(
								array(
									'name'              => "_nutrient_ids[{$nutrient->get_id()}]",
									'id'                => "_nutrient_{$nutrient->get_id()}",
									'value'             => $_gzd_product->get_nutrient_value( $nutrient->get_id(), 'edit' ),
									'label'             => ( $nutrient->has_parent() ? '— ' : '' ) . $nutrient->get_name() . ( $nutrient->is_mandatory() ? '<span class="mandatory">*</span>' : '' ),
									'data_type'         => 'price',
									'placeholder'       => '',
									'description'       => $nutrient->get_unit(),
									'wrapper_class'     => 'form-row-nutrient',
									'custom_attributes' => array(
										'data-unit'        => $nutrient->get_unit(),
										'data-regex'       => self::get_nutrient_regex( $nutrient ),
										'data-nutrient-id' => $nutrient->get_id(),
									),
								)
							);
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php
	}

	public static function get_allergenic() {
		$allergenic = get_terms(
			array(
				'taxonomy'   => 'product_allergen',
				'hide_empty' => false,
			)
		);

		$list = array();

		if ( ! empty( $allergenic ) && ! is_wp_error( $allergenic ) ) {
			foreach ( $allergenic as $term ) {
				$list[ $term->term_id ] = $term->name;
			}
		}

		return $list;
	}

	/**
	 * @return Nutrient[]
	 */
	public static function get_nutrients( $flat = true ) {
		$cache_key = 'wc_gzdp_nutrient_list_flat_' . wc_bool_to_string( $flat );
		$nutrients = wp_cache_get( $cache_key );

		if ( false === $nutrients ) {
			$nutrients        = array();
			$nutrient_parents = get_terms(
				array(
					'orderby'    => 'order',
					'taxonomy'   => 'product_nutrient',
					'depth'      => 1,
					'parent'     => 0,
					'order'      => 'ASC',
					'hide_empty' => false,
				)
			);

			foreach ( $nutrient_parents as $key => $parent ) {
				if ( ! $nutrient = self::get_nutrient( $parent ) ) {
					continue;
				}

				$nutrients[] = $nutrient;
				$children    = get_terms(
					array(
						'orderby'    => 'order',
						'taxonomy'   => 'product_nutrient',
						'depth'      => 1,
						'parent'     => $parent->term_id,
						'order'      => 'ASC',
						'hide_empty' => false,
					)
				);

				if ( $flat ) {
					foreach ( $children as $child ) {
						if ( ! $child_nutrient = self::get_nutrient( $child ) ) {
							continue;
						}
						$nutrient->add_child( $child_nutrient );

						$nutrients[] = $child_nutrient;
					}
				} else {
					$nutrient->set_children( $children );
				}
			}

			wp_cache_set( $cache_key, $nutrients );
		}

		return (array) $nutrients;
	}

	public static function edit_tags() {
		add_action( 'admin_print_scripts-edit-tags.php', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'admin_head-edit-tags.php', array( __CLASS__, 'admin_head' ) );
	}

	/**
	 * Handle ajax term reordering
	 *
	 * This bit is inspired by the Simple Page Ordering plugin from 10up
	 *
	 * @since 0.1.0
	 */
	public static function ajax_reordering_terms() {
		check_ajax_referer( 'wc-gzdp-reorder-nutrient', 'security' );

		if ( ! current_user_can( 'manage_product_terms' ) || empty( $_POST['id'] ) || ( ! isset( $_POST['previd'] ) && ! isset( $_POST['nextid'] ) ) ) {
			wp_die( -1 );
		}

		$taxonomy = 'product_nutrient';
		$term_id  = absint( wc_clean( wp_unslash( $_POST['id'] ) ) );
		$term     = get_term( $term_id, 'product_nutrient' );

		if ( empty( $term ) ) {
			wp_die( -1 );
		}

		// Sanitize positions
		$previd   = empty( $_POST['previd'] ) ? false : (int) wc_clean( wp_unslash( $_POST['previd'] ) );
		$nextid   = empty( $_POST['nextid'] ) ? false : (int) wc_clean( wp_unslash( $_POST['nextid'] ) );
		$start    = empty( $_POST['start'] ) ? 1 : (int) wc_clean( wp_unslash( $_POST['start'] ) );
		$excluded = empty( $_POST['excluded'] ) ? array( $term->term_id ) : array_filter( (array) wc_clean( wp_unslash( $_POST['excluded'] ) ), 'intval' );

		// Define return values
		$new_pos     = array();
		$return_data = array();

		// attempt to get the intended parent...
		$parent_id        = $term->parent;
		$next_term_parent = $nextid ? wp_get_term_taxonomy_parent_id( $nextid, $taxonomy ) : false;

		// If the preceding term is the parent of the next term, move it inside
		if ( $previd === $next_term_parent ) {
			$parent_id = $next_term_parent;

			// If the next term's parent isn't the same as our parent, we need more info
		} elseif ( $next_term_parent !== $parent_id ) {
			$prev_term_parent = $previd ? wp_get_term_taxonomy_parent_id( $nextid, $taxonomy ) : false;

			// If the previous term is not our parent now, set it
			if ( $prev_term_parent !== $parent_id ) {
				$parent_id = ( false !== $prev_term_parent ) ? $prev_term_parent : $next_term_parent;
			}
		}

		// If the next term's parent isn't our parent, set to false
		if ( $next_term_parent !== $parent_id ) {
			$nextid = false;
		}

		// Get term siblings for relative ordering
		$siblings = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'depth'      => 1,
				'number'     => 100,
				'parent'     => $parent_id,
				'orderby'    => 'order',
				'order'      => 'ASC',
				'hide_empty' => false,
				'exclude'    => $excluded,
			)
		);

		// Loop through siblings and update terms
		foreach ( $siblings as $sibling ) {

			// Skip the actual term if it's in the array
			if ( $sibling->term_id === (int) $term->term_id ) {
				continue;
			}

			// If this is the term that comes after our repositioned term, set
			// our repositioned term position and increment order
			if ( $nextid === (int) $sibling->term_id ) {
				self::set_nutrient_order( $term->term_id, $start );

				$ancestors = get_ancestors( $term->term_id, $taxonomy, 'taxonomy' );

				$new_pos[ $term->term_id ] = array(
					'order'  => $start,
					'parent' => $parent_id,
					'depth'  => count( $ancestors ),
				);

				++$start;
			}

			// If repositioned term has been set and new items are already in
			// the right order, we can stop looping
			if ( isset( $new_pos[ $term->term_id ] ) && (int) $sibling->order >= $start ) {
				$return_data['next'] = false;
				break;
			}

			// Set order of current sibling and increment the order
			if ( $start !== (int) $sibling->order ) {
				self::set_nutrient_order( $sibling->term_id, $start );
			}

			$new_pos[ $sibling->term_id ] = $start;
			++$start;

			if ( empty( $nextid ) && ( $previd === (int) $sibling->term_id ) ) {
				self::set_nutrient_order( $term->term_id, $start );

				$ancestors = get_ancestors( $term->term_id, $taxonomy, 'taxonomy' );

				$new_pos[ $term->term_id ] = array(
					'order'  => $start,
					'parent' => $parent_id,
					'depth'  => count( $ancestors ),
				);

				++$start;
			}
		}

		// max per request
		if ( ! isset( $return_data['next'] ) && count( $siblings ) > 1 ) {
			$return_data['next'] = array(
				'id'       => $term->term_id,
				'previd'   => $previd,
				'nextid'   => $nextid,
				'start'    => $start,
				'excluded' => array_merge( array_keys( $new_pos ), $excluded ),
			);
		} else {
			$return_data['next'] = false;
		}

		if ( empty( $return_data['next'] ) ) {
			// If the moved term has children, refresh the page for UI reasons
			$children = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'number'     => 1,
					'depth'      => 1,
					'orderby'    => 'order',
					'order'      => 'ASC',
					'parent'     => $term->term_id,
					'fields'     => 'ids',
					'hide_empty' => false,
				)
			);

			if ( ! empty( $children ) ) {
				wp_send_json( 'children' );
			}
		}

		$return_data['new_pos'] = $new_pos;

		wp_send_json( $return_data );
	}

	public static function admin_head() {
		?>
		<style>
			.wp-list-table .ui-sortable tr:not(.no-items) {
				cursor: move;
			}

			.striped.dragging > tbody > .ui-sortable-helper ~ tr:nth-child(even) {
				background: #f9f9f9;
			}

			.striped.dragging > tbody > .ui-sortable-helper ~ tr:nth-child(odd) {
				background: #fff;
			}

			.wp-list-table .to-updating tr,
			.wp-list-table .ui-sortable tr.inline-editor {
				cursor: default;
			}

			.wp-list-table .ui-sortable-placeholder {
				outline: 1px dashed #bbb;
				background: #f1f1f1 !important;
				visibility: visible !important;
			}
			.wp-list-table .ui-sortable-helper {
				background-color: #fff !important;
				outline: 1px solid #bbb;
				box-shadow: 0 3px 6px rgba(0, 0, 0, 0.175);
			}
			.wp-list-table .ui-sortable-helper .row-actions {
				visibility: hidden;
			}
			.to-row-updating .check-column {
				background: url('<?php echo esc_url( admin_url( '/images/spinner.gif' ) ); ?>') 10px 9px no-repeat;
			}
			@media print,
			(-o-min-device-pixel-ratio: 5/4),
			(-webkit-min-device-pixel-ratio: 1.25),
			(min-resolution: 120dpi) {
				.to-row-updating .check-column {
					background-image: url('<?php echo esc_url( admin_url( '/images/spinner-2x.gif' ) ); ?>');
					background-size: 20px 20px;
				}
			}
			.to-row-updating .check-column input {
				visibility: hidden;
			}
		</style>
		<?php
	}

	public static function enqueue_scripts() {
		wp_enqueue_script( 'wc-gzdp-nutrient-order', WC_germanized_pro()->get_assets_build_url( 'static/admin-nutrient-order.js' ), array( 'jquery-ui-sortable' ), WC_GERMANIZED_PRO_VERSION, true );

		wp_localize_script(
			'wc-gzdp-nutrient-order',
			'wc_gzdp_nutrient_order',
			array(
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'reorder_nutrient_nonce' => wp_create_nonce( 'wc-gzdp-reorder-nutrient' ),
			)
		);
	}

	/**
	 * Force `orderby` to `tt.order` if not explicitly set to something else
	 *
	 * @param  string $orderby
	 * @return string
	 */
	public static function get_terms_orderby( $orderby = 'name', $args = array() ) {
		$taxonomy = isset( $args['taxonomy'] ) ? (array) $args['taxonomy'] : array();

		if ( ! in_array( 'product_nutrient', $taxonomy, true ) ) {
			return $orderby;
		}

		// Do not override if being manually controlled
		if ( ! empty( $_GET['orderby'] ) && ! empty( $_GET['taxonomy'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $orderby;
		}

		$order_field = 'tt.order';

		/*
		 * There seems to be some issue while inserting the order column during installation.
		 */
		if ( 'yes' === get_option( 'woocommerce_gzdp_term_taxonomy_misses_order_column' ) ) {
			global $wpdb;

			WC_germanized_pro()->log( sprintf( 'Your %s table misses the order column. Please alter your table schema and include the column order int(11).', $wpdb->term_taxonomy ) );

			$order_field = 't.slug';
		}

		// Maybe force `orderby`
		if ( empty( $args['orderby'] ) || empty( $orderby ) || ( 'order' === $args['orderby'] ) || in_array( $orderby, array( 'name', 't.name' ), true ) ) {
			$orderby = $order_field;
		} elseif ( 't.name' === $orderby ) {
			$orderby = "{$order_field}, t.name";
		}

		// Return possibly modified `orderby` value
		return $orderby;
	}

	/**
	 * Add `order` to term when updating
	 *
	 * @since 0.1.0
	 *
	 * @param  int     $term_id   The ID of the term
	 * @param  int     $tt_id     Not used
	 * @param  string  $taxonomy  Taxonomy of the term
	 */
	public static function save_nutrient_fields( $term_id = 0, $tt_id = 0, $taxonomy = '' ) {
		if ( 'product_nutrient' !== $taxonomy || ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}

		if ( isset( $_POST['nutrient_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$order = ! empty( $_POST['nutrient_order'] ) ? (int) wc_clean( wp_unslash( $_POST['nutrient_order'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			self::set_nutrient_order( $term_id, $order );
		}

		if ( isset( $_POST['nutrient_unit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$unit = ! empty( $_POST['nutrient_unit'] ) ? absint( wc_clean( wp_unslash( $_POST['nutrient_unit'] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( empty( $unit ) ) {
				delete_term_meta( $term_id, '_unit' );
			} else {
				update_term_meta( $term_id, '_unit', $unit );
			}
		}

		update_term_meta( $term_id, '_is_mandatory', isset( $_POST['nutrient_is_mandatory'] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( isset( $_POST['nutrient_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$type = ! empty( $_POST['nutrient_type'] ) ? sanitize_key( wc_clean( wp_unslash( $_POST['nutrient_type'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( empty( $type ) || ! array_key_exists( $type, self::get_nutrient_types() ) ) {
				delete_term_meta( $term_id, '_nutrient_type' );
			} else {
				update_term_meta( $term_id, '_nutrient_type', $type );
			}
		}

		if ( isset( $_POST['nutrient_rounding_rule'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$rule = ! empty( $_POST['nutrient_rounding_rule'] ) ? sanitize_key( wc_clean( wp_unslash( $_POST['nutrient_rounding_rule'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( empty( $rule ) || ! array_key_exists( $rule, self::get_nutrient_rounding_rules() ) ) {
				delete_term_meta( $term_id, '_rounding_rule' );
			} else {
				update_term_meta( $term_id, '_rounding_rule', $rule );
			}
		}
	}

	/**
	 * Set order of a specific term
	 *
	 * @param  int $term_id
	 * @param  int $$order
	 */
	public static function set_nutrient_order( $term_id = 0, $order = 0 ) {
		global $wpdb;

		/*
		 * Update the database row
		 *
		 * We cannot call wp_update_term() here because it would cause recursion,
		 * and also the database columns are hardcoded and we can't modify them.
		 */
		$success = $wpdb->update(
			$wpdb->term_taxonomy,
			array(
				'order' => (int) $order,
			),
			array(
				'term_id'  => (int) $term_id,
				'taxonomy' => 'product_nutrient',
			)
		);

		// Only execute action and clean cache when update succeeds
		if ( ! empty( $success ) ) {
			do_action( 'woocommerce_gzdp_set_nutrient_order', $term_id, $order );

			clean_term_cache( $term_id, 'product_nutrient' );
		}
	}

	public static function add_nutrient_form_fields() {
		?>
		<div class="form-field">
			<label for="nutrient_unit">
				<?php echo esc_html_x( 'Unit', 'nutrients', 'woocommerce-germanized-pro' ); ?>
			</label>
			<select name="nutrient_unit" id="nutrient_unit">
				<?php
				foreach ( WC_germanized()->units->get_units( array( 'as' => 'term_id=>name' ) ) as $term_id => $unit ) :
					$selected = self::get_default_nutrient_unit() === strtolower( $unit );
					?>
					<option value="<?php echo esc_attr( $term_id ); ?>" <?php selected( $selected, true ); ?>><?php echo esc_html( $unit ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php echo esc_html_x( 'Choose a unit for this nutrient.', 'nutrients', 'woocommerce-germanized-pro' ); ?>
			</p>
		</div>

		<div class="form-field">
			<label for="nutrient_type">
				<?php echo esc_html_x( 'Type', 'nutrients', 'woocommerce-germanized-pro' ); ?>
			</label>
			<select name="nutrient_type" id="nutrient_type">
				<?php foreach ( self::get_nutrient_types() as $type => $title ) : ?>
					<option value="<?php echo esc_attr( $type ); ?>" <?php selected( 'numeric', $type ); ?>><?php echo esc_html( $title ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="form-field">
			<label for="nutrient_is_mandatory">
				<?php echo esc_html_x( 'Is mandatory?', 'nutrients', 'woocommerce-germanized-pro' ); ?>
			</label>

			<label for="nutrient_is_mandatory">
				<input id="nutrient_is_mandatory" type="checkbox" name="nutrient_is_mandatory" value="1" />

				<?php echo wp_kses_post( sprintf( _x( 'Nutrient is a <a href="%s">mandatory nutritional information</a>.', 'nutrients', 'woocommerce-germanized-pro' ), 'https://www.bzfe.de/lebensmittel/einkauf-und-kennzeichnung/kennzeichnung/naehrwertkennzeichnung/' ) ); ?>
			</label>
		</div>

		<div class="form-field">
			<label for="nutrient_rounding_rule">
				<?php echo esc_html_x( 'Rounding Rule', 'nutrients', 'woocommerce-germanized-pro' ); ?>
			</label>
			<select name="nutrient_rounding_rule" id="nutrient_rounding_rule">
				<?php foreach ( array( '-1' => array( 'title' => _x( 'None', 'rounding-rule', 'woocommerce-germanized-pro' ) ) ) + self::get_nutrient_rounding_rules() as $slug => $rule ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $rule['title'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php echo wp_kses_post( sprintf( _x( 'Apply a rounding rule based on the <a href="%1$s">EU food guide</a>', 'nutrients', 'woocommerce-germanized-pro' ), 'https://food.ec.europa.eu/system/files/2021-11/labelling_nutrition-vitamins_minerals-guidance_tolerances_1212_de.pdf' ) ); ?>
			</p>
		</div>

		<div class="form-field form-required">
			<label for="nutrient_order">
				<?php echo esc_html_x( 'Order', 'nutrients', 'woocommerce-germanized-pro' ); ?>
			</label>
			<input type="number" pattern="[0-9.]+" name="nutrient_order" id="nutrient_order" value="0" size="11" style="max-width: 100px;">
			<p class="description">
				<?php echo esc_html_x( 'Set a specific order by entering a number (1 for first, etc.) in this field.', 'nutrients', 'woocommerce-germanized-pro' ); ?>
			</p>
		</div>

		<?php
	}

	/**
	 * @param \WP_Term $term
	 */
	public static function edit_nutrient_form_fields( $term = false ) {
		$nutrient = self::get_nutrient( $term );
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="nutrient_unit">
					<?php echo esc_html_x( 'Unit', 'nutrients', 'woocommerce-germanized-pro' ); ?>
				</label>
			</th>
			<td>
				<select name="nutrient_unit" id="nutrient_unit">
					<?php
					$current_unit = ( $nutrient && $nutrient->get_unit_term( 'edit' ) ) ? $nutrient->get_unit_term( 'edit' )->slug : sanitize_key( self::get_default_nutrient_unit() );

					foreach ( WC_germanized()->units->get_units( array( 'as' => 'term_id=>name' ) ) as $term_id => $unit ) :
						$unit_term = WC_germanized()->units->get_unit_term( $term_id, 'id' );
						$slug      = is_a( $unit_term, 'WP_Term' ) ? $unit_term->slug : '';
						?>
						<option value="<?php echo esc_attr( $term_id ); ?>" <?php selected( $current_unit, $slug ); ?>><?php echo esc_html( $unit ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php echo esc_html_x( 'Choose a unit for this nutrient.', 'nutrients', 'woocommerce-germanized-pro' ); ?>
				</p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="nutrient_type">
					<?php echo esc_html_x( 'Type', 'nutrients', 'woocommerce-germanized-pro' ); ?>
				</label>
			</th>
			<td>
				<select name="nutrient_type" id="nutrient_type">
					<?php foreach ( self::get_nutrient_types() as $type => $title ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $nutrient->get_type( 'edit' ), $type ); ?>><?php echo esc_html( $title ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="nutrient_is_mandatory">
					<?php echo esc_html_x( 'Is mandatory?', 'nutrients', 'woocommerce-germanized-pro' ); ?>
				</label>
			</th>
			<td>
				<label for="nutrient_is_mandatory">
					<input id="nutrient_is_mandatory" type="checkbox" name="nutrient_is_mandatory" value="1" <?php checked( true, $nutrient->is_mandatory() ); ?> />

					<?php echo wp_kses_post( sprintf( _x( 'Nutrient is a <a href="%s">mandatory nutritional information</a>.', 'nutrients', 'woocommerce-germanized-pro' ), 'https://www.bzfe.de/lebensmittel/einkauf-und-kennzeichnung/kennzeichnung/naehrwertkennzeichnung/' ) ); ?>
				</label>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="nutrient_rounding_rule">
					<?php echo esc_html_x( 'Rounding Rule', 'nutrients', 'woocommerce-germanized-pro' ); ?>
				</label>
			</th>
			<td>
				<select name="nutrient_rounding_rule" id="nutrient_rounding_rule">
					<?php foreach ( array( '-1' => array( 'title' => _x( 'None', 'rounding-rule', 'woocommerce-germanized-pro' ) ) ) + self::get_nutrient_rounding_rules() as $slug => $rule ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $nutrient->get_rounding_rule_slug( 'edit' ), $slug ); ?>><?php echo esc_html( $rule['title'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php echo wp_kses_post( sprintf( _x( 'Apply a rounding rule based on the <a href="%1$s">EU food guide</a>', 'nutrients', 'woocommerce-germanized-pro' ), 'https://food.ec.europa.eu/system/files/2021-11/labelling_nutrition-vitamins_minerals-guidance_tolerances_1212_de.pdf' ) ); ?>
				</p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="nutrient_order">
					<?php echo esc_html_x( 'Order', 'nutrients', 'woocommerce-germanized-pro' ); ?>
				</label>
			</th>
			<td>
				<input name="nutrient_order" id="nutrient_order" type="text" value="<?php echo esc_attr( self::get_nutrient_order( $term ) ); ?>" size="11" style="max-width: 100px;" />
				<p class="description">
					<?php echo esc_html_x( 'Set a specific order by entering a number (1 for first, etc.) in this field.', 'nutrients', 'woocommerce-germanized-pro' ); ?>
				</p>
			</td>
		</tr>

		<?php
	}

	public static function get_default_nutrient_unit() {
		return 'g';
	}

	/**
	 * @param $nutrient_id
	 *
	 * @return string
	 */
	public static function get_nutrient_unit( $nutrient_id ) {
		$unit = self::get_default_nutrient_unit();

		if ( $nutrient = self::get_nutrient( $nutrient_id ) ) {
			$unit = $nutrient->get_unit();
		}

		return $unit;
	}

	/**
	 * @param int|\WP_Term $term
	 *
	 * @return Nutrient|false
	 */
	public static function get_nutrient( $term ) {
		try {
			$nutrient = new Nutrient( $term );

			return $nutrient;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * @param int|\WP_Term $term
	 *
	 * @return Allergen|false
	 */
	public static function get_allergen( $term ) {
		try {
			$allergen = new Allergen( $term );

			return $allergen;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	public static function get_nutrient_vitamin_reference_unit() {
		return apply_filters( 'woocommerce_gzdp_nutrient_vitamin_reference_value', '%' );
	}

	/**
	 * @param array $nutrients
	 * @param \WC_GZD_Product $product
	 * @param string $context
	 *
	 * @return Nutrient[]
	 */
	public static function get_product_nutrients( $nutrients, $product, $context = 'view' ) {
		$nutrients       = array();
		$last_title      = false;
		$nutrients_exist = false;

		/**
		 * In case of a variation/child product decide whether nutrients
		 * exist by default based on the parent product as the raw nutrient value
		 * returned does not inherit parent data.
		 */
		if ( $product->get_parent_id() > 0 ) {
			if ( $parent_product = wc_gzd_get_gzd_product( $product->get_parent_id() ) ) {
				$nutrients_exist = $parent_product->has_nutrients() ? true : false;
			}
		}

		foreach ( self::get_nutrients() as $nutrient ) {
			$new_nutrient       = false;
			$nutrient_value     = $product->get_nutrient_value( $nutrient->get_id(), $context );
			$nutrient_raw_value = $product->get_nutrient_value( $nutrient->get_id(), 'edit' );

			if ( 'title' === $nutrient->get_type() && ! apply_filters( 'woocommerce_gzdp_product_nutrients_skip_title', false, $nutrient ) ) {
				$last_title = $nutrient;
			} elseif ( apply_filters( 'woocommerce_gzdp_product_include_nutrient', ( $nutrient->is_mandatory() ? ( '' !== $nutrient_value ) : $nutrient_value ), $nutrient_value, $nutrient ) ) {
				$new_nutrient = $nutrient;
			}

			if ( $new_nutrient ) {
				if ( '' !== $nutrient_raw_value ) {
					$nutrients_exist = true;
				}

				/**
				 * Potentially add the title element right before the first child
				 */
				if ( $last_title && $last_title->get_id() === $nutrient->get_parent_id() ) {
					$nutrients[] = $last_title;
					$last_title  = false;
				}

				$nutrients[] = $new_nutrient;
			}
		}

		/**
		 * Prevent showing nutrients if no value exists at all.
		 */
		if ( ! $nutrients_exist ) {
			$nutrients = array();
		}

		return $nutrients;
	}

	/**
	 * @param string $html
	 * @param \WC_GZD_Product $gzd_product
	 *
	 * @return string
	 */
	public static function get_nutrients_html( $html, $gzd_product ) {
		if ( $gzd_product->has_nutrients() ) {
			global $product;
			$org_product = $product;
			$product     = $gzd_product->get_wc_product();

			ob_start();
			do_action( 'woocommerce_gzdp_product_nutrients' );
			$html = ob_get_clean();

			$product = $org_product;
		}

		return $html;
	}

	/**
	 * @param array $allergenic
	 * @param \WC_GZD_Product $product
	 * @param string $context
	 *
	 * @return Allergen[]
	 */
	public static function get_product_allergenic( $allergenic, $product, $context = 'view' ) {
		$allergenic = array();

		foreach ( wc_gzd_get_gzd_product( $product )->get_allergen_ids( $context ) as $id ) {
			if ( $allergen = self::get_allergen( $id ) ) {
				$allergenic[] = $allergen;
			}
		}

		return $allergenic;
	}

	/**
	 * Return the order of a nutrient
	 *
	 * @param int|\WP_Term $term_id
	 *
	 * @return int
	 */
	public static function get_nutrient_order( $term_id ) {
		if ( is_a( $term_id, 'WP_Term' ) ) {
			$term_id = $term_id->term_id;
		}

		$term = get_term( $term_id, 'product_nutrient' );

		if ( ! $term ) {
			return 0;
		}

		$order = 0;

		// Use term order if set
		if ( isset( $term->order ) ) {
			$order = $term->order;
		}

		return (int) $order;
	}

	public static function get_nutrient_types() {
		return class_exists( 'WC_GZD_Food_Helper' ) ? \WC_GZD_Food_Helper::get_nutrient_types() : array();
	}

	public static function get_nutrient_rounding_rules() {
		return class_exists( 'WC_GZD_Food_Helper' ) ? \WC_GZD_Food_Helper::get_nutrient_rounding_rules() : array();
	}

	/**
	 * @param $title
	 * @param $args
	 *
	 * @return Nutrient|false
	 */
	public static function create_nutrient( $title, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'order'         => -1,
				'parent'        => 0,
				'rounding_rule' => '',
				'is_mandatory'  => false,
				'slug'          => '',
				'type'          => 'numeric',
				'unit'          => self::get_default_nutrient_unit(),
				'children'      => array(),
			)
		);

		$args['slug'] = empty( $args['slug'] ) ? sanitize_title( $title ) : $args['slug'];

		$term = wp_insert_term(
			$title,
			'product_nutrient',
			array(
				'parent' => $args['parent'],
				'slug'   => $args['slug'],
			)
		);

		$nutrient = false;

		if ( ! is_wp_error( $term ) ) {
			if ( $nutrient = self::get_nutrient( $term['term_id'] ) ) {
				$nutrient->set_rounding_rule_slug( $args['rounding_rule'] );
				$nutrient->set_type( $args['type'] );
				$nutrient->set_is_mandatory( $args['is_mandatory'] );

				if ( $unit = WC_germanized()->units->get_unit_term( $args['unit'] ) ) {
					$nutrient->set_unit_id( $unit->term_id );
				}

				// Determine order
				if ( -1 === $args['order'] ) {
					$nutrients = get_terms(
						array(
							'taxonomy'   => 'product_nutrient',
							'orderby'    => 'order',
							'depth'      => 1,
							'parent'     => $args['parent'],
							'order'      => 'ASC',
							'hide_empty' => false,
						)
					);

					if ( ! empty( $nutrients ) && ! is_wp_error( $nutrients ) ) {
						$last_order    = self::get_nutrient_order( $nutrients[ count( $nutrients ) - 1 ]->term_id );
						$args['order'] = $last_order + 1;
					}
				}

				self::set_nutrient_order( $nutrient->get_id(), $args['order'] );

				/**
				 * Create children
				 */
				if ( ! empty( $args['children'] ) ) {
					$child_order = 1;

					foreach ( $args['children'] as $child ) {
						$child['parent'] = $nutrient->get_id();
						$child['order']  = $child_order;
						$child           = wp_parse_args(
							$child,
							array(
								'type'          => $args['type'],
								'rounding_rule' => $args['rounding_rule'],
								'unit'          => $args['unit'],
								'title'         => '',
							)
						);

						if ( $child_nutrient = self::create_nutrient( $child['title'], $child ) ) {
							$nutrient->add_child( $child_nutrient );
							++$child_order;
						}
					}
				}
			}
		}

		return $nutrient;
	}

	public static function create_default_nutrients( $delete_first = false ) {
		if ( $delete_first ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'product_nutrient',
					'hide_empty' => false,
				)
			);

			foreach ( $terms as $term ) {
				wp_delete_term( $term->term_id, 'product_nutrient' );
			}
		}

		$order = 1;

		foreach ( self::get_default_nutrients() as $nutrient_data ) {
			$nutrient_data = wp_parse_args(
				$nutrient_data,
				array(
					'title' => '',
					'slug'  => '',
				)
			);

			$slug = empty( $nutrient_data['slug'] ) ? sanitize_title( $nutrient_data['title'] ) : sanitize_title( $nutrient_data['slug'] );

			if ( ! $nutrient = self::get_nutrient( $slug ) ) {
				$nutrient_data['order'] = $order;

				$nutrient = self::create_nutrient( $nutrient_data['title'], $nutrient_data );
			}

			++$order;
		}
	}

	public static function get_default_nutrients() {
		return array(
			array(
				'title'         => _x( 'Calorific value kj', 'nutrients', 'woocommerce-germanized-pro' ),
				'unit'          => 'kj',
				'slug'          => 'calorific-value-kj',
				'rounding_rule' => 'energy',
				'is_mandatory'  => true,
			),
			array(
				'title'         => _x( 'Calorific value kcal', 'nutrients', 'woocommerce-germanized-pro' ),
				'unit'          => 'kcal',
				'slug'          => 'calorific-value-kcal',
				'rounding_rule' => 'energy',
				'is_mandatory'  => true,
			),
			array(
				'title'         => _x( 'Fat', 'nutrients', 'woocommerce-germanized-pro' ),
				'unit'          => 'g',
				'slug'          => 'fat',
				'rounding_rule' => 'proteins_sugar_fat',
				'is_mandatory'  => true,
				'children'      => array(
					array(
						'title'         => _x( 'saturated fatty acids', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'g',
						'slug'          => 'saturated-fatty-acids',
						'rounding_rule' => 'fatty_acids',
						'is_mandatory'  => true,
					),
					array(
						'title'         => _x( 'monounsaturated fatty acids', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'g',
						'slug'          => 'monounsaturated-fatty-acids',
						'rounding_rule' => 'fatty_acids',
					),
					array(
						'title'         => _x( 'polyunsaturated fatty acids', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'g',
						'slug'          => 'polyunsaturated-fatty-acids',
						'rounding_rule' => 'fatty_acids',
					),
				),
			),
			array(
				'title'         => _x( 'Carbohydrates', 'nutrients', 'woocommerce-germanized-pro' ),
				'unit'          => 'g',
				'slug'          => 'carbohydrates',
				'rounding_rule' => 'proteins_sugar_fat',
				'is_mandatory'  => true,
				'children'      => array(
					array(
						'title'         => _x( 'Sugar', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'g',
						'slug'          => 'sugar',
						'rounding_rule' => 'proteins_sugar_fat',
						'is_mandatory'  => true,
					),
					array(
						'title'         => _x( 'polyvalent alcohols', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'g',
						'slug'          => 'polyvalent-alcohols',
						'rounding_rule' => 'proteins_sugar_fat',
					),
					array(
						'title'         => _x( 'Starch', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'g',
						'slug'          => 'starch',
						'rounding_rule' => 'proteins_sugar_fat',
					),
				),
			),
			array(
				'title'         => _x( 'Dietary fiber', 'nutrients', 'woocommerce-germanized-pro' ),
				'unit'          => 'g',
				'slug'          => 'dietary-fiber',
				'rounding_rule' => 'proteins_sugar_fat',
			),
			array(
				'title'         => _x( 'Protein', 'nutrients', 'woocommerce-germanized-pro' ),
				'unit'          => 'g',
				'slug'          => 'protein',
				'rounding_rule' => 'proteins_sugar_fat',
				'is_mandatory'  => true,
			),
			array(
				'title'         => _x( 'Salt', 'nutrients', 'woocommerce-germanized-pro' ),
				'unit'          => 'g',
				'slug'          => 'salt',
				'rounding_rule' => 'salt',
				'is_mandatory'  => true,
			),
			array(
				'title'    => _x( 'Vitamins & Minerals', 'nutrients', 'woocommerce-germanized-pro' ),
				'type'     => 'vitamins',
				'slug'     => 'vitamins-minerals',
				'children' => array(
					array(
						'title'         => _x( 'Vitamin A', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'µg',
						'slug'          => 'vitamin-a',
						'rounding_rule' => 'vitamin_3',
						'type'          => 'vitamins',
					),
					array(
						'title'         => _x( 'Vitamin D', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'µg',
						'slug'          => 'vitamin-d',
						'rounding_rule' => 'vitamin_2',
						'type'          => 'vitamins',
					),
					array(
						'title'         => _x( 'Vitamin E', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'mg',
						'slug'          => 'vitamin-e',
						'rounding_rule' => 'vitamin_2',
						'type'          => 'vitamins',
					),
					array(
						'title'         => _x( 'Thiamine', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'mg',
						'slug'          => 'thiamine',
						'rounding_rule' => 'vitamin_2',
						'type'          => 'vitamins',
					),
					array(
						'title'         => _x( 'Vitamin B6', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'mg',
						'slug'          => 'vitamin-b6',
						'rounding_rule' => 'vitamin_2',
						'type'          => 'vitamins',
					),
					array(
						'title'         => _x( 'Calcium', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'mg',
						'slug'          => 'calcium',
						'rounding_rule' => 'vitamin_3',
						'type'          => 'vitamins',
					),
					array(
						'title'         => _x( 'Magnesium', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'mg',
						'slug'          => 'magnesium',
						'rounding_rule' => 'vitamin_3',
						'type'          => 'vitamins',
					),
					array(
						'title'         => _x( 'Iron', 'nutrients', 'woocommerce-germanized-pro' ),
						'unit'          => 'mg',
						'slug'          => 'iron',
						'rounding_rule' => 'vitamin_2',
						'type'          => 'vitamins',
					),
				),
			),
		);
	}

	public static function create_default_allergenic( $delete_first = false ) {
		if ( $delete_first ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'product_allergen',
					'hide_empty' => false,
				)
			);

			foreach ( $terms as $term ) {
				wp_delete_term( $term->term_id, 'product_allergen' );
			}
		}

		foreach ( self::get_default_allergenic() as $slug => $allergen_title ) {
			if ( ! self::get_allergen( $slug ) ) {
				wp_insert_term( $allergen_title, 'product_allergen', array( 'slug' => $slug ) );
			}
		}
	}

	public static function get_default_allergenic() {
		return array(
			'wheat'        => _x( 'Wheat', 'nutrients', 'woocommerce-germanized-pro' ),
			'rye'          => _x( 'Rye', 'nutrients', 'woocommerce-germanized-pro' ),
			'barley'       => _x( 'Barley', 'nutrients', 'woocommerce-germanized-pro' ),
			'oats'         => _x( 'Oats', 'nutrients', 'woocommerce-germanized-pro' ),
			'spelt'        => _x( 'Spelt', 'nutrients', 'woocommerce-germanized-pro' ),
			'eggs'         => _x( 'Eggs', 'nutrients', 'woocommerce-germanized-pro' ),
			'fish'         => _x( 'Fish', 'nutrients', 'woocommerce-germanized-pro' ),
			'peanuts'      => _x( 'Peanuts', 'nutrients', 'woocommerce-germanized-pro' ),
			'soybeans'     => _x( 'Soybeans', 'nutrients', 'woocommerce-germanized-pro' ),
			'milk'         => _x( 'Milk', 'nutrients', 'woocommerce-germanized-pro' ),
			'almonds'      => _x( 'Almonds', 'nutrients', 'woocommerce-germanized-pro' ),
			'hazelnuts'    => _x( 'Hazelnuts', 'nutrients', 'woocommerce-germanized-pro' ),
			'pecans'       => _x( 'Pecans', 'nutrients', 'woocommerce-germanized-pro' ),
			'brazil-nuts'  => _x( 'Brazil nuts', 'nutrients', 'woocommerce-germanized-pro' ),
			'pistachios'   => _x( 'Pistachios', 'nutrients', 'woocommerce-germanized-pro' ),
			'celery'       => _x( 'Celery', 'nutrients', 'woocommerce-germanized-pro' ),
			'sesame-seeds' => _x( 'Sesame seeds', 'nutrients', 'woocommerce-germanized-pro' ),
		);
	}
}
