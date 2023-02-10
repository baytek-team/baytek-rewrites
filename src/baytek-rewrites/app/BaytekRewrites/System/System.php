<?php

namespace BaytekRewrites\System;

use BaytekRewrites\Plugin;
use BaytekRewrites\ViewHandler;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handles localization of the plugin
 */
abstract class System {

	/**
	 * Main plugin instance
	 */
	protected $plugin;

	/**
	 * Create the System object, setting the main plugin
	 * instance and calling the addHooks() method
	 *
	 * @param  BaytekRewrites\Plugin  $instance
	 */
	public function __construct( $instance ) {
		$this->plugin = $instance;
		$this->addHooks();
	}
}
