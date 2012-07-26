<?php
/*
Plugin Name: API Client
Plugin URI: http://localhost
Description: fill this in
Version: 0.1
Author: Ben Lobaugh
Author URI: http://ben.lobaugh.net
*/


/**
* TODO:
*
* Prettify with WordPress styles
* Sanitize db inputs
* Possibly add listing of endpoints to form
*/

add_action( 'init', 'api_client_includes', 1 );

function api_client_includes() {
    require_once( 'ApiRequest.class.php' );
}

add_action( 'init', 'my_plugin_load_first' );
function my_plugin_load_first()
{
	$path = str_replace( WP_PLUGIN_DIR . '/', '', __FILE__ );
	if ( $plugins = get_option( 'active_plugins' ) ) {
		if ( $key = array_search( $path, $plugins ) ) {
			array_splice( $plugins, $key, 1 );
			array_unshift( $plugins, $path );
			update_option( 'active_plugins', $plugins );
		}
	}
}