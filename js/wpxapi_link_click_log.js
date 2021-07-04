jQuery(document).ready( function($) {
	debug = 1;

	$('a').click(function() {
		// Let's disable the inputs for the duration of the Ajax request.
		// Note: we disable elements AFTER the form data has been serialized.
		// Disabled form elements will not be serialized.
		// $inputs.prop("disabled", true);

		if (typeof jwplayer().onPlay !== 'function' && typeof jwplayer().onPause !== 'function') {
			var wpxapi_click_url_requested =  $(this).attr('href');
			var wpxapi_click_referrer_location = document.location.href;
			var data = {
				action: "xapiclicklog_action",
				wpxapi_click_log: "true",
				wpxapi_nonce: wpxapi_ajax_object.wpxapi_nonce,
				wpxapi_uid: wpxapi_ajax_object.wpxapi_uid,
				wpxapi_blogid: wpxapi_ajax_object.wpxapi_blogid,
				wpxapi_click_url_requested: wpxapi_click_url_requested,
				wpxapi_click_referrer_location: wpxapi_click_referrer_location
			};

			if (debug) {
				console.log( "Data to be transmitted1: " + data.toString() );
			}

			$.post( wpxapi_ajax_object.ajax_url, data, function( response ) {
				if (debug) {
					// alert('Got this response from the server: ' + response.toString() );
					console.log( "Data transmitted to server1: " + data.toString() );
					console.log( "Got this response from the server1: " + response.toString() );
				}
				// die();
			});
		}
	});

	$('a').click(function() {
		if (typeof jwplayer().onPlay === 'function' && typeof jwplayer().onPause === 'function') {
			// We're not going to sent a statement on PLAY for this example.  Instead, we're going to set the variable for what point within the video the user started playing a given period
			// This section shows the SEGMENT that the video played.  This is why we don't send the statement on the actual PLAY of the video, but when play stops.  This way we can gather both the start and stop points more easily
			// onPlay
			var jw_playcount, jw_playfrom, jw_position, jw_duration, jw_pausestatement = 0;
			jwplayer().onPlay(function(event){
				jw_playfrom = jwplayer().getPosition();
				if (debug) {
					console.log("Video Played from timestamp: " + jw_playfrom);
				}
			});
			// onPause
			jwplayer().onPause(function(event){
				jw_position = jwplayer().getPosition();
				jw_duration = jwplayer().getDuration();
				jw_pausestatement = 1;
				if (debug) {
					console.log("Video Paused. Played from " + jw_playfrom + " seconds, to " + jw_position + " seconds, full video duration: " + jw_duration + " seconds. PauseStatement = " + jw_pausestatement);
				}
			});
			// end onPlay
			// Send statement when someone pauses the video

			var wpxapi_click_url_requested =  $(this).attr('href');
			var wpxapi_click_referrer_location = document.location.href;
			var jw_videourl = window.location.pathname;
			var jw_filename = jw_videourl.substring(jw_videourl.lastIndexOf('/')+1, jw_videourl.lastIndexOf('.'));
			var data = {
				// action: "xapiclicklog_action",
				// wpxapi_click_log: "true",
				wpxapi_nonce: wpxapi_ajax_object.wpxapi_nonce,
				wpxapi_uid: wpxapi_ajax_object.wpxapi_uid,
				wpxapi_blogid: wpxapi_ajax_object.wpxapi_blogid,
				wpxapi_click_url_requested: wpxapi_click_url_requested,
				wpxapi_click_referrer_location: wpxapi_click_referrer_location,
				jw_playcount,
				jw_playfrom,
				jw_videourl,
				jw_filename,
				jw_position,
				jw_duration,
				jw_pausestatement
			};

			if (debug) {
				console.log( "Data to be transmitted2: " + data.toString() );
			}

			$.post( wpxapi_ajax_object.ajax_url, data, function( response ) {
				if (debug) {
					// alert('Got this response from the server: ' + response.toString() );
					console.log( "Data transmitted to server2: " + data.toString() );
					console.log( "Got this response from the server2: " + response.toString() );
				}
				// die();
			});
		}
	});
});