<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 *  Defines methods used by the payment endpoints callbacks.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    VSF_WC_API
 * @subpackage VSF_WC_API/public
 * @author     SwiftCom <hello@swiftcom.co.za>
 */
class VSF_WC_API_Payment_Methods {

  /**
   * Creates an order by using the convert_cart_to_order().
   *
   * @since    1.0.0
   * @access   public
   */
  public function create_order( $request ) {

    // Check if cart is empty
    $cart = WC()->cart;
    if ( $cart->is_empty() ) {
      return new WP_Error( 'bad_cart', 'You cannot check out an empty cart.', [ 'status' => 400 ] );
    }

    // Check cart item validity
    $result = $cart->check_cart_item_validity();
    if ( is_wp_error( $result ) ) {
      return new WP_Error( 'bad_cart_items', $result, [ 'status' => 400 ] ) ;
    }
    
    // Check cart item stock levels
    $result = $cart->check_cart_item_stock();
    if ( is_wp_error( $result ) ) {
      return new WP_Error( 'bad_cart_stock', $result, [ 'status' => 400 ] );
    }

    // Verify the total in the cart is the same as the front-end total. Kind of.
    $cart_total = round( $cart->get_totals()[ 'total' ] );
    $request_total = round( $request[ 'total' ] );

    if ( $cart_total != $request_total ) {
      return new WP_Error( 'bad_cart_meta', 'Cart totals do not match', [ 'status' => 400 ] );
    }

    // Convert cart to actual order and return order data
    return $this->convert_cart_to_order( $request );
  }

  /**
   * Converts a customer's cart to an order and saves it.
   *
   * @since    1.0.0
   * @access   public
   */
  public function convert_cart_to_order( $request ) {
    // Apply the chosen shipping method
    $packages = WC()->shipping()->calculate_shipping( WC()->cart->get_shipping_packages() );
    $customer = WC()->customer;
    $user_id = get_current_user_id();

    // Prepare order data
    $order_data = [
      "billing_first_name"  => $customer->get_billing_first_name(),
      "billing_last_name"   => $customer->get_billing_last_name(),
      "billing_address_1"   => $customer->get_billing_address_1(),
      "billing_address_2"   => $customer->get_billing_address_2(),
      "billing_city"        => $customer->get_billing_city(),
      "billing_state"       => $customer->get_billing_state(),
      "billing_postcode"    => $customer->get_billing_postcode(),
      "billing_country"     => $customer->get_billing_country(),
      "billing_phone"       => $customer->get_billing_phone(),

      "shipping_first_name" => $customer->get_shipping_first_name(),
      "shipping_last_name"  => $customer->get_shipping_last_name(),
      "shipping_company"    => $customer->get_shipping_company(),
      "shipping_address_1"  => $customer->get_shipping_address_1(),
      "shipping_address_2"  => $customer->get_shipping_address_2(),
      "shipping_city"       => $customer->get_shipping_city(),
      "shipping_state"      => $customer->get_shipping_state(),
      "shipping_postcode"   => $customer->get_shipping_postcode(),
      "shipping_country"    => $customer->get_shipping_country(),
    ];

    // Checkout cart and use prepared order data
    $order_id = WC()->checkout()->create_order( $order_data );
    $order = wc_get_order( $order_id );

    $order->calculate_totals();
    // Save to database
    $order->save();
    // Delete cart, it's used up
    WC()->cart->empty_cart();

    return $order;
  }

  /**
   * Initiates a customer's payment according to the payment method parameter.
   *
   * @since    1.0.0
   * @access   public
   */
  public function init_order_payment( $order, $request ) {
    $user_id = get_current_user_id();
    $order_id = $order->get_id();

    // Start processing payment method
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    $response_data = $order->get_data();

    // TODO: Add array of payment methods that can be filtered to add more.

    // Check the payment method specified and apply it to the order
    switch ( $request[ 'paymentMethod' ] ) {
      case 'bacs':
        $order->set_payment_method( $gateways[ 'bacs' ] );
        $order->add_order_note( 'Payment method Manual EFT initiated.' );
        $order->save();
        $gateways[ 'bacs' ]->process_payment( $order_id );
        if ( empty( $gateways[ 'bacs' ] ) ) {
          return new WP_Error( 'bad_payment_gateway', 'The bacs payment gateway is not enabled.', [ 'status' => 500 ] );
        }
      break;
    }

    // Save to database
    $order->save();
    if (
      is_wp_error( $response_data )
      && isset( $response_data->get_error_data()[ 'notification' ] )
      && !isset( $response_data->get_error_data()[ 'not_critical' ] )
    ) {
      error_log( 'wc_api_payment_create_payment: FAILED - ' . print_r( $response_data->get_error_messages(), true ) );
    }

    // append order data to response
    if ( is_array( $response_data ) ) {
      $response_data[ 'order' ] = $this->build_order_object( $order );
    } else if ( is_object( $response_data ) ) {
      $response_data->order = $this->build_order_object( $order );
    }
    return $response_data;
  }

