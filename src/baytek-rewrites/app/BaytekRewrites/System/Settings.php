<?php

namespace BaytekRewrites\System;

use BaytekRewrites\Plugin;
use BaytekRewrites\ViewHandler;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handles capabilities of the site
 */

class Settings extends System {

	/**
	 * Options to save
	 */
	protected $options = [
		'post-parents'
	];

	/**
	 * Add the plugin's actions and filters for existing content
	 */
	public function addHooks() {
		//Add the admin setting page
		add_action('admin_menu', [$this, 'settingsPageMenu']);

		//Init the setting
		add_action('admin_init', [$this, 'settings']);

		//Listen for the settings saving
		add_action('admin_notices', [$this, 'adminNotices']);

		//Admin scripts
		add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);

		//Listen for changes to parent pages
		add_action('update_option_rewrites-post-parents', [$this, 'updatePostParents'], 10, 3);
	}

	/**
	 * Output the menu item for the plugin settings page
	 */
	public function settingsPageMenu() {
		//Add the main generator page
		add_menu_page(
			'Rewrites',
			'Rewrites',
			'edit_posts',
			'rewrites',
			[
				$this,
				'settingsPage'
			],
			'dashicons-migrate',
			80
		);
	}

	/**
	 * Output the settings page
	 */
	public function settingsPage() {
		//Make sure we're in English
		do_action('wpml_switch_language', 'en');

		//Set all the attachment properties to be used in the view
		$args = [
			'post_types' => apply_filters('rewriteable_post_types', []),
			'pages' => get_pages(),
			'rewrites' => (array) get_option('rewrites-post-parents')
		];

		//Include the template
		ViewHandler::render( ViewHandler::getView( 'settings-page.php', 'admin' ), $args);
	}

	/**
	 * Create the settings
	 */
	public function settings() {
		//Register normal settings
		foreach ($this->options as $option) :
			register_setting( 'rewrites', 'rewrites-'.$option );
		endforeach;
	}

	/**
	 * Output admin notice for settings saving
	 */
	public function adminNotices() {
		//Make sure we're in the right place and the settings were updated
		if (isset($_GET['settings-updated']) && isset($_GET['page']) && $_GET['page'] == 'rewrites') {

			//Print the notice
			printf(
				'<div class="%s"><p>%s</p></div>',
				'notice notice-success is-dismissible',
				__('Settings saved.', Plugin::TEXTDOMAIN)
			);
		}
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueueAdminScripts($hook) {
		//Styles and scripts for the settings page
		if ($hook == 'toplevel_page_rewrites') {
			//Enqueue the scripts
			wp_enqueue_script('select2', trailingslashit($this->plugin->getAssetsUrl()).'scripts/select2.full.min.js', ['jquery'], Plugin::VERSION);

			wp_enqueue_script('rewrites', trailingslashit($this->plugin->getAssetsUrl()).'scripts/rewrites.js', ['select2'], Plugin::VERSION);

			//Enqueue the styles
			wp_enqueue_style('select2', trailingslashit($this->plugin->getAssetsUrl()).'styles/select2.min.css', Plugin::VERSION);
		}
	}

	/**
	 * When a post parent ID option changes, update all post parents for that post type
	 * 
	 * @param  mixed   $old  	The old option value
	 * @param  mixed   $new  	The new option value
	 * @param  string  $option  The option name
	 */
	public function updatePostParents($old, $new, $option) {
		global $wpdb;

		//Loop through each post type
		foreach ($new as $type => $value) {
			//Make sure it really has changed
			if ($value == $old[$type]) continue;

			//We don't change the parents of hierarchical post types
			if (is_post_type_hierarchical($type)) continue;

			//If no parent is chosen, then set the new value to 0
			if (empty($value)) $value = 0;

			//Set all the posts of this type to use the new post parent
			$wpdb->update(
				//Table
				$wpdb->posts,
				//Values
				[
					'post_parent' => $value
				],
				//Where
				[
					'post_type' => $type
				],
				//Format
				[
					'%d'
				]
			);
		}
	}
}
