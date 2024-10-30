<?php
/**
 * Jubelio Shipment Helper
 *
 * @author  Ali
 * @link    https://www.situsali.com
 * @package Jubelio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'jubelioshipment_autoload' ) ) :

	/**
	 * Class autoload
	 *
	 * @since 1.0.0
	 *
	 * @param string $class Class name.
	 *
	 * @return void
	 */
	function jubelioshipment_autoload( $class ) {
		$class = strtolower( $class );

		if ( strpos( $class, 'jubelioshipment' ) !== 0 ) {
			return;
		}

		require_once JUBELIO_SHIPMENT_PATH . 'includes/classes/class-' . str_replace( '_', '-', $class ) . '.php';
	}

endif;


if ( ! function_exists( 'jubelioshipment_is_plugin_active' ) ) :
	/**
	 * Check if plugin is active
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file Plugin file name.
	 */
	function jubelioshipment_is_plugin_active( $plugin_file ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_file );
	}
endif;

if ( ! function_exists( 'jubelioshipment_get_plugin_data' ) ) :
	/**
	 * Get plugin data
	 *
	 * @since 1.2.13
	 *
	 * @param string $selected Selected data key.
	 * @param string $selected_default Selected data key default value.
	 * @param bool   $markup If the returned data should have HTML markup applied.
	 * @param bool   $translate If the returned data should be translated.
	 *
	 * @return (string|array)
	 */
	function jubelioshipment_get_plugin_data( $selected = null, $selected_default = '', $markup = false, $translate = true ) {
		static $plugin_data;

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_null( $plugin_data ) ) {
			$plugin_data = get_plugin_data( JUBELIO_SHIPMENT_FILE, $markup, $translate );
		}

		if ( ! is_null( $selected ) ) {
			return isset( $plugin_data[ $selected ] ) ? $plugin_data[ $selected ] : $selected_default;
		}

		return $plugin_data;
	}

endif;

if ( ! function_exists( 'extract_coordinate' ) ) :
	/**
	 * Extract Coordinate
	 *
	 * @since 1.2.0
	 *
	 * @param string $coordinate Coordinate.
	 * @return array
	 */
	function extract_coordinate( $coordinate ) {
		$coordinate = explode( ',', preg_replace( "/[\(\)']+/", '', $coordinate ) );
		return array(
			'latitude'  => $coordinate[0] ?? '',
			'longitude' => $coordinate[1] ?? '',
		);
	}

endif;

if ( ! function_exists( 'extract_area_id' ) ) :
	/**
	 * Extract Area ID
	 *
	 * @since 1.2.0
	 *
	 * @param string $area_id Area ID.
	 * @return array
	 */
	function extract_area_id( $area_id ) {
		return array(
			'province'    => substr( $area_id, 0, 2 ),
			'city'        => substr( $area_id, 0, 4 ),
			'district'    => substr( $area_id, 0, 6 ),
			'subdistrict' => $area_id,
		);
	}

endif;

if ( ! function_exists( 'extract_region_by_address' ) ) :
	/**
	 * Extract Region by Full Address
	 *
	 * @since 1.2.0
	 *
	 * @param string $full_address Full Address.
	 * @return array
	 */
	function extract_region_by_address( $full_address ) {
		$region = explode( ',', $full_address );

		$province_zipcode = array();
		if ( isset( $region[3] ) ) {
			$province_zipcode = explode( '. ', $region[3] );
		}

		return array(
			'subdistrict' => $region[0] ?? '',
			'district'    => $region[1] ?? '',
			'city'        => $region[2] ?? '',
			'province'    => $province_zipcode[0] ?? '',
			'zipcode'     => $province_zipcode[1] ?? '',
		);
	}

endif;

