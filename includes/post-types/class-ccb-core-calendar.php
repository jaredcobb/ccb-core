<?php
/**
 * Custom post types used in this plugin
 *
 * @link       https://www.wpccb.com
 * @since      1.0.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes/post-types
 */

/**
 * Calendar Custom Post Type
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes/post-types
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Calendar extends CCB_Core_CPT {

	/**
	 * Name of the post type
	 *
	 * @var   string
	 */
	public $name = 'ccb-core-calendar';

	/**
	 * Setup the default CPT options
	 *
	 * @since    1.0.0
	 * @return   array   Default options for register_post_type
	 */
	public function get_cpt_defaults() {
		return array(
			'labels' => array(
				'name' => __( 'Events', 'ccb-core' ),
				'singular_name' => __( 'Event', 'ccb-core' ),
				'all_items' => __( 'All Events', 'ccb-core' ),
				'add_new' => __( 'Add New', 'ccb-core' ),
				'add_new_item' => __( 'Add New Event', 'ccb-core' ),
				'edit' => __( 'Edit', 'ccb-core' ),
				'edit_item' => __( 'Edit Event', 'ccb-core' ),
				'new_item' => __( 'New Event', 'ccb-core' ),
				'view_item' => __( 'View Event', 'ccb-core' ),
				'search_items' => __( 'Search Events', 'ccb-core' ),
				'not_found' => __( 'Nothing found in the Database.', 'ccb-core' ),
				'not_found_in_trash' => __( 'Nothing found in Trash', 'ccb-core' ),
				'parent_item_colon' => '',
			),
			'description' => __( 'These are the events that are synchronized with your Church Community Builder software.', 'ccb-core' ),
			'public' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'show_ui' => true,
			'show_in_nav_menus' => true,
			'query_var' => true,
			'menu_position' => 8,
			'menu_icon' => 'dashicons-calendar',
			'rewrite' => array( 'slug' => 'events' ),
			'has_archive' => 'events',
			'capability_type' => 'post',
			'hierarchical' => false,
			'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'sticky' ),
		);
	}

}

new CCB_Core_Calendar();
