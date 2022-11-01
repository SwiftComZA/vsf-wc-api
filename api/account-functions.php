<?php

// *************************************************************
//  This function registers the new user with WooCommerce
// *************************************************************
function vsf_register_user($request)
{
    $customer_email = sanitize_email($request['email']);
    // Create new customer
    $user_id = wc_create_new_customer($customer_email, null, $request['password']);
    if (is_wp_error($user_id)) {
        return $user_id;
    }

    $customer = new WC_Customer($user_id);
    $customer->set_first_name($request['firstName']);
    $customer->set_last_name($request['lastName']);
    $customer->set_display_name($request['firstName']);

    $customer->save();
    return array('message' => 'Your account has been successful created. Please log in with your new credentials.', 'userID' => $user_id);
}