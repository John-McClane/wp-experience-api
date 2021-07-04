<?php
/**
 * This is where the magic happens!  Just need to call ExperienceAPI::register()
 */

$debug = 1;
if ($debug) { error_reporting(E_ALL); }

function wpxapi_enqueue_script( $hook ) {
	// if( is_user_logged_in() ) {
		$wpxapi_uid = wp_get_current_user();
		$wpxapi_uid = base64_encode( $wpxapi_uid->ID );
		$wpxapi_blogid = get_current_blog_id();
		$wpxapi_blogid = base64_encode( $wpxapi_blogid );
		wp_enqueue_script( 'wpxapi_ajax_script', plugins_url( '/js/wpxapi_link_click_log.js', dirname(__FILE__) ), array('jquery') );
		// wp_localize_script( 'wpxapi_ajax_script', 'wpxapi_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ),  'wpxapi_uid' => $wpxapi_uid, 'wpxapi_blogid' => $wpxapi_blogid ) );
		$array = array( 'ajax_url' => admin_url( 'admin-ajax.php' ),  'wpxapi_uid' => $wpxapi_uid, 'wpxapi_blogid' => $wpxapi_blogid );
		wp_localize_script( 'wpxapi_ajax_script', 'wpxapi_ajax_object', $array );
	// }
}

add_action( 'wp_enqueue_scripts', 'wpxapi_enqueue_script' );
add_action( 'wp_ajax_xapiclicklog_action', 'xapiclicklog_action' );
add_action( 'wp_ajax_nopriv_xapiclicklog_action', 'xapiclicklog_action' );

function xapiclicklog_action() {
	do_action( 'xapiclicklog_action_fire' );
}

/**
 * Video Interactions tracking
 * WP_Experience_API::register( 'wpxapi_linkclick_track_log', array(
 *	    'hooks' => array( 'xapiclicklog_action_fire' ),
 *		'process' => function( $hook, $args ) {
 **/

