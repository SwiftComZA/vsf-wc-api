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
        'number' => $request['limit'] ? $request['limit'] : 100,
        'menu_order' => true
    );
    $items = get_terms($query_args);

    $return_data = array();

    foreach ($items as $category) {
        $return_data[] = array(
            'type' => "category",
            'id' => $category->term_id,
            'title' => $category->name,
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
//  Add all global attributes to product query taxonomy
// *************************************************************
function vsf_filter_add_custom_query_taxonomies( $query, $query_vars )
{

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