<?php

/**
 * StoreaBill Formatting Functions
 *
 * General formatting functions available on both the front-end and admin.
 *
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sab_format_price( $price, $args = array() ) {
	return apply_filters( 'storeabill_formatted_price', wc_price( $price, $args ), $price, $args );
}

function sab_clean( $value ) {
	return wc_clean( $value );
}

function sab_string_to_datetime( $time_string ) {
	return wc_string_to_datetime( $time_string );
}

function sab_string_to_timestamp( $time_string, $from_timestamp = null ) {
	return wc_string_to_timestamp( $time_string, $from_timestamp );
}

function sab_format_decimal( $number, $dp = false, $trim_zeros = false ) {
	return wc_format_decimal( $number, $dp, $trim_zeros );
}

function sab_round_tax_total( $value, $precision = null ) {
	return wc_round_tax_total( $value, $precision );
}

function sab_get_price_decimal_separator() {
	return wc_get_price_decimal_separator();
}

function sab_get_price_format() {
	return get_woocommerce_price_format();
}

function sab_get_price_format_by_position( $currency_pos ) {
	$format = '%1$s%2$s';

	switch ( $currency_pos ) {
		case 'left':
			$format = '%1$s%2$s';
			break;
		case 'right':
			$format = '%2$s%1$s';
			break;
		case 'left_space':
			$format = '%1$s&nbsp;%2$s';
			break;
		case 'right_space':
			$format = '%2$s&nbsp;%1$s';
			break;
	}

	return apply_filters( 'woocommerce_price_format', $format, $currency_pos );
}

function sab_get_price_thousand_separator() {
	return wc_get_price_thousand_separator();
}

function sab_get_currency_symbol( $currency = '' ) {
	if ( '' === $currency ) {
		$currency = sab_get_default_currency();
	}

	return get_woocommerce_currency_symbol( $currency );
}

function sab_get_price_decimals() {
	return wc_get_price_decimals();
}

function sab_get_rounding_precision() {
	return wc_get_rounding_precision();
}

function sab_hex_lighter( $color, $factor = 30 ) {
	return wc_hex_lighter( $color, $factor );
}

function sab_hex_darker( $color, $factor = 30 ) {
	return wc_hex_darker( $color, $factor );
}

function sab_strip_unit_from_number( $str ) {
	return str_replace( array( '%', 'pt', 'mm', 'cm', 'em', 'rem', 'px', 'vw' ), '', $str );
}

/**
 * WooCommerce Date Format - Allows to change date format for everything WooCommerce.
 *
 * @return string
 */
function sab_date_format() {
	return apply_filters( 'storeabill_date_format', get_option( 'date_format' ) );
}

/**
 * WooCommerce Time Format - Allows to change time format for everything WooCommerce.
 *
 * @return string
 */
function sab_time_format() {
	return apply_filters( 'storeabill_time_format', get_option( 'time_format' ) );
}

function sab_format_item_quantity( $quantity ) {
	return sab_format_decimal( $quantity, 3, true );
}

