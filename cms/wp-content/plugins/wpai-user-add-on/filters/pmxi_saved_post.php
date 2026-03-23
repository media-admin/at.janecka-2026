<?php

/**
 * Removes the cache contents.
 *
 * @param $user_id
 * @param $xml
 * @param $update
 * @return void
 */
function pmui_pmxi_saved_post( $user_id, $xml, $update ) {

    if ( 'import_users' != wp_all_import_get_import_post_type( wp_all_import_get_import_id() ) ) return;

    wp_cache_delete( $user_id, 'users' );
}
