<?php

namespace BaytekRewrites;

use BaytekRewrites\System\Rewrites;
use BaytekRewrites\System\Settings;
use BaytekRewrites\System\Updates;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Main Plugin Class
 */

class Plugin extends BasePlugin {

	/**
	 * Plugin constants
	 */
	const TEXTDOMAIN = 'baytek-rewrites';
	const VERSION = '1.1.4';

	/**
	 * Set the plugin paths
	 *
	 * @param  string  $plugin_path  The path to the main plugin file
	 */
	public function setPaths( $plugin_path ) {

		// Set default paths and URLs in parent
		parent::setPaths( $plugin_path );

		// Set the theme override directory
		$this->paths['theme-override'] = '/baytek/baytek-rewrites';
	}

	/**
	 * Run the plugin's default tasks
	 */
	public function run() {

		// Init system code
		new Updates( static::$instance );
		new Rewrites( static::$instance );
		new Settings( static::$instance );

		// Load textdomain
		load_plugin_textdomain( self::TEXTDOMAIN, false, 'baytek-rewrites/resources/assets/languages/' );
	}
}
