<?php
require_once dirname(__FILE__) . '/filters.php';
require_once dirname(__FILE__) . '/product-functions.php';
require_once dirname(__FILE__) . '/account-functions.php';
require_once dirname(__FILE__) . '/cart-functions.php';
require_once dirname(__FILE__) . '/payment-functions.php';

// *************************************************************
//  The GET to /products endpoint - NOT AUTHENTICATED (yet)
//  This endpoint exposes data to the frontend
// *************************************************************
function vsf_wc_api_get_all_products($request)
{
    // Prepare the WC query arguments
    $query_args = array(
        'status' => 'publish',
        'paginate' => $request['paginate'] && $request['paginate'] == 'true' ? true : false,
        'page' => $request['page'] ? $request['page'] : 1,
        'limit' => $request['limit'] ? $request['limit'] : 20,
        'orderby' => $request['orderby'] ? $request['orderby'] : 'id',
        'order' => $request['order'] ? $request['order'] : 'DESC',
        'category' => $request['categories'] ? explode(',', sanitize_text_field($request['categories'])) : [],
        'include' => $request['id'] ? [$request['id']] : []
    );

    // Add all query parameters that starts with pa_ to the get products query
    // This is so that the products can be filtered by any global attributes
    foreach ($request->get_query_params() as $key => $value) { 
        if (str_starts_with(sanitize_text_field($key), 'pa_')) {
            $query_args[sanitize_text_field($key)] = explode(',', sanitize_text_field($value));
        }
    }

    return vsf_product_query($query_args);
}

// *************************************************************
//  The GET to /products/{id} endpoint - NOT AUTHENTICATED (yet)
//  This endpoint exposes data to the frontend
// *************************************************************
function vsf_wc_api_get_single_product($request) {
    // Prepare the WC query arguments
    $query_args = array(
        'status' => 'publish',
        'paginate' => false,
        'include' => [$request['id'] ? $request['id'] : -1]
    );

    return vsf_product_query($query_args);
}



// *************************************************************
//  The GET to /categories endpoint - NOT AUTHENTICATED (yet)
//  This endpoint exposes category data to the frontend
// *************************************************************
function vsf_wc_api_get_all_categories($request)
{
    // Prepare category query
    $query_args = array(
        'taxonomy' => 'product_cat',
        'status' => 'publish',
        'hide_empty' => true,
        'offset' => $request['page'] ? $request['page'] : 0,
        'number' => $request['limit'] ? $request['limit'] : 0,
        'menu_order' => true
    );
    $items = get_terms($query_args);

    $return_data = array();

    foreach ($items as $category) {
        $return_data[] = array(
            'type' => "category",
            'id' => $category->term_id,
            'title' => htmlspecialchars_decode($category->name),
            'description' => $category->description,
            'slug' => $category->slug,
            'count' => $category->count,
            'parent_id' => $category->parent,
            'category_slug_path' => get_term_parents_list($category->term_id, 'product_cat', array("format" => "slug", "separator" => "/", "link" => false)),
        );
    }

    return $return_data;
}


// *************************************************************
//  The GET to /facets endpoint - NOT AUTHENTICATED (yet)
//  This endpoint returns available attributes
// *************************************************************
function vsf_wc_api_get_facets($request)
{
    // Prepare the WC query arguments
    $query_args = array(
        'status' => 'publish',
        'limit' => -1,
        'category' => $request['categories'] ? $request['categories'] : [],
        'include' => $request['id'] ? [$request['id']] : []
    );

    $filters = array();

    // Add all query parameters that starts with pa_ to the get products query
    // This is so that the products can be filtered by any global attributes
    foreach ($request->get_query_params() as $key => $value) { 
        if (str_starts_with(sanitize_text_field($key), 'pa_')) {
            $filters[$key] = explode(',', sanitize_text_field($value));
        }
    }

    // If only one filter is selected, still show all values for that filter
    if (sizeof($filters) === 1) {
        foreach ($filters as $key => $value) { 
            $query_args[$key] = [];
            $terms = get_terms($key);
            foreach ($terms as $term) {
                array_push($query_args[$key], $term->slug);
            }
        }
    }
    // Else, show only availble filter values
    else {   
        foreach ($filters as $key => $value) { 
            $query_args[$key] = $value;
        }
    }    

    $products = wc_get_products($query_args);
    $facets = array();
    
    // Get facet data from attributes
    foreach ($products as $product) {
        $attributes = $product->get_attributes();

        foreach ($attributes as $attribute => $v) {
            $values = $v->get_slugs();
            $terms = $v->get_terms();
            if ($terms != null) {
                if (!array_key_exists($attribute, $facets)) {
                    $facets[$attribute]['title'] = wc_attribute_label( $attribute );
                    $facets[$attribute]['id'] = $attribute;
                    $facets[$attribute]['values'] = array();
                    foreach ($values as $index=>$value) {
                        $facets[$attribute]['values'][$value] = array('title' => $terms[$index]->name, 'count' => 1);
                    }
                }
                else {
                    foreach ($values as $index=>$value) {
                        if (!array_key_exists($value, $facets[$attribute]['values'])) {
                            $facets[$attribute]['values'][$value] = array('title' => $terms[$index]->name, 'count' => 1);
                        }
                        else {
                            ++$facets[$attribute]['values'][$value]['count'];
                        }
                    }
                }
            }
        }
    }

    return $facets;

}


