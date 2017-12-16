<?php
/**
 * Custom post types used in this plugin
 *
 * @link       https://www.wpccb.com
 * @since      1.0.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 */

/**
 * Abstract custom post type used help define all CPTs
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
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
	 * The specific custom post type options
	 *
	 * @var   array
	 */
	protected $cpt_options = array();

	/**
	 * Initialize the class
	 */
	public function __construct() {

		$plugin_options = CCB_Core_Helpers::instance()->get_options();

		// If this CPT is enabled, merge the defaults and set the registration hook.
		if ( ! empty( $plugin_options[ $this->name ]['enabled'] ) ) {
			$this->cpt_options = wp_parse_args( $this->get_user_cpt_options( $plugin_options ), $this->get_cpt_defaults() );
			add_action( 'init', array( $this, 'register_post_type' ) );
		}

	}

	/**
	 * Get the dynamic CPT options based on
	 * what the user may have set
	 *
	 * @param    array $plugin_options The entire options array.
	 * @return   array
	 */
	protected function get_user_cpt_options( $plugin_options ) {

		$user_cpt_options = array();

		if ( ! empty( $plugin_options[ $this->name ]['cpt_options']['name'] ) ) {
			$user_cpt_options['labels']['name'] = $plugin_options[ $this->name ]['cpt_options']['name'];
			$user_cpt_options['labels']['all_items'] = sprintf( __( 'All %s', 'ccb-core' ), $plugin_options[ $this->name ]['cpt_options']['name'] );
			$user_cpt_options['labels']['search_items'] = sprintf( __( 'Search %s', 'ccb-core' ), $plugin_options[ $this->name ]['cpt_options']['name'] );
		}

		if ( ! empty( $plugin_options[ $this->name ]['cpt_options']['singular_name'] ) ) {
			$user_cpt_options['labels']['singular_name'] = $plugin_options[ $this->name ]['cpt_options']['singular_name'];
			$user_cpt_options['labels']['add_new_item'] = sprintf( __( 'Add New %s', 'ccb-core' ), $plugin_options[ $this->name ]['cpt_options']['singular_name'] );
			$user_cpt_options['labels']['edit_item'] = sprintf( __( 'Edit %s', 'ccb-core' ), $plugin_options[ $this->name ]['cpt_options']['singular_name'] );
			$user_cpt_options['labels']['new_item'] = sprintf( __( 'New %s', 'ccb-core' ), $plugin_options[ $this->name ]['cpt_options']['singular_name'] );
			$user_cpt_options['labels']['view_item'] = sprintf( __( 'View %s', 'ccb-core' ), $plugin_options[ $this->name ]['cpt_options']['singular_name'] );
		}

		if ( ! empty( $plugin_options[ $this->name ]['cpt_options']['slug'] ) ) {
			$user_cpt_options['rewrite'] = array( 'slug' => $plugin_options[ $this->name ]['cpt_options']['slug'] );
			$user_cpt_options['has_archive'] = $plugin_options[ $this->name ]['cpt_options']['slug'];
		}

		// The remaining options are boolean, so directly set their values if the option is set.
		if ( isset( $plugin_options[ $this->name ]['cpt_options']['publicly_queryable'] ) ) {
			$user_cpt_options['publicly_queryable'] = $plugin_options[ $this->name ]['cpt_options']['publicly_queryable'];
		}

		if ( isset( $plugin_options[ $this->name ]['cpt_options']['exclude_from_search'] ) ) {
			$user_cpt_options['exclude_from_search'] = $plugin_options[ $this->name ]['cpt_options']['exclude_from_search'];
		}

		if ( isset( $plugin_options[ $this->name ]['cpt_options']['show_ui'] ) ) {
			$user_cpt_options['show_ui'] = $plugin_options[ $this->name ]['cpt_options']['show_ui'];
		}

		if ( isset( $plugin_options[ $this->name ]['cpt_options']['show_in_nav_menus'] ) ) {
			$user_cpt_options['show_in_nav_menus'] = $plugin_options[ $this->name ]['cpt_options']['show_in_nav_menus'];
		}

		return $user_cpt_options;

	}

	/**
	 * Register the custom post type
	 *
	 * @access    public
	 * @since     1.0.0
	 * @return    void
	 */
	public function register_post_type() {
		register_post_type( $this->name, $this->cpt_options );
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
	 * Setup the default CPT options
	 *
	 * @since    1.0.0
	 * @return   array   Default options for register_post_type
	 */
	abstract public function get_cpt_defaults();

}
