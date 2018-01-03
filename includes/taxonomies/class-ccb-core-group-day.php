<?php
/**
 * Custom taxonomies used in this plugin
 *
 * @link       https://www.wpccb.com
 * @since      1.0.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes/taxonomies
 */

/**
 * Custom taxonomy class used to define custom taxonomies
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes/taxonomies
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Group_Day extends CCB_Core_Taxonomy {

	/**
	 * Name of the taxonomy
	 *
	 * @var   string
	 */
	public $name = 'ccb_core_group_day';

	/**
	 * Object types for this taxonomy
	 *
	 * @var   array
	 */
	public $object_types = [ 'ccb_core_group' ];

	/**
	 * Setup the default taxonomy mappings
	 *
	 * @since    1.0.0
	 * @return   array   Default options for register_taxonomy
	 */
	public static function get_taxonomy_args() {
		return [
			'labels' => [
				'name' => __( 'Days', 'ccb-core' ),
				'singular_name' => __( 'Day', 'ccb-core' ),
				'search_items' => __( 'Search Days', 'ccb-core' ),
				'all_items' => __( 'All Days', 'ccb-core' ),
				'parent_item' => __( 'Parent Day', 'ccb-core' ),
				'parent_item_colon' => __( 'Parent Day:', 'ccb-core' ),
				'edit_item' => __( 'Edit Day', 'ccb-core' ),
				'update_item' => __( 'Update Day', 'ccb-core' ),
				'add_new_item' => __( 'Add New Day', 'ccb-core' ),
				'new_item_name' => __( 'New Day', 'ccb-core' ),
			],
			'hierarchical' => true,
			'show_admin_column' => true,
			'show_ui' => true,
			'query_var' => true,
			'api_mapping' => 'meeting_day', // The field key from the CCB API.
		];
	}

}

new CCB_Core_Group_Day();
