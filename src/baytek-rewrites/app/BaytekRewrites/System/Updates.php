<?php

namespace BaytekRewrites\System;

use BaytekRewrites\Plugin;
use BaytekRewrites\ViewHandler;

use WP_Query;
use stdClass;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handles plugin updates
 */

class Updates extends System {
	/**
	 * Track whether we have checked the plugin updates transient
	 */
	protected $checked = false;

	/**
	 * Plugin main file
	 */
	protected $file = 'baytek-rewrites/main.php';

	/**
	 * Remote info URL
	 */
	protected $infoUrl = 'https://github.com/baytek-team/baytek-rewrites/raw/main/dist/info.json';

	/**
	 * Add the plugin's actions and filters for existing content
	 */
	public function addHooks() {
		//Provide plugin API info
		add_filter('plugins_api', [$this, 'filterPluginsApi'], 20, 3);

		//Filter the plugin updates transient to check our private repo
		add_filter('transient_update_plugins', [$this, 'checkForUpdates']);
		add_filter('site_transient_update_plugins', [$this, 'checkForUpdates']);
	}

	/**
	 * Provide plugin API info
	 * 
	 * @param  mixed   $res     The result object or array (default false)
	 * @param  string  $action  The type of information being requested from the Plugin Installation API.
	 * @param  object  $args 	Plugin API arguments.
	 * 
	 * @return object  $res 	The updated result
	 * 
	 * @see https://developer.wordpress.org/reference/hooks/plugins_api/
	 * @see https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
	 */
	public function filterPluginsApi($res, $action, $args) {
		// do nothing if this is not about getting plugin information
		if ( 'plugin_information' !== $action ) {
			return $res;
		}

		// do nothing if it is not our plugin
		if ( $this->file !== $args->slug ) {
			return $res;
		}

		// info.json is the file with the actual plugin information on your server
		$remote = wp_remote_get( 
			$this->infoUrl, 
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json'
				) 
			)
		);

		// do nothing if we don't get the correct response from the server
		if( 
			is_wp_error( $remote )
			|| 200 !== wp_remote_retrieve_response_code( $remote )
			|| empty( wp_remote_retrieve_body( $remote ) )
		) {
			return $res;	
		}

		$remote = json_decode( wp_remote_retrieve_body( $remote ) );
		
		$res = new stdClass();
		$res->name = $remote->name;
		$res->slug = $remote->slug;
		$res->author = $remote->author;
		$res->version = $remote->version;
		$res->tested = $remote->tested;
		$res->requires = $remote->requires;
		$res->requires_php = $remote->requires_php;
		$res->download_link = $remote->download_url;
		$res->trunk = $remote->download_url;
		$res->last_updated = $remote->last_updated;
		
		return $res;
	}

	/**
	 * Handle plugin updates
	 * 
	 * @param  object  $transient
	 * 
	 * @see https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
	 */
	public function checkForUpdates($transient) {
		//Only check once
		if ($this->checked) {
			return $transient;
		}

		//Set the flag
		$this->checked = true;

		//Do the checking
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote = wp_remote_get( 
			$this->infoUrl,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json'
				)
			)
		);

		if ( 
			is_wp_error( $remote )
			|| 200 !== wp_remote_retrieve_response_code( $remote )
			|| empty( wp_remote_retrieve_body( $remote ) )
		) {
			return $transient;
		}

		$remote = json_decode( wp_remote_retrieve_body( $remote ) );
	 
		// your installed plugin version should be on the line below! You can obtain it dynamically of course 
		if (
			$remote
			&& version_compare( Plugin::VERSION, $remote->version, '<' )
			&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<' )
			&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
		) {
			
			$res = new stdClass();
			$res->slug = $remote->slug;
			$res->plugin = $this->file;
			$res->new_version = $remote->version;
			$res->tested = $remote->tested;
			$res->package = $remote->download_url;
			$transient->response[$res->plugin] = $res;
			// $transient->checked[$res->plugin] = $remote->version;
		}
	 
		return $transient;
	}
}
