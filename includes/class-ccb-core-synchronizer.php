<?php
/**
 * Run a synchronization from CCB to WordPress
 *
 * @link       https://www.wpccb.com
 * @since      1.0.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Synchronize API data to WordPress using mappings defined
 * in the custom post type classes.
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Synchronizer {

	/**
	 * A complete mapping of CCB API data to post types and taxonomies
	 *
	 * @var   array
	 */
	public $map;

	/**
	 * Instance of the class
	 *
	 * @var      CCB_Core_Synchronizer
	 * @access   private
	 * @static
	 */
	private static $instance;

	/**
	 * Unused constructor in the singleton pattern
	 *
	 * @access   public
	 * @return   void
	 */
	public function __construct() {
		// Initialize this class with the instance() method.
	}

	/**
	 * Returns the instance of the class
	 *
	 * @access   public
	 * @static
	 * @return   CCB_Core_Synchronizer
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new CCB_Core_Synchronizer();
			static::$instance->setup();
		}
		return static::$instance;
	}

	/**
	 * Initial setup of the singleton
	 *
	 * @since    1.0.0
	 */
	private function setup() {
		// Wait to initialize the map until after the plugins / themes are fully
		// loaded so that all post types, taxonomies, and theme hooks have been registered.
		add_action( 'init', [ $this, 'initialize_map' ], 9, 1 ); // Cron runs on `init` priority `10`.
	}

	/**
	 * Setup the registered post type maps and taxonomy maps
	 * from the custom post type and taxonomy classes.
	 *
	 * @return void
	 */
	public function initialize_map() {
		$post_type_maps = [];
		$taxonomy_maps = [];
		$this->map = array_merge_recursive(
			/**
			 * Get a collection of all post type / API mappings.
			 *
			 * This is the main configuration for how the CCB API
			 * maps to a custom post type.
			 *
			 * @since 1.0.0
			 *
			 * @param array $post_type_maps {
			 *     A single map that defines the relationship between
			 *     the CCB API entity node and the custom post type.
			 *
			 *        $map[ {post_type} ] = [
			 *            'service' => {ccb_service_name},
			 *            'data' => [ {ccb_query_string_parameters} ],
			 *            'nodes' => [ {node list that maps to a single entity} ],
			 *            'fields' => [
			 *                {entity_name_node} => 'post_title',
			 *                {entity_description_node} => 'post_content',
			 *                {any_other_node} => 'post_meta',
			 *                {any_other_node} => 'post_meta',
			 *                {any_other_node} => 'post_meta',
			 *            ],
			 *        ];
			 * }
			 */
			apply_filters( 'ccb_core_synchronizer_post_api_map', $post_type_maps ),
			/**
			 * Get a collection of all taxonomy / API mappings.
			 *
			 * This is the main configuration for how the CCB API
			 * maps some of the nodes on an entity to custom taxonomies.
			 *
			 * @since 1.0.0
			 *
			 * @param array $taxonomy_maps {
			 *     A single map that defines the relationship between
			 *     some of the nodes on a CCB entity to custom taxonomies.
			 *
			 *        $map[ {post_type} ]['taxonomies']['hierarchical'][ {taxonomy} ] = [
			 *            'api_mapping' => {node},
			 *        ];
			 *
			 *        $map[ {post_type} ]['taxonomies']['nonhierarchical'][ {taxonomy} ] = [
			 *            'api_mapping' => [ {node} => {tag_name} ],
			 *        ];
			 * }
			 */
			apply_filters( 'ccb_core_synchronizer_taxonomy_api_map', $taxonomy_maps )
		);
	}

	/**
	 * Calls the CCB API and synchronizes post objects and
	 * taxonomies based on the mapping definitions from the
	 * custom post type and custom taxonomy classes.
	 *
	 * @return   array
	 */
	public function synchronize() {

		$result = [
			'success' => true,
		];

		// Set a flag to globally signal that a sync is in progress.
		set_transient( CCB_Core_Helpers::SYNC_STATUS_KEY, true, MINUTE_IN_SECONDS * 10 );

		// For each registered custom post type, call the
		// API and get a response object.
		foreach ( $this->map as $post_type => $settings ) {
			if ( ! empty( $settings['service'] ) ) {

				$data = ! empty( $settings['data'] ) ? $settings['data'] : [];

				/**
				 * Filters the API response for each service during the synchronization process.
				 *
				 * @since 1.0.0
				 *
				 * @param   array  $response The API response for a specific service call.
				 * @param   array  $settings The settings used for the API call.
				 * @param   string $post_type The current post type being synchronized.
				 */
				$response = apply_filters(
					'ccb_core_synchronizer_api_response',
					CCB_Core_API::instance()->get( $settings['service'], $data ),
					$settings,
					$post_type
				);

				if ( 'SUCCESS' === $response['status'] ) {

					// A successful API request was made, update the WordPress content.
					$update_result = $this->update_content( $response, $settings, $post_type );

					if ( false === $update_result['success'] ) {
						$result['success'] = false;
						$result['message'] = esc_html__( 'At least one API synchronization failed', 'ccb-core' );
					}
					$result['services'][ $settings['service'] ] = $update_result;

				} else {

					$result['success'] = false;
					$result['message'] = esc_html(
						sprintf(
							// Translators: Error message and error code.
							__( 'There was an API error: %1$s. Error code: %2$s', 'ccb-core' ),
							$response['error'],
							$response['code']
						)
					);
					break;

				}

			}
		}

		$result['timestamp'] = time();

		update_option( 'ccb_core_latest_sync_result', $result );
		// Delete the sync in progress flag.
		delete_transient( CCB_Core_Helpers::SYNC_STATUS_KEY );

		return $result;
	}

	/**
	 * Takes an API response and will either Insert, Update, or Delete
	 * content based on the settings and any applicable existing content.
	 *
	 * @param    array  $response An API response.
	 * @param    array  $settings The settings for the mapping.
	 * @param    string $post_type The post type being updated.
	 * @return   array
	 */
	public function update_content( $response, $settings, $post_type ) {

		$result = [
			'success' => true,
			'insert_update' => [
				'success' => true,
				'processed' => 0,
			],
			'delete' => [
				'success' => true,
				'processed' => 0,
			],
		];

		// The nodes are mapped down from the parent(s) to the
		// single child object. Get a collection of entities
		// that will map to a single post type.
		$entities = $this->get_entities( $response, $settings['nodes'] );

		// Get a collection of existing posts (previously imported) from WordPress.
		// This is organized by a key of an entitiy id (from CCB) and contains
		// the WordPress post_id and optional ccb_modified_date.
		$post_data = $this->get_existing_post_data( $post_type );

		// Organize the entities and existing post data into their
		// respective CRUD operations. This will return an array
		// with entities to insert for the first time, entities
		// to update (that already exist and have changed), and
		// posts that no longer exist in CCB and should be deleted.
		$organized_entities = $this->organize_entities( $entities, $post_data, $post_type );

		$insert_update_result = false;
		if ( ! empty( $organized_entities['insert_update'] ) ) {
			$insert_update_result = $this->insert_update_entities( $organized_entities['insert_update'], $settings, $post_type );
		}
		$delete_result = false;
		if ( ! empty( $organized_entities['delete'] ) ) {
			$delete_result = $this->delete_posts( $organized_entities['delete'] );
		}

		/**
		 * Whether or not we should clean up (delete) empty terms
		 * after a synchronization. Recommended to be true.
		 *
		 * @since 1.0.0
		 *
		 * @param   bool   $delete_terms Whether or not to delete empty terms.
		 * @param   array  $settings The settings for the current sync.
		 * @param   string $post_type The current post type.
		 */
		if ( apply_filters( 'ccb_core_synchronizer_delete_empty_terms', true, $settings, $post_type ) ) {
			$this->delete_empty_terms( $settings );
		}

		// Setup the result array.
		if ( ! empty( $insert_update_result ) ) {
			$result['insert_update'] = $insert_update_result;
			if ( false === $insert_update_result['success'] ) {
				// Set the overall result as a failure also, then
				// immediately return it.
				$result['success'] = false;
				return $result;
			}
		}

		if ( ! empty( $delete_result ) ) {
			$result['delete'] = $delete_result;
			if ( false === $delete_result['success'] ) {
				// Set the overall result as a failure also, then
				// immediately return it.
				$result['success'] = false;
				return $result;
			}
		}

		return $result;

	}

	/**
	 * Returns a collection of the CCB entities that will be processed.
	 *
	 * @param    array $response The standardized response array.
	 * @param    array $nodes A path to the single entity.
	 *
	 * @return   SimpleXML A collection of entities.
	 */
	public function get_entities( $response, $nodes ) {
		if ( ! empty( $nodes ) ) {
			$depth = count( $nodes ) - 1;
			$collection = $response['body']->response;
			for ( $i = 0; $i < $depth; $i++ ) {
				$collection = $collection->{$nodes[ $i ]};
			}
			return $collection;
		}
		return false;
	}

	/**
	 * Returns a collection of post data representing
	 * the existing (already imported) CCB entities.
	 *
	 * Return structure:
	 *
	 *     array[ $entity_id ][
	 *         'post_id' => $post_id,
	 *         'ccb_modified_date' => $ccb_modified_date,
	 *     ]
	 *
	 * @param    string $post_type The post type mapped to the CCB entitiy.
	 * @return   array
	 */
	public function get_existing_post_data( $post_type ) {
		// Batch the WP_Query for performance.
		$posts_per_page = 100;
		$offset = 0;
		$collection = [];

		do {
			$args = [
				'post_type' => $post_type,
				'post_status' => 'any',
				'posts_per_page' => $posts_per_page,
				'offset' => $offset,
				'orderby' => 'ID',
				'no_rows_found' => true,
				'fields' => 'ids',
			];

			$posts = new WP_Query( $args );
			$have_posts = ! empty( $posts->posts );

			if ( $have_posts ) {
				foreach ( $posts->posts as $post_id ) {
					// These are saved during the insert / update process (if possible)
					// in order to attempt future updates.
					$entity_id = get_post_meta( $post_id, 'entity_id', true );
					$ccb_modified_date = get_post_meta( $post_id, 'ccb_modified_date', true );

					if ( ! empty( $entity_id ) ) {
						$collection[ $entity_id ] = [
							'post_id' => $post_id,
							'ccb_modified_date' => $ccb_modified_date,
						];
					}
				}
			}

			$offset = $offset + $posts_per_page;

		} while ( $have_posts );

		return $collection;
	}

	/**
	 * Returns an array where entities are seperated into
	 * different database operations. Currently organized into
	 * `insert_update` and `delete` collections.
	 *
	 * Return structure:
	 *
	 *     array[
	 *         'insert_update' => array[$entities],
	 *         'delete' => array[$entities],
	 *     ]
	 *
	 * @param    SimpleXML $entities A parent collection of entities.
	 * @param    array     $post_data Existing posts (may be empty).
	 * @param    string    $post_type The post type to map to the entities.
	 * @return   array
	 */
	public function organize_entities( $entities, $post_data, $post_type ) {

		$collection = [
			'insert_update' => [],
			'delete' => [],
		];

		// Create a master collection of new entity ids
		// that were either inserted or updated
		// for quick filtering of existing posts
		// so that we can find posts to delete.
		$synced_entity_ids = [];

		foreach ( $entities->children() as $entity ) {

			$entity_id = $this->get_entity_id( $entity );

			// If the entity_id is a 32 character hash it means we cannot
			// map it to some known older post data because it either
			// doesn't have a CCB ID or else it doesn't give us a modified
			// date. So we hash its string value in order to get a unique
			// signature. We use this to determine if we "already have this
			// thing" so we don't need to insert / update it.
			if ( 32 === strlen( $entity_id ) ) {

				/**
				 * Whether or not this specific entity is allowed to insert.
				 *
				 * Helpful if you need to inspect an entity for custom business
				 * rules before allowing an insert to happen. For example, `group_profiles`
				 * will send us inactive groups. We do not want to insert inactive groups.
				 *
				 * @since 1.0.0
				 *
				 * @param   bool      $allowed Whether or not the entity is allowed.
				 * @param   SimpleXML $entity The entity to insert.
				 * @param   mixed     $entity_id The unique identifier from CCB.
				 * @param   string    $post_type The current post type.
				 */
				if ( apply_filters( 'ccb_core_synchronizer_entity_insert_allowed', true, $entity, $entity_id, $post_type ) ) {
					// If the unique id for the new entity couldn't be found
					// in the existing collection, it is new. Add it
					// to the insert collection.
					if ( ! array_key_exists( $entity_id, $post_data ) ) {
						$entity->addChild( 'post_id', 0 ); // This is a WordPress post ID, so this will be an insert.
						$collection['insert_update'][] = $entity;
					}
					// Regardless of whether or not this unique entity already exists
					// (i.e. regardless of an insert or update) we still record
					// that it was synced so that it doesn't get deleted.
					$synced_entity_ids[] = $entity_id;
				}
			} else {
				if ( array_key_exists( $entity_id, $post_data ) && ! empty( $entity->modified ) ) {
					/**
					 * Whether or not this specific entity is allowed to update.
					 *
					 * Helpful if you need to inspect an entity for custom business
					 * rules before allowing an update to happen.
					 *
					 * @since 1.0.0
					 *
					 * @param   bool      $allowed Whether or not the entity is allowed.
					 * @param   SimpleXML $entity The entity to insert.
					 * @param   mixed     $entity_id The unique identifier from CCB.
					 * @param   string    $post_type The current post type.
					 */
					if ( apply_filters( 'ccb_core_synchronizer_entity_update_allowed', true, $entity, $entity_id, $post_type ) ) {
						// If an existing post has a newer modified date from the API
						// add it to the insert_update collection with its existing post id.
						if ( strtotime( $entity->modified ) > strtotime( $post_data[ $entity_id ]['ccb_modified_date'] ) ) {
							$entity->addChild( 'post_id', $post_data[ $entity_id ]['post_id'] ); // This is a WordPress post ID, so this will be an update.
							$collection['insert_update'][] = $entity;
						}
						// Even though we may not have made an update (i.e. it currently
						// exists but hasn't changed), we still add this as a "synced" id
						// so that it doesn't get deleted.
						$synced_entity_ids[] = $entity_id;
					}
				} else {
					// The unique id for the new entity couldn't be found
					// in the existing collection, so it is new. Add it
					// to the insert_update collection.
					if ( apply_filters( 'ccb_core_synchronizer_entity_insert_allowed', true, $entity, $entity_id, $post_type ) ) {
						$entity->addChild( 'post_id', 0 ); // This is a WordPress post ID, so this will be an insert.
						$collection['insert_update'][] = $entity;
						$synced_entity_ids[] = $entity_id;
					}
				}
			}

		}

		// For each existing post, check to see if it was included
		// in this entity id collection from this most recent
		// API response. If it doesn't exist, it was deleted in CCB.
		foreach ( $post_data as $entity_id => $data ) {
			if ( ! in_array( $entity_id, $synced_entity_ids, true ) ) {
				/**
				 * Whether or not this specific entity is allowed to delete.
				 *
				 * Helpful if you need to inspect an entity for custom business
				 * rules before allowing a delete to happen.
				 *
				 * @since 1.0.0
				 *
				 * @param   bool   $allowed Whether or not the entity is allowed.
				 * @param   array  $data The WordPress post data.
				 * @param   mixed  $entity_id The unique identifier from CCB.
				 * @param   string $post_type The current post type.
				 */
				if ( apply_filters( 'ccb_core_synchronizer_entity_delete_allowed', true, $data, $entity_id, $post_type ) ) {
					$collection['delete'][] = $data['post_id'];
				}
			}
		}

		return $collection;
	}

	/**
	 * Returns a unique identifier for the CCB entitiy.
	 *
	 * If CCB included their own id (for example a group id) we
	 * use that. Otherwise we hash the string value of the entity.
	 *
	 * @param    SimpleXML $entity A single entity object.
	 * @return   mixed An integer id or string hash.
	 */
	public function get_entity_id( $entity ) {
		$entity_id = '';
		// As part of the insert / update process we sometimes append
		// a post_id to the entitiy. Ensure we remove it before hashing
		// the string value so that it represents what we received from CCB.
		if ( isset( $entity->post_id ) ) {
			unset( $entity->post_id );
		}

		// If an entity doesn't have a CCB ID, we cannot match it during
		// the sync process in order to perform an update. So instead,
		// create a hash of the entity and store it as a unique identifier.
		//
		// If an entity has an actual CCB ID, but *doesn't* have a modified
		// date, there's no point in storing the CCB ID. This is because
		// without a modified date we cannot determine if the entity has
		// changed since the previous sync. Instead, just create a hash of the entity.
		if ( ! empty( $entity->attributes() ) && ! empty( $entity->modified ) ) {
			foreach ( $entity->attributes() as $key => $value ) {
				if ( false !== stristr( $key, 'id' ) ) {
					$entity_id = (int) $value;
					break;
				}
			}
		}

		if ( ! $entity_id ) {
			$entity_id = md5( $entity->asXML() );
		}

		/**
		 * Filter the unique entity_id that we attempt to
		 * auto detect from the entity object
		 *
		 * @since 1.0.0
		 *
		 * @param   mixed     $entity_id Either an integer id or hash.
		 * @param   SimpleXML $entity The current entity object.
		 */
		return apply_filters( 'ccb_core_synchronizer_get_entity_id', $entity_id, $entity );
	}

	/**
	 * Inserts / Updates entities into WordPress.
	 *
	 * @param    array  $entities A collection of SimpleXML entity objects.
	 * @param    array  $settings The settings for the import.
	 * @param    string $post_type The current post type.
	 * @return   array
	 */
	public function insert_update_entities( $entities, $settings, $post_type ) {

		// Allow this script to run longer.
		set_time_limit( MINUTE_IN_SECONDS * 10 );
		$this->enable_optimizations();

		$result = [
			'success' => true,
			'processed' => 0,
			'message' => '',
		];

		foreach ( $entities as $entity ) {
			// Build a new $args array for each post insert
			// based on the settings config.
			$args = [
				'ID' => (int) $entity->post_id, // Will be set to 0 if this is an insert.
				'post_title' => '', // Default to empty, expected to be overriden by the settings.
				'post_content' => '', // Default to empty, expected to be overriden by the settings.
				'post_status' => 'publish',
				'post_type' => $post_type,
				'meta_input' => [],
			];

			// Inspect each field defined in the settings. If it's a
			// post_meta node, add it to the meta_input array, otherwise
			// assume it's part of the parent post object.
			foreach ( $settings['fields'] as $node => $field ) {
				if ( 'post_meta' === $field ) {
					$args['meta_input'][ $node ] = $this->auto_cast( $entity->{$node} );
				} else {
					$args[ $field ] = $this->auto_cast( $entity->{$node} );
				}
			}

			// Attempt to store additional meta related to synchronization.
			// If CCB provided a CCB ID for this entitiy, save it. If CCB
			// provided a `modified` node, save it. We use them to attempt
			// updates to the entity when needed.
			$args['meta_input']['entity_id'] = $this->get_entity_id( $entity );
			if ( isset( $entity->modified ) ) {
				$args['meta_input']['ccb_modified_date'] = $this->auto_cast( $entity->modified );
			}

			/**
			 * Filters the `wp_insert_post` $args for each entity.
			 *
			 * @since 1.0.0
			 *
			 * @param   array     $args The `wp_insert_post` args.
			 * @param   SimpleXML $entity The entity object.
			 * @param   array     $settings The current settings for the sync.
			 * @param   string    $post_type The current post type.
			 */
			$args = apply_filters( 'ccb_core_synchronizer_insert_post_args', $args, $entity, $settings, $post_type );

			/**
			 * Before the insert / update is processed.
			 *
			 * @since 1.0.0
			 *
			 * @param   SimpleXML $entity The entity object to be inserted / updated.
			 * @param   array     $settings The current settings for the import.
			 * @param   array     $args The args that will be used for wp_insert_post.
			 * @param   string    $post_type The current post type.
			 */
			do_action( 'ccb_core_before_insert_update_post', $entity, $settings, $args, $post_type );

			// Perform an insert (or update if we included a post id).
			$post_id = wp_insert_post( $args, true );

			if ( is_wp_error( $post_id ) ) {
				$result['success'] = false;
				$result['message'] = esc_html(
					sprintf(
						__( 'Inserting / Updating a post failed for the %1$s post type. Error: %2$s', 'ccb-core' ),
						$post_type,
						$post_id->get_error_message()
					)
				);
				$this->disable_optimizations();
				return $result;
			}

			// Prepare hierarchial taxonomies by ensuring the term id
			// already exists and set terms ids.
			$prepared_terms = $this->prepare_terms( $entity, $settings );
			if ( ! empty( $prepared_terms ) ) {
				foreach ( $prepared_terms as $taxonomy => $term_array ) {
					$term_set_results = wp_set_object_terms( $post_id, $term_array, $taxonomy );
				}
			}

			/**
			 * After the insert / update is processed.
			 *
			 * Useful if you need to run custom business logic after a post is
			 * inserted / updated. For example, this is how we import and attach
			 * featured images for Groups.
			 *
			 * @since 1.0.0
			 *
			 * @param   SimpleXML $entity The entity object to be inserted / updated.
			 * @param   array     $settings The current settings for the import.
			 * @param   array     $args The args that will be used for wp_insert_post.
			 * @param   string    $post_type The current post type.
			 * @param   int       $post_id The new post id (or existing post id if update).
			 */
			do_action( 'ccb_core_after_insert_update_post', $entity, $settings, $args, $post_type, $post_id );

			$result['processed'] += 1;

		}

		$this->disable_optimizations();

		return $result;
	}

	/**
	 * Deletes any posts that no longer exist in CCB.
	 *
	 * @param    array $post_ids A collection of post ids to delete.
	 * @return   array
	 */
	public function delete_posts( $post_ids ) {
		$result = [
			'success' => true,
			'processed' => 0,
			'message' => '',
		];

		foreach ( $post_ids as $post_id ) {

			/**
			 * Filters whether or not the attachment for this post should
			 * also be deleted.
			 *
			 * @since 1.0.3
			 *
			 * @param   bool  $delete  Whether or not the attachment should be deleted.
			 * @param   int   $post_id The post id.
			 */
			if ( apply_filters( 'ccb_core_synchronizer_delete_attachment', true, $post_id ) ) {
				$attachment_id = get_post_thumbnail_id( $post_id );
				if ( $attachment_id ) {
					wp_delete_attachment( $attachment_id, true );
				}
			}

			$deleted = wp_delete_post( $post_id, true );
			if ( ! $deleted ) {
				$result['success'] = false;
				$result['message'] = esc_html__( 'There was an error while attempting to delete orhpaned posts', 'ccb-core' );
				return $result;
			}
			$result['processed'] += 1;

		}
		return $result;
	}

	/**
	 * Cleans up any orphaned terms after a sync.
	 *
	 * @param    array $settings Definitions of taxonomies.
	 * @return   void
	 */
	public function delete_empty_terms( $settings ) {
		// Ensure we get empty terms.
		$args = [
			'hide_empty' => false,
		];

		// Build a collection of all taxonomies configured.
		if ( ! empty( $settings['taxonomies']['hierarchical'] ) ) {
			foreach ( $settings['taxonomies']['hierarchical'] as $taxonomy => $node ) {
				$args['taxonomy'][] = $taxonomy;
			}
		}
		if ( ! empty( $settings['taxonomies']['nonhierarchical'] ) ) {
			foreach ( $settings['taxonomies']['nonhierarchical'] as $taxonomy => $node ) {
				$args['taxonomy'][] = $taxonomy;
			}
		}

		// Delete any terms with a 0 count.
		$terms_query = new WP_Term_Query( $args );
		foreach ( $terms_query->get_terms() as $term ) {
			if ( 0 === $term->count ) {
				wp_delete_term( $term->term_id, $term->taxonomy );
			}
		}
	}

	/**
	 * Returns an array of hierarchical and nonhierarchical terms
	 * that are ready for use by wp_insert_post.
	 *
	 * @param    SimpleXML $entity An entity object.
	 * @param    array     $settings The taxonomy settings.
	 * @return   array
	 */
	public function prepare_terms( $entity, $settings ) {
		$categories = [];
		$tags = [];

		if ( ! empty( $settings['taxonomies']['hierarchical'] ) ) {
			foreach ( $settings['taxonomies']['hierarchical'] as $taxonomy => $node ) {
				$term_name = $this->auto_cast( $entity->{$node} );
				if ( $term_name ) {
					// phpcs:ignore
					$term = term_exists( $term_name, $taxonomy );
					if ( ! $term ) {
						$term = wp_insert_term( $term_name, $taxonomy );
					}
					if ( $term && ! is_wp_error( $term ) ) {
						$categories[ $taxonomy ] = (int) $term['term_id'];
					}
				} else {
					$categories[ $taxonomy ] = '';
				}
			}
		}

		if ( ! empty( $settings['taxonomies']['nonhierarchical'] ) ) {
			foreach ( $settings['taxonomies']['nonhierarchical'] as $taxonomy => $node_array ) {
				foreach ( $node_array as $node => $tag ) {
					$tag_is_set = $this->auto_cast( $entity->{$node} );
					if ( $tag_is_set ) {
						$tags[ $taxonomy ][] = $tag;
					} else {
						$tags[ $taxonomy ][] = '';
					}
				}
			}
		}

		return array_merge( $categories, $tags );
	}

	/**
	 * Attempt to cast a SimpleXML node to a strong type.
	 * Will recursively process arrays.
	 *
	 * @param    SimpleXML $node A single node on the entitiy.
	 * @return   mixed
	 */
	public function auto_cast( $node ) {
		// If the node has children, convert it to an array
		// and recursively process the child nodes.
		if ( $node->children()->count() ) {
			$array = [];
			// If the node happens to have an id attribute,
			// transform it into a property so that it's not lost.
			$id = false;
			if ( ! empty( $node->attributes() ) ) {
				foreach ( $node->attributes() as $key => $value ) {
					if ( false !== stristr( $key, 'id' ) ) {
						$id = (int) $value;
						break;
					}
				}
			}
			if ( $id ) {
				$array['id'] = $id;
			}
			foreach ( $node->children() as $child ) {
				$array[ $child->getName() ] = $this->auto_cast( $child );
			}
			return $array;
		} elseif ( 'true' === (string) $node ) {
			return true;
		} elseif ( 'false' === (string) $node ) {
			return false;
		} elseif ( strlen( (int) $node ) === strlen( (string) $node ) ) {
			return (int) $node;
		} else {
			return (string) $node;
		}
	}

	/**
	 * Configure some common optimizations for bulk
	 * insert / update processing.
	 *
	 * @return void
	 */
	private function enable_optimizations() {
		// Remove expensive unneeded actions.
		remove_action( 'do_pings', 'do_all_pings', 10, 1 );

		// Temporarily disable counting for performance.
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		wp_suspend_cache_addition( true );

		// Unit tests rollback database transactions, do not
		// alter commit settings for unit tests.
		if ( ! defined( 'IS_UNIT_TEST' ) ) {
			global $wpdb;
			// Temporarily disable autocommit.
			$wpdb->query( 'SET autocommit = 0;' ); // db call ok; no cache ok.
		}
	}

	/**
	 * Remove the optimizations after processing is complete.
	 *
	 * @return void
	 */
	private function disable_optimizations() {

		// Unit tests rollback database transactions, do not
		// alter commit settings for unit tests.
		if ( ! defined( 'IS_UNIT_TEST' ) ) {
			global $wpdb;
			// Commit the database operations now.
			$wpdb->query( 'COMMIT;' ); // db call ok; no cache ok.
			// Re-enable autocommit.
			$wpdb->query( 'SET autocommit = 1;' ); // db call ok; no cache ok.
		}

		// Re-enable counting.
		wp_suspend_cache_addition( false );
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		// Re-attach expensive actions.
		add_action( 'do_pings', 'do_all_pings', 10, 1 );
	}

}
CCB_Core_Synchronizer::instance();
