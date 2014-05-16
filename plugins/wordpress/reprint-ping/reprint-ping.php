<?php
/*
Plugin Name: Reprint Ping Callback
Plugin URI: http://github.com/adamaveray/reprint
Author: Adam Averay
Version: 1.0
Description: Trigger a refresh of a Reprint site every time a post is published or edited
*/


define('REPRINT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REPRINT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REPRINT_PLUGIN_FILE', basename(__FILE__));
define('REPRINT_PLUGIN_FULL_PATH', __FILE__);

// Main hook
function reprintPublishPing($postID){
	$url	= get_option('reprint_url');
	$secret	= get_option('reprint_secret');
	if(!isset($url) || !isset($secret)){
		return;
	}

	file_get_contents($url.'?secret='.urlencode($secret));
}

add_action('publish_post', 'reprintPublishPing');
add_action('edit_post', 'reprintPublishPing');
add_action('publish_post', 'reprintPublishPing');

// Settings Link
function reprintPluginActionLinks($links, $file){
	if($file === plugin_basename(dirname(REPRINT_PLUGIN_FULL_PATH).'/'.REPRINT_PLUGIN_FILE)){
		// Reprint list item - add settings link
		$link	= '<a href="options-general.php?page=settings-'.urlencode(REPRINT_PLUGIN_FILE).'">Settings</a>';
		array_unshift($links, $link);
	}

	return $links;
}

add_filter('plugin_action_links', 'reprintPluginActionLinks', 10, 2);



// Settings page
function reprintSettingsPageSubmit(){
	if(!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'reprint-settings')){
		wp_die('Security check');
	}

	if(isset($_POST['reprint-url'])){
		update_option('reprint_url', $_POST['reprint-url']);
	}
	if(isset($_POST['reprint-secret'])){
		update_option('reprint_secret', $_POST['reprint-secret']);
	}
}

function reprintSettingsPage(){
	if(isset($_POST['reprint-url'])){
		// Save changes
		reprintSettingsPageSubmit();
	}

	$settings	= array(
		'url'		=> (string)get_option('reprint_url'),
		'secret'	=> (string)get_option('reprint_secret'),
	);

	if(is_multisite()){
		$link = 'settings.php';
	} else {
		$link = 'options-general.php';
	}

	include(REPRINT_PLUGIN_DIR.'inc/settings.php');
}

function reprintSettingsMenu(){
	add_options_page('Reprint', 'Reprint', 'manage_options', 'settings-'.REPRINT_PLUGIN_FILE, 'reprintSettingsPage');
}

add_action('admin_menu', 'reprintSettingsMenu');
