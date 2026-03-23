<?php
/**
 * The Template for displaying submit buttons for the multistep checkout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/checkout/multistep/submit.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://vendidero.de/doc/woocommerce-germanized/template-struktur-templates-im-theme-ueberschreiben
 * @package Germanized/Pro/Templates
 * @version 1.2.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_gzdp_before_step_submit_content', $step ); ?>

<div class="step-buttons step-buttons-<?php echo esc_attr( $step->get_id() ); ?>">
	<?php do_action( 'woocommerce_gzdp_step_submit_content', $step ); ?>

	<?php if ( $step->has_prev() ) : ?>
		<a class="prev-step-button step-trigger" id="prev-step-<?php echo esc_attr( $step->get_id() ); ?>" data-href="<?php echo esc_attr( $step->prev->get_id() ); ?>" href="#step-<?php echo esc_attr( $step->prev->get_id() ); ?>"><?php printf( esc_html_x( 'Back to step %s', 'multistep', 'woocommerce-germanized-pro' ), esc_html( $step->prev->number ) ); ?></a>
	<?php endif; ?>

	<?php if ( $step->has_next() ) : ?>
		<button class="button alt next-step-button<?php echo esc_attr( wc_gzdp_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_gzdp_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" type="submit" name="woocommerce_gzdp_checkout_next_step" id="next-step-<?php echo esc_attr( $step->get_id() ); ?>" data-current="<?php echo esc_attr( $step->get_id() ); ?>" data-next="<?php echo esc_attr( $step->next->get_id() ); ?>"><?php printf( esc_html_x( 'Continue with step %s', 'multistep', 'woocommerce-germanized-pro' ), esc_html( $step->next->number ) ); ?></button>
	<?php endif; ?>

	<div class="clear"></div>
</div>
