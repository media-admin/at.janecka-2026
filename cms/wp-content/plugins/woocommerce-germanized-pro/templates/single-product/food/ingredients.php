<?php
/**
 * The Template for displaying ingredients for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/single-product/food/ingredients.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 4.3.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $product;
$heading = apply_filters( 'woocommerce_gzd_product_ingredients_heading', __( 'Ingredients', 'woocommerce-germanized-pro' ) );
?>
<?php if ( wc_gzd_get_gzd_product( $product )->get_formatted_ingredients() ) : ?>
	<?php if ( isset( $print_title ) && $print_title && $heading ) : ?>
		<h2 class="wc-gzd-ingredients-heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<div class="wc-gzd-ingredients wc-gzd-product-food-information">
		<?php echo wp_kses_post( wc_gzd_get_gzd_product( $product )->get_formatted_ingredients() ); ?>
	</div>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
	<?php if ( isset( $print_title ) && $print_title && $heading ) : ?>
		<h2 class="wc-gzd-ingredients-heading wc-gzd-additional-info-placeholder" aria-hidden="true"></h2>
	<?php endif; ?>
	<div class="wc-gzd-ingredients wc-gzd-product-food-information wc-gzd-additional-info-placeholder" aria-hidden="true"></div>
<?php endif; ?>
