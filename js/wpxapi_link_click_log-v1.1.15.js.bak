jQuery(document).ready( function($) {
	debug = 1;

	$('a').on("click",function() {
		// Let's disable the inputs for the duration of the Ajax request.
		// Note: we disable elements AFTER the form data has been serialized.
		// Disabled form elements will not be serialized.
		// $inputs.prop("disabled", true);

		// if (typeof jwplayer().onPlay === 'function' && typeof jwplayer().onPause === 'function') {
			var wpxapi_click_url_requested =  $(this).attr('href');
			var wpxapi_click_referrer_location = document.location.href;
			var data1 = {
				action: "xapiclicklog_action",
				wpxapi_click_log: "true",
				wpxapi_nonce: wpxapi_ajax_object1.wpxapi_nonce,
				wpxapi_uid: wpxapi_ajax_object1.wpxapi_uid,
				wpxapi_blogid: wpxapi_ajax_object1.wpxapi_blogid,
				wpxapi_click_url_requested: wpxapi_click_url_requested,
				wpxapi_click_referrer_location: wpxapi_click_referrer_location,
			};

			if (debug) {
				console.log( "Data1 (xapiclicklog_action) to be transmitted: " + data1.toString() );
			};

			$.post( wpxapi_ajax_object1.ajax_url, data1, function( response1 ) {
				if (debug) {
					// alert('Got this response from the server: ' + response1.toString() );
					console.log( "Data1 (xapiclicklog_action) transmitted to Server: " + data1.toString() );
					console.log( "Got this Response1 from the Server: " + response1.toString() );
				};
				// die();
			});
		// }
	});
});