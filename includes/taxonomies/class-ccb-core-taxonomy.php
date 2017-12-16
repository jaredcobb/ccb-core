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
	public $object_types = array();

	/**
	 * Initialize the class
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register the custom taxonomy
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy( $this->name, $this->object_types, static::get_taxonomy_mapping() );
	}

	/**
	 * Register the taxonomy.
	 */
	abstract public static function get_taxonomy_mapping();

}
