function initMap() {

	// const fromInit = document.getElementById('popup_from').value;
  // let longlat = '-6.200000,106.816666)';

	// if (document.getElementById(fromInit)) {
	// 	longlat = document.getElementById(fromInit).value;
	// }

	// const parseLonglat = longlat.match(/-?[0-9]\d*(\.\d+),?/gm);
	// const parseLongitude = Number(parseLonglat[0].replace(',','')) ?? -6.200000;
	// const parseLatitude = Number(parseLonglat[1].replace(',','')) ?? 106.816666;

	const myLatlng = new google.maps.LatLng( -6.200000, 106.816666 );
	const map = new google.maps.Map(document.getElementById("map"),
		{
			zoom: 13,
			center: myLatlng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		});

	if (navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function (position) {
			initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
			map.setCenter(initialLocation);
		});
	}

	var icon = {
		url: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAABmJLR0QA/wD/AP+gvaeTAAAEPElEQVRogdWZTWhcVRTHf+eNVavU6kpbbayKIloFPzCVzjxFYzUBEdHgQpCqUarW1qpgBBEUF4KlC6GIYhbisl3YLhqx1SbzksaULMSSiiLaFtMhLkr9SMtkMnNcTGxiM/e9c997fv2Wc/7nnv/h3rnv3fvgf47kNZCGrKBBJ0IRuB5oAy4EFDiBcARlHCGiTr8McyyPupkb0CL3IbwChEBgTGsA+1DeliH2ZqmfuoFZ428At2UxAIygvJ62Ee8GtIOlVNkGPJqmYAwf0eB5GeY3nySvBjTkahrsQrjWz5uZQxR4QAb43ppgbkBDbqDBHoSL03kzU0G4W8p8YxGbGtA1LCdgFLgskzU7xyjQLgP8lCRM3DX0FhZRYCf/nHmA5dTZoXdyVpIweds7j9dQbs3Flh/t1Hk1SRS7hLRIG8K3wLkehQ8i9FFnL+dzGIApVlKggwY9CKs8xjrJDNfICBMuQXwDIdtQnjUWqwKbiXhfmg+qheN1U2CS9ShbgbNNowrvSplN7rADXcMSAiaAJYYyVQI6ZZB9Fk8achdKP7YmfmExl8pnTLUKuv8DAWuxmQd4wWoeQMp8AbxklC/lFB2uoLsB5R5jgYNEfGDUznEJ7wHjRvW9roC7AeEm09DKh641H4dspw70GeU3ugJx2+gVNicZ3iYL7DEqr3IF4hq4wGjiqNHEQmocMSovcgXiGqgbTaQ/U8yYc2uuQFwDP5uGDlhhNLGQxbQZlU4vcQ3YloaYd6uF1I256l5qcQ0MmwZv0KPdFEza+Z6aOT0mccB+d8htLDINLqxikvUm7XwqPAdcZ9Kq24v7VaKTc/idCjE7wDymgS6J+Nzkp0gHwm5gkUF+nOMsk3GmWwWdMyD9VIEdFkM032l2a4kNcctJuylokY0e5kHZ7jIPya/T7QhfmgrNMQ70zT6kDs/+tpIZ1iI8iXXZzNEuEQdcwcR9WEtEQNGzaF6UJeKOOEHyiUx4Jzc7vihbkiS2Q32J/cDtmQ35cYCI1dK8mnRiuwoUenOx5ENAb5L5psyAlCkDOzObsvOJ9YBkvYwFZSO0PtblzEmEF61icwMyxFGEt9J58kB5U8r8aJXbZ6Cp3gKM+HoyI4xxiq1+KZ5okSsRvsJ+4LcyhXKzDPGdT5LfDAAyxA8oL/vmGdjsax6yfOAI2YVyf9r8M/iUiC7Ltnkm3jNwmipPAZXU+XNUmGZdGvOQoQEZZZKAh8D9pmigRsAjMspk2gHSzwAgg4z47Nkt2CSDxoOTy0OW5D/RkD6UJzzTPpaIx7LWzjQDp6mxAWHMrBfGKPB0HqXz+9BdYhnNh9zlCRUnCFht+XxkIZ8ZACSiAnQBJ2Jkv6J05WUecmwAQCIOoTxI652pRsDDEvF1njVzbQBAhhhAeJy/3lg3ENbJoPky999HSzyjJVRLNLSU4t7ov4CG9Gr4957m/gCUKBdIHeoOkAAAAABJRU5ErkJggg==", // url
		scaledSize: new google.maps.Size(30, 30), // scaled size
		origin: new google.maps.Point(0, 0), // origin
		anchor: new google.maps.Point(0, 0) // anchor
	};

	var geocoder = new google.maps.Geocoder();
	var marker = new google.maps.Marker({
		position: myLatlng,
		icon: icon,
		map: map,
		draggable: true
	});

	var input = document.getElementById('search_address');
	var autocomplete = new google.maps.places.Autocomplete(input);
	autocomplete.bindTo("bounds", map);

	autocomplete.addListener("place_changed", function () {
		marker.setVisible(false);

		var place = autocomplete.getPlace();

		if (!place.geometry) {
			window.alert("Autocomplete is returned place contains no geometry");
			return;
		}

		// If the place has a geometry, then present it on a map.
		if (place.geometry.viewport) {
			map.fitBounds(place.geometry.viewport);
		}
		else {
			map.setCenter(place.geometry.location);
			map.setZoom(17);
		}

		marker.setPosition(place.geometry.location);
		marker.setVisible(true);

		var address = "";
		if (place.address_components) {
			address = [
				(place.address_components[0] && place.address_components[0].short_name || ""),
				(place.address_components[1] && place.address_components[1].short_name || ""),
				(place.address_components[2] && place.address_components[2].short_name || "")
			].join(" ");
		}

		const latitude = marker.getPosition().lat();
		const longitude = marker.getPosition().lng();
		document.getElementById('search_address').value = place.formatted_address;
		document.getElementById('address_coordinate').value = `(${latitude},${longitude})`
	})

	google.maps.event.addListener(marker, "dragend", function () {
		var point = marker.getPosition();
		map.panTo(point);

		geocoder.geocode({ "latLng": marker.getPosition() }, function (results, status) {
			var address = results[0].formatted_address;

			if (status == google.maps.GeocoderStatus.OK) {
				map.setCenter(results[0].geometry.location);

				marker.setPosition(results[0].geometry.location);

				const latitude = marker.getPosition().lat();
				const longitude = marker.getPosition().lng();

				document.getElementById('search_address').value = address;
				document.getElementById('address_coordinate').value = `(${latitude},${longitude})`
			}

		})
	})
}
