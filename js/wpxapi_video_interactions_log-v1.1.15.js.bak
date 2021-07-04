jQuery(document).ready( function($) {
	var debug = 1;
	// var triggers_file = "/wp-content/plugins/wp-experience-api/includes/triggers.php";
	if (debug) {
		$(window).ajaxComplete(function () {console.log('Ajax Complete'); });
		$(window).ajaxError(function (data2, textStatus, jqXHR) {console.log('Ajax Error');
		console.log('data2: ' + data2);
		console.log('textStatus: ' + textStatus);
		console.log('jqXHR: ' + jqXHR); });
		$(window).ajaxSend(function () {console.log('Ajax Send'); });
		$(window).ajaxStart(function () {console.log('Ajax Start'); });
		$(window).ajaxStop(function () {console.log('Ajax Stop'); });
		$(window).ajaxSuccess(function () {console.log('Ajax Success'); });
	}

	var vid = document.getElementById("myVideo");
	vid.loadeddata = function() {
	  alert("Browser has loaded the current frame");
	// };

	// $('a').ready( function() {
	// $('a').on("loadedmetadata",function() {
		var wpxapi_jw_videourl, wpxapi_jw_videotitle, wpxapi_jw_playcount, wpxapi_jw_playfrom, wpxapi_jw_position, wpxapi_jw_duration, wpxapi_jw_pausestatement = 0;
		var wpxapi_click_url_requested =  $(this).attr('href');
		var wpxapi_click_referrer_location = document.location.href;
		// var wpxapi_jw_videourl = window.location.pathname;
		// var wpxapi_jw_videotitle = wpxapi_jw_videourl.substring(jw_videourl.lastIndexOf('/')+1, wpxapi_jw_videourl.lastIndexOf('.'));

		if (typeof jwplayer().onPlay === 'function' && typeof jwplayer().onPause === 'function') {

			// Let's disable the inputs for the duration of the Ajax request.
			// Note: we disable elements AFTER the form data has been serialized.
			// Disabled form elements will not be serialized.
			// $inputs.prop("disabled", true);
			$("input").prop("disabled", true);

			var data2 = {
				action: "xapivideointeraction_action",
				wpxapi_click_log: "true",
				wpxapi_nonce: wpxapi_ajax_object2.wpxapi_nonce,
				wpxapi_uid: wpxapi_ajax_object2.wpxapi_uid,
				wpxapi_blogid: wpxapi_ajax_object2.wpxapi_blogid,
				wpxapi_click_url_requested: wpxapi_click_url_requested,
				wpxapi_click_referrer_location: wpxapi_click_referrer_location,
				wpxapi_jw_videourl: wpxapi_ajax_object2.wpxapi_jw_videourl,
				wpxapi_jw_videotitle: wpxapi_ajax_object2.wpxapi_jw_videotitle,
				wpxapi_jw_playcount: wpxapi_ajax_object2.wpxapi_jw_playcount,
				wpxapi_jw_playfrom: wpxapi_ajax_object2.wpxapi_jw_playfrom,
				wpxapi_jw_position: wpxapi_ajax_object2.wpxapi_jw_position,
				wpxapi_jw_duration: wpxapi_ajax_object2.wpxapi_jw_duration,
				wpxapi_jw_pausestatement: wpxapi_ajax_object2.wpxapi_jw_pausestatement,
			};

			// We're not going to sent a statement on PLAY for this example.  Instead, we're going to set the variable for what point within the video the user started playing a given period
			// This section shows the SEGMENT that the video played.  This is why we don't send the statement on the actual PLAY of the video, but when play stops.  This way we can gather both the start and stop points more easily
			// onPlay
			jwplayer().onPlay(function(event){
				wpxapi_jw_pausestatement = 0;
				wpxapi_jw_videourl = jwplayer().getPlaylistItem()['file'];
				wpxapi_jw_videotitle = wpxapi_jw_videourl.title;
				// wpxapi_jw_videotitle = jwplayer().getPlaylistItem().title;
				wpxapi_jw_playfrom = jwplayer().getPosition();
				wpxapi_jw_duration = jwplayer().getDuration();
				if (debug) {
					console.log( "PauseStatement = " + wpxapi_jw_pausestatement) ;
					console.log( "Video Played. Played Video " + wpxapi_jw_videotitle + " (" + wpxapi_jw_videourl + ") from timestamp: " + wpxapi_jw_playfrom + " seconds, full video duration: " + wpxapi_jw_duration + " seconds." );
				};
			});
			// end onPlay
			// onPause
			jwplayer().onPause(function(event){
				wpxapi_jw_pausestatement = 1;
				wpxapi_jw_position = jwplayer().getPosition();
				wpxapi_jw_duration = jwplayer().getDuration();
				if (debug) {
					console.log( "PauseStatement=" + wpxapi_jw_pausestatement );
					console.log( "Video Paused. Played Video " + wpxapi_jw_videotitle + " (" + wpxapi_jw_videourl + ") from " + wpxapi_jw_playfrom + " seconds, to " + wpxapi_jw_position + " seconds, full video duration: " + wpxapi_jw_duration + " seconds. PauseStatement=" + wpxapi_jw_pausestatement );
					console.log( "Data2 (xapivideointeraction_action) to be transmitted: " + data2.toString() );
				};
				// end onPause

				// Send statement when someone pauses the video
				// Abort any pending request
				var request;
				if (request) { request.abort(); }
				request = $.post( wpxapi_ajax_object2.ajax_url, data2, function( response2 ) {
					if (debug) {
						// alert('Got this response from the server: ' + response2.toString() );
						console.log( "PauseStatement=" + wpxapi_jw_pausestatement );
						console.log( "Data2 (xapivideointeraction_action) transmitted to Server: " + data2.toString() );
						console.log( "Got this Response2 from the Server: " + response2.toString() );
					};
					// die();

					if (debug) {
						// Callback handler that will be called on success
						request.done(function (response2, textStatus, jqXHR){
							// Log a message to the console
							// alert('Successfully called');
							console.log("Success, it worked!");
							console.log("Success. Data2: " + data2);
						});

						// Callback handler that will be called on failure
						request.fail(function (jqXHR, textStatus, errorThrown){
							// Log the error to the console
							// alert('Error: ', errorThrown);
							console.error("The following error occurred: "+ textStatus, errorThrown);
							console.log("Error. Data2: " + data2 + jqXHR);
						});
					}

					// Callback handler that will be called regardless if the request failed or succeeded
					request.always(function () {
						// Reenable the inputs
						// $inputs.prop("disabled", false);
						$("input").prop("disabled", false);
					});
				});
			});
		};
	};
	// });
});