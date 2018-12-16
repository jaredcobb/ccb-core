<?php
/**
 * Calendar Custom Post Type
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
	public $name = 'ccb_core_calendar';

	/**
	 * Initialize the class
	 */
	public function __construct() {
		// Add the CCB event id as post meta onto each instance of the event.
		add_action( 'ccb_core_after_insert_update_post', [ $this, 'update_event_id' ], 10, 5 );

		$options = CCB_Core_Helpers::instance()->get_options();
		$this->enabled = ! empty( $options['calendar_enabled'] ) ? true : false;
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
		$plural = ! empty( $options['calendar_name'] ) ? $options['calendar_name'] : __( 'Events', 'ccb-core' );
		$singular = ! empty( $options['calendar_name_singular'] ) ? $options['calendar_name_singular'] : __( 'Event', 'ccb-core' );
		$rewrite = ! empty( $options['calendar_slug'] ) ? [ 'slug' => sanitize_title( $options['calendar_slug'] ) ] : [ 'slug' => 'events' ];
		$has_archive = ! empty( $options['calendar_slug'] ) ? sanitize_title( $options['calendar_slug'] ) : 'events';
		$exclude_from_search = ! empty( $options['calendar_exclude_from_search'] ) && 'yes' === $options['calendar_exclude_from_search'] ? true : false;
		$publicly_queryable = ! empty( $options['calendar_publicly_queryable'] ) && 'no' === $options['calendar_publicly_queryable'] ? false : true;
		$show_ui = ! empty( $options['calendar_show_ui'] ) && 'no' === $options['calendar_show_ui'] ? false : true;
		$show_in_nav_menus = ! empty( $options['calendar_show_in_nav_menus'] ) && 'yes' === $options['calendar_show_in_nav_menus'] ? true : false;

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
			'menu_icon' => 'dashicons-calendar',
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

		$settings['ccb_core_settings_calendar'] = [
			'page_title' => esc_html__( 'Public Events', 'ccb-core' ),
			'sections' => [
				'calendar' => [
					'section_title' => esc_html__( 'Public Events', 'ccb-core' ),
					'fields' => [
						'calendar_enabled' => [
							'field_title' => esc_html__( 'Enable Events', 'ccb-core' ),
							'field_render_function' => 'render_switch',
							'field_validation' => 'switch',
						],
						'calendar_name' => [
							'field_title' => esc_html__( 'Event Display Name (Plural)', 'ccb-core' ),
							'field_render_function' => 'render_text',
							'field_placeholder' => esc_html__( 'Events', 'ccb-core' ),
							'field_validation' => 'alphanumeric_extended',
							'field_attributes' => [ 'data-requires' => '{"calendar_enabled":1}' ],
							'field_tooltip' => esc_html__( 'This is what you call the events in your church (i.e. Meetups, Hangouts, etc.).', 'ccb-core' ),
						],
						'calendar_name_singular' => [
							'field_title' => esc_html__( 'Event Display Name (Singular)', 'ccb-core' ),
							'field_render_function' => 'render_text',
							'field_placeholder' => esc_html__( 'Event', 'ccb-core' ),
							'field_validation' => 'alphanumeric_extended',
							'field_attributes' => [ 'data-requires' => '{"calendar_enabled":1}' ],
							'field_tooltip' => esc_html__( 'This is the singular name of what you call the events in your church (i.e. Meetup, Hangout, etc.).', 'ccb-core' ),
						],
						'calendar_slug' => [
							'field_title' => esc_html__( 'Events URL Name', 'ccb-core' ),
							'field_render_function' => 'render_text',
							'field_placeholder' => 'events',
							'field_validation' => 'slug',
							'field_attributes' => [ 'data-requires' => '{"calendar_enabled":1}' ],
							'field_tooltip' => esc_html__( 'This is typically where your theme will display all the events. WordPress calls this a "slug".', 'ccb-core' ),
						],
						'calendar_advanced' => [
							'field_title' => esc_html__( 'Enable Advanced Settings (Optional)', 'ccb-core' ),
							'field_render_function' => 'render_switch',
							'field_validation' => 'switch',
							'field_attributes' => [ 'data-requires' => '{"calendar_enabled":1}' ],
						],
						'calendar_date_range_type' => [
							'field_title' => esc_html__( 'Date Range Type', 'ccb-core' ),
							'field_render_function' => 'render_radio',
							'field_options' => [
								'relative' => esc_html__( 'Relative Range', 'ccb-core' ),
								'specific' => esc_html__( 'Specific Range', 'ccb-core' ),
							],
							'field_validation' => '',
							'field_default' => 'relative',
							'field_attributes' => [
								'class' => 'date-range-type',
								'data-requires' => '{"calendar_enabled":1,"calendar_advanced":1}',
							],
							'field_tooltip' => sprintf(
								esc_html__(
									'Relative: For example, always get the events from "One week ago", up to "Eight weeks from now".%1$s
									This is the best setting for most churches.%2$s
									Specific: For example, only get events from "6/1/2018" to "12/1/2018".%3$s
									This setting is best if you want to tightly manage the events that get published.',
									'ccb-core'
								),
								'<br>',
								'<br><br>',
								'<br>'
							),
						],
						'calendar_relative_weeks_past' => [
							'field_title' => esc_html__( 'How Far Back?', 'ccb-core' ),
							'field_render_function' => 'render_slider',
							'field_options' => [
								'min' => '0',
								'max' => '26',
								'units' => 'weeks',
							],
							'field_default' => 1,
							'field_validation' => '',
							'field_attributes' => [ 'data-requires' => '{"calendar_enabled":1,"calendar_advanced":1,"calendar_date_range_type":"relative"}' ],
							'field_tooltip' => esc_html__( 'Every time we synchronize, how many weeks in the past should we look? (0 would be "today")', 'ccb-core' ),
						],
						'calendar_relative_weeks_future' => [
							'field_title' => esc_html__( 'How Into The Future?', 'ccb-core' ),
							'field_render_function' => 'render_slider',
							'field_options' => [
								'min' => '1',
								'max' => '52',
								'units' => 'weeks',
							],
							'field_default' => 16,
							'field_validation' => '',
							'field_attributes' => [ 'data-requires' => '{"calendar_enabled":1,"calendar_advanced":1,"calendar_date_range_type":"relative"}' ],
							'field_tooltip' => esc_html__( 'Every time we synchronize, how many weeks in the future should we look?', 'ccb-core' ),
						],
						'calendar_specific_start' => [
							'field_title' => esc_html__( 'Specific Start Date', 'ccb-core' ),
							'field_render_function' => 'render_date_picker',
							'field_validation' => '',
							'field_attributes' => [ 'data-requires' => '{"calendar_enabled":1,"calendar_advanced":1,"calendar_date_range_type":"specific"}' ],
							'field_tooltip' => sprintf(
								esc_html__(
									'When synchronizing, we should get events that start after this date.%s
									(Leave empty to always start "today")',
									'ccb-core'
								),
								'<br>'
							),
						],
						'calendar_specific_end' => [
							'field_title' => esc_html__( 'Specific End Date', 'ccb-core' ),
							'field_render_function' => 'render_date_picker',
							'field_validation' => '',
							'field_attributes' => [ 'data-requires' => '{"calendar_enabled":1,"calendar_advanced":1,"calendar_date_range_type":"specific"}' ],
							'field_tooltip' => sprintf(
								esc_html__(
									'When synchronizing, we should get events that start before this date.%s
									(Setting this too far into the future may cause the API to timeout)',
									'ccb-core'
								),
								'<br>'
							),
						],
						'calendar_exclude_from_search' => [
							'field_title' => esc_html__( 'Exclude From Search?', 'ccb-core' ),
							'field_render_function' => 'render_radio',
							'field_options' => [
								'yes' => esc_html__( 'Yes', 'ccb-core' ),
								'no' => esc_html__( 'No', 'ccb-core' ),
							],
							'field_validation' => '',
							'field_default' => 'no',
							'field_attributes' => [ 'data-requires' => '{"calendar_enabled":1,"calendar_advanced":1}' ],
						],
						'calendar_publicly_queryable' => [
							'field_title' => esc_html__( 'Publicly Queryable?', 'ccb-core' ),
							'field_render_function' => 'render_radio',
							'field_options' => [
								'yes' => esc_html__( 'Yes', 'ccb-core' ),
								'no' => esc_html__( 'No', 'ccb-core' ),
							],
							'field_validation' => '',
							'field_default' => 'yes',
							'field_attributes' => [ 'data-requires' => '{"calendar_enabled":1,"calendar_advanced":1}' ],
						],
						'calendar_show_ui' => [
							'field_title' => esc_html__( 'Show In Admin UI?', 'ccb-core' ),
							'field_render_function' => 'render_radio',
							'field_options' => [
								'yes' => esc_html__( 'Yes', 'ccb-core' ),
								'no' => esc_html__( 'No', 'ccb-core' ),
							],
							'field_validation' => '',
							'field_default' => 'yes',
							'field_attributes' => [ 'data-requires' => '{"calendar_enabled":1,"calendar_advanced":1}' ],
						],
						'calendar_show_in_nav_menus' => [
							'field_title' => esc_html__( 'Show In Navigation Menus?', 'ccb-core' ),
							'field_render_function' => 'render_radio',
							'field_options' => [
								'yes' => esc_html__( 'Yes', 'ccb-core' ),
								'no' => esc_html__( 'No', 'ccb-core' ),
							],
							'field_validation' => '',
							'field_default' => 'no',
							'field_attributes' => [ 'data-requires' => '{"calendar_enabled":1,"calendar_advanced":1}' ],
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
			$calendar_options = $this->get_calendar_options();

			$maps[ $this->name ] = [
				'service' => 'public_calendar_listing',
				'data' => [
					'date_start' => $calendar_options['date_start'],
					'date_end' => $calendar_options['date_end'],
				],
				'nodes' => [ 'items', 'item' ],
				'fields' => [
					'event_name' => 'post_title',
					'event_description' => 'post_content',
					'date' => 'post_meta',
					'start_time' => 'post_meta',
					'end_time' => 'post_meta',
					'event_duration' => 'post_meta',
					'location' => 'post_meta',
				],
			];
		}
		return $maps;
	}

	/**
	 * Parses the CCB event id from the `name` node and
	 * saves it as post meta.
	 *
	 * @since    1.0.4
	 *
	 * @param    SimpleXML $entity The entity object.
	 * @param    array     $settings The settings array for the import.
	 * @param    array     $args The `wp_insert_post` args.
	 * @param    string    $post_type The current post type.
	 * @param    int       $post_id The WordPress post id of this post.
	 * @return   bool
	 */
	public function update_event_id( $entity, $settings, $args, $post_type, $post_id ) {
		if ( ! empty( $entity->event_name ) ) {
			foreach ( $entity->event_name->attributes() as $key => $value ) {
				if ( 'ccb_id' === $key ) {
					$event_id = (int) $value;
					if ( $event_id ) {
						return update_post_meta( $post_id, 'event_id', $event_id );
					}
				}
			}
		}
		return false;
	}

	/**
	 * Returns a standardized configuration array of
	 * start and end dates to be used by the API call.
	 *
	 * @return array
	 */
	private function get_calendar_options() {
		$options = CCB_Core_Helpers::instance()->get_options();

		// By default, set some sane limits.
		$calendar_options = [
			'date_start' => date( 'Y-m-d', strtotime( '1 weeks ago' ) ),
			'date_end' => date( 'Y-m-d', strtotime( '+8 weeks' ) ),
		];

		// If the user has set a preferred date range type.
		if ( ! empty( $options['calendar_date_range_type'] ) ) {

			if ( 'relative' === $options['calendar_date_range_type'] ) {

				$calendar_options['date_start'] = date( 'Y-m-d', strtotime( $options['calendar_relative_weeks_past'] . ' weeks ago' ) );
				$calendar_options['date_end'] = date( 'Y-m-d', strtotime( '+' . $options['calendar_relative_weeks_future'] . ' weeks' ) );

			} elseif ( 'specific' === $options['calendar_date_range_type'] ) {

				// For each date range, do not let the user go further than
				// 1 year in the past or 1 year into the future to prevent
				// them from blowing up their server.
				if ( ! empty( $options['calendar_specific_start'] ) ) {
					$last_year = strtotime( '1 year ago' );
					$start_timestamp = strtotime( $options['calendar_specific_start'] );

					if ( $last_year < $start_timestamp ) {
						$calendar_options['date_start'] = date( 'Y-m-d', $start_timestamp );
					} else {
						$calendar_options['date_start'] = date( 'Y-m-d', $last_year );
					}
				}

				if ( ! empty( $options['calendar_specific_end'] ) ) {
					$next_year = strtotime( '+1 year' );
					$end_timestamp = strtotime( $options['calendar_specific_end'] );

					if ( $next_year > $end_timestamp ) {
						$calendar_options['date_end'] = date( 'Y-m-d', $end_timestamp );
					} else {
						$calendar_options['date_end'] = date( 'Y-m-d', $next_year );
					}
				}

			}
		}

		return $calendar_options;
	}

}

new CCB_Core_Calendar();
