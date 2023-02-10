<?php

namespace BaytekRewrites\PostTypes;

use \BaytekRewrites\Plugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Template for custom post types
 */

abstract class PostType {

	/**
	 * Main plugin instance
	 */
	protected $plugin;

	/**
	 * Post type arguments
	 */
	protected $slug = 'custom-post';

	protected $labels = [];
	protected $args = [];

	protected $supports = [ 'title', 'editor', 'excerpt', 'thumbnail' ];
	protected $taxonomies = [];
	protected $hierarchical = false;
	protected $public = true;
	protected $show_ui = true;
	protected $show_in_admin_bar = true;
	protected $menu_position = 5;
	protected $menu_icon = 'dashicons-format-gallery';
	protected $can_export = true;
	protected $has_archive = true;
	protected $exclude_from_search = false;
	protected $publicly_queryable = true;
	protected $capability_type = 'post';

	/**
	 * Create the PostType object, setting the main plugin
	 * instance and calling the addHooks() method
	 *
	 * @param  BaytekRewrites\Plugin  $instance
	 */
	public function __construct( $instance ) {
		$this->plugin = $instance;
		$this->setLabels();
		$this->addHooks();
	}

	/**
	 * Set the labels for the post type
	 */
	public function setLabels() {
	    $this->labels = [
			'name'                => $this->getPluralLabel(),
			'singular_name'       => $this->getSingularLabel(),
			'menu_name'           => $this->getPluralLabel(),
			'parent_item'  		  => sprintf( __( 'Parent %s', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
			'parent_item_colon'   => sprintf( __( 'Parent %s:', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
			'all_items'           => sprintf( __( 'All %s', Plugin::TEXTDOMAIN ), $this->getPluralLabel() ),
			'view_item'           => sprintf( __( 'View %s', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
			'add_new_item'        => sprintf( __( 'Add New %s', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
			'add_new'             => __( 'Add New', Plugin::TEXTDOMAIN ),
			'edit_item'           => sprintf( __( 'Edit %s', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
			'update_item' 		  => sprintf( __( 'Update %s', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
			'search_items'        => sprintf( __( 'Search %s', Plugin::TEXTDOMAIN ), $this->getPluralLabel() ),
			'not_found'           => __( 'Not Found', Plugin::TEXTDOMAIN ),
			'not_found_in_trash'  => __( 'Not Found in Trash', Plugin::TEXTDOMAIN ),
			'new_item_name' 	  => sprintf( __( 'New %s', Plugin::TEXTDOMAIN ), $this->getSingularLabel() ),
		];
	}

	/**
	 * Get the singular name of the post type
	 */
	public function getSingularLabel() {
		return __CLASS__;
	}

	/**
	 * Get the plural name of the post type
	 */
	public function getPluralLabel() {
		return __CLASS__.'s';
	}

	/**
	 * Get the description of the post type
	 */
	public function getDescription() {
		return __CLASS__.' post type';
	}

	/**
	 * Set up all class hooks
	 */
	public function addHooks() {
		// Register post type
		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Define labels and register the post type
	 */
	public function register() {
		$this->args = [
			'label'			  	  => $this->getPluralLabel(),
			'description'	  	  => $this->getDescription(),
			'labels'			  => $this->labels,
			'supports'            => $this->supports,
			'taxonomies'          => $this->taxonomies,
			'hierarchical'        => $this->hierarchical,
			'public'              => $this->public,
			'show_ui'             => $this->show_ui,
			'show_in_admin_bar'   => $this->show_in_admin_bar,
			'menu_position'       => $this->menu_position,
			'menu_icon'           => $this->menu_icon,
			'can_export'          => $this->can_export,
			'has_archive'         => $this->has_archive,
			'exclude_from_search' => $this->exclude_from_search,
			'publicly_queryable'  => $this->publicly_queryable,
			'capability_type'     => $this->capability_type,
			'rewrite'			  => [ 'slug' => $this->slug ],
		];

		register_post_type( $this->slug, $this->args );
	}
}
