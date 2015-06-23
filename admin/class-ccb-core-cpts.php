<?php
/**
 * Custom post types used in this plugin
 *
 * @link       http://jaredcobb.com/ccb-core
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 */

/**
 * Custom post types used in this plugin
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_CPTs extends CCB_Core_Plugin {

	/**
	 * The options we should use to register the groups CPT
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      array    $groups_cpt_options
	 */
	protected $groups_cpt_options = array();

	/**
	 * The options we should use to register the calendar CPT
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      array    $calendar_cpt_options
	 */
	protected $calendar_cpt_options = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.9.0
	 */
	public function __construct() {

		parent::__construct();

	}

	/**
	 * Determine which CCB custom post types should be registered
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function initialize() {

		$settings = get_option( $this->plugin_settings_name );

		if ( isset( $settings['groups-enabled'] ) && $settings['groups-enabled'] == 1 ) {

			$this->groups_cpt_options['name'] = ( empty( $settings['groups-name'] ) ? 'Groups' : $settings['groups-name'] );
			$this->groups_cpt_options['slug'] = ( empty( $settings['groups-slug'] ) ? 'groups' : $settings['groups-slug'] );
			$this->groups_cpt_options['singular_name'] = rtrim( $this->groups_cpt_options['name'], 's' ); // this is ghetto
			$this->groups_cpt_options['exclude_from_search'] = ( $settings['groups-exclude-from-search'] == 'yes' ? true : false );
			$this->groups_cpt_options['publicly_queryable'] = ( $settings['groups-publicly-queryable'] == 'yes' ? true : false );
			$this->groups_cpt_options['show_ui'] = ( $settings['groups-show-ui'] == 'yes' ? true : false );
			$this->groups_cpt_options['show_in_nav_menus'] = ( $settings['groups-show-in-nav-menus'] == 'yes' ? true : false );

			$this->register_groups();

		}

		if ( isset( $settings['calendar-enabled'] ) && $settings['calendar-enabled'] == 1 ) {

			$this->calendar_cpt_options['name'] = ( empty( $settings['calendar-name'] ) ? 'Events' : $settings['calendar-name'] );
			$this->calendar_cpt_options['slug'] = ( empty( $settings['calendar-slug'] ) ? 'events' : $settings['calendar-slug'] );
			$this->calendar_cpt_options['singular_name'] = rtrim( $this->calendar_cpt_options['name'], 's' ); // this is ghetto
			$this->calendar_cpt_options['exclude_from_search'] = ( $settings['calendar-exclude-from-search'] == 'yes' ? true : false );
			$this->calendar_cpt_options['publicly_queryable'] = ( $settings['calendar-publicly-queryable'] == 'yes' ? true : false );
			$this->calendar_cpt_options['show_ui'] = ( $settings['calendar-show-ui'] == 'yes' ? true : false );
			$this->calendar_cpt_options['show_in_nav_menus'] = ( $settings['calendar-show-in-nav-menus'] == 'yes' ? true : false );

			$this->register_calendar();

		}
	}

	/**
	 * Setup the CCB Groups custom post type and its taxonomies
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function register_groups() {

		register_post_type( $this->plugin_name . '-groups',
			array( 'labels' =>
				array(
					'name' => $this->groups_cpt_options['name'],
					'singular_name' => $this->groups_cpt_options['singular_name'],
					'all_items' => __( 'All ' . $this->groups_cpt_options['name'], $this->plugin_name ),
					'add_new' => __( 'Add New', $this->plugin_name ),
					'add_new_item' => __( 'Add New ' . $this->groups_cpt_options['singular_name'], $this->plugin_name ),
					'edit' => __( 'Edit', $this->plugin_name ),
					'edit_item' => __( 'Edit ' . $this->groups_cpt_options['name'], $this->plugin_name ),
					'new_item' => __( 'New ' . $this->groups_cpt_options['singular_name'], $this->plugin_name ),
					'view_item' => __( 'View ' . $this->groups_cpt_options['singular_name'], $this->plugin_name ),
					'search_items' => __( 'Search ' . $this->groups_cpt_options['singular_name'], $this->plugin_name ),
					'not_found' =>  __( 'Nothing found in the Database.', $this->plugin_name ),
					'not_found_in_trash' => __( 'Nothing found in Trash', $this->plugin_name ),
					'parent_item_colon' => ''
				),
				'description' => __( 'These are the groups that are synchronized with your Church Community Builder software.', $this->plugin_name ),
				'public' => true,
				'publicly_queryable' => $this->groups_cpt_options['publicly_queryable'],
				'exclude_from_search' => $this->groups_cpt_options['exclude_from_search'],
				'show_ui' => $this->groups_cpt_options['show_ui'],
				'show_in_nav_menus' => $this->groups_cpt_options['show_in_nav_menus'],
				'query_var' => true,
				'menu_position' => 8,
				'menu_icon' => 'dashicons-groups',
				'rewrite' => array( 'slug' => $this->groups_cpt_options['slug'] ),
				'has_archive' => $this->groups_cpt_options['slug'],
				'capability_type' => 'post',
				'hierarchical' => false,
				'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'sticky' ),
			)
		);

		$groups_taxonomies = self::get_groups_taxonomy_map();
		foreach ( $groups_taxonomies as $taxonomy_name=>$taxonomy ) {
			$taxonomy_options = array(
				'hierarchical' => $taxonomy['hierarchical'],
				'labels' => array(
					'name' => $taxonomy['name_plural'],
					'singular_name' => $taxonomy['name'],
					'search_items' =>  "Search {$taxonomy['name_plural']}",
					'all_items' => "All {$taxonomy['name_plural']}",
					'parent_item' => "Parent {$taxonomy['name']}",
					'parent_item_colon' => "Parent {$taxonomy['name']}:",
					'edit_item' => "Edit {$taxonomy['name']}",
					'update_item' => "Update {$taxonomy['name']}",
					'add_new_item' => "Add New {$taxonomy['name']}",
					'new_item_name' => "New {$taxonomy['name']}"
				),
				'show_admin_column' => true,
				'show_ui' => true,
				'query_var' => true,
			);

			register_taxonomy( $taxonomy_name, "{$this->plugin_name}-groups", $taxonomy_options );
		}

	}

	/**
	 * Setup the CCB Events custom post type and its taxonomies
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function register_calendar() {

		register_post_type( $this->plugin_name . '-calendar',
			array( 'labels' =>
				array(
					'name' => $this->calendar_cpt_options['name'],
					'singular_name' => $this->calendar_cpt_options['singular_name'],
					'all_items' => __( 'All ' . $this->calendar_cpt_options['name'], $this->plugin_name ),
					'add_new' => __( 'Add New', $this->plugin_name ),
					'add_new_item' => __( 'Add New ' . $this->calendar_cpt_options['singular_name'], $this->plugin_name ),
					'edit' => __( 'Edit', $this->plugin_name ),
					'edit_item' => __( 'Edit ' . $this->calendar_cpt_options['name'], $this->plugin_name ),
					'new_item' => __( 'New ' . $this->calendar_cpt_options['singular_name'], $this->plugin_name ),
					'view_item' => __( 'View ' . $this->calendar_cpt_options['singular_name'], $this->plugin_name ),
					'search_items' => __( 'Search ' . $this->calendar_cpt_options['singular_name'], $this->plugin_name ),
					'not_found' =>  __( 'Nothing found in the Database.', $this->plugin_name ),
					'not_found_in_trash' => __( 'Nothing found in Trash', $this->plugin_name ),
					'parent_item_colon' => ''
				),
				'description' => __( 'These are the calendar that are synchronized with your Church Community Builder software.', $this->plugin_name ),
				'public' => true,
				'publicly_queryable' => $this->calendar_cpt_options['publicly_queryable'],
				'exclude_from_search' => $this->calendar_cpt_options['exclude_from_search'],
				'show_ui' => $this->calendar_cpt_options['show_ui'],
				'show_in_nav_menus' => $this->calendar_cpt_options['show_in_nav_menus'],
				'query_var' => true,
				'menu_position' => 8,
				'menu_icon' => 'dashicons-calendar',
				'rewrite' => array( 'slug' => $this->calendar_cpt_options['slug'] ),
				'has_archive' => $this->calendar_cpt_options['slug'],
				'capability_type' => 'post',
				'hierarchical' => false,
				'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'sticky' ),
			)
		);

		$calendar_taxonomies = self::get_calendar_taxonomy_map();
		foreach ( $calendar_taxonomies as $taxonomy_name=>$taxonomy ) {
			$taxonomy_options = array(
				'hierarchical' => $taxonomy['hierarchical'],
				'labels' => array(
					'name' => $taxonomy['name_plural'],
					'singular_name' => $taxonomy['name'],
					'search_items' =>  "Search {$taxonomy['name_plural']}",
					'all_items' => "All {$taxonomy['name_plural']}",
					'parent_item' => "Parent {$taxonomy['name']}",
					'parent_item_colon' => "Parent {$taxonomy['name']}:",
					'edit_item' => "Edit {$taxonomy['name']}",
					'update_item' => "Update {$taxonomy['name']}",
					'add_new_item' => "Add New {$taxonomy['name']}",
					'new_item_name' => "New {$taxonomy['name']}"
				),
				'show_admin_column' => true,
				'show_ui' => true,
				'query_var' => true,
			);

			register_taxonomy( $taxonomy_name, "{$this->plugin_name}-calendar", $taxonomy_options );
		}

	}

	/**
	 * Helper method to hold a map of structure from the groups custom post
	 * type custom fields to the API schema
	 *
	 * @static
	 * @access    public
	 * @return    array
	 */
	public static function get_groups_custom_fields_map() {
		return array(
			'group_image_url' => array(
				'api_mapping' => 'image',
				'data_type' => 'string',
			),
			'group_leader' => array(
				'api_mapping' => 'main_leader',
				'data_type' => 'object',
				'child_object' => array(
					'leader_full_name' => array(
						'api_mapping' => 'full_name',
						'data_type' => 'string'
					),
					'leader_email' => array(
						'api_mapping' => 'email',
						'data_type' => 'string'
					)
				)
			),
			'group_calendar_feed' => array(
				'api_mapping' => 'calendar_feed',
				'data_type' => 'string',
			),
		);
	}

	/**
	 * Helper method to hold a map of structure from the calendar custom post
	 * type custom fields to the API schema
	 *
	 * @static
	 * @access    public
	 * @return    array
	 */
	public static function get_calendar_custom_fields_map() {
		return array(
			'calendar_date' => array(
				'api_mapping' => 'date',
				'data_type' => 'string',
			),
			'calendar_start_time' => array(
				'api_mapping' => 'start_time',
				'data_type' => 'string',
			),
			'calendar_end_time' => array(
				'api_mapping' => 'end_time',
				'data_type' => 'string',
			),
			'calendar_duration' => array(
				'api_mapping' => 'event_duration',
				'data_type' => 'int',
			),
		);
	}

	/**
	 * Helper method to hold a map of structure from the groups custom post
	 * type taxonomies to the API schema
	 *
	 * @static
	 * @access    public
	 * @return    array
	 */
	public static function get_groups_taxonomy_map() {

		return array(
			'group_areas' => array(
				'name' => 'Area',
				'name_plural' => 'Areas',
				'hierarchical' => true,
				'api_mapping' => 'area'
			),
			'group_days' => array(
				'name' => 'Day',
				'name_plural' => 'Days',
				'hierarchical' => true,
				'api_mapping' => 'meeting_day'
			),
			'group_types' => array(
				'name' => 'Type',
				'name_plural' => 'Types',
				'hierarchical' => true,
				'api_mapping' => 'group_type'
			),
			'group_times' => array(
				'name' => 'Time',
				'name_plural' => 'Times',
				'hierarchical' => true,
				'api_mapping' => 'meeting_time'
			),
			'group_departments' => array(
				'name' => 'Department',
				'name_plural' => 'Departments',
				'hierarchical' => true,
				'api_mapping' => 'department'
			),
			'group_tags' => array(
				'name' => 'Group Tag',
				'name_plural' => 'Group Tags',
				'hierarchical' => false,
				'api_mapping' => array(
					'childcare_provided' => 'Childcare Provided'
				)
			),
		);
	}

	/**
	 * Helper method to hold a map of structure from the events custom post
	 * type taxonomies to the API schema
	 *
	 * @static
	 * @access    public
	 * @return    array
	 */
	public static function get_calendar_taxonomy_map() {

		return array(
			'calendar_event_type' => array(
				'name' => 'Type',
				'name_plural' => 'Types',
				'hierarchical' => true,
				'api_mapping' => 'event_type'
			),
			'calendar_group_name' => array(
				'name' => 'Group Name',
				'name_plural' => 'Group Names',
				'hierarchical' => true,
				'api_mapping' => 'group_name'
			),
			'calendar_grouping_name' => array(
				'name' => 'Grouping Name',
				'name_plural' => 'Grouping Names',
				'hierarchical' => true,
				'api_mapping' => 'grouping_name'
			),
		);
	}

}
