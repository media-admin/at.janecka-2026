jQuery( function ( $ ) {
	var wc_gzdp_product = {

		init: function() {
			var self = wc_gzdp_product;

			// Simple products
			$( '#general_product_data, .woocommerce_variable_attributes' ).on( 'recalculate_unit_prices', function() {
				self.recalculateUnitPrices( $( this ) );
			});

			$( document ).on( 'click', '#_unit_price_auto', function() {
				if ( $( this ).is( ':checked' ) ) {
					$( this ).parents( '#general_product_data' ).trigger( 'recalculate_unit_prices' );
				} else {
					$( 'input' ).removeClass( 'wc_input_error' );
					$( '#general_product_data' ).find( 'input[name=_unit_price_regular]' ).removeAttr( 'readonly' );
					$( '#general_product_data' ).find( 'input[name=_unit_price_sale]' ).removeAttr( 'readonly' );
				}
			});

			$( document ).on( 'change', 'input[name=_regular_price], input[name=_sale_price], input[name=_unit_base], input[name=_unit_product]', function() {
				if ( $( 'input[name=_unit_price_auto]' ).is( ':checked' ) ) {
					$( this ).parents( '#general_product_data' ).trigger( 'recalculate_unit_prices' );

					if ( $( this ).parents( '.product_data' ).find( '.woocommerce_variable_attributes' ).length > 0 ) {
						$( this ).parents( '.product_data' ).find( '.woocommerce_variable_attributes' ).each( function() {
							self.recalculateUnitPrices( $( this ) );
						});
					}
				}
			});

			$( 'input[name=_regular_price]' ).trigger( 'change' );

			// Variable products
			$( document ).find( '.woocommerce_variable_attributes' ).addClass( 'event-bound' );

			$( document ).on( 'click', 'input[name*=variable_unit_price_auto]', function() {
				// Check for new variations that are not bound to the event
				if ( ! $( this ).parents( '.woocommerce_variable_attributes' ).hasClass( 'event-bound' ) ) {
					$( this ).parents( '.woocommerce_variable_attributes' ).addClass( 'event-bound' );
					$( this ).parents( '.woocommerce_variable_attributes' ).bind( 'recalculate_unit_prices', function() {
						self.recalculateUnitPrices( $( this ) );
					});
				}

				if ( $( this ).is( ':checked' ) ) {
					$( this ).parents( '.woocommerce_variable_attributes' ).trigger( 'recalculate_unit_prices' );
				} else {
					$( this ).parents( '.woocommerce_variable_attributes' ).find( 'input' ).removeClass( 'wc_input_error' );
					$( this ).parents( '.woocommerce_variable_attributes' ).find( 'input[name*=variable_unit_price_regular]' ).removeAttr( 'readonly' );
					$( this ).parents( '.woocommerce_variable_attributes' ).find( 'input[name*=variable_unit_price_sale]' ).removeAttr( 'readonly' );
				}
			});

			$( document ).on( 'change', 'input[name*=variable_regular_price], input[name*=variable_sale_price], input[name*=variable_unit_product]', function() {
				if ( $( this ).parents( '.woocommerce_variable_attributes' ).find( 'input[name*=variable_unit_price_auto]' ).is( ':checked' ) ) {
					self.recalculateUnitPrices( $( this ).parents( '.woocommerce_variable_attributes' ) );
				}
			});

			$( document ).on( 'click', '.woocommerce_variation', function() {
				if ( $( this ).find( 'input[name*=variable_unit_price_auto]' ).is( ':checked' ) ) {
					$( this ).find( 'input[name*=variable_unit_price_regular]' ).attr( 'readonly', 'readonly' );
					$( this ).find( 'input[name*=variable_unit_price_sale]' ).attr( 'readonly', 'readonly' );

					self.recalculateUnitPrices( $( this ).find( '.woocommerce_variable_attributes' ) );
				}
			});

			$( document ).on( 'woocommerce_variations_loaded', function() {
				$( '.woocommerce_variations .woocommerce_variable_attributes' ).each( function() {
					if ( $( this ).find( 'input[name*=variable_unit_price_auto]' ).is( ':checked' ) ) {
						self.recalculateUnitPrices( $( this ) );
					}
				});
			});

			$( document ).on( 'click', 'a.wc-gzdp-clear-nutrients', function() {
				var $panel = $( this ).parents( '.variable_nutrients' ).length > 0 ? $( this ).parents( '.variable_nutrients' ) : $( this ).parents( '.nutrients' );
				$panel.find( '.form-row-nutrient input.wc_input_price' ).val( '' );

				return false;
			});

			$( document ).on( 'paste', '.form-row-nutrient input.wc_input_price', self.onPasteNutrientData );
		},

		onPasteNutrientData: function( e ) {
			var unitPattern = wc_gzdp_admin_products_params.i18n_nutrient_units_regex;
			var text = e.originalEvent.clipboardData.getData(  'text' );
			text = text.toLowerCase();

			/**
			 * Clean data, e.g. in case the string contains numbers with point as decimal separator
			 * convert them to comma.
			 */
			// Found comma as decimal separator - remove dots (thousand seps)
			if ( /[0-9]{1,},\d{1,}/g.test( text ) ) {
				text = text.replace( /([1-9]{1})(\.)(.*)/g, '$1$3' );
			} else {
				// Convert point as decimal separator to comma.
				text = text.replace( /([0-9]{1})(\.)(.*)/g, '$1,$3' );
			}

			var textData = text.split( "\n" );

			// Replace whitespaces e.g. \t with normal whitespace
			textData = textData.map( function( str ) {
				return str.replace(/\s/g, " " );
			});

			textData = textData.filter( Boolean );

			var $panel = $( this ).parents( '.variable_nutrients' ).length > 0 ? $( this ).parents( '.variable_nutrients' ) : $( this ).parents( '.nutrients' );
			var fieldsLeft = [];
			hasReplaced = false;

			$panel.find( '.form-row-nutrient input[data-unit]' ).each( function() {
				fieldsLeft.push({
					'input': $( this ),
					'unit' : $( this ).data( 'unit' ),
					'regex': $( this ).data( 'regex' ),
					'nutrientId': $( this ).data( 'nutrient-id' ),
				});
			} );

			$.each( textData, function( i, line ) {
				var nutrient_ref_regex = new RegExp( wc_gzdp_admin_products_params.i18n_nutrient_reference_values_regex, 'i' );

				if ( nutrient_ref_regex.test( line ) ) {
					$.each( wc_gzdp_admin_products_params.i18n_nutrient_reference_values, function( refRegex, selectKey ) {
						nutrient_ref_regex = new RegExp( refRegex, 'i' );

						if ( nutrient_ref_regex.test( line ) ) {
							var label = $panel.find( '.nutrient-reference-value-wrapper' ).find( 'label' ).text();

							console.log( 'Detected ' + label + ' in clipboard = ' + selectKey );

							$panel.find( '.nutrient-reference-value-wrapper select' ).val( selectKey );

							return false;
						}
					} );
				} else {
					$.each( fieldsLeft, function( fieldIndex, field ) {
						if ( field === undefined ) {
							return;
						}

						var pattern 	   = new RegExp( field.regex, 'i' );
						var strWithNumbers = line;

						if ( pattern.test( line ) ) {
							console.log( "Detected " + field['nutrientId'] + ' in clipboard line ' + line );

							// Check whether the number is placed in the same line or next line
							if ( ! new RegExp( '[0-9](.*)' + unitPattern ).test( line ) ) {
								if ( textData.length >= ( i + 1 ) ) {
									strWithNumbers = textData[ i + 1 ];
								}
							}

							var numbers = []

							// Found a number in the line
							if ( new RegExp( '[0-9](.*)' + unitPattern ).test( strWithNumbers ) ) {
								var re = new RegExp( '([1-9]\\d*|0)(,\\d+)?(\\s' + unitPattern + ')?', 'gi' );
								var matches;

								// Multiple numbers may exist per line (e.g. 1732 kJ, 200 kcal)
								while ( ( matches = re.exec( strWithNumbers ) ) != null ) {
									matches = matches.filter( Boolean );

									var numberData = {
										'number': parseFloat( matches[0].replace( ',', '.' ) ),
										'unit'  : '',
									};

									if ( matches.length >= 4 ) {
										numberData['unit'] = matches[3].toLowerCase().replace( /\s/g, "" );
									}

									numbers.push( numberData );
								}
							}

							if ( numbers.length > 0 ) {
								var number = numbers[0];

								if ( numbers.length > 1 ) {
									$.each( numbers, function( numberIndex, numberData ) {
										if ( numberData.unit.toString().toLowerCase() === field.unit.toString().toLowerCase() ) {
											number = numberData;
										}
									} );
								}

								if ( number.unit.toString().toLowerCase() === field.unit.toString().toLowerCase() ) {
									$( field.input ).val( number.number.toString().replace( '.', wc_gzdp_admin_products_params.decimal_separator ) );

									var label = $( field.input ).parents( '.form-field' ).find( 'label' ).text();
									console.log( 'Detected ' + label + ' in clipboard = ' + number.number );

									hasReplaced = true;

									// Remove the field found
									if ( fieldsLeft[ fieldIndex ] !== undefined ) {
										delete fieldsLeft[ fieldIndex ];
									}
								}
							}
						}
					} );
				}
			});

			if ( hasReplaced ) {
				e.preventDefault();
			} else {
				/**
				 * In case we did not find anything to replace lets
				 * make sure the number is formatted as expected, e.g. decimal separator, units removed.
				 */
				var $el = $( this );

				setTimeout( function () {
					$el.val(function() {
						var val = this.value.toString().replace( '.', wc_gzdp_admin_products_params.decimal_separator );
						val = val.replace( new RegExp( unitPattern, 'i' ), "" );

						return val.replace( /\s/g, "" );
					})
				});
			}
		},

		formatPrice: function(price ) {
			return wc_gzdp_product.roundPrice( price ).toString().replace( '.', ',' );
		},

		roundPrice: function(price ) {
			var d = parseInt( 2,10 ),
				dx = Math.pow(10, d ),
				n = parseFloat( price ),
				f = Math.round(Math.round( n * dx * 10 ) / 10 ) / dx;

			return f.toFixed( 2 );
		},

		recalculateUnitPrices: function(element ) {
			var self = wc_gzdp_product;

			var fields = [
				'input[name=_regular_price], input[name*=variable_regular_price]',
				'input[name=_unit_base]',
			];

			var error = false;

			$.each( fields, function( index, value ) {
				$( element ).find( value ).removeClass( 'wc_input_error' );

				if ( ! $( element ).find( value ).val() && ! $( '#general_product_data' ).find( value ).val() ) {
					error = true;
					$( element ).find( value ).addClass( 'wc_input_error' );
				}
			});

			if ( ! error ) {
				$( element ).find( '#_unit_price_regular, input[name*=variable_unit_price_regular]' ).attr( 'readonly', 'readonly' );
				$( element ).find( '#_unit_price_sale, input[name*=variable_unit_price_sale]' ).attr( 'readonly', 'readonly' );

				var base = parseFloat( $( '#general_product_data input[name=_unit_base]' ).val().replace( ',', '.' ) );
				var price = parseFloat( $( element ).find( 'input[name=_regular_price], input[name*=variable_regular_price]' ).val().replace( ',', '.' ) );
				var base_product = base;

				if ( $( element ).find( 'input[name*=variable_unit_product]' ).val() ) {
					// First take variation product units
					base_product = parseFloat( $( element ).find( 'input[name*=variable_unit_product]' ).val().replace( ',', '.' ) );
				} else if ( $( '#general_product_data' ).find( 'input[name=_unit_product]' ).val() ) {
					// Check parent or simple product
					base_product = parseFloat( $( '#general_product_data' ).find( 'input[name=_unit_product]' ).val().replace( ',', '.' ) );
				} else {
					base = 1;
				}

				var old_price = $( element ).find( 'input[name=_unit_price_regular], input[name*=variable_unit_price_regular]' ).val();
				var new_price = self.formatPrice( ( price / base_product ) * base );

				$( element ).find( 'input[name=_unit_price_regular], input[name*=variable_unit_price_regular]' ).val( new_price );

				// Tell WooCommerce to save variations if prices have changed
				if ( old_price != new_price ) {
					$( element ).find( 'input[name=_unit_price_regular], input[name*=variable_unit_price_regular]' ).trigger( 'change' );
				}

				if ( $( element ).find( 'input[name=_sale_price], input[name*=variable_sale_price]' ).val() ) {
					var sale_price = parseFloat( $( element ).find( 'input[name=_sale_price], input[name*=variable_sale_price]' ).val().replace( ',', '.' ) );

					var new_sale_price = self.formatPrice( ( sale_price / base_product ) * base );
					var old_sale_price = $( element ).find( 'input[name=_unit_price_sale], input[name*=variable_unit_price_sale]' ).val();

					$( element ).find( 'input[name=_unit_price_sale], input[name*=variable_unit_price_sale]' ).val( new_sale_price );

					// Tell WooCommerce to save variations if prices have changed
					if ( new_sale_price != old_sale_price ) {
						$( element ).find( 'input[name=_unit_price_sale], input[name*=variable_unit_price_sale]' ).trigger( 'change' );
					}

				} else {
					$( element ).find( 'input[name=_unit_price_sale], input[name*=variable_unit_price_sale]' ).val( '' );
				}
			}
		}
	};

	wc_gzdp_product.init();
});