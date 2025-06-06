<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Plugin Name: Baytek Rewrites for Post Types
 * Description: Set parent pages for your flat & hierarchical post types and automatically manage URLs
 * Version: 1.1.5
 * Author: Baytek
 * Author URI: https://www.baytek.ca
 * Text Domain: baytek-rewrites
 * Domain Path: /resources/assets/languages/
 * Update URI:  https://github.com/baytek-team/baytek-rewrites/raw/main/dist/info.json

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

use BaytekRewrites\Plugin;

/**
 * Autoload classes if they belong to the plugin
 *
 * @param  string  $class_name  Name of the class being loaded
 */
function baytek_rewrites_autoloader( $class_name ) {
	
	if ( false !== strpos( $class_name, 'BaytekRewrites' ) ) {
		$classes_dir = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
		$class_file = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name ) . '.php';
		require_once $classes_dir . $class_file;
	}
}
spl_autoload_register( 'baytek_rewrites_autoloader' );

/**
 * Init plugin
 */
function baytek_rewrites_init() {
	$plugin = Plugin::getInstance();
	$plugin->setPaths( __FILE__ );
	$plugin->run();
}
add_action( 'plugins_loaded', 'baytek_rewrites_init' );
