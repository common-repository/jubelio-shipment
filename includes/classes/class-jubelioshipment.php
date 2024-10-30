<?php
/**
 * The file that defines the core plugin classes
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
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
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Jubelio
 * @subpackage Jubelio-Shipment/includes
 * @author     Ali <ali@jubelio.com>
 */
class JubelioShipment {

	/**
	 * Shipping base country
	 *
	 * @var string
	 */
	private $base_country = 'ID';

	/**
	 * Hold an instance of the class
	 *
	 * @var JubelioShipment
	 */
	private static $instance = null;

	/**
	 * Jubelio Shipment API
	 *
	 * @var JubelioShipment_Api
	 */
	private $api;

	/**
	 * The object is created from within the class itself
	 * only if the class has no instance.
	 *
	 * @return JubelioShipment
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new JubelioShipment();
		}

		return self::$instance;
	}
	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$this->load_all_hooks();
		$this->api = new JubelioShipment_Api();
	}

	/** Load all Hooks */
	public function load_all_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'register_shipping_method' ) );
		add_filter( 'woocommerce_shipping_' . JUBELIO_SHIPMENT_METHOD_ID . '_is_available', array( $this, 'check_is_available' ), 10, 2 );

		// Override All Address Fields in Checkout and User Account.
		add_filter( 'woocommerce_default_address_fields', array( $this, 'jubeship_override_address_fields' ), 1000 );
		add_filter( 'woocommerce_customer_meta_fields', array( $this, 'jubeship_override_address_fields_in_admin' ), 1000 );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'jubeship_checkout_field_update_order_meta' ) );

		// Add Email and Phone Field in Shipping Fields.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'phone_email_shipping_checkout_fields' ) );

		// Load JS/CSS all assets from Backend and Frontend.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_assets' ), 20 );

		add_action( 'wp_footer', array( $this, 'jubeship_footer_script' ), 100 );
		add_action( 'admin_footer', array( $this, 'jubeship_admin_footer_script' ), 100 );

		// Modify $package in Cart.
		add_filter( 'woocommerce_add_cart_item', array( $this, 'jubeship_flag_in_cart' ), 1 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'jubeship_flag_in_cart' ), 1 );
		add_action( 'woocommerce_review_order_after_shipping', array( $this, 'jubeship_extra_info_shipping' ), 10 );
		add_action( 'woocommerce_review_order_before_shipping', array( $this, 'jubeship_multiorigin' ), 10 );

		// Handling Ajax.
		add_action( 'wp_ajax_jubelio_shipment_set_coordinate', array( $this, 'ajax_set_coordinate' ), 1000 );
		add_action( 'wp_ajax_nopriv_jubelio_shipment_set_coordinate', array( $this, 'ajax_set_coordinate' ), 1000 );

		add_action( 'wp_ajax_jubelio_shipment_update_session_and_meta', array( $this, 'ajax_update_session_and_meta' ), 1000 );
		add_action( 'wp_ajax_nopriv_jubelio_shipment_update_session_and_meta', array( $this, 'ajax_update_session_and_meta' ), 1000 );

		add_action( 'wp_ajax_jubelio_shipment_set_voucher', array( $this, 'ajax_set_voucher' ), 1000 );
		add_action( 'wp_ajax_nopriv_jubelio_shipment_set_voucher', array( $this, 'ajax_set_voucher' ), 1000 );

		add_action( 'wp_ajax_jubelio_shipment_remove_voucher_code', array( $this, 'ajax_remove_voucher_code' ), 1000 );
		add_action( 'wp_ajax_nopriv_jubelio_shipment_remove_voucher_code', array( $this, 'ajax_remove_voucher_code' ), 1000 );

		add_action( 'wp_ajax_jubelio_shipment_set_shipping_insurance', array( $this, 'ajax_set_shipping_insurance' ), 1000 );
		add_action( 'wp_ajax_nopriv_jubelio_shipment_set_shipping_insurance', array( $this, 'ajax_set_shipping_insurance' ), 1000 );

		add_action( 'wp_ajax_jubelio_shipment_change_shipping_origin', array( $this, 'ajax_change_shipping_origin' ), 1000 );
		add_action( 'wp_ajax_nopriv_jubelio_shipment_change_shipping_origin', array( $this, 'ajax_change_shipping_origin' ), 1000 );

		// Handling Fee for Jubelio Shipment Voucher.
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_fee_to_cart' ), 100 );
		add_filter( 'woocommerce_cart_totals_fee_html', array( $this, 'jubeship_fee_html' ), 10, 3 );

		// Validate before checkout.
		add_action( 'woocommerce_checkout_process', array( $this, 'jubeship_checkout_process' ), );

		// Checkout modify.
		add_action( 'woocommerce_checkout_create_order_shipping_item', array( $this, 'jubeship_create_order_shipping_item' ), 10 );
		add_action( 'woocommerce_gateway_title', array( $this, 'jubeship_gateway_title' ), 10, 3 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'jubeship_order_processed' ), 10, 3 );
		add_action( 'woocommerce_thankyou', array( $this, 'jubeship_after_checkout_form' ), 10, 3 );

		// Modify Information of Address in MyAccount Page and Thankyou Page.
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'jubeship_my_account_address_form' ) );
		add_filter( 'woocommerce_order_get_formatted_billing_address', array( $this, 'jubeship_thankyou_page_billing_address_form' ), 10, 3 );
		add_filter( 'woocommerce_order_get_formatted_shipping_address', array( $this, 'jubeship_thankyou_page_shipping_address_form' ), 10, 3 );
		add_filter( 'woocommerce_shipping_chosen_method', array( $this, 'prevent_shipping_method_selection_when_single_method'), 10 );
		add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'override_shipping_label_for_promotion' ), 10, 2 );
	}

	/** Get Option
	 *
	 * @param String $option_name Option Name.
	 * @param Mixed  $default Default.
	 */
	public function get_option( $option_name, $default = null ) {
		$global_options = get_option( 'woocommerce_jubelioshipment_settings', null );
		if ( null === $global_options ) {
			return $default;
		}
		$result = $global_options[ $option_name ] ?? $default;
		return $result;
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'jubelio-shipment', false, basename( JUBELIO_SHIPMENT_PATH ) . '/languages' );
	}

	/**
	 * Register shipping method to WooCommerce.
	 *
	 * @since 1.0.0
	 *
	 * @param array $methods Registered shipping methods.
	 */
	public function register_shipping_method( $methods ) {
		if ( class_exists( 'JubelioShipment_Shipping_Method' ) ) {
			$methods[ JUBELIO_SHIPMENT_METHOD_ID ] = 'JubelioShipment_Shipping_Method';
		}

		return $methods;
	}

	/**
	 * Check if this method available
	 *
	 * @since 1.0.0
	 * @param boolean $available Current status is available.
	 * @param array   $package Current order package data.
	 * @return bool
	 */
	public function check_is_available( $available, $package ) {
		if ( WC()->countries->get_base_country() !== $this->base_country ) {
			return false;
		}

		if ( empty( $package ) || empty( $package['contents'] ) || empty( $package['destination'] ) ) {
			return false;
		}

		return $available;
	}
	/**
	 * Enqueue Frontend Assets.
	 *
	 * @since 1.0.0
	 * @param string $hook Passed screen ID in admin area.
	 */
	// phpcs:ignore
	public function enqueue_frontend_assets( $hook = null ) {

		$js_url  = JUBELIO_SHIPMENT_URL . 'assets/js/jubelio-shipment-fe.js';
		$css_url = JUBELIO_SHIPMENT_URL . 'assets/css/jubelio-shipment.css';

		wp_enqueue_style(
			'jubelio_shipment_css_fe',
			$css_url,
			array(),
			jubelioshipment_get_plugin_data( 'Version' ),
			''
		);

		wp_enqueue_script(
			'jubelio_shipment_fe',
			$js_url,
			array( 'jquery', 'selectWoo' ),
			jubelioshipment_get_plugin_data( 'Version' ),
			true
		);

		wp_localize_script(
			'jubelio_shipment_fe',
			'jubelio_shipment',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'jubelio_shipment_nonce' ),
			)
		);
	}

	/**
	 * Enqueue backend scripts.
	 *
	 * @since 1.0.0
	 * @param string $hook Passed screen ID in admin area.
	 */
	public function enqueue_backend_assets( $hook ) {

		$hooks = array(
			'profile.php',
			'woocommerce_page_wc-settings',
		);

		if ( ( ! is_admin() ) || ( ! in_array( $hook, $hooks, true ) ) ) {
			return;
		}

		// Define the scripts URL.
		$js_url  = JUBELIO_SHIPMENT_URL . 'assets/js/jubelio-shipment-be.js';
		$css_url = JUBELIO_SHIPMENT_URL . 'assets/css/jubelio-shipment.css';

		wp_enqueue_style(
			'jubelio_shipment_be',
			$css_url,
			array(),
			jubelioshipment_get_plugin_data( 'Version' ),
			''
		);

		wp_enqueue_script(
			'jubelio_shipment_be', // Give the script a unique ID.
			$js_url, // Define the path to the JS file.
			array( 'jquery' ), // Define dependencies.
			jubelioshipment_get_plugin_data( 'Version' ), // Define a version (optional).
			true // Specify whether to put in footer (leave this true).
		);

		wp_localize_script(
			'jubelio_shipment',
			'jubelio_shipment_be',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'jubelio_shipment_nonce' ),
			)
		);
	}

	/** Jubelio Shipment Override Addresss Fields
	 *
	 * @param Array $fields Checkout Fields.
	 */
	public function jubeship_override_address_fields( $fields ) {

		// Use this if map using google map
		$google_api = $this->get_option( 'google_api' );

		// Use this if map using Grabmap
		$grabmap_status = grabmap_auth();

		if ( ( is_checkout() || is_account_page() ) ) {
			if ( 'ID' !== WC()->customer->get_billing_country() ) {
				return $fields;
			}
		}

		$fields['address_search'] = array(
			'label'       => __( 'Province, City, District and Zipcode', 'jubelio-shipment' ),
			'type'        => 'select',
			'options'     => array( '' => 'Selected' ),
			'required'    => true,
			'priority'    => 42,
			'class'       => 'form-row-wide',
			'placeholder' => __( 'Please enter 3 or more characters', 'jubelio-shipment' ),
		);

		if ( 'enabled' === $grabmap_status ) {
			$fields['pinpoint_location'] = array(
				'label'             => __( 'Pinpoint Location ( Optional, Required for Gojek/Grab )', 'jubelio-shipment' ),
				'type'              => 'text',
				'class'             => array( 'address-field', 'update_totals_on_change' ),
				'input_class'       => array( 'update_meta_on_change' ),
				'priority'          => 43,
				'placeholder'       => __( 'Click if you want to use instant courier', 'jubelio-shipment' ),
				'custom_attributes' => array( 'readonly' => 'readonly' ),
				'description'				=> __( 'Required for Gojek/Grab services. This will be used to calculate more accurate shipping rates and determine the delivery location.' , 'jubelio-shipment')
			);
			$fields['coordinate']        = array(
				'label'             => '',
				'type'              => 'hidden',
				'class'             => array( 'address-field', 'update_totals_on_change', 'force_hidden' ),
				'input_class'       => array( 'update_meta_on_change' ),
				'priority'          => 44,
				'custom_attributes' => array( 'readonly' => 'readonly' ),
			);
		}

		// Extra fields for Jubelio Omnichannel.
		$fields['full_address'] = array(
			'label'       => __( 'Full Address', 'jubelio-shipment' ),
			'type'        => 'textarea',
			'required'    => true,
			'priority'    => 45,
			'class'       => 'form-row-wide',
			'input_class' => array( 'update_meta_on_change' ),
		);

		$fields['new_subdistrict'] = array(
			'label'    => '',
			'type'     => 'hidden',
			'class'    => 'force_hidden',
			'priority' => 50,
		);

		$fields['district'] = array(
			'label'    => '',
			'type'     => 'hidden',
			'class'    => 'force_hidden',
			'priority' => 51,
		);

		$fields['subdistrict'] = array(
			'label'    => '',
			'type'     => 'hidden',
			'class'    => 'force_hidden',
			'priority' => 52,
		);
		// Hide some Woocommerce's field for simplicity purpose.
		// We use search address to auto fill those fields.
		$hidden_fields = array(
			'state',
			'city',
			'address_1',
			'address_2',
			'postcode',
		);

		foreach ( $hidden_fields as $hidden_field ) {
			$fields[ $hidden_field ]['type']  = 'hidden';
			$fields[ $hidden_field ]['label'] = '';
			$fields[ $hidden_field ]['class'] = 'force_hidden';
		}

		return $fields;
	}

	/** Jubelio Shipment Override Addresss Fields in WP Admin
	 *
	 * @param Array $admin_fields Checkout Fields.
	 */
	public function jubeship_override_address_fields_in_admin( $admin_fields ) {

		$pinpoint_location = array(
			'label' => __( 'Pinpoint Location', 'jubelio-shipment' ),
			'type'  => 'text',
		);

		$full_address = array(
			'label' => __( 'Full Address', 'jubelio-shipment' ),
			'type'  => 'textarea',
		);

		$admin_fields['billing']['fields']['billing_pinpoint_location']   = $pinpoint_location;
		$admin_fields['shipping']['fields']['shipping_pinpoint_location'] = $pinpoint_location;

		$admin_fields['billing']['fields']['billing_full_address']   = $full_address;
		$admin_fields['shipping']['fields']['shipping_full_address'] = $full_address;

		return $admin_fields;
	}

	/** Jubelio Shipment Handling Order Meta for Custom Fields
	 *
	 * @param Integer $order_id Order Id.
	 */
	public function jubeship_checkout_field_update_order_meta( $order_id ) {

		$meta_names = array(
			'billing_pinpoint_location',
			'shipping_pinpoint_location',
			'billing_coordinate',
			'shipping_coordinate',
			'billing_full_address',
			'shipping_full_address',
			'billing_district',
			'shipping_district',
			'billing_new_subdistrict',
			'shipping_new_subdistrict',
			'billing_subdistrict',
			'shipping_subdistrict',
			'shipping_phone',
			'shipping_email',
		);

		$save_to_meta = function( $meta_name ) use ( &$order_id ) {
			//phpcs:ignore
			update_post_meta( $order_id, $meta_name, sanitize_text_field( $_POST[ $meta_name ] ) );
		};

		//phpcs:ignore
		$different_address = rest_sanitize_boolean( $_POST['ship_to_different_address'] );

		// set default value for some fields.
		$set_default_for_some_fields = function( $post ) {
			if ( ! isset( $post['_billing_new_subdistrict'] ) || empty( $post['_billing_new_subdistrict'] ) ) {
				$_POST['_billing_new_subdistrict'] = $_POST['billing_address_2']; //phpcs:ignore
			}
			if ( ! isset( $post['_shipping_new_subdistrict'] ) || empty( $post['_shipping_new_subdistrict'] ) ) {
				$_POST['_shipping_new_subdistrict'] = $_POST['shipping_address_2']; //phpcs:ignore
			}

			$billing_extract_region  = extract_region_by_address( $_POST['billing_address_1'] ); //phpcs:ignore
			$shipping_extract_region = extract_region_by_address( $_POST['shipping_address_1'] ); //phpcs:ignore

			if ( ! isset( $post['billing_district'] ) || empty( $post['billing_district'] ) ) {
				$_POST['billing_district'] = $billing_extract_region[ 'district' ]; //phpcs:ignore
			}

			if ( ! isset( $post['billing_subdistrict'] ) || empty( $post['billing_subdistrict'] ) ) {
				$_POST['billing_subdistrict'] = $billing_extract_region[ 'subdistrict' ]; //phpcs:ignore
			}

			if ( ! isset( $post['shipping_district'] ) || empty( $post['shipping_district'] ) ) {
				$_POST['shipping_district'] = $shipping_extract_region[ 'district' ]; //phpcs:ignore
			}

			if ( ! isset( $post['shipping_subdistrict'] ) || empty( $post['shipping_subdistrict'] ) ) {
				$_POST['shipping_subdistrict'] = $shipping_extract_region[ 'subdistrict' ]; //phpcs:ignore
			}

		};

		//phpcs:ignore
		$set_default_for_some_fields( $_POST );

		foreach ( $meta_names as $meta_name ) {
			//phpcs:ignore
			if ( $_POST[ $meta_name ] ) {
				$save_to_meta( $meta_name );

				// Special case in Jubelio Omnichannel.
				// This meta is just information for address mapping in Jubelio Omnichannel.
				if ( false !== strpos( $meta_name, 'coordinate' ) ) {
					//phpcs:ignore
					$meta_value = $different_address ? sanitize_textarea_field( $_POST[ 'shipping_coordinate' ] ) : sanitize_textarea_field( $_POST['billing_coordinate'] );
					$coordinate = extract_coordinate( $meta_value );
					$latitude   = $coordinate['latitude'];
					$longitude  = $coordinate['longitude'];
					update_post_meta( $order_id, '_latitude_user', $latitude );
					update_post_meta( $order_id, '_longitude_user', $longitude );
				}
			}
		}

	}

	/** Add Phone and Email to Shipping Fields
	 *
	 * @param Array $fields Fields.
	 * @return Array
	 */
	public function phone_email_shipping_checkout_fields( $fields ) {

		$fields['shipping']['shipping_phone'] = array(
			'label'    => __( 'Phone', 'woocommerce' ),
			'required' => true,
			'class'    => array( 'form-row-wide' ),
			'clear'    => true,
			'priority' => 60,
		);

		$fields['shipping']['shipping_email'] = array(
			'label'    => __( 'Email', 'woocommerce' ),
			'required' => true,
			'class'    => array( 'form-row-wide' ),
			'clear'    => true,
			'priority' => 61,
		);

		return $fields;
	}


	/** Jubelio Shipment Footer Script */
	public function jubeship_footer_script() {
		$this->gmap_footer_script();
	}

	/** Chosen Shipping Get Meta
	 *
	 * @param String $shipping_id Shipping ID.
	 * @return Object
	 */
	private function chosen_shipping_get_meta( $shipping_id ) {
		$packages = WC()->shipping->get_packages();
		$rates    = false;
		foreach ( $packages as $key => $package ) {
			foreach ( $package['rates'] as $rate ) {
				if ( $rate->id === $shipping_id ) {
					$rates = $rate;
				}
			}
		}

		return $rates;
	}

	/** Jubelio Shipment Admin Footer Script */
	public function jubeship_admin_footer_script() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( 'product' === $screen_id ) {
			$this->required_weight_and_dimensions();
		}
		$this->gmap_footer_script();
	}

	/** Set Require for weight and dimensions */
	private function required_weight_and_dimensions() {
		?>
	<script>
		jQuery(document).ready(function ($) {

			const parseIntEx = (number) => {
				return isNaN( parseInt( number ) ) ? 0 : parseInt( number );
			}

			const fields = [
				'_weight',
				'product_length',
				'product_width',
				'product_height'
			];

			fields.map( (field) => {
				$('#' + field).prop('required', true);
			});

			$('#publish').on('click', function () {

				const weight = parseIntEx( $('#_weight').val() );
				const length = parseIntEx( $('#product_length').val() );
				const width = parseIntEx( $('#product_width').val() );
				const height = parseIntEx( $('#product_height').val() );

				const dimensions = ( length * width * height );

				if ( ( weight < 1) || ( dimensions < 1 ) ) {
					alert('<?php esc_html_e( 'Weight and Dimensions must be set in the Shipping tab.', 'jubelio-shipment' ); ?>');

					$('.shipping_tab > a').click();  // Click on 'Shipping' tab.
					$('#_weight').focus();  // Focus on Weight field.
					return false;
				}
			});
		});
	</script>
		<?php
	}

	/** Jubelio Shipment Footer Script */
	private function gmap_footer_script() {

		$grabmap_status = grabmap_auth();
		$js_gmaps_url = JUBELIO_SHIPMENT_URL . 'assets/js/jubelio-shipment-grabmaps.js';

		$is_settings_page = function() {
			$page = $_GET['page'] ?? ''; //phpcs:ignore
			$tab  = $_GET['tab'] ?? ''; //phpcs:ignore
			return ( ( 'wc-settings' === $page ) && ( 'shipping' === $tab ) );
		};

		$is_jubelio_dashboard = function() {
			$page = $_GET['page'] ?? ''; //phpcs:ignore
			$on  = $_GET['on'] ?? ''; //phpcs:ignore
			return ( ( 'jubelio-store' === $page ) && ( 'courier_controller' === $on ) );
		};

		$css_on_admin = $is_settings_page() || $is_jubelio_dashboard() ? 'popup_in_admin' : '';

		/** Popup Confiration COD */
		if ( is_checkout() ) { ?>
			<input type="hidden" value="" id="cod_value_fee">
			<div class="popup_codConfirmation" id="codConfirmation">
				<div class="popup_container">
					<div class="popup_content">
						<p> <?php esc_attr_e( 'Are you sure you want to choose COD ?', 'jubelio-shipment' ); ?> </p>
						<button id="cancelCod"> <?php esc_attr_e( 'No', 'jubelio-shipment' ); ?></button>
						<button id="confirmCod"> <?php esc_attr_e( 'Yes', 'jubelio-shipment' ); ?> </button>
					</div>
				</div>
			</div>
		<?php
		}

		if ( ( is_checkout() || is_account_page() || $is_settings_page() || $is_jubelio_dashboard() ) && 'enabled' === $grabmap_status ) :
		?>

		<div class="popup_map <?php echo esc_attr( $css_on_admin ); ?>" id="popup_map">
			<div class="popup_container">
				<div class="popup_content">
					<header class="popup_header">
						<h1>
								<?php esc_attr_e( 'Pinpoint Location', 'jubelio-shipment' ); ?>
						</h1>
						<a href="#" class="popup_button" id="popup_close">
							<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-x"
								viewBox="0 0 16 16">
								<path
									d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z" />
							</svg>
						</a>
					</header>
					<div class="popup_fields">
						<div id="search_address"></div>
						<input id="address_coordinate" name="address_coordinate" type="hidden" />
						<input id="popup_from" name="popup_form" type="hidden">
					</div>

					<iframe id="jubelio-maps" allow="geolocation" src="https://maps-ui.jubelio.com" width="100%" height="350px" style="border:none"></iframe><br/>

					<footer class="popup_footer">
						<button class="button <?php echo $is_settings_page() ? 'button-primary button-large' : ''; ?>" id="popup_save">
								<?php esc_attr_e( 'Save', 'jubelio-shipment' ); ?>
						</button>
					</footer>

				</div>
			</div>
		</div>

		<script src="<?php echo esc_url( $js_gmaps_url ); ?>"></script>
		<?php endif; ?>
		<?php
	}

	/** Add Flagging in Cart
	 *
	 * @param Array $cart Cart.
	 */
	public function jubeship_flag_in_cart( $cart ) {

		$billing_pinpoint_session  = WC()->session->get( 'billing_pinpoint_location', '' );
		$shipping_pinpoint_session = WC()->session->get( 'shipping_pinpoint_location', '' );

		$billing_coordinate_session  = WC()->session->get( 'billing_coordinate', '' );
		$shipping_coordinate_session = WC()->session->get( 'shipping_coordinate', '' );

		$is_different_address = WC()->session->get( 'different_address', false );

		if ( 'true' === $is_different_address ) {
			$pinpoint_location = $shipping_pinpoint_session;
			$coordinate        = $shipping_coordinate_session;
		} else {
			$pinpoint_location = $billing_pinpoint_session;
			$coordinate        = $billing_coordinate_session;
		}

		$cart['data']->jubelio_shipment = array(
			'pinpoint_location'   => $pinpoint_location,
			'coordinate'          => $coordinate,
			'different_address'   => $is_different_address,
			'billing'             => array(
				'pinpoint_location' => $billing_pinpoint_session,
				'coordinate'        => $billing_coordinate_session,
			),
			'shipping'            => array(
				'pinpoint_location' => $shipping_pinpoint_session,
				'coordinate'        => $shipping_coordinate_session,
			),
			'activate_insurance'  => wc_bool_to_string( WC()->session->get( 'activate_insurance', false ) ),
			'active_multi_origin' => wc_bool_to_string( $this->active_multi_origin() ),
			'multi_origin'        => array(
				'postcode'       => WC()->session->get( '_multi_origin_postcode' ),
				'subdistrict_id' => WC()->session->get( '_multi_origin_subdistrict_id' ),
				'coordinate'     => WC()->session->get( '_multi_origin_coordinate' ),
				'location_name'  => WC()->session->get( '_multi_origin_location_name' ),
				'location_id'    => WC()->session->get( '_multi_origin_location_id' ),
			),
		);

		return $cart;
	}


	/** Ajax for Set Coordinate */
	public function ajax_set_coordinate() {
		if (
		! isset( $_POST['jubelio_shipment_nonce'] )
		&& ! wp_verify_nonce( sanitize_key( $_POST['jubelio_shipment_nonce'] ), 'jubelio_shipment_nonce' )
		) {
			wp_die( 'Nonce value cannot be verified.' );
		}

		$identifier = sanitize_textarea_field( $_POST['identifier'] );

		$fields = array( 'coordinate', 'pinpoint_location' );
		foreach ( $fields as $field ) {
			$value = sanitize_textarea_field( $_POST[ $field ] );
			$this->update_usermeta_and_session( $identifier . '_' . $field, $value );
		}

		$this->force_update_package();

		wp_send_json_success();
	}

	/** Return shipping method */
	public function prevent_shipping_method_selection_when_single_method($chosen_method) {
    // Check if there is only one available shipping method
    $shipping_methods 	= WC()->shipping->get_shipping_methods();
	$selected_couriers 	= get_option( 'woocommerce_jubelioshipment_13_settings');

	if( !empty( $selected_couriers ) ) {
		 if (count( $selected_couriers['selected_couriers'] ) !== 1) {
			// Return false to prevent the chosen shipping method from being set
			return false;
		}
	}

    // If there are multiple shipping methods, return the chosen method as normal
    return $chosen_method;
	}

	/** Ajax for Update Session and Meta */
	public function ajax_update_session_and_meta() {
		if (
		! isset( $_POST['jubelio_shipment_nonce'] )
		&& ! wp_verify_nonce( sanitize_key( $_POST['jubelio_shipment_nonce'] ), 'jubelio_shipment_nonce' )
		) {
			wp_die( 'Nonce value cannot be verified.' );
		}

		$value = sanitize_textarea_field( $_POST['value'] );
		$from  = sanitize_text_field( $_POST['from'] );

		$different_address = sanitize_text_field( $_POST['different_address'] );
		$this->update_usermeta_and_session( 'different_address', $different_address );

		$this->update_usermeta_and_session( $from, $value );

		$this->force_update_package();

		wp_send_json_success( array( 'success' => $different_address ) );
	}

	/** Jubelis Shipment Add Extra Info in Shipping */
	public function jubeship_extra_info_shipping() {

		$webstore_id = $this->get_option( 'webstore_id', '' );

		$show_shipping_insurance = $this->get_option( 'shipping_insurance', 'no' );
		$show_payment_voucher    = $this->get_option( 'payment_voucher', 'no' );

		$insurance          = WC()->session->get( 'activate_insurance' );
		$activate_insurance = ( 'true' === $insurance );

		$js_voucher_url = JUBELIO_SHIPMENT_URL . 'assets/js/jubelio-shipment-voucher.js';
		?>
		<?php if ( 'yes' === $show_shipping_insurance ) : ?>
		<tr class="text-shipping-insurance">
			<td colspan="2" style="border-bottom:0;text-align:left">
				<h3><?php esc_html_e( 'Information', 'jubelio-shipment' ); ?></h3>
				<span>
					<?php
						esc_html_e(
							'This shipment already supports Shipping Insurance. Your order can be protected from insurance for a transaction value of up to 100,000,000, with an easy claim process, through shipping in collaboration with Jubelio Shipment.',
							'jubelio-shipment'
						);
					?>
				</span>
			</td>
		<tr>
		<tr class="shipping_insurance">
			<td colspan="2" style="text-align: left;">
				<input type="hidden" id="insurance_value" name="insurance_value_status">
				<input type="hidden" id="subtotal_hidden" name="subtotal_hidden"
					value="<?php echo esc_html( WC()->cart->get_subtotal() ); ?>">
				<input type="hidden" id="insurance_value_price" name="insurance_value_price">
				<label>
					<input type="checkbox" id="activate_insurance" name="activated_insurance" <?php if ( isset( $insurance ) ) {  checked( $activate_insurance, true ); } else { checked( true, true ); } ?>>
					<?php esc_html_e( 'Activate shipping insurance', 'jubelio-shipment' ); ?>
				</label>
				<span class="insurance_value_price_span"></span><br>
			</td>
		</tr>
		<?php endif; ?>
		<?php if ( 'yes' === $show_payment_voucher && ! empty( $webstore_id ) ) : ?>
		<tr class="shipping_voucher_title">
			<td style="border: 0; text-align: left; margin: 0; padding-bottom: 0" colspan="2">
				<h3>
					<?php esc_html_e( 'Payment Voucher', 'jubelio-shipment' ); ?>
				</h3>
			</td>
		</tr>
		<tr class="shipping_voucher">
			<td colspan="2" style="border: 0; padding: 0; text-align: left" id="shipping_voucher_field">
				<input type="text" placeholder="<?php esc_html_e( 'Voucher Code', 'jubelio-shipment' ); ?>" name="shipping_voucher"
					class="shipping_voucher" id="shipping_voucher" />
			</td>
		</tr>
		<tr class="notification-voucher-wrapper">
			<td colspan="2" style="border: 0; padding: 0">
				<p class="notification-voucher"></p>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<span disabled="disabled" id="shipping_voucher_button"
					class="shipping-voucher-button checkout-button button alt expand wc-forward">
					<?php esc_html_e( 'Apply Voucher', 'jubelio-shipment' ); ?>
				</span>
			</td>
		</tr>
		<?php endif; ?>
		<?php if ( ( $show_payment_voucher ) && ( $show_shipping_insurance ) ) : ?>
		<?php //phpcs:ignore ?>
		<script src="<?php echo esc_attr( $js_voucher_url ); ?>"></script>
		<?php endif; ?>
		<?php
	}

	/** Jubelio Shipment Multi Origin */
	public function jubeship_multiorigin() {
		$active_multi_origin = $this->active_multi_origin();

		$locations_multi_origin = false;
		$location_id_selected   = null;
		if ( $active_multi_origin ) {
			$locations_multi_origin = $this->retrive_data_multi_origin_from_jubelio();
			$location_id_selected   = WC()->session->get( '_multi_origin_location_id' );
		}

		?>
		<?php if ( $active_multi_origin && ( false !== $locations_multi_origin ) ) : ?>
		<tr class="multi_origin">
			<td colspan="2" style="border-bottom:0;text-align:left">
				<h3 style="margin: 0 0 1px 0;"><?php esc_html_e( 'Select Origin Shipping Address', 'jubelio-shipment' ); ?></h3>
			</td>
		</tr>
		<tr>
			<td colspan="2" style="padding: 0 0 15px 0; border: 0; text-align: left" id="multi_origin_field">
				<select style="margin: 1px 0 10px 0;" id="multi_origin" class="multi_origin update_meta_on_change">
					<option value="base_origin">
						<?php esc_html_e( 'Base Origin', 'jubelio-shipment' ); ?>
					</option>
				</select>
			</td>
		</tr>
		<script>
		data = [
			<?php foreach ( $locations_multi_origin as $location ) : ?>
			{ id:<?php echo esc_html( $location['location_id'] ); ?>,
				text:"<?php echo esc_html( $location['location_name'] ); ?>",
				subdistrict_id:parseInt(<?php echo esc_html( $location['subdistrict_id'] ); ?>),
				postcode:parseInt(<?php echo esc_html( $location['post_code'] ); ?>),
				coordinate:"<?php echo esc_html( $location['coordinate'] ); ?>",
				selected: <?php /** phpcs:ignore **/ echo ( $location_id_selected == $location['location_id'] ) ? 'true' : 'false'; ?>
			},
		<?php endforeach; ?>
		];

			if (typeof jQuery().selectWoo === 'function' ) {
				jQuery('#multi_origin').selectWoo({
					width: '100%',
					data: data
				});

				jQuery( '#multi_origin' ).on(
					'select2:select',
					function ( e ) {
						const data = e.params.data;

						const request = jQuery.ajax({
							url: jubelio_shipment.ajaxurl,
							type: "POST",
							dataType: "json",
							data: {
								action: "jubelio_shipment_change_shipping_origin",
								jubelio_shipment_nonce: jubelio_shipment.nonce,
								postcode: data.postcode,
								subdistrict_id: data.subdistrict_id,
								coordinate: data.coordinate,
								location_name: data.text,
								location_id: data.id
							},
						});

						request.done( function () {
							jQuery('body').trigger('update_checkout');
						});
					}
				);
			}
		</script>
		<?php endif; ?>
		<?php
	}

	/** Ajax for Remove Voucher Code */
	public function ajax_remove_voucher_code() {
		if (
			! isset( $_POST['jubelio_shipment_nonce'] )
			&& ! wp_verify_nonce( sanitize_key( $_POST['jubelio_shipment_nonce'] ), 'jubelio_shipment_nonce' )
			) {
				wp_die( 'Nonce value cannot be verified.' );
		}

		$voucher_code              = sanitize_textarea_field( $_POST['voucher_code'] );
		$jubelio_shipment_vouchers = $this->get_session_jubelio_shipment_vouchers();

		if ( empty( $jubelio_shipment_vouchers ) ) {
			wp_send_json_success();
			return;
		}

		//phpcs:ignore
		$position = array_search( $voucher_code, array_column( $jubelio_shipment_vouchers, 'voucher_code' ) );
		unset( $jubelio_shipment_vouchers[ $position ] );
		$jubelio_shipment_vouchers = array_values( $jubelio_shipment_vouchers );
		$this->set_session_jubelio_shipment_vouchers( $jubelio_shipment_vouchers );
		wp_send_json_success( $jubelio_shipment_vouchers );
	}

	/** Ajax for Set Voucher */
	public function ajax_set_voucher() {
		if (
		! isset( $_POST['jubelio_shipment_nonce'] )
		&& ! wp_verify_nonce( sanitize_key( $_POST['jubelio_shipment_nonce'] ), 'jubelio_shipment_nonce' )
		) {
			wp_die( 'Nonce value cannot be verified.' );
		}

		$webstore_id = $this->get_option( 'webstore_id' );
		if ( null === $webstore_id ) {
			$message = __( 'Voucher cannot be used. You have not set your Webstore ID yet. Please set it first.', 'jubelio-shipment' );
			wp_send_json_error(
				array(
					'code'    => 422,
					'message' => $message,
					'errors'  => $message,
				),
				422
			);
		}

		$voucher_code              = sanitize_textarea_field( $_POST['voucher_code'] );
		$jubelio_shipment_vouchers = $this->get_session_jubelio_shipment_vouchers();

		$valid_voucher = $this->api->validate_voucher( $voucher_code );

		if ( is_wp_error( $valid_voucher ) ) {
			$error_code    = $valid_voucher->get_error_code();
			$error_message = $valid_voucher->get_error_message();
			$error_data    = $valid_voucher->get_error_data();

			wp_send_json_error(
				array(
					'code'    => $error_code,
					'message' => $error_message,
					'errors'  => $error_data['errors'][0],
				),
				$error_data['http_response']
			);
		}

		$sanitize_voucher_code       = strtoupper( sanitize_title( $voucher_code ) );
		$jubeship_flagging           = '[Jubelio Shipment]';
		$jubelio_shipment_vouchers[] = array(
			'voucher_code' => sanitize_textarea_field( $voucher_code ),
			'amount'       => (int) $valid_voucher[0]['amount'],
			'flagging'     => $jubeship_flagging,
			'title'        => $jubeship_flagging . ' ' . $sanitize_voucher_code,
		);

		$this->set_session_jubelio_shipment_vouchers( $jubelio_shipment_vouchers );
		wp_send_json_success();
	}

	/** Jubelio Shipment Ajax Set Shipping Insurance */
	public function ajax_set_shipping_insurance() {
		if (
			! isset( $_POST['jubelio_shipment_nonce'] )
			&& ! wp_verify_nonce( sanitize_key( $_POST['jubelio_shipment_nonce'] ), 'jubelio_shipment_nonce' )
			) {
				wp_die( 'Nonce value cannot be verified.' );
		}

		$from = $_POST['from'];
		wp_send_json_success( array( 'data' => $from ) );
	}

	/** Jubelio Shipment Ajax Change Shipping Origin */
	public function ajax_change_shipping_origin() {
		if (
			! isset( $_POST['jubelio_shipment_nonce'] )
			&& ! wp_verify_nonce( sanitize_key( $_POST['jubelio_shipment_nonce'] ), 'jubelio_shipment_nonce' )
			) {
				wp_die( 'Nonce value cannot be verified.' );
		}

		$postcode       = sanitize_text_field( $_POST['postcode'] );
		$subdistrict_id = sanitize_text_field( $_POST['subdistrict_id'] );
		$coordinate     = sanitize_textarea_field( $_POST['coordinate'] );
		$location_name  = sanitize_textarea_field( $_POST['location_name'] );
		$location_id    = sanitize_textarea_field( $_POST['location_id'] );

		$this->update_usermeta_and_session( '_multi_origin_postcode', $postcode );
		$this->update_usermeta_and_session( '_multi_origin_subdistrict_id', $subdistrict_id );
		$this->update_usermeta_and_session( '_multi_origin_coordinate', $coordinate );
		$this->update_usermeta_and_session( '_multi_origin_location_name', $location_name );
		$this->update_usermeta_and_session( '_multi_origin_location_id', $location_id );

		$this->force_update_package();
		wp_send_json_success();
	}

	/** Jubelio Shipment Ajax Clear Chosen shipping */
	public function jubelio_shipment_clear_selected_courier() {
		if (
			! isset( $_POST['jubelio_shipment_nonce'] )
			&& ! wp_verify_nonce( sanitize_key( $_POST['jubelio_shipment_nonce'] ), 'jubelio_shipment_nonce' )
			) {
				wp_die( 'Nonce value cannot be verified.' );
		}

		$cart                         = new WC_Cart();
		$clear_chosen_shipping_method = WC()->session->__unset( 'chosen_shipping_methods' );
		$recalculate_total            = $cart->calculate_totals();

		$data = array(
			'chosen_shipping_method' => $clear_chosen_shipping_method,
			'recalculate_total'      => $recalculate_total,
		);

		wp_send_json( $data );

		wp_die();
	}

	/** Add Feee
	 *
	 * @param Object $cart Cart From Woo.
	 */
	public function add_fee_to_cart( $cart ) {
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

		// Only Jubelio Shipment.
		if ( strpos( $chosen_methods[0], 'jubelio_shipment' ) === false ) {
			return;
		}

		$jubelio_shipment_vouchers = $this->get_session_jubelio_shipment_vouchers();

		// Shipping Insurance first.
		$activate_insurance = WC()->session->get( 'activate_insurance' );

		$packages          = WC()->shipping->get_packages();
		$shipping_selected = array();

		foreach ( $packages as $key => $package ) {
			foreach ( $package['rates'] as $rate ) {
				//phpcs:ignore
				if ( in_array( $rate->id, $chosen_methods ) ) {
					$shipping_selected = $rate;
				}
			}
		}

		if ( ! empty( $shipping_selected ) ) {

			$meta    = $shipping_selected->get_meta_data();
			if ( 'true' === $activate_insurance ) {
				$amount  = (int) $meta['_jubelio_shipment_insurance'];

				if ( $amount > 0 ) {
					$cart->add_fee( __( 'Shipping Insurance', 'jubelio-shipment' ), $amount );
				}
			}

			$cod_fee = (int) $meta['_jubelio_shipment_cod'];

			if ( !empty( $cod_fee ) ) {
				if (WC()->session->get('chosen_payment_method') == 'cod') {
					$cart->add_fee( __( 'COD Fee', 'jubelio-shipment' ), $cod_fee );
				}
				$this->activated_cod( 'yes');
			}
		}

		foreach ( $jubelio_shipment_vouchers as $voucher ) {
			$cart->add_fee( $voucher['title'], -$voucher['amount'] );
		}

	}

	/** Rename COD Payment Gateway */
	public function jubeship_gateway_title( $title, $payment_id ) {
		if ('cod' === $payment_id) {
			$title = __('COD', 'woocommerce');
		}
		return $title;
	}

	/** Activated COD Payment Gateway */
	private function activated_cod( $active = 'no' ) {
		if( 'no' === $active ) {
			return;
		}

		// Get the WooCommerce payment gateways
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		// Set the ID of the COD payment gateway
		$cod_gateway_id = 'cod';

		// Activate the COD payment method
		if (isset($payment_gateways[$cod_gateway_id])) {
			 $payment_gateways[$cod_gateway_id]->enabled = 'yes';
		 }
	}

	/** Jubelio Shipment Display Fee HTML
	 *
	 * @param String $fee_html Fee as HTML.
	 * @param Object $fee      Object of Fee.
	 */
	public function jubeship_fee_html( $fee_html, $fee ) {
		$jubelio_shipment_vouchers = $this->get_session_jubelio_shipment_vouchers();

		$key_position = array_search( $fee->name, array_column( $jubelio_shipment_vouchers, 'title' ), true );
		echo $fee_html; //phpcs:ignore
		if ( false !== $key_position ) {
			echo '<span class="alert-color remove_voucher_code" onclick="remove_voucher_code(this)" data-text="' . esc_html( $fee->name ) . '">x</span>';
		}
	}

	/** Jubelio Shipment Create Order Shipping Item
	 *
	 * @param Object $item Item from Cart.
	 */
	public function jubeship_create_order_shipping_item( $item ) {

		$method_ids_allowed = array(
			'jubelioshipment',
			'flat_rate',
			'free_shipping',
			'local_pickup',
		);

		if ( ! in_array( $item->get_method_id(), $method_ids_allowed, true ) ) {
			return;
		}

		if ( 'jubelioshipment' === $item->get_method_id() ) {

			$metas = $item->get_meta_data();

			$key_position = array_search( '_jubelio_shipment_data', array_column( $metas, 'key' ), true );
			if ( false === $key_position ) {
				return;
			}
			$meta = $metas[ $key_position ];

			$jubeship_flagging = ' ( Jubelio Shipment )';
			$item->set_method_title( $meta->value['courier_service_name'] . $jubeship_flagging );
		} else {

			// Special case for Jubelio Omni Channel.
			$item->add_meta_data( '_is_jubelio_shipment_exists', 'yes' );

		}

			// Promotion Validate.
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' )[0] ?? '';

		if ( ! empty( $chosen_shipping_methods ) && str_contains( $chosen_shipping_methods, 'jubelio_shipment' ) ) {

			$chosen_rate = get_shipping_choosen();

			if ( null !== $chosen_rate ) {
				$get_meta_data = $chosen_rate->get_meta_data();
				$jubeship_meta = $get_meta_data['_jubelio_shipment_data'] ?? null;

				if ( null !== $jubeship_meta ) {

					$is_promotion = ( null !== ( $jubeship_meta['promotion'] ?? null ) );

					if ( $is_promotion ) {
						$promotion_id    = (int) $jubeship_meta['promotion']['promotion_id'] ?? 0;
						$check_promotion = $this->api->check_promotion_usage( $promotion_id );

						if ( is_wp_error( $check_promotion ) ) {

							$invalid_promotions = get_transient( '_jubelio_shipment_invalid_promotions', array() );
							if ( ! is_array( $invalid_promotions ) ) {
								$invalid_promotions = array();
							}
							array_push( $invalid_promotions, $chosen_rate );
							set_transient( '_jubelio_shipment_invalid_promotions', $invalid_promotions );

							throw new \Exception( __( 'Sorry, the promotion is no longer available. Please refresh your cart to get the shipping price.', 'jubelio-shipment' ) );
						} else {
							$data = array(
								'shipping_chosen'    => $chosen_shipping_methods,
								'promotion_usage_id' => $check_promotion['promotion_usage_id'],
							);

							set_transient( 'jubelio_shipment_promotion_usage', $data );
						}
					}
				}
			}
		}

		$promotion_usage = get_transient( 'jubelio_shipment_promotion_usage' );

		if ( is_array( $promotion_usage ) ) {

			$promotion_usage_id = $promotion_usage['promotion_usage_id'] ?? false;

			if ( false !== $promotion_usage_id ) {
				$item->add_meta_data( '_promotion_usage_id', $promotion_usage_id );
			}
		}

	}

	/** Jubelio Shipment Order Proccessed
	 *
	 * @param Integer $order_id Order Id.
	 * @param Object  $posted_data Posted Data.
	 * @param Object  $order Order.
	 */
	//phpcs:ignore
	public function jubeship_order_processed( $order_id, $posted_data, $order ) {

		wc_clear_notices(); // Clear all notification.
		$vouchers = $this->get_session_jubelio_shipment_vouchers();

		if ( ! empty( $vouchers ) ) {

			$voucher_codes = array();
			foreach ( $vouchers as $voucher ) {
				$voucher_codes[] = $voucher['voucher_code'];
			}
			$webstore_id = $this->get_option( 'webstore_id', '' );

			if ( ! empty( $webstore_id ) ) {
				$this->api->push_to_jubelio_omni( $webstore_id, $order_id, $order );
				$ref_no = 'WS-' . $order_id . '-' . $webstore_id;
				$this->api->redeem_voucher( $ref_no, $voucher_codes );
			}
			$this->purge_all_sessions();

		}
	}

	/** Jubelio Shipment Revalidate After Checkout
	 *
	 * @param Integer $order_id Order Id.
	 */
	//phpcs:ignore
	public function jubeship_after_checkout_form( $order_id ) {
		$order          = wc_get_order( $order_id );
		$shiping_method = (array) $order->get_items( 'shipping' );

		foreach ( $shiping_method  as $item_id => $shipping_data ) {

			// Retrieve the customer shipping zone.
			$shipping_zone = WC_Shipping_Zones::get_zone_by( 'instance_id', $shipping_data->get_instance_id() );

			// Get an array of available shipping methods for the current shipping zone.
			$shipping_methods = $shipping_zone->get_shipping_methods();

			// Loop through available shipping methods.
			foreach ( $shipping_methods as $instance_id => $shipping ) {

				$jubeship_flagging = ' ( Jubelio Shipment )';

				// Targeting specific shipping method.
				if ( $shipping->is_enabled() && 'jubelioshipment' === $shipping->id ) {
					if ( str_contains( $shipping_data->get_method_title(), $jubeship_flagging ) ) {
						return;
					} else {
						// Targeting courier name from shipping meta.
						$get_meta_title = $shipping_data->get_meta( '_jubelio_shipment_data' ) ?? array();

						if ( empty( $get_meta_title ) ) {
								return;
						}

						$shipping_data->set_method_title( $get_meta_title['courier_service_name'] . $jubeship_flagging );
						$shipping_data->save();

						break; // stop the loop.
					}
				}
			}
		}
	}

	/** Jubelio Shipment Checkout Prossess for Validate Voucher
	 *
	 * @throws Exception $revalide_vouchers Revalidate Voucher.
	 */
	public function jubeship_checkout_process() {

		// Voucher Validate.
		$vouchers = $this->get_session_jubelio_shipment_vouchers();
		if ( ! empty( $vouchers ) ) {

			$voucher_codes = array();
			foreach ( $vouchers as $voucher ) {
				$voucher_codes[] = $voucher['voucher_code'];
			}

			$revalidate_vouchers = $this->api->validate_voucher( $voucher_codes );
			if ( is_wp_error( $revalidate_vouchers ) ) {

				$error_data    = $revalidate_vouchers->get_error_data();
				$http_response = (int) $error_data['http_response'];

				$messages = 'Voucher status: ' . $revalidate_vouchers->get_error_message();
				if ( 500 !== $http_response ) {
					$errors         = $error_data['errors'];
					$error_messages = array();
					foreach ( $errors as $error ) {
						$error_messages[] = $error['voucher_code'] . ' ' . $error['message'];
					}
					$messages = implode( "\r\n", $error_messages );
				}

				throw new Exception( $messages );
			}
		}

	}

	/** Jubelio Shipment Modify My Account Address
	 *
	 * @param Array $args Argument of Address.
	 * @return Array
	 */
	public function jubeship_my_account_address_form( $args ) {
		unset( $args['address_2'] );
		unset( $args['state'] );
		unset( $args['city'] );
		unset( $args['country'] );
		unset( $args['postcode'] );
		return $args;
	}

	/** Jubelio Shipment Modify in Thankyou Page
	 *
	 * @param String $args Raw Address.
	 * @return Array
	 */
	private function jubeship_thankyou_page_address_form( $args ) {
		unset( $args['address_2'] );
		unset( $args['state'] );
		unset( $args['city'] );
		unset( $args['country'] );
		unset( $args['postcode'] );
		return $args;
	}

	/** Jubelio Shipment Modify in Thankyou Page (Billing)
	 *
	 * @param String $address Address.
	 * @param String $args Raw Address.
	 * @return String
	 */
	public function jubeship_thankyou_page_billing_address_form( $address, $args ) {
		$args = $this->jubeship_thankyou_page_address_form( $args );
		if ( is_checkout() && ! empty( is_wc_endpoint_url( 'order-received' ) ) ) {
			$address_1         = $args['address_1'];
			$args['address_1'] = $address_1 . "\r\n\r\n" . WC()->customer->get_meta( 'billing_full_address' ) . "\r\n";
		}
		$address = WC()->countries->get_formatted_address( $args );
		return $address;
	}

	/** Jubelio Shipment Modify in Thankyou Page (Shipping)
	 *
	 * @param String   $address Address.
	 * @param String   $args Raw Address.
	 * @param WC_Order $order Order.
	 * @return String
	 */
	public function jubeship_thankyou_page_shipping_address_form( $address, $args, $order ) {
		$args  = $this->jubeship_thankyou_page_address_form( $args );
		$email = '<br /><br />' . $order->get_meta( 'shipping_email' );
		$phone = '';
		if ( is_checkout() && ! empty( is_wc_endpoint_url( 'order-received' ) ) ) {
			$address_1         = $args['address_1'];
			$args['address_1'] = $address_1 . "\r\n\r\n" . WC()->customer->get_meta( 'shipping_full_address' ) . "\r\n";

			$phone = '<p class="woocommerce-customer-details--phone">' . WC()->customer->get_shipping_phone() . '</p>';
			$email = '<p class="woocommerce-customer-details--email">' . WC()->customer->get_meta( 'shipping_email' ) . '</p>';
		}

		$address = WC()->countries->get_formatted_address( $args );
		return $address . $phone . $email;
	}


	/** Update User Meta and Session
	 *
	 * @param String $from From.
	 * @param String $value Value.
	 */
	private function update_usermeta_and_session( $from, $value ) {
		WC()->customer->update_meta_data( $from, $value );
		WC()->session->set( $from, $value );
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), $from, $value );
		}
	}

	/** Force update package on_change. */
	private function force_update_package() {

		$packages = WC()->cart->get_shipping_packages();
		foreach ( $packages as $package_key => $package ) {
			$session_key = 'shipping_for_package_' . $package_key;
				WC()->session->__unset( $session_key );
		}

		set_transient( 'jubelio_shipment_promotion_usage', '' );
	}

	/** Get Session Jubelio Shipment for Vouchers */
	private function get_session_jubelio_shipment_vouchers() {
		return WC()->session->get( '_jubelio_shipment_vouchers', array() );
	}

	/** Set Session Jubelio Shipment for Vouchers
	 *
	 * @param Array $vouchers Vouchers.
	 */
	private function set_session_jubelio_shipment_vouchers( $vouchers ) {
		if ( ! is_array( $vouchers ) ) {
			return;
		}
		return WC()->session->set( '_jubelio_shipment_vouchers', $vouchers );
	}

	/** Purge All Jubelio Shipment Sessions */
	private function purge_all_sessions() {
		WC()->session->__unset( '_jubelio_shipment_vouchers' );
		delete_transient( 'jubelio_shipment_promotion_usage' );
		delete_transient( '_jubelio_shipment_invalid_promotions' );
	}

	/** Retrieve data all locations that support multi origin from Jubelio */
	private function retrive_data_multi_origin_from_jubelio() {
		$response = $this->api->get_all_locations_from_jubelio();
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$cached = get_transient( 'jubelio_shipment_multi_origin' );
		if ( ! empty( $cached ) ) {
			return $cached;
		}

		$locations              = $response['data'];
		$locations_multi_origin = array();

		foreach ( $locations as $location ) {
			if ( true === $location['is_multi_origin'] ) {
				$locations_multi_origin[] = $location;
			}
		}

		set_transient( 'jubelio_shipment_multi_origin', $locations_multi_origin, MINUTE_IN_SECONDS * 30 );
		return $locations_multi_origin;
	}

	/** Is Multi Origin Active? */
	private function active_multi_origin() {
		return ( 'yes' === $this->get_option( 'multi_origin' ) && ( false !== get_option( 'jubelio_auth' ) ) );
	}

	/** Override Label when Promotion ID is set
	 *
	 * @param String $label Label.
	 * @param Object $shipping_method Shipping Method.
	 */
	public function override_shipping_label_for_promotion( $label, $shipping_method ) {

		// don't override when using shipping discount plugin (https://wordpress.org/plugins/shipping-discount/).
		$coupons         = WC()->cart->get_applied_coupons();
		$shipping_coupon = false;

		foreach ( $coupons as $coupon ) {
			$coupon = new WC_Coupon( $coupon );

			if ( 'shipping_discount' === $coupon->get_discount_type() ) {
				$shipping_coupon = true;
				break;
			}
		}

		if ( true === $shipping_coupon ) {
			return $label;
		}

		$method_id = $shipping_method->get_method_id();

		// skip for another shipping method.
		if ( JUBELIO_SHIPMENT_METHOD_ID !== $method_id ) {
			return $label;
		}

		$meta = $shipping_method->get_meta_data();

		$jubeship_meta_data = $meta['_jubelio_shipment_data'] ?? null;
		if ( null === $jubeship_meta_data ) {
			return $label;
		}

		$shipping_before_discount = (int) $jubeship_meta_data['rates'] ?? 0;
		$shipping_after_discount  = (int) $jubeship_meta_data['final_rates'] ?? 0;
		$shipping_discount_rate   = (int) $jubeship_meta_data['discount_rates'] ?? 0;

		// skip if discount rate is zero.
		if ( 0 === $shipping_discount_rate ) {
			return $label;
		}

		$label_array = explode( ':', $label );

		if ( count( $label_array ) > 1 ) {
			array_pop( $label_array );
		}

		$label_str = implode( ':', $label_array );

		if ( ':' !== substr( $label_str, -1 ) ) {
			$label_str .= ': ';
		}

		$label = $label_str . '<del>' . wc_price( $shipping_before_discount ) . '</del> <ins>' . wc_price( $shipping_after_discount ) . '</ins>';

		return $label;

	}

}
