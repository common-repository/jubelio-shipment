<?php
/**
 * Jubelio Shipment - Shipping Method Class.
 *
 * @author     Ali
 * @link       https://www.situsali.com
 * @since      1.0.0
 *
 * @package    Jubelio
 * @subpackage jubelio-shipment/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Jubelio Shipment Shipping Method Class.
 *
 * @since      1.0.0
 * @package    Jubelio
 * @subpackage jubelio-shipment/includes
 * @author     Ali <ali@jubelio.com>
 */
class JubelioShipment_Shipping_Method extends WC_Shipping_Method {

	/**
	 * Jubelio Shipment API Class Object
	 *
	 * @since 1.0.0
	 * @var Jubelio Shipment
	 */
	private $api;

	/**
	 * Supported features.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	public $supports = array(
		'shipping-zones',
		'instance-settings',
		'settings',
		'instance-settings-modal',
	);

	/**
	 * Constructor for your shipping class
	 *
	 * @since 1.0.0
	 * @param int $instance_id ID of settings instance.
	 * @return void
	 */
	public function __construct( $instance_id = 0 ) {
		$this->api                = new JubelioShipment_Api();
		$this->instance_id        = absint( $instance_id );
		$this->id                 = JUBELIO_SHIPMENT_METHOD_ID;
		$this->method_title       = jubelioshipment_get_plugin_data( 'Name' );
		$this->title              = jubelioshipment_get_plugin_data( 'Name' );
		$this->method_description = jubelioshipment_get_plugin_data( 'Description' );

		$this->init();
	}

	/**
	 * Init
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.
		$this->init_form_fields();
		$this->register_hooks();
	}
	/**
	 * Init form fields.
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$settings = array(
			'origin_location_search'  => array(
				'title'       => __( 'Province, City, District and Zipcode', 'jubelio-shipment' ),
				'type'        => 'select',
				'placeholder' => __( 'Please enter 3 or more characters', 'jubelio-shipment' ),
			),
			'origin_location_address'     => array(
				'title' => __( 'Shipping Origin Address', 'jubelio-shipment' ),
				'type'  => 'text',
			),
			'origin_location_subdistrict' => array(
				'title' => __( 'Shipping Origin Subdistrict', 'jubelio-shipment' ),
				'type'  => 'text',
			),
			'origin_location_zipcode'     => array(
				'title' => __( 'Shipping Origin Zipcode', 'jubelio-shipment' ),
				'type'  => 'text',
			),
			'origin_coordinate'           => array(
				'title' => __( 'Shipping Origin Coordinate', 'jubelio-shipment' ),
				'type'  => 'text',
				'css'   => 'width: 100%',
			),
			'base_weight'                 => array(
				'title'             => __( 'Base Cart Contents Weight (gram)', 'jubelio-shipment' ),
				'type'              => 'number',
				'description'       => __( 'The base cart contents weight will be calculated. If the value is blank or zero, the couriers list will not displayed when the actual cart contents weight is empty.', 'jubelio-shipment' ),
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '100',
				),
				'desc_tip'          => true,
				'css'               => 'width: 100%',
			),
			'selected_couriers'           => array(
				'title' => 'Selected Couriers',
				'type'  => 'selected_couriers',
			),

		);

		// Use this if map using Grabmap
		$grabmap_status = grabmap_auth();

		if ( 'enabled' !== $grabmap_status ) {
			unset( $settings['origin_coordinate'] );
		}

		$webstore_id = (int) $this->get_option( 'webstore_id', 0 );

		$admin_settings = array(
			'client_id'          => array(
				'title'             => __( 'Client Id', 'jubelio-shipment' ),
				'type'              => 'text',
				'custom_attributes' => array(
					'oninput' => 'hideSomeFieldWebstore(this)',
					'min'     => '1',
				),
			),
			'client_secret'      => array(
				'title' => __( 'Client Secret', 'jubelio-shipment' ),
				'type'  => 'password',
			),
			'webstore_id'        => array(
				'title'             => __( 'Webstore ID', 'jubelio-shipment' ),
				'type'              => 'number',
				'description'       => __( 'Requires Webstore ID for shipping insurance and multiorigin. You can get this ID if you subscribe to Jubelio Omnichannel', 'jubelio-shipment' ),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'oninput' => 'hideSomeFieldWebstore(this)',
					'min'     => '1',
				),
			),
			'show_eta'           => array(
				'title'       => __( 'Show ETA', 'jubelio-shipment' ),
				'type'        => 'checkbox',
				'description' => __( 'Show Estimate Time', 'jubelio-shipment' ),
				'desc_tip'    => true,
				'default'     => 'yes',
			),
			'shipping_insurance' => array(
				'title'             => __( 'Shipping Insurance', 'jubelio-shipment' ),
				'type'              => 'checkbox',
				'default'           => 'no',
				'description'       => __( 'Shipping Insurance', 'jubelio-shipment' ),
				'desc_tip'          => true,
				'custom_attributes' => array( 'disabled' => ( $webstore_id < 1 ? 'disabled' : '' ) ),
			),
		);

		$this->instance_form_fields = $settings;
		$this->form_fields          = $admin_settings;
	}

/**
 * Generate selected couriers list table.
 *
 * @since  1.0.0
 * @param  mixed $key Field key.
 * @param  mixed $data Field data.
 * @return string
 */
	public function generate_selected_couriers_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$data      = wp_parse_args(
			$data,
			array(
				'title'             => '',
				'class'             => '',
				'disabled'          => false,
				'class'             => '',
				'css'               => '',
				'placeholder'       => '',
				'type'              => 'text',
				'desc_tip'          => false,
				'description'       => '',
				'custom_attributes' => array(),
			)
		);