WP_Experience_API::register( 'wpxapi_video_interactions_log', array(
	'hooks' => array( 'xapivideointeraction_action_fire' ),
	'process' => function( $hook, $args ) {

	global $debug;
	if ( $debug ) { error_log("/includes/triggers.php line 36 hook and args"); error_log(print_r($hook,true)); error_log(print_r($args,true)); }

	$wpxapi_click_url_requested = urldecode( $_POST['wpxapi_click_url_requested'] );
	$wpxapi_click_url_requested = sanitize_text_field( $wpxapi_click_url_requested );

	$wpxapi_click_referrer_location = urldecode( $_POST['wpxapi_click_referrer_location'] );
	$wpxapi_click_referrer_location = sanitize_text_field( $wpxapi_click_referrer_location );

	$wpxapi_uid = base64_decode( $_POST['wpxapi_uid'] );
	$wpxapi_uid = intval( $wpxapi_uid );
	$wpxapi_uid = isset($wpxapi_uid) ? $wpxapi_uid : '';

	$wpxapi_blogid = base64_decode( $_POST['wpxapi_blogid'] );
	$wpxapi_blogid = intval( $wpxapi_blogid );
	$wpxapi_blogid = isset($wpxapi_blogid) ? $wpxapi_blogid : '';

	$request_site_url = get_site_url( $wpxapi_blogid );

	if ( $debug ) { error_log("/includes/triggers.php line 58 wpxapi_blogid, request_site_url, wpxapi_click_url_requested"); error_log(print_r($wpxapi_blogid,true)); error_log(print_r($request_site_url,true)); error_log(print_r($wpxapi_click_url_requested,true)); }
	if (strpos($wpxapi_click_url_requested, '#') === 0) {
		$wpxapi_click_url_requested = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'selflink').$wpxapi_click_url_requested;
		$wpxapi_click_url_requested = urldecode( $wpxapi_click_url_requested );
		$wpxapi_click_url_requested = sanitize_text_field( $wpxapi_click_url_requested );
		if ( $debug ) { error_log("/includes/triggers.php line 63 wpxapi_blogid, request_site_url, wpxapi_click_url_requested"); error_log(print_r($wpxapi_blogid,true)); error_log(print_r($request_site_url,true)); error_log(print_r($wpxapi_click_url_requested,true)); }
	}

	$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	if ( $debug ) { error_log("/includes/triggers.php line 67 referrer"); error_log(print_r($referrer,true)); }
	$referrer = urldecode( $referrer );
	$referrer = sanitize_text_field( $referrer );
	if ( $debug ) { error_log("/includes/triggers.php line 69 referrer"); error_log(print_r($referrer,true)); }

	$jw_videourl = urldecode( $_POST['jw_videourl'] );
	$jw_videourl = sanitize_text_field( $jw_videourl );
	$jw_videourl = isset($jw_videourl) ? $jw_videourl : '';

	$jw_filename = urldecode( $_POST['jw_filename'] );
	$jw_filename = sanitize_text_field( $jw_filename );
	$jw_filename = isset($jw_filename) ? $jw_filename : '';

	$jw_playcount = ( $_POST['jw_playcount'] );
	$jw_playcount = intval( $jw_playcount );
	$jw_playcount = isset($jw_playcount) ? $jw_playcount : '';

	$jw_playfrom = ( $_POST['jw_playfrom'] );
	$jw_playfrom = intval( $jw_playfrom );
	$jw_playfrom = isset($jw_playfrom) ? $jw_playfrom : '';

	$jw_position = ( $_POST['jw_position'] );
	$jw_position = intval( $jw_position );
	$jw_position = isset($jw_position) ? $jw_position : '';

	$jw_pausestatement = ( $_POST['jw_pausestatement'] );
	$jw_pausestatement = intval( $jw_pausestatement );
	$jw_pausestatement = isset($jw_pausestatement) ? $jw_pausestatement : '';

	// if ( $wpxapi_pausestatement == 1 ) {
		$statement = array(
			'verb' => array(
				'id' => 'http://adlnet.gov/expapi/verbs/Play',
				'display' => array( 'en-US' => 'Video Played' ),
			),

			'object' => array(
				'id' => $jw_filename,
				'definition' => array(
					'name' => array(
						'en-US' => '$jw_videourl + $jw_filename',
					),
					'description' => array(
						'en-US' => 'Video Paused. Played $jw_videourl $jw_filename from $jw_playfrom seconds, to $jw_position seconds, played for $jw_duration seconds',
					),
					'type' => 'http://adlnet.gov/expapi/activities/video',
					)
				),
			'result' => array(
				'extensions' => array(
					'http://adlnet.gov/expapi/activities/link'=> $jw_videourl,
					'http://adlnet.gov/expapi/activities/file'=> $jw_filename,
					'http://adlnet.gov/expapi/period_start'=> $jw_playfrom,
					'http://adlnet.gov/expapi/period_end'=> $jw_position,
					'http://adlnet.gov/expapi/period_duration'=> $jw_duration,
					),
				),
			'context_raw' => array(
				'extensions' => array(
					'http://id.tincanapi.com/extension/browser-info' => array( 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ),
					'http://id.tincanapi.com/extension/referrer' => $referrer,
					),
				'platform' => defined( 'CTLT_PLATFORM' ) ? constant( 'CTLT_PLATFORM' ) : 'Unknown'
				),
			'timestamp_raw' => date( 'c' )
			);
		// }

	/** $user_obj = get_user_by( 'ID', $wpxapi_uid );
	 * $user = $user_obj->ID; **/
	$options = get_option( 'wpxapi_settings' );
	$user = get_current_user_id();
	if ( empty( $user ) ) {
		if ( 1 == $options['wpxapi_guest'] ) {
			$user = array(
				'objectType' => 'Agent',
				'name' => 'Guest.' . $_SERVER['REMOTE_ADDR'],
				'mbox' => 'mailto:guest.' . $_SERVER['REMOTE_ADDR'] . '@ntua-guest.com',
				/** 'name' => 'Guest ' . $_SERVER['REMOTE_ADDR'], **/
				/** 'mbox' => 'mailto:guest-' . $_SERVER['REMOTE_ADDR'] . '@ntua-guest.com', **/
				/** 'mbox' => 'mailto:guest-' . $_SERVER['REMOTE_ADDR'] . '@' . preg_replace( '/http(s)?:\/\//', '', get_bloginfo( 'url' ) ), **/
			);
			  $statement = array_merge( $statement, array( 'actor_raw' => $user ) );
		 } else {
			return false;
		 }
	  } else {
		  $statement = array_merge( $statement, array( 'user' => $user ) );
	  }
	if ( $debug ) { error_log("/includes/triggers.php line 108 statement"); error_log(print_r($statement,true)); }
	return $statement;
  }
));

