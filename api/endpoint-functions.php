<?php

// *************************************************************
//  The GET to /products endpoint - NOT AUTHENTICATED (yet)
//  This endpoint exposes data to the frontend
// *************************************************************
function vsf_wc_api_get_all_products($request)
{
    $query_args = array(
        'orderby' => 'date',
        'order' => 'DESC',
        'status' => 'publish',
        'hide_empty' => true,
        'offset' => 0,
        'limit' => 10,
    );
    $products = wc_get_products($query_args);

    $return_data = array();
    // Add product information to return structure
    foreach ($products as $product) {

        // Prepare product data
        $product_data = array(
            'id' => $product->get_id(),
            'productType' => $product->get_type(),
            'title' => $product->get_title(),
            'description' => $product->get_description(),
            'slug' => $product->get_slug(),
            'price' => array('original' => $product->get_regular_price(), 'current' => $product->get_sale_price()),
            'regular_price' => $product->get_regular_price(),
            'attributes' => $product->get_attributes(),
            'sku' => $product->get_sku(),
            'sales' => $product->get_total_sales(),
            'availableForSale' => $product->get_availability(),
            'updatedAt' => $product->get_date_modified(),
            'createdAt' => $product->get_date_created(),
        );

        

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
        $return_data[] = $product_data;
    }

    return $return_data;
}