		$selected_couriers = $this->get_option( $key );
		$couriers          = $this->api->get_all_couriers();
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc" style="vertical-align: baseline;">
				<label for="<?php echo esc_attr( $field_key ); ?>">
					<?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</label>
			</th>
			<td class="forminp">
				<ul class="admin_list_courier">
						<li>
								<label>
											<input type="checkbox" id="check_all">
											<span><?php esc_attr_e( 'Check All', 'jubelio-shipment' ); ?></span>
								</label>
						</li>
					<?php foreach ( $couriers as $courier ) : ?>
						<?php $selected = in_array( (string) $courier['courier_service_id'], $selected_couriers, true ); ?>
						<li>
								<label>
											<input type="checkbox"
												name="<?php echo esc_attr( $field_key ); ?>[]"
												value="<?php echo esc_attr( $courier['courier_service_id'] ); ?>"
												<?php checked( $selected, true ); ?>
												>
											<span><?php echo wp_kses_post( $courier['name'] ); ?><strong> (<?php echo esc_attr( $courier['service_category_name'] ); ?>)</strong></span>
								</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

		/**
		 * Get instance for field data.
		 *
		 * @since 1.3
		 *
		 * @param string      $key Field key.
		 * @param string|null $property Selected field data property.
		 * @param string|null $default_value Default value.
		 *
		 * @return mixed
		 */
	protected function get_instance_form_field_data( $key, $property = null, $default_value = null ) {
		static $form_fields = null;

		if ( is_null( $form_fields ) ) {
			$form_fields = $this->get_instance_form_fields();
		}

		if ( isset( $form_fields[ $key ] ) ) {
			if ( $property ) {
				return isset( $form_fields[ $key ][ $property ] ) ? $form_fields[ $key ][ $property ] : $default_value;
			}

			return $form_fields[ $key ];
		}

		return null;
	}

		/**
		 * Validate settings field type selected couriers.
		 *
		 * @since 1.0.0
		 * @param  string $key Settings field key.
		 * @param  string $value Posted field value.
		 * @throws Exception If the field value is invalid.
		 * @return array
		 */
	public function validate_selected_couriers_field( $key, $value ) {
		if ( is_string( $value ) ) {
			$value = array_map( 'trim', explode( ',', $value ) );
		}

		$field_label = $this->get_instance_form_field_data( $key, 'title', $key );

		if ( ! $value ) {
			// Translators: %1$s Setting field label.
			throw new Exception( wp_sprintf( __( '%1$s is required. ', 'jubelio-shipment' ), $field_label ) );
		}

		return $value;
	}


