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
	 * @var array
	 */
	private $map;

	/**
	 * An instance of the CCB_Core_API class
	 *
	 * @var CCB_Core_API
	 */
	private $api;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Wait to initialize the map until after the plugins / themes are fully
		// loaded so that all post types and taxonomies have been registered.
		add_action( 'init', [ $this, 'initialize_map' ] );

		$this->api = new CCB_Core_API();
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
			apply_filters( 'ccb_core_post_type_map', $post_type_maps ),
			apply_filters( 'ccb_core_taxonomy_map', $taxonomy_maps )
		);
	}

	/**
	 * Calls the CCB API and synchronizes post objects and
	 * taxonomies based on the mapping definitions from the
	 * custom post type and custom taxonomy classes.
	 *
	 * @return bool True on success
	 */
	public function synchronize() {

		// For each registered custom post type, call the API and
		// get a response object.
		foreach ( $this->map as $post_type => $settings ) {
			if ( ! empty( $settings['service'] ) ) {
				$data = ! empty( $settings['data'] ) ? $settings['data'] : [];
				$response = $this->api->get( $settings['service'], $data );
				if ( 'SUCCESS' === $response['status'] ) {
					$update_successful = $this->update_content( $post_type, $settings, $response );
				}
			}
		}

		return true;
	}

	/**
	 * Takes an API response and will either Insert, Update, or Delete
	 * content based on the settings and any applicable existing content.
	 *
	 * @param    string $post_type The post type being updated.
	 * @param    array  $settings The settings for the mapping.
	 * @param    array  $response An API response.
	 * @return   void
	 */
	private function update_content( $post_type, $settings, $response ) {

		// The nodes are mapped down from the parent(s) to the
		// single child object. Get a collection of entities
		// that will map to a single post type.
		$entities = $this->get_entities( $response, $settings['nodes'] );

		// Get a collection of existing posts (previously imported) from WordPress.
		// This is organized by entitiy id (from CCB) and contains the WordPress
		// post_id and optional modified date.
		$post_data = $this->get_existing_post_data( $post_type );

		// Organize the entities and existing post data into their
		// respective CRUD operations. This will return an array
		// with entities to insert for the first time, entities
		// to update (that already exist and have changed), and
		// posts that no longer exist in CCB and should be deleted.
		$organized_entities = $this->organize_entities( $entities, $post_data, $post_type );

		error_log( 'before insert ' . $post_type . ': ' . size_format( memory_get_usage() ) );
		if ( ! empty( $organized_entities['insert_update'] ) ) {
			$insert_result = $this->insert_update_entities( $organized_entities['insert_update'], $post_type, $settings );
		}
		error_log( 'after insert ' . $post_type . ': ' . size_format( memory_get_usage() ) );
		error_log( 'before delete ' . $post_type . ': ' . size_format( memory_get_usage() ) );
		if ( ! empty( $organized_entities['delete'] ) ) {
			$delete_result = $this->delete_posts( $organized_entities['delete'] );
		}
		error_log( 'after delete ' . $post_type . ': ' . size_format( memory_get_usage() ) );

		$this->delete_empty_terms( $settings );

	}

	private function get_entities( $response, $nodes ) {
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

	private function get_existing_post_data( $post_type ) {
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
				'no_rows_found' => true,
				'fields' => 'ids',
			];

			$posts = new WP_Query( $args );

			if ( ! empty( $posts->posts ) ) {
				foreach ( $posts->posts as $post_id ) {

					// These are saved during the insert / update process (of possible)
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

		} while ( count( $posts->posts ) );

		return $collection;
	}

	private function organize_entities( $entities, $post_data, $post_type ) {

		$collection = [
			'insert_update' => [],
			'delete' => [],
		];

		// Create a master collection of new entity ids
		// that were either inserted or updated
		// for quick filtering of existing posts
		// so that we can find posts to delete.
		$synced_entity_ids = [];

		error_log( 'before organize ' . $post_type . ': ' . size_format( memory_get_usage() ) );
		foreach ( $entities->children() as $entity ) {

			$entity_id = $this->get_entity_id( $entity );

			// If the entity_id is a 32 character hash it means we cannot
			// map it to some known older post data because it either
			// doesn't have a CCB ID or else it doesn't give us a modified
			// date. So we hash its string value in order to get a unique
			// signature. We use this to determine if we "already have this
			// thing" so we don't need to insert / update it.
			if ( 32 === strlen( $entity_id ) ) {
				if ( apply_filters( 'ccb_core_entity_insert_allowed', true, $entity_id, $entity, $post_type ) ) {
					// If the unique id for the new entity couldn't be found
					// in the existing collection, it is new. Add it
					// to the insert collection.
					if ( ! array_key_exists( $entity_id, $post_data ) ) {
						$entity->addChild( 'post_id', 0 ); // This is a WordPress post ID, so this will be an insert.
						$collection['insert_update'][] = $entity;
					}
					// Regardless of whether or not this unique entity exists
					// (i.e. regardless of an insert or update) we still record
					// that it was synced so that it doesn't get deleted.
					$synced_entity_ids[] = $entity_id;
				}
			} else {
				if ( array_key_exists( $entity_id, $post_data ) && ! empty( $entity->modified ) ) {
					if ( apply_filters( 'ccb_core_entity_update_allowed', true, $entity_id, $entity, $post_type ) ) {
						// If an existing post has a newer modified date from the API
						// add it to the insert_update collection with its existing post id.
						if ( strtotime( $entity->modified ) > strtotime( $post_data[ $entity_id ]['ccb_modified_date'] ) ) {
							$entity->addChild( 'post_id', $post_data[ $entity_id ]['post_id'] ); // This is a WordPress post ID, so this will be an update.
							$collection['insert_update'][] = $entity;
						}
						// Even though we may not have made an update
						// we still add this as a "synced" id because it exists.
						$synced_entity_ids[] = $entity_id;
					}
				} else {
					// The unique id for the new entity couldn't be found
					// in the existing collection, so it is new. Add it
					// to the insert_update collection.
					if ( apply_filters( 'ccb_core_entity_insert_allowed', true, $entity_id, $entity, $post_type ) ) {
						$entity->addChild( 'post_id', 0 ); // This is a WordPress post ID, so this will be an insert.
						$collection['insert_update'][] = $entity;
						$synced_entity_ids[] = $entity_id;
					}
				}
			}

		}

		error_log( 'after organize ' . $post_type . ': ' . size_format( memory_get_usage() ) );
		// For each existing post, check to see if it was included
		// in the new entity id collection from this most recent
		// API response. If it doesn't exist, it was deleted.
		foreach ( $post_data as $entity_id => $data ) {
			if ( ! in_array( $entity_id, $synced_entity_ids, true ) ) {
				if ( apply_filters( 'ccb_core_entity_delete_allowed', true, $entity_id, $data, $post_type ) ) {
					$collection['delete'][] = $data['post_id'];
				}
			}
		}

		return $collection;
	}

	private function get_entity_id( $entity ) {
		// If an entity doesn't have a CCB ID, we cannot match it during
		// the sync process in order to perform an update. So instead,
		// create a hash of the entity and store it as a unique identifier.
		//
		// If an entity has an actual CCB ID, but *doesn't* have a modified
		// date, there's no point in storing the CCB ID. This is because
		// without a modified date we cannot determine if the entity has
		// changed since the previous sync. Instead, just create a has of the entity.
		if ( ! empty( $entity->attributes() ) && ! empty( $entity->modified ) ) {
			foreach ( $entity->attributes() as $key => $value ) {
				if ( false !== stristr( $key, 'id' ) ) {
					return (int) $value;
				}
			}
		}
		if ( isset( $entity->post_id ) ) {
			unset( $entity->post_id );
		}
		return md5( $entity->asXML() );
	}

	private function insert_update_entities( $entities, $post_type, $settings ) {
		global $wpdb;
		// Temporarily disable counting for performance.
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		wp_suspend_cache_addition( true );
		remove_action( 'do_pings', 'do_all_pings', 10, 1 );

		error_log( 'after setting optimizations ' . $post_type . ': ' . size_format( memory_get_usage() ) );

		// Temporarily disable autocommit.
		$wpdb->query( 'SET autocommit = 0;' ); // db call ok; no cache ok.
		set_time_limit( 600 );

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
				'tax_input' => [],
			];

			// Inspect each field defined in the settings. If it's a
			// post_meta element, add it to the meta_input array, otherwise
			// assume it's part of the parent post object.
			foreach ( $settings['fields'] as $element => $field ) {
				if ( 'post_meta' === $field ) {
					$args['meta_input'][ $element ] = $this->auto_cast( $entity->{$element} );
				} else {
					$args[ $field ] = $this->auto_cast( $entity->{$element} );
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

			// Prepare hierarchial taxonomies by ensuring the term id
			// already exists and set terms ids.
			$prepared_terms = $this->prepare_terms( $entity, $settings );
			if ( ! empty( $prepared_terms ) ) {
				$args['tax_input'] = $prepared_terms;
			}

			// If this is an update, we need to remove all term
			// relationships in order to ensure we are in
			// sync with the most recent entity. Otherwise if a
			// relationship was removed by CCB, it'll persist in WordPress.
			if ( $args['ID'] ) {
				if ( ! empty( $settings['taxonomies']['hierarchical'] ) ) {
					foreach ( $settings['taxonomies']['hierarchical'] as $taxonomy => $node ) {
						wp_delete_object_term_relationships( $args['ID'], $taxonomy );
					}
				}
				if ( ! empty( $settings['taxonomies']['nonhierarchical'] ) ) {
					foreach ( $settings['taxonomies']['nonhierarchical'] as $taxonomy => $node ) {
						wp_delete_object_term_relationships( $args['ID'], $taxonomy );
					}
				}
			}

			do_action( 'ccb_core_before_insert_update_post', $entity, $settings, $args, $post_type );

			// Perform an insert (or update if we included a post id).
			$post_id = wp_insert_post( $args, true );

			do_action( 'ccb_core_after_insert_update_post', $entity, $settings, $args, $post_type, $post_id );
		}

		error_log( 'after loop, before commit ' . $post_type . ': ' . size_format( memory_get_usage() ) );

		// Commit the database operations now.
		$wpdb->query( 'COMMIT;' ); // db call ok; no cache ok.
		// Re-enable autocommit.
		$wpdb->query( 'SET autocommit = 1;' ); // db call ok; no cache ok.

		error_log( 'after loop, before stop_the_insanity ' . $post_type . ': ' . size_format( memory_get_usage() ) );

		// Re-enable counting.
		wp_suspend_cache_addition( false );
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
		$this->stop_the_insanity();
	}

	private function delete_posts( $post_ids ) {
		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		return true;
	}

	private function delete_empty_terms( $settings ) {
		$args = [
			'hide_empty' => false,
		];

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

		$terms_query = new WP_Term_Query( $args );
		foreach ( $terms_query->get_terms() as $term ) {
			if ( 0 === $term->count ) {
				wp_delete_term( $term->term_id, $term->taxonomy );
			}
		}

		return true;
	}

	private function prepare_terms( $entity, $settings ) {
		$categories = [];
		$tags = [];

		if ( ! empty( $settings['taxonomies']['hierarchical'] ) ) {
			foreach ( $settings['taxonomies']['hierarchical'] as $taxonomy => $node ) {
				$term_name = $this->auto_cast( $entity->{$node} );
				if ( $term_name ) {
					$term = term_exists( $term_name, $taxonomy );
					if ( ! $term ) {
						$term = wp_insert_term( $term_name, $taxonomy );
					}
					if ( $term && ! is_wp_error( $term ) ) {
						$categories[ $taxonomy ][] = $term['term_taxonomy_id'];
					}
				}
			}
		}

		if ( ! empty( $settings['taxonomies']['nonhierarchical'] ) ) {
			foreach ( $settings['taxonomies']['nonhierarchical'] as $taxonomy => $node_array ) {
				foreach ( $node_array as $node => $tag ) {
					$tag_is_set = $this->auto_cast( $entity->{$node} );
					if ( $tag_is_set ) {
						$tags[ $taxonomy ][] = $tag;
					}
				}
			}
		}

		return array_merge( $categories, $tags );
	}

	private function auto_cast( $element ) {
		if ( $element->children()->count() ) {
			$array = [];
			// If the node happens to have an id attribute,
			// transform it into a property so that it's not lost.
			$id = false;
			if ( ! empty( $element->attributes() ) ) {
				foreach ( $element->attributes() as $key => $value ) {
					if ( false !== stristr( $key, 'id' ) ) {
						$id = (int) $value;
						break;
					}
				}
			}
			if ( $id ) {
				$array['id'] = $id;
			}
			foreach ( $element->children() as $child ) {
				$array[ $child->getName() ] = $this->auto_cast( $child );
			}
			return $array;
		} elseif ( 'true' === (string) $element ) {
			return true;
		} elseif ( 'false' === (string) $element ) {
			return false;
		} elseif ( strlen( (int) $element ) === strlen( (string) $element ) ) {
			return (int) $element;
		} else {
			return (string) $element;
		}
	}

	/**
	 * Clear all of the caches for memory management
	 */
	private function stop_the_insanity() {
		global $wpdb, $wp_object_cache;

		$wpdb->queries = array();

		if ( is_object( $wp_object_cache ) ) {
			$wp_object_cache->group_ops = array();
			$wp_object_cache->stats = array();
			$wp_object_cache->memcache_debug = array();
			$wp_object_cache->cache = array();

			if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
				$wp_object_cache->__remoteset();
			}
		}
	}
}