// *************************************************************
//  The POST to /register endpoint - NOT AUTHENTICATED (yet)
//  This endpoint registers a new customer user
// *************************************************************
function vsf_wc_api_register_user( $request )
{
    // Verify request data
    if (!sanitize_email($request['email'])) {
        return new WP_Error('bad_request', 'Bad Request, email invalid', array('status' => 400));
    }
    if (!$request['password']) {
        return new WP_Error('bad_request', 'Bad Request, password field missing', array('status' => 400));
    }
    if (!$request['firstName']) {
        return new WP_Error('bad_request', 'Bad Request, firstName field missing', array('status' => 400));
    }
    if (!$request['lastName']) {
        return new WP_Error('bad_request', 'Bad Request, lastName field missing', array('status' => 400));
    }

    // Verify this is a new user
    $user = get_user_by('email', $request['email']);
    if (!empty($user)) {
        return new WP_Error('already_exists', 'Bad Request, user already exists', array('status' => 400));
    }

    // Register new customer
    return vsf_register_user($request);
}


// *************************************************************
//  The GET to /cart endpoint - EXPOSED
//  Returns the cart to the customer browser
// *************************************************************
function vsf_wc_api_get_cart($request)
{
    // Load cart libraries
    wc_load_cart();

    // Return cart structure
    return vsf_get_cart();
}


// *************************************************************
//  The POST to /cart endpoint - EXPOSED
//  Add a product, delete a product or update quantity
// *************************************************************
function vsf_wc_api_update_cart($request)
{
    // Load cart libraries
    wc_load_cart();

    // Verify cart method
    if (!isset($request['cartMethod'])) {
        return new WP_Error('bad_request', 'Bad request, no cartMethod variable provided (either add, remove or update)', array('status' => 400));
    }
    
    // Verify quantity variable if provided
    $quantity = 1;
    if (isset($request['quantity'])) {
        $quantity = intval($request['quantity']);
    }

    // Add to cart
    if ($request['cartMethod'] == 'add') {
        // Verify product ID
        if (!isset($request['id'])) {
            return new WP_Error('bad_request', 'Bad request, no product id provided', array('status' => 400));
        }

        return vsf_add_to_cart($request['id'], $quantity);
    }

    // Verify cart item key
    if (!isset($request['key'])) {
        return new WP_Error('bad_request', 'Bad request, no cart item key provided', array('status' => 400));
    }

    // Update product quantity
    if ($request['cartMethod'] == 'update') {
        if (!isset($request['quantity'])) {
            return new WP_Error('bad_request', 'Bad request, no quantity provided for cart update', array('status' => 400));
        }

        return vsf_update_cart($request['key'], $quantity);
    }

    // Remove an item from the cart
    if ($request['cartMethod'] == 'remove') {
        return vsf_remove_from_cart($request['key']);
    }

    // Unkown cart method
    return new WP_Error('bad_request', 'Bad request, unknown cartMethod provided (should be add, remove or update)', array('status' => 400));
}


// *************************************************************
//  The GET to /address/billing endpoint - AUTHENTICATED
// *************************************************************
function vsf_wc_api_get_billing_address($request)
{
    $user = wp_get_current_user();
    if (!$user->exists()) {
        return new WP_Error('no_user', 'You have to be logged in to fetch your address.', array('status' => 403));
    }

    $user_id = get_current_user_id();
    $customer = new WC_Customer($user_id);
    return vsf_get_customer_billing_address($customer);
}


// *************************************************************
//  The GET to /address/shipping endpoint - AUTHENTICATED
// *************************************************************
function vsf_wc_api_get_shipping_address($request)
{
    $user = wp_get_current_user();
    if (!$user->exists()) {
        return new WP_Error('no_user', 'You have to be logged in to fetch your address.', array('status' => 403));
    }

    $user_id = get_current_user_id();
    $customer = new WC_Customer($user_id);
    return vsf_get_customer_shipping_address($customer);
}


