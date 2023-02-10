<?php

namespace BaytekRewrites\System;

use BaytekRewrites\Plugin;
use BaytekRewrites\ViewHandler;

use WP_Query;

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
	 * Add the plugin's actions and filters for existing content
	 */
	public function addHooks() {
		//Filter the plugin updates transient to check our private repo
		add_filter('transient_update_plugins', [$this, 'checkForUpdates']);
		add_filter('site_transient_update_plugins', [$this, 'checkForUpdates']);
	}

	/**
	 * Handle plugin updates
	 * 
	 * @param  mixed  $transient
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
			'https://github.com/baytek-team/baytek-rewrites/raw/main/dist/info.json',
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
			$res->plugin = plugin_basename( __FILE__ ); // it could be just YOUR_PLUGIN_SLUG.php if your plugin doesn't have its own directory
			$res->new_version = $remote->version;
			$res->tested = $remote->tested;
			$res->package = $remote->download_url;
			$transient->response[ $res->plugin ] = $res;
			
			//$transient->checked[$res->plugin] = $remote->version;
		}
	 
		return $transient;
	}
}
