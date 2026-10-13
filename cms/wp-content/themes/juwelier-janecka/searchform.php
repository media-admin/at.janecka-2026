<?php
/**
 * Suchformular (get_search_form()): Seitenkopf der Suchergebnisse, 404, Widgets.
 *
 * Eckig, Feld und Lupen-Button in einer Zeile (passt auch auf schmale Bildschirme).
 * Layout und Optik: assets/src/scss/pages/_post-archive.scss (.serp-search).
 *
 * @package juwelier-janecka
 */

$serp_cfg         = class_exists( 'MediaLab_Search_Settings' ) ? MediaLab_Search_Settings::get() : array();
$serp_placeholder = (string) ( $serp_cfg['text']['placeholder'] ?? '' );
$serp_submit      = (string) ( $serp_cfg['text']['aria_submit'] ?? '' );

if ( $serp_placeholder === '' ) {
    $serp_placeholder = __( 'Suche', 'juwelier-janecka' );
}
if ( $serp_submit === '' ) {
    $serp_submit = __( 'Suchen', 'juwelier-janecka' );
}

$serp_id = wp_unique_id( 'serp-search-' );
?>
<form role="search" method="get" class="serp-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <label class="screen-reader-text" for="<?php echo esc_attr( $serp_id ); ?>"><?php esc_html_e( 'Suche nach:', 'juwelier-janecka' ); ?></label>
    <input type="search" id="<?php echo esc_attr( $serp_id ); ?>" class="serp-search__field" name="s"
           value="<?php echo esc_attr( get_search_query( false ) ); ?>"
           placeholder="<?php echo esc_attr( $serp_placeholder ); ?>">
    <button type="submit" class="serp-search__submit" aria-label="<?php echo esc_attr( $serp_submit ); ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
    </button>
</form>