/**
 * Click Link tracking
 **/
WP_Experience_API::register( 'wpxapi_linkclick_track_log', array(
	    'hooks' => array( 'xapiclicklog_action_fire' ),
		'process' => function( $hook, $args ) {

		global $debug;
		if ( $debug ) { error_log("/includes/triggers.php line 36 hook and args"); error_log(print_r($hook,true)); error_log(print_r($args,true)); }

		$wpxapi_click_url_requested = urldecode( $_POST['wpxapi_click_url_requested'] );
		$wpxapi_click_url_requested = sanitize_text_field( $wpxapi_click_url_requested );

		$wpxapi_click_referrer_location = urldecode( $_POST['wpxapi_click_referrer_location'] );
		$wpxapi_click_referrer_location = sanitize_text_field( $wpxapi_click_referrer_location );

		$wpxapi_uid = base64_decode( $_POST['wpxapi_uid'] );
		$wpxapi_uid = intval( $wpxapi_uid );
		$wpxapi_uid = isset($wpxapi_uid) ? $wpxapi_uid : '';

		$wpxapi_blogid = base64_decode( $_POST['wpxapi_blogid'] );
		$wpxapi_blogid = intval( $wpxapi_blogid );
		$wpxapi_blogid = isset($wpxapi_blogid) ? $wpxapi_blogid : '';

		$request_site_url = get_site_url( $wpxapi_blogid );

		if ( $debug ) { error_log("/includes/triggers.php line 58 wpxapi_blogid, request_site_url, wpxapi_click_url_requested"); error_log(print_r($wpxapi_blogid,true)); error_log(print_r($request_site_url,true)); error_log(print_r($wpxapi_click_url_requested,true)); }
		if (strpos($wpxapi_click_url_requested, '#') === 0) {
			$wpxapi_click_url_requested = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'selflink').$wpxapi_click_url_requested;
			$wpxapi_click_url_requested = urldecode( $wpxapi_click_url_requested );
			$wpxapi_click_url_requested = sanitize_text_field( $wpxapi_click_url_requested );
			if ( $debug ) { error_log("/includes/triggers.php line 63 wpxapi_blogid, request_site_url, wpxapi_click_url_requested"); error_log(print_r($wpxapi_blogid,true)); error_log(print_r($request_site_url,true)); error_log(print_r($wpxapi_click_url_requested,true)); }
		}

		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		if ( $debug ) { error_log("/includes/triggers.php line 67 referrer"); error_log(print_r($referrer,true)); }
		$referrer = urldecode( $referrer );
		$referrer = sanitize_text_field( $referrer );
		if ( $debug ) { error_log("/includes/triggers.php line 69 referrer"); error_log(print_r($referrer,true)); }

		$statement = null;

		$statement = array(
			'verb' => array(
				'id' => 'http://adlnet.gov/expapi/verbs/interacted',
				'display' => array( 'en-US' => 'interacted' ),
			),

			'object' => array(
				'id' => $wpxapi_click_url_requested,
				'definition' => array(
					'name' => array(
						'en-US' => $wpxapi_click_url_requested,
					),
					'description' => array(
						'en-US' => 'interacted',
					),
					'type' => 'http://adlnet.gov/expapi/activities/link',
					)
				),
			'context_raw' => array(
				'extensions' => array(
					'http://id.tincanapi.com/extension/browser-info' => array( 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ),
					'http://id.tincanapi.com/extension/referrer' => $referrer,
					),
				'platform' => defined( 'CTLT_PLATFORM' ) ? constant( 'CTLT_PLATFORM' ) : 'Unknown'
				),
			'timestamp_raw' => date( 'c' )
		);

		/** $user_obj = get_user_by( 'ID', $wpxapi_uid );
		 * $user = $user_obj->ID; **/
		$options = get_option( 'wpxapi_settings' );
		$user = get_current_user_id();
		if ( empty( $user ) ) {
			if ( 1 == $options['wpxapi_guest'] ) {
				$user = array(
					'objectType' => 'Agent',
					'name' => 'Guest.' . $_SERVER['REMOTE_ADDR'],
					'mbox' => 'mailto:guest.' . $_SERVER['REMOTE_ADDR'] . '@ntua-guest.com',
					/** 'name' => 'Guest ' . $_SERVER['REMOTE_ADDR'], **/
					/** 'mbox' => 'mailto:guest-' . $_SERVER['REMOTE_ADDR'] . '@ntua-guest.com', **/
					/** 'mbox' => 'mailto:guest-' . $_SERVER['REMOTE_ADDR'] . '@' . preg_replace( '/http(s)?:\/\//', '', get_bloginfo( 'url' ) ), **/
				);
				  $statement = array_merge( $statement, array( 'actor_raw' => $user ) );
			 } else {
				return false;
			 }
		  } else {
			  $statement = array_merge( $statement, array( 'user' => $user ) );
		  }
		if ( $debug ) { error_log("/includes/triggers.php line 108 statement"); error_log(print_r($statement,true)); }
		return $statement;
	  }
));

