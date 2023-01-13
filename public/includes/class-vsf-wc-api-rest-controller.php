<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Defines the callback methods for the REST routes.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    VSF_WC_API
 * @subpackage VSF_WC_API/public
 * @author     SwiftCom <hello@swiftcom.co.za>
 */
class VSF_WC_API_Rest_Controller {

  /**
   * Defines methods used by the product endpoints callbacks.
   *
   * @since    1.0.0
   * @access   protected
   * @var string		
   */
  protected VSF_WC_API_Product_Methods $product_methods;

  /**
   * Defines methods used by the cart endpoints callbacks.
   *
   * @since    1.0.0
   * @access   protected
   * @var string		
   */
  protected VSF_WC_API_Cart_Methods $cart_methods;

  /**
   * Defines methods used by the account endpoints callbacks.
   *
   * @since    1.0.0
   * @access   protected
   * @var string		
   */
  protected VSF_WC_API_Account_Methods $account_methods;

  /**
   * Defines methods used by the payment endpoints callbacks.
   *
   * @since    1.0.0
   * @access   protected
   * @var string		
   */
  protected VSF_WC_API_Payment_Methods $payment_methods;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct(
      $product_methods,
      $payment_methods,
      $cart_methods,
      $account_methods
    ) {

    $this->product_methods = $product_methods;
    $this->payment_methods = $payment_methods;
    $this->cart_methods    = $cart_methods;
    $this->account_methods = $account_methods;

	}

