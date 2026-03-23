<?php
/**
 * Introduce special type for controllers which render pages inside admin area
 * 
 * @author Max Tsiplyakov <makstsiplyakov@gmail.com>
 */
abstract class PMUI_Controller_Admin extends PMUI_Controller {
	/**
	 * Admin page base url (request url without all get parameters but `page`)
	 * @var string
	 */
	public $baseUrl;
	/**
	 * Parameters which is left when baseUrl is detected
	 * @var array
	 */
	public $baseUrlParamNames = array('page', 'pagenum', 'order', 'order_by', 'type', 's', 'f');
	/**
	 * Whether controller is rendered inside wordpress page
	 * @var bool
	 */
	public $isInline = false;
	/**
	 * Constructor
	 */
	public function __construct() {
		
		$remove = array_diff(array_keys($_GET), $this->baseUrlParamNames);
		if ($remove) {
			$this->baseUrl = remove_query_arg($remove);
		} else {
			$this->baseUrl = $_SERVER['REQUEST_URI'];
		}
		parent::__construct();							

		wp_enqueue_style('pmui-admin-style', PMUI_ROOT_URL . '/static/css/admin.css');			

		wp_enqueue_script('pmui-script', PMUI_ROOT_URL . '/static/js/pmui.js', array('jquery'));		
		wp_enqueue_script('pmui-admin-script', PMUI_ROOT_URL . '/static/js/admin.js', array('jquery', 'pmxi-admin-script'));
		
		if ( class_exists( 'Woocommerce' ) ) {
			
			global $woocommerce;
			
			wp_enqueue_style('pmui-woo-style', $woocommerce->plugin_url() . '/assets/css/admin.css');
			
		}
	}	
	
	/**
	 * @see Controller::render()
	 */
	protected function render($viewPath = NULL)
	{
		// assume template file name depending on calling function
		if (is_null($viewPath)) {
			$trace = debug_backtrace();
			$viewPath = str_replace('_', '/', preg_replace('%^' . preg_quote(PMUI_Plugin::PREFIX, '%') . '%', '', strtolower($trace[1]['class']))) . '/' . $trace[1]['function'];
		}
		parent::render($viewPath);
	}
	
}