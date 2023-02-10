<?php

namespace BaytekRewrites;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Base plugin class to extend
 */

abstract class BasePlugin {

	/**
	 * Plugin instance
	 */
	protected static $instance = null;

	/**
	 * Plugin properties
	 */
	protected $paths = [];
	protected $urls = [];
	protected $postTypes = [];
	protected $metaBoxes = [];
	protected $taxonomies = [];
	protected $shortcodes = [];
	protected $system = [];

	/**
	 * Prevent direct access to the constructor
	 */
	protected function __construct() {}

	/**
	 * Prevent direct access to cloning
	 */
	protected function __clone() {}

	/**
	 * Get the instance of this class
	 */
	public static function getInstance() {
		if ( !isset( static::$instance ) ) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * Set the plugin paths
	 *
	 * @param  string  $plugin_path  The path to the main plugin file
	 */
	public function setPaths( $plugin_path ) {

		// Set all pertinent directory paths
		$this->paths['plugin'] = WP_PLUGIN_DIR . '/' . basename( dirname( $plugin_path ) );
		$this->paths['views'] = trailingslashit( $this->paths['plugin'] ) . 'resources/views';
		$this->paths['theme-override'] = '';

		// Set all pertinent URLs
		$this->urls['plugin'] = plugins_url() . '/' . basename( dirname( $plugin_path ) );
		$this->urls['assets'] = trailingslashit( $this->urls['plugin'] ) . 'resources/assets';
	}

	/**
	 * Get the plugin directory path
	 *
	 * @return string  The plugin directory
	 */
	public function getPluginDir() {

		return $this->paths['plugin'];
	}

	/**
	 * Get the plugin views directory path
	 *
	 * @return string  The view directory within the plugin
	 */
	public function getViewsDir() {

		return $this->paths['views'];
	}

	/**
	 * Get the theme override directory for plugin views
	 *
	 * @return string  The theme override directory (not full path)
	 */
	public function getThemeOverrideDir() {

		return $this->paths['theme-override'];
	}

	/**
	 * Get the plugin directory url
	 *
	 * @return string  The plugin URL
	 */
	public function getPluginUrl() {

		return $this->urls['plugin'];
	}

	/**
	 * Get the plugin assets directory url
	 *
	 * @return string  The assets URL within the plugin
	 */
	public function getAssetsUrl() {

		return $this->urls['assets'];
	}

	/**
	 * Abstract run function to be overwritten in each plugin.
	 * Responsible for governing primary plugin functionality
	 */
	abstract public function run();
}
