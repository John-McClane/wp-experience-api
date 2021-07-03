<?php
/**
 * This is where the magic happens!  Just need to call ExperienceAPI::register()
 */

/**
 * This trigger is for video interactions of various kinds
 */
WP_Experience_API::register( 'video_inter', array(
	'hooks' => array( 'wp' ), //yes, kinda broad, but if singular, should be ok
	'process' => function( $hook, $args ) {
		global $post;

		//need to make sure that description is working.
		$description = get_bloginfo( 'description' );
		if ( empty( $description ) ) {
			$description = 'n/a';
		}

		$statement = null;
		$statement = array(
			'verb' => array(
				'id' => 'http://id.tincanapi.com/verb/played',
				'display' => array( 'en-US' => 'played' ),
			),
			'object' => array(
				'id' => WP_Experience_API::current_page_url(),
				'definition' => array(
					'name' => array(
						'en-US' => get_the_title( absint( $post->ID ) ) . ' | ' . get_bloginfo( 'name' ),
					),
					'description' => array(
						'en-US' => $description,
					),
					'type' => 'http://activitystrea.ms/schema/1.0/video',
				)
			),
			'context_raw' => array(
				'extensions' => array(
					'http://id.tincanapi.com/extension/browser-info' => array( 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ),
					'http://id.tincanapi.com/extension/referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
				),
				'platform' => defined( 'CTLT_PLATFORM' ) ? constant( 'CTLT_PLATFORM' ) : 'unknown'
			),
			'timestamp_raw' => date( 'c' )
		);

		$user = get_current_user_id();
		if ( empty( $user ) ) {
			/* if ( 1 == $options['wpxapi_guest'] ) { */
				$user = array(
					'objectType' => 'Agent',
					'name' => 'Guest ' . $_SERVER['REMOTE_ADDR'],
					'mbox' => 'mailto:guest-' . $_SERVER['REMOTE_ADDR'] . '@ntua-guest.com',
					/* 'mbox' => 'mailto:guest-' . $_SERVER['REMOTE_ADDR'] . '@' . preg_replace( '/http(s)?:\/\//', '', get_bloginfo( 'url' ) ), */
				);
				$statement = array_merge( $statement, array( 'actor_raw' => $user ) );
			/* } else {
				return false;
			} */
		} else {
			$statement = array_merge( $statement, array( 'user' => $user ) );
		}

		return $statement;
	}
));
