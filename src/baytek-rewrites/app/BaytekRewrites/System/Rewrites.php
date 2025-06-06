<?php

namespace BaytekRewrites\System;

use BaytekRewrites\Plugin;
use BaytekRewrites\ViewHandler;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handles capabilities of the site
 */

class Rewrites extends System {
	/**
	 * Post parents
	 */
	protected $parents = null;

	/**
	 * Post types with parents set
	 */
	protected $parented = null;

	/**
	 * Site URL
	 */
	protected $siteUrl = '';

	/**
	 * Wordpress' supported default feeds.
	 *
	 * @var string[]
	 */
	public $feeds = ['feed', 'rdf', 'rss', 'rss2', 'atom'];

	/**
	 * Wordpress' default language
	 */
	public $defaultLang = ''; 

	/**
	 * Add the plugin's actions and filters for existing content
	 */
	public function addHooks() {
		//Need time for WPML to exist
		add_action('wp_loaded', [$this, 'setupRewrites']);

		//Filter Yoast breadcrumbs
		add_filter('wpseo_breadcrumb_links', [$this, 'filterYoastBreadcrumbLinks']);
	}

	/**
	 * Set up all the permalink filters and such
	 */
	public function setupRewrites() {
		//Filter list of post parents, allowing for translations
		add_filter('rewrite_post_type_parents', [$this, 'filterRewritePostTypeParents']);

		//Set the post parent option to avoid repeat requests
		$this->setCommonValues();

		//Filter list of rewriteable post types
		add_filter('rewriteable_post_types', [$this, 'getRewriteablePostTypes']);

		//Filter the post permalinks
		add_filter('post_type_link', [$this, 'filterPermalinks'], 10, 4);
		add_filter('post_link', [$this, 'filterPermalinks'], 10, 4);

		//Listen for post save of applicable post types and update post parent
		add_action('save_post', [$this, 'saveAdditionalPostData'], 10, 3);

		//Parse the request and check for applicable post types
		add_action('parse_request', [$this, 'parseRequestForPosts']);

		//Get post parent for requested post type
		add_filter('rewrite_get_post_type_parent_id', [$this, 'getPostTypeParentId'], 10, 2);
	}

	/**
	 * Set the common rewrite values so we only need to query them once
	 */
	protected function setCommonValues() {
		//Parents
		$this->parents = apply_filters('rewrite_post_type_parents', (array) get_option('rewrites-post-parents'));

		//Post types that have parents set
		$this->parented = array_keys(array_filter($this->parents));

		//Site URL
		$this->siteUrl = trailingslashit((string) get_site_url());

		//Default language
		$this->defaultLang = apply_filters('wpml_default_language', 'en');
	}

	/**
	 * Get the IDs of the post parents in the correct language
	 * 
	 * @param  array  $post_parents
	 * 
	 * @return array  $post_parents  The updated list of parent IDs
	 */
	public function filterRewritePostTypeParents($post_parents = []) {
		//Check language
		$lang = apply_filters('wpml_current_language', $this->defaultLang);

		//See if we can find the parents in the current language
		if ($lang != $this->defaultLang) {
			foreach ($post_parents as $type => $parent_id) {
				$translated_id = apply_filters('wpml_object_id', $parent_id, 'page', true, $lang);

				//If we found a translation, make the swap
				if ($translated_id) {
					$post_parents[$type] = $translated_id;
				}
			}
		}

		return $post_parents;
	}

	/**
	 * Get all the rewriteable post types
	 * 
	 * @param  array  $post_types
	 * 
	 * @return array  $post_types  The array of rewriteable post types
	 */
	public function getRewriteablePostTypes($post_types = []) {
		//Get custom post types
		$post_types = (array) get_post_types([
			'public' => true,
			'_builtin' => false,
		]);

		//Prepend posts
		array_unshift($post_types, 'post');

		//Return
		return $post_types;
	}

