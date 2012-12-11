<?php
/*
Plugin Name: Shakespearepress
Plugin URI: https://github.com/ostephens/shakespearepress
Description: Plugin to display interesting Shakespeare stuff as part of #willhack
Version: 0.1
Author: Owen Stephens
License: GPL2

*/

/*

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/
// Path to ShakespearePress plugin
if ( !defined( 'SP_PLUGIN_DIR' ) ) {
	define( 'SP_PLUGIN_DIR', WP_PLUGIN_DIR . '/shakespearepress' );
}
// Setup the ShakespearePress theme directory
register_theme_directory( SP_PLUGIN_DIR . '/sp-themes' );

if(@is_file( SP_PLUGIN_DIR.'/shakespearepress_functions.php')) {
    include_once(SP_PLUGIN_DIR.'/shakespearepress_functions.php'); 
}

/////////// set up activation and deactivation stuff
register_activation_hook(__FILE__,'shakespearepress_install');

function shakespearepress_install() {
  // do stuff when installed
  global $wp_version;
  if (version_compare($wp_version, "3", "<")) {
    deactivate_plugins(basename(__FILE__)); // deactivate plugin
    wp_die("This plugin requires WordPress Version 3 or higher.");
  } else {
	switch_theme( 'clean-home-sp', 'clean-home-sp' );
	createShakespeare();
	$play_url = "http://wwsrv.edina.ac.uk/wworld/plays/Much_Ado_about_Nothing.xml";
    //populatePlay($play_url);
 }
}


register_deactivation_hook(__FILE__,'shakespearepress_uninstall');

function shakespearepress_uninstall() {
	set_time_limit(0);
	delete_option( 'shakespearepress-play' );
	delete_option( 'theme_mods_clean-home' );
	switch_theme( 'twentyten','twentyten');
	wp_delete_user( username_exists( 'wshakespeare' ));
}


// add admin menu
add_action('admin_menu', 'shakespearepress_plugin_menu');

// call register settings function before admin pages rendered
add_action('admin_init', 'shakespearepress_register_settings');
?>