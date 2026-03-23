<?php

/**
 * Removes the cache contents.
 *
 * @param $user_id
 * @param $reassign
 * @param $user_object
 * @return void
 */
function pmui_deleted_user( $user_id, $reassign, $user_object ) {
    wp_cache_delete( $user_id, 'users' );
}