/**
 * This trigger is for page views of various kinds
 */
WP_Experience_API::register( 'page_views', array(
	'hooks' => array( 'shutdown' ), //yes, kinda broad, but if singular, should be ok. Was 'wp'
	'process' => function( $hook, $args ) {

		global $debug;
		if ( $debug ) { error_log("/includes/triggers.php line 121 hook and args"); error_log(print_r($hook,true)); error_log(print_r($args,true)); }

		// By default, we don't track AJAX requests within this hook
		$process_ajax_loads = apply_filters( 'wpxapi_ajax_page_views', false, $hook, $args );

		if ( ! $process_ajax_loads && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		// Should we track Admin requests (Backend/WordPress Dashboard and Plugin admin requests) ?
		$process_admin_requests = apply_filters( 'wpxapi_admin_page_views', false, $hook, $args );

		//only track front end for now.
		if ( is_admin() && ! $process_admin_requests ) {
			return false;
		}

		global $post;

		$options = get_option( 'wpxapi_settings' );
		if ( 3 == $options['wpxapi_pages'] ) {
			return false;
		}
		if ( 2 == $options['wpxapi_pages'] ) {
			if ( ! is_singular() ) {
				return false;
			}
		}

		//need to make sure that description is working.
		$description = get_bloginfo( 'description' );
		if ( empty( $description ) ) {
			$description = 'N/A';
		}

		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$referrer = urldecode( $referrer );
		$referrer = sanitize_text_field( $referrer );

		$postid    = isset( $post->ID ) ? $post->ID : 0;
		$statement = null;
		$statement = array(
			'verb' => array(
				'id' => 'http://id.tincanapi.com/verb/viewed',
				'display' => array( 'en-US' => 'viewed' ),
			),
			'object' => array(
				'id' => WP_Experience_API::current_page_url(),
				'definition' => array(
					'name' => array(
						'en-US' => get_the_title( absint( $postid ) ) . ' | ' . get_bloginfo( 'name' ),
					),
					'description' => array(
						'en-US' => $description,
					),
					'type' => 'http://activitystrea.ms/schema/1.0/page',
				)
			),
			'context_raw' => array(
				'extensions' => array(
					'http://id.tincanapi.com/extension/browser-info' => array( 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ),
					'http://id.tincanapi.com/extension/referrer' => $referrer,
				),
				'platform' => defined( 'CTLT_PLATFORM' ) ? constant( 'CTLT_PLATFORM' ) : 'Unknown'
			),
			'timestamp_raw' => date( 'c' )
		);

		$user = get_current_user_id();
		if ( empty( $user ) ) {
			if ( 1 == $options['wpxapi_guest'] ) {
				$user = array(
					'objectType' => 'Agent',
					'name' => 'Guest.' . $_SERVER['REMOTE_ADDR'],
					'mbox' => 'mailto:guest.' . $_SERVER['REMOTE_ADDR'] . '@ntua-guest.com',
					/** 'name' => 'Guest ' . $_SERVER['REMOTE_ADDR'], **/
					/** 'mbox' => 'mailto:guest-' . $_SERVER['REMOTE_ADDR'] . '@ntua-guest.com', **/
					/** 'mbox' => 'mailto:guest-' . $_SERVER['REMOTE_ADDR'] . '@' . preg_replace( '/http(s)?:\/\//', '', get_bloginfo( 'url' ) ), **/
				);
				$statement = array_merge( $statement, array( 'actor_raw' => $user ) );
			} else {
				return false;
			}
		} else {
			$statement = array_merge( $statement, array( 'user' => $user ) );
		}

		return $statement;
	}
));


/**
 * This trigger is to track some specific post transitions (going to published, trashed, etc)
 */
WP_Experience_API::register( 'transition_post', array(
	'hooks' => array( 'transition_post_status' ),
	'num_args' => array( 'transition_post_status' => 3 ),
	'process' => function( $hook, $args ) {  //args in this case should be ($new_status, $old_status, $post)
		global $post;

		$current_post = null;
		$switched_post = false; //so we can keep track if we switched posts
		//put verb here cause we have to account for multiple possible verbs (trashed/authored for now)
		$verb = array( 'id' => 'http://activitystrea.ms/schema/1.0/author', 'display' => array( 'en-US' => 'authored' ) );

		//switch to post passed in via args vs global one as it's old and we are updating posts
		if ( isset( $args[2] ) && ! empty( $args[2] ) && $args[2] instanceof WP_Post ) {
			$current_post =  $args[2];
		} else {
			$current_post = $post;
		}

		//check site level settings for what to watch 3: nothing, 2: only to published, 1: to published and deleted
		$options = get_option( 'wpxapi_settings' );
		if ( 5 == $options['wpxapi_publish'] ) {
			return false;
		}

		//currently, it defaults to working with only public post_types
		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ! empty( $post_type_obj ) && property_exists( $post_type_obj, 'public' ) && $post_type_obj->public != 1 ) {
			return false;
		}

		if ( 4 == $options['wpxapi_publish'] ) {
			if (
				( isset( $args[0] ) && 'publish' == $args[0] ) && ( isset( $args[1] ) && 'publish' != $args[1] ) && //if going from anything (excluding publish) to publish state
				( isset( $args[2] ) && $args[2] instanceof WP_Post ) //if post exists
			) {
				//do nothing as this should ONLY take going to published to send to xAPI statement
			} else {
				return false;
			}
		}

		if ( 3 == $options['wpxapi_publish'] ) {
			if (
				( ( ( isset( $args[0] ) && 'publish' == $args[0] ) && ( isset( $args[1] ) && 'publish' != $args[1] ) ) || //if going from anything (excluding publish) to publish state
				( ( isset( $args[0] ) && 'trash' == $args[0] ) && ( isset( $args[1] ) && 'trash' != $args[1] ) ) ) && //if going from anything (excluding rash) to trash state
				( isset( $args[2] ) && $args[2] instanceof WP_Post ) //if post exists
			) {
				if ( 'trash' == $args[0] ) {
					$verb = array( 'id' => 'http://activitystrea.ms/schema/1.0/delete', 'display' => array( 'en-US' => 'deleted' ) );
				}
			} else {
				return false;
			}
		}

		if ( 2 == $options['wpxapi_publish'] ) {
			if (
				( ( isset( $args[0] ) && 'publish' == $args[0] ) || //include state changes from anything to published state, including published to published
				( ( isset( $args[0] ) && 'trash' == $args[0] ) && ( isset( $args[1] ) && 'trash' != $args[1] ) ) ) && //if going from anything (excluding rash) to trash state
				( isset( $args[2] ) && $args[2] instanceof WP_Post ) //if post exists
			) {
				if ( 'trash' == $args[0] ) {
					$verb = array( 'id' => 'http://activitystrea.ms/schema/1.0/delete', 'display' => array( 'en-US' => 'deleted' ) );
				} else if ( 'publish' == $args[0] && 'publish' == $args[1] ) {
					$verb = array( 'id' => 'http://activitystrea.ms/schema/1.0/update', 'display' => array( 'en-US' => 'updated' ) );
				}
			} else {
				return false;
			}
		}

		//capture almost anything
		if ( 1 == $options['wpxapi_publish'] ) {
			if (
				( isset( $args[2] ) && $args[2] instanceof WP_Post ) //if post exists
			) {
				if ( 'trash' == $args[0] ) {
					//if going to trash (aka new state is trash
					$verb = array( 'id' => 'http://activitystrea.ms/schema/1.0/delete', 'display' => array( 'en-US' => 'deleted' ) );
				} else if ( 'publish' == $args[0] && 'publish' == $args[1] ) {
					//if going from published to published
					$verb = array( 'id' => 'http://activitystrea.ms/schema/1.0/update', 'display' => array( 'en-US' => 'updated' ) );
				} else if ( 'publish' == $args[1] && 'publish' != $args[0] ) {
					//if going from published to something OTHER than published (aka retracted)
					$verb = array( 'id' => 'http://activitystrea.ms/schema/1.0/retract', 'display' => array( 'en-US' => 'retracted' ) );
				} else if ( 'publish' == $args[0] && 'publish' != $args[1] ) {
					//do nothing as the $verb variable is already set and initialized to authored.
				} else {
					//we matched everything we cared about so we just return false for the rest
					return false;
				}
			} else {
				return false;
			}
		}

		$statement = null;
		$statement = array(
			'user' => get_current_user_id(),
			'verb' => array(
				'id' => $verb['id'],
				'display' => $verb['display'],
			),
			'object' => array(
				'id' => get_permalink( $current_post->ID ),
				'definition' => array(
					'name' => array(
						'en-US' => (string) $current_post->post_title . ' | ' . get_bloginfo( 'name' ),
					),
					'type' => 'http://activitystrea.ms/schema/1.0/page',
				)
			),
			'context_raw' => array(
				'extensions' => array(
					'http://id.tincanapi.com/extension/browser-info' => array( 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ),
					'http://id.tincanapi.com/extension/referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
				),
				'platform' => defined( 'CTLT_PLATFORM' ) ? constant( 'CTLT_PLATFORM' ) : 'Unknown'
			),
			'timestamp_raw' => date( 'c' )
		);

		//now get description and insert if there is something
		$description = '';
		if ( ! empty( $current_post->post_excerpt ) ) {
			$description = $current_post->post_excerpt;
		} else if ( ! empty( $current_post->post_content ) ) {
			$description = $current_post->post_content;
		}
		if ( ! empty( $description ) ) {
			$statement['object']['definition']['description'] = array( 'en-US' => $description );
		}
		$result = $current_post->post_content;
		if ( ! empty( $result ) ) {
			$statement['result_raw']['response']= $result;
		}

		return $statement;
	}
));