function sab_generate_block_classes( $attributes, $html_classes = '' ) {
	$base_classes = sab_get_html_classes( $html_classes );

	$wrapper_mappings = array(
		'backgroundColor' => 'has-background-color has-{value}-background-color',
		'borderColor'     => 'has-border-color has-{value}-border-color',
		'textColor'       => 'has-text-color has-{value}-color',
		'border'          => 'has-border-{value}',
		'fontSize'        => 'has-font-size has-{value}-font-size',
		'className'       => '{value}',
		'align'           => 'has-text-align-{value}',
		'hasDenseLayout'  => 'has-dense-layout',
	);

	$classes = array( 'sab-block' );

	if ( isset( $attributes['style'], $attributes['style']['border'] ) ) {
		$border_types = array(
			'top',
			'right',
			'bottom',
			'left',
		);

		foreach ( $border_types as $border_type ) {
			if ( isset( $attributes['style']['border'][ $border_type ] ) ) {
				$classes[] = 'has-border-' . $border_type;

				if ( ! in_array( 'has-border', $classes, true ) ) {
					$classes[] = 'has-border';
				}
			}
		}
	}

	foreach ( $attributes as $attribute => $value ) {
		/**
		 * Make sure to support customFontSize or customBorderColor by replacing custom prefix.
		 */
		if ( substr( $attribute, 0, 6 ) === 'custom' && ! empty( $value ) ) {
			// Remove the original value
			unset( $attributes[ $attribute ] );

			$attribute = lcfirst( substr( $attribute, 6 ) );

			/**
			 * Prefer custom attributes over their non-customs e.g. borderColor
			 */
			if ( isset( $attributes[ $attribute ] ) ) {
				unset( $attributes[ $attribute ] );
			}

			$attributes[ $attribute ] = $value;
		}
	}

	foreach ( $attributes as $attribute => $value ) {
		if ( array_key_exists( $attribute, $wrapper_mappings ) ) {
			$mapping = $wrapper_mappings[ $attribute ];

			if ( ! empty( $value ) ) {
				if ( ! is_array( $value ) ) {
					$value = array( $value );
				}

				foreach ( $value as $val ) {
					$classes = array_merge( $classes, explode( ' ', str_replace( '{value}', $val, $mapping ) ) );
				}
			} else {
				$inner_maps = explode( ' ', $mapping );

				if ( count( $inner_maps ) > 1 ) {
					$classes[] = 'without-' . $inner_maps[0];
				}
			}
		}
	}

	return array_unique( array_merge( $classes, $base_classes ) );
}

function sab_get_color_names() {
	return array(
		'pale-pink'             => '#f78da7',
		'vivid-red'             => '#cf2e2e',
		'vivid-orange'          => '#ff6900',
		'luminous-vivid-orange' => '#ff6900',
		'vivid-amber'           => '#fcb900',
		'luminous-vivid-amber'  => '#fcb900',
		'green-cyan'            => '#7bdcb5',
		'light-green-cyan'      => '#7bdcb5',
		'vivid-green-cyan'      => '#00d084',
		'cyan-blue'             => '#8ed1fc',
		'pale-cyan-blue'        => '#8ed1fc',
		'vivid-cyan-blue'       => '#0693e3',
		'vivid-purple'          => '#9b51e0',
		'light-gray'            => '#eee',
		'very-light-gray'       => '#eee',
		'bluish-gray'           => '#abb8c3',
		'cyan-bluish-gray'      => '#abb8c3',
		'dark-gray'             => '#313131',
		'black'                 => '#000000',
		'white'                 => '#FFFFFF',
	);
}

function sab_get_color_from_name( $color_name ) {

	// Check if it is a hex color
	if ( $hex_color = sanitize_hex_color( $color_name ) ) {
		$color = $hex_color;
	} else {
		$colors = sab_get_color_names();
		$color  = '';

		if ( array_key_exists( $color_name, $colors ) ) {
			$color = $colors[ $color_name ];
		}
	}

	return $color;
}

