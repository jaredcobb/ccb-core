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
class CCB_Core_Group_Tag extends CCB_Core_Taxonomy {

	/**
	 * Name of the taxonomy
	 *
	 * @var   string
	 */
	public $name = 'ccb_core_group_tag';

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
				'name' => __( 'Group Tags', 'ccb-core' ),
				'singular_name' => __( 'Group Tag', 'ccb-core' ),
				'search_items' => __( 'Search Group Tags', 'ccb-core' ),
				'all_items' => __( 'All Group Tags', 'ccb-core' ),
				'parent_item' => __( 'Parent Group Tag', 'ccb-core' ),
				'parent_item_colon' => __( 'Parent Group Tag:', 'ccb-core' ),
				'edit_item' => __( 'Edit Group Tag', 'ccb-core' ),
				'update_item' => __( 'Update Group Tag', 'ccb-core' ),
				'add_new_item' => __( 'Add New Group Tag', 'ccb-core' ),
				'new_item_name' => __( 'New Group Tag', 'ccb-core' ),
			],
			'hierarchical' => false,
			'show_admin_column' => true,
			'show_ui' => true,
			'query_var' => true,
			'api_mapping' => [
				'childcare_provided' => __( 'Childcare Provided', 'ccb-core' ), // The field key from the CCB API.
			],
		];
	}

}

new CCB_Core_Group_Tag();
