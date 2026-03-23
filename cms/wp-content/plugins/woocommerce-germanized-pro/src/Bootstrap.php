<?php
namespace Vendidero\Germanized\Pro;

use Vendidero\Germanized\Pro\Blocks\MultilevelCheckout;
use Vendidero\Germanized\Pro\Blocks\StoreApi\SchemaController;
use Vendidero\Germanized\Pro\Blocks\Assets;
use Vendidero\Germanized\Pro\Blocks\BlockTypesController;
use Vendidero\Germanized\Pro\Blocks\StoreApi\RoutesController;
use Vendidero\Germanized\Pro\Blocks\StoreApi\StoreApi;
use Vendidero\Germanized\Pro\Blocks\VatId;
use Vendidero\Germanized\Pro\Registry\Container;

/**
 * Takes care of bootstrapping the plugin.
 *
 * @since 2.5.0
 */
class Bootstrap {

	/**
	 * Holds the Dependency Injection Container
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Constructor
	 *
	 * @param Container $container  The Dependency Injection Container.
	 */
	public function __construct( $container ) {
		$this->container = $container;
		$this->init();

		do_action( 'woocommerce_gzdp_container_loaded' );
	}

	/**
	 * Init the package - load the blocks library and define constants.
	 */
	protected function init() {
		if ( ! Package::load_blocks() ) {
			return;
		}

		$this->register_dependencies();

		if ( did_action( 'woocommerce_blocks_loaded' ) ) {
			$this->load_blocks();
		} else {
			add_action(
				'woocommerce_blocks_loaded',
				function () {
					$this->load_blocks();
				}
			);
		}
	}

	protected function load_blocks() {
		add_filter(
			'__experimental_woocommerce_blocks_add_data_attributes_to_namespace',
			function ( $namespaces ) {
				return array_merge( $namespaces, array( 'woocommerce-germanized-pro' ) );
			}
		);

		$this->container->get( StoreApi::class )->init();
		$this->container->get( BlockTypesController::class );
		$this->container->get( VatId::class );

		if ( 'yes' === get_option( 'woocommerce_gzdp_checkout_enable' ) ) {
			$this->container->get( MultilevelCheckout::class );
		}
	}

	/**
	 * Register core dependencies with the container.
	 */
	protected function register_dependencies() {
		$this->container->register(
			Assets::class,
			function ( $container ) {
				return new Assets();
			}
		);
		$this->container->register(
			BlockTypesController::class,
			function ( $container ) {
				$assets = $container->get( Assets::class );

				return new BlockTypesController( $assets );
			}
		);
		$this->container->register(
			VatId::class,
			function ( $container ) {
				return new VatId();
			}
		);
		$this->container->register(
			MultilevelCheckout::class,
			function ( $container ) {
				return new MultilevelCheckout();
			}
		);
		$this->container->register(
			StoreApi::class,
			function ( $container ) {
				return new StoreApi();
			}
		);
		$this->container->register(
			SchemaController::class,
			function ( $container ) {
				return new SchemaController();
			}
		);
		$this->container->register(
			RoutesController::class,
			function ( $container ) {
				return new RoutesController( $container->get( SchemaController::class ) );
			}
		);
	}
}