function sab_generate_block_styles( $attributes, $attribute_mappings = array(), $as_html = false ) {
	$style_mappings = array(
		'customTextColor'       => 'color',
		'customBorderColor'     => 'border-color',
		'customBackgroundColor' => 'background-color',
		'borderColor'           => array(
			'attribute'   => 'border-color',
			'replacement' => '{value}',
			'callback'    => 'sab_get_color_from_name',
		),
		'textColor'             => array(
			'attribute'   => 'color',
			'replacement' => '{value}',
			'callback'    => 'sab_get_color_from_name',
		),
		'backgroundColor'       => array(
			'attribute'   => 'background-color',
			'replacement' => '{value}',
			'callback'    => 'sab_get_color_from_name',
		),
		'customFontSize'        => array(
			'attribute'   => 'font-size',
			'replacement' => '{value}px',
			'callback'    => 'sab_get_document_font_size',
		),
		'fontSize'              => array(
			'attribute'   => 'font-size',
			'replacement' => '{value}px',
			'callback'    => 'sab_get_document_font_size',
		),
	);

	/**
	 * Some custom attributes, e.g. custom font sizes are stored
	 * in a separate style attribute. Transform those attributes
	 * to normal, standalone attributes instead to allow matching.
	 */
	if ( ! empty( $attributes['style'] ) ) {
		foreach ( $attributes['style'] as $inner_styles ) {
			foreach ( $inner_styles as $style_name => $value ) {
				if ( ! in_array( $style_name, array_values( $attribute_mappings ), true ) ) {
					$attributes[ $style_name ] = $value;
				}
			}
		}
	}

	$styles = array();

	foreach ( $attributes as $attribute => $value ) {
		if ( '' === $value ) {
			continue;
		}

		// E.g. map headingTextColor to textColor
		if ( array_key_exists( $attribute, $attribute_mappings ) ) {
			$attribute = $attribute_mappings[ $attribute ];
		}

		if ( array_key_exists( $attribute, $style_mappings ) ) {
			$mapping = $style_mappings[ $attribute ];

			/**
			 * Prefer non-custom attributes over custom attributes
			 */
			if ( strstr( $attribute, 'custom' ) ) {
				$non_custom_key = lcfirst( substr( $attribute, 6 ) );

				if ( isset( $attributes[ $non_custom_key ] ) && ! empty( $attributes[ $non_custom_key ] ) ) {
					continue;
				}
			}

			if ( is_array( $mapping ) ) {
				$mapping = wp_parse_args(
					$mapping,
					array(
						'attribute'   => '',
						'replacement' => '{value}',
						'callback'    => null,
					)
				);

				if ( $mapping['callback'] ) {
					$value = call_user_func_array( $mapping['callback'], array( $value ) );
				}

				$styles[ $mapping['attribute'] ] = str_replace( '{value}', $value, $mapping['replacement'] );
			} else {
				$styles[ $style_mappings[ $attribute ] ] = $value;
			}
		}
	}

	if ( $as_html ) {
		return sab_print_styles( $styles, false );
	}

	return $styles;
}

function sab_print_html_classes( $classes, $echo = true ) {
	if ( is_array( $classes ) ) {
		$classes = array_unique( $classes );
		$classes = implode( ' ', $classes );
	}

	if ( $echo ) {
		echo esc_attr( $classes );
	} else {
		return esc_attr( $classes );
	}
}

function sab_print_styles( $styles, $echo = true ) {
	$style_output = '';

	foreach ( $styles as $tag => $style ) {
		if ( '' === $style ) {
			continue;
		}

		$style_output .= esc_attr( $tag ) . ':' . wp_kses_post( $style ) . ';';
	}

	if ( $echo ) {
		echo $style_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		return $style_output;
	}
}

