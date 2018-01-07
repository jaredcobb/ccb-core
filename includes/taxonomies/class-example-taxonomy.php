<?php
/**
 * An example implementation of CCB_Core_Taxonomy
 *
 * This file is not included.
 *
 * @link       https://www.wpccb.com
 * @since      1.0.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes/taxonomies
 */

/**
 * An example implementation of CCB_Core_Taxonomy
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes/taxonomies
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class Example_Taxonomy extends CCB_Core_Taxonomy {

	/**
	 * Name of the taxonomy
	 *
	 * @var   string
	 */
	public $name = 'ccb_core_example_taxonomy';

	/**
	 * Object types for this taxonomy
	 *
	 * This may be attached to multiple post types, if needed.
	 *
	 * @var   array
	 */
	public $object_types = [ 'ccb_core_example_post' ];

	/**
	 * Setup the default taxonomy mappings
	 *
	 * @since    1.0.0
	 * @return   array   Default options for register_taxonomy
	 */
	public static function get_taxonomy_args() {
		return [
			'labels' => [
				'name' => 'Example Categories',
				'singular_name' => 'Example Category',
				'search_items' => 'Search Example Categories',
				'all_items' => 'All Example Categories',
				'parent_item' => 'Parent Example Category',
				'parent_item_colon' => 'Parent Example Category:',
				'edit_item' => 'Edit Example Category',
				'update_item' => 'Update Example Category',
				'add_new_item' => 'Add New Example Category',
				'new_item_name' => 'New Example Category',
			],
			'hierarchical' => true,
			'show_admin_column' => true,
			'show_ui' => true,
			'query_var' => true,
			'api_mapping' => 'entity_property_name', // The field key from the CCB API.
		];
	}

}

new Example_Taxonomy();
