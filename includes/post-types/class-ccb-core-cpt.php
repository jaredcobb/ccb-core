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
 * Abstract custom post type used help define all CPTs
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes/post-types
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
abstract class CCB_Core_CPT {

	/**
	 * Name of the post type
	 *
	 * @var   string
	 */
	public $name;

	/**
	 * Whether or not this post type is enabled. (Overriden by child class).
	 *
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * Initialize the class
	 */
	public function __construct() {

		add_filter( 'ccb_core_settings_post_definitions', [ $this, 'get_post_settings_definitions' ] );

		// If this custom post type is enabled, merge the defaults and set the registration hook.
		if ( $this->enabled ) {
			add_action( 'init', [ $this, 'register_post_type' ] );
			add_filter( 'ccb_core_synchronizer_post_api_map', [ $this, 'get_post_api_map' ] );
		}

	}

	/**
	 * Register the custom post type
	 *
	 * @access    public
	 * @since     1.0.0
	 * @return    void
	 */
	public function register_post_type() {
		register_post_type( $this->name, $this->get_post_args() );
	}

	/**
	 * Get the post type object.
	 *
	 * @return   object
	 */
	public function get_post_type_object() {
		return get_post_type_object( $this->name );
	}

	/**
	 * Setup the custom post type $args
	 *
	 * @since    1.0.0
	 * @return   array   $args for register_post_type
	 */
	abstract public function get_post_args();

	/**
	 * Setup the default CPT options
	 *
	 * @since    1.0.0
	 * @param    array $settings The settings definitions.
	 * @return   array The configuration for the options settable by the user
	 */
	abstract public function get_post_settings_definitions( $settings );

	/**
	 * Define the mapping of CCB API fields to the Post fields
	 *
	 * @since    1.0.0
	 * @param    array $maps A collection of mappings from the API to WordPress.
	 * @return   array
	 */
	abstract public function get_post_api_map( $maps );

}
