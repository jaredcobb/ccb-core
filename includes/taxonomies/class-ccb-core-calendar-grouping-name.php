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
class CCB_Core_Calendar_Grouping_Name extends CCB_Core_Taxonomy {

	/**
	 * Name of the taxonomy
	 *
	 * @var   string
	 */
	public $name = 'ccb_core_calendar_grouping_name';

	/**
	 * Object types for this taxonomy
	 *
	 * @var   array
	 */
	public $object_types = [ 'ccb_core_calendar' ];

	/**
	 * Setup the default taxonomy mappings
	 *
	 * @since    1.0.0
	 * @return   array   Default options for register_taxonomy
	 */
	public static function get_taxonomy_args() {
		return [
			'labels' => [
				'name' => __( 'Grouping Names', 'ccb-core' ),
				'singular_name' => __( 'Grouping Name', 'ccb-core' ),
				'search_items' => __( 'Search Grouping Names', 'ccb-core' ),
				'all_items' => __( 'All Grouping Names', 'ccb-core' ),
				'parent_item' => __( 'Parent Grouping Name', 'ccb-core' ),
				'parent_item_colon' => __( 'Parent Grouping Name:', 'ccb-core' ),
				'edit_item' => __( 'Edit Grouping Name', 'ccb-core' ),
				'update_item' => __( 'Update Grouping Name', 'ccb-core' ),
				'add_new_item' => __( 'Add New Grouping Name', 'ccb-core' ),
				'new_item_name' => __( 'New Grouping Name', 'ccb-core' ),
			],
			'hierarchical' => true,
			'show_admin_column' => true,
			'show_ui' => true,
			'query_var' => true,
			'api_mapping' => 'grouping_name', // The field key from the CCB API.
		];
	}

}

new CCB_Core_Calendar_Grouping_Name();
