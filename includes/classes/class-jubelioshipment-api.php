<?php
/**
 * The file that defines the Jubelio Shipment API class
 *
 * @author     Ali
 * @link       https://www.situsali.com
 * @since      1.0.0
 *
 * @package    Jubelio
 * @subpackage Jubelio-Shipment/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The JubelioShipment_Api API class.
 *
 * This is used to make request to RajaOngkir.com API server.
 *
 * @since      1.0.0
 * @package    Jubelio
 * @subpackage Jubelio-Shipment/includes
 * @author     Ali <ali@jubelio.com>
 */
class JubelioShipment_Api {
	/**
	 * Default URL of API
	 *
	 * @var string
	 */
	private $url = '';

	/* * Default URL of API for Staging
	 *
	 * @var string
	 */
	private $url_staging = '';
	/** Default Token of Jubelio Shipment
	 *
	 * @var string
	 */
	private $token;

	/** Construct
	 *
	 * @return void
	 */
	public function __construct() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$this->token = apply_filters( 'jubelio_shipment_token_hardcoded', get_option( 'jubelio_shipment_token', false ) );
		$this->url   = apply_filters( 'jubelio_shipment_api_url', 'https://api-shipment.jubelio.com' );
		$this->url_staging   	= apply_filters( 'jubelio_shipment_api_url_staging', 'https://shipment-api.staging-k8s.jubelio.com' );
	}

	/**
	 * Request API
	 *
	 * @since 1.0.0
	 *
	 * @param string  $method Request method.
	 * @param string  $url Request url.
	 * @param array   $args Args of wp_remote.
	 * @param boolean $validate_data Validate Data get error if empty.
	 *
	 * @return array|WP_Error
	 */
	private function request( $method, $url, $args = array(), $validate_data = true, $is_staging = '', $cache_key = '', $cache_expiration = 600 ) {
		if( !empty( $is_staging ) )
		{
			$url = $this->url_staging . $url;
		}
		else
		{
			$url = $this->url . $url;
		}
		$this->auto_generate_token();

		$headers = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'timeout' => 30, // Increase timeout to 30 seconds
		);

		if ( false !== $this->token ) {
			$headers['headers']['Authorization'] = 'Bearer ' . $this->token;
		}

		$args = array_merge( $headers, $args );

		// Create a unique cache key based on the URL and method if not provided
    if ( empty( $cache_key ) ) {
			$cache_key = md5( $method . $url . serialize( $args ) );
		}

		 // Check if a cached response exists
		 $cached_response = get_transient( $cache_key );
		 if ( $cached_response !== false ) {
				 return $cached_response;
		 }

		if ( 'post' === $method ) {
			$response = wp_remote_post( $url, $args );
		} else {
			$response = wp_remote_get( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_message() );
		}

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $response_code > 299 ) {
			return new WP_Error(
				$json['code'] ?? $response_code,
				$json['message'] ?? '',
				array(
					'http_response' => $response_code,
					'errors'        => $json['errors'] ?? '',
				)
			);
		}

		if ( $validate_data ) {
			if ( isset( $json['data'] ) && ! is_array( $json['data'] ) && ! empty( $json['data'] ) ) {
				return new WP_Error( 'broke', __( 'No Data Found', 'jubelio-shipment' ) );
			}
		}

		// Cache the response
    set_transient( $cache_key, $json, $cache_expiration );

		return $json;
	}

	/**
	 * Get Request API
	 *
	 * @since 1.0.0
	 * @param mixed   $url Request URL.
	 * @param array   $args Request Args.
	 * @param boolean $validate_data Validate Data error if empty or null.

	 * @return array|WP_Error
	 */
	private function get_request( $url, $args = array(), $validate_data = true ) {
		return $this->request( 'get', $url, $args, $validate_data );
	}

	/**
	 * Post Request
	 *
	 * @param string  $url Request url.
	 * @param array   $args Request arguments.
	 * @param boolean $validate_data Validate Data error if empty or null.
	 *
	 * @return array|WP_Error
	 */
	private function post_request( $url, $args = array(), $validate_data = true, $is_staging = '' ) {
		return $this->request( 'post', $url, $args, $validate_data, $is_staging );
	}

	/**
	 * Post Rates
	 *
	 * @param array $body Arguments.
	 * @since 1.0.0
	 * @return array|WP_Error
	 */
	public function post_rates( $body = array(), $cache_expiration = 600 ) {
		$url = '/webstore/rates/all';

		if ( ! isset( $body ) ) {
			$body = array(
				'origin'              => array(
					'address' => '',
					'area_id' => '3174021004',
					'zipcode' => '12920',
				),
				'destination'         => array(
					'address' => '',
					'area_id' => '3275091002',
					'zipcode' => '12790',
				),
				'weight'              => 1,
				'package_detail'      => array(
					'weight' => 2000,
				),
				'service_category_id' => 0,
			);
		}

		$body_json = wp_json_encode( $body );
    $cache_key = md5( $url . $body_json );

		 // Check if a cached response exists
		 $cached_response = get_transient( $cache_key );
		 if ( $cached_response !== false ) {
				 return $cached_response;
		 }

		 // Perform the POST request
		 $response = $this->post_request( $url, array( 'body' => $body_json ) );

		 // Cache the response
		 if ( ! is_wp_error( $response ) ) {
			set_transient( $cache_key, $response, $cache_expiration );
		}

		return $response;
	}

	/** Address Search
	 *
	 * @param String $search Search.
	 * @return Array | WP_Error
	 */
	public function address_search( $search ) {
		$url = '/region/?name="' + $search + '"';
		return $this->get_request( $url );
	}

	/** Validate Voucher
	 *
	 * @param String | Array $voucher_code Voucher Code.
	 * @return array | WP_Error
	 */
	public function validate_voucher( $voucher_code ) {
		$url = '/voucher/validate';

		if ( ! is_array( $voucher_code ) ) {
			$voucher_code = array( $voucher_code );
		}

		$body = array(
			'voucher_code' => $voucher_code,
		);

		$body = wp_json_encode( $body );

		return $this->post_request( $url, array( 'body' => $body ) );
	}

	/** Redeem Voucher
	 *
	 * @param Integer        $ref_no      Reference No Ex WS-ORDER-ID-TENANT_NO.
	 * @param String | Array $voucher_code Voucher Code.
	 *
	 * @return array | WP_Error
	 */
	public function redeem_voucher( $ref_no, $voucher_code ) {
		$url = '/voucher/redeem ';

		if ( ! is_array( $voucher_code ) ) {
			$voucher_code = array( $voucher_code );
		}

		$body = array(
			'ref_no'       => $ref_no,
			'voucher_code' => $voucher_code,
		);

		$body = wp_json_encode( $body );

		return $this->post_request( $url, array( 'body' => $body ) );
	}

	/** Get Services Categories */
	public function get_service_categories() {
		$url = '/services/categories';
		return $this->get_request( $url, array(), false );
	}

	/** Get All Couriers
	 *
	 * @param Boolean $sort Sort Courier.
	 */
	public function get_all_couriers( $sort = true, $cache_expiration = 600 ) {

			// Retrieve Category Data.
			$categories = $this->get_service_categories();
			if ( is_wp_error( $categories ) ) {
					return $categories;
			}

			$url = '/courier-services/list?service_category_ids[]=1&service_category_ids[]=2&service_category_ids[]=3&service_category_ids[]=4&service_category_ids[]=5&service_category_ids[]=6';
			$cache_key = md5( $url );

			// Check if a cached response exists
			$cached_result = get_transient( $cache_key );
			if ( $cached_result !== false ) {
					return $cached_result;
			}

			$result = $this->get_request( $url );

			if ( is_wp_error( $result ) ) {
					return $result;
			}

			if ( $sort ) {
					usort(
							$result,
							function( $a, $b ) {
									return $a['courier_id'] - $b['courier_id'];
							}
					);
			}

			$get_service_name = function( $item ) use ( $categories ) {
					$service_category_id = $item['service_category_id'];
					//phpcs:ignore
					$service_category_index = array_search( $service_category_id, array_column( $categories, 'service_category_id' ) );

					$service_category = $categories[ $service_category_index ];
					return $service_category['name'];
			};

			$retval = array();
			foreach ( $result as $item ) {
					$service_category_name         = $get_service_name( $item );
					$item['service_category_name'] = $service_category_name;
					$retval[]                      = $item;
			}

			if ( empty( $retval ) ) {
					return new WP_Error( 'broke', __( 'No Data Found', 'jubelio-shipment' ) );
			}

			// Cache the result
			set_transient( $cache_key, $retval, $cache_expiration );

			return $retval;
	}


	/**
	 * Jubelio Shipment Auto Generate Token
	 *
	 * @return Array | WP_Error
	 */
	public function auto_generate_token() {

		$cached = get_transient( 'jubelio_shipment_token' );

		if ( ! empty( $cached ) ) {
			return $cached;
		}

		$jubelio_shipment_options = get_option( 'woocommerce_jubelioshipment_settings', false );
		if ( false === $jubelio_shipment_options ) {
			return new WP_Error( 'No Options' );
		}

		$client_id     = $jubelio_shipment_options['client_id'] ?? '';
		$client_secret = $jubelio_shipment_options['client_secret'] ?? '';

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return new WP_Error( 'No Client ID and Client Secret' );
		}

		$response = $this->generate_token( $client_id, $client_secret );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_message() );
		}

		$this->token = $response['token'];
		update_option( 'jubelio_shipment_token', $this->token );
		set_transient( 'jubelio_shipment_token', $response, MINUTE_IN_SECONDS * 15 );

		return $response;
	}

	/**
	 * Jubelio Generate Token by Client ID and Client Secret
	 *
	 * @param String $client_id Client ID.
	 * @param String $client_secret Client Secret.
	 * @return Array | WP_Error
	 */
	public function generate_token( string $client_id, string $client_secret ) {
		$url = $this->url . '/auth/generate-token';

		$headers = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		$body = array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
		);

		$body = wp_json_encode( $body );
		$args = array_merge( $headers, array( 'body' => $body ) );

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_message() );
		}

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			return new WP_Error(
				$json['code'] ?? $response_code,
				$json['message'] ?? '',
				array(
					'http_response' => $response_code,
					'errors'        => $json['errors'] ?? '',
				)
			);
		}

		return $json;
	}

		/** Push Order To Jubelio Omnichannel
		 *
		 * @param Integer $webstore_id Webstore ID.
		 * @param Integer $order_id Order ID.
		 * @param Object  $order Order.
		 *
		 * @return Array | WP_Error
		 */
	public function push_to_jubelio_omni( $webstore_id, $order_id, $order ) {
		$url = 'https://push.jubelio.com/woocomerce/notify/order/update_order';

		$headers = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		$body = wp_json_encode(
			array(
				'store_id'     => (int) $webstore_id,
				'order_id'     => (int) $order_id,
				'order_key'    => $order->get_order_key(),
				'status'       => $order->get_status(),
				'date_created' => $order->get_date_created(),
			)
		);

		$args     = array_merge( $headers, $body );
		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_message() );
		}

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			return new WP_Error(
				$json['code'],
				$json['message'],
				array(
					'http_response' => $response_code,
					'errors'        => $json['errors'],
				)
			);
		}

		if ( ! is_array( $json['data'] ) && ! empty( $json['data'] ) ) {
			return new WP_Error( 'broke', __( 'No Data Found', 'jubelio-shipment' ) );
		}

		return $json;
	}

	/** Get All Locations Address from Jubelio Omni */
	public function get_all_locations_from_jubelio() {

		$jubelio_auth = get_option( 'jubelio_auth' );
		if ( false === $jubelio_auth ) {
			return new WP_Error(
				401,
				__( 'Missing authentication', 'jubelio-shipment' ),
				array(
					'http_response' => 401,
					'errors'        => 'Unauthorized',
				)
			);
		}

		$maxpage = 50;
		$url     = 'https://api-lb.jubelio.com/locations/list?page=1&pageSize=' . $maxpage . '&sortBy=location_name&sortDirection=ASC';

		$headers = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $jubelio_auth,
			),
		);

		$response = wp_remote_get( $url, $headers );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_message() );
		}

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			return new WP_Error(
				$json['statusCode'],
				$json['message'],
				array(
					'http_response' => $response_code,
					'errors'        => $json['error'],
				)
			);
		}

		return $json;
	}

	/** Whitelist Domain for Getting Maps for Google Map
	 *
	 * @param String $domain Domain Name.
	 * @return Array | WP_Error
	 */
	public function whitelist_domain( $domain ) {
		$url  = '/whitelist-domain-grabmaps';
		$body = array( 'domain' => $domain );
		$body = wp_json_encode( $body );

		return $this->post_request( $url, array( 'body' => $body ) );
	}

	/** Whitelist Domain for Getting Maps for Grab Map
	 *
	 * @param String $domain Domain Name.
	 * @return Array | WP_Error
	 */
	public function inisialize_map( $domain ) {
		$url  = '/auth/initialize-map';
		$jubelio_auth = get_option( 'jubelio_shipment_token' );

		$error_arr = array(
			'http_response' => 401,
			'errors'        => 'Unauthorized',
		);

		if ( false === $jubelio_auth ) {
			$error_message = new WP_Error(
				401,
				__( 'Missing authentication', 'jubelio-shipment' ),
				$error_arr
			);

			return $error_message;
		}

		$body = array( 'domain' => $domain );
		$body = wp_json_encode( $body );

		$headers = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $jubelio_auth,
				'Origin' => $domain,
			),
		);

		$args = array_merge( $headers, array( 'body' => $body ) );

		return $this->post_request( $url, $args, false );
	}
	/** Check Promotion Usage
	 *
	 * @param Integer $promotion_id Promotion ID.
	 * @return Array | WP_Error
	 */
	public function check_promotion_usage( $promotion_id ) {
		$url  = '/promotion/usage';
		$body = array(
			'promotion_id' => $promotion_id,
			'source'       => 'WEBSTORE',
		);
		$body = wp_json_encode( $body );
		return $this->post_request( $url, array( 'body' => $body ) );
	}

}