	/**
	 * The GET to the /products endpoint - NOT AUTHENTICATED.
   * This callback returns product data according to the query parameters.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_all_products( $request ) {

    // Prepare the WC query arguments
    $query_args = [
      'status'   => 'publish',
      'paginate' => $request[ 'paginate' ] && $request[ 'paginate' ] == 'true' ?? false,
      'page'     => $request[ 'page' ] ?? 1,
      'limit'    => $request[ 'limit' ] ?? 20,
      'orderby'  => $request[ 'orderby' ] ?? 'id',
      'order'    => $request[ 'order' ] ?? 'DESC',
      'category' => $request[ 'categories' ] ? explode( ',', sanitize_text_field( $request[ 'categories' ] ) ) : [],
      'include'  => $request[ 'id' ] ? [ $request[ 'id' ] ] : []
    ];

    // Add all query parameters that starts with pa_ to the get products query
    // This is so that the products can be filtered by any global attributes
    foreach ( $request->get_query_params() as $key => $value ) {
      if ( str_starts_with( sanitize_text_field( $key ), 'pa_' ) ) {
        $query_args[ sanitize_text_field( $key ) ] = explode( ',', sanitize_text_field( $value ) );
      }
    }

    $query_args_filtered = apply_filters( 'vsf_wc_api_get_all_products_query_args', $query_args );

    $return_data = $this->product_methods->product_query( $query_args_filtered );

    $return_data_filtered = apply_filters( 'vsf_wc_api_get_all_products_return_data', $return_data );

    $response = new WP_REST_Response( $return_data_filtered );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );
    
    return $response;

  }

	/**
	 * The GET to the /products/{id} endpoint - NOT AUTHENTICATED.
   * This callback returns a single product's data by ID.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_single_product( $request ) {

    // Prepare the WC query arguments
    $query_args = [
      'status'   => 'publish',
      'paginate' => false,
      'include'  => [ $request[ 'id' ] ?? -1 ],
      'orderby'  => $request[ 'orderby' ] ?? 'id',
    ];

    $query_args_filtered = apply_filters( 'vsf_wc_api_get_single_product_query_args', $query_args );

    $return_data = $this->product_methods->product_query( $query_args_filtered );

    $return_data_filtered = apply_filters( 'vsf_wc_api_get_single_product_return_data', $return_data );

    $response = new WP_REST_Response( $return_data );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );
    
    return $response;

  }

	/**
	 * The GET to the /categories endpoint - NOT AUTHENTICATED.
   * This callback returns category data according to the query parameters.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_all_categories( $request ) {
    // Prepare category query
    $query_args = [
      'taxonomy'   => 'product_cat',
      'status'     => 'publish',
      'hide_empty' => true,
      'offset'     => $request[ 'page' ] ?? 0,
      'number'     => $request[ 'limit' ] ?? 0,
      'menu_order' => true
    ];

    $query_args_filtered = apply_filters( 'vsf_wc_api_get_all_categories_query_args', $query_args );

    $items = get_terms( $query_args_filtered );

    $return_data = [];

    foreach ( $items as $category ) {
      $return_data[] = [
        'type'               => 'category',
        'id'                 => $category->term_id,
        'title'              => htmlspecialchars_decode( $category->name ),
        'description'        => $category->description,
        'slug'               => $category->slug,
        'count'              => $category->count,
        'parent_id'          => $category->parent,
        'category_slug_path' => get_term_parents_list(
          $category->term_id,
          'product_cat',
          [
            'format'    => 'slug',
            'separator' => '/',
            'link'      => false
          ]
        ),
      ];
    }

    $return_data_filtered = apply_filters( 'vsf_wc_api_get_all_categories_return_data', $return_data );

    $response = new WP_REST_Response( $return_data_filtered );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );

    return $response;
  }

	/**
	 * The GET to /facets endpoint - NOT AUTHENTICATED.
   * This callback returns facet data according to the query parameters.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_facets( $request ) {
    // Prepare the WC query arguments
    $query_args = [
      'status'   => 'publish',
      'limit'    => -1,
      'category' => $request[ 'categories' ] ?? [],
      'include'  => $request[ 'id' ] ? [ $request[ 'id' ] ] : []
    ];

    $filters = [];

    // Add all query parameters that starts with pa_ to the get products query
    // This is so that the products can be filtered by any global attributes
    foreach ( $request->get_query_params() as $key => $value ) { 
      if ( str_starts_with( sanitize_text_field( $key ), 'pa_' ) ) {
        $filters[ $key ] = explode( ',', sanitize_text_field( $value ) );
      }
    }

    // If only one filter is selected, still show all values for that filter
    if ( sizeof( $filters ) === 1 ) {
      foreach ( $filters as $key => $value ) { 
        $query_args[ $key ] = [];
        $terms = get_terms( $key );
        foreach ( $terms as $term ) {
          array_push( $query_args[ $key ], $term->slug );
        }
      }
    }
    // Else, show only availble filter values
    else {   
      foreach ( $filters as $key => $value ) { 
        $query_args[ $key ] = $value;
      }
    }
    
    $query_args_filtered = apply_filters( 'vsf_wc_api_get_facets_query_args', $query_args );

    $products = wc_get_products( $query_args_filtered) ;
    $facets = [];
    
    // Get facet data from attributes
    foreach ( $products as $product ) {
      $attributes = $product->get_attributes();

      foreach ( $attributes as $attribute => $v ) {
        $values = $v->get_slugs();
        $terms = $v->get_terms();

        if ( $terms != null ) {
          if ( !array_key_exists( $attribute, $facets ) ) {
            $facets[ $attribute ][ 'title' ]  = wc_attribute_label( $attribute );
            $facets[ $attribute ][ 'id' ]     = $attribute;
            $facets[ $attribute ][ 'values' ] = [];
            foreach ( $values as $index => $value ) {
              $facets[ $attribute ][ 'values' ][ $value ] = [ 'title' => $terms[ $index ]->name, 'count' => 1 ];
            }
          }
          else {
            foreach ( $values as $index => $value ) {
              if ( !array_key_exists( $value, $facets[ $attribute ][ 'values' ] ) ) {
                $facets[ $attribute ][ 'values' ][ $value ] = [ 'title' => $terms[ $index ]->name, 'count' => 1 ];
              }
              else {
                ++$facets[ $attribute ][ 'values' ][ $value ][ 'count' ];
              }
            }
          }
        }
      }
    }

    $facets_filtered = apply_filters( 'vsf_wc_api_get_facets_return_data', $facets );

    $response = new WP_REST_Response( $facets );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );

    return $response;

  }

	/**
	 * The POST to the /register endpoint - NOT AUTHENTICATED.
   * This callback registers a new customer user.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function register_user( $request ) {
    // Verify request data
    if ( !sanitize_email( $request[ 'email' ] ) ) {
      return new WP_Error( 'bad_request', 'Bad Request, email invalid', [ 'status' => 400 ] );
    }
    if ( !$request[ 'password' ] ) {
      return new WP_Error( 'bad_request', 'Bad Request, password field missing', [ 'status' => 400 ] );
    }
    if ( !$request[ 'firstName' ] ) {
      return new WP_Error( 'bad_request', 'Bad Request, firstName field missing', [ 'status' => 400 ] );
    }
    if ( !$request[ 'lastName' ] ) {
      return new WP_Error( 'bad_request', 'Bad Request, lastName field missing', [ 'status' => 400 ] );
    }

    // Verify this is a new user
    $user = get_user_by( 'email', $request[ 'email' ] );
    if ( !empty( $user ) ) {
      return new WP_Error( 'already_exists', 'Bad Request, user already exists', [ 'status' => 400 ] );
    }

    // Register new customer
    $return_data = $this->account_methods->register_user( $request );

    $return_data_filtered = apply_filters( 'vsf_wc_api_register_user_return_data', $return_data );

    $response = new WP_REST_Response( $return_data_filtered );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );

    return $response;
  }

	/**
	 * The GET to the /cart endpoint - AUTHENTICATED.
   * This callback returns a customer's cart.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_cart() {
    // Load cart libraries
    wc_load_cart();

    // Return cart structure
    $return_data = $this->cart_methods->get_cart();

    $return_data_filtered = apply_filters( 'vsf_wc_api_get_cart_return_data', $return_data );

    $response = new WP_REST_Response( $return_data_filtered );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );

    return $response;
  }

	/**
	 * The POST to the /cart endpoint - AUTHENTICATED.
   * This callback updates a customer's cart
   * by adding a product, deleting a product or updating a 
   * product's quantity, depending on the body parameter.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function update_cart( $request ) {
    // Load cart libraries
    wc_load_cart();

    // Verify cart method
    if ( !isset( $request[ 'cartMethod' ] ) ) {
      return new WP_Error( 'bad_request', 'Bad request, no cartMethod variable provided (either add, remove or update)', [ 'status' => 400 ] );
    }
    
    // Verify quantity variable if provided
    $quantity = 1;
    if ( isset( $request[ 'quantity' ] ) ) {
      $quantity = intval( $request[ 'quantity' ] );
    }

    // Add to cart
    if ( $request[ 'cartMethod' ] == 'add' ) {
      // Verify product ID
      if ( !isset( $request[ 'id' ] ) ) {
        return new WP_Error( 'bad_request', 'Bad request, no product id provided', [ 'status' => 400 ] );
      }

      $return_data = $this->cart_methods->add_to_cart( $request[ 'id' ], $quantity );
  
      $return_data_filtered = apply_filters( 'vsf_wc_api_add_to_cart_return_data', $return_data );
  
      $response = new WP_REST_Response( $return_data_filtered );
      
      $response->set_headers( [
        'Content-Type' => 'application/json'
      ] );
  
      return $response;
    }

    // Verify cart item key
    if ( !isset( $request[ 'key' ] ) ) {
      return new WP_Error( 'bad_request', 'Bad request, no cart item key provided', [ 'status' => 400 ] );
    }

    // Update product quantity
    if ( $request[ 'cartMethod' ] == 'update' ) {
      if ( !isset( $request[ 'quantity' ] ) ) {
        return new WP_Error( 'bad_request', 'Bad request, no quantity provided for cart update', [ 'status' => 400 ] );
      }

      $return_data = $this->cart_methods->update_cart( $request[ 'key' ], $quantity );
  
      $return_data_filtered = apply_filters( 'vsf_wc_api_update_cart_return_data', $return_data );
  
      $response = new WP_REST_Response( $return_data_filtered );
      
      $response->set_headers( [
        'Content-Type' => 'application/json'
      ] );
  
      return $response;
    }

    // Remove an item from the cart
    if ( $request[ 'cartMethod' ] == 'remove' ) {

      $return_data = $this->cart_methods->remove_from_cart( $request[ 'key' ] );
  
      $return_data_filtered = apply_filters( 'vsf_wc_api_remove_from_cart_return_data', $return_data );
  
      $response = new WP_REST_Response( $return_data_filtered );
      
      $response->set_headers( [
        'Content-Type' => 'application/json'
      ] );
  
      return $response;
    }

    // Unkown cart method
    return new WP_Error( 'bad_request', 'Bad request, unknown cartMethod provided (should be add, remove or update)', [ 'status' => 400 ] );
  }

	/**
	 * The GET to the /address/billing endpoint - AUTHENTICATED.
   * This callback returns a customer's billing address.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_billing_address() {
    $user = wp_get_current_user();
    if ( !$user->exists() ) {
      return new WP_Error( 'no_user', 'You have to be logged in to fetch your address.', [ 'status' => 403 ] );
    }

    $user_id = get_current_user_id();
    $customer = new WC_Customer( $user_id );

    $return_data = $this->account_methods->get_customer_billing_address( $customer );

    $return_data_filtered = apply_filters( 'vsf_wc_api_get_billing_address_return_data', $return_data );

    $response = new WP_REST_Response( $return_data_filtered );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );

    return $response;
  }

	/**
	 * The GET to the /address/shipping endpoint - AUTHENTICATED.
   * This callback returns a customer's shipping address.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_shipping_address() {
    $user = wp_get_current_user();
    if ( !$user->exists() ) {
      return new WP_Error( 'no_user', 'You have to be logged in to fetch your address.', [ 'status' => 403 ] );
    }

    $user_id = get_current_user_id();
    $customer = new WC_Customer( $user_id );

    $return_data = $this->account_methods->get_customer_shipping_address( $customer );

    $return_data_filtered = apply_filters( 'vsf_wc_api_get_shipping_address_return_data', $return_data );

    $response = new WP_REST_Response( $return_data_filtered );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );

    return $response;
  }

	/**
	 * The POST to the /address/billing endpoint - AUTHENTICATED.
   * This callback updates a customer's billing address.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function set_billing_address( $request ) {
    // Load cart libraries
    wc_load_cart();

    // Authentication Check
    $user = wp_get_current_user();
    if ( !$user->exists() ) {
      return new WP_Error( 'no_user', 'You have to be logged in to set your address.', [ 'status' => 403 ] );
    }

    if ( !$request[ 'firstName' ] ) {
      return new WP_Error( 'no_first_name', 'Request body should contain firstName.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'lastName' ] ) {
      return new WP_Error( 'no_last_name', 'Request body should contain lastName.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'addressLine1' ] ) {
      return new WP_Error( 'no_address_line_1', 'Request body should contain an addressLine1.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'addressLine2' ] ) {
      return new WP_Error( 'no_address_line_2', 'Request body should contain an addressLine2.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'city' ] ) {
      return new WP_Error( 'no_city', 'Request body should contain city.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'postcode' ] ) {
      return new WP_Error( 'no_address_postcode', 'Request body should contain postcode.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'country' ] ) {
      return new WP_Error( 'no_address_state', 'Request body should contain country.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'state' ] ) {
      return new WP_Error( 'no_address_state', 'Request body should contain state.', [ 'status' => 400 ] );
    }

    $user_id = get_current_user_id();
    $customer = new WC_Customer( $user_id );

    $return_data = $this->account_methods->set_customer_billing_address( $customer, $request );

    $return_data_filtered = apply_filters( 'vsf_wc_api_set_billing_address_return_data', $return_data );

    $response = new WP_REST_Response( $return_data_filtered );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );

    return $response;
  }

	/**
	 * The POST to the /address/shipping endpoint - AUTHENTICATED.
   * This callback updates a customer's shipping address.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function set_shipping_address( $request ) {
    // Load cart libraries
    wc_load_cart();

    // Authentication Check
    $user = wp_get_current_user();
    if ( !$user->exists() ) {
      return new WP_Error( 'no_user', 'You have to be logged in to set your address.', [ 'status' => 403 ] );
    }

    if ( !$request[ 'firstName' ] ) {
      return new WP_Error( 'no_first_name', 'Request body should contain firstName.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'lastName' ] ) {
      return new WP_Error( 'no_last_name', 'Request body should contain lastName.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'addressLine1' ] ) {
      return new WP_Error( 'no_address_line_1', 'Request body should contain an addressLine1.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'addressLine2' ] ) {
      return new WP_Error( 'no_address_line_2', 'Request body should contain an addressLine2.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'city' ] ) {
      return new WP_Error( 'no_city', 'Request body should contain city.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'postcode' ] ) {
      return new WP_Error( 'no_address_postcode', 'Request body should contain postcode.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'country' ] ) {
      return new WP_Error( 'no_address_state', 'Request body should contain country.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'state' ] ) {
      return new WP_Error( 'no_address_state', 'Request body should contain state.', [ 'status' => 400 ] );
    }

    $user_id = get_current_user_id();
    $customer = new WC_Customer( $user_id );

    $return_data = $this->account_methods->set_customer_shipping_address( $customer, $request );

    $return_data_filtered = apply_filters( 'vsf_wc_api_set_shipping_address_return_data', $return_data );

    $response = new WP_REST_Response( $return_data_filtered );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );

    return $response;
  }

	/**
	 * The GET to the /shipping endpoint - NOT AUTHENTICATED.
   * This callback returns available shippping methods.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_shipping_methods() {
    // Load cart libraries
    wc_load_cart();

    $data = [];

    $cart = WC()->cart;

    // Loop through shipping packages from WC_Session (They can be multiple in some cases)
    foreach ( $cart->get_shipping_packages() as $package_id => $package ) {
      $package = 'shipping_for_package_' . $package_id;
      // Check if a shipping for the current package exist
      if ( WC()->session->__isset( $package ) ) {
        // Loop through shipping rates for the current package
        foreach ( WC()->session->get( $package )[ 'rates' ] as $shipping_rate_id => $shipping_rate ) {
          $rate = [];

          $rate[ 'rateID' ]    = $shipping_rate->get_id(); // same thing that $shipping_rate_id variable (combination of the shipping method and instance ID)
          $rate[ 'methodID' ]  = $shipping_rate->get_method_id(); // The shipping method slug
          $rate[ 'labelName' ] = $shipping_rate->get_label(); // The label name of the method
          $rate[ 'cost' ]      = $shipping_rate->get_cost(); // The cost without tax
          $rate[ 'taxCost' ]   = $shipping_rate->get_shipping_tax(); // The tax cost

          $data[] = $rate;
        }
      }
    }

    $data_filtered = apply_filters( 'vsf_wc_api_get_shipping_methods_return_data', $data );

    $response = new WP_REST_Response( $data_filtered );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );

    return $response;
  }

	/**
	 * The GET to the /payment endpoint - NOT AUTHENTICATED.
   * This callback returns available payment methods.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_payment_methods() {
    $payment_methods = [];

    foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $methods_key => $method ) {
      $formatted_payment_method = [];

      $formatted_payment_method[ 'id' ]          = $method->id;
      $formatted_payment_method[ 'title' ]       = $method->title;
      $formatted_payment_method[ 'description' ] = $method->description;

      $payment_methods[] =  $formatted_payment_method;
    }

    $payment_methods_filtered = apply_filters( 'vsf_wc_api_get_payment_methods_return_data', $payment_methods );

    $response = new WP_REST_Response( $payment_methods_filtered );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );

    return $response;
  }

	/**
	 * The POST to the /payment endpoint - AUTHENTICATED.
   * This callback initiates payment.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function make_payment( $request ) {
    // Load cart libraries
    wc_load_cart();

    // Authentication Check
    $user = wp_get_current_user();
    if ( !$user->exists() ) {
      return new WP_Error( 'no_user', 'You have to be logged in to initiate payment.', [ 'status' => 403 ] );
    }

    if ( !$request[ 'paymentMethod' ] ) {
      return new WP_Error( 'no_payment_method', 'Request body should contain paymentMethod.', [ 'status' => 400 ] );
    }
    if ( !$request[ 'total' ] ) {
      return new WP_Error( 'no_total', 'Request body should contain the cart total.', [ 'status' => 400 ] );
    }

    // Create order from cart
    $order = $this->payment_methods->create_order( $request );

    // Stop here if an error occured
    if ( is_wp_error( $order ) ) {
      return $order;
    }
    if ( empty( $order ) ) {
      return new WP_Error( 'bad_order_number', 'The order to be paid is invalid',  [ 'status' => 400 ] );
    }

    $return_data = $this->payment_methods->init_order_payment( $order, $request );

    $return_data_filtered = apply_filters( 'vsf_wc_api_make_payment_return_data', $return_data );

    $response = new WP_REST_Response( $return_data_filtered );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );

    return $response;
  }

	/**
	 * The GET to the /order{id} endpoint - AUTHENTICATED.
   * This callback returns an order by ID.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_order( $request ) {

    // Verify Authentication
    $user = wp_get_current_user();
    if ( !$user->exists() ) {
      return new WP_Error( 'no_user', 'You have to be logged in to fetch your orders.', [ 'status' => 403 ] );
    }
    // Verify request data
    if ( !$request[ 'id' ] ) {
      return new WP_Error( 'no_order', 'Request body should contain order data.', [ 'status' => 400 ] );
    }
    if ( !intval( $request[ 'id' ] ) ) {
      return new WP_Error( 'bad_order', 'Order ID should be an int.', [ 'status' => 400 ] );
    }

    // Check if order provided is valid and belongs to customer
    $order = wc_get_order( $request[ 'id' ] );
    if ( !$order ) { // Valid order number?
      return new WP_Error( 'bad_order_number', 'Order does not exist', [ 'status' => 400 ] );
    }
    $user_id = get_current_user_id();
    if ( $order->get_customer_id() != $user_id ) { // Does order belong to this user?
      return new WP_Error( 'bad_order_number', 'Order does not belong to user', [ 'status' => 400 ] );
    }

    $return_data = $this->payment_methods->build_order_object( $order );

    $return_data_filtered = apply_filters( 'vsf_wc_api_get_order_return_data', $return_data );

    $response = new WP_REST_Response( $return_data_filtered );
    
    $response->set_headers( [
      'Content-Type' => 'application/json'
    ] );

    return $response;
  }
}