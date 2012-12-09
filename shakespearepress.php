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
    populatePlay($play_url);
 }
}


register_deactivation_hook(__FILE__,'shakespearepress_uninstall');

function shakespearepress_uninstall() {
	wp_delete_user( username_exists( 'wshakespeare' ));
}

/*
// No options currently
/////////// set up option storing stuff
// create array of options
$shakespearepress_options_arr=array(
  "shakespearepress_option_1"=>'',
  );

// store them
update_option('shakespearepress_plugin_options',$shakespearepress_options_arr); 

// get them
$shakespearepress_options_arr = get_option('shakespearepress_plugin_options');

// use them. 
$shakespearepress_option_1 = $shakespearepress_options_arr["shakespearepress_option_1"];
// end option array setup
*/

/*
// no admin options yet
// required in WP 3 but not earlier?
add_action('admin_menu', 'shakespearepress_plugin_menu');

/////////// set up stuff for admin options pages
// add submenu item to existing WP menu
function shakespearepress_plugin_menu() {
add_options_page('Shakespeare Pres settings page', 'Shakespeare Press settings', 'manage_options', __FILE__, 'shakespearepress_settings_page');
}

// call register settings function before admin pages rendered
add_action('admin_init', 'shakespearepress_register_settings');

function shakespearepress_register_settings() {
  // register settings - array, not individual
  register_setting('shakespearepress-settings-group', 'shakespearepress_settings_values');
}

// write out the plugin options form. Form field name must match option name.
// add other options here as necessary
// just a placeholder in case

function shakespearepress_settings_page() {
  
  if (!current_user_can('manage_options'))  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }

  ?>
  <div>
  <h2><?php _e('share what you see plugin options', 'shakespearepress-plugin') ?></h2>
  <form method="post" action="options.php">
  <?php settings_fields('shakespearepress-settings-group'); ?>

  <?php _e('setting 1','shakespearepress-plugin') ?> 
  
  <?php shakespearepress_setting_1(); ?><br />

  <p class="submit"><input type="submit" class="button-primary" value=<?php _e('Save changes', 'shakespearepress-plugin') ?> /></p>
  </form>
  </div>
  <?php
}
*/


?>