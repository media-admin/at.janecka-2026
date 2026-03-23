<?php
/**
 * Document eInvoice HTML.
 */
defined( 'ABSPATH' ) || exit;
?>

<script type="text/template" id="tmpl-storeabill-admin-modal-create-einvoice-<?php echo esc_attr( $document->get_id() ); ?>">
	<div class="wc-backbone-modal storeabill-admin-document-modal storeabill-admin-modal-create-einvoice">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_x( 'Create eInvoice', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text">Close modal panel</span>
					</button>
				</header>
				<article class="storeabill-modal-content" data-document-type="<?php echo esc_attr( $document->get_type() ); ?>">
					<div class="notice-wrapper"></div>

					<form action="" method="post" class="storeabill-create-einvoice-form">
						<div class="storeabill-create-einvoice"></div>
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php echo esc_html_x( 'Create', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
