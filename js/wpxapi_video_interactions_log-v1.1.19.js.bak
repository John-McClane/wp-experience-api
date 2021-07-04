jQuery(document).ready( function($) {
	var debug = 2;
	if (debug > 1) {
		$(window).ajaxComplete(function () {console.log("=> Ajax Complete."); });
		$(window).ajaxError(function (data2, textStatus, jqXHR) {console.log("Info. Ajax Error.");
		console.log("Data2: " + data2 + ".");
		console.log("TextStatus: " + textStatus + ".");
		console.log("jqXHR: " + jqXHR + "."); });
		$(window).ajaxSend(function () {console.log("=> Ajax Send."); });
		$(window).ajaxStart(function () {console.log("Info. Ajax Start."); });
		$(window).ajaxStop(function () {console.log("Info. Ajax Stop."); });
		$(window).ajaxSuccess(function () {console.log("Info. Ajax Success."); });
		$("a").on({
			"click": function() {
				// $( this ).toggleClass( "active" );
				console.log("=> On Mouse Click.");
			},
			"mouseenter": function() {
				// $( this ).addClass( "inside" );
				console.log("=> On Mouse Enter.");
			},
			"mouseleave": function() {
				// $( this ).removeClass( "inside" );
				console.log("=> On Mouse Leave.");
			}
		});
	}

	var vid = document.getElementById("myVideo");
	if (vid) {
		vid.loadeddata = function() {
			if (debug > 0){
				console.log("Info. Browser has loaded the current frame.");
			}
		};
	};

	// $('a').on("click",function() {	// The only one that works reliably.

	if (typeof jwplayer().onPlay === 'function' && typeof jwplayer().onPause === 'function') {
		if (debug > 0) {
			console.log("Info. Detected JWPlayer frame with =>   if (typeof jwplayer().onPlay === 'function' && typeof jwplayer().onPause === 'function').");
		}

		// Prevent default posting of form - put here to work in case of errors
		// event.preventDefault();

		// Abort any pending request
		var request;
		if (request) { request.abort(); }

		var wpxapi_jw_videourl, wpxapi_jw_videotitle, wpxapi_jw_playcount, wpxapi_jw_playfrom, wpxapi_jw_position, wpxapi_jw_duration, wpxapi_jw_pausestatement = 0;
		var wpxapi_click_url_requested =  $(this).attr('href');
		var wpxapi_click_referrer_location = document.location.href;
		// var wpxapi_jw_videourl = window.location.pathname;
		// var wpxapi_jw_videotitle = wpxapi_jw_videourl.substring(jw_videourl.lastIndexOf('/')+1, wpxapi_jw_videourl.lastIndexOf('.'));

		jwplayer().onBeforePlay(function(){
			if (debug > 0) {
				console.log("Info. Detected JWPlayer initialization/loading with =>   jwplayer().onBeforePlay(function().");
			};

			// We're not going to sent a statement on PLAY for this example.  Instead, we're going to set the variable for what point within the video the user started playing a given period
			// This section shows the SEGMENT that the video played.  This is why we don't send the statement on the actual PLAY of the video, but when play stops.  This way we can gather both the start and stop points more easily
			// onPlay
			jwplayer().onPlay(function(event){
				wpxapi_jw_pausestatement = 0;
				wpxapi_jw_videourl = jwplayer().getPlaylistItem()['file'];
				wpxapi_jw_videotitle = wpxapi_jw_videourl.title;
				if (debug > 1) {
					console.log("document.title: " + document.title);
					console.log("wpxapi_jw_videotitle: " + wpxapi_jw_videotitle);
				};
				str = wpxapi_jw_videotitle;
				if (typeof str == 'undefined' || !str || str.length === 0 || str === "" || !/[^\s]/.test(str) || /^\s*$/.test(str) || str.replace(/\s/g,"") === "") { wpxapi_jw_videotitle = document.title; };

				if (debug > 1) {
					console.log("document.title: " + document.title);
					console.log("wpxapi_jw_videotitle: " + wpxapi_jw_videotitle);
				};
				// wpxapi_jw_videotitle = jwplayer().getPlaylistItem().title;
				wpxapi_jw_playfrom = jwplayer().getPosition();
				wpxapi_jw_duration = jwplayer().getDuration();
				if (debug > 0) {
					console.log("=> Video Played. Played Video titled: " + wpxapi_jw_videotitle + " (" + wpxapi_jw_videourl + ") from timestamp: " + wpxapi_jw_playfrom + " seconds. Full video duration: " + wpxapi_jw_duration + " seconds. PauseStatement: " + wpxapi_jw_pausestatement + ".");
				};
			// end onPlay
			});
			// onPause
		});
		jwplayer().onPause(function(event){
			wpxapi_jw_pausestatement = 1;
			wpxapi_jw_position = jwplayer().getPosition();
			wpxapi_jw_duration = jwplayer().getDuration();
			if (debug > 0) {
				console.log("=> Video Paused. Played Video titled: " + wpxapi_jw_videotitle + " (" + wpxapi_jw_videourl + ") from " + wpxapi_jw_playfrom + " seconds to " + wpxapi_jw_position + " seconds. Full video duration: " + wpxapi_jw_duration + " seconds. PauseStatement: " + wpxapi_jw_pausestatement + ".");
			};
			// end onPause

			// Let's disable the inputs for the duration of the Ajax request.
			// Note: we disable elements AFTER the form data has been serialized.
			// Disabled form elements will not be serialized.
			$("input").prop("disabled", true);

			// setup some local variables
			// var $form = $(data2);
			// Serialize the data in the form
			// var serializedData = $form.serialize();

			var data2 = {
				action: "xapivideointeraction_action",
				wpxapi_click_log: "true",
				wpxapi_nonce: wpxapi_ajax_object2.wpxapi_nonce,
				wpxapi_uid: wpxapi_ajax_object2.wpxapi_uid,
				wpxapi_blogid: wpxapi_ajax_object2.wpxapi_blogid,
				wpxapi_click_url_requested: wpxapi_click_url_requested,
				wpxapi_click_referrer_location: wpxapi_click_referrer_location,
				wpxapi_jw_videourl: wpxapi_jw_videourl,
				wpxapi_jw_videotitle: wpxapi_jw_videotitle,
				wpxapi_jw_playcount: wpxapi_jw_playcount,
				wpxapi_jw_playfrom: wpxapi_jw_playfrom,
				wpxapi_jw_position: wpxapi_jw_position,
				wpxapi_jw_duration: wpxapi_jw_duration,
				wpxapi_jw_pausestatement: wpxapi_jw_pausestatement,
			};

			if (wpxapi_jw_pausestatement == 1) {
				request = $.post( wpxapi_ajax_object2.ajax_url, data2, function( response2 ) {
					if (debug > 0) {
						// alert('Got this response from the server: ' + response2.toString() );
						console.log("Info. PauseStatement: " + wpxapi_jw_pausestatement + ". Data2 (xapivideointeraction_action) to be transmitted: " + data2.toString() + ".");
						console.log("=> Data2 (xapivideointeraction_action) transmitted to Server: " + data2.toString() + ".");
						console.log("=> Got this Response2 from the Server: " + response2.toString() + ".");
					};
				// die();
				// wpxapi_jw_pausestatement = 0;

					if (debug > 0) {
						// Callback handler that will be called on success
						request.done(function (response2, textStatus, jqXHR){
							// Log a message to the console
							// alert('Successfully called');
							console.log("Info. Success, it worked!");
							console.log("Info. Success. Data2: " + data2 + ".");
						});

						// Callback handler that will be called on failure
						request.fail(function (jqXHR, textStatus, errorThrown){
							// Log the error to the console
							alert('Error: ', errorThrown);
							console.error("Info. The following error occurred: "+ textStatus, errorThrown + ".");
							console.log("Info. Error. Data2: " + data2 + jqXHR + ".");
						});
					}

					// Callback handler that will be called regardless if the request failed or succeeded
					request.always(function () {
						// Reenable the inputs
						// $inputs.prop("disabled", false);
						$("input").prop("disabled", false);
					});
				});
			};
		});
	};
});