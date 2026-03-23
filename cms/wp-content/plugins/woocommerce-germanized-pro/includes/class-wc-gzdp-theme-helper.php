<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Vendidero\Germanized\Shopmarks;

class WC_GZDP_Theme_Helper {

	protected static $_instance = null;

	public $themes = array();

	/**
	 * @var WC_GZDP_Theme
	 */
	public $theme;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		$this->themes = array(
			'virtue',
			'flatsome',
			'enfold',
			'storefront',
			'shopkeeper',
			'astra',
			'twentytwentytwo',
			'twentytwentythree',
			'oceanwp',
		);

		$current = wp_get_theme();

		if ( in_array( $current->get_template(), $this->themes, true ) ) {
			$this->load_theme( $current->get_template() );
		}

		add_action( 'switch_theme', array( $this, 'refresh_shopmark_options' ), 10, 3 );
		add_filter( 'woocommerce_gzd_admin_notes', array( $this, 'register_shopmark_note' ), 10 );
	}

	public function register_shopmark_note( $notes ) {
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/notes/class-wc-gzdp-admin-note-refreshed-theme-shopmarks.php';

		$notes[] = 'WC_GZDP_Admin_Note_Refreshed_Theme_Shopmarks';

		return $notes;
	}

	/**
	 * After switching theme: Loop through all shopmarks and make sure that custom-theme-hooks
	 * are removed and default hooks are loaded instead.
	 *
	 * @param string $new_name
	 * @param WP_Theme $new_theme
	 * @param WP_Theme $old_theme
	 */
	public function refresh_shopmark_options( $new_name, $new_theme, $old_theme ) {
		/**
		 * Either the new or the old theme needs to be explicitly supported.
		 * Otherwise no reset is necessary.
		 */
		if ( ! in_array( $old_theme->get_template(), $this->themes, true ) && ! in_array( $new_theme->get_template(), $this->themes, true ) ) {
			return;
		}

		/**
		 * Child themes
		 */
		if ( $new_theme->get_template() === $old_theme->get_template() ) {
			return;
		}

		$reset_shopmarks = false;

		if ( $this->theme && $this->theme->name === $old_theme->get_template() && $this->theme->has_custom_shopmarks() ) {
			$this->theme->remove_shopmark_filters();

			$reset_shopmarks = true;
		}

		/**
		 * Load new theme filters
		 */
		$this->load_theme( $new_theme->get_template() );

		if ( $this->theme && $this->theme->has_custom_shopmarks() ) {
			$reset_shopmarks = true;
		}

		if ( $reset_shopmarks ) {
			delete_option( 'woocommerce_gzdp_refreshed_theme_shopmarks' );

			$shopmarks_updated = false;

			/**
			 * Force re-registering shopmarks to re-load default filters.
			 */
			\Vendidero\Germanized\Shopmarks::register();

			/**
			 * Reset shopmark default filter + priority
			 */
			foreach ( Shopmarks::get_locations() as $location => $location_data ) {
				$shopmarks = Shopmarks::get( $location );

				foreach ( $shopmarks as $shopmark ) {
					$filter               = $shopmark->get_default_filter();
					$current_filter_value = get_option( $shopmark->get_option_name( 'filter' ) );
					$current_is_no_std    = ! empty( $current_filter_value ) && substr( $current_filter_value, 0, 12 ) !== 'woocommerce_';
					$default_is_no_std    = substr( $filter, 0, 12 ) !== 'woocommerce_';

					/**
					 * Do only reset in case the current shopmark is not a standard filter.
					 */
					if ( $current_is_no_std || $default_is_no_std ) {
						$shopmarks_updated = true;

						update_option( $shopmark->get_option_name( 'filter' ), $filter );
						update_option( $shopmark->get_option_name( 'priority' ), $shopmark->get_default_priority() );
					}
				}
			}

			if ( $shopmarks_updated ) {
				if ( $note = WC_GZD_Admin_Notices::instance()->get_note( 'refreshed_theme_shopmarks' ) ) {
					$note->reset();

					update_option( 'woocommerce_gzdp_refreshed_theme_shopmarks', 'yes' );
				}
			}
		}
	}

	public function load_theme( $template ) {
		if ( ! in_array( $template, $this->themes, true ) ) {
			return false;
		}

		$classname = 'WC_GZDP_Theme_' . str_replace( '-', '_', ucfirst( sanitize_title( $template ) ) );

		if ( class_exists( $classname ) && apply_filters( 'woocommerce_gzdp_load_theme_compatibility', true, $template ) ) {
			$this->theme = new $classname( $template );
		}
	}
}

return WC_GZDP_Theme_Helper::instance();
