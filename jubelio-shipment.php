<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/jubelio
 * @since             1.0.0
 * @package           Jubelio
 * @subpackage        Jubelio-Shipment
 *
 * @wordpress-plugin
 * Plugin Name:       Jubelio Shipment
 * Plugin URI:        https://github.com/jubelio/jubelio-shipment
 * Description:       Simple and fast WooCommerce plugin with various courier options such as Grab Express, JNE, SiCepat, Paxel, and many more
 * Version:           1.8.2
 * Author:            Jubelio
 * Author URI:        https://github.com/jubelio
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       jubelio-shipment
 * Domain Path:       /languages
 *
 * WC requires at least: 9.0.0
 * WC tested up to: 9.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'JUBELIO_SHIPMENT_VERSION', '1.8.2' );
define( 'JUBELIO_SHIPMENT_METHOD_ID', 'jubelioshipment' );
define( 'JUBELIO_SHIPMENT_FILE', __FILE__ );
define( 'JUBELIO_SHIPMENT_PATH', plugin_dir_path( JUBELIO_SHIPMENT_FILE ) );
define( 'JUBELIO_SHIPMENT_URL', plugin_dir_url( JUBELIO_SHIPMENT_FILE ) );
define( 'JUBELIO_SHIPMENT_WC_VERSION', '9.0.0' );

require_once JUBELIO_SHIPMENT_PATH . 'includes/helpers.php';

// Show an admin notice if WooCommerce is not active
function jubelioshipment_woocommerce_inactive_notice() {
    echo '<div class="notice notice-error"><p>';
    _e( 'Jubelio Shipment requires WooCommerce to be active.', 'jubelioshipment' );
    echo '</p></div>';
}

// Show an admin notice if the WooCommerce version is not sufficient
function jubelioshipment_woocommerce_version_notice() {
    echo '<div class="notice notice-error"><p>';
    printf(
        __( 'Jubelio Shipment requires WooCommerce version %s or higher.', 'jubelioshipment' ),
        JUBELIO_SHIPMENT_WC_VERSION
    );
    echo '</p></div>';
}

// Check if WooCommerce is active and the required version is installed
function jubelioshipment_check_woocommerce_version() {
    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'jubelioshipment_woocommerce_inactive_notice' );
        return false;
    }

    // Check the WooCommerce version
    if ( version_compare( WC()->version, JUBELIO_SHIPMENT_WC_VERSION, '<' ) ) {
        add_action( 'admin_notices', 'jubelioshipment_woocommerce_version_notice' );
        return false;
    }

    return true;
}

// Activation hook to check WooCommerce version
function jubelioshipment_activation_check() {
    if ( ! jubelioshipment_check_woocommerce_version() ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            sprintf(
                __( 'Jubelio Shipment requires WooCommerce version %s or higher.', 'jubelioshipment' ),
                JUBELIO_SHIPMENT_WC_VERSION
            ),
            'Plugin Activation Error',
            array( 'back_link' => true )
        );
    }
}
register_activation_hook( __FILE__, 'jubelioshipment_activation_check' );

// Initialize the plugin
function jubelioshipment_init() {
    if ( jubelioshipment_check_woocommerce_version() ) {
        if ( function_exists( 'jubelioshipment_autoload' ) ) {
            spl_autoload_register( 'jubelioshipment_autoload' );
        }

        if ( class_exists( 'JubelioShipment' ) ) {
            JubelioShipment::get_instance();
        }
    }
}
add_action( 'plugins_loaded', 'jubelioshipment_init' );

?>
