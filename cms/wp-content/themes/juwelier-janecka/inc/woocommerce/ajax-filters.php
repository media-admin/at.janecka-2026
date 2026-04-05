<?php
/**
 * AJAX-Produktfilter
 *
 * Verarbeitet Filter-Anfragen vom Frontend ohne Template-Overrides.
 * Registriert wp_ajax_ und wp_ajax_nopriv_ Handler.
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// AJAX Handler registrieren
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_janecka_filter_products',        'janecka_ajax_filter_products' );
add_action( 'wp_ajax_nopriv_janecka_filter_products', 'janecka_ajax_filter_products' );

function janecka_ajax_filter_products(): void {
	check_ajax_referer( 'janecka_filter_nonce', 'nonce' );
	error_log( '=== FILTER POST: ' . print_r( $_POST, true ) );

	$category_slug = sanitize_text_field( $_POST['category'] ?? '' );
	$attributes    = $_POST['attributes'] ?? [];
    $price_min     = ( isset( $_POST['price_min'] ) && $_POST['price_min'] !== '' ) ? (float) $_POST['price_min'] : null;
    $price_max     = ( isset( $_POST['price_max'] ) && $_POST['price_max'] !== '' ) ? (float) $_POST['price_max'] : null;
	$orderby       = sanitize_text_field( $_POST['orderby'] ?? 'menu_order' );
	$paged         = (int) ( $_POST['paged'] ?? 1 );

	// Tax Query aufbauen
	$tax_query = [ 'relation' => 'AND' ];

	// Kategorie einschränken
	if ( $category_slug ) {
		$tax_query[] = [
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $category_slug,
		];
	}

	// Attribute-Filter (Checkboxen)
	if ( is_array( $attributes ) ) {
		foreach ( $attributes as $taxonomy => $terms ) {
			$taxonomy = preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $taxonomy ) );
			$terms    = array_map( 'sanitize_text_field', (array) $terms );

		
			if ( empty( $terms ) ) {
				continue;
			}

			$tax_query[] = [
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $terms,
				'operator' => 'IN',
			];
		}
	}

	// Meta Query für Preis
	$meta_query = [ 'relation' => 'AND' ];
	if ( $price_min !== null || $price_max !== null ) {
		$meta_query[] = [
			'key'     => '_price',
			'value'   => [ $price_min ?? 0, $price_max ?? 999999 ],
			'compare' => 'BETWEEN',
			'type'    => 'NUMERIC',
		];
	}

	// Sortierung
	$orderby_map = [
		'menu_order' => [ 'orderby' => 'menu_order', 'order' => 'ASC' ],
		'price'      => [ 'orderby' => 'meta_value_num', 'meta_key' => '_price', 'order' => 'ASC' ],
		'price-desc' => [ 'orderby' => 'meta_value_num', 'meta_key' => '_price', 'order' => 'DESC' ],
		'popularity' => [ 'orderby' => 'meta_value_num', 'meta_key' => 'total_sales', 'order' => 'DESC' ],
		'rating'     => [ 'orderby' => 'meta_value_num', 'meta_key' => '_wc_average_rating', 'order' => 'DESC' ],
		'date'       => [ 'orderby' => 'date', 'order' => 'DESC' ],
	];

	$order_args = $orderby_map[ $orderby ] ?? $orderby_map['menu_order'];

	$args = array_merge( [
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => (int) get_option( 'posts_per_page_shop', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() ),
		'paged'          => $paged,
		'tax_query'      => $tax_query,
		'meta_query'     => $meta_query,
	], $order_args );

	$query = new WP_Query( $args );

	ob_start();

	if ( $query->have_posts() ) {
		woocommerce_product_loop_start();

		while ( $query->have_posts() ) {
			$query->the_post();
			wc_get_template_part( 'content', 'product' );
		}

		woocommerce_product_loop_end();

		if ( $query->max_num_pages > 1 ) {
			// Basis-URL aufbauen
			if ( $category_slug ) {
				$cat_term_for_url = get_term_by( 'slug', $category_slug, 'product_cat' );
				$base_url = $cat_term_for_url ? get_term_link( $cat_term_for_url ) : home_url( '/' );
			} else {
				$base_url = get_permalink( wc_get_page_id( 'shop' ) );
			}
			$base_url = trailingslashit( $base_url );

			// Aktive Filter-Params für Pagination-URLs aufbauen
			$filter_params = [];
			if ( is_array( $attributes ) ) {
				foreach ( $attributes as $tax => $vals ) {
					$tax  = preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $tax ) );
					$vals = array_map( 'sanitize_text_field', (array) $vals );
					if ( ! empty( $vals ) ) {
						$filter_params[ $tax ] = implode( ',', $vals );
					}
				}
			}
			if ( $price_min !== null ) $filter_params['price_min'] = $price_min;
			if ( $price_max !== null ) $filter_params['price_max'] = $price_max;
			if ( $orderby && $orderby !== 'menu_order' ) $filter_params['orderby'] = $orderby;

			$query_string = ! empty( $filter_params ) ? '?' . http_build_query( $filter_params ) : '';

			$links = paginate_links( [
				'base'      => $base_url . '%_%' . $query_string,
				'format'    => 'page/%#%/',
				'current'   => max( 1, $paged ),
				'total'     => $query->max_num_pages,
				'prev_text' => '&#8592;',
				'next_text' => '&#8594;',
				'type'      => 'list',
			] );
			if ( $links ) {
				echo '<nav class="woocommerce-pagination">' . $links . '</nav>';
			}
		}
	} else {
		echo '<p class="wc-no-products">' . esc_html__( 'Keine Produkte gefunden.', 'juwelier-janecka' ) . '</p>';
	}

	$html = ob_get_clean();
	wp_reset_postdata();

	wp_send_json_success( [
		'html'        => $html,
		'found_posts' => $query->found_posts,
		'max_pages'   => $query->max_num_pages,
		'current'     => $paged,
	] );
}

// ---------------------------------------------------------------------------
// AJAX: Preis-Bereich für aktuelle Kategorie ermitteln
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_janecka_get_price_range',        'janecka_ajax_get_price_range' );
add_action( 'wp_ajax_nopriv_janecka_get_price_range', 'janecka_ajax_get_price_range' );

function janecka_ajax_get_price_range(): void {
	check_ajax_referer( 'janecka_filter_nonce', 'nonce' );

	$category_slug = sanitize_text_field( $_POST['category'] ?? '' );

	global $wpdb;

	$where = '';
	if ( $category_slug ) {
		$term = get_term_by( 'slug', $category_slug, 'product_cat' );
		if ( $term ) {
			$term_ids = get_term_children( $term->term_id, 'product_cat' );
			$term_ids[] = $term->term_id;
			$term_ids_str = implode( ',', array_map( 'intval', $term_ids ) );

			$where = "AND p.ID IN (
				SELECT object_id FROM {$wpdb->term_relationships} tr
				JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE tt.term_id IN ({$term_ids_str})
			)";
		}
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
	$row = $wpdb->get_row( "
		SELECT MIN(CAST(pm.meta_value AS DECIMAL(10,2))) AS min_price,
		       MAX(CAST(pm.meta_value AS DECIMAL(10,2))) AS max_price
		FROM {$wpdb->postmeta} pm
		JOIN {$wpdb->posts} p ON pm.post_id = p.ID
		WHERE pm.meta_key = '_price'
		  AND p.post_type   = 'product'
		  AND p.post_status = 'publish'
		  {$where}
	" );
	// phpcs:enable

	wp_send_json_success( [
		'min' => (float) ( $row->min_price ?? 0 ),
		'max' => (float) ( $row->max_price ?? 10000 ),
	] );
}




/**
 * Zählt Produkte eines Terms innerhalb einer Kategorie.
 */
