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
class CCB_Core_Group_Area extends CCB_Core_Taxonomy {

	/**
	 * Name of the taxonomy
	 *
	 * @var   string
	 */
	public $name = 'ccb_core_group_area';

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
				'name' => __( 'Areas', 'ccb-core' ),
				'singular_name' => __( 'Area', 'ccb-core' ),
				'search_items' => __( 'Search Areas', 'ccb-core' ),
				'all_items' => __( 'All Areas', 'ccb-core' ),
				'parent_item' => __( 'Parent Area', 'ccb-core' ),
				'parent_item_colon' => __( 'Parent Area:', 'ccb-core' ),
				'edit_item' => __( 'Edit Area', 'ccb-core' ),
				'update_item' => __( 'Update Area', 'ccb-core' ),
				'add_new_item' => __( 'Add New Area', 'ccb-core' ),
				'new_item_name' => __( 'New Area', 'ccb-core' ),
			],
			'hierarchical' => true,
			'show_admin_column' => true,
			'show_ui' => true,
			'query_var' => true,
			'api_mapping' => 'area', // The field key from the CCB API.
		];
	}

}

new CCB_Core_Group_Area();
