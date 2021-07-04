jQuery(document).ready( function($) {
	debug = 1;

	$('a').on("click",function() {
		// Let's disable the inputs for the duration of the Ajax request.
		// Note: we disable elements AFTER the form data has been serialized.
		// Disabled form elements will not be serialized.
		// $inputs.prop("disabled", true);

		// if (typeof jwplayer().onPlay === 'function' && typeof jwplayer().onPause === 'function') {
			var jw_videourl, jw_videotitle, jw_playcount, jw_playfrom, jw_position, jw_duration, jw_pausestatement = 0;
			var wpxapi_click_url_requested =  $(this).attr('href');
			var wpxapi_click_referrer_location = document.location.href;
			// var jw_videourl = window.location.pathname;
			// var jw_videotitle = jw_videourl.substring(jw_videourl.lastIndexOf('/')+1, jw_videourl.lastIndexOf('.'));

			var data1 = {
				action: "xapiclicklog_action",
				wpxapi_click_log: "true",
				wpxapi_nonce: wpxapi_ajax_object.wpxapi_nonce,
				wpxapi_uid: wpxapi_ajax_object.wpxapi_uid,
				wpxapi_blogid: wpxapi_ajax_object.wpxapi_blogid,
				wpxapi_click_url_requested: wpxapi_click_url_requested,
				wpxapi_click_referrer_location: wpxapi_click_referrer_location,
			};

			if (debug) {
				console.log( "Data1 (xapiclicklog_action) to be transmitted: " + data1.toString() );
			};

			$.post( wpxapi_ajax_object.ajax_url, data1, function( response1 ) {
				if (debug) {
					// alert('Got this response from the server: ' + response.toString() );
					console.log( "Data1 (xapiclicklog_action) transmitted to Server: " + data1.toString() );
					console.log( "Got this Response1 from the Server: " + response1.toString() );
				};
				// die();
			});

			var data2 = {
				action: "xapivideointeraction_action",
				wpxapi_click_log: "true",
				wpxapi_nonce: wpxapi_ajax_object.wpxapi_nonce,
				wpxapi_uid: wpxapi_ajax_object.wpxapi_uid,
				wpxapi_blogid: wpxapi_ajax_object.wpxapi_blogid,
				wpxapi_click_url_requested: wpxapi_click_url_requested,
				wpxapi_click_referrer_location: wpxapi_click_referrer_location,
				jw_videourl: jw_videourl,
				jw_videotitle: jw_videotitle,
				jw_playcount: jw_playcount,
				jw_playfrom: jw_playfrom,
				jw_position: jw_position,
				jw_duration: jw_duration,
				jw_pausestatement: jw_pausestatement,

				// jw_videourl: wpxapi_ajax_object.jw_videourl,
				// jw_videotitle: wpxapi_ajax_object.jw_videotitle,
				// jw_playcount: wpxapi_ajax_object.jw_playcount,
				// jw_playfrom: wpxapi_ajax_object.jw_playfrom,
				// jw_position: wpxapi_ajax_object.jw_position,
				// jw_duration: wpxapi_ajax_object.jw_duration,
				// jw_pausestatement: wpxapi_ajax_object.jw_pausestatement,
			};

			// We're not going to sent a statement on PLAY for this example.  Instead, we're going to set the variable for what point within the video the user started playing a given period
			// This section shows the SEGMENT that the video played.  This is why we don't send the statement on the actual PLAY of the video, but when play stops.  This way we can gather both the start and stop points more easily
			// onPlay
			jwplayer().onPlay(function(event){
				jw_pausestatement = 0;
				jw_videourl = jwplayer().getPlaylistItem()['file'];
				jw_videotitle = jw_videourl.title;
				// jw_videotitle = jwplayer().getPlaylistItem().title;
				jw_playfrom = jwplayer().getPosition();
				jw_duration = jwplayer().getDuration();
				if (debug) {
					console.log( "PauseStatement = " + jw_pausestatement) ;
					console.log( "Video Played. Played Video " + jw_videotitle + " (" + jw_videourl + ") from timestamp: " + jw_playfrom + " seconds, full video duration: " + jw_duration + " seconds." );
				};
			});
			// end onPlay
			// onPause
			// Send statement when someone pauses the video
			jwplayer().onPause(function(event){
				jw_pausestatement = 1;
				jw_position = jwplayer().getPosition();
				jw_duration = jwplayer().getDuration();
				if (debug) {
					console.log( "PauseStatement = " + jw_pausestatement );
					console.log( "Video Paused. Played Video " + jw_videotitle + " (" + jw_videourl + ") from " + jw_playfrom + " seconds, to " + jw_position + " seconds, full video duration: " + jw_duration + " seconds. PauseStatement=" + jw_pausestatement );
					console.log( "Data2 (xapivideointeraction_action) to be transmitted: " + data2.toString() );
				};
				$.post( wpxapi_ajax_object.ajax_url, data2, function( response2 ) {
					if (debug) {
						// alert('Got this response from the server: ' + response.toString() );
						console.log( "PauseStatement=" + jw_pausestatement );
						console.log( "Data2 (xapivideointeraction_action) transmitted to Server: " + data2.toString() );
						console.log( "Got this Response2 from the Server: " + response2.toString() );
					};
					// die();
				});
			});
	});
});