	/**
	 * Filter the post/post type permalinks
	 * 
	 * @param  string  	$post_link  The default URL of the post
	 * @param  WP_Post  $post  		The post
	 * @param  bool  	$leavename  Whether to keep the post name
	 * @param  bool  	$sample  	Whether this is a sample permalink
	 * 
	 * @return string   $post_link  The updated URL
	 */
	public function filterPermalinks($post_link, $post, $leavename, $sample = false) {
		// If draft post, do not alter link
		if (get_post_status($post) == 'draft' ) {
			return $post_link;
		}

		//Only filter posts we've set the parent for
		if (isset($this->parents[$post->post_type]) && !empty($this->parents[$post->post_type])) {
			//Get hierarchical details
			$is_hierarchical = is_post_type_hierarchical($post->post_type);
			$ancestor = null;
			$parent_id = apply_filters('baytek_rewrites_parent_id', $this->parents[$post->post_type], $post);

			//Get language details
			$lang = apply_filters('wpml_element_language_code', $this->defaultLang, ['element_id' => $post->ID, 'element_type' => $post->post_type]);

			//Fallback
			if (empty($lang)) {
				$lang = $this->defaultLang;
			}

			//Make sure parent is set correctly (flat post types only)
			if (!$is_hierarchical) {
				$post->post_parent = $parent_id;
			}
			else {
				$ancestor = $parent_id;
			}

			//Make sure the parent language matches the content language
			$parent_lang = apply_filters('wpml_element_language_code', $this->defaultLang, ['element_id' => $this->parents[$post->post_type], 'element_type' => 'page']);
			if ($lang != $parent_lang) {
				//Get the translated parent
				$translated_id = apply_filters('wpml_object_id', $parent_id, 'page', true, $lang);

				//Reset it (flat post types only)
				if (!$is_hierarchical) {
					$post->post_parent = $translated_id;
				}
				else {
					$ancestor = $translated_id;
				}
			}

			//Figure out permalink
			$post_link = sprintf(
				'%s%s%s%s',
				$this->siteUrl,
				$lang == $this->defaultLang ? '' : trailingslashit($lang),
				$is_hierarchical && $ancestor != null ? trailingslashit(trim(get_page_uri($ancestor))) : '',
				trailingslashit(trim(get_page_uri($post), '/'))
			);
		}

		return $post_link;
	}

	/**
	 * On save, also update the posts's post parent if applicable
	 * 
	 * @param  int  	$post_id  The ID of the post being saved
	 * @param  WP_Post  $post  	  The post being saved
	 * @param  bool  	$update   Whether this is an update
	 */
	public function saveAdditionalPostData($post_id, $post, $update) {
		//Check if we have a post parent available
		if (!isset($this->parents[$post->post_type]) || empty($this->parents[$post->post_type])) return;

		//Don't replace post parent of a hierarchical post type
		if (is_post_type_hierarchical($post->post_type)) return;

		//Prepare the array of data
		$data = [
			'ID' => $post_id
		];

		//Get the page that has been selected as the post parent
		$pages = get_posts([
			'post_type' => 'page',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'fields' => 'ids',
			'page_id' => $this->parents[$post->post_type]
		]);

		//If we don't have a valid parent, abort
		if (empty($pages)) return;

		//Allow filtering
		$parent_id = apply_filters('baytek_rewrites_parent_id', $pages[0], $post);

		$data['post_parent'] = $parent_id;

		//Remove this hook and update the parent
		remove_action('save_post', [$this, 'saveAdditionalPostData']);

		wp_update_post($data);

		//Put the hook back
		add_action('save_post', [$this, 'saveAdditionalPostData'], 10, 3);
	}

