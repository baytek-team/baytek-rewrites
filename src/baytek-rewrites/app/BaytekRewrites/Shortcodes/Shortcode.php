<?php

namespace BaytekRewrites\Shortcodes;

use BaytekRewrites\Plugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Template for custom meta boxes
 */

abstract class Shortcode {

	/**
	 * Unique tag used in content
	 */
	protected $tag = 'custom-shortcode';

	/**
	 * Main plugin instance
	 */
	protected $plugin;

	/**
	 * Create the MetaBox object, setting the main plugin
	 * instance and calling the addHooks() method
	 *
	 * @param  BaytekRewrites\Plugin  $instance
	 */
	public function __construct( $instance ) {
		$this->plugin = $instance;
		$this->addHooks();
	}

	// Functions required by child classes

	/**
	 * Set up all class hooks
	 */
	public function addHooks() {
		// Add the shortcode
		add_shortcode( $this->tag, [ $this, 'shortcodeCallback' ] );
	}

	/**
	 * Render the shortcode
	 *
	 * @param  array   $atts     The array of shortcode attributes
	 * @param  string  $content  The shortcode content
	 */
	abstract public function shortcodeCallback( $atts, $content = '' );
}
