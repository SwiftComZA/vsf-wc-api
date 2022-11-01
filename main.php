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
add_action('rest_api_init', 'vsf_wc_api_init_rest_api');

function vsf_wc_api_init_rest_api()
{
    // ************** Get all products
    register_rest_route('vsf-wc-api/v1', '/products', array(
        'methods' => 'GET',
        'callback' => 'vsf_wc_api_get_all_products',
        'permission_callback' => '__return_true',
    ));

    // ************** Get single product
    register_rest_route('vsf-wc-api/v1', '/products/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'vsf_wc_api_get_single_product',
        'permission_callback' => '__return_true',
    ));

    // ************** Get all categories
    register_rest_route('vsf-wc-api/v1', '/categories', array(
        'methods' => 'GET',
        'callback' => 'vsf_wc_api_get_all_categories',
        'permission_callback' => '__return_true',
    ));

    // ************** Get facets
    register_rest_route('vsf-wc-api/v1', '/facets', array(
        'methods' => 'GET',
        'callback' => 'vsf_wc_api_get_facets',
        'permission_callback' => '__return_true',
    ));

    // ************** Register a new user
    register_rest_route('vsf-wc-api/v1', '/register', array(
        'methods' => 'POST',
        'callback' => 'vsf_wc_api_register_user',
        'permission_callback' => '__return_true',
    ));

    // ************** Get cart
    register_rest_route('vsf-wc-api/v1', '/cart', array(
        'methods' => 'GET',
        'callback' => 'vsf_wc_api_get_cart',
        'permission_callback' => '__return_true',
    ));

    // ************** Update cart
    register_rest_route('vsf-wc-api/v1', '/cart', array(
        'methods' => 'POST',
        'callback' => 'vsf_wc_api_update_cart',
        'permission_callback' => '__return_true',
    ));
}
