<?php

namespace BaytekRewrites\Taxonomies;

use BaytekRewrites\Plugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Template for custom meta boxes
 */

abstract class Taxonomy {

	/**
	 * Supported post types for meta box
	 */
	protected $postTypes = [
		'post',
	];

	/**
	 * Taxonomy args
	 */
	protected $labels = [];

	protected $slug = 'custom-taxonomy';
	protected $hierarchical = false;
	protected $show_ui = true;
	protected $show_admin_columm = true;
	protected $update_count_callback = '_update_post_term_count';
	protected $query_var = true;

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

	/**
	 * Set up all class hooks
	 */
	public function addHooks() {
		add_action( 'init', [ $this, 'register' ], 20 );
	}

	/**
	 * Set the labels for the taxonomy
	 */
	public function setLabels() {
	    $this->labels = [
			'name'          	         => $this->getPluralLabel(),
			'singular_name'		         => $this->getSingularLabel(),
			'menu_name'          		 => $this->getPluralLabel(),
			'parent_item' 		  		 => sprintf( __( 'Parent %s', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
			'parent_item_colon' 		  		 => sprintf( __( 'Parent %s:', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
			'all_items' 		  		 => sprintf( __( 'All %s', Plugin::TEXTDOMAIN ), $this->getPluralLabel() ),
			'view_item' 		  		 => sprintf( __( 'View %s', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
			'add_new_item' 		  		 => sprintf( __( 'Add New %s', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
			'add_new'         		     => __( 'Add New', Plugin::TEXTDOMAIN ),
			'update_item' 		  		 => sprintf( __( 'Update %s', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
			'search_items' 		  		 => sprintf( __( 'Search %s', Plugin::TEXTDOMAIN ), $this->getPluralLabel() ),
			'not_found'          		 => __( 'Not Found', Plugin::TEXTDOMAIN ),
			'not_found_in_trash'  		 => __( 'Not Found in Trash', Plugin::TEXTDOMAIN ),
			'new_item_name' 	  		 => sprintf( __( 'New %s', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
			'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', Plugin::TEXTDOMAIN ), $this->getPluralLabel() ),
			'add_or_remove_items'		 => sprintf( __( 'Add or remove %s', Plugin::TEXTDOMAIN ), $this->getPluralLabel() ),
			'choose_from_most_used'		 => sprintf( __( 'Choose from the most used %s', Plugin::TEXTDOMAIN ), $this->getPluralLabel() ),
			'not_found'					 => sprintf( __( 'No %s found', Plugin::TEXTDOMAIN ), $this->getPluralLabel() ),
		];
	}

	/**
	 * Get the singular name of the taxonomy
	 */
	public function getSingularLabel() {
		return __CLASS__;
	}

	/**
	 * Get the plural name of the taxonomy
	 */
	public function getPluralLabel() {
		return __CLASS__.'s';
	}

	/**
	 * Register the taxonomy
	 */
	public function register() {
		$this->setLabels();

		$args = [
			'hierarchical'          => $this->hierarchical,
			'labels'                => $this->labels,
			'show_ui'               => $this->show_ui,
			'show_admin_column'     => $this->show_admin_columm,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => $this->query_var,
			'rewrite'               => [ 'slug' => $this->slug ]
		];

		register_taxonomy( $this->slug, $this->postTypes, $args );
	}
}
