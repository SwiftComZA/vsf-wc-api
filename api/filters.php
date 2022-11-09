<?php

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