  /**
   * Builds the order object.
   *
   * @since    1.0.0
   * @access   public
   */
  public function build_order_object( $order ) {
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    // Gets most of the order data neatly formatted
    $order_data = $order->get_data();
    $order_data[ 'order_number' ] = $order->get_order_number();

    foreach ( $order_data[ 'meta_data' ] as $meta_item ) {
      $meta_data = $meta_item->get_data();
    }

    // Get shipping information
    $order_item_shipping = array_values( $order->get_items( 'shipping' ) );
    if ( isset( $order_item_shipping[ 0 ] ) ) {
      $order_shipping_data = $order_item_shipping[ 0 ]->get_data();
    } else {
      $order_shipping_data = [];
    }
    $order_data[ 'shipping_method' ] = $order->get_shipping_method();
    $order_data[ 'shipping_data' ] = $order_shipping_data;

    // Add all customer order notes (such as why payments failed)
    $order_data[ 'order_notes' ] = $order->get_customer_order_notes();

    // Add a order subtotal which excludes the discount
    $order_data[ 'subtotal' ] = round( $order_data[ 'total' ] + $order_data[ 'discount_total' ] + $order_data[ 'discount_tax' ] );

    // Gets the payment method specified on the order
    $order_payment_method = $order_data[ 'payment_method' ];
    if ( $order_payment_method ) {
      $payment_method = $gateways[ $order_payment_method ];
      if ( $payment_method ) {
        switch ( $order_payment_method ) {
          case 'bacs':
            $order_data[ 'payment_method_description' ] = $payment_method->get_description();
            $order_data[ 'payment_method_account_details' ] = $payment_method->account_details; // TODO: Not allowed to read class property like this
          break;
          default:
            $order_data[ 'payment_method_description' ] = $payment_method->get_description();
            $order_data[ 'payment_method_account_details' ] = null;
          break;
        }
      }
    }

    // Order data does not contain line_item information by default. Add product information here
    $order_data[ 'line_items' ] = [];
    foreach ( $order->get_items() as $item_key => $item ) {
      $item_data = $item->get_data();
      $product = $item->get_product();
      if ( 'variation' === $product->get_type() ) {
        $parent_product = wc_get_product( $product->get_parent_id() );
      }
      $meta_data = $item->get_meta_data();
      $size = '';
      $totalPrice = 0;
      $totalTax = 0;

      // This data is fetched from "Product" object
      // won't be available if product got deleted
      if ( $product ) {
        $item_data[ 'sku' ] = $product->get_sku();
        $item_data[ 'slug' ] = $product->get_slug();
        if ( 'variation' === $product->get_type() ) {
          $item_data[ 'parent_slug' ] = $parent_product->get_slug();
        }
        $item_data[ 'unit_price' ] = $order->get_item_total( $item, true, true );
        $item_data[ 'image' ] = $product->get_image();
        if ( 'variation' === $product->get_type() ) {
          $item_data[ 'attributes' ] = $product->get_variation_attributes();
        } else {
          $item_data[ 'attributes' ] = $product->get_attributes();
        }
        $item_data[ 'title' ] = $product->get_title();
      } else {
        $item_data[ 'sku' ]        = '';
        $item_data[ 'slug' ]       = '';
        $item_data[ 'unit_price' ] = $order->get_item_total( $item, true, true );
        $item_data[ 'image' ]      = '';
        $item_data[ 'attributes' ] = '';
        $item_data[ 'title' ]      = '';
        $item_data[ 'categories' ] = [];
      }

      $item_data[ 'meta_data' ] = $meta_data;
      if ($item_data[ 'attributes' ] && isset( $item_data[ 'attributes' ][ 'pa_size' ] ) ) {
        $item_data[ 'size' ] = array_values( $item_data[ 'attributes' ][ 'pa_size' ] );
      } else if ( $item_data[ 'attributes' ] && isset( $item_data[ 'attributes' ][ 'attribute_pa_size' ] ) ) {
        $item_data[ 'size' ] = ucfirst( $item_data[ 'attributes' ][ 'attribute_pa_size' ] );
      }

      if ( $totalPrice != 0 ) {
        $item_data[ 'total' ] = round( $totalPrice + $totalTax );
        $item_data[ 'total_tax' ] = round( $totalTax );
      } else {
        $item_data[ 'total' ] = round( $item_data[ 'subtotal' ] + $item_data[ 'subtotal_tax' ] );
      }

      $order_data[ 'line_items' ][] = $item_data;
    }

    $order_data[ 'line_items' ] = array_values( $order_data[ 'line_items' ] );

    return $order_data;
  }

}