(function ($) {
	'use strict';

	$("body").on("updated_checkout", function () {

	 if ( $('#activate_insurance').prop('checked') ) {
			const checked = 'true';

			const request = $.ajax({
				url: jubelio_shipment.ajaxurl,
				type: "POST",
				dataType: "json",
				cache: true,
				data: {
					action: "jubelio_shipment_update_session_and_meta",
					jubelio_shipment_nonce: jubelio_shipment.nonce,
					value: checked,
					from: "activate_insurance",
				},
			});
			request.done(function () {
				console.log('sukses');
			});
		};
	})

	$('#shipping_voucher').on('input', (evt) => {
		const text = $(evt.target).val().trim();
		$('#shipping_voucher_button').attr('disabled', text.length < 7);
	});

	$('#shipping_voucher_button').on('click', (evt) => {
		evt.preventDefault();

		var $link = $(evt.target);
		const voucher_code = $('#shipping_voucher').val().trim();
		if (voucher_code.length < 7) {
			evt.stopPropagation();
			return;
		}

		// prevent floading click.
		if (!$link.data('lockedAt') || +new Date() - $link.data('lockedAt') > 300) {

			$('#shipping_voucher_button').attr('disabled', true);
			const text_before_loading = $(evt.target).text().trim();

			const request = $.ajax({
				url: jubelio_shipment.ajaxurl,
				type: "POST",
				dataType: "json",
				cache: true,
				delay: 300,
				data: {
					action: "jubelio_shipment_set_voucher",
					jubelio_shipment_nonce: jubelio_shipment.nonce,
					voucher_code: voucher_code,
				},
			});

			$(evt.target).text('Loading ...');

			request.done(function () {
				$('body').trigger('update_checkout');
				$(evt.target).text(text_before_loading);
				$('#shipping_voucher_button').attr('disabled', false);
			});

			request.error((response) => {
				const data = response.responseJSON.data;
				$('.notification-voucher-wrapper').html(`<td colspan="2" class="alert-color" style="text-align: left">${data.message}</td>`);
				$('#shipping_voucher_field').addClass('woocommerce-invalid');
				$(evt.target).text(text_before_loading);
				$('#shipping_voucher_button').attr('disabled', false);
			});

		}

		$link.data('lockedAt', +new Date());

	});

	$('#activate_insurance').on('input', function (evt) {
		const checked = evt.target.checked;

		const request = $.ajax({
			url: jubelio_shipment.ajaxurl,
			type: "POST",
			dataType: "json",
			cache: true,
			data: {
				action: "jubelio_shipment_update_session_and_meta",
				jubelio_shipment_nonce: jubelio_shipment.nonce,
				value: checked,
				from: evt.target.id,
			},
		});
		request.done(function () {
			$('body').trigger('update_checkout');
		});
	});


})(jQuery);

