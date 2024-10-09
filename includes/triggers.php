<?php
/**
 * This is where the magic happens!  Just need to call ExperienceAPI::register()
 */

$debug = 2;
if ($debug > 0) { error_reporting(E_ALL); }

function wpxapi_enqueue_script( $hook ) {
	// if( is_user_logged_in() ) {
		$wpxapi_uid = wp_get_current_user();
		$wpxapi_uid = base64_encode( $wpxapi_uid->ID );
		$wpxapi_blogid = get_current_blog_id();
		$wpxapi_blogid = base64_encode( $wpxapi_blogid );
		$wpxapi_jw_videourl; $wpxapi_jw_videotitle; $wpxapi_jw_playcount; $wpxapi_jw_playfrom; $wpxapi_jw_position; $wpxapi_jw_duration; $wpxapi_jw_pausestatement;

		wp_enqueue_script( 'wpxapi_ajax_script1', plugins_url( '/js/wpxapi_link_click_log.js', dirname(__FILE__) ), array('jquery') );
		$array1 = array( 'ajax_url' => admin_url( 'admin-ajax.php' ),  'wpxapi_uid' => $wpxapi_uid, 'wpxapi_blogid' => $wpxapi_blogid );
		wp_localize_script( 'wpxapi_ajax_script1', 'wpxapi_ajax_object1', $array1 );

		wp_enqueue_script( 'wpxapi_ajax_script2', plugins_url( '/js/wpxapi_video_interactions_log.js', dirname(__FILE__) ), array('jquery') );
		$array2 = array( 'ajax_url' => admin_url( 'admin-ajax.php' ),  'wpxapi_uid' => $wpxapi_uid, 'wpxapi_blogid' => $wpxapi_blogid );
		wp_localize_script( 'wpxapi_ajax_script2', 'wpxapi_ajax_object2', $array2 );
	// }
}

add_action( 'wp_enqueue_scripts', 'wpxapi_enqueue_script' );
add_action( 'wp_ajax_xapiclicklog_action', 'xapiclicklog_action' );
add_action( 'wp_ajax_nopriv_xapiclicklog_action', 'xapiclicklog_action' );
add_action( 'wp_ajax_xapivideointeraction_action', 'xapivideointeraction_action' );
add_action( 'wp_ajax_nopriv_xapivideointeraction_action', 'xapivideointeraction_action' );

function xapiclicklog_action() {
	do_action( 'xapiclicklog_action_fire' );
}

function xapivideointeraction_action() {
	do_action( 'xapivideointeraction_action_fire' );
}

// Returns true if $needle is a substring of $haystack
function contains($needle, $haystack) {
    return strpos($haystack, $needle) !== false;
}

// Debug function that outputs to console log
// Gets called this way: console_log('Console Debug:', $someRealVar1, $someVar, $someArray, $someObj);
function console_log() {
    $js_code = 'console.log(' . json_encode(func_get_args(), JSON_HEX_TAG) .
        ');';
    $js_code = '<script>' . $js_code . '</script>';
    echo $js_code;
}

/**************************************
***************************************
** Page Views Tracking
** This trigger is for page views of various kinds
***************************************
**************************************/

