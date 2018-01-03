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
class CCB_Core_Calendar_Event_Type extends CCB_Core_Taxonomy {

	/**
	 * Name of the taxonomy
	 *
	 * @var   string
	 */
	public $name = 'ccb_core_calendar_event_type';

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
				'name' => __( 'Types', 'ccb-core' ),
				'singular_name' => __( 'Type', 'ccb-core' ),
				'search_items' => __( 'Search Types', 'ccb-core' ),
				'all_items' => __( 'All Types', 'ccb-core' ),
				'parent_item' => __( 'Parent Type', 'ccb-core' ),
				'parent_item_colon' => __( 'Parent Type:', 'ccb-core' ),
				'edit_item' => __( 'Edit Type', 'ccb-core' ),
				'update_item' => __( 'Update Type', 'ccb-core' ),
				'add_new_item' => __( 'Add New Type', 'ccb-core' ),
				'new_item_name' => __( 'New Type', 'ccb-core' ),
			],
			'hierarchical' => true,
			'show_admin_column' => true,
			'show_ui' => true,
			'query_var' => true,
			'api_mapping' => 'event_type', // The field key from the CCB API.
		];
	}

}

new CCB_Core_Calendar_Event_Type();
