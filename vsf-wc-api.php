<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/SwiftComZA/vsf-wc-api
 * @since             1.0.0
 * @package           VSF_WC_API
 *
 * @wordpress-plugin
 * Plugin Name:       Vue Storefront WooCommerce Plugin
 * Plugin URI:        https://github.com/SwiftComZA/vsf-wc-api
 * Description:       A plugin for WooCommerce to create and expose endpoints for use with the Vue Storefront integration.
 * Version:           1.0.0
 * Author:            SwiftCom
 * Author URI:        https://www.swiftcom.co.za/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       vsf-wc-api
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version - https://semver.org
 */
define( 'VSF_WC_API_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-vsf-wc-api-activator.php
 */
function activate_vsf_wc_api() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vsf-wc-api-activator.php';
	VSF_WC_API_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-vsf-wc-api-deactivator.php
 */
function deactivate_vsf_wc_api() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vsf-wc-api-deactivator.php';
	VSF_WC_API_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_vsf_wc_api' );
register_deactivation_hook( __FILE__, 'deactivate_vsf_wc_api' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-vsf-wc-api.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_vsf_wc_api() {

	$plugin = new VSF_WC_API();
	$plugin->run();

}
run_vsf_wc_api();
