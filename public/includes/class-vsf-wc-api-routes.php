<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class that houses the REST routes to be registered.
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    VSF_WC_API
 * @subpackage VSF_WC_API/public
 * @author     SwiftCom <hello@swiftcom.co.za>
 */
class VSF_WC_API_Routes {

	/**
	 * The routes to be registered.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var string		$routes		The routes to be registered.
	 */
	private array $routes;

	/**
	 * Initialize the class and set the routes.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

    $this->routes = [
			[
				'endpoint' => '/products',
				'routes' => [
					[
						'method' => 'GET',
						'callback' => 'get_all_products',
					]
				]
			],
			[
				'endpoint' => '/products/(?P<id>\d+)',
				'routes' => [
					[
						'method' => 'GET',
						'callback' => 'get_single_product',
					]
				]
			],
			[
				'endpoint' => '/categories',
				'routes' => [
					[
						'method' => 'GET',
						'callback' => 'get_all_categories',
					]
				]
			],
			[
				'endpoint' => '/facets',
				'routes' => [
					[
						'method' => 'GET',
						'callback' => 'get_facets',
					]
				]
			],
			[
				'endpoint' => '/register',
				'routes' => [
					[
						'method' => 'POST',
						'callback' => 'register_user',
					]
				]
			],
			[
				'endpoint' => '/cart',
				'routes' => [
					[
						'method' => 'GET',
						'callback' => 'get_cart',
					],
					[
						'method' => 'POST',
						'callback' => 'update_cart',
					]
				]
			],
			[
				'endpoint' => '/address/billing',
				'routes' => [
					[
						'method' => 'GET',
						'callback' => 'get_billing_address',
					],
					[
						'method' => 'POST',
						'callback' => 'set_billing_address',
					]
				]
			],
			[
				'endpoint' => '/address/shipping',
				'routes' => [
					[
						'method' => 'GET',
						'callback' => 'get_shipping_address',
					],
					[
						'method' => 'POST',
						'callback' => 'set_shipping_address',
					]
				]
			],
			[
				'endpoint' => '/shipping',
				'routes' => [
					[
						'method' => 'POST',
						'callback' => 'get_shipping_methods',
					]
				]
			],
			[
				'endpoint' => '/payment',
				'routes' => [
					[
						'method' => 'GET',
						'callback' => 'get_payment_methods',
					],
					[
						'method' => 'POST',
						'callback' => 'make_payment',
					]
				]
			],
			[
				'endpoint' => '/order/(?P<id>\d+)',
				'routes' => [
					[
						'method' => 'POST',
						'callback' => 'get_order',
					]
				]
			],
		];

	}

	/**
	 * Returns the routes.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function get_routes() {
    return $this->routes;
  }

}