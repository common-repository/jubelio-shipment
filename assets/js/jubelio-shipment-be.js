(function ($) {
	'use strict';

	$(document.body).on('wc_backbone_modal_loaded', function () {
		const form = $('form');
		const hidden_fields = [
			'location_zipcode',
			'location_subdistrict',
			'location_address',
		];

		const hideTr = (el) => {
			$('#woocommerce_jubelioshipment_origin_' + el, form).closest('tr').hide();
		}

		hidden_fields.map((field) => { hideTr(field) });

		$('#woocommerce_jubelioshipment_origin_location_search', form).selectWoo({
			cacheDataSource: [],
			ajax: {
				url: 'https://api-shipment.jubelio.com/regions',
				dataType: 'json',
				type: 'GET',
				delay: 300,
				cache: true,
				data: function (params) {
					const query = {
						name: params.term,
					}
					return query;
				},
				processResults: function (data) {
					return {
						results: $.map(data, function (item) {
							return {
								text: item.name,
								id: item.area_id,
								province: item.province,
								city: item.city,
								district: item.district,
								area: item.area,
								area_id: item.area_id,
								zipcode: item.zipcode
							}
						})
					};
				}
			},
			minimumInputLength: 3,
		});

		$('#woocommerce_jubelioshipment_origin_location_search').append('<option value="' + $('#woocommerce_jubelioshipment_origin_location_address').val() + '" selected="selected">' + $('#woocommerce_jubelioshipment_origin_location_address').val() + '</option>');

		$('#woocommerce_jubelioshipment_origin_location_search', form).on('select2:select', function (e) {
			const data = e.params.data;
			$('#woocommerce_jubelioshipment_origin_location_zipcode').val(data.zipcode);
			$('#woocommerce_jubelioshipment_origin_location_address').val(data.text);
			$('#woocommerce_jubelioshipment_origin_location_subdistrict').val(data.area_id);
		});

		$('#woocommerce_jubelioshipment_origin_coordinate').on('click', (evt) => {
			evt.preventDefault();
			$('#popup_map').toggleClass('show');
			$('#popup_from').val(evt.target.id);
		});

		$('#popup_close').on('click', (evt) => {
			evt.preventDefault();
			$('#popup_map').removeClass('show');
		})

		$('#popup_save').on('click', (evt) => {
			document.getElementById('jubelio-maps').contentWindow.postMessage({
				messageType: 'REQUEST_UPDATE'
			}, '*' );

			setTimeout(() => {

				const from = document.getElementById('popup_from').value;
				const coordinate = document.getElementById('address_coordinate').value;
				document.getElementById(from).value = coordinate;
				$('#popup_map').removeClass('show');
			}, 200);

			evt.preventDefault();

		});


		$("#check_all").click(function(){
			$('input:checkbox').not(this).prop('checked', this.checked);
		});


	});

	$('document.body').ready(() => {

		const hideProfileField = () => {
			const fields = [
				'address_1',
				'address_2',
				'city',
				'postcode',
				'state'
			];

			fields.map((field) => {
				$('#billing_' + field).closest('tr').hide();
				$('#shipping_' + field).closest('tr').hide();
			});
		};

		const hideSettingWebstore = () => {
			const webstore_id = parseInt($('#woocommerce_jubelioshipment_webstore_id').val());
			const hidden_options = [
				'payment_voucher',
				'shipping_insurance',
				'multi_origin',
			];

			hidden_options.map((options) => {
				$('#woocommerce_jubelioshipment_' + options).attr('disabled', (webstore_id <= 0));
			});
		};

		hideProfileField();
		hideSettingWebstore();

	});

})(jQuery);

function hideSomeFieldWebstore(evt) {
	(function ($) {
		'use strict';

		const webstore_id = parseInt($(evt).val());
		const hidden_options = [
			'payment_voucher',
			'shipping_insurance',
			'multi_origin',
		];

	  const disabled = ( isNaN( webstore_id ) || webstore_id < 1 );

		hidden_options.map((options) => {
			$('#woocommerce_jubelioshipment_' + options).attr('disabled', disabled);
		});
	})(jQuery);
};