	/** Admin Options */
	public function admin_options() {
		$this->calling_post();
	}

	/** Calling Post */
	private function calling_post() {

		?>
		<h3><?php echo esc_html($this->method_title); ?></h3>
    <p><?php echo esc_html($this->method_description); ?></p>
		<table class="form-table">
				<?php $this->generate_settings_html(); ?>
		</table>
		<?php
		add_action(
			'admin_notice_error',
			function() {
				?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'Invalid Client ID or Client Secret', 'jubelio-shipment' ); ?></p>
			</div>
				<?php
			}
		);

		//phpcs:disable
		if ('POST' === $_SERVER['REQUEST_METHOD'] ) {

			$client_id     = sanitize_text_field( $_POST['woocommerce_jubelioshipment_client_id'] );
			$client_secret = sanitize_text_field( $_POST['woocommerce_jubelioshipment_client_secret'] );
		//phpcs:enable

			$response = $this->api->generate_token( $client_id, $client_secret );
			if ( is_wp_error( $response ) ) {
				do_action( 'admin_notice_error' );
			}

			if ( ! is_wp_error( $response ) ) {
				update_option( 'jubelio_shipment_token', $response['token'] );

				// Whitelist domain for Google API.
				$my_domain = get_my_domain();

				if ( ! empty( $my_domain ) ) {
					// Skip if you are use Jubelio Store subdomain.
					if ( ! str_contains( $my_domain, '.jubelio.store' ) ) {
						$response = $this->api->inisialize_map( $my_domain );
						if ( ! is_wp_error( $response ) ) {
							update_option( 'jubelio_shipment_domain', $my_domain );
						}
					}
				}
				// End Domain Whitelist.
			}
		}

	}

	/**
	 * Register Hooks
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'woocommerce_shipping_' . $this->id . '_instance_option', array( $this, 'instance_option_mapping' ), 10, 3 );
	}

	/**
	 * Filter option value mapping.
	 *
	 * @param mixed                           $option Original setting value.
	 * @param string                          $key Setting key.
	 * @param JubelioShipment_Shipping_Method $instance Instance class object.
	 *
	 * @return mixed
	 */
	public function instance_option_mapping( $option, $key, $instance ) {
		if ( $instance->instance_id !== $this->instance_id ) {
			return $option;
		}

		switch ( $key ) {
			case 'selected_couriers':
				if ( ! $option ) {
					$selected_couriers = array();
					return $selected_couriers;
				}
				break;
		}
		return $option;
	}

	/**
	 * Calculate the shipping fees.
	 *
	 * @param  array $package Package.
	 * @throws WP_Error If the field value is invalid.
	 */
	public function calculate_shipping( $package = array() ) {

		$all_items = $this->get_all_items( $package['contents'] );
		$weight    = $all_items['total_weight'];
		$items     = $all_items['items'];

		$dest_area_id = $package['destination']['address_2'];
		$dest_zipcode = $package['destination']['postcode'];

		foreach ( $package['contents'] as $content ) {
			$jubelio_shipment_package = $content['data']->jubelio_shipment;
		}

		$coordinate         = $jubelio_shipment_package['coordinate'] ?? '';
		$origin_coordinate  = $this->get_option( 'origin_coordinate', '' );
		$activate_insurance = $jubelio_shipment_package['activate_insurance'] ?? 'no';

		$active_multi_origin  = $jubelio_shipment_package['active_multi_origin'] ?? '';
		$base_origin_selected = false;

		if ( $active_multi_origin ) {
			$base_origin_selected = ( 'base_origin' === $jubelio_shipment_package['multi_origin']['location_id'] );
		}

		$body = array(
			'origin'              => array(
				'area_id' => $this->get_option( 'origin_location_subdistrict' ),
				'zipcode' => $this->get_option( 'origin_location_zipcode' ),
			),
			'destination'         => array(
				'area_id' => $dest_area_id,
				'zipcode' => $dest_zipcode,
			),
			'weight'              => $weight,
			'service_category_id' => 0,
			'total_value'         => WC()->cart->get_subtotal(),
		);

		if ( ! empty( $origin_coordinate ) ) {
			$body['origin']['coordinate'] = $origin_coordinate;
		}

		if ( ! empty( $coordinate ) && ! empty( $origin_coordinate ) ) {
			$body['destination']['coordinate'] = $coordinate;
		}

		if ( ( 'yes' === $active_multi_origin ) && ( false === $base_origin_selected ) ) {
			$body['origin']['area_id']    = $jubelio_shipment_package['multi_origin']['subdistrict_id'];
			$body['origin']['zipcode']    = $jubelio_shipment_package['multi_origin']['postcode'];
			$body['origin']['coordinate'] = $jubelio_shipment_package['multi_origin']['coordinate'];
		}

		if ( ! empty( $items ) ) {
			$body['items'] = $items;
		}

		$this->show_debug(
			wp_json_encode(
				array(
					'calculate_shipping.$api_request_body' => $body,
				)
			)
		);

		$rates = $this->api->post_rates( $body );



		do_action( 'jubelio_shipment_rates_debug', $rates );

		if ( is_wp_error( $rates ) ) {
			return new WP_Error( 'Error get rates!' );
		}

		$selected_couriers = $this->get_option( 'selected_couriers', array() );
		$promotion_count   = 0;

		foreach ( $rates as $rate ) {

			if ( is_array( $selected_couriers ) && isset( $rate['courier_service_id'] ) ) {
				if ( ! in_array( (string) $rate['courier_service_id'], $selected_couriers, true ) ) {
					continue; }
			}

			if ( 'yes' === $this->get_option( 'show_eta', 'yes' ) ) {
				$sla = $this->get_sla( $rate['eta_from'], $rate['eta_to'] );
			}

			$is_promotion = ( null !== ( $rate['promotion'] ?? null ) );
			if ( $is_promotion ) {
				$promotion_count++;
			}

			$cod_rate = '';

			if( !empty( $rate['is_cod_supported'] ) ) {
				$cod_rate = $rate['cod_fee'];
			}

			$promotion_rate     = ( null !== ( $rate['final_rates'] ?? null ) ) ? $rate['final_rates'] : $rate['rates'];
			$courier_service_id = $rate['courier_service_id'];

			$rate_data = array(
				'id'        => 'jubelio_shipment:' . $courier_service_id . ':' . $rate['courier_service_code'],
				'label'     => $rate['courier_service_name'] . $sla,
				'cost'      => $is_promotion ? $promotion_rate : $rate['rates'],
				'meta_data' => array(
					'_jubelio_shipment_data'      => $rate,
					'_jubelio_shipment_cod'       => $cod_rate,
					'_jubelio_shipment_insurance' => 'yes' === $activate_insurance ? $rate['shipping_insurance'] : 0,
				),
			);

			if ( ( 'yes' === $active_multi_origin ) && ( false === $base_origin_selected ) ) {
				$rate_data['meta_data']['mo_location_id'] = $jubelio_shipment_package['multi_origin']['location_id'];
			}

			do_action( 'jubelio_shipment_get_shipping_rate', $rate_data, $rate );

			$this->add_rate( $rate_data );

		}

		if ( better_is_checkout() ) {
			// Showing notification to client when several couriers have a promotion.
			if ( $promotion_count > 0 ) {
				wc_clear_notices(); // Clear all notification first to prevent duplicate.
				wc_add_notice( __( 'Congratulations you got a shipping discount for several couriers.', 'jubelio-shipment' ) );
			}
		}
	}

	/** Get SLA
	 *
	 * @param String $from SLA From.
	 * @param String $to SLA To.
	 * @return String
	 */
	private function get_sla( $from, $to ) {
		$currentDate = new DateTime( date("Y-m-d") );
		$sla_from = new DateTime( $from );
		$sla_to   = new DateTime( $to );

		// Calculate the difference in days
		$interval_from 	= $currentDate->diff($sla_from);
		$interval_to   	= $currentDate->diff($sla_to);
		$daysDifference_from 	= $interval_from->days;
		$daysDifference_to 		= $interval_to->days;

		// Create a string representation of the date range
		if ( $daysDifference_from < 1 ) {
			$sla_string = __( ' Hari Ini ' , 'jubelio-shipment' );
		}
		elseif ( ( $daysDifference_from === 1 || $daysDifference_from < 1 ) && ( ( $daysDifference_to === 1 || $daysDifference_to < 1 ) ) ) {
			$dateRangeString = " $daysDifference_to Day ";
			$sla_string = __( $dateRangeString , 'jubelio-shipment' );
		}
		elseif( $daysDifference_from < 1 && $daysDifference_to < 1 )
		{
			$dateRangeString = " 1 Day ";
			$sla_string = __( $dateRangeString , 'jubelio-shipment' );
		}
		else {
			$dateRangeString = " ". $daysDifference_from . " - $daysDifference_to Days ";
			 $sla_string = __( $dateRangeString , 'jubelio-shipment' );
		}

		return ' (' . $sla_string . ')';
	}

	/**
	 * Show debug info
	 *
	 * @since 1.0.0
	 * @param string $message     The text to display in the notice.
	 * @param string $notice_type The name of the notice type - either error, success or notice.
	 * @return void
	 */
	private function show_debug( $message, $notice_type = 'notice' ) {
		$message = $this->id . '_' . $this->instance_id . ' : ' . $message;

		if (
			defined( 'WC_DOING_AJAX' )
			|| 'yes' !== get_option( 'woocommerce_shipping_debug_mode', 'no' )
			|| ! current_user_can( 'manage_options' )
			|| wc_has_notice( $message )
		) {
			return;
		}

		wc_add_notice( $message, $notice_type );
	}

	/** Translate Name of Products Ids
	 *
	 * @param Array $category_ids Category IDs.
	 * @return Array
	 */
	private function translate_name_from_category_ids( $category_ids ) {
		$names = array();
		foreach ( $category_ids as $category_id ) {
			$term    = get_term_by( 'id', $category_id, 'product_cat' );
			$names[] = $term->name;
		}
		if ( ! empty( $names ) ) {
			$names = implode( ',', $names );
		}
		return $names;
	}

	/** Get All Items
	 *
	 * @param Object $contents Content from Package.
	 * @return Array
	 */
	private function get_all_items( $contents ) {
		$items        = array();
		$total_weight = array();

		foreach ( $contents as $item ) {
			$data     = $item['data'];
			$weight   = is_numeric( $data->get_weight() ) ? $data->get_weight() : 0;
			$width    = is_numeric( $data->get_width() ) ? $data->get_width() : 0;
			$length   = is_numeric( $data->get_length() ) ? $data->get_length() : 0;
			$height   = is_numeric( $data->get_height() ) ? $data->get_height() : 0;
			$quantity = abs( $item['quantity'] );
			$value    = is_numeric( $data->get_sale_price() ) ? $data->get_sale_price() : $data->get_regular_price();
			$name     = $data->get_name();

			$category_names = $this->translate_name_from_category_ids( $data->get_category_ids() );

			$dimentions = ( $length * $width * $height );
			if ( $dimentions > 0 ) {
				array_push(
					$items,
					array(
						'nama_barang' => $name,
						'category'    => $category_names,
						'weight'      => $weight,
						'length'      => $length,
						'width'       => $width,
						'height'      => $height,
						'quantity'    => $quantity,
						'value'       => $value,
					)
				);
			}
			array_push( $total_weight, $weight * $quantity );
		}

		$total_weight_gram = wc_get_weight( array_sum( $total_weight ), 'g' );
		$base_weight       = absint( $this->get_option( 'base_weight' ) );

		if ( $base_weight && $total_weight_gram < $base_weight ) {
			$total_weight_gram = $base_weight;
		}

		return array(
			'items'        => $items,
			'total_weight' => $total_weight_gram,
		);
	}
}
