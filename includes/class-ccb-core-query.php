<?php
/**
 * Custom Query object for better database performance.
 *
 * @link       https://www.wpccb.com
 * @since      1.0.8
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Custom Query object for better database performance.
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Query {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.8
	 */
	public function __construct() {
	}

	/**
	 * Returns an array of posts (by post type) with post meta
	 * keys and values as children (if they exist).
	 *
	 * Combine the post type and post meta queries into a single
	 * call for performance.
	 *
	 * @param string $post_type The post type.
	 *
	 * @return array
	 */
	public function get_existing_post_data( $post_type ) {
		global $wpdb;
		$results = [];

		$query_results = $wpdb->get_results(
			$wpdb->prepare(
				"
					SELECT {$wpdb->posts}.ID, {$wpdb->postmeta}.meta_key, {$wpdb->postmeta}.meta_value
					FROM {$wpdb->posts}
					LEFT JOIN {$wpdb->postmeta}
					ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
					WHERE {$wpdb->posts}.post_type = %s
					AND {$wpdb->posts}.post_status = 'publish'
					ORDER BY {$wpdb->posts}.ID
				",
				$post_type
			)
		);

		if ( ! empty( $query_results ) && is_array( $query_results ) ) {
			foreach ( $query_results as $result ) {
				if ( ! empty( $result->meta_key ) ) {
					$results[ $result->ID ][ $result->meta_key ] = $result->meta_value; // phpcs:ignore
				}
			}
		}

		return $results;
	}
}
