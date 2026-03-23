<?php
/**
 * The Template for displaying allergenic for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/single-product/food/allergenic.php.
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
$heading = apply_filters( 'woocommerce_gzd_product_allergenic_heading', __( 'Allergenic', 'woocommerce-germanized-pro' ) );
?>
<?php if ( wc_gzd_get_gzd_product( $product )->get_formatted_allergenic() ) : ?>
	<?php if ( isset( $print_title ) && $print_title && $heading ) : ?>
		<h2 class="wc-gzd-allergenic-heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<div class="wc-gzd-allergenic wc-gzd-product-food-information">
		<?php echo wp_kses_post( wpautop( wc_gzd_get_gzd_product( $product )->get_formatted_allergenic() ) ); ?>
	</div>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
	<?php if ( isset( $print_title ) && $print_title && $heading ) : ?>
		<h2 class="wc-gzd-allergenic-heading wc-gzd-additional-info-placeholder" aria-hidden="true"></h2>
	<?php endif; ?>
	<div class="wc-gzd-allergenic wc-gzd-product-food-information wc-gzd-additional-info-placeholder" aria-hidden="true"></div>
<?php endif; ?>
