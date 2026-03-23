<?php
/**
 * The Template for displaying customer data on the third step of the multistep checkout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/checkout/multistep/data.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://vendidero.de/doc/woocommerce-germanized/template-struktur-templates-im-theme-ueberschreiben
 * @package Germanized/Pro/Templates
 * @version 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$payment_gateway = false;
$gateways        = WC()->payment_gateways()->get_available_payment_gateways();
$method          = WC()->session->get( 'chosen_payment_method' );

if ( $method && isset( $gateways[ $method ] ) ) {
	$payment_gateway = $gateways[ $method ];
}
?>
<div class="woocommerce-gzdp-checkout-verify-data">
	<div class="col2-set addresses">
		<div class="col-1">
			<header class="title">
					<h4><?php echo esc_html_x( 'Billing Details', 'multistep', 'woocommerce-germanized-pro' ); ?></h4>
			</header>

			<address>
				<?php if ( ! $multistep->get_formatted_billing_address() ) : ?>
					<?php echo esc_html_x( 'N/A', 'multistep', 'woocommerce-germanized-pro' ); ?>
				<?php else : ?>
					<?php echo wp_kses_post( $multistep->get_formatted_billing_address() ); ?>
				<?php endif; ?>

				<?php if ( WC()->checkout->get_value( 'billing_email' ) ) : ?>
					<br/><?php echo esc_html( WC()->checkout->get_value( 'billing_email' ) ); ?>
				<?php endif; ?>

				<?php do_action( 'woocommerce_gzdp_multistep_confirmation_after_billing_address', $multistep ); ?>
			</address>

			<p><a href="#step-address" class="edit step-trigger" data-href="address"><?php echo esc_html_x( 'edit', 'multistep', 'woocommerce-germanized-pro' ); ?></a></p>

			<?php if ( $payment_gateway ) : ?>
				<header class="title">
					<h4><?php echo esc_html_x( 'Payment Method', 'multistep', 'woocommerce-germanized-pro' ); ?></h4>
				</header>
				<p class="wc-gzdp-payment-gateway"><?php echo esc_html( $payment_gateway->get_title() ); ?></p>

				<p><a href="#step-payment" class="edit step-trigger" data-href="payment"><?php echo esc_html_x( 'edit', 'multistep', 'woocommerce-germanized-pro' ); ?></a></p>
			<?php endif; ?>
		</div><!-- /.col-1 -->

		<div class="col-2">
			<header class="title">
				<h4><?php echo esc_html_x( 'Shipping Address', 'multistep', 'woocommerce-germanized-pro' ); ?></h4>
			</header>
			<address>
				<?php if ( ! $multistep->get_formatted_shipping_address() ) : ?>
					<?php echo esc_html_x( 'Same as billing address', 'multistep', 'woocommerce-germanized-pro' ); ?>
				<?php else : ?>
					<?php echo wp_kses_post( $multistep->get_formatted_shipping_address() ); ?>
				<?php endif; ?>

				<?php do_action( 'woocommerce_gzdp_multistep_confirmation_after_shipping_address', $multistep ); ?>
			</address>
			<?php if ( $multistep->supports_shipping_address() ) : ?>
				<p>
					<a href="#step-address" class="edit step-trigger" data-href="address"><?php echo esc_html_x( 'edit', 'multistep', 'woocommerce-germanized-pro' ); ?></a>
				</p>
			<?php endif; ?>
		</div><!-- /.col-2 -->

		<?php do_action( 'woocommerce_gzdp_multistep_confirmation_after_cols', $multistep ); ?>
	</div><!-- /.col2-set -->
</div>