	/**
	 * Check the parsed query to see if it might actually be a post/type
	 * 
	 * @param  WP_Query  $query  The Wordpress environment instance
	 * 
	 * @return void 			 Query is passed in by reference
	 */
	public function parseRequestForPosts($query) {
		//See if we have any post parents set for any post types
		if (empty($this->parented)) return;

		//Strip query string
		$stripped = strtok($query->request, '?');

		//See if there is a post
		$requested = explode('/', $stripped);
		$post_name = array_pop($requested);
		$feed = null;

		//Handle feeds
		if (in_array($post_name, $this->feeds)) {
			$feed = $post_name;
			$post_name = array_pop($requested);
		}

		//Make sure we are even looking at a thing
		if (empty($post_name)) return;

		//Check if any posts match
		$posts = get_posts([
			'post_type' => 'any',
			'name' => $post_name,
			'numberposts' => -1,
			'suppress_filters' => false
		]);

		//If no matches, then carry on
		if (empty($posts)) {
			return;
		}
		//If exactly one match, then use that post type
		else if (count($posts) == 1) {
			//If it's not a parented post type, don't interfere
			if (!in_array($posts[0]->post_type, $this->parented)) {
				return;
			}

			//Otherwise, it's a fair match
			$query->query_vars = [
				'post_type' => $posts[0]->post_type,
				// 'name' => $post_name
				'p' => $posts[0]->ID
			];
		}
		//If more than one match, look at the parent post name
		else {
			//Let's compare permalinks, that should be the easiest thing
			$full_requested = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

			foreach ($posts as $post) {
				$post_url = trim(parse_url(get_permalink($post->ID), PHP_URL_PATH), '/');

				if ($post_url == $full_requested) {
					$posts = [$post];
					break;
				}
			}

			//Do this other stuff if somehow we weren't able to match on the permalink idk why we wouldn't though
			if (count($posts) > 1) {
				$parent_name = array_pop($requested);

				//If we have a parent name, we can check it
				if (!empty($parent_name)) {
					$parents = get_posts([
						'post_type' => 'page',
						'name' => $parent_name,
						'numberposts' => 1,
						'fields' => 'ids',
						'suppress_filters' => false
					]);

					//If we found parents, we can look for a match
					if (!empty($parents)) {
						$post_parent = $parents[0];

						//Match post_parent to the parent ID we found
						$matches = array_filter($posts, function($post) use ($post_parent) {
							return $post->post_parent == $post_parent;
						});

						//If we have matches, we can use these in the query
						if (!empty($matches)) {
							$posts = array_values($matches); //array_values reindexes the array, so we always have a 0th element
						}
						//Hierarchical posts don't have post parents overwritten, so check for a match on the parents using IDs and URLs
						else {
							//Initial post type
							$found_post_type = $posts[0]->post_type;

							//Look for matches in our set parents
							foreach ($this->parents as $post_type => $parent_id) {
								if ($parent_id == $parents[0]) {
									$found_post_type = $post_type;
									break;
								}
							}

							//Now match against the hopefully correct new post type
							$matches = array_filter($posts, function($post) use ($found_post_type) {
								return $post->post_type == $found_post_type;
							});

							//If we have matches, we can use these in the query
							if (!empty($matches)) {
								$posts = array_values($matches); //array_values reindexes the array, so we always have a 0th element
							}
						}
					}
					//If the parents aren't one of our pages, this could be hierarchical post type - so check the URLs
					else {
						$post_parts = explode('/', $full_requested);

						//Remove the post name
						array_pop($post_parts);

						foreach ($posts as $post) {
							if ($post->post_parent) {
								$parent_url = trim(parse_url(get_permalink($post->post_parent), PHP_URL_PATH), '/');
								$parent_parts = explode('/', $parent_url);

								if ($parent_parts == $post_parts) {
									$posts = [$post];
									break;
								}
							}
						}
					}
				}

				//If it's not a parented post type, don't interfere
				if (!in_array($posts[0]->post_type, $this->parented)) {
					return;
				}
			}

			//Otherwise, use the first result, whether we find a permalink or parent match or not
			$query->query_vars = [
				'post_type' => $posts[0]->post_type,
				// 'name' => $post_name
				'p' => $posts[0]->ID
			];
		}

		//See if we found a feed
		if ($feed) {
			$query->query_vars['feed'] = $feed;
		}
	}

	/**
	 * Filter Yoast breadcrumbs to use post parent hierarchy
	 *
	 * @param  array  $crumbs  The array of page IDs
	 * 
	 * @return array  $crumbs  The updated page IDs
	 */
	public function filterYoastBreadcrumbLinks($crumbs) {
		global $post;

		//Make sure we have a post we can use
		if (!is_singular() || !$post || !isset($this->parents[$post->post_type]) || empty($this->parents[$post->post_type])) {
			return $crumbs;
		}

		//Get the ancestors
		$ancestors = array_reverse(get_post_ancestors($this->parents[$post->post_type]));

		//Add nodes for each post parent ancestor
		$new_crumbs = array(reset($crumbs));

		foreach ($ancestors as $ancestor) {
			array_push(
				$new_crumbs, [
					'id' => $ancestor,
					'url' => get_permalink($ancestor),
					'text' => get_the_title($ancestor)
				]
			);
		}

		//Add node for post parent
		array_push(
			$new_crumbs, [
				'id' => $this->parents[$post->post_type],
				'url' => get_permalink($this->parents[$post->post_type]),
				'text' => get_the_title($this->parents[$post->post_type])
			]
		);

		//Finish building new crumbs
		array_push($new_crumbs, end($crumbs));

		//Set and return
		$crumbs = $new_crumbs;

		return $crumbs;
	}

	/**
	 * Get the post type parent ID for the requested post type
	 * 
	 * @param  int     $parent_id  The parent ID
	 * @param  string  $post_type  The requested post type
	 * 
	 * @return int 	   $parent_id  The updated parent ID, if we have it
	 */
	public function getPostTypeParentId($parent_id, $post_type) {
		//If it's set in the array, update the value
		if (isset($this->parents[$post_type])) {
			$parent_id = $this->parents[$post_type];
		}

		return $parent_id;
	}
}
