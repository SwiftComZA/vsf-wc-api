<?php
/**
 * Plugin Name: Vue Storefront WooCommerce Integration
 * Description: This plugin integrates WooCommerce with Vue Storefront
 * Author: SwiftCom
 */


require_once dirname(__FILE__) . '/api/endpoint-functions.php';

// *************************************************************
//  On REST API init - register the REST API endpoints
// *************************************************************
add_action('rest_api_init', 'wp_sea_saas_init_rest_api');

function wp_sea_saas_init_rest_api()
{
    // ************** Get all products
    register_rest_route('vsf-wc-api/v1', '/products', array(
        'methods' => 'GET',
        'callback' => 'vsf_wc_api_get_all_products',
        'permission_callback' => '__return_true',
    ));
}
