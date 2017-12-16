<?php
/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       https://www.wpccb.com
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

}
