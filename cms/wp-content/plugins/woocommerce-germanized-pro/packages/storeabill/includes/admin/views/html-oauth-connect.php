<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var bool   $is_manual
 * @var string $url
 * @var string $handler_label
 * @var string $description
 * @var string $authorization_input_name
 */
?>
<div class="sab-oauth-connect sab-oauth-wrapper">
	<p class="description sab-additional-desc"><?php echo wp_kses_post( $description ); ?></p>
	<a class="button button-primary sab-oauth-button" href="<?php echo esc_url( $url ); ?>" target="_blank"><?php printf( esc_html_x( 'Connect to %s', 'storeabill-core', 'woocommerce-germanized-pro' ), esc_html( $handler_label ) ); ?></a>

	<?php if ( $is_manual ) : ?>
		<div class="authorization-code" style="display: none">
			<label for="<?php echo esc_attr( $authorization_input_name ); ?>"><?php echo esc_html_x( 'Authorization Code', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $authorization_input_name ); ?>" id="<?php echo esc_attr( $authorization_input_name ); ?>" value="" />

			<button class="button button-primary sab-oauth-submit-code"><?php echo esc_html_x( 'Save', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></button>
		</div>
	<?php endif; ?>
</div>

