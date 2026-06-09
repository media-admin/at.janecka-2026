<?php
/**
 * Gutenberg Block: Hervorgehobene Produkte
 *
 * Server-side rendered Block — gibt bis zu 4 WooCommerce "featured" Produkte
 * im Standard-Produkt-Grid aus. Alle Product-Card-Hooks greifen automatisch.
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;

// Editor-Script registrieren & einreihen
add_action( 'enqueue_block_editor_assets', function (): void {
	wp_register_script(
		'janecka-featured-products-editor',
		get_template_directory_uri() . '/inc/blocks/featured-products/editor.js',
		[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ],
		filemtime( get_template_directory() . '/inc/blocks/featured-products/editor.js' ),
		true
	);
} );

add_action( 'init', 'janecka_register_featured_products_block' );

function janecka_register_featured_products_block(): void {
	register_block_type( 'janecka/featured-products', [
		'api_version'     => 2,
		'title'           => __( 'Hervorgehobene Produkte', 'juwelier-janecka' ),
		'description'     => __( 'Zeigt bis zu 4 hervorgehobene WooCommerce-Produkte im Produkt-Grid an.', 'juwelier-janecka' ),
		'category'        => 'janecka',
		'icon'            => 'star-filled',
		'editor_script'   => 'janecka-featured-products-editor',
		'attributes'      => [
			'count' => [
				'type'    => 'number',
				'default' => 4,
			],
		],
		'render_callback' => 'janecka_render_featured_products_block',
		'supports'        => [
			'align'  => [ 'wide', 'full' ],
			'anchor' => true,
		],
	] );
}

function janecka_render_featured_products_block( array $attributes ): string {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return '';
	}

	$count    = isset( $attributes['count'] ) ? (int) $attributes['count'] : 4;
	$shop_url = wc_get_page_permalink( 'shop' );

	$products = wc_get_products( [
		'featured' => true,
		'limit'    => $count,
		'status'   => 'publish',
		'orderby'  => 'date',
		'order'    => 'DESC',
	] );

	if ( empty( $products ) ) {
		return '';
	}

	ob_start();

	wc_set_loop_prop( 'columns', 4 );
	wc_set_loop_prop( 'is_shortcode', true );

	?>
	<section class="featured-products-block">

		<div class="featured-products-block__header">
			<a href="<?php echo esc_url( $shop_url ); ?>" class="featured-products-block__all-link">
				<?php esc_html_e( 'Alle Produkte zeigen', 'juwelier-janecka' ); ?> &rarr;
			</a>
		</div>

		<?php
		woocommerce_product_loop_start();

		foreach ( $products as $product ) {
			$GLOBALS['post'] = get_post( $product->get_id() );
			setup_postdata( $GLOBALS['post'] );
			wc_get_template_part( 'content', 'product' );
		}

		wp_reset_postdata();
		woocommerce_product_loop_end();
		?>

	</section>
	<?php

	return ob_get_clean();
}

add_filter( 'block_categories_all', function ( array $categories ): array {
	foreach ( $categories as $cat ) {
		if ( isset( $cat['slug'] ) && $cat['slug'] === 'janecka' ) {
			return $categories;
		}
	}
	array_unshift( $categories, [
		'slug'  => 'janecka',
		'title' => 'Janecka',
		'icon'  => null,
	] );
	return $categories;
} );