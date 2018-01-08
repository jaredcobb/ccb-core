<?php
/**
 * An example implementation of CCB_Core_CPT
 *
 * This file is not included.
 *
 * @link       https://www.wpccb.com
 * @since      1.0.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes/post-types
 */

/**
 * An example implementation of CCB_Core_CPT
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes/post-types
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class Example_Post_Type extends CCB_Core_CPT {

	/**
	 * Name of the post type
	 *
	 * @var   string
	 */
	public $name = 'ccb_core_example_post';

	/**
	 * Use the constructor to add any actions / filters.
	 */
	public function __construct() {

		// There are several actions and filters that allow you to run additional code during
		// the synchronization process. If you need to hook into them, define them here.
		add_action( 'ccb_core_after_insert_update_post', [ $this, 'my_callback_after_post_inserted' ], 10, 5 );

		parent::__construct();
	}

	/**
	 * Setup the custom post type args
	 *
	 * @since    1.0.0
	 * @return   array $args for register_post_type
	 */
	public function get_post_args() {

		return [
			'labels' => [
				'name' => 'Example Posts',
				'singular_name' => 'Example Post',
				'all_items' => 'All Example Posts',
				'add_new' => 'Add New',
				'add_new_item' => 'Add New Example Post',
				'edit' => 'Edit',
				'edit_item' => 'Edit Example Post',
				'new_item' => 'New Example Post',
				'view_item' => 'View Example Post',
				'search_items' => 'Search Example Posts',
				'not_found' => 'Nothing found in the Database.',
				'not_found_in_trash' => 'Nothing found in Trash',
			],
			'description' => 'These are Example Posts that came from CCB',
			'public' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'show_ui' => true,
			'show_in_nav_menus' => false,
			'query_var' => true,
			'menu_position' => 8,
			'rewrite' => [ 'slug' => 'examples' ],
			'has_archive' => 'examples',
			'capability_type' => 'post',
			'hierarchical' => false,
			'supports' => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'sticky' ],
		];

	}

	/**
	 * Configure the options that users are allowed to set
	 *
	 * @since    1.0.0
	 * @param    array $settings The settings definitions.
	 * @return   array
	 */
	public function get_post_settings_definitions( $settings ) {
		// This method is required, but you do not need to actually create
		// a settings page or settings fields if you don't need them. Just
		// return the settings in that case.
		return $settings;
	}

	/**
	 * Define the mapping of CCB API fields to the Post fields
	 *
	 * @since    1.0.0
	 * @param    array $maps A collection of mappings from the API to WordPress.
	 * @return   array
	 */
	public function get_post_api_map( $maps ) {

		$maps[ $this->name ] = [
			'service' => 'ccb_service_name', // This becomes the `srv` URL parameter in the API request.
			'data' => [
				'another_parameter' => true, // These are any additional URL parameters that need to be sent with the API request.
				'yet_another_parameter' => 'abc123',
			],
			'nodes' => [ 'elements', 'element' ], // The path from <response> all the way to (and including) the CCB Entity.
			'fields' => [
				'some_property' => 'post_title', // Map to a Post Title from an entity's property.
				'another_property' => 'post_content', // Map to any other WP_Post property by name.
				'a_third_property' => 'post_meta', // Map to `post_meta` to have this saved as post meta.
			],
		];

		return $maps;
	}

	/**
	 * Run additional logic after a post gets inserted
	 *
	 * @since    1.0.0
	 *
	 * @param    SimpleXML $entity The entity object.
	 * @param    array     $settings The settings array for the import.
	 * @param    array     $args The `wp_insert_post` args.
	 * @param    string    $post_type The current post type.
	 * @param    int       $post_id The WordPress post id of this post.
	 * @return   void
	 */
	public function my_callback_after_post_inserted( $entity, $settings, $args, $post_type, $post_id ) {
		// If this is a callback for this post type...
		// phpcs:ignore
		if ( $this->name === $post_type ) {
			// Perhaps you want to inspect the new post after it gets inserted. You now have access
			// to the original Entity (XML object from CCB), the post id, etc. You can now alter the post
			// with any custom logic. For example, on the CCB_Core_Group post type we check whether the
			// post should also have a featured image (and we download the group image and attach it).
		}
	}
}

new Example_Post_Type();
