<?php
/**
 * Such-Karte für Produkte: rendert die Shop-Kachel.
 *
 * Die Kachel (div.product-card mit Bild, Badges, Titel, Preis, GZD, Wunschliste)
 * entsteht über die Hooks in inc/woocommerce/hooks-archive.php auf
 * woocommerce_before/after_shop_loop_item. Das Standard-Loop-Template von
 * WooCommerce löst sie aus - die Suche zeigt damit exakt dieselbe Kachel wie der Shop.
 *
 * @package juwelier-janecka
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $product;
if ( ! $product instanceof WC_Product ) {
    $product = wc_get_product( get_the_ID() );
}
if ( ! $product || ! $product->is_visible() ) return;
?>
<ul class="products search-products">
    <?php wc_get_template_part( 'content', 'product' ); ?>
</ul>
