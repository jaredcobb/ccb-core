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
 * Group Custom Post Type
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes/post-types
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Group extends CCB_Core_CPT {

	/**
	 * Name of the post type
	 *
	 * @var   string
	 */
	public $name = 'ccb_core_group';

	/**
	 * Initialize the class
	 */
	public function __construct() {
		add_filter( 'ccb_core_synchronizer_entity_insert_allowed', [ $this, 'entity_insert_update_allowed' ], 10, 4 );
		add_filter( 'ccb_core_synchronizer_entity_update_allowed', [ $this, 'entity_insert_update_allowed' ], 10, 4 );
		add_action( 'ccb_core_after_insert_update_post', [ $this, 'attach_group_image' ], 10, 5 );

		$options = CCB_Core_Helpers::instance()->get_options();
		$this->enabled = ! empty( $options['groups_enabled'] ) ? true : false;
		parent::__construct();
	}

	/**
	 * Setup the custom post type args
	 *
	 * @since    1.0.0
	 * @return   array $args for register_post_type
	 */
	public function get_post_args() {

		$options = CCB_Core_Helpers::instance()->get_options();
		$plural = ! empty( $options['groups_name'] ) ? $options['groups_name'] : __( 'Groups', 'ccb-core' );
		$singular = ! empty( $options['groups_name_singular'] ) ? $options['groups_name_singular'] : __( 'Group', 'ccb-core' );
		$rewrite = ! empty( $options['groups_slug'] ) ? [ 'slug' => sanitize_title( $options['groups_slug'] ) ] : [ 'slug' => 'groups' ];
		$has_archive = ! empty( $options['groups_slug'] ) ? sanitize_title( $options['groups_slug'] ) : 'groups';
		$exclude_from_search = ! empty( $options['groups_exclude_from_search'] ) && 'yes' === $options['groups_exclude_from_search'] ? true : false;
		$publicly_queryable = ! empty( $options['groups_publicly_queryable'] ) && 'no' === $options['groups_publicly_queryable'] ? false : true;
		$show_ui = ! empty( $options['groups_show_ui'] ) && 'no' === $options['groups_show_ui'] ? false : true;
		$show_in_nav_menus = ! empty( $options['groups_show_in_nav_menus'] ) && 'yes' === $options['groups_show_in_nav_menus'] ? true : false;

		return [
			'labels' => [
				'name' => $plural,
				'singular_name' => $singular,
				'all_items' => sprintf( __( 'All %s', 'ccb-core' ), $plural ),
				'add_new' => __( 'Add New', 'ccb-core' ),
				'add_new_item' => sprintf( __( 'Add New %s', 'ccb-core' ), $singular ),
				'edit' => __( 'Edit', 'ccb-core' ),
				'edit_item' => sprintf( __( 'Edit %s', 'ccb-core' ), $singular ),
				'new_item' => sprintf( __( 'New %s', 'ccb-core' ), $singular ),
				'view_item' => sprintf( __( 'View %s', 'ccb-core' ), $singular ),
				'search_items' => sprintf( __( 'Search %s', 'ccb-core' ), $plural ),
				'not_found' => __( 'Nothing found in the Database.', 'ccb-core' ),
				'not_found_in_trash' => __( 'Nothing found in Trash', 'ccb-core' ),
				'parent_item_colon' => '',
			],
			'description' => sprintf( __( 'These are the %s that are synchronized with your Church Community Builder software.', 'ccb-core' ), $plural ),
			'public' => true,
			'publicly_queryable' => $publicly_queryable,
			'exclude_from_search' => $exclude_from_search,
			'show_ui' => $show_ui,
			'show_in_nav_menus' => $show_in_nav_menus,
			'query_var' => true,
			'menu_position' => 8,
			'menu_icon' => 'dashicons-groups',
			'rewrite' => $rewrite,
			'has_archive' => $has_archive,
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

		$settings['ccb_core_settings_groups'] = [
			'page_title' => esc_html__( 'Groups', 'ccb-core' ),
			'sections' => [
				'groups' => [
					'section_title' => esc_html__( 'Groups', 'ccb-core' ),
					'fields' => [
						'groups_enabled' => [
							'field_title' => esc_html__( 'Enable Groups', 'ccb-core' ),
							'field_render_function' => 'render_switch',
							'field_validation' => 'switch',
						],
						'groups_name' => [
							'field_title' => esc_html__( 'Groups Display Name (Plural)', 'ccb-core' ),
							'field_render_function' => 'render_text',
							'field_placeholder' => esc_html__( 'Groups', 'ccb-core' ),
							'field_validation' => 'alphanumeric_extended',
							'field_attributes' => [ 'data-requires' => '{"groups_enabled":1}' ],
							'field_tooltip' => esc_html__( 'This is what you call the groups in your church (i.e. Home Groups, Connections, Life Groups, etc.).', 'ccb-core' ),
						],
						'groups_name_singular' => [
							'field_title' => esc_html__( 'Groups Display Name (Singular)', 'ccb-core' ),
							'field_render_function' => 'render_text',
							'field_placeholder' => esc_html__( 'Group', 'ccb-core' ),
							'field_validation' => 'alphanumeric_extended',
							'field_attributes' => [ 'data-requires' => '{"groups_enabled":1}' ],
							'field_tooltip' => esc_html__( 'This is the singular name of what you call the groups in your church (i.e. Home Group, Connection, Life Group, etc.).', 'ccb-core' ),
						],
						'groups_slug' => [
							'field_title' => esc_html__( 'Groups URL Name', 'ccb-core' ),
							'field_render_function' => 'render_text',
							'field_placeholder' => 'groups',
							'field_validation' => 'slug',
							'field_attributes' => [ 'data-requires' => '{"groups_enabled":1}' ],
							'field_tooltip' => esc_html__( 'This is typically where your theme will display all the groups. WordPress calls this a "slug".', 'ccb-core' ),
						],
						'groups_import_images' => [
							'field_title' => esc_html__( 'Also Import Group Images?', 'ccb-core' ),
							'field_render_function' => 'render_radio',
							'field_options' => [
								'yes' => esc_html__( 'Yes', 'ccb-core' ),
								'no' => esc_html__( 'No', 'ccb-core' ),
							],
							'field_validation' => '',
							'field_default' => 'no',
							'field_attributes' => [ 'data-requires' => '{"groups_enabled":1}' ],
							'field_tooltip' => sprintf(
								esc_html__(
									'This will download the CCB Group Image and attach it as a Featured Image.%s
									If you don\'t need group images, then disabling this feature will speed up the synchronization.',
									'ccb-core'
								),
								'<br>'
							),
						],
						'groups_advanced' => [
							'field_title' => esc_html__( 'Enable Advanced Settings (Optional)', 'ccb-core' ),
							'field_render_function' => 'render_switch',
							'field_validation' => 'switch',
							'field_attributes' => [ 'data-requires' => '{"groups_enabled":1}' ],
						],
						'groups_exclude_from_search' => [
							'field_title' => esc_html__( 'Exclude From Search?', 'ccb-core' ),
							'field_render_function' => 'render_radio',
							'field_options' => [
								'yes' => esc_html__( 'Yes', 'ccb-core' ),
								'no' => esc_html__( 'No', 'ccb-core' ),
							],
							'field_validation' => '',
							'field_default' => 'no',
							'field_attributes' => [ 'data-requires' => '{"groups_enabled":1,"groups_advanced":1}' ],
						],
						'groups_publicly_queryable' => [
							'field_title' => esc_html__( 'Publicly Queryable?', 'ccb-core' ),
							'field_render_function' => 'render_radio',
							'field_options' => [
								'yes' => esc_html__( 'Yes', 'ccb-core' ),
								'no' => esc_html__( 'No', 'ccb-core' ),
							],
							'field_validation' => '',
							'field_default' => 'yes',
							'field_attributes' => [ 'data-requires' => '{"groups_enabled":1,"groups_advanced":1}' ],
						],
						'groups_show_ui' => [
							'field_title' => esc_html__( 'Show In Admin UI?', 'ccb-core' ),
							'field_render_function' => 'render_radio',
							'field_options' => [
								'yes' => esc_html__( 'Yes', 'ccb-core' ),
								'no' => esc_html__( 'No', 'ccb-core' ),
							],
							'field_validation' => '',
							'field_default' => 'yes',
							'field_attributes' => [ 'data-requires' => '{"groups_enabled":1,"groups_advanced":1}' ],
						],
						'groups_show_in_nav_menus' => [
							'field_title' => esc_html__( 'Show In Navigation Menus?', 'ccb-core' ),
							'field_render_function' => 'render_radio',
							'field_options' => [
								'yes' => esc_html__( 'Yes', 'ccb-core' ),
								'no' => esc_html__( 'No', 'ccb-core' ),
							],
							'field_validation' => '',
							'field_default' => 'no',
							'field_attributes' => [ 'data-requires' => '{"groups_enabled":1,"groups_advanced":1}' ],
						],
					],
				],
			],
		];

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
		if ( $this->enabled ) {
			$options = CCB_Core_Helpers::instance()->get_options();
			$include_image_link = ! empty( $options['groups_import_images'] ) && 'yes' === $options['groups_import_images'] ? true : false;

			$maps[ $this->name ] = [
				'service' => 'group_profiles',
				'data' => [
					'include_participants' => false,
					'include_image_link' => $include_image_link,
				],
				'nodes' => [ 'groups', 'group' ],
				'fields' => [
					'name' => 'post_title',
					'description' => 'post_content',
					'main_leader' => 'post_meta',
					'calendar_feed' => 'post_meta',
					'current_members' => 'post_meta',
					'group_capacity' => 'post_meta',
					'addresses' => 'post_meta',
				],
			];
		}
		return $maps;
	}

	/**
	 * Callback function for `ccb_core_synchronizer_entity_insert_allowed` and
	 * `ccb_core_synchronizer_entity_update_allowed` so that we can filter OUT
	 * inactive and non-public groups from an import.
	 *
	 * @since    1.0.0
	 *
	 * @param    bool      $allowed Whether an insert/update is allowed.
	 * @param    SimpleXML $entity The specific entity object.
	 * @param    mixed     $entity_id A unique entity id.
	 * @param    string    $post_type The current post type.
	 * @return   bool
	 */
	public function entity_insert_update_allowed( $allowed, $entity, $entity_id, $post_type ) {
		if ( $this->name === $post_type ) {
			// Only allow active, publicly listed groups to be imported.
			if ( 'true' === (string) $entity->inactive || 'false' === (string) $entity->public_search_listed ) {
				$allowed = false;
			}
		}
		return $allowed;
	}

	/**
	 * Checks whether downloading group images is enabled
	 * and an entity has an image attachment, then attaches
	 * the image as a featured image.
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
	public function attach_group_image( $entity, $settings, $args, $post_type, $post_id ) {
		if ( $this->name === $post_type && $settings['data']['include_image_link'] ) {
			$image_url = (string) $entity->image;
			if ( $image_url ) {
				CCB_Core_Helpers::instance()->download_image( $image_url, $args['post_title'], $post_id );
			}
		}
	}
}

new CCB_Core_Group();