if ( ! function_exists( 'get_shipping_choosen' ) ) :
	/** Get Shipping by Shipping Choosen.
	 *
	 * @return Object | Null
	 */
	function get_shipping_choosen() {

		$packages       = WC()->shipping->get_packages();
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' )[0] ?? null;
		$rate_selected  = null;

		if ( null === $chosen_methods ) {
			return $rate_selected;
		}

		$packages    = WC()->cart->get_shipping_packages();
		$session_key = '';
		foreach ( $packages as $package_key => $package ) {
			$session_key = 'shipping_for_package_' . $package_key;
		}

		$get_rates = WC()->session->get( $session_key )['rates'] ?? array();

		foreach ( $get_rates as $method_id => $rate ) {
			if ( $chosen_methods === $method_id ) {
				$rate_selected = $rate;
			}
		}

		return $rate_selected;
	}
endif;

if ( ! function_exists( 'str_contains' ) ) :
	/** String Contains for PHP before 8
	 *
	 * @param String $haystack Haystack.
	 * @param String $needle Needle.
	 * @return Boolean
	 */
	function str_contains( $haystack, $needle ) {
		return ( strpos( $haystack, $needle ) !== false );
	}
endif;

if ( ! function_exists( 'is_https' ) ) :
	/** Check is protocol using https or not
	 *
	 * @return Boolean
	 */
	function is_https() {
		return ( isset( $_SERVER['HTTPS'] ) && 'off' !== strtolower( $_SERVER['HTTPS'] ) );
	}
endif;


if ( ! function_exists( 'better_is_checkout' ) ) :
	/**
	 * Source: https://stackoverflow.com/a/69530749
	 * Checks if checkout is the current page.
	 *
	 * @return boolean
	 */
	function better_is_checkout() {
		$checkout_path    = wp_parse_url( wc_get_checkout_url(), PHP_URL_PATH );
		$http_or_https    = is_https() ? 'https' : 'http';
		$current_url_path = wp_parse_url( "$http_or_https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", PHP_URL_PATH );

		return (
			null !== $checkout_path
			&& null !== $current_url_path
			&& trailingslashit( $checkout_path ) === trailingslashit( $current_url_path )
		);
	}

endif;

if ( ! function_exists( 'better_is_cart' ) ) :
	/**
	 * Source: https://stackoverflow.com/a/69530749
	 * Checks if cart is the current page.
	 *
	 * @return boolean
	 */
	function better_is_cart() {
		$cart_path        = wp_parse_url( wc_get_cart_url(), PHP_URL_PATH );
		$http_or_https    = is_https() ? 'https' : 'http';
		$current_url_path = wp_parse_url( "$http_or_https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", PHP_URL_PATH );

		return (
			null !== $cart_path
			&& null !== $current_url_path
			&& trailingslashit( $cart_path ) === trailingslashit( $current_url_path )
		);
	}

endif;

if ( ! function_exists( 'get_my_domain' ) ) :
	/** Get My Domain
	 *
	 * @return String
	 */
	function get_my_domain() {
		$parse_url = wp_parse_url( site_url() );
		return $parse_url['host'] ?? '';
	}
endif;

if ( ! function_exists( 'refresh_current_page' ) ) :
	/** Refresh current page */
	function refresh_current_page() {
		header( 'Location: ' . $_SERVER['REQUEST_URI'] );
	}
endif;

if ( ! function_exists( 'grabmap_auth' ) ) :
	/** Check is domain contains with jubelio.store
	 *
	 * @return String
	 */

	 function grabmap_auth() {

		$my_domain 			= get_my_domain();
		$api 						= new JubelioShipment_Api();
		$grabmap_status = '';

		if ( ! empty( $my_domain ) ) {
			/** Nunggu TIM API Rapihin Table */
			// $response = $api->inisialize_map( $my_domain );

			// if ( is_wp_error( $response ) ) {
			// 	$grabmap_status = 'disabled';
			// } else {
			// 	$grabmap_status = 'enabled';
			// }

			$jubelio_auth = get_option( 'jubelio_shipment_token' );

			if ( false === $jubelio_auth ) {
				$grabmap_status = 'disabled';
			}
			else {
				$grabmap_status = 'enabled';

				if ( ! str_contains( $my_domain, '.jubelio.store' ) ) {
					$response = $api->whitelist_domain( $my_domain );
					if ( ! is_wp_error( $response ) ) {
						update_option( 'jubelio_shipment_domain', $my_domain );
						refresh_current_page();
					}
				}
			}
		}

		return $grabmap_status;

	 }
endif;