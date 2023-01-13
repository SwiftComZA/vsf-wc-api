<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    VSF_WC_API
 * @subpackage VSF_WC_API/public
 * @author     SwiftCom <hello@swiftcom.co.za>
 */
class VSF_WC_API_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The class responsible for registering the plugin's REST routes.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      VSF_WC_API_Route_Registrar    $route_registrar    Registers REST routes for this plugin.
	 */
	private $route_registrar;

	/**
	 * The priority for running a filter last.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @const    int    MAX_FILTER_PRIORITY    The priority for running a filter last.
	 */
	protected const MAX_FILTER_PRIORITY = 9999;

	/**
	 * Initialize the class, load dependencies, set members and instantiate classes.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->load_dependencies();

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->register_class_filters();

		$this->instantiate_classes();

	}

	/**
	 * Load the required dependencies for this class.
	 *
	 * Include the following files:
	 *
	 * - VSF_WC_API_Product_Methods. Defines methods used by the product endpoints callbacks.
	 * - VSF_WC_API_Cart_Methods. Defines methods used by the cart endpoints callbacks.
	 * - VSF_WC_API_Account_Methods. Defines methods used by the account endpoints callbacks.
	 * - VSF_WC_API_Payment_Methods. Defines methods used by the payment endpoints callbacks.
	 * - VSF_WC_API_Rest_Controller. Defines all the callback methods.
	 * - VSF_WC_API_Route_Registrar. Defines the class that registers the routes.
	 * - VSF_WC_API_Routes. Defines the routes.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class VSF_WC_API_Product_Methods, responsible for defining the
		 * methods used by the product endpoints callbacks.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/includes/class-vsf-wc-api-product-methods.php';

		/**
		 * The class VSF_WC_API_Cart_Methods, responsible for defining the
		 * methods used by the cart endpoints callbacks.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/includes/class-vsf-wc-api-cart-methods.php';

		/**
		 * The class VSF_WC_API_Account_Methods, responsible for defining the
		 * methods used by the account endpoints callbacks.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/includes/class-vsf-wc-api-account-methods.php';

		/**
		 * The class VSF_WC_API_Payment_Methods, responsible for defining the
		 * methods used by the payment endpoints callbacks.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/includes/class-vsf-wc-api-payment-methods.php';

		/**
		 * The class VSF_WC_API_Rest_Controller, responsible for defining the
		 * callback methods
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/includes/class-vsf-wc-api-rest-controller.php';

		/**
		 * The class VSF_WC_API_Route_Registrar, responsible for registering the routes.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/includes/class-vsf-wc-api-route-registrar.php';

		/**
		 * The class VSF_WC_API_Routes, responsible for defining the routes.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/includes/class-vsf-wc-api-routes.php';

	}

	/**
	 * Instantiates the classes used by this class.
	 * 
	 * The classes are instantiated through filters such that they can be extended
	 * and the instances can be swapped out for the extended instances.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function instantiate_classes() {

		/**
		 * The classname variable and instance of VSF_WC_API_Product_Methods
		 */
		$class_product_methods = apply_filters( 'vsf_wc_api_product_methods', 'VSF_WC_API_Product_Methods');

    $product_methods = new $class_product_methods();

		/**
		 * The classname variable and instance of VSF_WC_API_Cart_Methods
		 */
		$class_cart_methods = apply_filters( 'vsf_wc_api_cart_methods', 'VSF_WC_API_Cart_Methods');

    $cart_methods = new $class_cart_methods();

		/**
		 * The classname variable and instance of VSF_WC_API_Account_Methods
		 */
		$class_account_methods = apply_filters( 'vsf_wc_api_account_methods', 'VSF_WC_API_Account_Methods');

    $account_methods = new $class_account_methods();

		/**
		 * The classname variable and instance of VSF_WC_API_Payment_Methods
		 */
		$class_payment_methods = apply_filters( 'vsf_wc_api_payment_methods', 'VSF_WC_API_Payment_Methods');

    $payment_methods = new $class_payment_methods();

		/**
		 * The classname variable and instance of VSF_WC_API_Rest_Controller
		 */
		$class_rest_controller = apply_filters( 'vsf_wc_api_rest_controller', 'VSF_WC_API_Rest_Controller');

		$rest_controller = new $class_rest_controller(
			$product_methods,
			$payment_methods,
			$cart_methods,
			$account_methods
		);

		/**
		 * The instance of VSF_WC_API_Route_Registrar
		 */
		$namespace = $this->plugin_name . '/v' . intval( $this->version );

		$this->route_registrar = new VSF_WC_API_Route_Registrar( $namespace, $rest_controller );
	}

	/**
	 * Register the API's REST endpoints.
	 *
	 * @since    1.0.0
	 */
	public function register_api_routes() {

		$routes = new VSF_WC_API_Routes();	

		$routes_filtered = apply_filters( 'vsf_wc_api_routes', $routes->get_routes() );

		$this->route_registrar->register ( $routes_filtered );
		
	}

	/**
	 * Instantiates the classes used by this class.
	 * 
	 * The classes are instantiated through filters such that they can be extended
	 * and the instances can be swapped out for the extended instances.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_class_filters() {

		add_filter(
			'vsf_wc_api_product_methods',
			[ $this, 'vsf_wc_api_product_methods_filter_callback' ],
			self::MAX_FILTER_PRIORITY
		);
		
		add_filter(
			'vsf_wc_api_payment_methods',
			[ $this, 'vsf_wc_api_payment_methods_filter_callback' ],
			self::MAX_FILTER_PRIORITY
		);
		
		add_filter(
			'vsf_wc_api_cart_methods',
			[ $this, 'vsf_wc_api_cart_methods_filter_callback' ],
			self::MAX_FILTER_PRIORITY
		);
		
		add_filter(
		'vsf_wc_api_account_methods',
		[ $this, 'vsf_wc_api_account_methods_filter_callback' ],
		self::MAX_FILTER_PRIORITY
		);
		
		add_filter(
			'vsf_wc_api_rest_controller',
			[ $this, 'vsf_wc_api_rest_controller_filter_callback' ],
			self::MAX_FILTER_PRIORITY
		);
		
		add_filter(
			'vsf_wc_api_routes',
			[ $this, 'vsf_wc_api_routes_filter_callback' ],
			self::MAX_FILTER_PRIORITY
		);

	}

	/**
	 * The following functions are the callbacks registered
	 * above by register_class_filters(). The should be called
	 * last in the filter pipeline as they perform validation.
	 * 
	 * They validate that the classes are correct subclasses or extensions.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function vsf_wc_api_product_methods_filter_callback( $product_methods ) {

		return $this->validate_class_name( $product_methods, 'VSF_WC_API_Product_Methods' );
	}

	public function vsf_wc_api_cart_methods_filter_callback( $cart_methods ) {

		return $this->validate_class_name( $cart_methods, 'VSF_WC_API_Cart_Methods' );
	}

	public function vsf_wc_api_account_methods_filter_callback( $account_methods ) {

		return $this->validate_class_name( $account_methods, 'VSF_WC_API_Account_Methods' );
	}

	public function vsf_wc_api_payment_methods_filter_callback( $payment_methods ) {

		return $this->validate_class_name( $payment_methods, 'VSF_WC_API_Payment_Methods' );
	}

	public function vsf_wc_api_rest_controller_filter_callback( $rest_controller ) {

		return $this->validate_class_name( $rest_controller, 'VSF_WC_API_Rest_Controller' );
	}

	public function vsf_wc_api_routes_filter_callback( $routes ) {
		// TODO: Validate the $routes parameter

		return $routes;
	}

	/**
	 * This function checks if a classname is valid.
	 * 
	 * If it is, the classname is simply returned.
	 * If not, the relevant error is logged and the default classname 
	 * is returned.
	 * 
	 * They validate that the classes are correct subclasses or extensions.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function validate_class_name( $class_name, $default ) {

		/**
		 * $class_name must be a string.
		 */
		if ( !is_string( $class_name ) ) {

			error_log( "VSF-WC-API: Invalid {$default} class: Not a string. Loading default class." );

			return $default;
		}

		/**
		 * $class_name must not be empty.
		 */
		if ( empty( $class_name ) ) {

			error_log( "VSF-WC-API: Invalid {$default} class: Empty string. Loading default class." );

			return $default;
		}

		/**
		 * The class desribed by $class_name must exist.
		 */
		if ( !class_exists( $class_name ) ) {

			error_log( "VSF-WC-API: Invalid {$default} class: Class does not exist. Loading default class." );

			return $default;
		}

		/**
		 * ReflectionClass is used to get info on $class_name.
		 */
		$reflection = new ReflectionClass( $class_name );

		/**
		 * $class_name must either be the class itself or a subclass or extension.
		 */
		if ( !$reflection->getName() === $default && !$reflection->isSubclassOf( $default ) ) {

			error_log( "VSF-WC-API: Invalid {$default} class: Incorrect subclass or extension. Loading default class." );

			return $default;
		}

		return $class_name;
	}
}
