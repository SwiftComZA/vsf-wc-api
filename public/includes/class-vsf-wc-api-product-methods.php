<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Defines methods used by the product endpoints callbacks.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    VSF_WC_API
 * @subpackage VSF_WC_API/public
 * @author     SwiftCom <hello@swiftcom.co.za>
 */
class VSF_WC_API_Product_Methods {

	/**
	 * Gets products with the wc_get_products() WooCommerce function,
   * formats it with format_product() and adds pagination
   * data if multiple products are returned.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function product_query( $query ) {
    // Get products query
    $products_query_response = wc_get_products( $query );

    // If the paginate query parameter is true, the response from wc_get_products() looks different
    $products = $query['paginate'] ? $products_query_response->products : $products_query_response;

    // This function is needed if we want to order by price
    if ( strtolower( $query[ 'orderby' ] ) == 'price' ) {
      $products = wc_products_array_orderby( $products, 'price', $query[ 'order' ] );
    }

    // Prepare products array
    $products_array = [];

    foreach ($products as $product) {
      // Format product and add to array
      $products_array[] = $this->format_product( $product );
    }

    // Prepare return data
    $return_data = [];

    // Return only pagination details and products in sub array if pagination is true
    if ( $query[ 'paginate' ] ) {
      // Add product and pagination data to return object
      $return_data[ 'products' ] = $products_array;
      $return_data[ 'total' ]    = $products_query_response->total;
      $return_data[ 'pages' ]    = $products_query_response->max_num_pages;
      $return_data[ 'page' ]     = intval( $query[ 'page' ] );
      $return_data[ 'perPage' ]  = intval( $query[ 'limit' ] );
    }
    else {
      $return_data = $products_array;
    }

    return $return_data;
  }


	/**
	 * Formats a product's data and returns the formatted product.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
  public function format_product( $product ) {
    
    $formatted_product = [
      'id'          => $product->get_id(),
      'type'        => $product->get_type(),
      'title'       => $product->get_title(),
      'description' => $product->get_description(),
      'slug'        => $product->get_slug(),
      'price'       => [ 'original' => $product->get_regular_price(), 'current' => $product->get_sale_price() ],
      'sku'         => $product->get_sku(),
      'sales'       => $product->get_total_sales(),
      'inStock'     => $product->is_in_stock(),
      'updatedAt'   => $product->get_date_modified(),
      'createdAt'   => $product->get_date_created(),
      'coverImage'  => wp_get_attachment_image_url( $product->get_image_id(), "full" ),
      'parent'      => $product->get_parent_id(),
      'featured'    => $product->is_featured(),
    ];

    $formatted_product[ 'categories' ] = [];
    // Get product categories
    foreach( $product->get_category_ids() as $category_id ) {
      $formatted_product[ 'categories' ][] =  get_term_parents_list( $category_id, 'product_cat', [ 'format' => 'slug', 'separator' => '/', 'link' => false ] );
    }

    // Get product gallery image urls
    $gallery_images = [];

    foreach ( $product->get_gallery_image_ids() as $gallery_image_id ) {
      $gallery_images[] = wp_get_attachment_image_url( $gallery_image_id, 'full' );
    }
    $formatted_product[ 'images' ] = $gallery_images;

    // Get parent product atributes this way
    $attribute_names = $product->get_attributes();

    $attributes = [];
    foreach ( $attribute_names as $key => $val ) {
      $attributes[ $key ] = $product->get_attribute( $key );
    }

    $formatted_product[ 'attributes' ] = $attributes;

    // Add variation data
    $available_variations = [];
    if ( $product->get_type() === 'variable' ) {
      $variation_ids = $product->get_children();

      // Iterate variations
      foreach ( $variation_ids as $variation_id ) {
        // Prepare variation data
        $variation = wc_get_product( $variation_id );

        // Add formatted variation to array
        $available_variations[] = $this->format_product( $variation );
      }
    }
    $formatted_product[ 'variants' ] = $available_variations;

    return $formatted_product;
  }

}