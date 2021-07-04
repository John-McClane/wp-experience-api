jQuery(document).ready( function($) {
	var debug = 1;
	if (debug) {
		$(window).ajaxComplete(function () {console.log('Ajax Complete'); });
		$(window).ajaxError(function (data, textStatus, jqXHR) {console.log('Ajax Error');
			console.log('data: ' + data);
			console.log('textStatus: ' + textStatus);
			console.log('jqXHR: ' + jqXHR); });
		$(window).ajaxSend(function () {console.log('Ajax Send'); });
		$(window).ajaxStart(function () {console.log('Ajax Start'); });
		$(window).ajaxStop(function () {console.log('Ajax Stop'); });
		$(window).ajaxSuccess(function () {console.log('Ajax Success'); });
	}

	// Let's disable the inputs for the duration of the Ajax request.
	// Note: we disable elements AFTER the form data has been serialized.
	// Disabled form elements will not be serialized.
	// $inputs.prop("disabled", true);

	$('a').click(function() {
		var wpxapi_click_url_requested =  $(this).attr('href');
		var wpxapi_click_referrer_location = document.location.href;
		var jw_playcount, jw_playfrom, jw_position, jw_duration, jw_pausestatement = 0;
		var jw_videourl = window.location.pathname;
		var jw_filename = jw_videourl.substring(jw_videourl.lastIndexOf('/')+1, jw_videourl.lastIndexOf('.'));
		var data = {
			action: "xapiclicklog_action",
			wpxapi_click_log: "true",
			wpxapi_nonce: wpxapi_ajax_object.wpxapi_nonce,
			wpxapi_uid: wpxapi_ajax_object.wpxapi_uid,
			wpxapi_blogid: wpxapi_ajax_object.wpxapi_blogid,
			wpxapi_click_url_requested: wpxapi_click_url_requested,
			wpxapi_click_referrer_location: wpxapi_click_referrer_location,
			jw_playcount: wpxapi_ajax_object.jw_playcount,
			jw_playfrom: wpxapi_ajax_object.jw_playfrom,
			jw_videourl: wpxapi_ajax_object.jw_videourl,
			jw_filename: wpxapi_ajax_object.jw_filename,
			jw_position: wpxapi_ajax_object.jw_position,
			jw_duration: wpxapi_ajax_object.jw_duration,
			jw_pausestatement: wpxapi_ajax_object.jw_pausestatement
		};

		// 	We're not going to sent a statement on PLAY for this example.  Instead, we're going to set the variable for what point within the video the user started playing a given period
		//	This section shows the SEGMENT that the video played.  This is why we don't send the statement on the actual PLAY of the video, but when play stops.  This way we can gather both the start and stop points more easily
		//  onPlay
		jwplayer().onPlay(function(event){
			jw_playfrom = jwplayer().getPosition();
			if (debug) {
				console.log("Video Played from timestamp: " + jw_playfrom);
			}
		});
		//  onPause
		jwplayer().onPause(function(event){
			jw_position = jwplayer().getPosition();
			jw_duration = jwplayer().getDuration();
			jw_pausestatement = 1;
			if (debug) {
				console.log("Video Paused. Played from " + jw_playfrom + " seconds, to " + jw_position + " seconds, full video duration: " + jw_duration + " seconds. PauseStatement = " + jw_pausestatement);
			}
		});
		//  end onPlay
		//  Send statement when someone pauses the video

		if (debug) {
			console.log( "Data to be transmitted: " + data );
		}

		// request =
		$.post( wpxapi_ajax_object.ajax_url, data, function( response ) {
			if (debug) {
				alert('Got this response from the server: ' + response );
				console.log( "Data transmitted to server: " + data );
				console.log( "Got this response from the server: " + response );
			}
			// die();
		});

		// if (debug) {
		// 	// Callback handler that will be called on success
		// 	request.done(function (response, textStatus, jqXHR){
		// 		// Log a message to the console
		// 		alert('Successfully called');
		// 		console.log("Success, it worked!");
		// 		console.log("Success. Data: " + data);
		// 	});

		// 	// Callback handler that will be called on failure
		// 	request.fail(function (jqXHR, textStatus, errorThrown){
		// 		// Log the error to the console
		// 		alert('Error: ', errorThrown);
		// 		console.error("The following error occurred: "+ textStatus, errorThrown);
		// 		console.log("Error. Data: " + data + jqXHR);
		// 	});

		// 	// Callback handler that will be called regardless if the request failed or succeeded
		// 	request.always(function () {
		// 		// Reenable the inputs
		// 		// $inputs.prop("disabled", false);
		// 	});
		// }
	});
});