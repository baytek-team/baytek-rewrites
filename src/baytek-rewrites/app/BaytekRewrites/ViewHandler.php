<?php

namespace BaytekRewrites;

use BaytekRewrites\Plugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Static class to handle views and rendering
 */

class ViewHandler {

	/**
	 * Handle the views
	 *
	 * @param  string  $view  The full path of the view to render
	 * @param  array   $args  The arguments for the view
	 */
	public static function render( $view, $args = [] ) {

		// Extract the args, if there are any
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args );
		}

		// Check if file exists and if it doesn't, throw a PHP Notice and return
		
		// Do pre-render actions
		do_action( __NAMESPACE__.'\\'.__CLASS__.'::'.__METHOD__.'-before', $view, $args );

		// Include the file
		include( $view );

		// Do post-render actions
		do_action( __NAMESPACE__.'\\'.__CLASS__.'::'.__METHOD__.'-after', $view, $args );
	}

	/**
	 * Searches the current theme for view file requested.
	 * Otherwise returns the plugin's default view file.
	 *
	 * @param  string  $view_name  The current view file WordPress intended to use to display the page
	 * @param  string  $subfolder  The subfolder under the main view directory to check
	 *
	 * @return string  $view 	   The full path view to use
	 */
	public static function getView( $view_name, $subfolder = '' ) {

		// Get the Plugin instance
		$plugin = Plugin::getInstance();

		// If no view name is provided, throw a PHP notice and return

		// If subfolder(s) were provided, append the view name
		if ( !empty( $subfolder ) ) {
			$view_name = trailingslashit( $subfolder ) . $view_name;
		}

		// Get the view file from the plugin folder
		$view = trailingslashit( $plugin->getViewsDir() ) . $view_name;

	    // Look in the current theme for the view file
	    if ( $theme_view = locate_template( trailingslashit( $plugin->getThemeOverrideDir() ) . $view_name ) ) {
	        $view = $theme_view;
	    }

	    // Apply filters and review the view
	    return apply_filters( __NAMESPACE__.'\\'.__CLASS__.'::'.__METHOD__, $view, $view_name, $subfolder );
	}

	/**
	 * Get view part from either the theme or plugin
	 *
	 * @param  string  		$slug  		The generic view part
	 * @param  string|null  $name  		The specific version of this view part
	 * @param  string|null  $subfolder  The subfolder under the main view directory to check
	 *
	 * @return string  		$view  		The full path view to use
	 */
	public static function getViewPart( $slug, $name = '', $subfolder = '' ) {

		// Get the Plugin instance
		$plugin = Plugin::getInstance();

		// If no slug is provided, throw a PHP notice and return

		$view = '';

		// If subfolder(s) were provided, append the view name
		if ( !empty( $subfolder ) ) {
			$slug = trailingslashit( $subfolder ) . $slug;
		}

		// First check theme for $slug-$name.php
		if ( !empty( $name ) ) {
			$view = locate_template( trailingslashit( $plugin->getThemeOverrideDir() ) . "{$slug}-{$name}.php" );
		}

		// If it doesn't exist, check plugin for $slug-$name.php
		if ( empty( $view ) ) {
			$view = trailingslashit( $plugin->getViewsDir() ) . "{$slug}-{$name}.php";
		}

		// Still no luck? Try the theme for $name.php
		if ( !file_exists( $view ) ) {
			$view = locate_template( trailingslashit( $plugin->getThemeOverrideDir() ) . "{$slug}.php" );
		}

		// Last but not least, use $name.php from the plugin
		if ( empty( $view ) ) {
			$view = trailingslashit( $plugin->getViewsDir() ) . "{$slug}.php";
		}

		// If it still can't be found, throw a PHP Notice

		// Apply filters and return the view
		return apply_filters( __NAMESPACE__.'\\'.__CLASS__.'::'.__METHOD__, $view, $slug, $name, $subfolder );
	}
}