// *************************************************************
//  The POST to /address/billing endpoint - AUTHENTICATED
// *************************************************************
function vsf_wc_api_set_billing_address($request)
{
    // Load cart libraries
    wc_load_cart();

    // Authentication Check
    $user = wp_get_current_user();
    if (!$user->exists()) {
        return new WP_Error('no_user', 'You have to be logged in to set your address.', array('status' => 403));
    }

    if (!$request['firstName']) {
        return new WP_Error('no_first_name', 'Request body should contain firstName.', array('status' => 400));
    }
    if (!$request['lastName']) {
        return new WP_Error('no_last_name', 'Request body should contain lastName.', array('status' => 400));
    }
    if (!$request['addressLine1']) {
        return new WP_Error('no_address_line_1', 'Request body should contain an addressLine1.', array('status' => 400));
    }
    if (!$request['addressLine2']) {
        return new WP_Error('no_address_line_2', 'Request body should contain an addressLine2.', array('status' => 400));
    }
    if (!$request['city']) {
        return new WP_Error('no_city', 'Request body should contain city.', array('status' => 400));
    }
    if (!$request['postcode']) {
        return new WP_Error('no_address_postcode', 'Request body should contain postcode.', array('status' => 400));
    }
    if (!$request['country']) {
        return new WP_Error('no_address_state', 'Request body should contain country.', array('status' => 400));
    }
    if (!$request['state']) {
        return new WP_Error('no_address_state', 'Request body should contain state.', array('status' => 400));
    }

    $user_id = get_current_user_id();
    $customer = new WC_Customer($user_id);

    return vsf_set_customer_billing_address($customer, $request);
}


// *************************************************************
//  The POST to /address/shipping endpoint - AUTHENTICATED
// *************************************************************
function vsf_wc_api_set_shipping_address($request)
{
    // Load cart libraries
    wc_load_cart();

    // Authentication Check
    $user = wp_get_current_user();
    if (!$user->exists()) {
        return new WP_Error('no_user', 'You have to be logged in to set your address.', array('status' => 403));
    }

    if (!$request['firstName']) {
        return new WP_Error('no_first_name', 'Request body should contain firstName.', array('status' => 400));
    }
    if (!$request['lastName']) {
        return new WP_Error('no_last_name', 'Request body should contain lastName.', array('status' => 400));
    }
    if (!$request['addressLine1']) {
        return new WP_Error('no_address_line_1', 'Request body should contain an addressLine1.', array('status' => 400));
    }
    if (!$request['addressLine2']) {
        return new WP_Error('no_address_line_2', 'Request body should contain an addressLine2.', array('status' => 400));
    }
    if (!$request['city']) {
        return new WP_Error('no_city', 'Request body should contain city.', array('status' => 400));
    }
    if (!$request['postcode']) {
        return new WP_Error('no_address_postcode', 'Request body should contain postcode.', array('status' => 400));
    }
    if (!$request['country']) {
        return new WP_Error('no_address_state', 'Request body should contain country.', array('status' => 400));
    }
    if (!$request['state']) {
        return new WP_Error('no_address_state', 'Request body should contain state.', array('status' => 400));
    }

    $user_id = get_current_user_id();
    $customer = new WC_Customer($user_id);

    return vsf_set_customer_shipping_address($customer, $request);
}


// *************************************************************
//  The POST to /payment endpoint - AUTHENTICATED
// *************************************************************
function vsf_wc_api_make_payment($request)
{
    // Load cart libraries
    wc_load_cart();

    // Authentication Check
    $user = wp_get_current_user();
    if (!$user->exists()) {
        return new WP_Error('no_user', 'You have to be logged in to initiate payment.', array('status' => 403));
    }

    if (!$request['paymentMethod']) {
        return new WP_Error('no_payment_method', 'Request body should contain paymentMethod.', array('status' => 400));
    }
    if (!$request['total']) {
        return new WP_Error('no_total', 'Request body should contain the cart total.', array('status' => 400));
    }

    // Create order from cart
    $order = vsf_create_order($request);

    // Stop here if an error occured
    if (is_wp_error($order)) {
        return $order;
    }
    if (empty($order)) {
        return new WP_Error('bad_order_number', 'The order to be paid is invalid', array('status' => 400));
    }
    
    return vsf_init_order_payment($order, $request);
}


// *************************************************************
//  The GET to /order/{{id}} endpoint - AUTHENTICATED
// *************************************************************
function vsf_wc_api_get_order($request)
{

    // Verify Authentication
    $user = wp_get_current_user();
    if (!$user->exists()) {
        return new WP_Error('no_user', 'You have to be logged in to fetch your orders.', array('status' => 403));
    }
    // Verify request data
    if (!$request['id']) {
        return new WP_Error('no_order', 'Request body should contain order data.', array('status' => 400));
    }
    if (!intval($request['id'])) {
        return new WP_Error('bad_order', 'Order ID should be an int.', array('status' => 400));
    }

    // Check if order provided is valid and belongs to customer
    $order = wc_get_order($request['id']);
    if (!$order) { // Valid order number?
        return new WP_Error('bad_order_number', 'Order does not exist', array('status' => 400));
    }
    $user_id = get_current_user_id();
    if ($order->get_customer_id() != $user_id) { // Does order belong to this user?
        return new WP_Error('bad_order_number', 'Order does not belong to user', array('status' => 400));
    }

    return vsf_build_order_object($order);
}