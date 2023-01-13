<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Defines methods used by the cart endpoints callbacks.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    VSF_WC_API
 * @subpackage VSF_WC_API/public
 * @author     SwiftCom <hello@swiftcom.co.za>
 */
class VSF_WC_API_Cart_Methods {

	/**
	 * Gets a customer, gets the cart contents with the 
   * get_cart_contents() method and returns the cart.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_cart() {
    // Get user id for wishlist
    $user_id = get_current_user_id();

    // Get customer cart and recalculate totals
    $cart = WC()->cart;
    $cart->calculate_totals();

    // Initial structure
    $cart_response = [];

    // Get cart data
    $cart_response[ 'contents' ] = $this->get_cart_contents( $cart );

    // Return cart structure
    return $cart_response;
  }

	/**
	 * Takes the WC cart object, formats the data and returns it.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_cart_contents($cart) {
    // Initial structure
    $formatted_cart_contents = [];

    // Get newest cart data
    $cart_contents = $cart->get_cart();

    // Add each product information to return structure
    foreach ( $cart_contents as $key => $cart_item ) {
      $product = $cart_item[ 'data' ];
      $parent_product = wc_get_product( $product->get_parent_id() );

      $item_formatted = [];

      // Add cart item data
      $item_formatted[ 'key' ]           = $cart_item[ 'key' ];
      $item_formatted[ 'quantity' ]      = $cart_item[ 'quantity' ];
      $item_formatted[ 'priceEach' ]     = $product->get_price();
      $item_formatted[ 'priceTax' ]      = $cart_item[ 'line_tax' ];

      // WooCommerce considers "subtotal" to be the price before discount
      $item_formatted[ 'priceSubtotal' ] = $cart_item[ 'line_subtotal' ];
      $item_formatted[ 'priceTotal' ]    = $cart_item[ 'line_subtotal' ] + $cart_item[ 'line_subtotal_tax' ];

      // Add product data
      $item_formatted[ 'name' ]          = $product->get_title();
      $item_formatted[ 'id' ]            = $product->get_id();
      $item_formatted[ 'type' ]          = $product->get_type();
      $item_formatted[ 'slug' ]          = $product->get_slug();
      $item_formatted[ 'sku' ]           = $product->get_sku();
      $item_formatted[ 'image' ]         = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );

      // Add product attributes
      $attribute_names = $product->get_attributes();

      $attributes = [];
      foreach ( $attribute_names as $key => $val ) {
        $attributes[ $key ] = $product->get_attribute( $key );
      }

      $item_formatted[ 'attributes' ]    = $attributes;

      $formatted_cart_contents[] = $item_formatted;

    }

    // Return cart structure
    return $formatted_cart_contents;
  }

	/**
	 * Adds a product with the given quantity to the 
   * customer's cart.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function add_to_cart( $product_id, $quantity ) {
    // Verify product ID
    if ( !get_post( $product_id ) ) {
      return new WP_Error( 'bad_request', 'Product does not exist', [ 'status' => 400 ] );
    }

    // Get customer cart
    $cart = WC()->cart;
    // This seems to be needed to "refresh" the cart
    $cart_contents = WC()->cart->get_cart();

    // Parse function result
    $add_result = $cart->add_to_cart( $product_id, $quantity );
    if ( isset( $add_result ) && !empty( $add_result ) ) {
      return $this->get_cart();
    }

    // Add product to cart
    return 'Failed to add product to cart';
  }

	/**
	 * Updates a product in the customer's cart's quantity.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function update_cart( $item_key, $quantity ) {
    // Get customer cart
    $cart = WC()->cart;

    // This seems to be needed to "refresh" the cart
    $cart_contents = WC()->cart->get_cart();

    if ( empty( $cart->get_cart_item( $item_key ) ) ) {
      return new WP_Error( 'bad_request', 'Bad cart item ID provided', ['status' => 400] );
    }
    // Add product to cart
    $cart->set_quantity( $item_key, $quantity );

    return $this->get_cart();
  }

	/**
	 * Removes a product from the customer's cart.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function remove_from_cart( $item_key ) {
    // Get customer cart
    $cart = WC()->cart;

    // This seems to be needed to "refresh" the cart
    $cart_contents = WC()->cart->get_cart();

    if ( empty( $cart->get_cart_item( $item_key) ) ) {
      return new WP_Error( 'bad_request', 'Bad cart item ID provided', ['status' => 400] );
    }

    // Remove product from cart
    $cart->remove_cart_item( $item_key );

    return $this->get_cart();
  }

}