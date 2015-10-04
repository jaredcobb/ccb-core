<?php
/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://jaredcobb.com/ccb-core
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Admin extends CCB_Core_Plugin {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.9.0
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Initialize the Settings Menu and Page
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function initialize_settings_menu() {

		$settings = new CCB_Core_Settings();
		$settings_definitions = $settings->get_settings_definitions();
		$settings_page = new CCB_Core_Settings_Page( $this->plugin_settings_name );

		add_menu_page( $this->plugin_display_name, $this->plugin_short_display_name, 'manage_options', $this->plugin_settings_name, '__return_null', 'dashicons-update', '80.9' );

		if ( is_array( $settings_definitions ) && ! empty( $settings_definitions ) ) {
			foreach ( $settings_definitions as $page_id => $page ) {
				$settings_page = new CCB_Core_Settings_Page( $page_id, $page );
				add_submenu_page( $this->plugin_settings_name, $page['page_title'], $page['page_title'], 'manage_options', $page_id, array( $settings_page, 'render_page' ) );
			}
		}
	}

	/**
	 * Initialize the Settings
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function initialize_settings() {

		$settings = new CCB_Core_Settings();
		$settings_definitions = $settings->get_settings_definitions();

		if ( is_array( $settings_definitions ) && ! empty( $settings_definitions ) ) {
			foreach ( $settings_definitions as $page_id => $page ) {

				register_setting( $page_id, $this->plugin_settings_name, array( $settings, 'validate_settings' ) );

				if ( isset( $page['sections'] ) && ! empty( $page['sections'] ) ) {
					foreach ( $page['sections'] as $section_id => $section ) {

						$settings_section = new CCB_Core_Settings_Section( $section_id, $section );
						add_settings_section( $section_id, $section['section_title'], array( $settings_section, 'render_section' ), $page_id );

						if ( isset( $section['fields'] ) && ! empty( $section['fields'] ) ) {
							foreach ( $section['fields'] as $field_id => $field ) {

								$settings_field = new CCB_Core_Settings_Field( $field_id, $field );
								add_settings_field( $field_id, $field['field_title'], array( $settings_field, 'render_field' ), $page_id, $section_id );

							}
						}

					}
				}

			}
		}

	}

	/**
	 * Just before the settings are saved, check for changes
	 * that would require us to flush the rewrite rules
	 *
	 * @param     array     $new_settings
	 * @param     array     $previous_settings
	 * @access    public
	 * @since     0.9.6
	 * @return    array
	 */
	public function update_settings_callback( $new_settings, $previous_settings ) {

		// create a collection of settings that, if they change, should
		// trigger a flush_rewrite_rules event
		$setting_array = array(
			'groups-enabled',
			'groups-slug',
			'calendar-enabled',
			'calendar-slug',
		);

		foreach ( $setting_array as $setting ) {
			if ( isset( $new_settings[ $setting ] ) ) {
				if ( ! isset( $previous_settings[ $setting ] ) || $new_settings[ $setting ] !== $previous_settings[ $setting ] ) {
					// schedule an event to flush the rewrite rules on the next page load because the settings aren't quite saved yet
					wp_schedule_single_event( time(), 'schedule_flush_rewrite_rules' );
				}
			}
		}

		return $new_settings;
	}

	/**
	 * Simple callback function for flushing the rewrite rules
	 *
	 * @access    public
	 * @since     0.9.6
	 * @return    void
	 */
	public function flush_rewrite_rules_event() {

		flush_rewrite_rules();
	}

	/**
	 * Register the CCB custom post types if enabled
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function initialize_custom_post_types() {
		$cpts = new CCB_Core_CPTs();
		$cpts->initialize();
	}

	/**
	 * Launches a synchronization from an ajax hook and will respond
	 * with a non-blocking ajax response
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function ajax_sync() {

		$nonce = $_POST['nextNonce'];

		if ( ! wp_verify_nonce( $nonce, $this->plugin_name . '-nonce' ) ) {
			wp_send_json( array('success' => false) );
		}

		// tell the user to move along and go about their business...
		$this->send_non_blocking_json_response( array( 'success' => true ) );

		$sync = new CCB_Core_Sync();
		$sync->sync();

	}

	/**
	 * Checks for an active synchronization from an ajax hook
	 * and responds with the transient value
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function ajax_poll_sync() {

		$nonce = $_POST['nextNonce'];
		if ( ! wp_verify_nonce( $nonce, $this->plugin_name . '-nonce' ) ) {
			wp_send_json( array('success' => false) );
		}

		$sync_in_progress = get_transient( $this->plugin_name . '-sync-in-progress' );
		wp_send_json( array( 'syncInProgress' => $sync_in_progress ) );

	}

	/**
	 * Gets the latest synchronization results from an ajax hook
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function ajax_get_latest_sync() {

		$nonce = $_POST['nextNonce'];
		if ( ! wp_verify_nonce( $nonce, $this->plugin_name . '-nonce' ) ) {
			wp_send_json( array('success' => false) );
		}

		$latest_sync = $this->get_latest_sync_results();
		wp_send_json( $latest_sync );

	}

	/**
	 * Checks the credentials for a user from an ajax hook
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function ajax_test_credentials() {

		$nonce = $_POST['nextNonce'];
		if ( ! wp_verify_nonce( $nonce, $this->plugin_name . '-nonce' ) ) {
			wp_send_json( array('success' => false) );
		}

		$sync = new CCB_Core_Sync();
		$validation_results = $sync->test_api_credentials();

		wp_send_json( $validation_results );

	}

	/**
	 * Create a helpful settings link on the plugin page
	 *
	 * @param array $links
	 * @access    public
	 * @since     0.9.0
	 * @return    array
	 */
	public function add_settings_link( $links ) {
		$links[] = '<a href="' . esc_url( get_admin_url( null, 'admin.php?page=' . $this->plugin_settings_name ) ) . '">Settings</a>';
		return $links;
	}

	/**
	 * Check if we should schedule a synchronization based on
	 * the options set by the user
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function check_auto_refresh() {

		$settings = get_option( $this->plugin_settings_name );

		if ( isset( $settings['auto-sync'] ) && $settings['auto-sync'] == 1 ) {
			$latest_sync = get_option( $this->plugin_name . '-latest-sync' );

			if ( ! empty( $latest_sync ) ) {
				$auto_sync_timeout = $settings['auto-sync-timeout'];
				$now = time();
				$diff = $now - $latest_sync['timestamp'];

				if ( $diff > $auto_sync_timeout * 60 ) {
					wp_schedule_single_event( time(), 'schedule_auto_refresh' );
				}

			}
			else {
				wp_schedule_single_event( time(), 'schedule_auto_refresh' );
			}
		}
	}

	/**
	 * Callback function to kick off a synchronization
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function auto_sync() {
		$sync = new CCB_Core_Sync();
		$sync->sync();
	}

	/**
	 * Register the stylesheets for the dashboard.
	 *
	 * @since    0.9.0
	 */
	public function enqueue_styles( $hook ) {

		if ( stristr( $hook, $this->plugin_settings_name ) !== false ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ccb-core-admin.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'switchery', plugin_dir_url( __FILE__ ) . 'css/vendor/switchery.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'powerange', plugin_dir_url( __FILE__ ) . 'css/vendor/powerange.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'picker', plugin_dir_url( __FILE__ ) . 'css/vendor/default.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'picker-date', plugin_dir_url( __FILE__ ) . 'css/vendor/default.date.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'tipr', plugin_dir_url( __FILE__ ) . 'css/vendor/tipr.css', array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the scripts for the dashboard.
	 *
	 * @since    0.9.0
	 */
	public function enqueue_scripts( $hook ) {

		if ( stristr( $hook, $this->plugin_settings_name ) !== false ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ccb-core-admin.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'switchery', plugin_dir_url( __FILE__ ) . 'js/vendor/switchery.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'powerange', plugin_dir_url( __FILE__ ) . 'js/vendor/powerange.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'picker', plugin_dir_url( __FILE__ ) . 'js/vendor/picker.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'picker-date', plugin_dir_url( __FILE__ ) . 'js/vendor/picker.date.js', array( 'picker' ), $this->version, false );
			wp_enqueue_script( 'tipr', plugin_dir_url( __FILE__ ) . 'js/vendor/tipr.min.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( $this->plugin_name, strtoupper( $this->plugin_settings_name ), array(
				'nextNonce' => wp_create_nonce( $this->plugin_name . '-nonce' ))
			);
		}

	}

}
