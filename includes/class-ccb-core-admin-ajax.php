<?php
/**
 * AJAX callbacks for the dashboard functionality
 *
 * @link       https://www.wpccb.com
 * @since      1.0.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 */

/**
 * AJAX callbacks for the dashboard functionality
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Admin_AJAX {

	/**
	 * An instance of the CCB_Core_API class
	 *
	 * @var CCB_Core_API
	 */
	private $api;

	/**
	 * An instance of the CCB_Core_Synchronizer class
	 *
	 * @var CCB_Core_Synchronizer
	 */
	private $synchronizer;

	/**
	 * Initialize the class and register hooks
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_sync', array( $this, 'ajax_sync' ) );
		add_action( 'wp_ajax_poll_sync', array( $this, 'ajax_poll_sync' ) );
		add_action( 'wp_ajax_test_credentials', array( $this, 'ajax_test_credentials' ) );
		add_action( 'wp_ajax_get_latest_sync', array( $this, 'ajax_get_latest_sync' ) );

		$this->api = new CCB_Core_API();
		$this->synchronizer = new CCB_Core_Synchronizer();
	}

	/**
	 * Launches a synchronization from an ajax hook and will respond
	 * with a non-blocking ajax response
	 *
	 * @access    public
	 * @since     1.0.0
	 * @return    void
	 */
	public function ajax_sync() {

		check_ajax_referer( 'ccb_core_nonce', 'nonce' );
		$this->synchronizer->synchronize();

		// Tell the user to move along and go about their business...
		CCB_Core_Helpers::instance()->send_non_blocking_json_success();

	}

	/**
	 * Checks for an active synchronization from an ajax hook
	 * and responds with the transient value
	 *
	 * @access    public
	 * @since     1.0.0
	 * @return    void
	 */
	public function ajax_poll_sync() {

		check_ajax_referer( 'ccb_core_nonce', 'nonce' );

		//$sync_in_progress = get_transient( $this->plugin_name . '-sync-in-progress' );
		//wp_send_json( array( 'syncInProgress' => $sync_in_progress ) );

	}

	/**
	 * Gets the latest synchronization results from an ajax hook
	 *
	 * @access    public
	 * @since     1.0.0
	 * @return    void
	 */
	public function ajax_get_latest_sync() {

		check_ajax_referer( 'ccb_core_nonce', 'nonce' );

		//$latest_sync = $this->get_latest_sync_results();
		//wp_send_json( $latest_sync );

	}

	/**
	 * Checks the credentials for a user from an ajax hook
	 *
	 * @access    public
	 * @since     1.0.0
	 * @return    void
	 */
	public function ajax_test_credentials() {
		check_ajax_referer( 'ccb_core_nonce', 'nonce' );
		$response = $this->api->get( 'api_status' );

		if ( 'SUCCESS' === $response['status'] ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( $response['error'] );
		}
	}

	/**
	 * Check if we should schedule a synchronization based on
	 * the options set by the user
	 *
	 * @access    public
	 * @since     1.0.0
	 * @return    void
	 */
	public function check_auto_refresh() {

		$settings = get_option( $this->plugin_settings_name );

		if ( isset( $settings['auto_sync'] ) && 1 === $settings['auto_sync'] ) {
			$latest_sync = get_option( $this->plugin_name . '-latest-sync' );

			if ( ! empty( $latest_sync ) ) {
				$auto_sync_timeout = $settings['auto_sync_timeout'];
				$now = time();
				$diff = $now - $latest_sync['timestamp'];

				if ( $diff > $auto_sync_timeout * 60 ) {
					wp_schedule_single_event( time(), 'schedule_auto_refresh' );
				}

			} else {
				wp_schedule_single_event( time(), 'schedule_auto_refresh' );
			}
		}
	}

	/**
	 * Callback function to kick off a synchronization
	 *
	 * @access    public
	 * @since     1.0.0
	 * @return    void
	 */
	public function auto_sync() {
		$sync = new CCB_Core_Sync();
		$sync->sync();
	}

}

new CCB_Core_Admin_AJAX();
