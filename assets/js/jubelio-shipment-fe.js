(function ($) {
	"use strict";

	$("#billing_address_search, #shipping_address_search").selectWoo({
		cacheDataSource: [],
		width: "100%",
		ajax: {
			url: "https://api-shipment.jubelio.com/regions",
			dataType: "json",
			type: "GET",
			delay: 300,
			cache: true,
			data: function (params) {
				var query = {
					name: params.term,
				};
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
							zipcode: item.zipcode,
						};
					}),
				};
			},
		},
		minimumInputLength: 3,
	});

	$("body").on("updated_checkout", function () {
		// Get the selected courier value
		var selectedCourier = $("[name*=shipping_method]:checked").val();
		var checkListCourier = $("[name*=shipping_method]").length;

		if( checkListCourier > 1 ) {
			// Check if the courier is not selected

			if (!selectedCourier || selectedCourier === "undefined") {
				$("button#place_order").attr("disabled", "disabled");
			} else {
				$("button#place_order").removeAttr("disabled");
			}
		} else {

			$("button#place_order").removeAttr("disabled");
		}

		$('th:contains("COD Fee")').addClass('cod-fee-lines');

		var fees = $('.cod-fee-lines').parent().find('td > span > bdi').text();
		var selectedPaymentMethod = $('input[name="payment_method"]:checked').val();

		if (selectedPaymentMethod === 'cod') {
			// Add or update the payment description with the COD fee
			var newDescription = 'COD fee : ' + fees;
			$('.payment_box.payment_method_cod').html(newDescription);
			$('button#place_order').attr('type', 'button');
			console.log( selectedPaymentMethod );
		}
		else
		{
			$('button#place_order').attr('type', 'submit');
		}

		// Check if the selected payment method is Cash on Delivery (cod)
		$('#place_order').on('click', function() {
			var selectedPaymentMethod = $('input[name="payment_method"]:checked').val();
			if (selectedPaymentMethod === 'cod') {
				$('#codConfirmation').toggleClass('show');

				$('#confirmCod').on('click', function(){
					$('form.checkout').submit();
					$('#codConfirmation').toggleClass('show');
				})

				$('#cancelCod').on('click', function(){
					$('#codConfirmation').toggleClass('show');
				})
			}
		})
	});

	$(".update_meta_on_change").on("update_meta", (evt) => {
		$.ajax({
			url: jubelio_shipment.ajaxurl,
			type: "POST",
			dataType: "json",
			data: {
				action: "jubelio_shipment_update_session_and_meta",
				jubelio_shipment_nonce: jubelio_shipment.nonce,
				value: evt.target.value,
				from: evt.target.id,
				different_address: document.getElementById(
					"ship-to-different-address-checkbox"
				).checked,
			},
		});
	});

	$("#ship-to-different-address-checkbox").on("change", (evt) => {
		$(".update_meta_on_change").trigger("update_meta");
	});

	$('#billing_pinpoint_location_field').find('label > span').hide();

	$("#billing_address_search").append(
		'<option value="' +
			$("#billing_address_1").val() +
			'" selected="selected">' +
			$("#billing_address_1").val() +
			"</option>"
	);
	$("#shipping_address_search").append(
		'<option value="' +
			$("#shipping_address_1").val() +
			'" selected="selected">' +
			$("#shipping_address_1").val() +
			"</option>"
	);

	$("#billing_address_1, #shipping_address_1").on(
		"keyup keypress blur change",
		function (e) {
			const id = e.target.id;
			const identifier = id.substring(0, id.indexOf("_"));
			if ($("#" + identifier + "_address_search option:visible").length < 1) {
				$("#" + identifier + "_address_search").append(
					'<option value="' +
						$("#" + identifier + "_address_1").val() +
						'" selected="selected">' +
						$("#" + identifier + "_address_1").val() +
						"</option>"
				);
			}
		}
	);

	$("#billing_address_search, #shipping_address_search").on(
		"select2:select",
		function (e) {
			const data = e.params.data;
			const id = e.target.id;
			const identifier = id.substring(0, id.indexOf("_"));

			$(`#${identifier}_city`).val(data.city);
			$(`#${identifier}_postcode`).val(data.zipcode);
			$(`#${identifier}_address_1`).val(data.text);
			$(`#${identifier}_address_2`).val(data.id);

			const province = getProvince(data.province);
			$(`#${identifier}_state`).val(province.code).selectWoo();
			$(`#${identifier}_coordinate`).val("");
			$(`#${identifier}_pinpoint_location`).val("");
			$(`#${identifier}_new_subdistrict`).val(data.id);
			$(`#${identifier}_district`).val(data.district);
			$(`#${identifier}_subdistrict`).val(data.area);

			const request = $.ajax({
				url: jubelio_shipment.ajaxurl,
				type: "POST",
				dataType: "json",
				data: {
					action: "jubelio_shipment_set_coordinate",
					jubelio_shipment_nonce: jubelio_shipment.nonce,
					coordinate: "",
					pinpoint_location: "",
					identifier: identifier,
				},
			});

			request.done(function () {
				$("body").trigger("update_checkout");
			});
		}
	);

	function getProvince(name) {
		const provinces = [
			{ name: "ACEH", code: "AC" },
			{ name: "BALI", code: "BA" },
			{ name: "BANTEN", code: "BT" },
			{ name: "BENGKULU", code: "BE" },
			{ name: "DAERAH ISTIMEWA YOGYAKARTA", code: "YO" },
			{ name: "DKI JAKARTA", code: "JK" },
			{ name: "GORONTALO", code: "GO" },
			{ name: "JAMBI", code: "JA" },
			{ name: "JAWA BARAT", code: "JB" },
			{ name: "JAWA TENGAH", code: "JT" },
			{ name: "JAWA TIMUR", code: "JI" },
			{ name: "KALIMANTAN BARAT", code: "KB" },
			{ name: "KALIMANTAN SELATAN", code: "KS" },
			{ name: "KALIMANTAN TENGAH", code: "KT" },
			{ name: "KALIMANTAN TIMUR", code: "KI" },
			{ name: "KALIMANTAN UTARA", code: "KU" },
			{ name: "KEPULAUAN RIAU", code: "KR" },
			{ name: "LAMPUNG", code: "LA" },
			{ name: "MALUKU", code: "MA" },
			{ name: "MALUKU", code: "MU" },
			{ name: "MALUKU UTARA", code: "MU" },
			{ name: "NUSA TENGGARA BARAT", code: "NB" },
			{ name: "NUSA TENGGARA TIMUR", code: "NT" },
			{ name: "PAPUA", code: "PA" },
			{ name: "PAPUA", code: "PB" },
			{ name: "PAPUA BARAT", code: "PB" },
			{ name: "RIAU", code: "RI" },
			{ name: "RIAU", code: "KR" },
			{ name: "SULAWESI BARAT", code: "SR" },
			{ name: "SULAWESI SELATAN", code: "SN" },
			{ name: "SULAWESI TENGAH", code: "ST" },
			{ name: "SULAWESI TENGGARA", code: "SG" },
			{ name: "SULAWESI UTARA", code: "SA" },
			{ name: "SUMATERA BARAT", code: "SB" },
			{ name: "SUMATERA SELATAN", code: "SS" },
			{ name: "SUMATERA UTARA", code: "SU" },
		];
		return provinces.find((province) => province.name === name);
	}

	$("document").ready(() => {
		$("#billing_pinpoint_location, #shipping_pinpoint_location").on(
			"click",
			(evt) => {
				evt.preventDefault();
				$("#popup_map").toggleClass("show");
				$("#popup_from").val(evt.target.id);
			}
		);

		$("#popup_close").on("click", (evt) => {
			evt.preventDefault();
			$("#popup_map").removeClass("show");
		});

		$("#popup_save").on("click", (evt) => {

			document.getElementById('jubelio-maps').contentWindow.postMessage({
				messageType: 'REQUEST_UPDATE'
			}, '*' );

			setTimeout(() => {
				const from = document.getElementById("popup_from").value;
				const identifier = from.substring(0, from.indexOf("_"));

				const coordinate = document.getElementById("address_coordinate").value;
				const address = document.getElementById("search_address").value;

				document.getElementById(from).value = address;
				document.getElementById(identifier + "_coordinate").value = coordinate;

				const request = $.ajax({
					url: jubelio_shipment.ajaxurl,
					type: "POST",
					dataType: "json",
					data: {
						action: "jubelio_shipment_set_coordinate",
						jubelio_shipment_nonce: jubelio_shipment.nonce,
						coordinate: coordinate,
						pinpoint_location: address,
						identifier: identifier,
					},
				});

				request.done(function () {
					$("body").trigger("update_checkout");
				});

				$("#popup_map").removeClass("show");
				$("[name*=shipping_method]").prop("checked", false);

			}, 200);

			evt.preventDefault();

		});

		//remove duplicate shipping phone in thank you page.
		$("p.woocommerce-customer-details--phone").last().remove();

		$('th:contains("COD Fee")').addClass('cod-fee-lines');

		// Check if the selected payment method is Cash on Delivery (cod)
		$('#place_order').on('click', function() {
			var selectedPaymentMethod = $('input[name="payment_method"]:checked').val();
			if (selectedPaymentMethod === 'cod') {
				$('#codConfirmation').toggleClass('show');

				$('#confirmCod').on('click', function(){
					$('#codConfirmation').toggleClass('show');
					$('form.checkout').submit();

				})

				$('#cancelCod').on('click', function(){
					$('#codConfirmation').toggleClass('show');
				})
			}
		})
	});
})(jQuery);

const remove_voucher_code = function (evt) {
	const voucher_code = evt.dataset.text;
	const request = jQuery.ajax({
		url: jubelio_shipment.ajaxurl,
		type: "POST",
		dataType: "json",
		data: {
			action: "jubelio_shipment_remove_voucher_code",
			jubelio_shipment_nonce: jubelio_shipment.nonce,
			voucher_code: voucher_code,
		},
	});

	request.done(function () {
		jQuery("body").trigger("update_checkout");
	});
};

window["remove_voucher_code"] = remove_voucher_code;


