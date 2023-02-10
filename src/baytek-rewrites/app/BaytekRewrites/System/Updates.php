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
	 * Remote info URL
	 */
	protected $infoUrl = 'https://github.com/baytek-team/baytek-rewrites/raw/main/dist/info.json';

	/**
	 * Cache key
	 */
	protected $cacheKey = 'baytek_rewrites_updates';

	/**
	 * Add the plugin's actions and filters for existing content
	 */
	public function addHooks() {
		//Provide plugin API info
		add_filter('plugins_api', [$this, 'filterPluginsApi'], 20, 3);

		//Filter the plugin updates transient to check our private repo
		// add_filter('transient_update_plugins', [$this, 'checkForUpdates']);
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
		if ('plugin_information' !== $action) {
			return $res;
		}

		// do nothing if it is not our plugin
		if ('baytek-rewrites' !== $args->slug) {
			return $res;
		}

		// info.json is the file with the actual plugin information on your server
		$remote = $this->requestRemoteData();

		if (!$remote) {
			return $res;
		}
		
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
		$res->sections = [
			'description' => $remote->sections->description
		];
		
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
		//Do the checking
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote = $this->requestRemoteData();
	 
		if (
			$remote
			&& version_compare( Plugin::VERSION, $remote->version, '<' )
			&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<' )
			&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
		) {
			
			$res = new stdClass();
			$res->slug = $remote->slug;
			$res->plugin = 'baytek-rewrites/main.php';
			$res->new_version = $remote->version;
			$res->tested = $remote->tested;
			$res->package = $remote->download_url;
			$transient->response[$res->plugin] = $res;
			// $transient->checked[$res->plugin] = $remote->version;
		}
	 
		return $transient;
	}

	/**
	 * Get the remote plugin data and save as a transient option
	 * 
	 * @return  The remote plugin data
	 * 
	 * @see https://github.com/rudrastyh/misha-update-checker/blob/main/misha-update-checker.php
	 */
	protected function requestRemoteData() {
		$remote = get_transient($this->cacheKey);

		if (false === $remote) {
			//Get the data
			$remote = wp_remote_get( 
				$this->infoUrl,
				array(
					'timeout' => 10,
					'headers' => array(
						'Accept' => 'application/json'
					)
				)
			);

			//Validate
			if ( 
				is_wp_error( $remote )
				|| 200 !== wp_remote_retrieve_response_code( $remote )
				|| empty( wp_remote_retrieve_body( $remote ) )
			) {
				return false;
			}

			//Cache
			set_transient($this->cacheKey, $remote, DAY_IN_SECONDS);
		}

		//Parse and return
		return json_decode(wp_remote_retrieve_body($remote));
	}
}
