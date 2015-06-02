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
	 * Initialize the ReduxFramework
	 *
	 * @since    0.9.0
	 */
	public function initialize_redux() {

		$redux_options = new CCB_Core_Redux_Config();
		$redux_options->initialize();

	}

	/**
	 * Removes the built in Redux menu so as not to confuse users
	 *
	 * @since    0.9.0
	 */
	public function remove_redux_menu() {

		remove_submenu_page('tools.php','redux-about');

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
	 * Decrypts the password and sets its plain text value
	 * into the field before the form is rendered
	 *
	 * @param object $redux_object
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function redux_before_form_render( $redux_object ) {
		$redux_object->parent->options['password']['password'] = $this->decrypt( $redux_object->parent->options['password']['password'] );
		$this->refresh_test_login_wrapper( $redux_object->parent->options );
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
		$links[] = '<a href="' . esc_url( get_admin_url( null, 'options-general.php?page=' . $this->plugin_name ) ) . '">Settings</a>';
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

		global ${$this->plugin_options_name};
		$options = ${$this->plugin_options_name};

		if ( $options['auto-sync'] == 1 ) {
			$latest_sync = get_option( $this->plugin_name . '-latest-sync' );

			if ( ! empty( $latest_sync ) ) {
				$auto_sync_timeout = $options['auto-sync-timeout'];
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

		if ( $hook == "settings_page_{$this->plugin_name}" ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ccb-core-admin.css', array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the scripts for the dashboard.
	 *
	 * @since    0.9.0
	 */
	public function enqueue_scripts( $hook ) {

		if ( $hook == "settings_page_{$this->plugin_name}" ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ccb-core-admin.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( $this->plugin_name, strtoupper( $this->plugin_options_name ), array(
				'nextNonce' => wp_create_nonce( $this->plugin_name . '-nonce' ))
			);
		}

	}

	/**
	 * Handles actions we wish to run after the form is saved
	 *
	 * @param array $value
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function redux_after_form_save( $value ) {
		$this->refresh_test_login_wrapper( $value );
	}

	/**
	 * Checks if the credentials are at least entered
	 * and shows/hides the test button after save
	 *
	 * @param    array    $value
	 * @access   protected
	 * @since    0.9.0
	 * @return   void
	 */
	protected function refresh_test_login_wrapper( $value ) {

		if ( empty( $value['subdomain'] ) || empty( $value['password']['password'] ) || empty( $value['password']['username'] ) ) {
			echo <<<HTML
				<style>
				.ccb-core-test-login {
					display: none;
				}
				</style>
HTML;
		}
		else {
			echo <<<HTML
				<style>
				.ccb-core-test-login {
					display: table-row;
				}
				</style>
HTML;
		}
	}

}
