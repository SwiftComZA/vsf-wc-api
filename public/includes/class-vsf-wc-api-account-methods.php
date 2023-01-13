<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Defines methods used by the account endpoints callbacks.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    VSF_WC_API
 * @subpackage VSF_WC_API/public
 * @author     SwiftCom <hello@swiftcom.co.za>
 */
class VSF_WC_API_Account_Methods {

	/**
	 * Register's a new customer user.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function register_user( $request ) {
    $customer_email = sanitize_email( $request[ 'email' ] );
    // Create new customer
    $user_id = wc_create_new_customer( $customer_email, null, $request[ 'password' ] );
    if ( is_wp_error( $user_id ) ) {
      return $user_id;
    }

    $customer = new WC_Customer( $user_id );
    $customer->set_first_name( $request[ 'firstName' ] );
    $customer->set_last_name( $request[ 'lastName' ] );
    $customer->set_display_name( $request[ 'firstName' ] );

    $customer->save();
    
    return [
      'message' => 'Your account has been successful created. Please log in with your new credentials.',
      'userID' => $user_id
    ];
  }

	/**
	 * Formats and returns a customer's billing address.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_customer_billing_address( $customer ) {
    $billing_address = [];

    $billing_address[ 'firstName' ]    = $customer->get_billing_first_name();
    $billing_address[ 'lastName' ]     = $customer->get_billing_last_name();
    $billing_address[ 'addressLine1' ] = $customer->get_billing_address_1();
    $billing_address[ 'addressLine2' ] = $customer->get_billing_address_2();
    $billing_address[ 'city' ]         = $customer->get_billing_city();
    $billing_address[ 'country' ]      = $customer->get_billing_country();
    $billing_address[ 'postcode' ]     = $customer->get_billing_postcode();
    $billing_address[ 'state' ]        = $customer->get_billing_state();
    $billing_address[ 'phone' ]        = $customer->get_billing_phone();

    return $billing_address;
  }

	/**
	 * Formats and returns a customer's shipping address.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function get_customer_shipping_address( $customer ) {
    $shipping_address = [];

    $shipping_address[ 'firstName' ]    = $customer->get_shipping_first_name();
    $shipping_address[ 'lastName' ]     = $customer->get_shipping_last_name();
    $shipping_address[ 'addressLine1' ] = $customer->get_shipping_address_1();
    $shipping_address[ 'addressLine2' ] = $customer->get_shipping_address_2();
    $shipping_address[ 'city' ]         = $customer->get_shipping_city();
    $shipping_address[ 'country' ]      = $customer->get_shipping_country();
    $shipping_address[ 'postcode' ]     = $customer->get_shipping_postcode();
    $shipping_address[ 'state' ]        = $customer->get_shipping_state();
    $shipping_address[ 'phone' ]        = $customer->get_shipping_phone();

    return $shipping_address;
  }

	/**
	 * Updates a customer's billing address.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function set_customer_billing_address( $customer, $request ) {

    $customer->set_billing_first_name( $request[ 'firstName' ] );
    $customer->set_billing_last_name( $request[ 'lastName' ] );
    $customer->set_billing_address_1( $request[ 'addressLine1' ] );
    $customer->set_billing_address_2( $request[ 'addressLine2' ] );
    $customer->set_billing_city( $request[ 'city' ] );
    $customer->set_billing_country( $request[ 'country' ] );
    $customer->set_billing_postcode( $request[ 'postcode' ] );
    $customer->set_billing_state( $request[ 'state' ] );
    $customer->set_billing_phone( $request[ 'phone' ] );

    $customer->save();

    return [ 'message' => 'Your billing address has been successfully updated.' ];
  }

	/**
	 * Updates a customer's shipping address.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function set_customer_shipping_address( $customer, $request ) {

    $customer->set_shipping_first_name( $request[ 'firstName' ] );
    $customer->set_shipping_last_name( $request[ 'lastName' ] );
    $customer->set_shipping_address_1( $request[ 'addressLine1' ] );
    $customer->set_shipping_address_2( $request[ 'addressLine2' ] );
    $customer->set_shipping_city( $request[ 'city' ] );
    $customer->set_shipping_country( $request[ 'country' ] );
    $customer->set_shipping_postcode( $request[ 'postcode' ] );
    $customer->set_shipping_state( $request[ 'state' ] );
    $customer->set_shipping_phone( $request[ 'phone' ] );

    $customer->save();

    return [ 'message' => 'Your shipping address has been successfully updated.' ];
  }
}