<?php
/**
 * These are just default constants that can be used anywhere
 *
 */
define( 'WP_XAPI_PLUGIN_VERSION', '202410081700' );
/* define( 'WP_XAPI_PLUGIN_VERSION', '20150908101' ); */
define( 'WP_XAPI_DEFAULT_ACTOR_ACCOUNT_HOMEPAGE', 'https://www.netmode.ntua.gr/' );
define( 'WP_XAPI_DEFAULT_XAPI_VERSION', '1.0.0' );
define( 'WP_XAPI_DEFAULT_ACTOR_ACCOUNT_NAME', 'puid' );
/* define( 'WP_XAPI_DEFAULT_ACTOR_ACCOUNT_NAME', 'unique_id' ); */
/* define( 'WP_XAPI_DEFAULT_ACTOR_ACCOUNT_NAME', 'Guest' ); */
define( 'WP_XAPI_MINIMUM_PHP_VERSION', '5.4' );
define( 'WP_XAPI_MAX_SENDING_TRIES', 15 );
/* define( 'WP_XAPI_MAX_SENDING_TRIES', 30 ); */
define( 'WP_XAPI_TABLE_NAME', 'wpxapi_queue' );	//queue used to try to resend failed statements
define( 'WP_XAPI_QUEUE_RECURRANCE', 'hourly' ); //other valid values: 'hourly', 'twicedaily', 'daily'
