jQuery(document).ready( function($) {
	$('a').click(function() {
		var debug = 1;
		var wpxapi_click_url_requested =  $(this).attr('href');
		var wpxapi_click_referrer_location = document.location.href;

		var playcount, playfrom, position, duration, pausestatement = 0;
		var videourl = window.location.pathname;
		var filename = videourl.substring(videourl.lastIndexOf('/')+1, videourl.lastIndexOf('.'));

		// 	We're not going to sent a statement on PLAY for this example.  Instead, we're going to set the variable for what point within the video the user started playing a given period
		//	This section shows the SEGMENT that the video played.  This is why we don't send the statement on the actual PLAY of the video, but when play stops.  This way we can gather both the start and stop points more easily
		//  onPlay
		jwplayer().onPlay(function(event){
			playfrom = jwplayer().getPosition();
			if (debug) { console.log("Video Played") };
		});
		//  onPause
		jwplayer().onPause(function(event){
			position = jwplayer().getPosition();
			duration = jwplayer().getDuration();
			pausestatement = 1;
			if (debug) { console.log("Video Paused. Played from " + playfrom + " seconds, to " + position + " seconds, full duration: " + duration + " seconds. PauseStatement = " + pausestatement); }
		});
		//  end onPlay
		//  Send statement when someone pauses the video

		data = {
			action: "xapiclicklog_action",
			wpxapi_click_log: "true",
			wpxapi_nonce: wpxapi_ajax_object.wpxapi_nonce,
			wpxapi_uid: wpxapi_ajax_object.wpxapi_uid,
			wpxapi_blogid: wpxapi_ajax_object.wpxapi_blogid,
			wpxapi_click_url_requested: wpxapi_click_url_requested,
			wpxapi_click_referrer_location: wpxapi_click_referrer_location,
			playcount,
			playfrom,
			videourl,
			filename,
			position,
			duration,
			pausestatement
		};

/* 		var data = {
			action: "xapiclicklog_action",
			wpxapi_click_log: "true",
			wpxapi_nonce: wpxapi_ajax_object.wpxapi_nonce,
			wpxapi_uid: wpxapi_ajax_object.wpxapi_uid,
			wpxapi_blogid: wpxapi_ajax_object.wpxapi_blogid,
			wpxapi_click_url_requested: wpxapi_click_url_requested,
			wpxapi_click_referrer_location: wpxapi_click_referrer_location
		}; */

/* 		if (debug) { console.log( "Data to be transmitted: action: " + action + ", wpxapi_click_log: " + wpxapi_click_log + ", wpxapi_nonce: " + wpxapi_nonce + ", wpxapi_uid: " + wpxapi_uid + ", wpxapi_blogid: " + wpxapi_blogid + ", wpxapi_click_url_requested: " + wpxapi_click_url_requested + ", wpxapi_click_referrer_location: " + wpxapi_click_referrer_location + ", wpxapi_playcount: " + wpxapi_playcount + ", wpxapi_playfrom: " + wpxapi_playfrom + ", wpxapi_videourl: " + wpxapi_videourl + ", wpxapi_filename: " + wpxapi_filename + ", wpxapi_duration: " + wpxapi_duration + ", wpxapi_position: " + wpxapi_position + ", wpxapi_pausestatement: " + wpxapi_pausestatement ); } */

		if (debug) { console.log( "Data to be transmitted: playcount: " + playcount + ", playfrom: " + playfrom + ", videourl: " + videourl + ", filename: " + filename + ", duration: " + duration + ", position: " + position + ", pausestatement: " + pausestatement ); }

		$.post( wpxapi_ajax_object.ajax_url, data, function( response ) {
			// alert( 'Got this response from the server: ' + response );
			if (debug) {
				console.log( "Data transmitted to server." );
				console.log( "Got this response from the server: " + response );
			}
			// die();
		});
	});
});

/* 	$.ajax({
		type:"POST",
		url: "/includes/triggers.php",
		data: {var1: 'playcount', var2: 'playfrom', var3: 'videourl', var4: 'filename', var5: 'duration', var6: 'position'},
		success: function(){
			if ( $debug ) {
				//do stuff after the AJAX Calls successfully completes
				alert("AJAX Calls Submitted");
				console.log("AJAX Calls Submitted");
			}
		}
	});
}); */