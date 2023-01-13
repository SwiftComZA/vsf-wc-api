<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class responsible for registering this plugin's public facing REST routes.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    VSF_WC_API
 * @subpackage VSF_WC_API/public
 * @author     SwiftCom <hello@swiftcom.co.za>
 */
class VSF_WC_API_Route_Registrar {

	/**
	 * The api namespace of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var string		$namespace		The api namespace of this plugin.
	 */
	private string $namespace;

	/**
	 * The rest controller that holds the namespace and callback methods.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var string		$rest_controller		The rest controller that holds the namespace and callback methods.
	 */
  private VSF_WC_API_Rest_Controller $rest_controller;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      VSF_WC_API_Rest_Controller    $rest_controller       The rest controller.
	 */
	public function __construct( $namespace, $rest_controller  ) {

		$this->namespace       = $namespace;
    $this->rest_controller = $rest_controller;

	}

	/**
	 * Loops through $routes and registers the endpoints.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function register( $routes ) {

    foreach ( $routes as $route ) {
			register_rest_route( 
				$this->namespace, $route[ 'endpoint' ],
				array_map( [  $this, 'define_route' ], $route[ 'routes' ] )
			);
    }

	}


	/**
	 * Return the route, formatted.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_route( $route ) {
		return [
			'methods' => $route[ 'method' ],
			'callback' => [ $this->rest_controller, $route[ 'callback' ] ],
			'permission_callback' => '__return_true',
		];
	}

}