function janecka_count_term_in_category( int $term_id, string $taxonomy, array $cat_product_ids ): int {
    if ( empty( $cat_product_ids ) ) return 0;

    $term_product_ids = get_objects_in_term( $term_id, $taxonomy );
    if ( is_wp_error( $term_product_ids ) ) return 0;

    // Nur publizierte Produkte zaehlen
    $intersect = array_intersect( $cat_product_ids, $term_product_ids );
    if ( empty( $intersect ) ) return 0;

    return (int) ( new WP_Query( [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'post__in'       => array_map( 'intval', $intersect ),
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ] ) )->found_posts;
}

// ---------------------------------------------------------------------------
// Filter-Sidebar HTML ausgeben (kein Template-Override)
// ---------------------------------------------------------------------------

/**
 * Rendert die Filter-Sidebar für die aktuelle Kategorie.
 * Wird via Hook in hooks-archive.php eingebunden.
 */
function janecka_render_filter_bar(): void {
	if ( ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
		return;
	}

	$config          = janecka_get_current_category_filter_config();
	$attribute_slugs = $config['attributes'];
	$show_price      = $config['show_price'];
	$labels          = janecka_get_attribute_labels();
	$category_slug   = is_product_category() ? get_queried_object()->slug : '';
	$active_attrs    = [];

	// Produkt-IDs der aktuellen Kategorie einmalig laden
	$cat_product_ids = [];
	if ( $category_slug ) {
		$cat_term_obj = get_term_by( 'slug', $category_slug, 'product_cat' );
		if ( $cat_term_obj ) {
			$all_cat_ids = array_merge(
				[ $cat_term_obj->term_id ],
				get_term_children( $cat_term_obj->term_id, 'product_cat' )
			);
			foreach ( $all_cat_ids as $cid ) {
				$cat_product_ids = array_merge( $cat_product_ids, get_objects_in_term( $cid, 'product_cat' ) );
			}
			$cat_product_ids = array_unique( $cat_product_ids );
		}
	}


	foreach ( $attribute_slugs as $attr_slug ) {
		if ( ! empty( $_GET[ $attr_slug ] ) ) {
			$active_attrs[ $attr_slug ] = array_map( 'sanitize_text_field', explode( ',', $_GET[ $attr_slug ] ) );
		}
	}
	?>
	<div class="wc-filter-bar" id="wc-filter-sidebar" data-category="<?php echo esc_attr( $category_slug ); ?>">

		<form class="wc-filter-bar__form js-filter-form" novalidate>
			<input type="hidden" name="category" value="<?php echo esc_attr( $category_slug ); ?>">

			<div class="wc-filter-bar__groups">

				<?php if ( $show_price ) : ?>
				<div class="wc-filter-group wc-filter-group--price js-filter-group" data-filter-type="price">
					<button class="wc-filter-group__toggle" type="button" aria-expanded="false">
						<?php esc_html_e( 'Preis', 'juwelier-janecka' ); ?>
						<span class="wc-filter-group__icon" aria-hidden="true"></span>
					</button>
					<div class="wc-filter-group__dropdown" hidden>
						<div class="wc-price-slider js-price-slider"></div>
						<div class="wc-price-inputs">
							<input class="wc-price-input js-price-min" type="number" name="price_min" min="0" step="1">
							<span class="wc-price-separator">–</span>
							<input class="wc-price-input js-price-max" type="number" name="price_max" min="0" step="1">
						</div>
					</div>
				</div>
				<?php endif; ?>

				<?php foreach ( $attribute_slugs as $attr_slug ) :
					$taxonomy = get_taxonomy( $attr_slug );
					if ( ! $taxonomy ) continue;

					// Kategorie-spezifische Term-Counts berechnen
					if ( $category_slug ) {
						$cat_term = get_term_by( 'slug', $category_slug, 'product_cat' );
						$terms = get_terms( [
							'taxonomy'   => $attr_slug,
							'hide_empty' => true,
							'orderby'    => 'name',
							'object_ids' => get_objects_in_term( $cat_term->term_id, 'product_cat' ),
						] );
					} else {
						$terms = get_terms( [
							'taxonomy'   => $attr_slug,
							'hide_empty' => true,
							'orderby'    => 'name',
						] );
					}

					if ( is_wp_error( $terms ) || empty( $terms ) ) continue;

					$label       = $labels[ $attr_slug ] ?? $taxonomy->labels->name;
					$active_vals = $active_attrs[ $attr_slug ] ?? [];
					$group_id    = 'filter-drop-' . esc_attr( $attr_slug );
				?>
				<div class="wc-filter-group js-filter-group" data-filter-type="attribute" data-attribute="<?php echo esc_attr( $attr_slug ); ?>">
					<button class="wc-filter-group__toggle<?php echo ! empty( $active_vals ) ? ' has-active' : ''; ?>"
						type="button"
						aria-expanded="false"
						aria-controls="<?php echo esc_attr( $group_id ); ?>">
						<?php echo esc_html( $label ); ?>
						<?php if ( ! empty( $active_vals ) ) : ?>
							<span class="wc-filter-group__count"><?php echo count( $active_vals ); ?></span>
						<?php endif; ?>
						<span class="wc-filter-group__icon" aria-hidden="true"></span>
					</button>
					<div class="wc-filter-group__dropdown" id="<?php echo esc_attr( $group_id ); ?>" hidden>
						<ul class="wc-filter-checklist" role="group">
							<?php foreach ( $terms as $term ) :
								$input_id = 'filter-' . $attr_slug . '-' . $term->slug;
							?>
							<li class="wc-filter-checklist__item">
								<label class="wc-filter-option" for="<?php echo esc_attr( $input_id ); ?>">
									<input class="wc-filter-option__checkbox"
										id="<?php echo esc_attr( $input_id ); ?>"
										type="checkbox"
										name="attributes[<?php echo esc_attr( $attr_slug ); ?>][]"
										value="<?php echo esc_attr( $term->slug ); ?>"
										<?php checked( in_array( $term->slug, $active_vals, true ) ); ?>>
									<span class="wc-filter-option__label"><?php echo esc_html( $term->name ); ?></span>
									<?php
									$count = $category_slug
										? janecka_count_term_in_category( $term->term_id, $attr_slug, $cat_product_ids )
										: $term->count;
									if ( $count > 0 ) :
									?>
									<span class="wc-filter-option__count">(<?php echo absint( $count ); ?>)</span>
									<?php endif; ?>
								</label>
							</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>
				<?php endforeach; ?>

			</div><!-- .wc-filter-bar__groups -->

			<button class="wc-filter-bar__reset js-filter-reset" type="button" hidden>
				<?php esc_html_e( 'Zurücksetzen', 'juwelier-janecka' ); ?>
			</button>

		</form>

		<div class="wc-active-filters js-active-filters"></div>

	</div><!-- .wc-filter-bar -->
	<?php
}
