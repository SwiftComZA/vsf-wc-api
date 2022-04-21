<?php
require_once dirname(__FILE__) . '/product-functions.php';

// *************************************************************
//  The GET to /products endpoint - NOT AUTHENTICATED (yet)
//  This endpoint exposes data to the frontend
// *************************************************************
function vsf_wc_api_get_all_products($request)
{
    // Prepare the WC query arguments
    $query_args = array(
        'status' => 'publish',
        'paginate' => true,
        'page' => $request['page'] ? $request['page'] : 0,
        'limit' => $request['limit'] ? $request['limit'] : 20,
        'orderby' => $request['orderby'] ? $request['orderby'] : 'id',
        'order' => $request['order'] ? $request['order'] : 'DESC',
        'category' => $request['categories'] ? $request['categories'] : [],
    );

    // Add all query parameters that starts with pa_ to the get products query
    // This is so that the products can be filtered by any global attributes
    foreach ($request->get_query_params() as $key => $value) { 
        if (str_starts_with(sanitize_text_field($key), 'pa_')) {
            $query_args[sanitize_text_field($key)] = sanitize_text_field($value);
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
//  Add all global attributes to product query taxonomy
// *************************************************************
function vsf_filter_add_custom_query_taxonomies( $query, $query_vars ) {

    // Add all parameters that start with pa_ to the taxonomy
    // so that products can be queried by any global attributes
    foreach($query_vars as $key => $value) {
        if (str_starts_with($key, 'pa_')) {
            $query['tax_query'][] = array(
                'taxonomy' => $key,
                'field'    => 'slug',
                'terms'    => $value,
                'operator' => 'IN',
            );
        }
    }

	return $query;
}
add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'vsf_filter_add_custom_query_taxonomies', 10, 2 );