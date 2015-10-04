<?php
/**
 * Parent class for all plugin files
 *
 * @link       http://jaredcobb.com/ccb-core
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Parent class used to store helpful properties
 * and define some helpful utility methods
 *
 * @since      0.9.0
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Plugin {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The settings variable name used to access the plugin settings
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $plugin_settings_name
	 */
	protected $plugin_settings_name;

	/**
	 * The display name of this plugin.
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $plugin_display_name    The display name of this plugin.
	 */
	protected $plugin_display_name;

	/**
	 * The short display name of this plugin.
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $plugin_short_display_name    The short display name of this plugin.
	 */
	protected $plugin_short_display_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core properties of the plugin
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 *
	 * @since    0.9.0
	 */
	public function __construct() {

		$this->plugin_name = 'ccb-core';
		$this->plugin_settings_name = 'ccb_core_settings';
		$this->plugin_display_name = __( 'Church Community Builder Core API', $this->plugin_name );
		$this->plugin_short_display_name = __( 'CCB Core API', $this->plugin_name );
		$this->version = '0.9.6';
		add_theme_support( 'post-thumbnails' );

	}

	/**
	 * Encrypts and base64_encodes a string safe for serialization in WordPress
	 *
	 * @since     0.9.0
	 * @access    protected
	 * @param     string    $data
	 * @return    string
	 */
	protected function encrypt( $data ) {

		$encrypted_value = false;
		$key = wp_salt() . md5( $this->plugin_name );

		if ( ! empty( $data ) ) {
			try {
				$e = new CCB_Core_Vendor_Encryption( MCRYPT_BlOWFISH, MCRYPT_MODE_CBC );
				$encrypted_value = base64_encode( $e->encrypt( $data, $key ) );
			}
			catch ( Exception $ex ) {
				// TODO: Better exception handling
			}

		}

		return $encrypted_value;
	}

	/**
	 * Decrypts and base64_decodes a string
	 *
	 * @since     0.9.0
	 * @access    protected
	 * @param     string    $data
	 * @return    string
	 */
	protected function decrypt( $data ) {

		$decrypted_value = false;
		$key = wp_salt() . md5( $this->plugin_name );

		if ( ! empty( $data ) ) {
			try {
				$e = new CCB_Core_Vendor_Encryption( MCRYPT_BlOWFISH, MCRYPT_MODE_CBC );
				$decrypted_value = $e->decrypt( base64_decode( $data ), $key );
			}
			catch ( Exception $ex ) {
				// TODO: Better exception handling
			}

		}

		return $decrypted_value;
	}

	/**
	 * Responds to the client with a json response
	 * but allows the script to continue
	 *
	 * @param     array    $response
	 * @access    protected
	 * @since     0.9.0
	 * @return    bool
	 */
	protected function send_non_blocking_json_response( $response ) {

		ignore_user_abort(true);
		ob_start();

		header( 'Content-Type: application/json' );

		echo json_encode( $response );

		header( 'Connection: close' );
		header( 'Content-Length: ' . ob_get_length() );

		ob_end_flush();
		ob_flush();
		flush();

		return true;

	}

	/**
	 * Helper function to check if a date is valid
	 *
	 * @param     string    $date
	 * @param     string    $format
	 * @access    protected
	 * @since     0.9.0
	 * @return    bool
	 */
	protected function valid_date( $date, $format = 'Y-m-d H:i:s' ) {
		$version = explode('.', phpversion());
		if ( (int) $version[0] >= 5 && (int) $version[1] >= 2 && (int) $version[2] > 17 ) {
			$d = DateTime::createFromFormat( $format, $date );
		} else {
			$d = new DateTime( date( $format, strtotime( $date ) ) );
		}
		return $d && $d->format( $format ) == $date;
	}

	/**
	 * Gets the most recent synchronization results in the form
	 * of an array with a style class and message
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    array
	 */
	protected function get_latest_sync_results() {

		$latest_sync = get_option( $this->plugin_name . '-latest-sync' );

		if ( is_array( $latest_sync ) && ! empty( $latest_sync ) ) {

			if ( $latest_sync['success'] == true ) {

				$latest_sync_message = array(
					'style' => 'updated',
					'description' => $latest_sync['message'],
				);

			}
			else {
				$latest_sync_message = array(
					'style' => 'error',
					'description' => $latest_sync['message'],
				);
			}
		}
		else {
			$latest_sync_message = array(
				'style' => 'notice',
				'description' => "It looks like you haven't synchronized anything yet."
			);
		}

		return $latest_sync_message;

	}

}