WP_Experience_API::register( 'page_views', array(
	'hooks' => array( 'shutdown' ), //yes, kinda broad, but if singular, should be ok. Was 'wp'
	'process' => function( $hook, $args ) use ($debug) {

		if ($debug > 0) { error_log("/includes/triggers.php line 121 hook and args"); error_log(print_r($hook,true)); error_log(print_r($args,true)); }

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
		$postid = isset( $post->ID ) ? $post->ID : 0;
		$page_title = get_the_title( absint( $postid ) );
		$page_description = get_the_title( absint( $postid ) ) . ' | ' . get_bloginfo( 'name' );

		if ($debug > 1) {
			console_log('Page Views Tracking Debug =>   $page_title: ',$page_title,', $page_description: ',$page_description,'.');
		}

		$options = get_option( 'wpxapi_settings' );
		if ( 3 == $options['wpxapi_pages'] ) {
			return false;
		}
		if ( 2 == $options['wpxapi_pages'] ) {
			if ( ! is_singular() ) {
				return false;
			}
		}

		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$referrer = urldecode( $referrer );
		$referrer = sanitize_text_field( $referrer );

		// isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
		isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

		$current_page_url = WP_Experience_API::current_page_url();
		$current_page_url = urldecode( $current_page_url );
		$current_page_url = sanitize_text_field( $current_page_url );

		// Check if $current_page_url is 'wp-cron.php' and omit capturing and sending statement, in order to not capture page "views" of cron events.
		if ( contains( 'wp-cron.php', $current_page_url ) | contains( 'wp-login.php', $current_page_url ) | contains( 'xmlrpc.php', $current_page_url ) | $current_page_url == 'http://') {
			return false;
		}

		$statement = null;
		$statement = array(
			'verb' => array(
				'id' => 'http://id.tincanapi.com/verb/viewed',
				'display' => array( 'en-US' => 'viewed' ),
			),
			'object' => array(
				// 'id' => WP_Experience_API::current_page_url(),
				'id' => $current_page_url,
				'objectType' => 'Activity',
				'definition' => array(
					'name' => array(
						'en-US' => $page_title,
					),
					'description' => array(
						'en-US' => 'Viewed Page',
					),
					'type' => 'http://activitystrea.ms/schema/1.0/page',
				)
			),
			'context_raw' => array(
				'extensions' => array(
					'http://id.tincanapi.com/extension/target' => $page_description,
					'http://id.tincanapi.com/extension/browser-info' => array( 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ),
					'http://id.tincanapi.com/extension/referrer' => $referrer,
					'http://id.tincanapi.com/extension/ip-address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
				),
				'platform' => defined( 'CTLT_PLATFORM' ) ? constant( 'CTLT_PLATFORM' ) : 'Unknown'
			),
			'timestamp_raw' => date( 'c' )
		);

		$UserAgentString = $_SERVER['HTTP_USER_AGENT'];
		// UserAgents = [
		// 	"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36",

		//  "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0",
		//  "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0",
		//  "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0",
		//  "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 Edg/127.0.0.0",
		//  "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0",
		//  "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 Edg/125.0.0.0",

		// 	"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0",
		// 	"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:129.0) Gecko/20100101 Firefox/129.0",
		// 	"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0",
		// 	"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0",
		// 	"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:126.0) Gecko/20100101 Firefox/126.0",
		// 	"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0",

		// 	"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36",

		//  "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0",
		//  "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0",
		//  "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0",
		//  "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 Edg/127.0.0.0",
		//  "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0",
		//  "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 Edg/125.0.0.0",

		// 	"Mozilla/5.0 (X11; Linux x86_64; rv:130.0) Gecko/20100101 Firefox/130.0",
		// 	"Mozilla/5.0 (X11; Linux x86_64; rv:129.0) Gecko/20100101 Firefox/129.0",
		// 	"Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0",
		// 	"Mozilla/5.0 (X11; Linux x86_64; rv:127.0) Gecko/20100101 Firefox/127.0",
		// 	"Mozilla/5.0 (X11; Linux x86_64; rv:126.0) Gecko/20100101 Firefox/126.0",
		// 	"Mozilla/5.0 (X11; Linux x86_64; rv:125.0) Gecko/20100101 Firefox/125.0",

		// 	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36",
		// 	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36",

		//  "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0",
		//  "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0",
		//  "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0",
		//  "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 Edg/127.0.0.0",
		//  "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0",
		//  "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 Edg/125.0.0.0",

		// 	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:130.0) Gecko/20100101 Firefox/130.0",
		// 	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:129.0) Gecko/20100101 Firefox/129.0",
		// 	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:128.0) Gecko/20100101 Firefox/128.0",
		// 	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:127.0) Gecko/20100101 Firefox/127.0",
		// 	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:126.0) Gecko/20100101 Firefox/126.0",
		// 	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:125.0) Gecko/20100101 Firefox/125.0",

		//  "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.3",
		//  "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Mobile Safari/537.3",
		//  "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Mobile Safari/537.3",
		//  "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Mobile Safari/537.3",
		//  "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.3",
		//  "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.3",

		//  "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36 EdgA/130.0.0.0",
		//  "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Mobile Safari/537.36 EdgA/129.0.0.0",
		//  "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Mobile Safari/537.36 EdgA/128.0.0.0",
		//  "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Mobile Safari/537.36 EdgA/127.0.0.0",
		//  "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36 EdgA/126.0.0.0",
		//  "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.36 EdgA/125.0.0.0",

		//  "Mozilla/5.0 (Android 15; Mobile; rv:130.0) Gecko/130.0 Firefox/130.0",
		//  "Mozilla/5.0 (Android 15; Mobile; rv:129.0) Gecko/129.0 Firefox/129.0",
		//  "Mozilla/5.0 (Android 15; Mobile; rv:128.0) Gecko/128.0 Firefox/128.0",
		//  "Mozilla/5.0 (Android 15; Mobile; rv:127.0) Gecko/127.0 Firefox/127.0",
		//  "Mozilla/5.0 (Android 15; Mobile; rv:126.0) Gecko/126.0 Firefox/126.0",
		//  "Mozilla/5.0 (Android 15; Mobile; rv:125.0) Gecko/125.0 Firefox/125.0"
		// ]
		if (str_contains($UserAgentString, "Windows")) {
			if ( (str_contains($UserAgentString, "Chrome")) and !(str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Chrome');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif (str_contains($UserAgentString, "Firefox")) {
				$subStr = strstr($UserAgentString, 'Firefox');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif ( (str_contains($UserAgentString, "Chrome")) and (str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Edg');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = str_replace("Edg" , "Edge" , $sub1) ;
			}
			$subOS = "Win";
			$sub = $subOS . "." . $sub2;
		}
		elseif ( (str_contains($UserAgentString, "Linux")) and !(str_contains($UserAgentString, "Android")) ) {
			if ( (str_contains($UserAgentString, "Chrome")) and !(str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Chrome');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif (str_contains($UserAgentString, "Firefox")) {
				$subStr = strstr($UserAgentString, 'Firefox');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif ( (str_contains($UserAgentString, "Chrome")) and (str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Edg');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = str_replace("Edg" , "Edge" , $sub1) ;
			}
			$subOS = "Linux";
			$sub = $subOS . "." . $sub2;
		}
		elseif (str_contains($UserAgentString, "Macintosh")) {
			if ( (str_contains($UserAgentString, "Chrome")) and !(str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Chrome');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif (str_contains($UserAgentString, "Firefox")) {
				$subStr = strstr($UserAgentString, 'Firefox');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif ( (str_contains($UserAgentString, "Chrome")) and (str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Edg');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = str_replace("Edg" , "Edge" , $sub1) ;
			}
			$subOS = "Mac";
			$sub = $subOS . "." . $sub2;
		}
		elseif (str_contains($UserAgentString, "Android")) {
			if ( (str_contains($UserAgentString, "Chrome")) and !(str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Chrome');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif (str_contains($UserAgentString, "Firefox")) {
				$subStr = strstr($UserAgentString, 'Firefox');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif ( (str_contains($UserAgentString, "Chrome")) and (str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Edg');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = str_replace("EdgA" , "Edge" , $sub1) ;
			}
			$subOS = "Android";
			$sub = $subOS . "." . $sub2;
		}
		else {
			$sub = "Win.Chrome.128";
		}

		function stripSpecialCharsAndWhitespace($string) {
			return preg_replace('/[^A-Za-z0-9.]/', '', $string);
		}
		$cleanedSub = stripSpecialCharsAndWhitespace($sub);
		$cleanedString = filter_var($cleanedSub, FILTER_SANITIZE_STRING);

		$UserAgent = $cleanedString;

		$user = get_current_user_id();
		if ( empty( $user ) ) {
			if ( 1 == $options['wpxapi_guest'] ) {
				$user = array(
					'objectType' => 'Agent',
					'name' => 'Guest.' . $UserAgent,
					// 'name' => 'Guest.' . $_SERVER['REMOTE_ADDR'],
					'mbox' => 'mailto:guest.' . $UserAgent . '@ntua-guest.com',
					// 'mbox' => 'mailto:guest.' . $_SERVER['REMOTE_ADDR'] . '@ntua-guest.com',
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

/**************************************
***************************************
 * Video Interactions Tracking
***************************************
**************************************/

WP_Experience_API::register( 'wpxapi_video_interactions_log', array(
	'hooks' => array( 'xapivideointeraction_action_fire' ),
	'process' => function( $hook, $args ) use ($debug) {

	if ($debug > 0) { error_log("/includes/triggers.php line 48 hook and args"); error_log(print_r($hook,true)); error_log(print_r($args,true)); }

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

	$wpxapi_jw_videourl = urldecode( $_POST['wpxapi_jw_videourl'] );
	$wpxapi_jw_videourl = sanitize_text_field( $wpxapi_jw_videourl );
	$wpxapi_jw_videourl = isset($wpxapi_jw_videourl) ? $wpxapi_jw_videourl : '';

	$wpxapi_jw_videotitle = $_POST['wpxapi_jw_videotitle'];
	$wpxapi_jw_videotitle = sanitize_text_field( $wpxapi_jw_videotitle );
	if ( !isset($wpxapi_jw_videotitle) || empty($wpxapi_jw_videotitle) )  { $wpxapi_jw_videotitle = get_the_title( absint( $postid ) ) . ' | ' . get_bloginfo( 'name' ); };

	global $post;
	$postid = isset( $post->ID ) ? $post->ID : 0;
	if ($debug > 1) {
		console_log('Video Interactions Tracking Debug1 =>   $post: ',$post,'.');
		console_log('Video Interactions Tracking Debug2 =>   $wpxapi_jw_videotitle: ',$wpxapi_jw_videotitle,'.');
	}

	$page_title = get_the_title( absint( $postid ) );
	$page_description = get_the_title( absint( $postid ) ) . ' | ' . get_bloginfo( 'name' );

	if ($debug > 1) {
		console_log('Video Interactions Tracking Debug3 =>   $wpxapi_jw_videotitle: ',$wpxapi_jw_videotitle,'.');
		console_log('Video Interactions Tracking Debug3 =>   $page_description: ',$page_description,'.');
		console_log('Video Interactions Tracking Debug3 =>   $page_title: ',$page_title,'.');
	}

	$wpxapi_jw_playcount = $_POST['wpxapi_jw_playcount'];
	$wpxapi_jw_playcount = intval( $wpxapi_jw_playcount );
	$wpxapi_jw_playcount = isset($wpxapi_jw_playcount) ? $wpxapi_jw_playcount : '';

	$wpxapi_jw_playfrom = $_POST['wpxapi_jw_playfrom'];
	$wpxapi_jw_playfrom = intval( $wpxapi_jw_playfrom );
	$wpxapi_jw_playfrom = isset($wpxapi_jw_playfrom) ? $wpxapi_jw_playfrom : '';

	$wpxapi_jw_position = $_POST['wpxapi_jw_position'];
	$wpxapi_jw_position = intval( $wpxapi_jw_position );
	$wpxapi_jw_position = isset($wpxapi_jw_position) ? $wpxapi_jw_position : '';

	$wpxapi_jw_duration = $_POST['wpxapi_jw_duration'];
	$wpxapi_jw_duration = intval( $wpxapi_jw_duration );
	$wpxapi_jw_duration = isset($wpxapi_jw_duration) ? $wpxapi_jw_duration : '';

	$wpxapi_jw_pausestatement = $_POST['wpxapi_jw_pausestatement'];
	$wpxapi_jw_pausestatement = intval( $wpxapi_jw_pausestatement );
	$wpxapi_jw_pausestatement = isset($wpxapi_jw_pausestatement) ? $wpxapi_jw_pausestatement : '';

	if ($debug > 0) { error_log("/includes/triggers.php line 66 wpxapi_blogid, request_site_url, wpxapi_click_url_requested"); error_log(print_r($wpxapi_blogid,true)); error_log(print_r($request_site_url,true)); error_log(print_r($wpxapi_click_url_requested,true)); }
	if (strpos($wpxapi_click_url_requested, '#') === 0) {
		$wpxapi_click_url_requested = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'selflink').$wpxapi_click_url_requested;
		$wpxapi_click_url_requested = urldecode( $wpxapi_click_url_requested );
		$wpxapi_click_url_requested = sanitize_text_field( $wpxapi_click_url_requested );
		if ($debug > 0) { error_log("/includes/triggers.php line 71 wpxapi_blogid, request_site_url, wpxapi_click_url_requested"); error_log(print_r($wpxapi_blogid,true)); error_log(print_r($request_site_url,true)); error_log(print_r($wpxapi_click_url_requested,true)); }
	}

	$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	if ($debug > 0) { error_log("/includes/triggers.php line 75 referrer"); error_log(print_r($referrer,true)); }
	$referrer = urldecode( $referrer );
	$referrer = sanitize_text_field( $referrer );
	if ($debug > 0) { error_log("/includes/triggers.php line 78 referrer"); error_log(print_r($referrer,true)); }

	if ($debug > 1) {
		console_log('Video Interactions Tracking Debug4 =>   wpxapi_jw_videourl: ',$wpxapi_jw_videourl,' wpxapi_jw_videotitle: ',$wpxapi_jw_videotitle,' wpxapi_jw_playcount: ',$wpxapi_jw_playcount,'.');
		console_log('Video Interactions Tracking Debug5 =>   wpxapi_jw_playfrom: ',$wpxapi_jw_playfrom,' wpxapi_jw_position: ',$wpxapi_jw_position,' wpxapi_jw_duration: ',$wpxapi_jw_duration,' wpxapi_jw_pausestatement: ',$wpxapi_jw_pausestatement,'.');
	}

	$statement = null;

	if ( $wpxapi_jw_pausestatement == 1 ) {
		$statement = array(
			'verb' => array(
				'id' => 'https://w3id.org/xapi/video/verbs/played',
				'display' => array(
					'en-US' => 'played',
				),
			),

			'object' => array(
				'id' => $wpxapi_jw_videourl,
				'objectType' => 'Activity',
				'definition' => array(
					'name' => array(
						'en-US' => $wpxapi_jw_videotitle,
					),
					'description' => array(
						'en-US' => 'Video Played',
					),
					'type' => 'http://adlnet.gov/expapi/activities/video',
				),
			),

			'context_raw' => array(
				'extensions' => array(
					'http://adlnet.gov/expapi/activities/link'=> $wpxapi_jw_videotitle,
					'http://adlnet.gov/expapi/activities/file'=> $wpxapi_jw_videourl,
					'http://adlnet.gov/expapi/period_start'=> $wpxapi_jw_playfrom,
					'http://adlnet.gov/expapi/period_end'=> $wpxapi_jw_position,
					'http://adlnet.gov/expapi/period_duration'=> $wpxapi_jw_duration,
					'http://id.tincanapi.com/extension/target' => $wpxapi_jw_videotitle,
					'http://id.tincanapi.com/extension/browser-info' => array( 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ),
					'http://id.tincanapi.com/extension/referrer' => $referrer,
					'http://id.tincanapi.com/extension/ip-address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
					// Initialize
					// 'https://w3id.org/xapi/video/extensions/length' => '194.937',
					// Play
					// 'https://w3id.org/xapi/video/extensions/time' => '120.5',
					// Pause
					// 'https://w3id.org/xapi/video/extensions/time' => '120.5',
					// 'https://w3id.org/xapi/video/extensions/progress' => '0.01',
					// 'https://w3id.org/xapi/video/extensions/played-segments' => '0[.]6.489',
					// Seek
					// 'https://w3id.org/xapi/video/extensions/time-from' => '55.579',
					// 'https://w3id.org/xapi/video/extensions/time-to' => '48.197',
					// Complete & Terminated (the same except for completion & duration)
					// 'https://w3id.org/xapi/video/extensions/length' => '194.937',
					// 'result' => array(
					// 		'extensions' => array(
					// 			'https://w3id.org/xapi/video/extensions/time' => '194.937',
					// 			'https://w3id.org/xapi/video/extensions/progress' => '1',
					// 			'https://w3id.org/xapi/video/extensions/played-segments' => '0[.]6.489[,]4.526[.]12.492[,]4.526[.]187.913[,]185.886[.]194.937',
					// 		)
					// 	)
					// 'completion' => 'true',
					// 'duration' => 'PT194.94S',
					),
				'platform' => defined( 'CTLT_PLATFORM' ) ? constant( 'CTLT_PLATFORM' ) : 'Unknown'
				),

			'timestamp_raw' => date( 'c' )
			);
		}

	$UserAgentString = $_SERVER['HTTP_USER_AGENT'];
	if (str_contains($UserAgentString, "Windows")) {
		if ( (str_contains($UserAgentString, "Chrome")) and !(str_contains($UserAgentString, "Edg")) ) {
			$subStr = strstr($UserAgentString, 'Chrome');
			$array= explode(' ',$subStr);
			$position = strpos($array[0], '.');
			if ($position !== false) {
				$sub0 = substr($array[0], 0, $position);
			} else {
				$sub0 = $array[0];
			}
			$sub1 = str_replace("/" , "." , $sub0) ;
			$sub2 = $sub1 ;
		}
		elseif (str_contains($UserAgentString, "Firefox")) {
			$subStr = strstr($UserAgentString, 'Firefox');
			$array= explode(' ',$subStr);
			$position = strpos($array[0], '.');
			if ($position !== false) {
				$sub0 = substr($array[0], 0, $position);
			} else {
				$sub0 = $array[0];
			}
			$sub1 = str_replace("/" , "." , $sub0) ;
			$sub2 = $sub1 ;
		}
		elseif ( (str_contains($UserAgentString, "Chrome")) and (str_contains($UserAgentString, "Edg")) ) {
			$subStr = strstr($UserAgentString, 'Edg');
			$array= explode(' ',$subStr);
			$position = strpos($array[0], '.');
			if ($position !== false) {
				$sub0 = substr($array[0], 0, $position);
			} else {
				$sub0 = $array[0];
			}
			$sub1 = str_replace("/" , "." , $sub0) ;
			$sub2 = str_replace("Edg" , "Edge" , $sub1) ;
		}
		$subOS = "Win";
		$sub = $subOS . "." . $sub2;
	}
	elseif ( (str_contains($UserAgentString, "Linux")) and !(str_contains($UserAgentString, "Android")) ) {
		if ( (str_contains($UserAgentString, "Chrome")) and !(str_contains($UserAgentString, "Edg")) ) {
			$subStr = strstr($UserAgentString, 'Chrome');
			$array= explode(' ',$subStr);
			$position = strpos($array[0], '.');
			if ($position !== false) {
				$sub0 = substr($array[0], 0, $position);
			} else {
				$sub0 = $array[0];
			}
			$sub1 = str_replace("/" , "." , $sub0) ;
			$sub2 = $sub1 ;
		}
		elseif (str_contains($UserAgentString, "Firefox")) {
			$subStr = strstr($UserAgentString, 'Firefox');
			$array= explode(' ',$subStr);
			$position = strpos($array[0], '.');
			if ($position !== false) {
				$sub0 = substr($array[0], 0, $position);
			} else {
				$sub0 = $array[0];
			}
			$sub1 = str_replace("/" , "." , $sub0) ;
			$sub2 = $sub1 ;
		}
		elseif ( (str_contains($UserAgentString, "Chrome")) and (str_contains($UserAgentString, "Edg")) ) {
			$subStr = strstr($UserAgentString, 'Edg');
			$array= explode(' ',$subStr);
			$position = strpos($array[0], '.');
			if ($position !== false) {
				$sub0 = substr($array[0], 0, $position);
			} else {
				$sub0 = $array[0];
			}
			$sub1 = str_replace("/" , "." , $sub0) ;
			$sub2 = str_replace("Edg" , "Edge" , $sub1) ;
		}
		$subOS = "Linux";
		$sub = $subOS . "." . $sub2;
	}
	elseif (str_contains($UserAgentString, "Macintosh")) {
		if ( (str_contains($UserAgentString, "Chrome")) and !(str_contains($UserAgentString, "Edg")) ) {
			$subStr = strstr($UserAgentString, 'Chrome');
			$array= explode(' ',$subStr);
			$position = strpos($array[0], '.');
			if ($position !== false) {
				$sub0 = substr($array[0], 0, $position);
			} else {
				$sub0 = $array[0];
			}
			$sub1 = str_replace("/" , "." , $sub0) ;
			$sub2 = $sub1 ;
		}
		elseif (str_contains($UserAgentString, "Firefox")) {
			$subStr = strstr($UserAgentString, 'Firefox');
			$array= explode(' ',$subStr);
			$position = strpos($array[0], '.');
			if ($position !== false) {
				$sub0 = substr($array[0], 0, $position);
			} else {
				$sub0 = $array[0];
			}
			$sub1 = str_replace("/" , "." , $sub0) ;
			$sub2 = $sub1 ;
		}
		elseif ( (str_contains($UserAgentString, "Chrome")) and (str_contains($UserAgentString, "Edg")) ) {
			$subStr = strstr($UserAgentString, 'Edg');
			$array= explode(' ',$subStr);
			$position = strpos($array[0], '.');
			if ($position !== false) {
				$sub0 = substr($array[0], 0, $position);
			} else {
				$sub0 = $array[0];
			}
			$sub1 = str_replace("/" , "." , $sub0) ;
			$sub2 = str_replace("Edg" , "Edge" , $sub1) ;
		}
		$subOS = "Mac";
		$sub = $subOS . "." . $sub2;
	}
	elseif (str_contains($UserAgentString, "Android")) {
		if ( (str_contains($UserAgentString, "Chrome")) and !(str_contains($UserAgentString, "Edg")) ) {
			$subStr = strstr($UserAgentString, 'Chrome');
			$array= explode(' ',$subStr);
			$position = strpos($array[0], '.');
			if ($position !== false) {
				$sub0 = substr($array[0], 0, $position);
			} else {
				$sub0 = $array[0];
			}
			$sub1 = str_replace("/" , "." , $sub0) ;
			$sub2 = $sub1 ;
		}
		elseif (str_contains($UserAgentString, "Firefox")) {
			$subStr = strstr($UserAgentString, 'Firefox');
			$array= explode(' ',$subStr);
			$position = strpos($array[0], '.');
			if ($position !== false) {
				$sub0 = substr($array[0], 0, $position);
			} else {
				$sub0 = $array[0];
			}
			$sub1 = str_replace("/" , "." , $sub0) ;
			$sub2 = $sub1 ;
		}
		elseif ( (str_contains($UserAgentString, "Chrome")) and (str_contains($UserAgentString, "Edg")) ) {
			$subStr = strstr($UserAgentString, 'Edg');
			$array= explode(' ',$subStr);
			$position = strpos($array[0], '.');
			if ($position !== false) {
				$sub0 = substr($array[0], 0, $position);
			} else {
				$sub0 = $array[0];
			}
			$sub1 = str_replace("/" , "." , $sub0) ;
			$sub2 = str_replace("EdgA" , "Edge" , $sub1) ;
		}
		$subOS = "Android";
		$sub = $subOS . "." . $sub2;
	}
	else {
		$sub = "Win.Chrome.128";
	}

	function stripSpecialCharsAndWhitespace($string) {
		return preg_replace('/[^A-Za-z0-9.]/', '', $string);
	}
	$cleanedSub = stripSpecialCharsAndWhitespace($sub);
	$cleanedString = filter_var($cleanedSub, FILTER_SANITIZE_STRING);

	$UserAgent = $cleanedString;

	// $user_obj = get_user_by( 'ID', $wpxapi_uid );
	// $user = $user_obj->ID;
	$options = get_option( 'wpxapi_settings' );
	$user = get_current_user_id();
	if ( empty( $user ) ) {
		if ( 1 == $options['wpxapi_guest'] ) {
			$user = array(
				'objectType' => 'Agent',
				'name' => 'Guest.' . $UserAgent,
				// 'name' => 'Guest.' . $_SERVER['REMOTE_ADDR'],
				'mbox' => 'mailto:guest.' . $UserAgent . '@ntua-guest.com',
				// 'mbox' => 'mailto:guest.' . $_SERVER['REMOTE_ADDR'] . '@ntua-guest.com',
				// 'name' => 'Guest ' . $_SERVER['REMOTE_ADDR'],
				// 'mbox' => 'mailto:guest-' . $_SERVER['REMOTE_ADDR'] . '@ntua-guest.com',
				// 'mbox' => 'mailto:guest-' . $_SERVER['REMOTE_ADDR'] . '@' . preg_replace( '/http(s)?:\/\//', '', get_bloginfo( 'url' ) ),
			);
			  $statement = array_merge( $statement, array( 'actor_raw' => $user ) );
		 } else {
			return false;
		 }
	  } else {
		  $statement = array_merge( $statement, array( 'user' => $user ) );
	  }
	if ($debug > 0) { error_log("/includes/triggers.php line 166 statement"); error_log(print_r($statement,true)); }
	return $statement;
  }
));

/**************************************
***************************************
** Click Link Tracking
***************************************
**************************************/

WP_Experience_API::register( 'wpxapi_linkclick_track_log', array(
	'hooks' => array( 'xapiclicklog_action_fire' ),
	'process' => function( $hook, $args ) use ($debug) {

		if ($debug > 0) { error_log("/includes/triggers.php line 36 hook and args"); error_log(print_r($hook,true)); error_log(print_r($args,true)); }

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

		if ($debug > 0) { error_log("/includes/triggers.php line 58 wpxapi_blogid, request_site_url, wpxapi_click_url_requested"); error_log(print_r($wpxapi_blogid,true)); error_log(print_r($request_site_url,true)); error_log(print_r($wpxapi_click_url_requested,true)); }
		if (strpos($wpxapi_click_url_requested, '#') === 0) {
			$wpxapi_click_url_requested = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'selflink').$wpxapi_click_url_requested;
			$wpxapi_click_url_requested = urldecode( $wpxapi_click_url_requested );
			$wpxapi_click_url_requested = sanitize_text_field( $wpxapi_click_url_requested );
			if ($debug > 0) { error_log("/includes/triggers.php line 63 wpxapi_blogid, request_site_url, wpxapi_click_url_requested"); error_log(print_r($wpxapi_blogid,true)); error_log(print_r($request_site_url,true)); error_log(print_r($wpxapi_click_url_requested,true)); }
		}

		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		if ($debug > 0) { error_log("/includes/triggers.php line 67 referrer"); error_log(print_r($referrer,true)); }
		$referrer = urldecode( $referrer );
		$referrer = sanitize_text_field( $referrer );
		if ($debug > 0) { error_log("/includes/triggers.php line 69 referrer"); error_log(print_r($referrer,true)); }

		$statement = null;

		$statement = array(
			'verb' => array(
				'id' => 'http://adlnet.gov/expapi/verbs/interacted',
				'display' => array( 'en-US' => 'interacted' ),
			),

			'object' => array(
				'id' => $wpxapi_click_url_requested,
				'objectType' => 'Activity',
				'definition' => array(
					'name' => array(
						'en-US' => $wpxapi_click_url_requested,
					),
					'description' => array(
						'en-US' => 'Interacted',
					),
					'type' => 'http://adlnet.gov/expapi/activities/link',
					)
				),
			'context_raw' => array(
				'extensions' => array(
					'http://id.tincanapi.com/extension/target' => $wpxapi_click_url_requested,
					'http://id.tincanapi.com/extension/browser-info' => array( 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ),
					'http://id.tincanapi.com/extension/referrer' => $referrer,
					'http://id.tincanapi.com/extension/ip-address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
					),
				'platform' => defined( 'CTLT_PLATFORM' ) ? constant( 'CTLT_PLATFORM' ) : 'Unknown'
				),
			'timestamp_raw' => date( 'c' )
		);

		$UserAgentString = $_SERVER['HTTP_USER_AGENT'];
		if (str_contains($UserAgentString, "Windows")) {
			if ( (str_contains($UserAgentString, "Chrome")) and !(str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Chrome');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif (str_contains($UserAgentString, "Firefox")) {
				$subStr = strstr($UserAgentString, 'Firefox');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif ( (str_contains($UserAgentString, "Chrome")) and (str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Edg');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = str_replace("Edg" , "Edge" , $sub1) ;
			}
			$subOS = "Win";
			$sub = $subOS . "." . $sub2;
		}
		elseif ( (str_contains($UserAgentString, "Linux")) and !(str_contains($UserAgentString, "Android")) ) {
			if ( (str_contains($UserAgentString, "Chrome")) and !(str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Chrome');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif (str_contains($UserAgentString, "Firefox")) {
				$subStr = strstr($UserAgentString, 'Firefox');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif ( (str_contains($UserAgentString, "Chrome")) and (str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Edg');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = str_replace("Edg" , "Edge" , $sub1) ;
			}
			$subOS = "Linux";
			$sub = $subOS . "." . $sub2;
		}
		elseif (str_contains($UserAgentString, "Macintosh")) {
			if ( (str_contains($UserAgentString, "Chrome")) and !(str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Chrome');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif (str_contains($UserAgentString, "Firefox")) {
				$subStr = strstr($UserAgentString, 'Firefox');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif ( (str_contains($UserAgentString, "Chrome")) and (str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Edg');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = str_replace("Edg" , "Edge" , $sub1) ;
			}
			$subOS = "Mac";
			$sub = $subOS . "." . $sub2;
		}
		elseif (str_contains($UserAgentString, "Android")) {
			if ( (str_contains($UserAgentString, "Chrome")) and !(str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Chrome');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif (str_contains($UserAgentString, "Firefox")) {
				$subStr = strstr($UserAgentString, 'Firefox');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = $sub1 ;
			}
			elseif ( (str_contains($UserAgentString, "Chrome")) and (str_contains($UserAgentString, "Edg")) ) {
				$subStr = strstr($UserAgentString, 'Edg');
				$array= explode(' ',$subStr);
				$position = strpos($array[0], '.');
				if ($position !== false) {
				    $sub0 = substr($array[0], 0, $position);
				} else {
				    $sub0 = $array[0];
				}
				$sub1 = str_replace("/" , "." , $sub0) ;
				$sub2 = str_replace("EdgA" , "Edge" , $sub1) ;
			}
			$subOS = "Android";
			$sub = $subOS . "." . $sub2;
		}
		else {
			$sub = "Win.Chrome.128";
		}

		function stripSpecialCharsAndWhitespace($string) {
			return preg_replace('/[^A-Za-z0-9.]/', '', $string);
		}
		$cleanedSub = stripSpecialCharsAndWhitespace($sub);
		$cleanedString = filter_var($cleanedSub, FILTER_SANITIZE_STRING);

		$UserAgent = $cleanedString;

		/** $user_obj = get_user_by( 'ID', $wpxapi_uid );
		 * $user = $user_obj->ID; **/
		$options = get_option( 'wpxapi_settings' );
		$user = get_current_user_id();
		if ( empty( $user ) ) {
			if ( 1 == $options['wpxapi_guest'] ) {
				$user = array(
					'objectType' => 'Agent',
					'name' => 'Guest.' . $UserAgent,
					// 'name' => 'Guest.' . $_SERVER['REMOTE_ADDR'],
					'mbox' => 'mailto:guest.' . $UserAgent . '@ntua-guest.com',
					// 'mbox' => 'mailto:guest.' . $_SERVER['REMOTE_ADDR'] . '@ntua-guest.com',
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
		if ($debug > 0) { error_log("/includes/triggers.php line 108 statement"); error_log(print_r($statement,true)); }
		return $statement;
	  }
));

/**************************************
***************************************
** Page Transitions Tracking
** This trigger is to track some specific post transitions (going to published, trashed, etc)
***************************************
**************************************/

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
					'http://id.tincanapi.com/extension/target' => (string) $current_post->post_title . ' | ' . get_bloginfo( 'name' ),
					'http://id.tincanapi.com/extension/browser-info' => array( 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ),
					'http://id.tincanapi.com/extension/referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
					'http://id.tincanapi.com/extension/ip-address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
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