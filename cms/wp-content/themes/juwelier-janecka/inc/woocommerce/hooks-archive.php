<?php
/**
 * WooCommerce Archive Hooks
 *
 * Ersetzt WooCommerce-Standard-Templates via Hooks statt Template-Overrides.
 * Zuständig für: Shop-Seite, Kategorie-Archive, Produkt-Karten.
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;

// ===========================================================================
// 1. LAYOUT: Wrapper anpassen
// ===========================================================================

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content',  'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar',             'woocommerce_get_sidebar', 10 );

add_action( 'woocommerce_before_main_content', 'janecka_wc_open_wrapper', 10 );
add_action( 'woocommerce_after_main_content',  'janecka_wc_close_wrapper', 10 );

// GZD Marken-Info im Loop entfernen (wird manuell über subtitle gerendert)
add_action( 'woocommerce_init', function() {
    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_gzd_template_loop_product_units', 9 );
} );

function janecka_wc_open_wrapper(): void {
	echo '<div class="wc-archive">';
	echo '<div class="container">';
	echo '<div class="wc-layout">';
	echo '<div class="wc-main">';
}

function janecka_wc_close_wrapper(): void {
	
	echo '</div><!-- .wc-layout -->';
	echo '</div><!-- .container -->';
	echo '</div><!-- .wc-archive -->';
}

function janecka_wc_render_mobile_filter_toggle(): void {
	?>
	<button class="wc-filter-toggle-mobile js-filter-toggle-mobile" type="button" aria-expanded="false" aria-controls="wc-filter-sidebar">
		<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="20" y2="12"/><line x1="12" y1="18" x2="20" y2="18"/></svg>
		<?php esc_html_e( 'Filter', 'juwelier-janecka' ); ?>
		<span class="wc-filter-toggle-mobile__count js-active-filter-count" hidden></span>
	</button>
	<?php
}

// ===========================================================================
// 2. LOOP HEADER: Anzahl + Sortierung + Produkt-Container
// ===========================================================================

remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count',     20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

// 1. Filter-Bar
add_action( 'woocommerce_before_shop_loop', function() {
    if ( function_exists( 'mlwf_render_filter_bar' ) ) {
        mlwf_render_filter_bar();
    } elseif ( function_exists( 'janecka_render_filter_bar' ) ) {
        janecka_render_filter_bar();
    }
}, 5 );

// 2. Loop-Header (Anzahl + Sortierung)
add_action( 'woocommerce_before_shop_loop', 'janecka_wc_loop_header', 10 );

function janecka_wc_loop_header(): void {
	?>
	<div class="wc-loop-header">
		<div class="wc-loop-header__count js-product-count">
			<?php woocommerce_result_count(); ?>
		</div>
		<div class="wc-loop-header__ordering">
			<?php woocommerce_catalog_ordering(); ?>
		</div>
	</div>
	<?php
}

// 3. Produkt-Container ÖFFNEN — nach Loop-Header (priority 10), vor ul.products
add_action( 'woocommerce_before_shop_loop', function() {
	echo '<div class="wc-products-container">';
}, 99 );

// 4. Produkt-Container SCHLIESSEN — nach Pagination (priority 10)
add_action( 'woocommerce_after_shop_loop', function() {
	echo '</div><!-- .wc-products-container -->';
}, 999 );

// ===========================================================================
// 3. PRODUKT-KARTE: Standard-Hooks entfernen + eigene ausgeben
// ===========================================================================

remove_action( 'woocommerce_before_shop_loop_item',       'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_after_shop_loop_item',        'woocommerce_template_loop_product_link_close', 5 );
remove_action( 'woocommerce_after_shop_loop_item',        'woocommerce_template_loop_add_to_cart', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_shop_loop_item_title',        'woocommerce_template_loop_product_title', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title',  'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_after_shop_loop_item_title',  'woocommerce_template_loop_price', 10 );

add_action( 'woocommerce_before_shop_loop_item', 'janecka_product_card_open', 5 );
add_action( 'woocommerce_after_shop_loop_item',  'janecka_product_card_actions_hook', 20 );
add_action( 'woocommerce_after_shop_loop_item',  'janecka_product_card_close', 999 );

function janecka_product_card_open(): void {
	global $product;
	$classes = [ 'product-card' ];
	if ( $product->is_on_sale() )    $classes[] = 'product-card--on-sale';
	if ( $product->is_featured() )   $classes[] = 'product-card--featured';
	if ( ! $product->is_in_stock() ) $classes[] = 'product-card--out-of-stock';
	?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<a class="product-card__link" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">

			<?php janecka_product_card_image(); ?>

			<div class="product-card__body">
				<?php janecka_product_card_badges(); ?>
				<?php janecka_product_card_subtitle(); ?> <!-- ← vor den Titel -->
				<h2 class="product-card__title"><?php the_title(); ?></h2>
				<div class="product-card__price">
					<?php woocommerce_template_loop_price(); ?>
				</div>
			</div>

		</a><!-- .product-card__link -->

	<?php
}

function janecka_product_card_actions_hook(): void {
        ?>
        <div class="product-card__actions">
                <?php janecka_product_card_actions(); ?>
        </div>
        <?php
}
function janecka_product_card_close(): void {
	echo '</div><!-- .product-card -->';
}

function janecka_product_card_image(): void {
    global $product;

    $image_id  = $product->get_image_id();

    // Immer eine URL liefern — Platzhalter wenn kein Bild
    $image_url = $image_id
        ? wp_get_attachment_image_url( $image_id, 'janecka-product-card' )
        : wc_placeholder_img_src( 'janecka-product-card' );

    // Sicherheitsnetz: falls wp_get_attachment_image_url false zurückgibt
    if ( ! $image_url ) {
        $image_url = wc_placeholder_img_src( 'janecka-product-card' );
    }

    $image_alt = $image_id
        ? trim( strip_tags( get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) )
        : get_the_title();
    ?>
    <div class="product-card__image-wrap">
        <img
            class="product-card__image"
            src="<?php echo esc_url( $image_url ); ?>"
            alt="<?php echo esc_attr( $image_alt ); ?>"
            loading="lazy"
            decoding="async"
        >
        <?php
        $gallery_ids = $product->get_gallery_image_ids();
        if ( ! empty( $gallery_ids[0] ) ) :
            $hover_url = wp_get_attachment_image_url( $gallery_ids[0], 'janecka-product-card' );
            if ( $hover_url ) :
        ?>
        <img
            class="product-card__image product-card__image--hover"
            src="<?php echo esc_url( $hover_url ); ?>"
            alt="" loading="lazy" decoding="async" aria-hidden="true"
        >
        <?php
            endif;
        endif;
        ?>
    </div>
    <?php
}

function janecka_product_card_badges(): void {
	global $product;
	?>
	<div class="product-card__badges" aria-label="<?php esc_attr_e( 'Produkt-Labels', 'juwelier-janecka' ); ?>">
		<?php if ( $product->is_on_sale() ) : ?>
			<span class="product-badge product-badge--sale"><?php esc_html_e( 'Sale', 'juwelier-janecka' ); ?></span>
		<?php endif; ?>
		<?php if ( $product->is_featured() ) : ?>
			<span class="product-badge product-badge--featured"><?php esc_html_e( 'Neu', 'juwelier-janecka' ); ?></span>
		<?php endif; ?>
		<?php if ( ! $product->is_in_stock() ) : ?>
			<span class="product-badge product-badge--out-of-stock"><?php esc_html_e( 'Ausverkauft', 'juwelier-janecka' ); ?></span>
		<?php endif; ?>
	</div>
	<?php
}

function janecka_product_card_subtitle(): void {
	global $product;
	$subtitle = '';
	foreach ( [ 'product_brand', 'pa_brand', 'pa_marke', 'pa_kollektion' ] as $attr ) {
		$terms = wc_get_product_terms( $product->get_id(), $attr, [ 'fields' => 'names' ] );
		if ( ! empty( $terms ) ) {
			$subtitle = implode( ', ', array_slice( $terms, 0, 2 ) );
			break;
		}
	}
	if ( $subtitle ) :
	?>
	<p class="product-card__subtitle"><?php echo esc_html( $subtitle ); ?></p>
	<?php
	endif;
}

function janecka_product_card_actions(): void {
	global $product;
	if ( in_array( $product->get_type(), [ 'variable', 'grouped' ], true ) ) :
	?>
	<a class="btn btn--outline btn--sm product-card__btn-details" href="<?php the_permalink(); ?>">
		<?php esc_html_e( 'Details ansehen', 'juwelier-janecka' ); ?>
	</a>
	<?php else :
		woocommerce_template_loop_add_to_cart( [
			'quantity' => 1,
			'class'    => 'btn btn--primary btn--sm product-card__btn-atc',
		] );
	endif;
	if ( function_exists( 'YITH_WCWL_Frontend' ) ) {
		echo do_shortcode( '[yith_wcwl_add_to_wishlist]' );
	}
}

// ===========================================================================
// 4. LOOP-GRID: CSS-Klassen
// ===========================================================================

add_filter( 'woocommerce_product_loop_start', 'janecka_wc_loop_start_html' );

function janecka_wc_loop_start_html( string $html ): string {
	return str_replace( 'class="products', 'class="products wc-products-grid', $html );
}

// ===========================================================================
// 5. BREADCRUMBS
// ===========================================================================

add_filter( 'woocommerce_breadcrumb_defaults', 'janecka_wc_breadcrumb_args' );

function janecka_wc_breadcrumb_args( array $args ): array {
	$args['delimiter']   = '<span class="breadcrumb__sep" aria-hidden="true">/</span>';
	$args['wrap_before'] = '<nav class="breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'juwelier-janecka' ) . '"><ol class="breadcrumb__list">';
	$args['wrap_after']  = '</ol></nav>';
	$args['before']      = '<li class="breadcrumb__item">';
	$args['after']       = '</li>';
	return $args;
}

// ===========================================================================
// 6. EMPTY SHOP
// ===========================================================================

remove_action( 'woocommerce_no_products_found', 'wc_no_products_found' );
add_action( 'woocommerce_no_products_found', 'janecka_wc_no_products_found' );

function janecka_wc_no_products_found(): void {
	?>
	<div class="wc-empty">
		<p class="wc-empty__text">
			<?php esc_html_e( 'Keine Produkte gefunden. Bitte versuche andere Filter.', 'juwelier-janecka' ); ?>
		</p>
		<a class="btn btn--outline" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
			<?php esc_html_e( 'Zum Shop', 'juwelier-janecka' ); ?>
		</a>
	</div>
	<?php
}

// ===========================================================================
// 7. PAGINATION
// ===========================================================================

remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
add_action( 'woocommerce_after_shop_loop', 'janecka_wc_pagination', 10 );

function janecka_wc_pagination(): void {
	woocommerce_pagination();
}

// ===========================================================================
// 8. SALE BADGE mit Prozentanzeige
// ===========================================================================

add_filter( 'woocommerce_sale_flash', function( $html, $post, $product ) {
	$percentage = '';
	if ( $product->is_type( 'variable' ) ) {
		$percentages = [];
		$prices      = $product->get_variation_prices();
		foreach ( $prices['price'] as $key => $price ) {
			if ( $prices['regular_price'][ $key ] !== $price ) {
				$percentages[] = round( ( ( $prices['regular_price'][ $key ] - $price ) / $prices['regular_price'][ $key ] ) * 100 );
			}
		}
		if ( ! empty( $percentages ) ) {
			$percentage = max( $percentages ) . '%';
		}
	} elseif ( $product->is_on_sale() ) {
		$regular = $product->get_regular_price();
		$sale    = $product->get_sale_price();
		if ( $regular && $sale ) {
			$percentage = round( ( ( $regular - $sale ) / $regular ) * 100 ) . '%';
		}
	}
	return $percentage ? '<span class="onsale">-' . $percentage . '</span>' : $html;
}, 10, 3 );

// ===========================================================================
// 9. CART FRAGMENTS
// ===========================================================================

add_filter( 'woocommerce_add_to_cart_fragments', function( $fragments ) {
	ob_start();
	?><span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span><?php
	$fragments['.cart-count'] = ob_get_clean();
	return $fragments;
} );
