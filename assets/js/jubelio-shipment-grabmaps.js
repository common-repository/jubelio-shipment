const allowedOrigin = ["https://maps-ui.jubelio.com"];
window.addEventListener('message', event => {

		const searchAddress = document.getElementById('search_address');
		const coordinate = document.getElementById('address_coordinate');

		if (allowedOrigin.includes(event.origin)) {
				const eventData= event.data;
				const messageType= eventData.messageType;

				switch(messageType){
						case 'MAP_RESULT_UPDATE':

								if(searchAddress){
										searchAddress.value = eventData.payload.address;
								}

								if(coordinate){
										coordinate.value = eventData.payload.coordinate;
								}

								console.log( eventData );
						return;
						break;
						default:
						return;
						break;
				}
		} else {
				return;
		}
});