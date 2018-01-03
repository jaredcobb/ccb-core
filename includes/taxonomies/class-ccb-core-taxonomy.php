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
 * Abstract custom taxonomy class used to define custom taxonomies
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes/taxonomies
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
abstract class CCB_Core_Taxonomy {

	/**
	 * Name of the taxonomy
	 *
	 * @var   string
	 */
	public $name;

	/**
	 * Object types for this taxonomy
	 *
	 * @var   array
	 */
	public $object_types = [];

	/**
	 * Initialize the class
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_filter( 'ccb_core_synchronizer_taxonomy_api_map', [ $this, 'get_taxonomy_map' ] );
	}

	/**
	 * Register the custom taxonomy
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy( $this->name, $this->object_types, static::get_taxonomy_args() );
	}

	/**
	 * Define the mapping of CCB API fields to this taxonomy
	 *
	 * @since    1.0.0
	 * @param    array $map A collection of mappings from the API to WordPress.
	 * @return   array
	 */
	public function get_taxonomy_map( $map ) {
		if ( ! empty( $this->object_types ) ) {
			foreach ( $this->object_types as $object_type ) {
				$taxonomy_args = static::get_taxonomy_args();
				$hierarchical = ! empty( $taxonomy_args['hierarchical'] ) ? 'hierarchical' : 'nonhierarchical';
				$map[ $object_type ]['taxonomies'][ $hierarchical ][ $this->name ] = $taxonomy_args['api_mapping'];
			}
		}
		return $map;
	}

}
