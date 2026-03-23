<?php
namespace Vendidero\StoreaBill\Utilities;

defined( 'ABSPATH' ) || exit;

class CacheHelper {

	/**
	 * Hook in methods.
	 */
	public static function init() {
	}

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @param  string $group Group of cache to get.
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		// Get cache key - uses cache key wc_orders_cache_prefix to invalidate when needed.
		$prefix = wp_cache_get( 'sab_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = microtime();
			wp_cache_set( 'sab_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'sab_cache_' . $prefix . '_';
	}

	/**
	 * Invalidate cache group.
	 *
	 * @param string $group Group of cache to clear.
	 * @since 3.9.0
	 */
	public static function invalidate_cache_group( $group ) {
		wp_cache_set( 'sab_' . $group . '_cache_prefix', microtime(), $group );
	}

	/**
	 * Prevent caching on certain pages
	 */
	public static function prevent_caching( $type = '' ) {
		if ( ! is_blog_installed() ) {
			return;
		}

		global $wpdb;
		$wpdb->flush();

		/**
		 * Calling wp_cache_flush_runtime() lets us clear the runtime cache without invalidating the external object
		 * cache, so we will always prefer this method (as compared to calling wp_cache_flush()) when it is available.
		 *
		 * However, this function was only introduced in WordPress 6.0. Additionally, the preferred way of detecting if
		 * it is supported changed in WordPress 6.1 so we use two different methods to decide if we should utilize it.
		 */
		$flushing_runtime_cache_explicitly_supported = function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_runtime' );
		$flushing_runtime_cache_implicitly_supported = ! function_exists( 'wp_cache_supports' ) && function_exists( 'wp_cache_flush_runtime' );

		if ( $flushing_runtime_cache_explicitly_supported || $flushing_runtime_cache_implicitly_supported ) {
			wp_cache_flush_runtime();
		} elseif ( apply_filters( 'storeabill_force_object_cache_flush', true, $type ) ) {
			wp_cache_flush();
		}

		self::set_nocache_constants();
		nocache_headers();
	}

	/**
	 * Set constants to prevent caching by some plugins.
	 *
	 * @param  mixed $return Value to return. Previously hooked into a filter.
	 * @return mixed
	 */
	public static function set_nocache_constants( $return = true ) {
		sab_maybe_define_constant( 'DONOTCACHEPAGE', true );
		sab_maybe_define_constant( 'DONOTCACHEOBJECT', true );
		sab_maybe_define_constant( 'DONOTCACHEDB', true );

		return $return;
	}
}