function sab_get_font_variant_types() {
	return array(
		'regular'     => _x( 'Regular', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'bold'        => _x( 'Bold', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'italic'      => _x( 'Italic', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'bold_italic' => _x( 'Bold Italic', 'storeabill-core', 'woocommerce-germanized-pro' ),
	);
}

function sab_filter_html_classes( $classes, $filter = '' ) {
	$filtered_classes = array();

	foreach ( $classes as $classname ) {
		if ( strpos( $classname, $filter ) !== false ) {
			$filtered_classes[] = $classname;
		}
	}

	return $filtered_classes;
}

function sab_get_html_classes( $html ) {
	$classes = array();

	preg_match( '/class="(.*?)"/s', $html, $matches );

	if ( isset( $matches[1] ) ) {
		$classes = explode( ' ', trim( $matches[1] ) );
	}

	return $classes;
}

function sab_get_html_loop_classes( $prefix, $total, $current ) {
	$classes = array(
		$prefix . '-' . $current,
	);

	if ( $total === $current ) {
		$classes[] = $prefix . '-last';
	} elseif ( 1 === $current ) {
		$classes[] = $prefix . '-first';
	}

	if ( 0 === $current % 2 ) {
		$classes[] = $prefix . '-even';
	} else {
		$classes[] = $prefix . '-odd';
	}

	return $classes;
}

function sab_get_html_part( $tag, $html ) {
	$tag     = preg_quote( $tag ); // phpcs:ignore WordPress.PHP.PregQuoteDelimiter.Missing
	$pattern = "'<!--\s*sab:" . $tag . '\s*-->(.*?)<!--\s*/sab:' . $tag . "\s*-->'si";

	if ( preg_match( $pattern, $html, $matches ) ) {
		return trim( $matches[1] );
	}

	return '';
}

/**
 * Display item meta data.
 *
 * @since  3.0.0
 * @param  \Vendidero\StoreaBill\Document\Item $item Document Item.
 * @param  array         $args Arguments.
 * @return string|void
 */
function sab_display_item_attributes( $item, $args = array() ) {
	$strings = array();
	$html    = '';
	$args    = wp_parse_args(
		$args,
		array(
			'styles'       => array(),
			'classes'      => array(),
			'before'       => '<ul class="sab-item-attributes"><li>',
			'after'        => '</li></ul>',
			'separator'    => '</li><li>',
			'echo'         => true,
			'autop'        => false,
			'label_before' => '<strong class="sab-item-attribute-label">',
			'label_after'  => ':</strong> ',
		)
	);

	foreach ( $item->get_attributes() as $attribute ) {
		$strings[] = $args['label_before'] . wp_kses_post( $attribute->get_formatted_label() ) . $args['label_after'] . wp_kses_post( $attribute->get_formatted_value( $args['autop'] ) );
	}

	if ( $strings ) {
		$html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
	}

	$html = apply_filters( 'storeabill_display_item_attributes', $html, $item, $args );

	if ( $args['echo'] ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		return $html;
	}
}

function sab_format_tax_rate_percentage( $number, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'html' => false,
		)
	);

	$text = sab_format_percentage( $number, $args );

	if ( $args['html'] ) {
		return apply_filters( 'storeabill_formatted_tax_rate_percentage_html', $text, $number, $args );
	} else {
		return apply_filters( 'storeabill_formatted_tax_rate_percentage', $text, $number, $args );
	}
}

/**
 * Trim trailing zeros off numbers.
 *
 * @param string|float|int $number Number.
 * @return string
 */
function sab_trim_zeros( $number ) {
	$result = strval( $number );

	if ( ! empty( $number ) ) {
		$result = rtrim( rtrim( $result, '0' ), '\.,' );
	}

	return $result;
}

function sab_format_country_name( $country_code ) {
	$countries = WC()->countries ? WC()->countries->get_countries() : array();

	if ( isset( $countries[ $country_code ] ) ) {
		$country_code = $countries[ $country_code ];
	}

	return $country_code;
}

function sab_format_percentage( $number, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'decimal_separator'  => sab_get_price_decimal_separator(),
			'thousand_separator' => sab_get_price_thousand_separator(),
			'decimals'           => 2,
			'percentage_sign'    => '%',
			'trim_zeros'         => apply_filters( 'storeabill_trim_percentage_zeros', true ),
			'html'               => false,
		)
	);

	$number = sab_format_decimal( trim( str_replace( '%', '', $number ) ), '', true );
	$text   = '';

	$formatted = number_format( $number, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );

	if ( true === $args['trim_zeros'] ) {
		$formatted = sab_trim_zeros( $formatted );
	}

	if ( $args['html'] ) {
		$text = sprintf( '<span class="percentage"><span class="number">%s</span>%s</span>', $formatted, ( ! empty( $args['percentage_sign'] ) ? sprintf( '<span class="sign">%s</span>', $args['percentage_sign'] ) : '' ) );
	} elseif ( ! empty( $args['percentage_sign'] ) ) {
			/* translators: 1: formatted number 2: percentage sign */
			$text = sprintf( _x( '%1$s %2$s', 'storeabill-core', 'woocommerce-germanized-pro' ), $formatted, $args['percentage_sign'] );
	} else {
		$text = $formatted;
	}

	if ( $args['html'] ) {
		return apply_filters( 'storeabill_formatted_percentage_html', $text, $number, $args );
	} else {
		return apply_filters( 'storeabill_formatted_percentage', $text, $number, $args );
	}
}

