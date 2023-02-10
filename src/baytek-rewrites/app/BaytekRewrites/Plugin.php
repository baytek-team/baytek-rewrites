<?php

namespace BaytekRewrites;

use BaytekRewrites\System\Rewrites;
use BaytekRewrites\System\Settings;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Main Plugin Class
 */

class Plugin extends BasePlugin {

	/**
	 * Plugin constants
	 */
	const TEXTDOMAIN = 'baytek-rewrites';
	const VERSION = '1.0.4';

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

		// Handle plugin updates
		add_filter('transient_update_plugins', [$this, 'checkForUpdates']);
		add_filter('site_transient_update_plugins', [$this, 'checkForUpdates']);
	}

	/**
	 * Run the plugin's default tasks
	 */
	public function run() {

		// Init system code
		new Rewrites( static::$instance );
		new Settings( static::$instance );

		// Load textdomain
		load_plugin_textdomain( self::TEXTDOMAIN, false, 'baytek-rewrites/resources/assets/languages/' );
	}

	/**
	 * Handle plugin updates
	 * 
	 * @param  mixed  $transient
	 * 
	 * @see https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
	 */
	public function checkForUpdates($transient) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote = wp_remote_get( 
			'https://github.com/baytek-team/baytek-rewrites/tbd/dist/info.json',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json'
				)
			)
		);

		if( 
			is_wp_error( $remote )
			|| 200 !== wp_remote_retrieve_response_code( $remote )
			|| empty( wp_remote_retrieve_body( $remote ) 
		) {
			return $transient;	
		}
		
		$remote = json_decode( wp_remote_retrieve_body( $remote ) );
	 
		// your installed plugin version should be on the line below! You can obtain it dynamically of course 
		if(
			$remote
			&& version_compare( $this->version, $remote->version, '<' )
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
