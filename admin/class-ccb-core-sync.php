<?php
/**
 * Synchronize CCB API data
 *
 * @link       http://jaredcobb.com/ccb-core
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 */

/**
 * Synchronize CCB API data
 *
 * Handles the cURL request, throttling, and caching of data
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Sync extends CCB_Core_Plugin {

	/**
	 * The subdomain of the ccb church installation
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $subdomain
	 */
	protected $subdomain;

	/**
	 * The ccb api username
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $username
	 */
	protected $username;

	/**
	 * The ccb api password
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $password
	 */
	protected $password;

	/**
	 * The CCB APIs we want to sync with
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      array    $enabled_apis
	 */
	protected $enabled_apis = array();

	/**
	 * The start date range for calendar events
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $calendar_start_date
	 */
	protected $calendar_start_date;

	/**
	 * The end date range for calendar events
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $calendar_end_date
	 */
	protected $calendar_end_date;

	/**
	 * Any valid service that the core API might integrate with
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      array    $valid_services
	 */
	protected $valid_services;

	/**
	 * Whether or not to additionally import group images
	 *
	 * @since    0.9.5
	 * @access   protected
	 * @var      array    $valid_services
	 */
	protected $import_group_images;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.9.0
	 */
	public function __construct() {

		parent::__construct();

		$settings = get_option( $this->plugin_settings_name );

		$this->subdomain = $settings['subdomain'];
		$this->username = $settings['credentials']['username'];
		$this->password = $this->decrypt( $settings['credentials']['password'] );

		if ( isset( $settings['groups-enabled'] ) && $settings['groups-enabled'] == 1 ) {

			$this->enabled_apis['group_profiles'] = true;

			if ( isset( $settings['groups-import-images'] ) && $settings['groups-import-images'] == 'yes' ) {
				$this->import_group_images = true;
			}
			else {
				$this->import_group_images = false;
			}

		}
		if ( isset( $settings['calendar-enabled'] ) && $settings['calendar-enabled'] == 1 ) {

			$this->enabled_apis['public_calendar_listing'] = true;

			// use sane defaults if this advanced setting isn't set
			if ( ! isset( $settings['calendar-date-range-type'] ) ) {

				$this->calendar_start_date = date( 'Y-m-d', strtotime( '1 weeks ago') );
				$this->calendar_end_date = date( 'Y-m-d', strtotime( '+16 weeks' ) );

			}
			elseif ( $settings['calendar-date-range-type'] == 'relative' ) {

				$this->calendar_start_date = date( 'Y-m-d', strtotime( $settings['calendar-relative-weeks-past'] . ' weeks ago') );
				$this->calendar_end_date = date( 'Y-m-d', strtotime( '+' . $settings['calendar-relative-weeks-future'] . ' weeks' ) );

			}
			elseif ( $settings['calendar-date-range-type'] == 'specific' ) {

				// TODO: Use localization for date formats other than U.S.

				if ( $settings['calendar-specific-start'] ) {

					$last_year = strtotime( '1 year ago' );
					$start_timestamp = strtotime( $settings['calendar-specific-start'] );

					if ( abs( $start_timestamp - $last_year ) > 0 ) {
						$this->calendar_start_date = date( 'Y-m-d', $start_timestamp );
					}
					else {
						$this->calendar_start_date = date( 'Y-m-d', $last_year );
					}

				}
				else {
					$this->calendar_start_date = date( 'Y-m-d' );
				}

				if ( $settings['calendar-specific-end'] ) {

					$next_year = strtotime( '+1 year' );
					$end_timestamp = strtotime( $settings['calendar-specific-end'] );

					if ( abs( $next_year - $end_timestamp ) > 0 ) {
						$this->calendar_end_date = date( 'Y-m-d', $end_timestamp );
					}
					else {
						$this->calendar_end_date = date( 'Y-m-d', $next_year );
					}

				}
				else {
					$this->calendar_end_date = date( 'Y-m-d', strtotime( '+1 year' ) );
				}
			}

		}

		$this->valid_services = array(
			array(
				'service_name' => 'api_status',
				'service_friendly_name' => 'Credentials',
			),
			array(
				'service_name' => 'group_profiles',
				'params' => array(
					'describe_api' => '1'
				),
				'service_friendly_name' => 'Group Profiles API',
			),
			array(
				'service_name' => 'public_calendar_listing',
				'params' => array(
					'describe_api' => '1'
				),
				'service_friendly_name' => 'Public Calendar Listing API',
			),
		);

	}

	/**
	 * Make a service call to the CCB API
	 *
	 * The $services array is in the format:
	 * $services = array(
	 *	 array (
	 *		 'service_name' => 'group_profiles',
	 *		 'params' => array(
	 *			 'modified_since' => '2015-06-01',
	 *			 'include_participants' => 'false'
	 *		 )
	 *	 ),
	 *	 array (
	 *		 'service_name' => 'public_calendar_listing',
	 *		 'params' => array(
	 *			 'date_start' => '2015-06-01',
	 *		 )
	 *	 ),
	 * )
	 *
	 * @since     0.9.0
	 * @param     array    $services    An array of services and parameters to call
	 * @access    protected
	 * @return    void
	 */
	protected function call_ccb_api( $services = array() ) {

		set_time_limit(600);
		$full_response = array();

		// for debugging purposes, set a constant and serialize an array like so:
		// define( 'RESPONSE_FILE', serialize( array( 'filename' => 'some_file.xml', 'service_name' => 'group_profiles' ) ) );
		// file must be located in the /uploads/ccb-core/ folder
		// this will prevent a real api call and will use an xml file
		if ( WP_DEBUG == true && defined( 'RESPONSE_FILE' ) ) {

			$service = unserialize( RESPONSE_FILE );
			$upload_dir = wp_upload_dir();
			$filepath = trailingslashit( trailingslashit( $upload_dir['basedir'] ) . $this->plugin_name ) . $service['filename'];

			if ( file_exists( $filepath ) ) {
				$response_body = file_get_contents( $filepath );
				libxml_use_internal_errors(true);
				$response_xml = simplexml_load_string( $response_body );

				if ( is_object( $response_xml ) ) {
					$full_response['success'] = true;
					$full_response[ $service['service_name'] ] = $response_xml;
				}

				return $full_response;
			}

		}

		if ( ! empty( $services ) && is_array( $services ) ) {

			foreach ( $services as $service ) {

				$params = '';
				if ( isset( $service['params'] ) && ! empty( $service['params'] ) ) {
					$params = http_build_query($service['params']);
				}

				$api_url = "https://{$this->subdomain}.ccbchurch.com/api.php?srv={$service['service_name']}&{$params}";
				$post_args = array(
					'body' => array(),
					'timeout' => '600',
					'redirection' => '15',
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array(
						'Authorization' => 'Basic ' . base64_encode( "{$this->username}:{$this->password}" )
					),
					'cookies' => array()
				);

				$response = wp_remote_post( $api_url, $post_args );
				$response_code = wp_remote_retrieve_response_code( $response );

				if ( $response_code != 200 ) {
					$full_response['success'] = false;
					$full_response['message'] = "There was a problem connecting with the Church Community Builder API - Response Code: {$response_code}";
					break;
				}
				else {
					try {
						libxml_use_internal_errors(true);
						$response_body = wp_remote_retrieve_body( $response );
						$response_xml = simplexml_load_string( $response_body );

						if ( is_object( $response_xml ) ) {
							$full_response['success'] = true;
							$full_response[ $service['service_name'] ] = $response_xml;
						}
						else {
							$full_response['success'] = false;
							$full_response['message'] = 'Oops, something went wrong while trying to read the API response. Is your subdomain correct?';
							break;
						}
					}
					catch ( Exception $ex ) {
						$full_response['success'] = false;
						$full_response['message'] = 'Oops, something went wrong while trying to read the API response. Is your subdomain correct?';
						break;
					}
				}

				// cache the xml response to the uploads folder if debug mode is on (testing purposes)
				if ( WP_DEBUG == true ) {

					$now = new DateTime();
					$cache_filename = $service['service_name'] . '_' . $now->format( 'Y-m-d_His' ) . '.xml';
					$upload_dir = wp_upload_dir();

					if ( wp_mkdir_p( trailingslashit( $upload_dir['basedir'] ) . $this->plugin_name ) ) {
						// first delete any files that weren't created "today" so we don't spam the server over time
						$files = preg_grep( '/' . $now->format( 'Y-m-d' ) . '/', glob( trailingslashit( trailingslashit( $upload_dir['basedir'] ) . $this->plugin_name ) . '*' ), PREG_GREP_INVERT );
						foreach ( $files as $file ) {
							if ( is_file( $file ) ) {
								@unlink( $file );
							}
						}

						$upload_file_path = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $this->plugin_name ) . $cache_filename;
						file_put_contents( $upload_file_path, $response_body );
					}

				}
			}
		}
		else {
			$full_response['success'] = false;
			$full_response['message'] = 'You tried to kick off a syncronization on ' . date( 'F j, Y @ h:i:s a (e)' ) . " but didn't have any integrations enabled (see each service tab).";
		}

		return $full_response;
	}

	/**
	 * Perform a synchronization
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function sync() {

		// check for a transient that assumes a sync in in progress
		if ( get_transient( $this->plugin_name . '-sync-in-progress' ) ) {
			return;
		}
		else {
			set_transient( $this->plugin_name . '-sync-in-progress', true, 60*20 );
		}

		$services = array();
		$current_time = time();

		// GROUP PROFILES
		if ( $this->enabled_apis['group_profiles'] ) {

			$include_participants = false;
			$include_participants = apply_filters( 'ccb_include_group_participants', $include_participants );

			$services[] = array(
				'service_name' => 'group_profiles',
				'params' => array(
					'include_participants' => $include_participants,
				),
			);

		}

		// PUBLIC CALENDAR LISTING
		if ( $this->enabled_apis['public_calendar_listing'] ) {

			$services[] = array(
				'service_name' => 'public_calendar_listing',
				'params' => array( 'date_start' => $this->calendar_start_date, 'date_end' => $this->calendar_end_date ),
			);

		}

		$full_response = $this->call_ccb_api( $services );
		$validation_results = $this->validate_response( $full_response );
		// a data structure to hold a unique status of the latest sync results, stored in the db
		$latest_sync = array();

		if ( $validation_results['success'] == true ) {

			// check if any services failed so we can abort the sync and show different messaging
			$service_failure = false;
			foreach ( $validation_results['services'] as $service ) {
				if ( $service['success'] == false ) {
					$service_failure = true;
					break;
				}
			}

			if ( $service_failure ) {

				$messages = array();

				foreach ( $validation_results['services'] as $service ) {

					if ( $service['success'] == false ) {
						$messages[] = 'We were <u>not</u> able to successfully synchronize with the <strong>' . $service['service_name'] . '</strong> service on ' . date( 'F j, Y @ h:i:s a (e)', $current_time ) . '.&nbsp;' . $service['message'];
					}
					else {
						$messages[] = 'We <u>were</u> able to successfully contact the <strong>' . $service['service_name'] . '</strong> service on ' . date( 'F j, Y @ h:i:s a (e)', $current_time ) . ', however we <em>cancelled the synchronization because of other service errors.</em>';
					}
				}

				$message = implode( '<br><br>', $messages );
				$latest_sync = array(
					'success' => false,
					'message' => $message,
				);
			}
			else {

				$this->import_cpts( $full_response );

				$latest_sync = array(
					'success' => true,
					'message' => 'We last successfully synchronized with the Church Community Builder API on ' . date( 'F j, Y @ h:i:s a (e)', $current_time ),
				);
			}
		}
		else {
			$latest_sync = array(
				'success' => false,
				'message' => $validation_results['message'] . ' We made the last attempt on ' . date( 'F j, Y @ h:i:s a (e)', $current_time ),
			);
		}

		$latest_sync['timestamp'] = $current_time;
		delete_transient( $this->plugin_name . '-sync-in-progress' );
		update_option( $this->plugin_name . '-latest-sync', $latest_sync );

	}

	/**
	 * Tests API connection, credentials, and specific services
	 * as defined in the constructor
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    string
	 */
	public function test_api_credentials() {

		$full_response = $this->call_ccb_api( $this->valid_services );
		delete_transient( $this->plugin_name . '-sync-in-progress' );
		return $this->validate_response( $full_response );

	}

	/**
	 * Takes a CCB API response and parses it for basic business rules.
	 * Returns an array of successes, failures, and messages
	 *
	 * @param     mixed    $full_response
	 * @access    protected
	 * @since     0.9.0
	 * @return    array
	 */
	protected function validate_response( $full_response ) {

		$validation_results = array();

		if ( $full_response['success'] ) {

			$validation_results['success'] = true;
			$validation_results['services'] = array();

			foreach ( $this->valid_services as $service ) {

				if ( ! empty ( $full_response[ $service['service_name'] ] ) ) {

					$result_array = array();

					if ( isset( $full_response[ $service['service_name'] ]->response->errors ) ) {
						if ( isset( $full_response[ $service['service_name'] ]->response->errors->error ) && ! empty( $full_response[ $service['service_name'] ]->response->errors->error ) ) {
							$result_array = array(
								'success' => false,
								'label' => $service['service_friendly_name'],
								'service_name' => $service['service_name'],
								'message' => 'The API responded with the message:<br><code>&quot;' . $full_response[ $service['service_name'] ]->response->errors->error . '&quot;</code>',
							);
						}
						else {
							$result_array = array(
								'success' => false,
								'label' => $service['service_friendly_name'],
								'service_name' => $service['service_name'],
								'message' => 'The API did not provide any other information',
							);
						}
					}
					else {
						$result_array = array(
							'success' => true,
							'label' => $service['service_friendly_name'],
							'service_name' => $service['service_name'],
							'message' => 'Success',
						);
					}

					$validation_results['services'][] = $result_array;
				}

			}

		}
		else {
			// the entire call was a failure. a sad sad failure.
			$validation_results['success'] = false;
			$validation_results['message'] = $full_response['message'];
		}

		return $validation_results;
	}

	/**
	 * Parses the XML response, deletes existing CPTs, and imports CCB data
	 *
	 * @param     mixed    $full_response
	 * @since     0.9.0
	 * @access    protected
	 * @return    void
	 */
	protected function import_cpts( $full_response ) {

		global $wpdb;
		// temporarily disable counting for performance
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		// temporarily disable autocommit
		$wpdb->query( 'SET autocommit = 0;' );


		// GROUP PROFILES
		if ( $this->enabled_apis['group_profiles'] == true && isset( $full_response['group_profiles']->response->groups->group ) && ! empty( $full_response['group_profiles']->response->groups->group ) ) {

			$groups_taxonomy_map = CCB_Core_CPTs::get_groups_taxonomy_map();
			$groups_taxonomy_map = apply_filters( 'ccb_get_groups_taxonomy_map', $groups_taxonomy_map );

			$groups_custom_fields_map = CCB_Core_CPTs::get_groups_custom_fields_map();
			$groups_custom_fields_map = apply_filters( 'ccb_get_groups_custom_fields_map', $groups_custom_fields_map );

			// delete the existing taxonomy terms
			foreach ( $groups_taxonomy_map as $taxonomy_name => $taxonomy ) {
				$terms = get_terms( $taxonomy_name, array( 'fields' => 'ids', 'hide_empty' => false ) );
				if ( ! empty( $terms ) ) {
					foreach ( $terms as $term_value ) {
						wp_delete_term( $term_value, $taxonomy_name );
					}
				}
			}

			// delete existing custom posts
			$custom_posts = get_posts( array( 'post_type' => $this->plugin_name . '-groups', 'posts_per_page' => -1 ) );
			foreach( $custom_posts as $custom_post ) {

				// delete the post thumbnail if it exists before deleting the post
				$thumbnail_id = get_post_thumbnail_id( $custom_post->ID );
				if ( $thumbnail_id ) {
					wp_delete_attachment( $thumbnail_id, true );
				}

				wp_delete_post( $custom_post->ID, true);
			}

			// commit the deletes now
			$wpdb->query( 'COMMIT;' );

			// keep track of whether or not a default image has already been imported
			$default_attachment = 0;

			foreach ( $full_response['group_profiles']->response->groups->group as $group ) {

				// only allow publicly listed and active groups to be imported
				if ( $group->inactive == 'false' && $group->public_search_listed == 'true' ) {

					$group_id = 0;
					foreach( $group->attributes() as $key => $value ) {
						if ( $key == 'id' ) {
							$group_id = (int) $value;
							break;
						}
					}

					// insert group post
					$group_post_atts = array(
						'post_title' => $group->name,
						'post_name' => $group->name,
						'post_content' => $group->description,
						'post_status' => 'publish',
						'post_type' => $this->plugin_name . '-groups',
					);
					$post_id = wp_insert_post( $group_post_atts );

					// insert hierarchial taxonomy values (categories) and non-hierarchial taxonomy values (tags)
					$taxonomy_atts = $this->get_taxonomy_atts( $group, $groups_taxonomy_map );
					if ( ! empty( $taxonomy_atts ) ) {
						foreach ( $taxonomy_atts as $taxonomy_attribute ) {
							wp_set_post_terms( $post_id, $taxonomy_attribute['terms'], $taxonomy_attribute['taxonomy'], true );
						}
					}

					// insert custom fields
					$custom_fields_atts = $this->get_custom_fields_atts( $group, $groups_custom_fields_map );
					if ( ! empty( $custom_fields_atts ) ) {
						foreach ( $custom_fields_atts as $field_key => $custom_fields_attribute ) {
							add_post_meta( $post_id, $custom_fields_attribute['field_name'], $custom_fields_attribute['field_value'] );
						}
					}

					// download and attach the group image as the featured image
					if ( isset( $group->image ) && $this->import_group_images == true ) {

						$group_image_url = esc_url_raw( $group->image );

						if ( ! empty( $group_image_url ) ) {

							// handle default images
							if ( strpos( $group_image_url, 'default' ) ) {
								if ( ! $default_attachment ) {
									$attachment_result = $this->create_media_image( 'default', 0, $group_image_url );
									if ( $attachment_result ) {
										$default_attachment = $attachment_result;
										set_post_thumbnail( $post_id, $default_attachment );
									}
								}
								else {
									set_post_thumbnail( $post_id, $default_attachment );
								}
							}
							else {
								$attachment_result = $this->create_media_image( $group->name, $post_id, $group_image_url );
								if ( $attachment_result ) {
									set_post_thumbnail( $post_id, $attachment_result );
								}
							}
						}

					}

				}

			}

			// commit the inserts now
			$wpdb->query( 'COMMIT;' );

		}

		// PUBLIC CALENDAR LISTING
		if ( $this->enabled_apis['public_calendar_listing'] == true && isset( $full_response['public_calendar_listing']->response->items->item ) && ! empty( $full_response['public_calendar_listing']->response->items->item ) ) {

			$calendar_taxonomy_map = CCB_Core_CPTs::get_calendar_taxonomy_map();
			$calendar_taxonomy_map = apply_filters( 'ccb_get_calendar_taxonomy_map', $calendar_taxonomy_map );

			$calendar_custom_fields_map = CCB_Core_CPTs::get_calendar_custom_fields_map();
			$calendar_custom_fields_map = apply_filters( 'ccb_get_calendar_custom_fields_map', $calendar_custom_fields_map );

			// delete the existing taxonomy terms
			foreach ( $calendar_taxonomy_map as $taxonomy_name => $taxonomy ) {
				$terms = get_terms( $taxonomy_name, array( 'fields' => 'ids', 'hide_empty' => false ) );
				if ( ! empty( $terms ) ) {
					foreach ( $terms as $term_value ) {
						wp_delete_term( $term_value, $taxonomy_name );
					}
				}
			}

			// delete existing custom posts
			$custom_posts = get_posts( array( 'post_type' => $this->plugin_name . '-calendar', 'posts_per_page' => -1 ) );
			foreach( $custom_posts as $custom_post ) {
				wp_delete_post( $custom_post->ID, true);
			}

			// commit the deletes now
			$wpdb->query( 'COMMIT;' );

			foreach ( $full_response['public_calendar_listing']->response->items->item as $event ) {

				// insert event post
				$event_post_atts = array(
					'post_title' => $event->event_name,
					'post_name' => $event->event_name,
					'post_content' => $event->event_description,
					'post_status' => 'publish',
					'post_type' => $this->plugin_name . '-calendar',
				);
				$post_id = wp_insert_post( $event_post_atts );

				// insert hierarchial taxonomy values (categories) and non-hierarchial taxonomy values (tags)
				$taxonomy_atts = $this->get_taxonomy_atts( $event, $calendar_taxonomy_map );
				if ( ! empty( $taxonomy_atts ) ) {
					foreach ( $taxonomy_atts as $taxonomy_attribute ) {
						wp_set_post_terms( $post_id, $taxonomy_attribute['terms'], $taxonomy_attribute['taxonomy'], true );
					}
				}

				// insert custom fields
				$custom_fields_atts = $this->get_custom_fields_atts( $event, $calendar_custom_fields_map );
				if ( ! empty( $custom_fields_atts ) ) {
					foreach ( $custom_fields_atts as $field_key => $custom_fields_attribute ) {
						add_post_meta( $post_id, $custom_fields_attribute['field_name'], $custom_fields_attribute['field_value'] );
					}
				}

			}

			// commit the inserts now
			$wpdb->query( 'COMMIT;' );

		}

		// re-enable autocommit
		$wpdb->query( 'SET autocommit = 1;' );
		// re-enable counting
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

	}

	/**
	 * Uses a taxonomy map to build out the categories and tags
	 * for a CCB custom post type
	 *
	 * @param     mixed    $post_data
	 * @param     array    $taxonomy_map
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function get_taxonomy_atts( $post_data, $taxonomy_map ) {

		foreach( $taxonomy_map as $taxonomy_name => $taxonomy ) {

			if ( $taxonomy['hierarchical'] ) {

				$taxonomy_value = (string) $post_data->$taxonomy['api_mapping'];

				if ( ! empty( $taxonomy_value ) ) {

					$term_id = term_exists( $taxonomy_value, $taxonomy_name );
					if ( $term_id ) {
						$terms_collection[] = array(
							'terms' => $term_id['term_id'],
							'taxonomy' => $taxonomy_name,
						);
					}
					else {
						$new_term = wp_insert_term( $taxonomy_value, $taxonomy_name );
						$terms_collection[] = array(
							'terms' => $new_term['term_id'],
							'taxonomy' => $taxonomy_name,
						);
					}

				}

			}
			else {

				if ( isset( $taxonomy['api_mapping'] ) && ! empty( $taxonomy['api_mapping'] ) ) {

					foreach ( $taxonomy['api_mapping'] as $api_mapping => $tag_name ) {

						$tag_value = ( $post_data->$api_mapping == 'true' ? $tag_name : false );
						if ( $tag_value ) {
							$terms_collection[] = array(
								'terms' => $tag_value,
								'taxonomy' => $taxonomy_name,
							);
						}

					}

				}
			}

		}

		return $terms_collection;

	}

	/**
	 * Uses a custom fields map to build out the custom fields
	 * for a CCB custom post type
	 *
	 * @param     mixed     $post_data
	 * @param     array     $custom_fields_map
	 * @param     string    $parent_field_name
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function get_custom_fields_atts( $post_data, $custom_fields_map, $parent_field_name = '' ) {

		$custom_fields_collection = array();

		foreach( $custom_fields_map as $field_name => $field_data ) {

			switch ( $field_data['data_type'] ) {
				case 'string':
					$field_value = (string) $post_data->$field_data['api_mapping'];
					$custom_fields_collection[] = array(
						'field_name' => $field_name,
						'field_value' => $field_value,
					);
					break;
				case 'int':
					$field_value = (int) $post_data->$field_data['api_mapping'];
					$custom_fields_collection[] = array(
						'field_name' => $field_name,
						'field_value' => $field_value,
					);
					break;
				case 'object':
					if ( isset( $field_data['child_object'] ) && ! empty( $field_data['child_object'] ) ) {
						// some child objects may be collections of objects
						if ( count( $post_data->$field_data['api_mapping'] ) > 1 ) {
							$collection_grouping = array();
							foreach ( $post_data->$field_data['api_mapping'] as $key => $child_field_data ) {
								if ( is_object ( $child_field_data ) ) {
									$collection_grouping[] = $this->get_custom_fields_atts( $child_field_data, $field_data['child_object'], $field_name );
								}
							}
							$prepared_field_collection = $this->prepare_field_collection( $parent_field_name, $collection_grouping );
							$custom_fields_collection[] = $prepared_field_collection;
						}
						else {
							$child_custom_fields_collection = $this->get_custom_fields_atts( $post_data->$field_data['api_mapping'], $field_data['child_object'], $field_name );
							$custom_fields_collection = array_merge( $custom_fields_collection, $child_custom_fields_collection );
						}
					}
					break;
			}

		}

		return $custom_fields_collection;

	}

	/**
	 * Takes a multidimensional array of field collections and formats
	 * them into groups that are compatible with storage in a
	 * single custom field
	 *
	 * @param     string    $field_name
	 * @param     array     $collection_grouping
	 * @access    protected
	 * @since     0.9.4
	 * @return    array
	 */
	protected function prepare_field_collection( $field_name, $collection_grouping ) {

		$flat_collection = array(
			'field_name' => $field_name,
			'field_value' => array(),
		);

		foreach( $collection_grouping as $grouping_value ) {

			$grouping_array = array();

			foreach ( $grouping_value as $value_pair ) {

				$grouping_array[] = array(
					$value_pair['field_name'] => $value_pair['field_value']
				);

			}

			$flat_collection['field_value'][] = $grouping_array;

		}

		return $flat_collection;
	}

	/**
	 * Downloads an image from a URL, uploads it to the Media Library,
	 * and then optionally attaches it to a post
	 *
	 * @param     string    $group_name
	 * @param     int       $post_id
	 * @param     string    $image_url
	 * @access    protected
	 * @since     0.9.5
	 * @return    mixed     Returns a media id or false on failure
	 */
	protected function create_media_image( $group_name, $post_id, $image_url ) {

		// fetch the image from the cdn and store temporarily
		$temp_file = download_url( $image_url );

		if ( is_wp_error( $temp_file ) ) {
			return false;
		}

		// attempt to detect the mimetype based on the available functions
		$extension = false;
		if ( function_exists( 'exif_imagetype' ) && function_exists( 'image_type_to_extension' ) ) {
			// open with exif
			$image_type = exif_imagetype( $temp_file );
			if ( $image_type ) {
				$extension = image_type_to_extension( $image_type );
			}
		}
		elseif ( function_exists( 'getimagesize' ) && function_exists( 'image_type_to_extension' ) ) {
			// open with gd
			$file_size = getimagesize( $temp_file );
			if ( isset( $file_size[2] ) ) {
				$extension = image_type_to_extension( $file_size[2] );
			}
		}
		elseif ( function_exists( 'finfo_open' ) ) {
			// open with fileinfo
			$resource = finfo_open( FILEINFO_MIME_TYPE );
			$mimetype = finfo_file( $resource, $temp_file );
			finfo_close( $resource );
			if ( $mimetype ) {
				$mimetype_array = explode( '/', $mimetype );
				$extension = '.' . $mimetype_array[1];
			}
		}

		if ( $extension ) {

			$filename = 'ccb-' . sanitize_file_name( strtolower( $group_name ) ) . $extension;

			$file_array = array(
				'name' => $filename,
				'tmp_name' => $temp_file,
			);

			$media_id = media_handle_sideload( $file_array, $post_id );
			@unlink( $temp_file );

			if ( is_wp_error( $media_id ) ) {
				return false;
			}

			return $media_id;

		}
		else {
			return false;
		}
	}

}