function sab_query_to_shortcode( $query ) {
	$shortcode_data = sab_get_shortcode_query_data( $query );
	$shortcode_str  = $shortcode_data['shortcode'];
	$shortcode      = '';

	foreach ( $shortcode_data['args'] as $attribute => $value ) {
		$shortcode_str .= ' ' . esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
	}

	if ( ! empty( $shortcode_str ) ) {
		$shortcode = '[' . $shortcode_str . ']';
	}

	return $shortcode;
}

function sab_get_shortcode_query_data( $query ) {
	$url            = wp_parse_url( $query );
	$shortcode_data = array(
		'shortcode' => isset( $url['path'] ) ? $url['path'] : '',
		'args'      => array(),
	);

	parse_str( ( isset( $url['query'] ) ? $url['query'] : '' ), $query_result );

	if ( ! empty( $query_result ) ) {
		foreach ( $query_result as $attribute => $value ) {
			$shortcode_data['args'][ $attribute ] = $value;
		}
	}

	return $shortcode_data;
}

function sab_remove_placeholder_tax_rate( $str ) {
	$str = strip_shortcodes( sab_blocks_convert_shortcodes( $str ) );
	$str = preg_replace( '#<span[^>]*(?:/>|>(?:\s|&nbsp;)*</span>)#im', '', $str );
	$str = trim( str_replace( array( '%s', '%', '{rate}', '{formatted_rate}' ), '', $str ) );

	return $str;
}

function sab_timezone_offset() {
	return wc_timezone_offset();
}

function sab_timezone_string() {
	return wc_timezone_string();
}

/**
 * Returns the current date time as GMT including local timezone offsets.
 * sab_string_to_datetime() expects strings to be in local timezone - use current_time for that.
 *
 * @return WC_DateTime
 * @throws Exception
 */
function sab_get_current_datetime() {
	return sab_string_to_datetime( current_time( 'mysql' ) );
}

function sab_normalize_postcode( $postcode ) {
	return wc_normalize_postcode( $postcode );
}

function sab_strtoupper( $string ) {
	return wc_strtoupper( $string );
}

/**
 * Converts a string (e.g. 'yes' or 'no') to a bool.
 *
 * @param string|bool $string String to convert. If a bool is passed it will be returned as-is.
 * @return bool
 */
function sab_string_to_bool( $string ) {
	return wc_string_to_bool( $string );
}

/**
 * Converts a bool to a 'yes' or 'no'.
 *
 * @param bool|string $bool Bool to convert. If a string is passed it will first be converted to a bool.
 * @return string
 */
function sab_bool_to_string( $bool ) {
	return wc_bool_to_string( $bool );
}

/**
 * Format a price with currency locale settings.
 *
 * @param  string $value Price to localize.
 * @return string
 */
function sab_format_localized_price( $value ) {
	return wc_format_localized_price( $value );
}

/**
 * Format a decimal with the decimal separator.
 *
 * @param  string $value Decimal to localize.
 * @return string
 */
function sab_format_localized_decimal( $value ) {
	return wc_format_localized_decimal( $value );
}

function sab_print_decimal( $value, $decimal_args = array() ) {
	$decimal_args = wp_parse_args(
		$decimal_args,
		array(
			'decimal_separator'  => sab_get_price_decimal_separator(),
			'thousand_separator' => sab_get_price_thousand_separator(),
			'decimals'           => sab_get_price_decimals(),
		)
	);

	$decimal     = (float) $value;
	$return_data = apply_filters( 'storeabill_print_decimal_format', number_format( $decimal, $decimal_args['decimals'], $decimal_args['decimal_separator'], $decimal_args['thousand_separator'] ), $decimal, $decimal_args, $value );

	if ( apply_filters( 'storeabill_print_decimal_format_trim_zeros', false ) && $decimal_args['decimals'] > 0 ) {
		$return_data = sab_trim_zeros( $return_data );
	}

	return strval( $return_data );
}
