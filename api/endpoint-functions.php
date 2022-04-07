<?php

// *************************************************************
//  The GET to /products endpoint - NOT AUTHENTICATED (yet)
//  This endpoint exposes data to the frontend
// *************************************************************
function vsf_wc_api_get_all_products($request)
{
    $query_args = array(
        'status' => 'publish',
        'paginate' => true,
        'page' => $request['page'] ? $request['page'] : 0,
        'limit' => $request['limit'] ? $request['limit'] : 20,
        'orderby' => $request['orderby'] ? $request['orderby'] : 'id',
        'order' => $request['order'] ? $request['order'] : 'DESC',
        'category' => $request['categories'] ? $request['categories'] : [],
    );
    $products_query_response = wc_get_products($query_args);

    $return_data = array();
    // Add product information to return structure
    foreach ($products_query_response->products as $product) {

        // Prepare product data
        $product_data = array(
            'id' => $product->get_id(),
            'productType' => $product->get_type(),
            'title' => $product->get_title(),
            'description' => $product->get_description(),
            'slug' => $product->get_slug(),
            'price' => array('original' => $product->get_regular_price(), 'current' => $product->get_sale_price()),
            'regular_price' => $product->get_regular_price(),
            'sku' => $product->get_sku(),
            'sales' => $product->get_total_sales(),
            'availableForSale' => $product->get_availability(),
            'updatedAt' => $product->get_date_modified(),
            'createdAt' => $product->get_date_created(),
        );

        // Get simple product atributes this way
        $attribute_names = $product->get_attributes();

        $attributes = array();
        foreach ($attribute_names as $key => $val) {
            $attributes[$key] = $product->get_attribute($key);
        }

        $product_data['attributes'] = $attributes;

        // Add variation data
        $available_variations = array();
        if ($product->get_type() == 'variable') {
            $variation_ids = $product->get_children();

            // Iterate variations
            foreach ($variation_ids as $variation_id) {
                // Prepare variation data
                $variation = wc_get_product($variation_id);
                $variation_data = array(
                    'id' => $variation->get_id(),
                    'productType' => $variation->get_type(),
                    'title' => $variation->get_title(),
                    'description' => $variation->get_description(),
                    'slug' => $variation->get_slug(),
                    'price' => array('original' => $variation->get_regular_price(), 'current' => $variation->get_sale_price()),
                    'regular_price' => $variation->get_regular_price(),
                    'attributes' => $variation->get_attributes(),
                    'sku' => $variation->get_sku(),
                    'sales' => $variation->get_total_sales(),
                    'availableForSale' => $variation->get_availability(),
                    'updatedAt' => $variation->get_date_modified(),
                    'createdAt' => $variation->get_date_created(),
                );

                // Add variation to array
                $available_variations[] = $variation_data;
            }
        }
        $product_data['variants'] = $available_variations;

        // Add product data
        $return_data['products'] = $product_data;
        $return_data['total'] = $products_query_response->total;
        $return_data['pages'] = $products_query_response->max_num_pages;
    }

    return $return_data;
}