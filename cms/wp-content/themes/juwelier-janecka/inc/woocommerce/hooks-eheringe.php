<?php
/**
 * Eheringe – Sonderfunktionen
 *
 * - Produktgrid: Warenkorb-Button + Versandkosten ausblenden
 * - Single Product: Standard-Kaufelemente entfernen, ACF-Felder + Buchungsbutton anzeigen
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;


// ============================================================
// Helper: Ist das aktuelle Produkt in der Kategorie "eheringe"?
// ============================================================

function janecka_is_eheringe_product( ?int $product_id = null ): bool {
	if ( ! $product_id ) {
		$product_id = get_the_ID();
	}
	if ( ! $product_id ) return false;

	return has_term( 'eheringe', 'product_cat', $product_id );
}

// Preisformatierung: "1250" → "1.250,00"
function janecka_format_ring_price( string $raw ): string {
	$num = (float) str_replace( [',', '.'], ['', '.'], trim( $raw ) );
	return number_format( $num, 2, ',', '.' );
}


// ============================================================
// 1. PRODUKTGRID: Warenkorb + Versandkosten ausblenden
// ============================================================

add_filter( 'janecka_product_card_show_actions', function( bool $show ): bool {
	if ( janecka_is_eheringe_product() ) {
		return false;
	}
	return $show;
} );

add_action( 'woocommerce_after_shop_loop_item', function(): void {
	if ( janecka_is_eheringe_product() ) {
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_gzd_template_loop_shipping_costs_info', 7 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_gzd_template_loop_tax_info', 6 );
	}
}, 1 );


// ============================================================
// 2. SINGLE PRODUCT: Standard-Kaufelemente + Meta-Row entfernen
// ============================================================

add_action( 'woocommerce_before_single_product', function(): void {
	if ( ! janecka_is_eheringe_product() ) return;

	// Preis + Warenkorb entfernen
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price',       20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

	// SKU-Block neu ausgeben (ohne leere Lieferzeit-Row)
	remove_action( 'woocommerce_single_product_summary', 'janecka_single_sku_delivery', 15 );
	add_action( 'woocommerce_single_product_summary', 'janecka_eheringe_sku', 15 );

	// GZD-Hooks entfernen (inkl. 20% MwSt. + Versandkosten)
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_gzd_template_single_tax_info',               25 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_gzd_template_single_shipping_costs_info',    26 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_gzd_template_single_delivery_time_info',     28 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_gzd_template_single_units',                  29 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_gzd_template_single_legal_info',             28 );

	// Diamant-Info direkt unter Titel (priority 12)
	add_action( 'woocommerce_single_product_summary', 'janecka_eheringe_diamant_subtitle', 12 );

	// Damen/Herrenring-Block
	add_action( 'woocommerce_single_product_summary', 'janecka_eheringe_acf_fields',    25 );

	// Buchungsbutton + Lieferzeit
	add_action( 'woocommerce_single_product_summary', 'janecka_eheringe_booking_button', 40 );
} );


// ============================================================
// 3. Beschreibungstext direkt unter dem Produkttitel
// ============================================================

function janecka_eheringe_diamant_subtitle(): void {
	$product_id = get_the_ID();
	$d_diamant = get_field( 'damenring_diamant',       $product_id );
	$d_steine  = get_field( 'damenring_anzahl_steine', $product_id );

	if ( ! $d_diamant ) return;

	$info = $d_diamant;
	if ( $d_steine ) {
		$info .= ' / Anzahl: ' . $d_steine;
	}
	?>
	<div class="eheringe-subtitle">
		<?php echo esc_html( $info ); ?>
	</div>
	<?php
}



// ============================================================
// 3b. SKU-Ausgabe ohne leere Lieferzeit-Row
// ============================================================

function janecka_eheringe_sku(): void {
	global $product;
	if ( ! $product ) return;

	$sku = $product->get_sku();
	if ( $sku ) {
		echo '<div class="single-product__meta-row">';
		echo '<span class="single-product__sku">';
		echo '<span class="single-product__meta-label">' . esc_html__( 'Artikelnummer', 'juwelier-janecka' ) . '</span>';
		echo esc_html( $sku );
		echo '</span>';
		echo '</div>';
	}

	// Lieferzeit
	if ( function_exists( 'wc_gzd_get_product' ) ) {
		$gzd_product = wc_gzd_get_product( $product );
		if ( $gzd_product ) {
			$delivery_term = $gzd_product->get_delivery_time();
			if ( $delivery_term instanceof WP_Term ) {
				echo '<div class="single-product__meta-row">';
				echo '<span class="single-product__delivery">';
				echo '<span class="single-product__meta-label">' . esc_html__( 'Lieferzeit', 'juwelier-janecka' ) . '</span>';
				echo esc_html( $delivery_term->name );
				echo '</span>';
				echo '</div>';
			}
		}
	}
}


// ============================================================
// 4. ACF-Felder: Damenring / Herrenring
// ============================================================

function janecka_eheringe_acf_fields(): void {
	$product_id = get_the_ID();

	$d_artnr  = get_field( 'damenring_artikelnummer',  $product_id );
	$d_diamant = get_field( 'damenring_diamant',        $product_id );
	$d_steine  = get_field( 'damenring_anzahl_steine',  $product_id );
	$d_preis   = get_field( 'damenring_basispreis',     $product_id );

	$h_artnr  = get_field( 'herrenring_artikelnummer', $product_id );
	$h_diamant = get_field( 'herrenring_diamant',       $product_id );
	$h_preis   = get_field( 'herrenring_basispreis',    $product_id );

	if ( ! $d_artnr && ! $h_artnr && ! $d_preis && ! $h_preis ) {
		return;
	}

	// Diamant-Info
	$d_diamant_info = '';
	if ( $d_diamant ) {
		$d_diamant_info = $d_diamant;
		if ( $d_steine ) {
			$d_diamant_info .= ' / Anzahl: ' . $d_steine;
		}
	}
	$h_diamant_info = $h_diamant ?? '';

	// Preisformatierung
	$d_preis_fmt = $d_preis ? janecka_format_ring_price( $d_preis ) : '';
	$h_preis_fmt = $h_preis ? janecka_format_ring_price( $h_preis ) : '';
	?>
	<div class="eheringe-details">
		<div class="eheringe-details__grid">

			<?php if ( $d_artnr || $d_preis_fmt ) : ?>
			<div class="eheringe-details__ring">
				<h3 class="eheringe-details__ring-title">
					<?php esc_html_e( 'Damenring', 'juwelier-janecka' ); ?>
				</h3>

				<?php if ( $d_artnr ) : ?>
				<div class="eheringe-details__row">
					<span class="eheringe-details__label">
						<?php esc_html_e( 'Artikelnummer', 'juwelier-janecka' ); ?>
					</span>
					<strong class="eheringe-details__value"><?php echo esc_html( $d_artnr ); ?></strong>
				</div>
				<?php endif; ?>

				<?php if ( $d_preis_fmt ) : ?>
				<div class="eheringe-details__price">
					€ <?php echo esc_html( $d_preis_fmt ); ?>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if ( $h_artnr || $h_preis_fmt ) : ?>
			<div class="eheringe-details__ring">
				<h3 class="eheringe-details__ring-title">
					<?php esc_html_e( 'Herrenring', 'juwelier-janecka' ); ?>
				</h3>

				<?php if ( $h_artnr ) : ?>
				<div class="eheringe-details__row">
					<span class="eheringe-details__label">
						<?php esc_html_e( 'Artikelnummer', 'juwelier-janecka' ); ?>
					</span>
					<strong class="eheringe-details__value"><?php echo esc_html( $h_artnr ); ?></strong>
				</div>
				<?php endif; ?>

				<?php if ( $h_preis_fmt ) : ?>
				<div class="eheringe-details__price">
					€ <?php echo esc_html( $h_preis_fmt ); ?>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div><!-- .eheringe-details__grid -->

		<p class="eheringe-details__tax-note">
			<?php esc_html_e( 'Die Preise verstehen sich inklusive 20% MwSt.', 'juwelier-janecka' ); ?>
		</p>
	</div><!-- .eheringe-details -->
	<?php
}


// ============================================================
// 5. Buchungsbutton + Lieferzeit
// ============================================================

function janecka_eheringe_booking_button(): void {
	if ( shortcode_exists( 'janecka_booking_button' ) ) {
		echo do_shortcode( '[janecka_booking_button label="Terminvereinbarung"]' );
	}

	// Lieferzeit via GZD
	global $product;
	if ( $product && function_exists( 'wc_gzd_get_product' ) ) {
		$gzd_product = wc_gzd_get_product( $product );
		if ( $gzd_product ) {
			$delivery_term = $gzd_product->get_delivery_time();
			if ( $delivery_term instanceof WP_Term ) {
				echo '<div class="eheringe-delivery wc-gzd-additional-info">';
				echo esc_html__( 'Lieferzeit', 'juwelier-janecka' ) . ': ';
				echo esc_html( $delivery_term->name );
				echo '</div>';
			}
		}
	}
}
